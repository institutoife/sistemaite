<?php

namespace App\Services;

use App\Models\GoogleContactAccount;
use App\Models\GoogleContactBulkRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class GoogleContactsBulkService
{
    public function ready(): bool
    {
        return Schema::hasTable('google_contact_bulk_runs') && Schema::hasTable('google_contact_bulk_items');
    }

    private function candidates(GoogleContactsService $service, string $source, bool $includeSynced)
    {
        $query = $service->eligibleQuery($source)
            ->whereRaw("REPLACE(TRIM(COALESCE(telefono, '')), '0', '') <> ''")
            ->whereIn('gcontact_sync_status', $includeSynced ? ['idle', 'pending', 'synced'] : ['idle', 'pending']);
        return $includeSynced ? $query : $query->whereNull('gcontact_synced_at');
    }

    public function preview(GoogleContactsService $service): array
    {
        $counts = [];
        foreach (array_keys(GoogleContactsService::SOURCES) as $source) {
            $counts[$source] = ['new' => $this->candidates($service, $source, false)->count(),
                'all' => $this->candidates($service, $source, true)->count()];
        }
        return $counts;
    }

    public function start(GoogleContactsService $service, int $actor, string $source, bool $includeSynced, int $limit): GoogleContactBulkRun
    {
        return DB::transaction(function () use ($service, $actor, $source, $includeSynced, $limit) {
            $account = GoogleContactAccount::where('slot', 'central')->lockForUpdate()->first();
            if (!$account || !$account->isConnected()) {
                throw ValidationException::withMessages(['bulk' => 'Conecte primero la cuenta central de Google.']);
            }
            if ($active = GoogleContactBulkRun::whereNotNull('active_slot')->first()) {
                if (!$this->progress($active)['finished']) {
                    return $active; // Concurrent clicks resume the same run, without resetting people's revisions.
                }
                $active->update(['status' => 'completed', 'active_slot' => null, 'finished_at' => now()]);
            }
            $max = [];
            $count = 0;
            foreach ($source === 'all' ? array_keys(GoogleContactsService::SOURCES) : [$source] as $kind) {
                $query = $this->candidates($service, $kind, $includeSynced);
                $max[$kind] = (int) (clone $query)->max('id');
                $count += (clone $query)->count();
            }
            if (!$count) {
                throw ValidationException::withMessages(['bulk' => 'No hay contactos con teléfono que coincidan con esta selección.']);
            }
            return GoogleContactBulkRun::create(['active_slot' => 'central', 'account_id' => $account->id,
                'created_by' => $actor, 'source' => $source, 'include_synced' => $includeSynced,
                'requested_limit' => $limit, 'max_ids' => $max, 'cursors' => array_fill_keys(array_keys($max), 0),
                'candidate_count' => $limit ? min($count, $limit) : $count]);
        });
    }

    public function progress(GoogleContactBulkRun $run): array
    {
        $totals = ['synced' => 0, 'errors' => 0, 'removed' => 0];
        $photoWarnings = 0;
        foreach (GoogleContactsService::SOURCES as $source => $class) {
            $table = (new $class)->getTable();
            $removed = 'contact.id IS NULL'.($source === 'comentario' || ($source === 'prospecto' && Schema::hasColumn($table,'persona_id')) ? ' OR contact.persona_id IS NOT NULL' : '');
            $row = DB::table('google_contact_bulk_items as item')->leftJoin($table.' as contact', 'contact.id', '=', 'item.record_id')
                ->where('item.run_id', $run->id)->where('item.source', $source)
                ->selectRaw("SUM(CASE WHEN $removed THEN 1 ELSE 0 END) AS removed,
                    SUM(CASE WHEN NOT ($removed) AND contact.gcontact_sync_status = 'synced'
                        AND contact.gcontact_sync_pending = 0 AND contact.gcontact_revision >= item.revision THEN 1 ELSE 0 END) AS synced,
                    SUM(CASE WHEN NOT ($removed) AND contact.gcontact_sync_status = 'error' THEN 1 ELSE 0 END) AS errors,
                    SUM(CASE WHEN NOT ($removed) AND contact.gcontact_sync_status = 'synced'
                        AND contact.gcontact_sync_pending = 0 AND contact.gcontact_revision >= item.revision
                        AND contact.gcontact_error_code IN ('invalid_photo', 'photo_extension') THEN 1 ELSE 0 END) AS photo_warnings")->first();
            foreach (array_keys($totals) as $key) { $totals[$key] += (int) $row->$key; }
            $photoWarnings += (int) $row->photo_warnings;
        }
        $totals['pending'] = max(0, $run->queued - array_sum($totals));
        $totals['photo_warnings'] = $photoWarnings;
        $totals['finished'] = $run->status !== 'preparing' && $totals['pending'] === 0;
        return $totals;
    }

    public function advance(GoogleContactsService $service): void
    {
        $account = $service->account();
        if (!$this->ready() || !$account || !$account->isConnected() || $account->isPaused()) { return; }
        DB::transaction(function () use ($service) {
            $run = GoogleContactBulkRun::whereNotNull('active_slot')->lockForUpdate()->first();
            if (!$run || (int) $service->account()->id !== (int) $run->account_id) { return; }
            $progress = $this->progress($run);
            if ($progress['finished']) {
                $run->update(['status' => 'completed', 'active_slot' => null, 'finished_at' => now()]);
                return;
            }
            if ($run->status !== 'preparing') { return; }
            // Leave room for normal daily updates. Never fill the whole queue with historical imports.
            $busy = DB::table('jobs')->where('queue', 'google-contacts')
                ->where(function ($query) { $query->whereNotNull('reserved_at')->orWhere('available_at', '<=', now()->timestamp); })->count();
            $capacity = max(0, max(1, min(50, (int) config('google-contacts.cron_max_jobs', 3))) - $busy);
            if (!$capacity) { return; }
            $cursors = $run->cursors;
            $examined = 0;
            foreach ($run->max_ids as $source => $maxId) {
                $records = $this->candidates($service, $source, $run->include_synced)->where('id', '>', $cursors[$source])
                    ->where('id', '<=', $maxId)->orderBy('id')->limit(100 - $examined)->lockForUpdate()->get();
                foreach ($records as $record) {
                    $cursors[$source] = $record->id;
                    $run->scanned++;
                    $examined++;
                    if (!app(TelefonoService::class)->normalize($record->telefono)) {
                        $run->skipped++;
                    } else {
                        $service->markPending($record, $run->created_by);
                        DB::table('google_contact_bulk_items')->insert(['run_id' => $run->id, 'source' => $source,
                            'record_id' => $record->id, 'revision' => $record->fresh()->gcontact_revision]);
                        $run->queued++;
                        $capacity--;
                    }
                    if (!$capacity || $examined >= 100 || ($run->requested_limit && $run->queued >= $run->requested_limit)) { break 2; }
                }
                $cursors[$source] = $maxId;
            }
            $run->cursors = $cursors;
            if ($cursors === $run->max_ids || ($run->requested_limit && $run->queued >= $run->requested_limit)) {
                $run->status = 'queued';
            }
            $run->save();
        });
    }
}

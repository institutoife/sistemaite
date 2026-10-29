<?php

namespace App\Console\Commands;

use App\Exceptions\GoogleContactsException;
use App\Services\GoogleContactProfile;
use App\Services\GoogleContactsService;
use Illuminate\Console\Command;

class SyncGoogleContactsBatch extends Command
{
    protected $signature = 'gcontact:sync-batch
        {--batch=1000 : Máximo de contactos a encolar}
        {--only-pending : Solo pendientes; no reintentar errores definitivos}
        {--only-photo-errors : Reprocesar solo Personas bloqueadas por errores de fotografía}
        {--dry-run : Revisar la selección sin modificar ni encolar contactos}
        {--source=persona : Origen: persona, prospecto o comentario}
        {--from-id=0 : Procesar desde este ID}';

    protected $description = 'Encola contactos IFE sin realizar llamadas a Google dentro del comando.';

    public function handle(GoogleContactsService $service): int
    {
        $source = (string) $this->option('source');
        if (!isset(GoogleContactsService::SOURCES[$source])) {
            $this->error('Origen válido: persona, prospecto o comentario.');
            return self::FAILURE;
        }
        if ($this->option('only-photo-errors') && ($source !== 'persona' || $this->option('only-pending'))) {
            $this->error('Use --only-photo-errors únicamente con --source=persona y sin --only-pending.');
            return self::FAILURE;
        }
        if (!$service->ready() || (!$this->option('dry-run') && (!$service->account() || !$service->account()->isConnected()))) {
            $this->error('Aplique las migraciones y conecte Google Contacts desde el panel.');
            return self::FAILURE;
        }
        if (!$this->option('dry-run') && $service->account()->isPaused()) {
            $this->info('Sincronización pausada por un administrador. Se conservan los pendientes.');
            return self::SUCCESS;
        }
        $remaining = max(1, min(10000, (int) $this->option('batch')));
        $query = $service->eligibleQuery($source)->where('id', '>=', max(0, (int) $this->option('from-id')));
        if ($this->option('only-pending')) {
            $query->where('gcontact_sync_pending', true)->whereIn('gcontact_sync_status', ['pending', 'idle']);
        }
        if ($this->option('only-photo-errors')) {
            $query->where('gcontact_sync_status', 'error')->whereIn('gcontact_error_code', ['invalid_photo', 'photo_extension']);
        }
        $count = 0;
        $examined = 0;
        $photoWarnings = 0;
        $lastId = null;
        $query->chunkById(100, function ($personas) use ($service, $source, &$remaining, &$count, &$examined, &$photoWarnings, &$lastId) {
            foreach ($personas as $persona) {
                $examined++;
                $lastId = $persona->id;
                if ($this->option('only-photo-errors')) {
                    try { app(GoogleContactProfile::class)->photo($persona); }
                    catch (GoogleContactsException $exception) { $photoWarnings++; }
                }
                if (!$this->option('dry-run')) {
                    if ($this->option('only-pending')) { $service->enqueue($persona->id, null, $source); }
                    else { $service->markPending($persona); }
                }
                $count++;
                $remaining--;
                if ($remaining === 0) {
                    return false;
                }
            }
        });
        $this->info(($this->option('dry-run') ? 'Comprobación sin cambios. Contactos preparados: ' : 'Contactos encolados: ').$count.'.');
        $this->line('Revisados: '.$examined.'. Fotografías que aún requieren atención: '.$photoWarnings.'.');
        if ($photoWarnings) { $this->line('Esas fichas sincronizarán sus datos con un aviso de fotografía, conservando la imagen existente en Google.'); }
        if ($lastId !== null) { $this->line('Para continuar con los siguientes registros: --from-id='.($lastId + 1)); }
        if (!$this->option('dry-run')) { $this->line('La cola Google procesará los contactos con el Cron o worker existente.'); }
        return self::SUCCESS;
    }
}

<?php

namespace App\Jobs;

use App\Exceptions\GoogleContactsException;
use App\Services\GoogleContactsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncGoogleContact implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $personaId;
    public $actorId;
    // Default keeps already queued/serialized Persona jobs compatible.
    public $source = 'persona';
    public $tries = 5;
    public $timeout = 150;
    public $uniqueFor = 7200;

    public function __construct(int $personaId, ?int $actorId = null, string $source = 'persona')
    {
        $this->personaId = $personaId;
        $this->actorId = $actorId;
        $this->source = $source;
        $this->onConnection('google_contacts');
        $this->onQueue('google-contacts');
    }

    public function uniqueId(): string
    {
        return 'gcontact:'.$this->source.':'.$this->personaId;
    }

    public function backoff(): array
    {
        return [60, 180, 600, 1800];
    }

    public function handle(GoogleContactsService $service): void
    {
        $model = $service->modelClass($this->source);
        $revision = $model::whereKey($this->personaId)->value('gcontact_revision');
        try {
            $service->sync($this->personaId, $this->actorId, $this->source);
        } catch (Throwable $e) {
            // Never persist HTTP exception bodies, tokens, credential URLs or stack traces.
            $error = $e instanceof GoogleContactsException ? $e : new GoogleContactsException(
                'local_error', 'No se pudo completar la sincronización. Revise la configuración y vuelva a intentarlo.');
            if ($error->reason === 'paused') {
                // A legacy worker may reserve a job just as an administrator pauses the account.
                // Leave it pending for resume, without consuming its retry budget or marking an error.
                $model::whereKey($this->personaId)->where('gcontact_revision', $revision)->update([
                    'gcontact_sync_pending' => true, 'gcontact_sync_status' => 'pending',
                    'gcontact_error_code' => null, 'gcontact_sync_error' => null,
                ]);
                return;
            }
            $model::whereKey($this->personaId)->where('gcontact_revision', $revision)->update([
                'gcontact_sync_pending' => true, 'gcontact_sync_status' => $error->retryable ? 'retrying' : 'error',
                'gcontact_error_code' => $error->reason, 'gcontact_sync_error' => $error->getMessage(),
            ]);
            Log::warning('Sincronización Google pendiente', ['source' => $this->source, 'record_id' => $this->personaId, 'code' => $error->reason]);
            if ($error->retryable) {
                throw $error;
            }
        }
    }

    public function failed(Throwable $exception): void
    {
        $model = app(GoogleContactsService::class)->modelClass($this->source);
        $model::whereKey($this->personaId)->whereIn('gcontact_sync_status', ['retrying', 'processing'])->update([
            'gcontact_sync_status' => 'error', 'gcontact_sync_pending' => true,
            'gcontact_sync_error' => 'Se agotaron los reintentos. Revise el contacto antes de reintentar.',
        ]);
    }
}

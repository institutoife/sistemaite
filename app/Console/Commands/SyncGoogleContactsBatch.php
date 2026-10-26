<?php

namespace App\Console\Commands;

use App\Services\GoogleContactsService;
use Illuminate\Console\Command;

class SyncGoogleContactsBatch extends Command
{
    protected $signature = 'gcontact:sync-batch
        {--batch=1000 : Máximo de contactos a encolar}
        {--only-pending : Solo pendientes; no reintentar errores definitivos}
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
        if (!$service->ready() || !$service->account() || !$service->account()->isConnected()) {
            $this->error('Aplique las migraciones y conecte Google Contacts desde el panel.');
            return self::FAILURE;
        }
        if ($service->account()->isPaused()) {
            $this->info('Sincronización pausada por un administrador. Se conservan los pendientes.');
            return self::SUCCESS;
        }
        $remaining = max(1, min(10000, (int) $this->option('batch')));
        $query = $service->eligibleQuery($source)->where('id', '>=', max(0, (int) $this->option('from-id')));
        if ($this->option('only-pending')) {
            $query->where('gcontact_sync_pending', true)->whereIn('gcontact_sync_status', ['pending', 'idle']);
        }
        $count = 0;
        $query->chunkById(100, function ($personas) use ($service, $source, &$remaining, &$count) {
            foreach ($personas as $persona) {
                if ($this->option('only-pending')) {
                    $service->enqueue($persona->id, null, $source);
                } else {
                    $service->markPending($persona);
                }
                $remaining--;
                $count++;
                if ($remaining === 0) {
                    return false;
                }
            }
        });
        $this->info('Contactos encolados: '.$count.'. Ejecute el worker google_contacts.');
        return self::SUCCESS;
    }
}

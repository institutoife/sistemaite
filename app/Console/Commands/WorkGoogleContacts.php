<?php

namespace App\Console\Commands;

use App\Services\GoogleContactsRuntime;
use App\Services\GoogleContactsService;
use Illuminate\Console\Command;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\Event;

class WorkGoogleContacts extends Command
{
    protected $signature = 'gcontact:work
        {--once : Comprobar y procesar como máximo un trabajo}
        {--cron : Procesar hasta tres trabajos y salir para la siguiente ejecución programada}';
    protected $description = 'Mantiene un único proceso central de contactos, con pausa global y señal de actividad.';

    public function handle(GoogleContactsService $service, GoogleContactsRuntime $runtime): int
    {
        if ($this->option('once') && $this->option('cron')) {
            $this->error('Use --once o --cron, sin combinarlos.');
            return self::FAILURE;
        }
        $cron = (bool) $this->option('cron');
        // An OS lock survives cache clearing and is released if this process crashes.
        $lock = @fopen(storage_path('framework/google-contacts-worker.lock'), 'c');
        if (!$lock) {
            $this->error('No se pudo abrir el bloqueo del proceso de contactos. Revise permisos de storage.');
            return self::FAILURE;
        }
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            $this->info('El proceso central de Google Contacts ya está iniciado.');
            return self::SUCCESS;
        }
        $active = true;
        $completed = false;
        $runtime->setMode($cron ? 'cron' : 'worker');
        try {
            $canWork = $runtime->tick($service);
            if (($this->option('once') || $cron) && !$canWork) {
                $completed = true;
                return self::SUCCESS;
            }
            if (!$this->option('once')) {
                Event::listen(Looping::class, function ($event) use ($runtime, $service, &$active) {
                    if ($active && $event->connectionName === 'google_contacts') {
                        return $runtime->tick($service) ? null : false;
                    }
                });
            }
            $options = [
                'connection' => 'google_contacts', '--queue' => 'google-contacts',
                '--name' => 'ife-google-contacts', '--sleep' => ($this->option('once') || $cron) ? 0 : 3,
                '--tries' => 5, '--timeout' => 150, '--once' => (bool) $this->option('once'),
            ];
            if ($cron) {
                // Limits apply between jobs: let a started contact finish within its HTTP limits.
                $options += ['--stop-when-empty' => true, '--max-jobs' => 3, '--max-time' => 50];
            }
            $result = $this->call('queue:work', $options);
            $completed = $result === self::SUCCESS;
            return $result;
        } finally {
            $active = false;
            $runtime->finish($completed);
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}

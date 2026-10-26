<?php

namespace App\Console\Commands;

use App\Services\GoogleContactsMigrationRunner;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class MigrateGoogleContacts extends Command
{
    use ConfirmableTrait;

    protected $signature = 'gcontact:migrate {--force : Ejecutar las migraciones de Google en producción}';
    protected $description = 'Aplica las migraciones Google conservando fechas heredadas y recuperando la ampliación de Persona interrumpida.';

    public function handle(GoogleContactsMigrationRunner $runner): int
    {
        if (!$this->confirmToProceed()) {
            return self::FAILURE;
        }
        $lock = @fopen(storage_path('framework/google-contacts-migrate.lock'), 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            if ($lock) { fclose($lock); }
            $this->error('No se pudo reservar la migración Google. Compruebe permisos y que no haya otra ejecución.');
            return self::FAILURE;
        }
        try {
            return $runner->run(function () {
                return $this->call('migrate', ['--force' => (bool) $this->option('force'), '--path' => GoogleContactsMigrationRunner::PATHS]);
            }, function ($message) { $this->info($message); });
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}

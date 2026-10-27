<?php

namespace App\Console\Commands;

use App\Models\GoogleContactAccount;
use App\Services\GoogleContactsRuntime;
use App\Services\GoogleContactsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CheckGoogleContacts extends Command
{
    protected $signature = 'gcontact:check
        {--production : Exigir la configuración de ife.bo en BanaHosting}
        {--allow-disconnected : Permitir la primera instalación antes de conectar OAuth}';
    protected $description = 'Comprueba configuración y esquema sin modificar datos ni llamar a Google.';

    public function handle(GoogleContactsService $service, GoogleContactsRuntime $runtime): int
    {
        $errors = 0;
        $rows = [];
        $check = function (string $name, bool $ok, string $instruction) use (&$errors, &$rows) {
            $rows[] = [$ok ? 'OK' : 'FALTA', $name, $ok ? 'Comprobado' : $instruction];
            $errors += $ok ? 0 : 1;
        };
        $this->line('PHP CLI '.PHP_VERSION.' · '.PHP_OS_FAMILY.'. No se realizarán cambios ni solicitudes externas.');
        $check('Laravel compatible', strpos(app()->version(), '8.') === 0 && version_compare(app()->version(), '8.83.27', '>='),
            'Esta entrega fue probada con Laravel 8.83.27. Revisar composer.lock y dependencias del servidor.');
        foreach (['curl', 'openssl', 'mbstring', 'fileinfo', 'gd'] as $extension) {
            $check('Extensión '.$extension, extension_loaded($extension), 'Habilitar esta extensión en el PHP CLI y web del sitio.');
        }
        foreach (['storage', 'storage/framework', 'storage/framework/cache', 'storage/logs', 'bootstrap/cache'] as $directory) {
            $check('Permisos '.$directory, is_dir(base_path($directory)) && is_writable(base_path($directory)), 'Corregir permisos de escritura para la cuenta del sitio.');
        }
        $check('Disco de fotos local', config('filesystems.disks.public.driver') === 'local',
            'Conservar el disco public local utilizado por las fotografías de Persona.');
        try {
            $encrypted = app('encrypter')->encryptString('ife-deployment-check');
            $validKey = app('encrypter')->decryptString($encrypted) === 'ife-deployment-check';
        } catch (Throwable $exception) {
            $validKey = false;
        }
        $check('Clave de cifrado', $validKey, 'Conservar APP_KEY del servidor; no generar otra sobre tokens existentes.');
        $check('Cliente OAuth', (bool) config('services.google.client_id') && (bool) config('services.google.client_secret'), 'Configurar las credenciales del cliente web en el entorno del servidor.');
        $queue = config('queue.connections.google_contacts', []);
        $check('Cola dedicada', ($queue['driver'] ?? '') === 'database' && ($queue['queue'] ?? '') === 'google-contacts'
            && ($queue['retry_after'] ?? 0) > 150, 'Usar la conexión dedicada de base de datos y retry_after mayor de 150 segundos.');
        if ($this->option('production')) {
            $check('Entorno de producción', app()->environment('production') && config('app.debug') === false,
                'Configurar APP_ENV=production y APP_DEBUG=false.');
            $check('URL de ife.bo', rtrim((string) config('app.url'), '/') === 'https://ife.bo', 'Configurar APP_URL=https://ife.bo.');
            $check('Retorno OAuth', config('services.google.redirect') === 'https://ife.bo/auth/google/callback',
                'Configurar GOOGLE_REDIRECT=https://ife.bo/auth/google/callback y registrar esa URL en Google Cloud.');
            $check('Sesión HTTPS', config('session.secure') === true, 'Configurar SESSION_SECURE_COOKIE=true.');
            $check('Modo Cron', config('google-contacts.process_mode') === 'cron', 'Configurar GOOGLE_CONTACTS_PROCESS_MODE=cron.');
            $check('Intervalo de Cron', (int) config('google-contacts.cron_interval_minutes') >= 1
                && (int) config('google-contacts.cron_interval_minutes') <= 60, 'Configurar el intervalo real de cPanel, entre 1 y 60 minutos.');
            $check('Caché persistente', in_array(config('cache.default'), ['file', 'database', 'redis', 'memcached'], true),
                'Usar un almacén persistente compatible con locks; para este servidor, CACHE_DRIVER=file.');
            $check('MySQL/MariaDB', config('database.default') === 'mysql' && extension_loaded('pdo_mysql'),
                'Configurar la base central MySQL/MariaDB del servidor y su extensión PDO.');
        }
        try {
            DB::connection()->getPdo();
            $check('Conexión a base de datos', true, '');
            $check('Migraciones Google', $service->ready(), 'Aplicar las migraciones incrementales indicadas en la guía.');
            $check('Verificación telefónica', app(\App\Services\PhoneVerificationService::class)->ready(), 'Aplicar la migración de verificación telefónica con gcontact:migrate.');
            $check('Lotes masivos', app(\App\Services\GoogleContactsBulkService::class)->ready(), 'Aplicar la migración de lotes masivos con gcontact:migrate.');
            $check('Tablas de cola', Schema::hasTable('jobs') && Schema::hasColumn('jobs', 'reserved_at'), 'Aplicar la migración existente de jobs si falta.');
            $check('Historial de acciones', Schema::hasTable('userables'), 'Revisar la base original y su migración de userables.');
            if ($service->ready()) {
                $account = GoogleContactAccount::where('slot', 'central')->first();
                $connected = $account && $account->isConnected();
                if ($this->option('allow-disconnected') && !$connected) {
                    $rows[] = ['PENDIENTE', 'Cuenta central', 'Conectar Google desde AdminLTE antes de activar el Cron.'];
                } else {
                    $check('Cuenta central y cifrado', (bool) $connected, 'Conectar/reconectar la cuenta central desde AdminLTE; conservar APP_KEY.');
                }
                if ($account && $account->isPaused()) {
                    $rows[] = ['AVISO', 'Sincronización pausada', 'Un administrador debe reanudarla cuando termine el despliegue.'];
                }
                foreach (array_keys(GoogleContactsService::SOURCES) as $source) {
                    $pending = $service->eligibleQuery($source)->where('gcontact_sync_pending', true)->count();
                    $rows[] = ['INFO', 'Pendientes '.$source, (string) $pending];
                }
            }
        } catch (Throwable $exception) {
            // Never render connection strings, encrypted tokens or exception bodies.
            $check('Base de datos o cifrado', false, 'Comprobar credenciales, esquema y APP_KEY sin sustituir los datos del servidor.');
        }
        try {
            $status = $runtime->status();
            $rows[] = [$status['healthy'] ? 'OK' : 'PENDIENTE', 'Actividad del proceso',
                $status['healthy'] ? 'Señal reciente de '.$status['mode'] : 'Comprobar después de la primera ejecución de Cron.'];
        } catch (Throwable $exception) {
            $check('Caché del proceso', false, 'Comprobar la configuración y disponibilidad del almacén de caché.');
        }
        $this->table(['Estado', 'Comprobación', 'Resultado / acción'], $rows);
        $this->line('Comprobar también composer check-platform-reqs --no-dev y las rutas, frecuencia y tiempo permitido por el alojamiento.');
        $this->line('Este comando no confirma la disponibilidad de Google ni que la tarea esté registrada en cPanel.');
        return $errors ? self::FAILURE : self::SUCCESS;
    }
}

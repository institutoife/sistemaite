<?php

// Local integration check. It never migrates the application database.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\GoogleContactsMigrationRunner;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

set_exception_handler(function (Throwable $exception) {
    fwrite(STDERR, 'Falló la comprobación local: '.$exception->getMessage().PHP_EOL);
    exit(1);
});

$default = config('database.default');
$connections = config('database.connections');
$adminConnection = DB::connection();
$config = $adminConnection->getConfig();
if (!app()->environment('local') || $adminConnection->getDriverName() !== 'mysql'
    || !in_array($config['host'], ['127.0.0.1', 'localhost'], true)) {
    throw new RuntimeException('Esta comprobación exige APP_ENV=local y MySQL local.');
}
$pdo = $adminConnection->getPdo();
$globalMode = $pdo->query('SELECT @@GLOBAL.sql_mode')->fetchColumn();
Http::fake(function () { throw new RuntimeException('HTTP externo bloqueado en esta comprobación.'); });
Queue::fake();
config(['cache.default' => 'array']);
$dates = ['1900-01-00', '0000-00-00', '2021-02-31', '2000-02-29'];
$strict = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION';
$results = [];
$assert = function ($condition, $message) { if (!$condition) { throw new RuntimeException($message); } };

foreach (['fresh', 'interrupted_fk', 'partial_columns', 'unlogged_complete', 'wrong_index', 'wrong_default'] as $scenario) {
    $database = 'ife_gcontact_migration_check_'.bin2hex(random_bytes(4));
    $assert((bool) preg_match('/^ife_gcontact_migration_check_[a-f0-9]{8}$/', $database), 'Nombre de base de prueba inválido.');
    $pdo->exec('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    try {
        $testConfig = $config;
        $testConfig['database'] = $database;
        unset($testConfig['url'], $testConfig['name'], $testConfig['read'], $testConfig['write']);
        // Remove access to application connections while any migration can run.
        // ConnectionFactory otherwise retains a copied config's original `name`.
        foreach (array_keys(DB::getConnections()) as $name) { DB::purge($name); }
        config(['database.connections' => ['google_migration_test' => $testConfig], 'database.default' => 'google_migration_test']);
        DB::purge('google_migration_test');
        Schema::clearResolvedInstance('db.schema');
        $connection = DB::connection();
        $assert($connection->getName() === 'google_migration_test' && $connection->getDatabaseName() === $database,
            'La conexión no está aislada en la base temporal.');
        app('migration.repository')->setSource('google_migration_test');
        $schema = $connection->getSchemaBuilder();
        $schema->create('migrations', function (Blueprint $table) {
            $table->increments('id'); $table->string('migration'); $table->integer('batch');
        });
        $schema->create('users', function (Blueprint $table) { $table->id(); });
        $schema->create('personas', function (Blueprint $table) {
            $table->id(); $table->date('fechanacimiento')->nullable();
            $table->string('telefono')->nullable(); $table->timestamps();
            $table->string('resourseName')->nullable(); $table->string('etag')->nullable();
            $table->boolean('gcontact_sync_pending')->default(false);
            $table->text('gcontact_sync_error')->nullable();
            $table->unsignedInteger('gcontact_sync_attempts')->default(0);
            $table->timestamp('gcontact_sync_last_attempt_at')->nullable();
        });
        foreach (['prospectos', 'comentarios'] as $tableName) {
            $schema->create($tableName, function (Blueprint $table) use ($tableName) {
                $table->id(); $table->string('telefono')->nullable(); $table->timestamps();
                if ($tableName === 'comentarios') { $table->unsignedBigInteger('persona_id')->nullable(); }
            });
        }
        $connection->statement('SET SESSION sql_mode = ?', [GoogleContactsMigrationRunner::ddlSqlMode($strict)]);
        foreach ($dates as $index => $date) {
            $connection->table('personas')->insert(['id' => $index + 1, 'fechanacimiento' => $date,
                'resourseName' => 'people/kept'.($index + 1), 'etag' => 'kept-etag']);
        }
        foreach (['prospectos', 'comentarios'] as $tableName) {
            $connection->table($tableName)->insert(['id' => 1, 'created_at' => '0000-00-00 00:00:00']);
        }
        foreach (array_slice(GoogleContactsMigrationRunner::PATHS, 0, 2) as $path) {
            $connection->table('migrations')->insert(['migration' => basename($path, '.php'), 'batch' => 1]);
        }
        if ($scenario !== 'fresh') {
            require_once database_path('migrations/2026_10_25_000001_create_google_contact_accounts_table.php');
            (new CreateGoogleContactAccountsTable)->up();
            $connection->table('migrations')->insert(['migration' => '2026_10_25_000001_create_google_contact_accounts_table', 'batch' => 1]);
            if (in_array($scenario, ['partial_columns', 'wrong_default'], true)) {
                $schema->table('personas', function (Blueprint $table) use ($scenario) {
                    $table->unsignedBigInteger('gcontact_account_id')->nullable();
                    $table->string('gcontact_sync_status', 20)->default($scenario === 'wrong_default' ? 'unexpected' : 'idle');
                });
            } else {
                require_once database_path('migrations/2026_10_25_000002_extend_personas_google_contacts.php');
                if ($scenario === 'unlogged_complete') {
                    (new ExtendPersonasGoogleContacts)->up();
                } else {
                    $connection->statement('SET SESSION sql_mode = ?', [$strict]);
                    $failed = false;
                    try { (new ExtendPersonasGoogleContacts)->up(); }
                    catch (Illuminate\Database\QueryException $exception) { $failed = (int) ($exception->errorInfo[1] ?? 0) === 1292; }
                    $assert($failed, 'No se reprodujo el fallo 1292 de la migración original.');
                }
            }
        }
        if ($scenario === 'wrong_index') {
            $schema->table('personas', function (Blueprint $table) { $table->index(['id'], 'personas_gcontact_pending_index'); });
        }
        $connection->statement('SET SESSION sql_mode = ?', [$strict]);
        $original = $connection->selectOne('SELECT @@SESSION.sql_mode AS mode', [], false)->mode;
        $before = $connection->select('SELECT id, CAST(fechanacimiento AS CHAR) AS birth, resourseName, etag FROM personas ORDER BY id');
        $failure = null;
        try { $exit = Artisan::call('gcontact:migrate', ['--force' => true]); }
        catch (RuntimeException $exception) { $failure = $exception; }
        $assert($connection->selectOne('SELECT @@SESSION.sql_mode AS mode', [], false)->mode === $original, 'No se restauró SQL_MODE.');
        $assert($connection->select('SELECT id, CAST(fechanacimiento AS CHAR) AS birth, resourseName, etag FROM personas ORDER BY id') == $before,
            'Se modificaron las fechas o los enlaces previos.');
        if (in_array($scenario, ['wrong_index', 'wrong_default'], true)) {
            $expected = $scenario === 'wrong_index' ? 'índice' : 'gcontact_sync_status';
            $assert($failure && strpos($failure->getMessage(), $expected) !== false, 'No se rechazó la definición incorrecta: '.$scenario);
            $assert(!$connection->table('migrations')->where('migration', '2026_10_25_000002_extend_personas_google_contacts')->exists(),
                'Se registró una migración incompleta.');
        } else {
            $assert(!$failure && $exit === 0, 'Falló el escenario '.$scenario.': '.($failure ? $failure->getMessage() : Artisan::output()));
            $assert($connection->table('migrations')->count() === count(GoogleContactsMigrationRunner::PATHS), 'Se alteró el historial fuera del alcance Google.');
            $assert($schema->hasColumn('personas', 'gcontact_photo_hash') && $schema->hasColumn('prospectos', 'gcontact_sync_status'), 'Falta parte del esquema final.');
            foreach (['prospectos', 'comentarios'] as $tableName) {
                $assert($connection->table($tableName)->value('created_at') === '0000-00-00 00:00:00', 'Se alteró la fecha heredada de un prospecto/consulta.');
            }
            $assert(Artisan::call('gcontact:migrate', ['--force' => true]) === 0, 'No se pudo repetir la ejecución.');
            $assert($connection->table('migrations')->count() === count(GoogleContactsMigrationRunner::PATHS), 'La repetición duplicó registros del historial.');
            $assert($schema->hasTable('person_phone_verifications') && $schema->hasTable('google_contact_bulk_items'), 'Faltan las tablas de verificación o lotes.');
            if ($scenario === 'fresh') {
                require_once database_path('migrations/2022_03_04_230700_create_jobs_table.php');
                (new CreateJobsTable)->up();
                $connection->table('users')->insert(['id' => 1]);
                \App\Models\GoogleContactAccount::create(['slot' => 'central', 'google_id' => 'migration-test',
                    'email' => 'migration@example.test', 'installation_id' => '11111111-1111-4111-8111-111111111111',
                    'refresh_token' => 'test-only', 'expires_at' => now()->addHour()]);
                foreach ([1 => '70000001', 2 => null, 3 => '123', 4 => '70000004'] as $id => $phone) {
                    $connection->table('personas')->where('id', $id)->update(['telefono' => $phone]);
                }
                $bulk = app(\App\Services\GoogleContactsBulkService::class);
                $service = app(\App\Services\GoogleContactsService::class);
                $run = $bulk->start($service, 1, 'persona', false, 0);
                $bulk->advance($service);
                $run->refresh();
                $assert($run->queued === 2 && $run->skipped === 1, 'El lote no separó teléfonos válidos, ausentes e inválidos.');
                $assert($connection->table('google_contact_bulk_items')->orderBy('id')->pluck('record_id')->all() === [1, 4], 'El lote incluyó otras personas.');
                $connection->table('personas')->where('id', 1)->update(['gcontact_sync_status' => 'synced', 'gcontact_sync_pending' => 0, 'gcontact_error_code' => 'invalid_photo']);
                $connection->table('personas')->where('id', 4)->update(['gcontact_sync_status' => 'error', 'gcontact_error_code' => 'invalid_birthday']);
                $progress = $bulk->progress($run);
                $assert($progress['synced'] === 1 && $progress['errors'] === 1 && $progress['pending'] === 0, 'El progreso MariaDB no coincide con los resultados.');
                $assert($progress['photo_warnings'] === 1, 'El progreso MariaDB no separó el aviso de fotografía de los errores y pendientes.');
                $bulk->advance($service);
                $assert($run->fresh()->status === 'completed', 'El lote no terminó en MariaDB.');
                $assert($connection->table('person_phone_verifications')->count() === 0 && $connection->table('jobs')->count() === 0, 'El lote confirmó teléfonos o ejecutó una cola real.');
                $assert($connection->select('SELECT id, CAST(fechanacimiento AS CHAR) AS birth, resourseName, etag FROM personas ORDER BY id') == $before, 'El lote cambió fechas o enlaces heredados.');
            }
        }
        $results[$scenario] = 'OK';
    } finally {
        DB::disconnect('google_migration_test');
        config(['database.default' => $default, 'database.connections' => $connections]);
        Schema::clearResolvedInstance('db.schema');
        app('migration.repository')->setSource($default);
        // Only the generated, explicitly checked scratch schema is ever removed.
        $assert((bool) preg_match('/^ife_gcontact_migration_check_[a-f0-9]{8}$/', $database), 'Nombre no permitido para limpieza.');
        $pdo->exec('DROP DATABASE `'.$database.'`');
    }
}
$assert($pdo->query('SELECT @@GLOBAL.sql_mode')->fetchColumn() === $globalMode, 'Se alteró SQL_MODE global.');
Http::assertNothingSent();
Queue::assertPushed(\App\Jobs\SyncGoogleContact::class, 2);
echo json_encode(['scenarios' => $results, 'legacy_dates_and_links_preserved' => true,
    'bulk_import_and_progress_on_mariadb' => true, 'session_and_global_sql_modes_preserved' => true,
    'application_database_untouched' => true], JSON_PRETTY_PRINT).PHP_EOL;

<?php

namespace App\Services;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GoogleContactsMigrationRunner
{
    public const PATHS = [
        'database/migrations/2024_03_17_153820_add_column_to_table.php',
        'database/migrations/2026_04_15_000007_add_gcontact_sync_queue_fields_to_personas_table.php',
        'database/migrations/2026_10_25_000001_create_google_contact_accounts_table.php',
        'database/migrations/2026_10_25_000002_extend_personas_google_contacts.php',
        'database/migrations/2026_10_25_000003_add_google_contacts_pause_controls.php',
        'database/migrations/2026_10_26_000001_extend_leads_google_contacts.php',
        'database/migrations/2026_10_26_000002_add_google_contact_photo_hash.php',
        'database/migrations/2026_10_27_000001_create_person_phone_verification_tables.php',
        'database/migrations/2026_10_27_000002_create_google_contact_bulk_tables.php',
    ];
    private const PERSONA_MIGRATION = '2026_10_25_000002_extend_personas_google_contacts';
    private const COLUMNS = [
        'gcontact_account_id' => ['bigint', null, true, null],
        'gcontact_sync_status' => ['varchar', 20, false, 'idle'],
        'gcontact_revision' => ['bigint', null, false, '0'],
        'gcontact_error_code' => ['varchar', 50, true, null],
        'gcontact_synced_at' => ['timestamp', null, true, null],
        'gcontact_create_started_at' => ['timestamp', null, true, null],
        'gcontact_payload_hash' => ['char', 64, true, null],
    ];

    public static function ddlSqlMode(string $original): string
    {
        $modes = array_filter(array_map('trim', explode(',', strtoupper($original))), function ($mode) {
            return $mode !== '' && !in_array($mode, ['NO_ZERO_DATE', 'NO_ZERO_IN_DATE'], true);
        });
        // Keep strict mode and other checks. Only permit dates already accepted by the old import.
        $modes[] = 'ALLOW_INVALID_DATES';
        return implode(',', array_unique($modes));
    }

    public function withLegacyDateMode(Connection $connection, callable $operation)
    {
        if ($connection->getDriverName() !== 'mysql') {
            return $operation();
        }
        $original = $connection->selectOne('SELECT @@SESSION.sql_mode AS mode', [], false)->mode;
        $connection->statement('SET SESSION sql_mode = ?', [self::ddlSqlMode($original)]);
        try {
            return $operation();
        } finally {
            // This never changes global SQL mode, Laravel strict configuration or stored birthdays.
            $connection->statement('SET SESSION sql_mode = ?', [$original]);
        }
    }

    public function run(callable $operation, callable $report)
    {
        $connection = DB::connection();
        return $this->withLegacyDateMode($connection, function () use ($connection, $operation, $report) {
            if ($connection->getDriverName() === 'mysql' && $this->resumePersonas($connection)) {
                $report('Se completó la migración interrumpida de Persona, conservando las fechas y enlaces existentes.');
            }
            return $operation();
        });
    }

    private function resumePersonas(Connection $connection): bool
    {
        $schema = $connection->getSchemaBuilder();
        $history = config('database.migrations', 'migrations');
        if (!$schema->hasTable($history) || !$schema->hasTable('personas')
            || $connection->table($history)->where('migration', self::PERSONA_MIGRATION)->exists()) {
            return false;
        }
        $present = array_intersect(array_keys(self::COLUMNS), $schema->getColumnListing('personas'));
        if (!$present) {
            // A clean installation runs the original migration through Laravel as usual.
            return false;
        }
        if (!$schema->hasTable('google_contact_accounts')) {
            throw new RuntimeException('La migración interrumpida necesita primero la tabla google_contact_accounts.');
        }
        $missing = array_diff(array_keys(self::COLUMNS), $present);
        if ($missing) {
            $schema->table('personas', function (Blueprint $table) use ($missing) {
                foreach ($missing as $column) {
                    switch ($column) {
                        case 'gcontact_account_id': $table->unsignedBigInteger($column)->nullable(); break;
                        case 'gcontact_sync_status': $table->string($column, 20)->default('idle'); break;
                        case 'gcontact_revision': $table->unsignedBigInteger($column)->default(0); break;
                        case 'gcontact_error_code': $table->string($column, 50)->nullable(); break;
                        case 'gcontact_synced_at':
                        case 'gcontact_create_started_at': $table->timestamp($column)->nullable(); break;
                        case 'gcontact_payload_hash': $table->char($column, 64)->nullable(); break;
                    }
                }
            });
        }
        $tableName = $connection->getTablePrefix().'personas';
        $columns = $connection->select('SELECT COLUMN_NAME AS name, DATA_TYPE AS type, COLUMN_TYPE AS definition, '
            .'CHARACTER_MAXIMUM_LENGTH AS size, IS_NULLABLE AS nullable, COLUMN_DEFAULT AS default_value FROM information_schema.COLUMNS '
            .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [$tableName], false);
        $columns = collect($columns)->keyBy('name');
        foreach (self::COLUMNS as $name => [$type, $size, $nullable, $default]) {
            $column = $columns->get($name);
            // MariaDB quotes literal defaults; MySQL returns their unquoted value.
            $actualDefault = $column->default_value ?? null;
            $actualDefault = $actualDefault === null || strtoupper($actualDefault) === 'NULL' ? null : trim($actualDefault, "'");
            if (!$column || $column->type !== $type || ($size !== null && (int) $column->size !== $size)
                || ($column->nullable === 'YES') !== $nullable
                || $actualDefault !== $default
                || ($type === 'bigint' && strpos($column->definition, 'unsigned') === false)) {
                throw new RuntimeException('La columna personas.'.$name.' tiene una definición diferente. Requiere revisión antes de continuar.');
            }
        }
        $foreign = 'personas_gcontact_account_id_foreign';
        $key = $this->foreignKey($connection, $tableName, $foreign);
        if (!$key) {
            $schema->table('personas', function (Blueprint $table) {
                $table->foreign('gcontact_account_id')->references('id')->on('google_contact_accounts');
            });
            $key = $this->foreignKey($connection, $tableName, $foreign);
        }
        if (count($key) !== 1 || $key[0]->column_name !== 'gcontact_account_id' || $key[0]->referenced_column !== 'id'
            || $key[0]->referenced_table !== $connection->getTablePrefix().'google_contact_accounts'
            || $key[0]->referenced_schema !== $connection->getDatabaseName()) {
            throw new RuntimeException('No se pudo verificar la clave foránea Google de Persona.');
        }
        $this->ensureIndex($connection, $tableName, 'personas_gcontact_pending_index',
            ['gcontact_sync_pending', 'gcontact_sync_status'], false);
        $this->ensureIndex($connection, $tableName, 'personas_gcontact_resource_unique',
            ['gcontact_account_id', 'resourseName'], true);

        // Register completion only after every column, foreign key and index has been verified.
        // Existing migration rows and all business records are left untouched.
        $connection->table($history)->insert(['migration' => self::PERSONA_MIGRATION,
            'batch' => (int) $connection->table($history)->max('batch') + 1]);
        return true;
    }

    private function foreignKey(Connection $connection, string $table, string $name)
    {
        return $connection->select('SELECT COLUMN_NAME AS column_name, REFERENCED_TABLE_NAME AS referenced_table, '
            .'REFERENCED_TABLE_SCHEMA AS referenced_schema, '
            .'REFERENCED_COLUMN_NAME AS referenced_column FROM information_schema.KEY_COLUMN_USAGE '
            .'WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?', [$table, $name], false);
    }

    private function ensureIndex(Connection $connection, string $tableName, string $name, array $columns, bool $unique): void
    {
        $read = function () use ($connection, $tableName, $name) {
            return $connection->select('SELECT COLUMN_NAME AS column_name, NON_UNIQUE AS non_unique, SUB_PART AS prefix_size '
                .'FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? '
                .'AND INDEX_NAME = ? ORDER BY SEQ_IN_INDEX', [$tableName, $name], false);
        };
        $index = $read();
        if (!$index) {
            $connection->getSchemaBuilder()->table('personas', function (Blueprint $table) use ($name, $columns, $unique) {
                $unique ? $table->unique($columns, $name) : $table->index($columns, $name);
            });
            $index = $read();
        }
        if (!$index || array_column($index, 'column_name') !== $columns || (int) $index[0]->non_unique !== ($unique ? 0 : 1)
            || count(array_filter(array_column($index, 'prefix_size'))) > 0) {
            throw new RuntimeException('El índice '.$name.' necesita revisión; no se marcará la migración como completada.');
        }
    }
}

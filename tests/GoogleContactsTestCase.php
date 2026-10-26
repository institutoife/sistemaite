<?php

namespace Tests;

use App\Models\GoogleContactAccount;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

abstract class GoogleContactsTestCase extends TestCase
{
    protected $realQueue;

    protected function setUp(): void
    {
        parent::setUp();
        if (!app()->environment('testing') || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Estas pruebas requieren SQLite :memory:. Use phpunit.google-contacts.xml.');
        }
        $this->realQueue = Queue::getFacadeRoot();
        Queue::fake();
        $this->fakeHttp([]);
        Schema::create('personas', function (Blueprint $table) {
            $table->id();
            foreach (['nombre', 'apellidop', 'apellidom', 'telefono', 'foto', 'papelinicial', 'resourseName', 'etag'] as $field) {
                $table->string($field)->nullable();
            }
            $table->date('fechanacimiento')->nullable();
            $table->boolean('habilitado')->default(1);
            $table->boolean('gcontact_sync_pending')->default(false);
            $table->text('gcontact_sync_error')->nullable();
            $table->unsignedInteger('gcontact_sync_attempts')->default(0);
            $table->timestamp('gcontact_sync_last_attempt_at')->nullable();
            $table->timestamps();
        });
        foreach (['estudiantes', 'computacions', 'docentes', 'administrativos', 'cliservicios'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('persona_id');
                $table->timestamps();
            });
        }
        Schema::create('persona_persona', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('persona_id');
            $table->unsignedBigInteger('persona_id_apoderado');
            $table->string('telefono')->nullable();
            $table->string('parentesco')->nullable();
            $table->timestamps();
        });
        foreach (['modalidads' => 'modalidad', 'asignaturas' => 'asignatura'] as $name => $field) {
            Schema::create($name, function (Blueprint $table) use ($field) {
                $table->id();
                $table->string($field);
                $table->timestamps();
            });
        }
        foreach (['inscripciones' => ['estudiante_id', 'modalidad_id'], 'matriculacions' => ['computacion_id', 'asignatura_id']] as $name => $fields) {
            Schema::create($name, function (Blueprint $table) use ($fields) {
                $table->id();
                foreach ($fields as $field) {
                    $table->unsignedBigInteger($field)->nullable();
                }
                $table->timestamps();
            });
        }
        Schema::create('comentarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->nullable();
            $table->string('telefono')->nullable();
            $table->text('comentario')->nullable();
            $table->boolean('vigente')->default(true);
            $table->unsignedBigInteger('persona_id')->nullable();
            $table->unsignedBigInteger('como_id')->nullable();
            $table->timestamps();
        });
        Schema::create('prospectos', function (Blueprint $table) {
            $table->id();
            $table->string('telefono')->nullable();
            $table->unsignedBigInteger('estado_id')->nullable();
            $table->timestamps();
        });
        foreach ([
            '2021_03_10_045811_create_users_table.php' => 'CreateUsersTable',
            '2021_05_04_154937_create_permission_tables.php' => 'CreatePermissionTables',
            '2021_06_27_180320_create_userables_table.php' => 'CreateUserablesTable',
            '2022_02_24_222218_create_notifications_table.php' => 'CreateNotificationsTable',
            '2022_03_04_230700_create_jobs_table.php' => 'CreateJobsTable',
            '2026_10_25_000001_create_google_contact_accounts_table.php' => 'CreateGoogleContactAccountsTable',
            '2026_10_25_000002_extend_personas_google_contacts.php' => 'ExtendPersonasGoogleContacts',
            '2026_10_25_000003_add_google_contacts_pause_controls.php' => 'AddGoogleContactsPauseControls',
            '2026_10_26_000001_extend_leads_google_contacts.php' => 'ExtendLeadsGoogleContacts',
            '2026_10_26_000002_add_google_contact_photo_hash.php' => 'AddGoogleContactPhotoHash',
        ] as $file => $class) {
            require_once database_path('migrations/'.$file);
            (new $class)->up();
        }
    }

    protected function service(): \App\Services\GoogleContactsService
    {
        return app(\App\Services\GoogleContactsService::class);
    }

    protected function fakeHttp($callbacks): void
    {
        // Laravel 8 accumulates callbacks. Use one strict dispatcher and a fresh recorder.
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(function ($request) use ($callbacks) {
            if (is_callable($callbacks)) {
                return $callbacks($request);
            }
            foreach ($callbacks as $pattern => $callback) {
                if (\Illuminate\Support\Str::is($pattern, $request->url())) {
                    return is_callable($callback) ? $callback($request) : $callback;
                }
            }
            throw new \RuntimeException('Solicitud HTTP no simulada; acceso externo bloqueado en estas pruebas.');
        });
    }

    protected function account(array $attributes = []): GoogleContactAccount
    {
        return GoogleContactAccount::create(array_merge([
            'slot' => 'central', 'google_id' => 'test-google-user', 'email' => 'central@example.test',
            'installation_id' => '11111111-1111-4111-8111-111111111111',
            'access_token' => 'access-test-only', 'refresh_token' => 'refresh-test-only',
            'expires_at' => now()->addHour(),
        ], $attributes));
    }

    protected function persona(array $attributes = []): Persona
    {
        $persona = Persona::withoutEvents(function () use ($attributes) {
            $persona = new Persona;
            $persona->forceFill(array_merge(['nombre' => 'Lucas', 'apellidop' => 'Mamani', 'telefono' => '60902299',
                'papelinicial' => 'estudiante', 'gcontact_sync_pending' => true, 'gcontact_sync_status' => 'pending'], $attributes))->save();
            return $persona;
        });
        DB::table('estudiantes')->insert(['persona_id' => $persona->id]);
        return $persona;
    }

    protected function remote(string $resource = 'people/contact1', string $etag = 'source-etag'): array
    {
        return ['resourceName' => $resource, 'etag' => 'person-etag',
            'metadata' => ['sources' => [['type' => 'CONTACT', 'id' => 'contact1', 'etag' => $etag]]]];
    }

    protected function actor(string $role = 'Admin'): User
    {
        $user = User::create(['name' => 'Operador', 'email' => uniqid().'@example.test', 'password' => bcrypt('testing-only')]);
        $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
        return $user;
    }
}

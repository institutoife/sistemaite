<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\GoogleContactsTestCase;

class PersonaStoreTest extends GoogleContactsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // All persistence is isolated by GoogleContactsTestCase; vCards use fake disks too.
        Storage::fake('local');
        Storage::fake('public');
        Schema::table('personas', function (Blueprint $table) {
            foreach (['direccion', 'carnet', 'expedido', 'genero', 'como_id', 'persona_id', 'pais_id', 'ciudad_id', 'zona_id'] as $field) {
                $table->string($field)->nullable();
            }
        });
        foreach (['zonas' => 'zona', 'comos' => 'como'] as $name => $field) {
            Schema::create($name, function (Blueprint $table) use ($field) {
                $table->id();
                $table->string($field);
            });
            DB::table($name)->insert(['id' => 1, $field => 'Dato de prueba']);
        }
        foreach ([
            '2022_03_15_090348_create_interests_table.php' => 'CreateInterestsTable',
            '2022_03_15_174505_create_interest_persona_table.php' => 'CreateInterestPersonaTable',
            '2021_04_29_030205_create_observacions_table.php' => 'CreateObservacionsTable',
        ] as $file => $class) {
            require_once database_path('migrations/'.$file);
            (new $class)->up();
        }
        DB::table('interests')->insert(['id' => 1, 'interest' => 'Curso de prueba', 'descripcion' => 'Solo para pruebas']);
        Role::firstOrCreate(['name' => 'Estudiante', 'guard_name' => 'web']);
        $actor = $this->actor();
        $actor->givePermissionTo(Permission::firstOrCreate(['name' => 'Crear Personas', 'guard_name' => 'web']));
        $this->actingAs($actor);
        $this->account();
        Queue::swap($this->realQueue);
        $this->withoutExceptionHandling();
    }

    public static function studentRolesAndPhones(): array
    {
        return [
            'estudiante_con_telefono' => ['estudiante', '60902299'],
            'estudiante_sin_telefono' => ['estudiante', null],
            'computacion_con_telefono' => ['computacion', '60902299'],
            'computacion_sin_telefono' => ['computacion', null],
        ];
    }

    /** @dataProvider studentRolesAndPhones */
    public function test_student_creation_saves_identity_roles_account_and_one_committed_google_job(string $role, ?string $phone): void
    {
        $response = $this->post(route('personas.store'), $this->data($role, $phone));
        $person = Persona::sole();
        $response->assertSessionHasNoErrors()->assertRedirect(route('tomar.foto.persona', $person));
        $this->assertDatabaseHas('estudiantes', ['persona_id' => $person->id]);
        $this->assertDatabaseHas('computacions', ['persona_id' => $person->id]);
        $this->assertDatabaseHas('interest_persona', ['persona_id' => $person->id, 'interest_id' => 1]);
        $this->assertDatabaseHas('observacions', ['observable_id' => $person->id,
            'observable_type' => Persona::class, 'observacion' => 'Alta de prueba.']);
        $student = User::where('persona_id', $person->id)->sole();
        $this->assertTrue($student->hasRole('Estudiante'));
        $this->assertSame($phone, $person->telefono);
        $this->assertSame('pending', $person->gcontact_sync_status);
        $this->assertTrue((bool) $person->gcontact_sync_pending);
        $this->assertSame(1, DB::table('jobs')->where('queue', 'google-contacts')->count());
        $this->assertSame(0, DB::transactionLevel());
        Storage::disk('local')->assertExists('contactos/'.$person->nombre.$person->id.'.vcf');
        Http::assertNothingSent();
    }

    public function test_failed_student_creation_rolls_back_identity_roles_account_and_pending_jobs(): void
    {
        Estudiante::creating(function () { throw new RuntimeException('student-role-write-failed'); });
        $failure = null;
        try {
            $this->post(route('personas.store'), $this->data('estudiante', null));
        } catch (RuntimeException $exception) {
            $failure = $exception;
        }
        $this->assertNotNull($failure);
        $this->assertSame('student-role-write-failed', $failure->getMessage());
        foreach (['personas', 'estudiantes', 'computacions', 'interest_persona', 'observacions', 'userables', 'jobs'] as $table) {
            $this->assertSame(0, DB::table($table)->count(), $table);
        }
        $this->assertSame(1, User::count(), 'Solo debe conservarse el operador de la prueba.');
        $this->assertSame(0, DB::transactionLevel());
        Http::assertNothingSent();
    }

    private function data(string $role, ?string $phone): array
    {
        return ['nombre' => 'Estudiante de prueba', 'apellidop' => 'Mamani', 'apellidom' => 'Prueba',
            'fechanacimiento' => '2012-05-15', 'direccion' => 'Direccion de prueba', 'genero' => 'MUJER',
            'zona_id' => 1, 'como_id' => '1', 'papel' => $role, 'telefono' => $phone,
            'interests' => [1 => 'on'], 'observacion' => 'Alta de prueba.'];
    }
}

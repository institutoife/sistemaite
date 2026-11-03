<?php
namespace Tests\Feature;

use App\Models\{CrmSede, User};
use App\Services\AccountSecurity;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Http, Queue, Schema};
use Spatie\Permission\Models\Permission;
use Tests\CrmTestCase;

class InscriptionCreationTest extends CrmTestCase
{
    private $person, $branch;

    protected function setUp(): void
    {
        parent::setUp();
        require_once database_path('migrations/2026_10_31_000001_add_user_access_controls.php');
        (new \AddUserAccessControls)->up();
        $this->app->forgetInstance(AccountSecurity::class);
        $this->operator->givePermissionTo(Permission::firstOrCreate(['name'=>'Crear Inscripciones','guard_name'=>'web']));
        User::withoutEvents(fn()=>$this->operator->forceFill([
            'two_factor_secret'=>'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP', // Public test fixture only.
            'two_factor_confirmed_at'=>now(),
        ])->save());
        $this->withSession(['auth_version'=>0,'two_factor_verified'=>0,'sede_activa'=>0]);
        $this->branch=CrmSede::where('activa',true)->firstOrFail();

        Schema::create('nivels',function(Blueprint $t){$t->id();$t->string('nivel');});
        Schema::table('grados',function(Blueprint $t){$t->unsignedBigInteger('nivel_id')->nullable();});
        Schema::create('estudiante_grado',function(Blueprint $t){
            $t->id();$t->unsignedBigInteger('estudiante_id');$t->unsignedBigInteger('grado_id');
            $t->integer('anio')->nullable();$t->unsignedBigInteger('colegio_id')->nullable();$t->timestamps();
        });
        Schema::table('modalidads',function(Blueprint $t){
            $t->unsignedBigInteger('nivel_id')->nullable();$t->decimal('costo',10,2)->nullable();$t->decimal('cargahoraria',8,2)->nullable();
        });
        Schema::table('inscripciones',function(Blueprint $t){
            $t->date('fechaini')->nullable();$t->date('fechafin')->nullable();$t->date('fecha_proximo_pago')->nullable();
            $t->decimal('costo',10,2)->nullable();$t->decimal('totalhoras',8,2)->nullable();$t->text('objetivo')->nullable();
            $t->boolean('vigente')->default(true);$t->boolean('condonado')->default(false);$t->unsignedBigInteger('motivo_id')->nullable();
        });
        Schema::table('docentes',function(Blueprint $t){$t->unsignedBigInteger('estado_id')->nullable();$t->string('nombrecorto')->nullable();});
        Schema::create('materias',function(Blueprint $t){$t->id();$t->string('materia');});
        Schema::create('materiables',function(Blueprint $t){$t->unsignedBigInteger('materia_id');$t->unsignedBigInteger('materiable_id');$t->string('materiable_type');});
        Schema::create('aulas',function(Blueprint $t){$t->id();$t->string('aula');$t->timestamps();});
        foreach([
            '2021_04_28_203844_create_tipomotivos_table.php'=>'CreateTipomotivosTable',
            '2021_04_28_203845_create_motivos_table.php'=>'CreateMotivosTable',
            '2021_05_06_133835_create_dias_table.php'=>'CreateDiasTable',
            '2021_10_12_073856_create_nivelables_table.php'=>'CreateNivelablesTable',
            '2021_05_01_025839_create_programacions_table.php'=>'CreateProgramacionsTable',
        ] as $file=>$class){require_once database_path('migrations/'.$file);(new $class)->up();}
        DB::table('nivels')->insert(['id'=>1,'nivel'=>'Primaria']);
        DB::table('grados')->where('id',1)->update(['nivel_id'=>1]);
        DB::table('modalidads')->insert([
            ['id'=>1,'modalidad'=>'Regular','nivel_id'=>1,'costo'=>100,'cargahoraria'=>4],
            ['id'=>2,'modalidad'=>'Intensivo','nivel_id'=>1,'costo'=>200,'cargahoraria'=>8],
        ]);
        DB::table('tipomotivos')->insert(['id'=>1,'tipomotivo'=>'Inscripción']);
        DB::table('motivos')->insert([
            ['id'=>1,'motivo'=>'Apoyo','tipomotivo_id'=>1],['id'=>2,'motivo'=>'Nivelación','tipomotivo_id'=>1],
        ]);
        $this->person=$this->persona();
        DB::table('estudiante_grado')->insert(['estudiante_id'=>$this->person->estudiante->id,'grado_id'=>1]);
    }

    private function data(array $overrides=[]): array
    {
        return array_merge(['estudiante_id'=>$this->person->estudiante->id,'modalidad_id'=>1,'motivo_id'=>1,
            'fechaini'=>'2026-11-02','costo'=>100,'totalhoras'=>4,'objetivo'=>'Reforzar contenidos del nivel.'],$overrides);
    }

    private function formUrl(): string { return url('/inscripcione/crear/'.$this->person->id); }

    private function staff(): User
    {
        $staff=$this->actor('Secretaria');
        User::withoutEvents(fn()=>$staff->forceFill([
            'two_factor_secret'=>'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP',
            'two_factor_confirmed_at'=>now(),
            'force_password_change'=>false,'temporary_password_expires_at'=>null,
        ])->save());
        $staff->sedes()->sync([$this->branch->id]);
        $this->actingAs($staff)->withSession(['auth_version'=>0,'two_factor_verified'=>0,'sede_activa'=>$this->branch->id]);
        return $staff;
    }

    private function dom(string $html): \DOMXPath
    {
        $dom=new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        return new \DOMXPath($dom);
    }

    public function test_form_has_explicit_branch_inside_save_form_and_uses_local_catalog(): void
    {
        $response=$this->get($this->formUrl());
        $this->assertSame(200,$response->status(),'Redirect: '.$response->headers->get('Location'));
        $xpath=$this->dom($response->getContent());
        $this->assertSame(1,$xpath->query('//form[.//button[@id="guardar"]]//select[@name="sede_id" and @required]')->length);
        $this->assertSame(0,$xpath->query('//select[@id="inscripcion-sede"]/option[@selected]')->length);
        $this->assertSame('100',$xpath->query('//select[@id="modalidad_id"]/option[@value="1"]')->item(0)->getAttribute('data-costo'));
        $response->assertSee('vistas/inscripcione/form.js')->assertDontSee('cdn.ckeditor.com');
    }

    public function test_admin_all_requires_branch_and_displays_validation_on_return(): void
    {
        $this->from($this->formUrl())->post(route('inscripciones.store'),$this->data())
            ->assertRedirect($this->formUrl())->assertSessionHasErrors('sede_id');
        $this->get($this->formUrl())->assertOk()->assertSee('Seleccione la Sucursal de la inscripción.');
        $this->assertSame(0,DB::table('inscripciones')->count());
    }

    public function test_admin_can_choose_branch_save_and_open_class_configuration(): void
    {
        $response=$this->post(route('inscripciones.store'),$this->data(['sede_id'=>$this->branch->id]))
            ->assertOk()->assertViewIs('inscripcione.configurar')->assertSessionHas('sede_activa',$this->branch->id);
        $enrollment=$response->viewData('inscripcion');
        $this->assertDatabaseHas('inscripciones',['id'=>$enrollment->id,'estudiante_id'=>$this->person->estudiante->id,'costo'=>100]);
        $this->assertDatabaseHas('sede_registros',['tabla'=>'inscripciones','registro_id'=>$enrollment->id,'sede_id'=>$this->branch->id]);
        $this->assertEquals($this->operator->id,$enrollment->usuarios->sole()->id);
        $this->assertSame(0,DB::table('persona_sede')->count());
        Http::assertNothingSent();Queue::assertNothingPushed();
    }

    public function test_existing_clients_can_save_with_authorized_active_branch_without_extra_field(): void
    {
        $this->withSession(['sede_activa'=>$this->branch->id]);
        $this->post(route('inscripciones.store'),$this->data())->assertOk()->assertViewIs('inscripcione.configurar');
        $this->assertDatabaseHas('sede_registros',['tabla'=>'inscripciones','sede_id'=>$this->branch->id]);
    }

    public function test_staff_cannot_forge_branch_and_form_only_lists_authorized_branches(): void
    {
        $staff=$this->staff();
        $staff->givePermissionTo('Crear Inscripciones');
        $response=$this->get($this->formUrl())->assertOk();
        $xpath=$this->dom($response->getContent());
        $this->assertSame(1,$xpath->query('//select[@id="inscripcion-sede"]/option[@value!=""]')->length);
        $other=CrmSede::where('id','!=',$this->branch->id)->firstOrFail();
        $this->post(route('inscripciones.store'),$this->data(['sede_id'=>$other->id]))->assertForbidden();
        $this->assertSame(0,DB::table('inscripciones')->count());
        $this->assertSame($this->branch->id,session('sede_activa'));
    }

    public function test_zero_is_not_a_valid_destination_for_a_new_enrollment(): void
    {
        $this->postJson(route('inscripciones.store'),$this->data(['sede_id'=>0]))
            ->assertUnprocessable()->assertJsonValidationErrors('sede_id');
        $this->assertSame(0,DB::table('inscripciones')->count());
    }

    public function test_validation_preserves_new_choices_instead_of_reusing_previous_enrollment(): void
    {
        DB::table('inscripciones')->insert($this->data(['fechaini'=>'2026-10-01']));
        $this->from($this->formUrl())->post(route('inscripciones.store'),$this->data([
            'sede_id'=>$this->branch->id,'modalidad_id'=>2,'motivo_id'=>2,'costo'=>175,'totalhoras'=>7,'objetivo'=>'Corto',
        ]))->assertRedirect($this->formUrl())->assertSessionHasErrors('objetivo');
        $response=$this->get($this->formUrl())->assertOk();
        $xpath=$this->dom($response->getContent());
        foreach(['modalidad_id'=>'2','motivo_id'=>'2','sede_id'=>(string)$this->branch->id] as $name=>$value){
            $this->assertSame($value,$xpath->query('//form[.//button[@id="guardar"]]//select[@name="'.$name.'"]/option[@selected]')->item(0)->getAttribute('value'));
        }
        $this->assertSame('175',$xpath->query('//input[@name="costo"]')->item(0)->getAttribute('value'));
        $this->assertSame('7',$xpath->query('//input[@name="totalhoras"]')->item(0)->getAttribute('value'));
        $this->assertSame(1,DB::table('inscripciones')->count());
        $this->assertDatabaseHas('inscripciones',['modalidad_id'=>1,'motivo_id'=>1,'costo'=>100,'fechaini'=>'2026-10-01']);
    }

    public function test_create_permission_remains_required(): void
    {
        $this->staff();
        $response=$this->post(route('inscripciones.store'),$this->data(['sede_id'=>$this->branch->id]));
        $this->assertSame(403,$response->status(),'Redirect: '.$response->headers->get('Location'));
        $this->assertSame(0,DB::table('inscripciones')->count());
    }
}

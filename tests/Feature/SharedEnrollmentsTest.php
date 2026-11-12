<?php
namespace Tests\Feature;

use App\Models\{Inscripcione,Matriculacion,Persona,User};
use App\Services\{AccountSecurity,SedeAccess};
use App\Support\SedeDB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Spatie\Permission\Models\Permission;
use Tests\AttendanceTestCase;

class SharedEnrollmentsTest extends AttendanceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('crm_sedes',function(Blueprint $t){$t->id();$t->string('nombre');$t->boolean('activa')->default(true);$t->timestamps();});
        DB::table('crm_sedes')->insert([
            ['id'=>1,'nombre'=>'IFE Central','activa'=>true],['id'=>2,'nombre'=>'IFE Villa','activa'=>true],['id'=>3,'nombre'=>'IFE Plan','activa'=>true],
        ]);
        require_once database_path('migrations/2026_10_31_000001_add_user_access_controls.php');
        (new \AddUserAccessControls)->up();$this->app->forgetInstance(AccountSecurity::class);
        DB::table('estados')->insert(['estado'=>'RESERVADO']);
        Schema::table('pagos',function(Blueprint $t){$t->decimal('pagocon')->nullable();$t->decimal('cambio')->nullable();$t->string('forma_pago')->nullable();$t->timestamps();});
        Schema::table('asignaturas',fn(Blueprint $t)=>$t->decimal('costo')->default(400));
        Schema::table('matriculacions',fn(Blueprint $t)=>$t->decimal('calificacion')->nullable());
        Schema::create('ventas',function(Blueprint $t){$t->id();$t->timestamps();});
        $this->operator->removeRole('Admin');$this->operator->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name'=>'Secretaria','guard_name'=>'web']));
        foreach(['Listar Inscripciones','Crear Inscripciones','Editar Inscripciones','Listar Matriculaciones','Listar Pagos','Crear Pagos','Crear Pagoscomputacion'] as $name){$this->operator->givePermissionTo(Permission::firstOrCreate(['name'=>$name,'guard_name'=>'web']));}
        User::withoutEvents(fn()=>$this->operator->forceFill(['sede_id'=>1,'two_factor_secret'=>'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP','two_factor_confirmed_at'=>now()])->save());
        $this->operator->sedes()->sync([1]);$this->withSession(['sede_activa'=>1,'auth_version'=>0,'two_factor_verified'=>0]);
    }

    private function tag(string $table,int $id,int $branch): void
    {
        DB::table('sede_registros')->insert(['tabla'=>$table,'registro_id'=>$id,'sede_id'=>$branch,'created_at'=>now()]);
    }

    private function scope(): void
    {
        $request=\Illuminate\Http\Request::create('/');$request->setLaravelSession(session());
        app(SedeAccess::class)->start($this->operator,$request);
    }

    public function test_personal_list_returns_every_origin_including_unknown_and_preserves_attribution(): void
    {
        $central=$this->enroll();$legacy=$this->enroll();
        $this->tag('inscripciones',$this->enrollment->id,2);$this->tag('inscripciones',$central->id,1);
        DB::table('persona_sede')->insert(['persona_id'=>$this->person->id,'sede_id'=>2]);
        $response=$this->getJson(route('inscripciones.de.estudiante',['estudiante_id'=>$this->person->estudiante->id]))->assertOk();
        $rows=collect($response->json('data'))->keyBy('id');
        $this->assertCount(3,$rows);$this->assertSame('IFE Villa',$rows[$this->enrollment->id]['sucursal_origen']);
        $this->assertSame('IFE Central',$rows[$central->id]['sucursal_origen']);$this->assertNull($rows[$legacy->id]['sucursal_origen']);
        $this->assertSame(1,session('sede_activa'));
    }

    public function test_computing_list_and_main_api_include_original_branch(): void
    {
        $computing=$this->enroll('computacion');$this->tag('matriculacions',$computing->id,3);$this->tag('inscripciones',$this->enrollment->id,2);
        $this->getJson(route('matriculaciones.de.estudiante',['estudiante_id'=>$this->person->estudiante->id]))
            ->assertOk()->assertJsonPath('data.0.sucursal_origen','IFE Plan');
        $this->getJson('/api/inscripciones')->assertOk()->assertJsonPath('data.0.sucursal_origen','IFE Villa')
            ->assertJsonPath('recordsTotal',1);
    }

    /** @dataProvider channels */
    public function test_remote_attendance_uses_classroom_location_and_keeps_enrollment_origin(string $channel): void
    {
        $enrollment=$channel==='apoyo'?$this->enrollment:$this->enroll('computacion');
        $program=$this->program($enrollment,array_merge(['fecha'=>'2026-11-02','estado_id'=>estado('INDEFINIDO')],
            $channel==='apoyo'?['hora_ini'=>'11:00','hora_fin'=>'12:00']:['horaini'=>'11:00','horafin'=>'12:00']));
        $this->tag($enrollment->getTable(),$enrollment->id,2);$this->tag($program->getTable(),$program->id,2);$this->tag('aulas',$this->room,2);
        DB::table('persona_sede')->insert(['persona_id'=>$this->person->id,'sede_id'=>2]);
        $this->postJson(route($channel==='apoyo'?'clases.guardar':'clasescom.guardar',$program),$this->data())->assertOk();
        $this->assertDatabaseHas('sede_registros',['tabla'=>$channel==='apoyo'?'clases':'clasecoms','sede_id'=>2]);
        $this->assertDatabaseHas('sede_registros',['tabla'=>$enrollment->getTable(),'registro_id'=>$enrollment->id,'sede_id'=>2]);
        $this->assertSame(1,session('sede_activa'));
    }

    public static function channels(): array { return [['apoyo'],['computacion']]; }

    public function test_register_payment_and_edit_enrollment_from_another_branch_preserves_origin_and_full_balance(): void
    {
        $this->enrollment->costo=800;$this->enrollment->save();
        $this->program(null,['fecha'=>'2026-11-02']);$this->tag('inscripciones',$this->enrollment->id,2);
        $existing=DB::table('pagos')->where('pagable_id',$this->enrollment->id)->value('id');$this->tag('pagos',$existing,2);
        $this->post(route('pagos.guardar',$this->enrollment),['monto'=>100,'pagocon'=>100,'cambio'=>0,'forma_pago'=>'QR'])->assertRedirect();
        $this->getJson(route('inscripcion.saldo.ajax',['inscripcion_id'=>$this->enrollment->id]))->assertOk()->assertJsonPath('acuenta',500);
        $this->scope();$record=Inscripcione::findOrFail($this->enrollment->id);$record->objetivo='Actualizado desde Central';$record->save();
        $this->assertSame('Actualizado desde Central',$record->fresh()->objetivo);
        $this->assertDatabaseHas('sede_registros',['tabla'=>'inscripciones','registro_id'=>$record->id,'sede_id'=>2]);
        $this->assertSame(1,session('sede_activa'));
    }

    public function test_roles_family_links_and_unrelated_branch_restrictions_remain_enforced(): void
    {
        $this->tag('inscripciones',$this->enrollment->id,2);
        DB::table('ventas')->insert([['id'=>1],['id'=>2]]);$this->tag('ventas',1,1);$this->tag('ventas',2,2);
        $this->scope();$this->assertSame([1],SedeDB::table('ventas')->pluck('id')->all());
        $local=DB::table('pagos')->insertGetId(['monto'=>10,'pagable_type'=>\App\Models\Venta::class,'pagable_id'=>1]);
        $foreign=DB::table('pagos')->insertGetId(['monto'=>20,'pagable_type'=>\App\Models\Venta::class,'pagable_id'=>2]);
        $this->tag('pagos',$local,1);$this->tag('pagos',$foreign,2);
        $this->assertSame([$local],SedeDB::table('pagos')->whereIn('id',[$local,$foreign])->pluck('id')->all());
        $this->operator->revokePermissionTo('Listar Inscripciones');
        $this->getJson(route('inscripciones.de.estudiante',['estudiante_id'=>$this->person->estudiante->id]))->assertForbidden();
        $this->operator->revokePermissionTo('Crear Clases');$program=$this->program(null,['fecha'=>'2026-11-02','estado_id'=>estado('INDEFINIDO')]);
        $this->postJson(route('clases.guardar',$program),$this->data())->assertForbidden();
        $parent=$this->actor('Padre');User::withoutEvents(fn()=>$parent->forceFill(['force_password_change'=>false,'temporary_password_expires_at'=>null])->save());
        $this->actingAs($parent)->withSession(['auth_version'=>0]);
        $this->getJson('/api/inscripciones')->assertForbidden();
    }

    public function test_computing_payment_from_another_branch_counts_all_receipts_with_unknown_due_date(): void
    {
        $enrollment=$this->enroll('computacion',['costo'=>800]);
        $this->program($enrollment);$this->tag('matriculacions',$enrollment->id,3);
        $existing=DB::table('pagos')->where('pagable_type',Matriculacion::class)->where('pagable_id',$enrollment->id)->value('id');
        DB::table('pagos')->where('id',$existing)->update(['monto'=>300]);
        $this->tag('pagos',$existing,3);
        $this->post(route('pagocom.guardar',$enrollment),['monto'=>100,'pagocon'=>100,'cambio'=>0,'forma_pago'=>'EFECTIVO'])->assertRedirect();
        $response=$this->getJson(route('matriculacion.saldo.ajax',['matriculacion_id'=>$enrollment->id]))->assertOk();
        $this->assertEquals(400,$response->json('acuenta'));$this->assertEquals(400,$response->json('saldo'));
        $this->assertSame('Sin fecha programada',$response->json('fechaHumamizado'));
        $this->assertDatabaseHas('sede_registros',['tabla'=>'matriculacions','registro_id'=>$enrollment->id,'sede_id'=>3]);
    }

    public function test_origin_label_escapes_names_and_displays_unknown_without_adding_columns(): void
    {
        $this->tag('inscripciones',$this->enrollment->id,2);DB::table('crm_sedes')->where('id',2)->update(['nombre'=>'Villa <script>alert(1)</script>']);
        $record=Inscripcione::with('estado')->withSucursalOrigen()->findOrFail($this->enrollment->id);
        $html=view('inscripcione.estado-sucursal',['inscripcion'=>$record])->render();
        $this->assertStringContainsString('Sucursal: Villa &lt;script&gt;',$html);$this->assertStringNotContainsString('<script>',$html);
        $record->sucursal_origen=null;$this->assertStringContainsString('Sin registrar',view('inscripcione.estado-sucursal',['inscripcion'=>$record])->render());
    }
}

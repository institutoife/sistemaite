<?php
namespace Tests\Feature;

use App\Jobs\SyncGoogleContact;
use App\Models\{CrmEtapa,CrmActividadDetalle,Inscripcione,Matriculacion};
use App\Services\{CrmAnalytics,CrmUserAnalytics};
use Illuminate\Support\Facades\{DB,Schema,Http,Queue};
use Spatie\Permission\Models\Permission;
use Tests\CrmTestCase;

class CrmConversionBoardTest extends CrmTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('pagos',function ($table) {
            $table->id(); $table->unsignedBigInteger('pagable_id'); $table->string('pagable_type');
            $table->decimal('monto',10,2); $table->timestamps();
        });
    }

    private function enrollment($person, int $id=13842): int
    {
        DB::table('inscripciones')->insert(['id'=>$id,'estudiante_id'=>$person->estudiante->id,'created_at'=>now()]);
        return $id;
    }

    private function proof($person, int $id=13842, array $extra=[]): array
    {
        return array_merge(['version'=>0,'persona_id'=>$person->id,'estudiante_persona_id'=>$person->id,
            'tipo'=>'inscripcion','inscripcion_id'=>$id,'confirmar_vinculos'=>1],$extra);
    }

    public function test_paid_enrollment_can_be_selected_without_altering_payments_or_counting_a_sale_before_confirmation(): void
    {
        $person=$this->persona(); $this->enrollment($person); $lead=$this->lead(['persona_id'=>$person->id]);
        DB::table('pagos')->insert(['pagable_id'=>13842,'pagable_type'=>Inscripcione::class,'monto'=>50]);
        $before=$lead->fresh()->getAttributes();
        $this->get(route('crm.conversion.form',[$lead,'etapa_id'=>CrmEtapa::where('conversion',true)->value('id')]))
            ->assertOk()->assertSee('data-crm-conversion-form',false)->assertSee('Confirmar conversión');
        $this->getJson(route('crm.conversion.inscripciones',$lead))->assertOk()
            ->assertJsonPath('enrollments.0.id',13842)->assertJsonPath('enrollments.0.estudiante_persona_id',$person->id)
            ->assertJsonPath('enrollments.0.tiene_pago',null);
        $this->operator->givePermissionTo(Permission::firstOrCreate(['name'=>'Listar Pagos','guard_name'=>'web']));
        $response=$this->getJson(route('crm.conversion.inscripciones',$lead))->assertOk()->assertJsonPath('enrollments.0.tiene_pago',true);
        $this->assertArrayNotHasKey('monto',$response->json('enrollments.0'));
        $this->assertSame($before,$lead->fresh()->getAttributes());
        $this->assertSame(0,app(CrmAnalytics::class)->report([])['converted']);
        $this->assertSame(0,CrmActividadDetalle::count());
        $this->assertSame(1,DB::table('pagos')->count());
        Queue::assertNothingPushed(); Http::assertNothingSent();
    }

    public function test_enrollment_options_use_family_links_and_never_shared_phone_as_identity(): void
    {
        $parent=$this->persona(); $student=$this->persona(); $unrelated=$this->persona();
        $student->apoderados()->attach($parent->id,['parentesco'=>'Mamá']);
        $this->enrollment($student); $this->enrollment($unrelated,13843);
        $lead=$this->lead(['persona_id'=>$parent->id]);
        $this->getJson(route('crm.conversion.inscripciones',$lead))->assertOk()->assertJsonCount(1,'enrollments')
            ->assertJsonPath('enrollments.0.estudiante_persona_id',$student->id);
        $this->getJson(route('crm.conversion.inscripciones',[$lead,'persona_id'=>$unrelated->id]))
            ->assertUnprocessable()->assertJsonValidationErrors('persona_id');
        $unlinked=$this->lead();
        $this->getJson(route('crm.conversion.inscripciones',$unlinked))->assertOk()->assertExactJson(['enrollments'=>[],'students'=>[],'google_conflict'=>false]);
        $this->assertNull($unlinked->fresh()->persona_id); Http::assertNothingSent();
    }

    public function test_payment_indication_is_scoped_to_the_exact_enrollment_type(): void
    {
        $person=$this->persona(); $this->enrollment($person);
        $role=DB::table('computacions')->insertGetId(['persona_id'=>$person->id]);
        DB::table('matriculacions')->insert(['id'=>13842,'computacion_id'=>$role]);
        DB::table('pagos')->insert(['pagable_id'=>13842,'pagable_type'=>Matriculacion::class,'monto'=>50]);
        $this->operator->givePermissionTo(Permission::firstOrCreate(['name'=>'Listar Pagos','guard_name'=>'web']));
        $lead=$this->lead(['persona_id'=>$person->id]);
        $this->getJson(route('crm.conversion.inscripciones',$lead))->assertOk()->assertJsonCount(2,'enrollments')
            ->assertJsonPath('enrollments.0.tipo','inscripcion')->assertJsonPath('enrollments.0.tiene_pago',false)
            ->assertJsonPath('enrollments.1.tipo','matriculacion')->assertJsonPath('enrollments.1.tiene_pago',true);
    }

    public function test_board_confirmation_registers_one_sale_and_repeat_submission_is_idempotent(): void
    {
        $this->account(); $person=$this->persona(); $this->enrollment($person);
        $lead=$this->lead(['persona_id'=>$person->id,'nombre'=>'Cliente de prueba','proximo_contacto_at'=>now()->subDay()]);
        DB::table('pagos')->insert(['pagable_id'=>13842,'pagable_type'=>Inscripcione::class,'monto'=>50]);
        $stage=CrmEtapa::create(['codigo'=>'otra_conversion','nombre'=>'Clientes confirmados','color'=>'#26BAA5','orden'=>90,'conversion'=>true,'activa'=>true]);
        $proof=$this->proof($person,13842,['etapa_id'=>$stage->id]);
        $this->postJson(route('crm.convertir',$lead),$proof)->assertOk()->assertJsonPath('converted',true)->assertJsonPath('etapa_id',$stage->id);
        $this->postJson(route('crm.convertir',$lead),$proof)->assertOk()->assertJsonPath('version',1);
        $current=$lead->fresh();
        $this->assertSame('convertido',$current->crm_status); $this->assertNull($current->proximo_contacto_at);
        $this->assertSame($this->operator->id,$current->convertido_por);
        $this->assertSame($this->operator->id,$current->responsable_conversion_id);
        $this->assertSame(1,CrmActividadDetalle::where('tipo','conversion')->count());
        $this->assertSame(1,app(CrmAnalytics::class)->report([])['converted']);
        $this->assertSame(1,app(CrmUserAnalytics::class)->report([])['totals']['conversions_done']);
        $this->get(route('crm.tablero'))->assertOk()->assertSee('Cliente de prueba')->assertSee('Conversión confirmada');
        $this->get(route('crm.tablero',['status'=>'activo']))->assertOk()->assertDontSee('Cliente de prueba');
        $this->get(route('crm.lista'))->assertOk()->assertDontSee('Cliente de prueba');
        $this->assertSame(1,DB::table('inscripciones')->count()); $this->assertSame(1,DB::table('pagos')->count());
        Queue::assertPushed(SyncGoogleContact::class,1); Http::assertNothingSent();
    }

    public function test_wrong_student_missing_confirmation_or_normal_stage_never_creates_a_conversion(): void
    {
        $person=$this->persona(); $other=$this->persona(); $this->enrollment($other); $lead=$this->lead(['persona_id'=>$person->id]);
        $proof=$this->proof($person);
        $this->postJson(route('crm.convertir',$lead),$proof)->assertUnprocessable()->assertJsonValidationErrors('inscripcion_id');
        $proof['estudiante_persona_id']=$other->id;
        $this->postJson(route('crm.convertir',$lead),$proof)->assertUnprocessable()->assertJsonValidationErrors('parentesco');
        $proof['parentesco']='Mamá'; $proof['confirmar_vinculos']=0;
        $this->postJson(route('crm.convertir',$lead),$proof)->assertUnprocessable()->assertJsonValidationErrors('confirmar_vinculos');
        $proof['confirmar_vinculos']=1; $proof['etapa_id']=app(\App\Services\CrmService::class)->initial()->id;
        $this->postJson(route('crm.convertir',$lead),$proof)->assertUnprocessable()->assertJsonValidationErrors('conversion');
        $this->assertSame('activo',$lead->fresh()->crm_status); $this->assertSame(0,DB::table('persona_persona')->count());
        $this->assertSame(0,CrmActividadDetalle::count()); Queue::assertNothingPushed(); Http::assertNothingSent();
    }

    public function test_stale_confirmation_and_a_different_retry_cannot_overwrite_the_sale(): void
    {
        $person=$this->persona(); $this->enrollment($person); $this->enrollment($person,13843);
        $lead=$this->lead(['persona_id'=>$person->id,'crm_version'=>2]);
        $proof=$this->proof($person);
        $this->postJson(route('crm.convertir',$lead),$proof)->assertStatus(409);
        $proof['version']=2;
        $this->postJson(route('crm.convertir',$lead),$proof)->assertOk();
        $proof['inscripcion_id']=13843;
        $this->postJson(route('crm.convertir',$lead),$proof)->assertStatus(409);
        $this->assertSame(13842,$lead->fresh()->inscripcione_id);
        $this->assertSame(1,CrmActividadDetalle::where('tipo','conversion')->count());
        $this->getJson(route('crm.conversion.form',$lead))->assertUnprocessable();
    }

    public function test_conversion_form_and_options_require_permission_and_protect_unassigned_history(): void
    {
        $person=$this->persona(); $this->enrollment($person); $lead=$this->lead(['persona_id'=>$person->id]);
        $reader=$this->actor('Padre');
        $reader->givePermissionTo(Permission::firstOrCreate(['name'=>'Listar Prospectos','guard_name'=>'web']));
        $this->actingAs($reader)->getJson(route('crm.conversion.form',$lead))->assertForbidden();
        $this->getJson(route('crm.conversion.inscripciones',$lead))->assertForbidden();
        $this->postJson(route('crm.convertir',$lead),$this->proof($person))->assertForbidden();
        $secretary=$this->actor('Secretaria'); $this->actingAs($secretary);
        $historical=$this->lead(['responsable_id'=>null,'persona_id'=>$person->id]);
        $this->getJson(route('crm.conversion.form',$historical))->assertForbidden();
        $this->getJson(route('crm.conversion.inscripciones',$historical))->assertForbidden();
        $this->assertSame(0,CrmActividadDetalle::count()); Http::assertNothingSent(); Queue::assertNothingPushed();
    }
}

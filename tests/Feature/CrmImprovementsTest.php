<?php
namespace Tests\Feature;

use App\Models\{Prospecto,CrmEtapa,CrmActividadDetalle};
use App\Services\{CrmService,CrmAnalytics,CrmUserAnalytics,CrmHistoryBackfill};
use Illuminate\Support\Facades\{DB,Http,Queue};
use Tests\CrmTestCase;

class CrmImprovementsTest extends CrmTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-30 12:00:00'));
        DB::table('interests')->insert(['id'=>2,'interest'=>'Apoyo escolar','descripcion'=>'Prueba']);
    }
    private function createLead(array $data=[]): Prospecto
    {
        return app(CrmService::class)->save(null,array_merge(['telefono'=>'70000001','interests'=>[1,2],'crm_sede_id'=>1],$data),auth()->id());
    }
    private function activity(Prospecto $lead, array $extra=[]): void
    {
        $this->post(route('crm.actividad',$lead),array_merge(['version'=>$lead->fresh()->crm_version,'tipo'=>'llamada','resultado'=>'respondio','observacion'=>'Atendió en recepción.'],$extra))->assertRedirect();
    }
    public function test_creator_is_automatic_and_another_operators_activity_and_move_never_take_ownership(): void
    {
        $a=$this->operator; $b=$this->actor('Secretaria');
        $this->get(route('prospecto.create'))->assertOk()->assertDontSee('name="responsable_id"',false);
        $this->postJson(route('prospecto.store'),['telefono'=>'70000001','responsable_id'=>$b->id])->assertUnprocessable();
        $this->post(route('prospecto.store'),['telefono'=>'70000001'])->assertRedirect(); $lead=Prospecto::sole();
        $this->assertSame($a->id,$lead->responsable_id); $this->assertSame($a->id,$lead->creado_por); $this->assertSame($a->id,$lead->responsable_inicial_id);
        $this->actingAs($b);
        $this->activity($lead,['responsable_id'=>$b->id,'actor_id'=>$a->id,'ocurrio_at'=>'2000-01-01 00:00:00','proximo_contacto_at'=>now()->addDay()->toDateTimeString()]);
        $this->postJson(route('crm.mover',$lead),['version'=>$lead->fresh()->crm_version,'etapa_id'=>2])->assertOk();
        $this->assertSame($a->id,$lead->fresh()->responsable_id); $this->assertSame($a->id,$lead->fresh()->creado_por);
        $event=CrmActividadDetalle::where('tipo','llamada')->sole();
        $this->assertSame($b->id,$event->actor_id); $this->assertSame($a->id,$event->responsable_comercial_id);
        $this->assertSame(now()->toDateTimeString(),$event->ocurrio_at->toDateTimeString());
        $this->assertSame(3,$lead->observaciones()->count()); Queue::assertNothingPushed(); Http::assertNothingSent();
    }
    public function test_reassignment_requires_permission_is_explicit_and_preserves_original_responsibility(): void
    {
        $lead=$this->createLead(); $b=$this->actor('Secretaria');
        $this->actingAs($b)->post(route('crm.reasignar',$lead),['version'=>$lead->crm_version,'responsable_id'=>$b->id,'observacion'=>'Cambio de turno.'])->assertForbidden();
        $this->patchJson(route('prospecto.update',$lead),['version'=>$lead->crm_version,'telefono'=>$lead->telefono,'responsable_id'=>$b->id])->assertUnprocessable();
        $this->actingAs($this->operator)->post(route('crm.reasignar',$lead),['version'=>$lead->crm_version,'responsable_id'=>$b->id,'observacion'=>'Cambio de turno.'])->assertRedirect();
        $current=$lead->fresh(); $this->assertSame($b->id,$current->responsable_id); $this->assertSame($this->operator->id,$current->responsable_inicial_id);
        $event=CrmActividadDetalle::where('tipo','reasignacion')->sole();
        $this->assertSame($this->operator->id,$event->actor_id); $this->assertSame($b->id,$event->responsable_comercial_id);
        $this->assertSame($this->operator->id,$event->datos['anterior']);
        $this->postJson(route('crm.reasignar',$lead),['version'=>$lead->crm_version,'responsable_id'=>$b->id,'observacion'=>'Solicitud repetida.'])->assertStatus(409);
        $this->assertSame(1,CrmActividadDetalle::where('tipo','reasignacion')->count());
    }
    public function test_individually_authorized_users_can_own_leads_and_register_activities_without_a_fixed_role_name(): void
    {
        $other=$this->actor('Docente'); $other->givePermissionTo(['Listar Prospectos','Crear Prospectos','Editar Prospectos']);
        $this->actingAs($other); $lead=$this->createLead(); $this->activity($lead);
        $this->assertSame($other->id,$lead->fresh()->responsable_id);
        $this->assertTrue(app(CrmService::class)->responsibleUsers()->whereKey($other->id)->exists());
        $this->get(route('crm.dashboard'))->assertOk()->assertSee('Mis estadísticas');
        $this->actingAs($this->operator)->post(route('crm.reasignar',$lead),['version'=>$lead->fresh()->crm_version,'responsable_id'=>$other->id,'observacion'=>'Confirmado.'])->assertRedirect();
    }
    public function test_multiple_services_validate_save_edit_clear_and_filter_without_duplicating_the_lead(): void
    {
        $this->post(route('prospecto.store'),['telefono'=>'70000001','interests'=>[1,2],'grado_id'=>1])->assertRedirect();
        $lead=Prospecto::sole(); $this->assertSame([1,2],$lead->interests()->orderBy('interests.id')->pluck('interests.id')->all());
        foreach ([1,2] as $id) { $this->get(route('crm.lista',['interest_id'=>$id]))->assertOk()->assertViewHas('records',function ($rows) { return $rows->total()===1; }); }
        $this->get(route('prospecto.view',$lead))->assertOk()->assertSee('Computación, Apoyo escolar')->assertSee('Sucursal')->assertDontSee('<dt>Sede</dt>',false);
        $this->get(route('prospecto.edit',$lead))->assertOk()->assertSee('data-filter-select',false)->assertSee('Primaria');
        $this->patchJson(route('prospecto.update',$lead),['version'=>$lead->crm_version,'telefono'=>$lead->telefono,'interests'=>[999]])->assertUnprocessable();
        $this->assertSame(2,$lead->interests()->count());
        $this->patch(route('prospecto.update',$lead),['version'=>$lead->crm_version,'telefono'=>$lead->telefono,'interests_present'=>1])->assertRedirect();
        $this->assertSame(0,$lead->interests()->count()); $this->assertNull($lead->fresh()->interest_id);
        app(CrmHistoryBackfill::class)->run(); $this->assertSame(0,$lead->interests()->count());
    }
    public function test_unowned_legacy_records_are_preserved_but_restricted_and_excluded_from_tasks_until_recovered(): void
    {
        $old=$this->lead(['nombre'=>'Histórico reservado','responsable_id'=>null,'creado_por'=>null,'responsable_inicial_id'=>null,'proximo_contacto_at'=>now()->subDay()]);
        $this->get(route('crm.tablero'))->assertOk()->assertDontSee('Histórico reservado');
        $this->assertSame(0,app(CrmAnalytics::class)->report([])['overdue']);
        $this->get(route('crm.lista',['asignacion'=>'sin_responsable']))->assertOk()->assertSee('Histórico reservado');
        $this->assertSame(0,app(CrmAnalytics::class)->report(['asignacion'=>'sin_responsable'])['overdue']);
        $b=$this->actor('Secretaria'); $this->actingAs($b);
        $this->get(route('crm.lista',['asignacion'=>'todos']))->assertForbidden();
        $this->get(route('prospecto.view',$old))->assertForbidden();
        $this->post(route('crm.actividad',$old),['version'=>0,'tipo'=>'observacion','resultado'=>'otro'])->assertForbidden();
        $this->getJson(route('crm.coincidencias',['telefono'=>'70000001']))->assertOk()->assertDontSee('Histórico reservado');
        $this->getJson(route('prospectos.listar'))->assertOk()->assertDontSee('Histórico reservado');
        $this->assertNull($old->fresh()->responsable_id);
        $this->actingAs($this->operator)->post(route('crm.reasignar',$old),['version'=>0,'responsable_id'=>$b->id,'observacion'=>'Recuperado tras visita presencial.'])->assertRedirect();
        $this->actingAs($b)->get(route('crm.tablero'))->assertOk()->assertSee('Histórico reservado');
        $this->assertNull($old->fresh()->creado_por); $this->assertNull($old->fresh()->responsable_inicial_id);
    }
    public function test_deleting_a_populated_stage_moves_records_and_archives_it_without_losing_audit_or_recreating_it(): void
    {
        $stage=CrmEtapa::where('codigo','contacto')->sole(); $target=CrmEtapa::where('codigo','seguimiento')->sole();
        $lead=$this->lead(['crm_etapa_id'=>$stage->id]);
        $this->deleteJson(route('crm.eliminar.etapa',$stage),['version'=>0])->assertUnprocessable();
        $this->assertSame($stage->id,$lead->fresh()->crm_etapa_id);
        $this->delete(route('crm.eliminar.etapa',$stage),['version'=>0,'destino_id'=>$target->id])->assertRedirect();
        $this->assertNull(CrmEtapa::find($stage->id)); $this->assertNotNull(CrmEtapa::withTrashed()->find($stage->id));
        $this->assertSame($target->id,$lead->fresh()->crm_etapa_id); $this->assertSame($this->operator->id,$lead->fresh()->responsable_id);
        $this->assertSame($stage->nombre,CrmActividadDetalle::where('tipo','etapa')->sole()->datos['etapa_eliminada']);
        $this->seed(\Database\Seeders\CrmSeeder::class);
        $this->assertSame(1,CrmEtapa::withTrashed()->where('codigo','contacto')->count()); $this->assertNull(CrmEtapa::find($stage->id));
        $this->get(route('crm.tablero'))->assertOk()->assertDontSee('aria-label="'.$stage->nombre.'"',false);
    }
    public function test_stage_creation_deletion_permissions_stale_versions_and_required_stages_are_protected(): void
    {
        $initial=app(CrmService::class)->initial(); $converted=CrmEtapa::where('conversion',true)->sole();
        $this->deleteJson(route('crm.eliminar.etapa',$initial),['version'=>0])->assertUnprocessable();
        $this->deleteJson(route('crm.eliminar.etapa',$converted),['version'=>0])->assertUnprocessable();
        $data=['nombre'=>'Nueva etapa de prueba','color'=>'#375F7A','orden'=>8,'activa'=>1,'inicial'=>0,'conversion'=>0];
        $this->post(route('crm.configurar.etapa'),$data)->assertRedirect(); $stage=CrmEtapa::where('nombre',$data['nombre'])->sole();
        $b=$this->actor('Secretaria'); $this->actingAs($b)->deleteJson(route('crm.eliminar.etapa',$stage),['version'=>0])->assertForbidden();
        $this->actingAs($this->operator)->deleteJson(route('crm.eliminar.etapa',$stage),['version'=>0])->assertStatus(409);
        $this->delete(route('crm.eliminar.etapa',$stage),['version'=>$stage->version])->assertRedirect();
        $this->assertNull(CrmEtapa::find($stage->id));
    }
    public function test_user_statistics_distinguish_creator_actor_owner_and_conversion_and_keep_history_after_reassignment(): void
    {
        $a=$this->operator; $b=$this->actor('Secretaria'); $c=$this->actor('Secretaria');
        $lead=$this->createLead(); $this->actingAs($b); $this->activity($lead);
        foreach ([2,5,2] as $stage) { $this->postJson(route('crm.mover',$lead),['version'=>$lead->fresh()->crm_version,'etapa_id'=>$stage])->assertOk(); }
        $this->actingAs($a)->post(route('crm.reasignar',$lead),['version'=>$lead->fresh()->crm_version,'responsable_id'=>$b->id,'observacion'=>'Continúa el seguimiento.'])->assertRedirect();
        $person=$this->persona(); $enrollment=DB::table('inscripciones')->insertGetId(['estudiante_id'=>$person->estudiante->id]);
        $this->actingAs($c)->post(route('crm.convertir',$lead),['version'=>$lead->fresh()->crm_version,'persona_id'=>$person->id,'estudiante_persona_id'=>$person->id,'tipo'=>'inscripcion','inscripcion_id'=>$enrollment,'confirmar_vinculos'=>1])->assertRedirect();
        $this->assertSame($c->id,$lead->fresh()->convertido_por); $this->assertSame($b->id,$lead->fresh()->responsable_conversion_id);
        $this->actingAs($a)->post(route('crm.reasignar',$lead),['version'=>$lead->fresh()->crm_version,'responsable_id'=>$c->id,'observacion'=>'Atención posterior a la venta.'])->assertRedirect();
        $stats=app(CrmUserAnalytics::class)->report([]); $rows=$stats['rows']->keyBy('id');
        $this->assertSame(1,$rows[$a->id]['registered']); $this->assertSame(0,$rows[$a->id]['conversions_owned']);
        $this->assertSame(1,$rows[$b->id]['activities']); $this->assertSame(3,$rows[$b->id]['stage_changes']); $this->assertSame(1,$rows[$b->id]['moved_leads']);
        $this->assertSame(1,$rows[$b->id]['conversions_owned']); $this->assertSame(100.0,$rows[$b->id]['rate']);
        $this->assertSame(1,$rows[$c->id]['conversions_done']); $this->assertSame(0,$rows[$c->id]['conversions_owned']); $this->assertSame(0,$rows[$c->id]['received']);
        $this->assertSame(1,$stats['totals']['moved_leads']); $this->assertSame(1,$stats['totals']['conversions_done']);
        $filtered=app(CrmUserAnalytics::class)->report(['usuario_id'=>$b->id,'interest_id'=>2,'crm_sede_id'=>1]);
        $this->assertCount(1,$filtered['rows']); $this->assertSame(1,$filtered['totals']['activities']);
        $this->assertSame(0,app(CrmUserAnalytics::class)->report(['crm_sede_id'=>3])['totals']['conversions_done']);
        $this->assertSame($a->id,$lead->fresh()->creado_por); $this->assertSame($b->id,$lead->fresh()->responsable_conversion_id);
    }
    public function test_general_statistics_require_permission_but_operators_can_see_their_own(): void
    {
        $this->operator->name='Administrador reservado'; $this->operator->save(); $lead=$this->createLead();
        $b=$this->actor('Secretaria'); $this->actingAs($b); $this->activity($lead);
        $this->get(route('crm.dashboard'))->assertOk()->assertSee('Mis estadísticas')->assertDontSee('Administrador reservado');
        $this->get(route('crm.dashboard',['usuario_id'=>$this->operator->id]))->assertForbidden();
        $this->actingAs($this->operator)->get(route('crm.dashboard',['usuario_id'=>$b->id]))->assertOk()->assertViewHas('userStats',function ($stats) use ($b) { return $stats['rows']->count()===1&&$stats['rows']->first()['id']===$b->id; });
    }
    public function test_followup_age_only_resets_for_contact_activities_and_stages_never_create_sales(): void
    {
        $lead=$this->lead(['created_at'=>now()->subDays(10),'ultimo_seguimiento_at'=>now()->subDays(6)]);
        $this->assertSame(6,$lead->dias_sin_seguimiento);
        $this->activity($lead,['tipo'=>'observacion']); $this->assertSame(6,$lead->fresh()->dias_sin_seguimiento);
        $this->postJson(route('crm.mover',$lead),['version'=>$lead->fresh()->crm_version,'etapa_id'=>2])->assertOk();
        $this->assertSame(6,$lead->fresh()->dias_sin_seguimiento);
        $this->activity($lead); $this->assertSame(0,$lead->fresh()->dias_sin_seguimiento);
        $this->postJson(route('crm.mover',$lead),['version'=>$lead->fresh()->crm_version,'etapa_id'=>CrmEtapa::where('conversion',true)->value('id')])->assertUnprocessable();
        $this->assertSame(0,app(CrmAnalytics::class)->report([])['converted']);
        $this->assertSame(0,app(CrmUserAnalytics::class)->report([])['totals']['conversions_done']);
    }
    public function test_backfill_preserves_legacy_services_links_and_unknown_ownership_without_restoring_removed_services(): void
    {
        $person=$this->persona(['habilitado'=>2]); $person->interests()->sync([1,2]);
        $lead=$this->lead(['legacy_persona_id'=>$person->id,'persona_id'=>$person->id,'interest_id'=>1,'crm_services_migrated_at'=>null,
            'responsable_id'=>null,'creado_por'=>null,'responsable_inicial_id'=>null,'resourseName'=>'people/preserved','created_at'=>'2020-01-01 00:00:00']);
        $actor=$this->actor('Secretaria'); $observation=$lead->observaciones()->create(['observacion'=>'Alta anterior','activo'=>1]); $observation->usuarios()->attach($actor->id);
        CrmActividadDetalle::create(['observacion_id'=>$observation->id,'tipo'=>'alta','ocurrio_at'=>'2020-01-01 00:00:00','responsable_id'=>$this->operator->id]);
        foreach ([0,1] as $repeat) { app(CrmHistoryBackfill::class)->run(); }
        $this->assertSame([1,2],$lead->interests()->orderBy('interests.id')->pluck('interests.id')->all());
        $current=$lead->fresh(); $this->assertNull($current->responsable_id); $this->assertNull($current->responsable_inicial_id);
        $this->assertSame($actor->id,$current->creado_por); $this->assertSame('people/preserved',$current->resourseName);
        $this->assertSame('2020-01-01 00:00:00',$current->getRawOriginal('created_at'));
        $event=CrmActividadDetalle::sole(); $this->assertSame($actor->id,$event->actor_id); $this->assertNull($event->responsable_comercial_id);
        $lead->interests()->sync([2]); app(CrmHistoryBackfill::class)->run();
        $this->assertSame([2],$lead->interests()->pluck('interests.id')->all()); Queue::assertNothingPushed(); Http::assertNothingSent();
    }
}

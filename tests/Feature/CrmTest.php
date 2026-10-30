<?php
namespace Tests\Feature;
use App\Models\{Prospecto,Persona,CrmEtapa,CrmSede,CrmSeguimiento,Observacion};
use App\Services\{CrmService,CrmAnalytics};
use Illuminate\Support\Facades\{DB,Http,Queue,Cache};
use App\Jobs\SyncGoogleContact;
use Tests\CrmTestCase;
class CrmTest extends CrmTestCase
{
    public function test_catalogs_permissions_and_all_crm_views_are_available_to_the_commercial_team(): void
    {
        $lead=$this->lead(['nombre'=>'María Pérez','responsable_id'=>$this->operator->id]);
        $this->assertSame(['IFE Central','IFE Villa','IFE Plan'],CrmSede::orderBy('id')->pluck('nombre')->all());
        $this->assertSame(1,CrmEtapa::whereNotNull('inicial')->count());
        foreach (['crm.tablero','crm.lista','crm.dashboard','crm.configuracion','prospecto.create'] as $route) { $this->get(route($route))->assertOk(); }
        $this->get(route('prospecto.view',$lead))->assertOk()->assertSee('María Pérez')->assertSee('Registrar actividad');
        $this->get(route('prospecto.edit',$lead))->assertOk()->assertSee('Servicios de interés');
        $this->actingAs($this->actor('Padre'))->get(route('crm.tablero'))->assertForbidden();
        $this->get(route('crm.personas',['q'=>'Mar']))->assertForbidden();
        $this->post(route('crm.mover',$lead),['version'=>0,'etapa_id'=>2])->assertForbidden();
        $secretary=$this->actor('Secretaria'); $this->actingAs($secretary)->get(route('crm.tablero'))->assertOk();
        $this->get(route('crm.configuracion'))->assertForbidden();
        Http::assertNothingSent(); Queue::assertNothingPushed();
    }
    public function test_quick_creation_and_all_optional_fields_are_preserved_on_edit(): void
    {
        $this->account();
        $this->post(route('prospecto.store'),['telefono'=>'+591 70000001'])->assertRedirect();
        $lead=Prospecto::sole(); $this->assertSame('70000001',$lead->telefono); $this->assertNull($lead->nombre);
        $this->assertSame($this->operator->id,$lead->responsable_id);
        $data=['telefono'=>'70000001','nombre'=>'Juan Pérez','interests'=>[1],'como_id'=>1,'grado_id'=>1,'crm_sede_id'=>2,
            'estudiante_nombre'=>'Ana Pérez','observacion'=>'Consulta sobre horarios.',
            'proximo_contacto_at'=>now()->addDay()->format('Y-m-d H:i:s'),'version'=>$lead->crm_version];
        $this->patch(route('prospecto.update',$lead),$data)->assertRedirect(route('prospecto.view',$lead));
        $this->assertDatabaseHas('prospectos',['id'=>$lead->id,'nombre'=>'Juan Pérez','crm_sede_id'=>2,'estudiante_nombre'=>'Ana Pérez']);
        $this->get(route('prospecto.view',$lead))->assertSee('IFE Villa')->assertSee('Ana Pérez')->assertSee('Consulta sobre horarios.');
        $this->assertSame(2,$lead->observaciones()->count());
        Queue::assertPushed(SyncGoogleContact::class); Http::assertNothingSent();
    }
    public function test_duplicate_phone_warning_requires_review_and_never_merges_family_members(): void
    {
        $person=$this->persona(['telefono'=>'+591 70000001']); $lead=$this->lead(['telefono'=>'70000001']);
        $this->getJson(route('crm.coincidencias',['telefono'=>'+59170000001']))->assertOk()->assertJsonCount(2,'matches');
        $this->postJson(route('prospecto.store'),['telefono'=>'70000001'])->assertUnprocessable()->assertJsonValidationErrors('confirmar_coincidencias');
        $this->post(route('prospecto.store'),['telefono'=>'70000001','confirmar_coincidencias'=>1])->assertRedirect();
        $this->assertSame(2,Prospecto::count()); $this->assertSame(1,Persona::count());
        $this->assertNull($lead->fresh()->persona_id); $this->assertSame('+591 70000001',$person->fresh()->telefono);
        $this->postJson(route('prospecto.store'),['telefono'=>'123'])->assertUnprocessable();
    }
    public function test_stage_changes_are_audited_and_stale_or_invalid_moves_do_not_write(): void
    {
        $lead=$this->lead(); $stage=CrmEtapa::where('codigo','seguimiento')->first();
        $this->postJson(route('crm.mover',$lead),['etapa_id'=>$stage->id,'version'=>0])->assertOk()->assertJsonPath('version',1);
        $this->assertSame($stage->id,$lead->fresh()->crm_etapa_id); $this->assertSame(1,$lead->observaciones()->count());
        $this->postJson(route('crm.mover',$lead),['etapa_id'=>1,'version'=>0])->assertStatus(409);
        $this->postJson(route('crm.mover',$lead),['etapa_id'=>CrmEtapa::where('conversion',true)->value('id'),'version'=>1])->assertUnprocessable();
        $stage->update(['activa'=>false]);
        $this->postJson(route('crm.mover',$lead),['etapa_id'=>$stage->id,'version'=>1])->assertUnprocessable();
        $this->assertSame(1,$lead->observaciones()->count()); Http::assertNothingSent();
    }
    public function test_scheduling_and_human_confirmed_activities_do_not_treat_whatsapp_opening_as_sent(): void
    {
        $lead=$this->lead(['responsable_id'=>$this->operator->id]);
        $before=$lead->fresh()->getAttributes();
        $this->getJson(route('crm.whatsapp',[$lead,'plantilla'=>'primer_contacto']))->assertOk()->assertJsonPath('mensaje','Hola. Le saludamos del Instituto IFE. ¿Podemos ayudarle con su consulta?');
        $this->assertSame(0,$lead->observaciones()->count()); $this->assertSame($before,$lead->fresh()->getAttributes());
        $data=['version'=>0,'tipo'=>'whatsapp','ocurrio_at'=>now()->subMinute()->toDateTimeString(),'responsable_id'=>$this->operator->id,'resultado'=>'enviado_confirmado'];
        $this->postJson(route('crm.actividad',$lead),$data)->assertUnprocessable()->assertJsonValidationErrors('confirmado');
        $data['confirmado']=1; $data['proximo_contacto_at']=now()->addDay()->toDateTimeString(); $data['proxima_accion']='Resolver dudas';
        $this->post(route('crm.actividad',$lead),$data)->assertRedirect();
        $this->assertSame(1,$lead->observaciones()->count()); $this->assertNotNull($lead->fresh()->contactado_at); $this->assertNotNull($lead->fresh()->proximo_contacto_at);
        $this->assertDatabaseHas('crm_actividad_detalles',['tipo'=>'whatsapp','resultado'=>'enviado_confirmado','proxima_accion'=>'Resolver dudas']);
        Http::assertNothingSent();
    }
    public function test_baja_and_reactivation_keep_history_and_opt_out_until_new_permission(): void
    {
        $person=$this->persona(['votos'=>1]); $lead=$this->lead(['persona_id'=>$person->id]);
        $this->post(route('crm.baja',$lead),['version'=>0,'motivo'=>'no_mensajes','observacion'=>'Lo solicitó en recepción.'])->assertRedirect();
        $this->assertSame('baja',$lead->fresh()->crm_status); $this->assertTrue($lead->fresh()->no_contactar); $this->assertSame(0,(int)$person->fresh()->votos);
        $person->votos=1; $person->save(); $this->assertSame(0,(int)$person->fresh()->votos);
        $this->post(route('crm.reactivar',$lead),['version'=>$lead->fresh()->crm_version])->assertRedirect();
        $this->assertTrue($lead->fresh()->no_contactar);
        $this->getJson(route('crm.whatsapp',[$lead,'plantilla'=>'seguimiento']))->assertUnprocessable();
        $this->post(route('crm.baja',$lead),['version'=>$lead->fresh()->crm_version,'motivo'=>'otro','observacion'=>'Visita pendiente.'])->assertRedirect();
        $this->post(route('crm.reactivar',$lead),['version'=>$lead->fresh()->crm_version,'autoriza_contacto'=>1])->assertRedirect();
        $this->assertFalse($lead->fresh()->no_contactar); $this->assertSame(1,(int)$person->fresh()->votos);
        $this->assertSame(4,$lead->observaciones()->count());
        $this->delete(route('prospecto.delete',$lead))->assertForbidden(); $this->assertNotNull($lead->fresh());
    }
    public function test_prepare_legacy_links_potenciales_once_without_google_jobs_or_destroying_old_history(): void
    {
        $person=$this->persona(['habilitado'=>2,'nombre'=>'Potencial antiguo','como_id'=>1,'votos'=>0,'vuelvefecha'=>'2026-11-05']);
        $person->interests()->attach(1);
        $old=$person->observaciones()->create(['observacion'=>'Consulta original','activo'=>1]);
        CrmSeguimiento::create(['persona_id'=>$person->id,'user_id'=>$this->operator->id,'accion'=>'posponer','proximo_contacto'=>'2026-11-05']);
        $this->assertSame(1,app(CrmService::class)->prepareLegacy()); $this->assertSame(0,app(CrmService::class)->prepareLegacy());
        $lead=Prospecto::sole(); $this->assertSame($person->id,$lead->persona_id); $this->assertTrue($lead->no_contactar);
        $this->assertSame(1,$lead->interest_id); $this->assertSame(1,CrmSeguimiento::count()); $this->assertNotNull($old->fresh());
        $this->assertSame('2026-11-05 00:00:00',$lead->proximo_contacto_at->format('Y-m-d H:i:s'));
        $this->assertNull($lead->responsable_id);
        $this->get(route('prospecto.view',$lead))->assertOk()->assertSee('Consulta original')->assertSee('posponer');
        Queue::assertNothingPushed(); Http::assertNothingSent();
    }
    public function test_prepare_command_can_be_repeated_without_resetting_catalogs_or_exporting_legacy_people(): void
    {
        \Illuminate\Support\Facades\Schema::create('migrations',function ($table) { $table->increments('id'); $table->string('migration'); $table->integer('batch'); });
        DB::table('migrations')->insert([['migration'=>'2026_10_29_000001_extend_prospectos_crm','batch'=>1],['migration'=>'2026_10_30_000001_extend_crm_ownership_and_interests','batch'=>1]]);
        $person=$this->persona(['habilitado'=>2,'votos'=>null]);
        $stage=app(CrmService::class)->initial(); $stage->nombre='Primer ingreso'; $stage->save();
        foreach ([0,1] as $repeat) { $this->assertSame(0,\Illuminate\Support\Facades\Artisan::call('crm:prepare',['--force'=>true])); }
        $this->assertSame(1,Prospecto::count()); $this->assertSame(3,CrmSede::count());
        $lead=Prospecto::sole(); $this->assertSame($person->id,$lead->persona_id);
        $this->assertFalse($lead->no_contactar); $this->assertTrue(app(CrmService::class)->mayContact($lead));
        $this->assertSame('Primer ingreso',$stage->fresh()->nombre); Queue::assertNothingPushed(); Http::assertNothingSent();
    }
    public function test_activity_history_uses_the_event_date_and_editing_preserves_the_previous_note(): void
    {
        $lead=$this->lead(['observacion'=>'Consulta previa']);
        $this->patch(route('prospecto.update',$lead),['version'=>0,'telefono'=>$lead->telefono,'observacion'=>'Consulta actual'])->assertRedirect();
        foreach (['ayer'=>now()->subDay(),'antes'=>now()->subDays(2)] as $note=>$date) {
            // Existing backdated history keeps its event order. New UI activities use server time.
            app(CrmService::class)->log($lead,$this->operator->id,'observacion',$note,['ocurrio_at'=>$date]);
        }
        $this->get(route('prospecto.view',$lead))->assertOk()->assertSeeInOrder(['Consulta actual','Consulta previa','ayer','antes']);
        $this->assertSame('Consulta previa',\App\Models\CrmActividadDetalle::where('tipo','edicion')->first()->datos['observacion_anterior']);
    }
    public function test_generic_observation_routes_cannot_edit_or_delete_typed_crm_history(): void
    {
        foreach (['Editar Observaciones','Eliminar Observaciones'] as $name) { $this->operator->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate(['name'=>$name,'guard_name'=>'web'])); }
        $lead=$this->lead(); app(CrmService::class)->log($lead,$this->operator->id,'observacion','Gestión confirmada');
        $entry=$lead->observaciones()->sole();
        $this->getJson(route('observacion.actualizar',['observacion_id'=>$entry->id,'observacion'=>'Cambio no permitido']))->assertUnprocessable();
        $this->deleteJson(route('eliminar.observacion',$entry))->assertUnprocessable();
        $this->assertSame('Gestión confirmada',$entry->fresh()->observacion);
        $this->assertNotNull($entry->fresh()->crmDetalle);
        $old=$lead->observaciones()->create(['observacion'=>'Nota anterior','activo'=>1]);
        $this->getJson(route('observacion.actualizar',['observacion_id'=>$old->id,'observacion'=>'Nota anterior corregida']))->assertOk();
        $this->assertSame('Nota anterior corregida',$old->fresh()->observacion);
    }
    public function test_new_potenciales_from_personas_enter_the_same_board_after_commit_without_a_second_google_source(): void
    {
        $this->account();
        $person=$this->persona(['habilitado'=>1]);
        DB::transaction(function () use ($person) {
            $person->habilitado=2; $person->save();
            $this->assertSame(0,Prospecto::count());
        });
        $lead=Prospecto::sole(); $this->assertSame($person->id,$lead->persona_id);
        $this->assertFalse($this->service()->eligibleQuery('prospecto')->whereKey($lead->id)->exists());
        Queue::assertPushed(SyncGoogleContact::class,function ($job) use ($person) { return $job->source==='persona'&&$job->personaId===$person->id; });
        Queue::assertNotPushed(SyncGoogleContact::class,function ($job) { return $job->source==='prospecto'; });
        $person->habilitado=3; $person->save(); $this->assertSame(1,Prospecto::count());
        $this->get(route('crm.tablero'))->assertOk()->assertSee($lead->identificacion);
        $other=$this->persona(['habilitado'=>1]);
        try { DB::transaction(function () use ($other) { $other->habilitado=2; $other->save(); throw new \RuntimeException('Rollback fixture'); }); }
        catch (\RuntimeException $exception) { $this->assertSame('Rollback fixture',$exception->getMessage()); }
        $this->assertSame(1,Prospecto::count()); Http::assertNothingSent();
    }
    public function test_conversion_requires_real_enrollment_and_can_link_a_parent_to_a_different_student(): void
    {
        $this->account(); $parent=$this->persona(['nombre'=>'Mamá','telefono'=>'70000001']); $student=$this->persona(['nombre'=>'Hija']);
        $lead=$this->lead(['resourseName'=>'people/parent']);
        $data=['version'=>0,'persona_id'=>$parent->id,'estudiante_persona_id'=>$student->id,'tipo'=>'inscripcion','inscripcion_id'=>999,'confirmar_vinculos'=>1,'parentesco'=>'Mamá'];
        $this->postJson(route('crm.convertir',$lead),$data)->assertUnprocessable(); $this->assertSame('activo',$lead->fresh()->crm_status);
        $id=DB::table('inscripciones')->insertGetId(['estudiante_id'=>$student->estudiante->id]); $data['inscripcion_id']=$id;
        $this->post(route('crm.convertir',$lead),$data)->assertRedirect();
        $this->assertSame('convertido',$lead->fresh()->crm_status); $this->assertSame($parent->id,$lead->fresh()->persona_id);
        $this->assertDatabaseHas('persona_persona',['persona_id'=>$student->id,'persona_id_apoderado'=>$parent->id,'parentesco'=>'Mamá']);
        $this->assertSame(1,$lead->observaciones()->count());
        $this->assertFalse($this->service()->eligibleQuery('prospecto')->whereKey($lead->id)->exists());
        Queue::assertPushed(SyncGoogleContact::class,function ($job) use ($parent) { return $job->source==='persona'&&$job->personaId===$parent->id; });
        $this->assertNull($parent->fresh()->resourseName); // Handoff happens under the Google worker lock, not via HTTP in conversion.
        $remote=$this->remote('people/parent')+['userDefined'=>[['key'=>'IFE_PROSPECTO','value'=>$this->service()->account()->installation_id.':'.$lead->id]]];
        $this->fakeHttp(['*people/parent*'=>Http::response($remote)]); $this->service()->sync($parent->id);
        $this->assertSame('people/parent',$parent->fresh()->resourseName); Http::assertSentCount(2);
        Http::assertNotSent(function ($request) { return $request->method()==='POST'; });
    }
    public function test_conversion_accepts_an_existing_computation_enrollment_for_the_selected_student(): void
    {
        $student=$this->persona(); $lead=$this->lead(['persona_id'=>$student->id]);
        $role=DB::table('computacions')->insertGetId(['persona_id'=>$student->id]);
        $enrollment=DB::table('matriculacions')->insertGetId(['computacion_id'=>$role]);
        $this->post(route('crm.convertir',$lead),['version'=>0,'persona_id'=>$student->id,'estudiante_persona_id'=>$student->id,
            'tipo'=>'matriculacion','inscripcion_id'=>$enrollment,'confirmar_vinculos'=>1])->assertRedirect();
        $this->assertSame('convertido',$lead->fresh()->crm_status); $this->assertSame($enrollment,$lead->fresh()->matriculacion_id);
        $this->assertNull($lead->fresh()->inscripcione_id); Http::assertNothingSent();
    }
    public function test_conversion_preserves_both_links_when_google_resources_conflict_and_respects_the_account_lock(): void
    {
        $student=$this->persona(['resourseName'=>'people/client']); $lead=$this->lead(['resourseName'=>'people/prospect']);
        $id=DB::table('inscripciones')->insertGetId(['estudiante_id'=>$student->estudiante->id]);
        $data=['version'=>0,'persona_id'=>$student->id,'estudiante_persona_id'=>$student->id,'tipo'=>'inscripcion','inscripcion_id'=>$id,'confirmar_vinculos'=>1];
        $this->postJson(route('crm.convertir',$lead),$data)->assertUnprocessable();
        $this->assertSame('people/client',$student->fresh()->resourseName); $this->assertSame('people/prospect',$lead->fresh()->resourseName); $this->assertSame(0,$lead->observaciones()->count());
        $lock=Cache::lock('ife:gcontact:central',180); $lock->get();
        try { $this->postJson(route('crm.convertir',$lead),$data)->assertStatus(409); } finally { $lock->release(); }
        $this->assertSame('activo',$lead->fresh()->crm_status); Queue::assertNothingPushed(); Http::assertNothingSent();
    }
    public function test_cohort_conversion_rate_and_period_conversions_are_separate_and_filters_apply(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-29 12:00:00'));
        $this->lead(['created_at'=>'2026-09-01 00:00:00','convertido_at'=>'2026-10-10 00:00:00','inscripcione_id'=>1,'crm_status'=>'convertido','crm_sede_id'=>1]);
        $this->lead(['created_at'=>'2026-10-01 00:00:00','convertido_at'=>'2026-10-11 00:00:00','inscripcione_id'=>2,'crm_status'=>'convertido','crm_sede_id'=>1]);
        $this->lead(['created_at'=>'2026-10-12 00:00:00','crm_sede_id'=>1,'proximo_contacto_at'=>'2026-10-28 12:00:00']);
        $this->lead(['created_at'=>'2026-10-15 00:00:00','crm_sede_id'=>2]);
        $stats=app(CrmAnalytics::class)->report(['desde'=>'2026-10-01','hasta'=>'2026-10-29','crm_sede_id'=>1]);
        $this->assertSame(2,$stats['created']); $this->assertSame(2,$stats['converted']); $this->assertSame(50.0,$stats['rate']);
        $this->assertSame(24.5,$stats['average_days']); $this->assertSame(1,$stats['overdue']);
        $this->get(route('crm.lista',['crm_sede_id'=>2,'desde'=>'2026-10-01','hasta'=>'2026-10-29']))->assertOk()->assertViewHas('records',function ($rows) { return $rows->total()===1; });
    }
    public function test_period_filters_accept_an_end_date_alone_and_reject_inverted_ranges(): void
    {
        $this->lead();
        $this->get(route('crm.lista',['hasta'=>today()->toDateString()]))->assertOk();
        $this->getJson(route('crm.dashboard',['desde'=>'2026-10-29','hasta'=>'2026-10-01']))->assertUnprocessable();
    }
    public function test_reactivation_keeps_period_deactivation_reasons_in_the_dashboard(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-29 12:00:00'));
        $lead=$this->lead(['crm_sede_id'=>1]);
        $this->post(route('crm.baja',$lead),['version'=>0,'motivo'=>'otro','observacion'=>'Por viaje.'])->assertRedirect();
        $this->post(route('crm.reactivar',$lead),['version'=>1])->assertRedirect();
        $stats=app(CrmAnalytics::class)->report(['desde'=>'2026-10-01','hasta'=>'2026-10-29','crm_sede_id'=>1]);
        $this->assertSame(0,$stats['inactive']); $this->assertSame(1,$stats['deactivations']);
        $this->assertSame('otro',$stats['reasons']->first()->motivo_baja);
        $this->assertSame(1,$stats['reasons']->first()->total);
        $this->assertSame(0,app(CrmAnalytics::class)->report(['crm_sede_id'=>2])['deactivations']);
    }
    public function test_initial_stage_cannot_be_disabled_and_concurrent_configuration_is_rejected(): void
    {
        $initial=app(CrmService::class)->initial();
        $data=['nombre'=>$initial->nombre,'color'=>'#375F7A','orden'=>1,'activa'=>0,'inicial'=>1,'conversion'=>0,'version'=>$initial->version];
        $this->postJson(route('crm.configurar.etapa',$initial),$data)->assertUnprocessable();
        $target=CrmEtapa::where('codigo','contacto')->first();
        $data['nombre']='Contacto inicial'; $data['activa']=1; $data['version']=$target->version;
        $this->post(route('crm.configurar.etapa',$target),$data)->assertRedirect();
        $this->assertSame($target->id,app(CrmService::class)->initial()->id); $this->assertSame(1,CrmEtapa::whereNotNull('inicial')->count());
        $this->postJson(route('crm.configurar.etapa',$target),$data)->assertStatus(409);
    }
    public function test_existing_contact_form_links_the_new_person_before_dispatching_google_and_keeps_the_prospect_history(): void
    {
        $this->account(); $lead=$this->lead(['nombre'=>'Ana Pérez','resourseName'=>'people/existing']);
        Queue::swap($this->realQueue);
        $this->post(route('personas.store.contacto'),['nombre'=>'Ana','apellidop'=>'Pérez','telefono'=>'70000001','como_id'=>1,
            'observacion'=>'Consulta inicial','crm_prospecto_id'=>$lead->id,'crm_prospecto_version'=>0])->assertRedirect(route('prospecto.view',$lead));
        $person=Persona::sole(); $this->assertSame($person->id,$lead->fresh()->persona_id); $this->assertSame('people/existing',$lead->fresh()->resourseName);
        $this->assertSame(1,$lead->observaciones()->count()); $this->assertSame(1,DB::table('jobs')->count());
        $this->assertFalse($this->service()->eligibleQuery('prospecto')->whereKey($lead->id)->exists());
        $person->telefono='70000002'; $person->save(); $this->assertSame('70000002',$lead->fresh()->telefono);
        $this->patchJson(route('prospecto.update',$lead),['version'=>$lead->fresh()->crm_version,'telefono'=>'70000003','confirmar_coincidencias'=>1])->assertUnprocessable();
        $this->assertSame('70000002',$person->fresh()->telefono); Http::assertNothingSent();
    }
    public function test_failed_new_contact_link_rolls_back_person_and_jobs(): void
    {
        $this->account(); $lead=$this->lead(['crm_version'=>1]); Queue::swap($this->realQueue);
        $this->postJson(route('personas.store.contacto'),['nombre'=>'Ana','apellidop'=>'Pérez','telefono'=>'70000001','como_id'=>1,
            'observacion'=>'Consulta','crm_prospecto_id'=>$lead->id,'crm_prospecto_version'=>0])->assertStatus(409);
        $this->assertSame(0,Persona::count()); $this->assertSame(0,DB::table('jobs')->count()); $this->assertNull($lead->fresh()->persona_id);
    }
}

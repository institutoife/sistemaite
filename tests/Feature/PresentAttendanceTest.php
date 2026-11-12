<?php
namespace Tests\Feature;

use App\Models\{AsistenciaSalidaAviso, Clase, Clasecom, ClaseAsistenciaEvento};
use App\Services\{ClassAttendanceService, PresentAttendanceService, SedeAccess};
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Spatie\Permission\Models\Permission;
use Tests\AttendanceTestCase;

class PresentAttendanceTest extends AttendanceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once database_path('migrations/2026_11_03_000001_create_asistencia_salida_avisos.php');
        (new \CreateAsistenciaSalidaAvisos)->up();
    }

    protected function present(string $channel='apoyo', array $data=[], $enrollment=null)
    {
        $enrollment=$enrollment ?? ($channel==='apoyo'?$this->enrollment:$this->enroll('computacion'));
        $program=$this->program($enrollment,['fecha'=>'2026-11-02','estado_id'=>estado('PRESENTE')]);
        $attendance=$channel==='apoyo'?new Clase:new Clasecom;
        $attendance->forceFill(array_merge(['fecha'=>'2026-11-02','horainicio'=>'09:00','horafin'=>'10:00',
            'docente_id'=>$this->teacher,'aula_id'=>$this->room,'estado_id'=>estado('PRESENTE'),
            $program->attendanceKey()=>$program->id],$channel==='apoyo'?['materia_id'=>$this->subject]:[],$data))->save();
        return $attendance;
    }

    public function test_lists_both_circuits_without_actor_and_does_not_duplicate_multiple_actors(): void
    {
        $support=$this->present();$computing=$this->present('computacion');
        $support->usuarios()->attach([$this->operator->id,$this->actor('Secretaria')->id]);
        $response=$this->getJson(route('presentes.estado'))->assertOk()->assertJsonCount(2,'data');
        $this->assertEqualsCanonicalizing(['apoyo:'.$support->id,'computacion:'.$computing->id],array_column($response->json('data'),'key'));
        $this->assertSame('2026-11-02T10:00:00-04:00',$response->json('server_now'));
        $this->assertSame('Computación',$response->json('data.1.materia'));
        $this->assertStringContainsString('no-store',$response->headers->get('Cache-Control'));
        $this->getJson(route('clases.presente'))->assertOk()->assertJsonCount(1,'data');
        $this->getJson(route('clasescom.presente'))->assertOk()->assertJsonCount(1,'data');
    }

    public function test_only_open_attendance_is_listed_including_previous_days(): void
    {
        $this->present('apoyo',['estado_id'=>estado('FINALIZADO')]);
        $old=$this->present('apoyo',['fecha'=>'2026-11-01']);
        $this->present('computacion',['fecha'=>'2026-11-03']);
        $this->getJson(route('presentes.estado'))->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.key','apoyo:'.$old->id)
            ->assertJsonPath('data.0.inicio','2026-11-01T09:00:00-04:00');
    }

    public function test_additional_recovery_advance_and_extra_are_listed_in_both_circuits(): void
    {
        $hour=10;
        foreach(['apoyo','computacion'] as $channel) foreach(['recuperacion','adelanto','extra'] as $type) {
            $enrollment=$this->enroll($channel);
            $original=$this->program($enrollment,['fecha'=>$type==='recuperacion'?'2026-10-28':'2026-11-05',
                'estado_id'=>estado($type==='recuperacion'?'LICENCIA':'INDEFINIDO')]);
            $hour++;
            app(ClassAttendanceService::class)->create($channel,$enrollment->id,$this->data($original,['tipo'=>$type,'marcar_entrada'=>0,
                'horainicio'=>sprintf('%02d:00',$hour),'horafin'=>sprintf('%02d:00',$hour+1)]),$this->operator);
            $effective=\App\Models\ClaseAdicional::orderByDesc('id')->first()->programa;
            $class=$channel==='apoyo'?new Clase:new Clasecom;
            $class->forceFill([$effective->attendanceKey()=>$effective->id,'fecha'=>'2026-11-02','horainicio'=>'11:00','horafin'=>'12:00',
                'docente_id'=>$this->teacher,'aula_id'=>$this->room,'estado_id'=>estado('PRESENTE')]+($channel==='apoyo'?['materia_id'=>$this->subject]:[]))->save();
        }
        $response=$this->getJson(route('presentes.estado'))->assertOk()->assertJsonCount(6,'data');
        $this->assertEqualsCanonicalizing(['Recuperación','Adelanto','Extra','Recuperación','Adelanto','Extra'],array_column($response->json('data'),'tipo'));
    }

    public function test_separate_enrollments_and_classes_of_same_person_keep_distinct_keys(): void
    {
        $one=$this->present();$two=$this->present('apoyo',[],$this->enroll());$three=$this->present('computacion');
        $response=$this->getJson(route('presentes.estado'))->assertOk()->assertJsonCount(3,'data');
        $this->assertCount(3,array_unique(array_column($response->json('data'),'key')));
        $this->postJson(route('clases.finalizar'),['id'=>$two->id])->assertOk();
        $this->getJson(route('presentes.estado'))->assertOk()->assertJsonCount(2,'data');
        $this->assertEquals(estado('PRESENTE'),$one->fresh()->estado_id);
        $this->assertEquals(estado('PRESENTE'),$three->fresh()->estado_id);
    }

    public function test_notifying_preserves_attendance_and_audits_actor_once(): void
    {
        $attendance=$this->present();$url=route('presentes.aviso',['apoyo',$attendance->id]);
        $this->postJson($url)->assertOk();$this->postJson($url)->assertOk();
        $this->assertEquals(estado('PRESENTE'),$attendance->fresh()->estado_id);
        $this->assertSame(1,AsistenciaSalidaAviso::count());
        $event=ClaseAsistenciaEvento::where('accion','aviso_salida')->sole();
        $this->assertEquals($this->operator->id,$event->usuario_id);
        $this->assertEquals($attendance->id,$event->despues['asistencia_id']);
        $this->getJson(route('presentes.estado'))->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.avisado_at','2026-11-02T10:00:00-04:00');
    }

    public function test_snooze_is_persistent_and_per_user_and_cannot_close_class(): void
    {
        $attendance=$this->present('computacion');
        $this->postJson(route('presentes.posponer',['computacion',$attendance->id]),['minutos'=>5])->assertOk();
        $this->getJson(route('presentes.estado'))->assertJsonPath('data.0.pospuesto_hasta','2026-11-02T10:05:00-04:00');
        $other=$this->actor('Secretaria');$other->givePermissionTo(['Listar Clasescom','Editar Clasescom']);$this->actingAs($other);
        $this->getJson(route('presentes.estado'))->assertJsonPath('data.0.pospuesto_hasta',null);
        $this->actingAs($this->operator);Carbon::setTestNow('2026-11-02 10:06:00');
        $response=$this->getJson(route('presentes.estado'))->assertJsonCount(1,'data');
        $this->assertTrue(Carbon::parse($response->json('data.0.pospuesto_hasta'))->lt(Carbon::parse($response->json('server_now'))));
        $this->assertEquals(estado('PRESENTE'),$attendance->fresh()->estado_id);
        $this->assertSame(1,ClaseAsistenciaEvento::where('accion','salida_pospuesta')->count());
    }

    public function test_notify_and_snooze_validate_state_time_channel_and_duration(): void
    {
        $attendance=$this->present('apoyo',['horafin'=>'11:00']);
        $this->postJson(route('presentes.aviso',['apoyo',$attendance->id]))->assertUnprocessable();
        $this->postJson(route('presentes.posponer',['apoyo',$attendance->id]),['minutos'=>1000])->assertUnprocessable();
        $this->postJson(route('presentes.aviso',['unknown',$attendance->id]))->assertNotFound();
        $this->postJson(route('clases.finalizar'),['id'=>$attendance->id])->assertOk();
        $this->postJson(route('presentes.aviso',['apoyo',$attendance->id]))->assertUnprocessable();
        $this->assertSame(0,AsistenciaSalidaAviso::count());
    }

    public function test_exit_records_real_datetime_and_actor_once_without_changing_original_schedule(): void
    {
        foreach(['apoyo','computacion'] as $channel) {
            $attendance=$this->present($channel,['fecha'=>'2026-11-01']);
            $program=$channel==='apoyo'?$attendance->programacion:$attendance->programacioncom;
            $schedule=[$program->fecha->format('Y-m-d'),$program->{$channel==='apoyo'?'hora_fin':'horafin'}->format('H:i:s')];
            $url=route($channel==='apoyo'?'clases.finalizar':'clasecom.finalizar');
            $this->postJson($url,['id'=>$attendance->id])->assertOk();$this->postJson($url,['id'=>$attendance->id])->assertOk();
            $event=ClaseAsistenciaEvento::where($program->attendanceKey(),$program->id)->where('accion','salida')->sole();
            $this->assertSame('2026-11-02T10:00:00-04:00',$event->despues['salida_registrada_at']);
            $this->assertEquals($this->operator->id,$event->despues['salida_registrada_por']);
            $this->assertSame('10:00:00',$attendance->fresh()->horafin->format('H:i:s'));
            $this->assertSame($schedule,[$program->fresh()->fecha->format('Y-m-d'),$program->fresh()->{$channel==='apoyo'?'hora_fin':'horafin'}->format('H:i:s')]);
        }
        $this->getJson(route('presentes.estado'))->assertJsonCount(0,'data');
    }

    public function test_get_cannot_mark_exit_or_acknowledge_and_guest_cannot_read(): void
    {
        $attendance=$this->present();
        $this->getJson(route('clases.finalizar',['id'=>$attendance->id]))->assertStatus(405);
        $this->getJson(route('presentes.aviso',['apoyo',$attendance->id]))->assertStatus(405);
        $this->assertEquals(estado('PRESENTE'),$attendance->fresh()->estado_id);
        auth()->logout();$this->getJson(route('presentes.estado'))->assertUnauthorized();
    }

    public function test_list_permissions_do_not_grant_exit_or_notification_permissions(): void
    {
        $attendance=$this->present();$actor=$this->actor('Secretaria');$actor->givePermissionTo('Listar Clases');$this->actingAs($actor);
        $this->getJson(route('presentes.estado'))->assertOk()->assertJsonPath('data.0.can_finish',false);
        $this->postJson(route('presentes.aviso',['apoyo',$attendance->id]))->assertForbidden();
        $this->postJson(route('presentes.posponer',['apoyo',$attendance->id]),['minutos'=>5])->assertForbidden();
        $this->postJson(route('clases.finalizar'),['id'=>$attendance->id])->assertForbidden();
        $actor->revokePermissionTo('Listar Clases');$this->getJson(route('presentes.estado'))->assertForbidden();
        $this->get(route('clase.presentes'))->assertForbidden();
    }

    public function test_a_single_channel_permission_never_exposes_other_channel(): void
    {
        $this->present();$this->present('computacion');
        $actor=$this->actor('Secretaria');$actor->givePermissionTo('Listar Clasescom');$this->actingAs($actor);
        $this->getJson(route('presentes.estado'))->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.canal','computacion');
        $this->getJson(route('clases.presente'))->assertForbidden();
    }

    public function test_teacher_can_only_list_and_finish_own_attendances(): void
    {
        $own=$this->present();$other=$this->present('apoyo',['docente_id'=>DB::table('docentes')->insertGetId([
            'persona_id'=>$this->persona()->id,'estado_id'=>estado('HABILITADO'),'nombrecorto'=>'Otro docente'])]);
        $actor=$this->actor('Docente');$actor->givePermissionTo(['Listar Clases','Editar Clases']);
        $actor->persona_id=DB::table('docentes')->where('id',$this->teacher)->value('persona_id');$actor->save();$this->actingAs($actor);
        $this->getJson(route('presentes.estado'))->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.id',$own->id);
        $this->postJson(route('presentes.aviso',['apoyo',$other->id]))->assertForbidden();
        $this->postJson(route('clases.finalizar'),['id'=>$other->id])->assertForbidden();
        $this->postJson(route('clases.finalizar'),['id'=>$own->id])->assertOk();
    }

    public function test_family_is_denied_even_if_accidentally_given_list_permission(): void
    {
        $this->present();$actor=$this->actor('Padre');$actor->givePermissionTo('Listar Clases');$this->actingAs($actor);
        $this->getJson(route('presentes.estado'))->assertForbidden();
    }

    public function test_notification_query_count_is_bounded_with_multiple_students(): void
    {
        $this->present(); DB::enableQueryLog();$this->getJson(route('presentes.estado'))->assertOk();
        $small=count(DB::getQueryLog());DB::disableQueryLog();
        for($i=0;$i<12;$i++) $this->present();
        DB::flushQueryLog();DB::enableQueryLog();$this->getJson(route('presentes.estado'))->assertOk()->assertJsonCount(13,'data');
        $large=count(DB::getQueryLog());DB::disableQueryLog();
        $this->assertLessThanOrEqual($small+2,$large,'No debe consultar relaciones individualmente por estudiante.');
    }

    public function test_authorized_staff_can_list_and_manage_present_students_across_branches(): void
    {
        $own=$this->present();$foreign=$this->present('computacion');
        Schema::table('users',fn(Blueprint $t)=>$t->unsignedBigInteger('sede_id')->nullable());
        Schema::create('crm_sedes',function(Blueprint $t){$t->id();$t->string('nombre');$t->boolean('activa')->default(true);});
        Schema::create('sede_user',function(Blueprint $t){$t->unsignedBigInteger('user_id');$t->unsignedBigInteger('sede_id');});
        Schema::create('persona_sede',function(Blueprint $t){$t->unsignedBigInteger('persona_id');$t->unsignedBigInteger('sede_id');});
        Schema::create('sede_registros',function(Blueprint $t){$t->string('tabla');$t->unsignedBigInteger('registro_id');$t->unsignedBigInteger('sede_id');});
        DB::table('crm_sedes')->insert([['id'=>1,'nombre'=>'IFE Central'],['id'=>2,'nombre'=>'IFE Villa']]);
        DB::table('sede_registros')->insert([['tabla'=>'clases','registro_id'=>$own->id,'sede_id'=>1],
            ['tabla'=>'matriculacions','registro_id'=>$foreign->programacioncom->matriculacion_id,'sede_id'=>2]]);
        $actor=$this->actor('Secretaria');$actor->givePermissionTo(['Listar Clases','Editar Clases','Listar Clasescom','Editar Clasescom']);
        DB::table('sede_user')->insert(['user_id'=>$actor->id,'sede_id'=>1]);
        $request=\Illuminate\Http\Request::create('/');$request->setLaravelSession(session());session()->put('sede_activa',1);
        app(SedeAccess::class)->start($actor,$request);
        try {
            $rows=app(PresentAttendanceService::class)->rows($actor);
            $this->assertCount(2,$rows);$this->assertSame('IFE Central',$rows->first()['sucursal']);
            app(PresentAttendanceService::class)->act('computacion',$foreign->id,$actor);
        } finally { app(SedeAccess::class)->reset(); }
        $this->assertSame(1,AsistenciaSalidaAviso::count());
    }

    public function test_migration_is_additive_and_does_not_rewrite_existing_attendance(): void
    {
        $attendance=$this->present();$before=$attendance->fresh()->getAttributes();
        (new \CreateAsistenciaSalidaAvisos)->down();(new \CreateAsistenciaSalidaAvisos)->up();
        $this->assertSame($before,$attendance->fresh()->getAttributes());
        $this->assertSame(0,AsistenciaSalidaAviso::count());
    }

    public function test_present_page_uses_one_adminlte_widget_without_second_bootstrap(): void
    {
        $response=$this->get(route('clase.presentes'))->assertOk()->assertSee('Asistencias sin salida')->assertSee('Tiempo restante');
        $this->assertSame(1,substr_count($response->getContent(),'id="ife-exit-modal"'));
        $this->assertSame(1,substr_count($response->getContent(),'assets/js/presentes.js'));
        $response->assertDontSee('bootstrap@5')->assertSee('bootstrap.bundle.min.js');
        $this->get(route('asistencia.historial',['apoyo',$this->enrollment->id]))->assertOk()->assertSee('id="ife-exit-modal"',false);
    }

    public function test_revoked_session_cannot_poll_or_act(): void
    {
        Schema::create('crm_sedes',function(Blueprint $t){$t->id();$t->string('nombre');$t->boolean('activa')->default(true);$t->timestamps();});
        require_once database_path('migrations/2026_10_31_000001_add_user_access_controls.php');
        (new \AddUserAccessControls)->up();$this->app->forgetInstance(\App\Services\AccountSecurity::class);
        $actor=$this->actor('Secretaria');$actor->givePermissionTo(['Listar Clases','Editar Clases']);
        \App\Models\User::withoutEvents(fn()=>$actor->forceFill(['two_factor_confirmed_at'=>now(),'auth_version'=>0,
            'force_password_change'=>false,'temporary_password_expires_at'=>null])->save());
        $this->actingAs($actor)->withSession(['auth_version'=>0,'two_factor_verified'=>0]);
        $this->getJson(route('presentes.estado'))->assertOk();
        \App\Models\User::withoutEvents(fn()=>$actor->forceFill(['auth_version'=>1])->save());
        $this->getJson(route('presentes.estado'))->assertUnauthorized();
        $this->postJson(route('presentes.aviso',['apoyo',1]))->assertUnauthorized();
    }
}

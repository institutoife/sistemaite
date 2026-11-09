<?php
namespace Tests\Feature;

use App\Models\{Clase, ClaseAdicional, ClaseAsistenciaEvento, Clasecom, Inscripcione, Matriculacion, Programacion, Programacioncom};
use App\Services\{ClassAttendanceService, ClassAttendanceView};
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Http, Queue, Schema};
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\AttendanceTestCase;

class ClassAttendanceTest extends AttendanceTestCase
{
    public static function academicChannels(): array
    {
        return [['apoyo','computacion'],['computacion','apoyo']];
    }

    /** @dataProvider academicChannels */
    public function test_first_actual_attendance_sets_location_and_other_students_can_join_it(string $first,string $second): void
    {
        $otherRoom=DB::table('aulas')->insertGetId(['aula'=>'Aula programada distinta']);
        $a=$this->program($this->otherEnrollment($first),array_merge(['fecha'=>'2026-11-02','estado_id'=>estado('INDEFINIDO')],
            $first==='apoyo'?['hora_ini'=>'07:30','hora_fin'=>'09:00']:['horaini'=>'07:30','horafin'=>'09:00']));
        $b=$this->program($this->otherEnrollment($second),array_merge(['fecha'=>'2026-11-02','estado_id'=>estado('INDEFINIDO'),'aula_id'=>$otherRoom],
            $second==='apoyo'?['hora_ini'=>'07:30','hora_fin'=>'09:00']:['horaini'=>'07:30','horafin'=>'09:00']));
        $before=$b->fresh()->getAttributes();
        $data=$this->data(null,['horainicio'=>'07:30','horafin'=>'09:00']);
        $this->postJson(route($first==='apoyo'?'clases.guardar':'clasescom.guardar',$a),$data)->assertOk();
        $this->assertSame($before,$b->fresh()->getAttributes());
        $route=route($second==='apoyo'?'clases.guardar':'clasescom.guardar',$b);
        $id=$this->postJson($route,$data)->assertOk()->json('id');
        $this->postJson($route,$data)->assertOk()->assertJsonPath('id',$id);
        $this->assertSame(1,Clase::count());$this->assertSame(1,Clasecom::count());
        $this->assertEquals($this->room,$b->fresh()->aula_id);
        $this->assertSame(2,ClaseAsistenciaEvento::where('accion','entrada')->count());
    }

    /** @dataProvider academicChannels */
    public function test_same_student_is_reported_before_teacher_conflict_in_either_circuit(string $first,string $second): void
    {
        $otherTeacher=DB::table('docentes')->insertGetId(['persona_id'=>$this->persona()->id,'estado_id'=>estado('HABILITADO')]);
        $attended=$this->program($this->enroll($first),array_merge(['fecha'=>'2026-11-02','docente_id'=>$otherTeacher],
            $first==='apoyo'?['hora_ini'=>'11:00','hora_fin'=>'12:00']:['horaini'=>'11:00','horafin'=>'12:00']));
        $this->recorded($attended);
        $room=DB::table('aulas')->insertGetId(['aula'=>'Otra aula']);
        $this->recorded($this->program($this->otherEnrollment($second),array_merge(['fecha'=>'2026-11-02','aula_id'=>$room],
            $second==='apoyo'?['hora_ini'=>'11:00','hora_fin'=>'12:00']:['horaini'=>'11:00','horafin'=>'12:00'])));
        $original=$this->program($this->enroll($second));
        $response=$this->postJson($this->url(app(ClassAttendanceService::class)->enrollment($original)),$this->data($original))
            ->assertUnprocessable()->assertJsonValidationErrors('horainicio');
        $this->assertStringContainsString('El estudiante ya tiene una asistencia',$response->json('errors.horainicio.0'));
        $this->assertArrayNotHasKey('docente_id',$response->json('errors'));
        $this->assertSame(0,ClaseAdicional::count());
    }

    public function test_duplicate_student_with_two_planned_classes_gets_student_error_even_if_rooms_differ(): void
    {
        $original=$this->program();
        $this->program($this->enroll(),['fecha'=>'2026-11-02','hora_ini'=>'07:30','hora_fin'=>'09:00',
            'estado_id'=>estado('INDEFINIDO'),'aula_id'=>DB::table('aulas')->insertGetId(['aula'=>'Aula4'])]);
        $response=$this->postJson($this->url(),$this->data($original,['horainicio'=>'07:30','horafin'=>'08:30']))
            ->assertUnprocessable()->assertJsonValidationErrors('horainicio');
        $this->assertStringContainsString('El estudiante tiene otra clase programada',$response->json('errors.horainicio.0'));
        $this->assertArrayNotHasKey('docente_id',$response->json('errors'));
    }

    public function test_partial_overlap_shares_location_but_blocks_another_room_and_allows_touching_intervals(): void
    {
        $room=DB::table('aulas')->insertGetId(['aula'=>'Otra aula']);
        $anchor=$this->program($this->otherEnrollment(),['fecha'=>'2026-11-02','hora_ini'=>'09:00','hora_fin'=>'10:00']);
        $attendance=$this->recorded($anchor);
        $original=$this->program();
        $response=$this->postJson($this->url(),$this->data($original,['horainicio'=>'09:30','horafin'=>'10:30','aula_id'=>$room]))
            ->assertUnprocessable()->assertJsonValidationErrors('docente_id');
        $this->assertStringContainsString('otra aula',$response->json('errors.docente_id.0'));
        $this->assertStringContainsString('asistencia #'.$attendance->id,$response->json('errors.docente_id.0'));
        $this->postJson($this->url(),$this->data($original,['horainicio'=>'09:30','horafin'=>'10:30']))->assertOk();
        $enrollment=$this->otherEnrollment('computacion');$original=$this->program($enrollment);
        $this->postJson($this->url($enrollment),$this->data($original,['horainicio'=>'10:00','horafin'=>'11:00','aula_id'=>$room]))
            ->assertUnprocessable()->assertJsonValidationErrors('docente_id');
        foreach([['08:00','09:00'],['10:30','11:30']] as [$start,$end]) {
            $enrollment=$this->otherEnrollment('computacion');$original=$this->program($enrollment);
            $this->postJson($this->url($enrollment),$this->data($original,['horainicio'=>$start,'horafin'=>$end,'aula_id'=>$room]))->assertOk();
        }
        $this->assertSame(4,Clase::count()+Clasecom::count());
    }

    public function test_student_can_take_adjacent_class_after_actual_early_exit_even_with_a_later_planned_end(): void
    {
        $attended=$this->program($this->enroll('computacion'),['fecha'=>'2026-11-02','horaini'=>'09:00','horafin'=>'11:00']);
        $this->recorded($attended,['horafin'=>'10:00','estado_id'=>estado('FINALIZADO')]);
        $original=$this->program();
        $this->postJson($this->url(),$this->data($original,['horainicio'=>'10:00','horafin'=>'11:00']))->assertOk();
        $this->assertSame('11:00',$attended->fresh()->horafin->format('H:i'));
    }

    public function test_actual_sucursal_takes_precedence_over_historic_parent_attributions(): void
    {
        $original=$this->program();
        $busy=$this->program($this->otherEnrollment('computacion'),['fecha'=>'2026-11-02','horaini'=>'11:00','horafin'=>'12:00']);
        $attendance=$this->recorded($busy);
        $this->branchScope();
        DB::table('sede_registros')->insert([
            ['tabla'=>'clasecoms','registro_id'=>$attendance->id,'sede_id'=>1],
            ['tabla'=>'programacioncoms','registro_id'=>$busy->id,'sede_id'=>2],
            ['tabla'=>'matriculacions','registro_id'=>$busy->matriculacion_id,'sede_id'=>2],
            ['tabla'=>'aulas','registro_id'=>$this->room,'sede_id'=>1],
        ]);
        try {
            app(ClassAttendanceService::class)->create('apoyo',$this->enrollment->id,$this->data($original),$this->operator);
            $this->assertSame(1,Clase::count());
            $this->assertDatabaseHas('sede_registros',['tabla'=>'matriculacions','registro_id'=>$busy->matriculacion_id,'sede_id'=>2]);
        } finally { app(\App\Services\SedeAccess::class)->reset(); }
    }

    public function test_actual_foreign_sucursal_cannot_be_overridden_by_local_program_or_room(): void
    {
        $original=$this->program();
        $busy=$this->program($this->otherEnrollment('computacion'),['fecha'=>'2026-11-02','horaini'=>'11:00','horafin'=>'12:00']);
        $attendance=$this->recorded($busy);
        $this->branchScope();
        DB::table('sede_registros')->insert([
            ['tabla'=>'clasecoms','registro_id'=>$attendance->id,'sede_id'=>2],
            ['tabla'=>'programacioncoms','registro_id'=>$busy->id,'sede_id'=>1],
            ['tabla'=>'aulas','registro_id'=>$this->room,'sede_id'=>1],
        ]);
        try {
            app(ClassAttendanceService::class)->create('apoyo',$this->enrollment->id,$this->data($original),$this->operator);
            $this->fail('La asistencia efectiva fija la ubicación del docente.');
        } catch(\Illuminate\Validation\ValidationException $e) {
            $message=$e->errors()['docente_id'][0];
            $this->assertStringContainsString('otra Sucursal',$message);
            $this->assertStringNotContainsString('asistencia #',$message);
            $this->assertSame(0,ClaseAdicional::count());
        } finally { app(\App\Services\SedeAccess::class)->reset(); }
    }

    public function test_legacy_classes_without_sucursal_can_share_first_actual_location_without_rewriting_history(): void
    {
        $a=$this->program($this->otherEnrollment(),['fecha'=>'2026-11-02','hora_ini'=>'07:30','hora_fin'=>'09:00','estado_id'=>estado('INDEFINIDO')]);
        $b=$this->program($this->otherEnrollment('computacion'),['fecha'=>'2026-11-02','horaini'=>'07:30','horafin'=>'09:00','estado_id'=>estado('INDEFINIDO')]);
        $this->branchScope();
        try {
            foreach([$a,$b] as $program) app(ClassAttendanceService::class)->record($program,$this->data(null,[
                'horainicio'=>'07:30','horafin'=>'09:00']),$this->operator);
            $this->assertSame(2,DB::table('sede_registros')->whereIn('tabla',['clases','clasecoms'])->where('sede_id',1)->count());
            $this->assertSame(0,DB::table('sede_registros')->whereIn('tabla',['programacions','programacioncoms','inscripciones','matriculacions','aulas'])->count());
        } finally { app(\App\Services\SedeAccess::class)->reset(); }
    }

    public function test_pending_schedules_in_other_rooms_do_not_block_recovery_advance_or_extra(): void
    {
        $room=DB::table('aulas')->insertGetId(['aula'=>'Aula pendiente']);
        $this->program($this->otherEnrollment(),['fecha'=>'2026-11-02','hora_ini'=>'11:00','hora_fin'=>'12:00','aula_id'=>$room,'estado_id'=>estado('INDEFINIDO')]);
        foreach(['recuperacion','adelanto','extra'] as $type) {
            $enrollment=$this->otherEnrollment('computacion');
            $original=$this->program($enrollment,$type!=='recuperacion'?['fecha'=>'2026-11-05','estado_id'=>estado('INDEFINIDO')]:[]);
            $this->postJson($this->url($enrollment),$this->data($original,['tipo'=>$type]))->assertOk();
        }
        $this->assertSame(3,Clasecom::count());$this->assertSame(3,ClaseAdicional::count());
    }

    public function test_correcting_an_attended_additional_cannot_move_teacher_away_from_other_active_students(): void
    {
        $original=$this->program();
        $id=$this->postJson($this->url(),$this->data($original))->assertOk()->json('id');
        $other=$this->program($this->otherEnrollment('computacion'),[
            'fecha'=>'2026-11-02','horaini'=>'11:00','horafin'=>'12:00','estado_id'=>estado('INDEFINIDO')]);
        $this->postJson(route('clasescom.guardar',$other),$this->data())->assertOk();
        $room=DB::table('aulas')->insertGetId(['aula'=>'Otra aula']);
        $this->patchJson(route('asistencia.adicional.correct',['apoyo',$id]),$this->data(null,[
            'aula_id'=>$room,'motivo'=>'Cambio de ubicación solicitado']))->assertUnprocessable()->assertJsonValidationErrors('docente_id');
        $this->assertEquals($this->room,Programacion::findOrFail($id)->aula_id);
        $this->assertEquals($this->room,Clase::where('programacion_id',$id)->sole()->aula_id);
        $this->assertSame(0,ClaseAsistenciaEvento::where('accion','correccion')->count());
    }

    public function test_authorized_teacher_conflict_explains_actual_attendance_and_requested_interval(): void
    {
        $original=$this->program();$other=$this->otherEnrollment();
        $room=DB::table('aulas')->insertGetId(['aula'=>'Otra aula']);
        $busy=$this->program($other,['fecha'=>'2026-11-02','hora_ini'=>'12:00','hora_fin'=>'13:00',
            'estado_id'=>estado('INDEFINIDO'),'aula_id'=>$room]);
        $attendance=$this->recorded($busy);
        $response=$this->postJson($this->url(),$this->data($original,['horainicio'=>'11:30','horafin'=>'12:30']))
            ->assertUnprocessable()->assertJsonValidationErrors('docente_id');
        $message=$response->json('errors.docente_id.0');
        $this->assertStringContainsString('asistencia #'.$attendance->id,$message);
        $this->assertStringContainsString('inscripción #'.$other->id,$message);
        $this->assertStringContainsString('12:00–13:00',$message);
        $this->assertStringContainsString('Horario solicitado: 11:30–12:30',$message);
        $this->assertStringContainsString('Aula solicitada: #'.$this->room.'.',$message);
        $this->assertSame(0,ClaseAdicional::count());
    }

    public function test_explicit_same_branch_teacher_room_and_time_allow_several_students_in_both_circuits(): void
    {
        $programs=[];
        foreach(['apoyo','computacion','apoyo','computacion'] as $channel) {
            $enrollment=$this->otherEnrollment($channel);
            $programs[]=$this->program($enrollment,array_merge(['fecha'=>'2026-11-02','estado_id'=>estado('INDEFINIDO')],
                $channel==='apoyo'?['hora_ini'=>'07:30','hora_fin'=>'09:00']:['horaini'=>'07:30','horafin'=>'09:00']));
        }
        $this->branchScope();
        DB::table('sede_registros')->insert(['tabla'=>'aulas','registro_id'=>$this->room,'sede_id'=>1]);
        foreach($programs as $program) {
            $enrollment=app(ClassAttendanceService::class)->enrollment($program);
            foreach([$program,$enrollment] as $record) DB::table('sede_registros')->insert([
                'tabla'=>$record->getTable(),'registro_id'=>$record->id,'sede_id'=>1]);
        }
        try {
            foreach($programs as $program) {
                $attendance=app(ClassAttendanceService::class)->record($program,$this->data(null,[
                    'horainicio'=>'07:30','horafin'=>'09:00']),$this->operator);
                $this->assertEquals($this->room,$attendance->aula_id);
                $this->assertEquals($this->teacher,$attendance->docente_id);
            }
            $this->assertSame(2,Clase::count());
            $this->assertSame(2,Clasecom::count());
            $this->assertSame(4,ClaseAsistenciaEvento::where('accion','entrada')->count());
        } finally { app(\App\Services\SedeAccess::class)->reset(); }
    }

    public function test_equal_classroom_labels_do_not_make_different_classroom_ids_compatible(): void
    {
        $original=$this->program();
        $duplicateName=DB::table('aulas')->insertGetId(['aula'=>DB::table('aulas')->where('id',$this->room)->value('aula')]);
        $this->recorded($this->program($this->otherEnrollment(),['fecha'=>'2026-11-02','hora_ini'=>'11:00','hora_fin'=>'12:00',
            'estado_id'=>estado('INDEFINIDO'),'aula_id'=>$duplicateName]));
        $message=$this->postJson($this->url(),$this->data($original))->assertUnprocessable()
            ->assertJsonValidationErrors('docente_id')->json('errors.docente_id.0');
        $this->assertStringContainsString('aula #'.$duplicateName.'.',$message);
        $this->assertStringContainsString('Aula solicitada: #'.$this->room.'.',$message);
        $this->assertSame(1,Clase::count());
    }

    public function test_conflict_details_are_hidden_without_list_permission(): void
    {
        $original=$this->program();$other=$this->otherEnrollment('computacion');
        $this->recorded($this->program($other,['fecha'=>'2026-11-02','horaini'=>'11:00','horafin'=>'12:00','estado_id'=>estado('INDEFINIDO'),
            'aula_id'=>DB::table('aulas')->insertGetId(['aula'=>'Otra aula'])]));
        $actor=$this->actor('Secretaria');$actor->givePermissionTo(['Crear Clases','Crear Programaciones']);$this->actingAs($actor);
        $message=$this->postJson($this->url(),$this->data($original))->assertUnprocessable()->json('errors.docente_id.0');
        $this->assertStringNotContainsString('matrícula #',$message);
        $this->assertStringNotContainsString('programación #',$message);
        $this->assertStringNotContainsString('asistencia #',$message);
        $this->assertStringContainsString('Horario solicitado: 11:00–12:00',$message);
    }

    public function test_conflict_in_another_branch_never_exposes_record_codes(): void
    {
        $original=$this->program();$other=$this->otherEnrollment();
        $busy=$this->program($other,['fecha'=>'2026-11-02','hora_ini'=>'11:00','hora_fin'=>'12:00','estado_id'=>estado('INDEFINIDO')]);
        $attendance=$this->recorded($busy);
        $this->branchScope();
        DB::table('sede_registros')->insert(['tabla'=>'clases','registro_id'=>$attendance->id,'sede_id'=>2]);
        try {
            app(ClassAttendanceService::class)->create('apoyo',$this->enrollment->id,$this->data($original),$this->operator);
            $this->fail('Debe conservar la restricción entre sucursales.');
        } catch(\Illuminate\Validation\ValidationException $e) {
            $message=$e->errors()['docente_id'][0];
            $this->assertStringContainsString('otra Sucursal',$message);
            $this->assertStringContainsString('Aula solicitada: #'.$this->room.'.',$message);
            $this->assertStringContainsString('Sucursal activa: #1.',$message);
            $this->assertStringNotContainsString('programación #',$message);
            $this->assertStringNotContainsString('inscripción #',$message);
            $this->assertStringNotContainsString('asistencia #',$message);
        } finally { app(\App\Services\SedeAccess::class)->reset(); }
    }

    public function test_modal_opens_enrollment_with_today_classes_before_old_active_enrollment(): void
    {
        $current=$this->enroll();
        $this->program($current,['fecha'=>'2026-11-02','estado_id'=>estado('INDEFINIDO')]);
        $response=$this->get(route('marcado.presente.tabs',$this->person))->assertOk();
        $tabs=$response->viewData('tabs');
        $this->assertEquals($current->id,$tabs[0]['enrollment']->id);
        $this->assertCount(2,$tabs);
    }

    public function test_reported_multiple_enrollment_case_opens_finished_class_without_allowing_duplicate_entry(): void
    {
        $late=DB::table('estados')->insertGetId(['estado'=>'LLEGO ATRAZADO']);
        for($i=0;$i<7;$i++) $this->enroll('apoyo',['estado_id'=>$late]);
        $this->enroll('apoyo',['estado_id'=>estado('INDEFINIDO')]);
        for($i=0;$i<3;$i++) $this->enroll();
        $current=$this->enroll();
        $program=$this->program($current,['fecha'=>'2026-11-02','hora_ini'=>'10:30','hora_fin'=>'12:00',
            'estado_id'=>estado('FINALIZADO')]);
        $before=$program->fresh()->getAttributes();
        $response=$this->get(route('marcado.presente.tabs',$this->person))->assertOk();
        $tabs=$response->viewData('tabs');
        $this->assertCount(5,$tabs);
        $this->assertEquals($current->id,$tabs[0]['enrollment']->id);
        $this->assertCount(1,$tabs[0]['todayPrograms']);
        $dom=new \DOMDocument;@$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath=new \DOMXPath($dom);
        $pane=$xpath->query('//*[@id="attendance-apoyo-'.$current->id.'"]')->item(0);
        $this->assertStringContainsString('active',$pane->getAttribute('class'));
        $this->assertStringContainsString('FINALIZADO',$pane->textContent);
        $this->assertStringContainsString('Asistencia registrada',$pane->textContent);
        $this->assertStringNotContainsString('Sin clase hoy',$pane->textContent);
        $this->assertSame(0,$xpath->query('.//button[contains(@class,"attendance-quick-entry")]',$pane)->length);
        $this->assertSame($before,$program->fresh()->getAttributes());
    }

    public function test_modal_includes_enrollments_of_all_legacy_student_records_for_same_person(): void
    {
        $secondStudent=DB::table('estudiantes')->insertGetId(['persona_id'=>$this->person->id]);
        $second=$this->enroll('apoyo',['estudiante_id'=>$secondStudent]);
        $this->program($second,['fecha'=>'2026-11-02','estado_id'=>estado('INDEFINIDO')]);
        $before=DB::table('estudiantes')->where('persona_id',$this->person->id)->count();
        $response=$this->get(route('marcado.presente.tabs',$this->person))->assertOk();
        $this->assertEqualsCanonicalizing([$this->enrollment->id,$second->id],array_map(fn($tab)=>$tab['enrollment']->id,$response->viewData('tabs')));
        $this->assertSame($before,DB::table('estudiantes')->where('persona_id',$this->person->id)->count());
        $this->assertEquals($second->id,$response->viewData('tabs')[0]['enrollment']->id);
    }

    public function test_modal_includes_all_legacy_computing_records_without_merging_or_crossing_people(): void
    {
        $first=$this->enroll('computacion');
        $secondRole=DB::table('computacions')->insertGetId(['persona_id'=>$this->person->id]);
        $second=$this->enroll('computacion',['computacion_id'=>$secondRole]);
        $this->program($second,['fecha'=>'2026-11-02','estado_id'=>estado('INDEFINIDO')]);
        $this->otherEnrollment('computacion');
        $tabs=$this->get(route('marcado.presente.tabs',$this->person))->assertOk()->viewData('tabs');
        $computing=collect($tabs)->where('canal','computacion');
        $this->assertEqualsCanonicalizing([$first->id,$second->id],$computing->pluck('enrollment.id')->all());
        $this->assertEquals($second->id,$tabs[0]['enrollment']->id);
        $this->assertSame(2,DB::table('computacions')->where('persona_id',$this->person->id)->count());
    }

    public function test_today_consumed_by_a_future_recovery_is_explained_instead_of_no_class(): void
    {
        $original=$this->program(null,['fecha'=>'2026-11-02','hora_ini'=>'08:00','hora_fin'=>'09:00']);
        $this->postJson($this->url(),$this->data($original,['fecha'=>'2026-11-03','marcar_entrada'=>0]))->assertOk();
        $this->get(route('marcado.presente.tabs',$this->person))->assertOk()
            ->assertSee('La clase programada para hoy ya fue vinculada a una clase adicional.')
            ->assertDontSee('Sin clase hoy.')->assertSee('03/11/2026');
    }

    public function test_inactive_enrollment_schedule_does_not_reserve_teacher_or_student(): void
    {
        foreach(['apoyo','computacion'] as $channel) {
            $current=$this->otherEnrollment($channel);
            $original=$this->program($current);
            $inactive=$this->otherEnrollment($channel);$inactive->vigente=false;$inactive->save();
            $otherRoom=DB::table('aulas')->insertGetId(['aula'=>'Otra aula']);
            $this->program($inactive,array_merge(['fecha'=>'2026-11-02','estado_id'=>estado('INDEFINIDO'),'aula_id'=>$otherRoom],
                $channel==='apoyo'?['hora_ini'=>'11:00','hora_fin'=>'12:00']:['horaini'=>'11:00','horafin'=>'12:00']));
            $this->postJson($this->url($current),$this->data($original))->assertOk();
        }
    }

    public function test_attendance_in_inactive_enrollment_still_protects_actual_teacher_occupancy(): void
    {
        $current=$this->otherEnrollment('apoyo');$original=$this->program($current);
        $inactive=$this->otherEnrollment('computacion');$inactive->vigente=false;$inactive->save();
        $otherRoom=DB::table('aulas')->insertGetId(['aula'=>'Otra aula']);
        $program=$this->program($inactive,['fecha'=>'2026-11-02','estado_id'=>estado('FINALIZADO'),'aula_id'=>$otherRoom]);
        DB::table('clasecoms')->insert(['programacioncom_id'=>$program->id,'fecha'=>'2026-11-02','horainicio'=>'11:00','horafin'=>'12:00',
            'docente_id'=>$this->teacher,'aula_id'=>$otherRoom,'estado_id'=>estado('PRESENTE')]);
        $this->postJson($this->url($current),$this->data($original))->assertUnprocessable()->assertJsonValidationErrors('docente_id');
    }

    public function test_inactive_schedule_of_same_student_does_not_block_a_current_enrollment(): void
    {
        $original=$this->program();$inactive=$this->enroll('apoyo',['vigente'=>0]);
        $this->program($inactive,['fecha'=>'2026-11-02','hora_ini'=>'11:00','hora_fin'=>'12:00','estado_id'=>estado('INDEFINIDO')]);
        $this->postJson($this->url(),$this->data($original))->assertOk();
        $this->assertSame(1,Clase::count());
    }

    public function test_enrollment_not_running_does_not_reserve_teacher_even_if_vigente_flag_remains(): void
    {
        $original=$this->program();$closed=$this->otherEnrollment();$closed->estado_id=estado('FINALIZADO');$closed->save();
        $this->program($closed,['fecha'=>'2026-11-02','hora_ini'=>'11:00','hora_fin'=>'12:00','estado_id'=>estado('INDEFINIDO'),
            'aula_id'=>DB::table('aulas')->insertGetId(['aula'=>'Otra aula'])]);
        $this->postJson($this->url(),$this->data($original))->assertOk();
        $this->assertEquals(estado('FINALIZADO'),$closed->fresh()->estado_id);
    }

    public function test_modal_legacy_role_lookup_still_respects_branch_and_create_permissions(): void
    {
        $secondRole=DB::table('estudiantes')->insertGetId(['persona_id'=>$this->person->id]);
        $foreign=$this->enroll('apoyo',['estudiante_id'=>$secondRole]);
        $this->program($foreign,['fecha'=>'2026-11-02','estado_id'=>estado('INDEFINIDO')]);
        $this->branchScope();
        DB::table('sede_registros')->insert(['tabla'=>'inscripciones','registro_id'=>$foreign->id,'sede_id'=>2]);
        try {
            $tabs=app(ClassAttendanceView::class)->tabs($this->person);
            $this->assertSame([$this->enrollment->id],collect($tabs)->pluck('enrollment.id')->all());
        } finally { app(\App\Services\SedeAccess::class)->reset(); }
        $viewer=$this->actor('Docente');$viewer->givePermissionTo('Listar Clases');$this->actingAs($viewer);
        $this->get(route('marcado.presente.tabs',$this->person))->assertOk()->assertDontSee('attendance-quick-entry');
    }

    public function test_recovery_preserves_original_and_reuses_attendance_without_new_charge(): void
    {
        $original=$this->program();$before=$original->getAttributes();$paymentCount=DB::table('pagos')->count();
        $response=$this->postJson($this->url(),$this->data($original))->assertOk();
        $effective=Programacion::findOrFail($response->json('id'));
        $this->assertEquals($before,$original->fresh()->getAttributes());
        $this->assertEquals('recuperacion',$effective->adicional->tipo);
        $this->assertEquals($original->id,$effective->adicional->original->id);
        $this->assertEquals(estado('PRESENTE'),$effective->estado_id);
        $this->assertEquals($this->operator->id,$effective->clases->sole()->usuarios->sole()->id);
        $this->assertSame($paymentCount,DB::table('pagos')->count());
        $this->assertEquals(1,Programacion::where('inscripcione_id',$this->enrollment->id)->horasContratadas()->count());
        $this->assertSame(2,ClaseAsistenciaEvento::count());
        Http::assertNothingSent();Queue::assertNothingPushed();
    }

    public function test_advance_leaves_original_pending_state_but_excludes_it_from_effective_pending_and_duplicates(): void
    {
        $original=$this->program(null,['fecha'=>'2026-11-05','estado_id'=>estado('INDEFINIDO')]);
        $this->postJson($this->url(),$this->data($original,['tipo'=>'adelanto']))->assertOk();
        $this->assertEquals(estado('INDEFINIDO'),$original->fresh()->estado_id);
        $this->assertFalse(Programacion::whereKey($original->id)->sinCompensar()->exists());
        $this->postJson($this->url(),$this->data($original,['tipo'=>'adelanto','horainicio'=>'13:00','horafin'=>'14:00']))->assertUnprocessable()->assertJsonValidationErrors('original_id');
        $this->postJson(route('clases.guardar',$original),$this->data(null,['fecha'=>'2026-11-05']))->assertUnprocessable();
    }

    public function test_duplicate_submission_creates_one_program_and_one_attendance_only(): void
    {
        $original=$this->program();$data=$this->data($original);
        $id=$this->postJson($this->url(),$data)->assertOk()->json('id');
        $this->postJson($this->url(),$data)->assertOk()->assertJsonPath('id',$id);
        $this->assertSame(1,ClaseAdicional::count());$this->assertSame(1,Clase::count());
        $this->postJson(route('clases.guardar',$id),$data)->assertOk();$this->assertSame(1,Clase::count());
    }

    public function test_extra_requires_original_and_consumes_its_paid_hours_without_another_charge(): void
    {
        $original=$this->program(null,['fecha'=>'2026-11-05','estado_id'=>estado('INDEFINIDO'),'habilitado'=>0]);
        $this->postJson($this->url(),$this->data(null,['tipo'=>'extra']))->assertUnprocessable()->assertJsonValidationErrors('original_id');
        $this->postJson($this->url(),$this->data($original,['tipo'=>'extra']))->assertUnprocessable()->assertJsonValidationErrors('original_id');
        $this->assertSame(0,ClaseAdicional::count());
        $this->enrollment->totalhoras=1;$this->enrollment->costo=100;$this->enrollment->save();
        DB::table('pagos')->update(['monto'=>100]);$original->habilitado=1;$original->save();
        $this->postJson($this->url(),$this->data($original,['tipo'=>'extra']))->assertOk();
        $this->assertSame(1,ClaseAdicional::where('tipo','extra')->count());
        $this->assertEquals($original->id,ClaseAdicional::sole()->original_programacion_id);
        $this->assertEquals(1,Programacion::horasContratadas()->sum('horas_por_clase'));
        $this->assertSame(1,DB::table('pagos')->count());$this->assertEquals(100,DB::table('pagos')->sum('monto'));
        $stats=app(ClassAttendanceView::class)->tab('apoyo',$this->enrollment)['stats'];
        $this->assertSame(1,$stats['Programadas']);$this->assertSame(1,$stats['Extras']);$this->assertSame(1,$stats['Asistidas']);
        $this->assertFalse(Programacion::whereKey($original->id)->sinCompensar()->exists());
        $this->postJson($this->url(),$this->data($original,['tipo'=>'extra','horainicio'=>'13:00','horafin'=>'14:00']))->assertUnprocessable()->assertJsonValidationErrors('original_id');
    }

    public function test_unpaid_original_is_blocked_even_for_admin(): void
    {
        $original=$this->program(null,['habilitado'=>0]);
        $this->postJson($this->url(),$this->data($original))->assertUnprocessable()->assertJsonValidationErrors('original_id');
        $this->assertSame(0,ClaseAdicional::count());
    }

    public function test_extra_in_both_circuits_requires_original_is_idempotent_and_uses_original_payment_once(): void
    {
        foreach(['apoyo','computacion'] as $channel){
            $enrollment=$channel==='apoyo'?$this->enrollment:$this->enroll('computacion');
            $enrollment->totalhoras=1;$enrollment->costo=100;$enrollment->save();
            $payments=fn()=>DB::table('pagos')->where('pagable_type',get_class($enrollment))->where('pagable_id',$enrollment->id);
            $payments()->update(['monto'=>100]);
            $original=$this->program($enrollment,['fecha'=>'2026-11-05','estado_id'=>estado('INDEFINIDO')]);
            $time=$channel==='apoyo'?['horainicio'=>'11:00','horafin'=>'12:00']:['horainicio'=>'13:00','horafin'=>'14:00'];
            $data=$this->data($original,array_merge($time,['tipo'=>'extra']));
            $this->postJson($this->url($enrollment),array_merge($data,['original_id'=>null]))->assertUnprocessable()->assertJsonValidationErrors('original_id');
            $this->postJson($this->url($enrollment),array_merge($data,['horafin'=>$channel==='apoyo'?'12:01':'14:01']))->assertUnprocessable()->assertJsonValidationErrors('horafin');
            $id=$this->postJson($this->url($enrollment),$data)->assertOk()->json('id');
            $this->postJson($this->url($enrollment),$data)->assertOk()->assertJsonPath('id',$id);
            $program=$original->newQuery()->findOrFail($id);
            $this->assertEquals($original->id,$program->adicional->original->id);
            $this->assertFalse($program->isContractedClass());$this->assertTrue($original->isContractedClass());
            $this->assertEquals(1,$original->newQuery()->horasContratadas()->where($channel==='apoyo'?'inscripcione_id':'matriculacion_id',$enrollment->id)->sum('horas_por_clase'));
            $this->assertSame(1,$payments()->count());$this->assertEquals(100,$payments()->sum('monto'));
            $calculator=app($channel==='apoyo'?\App\Http\Controllers\ProgramacionController::class:\App\Http\Controllers\ProgramacioncomController::class);
            $method=$channel==='apoyo'?'actualizarProgramaSegunPago':'actualizarProgramaSegunPagocom';
            $calculator->$method($enrollment->id);
            $this->assertTrue((bool)$original->fresh()->habilitado);$this->assertTrue((bool)$program->fresh()->habilitado);
            $payments()->update(['monto'=>0]);$calculator->$method($enrollment->id);
            $this->assertFalse((bool)$original->fresh()->habilitado);$this->assertFalse((bool)$program->fresh()->habilitado);
            $payments()->update(['monto'=>100]);$calculator->$method($enrollment->id);
            $this->assertTrue((bool)$original->fresh()->habilitado);$this->assertTrue((bool)$program->fresh()->habilitado);
            $this->postJson($this->url($enrollment),$this->data($program,array_merge($time,['tipo'=>'extra'])))->assertUnprocessable()->assertJsonValidationErrors('original_id');
            $this->get(route('asistencia.historial',[$channel,$enrollment->id]))->assertOk()->assertSee('Extra')->assertSee('Original #'.$original->id);
        }
        $this->assertSame(2,ClaseAdicional::count());$this->assertSame(1,Clase::count());$this->assertSame(1,Clasecom::count());
    }

    public function test_extra_can_consume_a_later_paid_class_on_the_same_day_without_counting_it_twice(): void
    {
        $today=$this->program(null,['fecha'=>'2026-11-02','estado_id'=>estado('INDEFINIDO')]);
        $this->postJson(route('clases.guardar',$today),$this->data(null,['horainicio'=>'09:00','horafin'=>'10:00']))->assertOk();
        $original=$this->program(null,['fecha'=>'2026-11-02','hora_ini'=>'15:00','hora_fin'=>'16:00','estado_id'=>estado('INDEFINIDO')]);
        $this->postJson($this->url(),$this->data($original,['tipo'=>'extra']))->assertOk();
        $stats=app(ClassAttendanceView::class)->tab('apoyo',$this->enrollment)['stats'];
        $this->assertSame(2,$stats['Programadas']);$this->assertSame(2,$stats['Asistidas']);$this->assertSame(1,$stats['Extras']);
        $this->assertEquals(2,Programacion::horasContratadas()->sum('horas_por_clase'));
        $this->postJson(route('clases.guardar',$original),$this->data(null,['horainicio'=>'15:00','horafin'=>'16:00']))->assertUnprocessable()->assertJsonValidationErrors('programacion');
    }

    public function test_unattended_consuming_extra_can_be_corrected_after_original_date_with_trace(): void
    {
        $original=$this->program(null,['fecha'=>'2026-11-05','estado_id'=>estado('INDEFINIDO')]);
        $id=$this->postJson($this->url(),$this->data($original,['tipo'=>'extra','marcar_entrada'=>0]))->assertOk()->json('id');
        Carbon::setTestNow('2026-11-06 10:00:00');
        $this->patchJson(route('asistencia.adicional.correct',['apoyo',$id]),$this->data(null,['fecha'=>'2026-11-06']))->assertOk();
        $this->assertSame('recuperacion',Programacion::findOrFail($id)->adicional->tipo);
        $event=ClaseAsistenciaEvento::where('accion','correccion')->sole();
        $this->assertSame('extra',$event->antes['adicional']['tipo']);$this->assertSame('recuperacion',$event->despues['adicional']['tipo']);
        $this->assertEquals($original->id,$event->despues['adicional']['original_programacion_id']);
        $this->assertEquals(estado('INDEFINIDO'),$original->fresh()->estado_id);
    }

    public function test_attended_original_and_different_enrollment_cannot_be_recovered(): void
    {
        $original=$this->program(null,['estado_id'=>estado('FINALIZADO')]);
        $this->postJson($this->url(),$this->data($original))->assertUnprocessable()->assertJsonValidationErrors('original_id');
        $other=$this->enroll();$foreign=$this->program($other);
        $this->postJson($this->url(),$this->data($foreign))->assertNotFound();
    }

    public function test_conflicts_cover_student_and_teacher_across_both_circuits(): void
    {
        $original=$this->program();$computing=$this->enroll('computacion');
        $this->program($computing,['fecha'=>'2026-11-02','horaini'=>'11:30','horafin'=>'12:30','estado_id'=>estado('INDEFINIDO')]);
        $this->postJson($this->url(),$this->data($original))->assertUnprocessable()->assertJsonValidationErrors('horainicio');
        $otherTeacher=DB::table('docentes')->insertGetId(['persona_id'=>$this->persona()->id,'estado_id'=>estado('HABILITADO'),'nombrecorto'=>'Otro']);
        $this->postJson($this->url(),$this->data($original,['docente_id'=>$otherTeacher]))->assertUnprocessable()->assertJsonValidationErrors('horainicio');
        $this->assertSame(0,ClaseAdicional::count());
    }

    public function test_computing_recovery_and_exit_use_same_flow_and_audit(): void
    {
        $enrollment=$this->enroll('computacion');$original=$this->program($enrollment);
        $id=$this->postJson($this->url($enrollment),$this->data($original,['horainicio'=>'09:00','horafin'=>'10:00']))->assertOk()->json('id');
        $attendance=Clasecom::where('programacioncom_id',$id)->sole();
        $this->postJson(route('clasecom.finalizar'),['id'=>$attendance->id])->assertOk();
        $this->assertEquals(estado('FINALIZADO'),$attendance->fresh()->estado_id);
        $this->assertEquals(estado('LICENCIA'),$original->fresh()->estado_id);
        $this->assertSame(3,ClaseAsistenciaEvento::where('programacioncom_id',$id)->count());
        $this->postJson(route('clasecom.finalizar'),['id'=>$attendance->id])->assertOk();
        $this->assertSame(3,ClaseAsistenciaEvento::where('programacioncom_id',$id)->count());
    }

    public function test_modal_lists_separate_active_enrollments_and_blue_state_without_classes(): void
    {
        $this->enroll();$this->enroll('computacion');
        $response=$this->get(route('marcado.presente.tabs',$this->person));
        $response->assertOk()->assertSee('Sin clase hoy')->assertSee('Agregar clase')->assertSee('Matrícula');
        $this->assertCount(3,$response->viewData('tabs'));
        DB::table('inscripciones')->update(['vigente'=>0]);DB::table('matriculacions')->update(['vigente'=>0]);
        $this->get(route('marcado.presente.tabs',$this->person))->assertOk()->assertDontSee('attendance-add-form',false);
    }

    public function test_corrections_are_authorized_keep_link_and_capture_before_after(): void
    {
        $original=$this->program();
        $id=$this->postJson($this->url(),$this->data($original))->assertOk()->json('id');
        $this->patchJson(route('asistencia.adicional.correct',['apoyo',$id]),$this->data(null,['horainicio'=>'13:00','horafin'=>'14:00','motivo'=>'Horario registrado incorrectamente']))->assertOk();
        $event=ClaseAsistenciaEvento::where('accion','correccion')->sole();
        $this->assertSame('11:00',Carbon::parse($event->antes['programacion']['hora_ini'])->format('H:i'));
        $this->assertSame('13:00',Carbon::parse($event->despues['programacion']['hora_ini'])->format('H:i'));
        $this->assertEquals($original->id,Programacion::find($id)->adicional->original->id);
        $this->assertEquals(estado('LICENCIA'),$original->fresh()->estado_id);
        $this->deleteJson(route('clases.destroy',Clase::sole()))->assertUnprocessable();
    }

    public function test_statistics_and_payment_allocation_do_not_double_count_compensated_hours(): void
    {
        $original=$this->program();$this->postJson($this->url(),$this->data($original))->assertOk();
        $tab=app(ClassAttendanceView::class)->tab('apoyo',$this->enrollment);
        $this->assertSame(1,$tab['stats']['Programadas']);$this->assertSame(1,$tab['stats']['Asistidas']);
        $this->assertSame(1,$tab['stats']['Licencias']);$this->assertSame(1,$tab['stats']['Recuperadas']);
        $this->assertSame(0,$tab['stats']['Pendientes de recuperación']);
        DB::table('pagos')->update(['monto'=>100]);
        app(\App\Http\Controllers\ProgramacionController::class)->actualizarProgramaSegunPago($this->enrollment->id);
        $this->assertTrue((bool)$original->fresh()->habilitado);
        $this->assertTrue((bool)ClaseAdicional::sole()->programa->habilitado);
        $this->assertEquals(1,Programacion::horasContratadas()->sum('horas_por_clase'));
    }

    public function test_view_only_user_cannot_create_correct_or_mark_attendance(): void
    {
        $original=$this->program();$viewer=$this->actor('Docente');
        $viewer->givePermissionTo(Permission::findByName('Listar Clases'));$this->actingAs($viewer);
        $this->get(route('marcado.presente.tabs',$this->person))->assertOk()->assertDontSee('attendance-add-form',false);
        $this->postJson($this->url(),$this->data($original))->assertForbidden();
        $this->postJson(route('clases.guardar',$original),$this->data($original))->assertForbidden();
    }

    public function test_invalid_duration_inactive_enrollment_and_future_attendance_are_rejected_atomically(): void
    {
        $original=$this->program();
        $this->postJson($this->url(),$this->data($original,['horafin'=>'12:30']))->assertUnprocessable()->assertJsonValidationErrors('horafin');
        $this->postJson($this->url(),$this->data($original,['fecha'=>'2026-11-03']))->assertUnprocessable()->assertJsonValidationErrors('fecha');
        $this->assertSame(0,ClaseAdicional::count());$this->assertSame(1,Programacion::count());
        $this->enrollment->vigente=0;$this->enrollment->save();
        $this->postJson($this->url(),$this->data($original))->assertUnprocessable()->assertJsonValidationErrors('inscripcion');
    }

    public function test_future_additional_can_be_scheduled_without_faking_attendance(): void
    {
        $original=$this->program(null,['fecha'=>'2026-11-05','estado_id'=>estado('INDEFINIDO')]);
        $id=$this->postJson($this->url(),$this->data($original,['tipo'=>'adelanto','fecha'=>'2026-11-03','marcar_entrada'=>0]))->assertOk()->json('id');
        $this->assertEquals(estado('INDEFINIDO'),Programacion::find($id)->estado_id);
        $this->assertSame(0,Clase::count());
        $this->get(route('asistencia.historial',['apoyo',$this->enrollment->id]))->assertOk()->assertSee('05/11/2026')->assertSee('03/11/2026')->assertSee('Adelanto');
    }

    public function test_second_class_on_same_day_is_shown_and_counted_separately(): void
    {
        $today=$this->program(null,['fecha'=>'2026-11-02','estado_id'=>estado('INDEFINIDO')]);
        $this->postJson(route('clases.guardar',$today),$this->data(null,['horainicio'=>'09:00','horafin'=>'10:00']))->assertOk();
        $future=$this->program(null,['fecha'=>'2026-11-05','estado_id'=>estado('INDEFINIDO')]);
        $this->postJson($this->url(),$this->data($future,['tipo'=>'adelanto']))->assertOk();
        $response=$this->get(route('marcado.presente.tabs',$this->person))->assertOk();
        $this->assertSame(2,substr_count($response->getContent(),'class="btn btn-sm btn-danger attendance-exit"'));
        $this->assertSame(2,Clase::count());
        $this->assertSame(2,app(ClassAttendanceView::class)->tab('apoyo',$this->enrollment)['stats']['Asistidas']);
    }

    public function test_linked_history_cannot_be_regenerated_or_removed(): void
    {
        $original=$this->program();$this->postJson($this->url(),$this->data($original))->assertOk();
        try { app(ClassAttendanceService::class)->protectRegeneration('apoyo',$this->enrollment->id);$this->fail('La regeneración debe estar bloqueada.'); }
        catch(\Illuminate\Validation\ValidationException $expected) { $this->assertArrayHasKey('programacion',$expected->errors()); }
        try { (new \CreateClasesAdicionales)->down();$this->fail('No debe eliminarse un historial existente.'); }
        catch(\RuntimeException $expected) { $this->assertStringContainsString('historial',$expected->getMessage()); }
        $this->assertSame(1,ClaseAdicional::count());
        $this->assertEquals(estado('LICENCIA'),$original->fresh()->estado_id);
    }

    public function test_unknown_channel_and_tampered_enrollment_do_not_bypass_permissions(): void
    {
        $this->postJson(route('asistencia.adicional.store',['unknown',$this->enrollment->id]),$this->data())->assertNotFound();
        $original=$this->program();$id=$this->postJson($this->url(),$this->data($original))->assertOk()->json('id');
        $viewer=$this->actor('Docente');$this->actingAs($viewer);
        $this->patchJson(route('asistencia.adicional.correct',['apoyo',$id]),$this->data())->assertForbidden();
        $this->postJson(route('clases.finalizar'),['id'=>Clase::sole()->id])->assertForbidden();
        $this->get(route('asistencia.historial',['apoyo',$this->enrollment->id]))->assertForbidden();
        $secretary=$this->actor('Secretaria');$this->actingAs($secretary);
        $this->postJson(route('clases.finalizar'),['id'=>Clase::sole()->id])->assertForbidden();
    }

    public function test_notified_absence_and_same_day_missed_class_can_be_recovered(): void
    {
        $original=$this->program(null,['fecha'=>'2026-11-02','hora_ini'=>'08:00','hora_fin'=>'09:00','estado_id'=>estado('FALTANOTIFICADA')]);
        $this->postJson($this->url(),$this->data($original))->assertOk();
        $this->assertEquals(estado('FALTANOTIFICADA'),$original->fresh()->estado_id);
        $this->assertSame(0,app(ClassAttendanceView::class)->tab('apoyo',$this->enrollment)['stats']['Pendientes de recuperación']);
    }

    private function branchScope(): void
    {
        Schema::table('users',function(Blueprint $t){$t->unsignedBigInteger('sede_id')->nullable();});
        Schema::create('crm_sedes',function(Blueprint $t){$t->id();$t->string('nombre');$t->boolean('activa')->default(true);});
        Schema::create('sede_user',function(Blueprint $t){$t->unsignedBigInteger('user_id');$t->unsignedBigInteger('sede_id');});
        Schema::create('sede_registros',function(Blueprint $t){$t->string('tabla');$t->unsignedBigInteger('registro_id');$t->unsignedBigInteger('sede_id');$t->timestamp('created_at')->nullable();});
        Schema::create('persona_sede',function(Blueprint $t){$t->unsignedBigInteger('persona_id');$t->unsignedBigInteger('sede_id');$t->timestamps();});
        DB::table('crm_sedes')->insert([['id'=>1,'nombre'=>'IFE Central','activa'=>1],['id'=>2,'nombre'=>'IFE Villa','activa'=>1]]);
        $this->operator->removeRole('Admin');$this->operator->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name'=>'Secretaria','guard_name'=>'web']));
        DB::table('sede_user')->insert(['user_id'=>$this->operator->id,'sede_id'=>1]);
        $request=\Illuminate\Http\Request::create('/');$request->setLaravelSession(session());session()->put('sede_activa',1);
        app(\App\Services\SedeAccess::class)->start($this->operator,$request);
    }

    public function test_branch_restriction_cannot_be_bypassed_by_changing_enrollment_id(): void
    {
        $original=$this->program();$this->branchScope();
        DB::table('sede_registros')->insert(['tabla'=>'inscripciones','registro_id'=>$this->enrollment->id,'sede_id'=>2]);
        try {
            app(ClassAttendanceService::class)->create('apoyo',$this->enrollment->id,$this->data($original),$this->operator);
            $this->fail('No puede usar una inscripción de otra sucursal.');
        } catch(\Illuminate\Database\Eloquent\ModelNotFoundException $expected) { $this->assertSame(0,ClaseAdicional::count()); }
        finally { app(\App\Services\SedeAccess::class)->reset(); }
    }

    public function test_teacher_conflict_in_another_branch_is_validated_without_exposing_its_records(): void
    {
        $original=$this->program();$currentPerson=$this->person;
        $this->person=$this->persona();$foreign=$this->enroll();$this->person=$currentPerson;
        $foreignProgram=$this->program($foreign,['fecha'=>'2026-11-02','hora_ini'=>'11:30','hora_fin'=>'12:30','estado_id'=>estado('INDEFINIDO')]);
        $attendance=$this->recorded($foreignProgram);
        $this->branchScope();
        DB::table('sede_registros')->insert(['tabla'=>'programacions','registro_id'=>$foreignProgram->id,'sede_id'=>2]);
        DB::table('sede_registros')->insert(['tabla'=>'clases','registro_id'=>$attendance->id,'sede_id'=>2]);
        $this->assertFalse(Programacion::whereKey($foreignProgram->id)->exists());
        try {
            app(ClassAttendanceService::class)->create('apoyo',$this->enrollment->id,$this->data($original),$this->operator);
            $this->fail('Debe detectar también la ocupación del docente en otra sucursal.');
        } catch(\Illuminate\Validation\ValidationException $expected) {
            $this->assertArrayHasKey('docente_id',$expected->errors());$this->assertSame(0,ClaseAdicional::count());
        } finally { app(\App\Services\SedeAccess::class)->reset(); }
    }

    public function test_missed_additional_can_be_rescheduled_with_trace_without_changing_original(): void
    {
        $original=$this->program();
        $id=$this->postJson($this->url(),$this->data($original,['marcar_entrada'=>0]))->assertOk()->json('id');
        Programacion::whereKey($id)->update(['estado_id'=>estado('FALTA')]);
        $this->patchJson(route('asistencia.adicional.correct',['apoyo',$id]),$this->data(null,['fecha'=>'2026-11-03','marcar_entrada'=>0]))->assertOk();
        $program=Programacion::find($id);
        $this->assertEquals(estado('INDEFINIDO'),$program->estado_id);
        $this->assertSame('2026-11-03',$program->fecha->format('Y-m-d'));
        $this->assertEquals(estado('FALTA'),ClaseAsistenciaEvento::where('accion','correccion')->sole()->antes['programacion']['estado_id']);
        $this->assertEquals(estado('LICENCIA'),$original->fresh()->estado_id);
    }

    public function test_normal_attendance_with_legacy_seconds_remains_supported_and_audited(): void
    {
        $program=$this->program(null,['fecha'=>'2026-11-02','estado_id'=>estado('INDEFINIDO')]);
        $this->get(route('marcado.presente.tabs',$this->person))->assertOk()->assertSee('Entrada rápida');
        $this->postJson(route('clases.guardar',$program),$this->data(null,['horainicio'=>'09:00:30','horafin'=>'10:00:30']))->assertOk();
        $this->assertSame(1,Clase::count());$this->assertSame(1,ClaseAsistenciaEvento::where('accion','entrada')->count());
        $this->postJson(route('clases.finalizar'),['id'=>Clase::sole()->id])->assertOk();
        $this->assertEquals(estado('FINALIZADO'),$program->fresh()->estado_id);
    }

    public function test_unattended_advance_can_be_recovered_after_original_date_with_audit(): void
    {
        $original=$this->program(null,['fecha'=>'2026-11-05','estado_id'=>estado('INDEFINIDO')]);
        $id=$this->postJson($this->url(),$this->data($original,['tipo'=>'adelanto','marcar_entrada'=>0]))->assertOk()->json('id');
        Carbon::setTestNow('2026-11-06 10:00:00');
        $this->patchJson(route('asistencia.adicional.correct',['apoyo',$id]),$this->data(null,['fecha'=>'2026-11-06','marcar_entrada'=>0]))->assertOk();
        $this->assertSame('recuperacion',Programacion::find($id)->adicional->tipo);
        $event=ClaseAsistenciaEvento::where('accion','correccion')->sole();
        $this->assertSame('adelanto',$event->antes['adicional']['tipo']);$this->assertSame('recuperacion',$event->despues['adicional']['tipo']);
        $this->assertEquals(estado('INDEFINIDO'),$original->fresh()->estado_id);
        $this->postJson(route('clases.guardar',$id),$this->data(null,['fecha'=>'2026-11-06']))->assertOk();
    }

    public function test_present_class_report_includes_additional_without_a_topic_and_links_to_audit(): void
    {
        DB::connection()->getPdo()->sqliteCreateFunction('concat_ws',fn($separator,...$parts)=>implode($separator,$parts));
        $original=$this->program();$this->postJson($this->url(),$this->data($original))->assertOk();
        $response=$this->getJson(route('clases.presente'),['X-Requested-With'=>'XMLHttpRequest'])->assertOk()->assertJsonCount(1,'data');
        $this->assertStringContainsString('Recuperación',$response->json('data.0.btn'));
        $this->assertStringContainsString('Historial y correcciones',$response->json('data.0.btn'));
    }

    public function test_paid_extra_can_be_recovered_without_recharging_or_modifying_its_history(): void
    {
        // A legacy unlinked extra remains a contracted block; existing rows are not rewritten.
        $legacy=$this->program(null,['fecha'=>'2026-11-03','hora_ini'=>'11:00','hora_fin'=>'12:00','estado_id'=>estado('INDEFINIDO')]);
        ClaseAdicional::create(['programacion_id'=>$legacy->id,'tipo'=>'extra','solicitud'=>(string)Str::uuid(),
            'creado_por'=>$this->operator->id,'usuario_nombre'=>$this->operator->name,'motivo'=>'Extra anterior sin vínculo original']);
        $extra=$legacy->id;
        Carbon::setTestNow('2026-11-04 10:00:00');Programacion::whereKey($extra)->update(['estado_id'=>estado('FALTA')]);
        $original=Programacion::find($extra);$before=$original->getAttributes();
        $this->postJson($this->url(),$this->data($original,['fecha'=>'2026-11-04']))->assertOk();
        $this->assertEquals($before,$original->fresh()->getAttributes());
        $this->assertEquals(1,Programacion::horasContratadas()->sum('horas_por_clase'));
        $this->assertSame(1,DB::table('pagos')->count());
        $stats=app(ClassAttendanceView::class)->tab('apoyo',$this->enrollment)['stats'];
        $this->assertSame(1,$stats['Extras']);$this->assertSame(1,$stats['Recuperadas']);$this->assertSame(1,$stats['Faltas']);
        $this->assertSame(0,$stats['Pendientes de recuperación']);
        $this->patchJson(route('asistencia.adicional.correct',['apoyo',$extra]),$this->data(null,['fecha'=>'2026-11-04']))->assertUnprocessable()->assertJsonValidationErrors('programacion');
    }

    public function test_stale_original_model_cannot_overwrite_linked_history_but_payments_can_update(): void
    {
        $original=$this->program();$this->assertNull($original->compensacion);
        $this->postJson($this->url(),$this->data($original))->assertOk();
        $original->fecha='2026-10-29';
        try { $original->save();$this->fail('Una ficha antigua no debe modificar la clase original compensada.'); }
        catch(\Illuminate\Validation\ValidationException $expected) { $this->assertArrayHasKey('programacion',$expected->errors()); }
        $this->assertSame('2026-10-28',$original->fresh()->fecha->format('Y-m-d'));
        $original=$original->fresh();$original->habilitado=0;$original->save();
        $this->assertFalse((bool)$original->fresh()->habilitado);
    }

    private function otherEnrollment(string $channel='apoyo')
    {
        $current=$this->person;
        try { $this->person=$this->persona();return $this->enroll($channel); }
        finally { $this->person=$current; }
    }

    private function recorded($program,array $data=[])
    {
        $support=$program instanceof Programacion;
        $attendance=$support?new Clase:new Clasecom;
        $attendance->forceFill(array_merge([
            $support?'programacion_id':'programacioncom_id'=>$program->id,
            'fecha'=>$program->fecha->format('Y-m-d'),
            'horainicio'=>$program->{$support?'hora_ini':'horaini'}->format('H:i:s'),
            'horafin'=>$program->{$support?'hora_fin':'horafin'}->format('H:i:s'),
            'docente_id'=>$program->docente_id,'aula_id'=>$program->aula_id,'estado_id'=>estado('PRESENTE'),
        ],$support?['materia_id'=>$program->materia_id]:[],$data))->save();
        $program->estado_id=$attendance->estado_id;$program->save();
        return $attendance;
    }

    public function test_class_type_is_empty_by_default_and_required_on_server(): void
    {
        $this->program();$computing=$this->enroll('computacion');
        $html=$this->get(route('marcado.presente.tabs',$this->person))->assertOk()->getContent();
        $dom=new \DOMDocument;@$dom->loadHTML('<?xml encoding="UTF-8">'.$html);$xpath=new \DOMXPath($dom);
        foreach($xpath->query('//select[@name="tipo"]') as $select){
            $this->assertTrue($select->hasAttribute('required'));
            $this->assertSame('',$xpath->query('./option[@selected]',$select)->item(0)->getAttribute('value'));
            $this->assertSame(4,$xpath->query('./option',$select)->length);
        }
        foreach([$this->enrollment,$computing] as $enrollment){
            $this->postJson($this->url($enrollment),$this->data(null,['tipo'=>'']))->assertUnprocessable()->assertJsonValidationErrors('tipo');
        }
        $this->assertSame(0,ClaseAdicional::count());
    }

    public function test_original_metadata_and_ninety_minutes_are_preserved_with_manual_authorized_changes(): void
    {
        $original=$this->program(null,['hora_ini'=>'09:00','hora_fin'=>'10:30','horas_por_clase'=>1.5]);
        $subject=DB::table('materias')->insertGetId(['materia'=>'Lenguaje']);
        DB::table('materiables')->insert(['materia_id'=>$subject,'materiable_id'=>1,'materiable_type'=>\App\Models\Nivel::class]);
        $room=DB::table('aulas')->insertGetId(['aula'=>'Aula 2']);
        $teacher=DB::table('docentes')->insertGetId(['persona_id'=>$this->persona()->id,'nombrecorto'=>'Otro docente','estado_id'=>estado('HABILITADO')]);
        $data=$this->data($original,['horainicio'=>'14:20','horafin'=>'15:50','docente_id'=>$teacher,'aula_id'=>$room,'materia_id'=>$subject]);
        $this->postJson($this->url(),array_merge($data,['horafin'=>'15:49']))->assertUnprocessable()->assertJsonValidationErrors('horafin');
        $id=$this->postJson($this->url(),$data)->assertOk()->json('id');
        $program=Programacion::findOrFail($id);
        $this->assertEquals(1.5,$program->horas_por_clase);$this->assertSame('15:50',$program->hora_fin->format('H:i'));
        $this->assertEquals($teacher,$program->docente_id);$this->assertEquals($subject,$program->materia_id);$this->assertEquals($room,$program->aula_id);
        $this->assertSame('09:00',$original->fresh()->hora_ini->format('H:i'));$this->assertEquals($this->teacher,$original->fresh()->docente_id);
        $this->get(route('asistencia.historial',['apoyo',$this->enrollment->id]))->assertOk()->assertSee('14:20')->assertSee('15:50');
    }

    public function test_n_students_can_share_teacher_room_and_time_across_both_circuits_without_duplicate_attendance(): void
    {
        foreach(['apoyo','computacion','apoyo','computacion','apoyo','computacion'] as $channel){
            $enrollment=$this->otherEnrollment($channel);
            $program=$this->program($enrollment,array_merge(['fecha'=>'2026-11-02','estado_id'=>estado('INDEFINIDO')],
                $channel==='apoyo'?['hora_ini'=>'11:00','hora_fin'=>'12:00']:['horaini'=>'11:00','horafin'=>'12:00']));
            $route=route($channel==='apoyo'?'clases.guardar':'clasescom.guardar',$program);
            $id=$this->postJson($route,$this->data())->assertOk()->json('id');
            $this->postJson($route,$this->data())->assertOk()->assertJsonPath('id',$id);
        }
        $this->assertSame(3,Clase::count());$this->assertSame(3,Clasecom::count());
        $this->assertSame(3,Programacion::count());$this->assertSame(3,Programacioncom::count());
        $this->assertSame(6,ClaseAsistenciaEvento::where('accion','entrada')->count());
    }

    public function test_recovery_advance_and_extra_can_join_an_existing_shared_teaching_slot(): void
    {
        $this->program(null,['fecha'=>'2026-11-02','hora_ini'=>'11:00','hora_fin'=>'12:00','estado_id'=>estado('INDEFINIDO')]);
        foreach(['apoyo','computacion'] as $channel){
            foreach(['recuperacion','adelanto','extra'] as $type){
                $enrollment=$this->otherEnrollment($channel);
                $original=$this->program($enrollment,$type!=='recuperacion'?['fecha'=>'2026-11-05','estado_id'=>estado('INDEFINIDO')]:[]);
                $this->postJson($this->url($enrollment),$this->data($original,['tipo'=>$type]))->assertOk();
            }
        }
        $this->assertSame(6,ClaseAdicional::count());$this->assertSame(3,Clase::count());$this->assertSame(3,Clasecom::count());
        $this->assertSame(2,ClaseAdicional::where('tipo','extra')->count());
    }

    public function test_different_rooms_still_conflict_for_actual_teacher_work(): void
    {
        $original=$this->program();$other=$this->otherEnrollment('computacion');
        $room=DB::table('aulas')->insertGetId(['aula'=>'Aula incompatible']);
        $foreign=$this->program($other,['fecha'=>'2026-11-02','horaini'=>'11:30','horafin'=>'12:30','aula_id'=>$room,'estado_id'=>estado('INDEFINIDO')]);
        $foreign->estado_id=estado('FINALIZADO');$foreign->save();
        DB::table('clasecoms')->insert(['programacioncom_id'=>$foreign->id,'fecha'=>'2026-11-02','horainicio'=>'11:30','horafin'=>'12:30',
            'docente_id'=>$this->teacher,'aula_id'=>$room,'estado_id'=>estado('FINALIZADO')]);
        $this->postJson($this->url(),$this->data($original))->assertUnprocessable()->assertJsonValidationErrors('docente_id');
        $this->assertSame(0,ClaseAdicional::count());
    }

    public function test_student_overlap_remains_blocked_even_in_same_teacher_room_and_adjacent_slots_are_allowed(): void
    {
        $original=$this->program();
        $overlap=$this->program(null,['fecha'=>'2026-11-02','hora_ini'=>'11:30','hora_fin'=>'12:30','estado_id'=>estado('INDEFINIDO')]);
        $this->postJson($this->url(),$this->data($original))->assertUnprocessable()->assertJsonValidationErrors('horainicio');
        $overlap->hora_ini='10:00';$overlap->hora_fin='11:00';$overlap->save();
        $this->postJson($this->url(),$this->data($original))->assertOk();
    }

    public function test_topic_search_is_paginated_filtered_and_treats_search_wildcards_literally(): void
    {
        for($i=1;$i<=45;$i++)DB::table('temas')->insert(['materia_id'=>$this->subject,'tema'=>'Tema '.str_pad($i,2,'0',STR_PAD_LEFT)]);
        DB::table('temas')->insert(['materia_id'=>999,'tema'=>'Tema ajeno']);
        $url=route('asistencia.temas',$this->enrollment);
        $first=$this->getJson($url.'?materia_id='.$this->subject.'&q=Tema&page=1')->assertOk()->assertJsonCount(20,'results')->assertJsonPath('pagination.more',true);
        $second=$this->getJson($url.'?materia_id='.$this->subject.'&q=Tema&page=2')->assertOk()->assertJsonCount(20,'results');
        $this->assertEmpty(array_intersect(array_column($first->json('results'),'id'),array_column($second->json('results'),'id')));
        $this->getJson($url.'?materia_id='.$this->subject.'&q=Tema&page=3')->assertOk()->assertJsonCount(5,'results')->assertJsonPath('pagination.more',false);
        $this->getJson($url.'?materia_id='.$this->subject.'&q=%25')->assertOk()->assertJsonCount(0,'results');
        $this->getJson($url.'?materia_id=999&q=Tema')->assertForbidden();
        $this->getJson($url.'?materia_id='.$this->subject.'&page=0')->assertUnprocessable();
        $viewer=$this->actor('Docente');$this->actingAs($viewer);
        $this->getJson($url.'?materia_id='.$this->subject.'&q=Tema')->assertForbidden();
    }

    public function test_optional_topic_survives_scheduling_later_attendance_and_correction(): void
    {
        $topic=DB::table('temas')->insertGetId(['materia_id'=>$this->subject,'tema'=>'Fracciones']);
        $original=$this->program();
        $id=$this->postJson($this->url(),$this->data($original,['tema_id'=>$topic,'marcar_entrada'=>0]))->assertOk()->json('id');
        $this->assertDatabaseHas('clases_adicionales',['programacion_id'=>$id,'tema_id'=>$topic]);
        $this->get(route('asistencia.historial',['apoyo',$this->enrollment->id]))->assertOk()->assertSee('Fracciones');
        $this->postJson(route('clases.guardar',$id),$this->data())->assertOk();
        $this->assertEquals($topic,Clase::sole()->tema_id);
        $this->patchJson(route('asistencia.adicional.correct',['apoyo',$id]),$this->data(null,['tema_id'=>null,'motivo'=>'Retirar tema sin alterar la clase']))->assertOk();
        $this->assertNull(Clase::sole()->tema_id);$this->assertNull(Programacion::find($id)->adicional->tema_id);
        $this->assertEquals($topic,ClaseAsistenciaEvento::where('accion','correccion')->sole()->antes['adicional']['tema_id']);
    }

    public function test_topic_lookup_cannot_use_an_enrollment_from_another_branch(): void
    {
        $this->branchScope();
        DB::table('sede_registros')->insert(['tabla'=>'inscripciones','registro_id'=>$this->enrollment->id,'sede_id'=>2]);
        $request=\Illuminate\Http\Request::create('/asistencia/temas/'.$this->enrollment->id,'GET',['materia_id'=>$this->subject,'q'=>'Tema']);
        $request->setUserResolver(fn()=>$this->operator);
        try{
            app(\App\Http\Controllers\ClaseAdicionalController::class)->topics($request,$this->enrollment->id);
            $this->fail('No puede consultar temas a través de una inscripción de otra Sucursal.');
        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $expected){$this->assertTrue(true);}
        finally{app(\App\Services\SedeAccess::class)->reset();}
    }

    public function test_existing_attendance_topic_is_preserved_when_correcting_a_legacy_additional(): void
    {
        $topic=DB::table('temas')->insertGetId(['materia_id'=>$this->subject,'tema'=>'Tema histórico']);
        $original=$this->program();
        $id=$this->postJson($this->url(),$this->data($original,['tema_id'=>$topic]))->assertOk()->json('id');
        DB::table('clases_adicionales')->where('programacion_id',$id)->update(['tema_id'=>null]);
        $html=$this->get(route('asistencia.historial',['apoyo',$this->enrollment->id]))->assertOk()->assertSee('Tema histórico')->getContent();
        $dom=new \DOMDocument;@$dom->loadHTML('<?xml encoding="UTF-8">'.$html);$xpath=new \DOMXPath($dom);
        $this->assertSame((string)$topic,$xpath->query('//form//select[@name="tema_id"]/option[@selected]')->item(0)->getAttribute('value'));
        $this->patchJson(route('asistencia.adicional.correct',['apoyo',$id]),$this->data(null,['horainicio'=>'13:00','horafin'=>'14:00']))->assertOk();
        $this->assertEquals($topic,Clase::sole()->tema_id);$this->assertEquals($topic,ClaseAdicional::sole()->tema_id);
    }

    public function test_topic_must_belong_to_subject_but_empty_topic_is_accepted(): void
    {
        $topic=DB::table('temas')->insertGetId(['materia_id'=>999,'tema'=>'Tema ajeno']);$original=$this->program();
        $this->postJson($this->url(),$this->data($original,['tema_id'=>$topic]))->assertUnprocessable()->assertJsonValidationErrors('tema_id');
        $this->postJson($this->url(),$this->data($original,['tema_id'=>null]))->assertOk();
        $this->assertNull(Clase::sole()->tema_id);$this->assertNull(ClaseAdicional::sole()->tema_id);
    }

    public function test_topic_migration_preserves_existing_metadata_and_prevents_discarding_recorded_topics(): void
    {
        $original=$this->program();$this->postJson($this->url(),$this->data($original,['marcar_entrada'=>0]))->assertOk();
        $before=ClaseAdicional::sole()->getAttributes();unset($before['tema_id']);
        // Recreate the pre-migration fixture; Laravel 8 cannot drop SQLite foreign keys.
        DB::statement('ALTER TABLE clases_adicionales DROP COLUMN tema_id');
        (new \AddTopicToClasesAdicionales)->up();
        $after=ClaseAdicional::sole()->getAttributes();$this->assertNull($after['tema_id']);unset($after['tema_id']);
        $this->assertEquals($before,$after);
        $topic=DB::table('temas')->insertGetId(['materia_id'=>$this->subject,'tema'=>'Conservar']);ClaseAdicional::query()->update(['tema_id'=>$topic]);
        try{(new \AddTopicToClasesAdicionales)->down();$this->fail('No debe descartarse un tema registrado.');}
        catch(\RuntimeException $expected){$this->assertStringContainsString('temas',$expected->getMessage());}
        $this->assertEquals($topic,ClaseAdicional::sole()->tema_id);
    }

    public function test_actual_teacher_attendance_in_another_branch_is_not_treated_as_a_shared_slot(): void
    {
        $original=$this->program();$foreign=$this->program($this->otherEnrollment('computacion'),[
            'fecha'=>'2026-11-02','horaini'=>'11:00','horafin'=>'12:00','estado_id'=>estado('FINALIZADO')]);
        DB::table('clasecoms')->insert(['programacioncom_id'=>$foreign->id,'fecha'=>'2026-11-02','horainicio'=>'11:00','horafin'=>'12:00',
            'docente_id'=>$this->teacher,'aula_id'=>$this->room,'estado_id'=>estado('FINALIZADO')]);
        $this->branchScope();
        DB::table('sede_registros')->insert(['tabla'=>'matriculacions','registro_id'=>$foreign->matriculacion_id,'sede_id'=>2]);
        try{
            app(ClassAttendanceService::class)->create('apoyo',$this->enrollment->id,$this->data($original),$this->operator);
            $this->fail('No puede compartir una asistencia de otra Sucursal.');
        }catch(\Illuminate\Validation\ValidationException $expected){$this->assertArrayHasKey('docente_id',$expected->errors());}
        finally{app(\App\Services\SedeAccess::class)->reset();}
        $this->assertSame(0,ClaseAdicional::count());
    }

    public function test_both_full_marking_pages_render_the_same_empty_type_and_local_topic_search_assets(): void
    {
        $computing=$this->enroll('computacion');
        Schema::create('motivos',function(Blueprint $t){$t->id();$t->string('motivo');});
        DB::table('motivos')->insert(['id'=>1,'motivo'=>'Apoyo de prueba']);
        foreach(['inscripciones','matriculacions'] as $table){
            Schema::table($table,function(Blueprint $t){$t->unsignedBigInteger('motivo_id')->nullable();});
            DB::table($table)->update(['motivo_id'=>1]);
        }
        Schema::create('dias',function(Blueprint $t){$t->id();$t->string('dia');});
        foreach(['sesions'=>'inscripcione_id','sesioncoms'=>'matriculacion_id'] as $table=>$key){
            Schema::create($table,function(Blueprint $t)use($key){$t->id();$t->unsignedBigInteger($key);$t->unsignedBigInteger('dia_id');$t->unsignedBigInteger('aula_id');$t->unsignedBigInteger('docente_id');$t->unsignedBigInteger('materia_id')->nullable();});
        }
        DB::connection()->getPdo()->sqliteCreateFunction('now',fn()=>now()->toDateTimeString());
        DB::connection()->getPdo()->sqliteCreateFunction('DAYOFWEEK',fn($date)=>Carbon::parse($date)->dayOfWeek+1);
        foreach([route('clases.marcado.general',$this->enrollment),route('clases.marcadocom.general',$computing)] as $url){
            $this->get($url)->assertOk()->assertSee('Elija tipo de clase')->assertSee('vendor/select2/js/select2.full.min.js')->assertSee('vistas/clase/asistencia.js');
        }
    }

    public function test_followup_absences_exclude_only_completed_compensations_and_preserve_historical_state(): void
    {
        $original=$this->program(null,['estado_id'=>estado('FALTA')]);
        $pending=$this->postJson($this->url(),$this->data($original,['marcar_entrada'=>0]))->assertOk()->json('id');
        $service=app(ClassAttendanceService::class);
        $this->assertSame(0,DB::table('programacions')->where('id',$original->id)->whereIn('id',$service->resolvedAbsenceIds('programacions'))->count());
        $this->postJson(route('clases.guardar',$pending),$this->data())->assertOk();
        $this->assertSame(1,DB::table('programacions')->where('id',$original->id)->whereIn('id',$service->resolvedAbsenceIds('programacions'))->count());
        $this->assertEquals(estado('FALTA'),$original->fresh()->estado_id);
    }
}

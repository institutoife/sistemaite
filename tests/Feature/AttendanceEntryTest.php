<?php
namespace Tests\Feature;

use App\Models\{Clase, Clasecom};
use App\Services\{ClassAttendanceService, ClassAttendanceView, TeacherPresence};
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\AttendanceTestCase;

class AttendanceEntryTest extends AttendanceTestCase
{
    public static function arrivals(): array
    {
        $cases=[];
        foreach(['apoyo','computacion'] as $channel) {
            foreach([['08:49','early',11],['08:50',null,10],['09:10',null,10],['09:11','late',11]] as [$time,$type,$minutes])
                $cases[]=[$channel,$time,$type,$minutes,$channel==='apoyo'?90:60];
        }
        return $cases;
    }

    /** @dataProvider arrivals */
    public function test_early_and_late_entries_preserve_enrolled_values_and_warn_only_beyond_tolerance(string $channel,string $time,?string $type,int $minutes,int $duration): void
    {
        $enrollment=$channel==='apoyo'?$this->enrollment:$this->enroll($channel);
        $program=$this->program($enrollment,array_merge(['fecha'=>'2026-11-02','estado_id'=>estado('INDEFINIDO')],
            $channel==='apoyo'?['hora_ini'=>'09:00','hora_fin'=>'10:30']:['horaini'=>'09:00','horafin'=>'10:00']));
        $before=$program->getAttributes();
        $teacher=DB::table('docentes')->insertGetId(['persona_id'=>$this->persona()->id,'estado_id'=>estado('HABILITADO')]);
        $room=DB::table('aulas')->insertGetId(['aula'=>'Aula efectiva']);
        $subject=DB::table('materias')->insertGetId(['materia'=>'Física']);
        DB::table('materiables')->insert(['materia_id'=>$subject,'materiable_id'=>1,'materiable_type'=>\App\Models\Nivel::class]);
        $end=Carbon::parse($time)->addMinutes($duration)->format('H:i');
        $route=route($channel==='apoyo'?'clases.guardar':'clasescom.guardar',$program);
        $data=$this->data(null,['horainicio'=>$time,'horafin'=>$end,'docente_id'=>$teacher,'aula_id'=>$room,'materia_id'=>$subject]);
        $response=$this->postJson($route,$data)->assertOk();
        $notice=$response->json('arrival_notice');
        if ($type===null) $this->assertNull($notice);
        else {
            $this->assertSame($type,$notice['type']);$this->assertSame($time,$notice['actual']);
            $this->assertSame($minutes,$notice['minutes']);$this->assertStringContainsString('Horario inscrito: 09:00',$notice['message']);
        }
        $actual=($channel==='apoyo'?Clase::class:Clasecom::class)::findOrFail($response->json('id'));
        $this->assertSame($time,$actual->horainicio->format('H:i'));$this->assertSame($end,$actual->horafin->format('H:i'));
        $this->assertEquals($teacher,$actual->docente_id);$this->assertEquals($room,$actual->aula_id);
        foreach($before as $key=>$value) if(!in_array($key,['estado_id','updated_at'])) $this->assertEquals($value,$program->fresh()->getRawOriginal($key),$key);
        $this->postJson($route,$data)->assertOk()->assertJsonPath('id',$actual->id);
        $this->assertSame(1,($channel==='apoyo'?Clase::class:Clasecom::class)::count());
    }

    public function test_modal_keeps_full_entry_forms_visible_and_cleans_objective_safely(): void
    {
        $this->enrollment->objetivo='<p>3RO SEC. &nbsp;CLASES DE MATEMATICAS , FISICA Y QUIMICA&nbsp;DE LUNES A VIERNES&nbsp;&nbsp;</p>';
        $this->enrollment->save();
        $this->program(null,['fecha'=>'2026-11-02','hora_ini'=>'09:00','hora_fin'=>'10:30','estado_id'=>estado('INDEFINIDO')]);
        $this->program($this->enroll('computacion'),['fecha'=>'2026-11-02','estado_id'=>estado('INDEFINIDO')]);
        $html=$this->get(route('marcado.presente.tabs',$this->person))->assertOk()
            ->assertSee('3RO SEC. CLASES DE MATEMATICAS , FISICA Y QUIMICA DE LUNES A VIERNES')
            ->assertSee('PROGRAMADA')->assertSee('Horario inscrito: 09:00–10:30')
            ->assertDontSee('Revisar horario y marcar entrada')->assertDontSee('Entrada rápida')->getContent();
        $dom=new \DOMDocument;@$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);$xpath=new \DOMXPath($dom);
        $forms=$xpath->query('//form[contains(@class,"attendance-entry-form")]');$this->assertSame(2,$forms->length);
        foreach($forms as $form) {
            $this->assertSame(0,$xpath->query('ancestor::details',$form)->length);
            $this->assertSame(1,$xpath->query('.//button[@type="submit"]',$form)->length);
            $this->assertSame(1,$xpath->query('.//button[@type="button" and contains(@class,"attendance-scheduled")]',$form)->length);
            $this->assertSame(1,$xpath->query('.//select[@name="docente_id"]',$form)->length);
        }
        $this->assertSame('&',app(ClassAttendanceView::class)->objective('&amp; &lt;img src=x onerror=alert(1)&gt;'));
    }

    public static function channels(): array { return [['apoyo','computacion'],['computacion','apoyo']]; }

    /** @dataProvider channels */
    public function test_payment_summary_uses_channel_read_permission_and_current_receipts(string $channel,string $otherChannel): void
    {
        $permission=$channel==='apoyo'?'Listar Pagos':'Listar Pagoscomputacion';
        $otherPermission=$otherChannel==='apoyo'?'Listar Pagos':'Listar Pagoscomputacion';
        $enrollment=$channel==='apoyo'?$this->enrollment:$this->enroll($channel);
        $enrollment->costo=450;$enrollment->save();
        DB::table('pagos')->where('pagable_type',get_class($enrollment))->where('pagable_id',$enrollment->id)->update(['monto'=>175]);
        DB::table('pagos')->insert(['pagable_type'=>get_class($enrollment),'pagable_id'=>$enrollment->id,'monto'=>125]);
        $render=function() use($channel,$enrollment) {
            $tab=app(ClassAttendanceView::class)->tab($channel,$enrollment->fresh());
            return view('clase.asistencia.summary',['tab'=>$tab,'enrollment'=>$tab['enrollment']])->render();
        };
        $this->operator->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate(['name'=>$otherPermission,'guard_name'=>'web']));
        $hidden=$render();
        $this->assertStringNotContainsString('aria-label="Resumen de pagos"',$hidden);
        $this->assertStringContainsString('No tienes permiso para consultar los pagos',$hidden);
        $this->assertStringNotContainsString('450.00',$hidden);
        $this->operator->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate(['name'=>$permission,'guard_name'=>'web']));
        $this->assertFalse($this->operator->can('Reporte Pagos'));
        $html=$render();
        $this->assertStringContainsString('aria-label="Resumen de pagos"',$html);
        $this->assertStringContainsString('Monto',$html);$this->assertStringContainsString('450.00',$html);
        $this->assertStringContainsString('A cuenta',$html);$this->assertStringContainsString('300.00',$html);
        $this->assertStringContainsString('Saldo',$html);$this->assertStringContainsString('150.00',$html);
        $this->assertStringContainsString('attendance-finance-card--due',$html);
        DB::table('pagos')->insert(['pagable_type'=>get_class($enrollment),'pagable_id'=>$enrollment->id,'monto'=>150]);
        $this->assertStringNotContainsString('attendance-finance-card--due',$render());
        $this->operator->revokePermissionTo($permission);
        $this->operator->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate(['name'=>'Reporte Pagos','guard_name'=>'web']));
        $this->assertStringContainsString('aria-label="Resumen de pagos"',$render());
    }

    public static function pendingClasses(): array
    {
        return [['apoyo','computacion','2026-11-01'],['computacion','apoyo','2026-11-01'],
            ['apoyo','computacion','2026-11-02'],['computacion','apoyo','2026-11-02']];
    }

    /** @dataProvider channels */
    public function test_quick_entry_retains_duration_and_flashes_notice(string $channel,string $unused): void
    {
        $program=$this->program($channel==='apoyo'?$this->enrollment:$this->enroll($channel),array_merge(
            ['fecha'=>'2026-11-02','estado_id'=>estado('INDEFINIDO')],$channel==='apoyo'?['hora_ini'=>'09:00','hora_fin'=>'10:30']:['horaini'=>'09:00','horafin'=>'10:30']));
        $this->get(route($channel==='apoyo'?'marcado.presente.rapido':'marcadocom.presente.rapido',$program))
            ->assertRedirect(route('clase.presentes'))->assertSessionHas('attendance_arrival_notice.type','late');
        $class=($channel==='apoyo'?Clase::class:Clasecom::class)::sole();
        $this->assertSame('10:00',$class->horainicio->format('H:i'));$this->assertSame('11:30',$class->horafin->format('H:i'));
        $this->assertSame(60,session('attendance_arrival_notice.minutes'));
        $this->assertSame('09:00',($channel==='apoyo'?$program->fresh()->hora_ini:$program->fresh()->horaini)->format('H:i'));
    }

    /** @dataProvider pendingClasses */
    public function test_overdue_pending_exit_blocks_a_second_active_class_across_circuits(string $first,string $second,string $date): void
    {
        $before=$this->program($this->enroll($first),['fecha'=>$date]);
        $this->actual($before,['fecha'=>$date,'horainicio'=>'08:00','horafin'=>'09:00']);
        $program=$this->program($this->enroll($second),['fecha'=>'2026-11-02','estado_id'=>estado('INDEFINIDO')]);
        $route=route($second==='apoyo'?'clases.guardar':'clasescom.guardar',$program);
        $this->postJson($route,$this->data())->assertUnprocessable()->assertJsonValidationErrors('horainicio')
            ->assertJsonPath('errors.horainicio.0','El estudiante ya tiene una asistencia activa sin salida registrada. Marque su salida antes de iniciar otra clase.');
        $actual=($first==='apoyo'?Clase::class:Clasecom::class)::sole();
        app(ClassAttendanceService::class)->finish($actual,$this->operator);
        $this->postJson($route,$this->data())->assertOk();
    }

    public function test_teacher_presence_counts_distinct_people_from_both_circuits_and_removes_exits(): void
    {
        $a=$this->actual($this->program(null,['fecha'=>'2026-11-02']));
        $this->actual($this->program($this->enroll('computacion'),['fecha'=>'2026-11-02'])); // Legacy duplicate person: count once.
        $person=$this->person;$this->person=$this->persona();
        $b=$this->actual($this->program($this->enroll('computacion'),['fecha'=>'2026-11-02']));
        $this->person=$this->persona();
        $this->actual($this->program($this->enroll(),['fecha'=>'2026-11-02']),['estado_id'=>estado('FINALIZADO')]);
        $this->person=$person;
        $this->getJson(route('asistencia.docentes'))->assertOk()->assertHeader('Cache-Control','no-store, private')
            ->assertJsonPath('teachers.'.$this->teacher.'.0.presentes',2)->assertDontSee('persona_id');
        app(ClassAttendanceService::class)->finish($b,$this->operator);
        $snapshot=app(TeacherPresence::class)->snapshot();$this->assertSame(1,$snapshot[$this->teacher][0]['presentes']);
        $this->assertSame('JUAN (Aula 1, 1)',TeacherPresence::label('JUAN',$snapshot[$this->teacher],null));
        $this->assertSame('LUCAS (S/E, 0)',TeacherPresence::label('LUCAS',[],null));
        $this->get(route('marcado.presente.tabs',$this->person))->assertOk()->assertSee('Docente de prueba (Aula 1, 1)');
    }

    public function test_teacher_presence_distinguishes_physical_branches_and_requires_permission(): void
    {
        Schema::create('crm_sedes',function(Blueprint $t){$t->id();$t->string('nombre');});
        Schema::create('sede_registros',function(Blueprint $t){$t->string('tabla');$t->unsignedBigInteger('registro_id');$t->unsignedBigInteger('sede_id');});
        DB::table('crm_sedes')->insert([['id'=>1,'nombre'=>'Central'],['id'=>2,'nombre'=>'Villa']]);
        $a=$this->actual($this->program(null,['fecha'=>'2026-11-02']));
        $this->person=$this->persona();
        $b=$this->actual($this->program($this->enroll('computacion'),['fecha'=>'2026-11-02']));
        DB::table('sede_registros')->insert([
            ['tabla'=>'aulas','registro_id'=>$this->room,'sede_id'=>1],
            ['tabla'=>'clasecoms','registro_id'=>$b->id,'sede_id'=>2],
        ]);
        $snapshot=app(TeacherPresence::class)->snapshot();
        $this->assertCount(2,$snapshot[$this->teacher]);
        $this->assertSame('JUAN (Aula 1, 1)',TeacherPresence::label('JUAN',$snapshot[$this->teacher],1));
        $this->assertSame('JUAN (Aula 1 · Central, 1 / Aula 1 · Villa, 1)',TeacherPresence::label('JUAN',$snapshot[$this->teacher],null));
        $this->actingAs($this->actor('Docente'));
        $this->getJson(route('asistencia.docentes'))->assertForbidden();
        $this->actingAs($this->actor('Estudiante'));
        $this->getJson(route('asistencia.docentes'))->assertForbidden();
    }

    private function actual($program,array $values=[])
    {
        $support=$program instanceof \App\Models\Programacion;$class=$support?new Clase:new Clasecom;
        $class->forceFill(array_merge(['fecha'=>'2026-11-02','horainicio'=>'08:00','horafin'=>'09:00','docente_id'=>$this->teacher,
            'aula_id'=>$this->room,'estado_id'=>estado('PRESENTE'),$program->attendanceKey()=>$program->id],$support?['materia_id'=>$this->subject]:[],$values))->save();
        $program->estado_id=$class->estado_id;$program->save();return $class;
    }
}

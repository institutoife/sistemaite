<?php
namespace Tests\Feature;

use App\Models\{Clase, ClaseAsistenciaEvento, Clasecom};
use App\Services\{ClassAttendanceService, ClassAttendanceView};
use Illuminate\Support\Facades\DB;
use Tests\AttendanceTestCase;

class LegacyScheduledAttendanceTest extends AttendanceTestCase
{
    public static function channels(): array { return [['apoyo'],['computacion']]; }

    /** @dataProvider channels */
    public function test_enabled_legacy_class_opens_and_records_once_without_rewriting_enrolled_values(string $channel): void
    {
        $program=$this->legacyProgram($channel);
        $before=$program->getAttributes();
        $this->get(route('marcado.presente.tabs',$this->person))->assertOk()->assertSee('PROGRAMADA')
            ->assertSee('attendance-entry-form')->assertSee('Confirmar entrada')
            ->assertDontSee('Conserva su estado histórico');
        $id=$this->postJson($this->entryUrl($channel,$program),$this->data())->assertOk()->json('id');
        $this->postJson($this->entryUrl($channel,$program),$this->data())->assertOk()->assertJsonPath('id',$id);
        $class=$channel==='apoyo'?Clase::class:Clasecom::class;
        $this->assertSame(1,$class::count());
        $this->assertSame('11:00',$class::sole()->horainicio->format('H:i'));
        $this->assertSame('12:00',$class::sole()->horafin->format('H:i'));
        foreach($before as $key=>$value) if(!in_array($key,['estado_id','updated_at'])) $this->assertEquals($value,$program->fresh()->getRawOriginal($key),$key);
        $this->assertEquals(estado('PRESENTE'),$program->fresh()->estado_id);
        $event=ClaseAsistenciaEvento::where('accion','entrada')->sole();
        $this->assertEquals(estado('HABILITADO'),$event->antes['programacion']['estado_id']);
        $this->get(route('marcado.presente.tabs',$this->person))->assertOk()->assertDontSee('attendance-entry-form')->assertSee('Marcar salida');
    }

    public static function blockedClasses(): array
    {
        $cases=[];
        foreach(['apoyo','computacion'] as $channel) {
            $cases[]=[$channel,['habilitado'=>0],'pago','Registre primero el pago'];
            $cases[]=[$channel,['activo'=>0],'programacion','Esta programación está inactiva'];
            foreach(['LICENCIA','FALTA','CONGELADO','PRESENTE','FINALIZADO'] as $state)
                $cases[]=[$channel,['state'=>$state],'programacion',null];
        }
        return $cases;
    }

    /** @dataProvider blockedClasses */
    public function test_legacy_compatibility_keeps_payment_active_and_historical_guards(string $channel,array $values,string $field,?string $notice): void
    {
        if(isset($values['state'])) { $values['estado_id']=estado($values['state']);unset($values['state']); }
        $program=$this->legacyProgram($channel,$values);
        $before=$program->getAttributes();
        $modal=$this->get(route('marcado.presente.tabs',$this->person))->assertOk()->assertDontSee('attendance-entry-form');
        if ($notice) $modal->assertSee($notice);
        $this->postJson($this->entryUrl($channel,$program),$this->data())->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertSame(0,Clase::count()+Clasecom::count());
        $this->assertSame(0,ClaseAsistenciaEvento::count());
        $this->assertEquals($before,$program->fresh()->getAttributes());
    }

    /** @dataProvider channels */
    public function test_existing_attendance_wins_over_a_stale_legacy_schedule_state(string $channel): void
    {
        $program=$this->legacyProgram($channel);
        $class=$channel==='apoyo'?new Clase:new Clasecom;
        $class->forceFill(array_merge([$program->attendanceKey()=>$program->id,'fecha'=>'2026-11-02','horainicio'=>'09:00','horafin'=>'10:00',
            'docente_id'=>$this->teacher,'aula_id'=>$this->room,'estado_id'=>estado('FINALIZADO')],$channel==='apoyo'?['materia_id'=>$this->subject]:[]))->save();
        $this->get(route('marcado.presente.tabs',$this->person))->assertOk()->assertSee('Asistencia registrada')->assertDontSee('attendance-entry-form');
        $this->postJson($this->entryUrl($channel,$program),$this->data())->assertOk()->assertJsonPath('id',$class->id);
        $this->assertSame(1,Clase::count()+Clasecom::count());
        $this->assertSame(0,ClaseAsistenciaEvento::count());
        $this->assertEquals(estado('HABILITADO'),$program->fresh()->estado_id);
    }

    public static function compensations(): array
    {
        return [['apoyo','adelanto'],['apoyo','extra'],['computacion','adelanto'],['computacion','extra']];
    }

    /** @dataProvider compensations */
    public function test_legacy_future_class_can_be_consumed_once_by_advance_or_extra(string $channel,string $type): void
    {
        $program=$this->legacyProgram($channel,['fecha'=>'2026-11-05']);
        $enrollment=app(ClassAttendanceService::class)->enrollment($program);
        $before=$program->getAttributes();$receipts=DB::table('pagos')->count();
        $this->assertOriginalOption($program,'data-advance');
        $data=$this->data($program,['tipo'=>$type]);
        $id=$this->postJson($this->url($enrollment),$data)->assertOk()->json('id');
        $this->postJson($this->url($enrollment),$data)->assertOk()->assertJsonPath('id',$id);
        $this->assertEquals($before,$program->fresh()->getAttributes());
        $this->assertSame($receipts,DB::table('pagos')->count());
        $this->assertSame(1,Clase::count()+Clasecom::count());
        $this->postJson($this->entryUrl($channel,$program),$this->data())->assertUnprocessable()->assertJsonValidationErrors('programacion');
    }

    /** @dataProvider channels */
    public function test_past_unattended_legacy_class_remains_recoverable_with_its_history(string $channel): void
    {
        $program=$this->legacyProgram($channel,['fecha'=>'2026-10-28']);
        $enrollment=app(ClassAttendanceService::class)->enrollment($program);
        $before=$program->getAttributes();
        $this->assertOriginalOption($program,'data-recovery');
        $this->assertSame(1,app(ClassAttendanceView::class)->tab($channel,$enrollment)['stats']['Pendientes de recuperación']);
        $this->postJson($this->url($enrollment),$this->data($program))->assertOk();
        $this->assertEquals($before,$program->fresh()->getAttributes());
        $this->assertSame(0,app(ClassAttendanceView::class)->tab($channel,$enrollment->fresh())['stats']['Pendientes de recuperación']);
    }

    /** @dataProvider channels */
    public function test_another_enabled_legacy_class_still_blocks_student_overlap_across_channels(string $channel): void
    {
        $other=$channel==='apoyo'?'computacion':'apoyo';
        $this->legacyProgram($other,$other==='apoyo'?['hora_ini'=>'11:30','hora_fin'=>'12:30']:['horaini'=>'11:30','horafin'=>'12:30']);
        $program=$this->legacyProgram($channel,['estado_id'=>estado('INDEFINIDO')]);
        $this->postJson($this->entryUrl($channel,$program),$this->data())->assertUnprocessable()->assertJsonValidationErrors('horainicio');
        $this->assertSame(0,Clase::count()+Clasecom::count());
    }

    /** @dataProvider channels */
    public function test_enabled_legacy_state_does_not_grant_permission_to_mark(string $channel): void
    {
        $program=$this->legacyProgram($channel);
        $viewer=$this->actor('Docente');$viewer->givePermissionTo('Listar '.($channel==='apoyo'?'Clases':'Clasescom'));$this->actingAs($viewer);
        $this->get(route('marcado.presente.tabs',$this->person))->assertOk()->assertDontSee('attendance-entry-form');
        $this->postJson($this->entryUrl($channel,$program),$this->data())->assertForbidden();
        $this->assertSame(0,Clase::count()+Clasecom::count());
    }

    private function legacyProgram(string $channel,array $values=[])
    {
        return $this->program($channel==='apoyo'?$this->enrollment:$this->enroll($channel),array_merge([
            'fecha'=>'2026-11-02','estado_id'=>estado('HABILITADO')],$values));
    }

    private function entryUrl(string $channel,$program): string
    {
        return route($channel==='apoyo'?'clases.guardar':'clasescom.guardar',$program);
    }

    private function assertOriginalOption($program,string $attribute): void
    {
        $html=$this->get(route('marcado.presente.tabs',$this->person))->assertOk()->getContent();
        $dom=new \DOMDocument;@$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $xpath=new \DOMXPath($dom);
        $options=$xpath->query('//select[contains(@class,"attendance-original")]/option[@value="'.$program->id.'" and @'.$attribute.'="1"]');
        $this->assertSame(1,$options->length);
    }
}

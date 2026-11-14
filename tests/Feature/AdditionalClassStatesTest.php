<?php
namespace Tests\Feature;

use App\Models\{Clase, ClaseAdicional, ClaseAsistenciaEvento, Clasecom};
use App\Services\{ClassAttendanceService, ClassAttendanceView};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\AttendanceTestCase;

class AdditionalClassStatesTest extends AttendanceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once database_path('migrations/2026_11_03_000001_create_asistencia_salida_avisos.php');
        (new \CreateAsistenciaSalidaAvisos)->up();
        require_once database_path('migrations/2026_11_14_000001_preserve_additional_class_states.php');
    }

    public static function additionalTypes(): array
    {
        $cases=[];
        foreach (['apoyo','computacion'] as $channel) {
            foreach (ClaseAdicional::COMPLETED_STATES as $type=>$state) $cases[]=[$channel,$type,$state];
        }
        return $cases;
    }

    private function original(string $channel,string $type)
    {
        $enrollment=$channel==='apoyo'?$this->enrollment:$this->enroll($channel);
        return $this->program($enrollment,['fecha'=>$type==='recuperacion'?'2026-10-28':'2026-11-05',
            'estado_id'=>estado($type==='recuperacion'?'LICENCIA':'INDEFINIDO')]);
    }

    private function effective($original,array $data=[])
    {
        $service=app(ClassAttendanceService::class);
        return $service->create($service->canal($original),$service->enrollment($original)->id,
            $this->data($original,array_merge(['horainicio'=>'09:00','horafin'=>'10:00'],$data)),$this->operator);
    }

    /** @dataProvider additionalTypes */
    public function test_exit_keeps_the_additional_type_without_losing_real_attendance(string $channel,string $type,string $state): void
    {
        $original=$this->original($channel,$type);$before=$original->fresh()->getAttributes();
        $program=$this->effective($original,['tipo'=>$type]);
        $attendance=($channel==='apoyo'?Clase::class:Clasecom::class)::sole();
        $this->assertEquals(estado('PRESENTE'),$program->estado_id);
        $this->assertEquals(estado('PRESENTE'),$attendance->estado_id);
        $this->getJson(route('presentes.estado'))->assertOk()->assertJsonCount(1,'data');
        Carbon::setTestNow('2026-11-02 10:05:00');
        $exitRoute=route($channel==='apoyo'?'clases.finalizar':'clasecom.finalizar');
        $this->postJson($exitRoute,['id'=>$attendance->id])->assertOk();
        $this->assertEquals(estado($state),$program->fresh()->estado_id);
        $this->assertEquals(estado('FINALIZADO'),$attendance->fresh()->estado_id);
        $this->assertSame('10:05:00',$attendance->fresh()->horafin->format('H:i:s'));
        $this->assertEquals($before,$original->fresh()->getAttributes());
        $this->getJson(route('presentes.estado'))->assertOk()->assertJsonCount(0,'data');
        $this->postJson($exitRoute,['id'=>$attendance->id])->assertOk();
        $this->assertSame(1,ClaseAsistenciaEvento::where('accion','salida')->count());
        $this->assertEquals(estado($state),ClaseAsistenciaEvento::where('accion','salida')->sole()->despues['programacion_estado_id']);
        $this->postJson(route($channel==='apoyo'?'clases.guardar':'clasescom.guardar',$program),$this->data())
            ->assertOk();
        $this->assertSame(1,Clase::count()+Clasecom::count());
        $this->assertEquals(estado($state),$program->fresh()->estado_id);

        $enrollment=app(ClassAttendanceService::class)->enrollment($program);
        $today=$this->getJson(route($channel==='apoyo'?'programaciones.hoy':'programacioncom.hoy',$enrollment))->assertOk();
        $today->assertJsonPath('data.0.estado',$state);
        $tab=app(ClassAttendanceView::class)->tab($channel,$enrollment);
        $this->assertSame(1,$tab['stats']['Asistidas']);
        $this->assertSame(1,$tab['stats'][['recuperacion'=>'Recuperadas','adelanto'=>'Adelantadas','extra'=>'Extras'][$type]]);
        $this->assertSame(0,$tab['stats']['Pendientes de recuperación']);
        $service=app(ClassAttendanceService::class);
        $this->assertTrue(DB::table($original->getTable())->where('id',$original->id)->whereIn('id',$service->resolvedAbsenceIds($original->getTable()))->exists());
        $this->get(route('asistencia.historial',[$channel,$enrollment->id]))->assertOk()->assertSee($state);
    }

    /** @dataProvider additionalTypes */
    public function test_historical_attendance_gets_the_type_and_authorized_corrections_preserve_it(string $channel,string $type,string $state): void
    {
        $original=$this->original($channel,$type);
        $program=$this->effective($original,['tipo'=>$type,'fecha'=>'2026-11-01']);
        $attendance=($channel==='apoyo'?Clase::class:Clasecom::class)::sole();
        $this->assertEquals(estado($state),$program->estado_id);
        $this->assertEquals(estado('FINALIZADO'),$attendance->estado_id);
        $this->patchJson(route('asistencia.adicional.correct',[$channel,$program->id]),$this->data(null,
            ['fecha'=>'2026-11-01','horainicio'=>'08:00','horafin'=>'09:00','motivo'=>'Corrección autorizada de horario']))->assertOk();
        $this->assertEquals(estado($state),$program->fresh()->estado_id);
        $this->assertSame('08:00:00',$attendance->fresh()->horainicio->format('H:i:s'));
        $this->assertEquals(estado('FINALIZADO'),$attendance->fresh()->estado_id);
    }

    /** @dataProvider additionalTypes */
    public function test_scheduling_without_entry_does_not_mark_the_class_as_taken(string $channel,string $type,string $state): void
    {
        $original=$this->original($channel,$type);
        $program=$this->effective($original,['tipo'=>$type,'fecha'=>'2026-11-03','marcar_entrada'=>0]);
        $service=app(ClassAttendanceService::class);
        $enrollment=$service->enrollment($program);
        $this->assertEquals(estado('INDEFINIDO'),$program->estado_id);
        $this->assertFalse($service->hasAttendance($program));
        $this->assertSame(0,app(ClassAttendanceView::class)->tab($channel,$enrollment)['stats']['Asistidas']);
        $this->assertFalse(DB::table($original->getTable())->where('id',$original->id)->whereIn('id',$service->resolvedAbsenceIds($original->getTable()))->exists());
        Carbon::setTestNow('2026-11-03 10:00:00');
        $attendance=$service->record($program,$this->data(null,['fecha'=>'2026-11-03','horainicio'=>'09:00','horafin'=>'10:00']),$this->operator);
        $service->finish($attendance,$this->operator);
        $this->assertEquals(estado($state),$program->fresh()->estado_id);
    }

    /** @dataProvider additionalTypes */
    public function test_migration_reuses_catalog_ids_and_audits_only_completed_effective_programs(string $channel,string $type,string $state): void
    {
        $original=$this->original($channel,$type);$before=$original->fresh()->getAttributes();
        $program=$this->effective($original,['tipo'=>$type,'fecha'=>'2026-11-01']);
        $attendance=($channel==='apoyo'?Clase::class:Clasecom::class)::sole();$actualBefore=$attendance->getAttributes();
        DB::table($program->getTable())->where('id',$program->id)->update(['estado_id'=>estado('FINALIZADO')]);
        $stateId=estado($state);$count=DB::table('estados')->count();
        (new \PreserveAdditionalClassStates)->up();
        (new \PreserveAdditionalClassStates)->up();
        $this->assertSame($count,DB::table('estados')->count());
        $this->assertEquals($stateId,$program->fresh()->estado_id);
        $this->assertSame($actualBefore,$attendance->fresh()->getAttributes());
        $this->assertSame($before,$original->fresh()->getAttributes());
        $event=ClaseAsistenciaEvento::where('accion','estado_adicional_actualizado')->sole();
        $this->assertNull($event->usuario_id);
        $this->assertEquals(estado('FINALIZADO'),$event->antes['estado_id']);
        $this->assertEquals($stateId,$event->despues['estado_id']);
        (new \PreserveAdditionalClassStates)->down();
        $this->assertEquals($stateId,$program->fresh()->estado_id);
    }

    public function test_migration_preserves_open_pending_and_orphan_attendance_and_adds_only_missing_states(): void
    {
        $original=$this->original('apoyo','recuperacion');
        $open=$this->effective($original);
        $pending=$this->effective($this->program(),['fecha'=>'2026-11-03','marcar_entrada'=>0]);
        $orphan=$this->effective($this->program(),['fecha'=>'2026-11-04','marcar_entrada'=>0]);
        DB::table('programacions')->where('id',$orphan->id)->update(['estado_id'=>estado('FINALIZADO')]);
        $ordinary=$this->program(null,['estado_id'=>estado('FINALIZADO')]);
        $stateIds=DB::table('estados')->pluck('id','estado');
        DB::table('estados')->whereIn('estado',['RECUPERADA','EXTRA'])->delete();
        (new \PreserveAdditionalClassStates)->up();
        $this->assertEquals(estado('PRESENTE'),$open->fresh()->estado_id);
        $this->assertEquals(estado('INDEFINIDO'),$pending->fresh()->estado_id);
        $this->assertEquals(estado('FINALIZADO'),$orphan->fresh()->estado_id);
        $this->assertEquals(estado('FINALIZADO'),$ordinary->fresh()->estado_id);
        $this->assertEquals($stateIds['ADELANTADA'],estado('ADELANTADA'));
        $this->assertSame(1,DB::table('estados')->where('estado','RECUPERADA')->count());
        $this->assertSame(1,DB::table('estados')->where('estado','EXTRA')->count());
        $this->assertSame(0,ClaseAsistenciaEvento::where('accion','estado_adicional_actualizado')->count());
    }

    public function test_missing_catalog_state_rolls_back_the_exit_instead_of_losing_the_attendance(): void
    {
        $program=$this->effective($this->original('apoyo','recuperacion'));
        $attendance=Clase::sole();
        DB::table('estados')->where('estado','RECUPERADA')->delete();
        $this->postJson(route('clases.finalizar'),['id'=>$attendance->id])->assertUnprocessable()->assertJsonValidationErrors('estado');
        $this->assertEquals(estado('PRESENTE'),$attendance->fresh()->estado_id);
        $this->assertEquals(estado('PRESENTE'),$program->fresh()->estado_id);
        $this->assertSame(0,ClaseAsistenciaEvento::where('accion','salida')->count());
    }
}

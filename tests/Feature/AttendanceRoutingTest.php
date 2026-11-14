<?php
namespace Tests\Feature;

use App\Models\{Clase, Clasecom};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\AttendanceTestCase;

class AttendanceRoutingTest extends AttendanceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once database_path('migrations/2026_11_03_000001_create_asistencia_salida_avisos.php');
        (new \CreateAsistenciaSalidaAvisos)->up();
        require_once database_path('migrations/2021_04_29_030205_create_observacions_table.php');
        (new \CreateObservacionsTable)->up();
    }

    public static function channels(): array { return [['apoyo'],['computacion']]; }
    public static function additionalTypes(): array
    {
        return [['apoyo','adelanto'],['computacion','adelanto'],['apoyo','extra'],['computacion','extra'],['apoyo','recuperacion'],['computacion','recuperacion']];
    }

    /** @dataProvider additionalTypes */
    public function test_schedule_ajax_retains_parent_links_after_an_additional_class(string $channel,string $type): void
    {
        $enrollment=$channel==='apoyo'?$this->enrollment:$this->enroll($channel);
        $original=$this->program($enrollment,['fecha'=>$type==='recuperacion'?'2026-10-28':'2026-11-05','estado_id'=>estado($type==='recuperacion'?'LICENCIA':'INDEFINIDO')]);
        $effective=$this->postJson($this->url($enrollment),$this->data($original,['tipo'=>$type]))->assertOk()->json('id');
        $future=$this->getJson(route($channel==='apoyo'?'programacion.futuro':'programacioncom.futuro',$enrollment))->assertOk();
        $this->assertArrayNotHasKey('error',$future->json(),$future->json('error')??'');
        $rows=collect($future->json('data'))->keyBy('id');
        $this->assertCount(2,$rows);
        $history=route($channel==='apoyo'?'clases.marcado.general':'clases.marcadocom.general',$enrollment);
        foreach([$original->id,$effective] as $id) $this->assertStringContainsString($history,$rows[$id]['btn']);
        $today=$this->getJson(route($channel==='apoyo'?'programaciones.hoy':'programacioncom.hoy',$enrollment))->assertOk();
        $this->assertArrayNotHasKey('error',$today->json(),$today->json('error')??'');
        $today->assertJsonCount(1,'data')->assertJsonPath('data.0.id',$effective);
        $this->assertStringContainsString($history,$today->json('data.0.btn'));
        $this->getJson(route('presentes.estado'))->assertOk()->assertJsonCount(1,'data')
            ->assertJsonPath('data.0.programacion_id',$effective)->assertJsonPath('data.0.tipo',['adelanto'=>'Adelanto','extra'=>'Extra','recuperacion'=>'Recuperación'][$type]);
    }

    /** @dataProvider channels */
    public function test_native_form_submission_for_today_is_listed_until_real_exit(string $channel): void
    {
        Carbon::setTestNow('2026-11-02 10:07:37');
        $enrollment=$channel==='apoyo'?$this->enrollment:$this->enroll($channel);
        $program=$this->program($enrollment,['fecha'=>'2026-11-02','estado_id'=>estado('INDEFINIDO')]);
        $page=route($channel==='apoyo'?'marcado.presente.normal':'marcadocom.presente.normal.programacioncom',$program);
        $html=$this->get($page)->assertOk()->getContent();
        $dom=new \DOMDocument;@$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);$xpath=new \DOMXPath($dom);
        $form=$xpath->query('//form[contains(@class,"attendance-entry-form")]')->item(0);
        $this->assertNotNull($form);
        $route=$form->getAttribute('action');
        $data=$this->data(null);
        foreach(['fecha','horainicio','horafin'] as $field) {
            $input=$xpath->query('.//input[@name="'.$field.'"]',$form)->item(0);
            $data[$field]=$input->getAttribute('value');
            if($field!=='fecha') { $this->assertSame('60',$input->getAttribute('step'));$this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/',$data[$field]); }
        }
        $this->assertSame('10:07',$data['horainicio']);$this->assertSame('11:07',$data['horafin']);
        $this->from($page)->post($route,array_merge($data,['horafin'=>'09:00']))->assertRedirect($page)->assertSessionHasErrors('horafin');
        $failed=$this->get($page)->assertOk()->getContent();
        $this->assertStringContainsString('alert alert-danger',$failed);
        $this->assertStringContainsString('value="09:00"',$failed);
        $this->post($route,$data)->assertSessionHasNoErrors()->assertRedirect(route('clase.presentes'));
        $this->assertStringContainsString('Entrada registrada.',session('success'));
        $this->getJson(route('presentes.estado'))->assertOk()->assertJsonCount(1,'data')
            ->assertJsonPath('data.0.programacion_id',$program->id)->assertJsonPath('data.0.inscripcion_id',$enrollment->id);
        $class=($channel==='apoyo'?Clase::class:Clasecom::class)::sole();
        $this->assertEquals(estado('PRESENTE'),$class->estado_id);
        $this->post($route,$data)->assertRedirect(route('clase.presentes'));
        $this->assertSame(1,Clase::count()+Clasecom::count());
        $this->postJson(route($channel==='apoyo'?'clases.finalizar':'clasecom.finalizar'),['id'=>$class->id])->assertOk();
        $this->getJson(route('presentes.estado'))->assertOk()->assertJsonCount(0,'data');
    }

    /** @dataProvider channels */
    public function test_schedule_ajax_requires_its_existing_channel_permission(string $channel): void
    {
        $enrollment=$channel==='apoyo'?$this->enrollment:$this->enroll($channel);
        $this->actingAs($this->actor('Docente'));
        foreach($channel==='apoyo'?['programaciones.hoy','programacion.futuro']:['programacioncom.hoy','programacioncom.futuro'] as $route)
            $this->getJson(route($route,$enrollment))->assertForbidden();
    }

    /** @dataProvider channels */
    public function test_program_edit_cannot_create_fake_presence_without_an_attendance(string $channel): void
    {
        $program=$this->program($channel==='apoyo'?$this->enrollment:$this->enroll($channel),['fecha'=>'2026-11-02','estado_id'=>estado('INDEFINIDO')]);
        $before=$program->getAttributes();
        $this->getJson($this->editUrl($channel,$program,estado('PRESENTE')))->assertUnprocessable()->assertJsonValidationErrors('estado_id');
        $this->assertEquals($before,$program->fresh()->getAttributes());
        $this->assertSame(0,DB::table('observacions')->count());
        $this->assertSame(0,Clase::count()+Clasecom::count());
        $this->getJson(route('presentes.estado'))->assertOk()->assertJsonCount(0,'data');
    }

    /** @dataProvider channels */
    public function test_schedule_edit_keeps_real_attendance_and_requires_real_exit(string $channel): void
    {
        $program=$this->program($channel==='apoyo'?$this->enrollment:$this->enroll($channel),['fecha'=>'2026-11-02','estado_id'=>estado('INDEFINIDO')]);
        $id=$this->postJson(route($channel==='apoyo'?'clases.guardar':'clasescom.guardar',$program),$this->data(null,['horainicio'=>'09:00','horafin'=>'10:00']))->assertOk()->json('id');
        $this->getJson($this->editUrl($channel,$program,estado('PRESENTE')))->assertOk();
        $this->assertSame(1,DB::table('observacions')->count());
        foreach(['FINALIZADO','INDEFINIDO'] as $state)
            $this->getJson($this->editUrl($channel,$program,estado($state)))->assertUnprocessable()->assertJsonValidationErrors('estado_id');
        $this->assertSame(1,DB::table('observacions')->count());
        $this->getJson(route('presentes.estado'))->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.id',$id);
        $this->postJson(route($channel==='apoyo'?'clases.finalizar':'clasecom.finalizar'),['id'=>$id])->assertOk();
        $this->getJson($this->editUrl($channel,$program,estado('PRESENTE')))->assertUnprocessable()->assertJsonValidationErrors('estado_id');
        $this->assertSame(1,DB::table('observacions')->count());
        $this->getJson(route('presentes.estado'))->assertOk()->assertJsonCount(0,'data');
    }

    /** @dataProvider channels */
    public function test_orphan_present_schedule_is_explained_without_fabricating_an_attendance(string $channel): void
    {
        $program=$this->program($channel==='apoyo'?$this->enrollment:$this->enroll($channel),['fecha'=>'2026-11-02','estado_id'=>estado('PRESENTE')]);
        $this->get(route('marcado.presente.tabs',$this->person))->assertOk()->assertSee('La programación indica PRESENTE, pero no tiene una entrada registrada.')
            ->assertDontSee('attendance-entry-form');
        $this->getJson(route('presentes.estado'))->assertOk()->assertJsonCount(0,'data');
        $this->assertSame(0,Clase::count()+Clasecom::count());
        $this->assertEquals(estado('PRESENTE'),$program->fresh()->estado_id);
    }

    private function editUrl(string $channel,$program,int $state): string
    {
        return route($channel==='apoyo'?'programacion.actualizar':'programacioncom.actualizar',[
            $channel==='apoyo'?'programacion_id':'programacioncom_id'=>$program->id,
            $channel==='apoyo'?'inscripcione_id':'matriculacion_id'=>$channel==='apoyo'?$program->inscripcione_id:$program->matriculacion_id,
            'estado_id'=>$state,'activo'=>1,'fecha'=>'2026-11-02','hora_ini'=>'09:00','hora_fin'=>'10:00',
            'docente_id'=>$this->teacher,'materia_id'=>$this->subject,'aula_id'=>$this->room,
        ]);
    }
}

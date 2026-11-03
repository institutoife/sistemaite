<?php
namespace Tests;

use App\Models\{Clase, ClaseAdicional, ClaseAsistenciaEvento, Clasecom, Inscripcione, Matriculacion, Programacion, Programacioncom};
use App\Services\{ClassAttendanceService, ClassAttendanceView};
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Http, Queue, Schema};
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\GoogleContactsTestCase;

abstract class AttendanceTestCase extends GoogleContactsTestCase
{
    protected $operator, $person, $teacher, $room, $subject, $enrollment;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-02 10:00:00'));
        $this->operator=$this->actor();
        foreach(['Listar Clases','Crear Clases','Editar Clases','Eliminar Clases','Crear Programaciones','Editar Programaciones',
            'Listar Clasescom','Crear Clasescom','Editar Clasescom','Editar Programacioncom','Listar Programaciones','Listar Programacioncom'] as $permission) {
            $this->operator->givePermissionTo(Permission::firstOrCreate(['name'=>$permission,'guard_name'=>'web']));
        }
        $this->actingAs($this->operator);
        Schema::create('estados',function(Blueprint $t){$t->id();$t->string('estado');});
        foreach(['HABILITADO','CORRIENDO','INDEFINIDO','PRESENTE','FINALIZADO','FALTA','LICENCIA','CONGELADO','FALTANOTIFICADA'] as $state) DB::table('estados')->insert(['estado'=>$state]);
        Schema::create('nivels',function(Blueprint $t){$t->id();$t->string('nivel');});
        Schema::create('materias',function(Blueprint $t){$t->id();$t->string('materia');});
        Schema::create('materiables',function(Blueprint $t){$t->unsignedBigInteger('materia_id');$t->unsignedBigInteger('materiable_id');$t->string('materiable_type');});
        Schema::create('aulas',function(Blueprint $t){$t->id();$t->string('aula');$t->timestamps();});
        Schema::create('temas',function(Blueprint $t){$t->id();$t->unsignedBigInteger('materia_id');$t->string('tema');});
        Schema::create('pagos',function(Blueprint $t){$t->id();$t->string('pagable_type');$t->unsignedBigInteger('pagable_id');$t->decimal('monto',10,2);});
        Schema::table('docentes',function(Blueprint $t){$t->unsignedBigInteger('estado_id')->nullable();$t->string('nombrecorto')->nullable();});
        Schema::table('modalidads',function(Blueprint $t){$t->unsignedBigInteger('nivel_id')->nullable();});
        foreach(['inscripciones','matriculacions'] as $name) Schema::table($name,function(Blueprint $t){
            $t->unsignedBigInteger('estado_id')->nullable();$t->boolean('vigente')->default(true);$t->decimal('totalhoras',8,2)->default(4);
            $t->decimal('costo',8,2)->default(400);$t->text('objetivo')->nullable();$t->date('fecha_proximo_pago')->nullable();
        });
        foreach(['2021_05_01_025839_create_programacions_table.php'=>'CreateProgramacionsTable',
            '2021_11_05_050738_create_programacioncoms_table.php'=>'CreateProgramacioncomsTable',
            '2021_05_01_031615_create_clases_table.php'=>'CreateClasesTable',
            '2021_11_05_050904_create_clasecoms_table.php'=>'CreateClasecomsTable',
            '2026_11_02_000001_create_clases_adicionales.php'=>'CreateClasesAdicionales',
            '2026_11_02_000002_add_topic_to_clases_adicionales.php'=>'AddTopicToClasesAdicionales'] as $file=>$class) {
            require_once database_path('migrations/'.$file);(new $class)->up();
        }
        DB::table('nivels')->insert(['id'=>1,'nivel'=>'Primaria']);
        $this->subject=DB::table('materias')->insertGetId(['materia'=>'Matemática']);
        DB::table('materiables')->insert(['materia_id'=>$this->subject,'materiable_id'=>1,'materiable_type'=>\App\Models\Nivel::class]);
        DB::table('modalidads')->insert(['id'=>1,'modalidad'=>'Apoyo','nivel_id'=>1]);
        DB::table('asignaturas')->insert(['id'=>1,'asignatura'=>'Computación']);
        $this->room=DB::table('aulas')->insertGetId(['aula'=>'Aula 1']);
        $teacherPerson=$this->persona();
        $this->teacher=DB::table('docentes')->insertGetId(['persona_id'=>$teacherPerson->id,'estado_id'=>estado('HABILITADO'),'nombrecorto'=>'Docente de prueba']);
        $this->person=$this->persona();
        $this->enrollment=$this->enroll();
    }

    protected function tearDown(): void { Carbon::setTestNow();parent::tearDown(); }

    protected function enroll(string $channel='apoyo', array $data=[])
    {
        if($channel==='apoyo') {
            $model=new Inscripcione;
            $model->forceFill(array_merge(['estudiante_id'=>$this->person->estudiante->id,'modalidad_id'=>1,'estado_id'=>estado('CORRIENDO'),
                'vigente'=>1,'totalhoras'=>4,'costo'=>400],$data))->save();
        } else {
            $computacion=DB::table('computacions')->where('persona_id',$this->person->id)->value('id')
                ?: DB::table('computacions')->insertGetId(['persona_id'=>$this->person->id]);
            $this->person->unsetRelation('computacion');
            $model=new Matriculacion;
            $model->forceFill(array_merge(['computacion_id'=>$computacion,'asignatura_id'=>1,'estado_id'=>estado('CORRIENDO'),
                'vigente'=>1,'totalhoras'=>4,'costo'=>400],$data))->save();
        }
        DB::table('pagos')->insert(['pagable_type'=>get_class($model),'pagable_id'=>$model->id,'monto'=>$model->costo]);
        return $model;
    }

    protected function program($enrollment=null,array $data=[])
    {
        $enrollment=$enrollment??$this->enrollment;
        $computing=$enrollment instanceof Matriculacion;
        $model=$computing?new Programacioncom:new Programacion;
        $model->forceFill(array_merge(['fecha'=>'2026-10-28',($computing?'horaini':'hora_ini')=>'09:00',($computing?'horafin':'hora_fin')=>'10:00',
            'horas_por_clase'=>1,'habilitado'=>1,'activo'=>1,'estado_id'=>estado('LICENCIA'),'docente_id'=>$this->teacher,
            'aula_id'=>$this->room,($computing?'matriculacion_id':'inscripcione_id')=>$enrollment->id],$computing?[]:['materia_id'=>$this->subject],$data))->save();
        return $model;
    }

    protected function data($original=null,array $data=[]): array
    {
        return array_merge(['tipo'=>'recuperacion','solicitud'=>(string)Str::uuid(),'original_id'=>$original?$original->id:null,
            'fecha'=>'2026-11-02','horainicio'=>'11:00','horafin'=>'12:00','docente_id'=>$this->teacher,'aula_id'=>$this->room,
            'materia_id'=>$this->subject,'motivo'=>'Recuperación solicitada en oficina','marcar_entrada'=>1],$data);
    }

    protected function url($enrollment=null): string
    {
        $enrollment=$enrollment??$this->enrollment;
        return route('asistencia.adicional.store',[$enrollment instanceof Matriculacion?'computacion':'apoyo',$enrollment->id]);
    }

}

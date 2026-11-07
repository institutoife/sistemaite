<?php
namespace App\Services;

use App\Models\{Aula, Docente, Inscripcione, Matriculacion, Persona, Programacion, Programacioncom};
use Illuminate\Support\Collection;

class ClassAttendanceView
{
    public function tabs(Persona $person): array
    {
        $tabs=[];
        if(auth()->user()->can('Listar Clases') || auth()->user()->can('Crear Clases')) {
            // Migrated data can contain several student role rows for one Persona.
            // Read every authorized enrollment without merging or rewriting those rows.
            foreach(Inscripcione::with('modalidad.nivel.materias','pagos')->whereHas('estudiante',fn($q)=>$q->where('persona_id',$person->id))
                ->where('vigente',1)->where('estado_id',estado('CORRIENDO'))->orderBy('id')->get() as $enrollment) {
                $tabs[]=$this->tab('apoyo',$enrollment);
            }
        }
        if(auth()->user()->can('Listar Clasescom') || auth()->user()->can('Crear Clasescom')) {
            foreach(Matriculacion::with('asignatura','pagos')->whereHas('computacion',fn($q)=>$q->where('persona_id',$person->id))
                ->where('vigente',1)->where('estado_id',estado('CORRIENDO'))->orderBy('id')->get() as $enrollment) {
                $tabs[]=$this->tab('computacion',$enrollment);
            }
        }
        // Open today's enrollment first; never hide or combine the other enrollments.
        return collect($tabs)->sortByDesc(fn($tab)=>$tab['todayPrograms']->isNotEmpty())->values()->all();
    }

    public function tab(string $canal, $enrollment): array
    {
        [$pc,,,$ek,$pk,$start,$end,$permission,$programPermission]=app(ClassAttendanceService::class)->definition($canal);
        $relations=['docente.persona','aula','estado','adicional.tema','adicional.originalProgramacion','adicional.originalProgramacioncom',
            'compensacion.programacion','compensacion.programacioncom','eventosAsistencia'];
        if($canal==='apoyo')$relations[]='materia';
        $actualRelation=$canal==='apoyo'?'clases':'clasescom';
        foreach(['usuarios','docente','aula'] as $related) $relations[]=$actualRelation.'.'.$related;
        if($canal==='apoyo') { $relations[]='clases.materia';$relations[]='clases.tema'; }
        $programs=$pc::with($relations)->where($ek,$enrollment->id)->orderBy('fecha')->orderBy($start)->orderBy('id')->get();
        $today=now('America/La_Paz')->toDateString();
        $datedToday=$programs->filter(fn($p)=>$p->fecha->format('Y-m-d')===$today);
        $canAdd=auth()->user()->can('Crear '.$permission)
            && auth()->user()->can(($canal==='apoyo'?'Crear ':'Editar ').$programPermission)
            && $enrollment->vigente && (int)$enrollment->estado_id===(int)estado('CORRIENDO');
        return ['canal'=>$canal,'enrollment'=>$enrollment,'programaciones'=>$programs,'start'=>$start,'end'=>$end,
            'todayPrograms'=>$datedToday->filter(fn($p)=>!$p->compensacion),
            'compensatedToday'=>$datedToday->filter(fn($p)=>(bool)$p->compensacion),
            'permission'=>$permission,'canAdd'=>$canAdd,
            'canCorrect'=>auth()->user()->can('Editar '.$permission)&&auth()->user()->can('Editar '.$programPermission),
            'materias'=>$canal==='apoyo'?$enrollment->modalidad->nivel->materias:collect(),
            'docentes'=>Docente::with('persona')->where('estado_id',estado('HABILITADO'))->orderBy('nombrecorto')->get(),
            'aulas'=>Aula::orderBy('aula')->get(),
            'stats'=>$this->statistics($programs),
            'totalPagado'=>$enrollment->pagos->sum('monto'),
            'horasDisponibles'=>max(0,(float)$enrollment->totalhoras-$programs->filter(fn($p)=>$p->isContractedClass())->sum('horas_por_clase'))];
    }

    public function statistics(Collection $programs): array
    {
        $attended=fn($p)=>in_array((int)$p->estado_id,array_map('intval',[estado('PRESENTE'),estado('FINALIZADO')]),true);
        $absenceIds=app(ClassAttendanceService::class)->absenceIds();
        $faultIds=\App\Models\Estado::whereIn('estado',['FALTA','FALTANOTIFICADA'])->pluck('id')->map(fn($id)=>(int)$id)->all();
        $absent=fn($p)=>in_array((int)$p->estado_id,$absenceIds,true)
            && \Carbon\Carbon::parse($p->fecha->format('Y-m-d').' '.($p instanceof Programacion?$p->hora_fin:$p->horafin)->format('H:i'))->lte(now());
        return [
            'Programadas'=>$programs->filter(fn($p)=>$p->isContractedClass())->count(),
            'Asistidas'=>$programs->filter($attended)->count(),
            'Faltas'=>$programs->filter(fn($p)=>$p->isContractedClass() && in_array((int)$p->estado_id,$faultIds,true))->count(),
            'Licencias'=>$programs->filter(fn($p)=>$p->isContractedClass() && (int)$p->estado_id===(int)estado('LICENCIA'))->count(),
            'Recuperadas'=>$programs->filter(fn($p)=>$p->adicional && $p->adicional->tipo==='recuperacion' && $attended($p))->count(),
            'Adelantadas'=>$programs->filter(fn($p)=>$p->adicional && $p->adicional->tipo==='adelanto' && $attended($p))->count(),
            'Extras'=>$programs->filter(fn($p)=>$p->adicional && $p->adicional->tipo==='extra')->count(),
            'Pendientes de recuperación'=>$programs->filter(function($p)use($absent,$attended){
                if(!$p->isContractedClass()||!$absent($p)||$attended($p))return false;
                return !$p->compensacion || !$p->compensacion->programa || !$attended($p->compensacion->programa);
            })->count(),
        ];
    }
}

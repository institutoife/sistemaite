<?php
namespace App\Http\Controllers;

use App\Services\{ClassAttendanceService, ClassAttendanceView};
use Illuminate\Http\Request;
use App\Models\{Inscripcione, Tema};

class ClaseAdicionalController extends Controller
{
    public function teachers(Request $request, \App\Services\TeacherPresence $presence)
    {
        abort_if(app(\App\Services\AccountSecurity::class)->family($request->user()),403);
        abort_unless($request->user()->canAny(['Crear Clases','Editar Clases','Crear Clasescom','Editar Clasescom']),403);
        return response()->json(['teachers'=>$presence->snapshot()])->header('Cache-Control','no-store');
    }

    public function topics(Request $request, int $inscripcion)
    {
        abort_unless($request->user()->can('Crear Clases') || $request->user()->can('Editar Clases') || $request->user()->can('Listar Clases'),403);
        $data=$request->validate(['materia_id'=>'required|integer','q'=>'nullable|string|max:120','page'=>'nullable|integer|min:1|max:10000']);
        $enrollment=Inscripcione::findOrFail($inscripcion);
        abort_unless($enrollment->modalidad->nivel->materias->contains('id',(int)$data['materia_id']),403);
        $topics=Tema::where('materia_id',$data['materia_id']);
        if (trim($data['q']??'')!=='') {
            $search=str_replace(['!','%','_'],['!!','!%','!_'],trim($data['q']));
            $topics->whereRaw("tema LIKE ? ESCAPE '!'",['%'.$search.'%']);
        }
        $rows=$topics->orderBy('tema')->orderBy('id')->simplePaginate(20,['id','tema'],'page',$data['page']??1);
        return response()->json(['results'=>$rows->getCollection()->map(fn($topic)=>['id'=>$topic->id,'text'=>$topic->tema])->values(),
            'pagination'=>['more'=>$rows->hasMorePages()]]);
    }

    public function store(Request $request, string $canal, int $inscripcion, ClassAttendanceService $service)
    {
        $program=$service->create($canal,$inscripcion,$request->all(),$request->user());
        return $request->expectsJson() ? response()->json(['message'=>'Clase adicional registrada.','id'=>$program->id]) : back()->with('success','Clase adicional registrada.');
    }

    public function correct(Request $request, string $canal, int $programacion, ClassAttendanceService $service)
    {
        [$model]=$service->definition($canal);
        $program=$service->correct($model::findOrFail($programacion),$request->all(),$request->user());
        return $request->expectsJson() ? response()->json(['message'=>'Corrección guardada en el historial.','id'=>$program->id]) : back();
    }

    public function report(Request $request, string $canal, int $inscripcion, ClassAttendanceService $service, ClassAttendanceView $views)
    {
        $d=$service->definition($canal);
        abort_unless($request->user()->can('Listar '.$d[7]),403);
        $enrollment=$d[1]::findOrFail($inscripcion);
        $tab=$views->tab($canal,$enrollment);
        return view('clase.asistencia.report',compact('tab'));
    }
}

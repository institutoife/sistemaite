<?php

namespace App\Http\Controllers;

use App\Models\Computacion;
use App\Models\Matriculacion;
use App\Models\Persona;
use App\Models\Carrera;
use App\Models\Mensajeable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Contracts\DataTable as DataTable; 
use Yajra\DataTables\DataTables;
use Carbon\Carbon;

class ComputacionController extends Controller
{
    







    public function __construct()
    {
        $this->middleware('can:Listar Computacion')->only('index','mostrar_carreras','computacionEmpezando','computacionsFaltones','computacionsSinFalta','computacionFinalizando');
        $this->middleware('can:Crear Computacion')->only('GuardarNuevaCarrera');
        $this->middleware('can:Eliminar Computacion')->only('destroy');
    }
    
    public function index()
    {
        return view('computacion.index');
    }

    public function destroy(Computacion $computacion)
    {
        $computacion->delete();
        return response()->json(['mensaje' => 'Registro Eliminado correctamente']);
    }

    public function mostrar_carreras($persona){
        
        $Persona= Persona::findOrFail($persona);
        $idsCarreras=Arr::pluck($Persona->computacion->carreras, 'id');
        $CarrerasTodos=Arr::pluck(Carrera::select('id')->get(),'id');
        $CarrerasFaltantes=collect($CarrerasTodos)->diff($idsCarreras);
        $CarrerasFaltantes=Carrera::whereIn('id',$CarrerasFaltantes)->get();
        $CarrerasComputacion=Carrera::whereIn('id',$idsCarreras)->get();
        return view('computacion.carrerasconfig',compact('CarrerasComputacion','CarrerasFaltantes','Persona'));
    }

    public function GuardarNuevaCarrera(Request $request,$computacion) {
        
        $computacion = Persona::findOrFail($computacion)->computacion;
        $persona=$computacion->persona;
        $estudiante=$persona->estudiante;
        if(!empty($request->carreras)){
            $computacion->carreras()->attach(array_keys($request->carreras));
            $carrera=$computacion->carreras->last();
            return redirect()->route('matriculacion.create',['computacion'=>$computacion->id,'carrera'=>$carrera->id]);
        }
        else{
            return view('opcion.principal', compact('persona','estudiante'));
        }  
        //return redirect()->route('configuracion.gestionar.carreras',$computacion->persona->id);
    } 

    public function computacionsFaltones()
    {
        $faltonescom=Persona::join('computacions','personas.id','computacions.persona_id')
                ->join('matriculacions','matriculacions.computacion_id','computacions.id')
                ->join('programacioncoms','programacioncoms.matriculacion_id','matriculacions.id')
                ->join('estados','estados.id','programacioncoms.estado_id')
                ->join('userables','userables.userable_id','matriculacions.id')
                ->join('users','users.id','userables.user_id')
                ->where('userables.userable_type',Matriculacion::class)
                ->where('estados.estado','FALTANOTIFICADA')->whereNotIn('programacioncoms.id',app(\App\Services\ClassAttendanceService::class)->resolvedAbsenceIds('programacioncoms'))
                ->where('matriculacions.vigente',1)
                // ->where('estados.estado','FALTANOTIFICADA')
                ->select('personas.id','programacioncoms.id as programacioncom_id','nombre','apellidop','apellidom','telefono','users.name','personas.foto')
                ->get();
        return DataTables::of($faltonescom)
        ->addColumn('btn','computacion.actionfaltones')
        ->rawColumns(['btn'])
        ->toJson(); 
    }
    public function computacionsSinFalta()
    {
             $faltonescom=Persona::join('computacions','personas.id','computacions.persona_id')
                ->join('matriculacions','matriculacions.computacion_id','computacions.id')
                ->join('programacioncoms','programacioncoms.matriculacion_id','matriculacions.id')
                ->join('estados','estados.id','programacioncoms.estado_id')
                ->join('userables','userables.userable_id','matriculacions.id')
                ->join('users','users.id','userables.user_id')
                ->where('userables.userable_type',Matriculacion::class)
                ->where('estados.estado','FALTA')
                ->orWhere('estados.estado','FALTANOTIFICADA')
                ->where('matriculacions.vigente',1)
                ->select('matriculacions.id')
                ->get();
            $MatriculacionesSinFalta=Matriculacion::whereNotIn('matriculacions.id',$faltonescom)
                ->join('computacions','matriculacions.computacion_id','computacions.id')
                ->join('personas','personas.id','computacions.persona_id')
                ->join('userables','userables.userable_id','matriculacions.id')
                ->join('users','users.id','userables.user_id')
                ->where('userables.userable_type',Matriculacion::class)
                ->where('matriculacions.vigente',1)
                ->select('personas.id', 'matriculacions.id as matriculacion','nombre','apellidop','apellidom','telefono','users.name','personas.foto')
                ->groupBy('personas.id','matriculacion','nombre','apellidop','apellidom','telefono','users.name','personas.foto')
                ->get();		

            return DataTables::of($MatriculacionesSinFalta)
            ->addColumn('btn','computacion.actionsinfalta')
            ->rawColumns(['btn'])
            ->toJson(); 
    }

    public function computacionFinalizando(){
        $matriculacionesFinalizadas=Mensajeable::where('mensajeable_type',Matriculacion::class)
            ->where('mensaje_id',idMensaje('FINALIZANDOMATRICULACION'))
            ->select('mensajeable_id')
            ->get();
        
        $finalizancomputacion=Persona::join('computacions', 'computacions.persona_id','personas.id')
        ->join('matriculacions','matriculacions.computacion_id','computacions.id')
        ->join('asignaturas','matriculacions.asignatura_id','asignaturas.id')
        ->join('userables','userables.userable_id','matriculacions.id')
        ->join('users','users.id','userables.user_id')
        ->where('userables.userable_type',Matriculacion::class)
        ->where('matriculacions.vigente',1)
        ->where('matriculacions.condonado',0)
        ->whereNotIn('matriculacions.id',$matriculacionesFinalizadas)
        ->select('personas.id','matriculacions.id as matriculacion_id','nombre','apellidop','asignatura','fechafin','telefono','personas.foto','users.name as usuario')
        ->orderBy('fechafin','asc')
        ->get();        
        return DataTables::of($finalizancomputacion)
            ->addColumn('btn','computacion.actionfinalizando')
            ->rawColumns(['btn'])
            ->toJson(); 
    }
    public function computacionEmpezando(){
        $matriculacionesEmpezados=Mensajeable::where('mensajeable_type',Matriculacion::class)
            ->where('mensaje_id',idMensaje('EMPEZANDOMATRICULACION'))
            ->select('mensajeable_id')
            ->get();
        
        $empiezancomputacion=Persona::join('computacions', 'computacions.persona_id','personas.id')
        ->join('matriculacions','matriculacions.computacion_id','computacions.id')
        ->join('asignaturas','matriculacions.asignatura_id','asignaturas.id')
        ->join('userables','userables.userable_id','matriculacions.id')
        ->join('users','users.id','userables.user_id')
        ->where('userables.userable_type',Matriculacion::class)
        ->where('matriculacions.vigente',1)
        ->where('matriculacions.condonado',0)
        ->whereNotIn('matriculacions.id',$matriculacionesEmpezados)
        ->select('personas.id','matriculacions.id as matriculacion_id','nombre','apellidop','asignatura','fechaini','telefono','personas.foto','users.name as usuario')
        ->orderBy('fechaini','asc')
        ->get();        
        return DataTables::of($empiezancomputacion)
            ->addColumn('btn','computacion.actionempezando')
            ->rawColumns(['btn'])
            ->toJson(); 
    }

}

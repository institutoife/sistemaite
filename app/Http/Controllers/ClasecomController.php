<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Config;


use App\Models\Clasecom;
use App\Models\Aula;
use App\Models\Programacioncom;
use App\Models\Matriculacion;
use App\Models\Docente;
use App\Models\Observacion;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\User;

use Illuminate\Support\Facades\Auth;

use Yajra\DataTables\Contracts\DataTable as DataTable; 
use Yajra\DataTables\DataTables;


class ClasecomController extends Controller
{
  
    public function __construct()
    {
        $this->middleware('can:Listar Clasescom')->only('clasesPresentes','mostrarcom');
        $this->middleware('can:Crear Clasescom')->only('guardar');
        $this->middleware('can:Editar Clasescom')->only('edit','actualizar','editar','marcadoGeneral','marcadoRapido');
    }


    public function guardar(Request $request,$programacioncom_id)
    {
        $programa=Programacioncom::findOrFail($programacioncom_id);
        $clase=app(\App\Services\ClassAttendanceService::class)->record($programa,$request->all(),$request->user());
        return $request->expectsJson() ? response()->json(['message'=>'Asistencia registrada.','id'=>$clase->id]) : redirect()->route('clase.presentes');
    }
    

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Clasecom  $clasecom
     * @return \Illuminate\Http\Response
     */
    public function mostrarcom(Request $request)
    {
        // return response()->json($request->all());
        $clase = Clasecom::join('aulas', 'clasecoms.aula_id', 'aulas.id')
            ->join('docentes', 'clasecoms.docente_id', 'docentes.id')
            ->join('personas', 'docentes.persona_id', 'personas.id')
            ->join('estados', 'estados.id', 'clasecoms.estado_id')
            ->where('clasecoms.id',$request->id)
            ->select('clasecoms.id','fecha','estados.estado','horainicio','horafin','personas.nombre'
                    ,'personas.apellidop','personas.apellidom','personas.foto','aulas.aula',
                'clasecoms.created_at','clasecoms.updated_at')->get()->first();
        $user=Clasecom::findOrFail($request->id)->usuarios->first();
        $data=['clase'=>$clase,'user'=>$user];
        return response()->json($data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Clasecom  $clasecom
     * @return \Illuminate\Http\Response
     */
    public function editar(Request $request){
        //$request->id=1;
        $clasecom=Clasecom::findOrFail($request->id);
        $docentes = Docente::join('personas', 'personas.id', '=', 'docentes.persona_id')
        ->where('docentes.estado_id', '=', estado("HABILITADO"))
            ->select('docentes.id', 'personas.nombre', 'personas.apellidop')
            ->get();
        $programacom = $clasecom->programacioncom;
        $matriculacion = $programacom->matriculacion;
        $aulas = Aula::all();
        $data=['clasecom'=>$clasecom,'docentes'=>$docentes,'programacom'=>$programacom,'matriculacion'=>$matriculacion,'aulas'=>$aulas];
        return response()->json($data);
    }

     public function edit($id)
    {
        $clasecom = Clasecom::find($id);
        $docentes = Docente::join('personas', 'personas.id', '=', 'docentes.persona_id')
        ->where('docentes.estado_id', '=', estado("HABILITADO"))
            ->select('docentes.id', 'personas.nombre', 'personas.apellidop')
            ->get();
        $programacioncom=$clasecom->programacioncom;
        $matriculacion=$programacioncom->matriculacion;
        $aulas = Aula::all();
        $hora_inicio = $clasecom->horainicio->isoFormat('hh:mm:ss');
        $hora_fin = $clasecom->horafin->isoFormat('hh:mm:ss');
        return view('clasecom.edit', compact('docentes','clasecom', 'programacioncom', 'matriculacion','aulas', 'hora_inicio', 'hora_fin'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Clasecom  $clasecom
     * @return \Illuminate\Http\Response
     */
    public function actualizar(Request $request, Clasecom $clasecom)
    {
        app(\App\Services\ClassAttendanceService::class)->protectHistory($clasecom->programacioncom);
        $clasecom->fecha=$request->fecha;
        $clasecom->docente_id=$request->docente_id;
        $clasecom->horainicio=$request->horainicio;
        $clasecom->horafin=$request->horafin;
        $clasecom->aula_id=$request->aula_id;
        $clasecom->save();
        return redirect()->route('clase.presentes');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Clasecom  $clasecom
     * @return \Illuminate\Http\Response
     */
    
    public function marcadoGeneral(Matriculacion $matriculacion){
        
        // $programaciones=Programacioncom::where('matriculacion_id', '=', $matriculacion_id)->get();
        $programaciones=$matriculacion->programacionescom;

        $programacionesHoy = Programacioncom::join('matriculacions', 'matriculacions.id', '=', 'programacioncoms.matriculacion_id') //ok
        ->join('sesioncoms', 'sesioncoms.matriculacion_id', '=', 'matriculacions.id')
        ->join('dias', 'dias.id', '=', 'sesioncoms.dia_id')
        ->join('aulas', 'sesioncoms.aula_id', '=', 'aulas.id')
        ->join('docentes', 'sesioncoms.docente_id', '=', 'docentes.id')
        ->join('personas', 'docentes.persona_id', '=', 'personas.id')
        ->join('estados', 'estados.id', '=', 'programacioncoms.estado_id')

        // ->where('matriculacions.id', '=', $matriculacion_id)
        ->where('matriculacions.id', '=', $matriculacion->id)
        ->where('programacioncoms.fecha', '=',DB::raw('date(now())'))
            ->where('dias.id', '=', DB::raw("DAYOFWEEK(programacioncoms.fecha)-1"))
            
        ->select('programacioncoms.id', 'programacioncoms.fecha', 'estados.estado','aulas.aula' ,'programacioncoms.horaini', 'programacioncoms.horafin', 'programacioncoms.habilitado', 'personas.nombre','programacioncoms.docente_id')
        ->get();

        // $matriculacion=Matriculacion::findOrFail($matriculacion_id);
        $dias_que_faltan_para_pagar= $matriculacion->fecha_proximo_pago ? $matriculacion->fecha_proximo_pago->diffInDays(now()) : 0;
        
        $pago=$matriculacion->pagos->sum('monto');
        $indefinido=$matriculacion->programacionescom->where('estado_id',estado("INDEFINIDO"))->count();
        
        $presentes = $this->CantidadDePresentesCom($matriculacion);
        $faltas = $this->CantidadDeFaltasCom($matriculacion);
        $congelados = $this->CantidadDeCongeladosCom($matriculacion);
        $licencias = $this->CantidadDeLicenciasCom($matriculacion);

        $persona = $matriculacion->computacion->persona;

        $attendanceTab=app(\App\Services\ClassAttendanceView::class)->tab('computacion',$matriculacion);
        $presentes=$attendanceTab['stats']['Asistidas']; $faltas=$attendanceTab['stats']['Faltas']; $licencias=$attendanceTab['stats']['Licencias'];
        return view('programacioncom.marcadoGeneral',compact('attendanceTab','programaciones', 'faltas', 'presentes', 'licencias', 'pago','persona', 'matriculacion', 'dias_que_faltan_para_pagar'));
        //return redirect()->route('clases.marcado.general',$inscripcion_id)->with('programaciones', 'programacionesHoy', 'faltas', 'presentes', 'licencias', 'pago', 'inscripcion','dias_que_faltan_para_pagar');
    }

    public function CantidadDePresentesCom($matriculacion){
        return $matriculacion->programacionescom->where('estado_id',estado("FINALIZADO"))->count();
    }
    public function CantidadDeFaltasCom($matriculacion){
        return $matriculacion->programacionescom->where('estado_id',estado("FALTA"))->count();
    }

    public function CantidadDeCongeladosCom($matriculacion){
        return $matriculacion->programacionescom->where('estado_id',estado("CONGELADO"))->count();
    }
    public function CantidadDeLicenciasCom($matriculacion){
        return $matriculacion->programacionescom->where('estado_id',estado("LICENCIA"))->count();
    }



    public function marcadoRapido($programacion_id)
    {
        $programa=Programacioncom::findOrFail($programacion_id);
        $data=['fecha'=>$programa->fecha->format('Y-m-d'), 'horainicio'=>now()->format('H:i'),
            'horafin'=>now()->addMinutes($programa->horaini->diffInMinutes($programa->horafin))->format('H:i'),
            'docente_id'=>$programa->docente_id,'aula_id'=>$programa->aula_id];
        app(\App\Services\ClassAttendanceService::class)->record($programa,$data,auth()->user());
        return redirect()->route('clase.presentes');
    }
     public function clasesPresentes(Request $request){
        
            $clasecoms =  Clasecom::with('programacioncom.adicional.originalProgramacion','programacioncom.adicional.originalProgramacioncom')->join('programacioncoms', 'clasecoms.programacioncom_id', '=', 'programacioncoms.id')
                ->join('matriculacions', 'programacioncoms.matriculacion_id', '=', 'matriculacions.id')
                ->join('computacions', 'matriculacions.computacion_id', '=', 'computacions.id')
                ->join('personas', 'computacions.persona_id', '=', 'personas.id')
                ->join('docentes', 'clasecoms.docente_id', '=', 'docentes.id')
                ->join('asignaturas', 'matriculacions.asignatura_id', '=', 'asignaturas.id')
                ->join('aulas', 'clasecoms.aula_id', '=', 'aulas.id')
                ->join('userables','userables.userable_id','clasecoms.id')
                ->join('users','userables.user_id','users.id')
                ->where('userables.userable_type',Clasecom::class)
                ->where('clasecoms.estado_id',estado('PRESENTE'))
                ->whereDate('clasecoms.fecha',now()->toDateString())
                ->select('clasecoms.programacioncom_id','clasecoms.id','personas.id as codigo', 'personas.nombre as name','clasecoms.horainicio', 'clasecoms.horafin', 'docentes.nombrecorto as nombre', 'asignaturas.asignatura', 'aulas.aula','personas.foto','users.name as user')->get();
            return datatables()->of($clasecoms)
                ->addColumn('btn',fn($attendance)=>view('clasecom.action_marcar',array_merge($attendance->toArray(),['program'=>$attendance->programacioncom]))->render())
                ->rawColumns(['btn', 'foto'])
                ->toJson();
        
    }
    public function finalizarClasecom(Request $request)
    {
        $request->validate(['id'=>'required|integer']);
        app(\App\Services\ClassAttendanceService::class)->finish(Clasecom::findOrFail($request->id),$request->user());
        return response()->json(['message'=>'Salida registrada.','status'=>200]);
    }
    
}

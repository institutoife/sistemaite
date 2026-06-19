<?php

namespace App\Http\Controllers;

use App\Models\Programacioncom;
use App\Models\Matriculacion;
use App\Models\Inscripcione;
use App\Models\Estado;
use App\Models\Persona;
use App\Models\Docente;
use App\Models\Dia;
use App\Models\Aula;
use App\Models\Clasecom;
use App\Models\Sesioncom;
use App\Models\User;
use App\Models\Computacion;
use App\Models\Nivel;
use App\Models\Observacion;
use App\Models\Licencia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Contracts\DataTable as DataTable; 
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Config;

use Illuminate\Support\Facades\Auth;
use PDF;
use Illuminate\Http\Request;

class ProgramacioncomController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Listar Programacioncom')->only('marcadoNormal',"imprimirProgramacom","mostrarProgramacom");
        $this->middleware('can:Editar Programacioncom')->only("editar","agregarClase","actualizar","generarProgramacom","EliminarTodosLosProgramascom","regenerarProgramacom");
        $this->middleware('can:Eliminar Programacioncom')->only("destroy");
    }

    public function editar(Request $request)
    {

        
       

        //$programacioncom = Programacioncom::findOrFail(3);
        $programacioncom = Programacioncom::findOrFail($request->id);
        $nivel = Nivel::findOrFail(6);
        // $docentes = $nivel->docentes;
        $docentes=Docente::all();
        $aulas= Aula::all();
        $estados=Estado::all();

        $data=['programacioncom'=>$programacioncom,'docentes'=>$docentes,'aulas'=>$aulas,'estados'=>$estados];
        return response()->json($data);
    }

    public function actualizar(Request $request)
    {
            //$request->estado_id=1;
            // return response()->json($request->all());
            $programacioncom=Programacioncom::findOrFail($request->programacioncom_id);
            $arrayObservable= [
            'observacion' => 'Se editó los valores anteriores son' .
            'Hora Inicio: ' . $programacioncom->horaini . ' ' .
            'Hora Fin: ' . $programacioncom->horafin . ' ' .
            'Fecha : ' . $programacioncom->fecha . ' ' .
                'Estado : ' . $programacioncom->estado_id . ' ' .
                'activo : ' . $programacioncom->activo . ' ' .
                'horas por clase: ' . $programacioncom->horas_por_clase . ' ' .
                'Docente: ' . $programacioncom->docente->nombrecorto . ' ' .
                'Aula: ' . $programacioncom->aula->aula,
                'activo'=> $programacioncom->activo,
                'observable_id'=> $programacioncom->id,
                'observable_tipe'=> Programacioncom::class,
            ];
            $programacioncom->observaciones()->create($arrayObservable);
        
        
        $horainicio=Carbon::create($request->horaini);
        $horafin=Carbon::create($request->horafin);
        $programacioncom->fecha            =$request->fecha;
        $programacioncom->activo           =$request->activo;
        $programacioncom->estado_id       =$request->estado_id;
        $programacioncom->horafin         =$request->hora_fin;
        $programacioncom->horaini         =$request->hora_ini;
        $programacioncom->horas_por_clase  = $horainicio->floatDiffInHours($horafin);
        $programacioncom->docente_id       =$request->docente_id;
        $programacioncom->aula_id          =$request->aula_id;
        $programacioncom->matriculacion_id  =$request->matriculacion_id;
        $programacioncom->save();
        
        $docente=$programacioncom->docente;
        
        $data=["programacioncom"=>$programacioncom,"docente"=>$docente];
        
        return response()->json($data);

    }

    public function destroy(Programacioncom $programacioncom)
    {
        $programacioncom->delete();
        return response()->json(['mensaje'=>"El registro se eliminó correctamente"]);
    }


    public function generarProgramacom($matriculacion_id){
        $matriculacion=Matriculacion::findOrFail($matriculacion_id);
        $costo_total=$matriculacion->costo;
        $total_horas=$matriculacion->totalhoras;
        $acuenta =$matriculacion->pagos->sum('monto');
        $fecha=$matriculacion->fechaini;
        foreach ($matriculacion->sesionescoms as $dia)
            $vector_dias[] = Dia::findOrFail($dia->dia_id)->dia;
        while ((!in_array($fecha->isoFormat('dddd'), $vector_dias))) 
            $fecha->addDay();
        $sesiones=Sesioncom::where('matriculacion_id','=',$matriculacion_id)->get();
            while($total_horas>0){
                foreach ($sesiones as $sesion) {
                    if ($total_horas > 0) {
                        $programa = new Programacioncom();
                        $hora_x_sesion= $sesion->horainicio->floatDiffInHours($sesion->horafin);
                        $costo_x_sesion = ($costo_total / $matriculacion->totalhoras)*$hora_x_sesion ;
                        $costo_hora= $costo_total / $total_horas;
                        if($total_horas>=$hora_x_sesion){
                            if ($acuenta >= $costo_x_sesion) {
                                $this->agregarClase($programa,$fecha,$hora_x_sesion,$total_horas,$sesion,true,$matriculacion);
                            } else {
                                $this->agregarClase($programa, $fecha, $hora_x_sesion,$total_horas, $sesion, false, $matriculacion);
                            }
                        }else{
                            if ($acuenta >= $costo_hora*$total_horas) {
                                $this->agregarClase($programa, $fecha, $hora_x_sesion,$total_horas, $sesion, true, $matriculacion);
                            } else {
                                $this->agregarClase($programa, $fecha, $hora_x_sesion,$total_horas, $sesion, false, $matriculacion);
                            }
                        }
                        $programa->save();
                        $acuenta = $acuenta - $costo_x_sesion;
                        $total_horas = $total_horas - $hora_x_sesion;
                        $siguiente_sesion= $this->siguienteSesion($matriculacion, $sesion);
                        if(($siguiente_sesion->dia_id!=$sesion->dia_id)||($siguiente_sesion->id== Sesioncom::where('matriculacion_id', $matriculacion->id)->get()->first()->id)){
                            $fecha->addDay();
                            while ((!in_array($fecha->isoFormat('dddd'), $vector_dias))||($this->esFeriado($fecha))) {
                                $fecha->addDay();
                            }
                        }
                    }
                }
            }
            $matriculacion->fechafin = $programa->fecha;
            $matriculacion->estado_id=estado('CORRIENDO');
            //dd($matriculacion);
            $matriculacion->save();
            if ($matriculacion->pagos->sum('monto') < $matriculacion->costo) {
                return redirect()->route('mostrar.programacioncom', $matriculacion);
            } else {
                $matriculacion->fecha_proximo_pago = $fecha;
                $matriculacion->save();
                return redirect()->route('mostrar.programacioncom', $matriculacion);
                // return redirect()->route('imprimir.programacioncom', $matriculacion->id);;
            }   
    }

    public function EliminarTodosLosProgramascom($matriculacion_id){
        try {
            Programacioncom::where('matriculacion_id', '=', $matriculacion_id)->delete();
        } catch (\Illuminate\Database\QueryException $e) {
           abort(404);
        }        
    }

    public function regenerarProgramacom($matriculacion_id,$unaFecha,$unModo = 'desde'){
        if($unModo=='todo'){
            $this->EliminarTodosLosProgramascom($matriculacion_id);
            $this->generarProgramacom($matriculacion_id);
        }
        $unaFecha= Carbon::createFromFormat('Y-m-d', $unaFecha);
        $matriculacion= Matriculacion::findOrFail($matriculacion_id);
        $FechaDesde=$unaFecha->format('Y-m-d');
        $horasFaltantes = Programacioncom::where('matriculacion_id', '=', $matriculacion_id)->where('fecha', '>=', $FechaDesde)->sum('horas_por_clase');
        $total_horas=$horasFaltantes;                
        $horasPasadas = $matriculacion->totalhoras-$horasFaltantes;
        Programacioncom::where('matriculacion_id', '=', $matriculacion_id)->where('fecha', '>=', $FechaDesde)->delete();
        $costo_hora=$matriculacion->costo/$matriculacion->totalhoras;
        $acuentaTotal = $matriculacion->pagos->sum('monto');
        $CostoTotal = $matriculacion->costo;
        $costo_horas_pasadas=$horasPasadas*$costo_hora;
        $costo_restante=$CostoTotal-$costo_horas_pasadas;
        $Acuenta_para_regenerar=$acuentaTotal-$costo_horas_pasadas;
        $fecha = $unaFecha;
        foreach ($matriculacion->sesionescoms as $dia)
            $vector_dias[] = Dia::findOrFail($dia->dia_id)->dia;
        while ((!in_array($fecha->isoFormat('dddd'), $vector_dias)))
            $fecha->addDay();
        $sesiones = Sesioncom::where('matriculacion_id', '=', $matriculacion_id)->get();
        while ($horasFaltantes > 0) {
            foreach ($sesiones as $sesion) {
                if ($horasFaltantes > 0) {
                    $programa = new Programacioncom();
                    $hora_x_sesion = $sesion->horainicio->floatDiffInHours($sesion->horafin);
                    $costo_x_sesion = ($costo_restante / $total_horas) * $hora_x_sesion;
                    if ($horasFaltantes > $hora_x_sesion) {
                        if ($Acuenta_para_regenerar > $costo_x_sesion) {
                            $this->agregarClase($programa, $fecha, $hora_x_sesion, $horasFaltantes, $sesion, true, $matriculacion);
                        } else {
                            $this->agregarClase($programa, $fecha, $hora_x_sesion, $horasFaltantes, $sesion, false, $matriculacion);
                        }
                    } else {
                        if ($Acuenta_para_regenerar >= $costo_hora * $horasFaltantes) {
                            $this->agregarClase($programa, $fecha, $hora_x_sesion, $horasFaltantes, $sesion, true, $matriculacion);
                        } else {
                            $this->agregarClase($programa, $fecha, $hora_x_sesion, $horasFaltantes, $sesion, false, $matriculacion);
                        }
                    }
                    $programa->save();
                    $Acuenta_para_regenerar = $Acuenta_para_regenerar - $costo_x_sesion;
                    $horasFaltantes = $horasFaltantes - $hora_x_sesion;
                    $siguiente_sesion = $this->siguienteSesion($matriculacion, $sesion);
                    if (($siguiente_sesion->dia_id != $sesion->dia_id) || ($siguiente_sesion->id == Sesioncom::where('matriculacion_id', $matriculacion->id)->get()->first()->id)) {
                        $fecha->addDay();
                        while ((!in_array($fecha->isoFormat('dddd'), $vector_dias))||($this->esFeriado($fecha))) {
                            $fecha->addDay();
                        }
                    }
                } 
            }
        }
        $matriculacion->fechafin = $programa->fecha;
        $matriculacion->estado_id = estado('CORRIENDO');
        $matriculacion->save();
        if ($Acuenta_para_regenerar < $matriculacion->costo) {
            return redirect()->route('mostrar.programacioncom', $matriculacion);
        } else {
            $matriculacion->fecha_proximo_pago = $fecha;
            $matriculacion->save();
            //return redirect()->route('imprimir.programacioncom', $matriculacion->id);/** llamar al metodo que muestra pdf*/
            return redirect()->route('mostrar.programacioncom', $matriculacion);
        }    
    }
    public function agregarClase(&$programacom, &$fecha, $hora_x_sesion, &$total_horas , &$sesioncom , $habilitado, &$matriculacion){
        if ($total_horas > $hora_x_sesion) {
            $programacom->horafin = $sesioncom->horafin;
            $programacom->horas_por_clase = $hora_x_sesion;
        }else{
            $programacom->horafin = $sesioncom->horainicio->addMinutes($total_horas * 60);
            $programacom->horas_por_clase = $total_horas;
        }
        $programacom->fecha = $fecha;
        $programacom->habilitado = $habilitado;
        $programacom->activo = true;
        $programacom->estado_id =estado('INDEFINIDO');
        $programacom->horaini = $sesioncom->horainicio;
        
        $programacom->docente_id = $sesioncom->docente_id;
        $programacom->aula_id = $sesioncom->aula_id;
        $programacom->matriculacion_id = $matriculacion->id;
    }


    /**
 * Funciones de apoyo para generar clases
 */
    public function esFeriado($unaFecha){
        //$frecuencia=count(Feriado::where('fecha','=',$unaFecha)->get());
        $cantidad = count(DB::table('feriados')->whereIn('fecha', [$unaFecha])->get());
        return $cantidad>0;
    }

    /**
     * esta funcion hace rotar el turno de las sesiones tipo ronda 
     */
    public function siguienteSesion($unaMatriculacion,$unaSesioncom){
        $sesiones_de_esta_matriculacion=Sesioncom::where('matriculacion_id',$unaMatriculacion->id)->get();
        // la sesion actual el es primero consicedarar si tiene una sola sesion
        
        if($unaSesioncom->id==$sesiones_de_esta_matriculacion->last()->id){
            //$respuesta = 
            $respuesta= Sesioncom::where('matriculacion_id', $unaMatriculacion->id)->get()->first();
        }else{
            
            $respuesta = Sesioncom::where('matriculacion_id', '=', $unaSesioncom->matriculacion_id)->where('id', '>', $unaSesioncom->id)
                        ->orderBy('id', 'asc')->first();
        }
        return $respuesta;
    }

    public function mostrarProgramacom(Matriculacion $matriculacion){
        $programacioncom = Programacioncom::join('aulas', 'programacioncoms.aula_id', '=', 'aulas.id')
        ->join('docentes', 'programacioncoms.docente_id', '=', 'docentes.id')
        ->join('personas', 'personas.id', '=', 'docentes.persona_id')
        ->select('programacioncoms.id','programacioncoms.fecha', 'horaini', 'horafin','horas_por_clase', 'personas.nombre', 'aulas.aula', 'programacioncoms.habilitado', 'programacioncoms.matriculacion_id')
        ->orderBy('fecha', 'asc')
        ->where('matriculacion_id', '=', $matriculacion->id)->get();
        $persona=$matriculacion->computacion->persona;
        $estudiante=$persona->estudiante;
        return view('programacioncom.show', compact('programacioncom','matriculacion','persona','estudiante'));
    }


    public function imprimirProgramacom($matriculacion_id){
        $matriculacion=Matriculacion::findOrFail($matriculacion_id);
        $programacion = Programacioncom::join('aulas', 'programacioncoms.aula_id', '=', 'aulas.id')
            ->join('docentes', 'programacioncoms.docente_id', '=', 'docentes.id')
            ->join('personas', 'personas.id', '=', 'docentes.persona_id')
            ->select('programacioncoms.fecha', 'horaini','programacioncoms.habilitado', 'horafin', 'horas_por_clase', 'personas.nombre', 'aulas.aula', 'programacioncoms.habilitado')
            ->orderBy('fecha', 'asc')
            ->where('matriculacion_id', '=', $matriculacion_id)->get();


        $computacion = Computacion::findOrFail($matriculacion->computacion_id);
        $persona = $computacion->persona;
        //dd($matriculacion);
        $usuario=$matriculacion->usuarios->first();
        $asignatura=$matriculacion->asignatura;
        $carrera=$asignatura->carrera;

        if (! is_dir(storage_path('fonts'))) {
            @mkdir(storage_path('fonts'), 0775, true);
        }

        $dompdf = PDF::loadView('programacioncom.reporte', compact('carrera','usuario','programacion','persona','computacion','persona','matriculacion','asignatura'));
        /**entrae a la persona al cual corresponde esta inscripcion */
        $fecha_actual = Carbon::now();
        $fecha_actual->isoFormat('DD-MM-YYYY-HH:mm:ss');
        $dompdf->setPaper('letter','portrait');
        return $dompdf->download($persona->id . '_' . $fecha_actual . '_' . $persona->nombre . '_' . $persona->apellidop . '.pdf');
    }
    
    public function marcadoNormal($programacioncom_id){
        $programacioncom=Programacioncom::findOrFail($programacioncom_id);
        $matriculacion=Matriculacion::findOrFail($programacioncom->matriculacion->id);
        $docentes=Docente::join('personas','personas.id','=','docentes.persona_id')
                        //join('estados','estados.id','=','docentes.estado_id')
                        ->where('docentes.estado_id','=',estado('HABILITADO'))
                        ->select('docentes.id','personas.nombre','personas.apellidop')
                        ->get(); 
        $aulas=Aula::all();
        $hora_inicio=Carbon::now()->isoFormat('HH:mm:ss');
        $hora_fin = Carbon::now()->addMinutes(($programacioncom->horaini->floatDiffInMinutes($programacioncom->horafin)))->isoFormat('HH:mm:ss');
        return view('clasecom.create',compact('docentes','programacioncom','matriculacion','aulas','hora_inicio','hora_fin'));
    }

    public function marcadoNormalModal($programacioncom_id){
        $programacioncom = Programacioncom::findOrFail($programacioncom_id);
        $matriculacion = Matriculacion::findOrFail($programacioncom->matriculacion->id);
        $personaId = $matriculacion->computacion->persona_id;
        $docentes = Docente::join('personas', 'personas.id', '=', 'docentes.persona_id')
            ->join('estados', 'estados.id', '=', 'docentes.estado_id')
            ->where('docentes.estado_id', '=', estado('HABILITADO'))
            ->select('docentes.id', 'personas.nombre', 'personas.apellidop')
            ->orderBy('personas.nombre', 'asc')
            ->get();
        $aulas = Aula::all();
        $hora_inicio = Carbon::now()->format('H:i');
        $hora_fin = Carbon::now()->addMinutes($programacioncom->horaini->floatDiffInMinutes($programacioncom->horafin))
            ->format('H:i');

        $clasecomPresente = Clasecom::where('programacioncom_id', $programacioncom->id)
            ->where('estado_id', estado('PRESENTE'))
            ->where('fecha', Carbon::now()->toDateString())
            ->first();

        $totalPagado = $matriculacion->pagos->sum('monto');
        $saldo = max($matriculacion->costo - $totalPagado, 0);
        $ultimaProgramacionPagada = Programacioncom::where('matriculacion_id', $matriculacion->id)
            ->where('habilitado', 1)
            ->orderBy('fecha', 'desc')
            ->first();
        $programaciones = Programacioncom::with(['docente','aula','estado'])
            ->where('matriculacion_id', $matriculacion->id)
            ->orderBy('fecha', 'asc')
            ->get();

        $inscripcionesActivas = Inscripcione::where('estudiante_id', $matriculacion->computacion->persona->estudiante->id ?? null)
            ->where('estado_id', estado('CORRIENDO'))
            ->where('vigente', 1)
            ->count();
        $matriculacionesActivas = Matriculacion::join('computacions', 'computacions.id', '=', 'matriculacions.computacion_id')
            ->where('computacions.persona_id', $personaId)
            ->where('matriculacions.estado_id', estado('CORRIENDO'))
            ->where('matriculacions.vigente', 1)
            ->count();

        return view('clasecom.modalmarcado', compact(
            'docentes',
            'programacioncom',
            'matriculacion',
            'aulas',
            'hora_inicio',
            'hora_fin',
            'clasecomPresente',
            'inscripcionesActivas',
            'matriculacionesActivas',
            'totalPagado',
            'saldo',
            'ultimaProgramacionPagada',
            'programaciones'
        ));
    }

    public function hoycom(){
        $programas= Programacioncom::join('matriculacions','matriculacions.id','programacioncoms.matriculacion_id')
            ->join('computacions','computacions.id','matriculacions.computacion_id')
            ->join('personas','personas.id','computacions.persona_id')
            ->join('docentes','programacioncoms.docente_id','docentes.id')
            ->join('asignaturas','matriculacions.asignatura_id','asignaturas.id')
            ->join('estados','programacioncoms.estado_id','estados.id')

            ->where('programacioncoms.fecha',Carbon::now()->format('Y-m-d'))
            ->select('matriculacions.id','personas.nombre as estudiante','estados.estado','personas.apellidop','foto','docentes.nombrecorto as docente','programacioncoms.horaini','programacioncoms.horafin','asignaturas.asignatura')  
            ->get();

        return DataTables::of($programas)
                ->addColumn('btn','programacioncom.actionshoy')
                ->rawColumns(['btn'])
                ->toJson();
    }

    public function programacionescomHoy(Request $request){

        $programacion=Programacioncom::join('docentes','docentes.id','=','programacioncoms.docente_id')
                    ->join('aulas','aulas.id','=','programacioncoms.aula_id')
                    ->join('estados','estados.id','=','programacioncoms.estado_id')
                    ->where('matriculacion_id',$request->matriculacion)
                    ->where('fecha','=', Carbon::now()->isoFormat('Y-M-D'))
                    ->select('programacioncoms.id','fecha','horaini','horafin','estados.estado','docentes.nombrecorto','aulas.aula');
        
        return DataTables::of($programacion)
                ->addColumn('btn','programacioncom.actions')
                ->rawColumns(['btn'])
                ->toJson();
    }
    public function programacionescomFuturo(Request $request){
        $programacion=Programacioncom::join('docentes','docentes.id','=','programacioncoms.docente_id')
                    ->join('aulas','aulas.id','=','programacioncoms.aula_id')
                    ->join('estados','estados.id','=','programacioncoms.estado_id')

                    ->where('matriculacion_id',$request->matriculacion)
                    // ->where('fecha','>', Carbon::now()->isoFormat('Y-M-D'))
                    ->select('programacioncoms.id','fecha','estados.estado','docentes.nombrecorto as docente','horaini','horafin','aulas.aula','programacioncoms.habilitado');
        return DataTables::of($programacion)
                ->addColumn('btn','programacioncom.actionsfuturo')
                ->rawColumns(['btn'])
                ->toJson();
    }

    public function deshabilitarTodoProgramascom($matriculacion_id){
        $programascom=Programacioncom::where('matriculacion_id','=', $matriculacion_id)->get();
        foreach ($programascom as $programa) {
            $programa->habilitado=0;
            $programa->save();
        }
    }


    public function actualizarProgramaSegunPagocom($matriculacion_id){
        
        $matriculacion = Matriculacion::findOrFail($matriculacion_id);
        $total_costo=$matriculacion->costo;
        $total_horas=$matriculacion->totalhoras;
        $costo_por_hora=$total_costo/$total_horas;
        $this->deshabilitarTodoProgramascom($matriculacion_id);
        $programascom = Programacioncom::where('matriculacion_id', '=', $matriculacion_id)
                                        ->get();
        $acuentaTotal=$matriculacion->pagos->sum->monto;
        $TotalPagado=$acuentaTotal;
        

        $cuantas=[];
        foreach ($programascom as $programa) {
            $costo_programa = $programa->horaini->floatDiffInHours($programa->horafin)*($costo_por_hora);
            if($TotalPagado>=$costo_programa){
                $programa->habilitado=1;
                $programa->save();
                $TotalPagado=$TotalPagado-$costo_programa;
            }
        }
        $programascom = Programacioncom::where('matriculacion_id', '=', $matriculacion_id)
                                        ->get();

        if ($TotalPagado <= $matriculacion->costo) {
            return redirect()->route('mostrar.programacioncom', $matriculacion);
        } else {
            $matriculacion->fecha_proximo_pago = $matriculacion->programaciones->last()->fecha->isoFormat('Y-M-D');
            $matriculacion->save();
            return redirect()->route('imprimir.programa', $matriculacion->id);
            
        }    
    }
    public function deshabilitarTodoProgramas($inscripcione_id){
        $programas=Programacion::where('inscripcione_id', $inscripcione_id)->get();
        foreach ($programas as $programa) {
            $programa->habilitado=0;
            $programa->save();
        }
    }

    public function guardarObservacion(Request $request){
        $observacion=new Observacion();
        $observacion->observacion=$request->observacion;
        $observacion->activo=1;
        $observacion->observable_id=$request->id_programacioncom;
        $observacion->observable_type= Programacioncom::class;
        $observacion->save();

        $observacion->usuarios()->attach(Auth::user()->id);
        
        return response()->json($request->all());
    }

    public function mostrarClases(Request $request)
    {
        $programacioncom = Programacioncom::findOrFail($request->id);
        $observaciones=Observacion::join('programacioncoms','programacioncoms.id','observacions.observable_id')
						->join('userables','userables.userable_id','observacions.id')
                        ->join('users','userables.user_id','users.id')
                        ->where('userables.userable_type',Observacion::class)
                        ->where('observacions.observable_type',Programacioncom::class)
                        ->where('programacioncoms.id',$request->id)
                        ->select('observacions.id','observacion','name')
                        ->get();
        $docente = $programacioncom->docente;
        $materia = $programacioncom->materia;
        $asignatura=$programacioncom->matriculacion->asignatura;
        $aula = $programacioncom->aula;
        $licencias=Licencia::join('motivos','motivos.id','=','licencias.motivo_id')
            ->join('programacioncoms','programacioncoms.id','=','licencias.licenciable_id')
            ->join('userables','userables.userable_id','licencias.id')
            ->join('users','userables.user_id','users.id')
            ->where('userables.userable_type',Licencia::class)
            ->where('licencias.licenciable_id',$request->id)
            ->where('licencias.licenciable_type',Programacioncom::class)
            ->where('programacioncoms.id','=',$request->id)
            ->select('licencias.id','motivos.motivo','solicitante','parentesco','users.name as user','licencias.created_at','licencias.updated_at')
            ->get();
        $clases=Programacioncom::join('clasecoms','programacioncoms.id','clasecoms.programacioncom_id')
                    ->join('docentes','docentes.id','programacioncoms.docente_id')
                    ->join('aulas','aulas.id','programacioncoms.aula_id')
                    ->join('estados','estados.id','clasecoms.estado_id')
                    ->join('userables','userables.userable_id','clasecoms.id')
                    ->join('users','userables.user_id','users.id')
                    ->where('userables.userable_type',Clasecom::class)
                    ->where('programacioncoms.id',$request->id)
                    ->select('clasecoms.id','clasecoms.fecha','clasecoms.horainicio','users.name as user','estados.estado','clasecoms.horafin','docentes.nombrecorto as nombre', 'aulas.aula')
                    ->get();
        $estado=$programacioncom->estado;
        $data = ['programacioncom' => $programacioncom, 'observaciones' => $observaciones, 'docente' => $docente,'estado' => $estado, 'aula' => $aula,'licencias'=>$licencias, 'clasescom' => $clases,'asignatura'=>$asignatura];
        return response()->json($data);
    }

    public function asisntecia(Request $request){
        $estados=Matriculacion::join('programacioncoms','programacioncoms.matriculacion_id','matriculacions.id')
        ->join('computacions','computacions.id','matriculacions.computacion_id')
        ->join('personas','personas.id','computacions.persona_id')
        ->join('estados','estados.id','programacioncoms.estado_id')
        ->where('matriculacions.vigente',1)
        ->where('matriculacions.id',$request->matriculacion_id)
        ->select('personas.id','programacioncoms.id as programacioncom_id','personas.nombre','personas.apellidop','estados.estado')
        ->orderBy('programacioncoms.id','asc')
        ->get();
        return response()->json($estados);
    }
    public function asignarFaltasFechasPasadas(){
        Programacioncom::where('fecha','<',Carbon::now())
                    ->where('estado_id',estado('INDEFINIDO'))->update(['estado_id'=>estado('FALTA')]);
        return response()->json(['id'=>"Todo Bien"]);
    }
    public function programacioncomMostrarAjax(Request $request){
            $programacion = Programacioncom::join('matriculacions', 'programacioncoms.matriculacion_id', '=', 'matriculacions.id')
            ->join('aulas', 'programacioncoms.aula_id', '=', 'aulas.id')
            ->join('asignaturas', 'matriculacions.asignatura_id', '=', 'asignaturas.id')
            ->join('docentes', 'programacioncoms.docente_id', '=', 'docentes.id')
            ->join('personas', 'personas.id', '=', 'docentes.persona_id')
            ->join('estados','estados.id','programacioncoms.estado_id')
            ->select('programacioncoms.fecha', 'horaini','estados.estado','programacioncoms.habilitado', 'horafin', 'horas_por_clase', 'nombrecorto', 'asignaturas.asignatura', 'aulas.aula')
            ->orderBy('fecha', 'asc')
            // ->where('matriculacion_id', '=', 1)->get();
            ->where('matriculacion_id', '=', $request->matriculacion_id)->get();
        return response()->json($programacion);

    }
    // public function estudiantesDeUnProfesorCom($docente_id){
    //     $estudiantes=Computacion::join('matriculacions','matriculacions.computacion_id','computacions.id')
    //         ->join('programacions','programacions.inscripcione_id','inscripciones.id')
    //         ->join('clasecoms','clases.programacion_id','programacions.id')
    //         ->join('docentes','docentes.id','clases.docente_id')
    //         ->join('personas','personas.id','estudiantes.persona_id')
    //         ->join('temas','temas.id','clases.tema_id')
    //         ->join('materias','materias.id','clases.materia_id')
    //         ->where('docentes.id',$docente_id)
    //         ->where('programacions.fecha','=',Carbon::now()->isoFormat('Y-M-D'))
    //         ->where('clases.estado_id',estado('PRESENTE'))
    //         ->select('personas.id','personas.nombre','personas.apellidop','personas.apellidom','temas.tema','materias.materia')
    //         ->get();
    //     return $estudiantes;
    // }
}

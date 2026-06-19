<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Config;

use App\Models\Programacion;
use App\Models\Pago;

use App\Models\Docente;
use App\Models\Sesion;
use App\Models\Aula;
use App\Models\Dia;
use App\Models\Inscripcione;
use Carbon\Carbon;
// use Barryvdh\DomPDF\Facade as PDF;
use App\Models\Estudiante;
use App\Models\Feriado;
use App\Models\Materia;
use App\Models\Modalidad;
use App\Models\Licencia;
use App\Models\Nivel;
use App\Models\Tema;
use App\Models\Observacion;
use App\Models\Estado;
use App\Models\Persona;
use App\Models\Colegio;
use App\Models\User;
use App\Models\Grado;
use App\Models\Clase;
use App\Models\Programacioncom;
use App\Models\Clasecom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Contracts\DataTable as DataTable; 
use Yajra\DataTables\DataTables;
use App\Http\Controllers\PDFController;
use Illuminate\Support\Facades\Auth;
use PDF;

class ProgramacionController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Listar Programaciones')->only("mostrar","mostrarClases","programacionesHoy","mostrarPrograma","imprimirPrograma","controlAsitencia");
        $this->middleware('can:Crear Programaciones')->only("marcadoNormal","generarPrograma","generarProgramaGuarderia");
        $this->middleware('can:Editar Programaciones')->only("edit","editar","update","actualizar","regenerarPrograma","actualizarProgramaSegunPago");
        $this->middleware('can:Eliminar Programaciones')->only("destroy");
    }
    
    public function mostrar(Request $request)
    {       
        $programacion=Programacion::findOrFail($request->id);
        $observaciones=$programacion->observaciones;
        $docente=$programacion->docente;
        $materia=$programacion->materia;
        $aula=$programacion->aula;
        $clases=$programacion->clases;
        $licencias=Licencia::join('motivos','motivos.id','=','licencias.motivo_id')
            ->join('programacions','programacions.id','=','licencias.licenciable_id')
            ->where('programacions.id','=',$request->id)
            ->select('motivos.motivo','solicitante','parentesco','licencias.created_at','licencias.updated_at')
            ->get();
        $estado=Estado::findOrFail($programacion->estado_id);
        //return response()->json($programacion);    
        $data=['programacion'=>$programacion,'estado'=>$estado , 'observaciones'=>$observaciones,'docente'=>$docente, 'materia' => $materia, 'aula' => $aula,'clases'=>$clases,'licencias'=>$licencias];
        return response()->json($data);
    }
    public function mostrarClases(Request $request)
    {
        $programacion = Programacion::findOrFail($request->id);
        $observaciones=Observacion::join('programacions','programacions.id','observacions.observable_id')
						->join('userables','userables.userable_id','observacions.id')
                        ->join('users','userables.user_id','users.id')
                        ->where('userables.userable_type',Observacion::class)
                        ->where('observacions.observable_type',Programacion::class)
                        ->where('programacions.id',$request->id)
                        ->select('observacions.id','observacion','name')
                        ->get();


        $docente = $programacion->docente;
        $materia = $programacion->materia;
        $aula = $programacion->aula;
        $estado=$programacion->estado;

        $licencias=Licencia::join('motivos','motivos.id','=','licencias.motivo_id')
            ->join('programacions','programacions.id','=','licencias.licenciable_id')

            ->join('userables','userables.userable_id','licencias.id')
            ->join('users','userables.user_id','users.id')
            ->where('userables.userable_type',Licencia::class)
            ->where('licencias.licenciable_id',$request->id)
            ->where('licencias.licenciable_type',Programacion::class)
            ->where('programacions.id','=',$request->id)

            ->select('licencias.id','motivos.motivo','solicitante','parentesco','licencias.created_at','users.name as user','licencias.updated_at')
            ->get();

        $clases=Programacion::join('clases','programacions.id','clases.programacion_id')
                    ->join('docentes','docentes.id','programacions.docente_id')
                    ->join('materias','materias.id','programacions.materia_id')
                    ->join('aulas','aulas.id','programacions.aula_id')
                    ->join('temas','temas.id','clases.tema_id')
                    ->join('estados','estados.id','clases.estado_id')
                    
                    ->join('userables','userables.userable_id','clases.id')
                    ->join('users','userables.user_id','users.id')
                    ->where('userables.userable_type',Clase::class)
                    
                    ->where('programacions.id',$request->id)
                    ->select('clases.id','clases.fecha','clases.horainicio','estados.estado','users.name as user','clases.horafin','docentes.nombrecorto','materias.materia', 'aulas.aula','temas.tema')
                    ->get();
        // $user=User::findOrFail($programacion->inscripcione->userable->user_id);	
        $user=$programacion->inscripcione->usuarios->first();
        
        $data = ['programacion' => $programacion,'user'=> $user, 'estado'=>$estado,'observaciones' => $observaciones, 'docente' => $docente, 'materia' => $materia, 'aula' => $aula, 'clases' => $clases,'licencias'=>$licencias];
        return response()->json($data);
    }

    public function programacionesHoy(Request $request){
        $programacion=Programacion::join('docentes','docentes.id','=','programacions.docente_id')
                    ->join('materias','materias.id','=','programacions.materia_id')
                    ->join('aulas','aulas.id','=','programacions.aula_id')
                    ->join('estados','estados.id','=','programacions.estado_id')
                    ->where('inscripcione_id',$request->inscripcion)
                    ->where('fecha','=', Carbon::now()->isoFormat('Y-M-D'))
                    ->select('programacions.id','fecha','hora_ini','hora_fin','estados.estado','docentes.nombrecorto','materias.materia','aulas.aula');
        return DataTables::of($programacion)
                ->addColumn('btn','programacion.actions')
                ->rawColumns(['btn'])
                ->toJson();
    }

    

    public function edit(Programacion $programacion)
    {
        $docentes=Docente::all();
        $materias=Materia::all();
        $aulas=Aula::all();
        return view('programacion.edit',compact('programacion','docentes','materias','aulas'));
    }

     public function editar(Request $request)
    {
        $programacion=Programacion::findOrFail($request->id);
        $docentes=Docente::all();
        $materias=Materia::all();
        $aulas=Aula::all();
        $estados=Estado::all();
        //return view('programacion.edit',compact('programacion','docentes','materias','aulas'));
        $data=['programacion'=>$programacion,'docentes'=>$docentes,'materias'=>$materias,'aulas'=>$aulas,'estados'=>$estados];
        return response()->json($data);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Programacion  $programacion
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Programacion $programacion)
    {
        //dd($request->activo);
        $arrayObservable= [
            'observacion' => 'Se editó los valores anteriores son' .
                'Hora Inicio= ' . $programacion->hora_ini . ' ' .
                'Hora Fin= ' . $programacion->hora_fin . ' ' .
                'Fecha = ' . $programacion->fecha . ' ' .
                'Estado = ' . $programacion->estado_id . ' ' .
                'activo = ' . $programacion->activo . ' ' .
                'horas por clase= ' . $programacion->horas_por_clase . ' ' .
                'Docente id=' . $programacion->docente_id . ' ' .
                'Materia id=' . $programacion->materia_id . ' ' .
                'Aula id=' . $programacion->aula_id . ' ',
            'activo' => 1,
            'observable_id' => $programacion->id,
            'observable_tipe' => Programacion::class,
        ];
        
        $programacion->observaciones()->create($arrayObservable);
        $hora_inicio=Carbon::create($request->hora_ini);
        $hora_fin=Carbon::create($request->hora_fin);
        $programacion->fecha            =$request->fecha;
        $programacion->activo           =$request->activo;
        $programacion->estado_id           =$request->estado_id;
        $programacion->hora_fin         =$request->hora_fin;
        $programacion->hora_ini         =$request->hora_ini;
        $programacion->horas_por_clase  = $hora_inicio->floatDiffInHours($hora_fin);
        $programacion->docente_id       =$request->docente_id;
        $programacion->materia_id       =$request->materia_id;
        $programacion->aula_id          =$request->aula_id;
        $programacion->inscripcione_id  =$request->inscripcione_id;
        $programacion->save();

        return redirect()->route('clases.marcado.general', $programacion->inscripcione_id);

    }
    public function actualizar(Request $request)
    {
        //return response()->json($request->all());
        $programacion=Programacion::findOrFail($request->programacion_id);
        $arrayObservable= [
            'observacion' => 'Se editó los valores anteriores son' .
                'Hora Inicio: ' . $programacion->hora_ini . ' ' .
                'Hora Fin: ' . $programacion->hora_fin . ' ' .
                'Fecha : ' . $programacion->fecha . ' ' .
                'Estado : ' . $programacion->estado->estado . ' ' .
                'activo : ' . $programacion->activo . ' ' .
                'horas por clase: ' . $programacion->horas_por_clase . ' ' .
                'Docente: ' . $programacion->docente->nombrecorto . ' ' .
                'Materia: ' . $programacion->materia->materia . ' ' .
                'Aula: ' . $programacion->aula->aula,
                'activo'=> 1,
                'observable_id'=> $programacion->id,
                'observable_tipe'=> Programacion::class,
        ];
        $programacion->observaciones()->create($arrayObservable);

        $hora_inicio=Carbon::create($request->hora_ini);
        $hora_fin=Carbon::create($request->hora_fin);
        $programacion->fecha            =$request->fecha;
        $programacion->activo           =$request->activo;
        $programacion->estado_id           =$request->estado_id;
        $programacion->hora_fin         =$request->hora_fin;
        $programacion->hora_ini         =$request->hora_ini;
        $programacion->horas_por_clase  = $hora_inicio->floatDiffInHours($hora_fin);
        $programacion->docente_id       =$request->docente_id;
        $programacion->materia_id       =$request->materia_id;
        $programacion->aula_id          =$request->aula_id;
        $programacion->inscripcione_id  =$request->inscripcione_id;
        $programacion->save();

        $docente=$programacion->docente;
        $materia=$programacion->materia;
        $aula=$programacion->aula;

        $data=["programacion"=>$programacion,"docente"=>$docente,"materia"=>$materia,"aula"=>$aula];
        
        return response()->json($data);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Programacion  $programacion
     * @return \Illuminate\Http\Response
     */
    public function destroy(Programacion $programacion)
    {
        $programacion->delete();
        return response()->json(['mensaje'=>'El registro fue eliminado correctamente']);
    }

    /***********************************************************************************************
     * 
     *           G E N E R A R  P R O G R A M A  
     * 
     ***********************************************************************************************/
    public function generarPrograma($inscripcione_id){
        $inscripcion=Inscripcione::findOrFail($inscripcione_id);
        
        $costo_total=$inscripcion->costo;
        $total_horas=$inscripcion->totalhoras;
        $acuenta =$inscripcion->pagos->sum('monto');
        $fecha=$inscripcion->fechaini;
        foreach ($inscripcion->sesiones as $dia) { 
            $vector_dias[] = Dia::findOrFail($dia->dia_id)->dia;
        }
        while ((!in_array($fecha->isoFormat('dddd'), $vector_dias))) {
            $fecha->addDay();
        }
        $sesiones=Sesion::where('inscripcione_id','=',$inscripcione_id)->get();
            while($total_horas>0){
                foreach ($sesiones as $sesion) {
                    if ($total_horas > 0) {
                        $programa = new Programacion();
                        $hora_x_sesion= $sesion->horainicio->floatDiffInHours($sesion->horafin);
                        $costo_x_sesion = ($costo_total / $inscripcion->totalhoras) *$hora_x_sesion ;
                        $costo_hora= $costo_total / $total_horas;
                        
                        if($total_horas>=$hora_x_sesion){
                            if ($acuenta >= $costo_x_sesion) {
                                $this->agregarClase($programa,$fecha,$hora_x_sesion,$total_horas,$sesion,true,$inscripcion);
                            } else {
                                $this->agregarClase($programa, $fecha, $hora_x_sesion,$total_horas, $sesion, false, $inscripcion);
                            }
                        }else{
                            if ($acuenta >= $costo_hora*$total_horas) {
                                $this->agregarClase($programa, $fecha, $hora_x_sesion,$total_horas, $sesion, true, $inscripcion);
                                
                            } else {
                                $this->agregarClase($programa, $fecha, $hora_x_sesion,$total_horas, $sesion, false, $inscripcion);
                            }
                        }
                        $programa->save();
                        $acuenta = $acuenta - $costo_x_sesion;
                        
                        $total_horas = $total_horas - $hora_x_sesion;
                        $siguiente_sesion= $this->siguienteSesion($inscripcion, $sesion);
                        
                        if(($siguiente_sesion->dia_id!=$sesion->dia_id)||($siguiente_sesion->id== Sesion::where('inscripcione_id', $inscripcion->id)->get()->first()->id)){
                            $fecha->addDay();
                            while ((!in_array($fecha->isoFormat('dddd'), $vector_dias))||($this->esFeriado($fecha))) {
                                $fecha->addDay();
                            }
                        }
                    }
                }
            }
            $inscripcion->estado_id = estado('CORRIENDO');
            $inscripcion->fechafin = $programa->fecha;
            
            if ($inscripcion->pagos->sum('monto') < $inscripcion->costo) {
                $inscripcion->save();
                return redirect()->route('mostrar.programa', $inscripcion);
            } else {
                $inscripcion->fecha_proximo_pago = $fecha;
                $inscripcion->save();
                return redirect()->route('mostrar.programa', $inscripcion);
                //return redirect()->route('imprimir.programa', $inscripcion->id);;
            }   
        
    }

/***********************************************************************************************
 * 
 *                                      G E N E R A R  P R O G R A M A  D E G  U A R D  E R I A
 * 
 ***********************************************************************************************/

    public function generarProgramaGuarderia($inscripcione_id)
    {
        $inscripcion = Inscripcione::findOrFail($inscripcione_id);
        //dd($inscripcion);

        $costo_total = $inscripcion->costo;
        $total_horas = $inscripcion->totalhoras;
        $acuenta = $inscripcion->pagos->sum('monto');
        $fecha = $inscripcion->fechaini;
        foreach ($inscripcion->sesiones as $dia) {
            $vector_dias[] = Dia::findOrFail($dia->dia_id)->dia;
        }
        while ((!in_array($fecha->isoFormat('dddd'), $vector_dias))) {
            $fecha->addDay();
        }

        $sesiones = Sesion::where('inscripcione_id', '=', $inscripcione_id)->get();
        $inscripcion = Inscripcione::findOrFail($inscripcione_id);
        $sesiones = Sesion::where('inscripcione_id', '=', $inscripcione_id)->get();
        while ($fecha < $inscripcion->fechaini->addMonth()->subDay()) {
            foreach ($sesiones as $sesion) {
                $programa = new Programacion();
                $hora_x_sesion = $sesion->horainicio->floatDiffInHours($sesion->horafin);
                $costo_x_sesion = ($costo_total / $inscripcion->totalhoras) * $hora_x_sesion;

                $costo_hora = $costo_total / $total_horas;
                //dd($costo_hora);
                if ($total_horas >= $hora_x_sesion) {
                    if ($acuenta >= $costo_x_sesion) {
                        $this->agregarClase($programa, $fecha, $hora_x_sesion, $total_horas, $sesion, true, $inscripcion);
                    } else {
                        $this->agregarClase($programa, $fecha, $hora_x_sesion, $total_horas, $sesion, false, $inscripcion);
                    }
                } else {
                    if ($acuenta >= $costo_hora * $total_horas) {
                        $this->agregarClase($programa, $fecha, $hora_x_sesion, $total_horas, $sesion, true, $inscripcion);
                    } else {
                        $this->agregarClase($programa, $fecha, $hora_x_sesion, $total_horas, $sesion, false, $inscripcion);
                    }
                }
                $programa->save();
                $acuenta = $acuenta - $costo_x_sesion;
                $siguiente_sesion = $this->siguienteSesion($inscripcion, $sesion);

                if (($siguiente_sesion->dia_id != $sesion->dia_id) || ($siguiente_sesion->id == Sesion::where('inscripcione_id', $inscripcion->id)->get()->first()->id)) {
                    $fecha->addDay();
                    while ((!in_array($fecha->isoFormat('dddd'), $vector_dias)) || ($this->esFeriado($fecha))) {
                        $fecha->addDay();
                    }
                }
                if ($fecha >= $inscripcion->fechaini->addMonth()) {
                    break;
                }
            }
        }
        $inscripcion->fechafin = $programa->fecha;
        if ($inscripcion->pagos->sum('monto') < $inscripcion->costo) {
            return redirect()->route('mostrar.programa', $inscripcion);
        } else {
            $inscripcion->fecha_proximo_pago = $fecha;
            $inscripcion->save();
            return redirect()->route('imprimir.programa', $inscripcion->id);;
        }   
    }
/**
 * Funciones de apoyo para generar clases
 */
    public function esFeriado($unaFecha){
        $cantidad = count(DB::table('feriados')->whereIn('fecha', [$unaFecha])->get());
        return $cantidad>0;
    }


    /**
     * esta funcion hace rotar el turno de las sesiones tipo ronda 
     */
    public function siguienteSesion($unaInscripcion,$unaSesion){
        $sesiones_de_esta_inscripcion=Sesion::where('inscripcione_id',$unaInscripcion->id)->get();
        // la sesion actual el es primero consicedarar si tiene una sola sesion
        if($unaSesion->id==$sesiones_de_esta_inscripcion->last()->id){
            //$respuesta = 
            $respuesta= Sesion::where('inscripcione_id', $unaInscripcion->id)->get()->first();
        }else{
            $respuesta = Sesion::where('inscripcione_id', '=', $unaSesion->inscripcione_id)->where('id', '>', $unaSesion->id)
                        ->orderBy('id', 'asc')->first();
        }
        return $respuesta;
    }


    public function agregarClase(&$programa, &$fecha, $hora_x_sesion, &$total_horas , &$sesion , $habilitado, &$inscripcion){
        if ($total_horas > $hora_x_sesion) {
            $programa->hora_fin = $sesion->horafin;
            $programa->horas_por_clase = $hora_x_sesion;
        }else{
            $programa->hora_fin = $sesion->horainicio->addMinutes($total_horas * 60);
            $programa->horas_por_clase = $total_horas;
        }
        $programa->fecha = $fecha;
        $programa->habilitado = $habilitado;
        $programa->activo = true;
        $programa->estado_id = estado('INDEFINIDO');
        $programa->hora_ini = $sesion->horainicio;
        
        $programa->docente_id = $sesion->docente_id;
        $programa->materia_id = $sesion->materia_id;
        $programa->aula_id = $sesion->aula_id;
        $programa->inscripcione_id = $inscripcion->id;
    }

    public function EliminarTodosLosProgramas($inscripcione_id){
        Programacion::where('inscripcione_id', '=', $inscripcione_id)->delete();
    }

    public function regenerarPrograma($inscripcione_id,$unaFecha,$unModo = 'desde'){
        if($unModo=='todo'){
            $this->EliminarTodosLosProgramas($inscripcione_id);
            $this->generarPrograma($inscripcione_id);
        }
        $unaFecha= Carbon::createFromFormat('Y-m-d', $unaFecha);
        $inscripcion= Inscripcione::findOrFail($inscripcione_id);
        $FechaDesde=$unaFecha->format('Y-m-d');
        
        $horasFaltantesAntiguo = Programacion::where('inscripcione_id', '=', $inscripcione_id)->where('fecha', '>=', $FechaDesde)->sum('horas_por_clase');
        $totalHorasAntiguo=Programacion::where('inscripcione_id', '=', $inscripcione_id)->sum('horas_por_clase');
        $totalHorasNuevo=$inscripcion->totalhoras;
        $horas_pasadas=$totalHorasAntiguo-$horasFaltantesAntiguo;

        if($totalHorasAntiguo<=$totalHorasNuevo){
            $total_horas=$horasFaltantesAntiguo+($totalHorasNuevo-$totalHorasAntiguo);
        }else{
            $total_horas=$horasFaltantesAntiguo-($horasFaltantesAntiguo-$totalHorasNuevo)-$horas_pasadas;
        }
        $horasFaltantes=$total_horas;
        $horasPasadas = $inscripcion->totalhoras-$horasFaltantes;
        Programacion::where('inscripcione_id', '=', $inscripcione_id)->where('fecha', '>=', $FechaDesde)->delete();
        $costo_hora=$inscripcion->costo/$inscripcion->totalhoras;
        $acuentaTotal = $inscripcion->pagos->sum('monto');
        $CostoTotal = $inscripcion->costo;
        $costo_horas_pasadas=$horasPasadas*$costo_hora;
        $costo_restante=$CostoTotal-$costo_horas_pasadas;
        $Acuenta_para_regenerar=$acuentaTotal-$costo_horas_pasadas;
        $fecha = $unaFecha;
        foreach ($inscripcion->sesiones as $dia)
            $vector_dias[] = Dia::findOrFail($dia->dia_id)->dia;
        while ((!in_array($fecha->isoFormat('dddd'), $vector_dias)))
            $fecha->addDay();
        $sesiones = Sesion::where('inscripcione_id', '=', $inscripcione_id)->get();
        while ($horasFaltantes > 0) {
            foreach ($sesiones as $sesion) {
                if ($horasFaltantes > 0) {
                    $programa = new Programacion();
                    $hora_x_sesion = $sesion->horainicio->floatDiffInHours($sesion->horafin);
                    $costo_x_sesion = ($costo_restante / $total_horas) * $hora_x_sesion;
                    if ($horasFaltantes >= $hora_x_sesion) {
                        if ($Acuenta_para_regenerar > $costo_x_sesion) {
                            $this->agregarClase($programa, $fecha, $hora_x_sesion, $horasFaltantes, $sesion, true, $inscripcion);
                        } else {
                            $this->agregarClase($programa, $fecha, $hora_x_sesion, $horasFaltantes, $sesion, false, $inscripcion);
                        }
                    } else {
                        if ($Acuenta_para_regenerar >= $costo_hora * $horasFaltantes) {
                            $this->agregarClase($programa, $fecha, $hora_x_sesion, $horasFaltantes, $sesion, true, $inscripcion);
                        } else {
                            $this->agregarClase($programa, $fecha, $hora_x_sesion, $horasFaltantes, $sesion, false, $inscripcion);
                        }
                    }
                    $programa->save();
                    $Acuenta_para_regenerar = $Acuenta_para_regenerar - $costo_x_sesion;
                    $horasFaltantes = $horasFaltantes - $hora_x_sesion;
                    $siguiente_sesion = $this->siguienteSesion($inscripcion, $sesion);
                    if (($siguiente_sesion->dia_id != $sesion->dia_id) || ($siguiente_sesion->id == Sesion::where('inscripcione_id', $inscripcion->id)->get()->first()->id)) {
                        $fecha->addDay();
                        //while ((!in_array($fecha->isoFormat('dddd'), $vector_dias))) {
                        while ((!in_array($fecha->isoFormat('dddd'), $vector_dias))||($this->esFeriado($fecha))) {
                            $fecha->addDay();
                        }
                    }
                } 
            }
        }
        $inscripcion->fechafin = $programa->fecha;
        $inscripcion->estado_id = estado('CORRIENDO');
        $inscripcion->save();
        if ($Acuenta_para_regenerar < $inscripcion->costo) {
            return redirect()->route('mostrar.programa', $inscripcion);
        } else {
            $inscripcion->fecha_proximo_pago = $fecha;
            $inscripcion->save();
            return redirect()->route('mostrar.programa', $inscripcion);
            //return redirect()->route('imprimir.programa', $inscripcion->id);/** llamar al metodo que muestra pdf*/
        }    
    }


    public function deshabilitarTodoProgramas($inscripcione_id){
        $programas=Programacion::where('inscripcione_id', $inscripcione_id)->get();
        foreach ($programas as $programa) {
            $programa->habilitado=0;
            $programa->save();
        }
    }
    public function actualizarProgramaSegunPago($inscripcione_id){
        
        $inscripcion = Inscripcione::findOrFail($inscripcione_id);
        $total_costo=$inscripcion->costo;
        $total_horas=$inscripcion->totalhoras;
        $costo_por_hora=$total_costo/$total_horas;
        
        $programas = Programacion::where('inscripcione_id', '=', $inscripcione_id)
        ->get();
        
        $acuentaTotal=$inscripcion->pagos->sum->monto;
        $TotalPagado=$acuentaTotal;
        $this->deshabilitarTodoProgramas($inscripcione_id);
        
        foreach ($programas as $programa) {
            $costo_programa = $programa->hora_ini->floatDiffInHours($programa->hora_fin)*($costo_por_hora);
            $P=Programacion::findOrFail($programa->id);
            if($acuentaTotal>=$costo_programa){
                $P->habilitado=1;
                $P->save();
                $acuentaTotal=$acuentaTotal-$costo_programa;
            }
        }

        if ($TotalPagado <= $inscripcion->costo) {
            return redirect()->route('mostrar.programa', $inscripcion);
        } else {
            $inscripcion->fecha_proximo_pago = $inscripcion->programaciones->last()->fecha->isoFormat('Y-M-D');
            $inscripcion->save();
            return redirect()->route('imprimir.programa', $inscripcion->id);
            
        }    
    }
    
    // public function mostrarPrograma(Inscripcione $inscripcion){
    public function mostrarPrograma($inscripcion_id){ //llegaba un id de inscripcion
        $inscripcion=Inscripcione::findOrFail($inscripcion_id);
        $programacion = Programacion::join('materias', 'programacions.materia_id', '=', 'materias.id')
        ->join('aulas', 'programacions.aula_id', '=', 'aulas.id')
        ->join('docentes', 'programacions.docente_id', '=', 'docentes.id')
        ->join('personas', 'personas.id', '=', 'docentes.persona_id')
        ->select('programacions.id','programacions.fecha', 'hora_ini', 'hora_fin','horas_por_clase', 'personas.nombre', 'materias.materia', 'aulas.aula', 'programacions.habilitado', 'programacions.inscripcione_id')
        ->orderBy('fecha', 'asc')
        ->where('inscripcione_id', '=', $inscripcion->id)->get();
        // $persona=Persona::findOrFail(->estudiante->persona_id);
        $persona=$inscripcion->estudiante->persona;
        
        return view('programacion.show', compact('programacion','inscripcion','persona'));
    }

    public function imprimirPrograma($inscripcione_id){
        $inscripcion=Inscripcione::findOrFail($inscripcione_id);
        $programacion = Programacion::join('materias', 'programacions.materia_id', '=', 'materias.id')
            ->join('aulas', 'programacions.aula_id', '=', 'aulas.id')
            ->join('docentes', 'programacions.docente_id', '=', 'docentes.id')
            ->join('personas', 'personas.id', '=', 'docentes.persona_id')
            ->select('programacions.fecha', 'hora_ini','programacions.habilitado', 'hora_fin', 'horas_por_clase', 'personas.nombre', 'materias.materia', 'aulas.aula', 'programacions.habilitado')
            ->orderBy('fecha', 'asc')
            ->where('inscripcione_id', '=', $inscripcione_id)->get();
        $estudiante = Estudiante::findOrFail($inscripcion->estudiante_id);
        $persona = $estudiante->persona;
        $colegio=Colegio::find($estudiante->grados->last()->pivot->colegio_id);
        //$grado=Grado::find($estudiante->grados->last()->pivot->grado_id);
        $dias=$inscripcion->dias;
        $usuario=$inscripcion->usuarios->first();
        $modalidad=$inscripcion->modalidad;
        $edad = Carbon::parse($persona->fechanacimiento)->age; 
        $nivel=Nivel::findOrFail($estudiante->grados->last()->nivel_id);
        $grado=Grado::findOrFail($estudiante->grados->last()->pivot->grado_id);
        $pago=$inscripcion->pagos->sum('monto');

        if (! is_dir(storage_path('fonts'))) {
            @mkdir(storage_path('fonts'), 0775, true);
        }
        
        $dompdf = PDF::loadView('programacion.reporte', compact('grado','nivel','modalidad','usuario','programacion','persona','estudiante','persona','colegio','inscripcion','pago','dias','edad'));
        /**entrae a la persona al cual corresponde esta inscripcion */
        $fecha_actual = Carbon::now();
        $fecha_actual->isoFormat('DD-MM-YYYY-HH:mm:ss');
        $dompdf->setPaper('letter','portrait');
        // $dompdf->setPaper('A4', 'portrait');
        return $dompdf->download($persona->id . '_' . $fecha_actual . '_' . $persona->nombre . '_' . $persona->apellidop . '.pdf');
    }

    public function marcadoNormal($programacion_id){
        $programa=Programacion::findOrFail($programacion_id);
        
        $inscripcion=Inscripcione::findOrFail($programa->inscripcione_id);
        //$programasAnteriores=
        $docentes = Docente::join('personas', 'personas.id', '=', 'docentes.persona_id')
            ->join('estados', 'estados.id', '=', 'docentes.estado_id')
            ->where('docentes.estado_id', '=', estado('HABILITADO'))
            ->select('docentes.id', 'personas.nombre', 'personas.apellidop')
            ->orderBy('personas.nombre', 'asc') // Ordenar por nombre en orden ascendente
            ->get();

        $nivel=Nivel::findOrFail(Modalidad::findOrFail($inscripcion->modalidad_id)->nivel_id);
        $materias = $nivel->materias;
        $aulas=Aula::all();
        $temas=Tema::all();
        $hora_inicio=Carbon::now()->isoFormat('HH:mm:ss');
        $hora_fin = Carbon::now()->addMinutes($programa->hora_ini->floatDiffInMinutes($programa->hora_fin))->isoFormat('HH:mm:ss');

        
        $historiaclases=Clase::join('programacions','clases.programacion_id','programacions.id')
            ->join('inscripciones','programacions.inscripcione_id','inscripciones.id')
            ->join('docentes','docentes.id','clases.docente_id')
            ->join('materias','materias.id','clases.materia_id')
            ->join('temas','temas.id','clases.tema_id')
            ->join('estados','estados.id','clases.estado_id')
            ->join('aulas','aulas.id','clases.aula_id')
            ->where('inscripciones.id',$inscripcion->id)
            ->select('clases.estado_id','clases.fecha','estados.estado','clases.horainicio','clases.horafin','docentes.nombrecorto','materias.materia','temas.tema','aulas.aula')
            ->orderBy('fecha','asc')
            ->get();

        $historiaprogramas=Programacion::join('inscripciones','programacions.inscripcione_id','inscripciones.id')
            ->join('docentes','docentes.id','programacions.docente_id')
            ->join('materias','materias.id','programacions.materia_id')
            ->join('aulas','aulas.id','programacions.aula_id')
            ->join('estados','estados.id','programacions.estado_id')
            ->where('inscripciones.id',$inscripcion->id)
            ->where('programacions.fecha','<',Carbon::now()->isoFormat('Y-M-D'))
            ->select('estados.estado','programacions.fecha','programacions.hora_ini','programacions.hora_fin','docentes.nombrecorto','materias.materia','aulas.aula')
            ->orderBy('fecha','asc')
            ->get();
        $docenteshabilitados=Docente::where('docentes.estado_id',estado('HABILITADO'))->select('id','nombrecorto')->get();
        
        $data['label'][]=[];
        $data['presente'][]=[];
        $data['finalizado'][]=[];
        $data['indefinido'][]=[];

        
        
        foreach ($docenteshabilitados as $docente) {
            $finalizados=Programacion::join('docentes','docentes.id','programacions.docente_id')
            ->where('docente_id',$docente->id)
            ->where('programacions.estado_id',estado('FINALIZADO'))
            ->where('programacions.fecha','=',Carbon::now()->isoFormat('Y-M-D'))
            ->select('nombrecorto',DB::raw('count(*) as cantidad'))
            ->groupBy('nombrecorto')
            ->get()->first();
            $presentes=Programacion::join('docentes','docentes.id','programacions.docente_id')
            ->where('docente_id',$docente->id)
            ->where('programacions.estado_id',estado('PRESENTE'))
            ->where('programacions.fecha','=',Carbon::now()->isoFormat('Y-M-D'))
            ->select('nombrecorto',DB::raw('count(*) as cantidad'))
            ->groupBy('nombrecorto')
            ->get()->first();
            $indefinidos=Programacion::join('docentes','docentes.id','programacions.docente_id')
            ->where('docente_id',$docente->id)
            ->where('programacions.estado_id',estado('INDEFINIDO'))
            ->where('programacions.fecha','=',Carbon::now()->isoFormat('Y-M-D'))
            ->select('nombrecorto',DB::raw('count(*) as cantidad'))
            ->groupBy('nombrecorto')
            ->get()->first();
            $data['label'][]=$docente->nombrecorto;
		  	
            if(empty($presentes->cantidad))
				$data['presente'][]=0;  
			else
			  $data['presente'][]=$presentes->cantidad;
		  
            if(empty($finalizados->cantidad))
				$data['finalizado'][]=0;  
			else
                $data['finalizado'][]=$finalizados->cantidad;
			if(empty($indefinidos->cantidad))
				$data['indefinido'][]=0;  
			else		  
                $data['indefinido'][]=$indefinidos->cantidad;

        }
        
        $data['data']=json_encode($data);
        return view('clase.create',compact('docentes','programa','inscripcion','materias','aulas','hora_inicio','hora_fin','temas','historiaclases','historiaprogramas'),$data);
    }

    public function marcadoNormalModal($programacion_id){
        $programa = Programacion::findOrFail($programacion_id);
        $inscripcion = Inscripcione::findOrFail($programa->inscripcione_id);
        $estudianteId = $inscripcion->estudiante_id;
        $docentes = Docente::join('personas', 'personas.id', '=', 'docentes.persona_id')
            ->join('estados', 'estados.id', '=', 'docentes.estado_id')
            ->where('docentes.estado_id', '=', estado('HABILITADO'))
            ->select('docentes.id', 'personas.nombre', 'personas.apellidop')
            ->orderBy('personas.nombre', 'asc')
            ->get();

        $nivel = Nivel::findOrFail(Modalidad::findOrFail($inscripcion->modalidad_id)->nivel_id);
        $materias = $nivel->materias;
        $aulas = Aula::all();
        $temas = Tema::all();
        $hora_inicio = Carbon::now()->format('H:i');
        $hora_fin = Carbon::now()->addMinutes($programa->hora_ini->floatDiffInMinutes($programa->hora_fin))->format('H:i');

        $clasePresente = Clase::where('programacion_id', $programa->id)
            ->where('estado_id', estado('PRESENTE'))
            ->where('fecha', Carbon::now()->toDateString())
            ->first();

        $totalPagado = $inscripcion->pagos->sum('monto');
        $saldo = max($inscripcion->costo - $totalPagado, 0);
        $ultimaProgramacionPagada = Programacion::where('inscripcione_id', $inscripcion->id)
            ->where('habilitado', 1)
            ->orderBy('fecha', 'desc')
            ->first();
        $programaciones = Programacion::with(['materia','docente','aula','estado'])
            ->where('inscripcione_id', $inscripcion->id)
            ->orderBy('fecha', 'asc')
            ->get();

        $inscripcionesActivas = Inscripcione::where('estudiante_id', $estudianteId)
            ->where('estado_id', estado('CORRIENDO'))
            ->where('vigente', 1)
            ->count();
        $matriculacionesActivas = \App\Models\Matriculacion::join('computacions', 'computacions.id', '=', 'matriculacions.computacion_id')
            ->where('computacions.persona_id', $inscripcion->estudiante->persona_id)
            ->where('matriculacions.estado_id', estado('CORRIENDO'))
            ->where('matriculacions.vigente', 1)
            ->count();

        return view('clase.modalmarcado', compact(
            'docentes',
            'programa',
            'inscripcion',
            'materias',
            'aulas',
            'hora_inicio',
            'hora_fin',
            'temas',
            'clasePresente',
            'inscripcionesActivas',
            'matriculacionesActivas',
            'totalPagado',
            'saldo',
            'ultimaProgramacionPagada',
            'programaciones'
        ));
    }

    public function marcadoTabs($persona_id){
        $persona = Persona::findOrFail($persona_id);
        $estudiante = $persona->estudiante;

        $inscripciones = Inscripcione::where('estudiante_id', $estudiante->id)
            ->where('estado_id', estado('CORRIENDO'))
            ->where('vigente', 1)
            ->get();

        $inscripcionTabs = [];
        foreach ($inscripciones as $inscripcion) {
            $programa = Programacion::where('inscripcione_id', $inscripcion->id)
                ->where('fecha', Carbon::now()->toDateString())
                ->orderBy('hora_ini', 'asc')
                ->first();

            $totalPagado = $inscripcion->pagos->sum('monto');
            $saldo = max($inscripcion->costo - $totalPagado, 0);
            $ultimaProgramacionPagada = Programacion::where('inscripcione_id', $inscripcion->id)
                ->where('habilitado', 1)
                ->orderBy('fecha', 'desc')
                ->first();
            $programaciones = Programacion::with(['materia','docente','aula','estado'])
                ->where('inscripcione_id', $inscripcion->id)
                ->orderBy('fecha', 'asc')
                ->get();

            $nivel = Nivel::findOrFail(Modalidad::findOrFail($inscripcion->modalidad_id)->nivel_id);
            $materias = $nivel->materias;
            $docentes = Docente::join('personas', 'personas.id', '=', 'docentes.persona_id')
                ->join('estados', 'estados.id', '=', 'docentes.estado_id')
                ->where('docentes.estado_id', '=', estado('HABILITADO'))
                ->select('docentes.id', 'personas.nombre', 'personas.apellidop')
                ->orderBy('personas.nombre', 'asc')
                ->get();
            $aulas = Aula::all();
            $temas = Tema::all();

            $hora_inicio = $programa
                ? Carbon::now()->format('H:i')
                : null;
            $hora_fin = $programa
                ? Carbon::now()->addMinutes($programa->hora_ini->floatDiffInMinutes($programa->hora_fin))->format('H:i')
                : null;

            $clasePresente = $programa
                ? Clase::where('programacion_id', $programa->id)
                    ->where('estado_id', estado('PRESENTE'))
                    ->where('fecha', Carbon::now()->toDateString())
                    ->first()
                : null;

            $inscripcionTabs[] = [
                'inscripcion' => $inscripcion,
                'programa' => $programa,
                'docentes' => $docentes,
                'materias' => $materias,
                'aulas' => $aulas,
                'temas' => $temas,
                'hora_inicio' => $hora_inicio,
                'hora_fin' => $hora_fin,
                'clasePresente' => $clasePresente,
                'totalPagado' => $totalPagado,
                'saldo' => $saldo,
                'ultimaProgramacionPagada' => $ultimaProgramacionPagada,
                'programaciones' => $programaciones,
            ];
        }

        $matriculacionTabs = [];
        if ($persona->computacion) {
            $matriculaciones = \App\Models\Matriculacion::where('computacion_id', $persona->computacion->id)
                ->where('estado_id', estado('CORRIENDO'))
                ->where('vigente', 1)
                ->get();

            foreach ($matriculaciones as $matriculacion) {
                $programacioncom = Programacioncom::where('matriculacion_id', $matriculacion->id)
                    ->where('fecha', Carbon::now()->toDateString())
                    ->orderBy('horaini', 'asc')
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

                $docentes = Docente::join('personas', 'personas.id', '=', 'docentes.persona_id')
                    ->join('estados', 'estados.id', '=', 'docentes.estado_id')
                    ->where('docentes.estado_id', '=', estado('HABILITADO'))
                    ->select('docentes.id', 'personas.nombre', 'personas.apellidop')
                    ->orderBy('personas.nombre', 'asc')
                    ->get();
                $aulas = Aula::all();

                $hora_inicio = $programacioncom
                    ? Carbon::now()->format('H:i')
                    : null;
                $hora_fin = $programacioncom
                    ? Carbon::now()->addMinutes($programacioncom->horaini->floatDiffInMinutes($programacioncom->horafin))->format('H:i')
                    : null;

                $clasecomPresente = $programacioncom
                    ? Clasecom::where('programacioncom_id', $programacioncom->id)
                        ->where('estado_id', estado('PRESENTE'))
                        ->where('fecha', Carbon::now()->toDateString())
                        ->first()
                    : null;

                $matriculacionTabs[] = [
                    'matriculacion' => $matriculacion,
                    'programacioncom' => $programacioncom,
                    'docentes' => $docentes,
                    'aulas' => $aulas,
                    'hora_inicio' => $hora_inicio,
                    'hora_fin' => $hora_fin,
                    'clasecomPresente' => $clasecomPresente,
                    'totalPagado' => $totalPagado,
                    'saldo' => $saldo,
                    'ultimaProgramacionPagada' => $ultimaProgramacionPagada,
                    'programaciones' => $programaciones,
                ];
            }
        }

        return view('clase.modalmarcado_tabs', [
            'persona' => $persona,
            'inscripcionTabs' => $inscripcionTabs,
            'matriculacionTabs' => $matriculacionTabs,
        ]);
    }

    public function estudiantesDeUnProfesor($docente_id){
        $estudiantes=Estudiante::join('inscripciones','inscripciones.estudiante_id','estudiantes.id')
            ->join('programacions','programacions.inscripcione_id','inscripciones.id')
            ->join('clases','clases.programacion_id','programacions.id')
            ->join('docentes','docentes.id','clases.docente_id')
            ->join('personas','personas.id','estudiantes.persona_id')
            ->join('temas','temas.id','clases.tema_id')
            ->join('materias','materias.id','clases.materia_id')
            ->where('docentes.id',$docente_id)
            ->where('programacions.fecha','=',Carbon::now()->isoFormat('Y-M-D'))
            ->where('clases.estado_id',estado('PRESENTE'))
            ->select('personas.id','clases.id as clase_id','personas.nombre','clases.horafin','clases.horainicio','personas.foto','personas.apellidop','personas.apellidom','temas.tema','materias.materia')
            ->get();
        return $estudiantes;
    }
    
    public function estadoAsistencia(){
        $docenteshabilitados=Docente::where('docentes.estado_id',estado('HABILITADO'))->select('id','nombrecorto')->get();
       
        $data['label'][]=[];
        $data['presente'][]=[];
        $data['finalizado'][]=[];
        $data['indefinido'][]=[];
        $estudiantes=array();
        foreach ($docenteshabilitados as $doc) {
            $finalizados=Programacion::join('docentes','docentes.id','programacions.docente_id')
            ->where('docente_id',$doc->id)
            ->where('programacions.estado_id',estado('FINALIZADO'))
            ->where('programacions.fecha','=',Carbon::now()->isoFormat('Y-M-D'))
            ->select('nombrecorto',DB::raw('count(*) as cantidad'))
            ->groupBy('nombrecorto')
            ->get()->first();
            $presentes=Programacion::join('docentes','docentes.id','programacions.docente_id')
            ->where('docente_id',$doc->id)
            ->where('programacions.estado_id',estado('PRESENTE'))
            ->where('programacions.fecha','=',Carbon::now()->isoFormat('Y-M-D'))
            ->select('nombrecorto',DB::raw('count(*) as cantidad'))
            ->groupBy('nombrecorto')
            ->get()->first();
            $indefinidos=Programacion::join('docentes','docentes.id','programacions.docente_id')
            ->where('docente_id',$doc->id)
            ->where('programacions.estado_id',estado('INDEFINIDO'))
            ->where('programacions.fecha','=',Carbon::now()->isoFormat('Y-M-D'))
            ->select('nombrecorto',DB::raw('count(*) as cantidad'))
            ->groupBy('nombrecorto')
            ->get()->first();

            $estudiantesDeUnDocente=$this->estudiantesDeUnProfesor($doc->id);
            array_push($estudiantes,$estudiantesDeUnDocente);
            $data['label'][]=$doc->nombrecorto;
            
            if(empty($presentes->cantidad))
				$data['presente'][]=0;  
			else
			    $data['presente'][]=$presentes->cantidad;
            if(empty($finalizados->cantidad))
				$data['finalizado'][]=0;  
			else
                $data['finalizado'][]=$finalizados->cantidad;
			if(empty($indefinidos->cantidad))
				$data['indefinido'][]=0;  
			else		  
                $data['indefinido'][]=$indefinidos->cantidad;
        }
        
        
        $data['data']=json_encode($data);
        //dd($estudiantes);
        return view('clase.estado',compact('estudiantes','docenteshabilitados'),$data);
    }

    public function guardarObservacion(Request $request){
        $observacion=new Observacion();
        $observacion->observacion=$request->observacion;
        $observacion->activo=1;
        $observacion->observable_id=$request->id_programacion;
        $observacion->observable_type= Programacion::class;
        $observacion->save();

        $observacion->usuarios()->attach(Auth::user()->id);
        
        return response()->json($observacion);
    }

    public function programacionesFuturo(Request $request){
        $programacion=Programacion::join('docentes','docentes.id','=','programacions.docente_id')
                    ->join('aulas','aulas.id','=','programacions.aula_id')
                    ->join('inscripciones','inscripciones.id','=','programacions.inscripcione_id')
                    ->join('materias','materias.id','=','programacions.materia_id')
                    ->join('estados','estados.id','=','programacions.estado_id')
                    ->where('inscripcione_id',$request->inscripcion)
                    ->select('programacions.id','fecha','estados.estado','materia','docentes.nombrecorto as docente','programacions.hora_ini','programacions.hora_fin','aulas.aula','programacions.habilitado')->get();
        return DataTables::of($programacion)
                ->addColumn('btn','programacion.actionsfuturo')
                ->rawColumns(['btn'])
                ->toJson();
    }

    public function controlAsitencia($inscripcion=1){
       // $inscripcion=1;
        $programacion=Programacion::join('inscripciones','inscripciones.id','=','programacions.inscripcione_id')
                    ->join('estados','estados.id','=','programacions.estado_id')
                    ->where('inscripcione_id',$inscripcion)
                    ->select('programacions.id','fecha','estados.estado','programacions.habilitado')->get();
        return DataTables::of($programacion)
                ->toJson();
    }

    public function asisntecia(Request $request){
        $estados=Inscripcione::join('programacions','programacions.inscripcione_id','inscripciones.id')
        ->join('estudiantes','estudiantes.id','inscripciones.estudiante_id')
        ->join('personas','personas.id','estudiantes.persona_id')
        ->join('estados','estados.id','programacions.estado_id')
        ->where('inscripciones.vigente',1)
        ->where('inscripciones.id',$request->inscripcione_id)
        ->select('personas.id','programacions.id as programacion_id','personas.nombre','personas.apellidop','estados.estado')
        ->orderBy('programacions.id','asc')
        ->get();
        return response()->json($estados);
    }
    public function asignarFaltasFechasPasadas(){
        $programacion = Programacion::where('fecha','<',Carbon::now()->format('Y-m-d'))
                    ->where('estado_id',estado('INDEFINIDO'))->update(['estado_id'=>estado('FALTA')]);
        return response()->json($programacion);
    }

    public function hoy(){
        $programas= Programacion::join('inscripciones','inscripciones.id','programacions.inscripcione_id')
        ->join('estudiantes','estudiantes.id','inscripciones.estudiante_id')
        ->join('personas','personas.id','estudiantes.persona_id')
        ->join('docentes','programacions.docente_id','docentes.id')
        ->join('materias','programacions.materia_id','materias.id')
        ->join('estados','programacions.estado_id','estados.id')

        ->where('programacions.fecha',Carbon::now()->format('Y-m-d'))
        ->select('inscripciones.id','personas.id as persona_id','personas.nombre as estudiante','estados.estado','personas.apellidop','foto','docentes.nombrecorto as docente','programacions.hora_ini','programacions.hora_fin','materias.materia')  
        ->get();

        return DataTables::of($programas)
                ->addColumn('btn','programacion.actionshoy')
                ->rawColumns(['btn'])
                ->toJson();
    }

    public function programacionMostrarAjax(Request $request){
            $programacion = Programacion::join('materias', 'programacions.materia_id', '=', 'materias.id')
            ->join('aulas', 'programacions.aula_id', '=', 'aulas.id')
            ->join('docentes', 'programacions.docente_id', '=', 'docentes.id')
            ->join('personas', 'personas.id', '=', 'docentes.persona_id')
            ->join('estados','estados.id','programacions.estado_id')
            ->select('programacions.fecha', 'hora_ini','estados.estado','programacions.habilitado', 'hora_fin', 'horas_por_clase', 'personas.nombre', 'materias.materia', 'aulas.aula')
            ->orderBy('fecha', 'asc')
            ->where('inscripcione_id', '=', $request->inscripcion_id)->get();
        return response()->json($programacion);

    }

}

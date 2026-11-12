<?php

namespace App\Http\Controllers;

use App\Http\Controllers\EstudianteController;

use Illuminate\Support\Facades\Config;
use App\Http\Requests\ConfigurarionInscripcionRequest;
use App\Http\Requests\ActualizarConfigurarionInscripcionRequest;
use App\Models\Inscripcione;
use App\Models\Modalidad;
use App\Models\Motivo;
use App\Models\Persona;
use App\Models\Materia;
use App\Models\Aula;
use App\Models\Sesion;
use App\Models\Dia;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Gestion;
use App\Models\Grado;
use App\Models\Nivel;
use App\Models\Programacion;
use App\Models\Tipomotivo;
use App\Models\Constante;
use App\Models\Matriculacion;
use App\Models\User;
use App\Models\Estado;

use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Contracts\DataTable as DataTable; 
use Yajra\DataTables\DataTables;


use Illuminate\Support\Facades\Auth;

use Carbon\Carbon;


use Illuminate\Http\Request;

/**
 * Class InscripcioneController
 * @package App\Http\Controllers
 */
class InscripcioneController extends Controller
{


    public function __construct()
    {
        $this->middleware('can:Listar Inscripciones')->only('index','show','UltimaInscripcion','tusInscripcionesVigentes','tusinscripciones','inscripcionesVigentes','Saldo','listar','vigentesAjax','inscripcionMostrarAjax');
        $this->middleware('can:Crear Inscripciones')->only('crear','store','configurarView');
        $this->middleware('can:Editar Inscripciones')->only('daralta','fecharProximoPago','edit','darbaja','guardarconfiguracion','edit','configurarView','update','actualizarConfiguracion','actualizar_fecha_proximo_pago','reservar');
        $this->middleware('can:Eliminar Inscripciones')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $inscripciones = Inscripcione::paginate();
        return view('inscripcione.index', compact('inscripciones'))
            ->with('i', (request()->input('page', 1) - 1) * $inscripciones->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $modalidades = Modalidad::all();
        $motivos = Motivo::all();
        //dd($modalidades);
        //desde el menu puede enviar el objeto persona a create:
        return view('inscripcione.create', compact('modalidades','motivos'));
    }
    
    public function configurarView($inscripcion_id)
    {
        $inscripcion=Inscripcione::findOrFail($inscripcion_id);
        $nivel=Nivel::findOrFail(Modalidad::findOrFail($inscripcion->modalidad_id)->nivel_id);
        $materias = $nivel->materias;
        $aulas = Aula::get();
        $docentes = $nivel->docentes;
        $dias = Dia::get();
        $tipo='actualizando';
        $programacion = $inscripcion->programaciones; 
        return view('inscripcione.configurar', compact('nivel','inscripcion','materias','aulas','docentes','dias','tipo','programacion'));
    }

    public function UltimaInscripcion(Persona $persona){
        return Inscripcione::where('estudiante_id','=',$persona->estudiante->id)->orderBy('id','desc')->first();
    }
    public function UltimoNivel(Persona $persona){
        $ultima_inscripcion=$this->UltimaInscripcion($persona);
        if($ultima_inscripcion!=null)        
        $ultimo_nivel=Nivel::findOrFail(Modalidad::findOrFail($ultima_inscripcion->modalidad_id)->nivel_id);
        else {
            
            $ultimo_grado=$persona->estudiante->grados->first();
            $ultimo_nivel=$ultimo_grado->nivel;
        }
        return $ultimo_nivel;
    }
        


    public function crear(Persona $persona)
    {
        
        $motivos = Tipomotivo::findOrFail(1)->motivos;
        $ultima_inscripcion=$this->UltimaInscripcion($persona);
        if($ultima_inscripcion!=null){        
            // $ultimo_nivel=Nivel::findOrFail(Modalidad::findOrFail($ultima_inscripcion->modalidad_id)->nivel_id);
            $ultimo_nivel=$persona->estudiante->grados->last()->nivel;

        }
        else {
            $ultimo_grado=$persona->estudiante->grados->last();
            // dd($ultimo_grado);
            if(empty($ultimo_grado->nivel)){
                return redirect()->route('gestion.create',$persona->estudiante->id);
            }else{
                $ultimo_nivel=$ultimo_grado->nivel;
            }
        }
        $modalidades = $ultimo_nivel->modalidades;
        if($ultimo_nivel->nivel=='GUARDERIA'){
            $FACTORGUARDERIA=Constante::where('constante',"FACTORGUARDERIA")->first();
            $FACTORCOSTOHORAGUARDERIA=Constante::where('constante',"FACTORCOSTOHORAGUARDERIA")->first();
            $FACTORGUARDERIAMENOR111=Constante::where('constante',"FACTORGUARDERIAMENOR111")->first();
            $FACTORGUARDERIAMAYOR111=Constante::where('constante',"FACTORGUARDERIAMAYOR111")->first();
            return view('inscripcione.guarderia.create', compact('modalidades', 'motivos','persona','ultima_inscripcion','FACTORGUARDERIA','FACTORCOSTOHORAGUARDERIA','FACTORGUARDERIAMENOR111','FACTORGUARDERIAMAYOR111'));
        }
        else{
            return view('inscripcione.create', compact('modalidades', 'motivos', 'persona', 'ultima_inscripcion'));
        }
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate(Inscripcione::$rules);
        app(\App\Services\SedeAccess::class)->selectForCreation($request);
        $datos=$request->all();
        $inscripcion=new Inscripcione();
        $inscripcion->fechaini=$request->fechaini;
        $inscripcion->fechafin = $request->fechaini;
        $inscripcion->fecha_proximo_pago = $request->fechaini;
        if(!is_null($request->horas_total)){
            $inscripcion->totalhoras=$request->horas_total;
        }else{
            $inscripcion->totalhoras = $request->totalhoras;
        }
        $inscripcion->costo=$request->costo;
        $inscripcion->vigente=1;
        $inscripcion->condonado=0; 
        $inscripcion->objetivo=$request->objetivo;
        $inscripcion->estudiante_id=$request->estudiante_id;
        $inscripcion->modalidad_id=$request->modalidad_id;
        $inscripcion->motivo_id=$request->motivo_id;
        $inscripcion->save();
        // dd($inscripcion);
        
        //**%%%%%%%%%%%%%%%%%%%%  B  I  T  A  C  O  R  A   %%%%%%%%%%%%%%%%*/
        $inscripcion->usuarios()->attach(Auth::user()->id);
        $nivel=Nivel::findOrFail(Modalidad::findOrFail($inscripcion->modalidad_id)->nivel_id);
        $materias = $nivel->materias;
        $aulas = Aula::get();
        $docentes = $nivel->docentes()
            ->where('docentes.estado_id', '=', estado('HABILITADO'))
            ->orderBy('nombrecorto', 'asc') // Ordenar por nombre ascendente
            ->get();
            //dd($docentes);
        $dias = Dia::get();
        $tipo = 'guardando';    
        $programacion = $inscripcion->programaciones;
        if ($nivel->nivel=='GUARDERIA'){
            return view('inscripcione.guarderia.config', compact('datos','nivel','inscripcion', 'materias', 'aulas', 'docentes', 'tipo', 'dias', 'programacion')); 
        }else{
            return view('inscripcione.configurar', compact('nivel','inscripcion', 'materias', 'aulas', 'docentes', 'tipo', 'dias', 'programacion')); 
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */



    public function show($inscripciones_id)
    {
        $inscripcione = Inscripcione::findOrFail($inscripciones_id);
        $programacion = Programacion::with([
                'materia',
                'aula',
                'docente.persona',
                'estado',
                'clases.estado',
                'clases.docente.persona',
                'clases.aula',
                'clases.materia',
            ])
            ->where('inscripcione_id', '=', $inscripciones_id)
            ->orderBy('fecha', 'asc')
            ->get();
        $user=$inscripcione->usuarios->first();
        $pagos = $inscripcione->pagos()->orderBy('created_at', 'desc')->get();
        $acuenta = $pagos->sum('monto');
        $saldo = $inscripcione->costo - $acuenta;
        return view('inscripcione.show', compact('inscripcione','programacion','user','pagos','acuenta','saldo'));
    }

    public function inscripcionMostrarAjax(Request $request){
        $inscripcion = Inscripcione::findOrFail($request->inscripcion_id);
        $modalidad=Modalidad::findOrFail($inscripcion->modalidad_id);
        $persona=$inscripcion->estudiante->persona;
        $motivo=Motivo::findOrFail($inscripcion->motivo_id);
        $empezo=$inscripcion->fechaini->diffForHumans();    
        $finaliza=$inscripcion->fechafin->diffForHumans();    
        $proximo_pago=$inscripcion->fecha_proximo_pago->diffForHumans();    
        $creado=$inscripcion->created_at->diffForHumans();
        $actualizado=$inscripcion->updated_at->diffForHumans();
        $data=['inscripcion'=>$inscripcion,
                'modalidad'=>$modalidad,
                'persona'=>$persona, 
                'motivo'=>$motivo,
                'empezo'=>$empezo,
                'finaliza'=>$finaliza,
                'proximo_pago'=>$proximo_pago,
                'creado'=>$creado, 
                'actualizado'=>$actualizado];
        return response()->json($data);
    }





    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $inscripcione = Inscripcione::find($id);
        $nivel=Nivel::findOrFail(Modalidad::findOrFail($inscripcione->modalidad_id)->nivel_id);

        $persona=$inscripcione->estudiante->persona;
        $ultima_inscripcion=$this->UltimaInscripcion($persona);
        $ultimo_nivel= $this->ultimoNivel($persona);
        //dd($persona);
        // if($ultima_inscripcion==null){
        //     $ultima_inscripcion=$inscripcione;
        // }


        if($ultima_inscripcion!=null)        
            $ultimo_nivel=Nivel::findOrFail(Modalidad::findOrFail($ultima_inscripcion->modalidad_id)->nivel_id);
        else {
            $ultimo_grado=$persona->estudiante->grados->first();
            
            if(empty($ultimo_grado->nivel)){
                return redirect()->route('gestion.create',$persona->estudiante->id);
            }else{
                $ultimo_nivel=$ultimo_grado->nivel;
            }
        }
        
        
        $modalidades = $ultimo_nivel->modalidades;
        $motivos = Tipomotivo::findOrFail(1)->motivos;
        $estados=Estado::get();
        $editando=true;
        if ($nivel->nivel=='GUARDERIA'){
            $FACTORGUARDERIA=Constante::where('constante',"FACTORGUARDERIA")->first();
            $FACTORCOSTOHORAGUARDERIA=Constante::where('constante',"FACTORCOSTOHORAGUARDERIA")->first();
            $FACTORGUARDERIAMENOR111=Constante::where('constante',"FACTORGUARDERIAMENOR111")->first();
            $FACTORGUARDERIAMAYOR111=Constante::where('constante',"FACTORGUARDERIAMAYOR111")->first();
            //return view('inscripcione.guarderia.create', compact('modalidades', 'motivos','persona','ultima_inscripcion',));
            return view('inscripcione.guarderia.edit', compact('editando','inscripcione','estados','persona','modalidades','ultima_inscripcion','motivos','FACTORGUARDERIA','FACTORCOSTOHORAGUARDERIA','FACTORGUARDERIAMENOR111','FACTORGUARDERIAMAYOR111')); 
        }else{
             return view('inscripcione.edit', compact('editando','inscripcione','persona','estados', 'ultima_inscripcion','modalidades','motivos')); 
        }
       
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Inscripcione $inscripcione
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Inscripcione $inscripcione)
    {
        request()->validate(Inscripcione::$rules);
        $datos=$request->all();
        $inscripcione->fechaini = $request->fechaini;
        $inscripcione->totalhoras = $request->totalhoras;
        $inscripcione->costo = $request->costo;
        $inscripcione->objetivo = $request->objetivo;
        $inscripcione->estudiante_id = $request->estudiante_id;
        $inscripcione->modalidad_id = $request->modalidad_id;
        $inscripcione->motivo_id = $request->motivo_id;
        $inscripcione->estado_id = $request->estado_id;
        $inscripcione->condonado = $request->condonado;
        $inscripcione->vigente = $request->vigente;
        $inscripcione->save();
        $inscripcion=$inscripcione;
        $nivel=Nivel::findOrFail(Modalidad::findOrFail($inscripcion->modalidad_id)->nivel_id);
        $materias = $nivel->materias;
        $aulas = Aula::get();
        $docentes = Docente::get();
        $dias = Dia::get();
        $tipo='actualizando';
        $programacion=$inscripcione->programaciones;
        $nivel=Nivel::findOrFail(Modalidad::findOrFail($inscripcione->modalidad_id)->nivel_id);

        //dd($nivel);

        if ($nivel->nivel=='GUARDERIA'){
            return view('inscripcione.guarderia.config', compact('datos','nivel','inscripcion', 'materias', 'aulas', 'docentes','tipo', 'dias', 'programacion')); 
        }else{
            return view('inscripcione.configurar', compact('nivel','inscripcion', 'materias', 'aulas', 'docentes','tipo', 'dias', 'programacion')); 
        }
        //return view('inscripcione.configurar', compact('nivel','inscripcion', 'materias', 'aulas', 'docentes','tipo','dias','programacion'));
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $inscripcion = Inscripcione::findOrFail($id);
        $inscripcion->delete();
        return response()->json(['message' => 'Registro Eliminado', 'status' => 200]);
        
    }

    public function listar(Persona $persona){
        $persona=Persona::findOrFail($persona->id);
        $inscripcionesVigentes = Inscripcione::join('pagos','pagos.pagable_id','=','inscripciones.id')
                                        ->where('estudiante_id', '=', $persona->estudiante->id)
                                        ->where('vigente',1)
                                        ->select(['inscripciones.id','objetivo','costo','acuenta'=>\App\Services\SedeAccess::aggregate('pagos','SUM(monto)','pagos.pagable_id','inscripciones.id')])
                                        ->groupBy('inscripciones.id','objetivo','acuenta','costo')
                                        ->get();
        $inscripcionesOtras = Inscripcione::where('estudiante_id', '=', $persona->estudiante->id)
                                        ->where('vigente', 0)
                                        ->select('id', 'objetivo', 'costo')->get();
                            
        
        return redirect()->route('tus.inscripciones', ['estudiante_id'=>$persona->estudiante->id]);
    
        //return redirect()->action([InscripcioneController::class, 'tusinscripciones'], ['estudiante_id' => $persona->estudiante_id]);
    }

    /*%%%%%%%%%%%%%%%%%%%%%%%%%  INSCRIPCIONES PARA DATATABLE DE inscripcione/tusinscripciones.blade */
    public function tusInscripcionesVigentes(Request $request){
    // public function tusInscripcionesVigentes(){
        // $estudiante_id=1;
        $estudiante_id=$request->estudiante_id;
        // $estudiante_id=1;
        $persona=Estudiante::findOrFail($estudiante_id)->persona;
        $inscripcionesVigentes = Inscripcione::join('modalidads','modalidads.id','inscripciones.modalidad_id')
            ->join('estados','estados.id','inscripciones.estado_id')
            ->where('estudiante_id','=',$estudiante_id)
            // ->where('vigente', 1)
            ->select('inscripciones.id','inscripciones.vigente', 'objetivo', 'inscripciones.costo','fecha_proximo_pago','modalidads.modalidad','estado')
            ->withSucursalOrigen()
            ->get();
        return datatables()->of($inscripcionesVigentes)
                            ->addColumn('btn', 'inscripcione.actiontusinscripciones')
                            ->rawColumns(['btn','objetivo','sucursal_origen'])
                            ->toJson();
        
    }
    

    public function tusinscripciones(Estudiante $estudiante){
        
        $inscripciones=Inscripcione::where('estudiante_id', '=', $estudiante->id)->select('id', 'objetivo', 'costo')->get();

        // $persona=Estudiante::findOrFail($estudiante_id)->persona;
        $persona=$estudiante->persona;
        $inscripcionesVigentes = Inscripcione::join('pagos', 'pagos.pagable_id', '=', 'inscripciones.id')
            // ->where('estudiante_id', '=', $persona->estudiante->id)
            ->where('estudiante_id', '=', $estudiante->id)
            ->where('vigente', 1)
            ->where('pagos.pagable_id', '=', 'inscripciones.id')
            ->where('pagos.pagable_type', '=', 'App\Models\Inscripcione')
                    ->select(['inscripciones.id','vigente','objetivo','costo','acuenta'=>\App\Services\SedeAccess::aggregate('pagos','AVG(monto)','pagos.id','inscripciones.id')->where('inscripciones.id',1)])
            ->groupBy('inscripciones.id', 'vigente','objetivo', 'acuenta', 'costo')
            ->get();
        // $inscripcionesOtras = Inscripcione::where('estudiante_id', '=', $persona->estudiante->id)
        $inscripcionesOtras = Inscripcione::where('estudiante_id', '=', $estudiante->id)
            ->where('vigente', 0)
            ->select('id', 'objetivo', 'costo')->get();

            $objetoEstudiante = new EstudianteController();
            $gestionado=$objetoEstudiante->yaEstaGestionado($estudiante);

             //dd($gestionado);
            if($persona->computacion!==null){
                $matriculaciones=Matriculacion::where('computacion_id','=',$persona->computacion->id)->get();
                $matriculacionesVigentes=Matriculacion::join('pagos','pagos.pagable_id','=','matriculacions.id')
                        ->join('asignaturas','asignaturas.id','=','matriculacions.asignatura_id')        
                ->where('computacion_id','=',$persona->computacion->id)->where('vigente',1)
                ->select(['matriculacions.id','vigente','asignaturas.costo','asignatura','acuenta'=>\App\Support\SedeDB::table('pagos')->selectRaw('SUM(monto)')->where('pagable_type',Inscripcione::class)->where('pagable_id',1)])
                ->groupBy('matriculacions.id', 'vigente', 'costo','asignatura','acuenta')->get();
                //dd($matriculacionesVigentes);
                $matriculacionesOtras=Matriculacion::where('computacion_id','=',$persona->computacion->id)->where('vigente',0)->get();
                if($gestionado)
                    return view('inscripcione.tusinscripciones',compact('inscripciones','persona','inscripcionesVigentes','inscripcionesOtras','matriculaciones','matriculacionesVigentes','matriculacionesOtras'));
                else {
                    return redirect()->route('gestion.create',$estudiante->id);
                }
            }else{
                if($gestionado)
                    return view('inscripcione.tusinscripciones',compact('inscripciones','persona','inscripcionesVigentes','inscripcionesOtras'));
                else 
                    return redirect()->route('gestion.create',$estudiante->id);
            }    
    }

    
    public function guardarconfiguracion(Request $request,$id){
       
        $inscripcion=Inscripcione::find($id);
        $cuantas_sesiones=count($request->dias);
        $i=0;
        
        while($i<$cuantas_sesiones){
            $sesion=new Sesion();
            $sesion->horainicio=$request->horainicio[$i];
            $sesion->horafin=$request->horafin[$i];
            $sesion->dia_id=$request->dias[$i];
            $sesion->docente_id=$request->docentes[$i];
            $sesion->materia_id=$request->materias[$i];
            $sesion->aula_id=$request->aulas[$i];
            $sesion->inscripcione_id=$id;
            $sesion->save();
            $i=$i+1;
        }
            $pagos=$inscripcion->pagos();
        return redirect()->route('pagos.crear',['inscripcione'=>$inscripcion->id]);
    }

    public function actualizarConfiguracion(ActualizarConfigurarionInscripcionRequest $request)
    {
       
        $cuantas_sesiones = count($request->dias);
        $fecha=$request->fecha;
        $inscripcion_id=$request->inscripcione_id;
        Sesion::where('inscripcione_id', '=', $inscripcion_id)->delete();
        $inscripcion = Inscripcione::findOrFail($inscripcion_id);
        if($fecha<=$inscripcion->fechaini){
            $inscripcion->fechaini=$fecha;
        }
        $i = 0;
        while ($i < $cuantas_sesiones) {
            $sesion = new Sesion();
            $sesion->horainicio = $request->horainicio[$i];
            $sesion->horafin = $request->horafin[$i];
            $sesion->dia_id = $request->dias[$i];
            $sesion->docente_id = $request->docentes[$i];
            $sesion->materia_id = $request->materias[$i];
            $sesion->aula_id = $request->aulas[$i];
            $sesion->inscripcione_id = $inscripcion_id;
            $sesion->save();
            $i = $i + 1;
        }
        $inscripcion->save();
        if ($request->radioconfig=='radiodesde'){
            return redirect()->route('regenerar.programa', ['inscripcione'=>$inscripcion->id,'fecha'=>$fecha]);   
        }
        if ($request->radioconfig=='radiotodo'){
            return redirect()->route('regenerar.programa', ['inscripcione'=>$inscripcion->id,'fecha'=>$fecha,'unModo'=>'todo']);   
        }
    }


    public function actualizar_fecha_proximo_pago($programaSelected,$id){
        $programa=Programacion::findOrFail($programaSelected);

        $inscripcion=Inscripcione::findOrFail($id);
        $inscripcion->fecha_proximo_pago=$programa->fecha;
        $inscripcion->save();

        return response()->json(['mensaje'=>"Fecha proximo pago asignada correctamente"]);
    }

    public function inscripcionesVigentes($persona_id){
        $estudiante_id=Persona::findOrFail($persona_id)->estudiante->id;
        $inscripcionesVigentes=Inscripcione::
                                        where('estudiante_id','=',$estudiante_id)
                                        ->where('vigente','=',1)->get();
        $inscripcionesTodas = Inscripcione::where('estudiante_id', '=', $estudiante_id)
                                        ->get();
        
        //dd($inscripcionesVigentes->count());
        switch ($inscripcionesVigentes->count()) {
            case 0:
                return redirect()->route('listar_inscripciones',$persona_id);
                break;
            case 1:
                return redirect()->route('clases.marcado.general',$inscripcionesVigentes[0]->id);
                break;
            default:
                return redirect()->route('tus.inscripciones', $estudiante_id);
                //return redirect()->route([InscripcioneController::class, 'tusinscripciones'], ['estudiante_id' => $estudiante_id]);
                break;
        }
    }

    /** INSCIRPCIONES VIGENTES */
    public function vigentesAjax(){
 



        // $inscripcionesVigentes=Inscripcione::join('estudiantes','estudiantes.id','inscripciones.estudiante_id')
        // ->join('personas','personas.id','estudiantes.persona_id')
        // ->join('comos','comos.id','personas.como_id')
        // ->join('programacions','inscripciones.id','programacions.inscripcione_id')
        // ->where('vigente',1)
        // ->select('inscripciones.id','personas.id as persona_id','comos.como','personas.nombre','personas.apellidop','personas.apellidom')->get();
        $inscripcionesVigentes = Inscripcione::join('estudiantes', 'estudiantes.id', '=', 'inscripciones.estudiante_id')
            ->join('personas', 'personas.id', '=', 'estudiantes.persona_id')
            ->join('comos', 'comos.id', '=', 'personas.como_id')
            ->join('programacions', 'inscripciones.id', '=', 'programacions.inscripcione_id')
            ->where('vigente', 1)
            ->select('inscripciones.id', 'personas.id as persona_id', 'comos.como', 'personas.nombre', 'personas.apellidop', 'personas.apellidom')
            ->where(DB::raw('NOW()'),'<',\App\Services\SedeAccess::aggregate('programacions','MAX(fecha)','programacions.inscripcione_id','inscripciones.id'))
            ->distinct()
            ->get();

        return datatables()->of($inscripcionesVigentes)
        ->addColumn('btn', 'inscripcione.actionvigentes')
        ->rawColumns(['btn'])
        ->toJson();
    }
    public function Saldo(Request $request){
        $inscripcion=Inscripcione::findOrFail($request->inscripcion_id);
        $acuenta=$inscripcion->pagos->sum('monto');
        $costo=$inscripcion->costo;
        $saldo=$costo-$acuenta;
        $fechaHumamizado=optional($inscripcion->fecha_proximo_pago)->diffForHumans()??'Sin fecha programada';
        $data=['acuenta'=>$acuenta,'costo'=>$costo,'saldo'=>$saldo,'fechaHumamizado'=>$fechaHumamizado];
        return response()->json($data);
    }
    public function reservar($inscripcion_id){
        $inscripcion=Inscripcione::findOrFail($inscripcion_id);
        $inscripcion->estado_id=estado('RESERVADO');
        $inscripcion->save();
        //dd($inscripcion->estudiante);
        return redirect()->route("opcion.principal",$inscripcion->estudiante->persona);
    }
    public function darbaja(Request $request)
    {
        $inscripcion=Inscripcione::findOrFail($request->inscripcion_id);
        $inscripcion->vigente=0;
        $inscripcion->estado_id = estado('DESVIGENTE');
        $inscripcion->save();
        return response()->json(["mensaje"=>"Se dio de baja exitosamente"]);
    }
    public function daralta(Request $request)
    {
        //return response()->json(['e'=>2]);
        $inscripcion=Inscripcione::findOrFail($request->inscripcion_id);
        $inscripcion->vigente=1;
        $inscripcion->save();
        return response()->json(["mensaje"=>"Se dio de alta exitosamente"]);
    }

    public function fecharProximoPago(Request $request){
        $fecha_proximo_pago=$request->fecha_proximo_pago;
        $inscripcione_id=$request->inscripcione_id;
        $inscripcion=Inscripcione::findOrFail($inscripcione_id);
        $inscripcion->fecha_proximo_pago=$fecha_proximo_pago;
        $inscripcion->save();
        return response()->json($inscripcion);
    }

}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\PagoStoreRequest;
use App\Models\Inscripcione;
use App\Models\Matriculacion;
use App\Models\Pago;
use App\Models\User;
use App\Models\Aula;
use App\Models\Dia;
use App\Models\Modalidad;
use App\Models\Persona;
use App\Models\Nivel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Contracts\DataTable as DataTable; 
use Yajra\DataTables\DataTables;
use App\Http\Controllers\ProgramacionController;

/**
 * Class PagoController
 * @package App\Http\Controllers
 */
class PagoController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Listar Pagos')->only("index","detallar","show","mostrar","deudoresInscripcion","deudoresMatriculacion","deudoresView","listarPagos");
        $this->middleware('can:Reporte Pagos')->only("pagoInscripcionesView","pagoModelo");
        $this->middleware('can:Crear Pagos')->only("create","store","crear","guardar");
        $this->middleware('can:Editar Pagos')->only("edit","editar","edicion","update","actualizar");
        $this->middleware('can:Eliminar Pagos')->only("destroy");
        $this->middleware('can:Graficar Pagos')->only("graficaPorPagablestype");
    }
    
    public function index()
    {
        $pagos = Pago::all();
        return view('pago.index', compact('pagos'));
    }
    public function detallar($inscripcion_id)
    {
        $inscripcion = Inscripcione::findOrFail($inscripcion_id);
        $pagos = $inscripcion->pagos;
        $acuenta = $inscripcion->pagos->sum->monto;
        $saldo = $inscripcion->costo - $acuenta;
        return view('pago.detalle', compact('inscripcion', 'pagos', 'acuenta', 'saldo'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pago = new Pago();
        return view('pago.create', compact('pago'));
    }

    

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        request()->validate(Pago::$rules);

        $pago = Pago::create($request->all());
       
        return redirect()->route('pagos.index')
            ->with('success', 'Pago created successfully.');
    }
    public function crear($inscripcion_id)
    {
        $inscripcion=Inscripcione::findOrFail((int)$inscripcion_id);
        $pagos = $inscripcion->pagos;
        $acuenta= $inscripcion->pagos->sum->monto;
        $saldo=$inscripcion->costo-$acuenta;
        return view('pago.create', compact('inscripcion','pagos','acuenta','saldo'));
    }

    public function guardar(PagoStoreRequest $request,$inscripcion_id){
        $inscripcion=Inscripcione::findOrFail($inscripcion_id);
        $pago=new Pago();
        $pago->monto=$request->monto;
        $pago->pagocon=$request->pagocon;
        $pago->cambio=$request->cambio;
        $pago->forma_pago=$request->forma_pago;
        $pago->pagable_id=$inscripcion->id;
        $pago->pagable_type='App\Models\Inscripcione';
        $pago->save();
        //**%%%%%%%%%%%%%%%%%%%%  B  I  T  A  C  O  R  A   %%%%%%%%%%%%%%%%*/
        $pago->usuarios()->attach(Auth::user()->id);

            if($inscripcion->estado_id==estado("RESERVADO")){
                //dd("ESTADO INDEFINIDO");
                return redirect()->route('inscripcion.configuracion',$inscripcion);
            }else{
                if ($inscripcion->programaciones->count() == 0) {
                    $nivel=Modalidad::findOrFail($inscripcion->modalidad_id)->nivel->nivel;
                    if($nivel=="GUARDERIA"){
                        // dd("guarderia");
                        return redirect()->route('generar.programa.guarderia', $inscripcion->id);
                    }else {
                        // dd("FALSO DE guarderia");
                        return redirect()->route('generar.programa', $inscripcion->id);
                    }
                } else {
                    // dd("FALSO DE cripcion->programaciones->count()");
                    return redirect()->route('actualizar.programa.segun.pago', ['inscripcione' => $inscripcion->id, 'pago' => $pago->id]);
                }
            }

        //return redirect()->route('billete.crear',['pago'=>$pago]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $pago = Pago::find($id);

        return view('pago.show', compact('pago'));
    }
    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function mostrar($pago_id)
    {   
        $pago = Pago::find($pago_id);
        $billetes=$pago->billetes;
        //$user=User::find($pago->userable->user_id);
        $user=$pago->usuarios->first();
        $data=['pago'=>$pago,'user'=>$user,'billetes'=>$billetes];

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
        $pago = Pago::find($id);

        return view('pago.editar', compact('pago'));
    }
    public function editar($pago_id)
    {
        $pago = Pago::find($pago_id);
        $inscripcion=$pago->pagable; 
        return view('pago.edit',compact('pago','inscripcion'));
    }
    public function edicion(Pago $pago)
    {
        return response()->json($pago);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Pago $pago
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Pago $pago)
    {
        request()->validate(Pago::$rules);
        $pago->update($request->all());
       
        return response()->json($pago); 
    }

    public function actualizar(PagoStoreRequest $request, Pago $pago)
    {
        $pago->update($request->all());
        return redirect()->route('billete.crear', ['pago' => $pago]); 
    }


    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($pago_id)
    {
        $pago = Pago::find($pago_id);
        $pago->billetes()->detach();
        $pago->delete(); 
        
        if($pago->pagable_type=="App\\Models\\Inscripcione"){
            $objeto = new ProgramacionController();
            $objeto->actualizarProgramaSegunPago(Inscripcione::findOrFail($pago->pagable_id)->id);
        }
        if($pago->pagable_type=="App\\Models\\Matriculacion"){
            $objeto = new ProgramacioncomController();
            $objeto->actualizarProgramaSegunPagocom(Matriculacion::findOrFail($pago->pagable_id)->id);
        }
        return response()->json(['message' => 'Registro Eliminado', 'status' => 200]);
    }
    public function pagosMostrarAjax(Request $request){
        $inscripcion = Inscripcione::findOrFail($request->inscripcion_id);
        $persona=$inscripcion->estudiante->persona;
        $pagos = $inscripcion->pagos;
        $acuenta = $inscripcion->pagos->sum->monto;
        $saldo = $inscripcion->costo - $acuenta;
        // return response()->json($persona);
        $data=['pagos'=>$pagos, 'persona'=>$persona,'acuenta'=>$acuenta, 'saldo'=>$saldo,'total'=>$inscripcion->costo];
        return response()->json($data);
    }

    public function deudoresInscripcion(){
        // return response()->json(['e'=>2]);
        $deudores =Persona::join('estudiantes','estudiantes.persona_id','personas.id')
            ->join('inscripciones','inscripciones.estudiante_id','estudiantes.id')
            ->join('pagos','pagos.pagable_id','inscripciones.id')
            ->where('pagos.pagable_type',Inscripcione::class)
            ->where('inscripciones.condonado',0)
            ->where('inscripciones.costo','>',DB::raw("(SELECT sum(monto) FROM pagos WHERE pagos.pagable_id= inscripciones.id)"))
            ->select('personas.id','inscripciones.id as inscripcione_id','personas.nombre','personas.apellidop','costo','foto','telefono','fecha_proximo_pago',DB::raw("(SELECT sum(monto) FROM pagos WHERE pagos.pagable_id= inscripciones.id) as acuenta"))
            ->groupBy('personas.id','inscripcione_id','personas.nombre','personas.apellidop','acuenta','foto','telefono','fecha_proximo_pago','costo')
            ->get();
        return DataTables::of($deudores)
                ->addColumn('btn','persona.deudores.actiondeudoresinscripcion')
                ->rawColumns(['btn'])
                ->toJson();

    } 
    public function deudoresMatriculacion(){
     
        $deudorescomputacion =Persona::join('computacions','computacions.persona_id','personas.id')
        ->join('matriculacions','matriculacions.computacion_id','computacions.id')
        ->join('asignaturas','matriculacions.asignatura_id','asignaturas.id')
        ->join('pagos','pagos.pagable_id','matriculacions.id')
        ->where('pagos.pagable_type',Matriculacion::class)
        ->where('matriculacions.condonado',0)
        ->where('matriculacions.costo','>',DB::raw("(SELECT sum(monto) FROM pagos WHERE pagos.pagable_id= matriculacions.id)"))
        ->select('personas.id','matriculacions.id as matriculacion_id','asignatura','personas.nombre','personas.apellidop','costo','foto','telefono','fecha_proximo_pago',DB::raw("(SELECT sum(monto) FROM pagos WHERE pagos.pagable_id= matriculacions.id) as acuenta"))
        ->groupBy('personas.id','matriculacion_id','asignatura','personas.nombre','personas.apellidop','foto','telefono','fecha_proximo_pago','costo','acuenta')
        ->get();
        return DataTables::of($deudorescomputacion)
                ->addColumn('btn','persona.deudores.actiondeudoresmatriculacion')
                ->rawColumns(['btn'])
                ->toJson();

    } 
    public function deudoresView(){
        return view('persona.deudores.index');
    }
    public function listarPagos(){
        $pagos = Pago::join('userables','userables.userable_id','pagos.id')
                ->join('users','users.id','userables.user_id')
                ->where('userables.userable_type', "App\\Models\\Pago")
                ->select('pagos.id','monto','pagocon','cambio','forma_pago','pagos.created_at','name as user','pagos.pagable_type')
                ->get();
        return DataTables::of($pagos)
                ->addColumn('btn','pago.reportes.actionpagos')
                ->rawColumns(['btn'])
                ->toJson();
    }

    /**%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% REPORTES %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% */
    public function pagoInscripcionesMax(){
        return view('reportes.pago.pagoinscripcionesmejorado');
    } 
    public function pagoModeloMax(Request $request)
    {
        
        $fechaini = $request->fechaini; // Fecha de inicio
        $fechafin = $request->fechafin; // Fecha de fin
        
        $pagos = DB::table('pagos')
            ->join('userables', function ($join) {
                $join->on('pagos.id', '=', 'userables.userable_id')
                    ->where('userables.userable_type', '=', 'App\\Models\\Pago');
            })
            ->join('users', 'userables.user_id', '=', 'users.id')
            ->leftJoin('inscripciones', function ($join) {
                $join->on('pagos.pagable_id', '=', 'inscripciones.id')
                    ->where('pagos.pagable_type', '=', 'App\\Models\\Inscripcione');
            })
            ->leftJoin('matriculacions', function ($join) {
                $join->on('pagos.pagable_id', '=', 'matriculacions.id')
                    ->where('pagos.pagable_type', '=', 'App\\Models\\Matriculacion');
            })
            ->leftJoin('estudiantes as insc_estudiantes', 'inscripciones.estudiante_id', '=', 'insc_estudiantes.id')
            ->leftJoin('estudiantes as matri_estudiantes', 'matriculacions.computacion_id', '=', 'matri_estudiantes.id')
            ->leftJoin('personas as insc_personas', 'insc_estudiantes.persona_id', '=', 'insc_personas.id')
            ->leftJoin('personas as matri_personas', 'matri_estudiantes.persona_id', '=', 'matri_personas.id')
            ->whereBetween('pagos.created_at', [$fechaini, $fechafin])
            ->select(
                'pagos.*',
                'users.name as usuario',
                DB::raw("COALESCE(insc_personas.id, matri_personas.id) as estudiante_codigo"),
                DB::raw("COALESCE(insc_personas.nombre, matri_personas.nombre) as estudiante_nombre"),
                DB::raw("COALESCE(insc_personas.apellidop, matri_personas.apellidop) as estudiante_apellido_paterno"),
                DB::raw("COALESCE(insc_personas.apellidom, matri_personas.apellidom) as estudiante_apellido_materno")
            )
            ->get();
        
        
        // $sumaTotal = Pago::whereBetween('created_at', [$request->fechaini, $request->fechafin])->sum('monto');
        $sumaTotal = Pago::whereBetween('created_at', [$fechaini, $fechafin])->sum('monto');
    
        //dd($pagos);

        return response()->json([
            'pagos' => $pagos,
            'suma' => $sumaTotal,
            'subtotal_qr' => (float) $pagos->where('forma_pago', 'QR')->sum('monto'),
            'subtotal_efectivo' => (float) $pagos->where('forma_pago', 'EFECTIVO')->sum('monto')
        ]);
    }
    public function pagoInscripcionesMaxBnc(){
        return view('reportes.pago.pagoinscripcionesmejoradobnc');
    } 
    public function pagoModeloMaxBnc(Request $request)
    {
        
        $fechaini = $request->fechaini; // Fecha de inicio
        $fechafin = $request->fechafin; // Fecha de fin
        
        $pagos = DB::table('pagos')
            ->join('userables', function ($join) {
                $join->on('pagos.id', '=', 'userables.userable_id')
                    ->where('userables.userable_type', '=', 'App\\Models\\Pago');
            })
            ->join('users', 'userables.user_id', '=', 'users.id')
            ->leftJoin('inscripciones', function ($join) {
                $join->on('pagos.pagable_id', '=', 'inscripciones.id')
                    ->where('pagos.pagable_type', '=', 'App\\Models\\Inscripcione');
            })
            ->leftJoin('matriculacions', function ($join) {
                $join->on('pagos.pagable_id', '=', 'matriculacions.id')
                    ->where('pagos.pagable_type', '=', 'App\\Models\\Matriculacion');
            })
            ->leftJoin('estudiantes as insc_estudiantes', 'inscripciones.estudiante_id', '=', 'insc_estudiantes.id')
            ->leftJoin('estudiantes as matri_estudiantes', 'matriculacions.computacion_id', '=', 'matri_estudiantes.id')
            ->leftJoin('personas as insc_personas', 'insc_estudiantes.persona_id', '=', 'insc_personas.id')
            ->leftJoin('personas as matri_personas', 'matri_estudiantes.persona_id', '=', 'matri_personas.id')
            ->whereBetween('pagos.created_at', [$fechaini, $fechafin])
            ->select(
                'pagos.*',
                'users.name as usuario',
                DB::raw("COALESCE(insc_personas.id, matri_personas.id) as estudiante_codigo"),
                DB::raw("COALESCE(insc_personas.nombre, matri_personas.nombre) as estudiante_nombre"),
                DB::raw("COALESCE(insc_personas.apellidop, matri_personas.apellidop) as estudiante_apellido_paterno"),
                DB::raw("COALESCE(insc_personas.apellidom, matri_personas.apellidom) as estudiante_apellido_materno")
            )
            ->get();
        
        
        // $sumaTotal = Pago::whereBetween('created_at', [$request->fechaini, $request->fechafin])->sum('monto');
        $sumaTotal = Pago::whereBetween('created_at', [$fechaini, $fechafin])->sum('monto');
    
        //dd($pagos);

        return response()->json([
            'pagos' => $pagos,
            'suma' => $sumaTotal,
            'subtotal_qr' => (float) $pagos->where('forma_pago', 'QR')->sum('monto'),
            'subtotal_efectivo' => (float) $pagos->where('forma_pago', 'EFECTIVO')->sum('monto')
        ]);
    }
    
    // $pagos=Pago::join('inscripciones','inscripciones.id','pagos.pagable_id')
    //     ->join('estudiantes','estudiantes.id','inscripciones.estudiante_id')
    //     ->join('personas','personas.id','estudiantes.persona_id')
    //     ->join('modalidads','inscripciones.modalidad_id','modalidads.id')
    //     ->join('userables','userables.userable_id','pagos.id')
    //     ->join('users','users.id','userables.user_id')
    //     ->where('pagos.pagable_type','App\\Models\\'.$request->modelo)
    //     ->where('userable_type','App\\Models\\Pago')
    //     ->whereDate('pagos.created_at','<=',$request->fechafin)
    //     ->whereDate('pagos.created_at','>=',$request->fechaini)
    //     ->select('personas.id','personas.nombre','apellidop','apellidom','monto','pagos.created_at')
    //     ->orderBy('created_at',"asc")
    //     ->get();
    /**%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% REPORTES %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% */
    public function pagoInscripcionesView(){
        return view('reportes.pago.pagoinscripciones');
    } 
    public function pagoModelo(Request $request){
        // return response()->json($request->all());
        $pagos=Pago::join('inscripciones','inscripciones.id','pagos.pagable_id')
            ->join('estudiantes','estudiantes.id','inscripciones.estudiante_id')
            ->join('personas','personas.id','estudiantes.persona_id')
            ->join('modalidads','inscripciones.modalidad_id','modalidads.id')
            ->join('userables','userables.userable_id','pagos.id')
            ->join('users','users.id','userables.user_id')
            ->where('pagos.pagable_type','App\\Models\\'.$request->modelo)
            ->where('userable_type','App\\Models\\Pago')
            ->whereDate('pagos.created_at','<=',$request->fechafin)
            ->whereDate('pagos.created_at','>=',$request->fechaini)
            ->select('personas.id','personas.nombre','apellidop','apellidom','modalidad','monto','users.foto','name','pagocon','cambio','forma_pago','pagos.created_at','personas.foto as personafoto','users.id as foto_user_id')
            ->orderBy('created_at',"asc")
            ->get();
        app(\App\Services\ProfilePhotoService::class)->fillUserPhotos($pagos);
        return DataTables::of($pagos)
                ->rawColumns(['foto'])
                ->toJson();
    }

    public function graficaPorPagablestype(){
        $pagos = Pago::join('userables','userables.userable_id','pagos.id')
            ->join('users','users.id','userables.user_id')
            ->where('userables.userable_type', "App\\Models\\Pago")
            ->select('pagable_type',DB::raw('sum(monto) as Suma'))
            ->groupBy('pagable_type')
            ->get();
        return response()->json($pagos);
    }

    
    

}

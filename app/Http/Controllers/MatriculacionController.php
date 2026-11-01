<?php

namespace App\Http\Controllers;
use App\Http\Controllers\ClasecomController;

use Illuminate\Http\Request;
use App\Models\Computacion;
use App\Models\Persona;
use App\Models\Sesioncom;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Nivel;
use App\Models\Aula;
use App\Models\Dia;
use App\Models\Matriculacion;
use App\Models\Programacioncom;
use App\Models\Estado;
use App\Models\Estudiante;
use App\Models\Docente;
use App\Models\Tipomotivo;
use App\Models\Motivo;
use Illuminate\Support\Arr;
use App\Http\Requests\MatriculacionStoreRequest;
use App\Http\Requests\ActualizarConfiguracionMatriculacionRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

use Illuminate\Support\Facades\Auth;
use App\Models\User;

use Carbon\Carbon;

class MatriculacionController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Listar Matriculaciones')->only("misCarreras","show");
        $this->middleware('can:Crear Matriculaciones')->only("create","store","guardarconfiguracion");
        $this->middleware('can:Editar Matriculaciones')->only("configurarView","darbaja","daralta","edit","update","actualizarConfiguracion","actualizar_fecha_proximo_pago");
        $this->middleware('can:Eliminar Matriculaciones')->only("destroy");
    }

    
    
    public function misCarreras(Computacion $computacion)
    {
        $carreras=$computacion->carreras;
        if($carreras->count()>1){
            return view('matriculacion.carreras',compact('computacion','carreras'));
        }else{
            if($carreras->count()==0){
                return redirect()->route('configuracion.gestionar.carreras',$computacion->persona->id);
            }else{
                
                $carrera=$computacion->carreras->first();
                
                $asignaturas=$carrera->asignaturas;
                $inscritas =$computacion->matriculaciones;
                $ids=Arr::pluck($inscritas, 'asignatura_id');

                $motivos = Tipomotivo::findOrFail(2)->motivos;
                $asignaturasFaltantes=$carrera->asignaturas->whereNotIn('id', $ids);
                return view('matriculacion.create',compact('computacion','carrera','asignaturasFaltantes','motivos'));
            }
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Computacion $computacion,Carrera $carrera)
    {
        $asignaturas=$carrera->asignaturas;
        $inscritas =$computacion->matriculaciones;
        $ids=Arr::pluck($inscritas, 'asignatura_id');
        $motivos = Tipomotivo::findOrFail(2)->motivos;
        $asignaturasFaltantes=$carrera->asignaturas->whereNotIn('id', $ids);
        return view('matriculacion.create',compact('computacion','asignaturasFaltantes','carrera','motivos'));
    }
    
    public function configurarView($matriculacion_id)
    {
        $matriculacion=Matriculacion::findOrFail($matriculacion_id);
        
        
        $nivel=Nivel::findOrFail(6);
        $aulas = Aula::get();
        $docentes = Docente::with('persona', 'mododocente')
            ->where('estado_id', estado('HABILITADO'))
            ->whereHas('mododocente', function ($query) {
                $query->whereRaw('UPPER(TRIM(mododocente)) LIKE ?', ['%COMPUTACION%']);
            })
            ->orderBy('nombrecorto')
            ->get();
        $dias = Dia::get();
        $computacion=$matriculacion->computacion;
        $matriculacion->usuarios()->attach(Auth::user()->id);
        $programacioncoms=$matriculacion->programacionescom;
        $clasesConsumidas=count($programacioncoms->where('estado_id','<>',estado('INDEFINIDO')));
        $ultimaclasepasada=$programacioncoms->where('estado_id','<>',estado('INDEFINIDO'))->max();
        return view('matriculacion.configurarupdate', compact('ultimaclasepasada','programacioncoms','clasesConsumidas','computacion','matriculacion', 'aulas', 'docentes','dias'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(MatriculacionStoreRequest $request)
    {
        //dd($request->all());
        $matriculacion = new Matriculacion();
        $matriculacion->computacion_id=$request->computacion_id;
        $matriculacion->asignatura_id=$request->asignatura_id;
        $matriculacion->fechaini=$request->fechaini;
        $matriculacion->fechafin=$request->fechaini;
        $matriculacion->fecha_proximo_pago=$request->fechaini;
        $matriculacion->costo=$request->costo;
        $matriculacion->totalhoras=$request->totalhoras;
        $matriculacion->vigente=1;
        $matriculacion->condonado=0;

        $matriculacion->hacer=0;
        $matriculacion->saber=0;
        $matriculacion->decidir=0;
        $matriculacion->calificacion=0;
        
        $matriculacion->motivo_id=$request->motivo_id;
        $matriculacion->save();
        $nivel=Nivel::findOrFail(6);
        $aulas = Aula::get();
        $docentes = Docente::with('persona', 'mododocente')
            ->where('estado_id', estado('HABILITADO'))
            ->whereHas('mododocente', function ($query) {
                $query->whereRaw('UPPER(TRIM(mododocente)) LIKE ?', ['%COMPUTACION%']);
            })
            ->orderBy('nombrecorto')
            ->get();
        $dias = Dia::get();
        $computacion=$matriculacion->computacion;
        $matriculacion->usuarios()->attach(Auth::user()->id);
        $carrera=$matriculacion->asignatura->carrera;
        return view('matriculacion.configurar', compact('computacion','matriculacion','carrera','aulas', 'docentes','dias')); 
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($matriculacion_id)
    {
        $matriculacion = Matriculacion::findOrFail($matriculacion_id);
        $programacioncom = Programacioncom::join('aulas', 'programacioncoms.aula_id', '=', 'aulas.id')
        ->join('docentes', 'programacioncoms.docente_id', '=', 'docentes.id')
        ->join('personas', 'personas.id', '=', 'docentes.persona_id')
        ->select('programacioncoms.id','programacioncoms.fecha', 'horaini', 'horafin', 'horas_por_clase', 'personas.nombre', 'aulas.aula', 'programacioncoms.habilitado', 'programacioncoms.matriculacion_id')
        ->orderBy('fecha', 'asc')
        ->where('matriculacion_id', '=', $matriculacion_id)->get();
        $user=$matriculacion->usuarios->first();
        return view('matriculacion.show', compact('matriculacion','programacioncom','user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $matriculacion = Matriculacion::find($id);
        $persona=$matriculacion->computacion->persona;
        $carrera=$matriculacion->asignatura->carrera;
        $asignaturasFaltantes = $carrera->asignaturas;
        $motivos = Tipomotivo::findOrFail(2)->motivos;
        $estados=Estado::get();
        return view('matriculacion.edit', compact('matriculacion','persona','estados','asignaturasFaltantes','motivos'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $matriculacion_id)
    {
        //dd($request->all());
        $matriculacion = Matriculacion::findOrFail($matriculacion_id);
        $matriculacion->fechaini=$request->fechaini;
        $matriculacion->fechafin=$request->fechaini;
        $matriculacion->fecha_proximo_pago=$request->fechaini;
        $matriculacion->costo=$request->costo;
        $matriculacion->totalhoras=$request->totalhoras;
        $matriculacion->asignatura_id=$request->asignatura_id;
        $matriculacion->motivo_id=$request->motivo_id;

        $matriculacion->estado_id = $request->estado_id;
        $matriculacion->condonado = $request->condonado;
        $matriculacion->vigente = $request->vigente;

        $matriculacion->save();
        $nivel=Nivel::findOrFail(6);
        $aulas = Aula::get();
        $docentes = Docente::with('persona', 'mododocente')
            ->where('estado_id', estado('HABILITADO'))
            ->whereHas('mododocente', function ($query) {
                $query->whereRaw('UPPER(TRIM(mododocente)) LIKE ?', ['%COMPUTACION%']);
            })
            ->orderBy('nombrecorto')
            ->get();
        $dias = Dia::get();
        $computacion=$matriculacion->computacion;
        $programacioncoms=$matriculacion->programacionescom;
        $clasesConsumidas=count($programacioncoms->where('estado_id','<>',estado('INDEFINIDO')));
        $ultimaclasepasada=$programacioncoms->where('estado_id','<>',estado('INDEFINIDO'))->max();
        return view('matriculacion.configurarupdate', compact('ultimaclasepasada','computacion','matriculacion', 'aulas', 'docentes','dias','programacioncoms','clasesConsumidas'));
    }
    public function actualizarNotas(Request $request)
    {
        $matriculacion = Matriculacion::findOrFail($request->matriculacion_id);
        $matriculacion->ser=$request->ser;
        $matriculacion->hacer=$request->hacer;
        $matriculacion->saber=$request->saber;
        $matriculacion->decidir=$request->decidir;
        $matriculacion->calificacion=$request->ser*20/100 +$request->hacer*20/100+$request->saber*40/100+$request->decidir*20/100;
        $matriculacion->save();
        //$matriculacion->calificacion=$request->calificacion;
        return response()->json(['mensaje' => "la nota fue actualizada correctamente"]);
        // return response()->json(['mensaje' => $matriculacion]);
    }
    // public function editarNotas(Request $request)
    public function editarNotas()
    {

        $clasecom=new ClasecomController();
        //$matriculacion=Matriculacion::findOrFail($request->matriculacion_id);
        $matriculacion=Matriculacion::findOrFail(3);
        $faltas=$clasecom->CantidadDeFaltasCom($matriculacion);
        $fechaDeberiaTerminar = $matriculacion->fechafin;
        $fechaTerminaRealmente = Carbon::now();
        
        if($fechaTerminaRealmente>$fechaDeberiaTerminar){
            $atraso=$fechaTerminaRealmente->diffInDays($fechaDeberiaTerminar);
        }else{
            $atraso=(-1)*$fechaTerminaRealmente->diffInDays($fechaDeberiaTerminar);
        }

        
        
        $licencias=$clasecom->CantidadDeLicenciasCom($matriculacion);

        if($atraso>0)
            $decidir=100-$faltas*10-$atraso*10-$licencias*5;
        else {
            $decidir=100-$faltas*10-$licencias*5;
        }
        if($decidir<0){
            $decidir=0;
        }

        //return response()->json($decidir);

        $matriculacion->decidir=$decidir;
        $matriculacion->save();
        return response()->json(["matriculacion"=>$matriculacion]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $matriculacion = Matriculacion::findOrFail($id);
        $matriculacion->delete();
        return response()->json(['message' => 'Registro Eliminado']);
    }
    public function guardarconfiguracion(Request $request){
        // dd($request->all());
        $request->validate([
            'dias.*' => 'required|integer',
            'docentes.*' => 'required|integer',
            'aulas.*' => 'required|integer',
            'horainicio.*' => 'required|date_format:H:i',
            'horafin.*' => 'required|date_format:H:i',
        ]);

        $matriculacion=$request->matriculacion_id;
        $cuantas_sesiones=count($request->dias);
        $i=0;
        while($i<$cuantas_sesiones){
            $sesion=new Sesioncom();
            $sesion->horainicio=$request->horainicio[$i];
            $sesion->horafin=$request->horafin[$i];
            $sesion->dia_id=$request->dias[$i];
            $sesion->docente_id=$request->docentes[$i];
            $sesion->aula_id=$request->aulas[$i];
            $sesion->matriculacion_id=$matriculacion;
            $sesion->save();
            $i=$i+1;
        }
        //$pagos=$matriculacion->pagos();
        return redirect()->route('pagocom.crear',$matriculacion);
    }

      public function actualizarConfiguracion(ActualizarConfiguracionMatriculacionRequest $request)
    {
                $request->validate([
                        'dias.*' => 'required|integer',
                        'docentes.*' => 'required|integer',
                        'aulas.*' => 'required|integer',
                        'horainicio.*' => 'required|date_format:H:i',
                        'horafin.*' => 'required|date_format:H:i',
                ]);

        $matriculacion_id=$request->matriculacion_id;
        $cuantas_sesiones = count($request->dias);
        $fecha=$request->fecha;
        Sesioncom::where('matriculacion_id', '=', $matriculacion_id)->delete();
        
        $matriculacion = Matriculacion::findOrFail($matriculacion_id);
        if($fecha<=$matriculacion->fechaini){
            $matriculacion->fechaini=$fecha;
        }
        
        $i = 0;
        while ($i < $cuantas_sesiones) {
            $sesion = new Sesioncom();
            $sesion->horainicio = $request->horainicio[$i];
            $sesion->horafin = $request->horafin[$i];
            $sesion->dia_id = $request->dias[$i];
            $sesion->docente_id = $request->docentes[$i];
            $sesion->aula_id = $request->aulas[$i];
            $sesion->matriculacion_id = $matriculacion_id;
            $sesion->save();
            $i = $i + 1;
        }
        
        $matriculacion->save();
        if ($request->radioconfig=='radiodesde'){
            return redirect()->route('regenerar.programacioncom', ['matriculacion'=>$matriculacion->id,'fecha'=>$fecha]);   
        }
        if ($request->radioconfig=='radiotodo'){
            return redirect()->route('regenerar.programacioncom', ['matriculacion'=>$matriculacion->id,'fecha'=>$fecha,'unModo'=>'todo']);   
        }
    }

    public function actualizar_fecha_proximo_pago($programacomSelected,$id){
        $programa=Programacioncom::findOrFail($programacomSelected);
        $matriculacion=Matriculacion::findOrFail($id);
        $matriculacion->fecha_proximo_pago=$programa->fecha;
        $matriculacion->save();
        //return response()->json(['p'=>$matriculacion]);
        return response()->json(['mensaje'=>"Fecha proximo pago asignada correctamente"]);
    }

    public function matriculacionMostrarAjax(Request $request){
        $matriculacion = Matriculacion::findOrFail($request->matriculacion_id);
        // $matriculacion = Matriculacion::findOrFail(1);
        $asignatura=Asignatura::findOrFail($matriculacion->asignatura_id);
        $persona=$matriculacion->computacion->persona;
        $motivo=Motivo::findOrFail($matriculacion->motivo_id);
        
        $empezo=$matriculacion->fechaini->diffForHumans();    
        $finaliza=$matriculacion->fechafin->diffForHumans();    
        $proximo_pago=$matriculacion->fecha_proximo_pago->diffForHumans();    
        $creado=$matriculacion->created_at->diffForHumans();
        $actualizado=$matriculacion->updated_at->diffForHumans();
        $data=['matriculacion'=>$matriculacion,
                'asignatura'=>$asignatura,
                'persona'=>$persona, 
                'motivo'=>$motivo,
                'empezo'=>$empezo,
                'finaliza'=>$finaliza,
                'proximo_pago'=>$proximo_pago,
                'creado'=>$creado, 
                'actualizado'=>$actualizado];
        return response()->json($data);
    }


    public function tusMatriculacionesVigentes(Request $request){

        $computacion = Estudiante::findOrFail($request->estudiante_id)->persona->computacion;
        $matriculacionesVigentes = collect();
        if ($computacion !== null) {
            $matriculacionesVigentes = Matriculacion::join('asignaturas', 'asignaturas.id', '=', 'matriculacions.asignatura_id')
                ->join('estados', 'estados.id', 'matriculacions.estado_id')
                ->where('computacion_id', '=', $computacion->id)
                ->select('matriculacions.id', 'vigente', 'asignaturas.costo', 'asignatura', 'fecha_proximo_pago', 'estado', 'calificacion')
                ->get();
        }
        return datatables()->of($matriculacionesVigentes)
            ->addColumn('btn', 'inscripcione.actiontusmatriculacion')
            ->rawColumns(['btn'])
            ->toJson();
    }

   

    public function vigentesAjax(){
        $matriculacionesVigentes=Matriculacion::join('computacions','computacions.id','matriculacions.computacion_id')
        ->join('personas','personas.id','computacions.persona_id')
        ->join('comos','comos.id','personas.como_id')
        ->join('programacioncoms', 'matriculacions.id', '=', 'programacioncoms.matriculacion_id')
        ->where('vigente',1)
        ->select('matriculacions.id','personas.id as persona_id','comos.como','personas.nombre','personas.apellidop','personas.apellidom')
            ->where(DB::raw('NOW()'),'<',\App\Services\SedeAccess::aggregate('programacioncoms','MAX(fecha)','programacioncoms.matriculacion_id','matriculacions.id'))
        ->distinct()
        ->get();

       

        return datatables()->of($matriculacionesVigentes)
            ->addColumn('btn', 'inscripcione.actionmatriculacionesvigentes')
            ->rawColumns(['btn'])
            ->toJson();
    }
    public function Saldo(Request $request){
        $matriculacion=Matriculacion::findOrFail($request->matriculacion_id);
        $acuenta=$matriculacion->pagos->sum('monto');
        $costo=$matriculacion->costo;
        $saldo=$costo-$acuenta;
        $fechaHumamizado=$matriculacion->fecha_proximo_pago->diffForHumans();
        $data=['acuenta'=>$acuenta,'costo'=>$costo,'saldo'=>$saldo,'fechaHumamizado'=>$fechaHumamizado];
        return response()->json($data);
    }
     public function reservar($matriculacion_id){
        $matriculacion=Matriculacion::findOrFail($matriculacion_id);
        $matriculacion->estado_id=estado('RESERVADO');
        $matriculacion->save();
        return redirect()->route("opcion.principal",$matriculacion->computacion->persona->estudiante->id);
    }
    public function darbaja(Request $request)
    {
        $matriculacion=Matriculacion::findOrFail($request->matriculacion_id);
        $matriculacion->vigente=0;
        $matriculacion->save();
        return response()->json($request->all());
    }
    public function daralta(Request $request)
    {
        $matriculacion=Matriculacion::findOrFail($request->matriculacion_id);
        $matriculacion->vigente=1;
        $matriculacion->save();
        return response()->json($request->all());
    }
}



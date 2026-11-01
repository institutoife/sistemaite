<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\Administrativo;
use App\Models\Inscripcione;
use App\Models\Matriculacion;
use App\Models\User;
use App\Models\Persona;
use App\Models\Cargo;
use App\Models\Estado;
use Yajra\DataTables\Contracts\DataTable as DataTable; 
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\DeleteRequest;

class AdministrativoController extends Controller
{

    public function __construct(){
        $this->middleware('can:Administrar Cartera')->only(['miCarteraMatriculacionesDesvigentes',
                                                            'miCarteraMatriculaciones',
                                                            "miCarteraInscripcionesDesvigentes",
                                                            "vistaCartera",
                                                            "miCarteraInscripciones"
                                                        ]);
        $this->middleware('can:Contactar Administrativos')->only('contactarAdministrativos');
        $this->middleware('can:Listar Administrativos')->only('index');
        $this->middleware('can:Crear Administrativos')->only('store','crear');
        $this->middleware('can:Editar Administrativos')->only('edit','update','editar');
        $this->middleware('can:Eliminar Administrativos')->only('destroy');
    }

   
    public function index()
    {
        return view('administrativo.index');
    }

    public function crear($persona_id)
    {
        $persona=Persona::findOrFail($persona_id);
        $estados=Estado::get();
        $cargos=Cargo::get();
        return view('administrativo.create',compact('estados','cargos','persona'));
    }

    

    public function store(Request $request)
    {
        $persona=Persona::findOrFail($request->get('persona_id')); 
        if(!$persona->isAdministrativo())
            $administrativo=new Administrativo();
        else{
            $administrativo=$persona->Administrativo;
        }

        $administrativo->fechaingreso=$request->fechaingreso;
        $administrativo->diasprueba=$request->get('diasprueba');
        $administrativo->sueldo=$request->get('sueldo');
        $administrativo->estado_id=$request->get('estado_id');
        $administrativo->cargo_id=$request->get('cargo_id');
        $administrativo->persona_id=$request->get('persona_id');
        $administrativo->save();
        return redirect()->route("administrativos.index");
    }

    public function edit(Administrativo $administrativo)
    {
        $estados=Estado::get();
        $cargos=Cargo::get();
        return view('administrativo.create',compact('estados','cargos','administrativo'));
    }
    public function editar(Administrativo $administrativo)
    {
        //dd($administrativo);
        $estados=Estado::get();
        $cargos=Cargo::get();
        return view('administrativo.edit',compact('estados','cargos','administrativo'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,Administrativo $administrativo)
    {
        $administrativo->fechaingreso=$request->fechaingreso;
        $administrativo->diasprueba=$request->get('diasprueba');
        $administrativo->sueldo=$request->get('sueldo');
        $administrativo->estado_id=$request->get('estado_id');
        $administrativo->cargo_id=$request->get('cargo_id');
        $administrativo->save();
        return redirect()->route("administrativos.index");
    }

    public function destroy(Administrativo $administrativo)
    {
        $administrativo->delete();
        return response()->json(['mensaje'=>"El registro fue eliminado correctamente"]);
    }

    public function contactarAdministrativos(){
        $administrativos = Administrativo::join('personas','personas.id','administrativos.persona_id')
        ->select('administrativos.id','personas.nombre','personas.apellidop','personas.telefono')
        ->get();
        return datatables()->of($administrativos)
                ->addColumn('btn', '')
                ->rawColumns(['btn'])
                ->toJson();
    }

    public function vistaCartera(){
        return view('cartera.index');
    }
    public function miCarteraInscripciones(){
        $userActual = Auth::user();
        $inscripciones= Inscripcione::join('userables','userables.userable_id','inscripciones.id')
            ->join('estudiantes','inscripciones.estudiante_id','estudiantes.id')
            ->join('modalidads','modalidads.id','inscripciones.modalidad_id')
            ->join('personas','personas.id','estudiantes.persona_id')
            ->join('users','users.id','userables.user_id')
            ->where('users.id', $userActual->id)
            ->where('inscripciones.vigente',1)
            ->where('userables.userable_type',Inscripcione::class)
            ->select('inscripciones.id','personas.nombre','modalidads.modalidad','inscripciones.costo','fecha_proximo_pago')
            ->get();
        
            return datatables()->of($inscripciones)
                ->addColumn('btn', 'cartera.actioninscripciones')
                ->rawColumns(['btn'])
                ->toJson();
    }
    public function miCarteraInscripcionesDesvigentes(){
        $userActual = Auth::user();
        
        $inscripciones= Inscripcione::join('userables','userables.userable_id','inscripciones.id')
        ->join('estudiantes','inscripciones.estudiante_id','estudiantes.id')
        ->join('personas','personas.id','estudiantes.persona_id')
        ->join('users','users.id','userables.user_id')
        ->where('users.id', $userActual->id)
        ->where('vigente',0)
        ->where('userables.userable_type',Inscripcione::class)
        ->select('personas.id','nombre','apellidop','apellidom','vuelvefecha','volvera')
        ->groupBy('personas.id','nombre','apellidop','apellidom','vuelvefecha','volvera')
        ->get();
        return datatables()->of($inscripciones)
                ->addColumn('btn', 'cartera.actioninscripcionesdesvigentes')
                ->rawColumns(['btn'])
                ->toJson();
        
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

            return datatables()->of($inscripciones)
                ->addColumn('btn', 'cartera.actioninscripcionesdesvigentes')
                ->rawColumns(['btn'])
                ->toJson();
    }
    public function miCarteraMatriculaciones(){
        $userActual = Auth::user();
        $matriculaciones=Matriculacion::join('userables','userables.userable_id','matriculacions.id')
        ->join('computacions','matriculacions.computacion_id','computacions.id')
        ->join('asignaturas','asignaturas.id','matriculacions.asignatura_id')
        ->join('personas','personas.id','computacions.persona_id')
        ->join('users','users.id','userables.user_id')
        ->where('users.id', $userActual->id)
        ->where('vigente',1)
        ->where('userables.userable_type',Matriculacion::class)
        ->select('matriculacions.id','personas.nombre','asignaturas.asignatura','matriculacions.costo','fecha_proximo_pago')
        ->get();
    
        return datatables()->of($matriculaciones)
                ->addColumn('btn', 'cartera.actionmatriculacion')
                ->rawColumns(['btn'])
                ->toJson();
    }
    public function miCarteraMatriculacionesDesvigentes(){
        $userActual = Auth::user();
        $personas=Persona::join('computacions','computacions.persona_id','personas.id')
                ->join('matriculacions','matriculacions.computacion_id','computacions.id')
                ->join('userables','userables.userable_id','matriculacions.id')
                ->join('users','users.id','userables.user_id')
                ->where('users.id', $userActual->id)
                ->where('matriculacions.vigente', 0)
                ->select('personas.id','nombre','apellidop','apellidom','vuelvefecha','volvera')
                ->groupBy('personas.id','nombre','apellidop','apellidom','vuelvefecha','volvera')
                ->get();

        return datatables()->of($personas)
                ->addColumn('btn', 'cartera.actionmatriculaciondesvigentes')
                ->rawColumns(['btn'])
                ->toJson();
    }

}

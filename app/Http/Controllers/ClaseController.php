<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Config;

use App\Models\Aula;
use SweetAlert;
use RealRashid\SweetAlert\Facades\Alert;
use App\Models\Inscripcione;
use App\Models\Clase;
use App\Models\Docente;
use App\Models\Materia;
use App\Models\Programacion;
use App\Models\Tema;
use App\Models\Nivel;
use App\Models\User;
use App\Models\Modalidad;
use App\Models\Observacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\ToSweetAlert;
use UxWeb\SweetAlert\SweetAlert as SweetAlertSweetAlert;

use Yajra\DataTables\Contracts\DataTable as DataTable; 
use Yajra\DataTables\DataTables;

/**
 * Class ClaseController
 * @package App\Http\Controllers
 */
class ClaseController extends Controller
{

    public function __construct()
    {
        $this->middleware('can:Listar Clases')->only('index','clasesPresentes','mostrar','show');
        $this->middleware('can:Crear Clases')->only('create','crear','guardar');
        $this->middleware('can:Editar Clases')->only('edit','update','actualizar','editar','marcadoGeneral','marcadoRapido');
        $this->middleware('can:Eliminar Clases')->only('destroy');
    }
    public function index()
    {
        $clases = Clase::paginate();

        return view('clase.index', compact('clases'))
            ->with('i', (request()->input('page', 1) - 1) * $clases->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $clase = new Clase();
        return view('clase.create', compact('clase'));
    }
    public function crear()
    {
        return view('clase.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */

    
    

    public function guardar(Request $request,$programacion_id)
    {
        $programa=Programacion::findOrFail($programacion_id);
        $clase=app(\App\Services\ClassAttendanceService::class)->record($programa,$request->all(),$request->user());
        return $request->expectsJson() ? response()->json(['message'=>'Asistencia registrada.','id'=>$clase->id]) : redirect()->route('clase.presentes');
    }
    
    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $clase = Clase::find($id);
        return view('clase.show', compact('clase'));
    }
    public function mostrar(Request $request)
    {
        
        $clase = Clase::join('materias','clases.materia_id','materias.id')
                        ->join('aulas', 'clases.aula_id', 'aulas.id')
                        ->leftJoin('temas', 'clases.tema_id', 'temas.id')
                        ->join('docentes', 'clases.docente_id', 'docentes.id')
                        ->join('personas', 'docentes.persona_id', 'personas.id')
                        ->join('estados', 'clases.estado_id', 'estados.id')
                        ->where('clases.id',$request->id)
                        ->select('clases.id','fecha','estados.estado','horainicio','horafin','personas.nombre'
                                ,'personas.apellidop','personas.apellidom','personas.foto','materias.materia','aulas.aula','temas.tema',
                            'clases.created_at','clases.updated_at')->get()->first();

        $user=Clase::findOrFail($request->id)->usuarios->first();
        $data=['clase'=>$clase,'user'=>$user];
        return response()->json($data);
    }

    ///*  clase edu */
    public function editar(Request $request){
        
        $clase=Clase::findOrFail($request->id);
        $docentes = Docente::join('personas', 'personas.id', '=', 'docentes.persona_id')
        ->where('docentes.estado_id', estado('HABILITADO'))
            ->select('docentes.id', 'personas.nombre','docentes.nombrecorto', 'personas.apellidop')
            ->get();
        //dd($docentes);
        $programa = $clase->programacion;
        $inscripcion = $programa->inscripcione;
        $nivel=Nivel::findOrFail(Modalidad::findOrFail($inscripcion->modalidad_id)->nivel_id);
        $materias = $nivel->materias;
        $aulas = Aula::all();
        $temas = Tema::all();
        $data=['clase'=>$clase,'docentes'=>$docentes,'programa'=>$programa,'inscripcion'=>$inscripcion,'materias'=>$materias,'aulas'=>$aulas,'temas'=>$temas];

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
        
        $clase = Clase::find($id);
        $docentes = Docente::join('personas', 'personas.id', '=', 'docentes.persona_id')
        ->where('docentes.estado', '=', 'activo')
            ->select('docentes.id', 'personas.nombre', 'personas.apellidop')
            ->get();
        $programa=$clase->programacion;
        $inscripcion=$programa->inscripcione;
        
        $materias=Materia::join('sesions','sesions.materia_id','=','materias.id')
                            ->where('sesions.inscripcione_id',$inscripcion->id)
                            ->get();
        $aulas = Aula::all();

        $hora_inicio = $clase->hora_inicio;
        $hora_fin = $clase->hora_fin;
   

/** enviar los datos a la ventana edicion de clases */


        //return view('clase.edit', compact('clase'));
        return view('clase.edit', compact('docentes','clase', 'programa', 'inscripcion', 'materias', 'aulas', 'hora_inicio', 'hora_fin'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Clase $clase
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Clase $clase)
    {
        app(\App\Services\ClassAttendanceService::class)->protectHistory($clase->programacion);
        if($request->filled('programacion_id') && (int)$request->programacion_id !== (int)$clase->programacion_id) {
            app(\App\Services\ClassAttendanceService::class)->protectHistory(Programacion::findOrFail($request->programacion_id));
        }
        $clase->update($request->all());
        return redirect()->route('clases.index')
            ->with('success', 'Clase updated successfully');
    }
    public function actualizar(Request $request, Clase $clase)
    {
        app(\App\Services\ClassAttendanceService::class)->protectHistory($clase->programacion);
        //return response()->json($clase);

        $clase->aula_id=$request->aula_id;
        $clase->docente_id=$request->docente_id;
        $clase->estado_id=estado('PRESENTE');
        $clase->fecha =$request->fecha;
        $clase->horafin=$request->horafin;
        $clase->horainicio=$request->horainicio;
        $clase->materia_id=$request->materia_id;
        $clase->tema_id=$request->tema_id;
        $clase->save();
        return response()->json(['mensaje'=>'La clase ha sido actualizada correctamente']);
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $clase = Clase::findOrFail($id);
        app(\App\Services\ClassAttendanceService::class)->protectHistory($clase->programacion);
        $clase->delete();
        return redirect()->route('clases.index')
            ->with('success', 'Clase deleted successfully');
    }
    // public function marcadoGeneral($inscripcion_id){
    public function marcadoGeneral($inscripcion_id){
        //dd($inscripcion);
        $inscripcion=Inscripcione::findOrFail($inscripcion_id);
        // $programaciones=Programacion::where('inscripcione_id', '=', $inscripcion_id)->get();
        $programaciones=$inscripcion->programaciones;
        $programacionesHoy = Programacion::join('inscripciones', 'inscripciones.id', '=', 'programacions.inscripcione_id') //ok
        ->join('sesions', 'sesions.inscripcione_id', '=', 'inscripciones.id')
        ->join('materias', 'sesions.materia_id', '=', 'materias.id')
        ->join('dias', 'dias.id', '=', 'sesions.dia_id')
        ->join('aulas', 'sesions.aula_id', '=', 'aulas.id')
        ->join('docentes', 'sesions.docente_id', '=', 'docentes.id')
        ->join('personas', 'docentes.persona_id', '=', 'personas.id')
        ->join('estados', 'estados.id', '=', 'programacions.estado_id')
        // ->where('inscripciones.id', '=', $inscripcion_id)
        ->where('inscripciones.id', '=', $inscripcion->id)
        ->where('programacions.fecha', '=',DB::raw('date(now())'))
            ->where('dias.id', '=', DB::raw("DAYOFWEEK(programacions.fecha)-1"))
        ->select('programacions.id', 'programacions.fecha', 'estados.estado', 'hora_ini', 'hora_fin', 'programacions.habilitado', 'personas.nombre', 'materia','programacions.docente_id','programacions.materia_id')
        ->get();
        
        //dd($inscripcion);
        $dias_que_faltan_para_pagar= $inscripcion->fecha_proximo_pago ? $inscripcion->fecha_proximo_pago->diffInDays(now()) : 0;
        
        $pago=$inscripcion->pagos->sum('monto');
        $indefinido=$inscripcion->programaciones->where('estado_id',estado('INDEFINIDO'))->count();
        $presentes = $inscripcion->programaciones->where('estado_id',estado('FINALIZADO'))->count();
        $faltas = $inscripcion->programaciones->where('estado_id',estado('FALTA'))->count();
        $congelados = $inscripcion->programaciones->where('estado_id',estado('CONGELADO'))->count();
        $licencias = $inscripcion->programaciones->where('estado_id',estado('LICENCIA'))->count();
        $persona = $inscripcion->estudiante->persona;
    
        $attendanceTab=app(\App\Services\ClassAttendanceView::class)->tab('apoyo',$inscripcion);
        $presentes=$attendanceTab['stats']['Asistidas']; $faltas=$attendanceTab['stats']['Faltas']; $licencias=$attendanceTab['stats']['Licencias'];
        return view('programacion.marcadoGeneral',compact('attendanceTab','programaciones','persona','faltas', 'presentes', 'licencias', 'pago', 'inscripcion', 'dias_que_faltan_para_pagar'));
        //return redirect()->route('clases.marcado.general',$inscripcion_id)->with('programaciones', 'programacionesHoy', 'faltas', 'presentes', 'licencias', 'pago', 'inscripcion','dias_que_faltan_para_pagar');
    }
    
    public function marcadoRapido($programacion_id)
    {
        $programa=Programacion::findOrFail($programacion_id);
        $data=['fecha'=>$programa->fecha->format('Y-m-d'), 'horainicio'=>now()->format('H:i'),
            'horafin'=>now()->addMinutes($programa->hora_ini->diffInMinutes($programa->hora_fin))->format('H:i'),
            'docente_id'=>$programa->docente_id,'aula_id'=>$programa->aula_id];
        $data['materia_id']=$programa->materia_id;
        app(\App\Services\ClassAttendanceService::class)->record($programa,$data,auth()->user());
        return redirect()->route('clase.presentes');
    }
    public function clasesPresentes(Request $request)
    {
        $rows=app(\App\Services\PresentAttendanceService::class)->rows($request->user(), 'apoyo');
        return datatables()->of($rows)->rawColumns(['btn'])->toJson();
    }

    public function finalizarClase(Request $request)
    {
        $request->validate(['id'=>'required|integer']);
        app(\App\Services\ClassAttendanceService::class)->finish(Clase::findOrFail($request->id),$request->user());
        return response()->json(['message'=>'Salida registrada.','status'=>200]);
    }
    
}

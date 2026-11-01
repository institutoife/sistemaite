<?php

namespace App\Http\Controllers;

use App\Models\Colegio;
use Illuminate\Http\Request;
use App\Models\Grado;
use App\Models\Gestion;
use App\Models\Estudiante;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Http\Requests\GestionStoreRequest;
use App\Http\Requests\GestionUpdateRequest;


class GestionController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('can:Listar Archivos')->only("index");
        $this->middleware('can:Crear Archivos')->only("create","store");
        $this->middleware('can:Editar Archivos')->only("editar","actualizar");
        $this->middleware('can:Eliminar Archivos')->only("destroy");
    }

    public function index($estudiante_id)
    {
        $estudiante = Estudiante::findOrFail($estudiante_id);
        if (!is_null($estudiante->grados()->first())) {
            $anioUltimo = $estudiante->grados()->orderBy('anio', 'desc')->get()->first()->pivot->anio;
        }

        $colegios = Colegio::all();
        $objetoGrado = new GradoController();
        $grados = $objetoGrado->gradosAunNoCursados($estudiante_id);
        $gestiones = Estudiante::join('estudiante_grado', 'estudiantes.id', '=', 'estudiante_grado.estudiante_id')
        ->join('grados', 'grados.id', '=', 'estudiante_grado.grado_id')
        ->join('colegios', 'colegios.id', '=', 'estudiante_grado.colegio_id')
        ->where('estudiante_id', '=', $estudiante_id)
            ->select('estudiante_grado.id', 'colegio_id', 'colegios.nombre', 'grados.grado', 'anio')
            ->orderBy('anio', 'desc')
            ->get();

        //dd($estudiante);
        return view('gestion.index', compact('estudiante', 'grados', 'colegios', 'gestiones'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Estudiante $estudiante)
    {
        //dd($estudiante);
        $colegios = Colegio::all();
        $grados = Grado::all();
        
        $gestion = Estudiante::join('estudiante_grado', 'estudiantes.id', '=', 'estudiante_grado.estudiante_id')
        ->join('grados', 'grados.id', '=', 'estudiante_grado.grado_id')
        ->join('colegios', 'colegios.id', '=', 'estudiante_grado.colegio_id')
        ->where('estudiante_id', '=', $estudiante->id)
        ->select('colegio_id', 'colegios.nombre', 'grados.grado', 'anio')
        ->orderBy('anio', 'desc')
        ->get()->first();

        return view('gestion.create',compact('estudiante','colegios','grados','gestion'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(GestionStoreRequest $request)
    {
        //dd($request->all());
        $estudiante = Estudiante::findOrFail($request->estudiante_id);
        $estudiante->grados()->attach($request->grado_id, ['colegio_id' => $request->colegio_id, 'anio' => $request->anio]);
        $grados = Grado::whereNotIn('grado', $estudiante->grados->pluck('grado'))->get();
        if($request->anio==Carbon::now()->isoFormat('Y')){
            return redirect()->route('telefonos.persona',$estudiante->persona);
        }else{
            return redirect()->route('gestion.index',$estudiante->id);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function editar(Request $request)
    {
        $gestion = Gestion::where('id',$request->id_gestion)->get();
        $colegios = Colegio::all();
        $grados = Grado::all();
        $data=['gestion'=>$gestion,'grados'=>$grados,'colegios'=>$colegios];
        return response()->json($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function actualizar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'grado_id' => ['required'],  
            'colegio_id' => ['required'],
            'anio' => ['required'],
        ]);
        if ($validator->passes()) {
            $gestion=Gestion::findOrFail($request->gestion_id);
            $gestion->colegio_id=$request->colegio_id;
            $gestion->grado_id=$request->grado_id;
            $gestion->anio=$request->anio;
            $gestion->save();
            return response()->json($gestion);
        } else {
            return response()->json(['error' => $validator->errors()->all()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Gestion $gestion)
    {
        $gestion->delete();
        return response()->json(['mensaje' =>"El registro fue eliminado correctamente"]);
    }
}

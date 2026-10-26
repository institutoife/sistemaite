<?php

namespace App\Http\Controllers;

use App\Models\Prospecto;
use App\Models\Observacion;
use App\Http\Requests\StoreProspectoRequest;
use App\Http\Requests\UpdateProspectoRequest;
use Yajra\DataTables\Contracts\DataTable as DataTable; 
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use App\Models\Estado;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class ProspectoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('prospecto.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $estados=Estado::get();
        return view('prospecto.create',compact('estados'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreProspectoRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreProspectoRequest $request)
    {
        
        $prospecto = new Prospecto();
        $prospecto->telefono=$request->telefono;
        $prospecto->estado_id=$request->estado_id;
        $prospecto->save();
        $observacion=new Observacion();
        $observacion->observacion="Prospecto a vender";
        $observacion->activo=1;
        $observacion->observable_id=$prospecto->id;
        $observacion->observable_type="App\Models\Prospecto";
        $observacion->save();
                //**%%%%%%%%%%%%%%%%%%%%  B  I  T  A  C  O  R  A  O B S E R V A C I O N   %%%%%%%%%%%%%%%%*/
        $observacion->usuarios()->attach(Auth::user()->id);

        return view('prospecto.index', compact('prospecto'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Prospecto  $prospecto
     * @return \Illuminate\Http\Response
     */
    public function show(Prospecto $prospecto)
    {
        $observaciones=$prospecto->observaciones;
        return view('prospecto.show',compact('observaciones','prospecto'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Prospecto  $prospecto
     * @return \Illuminate\Http\Response
     */
    public function edit(Prospecto $prospecto)
    {
        $estados=Estado::get();
        return view('prospecto.edit', compact('prospecto','estados'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateProspectoRequest  $request
     * @param  \App\Models\Prospecto  $prospecto
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateProspectoRequest $request, Prospecto $prospecto)
    {
        $prospecto->telefono=$request->telefono;
        if ($request->has('estado_id')) {
            $prospecto->estado_id = $request->estado_id;
        }
        $prospecto->save();
        return redirect()->route('prospecto.view', $prospecto);
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Prospecto  $prospecto
     * @return \Illuminate\Http\Response
     */
    public function destroy(Prospecto $prospecto)
    {
        $prospecto->delete();
        return response()->json(['mensaje' => 'Registro Eliminado correctamente']);
    }
    public function listar()
    {
        // $prospectos = Prospecto::join('estados', 'estados.id', '=', 'prospectos.estado_id')
        // ->join('observacions', function($join) {
        //     $join->on('observacions.observable_id', '=', 'prospectos.id')
        //         ->where('observacions.observable_type', '=', 'App\Models\Prospecto');
        // })
        // ->select('prospectos.id','observacions.id as observacion_id', 'telefono', 'estados.estado', DB::raw('MAX(observacions.updated_at) as ultima_observacion'))
        // ->groupBy('prospectos.id','observacion_id', 'telefono', 'estados.estado')
        // ->get();
        $prospectos = Prospecto::join('estados', 'estados.id', '=', 'prospectos.estado_id')
  
        ->join('observacions', function($join) {
            $join->on('observacions.observable_id', '=', 'prospectos.id')
                ->where('observacions.observable_type', '=', 'App\Models\Prospecto');
        })
  		->join('userables','userable_id','observacions.id')
  		->join('users','users.id','userables.user_id')
  		->where('userable_type','App\Models\Observacion')
        ->select('prospectos.id','name', 'telefono', 'estados.estado', DB::raw('MAX(observacions.updated_at) as ultima_observacion'))
        ->groupBy('prospectos.id','name', 'telefono', 'estados.estado')
        ->get();


        return datatables()->of($prospectos)
        ->addColumn('btn', 'prospecto.action')
        ->rawColumns(['btn'])
        ->toJson();
    }
}

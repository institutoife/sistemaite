<?php

namespace App\Http\Controllers;

use App\Models\Constante;
use App\Http\Requests\StoreConstanteRequest;
use App\Http\Requests\UpdateConstanteRequest;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Contracts\DataTable as DataTable; 
use Yajra\DataTables\DataTables;

class ConstanteController extends Controller
{

    public function __construct()
    {
        $this->middleware('can:Listar Constantes')->only('index','show');
        $this->middleware('can:Crear Constantes')->only('create','store');
        $this->middleware('can:Editar Constantes')->only('edit','update');
        $this->middleware('can:Eliminar Constantes')->only('destroy');
    }

    public function index()
    {
        return view('constante.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('constante.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreConstanteRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreConstanteRequest $request)
    {
        $validated = $this->datosSegunColumnas($request->validated());
        $constante = new Constante($validated);
        $constante->save();
        return redirect()->route('constante.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Constante  $constante
     * @return \Illuminate\Http\Response
     */
    public function show(Constante $constante)
    {
        return view('constante.show', compact('constante'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Constante  $constante
     * @return \Illuminate\Http\Response
     */
    public function edit(Constante $constante)
    {
        // dd($constante);
        return view('constante.edit', compact('constante'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateConstanteRequest  $request
     * @param  \App\Models\Constante  $constante
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateConstanteRequest $request, Constante $constante)
    {
        $validated = $this->datosSegunColumnas($request->validated());
        $constante->update($validated);
        return redirect()->route('constante.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Constante  $constante
     * @return \Illuminate\Http\Response
     */
    public function destroy(Constante $constante)
    {
        $constante->delete();
        return response()->json(['mensaje'=>"El registro fue eliminado correctamente"]);
    }
    public function listar(){
        $constantes = Constante::select('id', 'cuenta', 'plataforma', 'clave', 'descripcion', 'created_at')->get();
        return datatables()->of($constantes)
            ->addColumn('btn', 'constante.action')
            ->rawColumns(['btn'])
            ->toJson();
    }

    private function datosSegunColumnas(array $datos): array
    {
        if (!Schema::hasColumn('constantes', 'constante')) {
            unset($datos['constante']);
        }

        if (!Schema::hasColumn('constantes', 'valor')) {
            unset($datos['valor']);
        }

        return $datos;
    }
}

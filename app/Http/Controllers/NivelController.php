<?php

namespace App\Http\Controllers;

use App\Models\Nivel;
use App\Models\Modalidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

/**
 * Class NivelController
 * @package App\Http\Controllers
 */
use App\Http\Requests\NivelStoreRequest;
use App\Http\Requests\NivelUpdateRequest;

class NivelController extends Controller
{
    public function __construct()
    {
        //$this->middleware('can:Listar Niveles')->only('index','show');
        $this->middleware('can:Crear Niveles')->only('create','store');
        $this->middleware('can:Editar Niveles')->only('edit','update');
        $this->middleware('can:Eliminar Niveles')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $nivels = Nivel::all();

        return view('nivel.index', compact('nivels'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $nivel = new Nivel();
        return view('nivel.create', compact('nivel'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(NivelStoreRequest $request)
    {
        request()->validate(Nivel::$rules);

        $nivel = Nivel::create($request->all());
        $nivel->usuarios()->attach(Auth::user()->id);
        return redirect()->route('nivels.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Nivel $nivel)
    {
        //$nivel = Nivel::find($id);
        //$user=$nivel->usuarios->first();
        $modalidades=Modalidad::where('nivel_id',$nivel->id)->get();
        // dd($modalidades);
        return view('home.public.level', compact('nivel', 'modalidades'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $nivel = Nivel::find($id);

        return view('nivel.edit', compact('nivel'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Nivel $nivel
     * @return \Illuminate\Http\Response
     */
    public function update(NivelUpdateRequest $request, Nivel $nivel)
    {
       //request()->validate(Nivel::$rules);
        $nivel->update($request->all());
        return redirect()->route('nivels.index');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(Nivel $nivel)
    {
        $nivel->delete();
        return response()->json(['mensaje' => 'Registro Eliminado']);
    }
    public function destroyer()
    {   
        $id=8;
        $nivel = Nivel::findOrFail($id);
        $nivel->delete();
        // return response()->json(['id'=>$nivel]);
        return response()->json(['message' => 'Registro Eliminado', 'status' => 200]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Interest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests\InterestGuardarRequest;

class InterestController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Listar Intereses')->only('index','mostrar');
        $this->middleware('can:Crear Intereses')->only('create','store');
        $this->middleware('can:Editar Intereses')->only('editar','actualizar');
        $this->middleware('can:Eliminar Intereses')->only('destroy');
    }

    public function index()
    {
        return view('interest.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('interest.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(InterestGuardarRequest $request)
    {
        $interest = new interest();
        $interest->interest = $request->interest;
        $interest->descripcion = $request->descripcion;
        $interest->save();
        $interest->usuarios()->attach(Auth::user()->id);
        return redirect()->route('interest.index')
            ->with('success', 'Motivo created successfully.');
    }

    
    public function mostrar(Request $request)
    {
        $interest = Interest::findOrFail($request->id);
        $user = $interest->usuarios->first();
        $data=['interest'=>$interest,'user' => $user];
        return response()->json($data);
    }

    
    
    public function show(Interest $interest)
    {
        $observaciones=$interest->observaciones;
        return view('home.public.interest', compact('observaciones', 'interest'));
    }

    

    public function editar(Request $request)
    {
        $interest = Interest::find($request->id);
        return response()->json($interest);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Interest  $interest
     * @return \Illuminate\Http\Response
     */
     public function actualizar(Request $request)
    {
        $interest = Interest::findOrFail($request->id);
        $validator = Validator::make($request->all(), [
            'interest' => ['required','min:5','max:30',Rule::unique('interests', 'interest')->ignore($interest)],
            'descripcion' => ['required','min:5'],
        ]);

        if ($validator->passes()) {
            $interest = Interest::findOrFail($request->id);
            $interest->interest = $request->interest;
            $interest->descripcion = $request->descripcion;
            $interest->save();
            return response()->json(['interest'=>$interest]);
        }else{
            return response()->json(['errores' => $validator->errors()]);
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Interest  $interest
     * @return \Illuminate\Http\Response
     */
    public function destroy(Interest $interest)
    {
        $interest->delete();
        return response()->json(['mensaje'=>"Se elimino correctamente"]);
    }

    public function listar(){
        return datatables()->of(Interest::get())
        ->addColumn('btn', 'interest.action')
        ->rawColumns(['btn'])
        ->toJson();
    }
    public function getParaHome(){
        $interests=Interest::get();
        $data=['interests'=>$interests];
        return response()->json($data);
    }
}

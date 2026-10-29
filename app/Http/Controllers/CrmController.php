<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Persona;
use App\Models\Estado;

use Yajra\DataTables\Contracts\DataTable as DataTable; 
use Yajra\DataTables\DataTables;

class CrmController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return redirect()->route('crm.tablero');
    }
    public function esperaNuevoView(){

        $estados=Estado::get();
        return view("crm.esperanuevos",compact("estados"));
    }
    /*  ESPERANUEVO	 */
    public function esperanuevo(){
        $potenciales= Persona::join('interest_persona','interest_persona.persona_id','personas.id')
        ->join('interests','interests.id','interest_persona.interest_id')
        ->where('habilitado',estado("ESPERANUEVO"))
        ->select('personas.id','personas.nombre','personas.apellidop','apellidom','interests.interest','volvera','vuelvefecha')  
        ->get();

        return DataTables::of($potenciales)
                ->addColumn('btn','persona.rapidingo.actionpotenciales')
                ->rawColumns(['btn'])
                ->toJson();
    }
    public function potencialesParametrizada(Request $request){
        $potenciales= Persona::join('interest_persona','interest_persona.persona_id','personas.id')
        ->join('interests','interests.id','interest_persona.interest_id')
        ->where('habilitado',estado($request->estado))
        ->select('personas.id','personas.nombre','personas.apellidop','apellidom','interests.interest','volvera','vuelvefecha')  
        ->get();

        return DataTables::of($potenciales)
                ->addColumn('btn','persona.rapidingo.actionpotenciales')
                ->rawColumns(['btn'])
                ->toJson();

    }


    /*  ESPERAREINCRIPCION	 */
    public function esperarescription(){
        $potenciales= Persona::join('interest_persona','interest_persona.persona_id','personas.id')
        ->join('interests','interests.id','interest_persona.interest_id')
        ->where('habilitado',estado("ESPERAREINSCRIPCION"))
        ->select('personas.id','personas.nombre','personas.apellidop','apellidom','interests.interest','volvera','vuelvefecha')  
        ->get();

        return DataTables::of($potenciales)
                ->addColumn('btn','crm.actionesperarescription')
                ->rawColumns(['btn'])
                ->toJson();
    }
    /*  ESPERAREMATRICULACION	 */
    public function rematriculacion(){
        $potenciales= Persona::join('interest_persona','interest_persona.persona_id','personas.id')
        ->join('interests','interests.id','interest_persona.interest_id')
        ->where('habilitado',estado("ESPERAREMATRICULACION"))
        ->select('personas.id','personas.nombre','personas.apellidop','apellidom','interests.interest','volvera','vuelvefecha')  
        ->get();

        return DataTables::of($potenciales)
                ->addColumn('btn','persona.rapidingo.actionpotenciales')
                ->rawColumns(['btn'])
                ->toJson();
    }
    
    /*  ESPERARETOMA	 */
    public function esperaretoma(){
        $potenciales= Persona::join('interest_persona','interest_persona.persona_id','personas.id')
        ->join('interests','interests.id','interest_persona.interest_id')
        ->where('habilitado',estado("ESPERARETOMA"))
        ->select('personas.id','personas.nombre','personas.apellidop','apellidom','interests.interest','volvera','vuelvefecha')  
        ->get();

        return DataTables::of($potenciales)
                ->addColumn('btn','persona.rapidingo.actionpotenciales')
                ->rawColumns(['btn'])
                ->toJson();
    }
    /*  SEGUIMIENTO	 */
    public function seguimiento(){
        $potenciales= Persona::join('interest_persona','interest_persona.persona_id','personas.id')
        ->join('interests','interests.id','interest_persona.interest_id')
        ->where('habilitado',estado("SEGUIMIENTO"))
        ->select('personas.id','personas.nombre','personas.apellidop','apellidom','interests.interest','volvera','vuelvefecha')  
        ->get();

        return DataTables::of($potenciales)
                ->addColumn('btn','persona.rapidingo.actionpotenciales')
                ->rawColumns(['btn'])
                ->toJson();
    }
    /*  PROSPECTO	 */
    public function prospecto(){
        $potenciales= Persona::join('interest_persona','interest_persona.persona_id','personas.id')
        ->join('interests','interests.id','interest_persona.interest_id')
        ->where('habilitado',estado("PROSPECTO"))
        ->select('personas.id','personas.nombre','personas.apellidop','apellidom','interests.interest','volvera','vuelvefecha')  
        ->get();

        return DataTables::of($potenciales)
                ->addColumn('btn','persona.rapidingo.actionpotenciales')
                ->rawColumns(['btn'])
                ->toJson();
    }
    /*  CANCELADO	 */
    public function cancelado(){
        $potenciales= Persona::join('interest_persona','interest_persona.persona_id','personas.id')
        ->join('interests','interests.id','interest_persona.interest_id')
        ->where('habilitado',estado("CANCELADO"))
        ->select('personas.id','personas.nombre','personas.apellidop','apellidom','interests.interest','volvera','vuelvefecha')  
        ->get();

        return DataTables::of($potenciales)
                ->addColumn('btn','persona.rapidingo.actionpotenciales')
                ->rawColumns(['btn'])
                ->toJson();
    }
    /*  PERDIDO	 */
    public function perdido(){
        $potenciales= Persona::join('interest_persona','interest_persona.persona_id','personas.id')
        ->join('interests','interests.id','interest_persona.interest_id')
        ->where('habilitado',estado("PERDIDO"))
        ->select('personas.id','personas.nombre','personas.apellidop','apellidom','interests.interest','volvera','vuelvefecha')  
        ->get();

        return DataTables::of($potenciales)
                ->addColumn('btn','persona.rapidingo.actionpotenciales')
                ->rawColumns(['btn'])
                ->toJson();
    }
}

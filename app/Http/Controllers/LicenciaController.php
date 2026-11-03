<?php

namespace App\Http\Controllers;

use App\Models\Licencia;
use App\Models\Programacioncom;
use App\Models\Programacion;
use App\Models\Tipomotivo;
use App\Models\Motivo;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

use Illuminate\Support\Facades\Config;
use Yajra\DataTables\Contracts\DataTable as DataTable; 
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;


/**
 * Class LicenciaController
 * @package App\Http\Controllers
 */
class LicenciaController extends Controller
{
   public function __construct()
    {
        $this->middleware('can:Listar Licencias')->only('index','show');
        $this->middleware('can:Crear Licencias')->only('createcom','createprogramacion','storecom','storeprogramacion');
        $this->middleware('can:Editar Licencias')->only('actualizar','edit','update','editar');
        $this->middleware('can:Eliminar Licencias')->only('destroy');
    }

    public function index()
    {
        return view("licencia.index");
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function createcom(Request $request)
    {
        $motivos=Tipomotivo::findOrFail(4)->motivos;    
        $programacioncom=Programacioncom::findOrFail($request->id);
        $apoderados=$programacioncom->matriculacion->computacion->persona->apoderados;
        $data=['motivos'=>$motivos, 'programacioncom'=>$programacioncom,'apoderados'=>$apoderados];
        return response()->json($data);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function createprogramacion(Request $request)
    {
        $motivos=Tipomotivo::findOrFail(4)->motivos;    
        $programacion=Programacion::findOrFail($request->id);
        $apoderados=$programacion->inscripcione->estudiante->persona->apoderados;
        $data=['motivos'=>$motivos, 'programacion'=>$programacion,'apoderados'=>$apoderados];
        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function storecom(Request $request)
    {
        app(\App\Services\ClassAttendanceService::class)->protectHistory(Programacioncom::findOrFail($request->programacioncom_id));
        $validator = Validator::make($request->all(), [
            'motivo_id' => 'required',
            'solicitante' => 'required',
            'parentesco' => 'required',
        ]);

        if ($validator->passes()) {
            $licenciacom    =new Licencia();
            $licenciacom->motivo_id=$request->motivo_id;

            $cadena=$request->solicitante;
            $pos=stripos($cadena,'(');
            $apoderado=substr($cadena,0,$pos);
                
            $licenciacom->solicitante=$apoderado;  
            
            $licenciacom->parentesco=$request->parentesco;  
            $licenciacom->licenciable_id=$request->programacioncom_id;
            $licenciacom->licenciable_type=Programacioncom::class;
            $licenciacom->save();

            $programacioncom=Programacioncom::findOrFail($request->programacioncom_id);
            $programacioncom->estado_id=estado('LICENCIA');
            $programacioncom->save();

            $licenciacom->usuarios()->attach(Auth::user()->id);
            return response()->json(['errores'=>[]]);
        }
            return response()->json(['errores' => $validator->errors()->all()]);
    }



    public function storeprogramacion(Request $request)
    {
        app(\App\Services\ClassAttendanceService::class)->protectHistory(Programacion::findOrFail($request->programacion_id));
        //return response()->json(['d'=>2]);
        $validator = Validator::make($request->all(), [
            'motivo_id' => 'required',
            'solicitante' => 'required',
            'parentesco' => 'required',
        ]);

        

         if ($validator->passes()) {
             $licencia    =new Licencia();
             $licencia->motivo_id=$request->motivo_id;
             
             
             $cadena=$request->solicitante;
             $pos=stripos($cadena,'(');
            if(!$pos){
                $licencia->solicitante=$request->solicitante;  
            }
            else
            {
                $apoderado=substr($cadena,0,$pos);
                $licencia->solicitante=$apoderado;  
            } 
                
                
            
            $licencia->parentesco=$request->parentesco;  
            $licencia->licenciable_id=$request->programacion_id;
            $licencia->licenciable_type=Programacion::class;
            $licencia->save();

            $programacion=Programacion::findOrFail($request->programacion_id);
            $programacion->estado_id=estado('LICENCIA');
            $programacion->save();
            
            $licencia->usuarios()->attach(Auth::user()->id);
            return response()->json(['errores'=>[]]);
        }
            return response()->json(['errores' => $validator->errors()->all()]);
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $licencia = Licencia::find($id);
        return view('licencia.show', compact('licencia'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $licencia = Licencia::find($id);
        return view('licencia.edit', compact('licencia'));
    }
    // utilizado desde mostrar en marcadoGeneral
    public function editarcom(Licencia $licencia)
    {
        $motivos=Tipomotivo::findOrFail(4)->motivos;    
        $programacioncom=Programacioncom::findOrFail($licencia->licenciable_id);
        $apoderados=$programacioncom->matriculacion->computacion->persona->apoderados;
        $motivo=Motivo::findOrFail($licencia->motivo_id);
        $data=['motivos'=>$motivos, 'programacioncom'=>$programacioncom,'apoderados'=>$apoderados,'licencia'=>$licencia,'motivo'=>$motivo];
        return response()->json($data);
    }
    public function editar(Licencia $licencia)
    {
        $motivos=Tipomotivo::findOrFail(4)->motivos;    
        $programacion=Programacion::findOrFail($licencia->licenciable_id);
        $apoderados=$programacion->inscripcione->estudiante->persona->apoderados;
        $motivo=Motivo::findOrFail($licencia->motivo_id);
        $data=['motivos'=>$motivos, 'programacion'=>$programacion,'apoderados'=>$apoderados,'licencia'=>$licencia,'motivo'=>$motivo];
        return response()->json($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Licencia $licencia
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Licencia $licencia)
    {
        request()->validate(Licencia::$rules);
        if(in_array($licencia->licenciable_type,[Programacion::class,Programacioncom::class],true)) { app(\App\Services\ClassAttendanceService::class)->protectHistory($licencia->licenciable_type::findOrFail($licencia->licenciable_id)); }

        $licencia->update($request->all());

        return redirect()->route('licencias.index')
            ->with('success', 'Licencia updated successfully');
    }
    public function actualizar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'motivo_id' => 'required',
            'solicitante' => 'required',
            'parentesco' => 'required',
        ]);
        $licencia= Licencia::find($request->licencia_id);
        if(in_array($licencia->licenciable_type,[Programacion::class,Programacioncom::class],true)) { app(\App\Services\ClassAttendanceService::class)->protectHistory($licencia->licenciable_type::findOrFail($licencia->licenciable_id)); }
        $licencia->motivo_id=$request->motivo_id;
        $cadena=$request->solicitante;
        $pos=stripos($cadena,'(');
        if(!$pos){
            $licencia->solicitante=$request->solicitante;  
        }
        else
        {
            $apoderado=substr($cadena,0,$pos);
            $licencia->solicitante=$apoderado;  
        } 
        $licencia->solicitante=$request->solicitante;
        $licencia->parentesco=$request->parentesco;
        $licencia->save();

        return response()->json($licencia);
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($licencia_id)
    {
        //=8;
        $licencia = Licencia::findOrFail($licencia_id);
        if(in_array($licencia->licenciable_type,[Programacion::class,Programacioncom::class],true)) { app(\App\Services\ClassAttendanceService::class)->protectHistory($licencia->licenciable_type::findOrFail($licencia->licenciable_id)); }
        $licencia->delete();
        //return response()->json($licencia);
        return response()->json(['mensaje'=>"Registro eliminado correctamente"]);
    }

    public function listar(){
        $licencias=Licencia::join('motivos','licencias.motivo_id','motivos.id')
            ->select('motivo',DB::raw('count(*) as cantidad'))
            ->groupBy('motivo')
            ->orderBy('cantidad','desc')
            ->get();
        return datatables()->of($licencias)
        ->toJson();
    }
}

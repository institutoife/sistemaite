<?php

namespace App\Http\Controllers;

use App\Http\Requests\PagocomStoreRequest;
use Illuminate\Http\Request;
use App\Models\Matriculacion;
use App\Models\Pagocom;
use App\Models\Pago;
use Illuminate\Support\Facades\Auth;

use Yajra\DataTables\Contracts\DataTable as DataTable; 
use Yajra\DataTables\DataTables;

class PagocomController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Listar Pagoscomputacion')->only("detallar","pagoMatriculacionesView","pagoModelo");
        $this->middleware('can:Crear Pagoscomputacion')->only("crear","guardar");
        $this->middleware('can:Editar Pagoscomputacion')->only("editar","actualizar");
    }
    
    public function crear($matriculacion_id)
    {
        $matriculacion=Matriculacion::findOrFail($matriculacion_id);
        $pagoscom = $matriculacion->pagos;
        $acuenta= $matriculacion->pagos->sum->monto;
        $saldo=$matriculacion->costo-$acuenta;
        
        return view('pagocom.create', compact('matriculacion','pagoscom','acuenta','saldo'));
    }

    public function guardar(PagocomStoreRequest $request,$matriculacion_id){
        $matriculacion=Matriculacion::findOrFail($matriculacion_id);
        $pago=new Pago();
        $pago->monto=$request->monto;
        $pago->pagocon=$request->pagocon;
        $pago->cambio=$request->cambio;
        $pago->forma_pago=$request->forma_pago;
        $pago->pagable_id=$matriculacion_id;
        $pago->pagable_type=Matriculacion::class;
        $pago->save();
        //**%%%%%%%%%%%%%%%%%%%%  B  I  T  A  C  O  R  A   %%%%%%%%%%%%%%%%*/
        
        $pago->usuarios()->attach(Auth::user()->id);
        
          if($matriculacion->estado_id==estado("RESERVADO")){
                return redirect()->route('matriculacion.configuracion',$matriculacion);
            }else{
                if ($matriculacion->programacionescom->count() == 0) {
                    return redirect()->route('generar.programacioncom', $matriculacion->id);
                } else {
                    
                    return redirect()->route('actualizar.programacioncom.segun.pago', ['matriculacion' => $matriculacion->id, 'pago' => $pago->id]);
                }
            }

        //return redirect()->route('billetecom.crear',['pago'=>$pago]);
    }


    public function editar($pago_id)
    {
        $pago = Pago::find($pago_id);
        $matriculacion=$pago->pagable; 
        return view('pagocom.editar',compact('pago','matriculacion'));
    }


    public function detallar($matriculacion_id){
        $pagos=Pago::where('pagable_id','=',$matriculacion_id)->get();
        $matriculacion = Matriculacion::findOrFail($matriculacion_id);
        $pagos = $matriculacion->pagos;
        $acuenta = $matriculacion->pagos->sum->monto;
        $saldo = $matriculacion->costo - $acuenta;
        return view('pagocom.detalle', compact('matriculacion', 'pagos', 'acuenta', 'saldo'));

    }
    public function actualizar(PagocomStoreRequest $request, $pago_id)
    {
        $pago=Pago::findOrFail($pago_id);
        $pago->monto=$request->monto;
        $pago->pagocon=$request->pagocon;
        $pago->cambio=$request->cambio;
        $pago->forma_pago=$request->forma_pago;
        $pago->save();

        return redirect()->route('billetecom.crear', ['pago'=>$pago]); 
    }
    public function pagosComMostrarAjax(Request $request){
        $matriculacion = Matriculacion::findOrFail(1);
        $persona=$matriculacion->computacion->persona;
        $pagos = $matriculacion->pagos;
        $acuenta = $matriculacion->pagos->sum->monto;
        $saldo = $matriculacion->costo - $acuenta;
        $data=['pagos'=>$pagos, 'persona'=>$persona,'acuenta'=>$acuenta, 'saldo'=>$saldo,'total'=>$matriculacion->costo];
        return response()->json($data);
    }

    public function pagoMatriculacionesView(){
        return view('reportes.pago.pagomatriculaciones');
    } 
    public function pagoModelo(Request $request){
        $pagos=Pago::join('matriculacions','matriculacions.id','pagos.pagable_id')
            ->join('computacions','computacions.id','matriculacions.computacion_id')
            ->join('personas','personas.id','computacions.persona_id')
            ->join('asignaturas','asignaturas.id','matriculacions.asignatura_id')
            ->join('userables','userables.userable_id','pagos.id')
            ->join('users','users.id','userables.user_id')
            ->where('pagos.pagable_type','App\\Models\\'.$request->modelo)
            ->where('userable_type','App\\Models\\Pago')
            ->whereDate('pagos.created_at','<=',$request->fechafin)
            ->whereDate('pagos.created_at','>=',$request->fechaini)
            ->select('personas.id','personas.nombre','apellidop','apellidom','asignatura','monto','users.foto','name','pagocon','cambio','forma_pago','pagos.created_at','personas.foto as personafoto','users.id as foto_user_id')
            ->orderBy('created_at',"asc")
            ->get();
        app(\App\Services\ProfilePhotoService::class)->fillUserPhotos($pagos);
        return DataTables::of($pagos)
                ->rawColumns(['foto'])
                ->toJson();
    }
    public function pagoMatriculacionesMax(){
        return view('reportes.pago.pagomatriculacionesmejorado');
    } 
    public function pagoModeloMax(Request $request){
        $pagos=Pago::join('matriculacions','matriculacions.id','pagos.pagable_id')
            ->join('computacions','computacions.id','matriculacions.computacion_id')
            ->join('personas','personas.id','computacions.persona_id')
            ->join('asignaturas','asignaturas.id','matriculacions.asignatura_id')
            ->join('userables','userables.userable_id','pagos.id')
            ->join('users','users.id','userables.user_id')
            ->where('pagos.pagable_type','App\\Models\\'.$request->modelo)
            ->where('userable_type','App\\Models\\Pago')
            ->whereDate('pagos.created_at','<=',$request->fechafin)
            ->whereDate('pagos.created_at','>=',$request->fechaini)
            ->select('personas.id','personas.nombre','apellidop','apellidom','asignatura','monto','name','pagocon','cambio','forma_pago','pagos.created_at')
            ->orderBy('created_at',"asc")
            ->get();
        return DataTables::of($pagos)
                ->toJson();
    }
}

<?php
namespace App\Http\Controllers;

use App\Services\PresentAttendanceService;
use Illuminate\Http\Request;

class PresentAttendanceController extends Controller
{
    public function index(Request $request, PresentAttendanceService $service)
    {
        abort_unless($service->canList($request->user()),403);
        return view('clase.presentes',['noticesReady'=>$service->ready()]);
    }

    public function status(Request $request, PresentAttendanceService $service)
    {
        return response()->json(['server_now'=>now(PresentAttendanceService::TIMEZONE)->toIso8601String(),
            'data'=>$service->rows($request->user())])->header('Cache-Control','no-store, private');
    }

    public function notify(Request $request, PresentAttendanceService $service, string $canal, int $id)
    {
        $service->act($canal,$id,$request->user());
        return response()->json(['message'=>'Aviso registrado. La salida continúa pendiente.']);
    }

    public function snooze(Request $request, PresentAttendanceService $service, string $canal, int $id)
    {
        $data=$request->validate(['minutos'=>'required|integer|in:5,10,15']);
        $service->act($canal,$id,$request->user(),(int)$data['minutos']);
        return response()->json(['message'=>'Aviso pospuesto '.$data['minutos'].' minutos para tu usuario.']);
    }
}

<?php
namespace App\Http\Controllers;
use App\Models\{Prospecto,Persona,CrmEtapa,CrmSede};
use App\Http\Requests\{CrmFilterRequest,CrmActionRequest};
use App\Services\{CrmService,CrmAnalytics,CrmConversionService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class CrmProspectoController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','can:Listar Prospectos']);
        $this->middleware(function ($request,$next) { abort_unless(app(CrmService::class)->ready(),503,'Prepare el CRM con php artisan crm:prepare --force.'); return $next($request); });
    }
    public function index(CrmFilterRequest $request, CrmService $crm)
    {
        $filters=$request->validated();
        return view('crm.tablero',$crm->catalogs()+$crm->board($filters)+['filters'=>$filters,'stats'=>app(CrmAnalytics::class)->report($filters)]);
    }
    public function listing(CrmFilterRequest $request, CrmService $crm)
    {
        return view('crm.lista',$crm->catalogs()+['records'=>$crm->query($request->validated())->with(CrmService::RELATIONS)->orderByDesc('id')->paginate(30)->withQueryString()]);
    }
    public function report(CrmFilterRequest $request, CrmService $crm, CrmAnalytics $analytics)
    {
        $filters=$request->validated(); $general=$request->user()->can('Ver Estadisticas CRM');
        if (!$general) { $filters['usuario_id']=$request->user()->id; $filters['responsable_id']=$request->user()->id; unset($filters['asignacion']); }
        return view('crm.dashboard',$crm->catalogs()+['usuariosEstadisticas'=>$general?\App\Models\User::orderBy('name')->get(['id','name']):collect(),
            'stats'=>$general&&!$request->filled('usuario_id')?$analytics->report($filters):null,'general'=>$general,
            'userStats'=>app(\App\Services\CrmUserAnalytics::class)->report($filters)]);
    }
    public function matches(Request $request, CrmService $crm)
    {
        $data=$request->validate(['telefono'=>'required|string|max:20','except'=>'nullable|integer']);
        return response()->json(['matches'=>$crm->duplicates($data['telefono'],$data['except']??null)])->header('Cache-Control','private, no-store');
    }
    public function people(Request $request)
    {
        $data=$request->validate(['q'=>'required|string|min:1|max:100']);
        if (!ctype_digit($data['q'])&&mb_strlen(trim($data['q']))<3) { throw ValidationException::withMessages(['q'=>'Escriba al menos tres letras o un código numérico.']); }
        $people=Persona::where(function ($query) use ($data) { $query->where('nombre','like','%'.$data['q'].'%')->orWhere('apellidop','like','%'.$data['q'].'%')->orWhere('telefono','like','%'.$data['q'].'%'); if (ctype_digit($data['q'])) { $query->orWhere('id',(int)$data['q']); } })
            ->with(['estudiante:id,persona_id','computacion:id,persona_id'])->limit(20)->get(['id','nombre','apellidop','apellidom']);
        return response()->json(['people'=>$people->map(function ($row) { return ['id'=>$row->id,'nombre'=>trim($row->nombre.' '.$row->apellidop.' '.$row->apellidom),
            'url'=>route('personas.show',$row->id),'academico'=>$row->estudiante?route('inscribir',$row->id):null,
            'computacion'=>$row->computacion?route('configuracion.gestionar.carreras',$row->id):null]; })])->header('Cache-Control','private, no-store');
    }
    public function move(CrmActionRequest $request, Prospecto $prospecto, CrmService $crm)
    {
        $record=$crm->move($prospecto,(int)$request->etapa_id,(int)$request->version,$request->user()->id);
        if ($request->expectsJson()) { return response()->json(['saved'=>true,'version'=>(int)$record->crm_version,'etapa_id'=>(int)$record->crm_etapa_id,'etapa_desde'=>$record->etapa_desde->format('d/m/Y H:i')]); }
        return back()->with('success','Etapa actualizada.');
    }
    public function activity(CrmActionRequest $request, Prospecto $prospecto, CrmService $crm) { $crm->activity($prospecto,$request->validated(),$request->user()->id); return back()->with('success','Actividad registrada.'); }
    public function reassign(CrmActionRequest $request, Prospecto $prospecto, CrmService $crm) { $crm->reassign($prospecto,$request->validated(),$request->user()->id); return back()->with('success','Responsable reasignado; se conserva la atribución histórica.'); }
    public function deactivate(CrmActionRequest $request, Prospecto $prospecto, CrmService $crm) { $crm->deactivate($prospecto,$request->validated(),$request->user()->id); return back()->with('success','Baja registrada; se conserva el historial.'); }
    public function reactivate(CrmActionRequest $request, Prospecto $prospecto, CrmService $crm) { $crm->reactivate($prospecto,$request->validated(),$request->user()->id); return back()->with('success','Oportunidad reactivada.'); }
    public function conversionForm(Request $request, Prospecto $prospecto)
    {
        $this->authorize('convert',$prospecto);
        abort_unless($prospecto->crm_status==='activo'&&!$prospecto->convertido_at,422,'La oportunidad ya está convertida o no está activa. Actualice el tablero.');
        $data=$request->validate(['etapa_id'=>'nullable|integer|exists:crm_etapas,id']);
        $conversionStage=!empty($data['etapa_id'])?CrmEtapa::whereKey($data['etapa_id'])->where('activa',true)->where('conversion',true)->firstOrFail():null;
        $prospecto->load(['persona','estudiantePersona']);
        return response()->view('crm.conversion-form',compact('prospecto','conversionStage'))->header('Cache-Control','private, no-store');
    }
    public function enrollmentOptions(Request $request, Prospecto $prospecto, \App\Services\CrmEnrollmentOptions $options)
    {
        $this->authorize('convert',$prospecto);
        $data=$request->validate(['persona_id'=>'nullable|integer|exists:personas,id','estudiante_persona_id'=>'nullable|integer|exists:personas,id']);
        $result=$options->forLead($prospecto,isset($data['persona_id'])?(int)$data['persona_id']:null,isset($data['estudiante_persona_id'])?(int)$data['estudiante_persona_id']:null,$request->user()->can('Listar Pagos'));
        $contact=Persona::find($data['persona_id']??$prospecto->persona_id);
        $result['google_conflict']=$contact?app(\App\Services\CrmGoogleContactPolicy::class)->differentLinks($prospecto,$contact):false;
        return response()->json($result)->header('Cache-Control','private, no-store');
    }
    public function convert(CrmActionRequest $request, Prospecto $prospecto, CrmConversionService $service)
    {
        $service->convert($prospecto,$request->validated(),$request->user()->id);
        if ($request->expectsJson()) { $current=$prospecto->fresh(); return response()->json(['converted'=>true,'version'=>(int)$current->crm_version,'etapa_id'=>(int)$current->crm_etapa_id,'message'=>'Conversión registrada. Los indicadores ya incluyen esta inscripción.']); }
        return redirect()->route('crm.tablero',['status'=>'operativo'])->with('success','Conversión registrada con su inscripción y contacto vinculados.');
    }
    public function whatsapp(Request $request, Prospecto $prospecto, CrmService $crm)
    {
        $this->authorize('update',$prospecto);
        $data=$request->validate(['plantilla'=>'required|in:primer_contacto,informacion,seguimiento,inscripcion,reactivacion']);
        return response()->json($crm->whatsapp($prospecto,$data['plantilla']))->header('Cache-Control','private, no-store');
    }
    public function settings(CrmService $crm) { abort_unless(auth()->user()->can('Configurar CRM'),403); return view('crm.configuracion',$crm->catalogs()); }
    public function stage(Request $request, ?CrmEtapa $etapa=null)
    {
        abort_unless($request->user()->can('Configurar CRM'),403);
        $data=$request->validate(['nombre'=>'required|string|max:80','color'=>'required|regex:/^#[0-9A-Fa-f]{6}$/','orden'=>'required|integer|min:0|max:10000',
            'activa'=>'required|boolean','inicial'=>'required|boolean','conversion'=>'required|boolean','version'=>$etapa?'required|integer|min:0':'sometimes|integer']);
        DB::transaction(function () use ($etapa,$data) {
            $all=CrmEtapa::orderBy('id')->lockForUpdate()->get(); $row=$etapa?$all->firstWhere('id',$etapa->id):new CrmEtapa;
            if ($etapa&&(int)$row->version!==(int)$data['version']) { abort(409,'Otra persona modificó esta etapa. Actualice la página.'); }
            if ($data['inicial']&&(!$data['activa']||$data['conversion'])) { throw ValidationException::withMessages(['inicial'=>'La etapa inicial debe estar activa y permitir nuevas oportunidades.']); }
            if (!$data['inicial']&&$row->inicial) { throw ValidationException::withMessages(['inicial'=>'Seleccione primero otra etapa como inicial.']); }
            if ($data['conversion']&&!$row->conversion&&$row->id&&Prospecto::where('crm_etapa_id',$row->id)->exists()) { throw ValidationException::withMessages(['conversion'=>'Una etapa con oportunidades no puede cambiarse a etapa de conversión.']); }
            if (!$data['conversion']&&$row->id&&Prospecto::where('crm_etapa_id',$row->id)->whereNotNull('convertido_at')->exists()) { throw ValidationException::withMessages(['conversion'=>'Esta etapa conserva conversiones confirmadas.']); }
            if ($data['inicial']) { CrmEtapa::whereNotNull('inicial')->update(['inicial'=>null,'version'=>DB::raw('version + 1')]); }
            $row->fill(array_diff_key($data,['inicial'=>true,'version'=>true])); $row->inicial=$data['inicial']?'central':null;
            $row->version=(int)$row->version+1; $row->save();
        });
        return back()->with('success','Etapa guardada.');
    }
    public function deleteStage(Request $request, CrmEtapa $etapa, \App\Services\CrmStageService $service)
    {
        abort_unless($request->user()->can('Configurar CRM'),403);
        $data=$request->validate(['version'=>'required|integer|min:0','destino_id'=>'nullable|integer|exists:crm_etapas,id']);
        $service->archive($etapa,$data,$request->user()->id);
        return back()->with('success','Etapa eliminada del tablero. Sus fichas e historial se conservan.');
    }
    public function sede(Request $request, ?CrmSede $sede=null)
    {
        abort_unless($request->user()->can('Configurar CRM'),403);
        $data=$request->validate(['nombre'=>'required|string|max:100|unique:crm_sedes,nombre,'.($sede?$sede->id:'NULL').',id','activa'=>'required|boolean']);
        ($sede?:new CrmSede)->fill($data)->save();
        return back()->with('success','Sucursal guardada.');
    }
}

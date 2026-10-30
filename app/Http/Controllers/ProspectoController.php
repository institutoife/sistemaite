<?php
namespace App\Http\Controllers;
use App\Models\{Prospecto,CrmSeguimiento};
use App\Http\Requests\{StoreProspectoRequest,UpdateProspectoRequest};
use App\Services\CrmService;
use Illuminate\Support\Facades\Schema;
class ProspectoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:Listar Prospectos')->only('index','show','listar');
        $this->middleware(function ($request,$next) { abort_unless(app(CrmService::class)->ready(),503,'Prepare el CRM con php artisan crm:prepare --force.'); return $next($request); });
    }
    public function index() { return redirect()->route('crm.tablero'); }
    public function create(CrmService $crm)
    {
        $this->authorize('create',Prospecto::class);
        return view('prospecto.create',$crm->catalogs()+['prospecto'=>new Prospecto]);
    }
    public function store(StoreProspectoRequest $request, CrmService $crm)
    {
        $record=$crm->save(null,$request->validated(),$request->user()->id);
        return redirect()->route('prospecto.view',$record)->with('success','Prospecto registrado.');
    }
    public function show(Prospecto $prospecto, CrmService $crm)
    {
        $this->authorize('view',$prospecto); $prospecto->load(CrmService::RELATIONS);
        $observaciones=$prospecto->observaciones()->leftJoin('crm_actividad_detalles as crm_history','crm_history.observacion_id','=','observacions.id')
            ->select('observacions.*')->with(['usuarios:id,name','crmDetalle.actor:id,name','crmDetalle.responsableComercial:id,name'])
            ->orderByRaw('COALESCE(crm_history.ocurrio_at, observacions.created_at) DESC')->orderByDesc('observacions.id')->paginate(30);
        $historiaPersona=$prospecto->persona?$prospecto->persona->observaciones()->with('usuarios:id,name')->orderByDesc('id')->limit(50)->get():collect();
        $seguimientos=$prospecto->persona_id&&Schema::hasTable('crm_seguimientos')?CrmSeguimiento::with('user:id,name')->where('persona_id',$prospecto->persona_id)->latest()->limit(50)->get():collect();
        return view('prospecto.show',$crm->catalogs()+compact('prospecto','observaciones','historiaPersona','seguimientos')+['mayContact'=>$crm->mayContact($prospecto)]);
    }
    public function edit(Prospecto $prospecto, CrmService $crm)
    {
        $this->authorize('update',$prospecto);
        return view('prospecto.edit',$crm->catalogs()+compact('prospecto'));
    }
    public function update(UpdateProspectoRequest $request, Prospecto $prospecto, CrmService $crm)
    {
        $crm->save($prospecto,$request->validated(),$request->user()->id);
        return redirect()->route('prospecto.view',$prospecto)->with('success','Datos comerciales actualizados.');
    }
    public function destroy(Prospecto $prospecto) { $this->authorize('delete',$prospecto); }
    public function listar(CrmService $crm)
    {
        return datatables()->eloquent($crm->query([])->with(['etapa','responsable'])->withMax('observaciones','updated_at'))
            ->addColumn('estado',function ($row) { return optional($row->etapa)->nombre; })
            ->addColumn('name',function ($row) { return optional($row->responsable)->name; })
            ->addColumn('ultima_observacion',function ($row) { return $row->observaciones_max_updated_at; })
            ->addColumn('btn','prospecto.action')->rawColumns(['btn'])->toJson();
    }
}

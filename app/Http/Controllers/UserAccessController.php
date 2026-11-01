<?php
namespace App\Http\Controllers;
use App\Models\{User,Persona,Prospecto};
use App\Services\{AccountSecurity,UserAdministration,SedeAccess,CrmService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Hash};
use Illuminate\Validation\ValidationException;
class UserAccessController extends Controller
{
    public function __construct() { $this->middleware('auth'); }
    private function admin(): void { $this->authorize('administer',User::class); }
    public function people(Request $request)
    {
        $this->admin(); $request->validate(['q'=>'required|string|min:2|max:80']); $term=trim($request->q);
        return response()->json(Persona::where(function($q)use($term){$q->where('nombre','like','%'.$term.'%')->orWhere('apellidop','like','%'.$term.'%');if(ctype_digit($term))$q->orWhere('id',(int)$term);})
            ->with('user:id,persona_id')->orderBy('id')->limit(20)->get(['id','nombre','apellidop','apellidom'])->map(fn($p)=>['id'=>$p->id,'label'=>'#'.$p->id.' · '.trim($p->nombre.' '.$p->apellidop.' '.$p->apellidom),'user_id'=>optional($p->user)->id]));
    }
    public function edit(User $user)
    {
        $this->authorize('manageAccess',$user);
        return view('user.access.controls',[
            'user'=>$user->load(['roles','sede','sedes','persona']),
            'windows'=>DB::table('user_access_windows')->where('user_id',$user->id)->get(),
            'exceptions'=>DB::table('user_access_exceptions')->where('user_id',$user->id)->get(),
            'audits'=>$user->accessAudits()->with('actor:id,name')->paginate(20),
            'pending'=>Prospecto::where('responsable_id',$user->id)->where('crm_status','activo')->count(),
            'responsibles'=>User::currentlyActive()->where('id','!=',$user->id)->with('roles.permissions')->orderBy('name')->get()->filter(fn($candidate)=>!app(AccountSecurity::class)->family($candidate)&&$candidate->can('Listar Prospectos')),
        ]);
    }
    public function state(Request $request,User $user,UserAdministration $admin)
    {
        $this->authorize('manageAccess',$user);
        $data=$request->validate(['status'=>'required|in:active,suspended,blocked','suspended_until'=>'nullable|date|after:now','reason'=>'required|string|min:5|max:500']);
        $admin->state($user,$data);
        return back()->with('success','Estado actualizado. Las sesiones y tokens anteriores fueron revocados.');
    }
    public function resetPassword(Request $request,User $user,AccountSecurity $security)
    {
        $this->authorize('manageAccess',$user); $data=$request->validate(['reason'=>'required|string|min:5|max:500']);
        $password=$security->temporaryPassword(); $fresh=$security->password($user,$password,true,$data['reason']);
        return response()->view('user.access.credentials',['user'=>$fresh,'password'=>$password])->header('Cache-Control','private, no-store')->header('Referrer-Policy','no-referrer');
    }
    public function schedule(Request $request,User $user,UserAdministration $admin)
    {
        $this->authorize('manageAccess',$user);
        $data=$request->validate(['enabled'=>'required|boolean','reason'=>'required|string|min:5|max:500','windows'=>'nullable|array|max:35',
            'windows.*.weekday'=>'required|integer|between:1,7','windows.*.starts_at'=>'required|date_format:H:i','windows.*.ends_at'=>'required|date_format:H:i',
            'exceptions'=>'nullable|array|max:50','exceptions.*.starts_at'=>'required|date','exceptions.*.ends_at'=>'required|date|after:exceptions.*.starts_at',
            'exceptions.*.allowed'=>'required|boolean','exceptions.*.reason'=>'required|string|max:500']);
        if ($data['enabled']&&empty($data['windows'])&&empty($data['exceptions'])) { throw ValidationException::withMessages(['windows'=>'Agregue una franja horaria o excepción.']); }
        foreach ($data['windows']??[] as $window) { if($window['starts_at']===$window['ends_at'])throw ValidationException::withMessages(['windows'=>'La hora inicial y final deben ser diferentes.']); }
        $admin->schedule($user,$data);
        return back()->with('success','Horario guardado en America/La_Paz. Las sesiones anteriores fueron revocadas.');
    }
    public function resetTwoFactor(Request $request,User $user,AccountSecurity $security)
    {
        $this->authorize('manageAccess',$user); $data=$request->validate(['current_password'=>'required|string','reason'=>'required|string|min:5|max:500']);
        if (!Hash::check($data['current_password'],$request->user()->password)) { throw ValidationException::withMessages(['current_password'=>'La contraseña del administrador no es correcta.']); }
        abort_if($user->id===$request->user()->id,422,'Use sus códigos de recuperación para su propia cuenta.');
        DB::transaction(function()use($user,$security,$data){$user=User::lockForUpdate()->findOrFail($user->id);$user->two_factor_secret=null;$user->two_factor_confirmed_at=null;$user->two_factor_last_step=null;$user->two_factor_recovery_codes=null;$security->revoke($user);$security->audit($user,'two_factor_reset',[],[],$data['reason']);});
        return back()->with('success','Segundo factor restablecido. El titular deberá configurarlo nuevamente si es obligatorio.');
    }
    public function reassign(Request $request,User $user,CrmService $crm)
    {
        $this->authorize('manageAccess',$user); $data=$request->validate(['responsable_id'=>'required|integer|exists:users,id','reason'=>'required|string|min:5|max:500']);
        $target=User::findOrFail($data['responsable_id']); abort_if($target->id===$user->id||$target->effective_access_status!=='active'||app(AccountSecurity::class)->family($target),422,'Seleccione personal activo diferente.');
        abort_unless($target->can('Listar Prospectos'),422,'El nuevo responsable debe tener acceso al CRM.');
        DB::transaction(function()use($user,$target,$data,$crm){foreach(Prospecto::where('responsable_id',$user->id)->where('crm_status','activo')->lockForUpdate()->get() as $lead){
            if($lead->crm_sede_id&&!$target->hasRole('Admin')&&!in_array((int)$lead->crm_sede_id,app(SedeAccess::class)->allowedIds($target),true)){throw ValidationException::withMessages(['responsable_id'=>'El nuevo responsable no tiene acceso a todas las sucursales de esos prospectos.']);}
            $crm->reassign($lead,['responsable_id'=>$target->id,'version'=>$lead->crm_version,'observacion'=>$data['reason']],auth()->id());}app(AccountSecurity::class)->audit($user,'pending_reassigned',[],['responsable_id'=>$target->id],$data['reason']);});
        return back()->with('success','Responsabilidades CRM reasignadas con historial.');
    }
    public function switchSede(Request $request,SedeAccess $access)
    {
        abort_if(app(AccountSecurity::class)->family($request->user()),403);
        $data=$request->validate(['sede_id'=>'required|integer|min:0']); $id=(int)$data['sede_id'];
        abort_unless(($id===0&&$request->user()->hasRole('Admin'))||in_array($id,$access->allowedIds($request->user()),true),403,'Sucursal no autorizada.');
        $request->session()->put('sede_activa',$id); $request->session()->regenerate();
        return back()->with('success','Sucursal activa actualizada.');
    }
}

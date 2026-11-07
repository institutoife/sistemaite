<?php
namespace App\Http\Controllers;
use App\Services\{AccountSecurity,UserTwoFactor,TrustedDevices};
use App\Models\UserTrustedDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Crypt,DB,Hash};
use Illuminate\Validation\Rules\Password;
class AccountSecurityController extends Controller
{
    public function __construct() { $this->middleware('auth'); }
    private function page(string $view,array $data=[]) { return response()->view($view,$data)->header('Cache-Control','private, no-store')->header('Referrer-Policy','no-referrer'); }
    private function currentPassword(Request $request): void
    {
        $request->validate(['current_password'=>'required|string|max:255']);
        if (!Hash::check($request->current_password,$request->user()->password)) { throw \Illuminate\Validation\ValidationException::withMessages(['current_password'=>'La contraseña actual no es correcta.']); }
    }
    public function passwordForm(Request $request) { return $this->page('user.security.password',['forced'=>$request->user()->force_password_change]); }
    public function password(Request $request,AccountSecurity $security)
    {
        $this->currentPassword($request);
        $data=$request->validate(['password'=>['required','confirmed','max:64','different:current_password',Password::min(12)->mixedCase()->numbers()->symbols()]]);
        $user=$security->password($request->user(),$data['password'],false,'Cambio realizado por el titular');
        $request->session()->regenerate(); $request->session()->regenerateToken(); $request->session()->put('auth_version',(int)$user->auth_version);
        // Preserve second-factor proof only if this session already completed it.
        if ($request->session()->has('two_factor_verified')) { $request->session()->put('two_factor_verified',(int)$user->auth_version); }
        $request->session()->forget(['two_factor_via_device','trusted_device_current','trusted_device_offer','two_factor_fresh_at']);
        app(TrustedDevices::class)->forgetCookie($request,$user);
        return redirect()->route('profile.show')->with('success','Contraseña actualizada. Se revocaron las otras sesiones.');
    }
    public function twoFactorForm(Request $request)
    {
        $pending=$request->session()->get('two_factor_pending');
        $secret=$pending&&$pending['expires']>now()->timestamp?Crypt::decryptString($pending['secret']):null;
        return $this->page('user.security.two-factor',['user'=>$request->user(),'secret'=>$secret]);
    }
    public function beginTwoFactor(Request $request,UserTwoFactor $twoFactor)
    {
        $this->currentPassword($request); abort_if($request->user()->two_factor_confirmed_at,409);
        $request->session()->put('two_factor_pending',['secret'=>Crypt::encryptString($twoFactor->secret()),'expires'=>now()->addMinutes(10)->timestamp]);
        return redirect()->route('security.two-factor.show');
    }
    public function confirmTwoFactor(Request $request,UserTwoFactor $twoFactor)
    {
        $data=$request->validate(['code'=>'required|string|regex:/^\d{6}$/D']); $pending=$request->session()->get('two_factor_pending');
        abort_unless($pending&&$pending['expires']>now()->timestamp,419,'La configuración venció. Comience de nuevo.');
        $result=$twoFactor->confirm($request->user(),Crypt::decryptString($pending['secret']),$data['code']);
        $request->session()->forget('two_factor_pending'); $request->session()->regenerate();
        $request->session()->put(['auth_version'=>(int)$result['user']->auth_version,'two_factor_verified'=>(int)$result['user']->auth_version]);
        DB::table('users')->where('id',$result['user']->id)->update(['last_login_at'=>now()]);
        $trust=app(TrustedDevices::class);
        if($trust->ready()){$trust->offer($result['user'],$request);}
        return $this->page('user.security.recovery',['codes'=>$result['codes'],'trustOffer'=>$trust->ready()]);
    }
    public function challenge() { return $this->page('user.security.challenge'); }
    public function verify(Request $request,UserTwoFactor $twoFactor,AccountSecurity $security)
    {
        $data=$request->validate(['code'=>'required|string|max:64']); $user=$twoFactor->verify($request->user(),$data['code']);
        $request->session()->regenerate(); $request->session()->put('two_factor_verified',(int)$user->auth_version);
        DB::table('users')->where('id',$user->id)->update(['last_login_at'=>now()]); $security->audit($user,'login_two_factor');
        if(app(TrustedDevices::class)->ready()){
            app(TrustedDevices::class)->offer($user,$request);
            return redirect()->route('security.devices.offer');
        }
        return redirect()->route('profile.show');
    }
    public function disable(Request $request,UserTwoFactor $twoFactor,AccountSecurity $security)
    {
        abort_if($security->requiresTwoFactor($request->user()),403,'El segundo factor es obligatorio para esta cuenta.');
        $this->currentPassword($request); $request->validate(['code'=>'required|string|max:64']);
        DB::transaction(function () use ($request,$twoFactor,$security) {
            $user=$twoFactor->verify($request->user(),$request->code);
            $user->two_factor_secret=null; $user->two_factor_confirmed_at=null; $user->two_factor_recovery_codes=null; $user->two_factor_last_step=null;
            $security->revoke($user); $security->audit($user,'two_factor_disabled');
            $request->session()->put('auth_version',(int)$user->auth_version); $request->session()->forget('two_factor_verified');
        });
        return redirect()->route('profile.show')->with('success','Segundo factor desactivado.');
    }

    public function devices(Request $request,TrustedDevices $trust)
    {
        abort_unless($trust->ready(),503,'La administración de dispositivos requiere la migración pendiente.');
        return $this->page('user.security.devices',['user'=>$request->user(),
            'devices'=>UserTrustedDevice::where('user_id',$request->user()->id)->latest('created_at')->paginate(20),
            'current'=>$request->session()->get('trusted_device_current')]);
    }

    public function trustOffer(Request $request,TrustedDevices $trust)
    {
        if(!$trust->canOffer($request->user(),$request)){return redirect()->route('profile.show');}
        return $this->page('user.security.trust',['deviceName'=>$trust->deviceName($request)]);
    }

    public function trustDevice(Request $request,TrustedDevices $trust)
    {
        abort_unless($trust->canOffer($request->user(),$request),403,'Verifique nuevamente su segundo factor.');
        $data=$request->validate(['trust_device'=>'required|boolean','device_name'=>'nullable|string|max:100']);
        if($data['trust_device']){$trust->issue($request->user(),$request,trim($data['device_name']??'')?:null);}
        else{$trust->revokeCurrent($request->user(),$request);$request->session()->forget('trusted_device_offer');}
        return redirect()->route('profile.show')->with('success',$data['trust_device']?'Dispositivo de confianza registrado durante 30 días.':'Continuó sin guardar confianza en este navegador.');
    }

    public function revokeDevice(Request $request,TrustedDevices $trust,$device)
    {
        abort_unless($trust->ready(),503);
        $record=UserTrustedDevice::where('user_id',$request->user()->id)->findOrFail($device);
        if(!$record->revoked_at){$record->update(['revoked_at'=>now()]);app(AccountSecurity::class)->audit($request->user(),'trusted_device_revoked',[],['device_id'=>$record->id]);}
        if((int)$request->session()->get('trusted_device_current')===(int)$record->id){$trust->forgetCookie($request,$request->user());$request->session()->forget('trusted_device_current');}
        return redirect()->route('security.devices.index')->with('success','Confianza revocada. Ese dispositivo deberá verificar nuevamente el segundo factor.');
    }

    public function revokeAllDevices(Request $request,TrustedDevices $trust)
    {
        abort_unless($trust->ready(),503);$this->currentPassword($request);
        $trust->revokeAll($request->user());$trust->forgetCookie($request,$request->user());$request->session()->forget('trusted_device_current');
        app(AccountSecurity::class)->audit($request->user(),'trusted_devices_revoked');
        return redirect()->route('security.devices.index')->with('success','Se revocaron todos los dispositivos de confianza.');
    }

    public function otherSessions(Request $request,AccountSecurity $security,TrustedDevices $trust)
    {
        $this->currentPassword($request);$old=(int)$request->user()->auth_version;
        $verified=(int)$request->session()->get('two_factor_verified',-1)===$old;
        $user=DB::transaction(function()use($request,$security){$user=\App\Models\User::lockForUpdate()->findOrFail($request->user()->id);$security->revoke($user);$security->audit($user,'other_sessions_revoked');return $user;});
        $request->session()->regenerate();$request->session()->regenerateToken();$request->session()->put('auth_version',(int)$user->auth_version);
        if($verified){$request->session()->put('two_factor_verified',(int)$user->auth_version);}
        $request->session()->forget(['two_factor_via_device','trusted_device_current','trusted_device_offer','two_factor_fresh_at']);$trust->forgetCookie($request,$user);
        return redirect()->route('security.devices.index')->with('success','Se cerraron las demás sesiones y se revocó la confianza de todos los dispositivos. Este ingreso sigue activo.');
    }
}

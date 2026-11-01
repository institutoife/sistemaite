<?php
namespace App\Listeners;
use Illuminate\Auth\Events\Login;
use App\Services\AccountSecurity;
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Validation\ValidationException;
class RecordUserLogin
{
    public function handle(Login $event): void
    {
        $security=app(AccountSecurity::class);
        if (!$security->ready()||!request()->hasSession()) { return; }
        $user=$event->user;
        if ($reason=$security->rejection($user)) {
            Auth::guard($event->guard)->logout(); request()->session()->invalidate(); request()->session()->regenerateToken();
            $security->audit($user,'login_denied',[],[],$reason);
            throw ValidationException::withMessages(['email'=>$reason]);
        }
        request()->session()->put('auth_version',(int)$user->auth_version);
        request()->session()->forget(['two_factor_verified','two_factor_pending','sede_activa','two_factor_via_device','trusted_device_current','trusted_device_offer','two_factor_fresh_at']);
        if(app(\App\Services\TrustedDevices::class)->restore($user,request())){
            DB::table('users')->where('id',$user->id)->update(['last_login_at'=>now()]);
        }
        if (!$security->requiresTwoFactor($user)&&!$user->two_factor_confirmed_at) {
            DB::table('users')->where('id',$user->id)->update(['last_login_at'=>now()]);
            $security->audit($user,'login');
        }
    }
}

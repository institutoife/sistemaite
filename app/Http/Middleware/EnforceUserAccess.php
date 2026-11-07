<?php
namespace App\Http\Middleware;
use Closure;
use App\Services\{AccountSecurity,SedeAccess};
use Illuminate\Support\Facades\Auth;
class EnforceUserAccess
{
    public function handle($request,Closure $next)
    {
        app(SedeAccess::class)->reset();
        $security=app(AccountSecurity::class); $user=$request->user();
        if (!$user||!$security->ready()) { return $next($request); }
        $user->refresh();
        $version=$request->session()->get('auth_version');
        $reason=$security->rejection($user);
        if (($version===null&&(int)$user->auth_version>0)||($version!==null&&(int)$version!==(int)$user->auth_version)) { $reason='La sesión fue revocada. Inicie sesión nuevamente.'; }
        if ($reason) {
            Auth::guard('web')->logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
            return $request->expectsJson()?response()->json(['message'=>$reason],401):redirect()->route('login')->withErrors(['email'=>$reason]);
        }
        if ($version===null) { $request->session()->put('auth_version',0); }
        if ($request->routeIs('logout')) { return $next($request); }
        if(!app(\App\Services\TrustedDevices::class)->sessionValid($user,$request)){
            $request->session()->forget(['two_factor_verified','two_factor_via_device','trusted_device_current','trusted_device_offer','two_factor_fresh_at']);
            app(\App\Services\TrustedDevices::class)->forgetCookie($request,$user);
        }
        $challenge=$request->routeIs('security.two-factor.challenge','security.two-factor.verify');
        if ($user->two_factor_confirmed_at&&(int)$request->session()->get('two_factor_verified',-1)!==(int)$user->auth_version&&!$challenge) { return $this->redirect($request,'security.two-factor.challenge','Verifique su segundo factor.'); }
        if ($user->force_password_change&&!$request->routeIs('security.password.*')&&!$challenge) { return $this->redirect($request,'security.password.show','Debe cambiar su contraseña temporal.'); }
        if (!$user->force_password_change&&$security->requiresTwoFactor($user)&&!$user->two_factor_confirmed_at&&!$request->routeIs('security.two-factor.*','security.password.*')) { return $this->redirect($request,'security.two-factor.show','Configure el segundo factor para continuar.'); }
        $public=$request->isMethod('GET')&&($request->path()==='/'||$request->routeIs('cursos','nosotros','nivel.mostrar','interest.show','interest.para.home'));
        $public=$public||($request->is('api/*')&&RequireInternalApi::publicRequest($request));
        $personal=$request->routeIs('security.*','profile.show','foto.*','padre.*')||($request->isMethod('GET')&&$request->path()==='api/user');
        if (!$public&&!$personal&&($user->getRoleNames()->isEmpty()||$security->family($user))) { abort(403,'Esta cuenta solo puede consultar su perfil y su portal familiar.'); }
        if (!$request->routeIs('security.*')&&!$public) { app(SedeAccess::class)->start($user,$request); }
        return $next($request);
    }
    private function redirect($request,string $route,string $message)
    {
        return $request->expectsJson()?response()->json(['message'=>$message,'redirect'=>route($route)],423):redirect()->route($route);
    }
}

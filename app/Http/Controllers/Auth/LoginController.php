<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


use Spatie\Permission\Traits\HasRoles;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;
    use HasRoles;

    protected function attemptLogin(Request $request)
    {
        return $this->guard()->attempt($this->credentials($request), false);
    }

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected function authenticated(Request $request, $user){
        $cookieName = $this->rememberedEmailCookie();
        if ($request->boolean('remember')) {
            Cookie::queue(Cookie::make($cookieName, $user->email, 60 * 24 * 30, '/', null, config('session.secure'), true, false, 'lax'));
        } else {
            Cookie::queue(Cookie::forget($cookieName, '/', null));
        }
        
        if ($user->hasRole(['Admin']))
            return redirect('/home');
        else if ($user->hasRole(['Secretaria']))
            return redirect('/home');
        else if ($user->hasRole(['Docente']))
            return redirect('/misestudiantes');
            else if ($user->hasRole(['Estudiante']))
            return redirect()->route('padre.home');
            else if ($user->hasRole(['Padre']))
            return redirect()->route('padre.home');
    }

    protected $redirectTo = RouteServiceProvider::HOME;

    private function rememberedEmailCookie(): string
    {
        $sessionCookie = config('session.cookie');
        return (str_starts_with($sessionCookie, '__Host-') ? '__Host-' : '').'ife_login_email_'.substr(hash('sha256', $sessionCookie), 0, 16);
    }

    public function showLoginForm(Request $request)
    {
        $email = $request->cookie($this->rememberedEmailCookie());
        $rememberedEmail = is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
        return response()
            ->view('auth.login', ['rememberedEmail' => $rememberedEmail])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}

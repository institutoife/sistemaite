<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\ResetsPasswords;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;
    protected function rules()
    {
        return ['token'=>'required','email'=>'required|email','password'=>['required','confirmed','max:64',\Illuminate\Validation\Rules\Password::min(12)->mixedCase()->numbers()->symbols()]];
    }
    protected function resetPassword($user,$password)
    {
        if (app(\App\Services\AccountSecurity::class)->ready()) {
            $user=app(\App\Services\AccountSecurity::class)->password($user,$password,false,'Restablecimiento mediante token de Laravel');
        } else { $user->password=\Illuminate\Support\Facades\Hash::make($password); $user->setRememberToken(\Illuminate\Support\Str::random(60)); $user->save(); }
        event(new \Illuminate\Auth\Events\PasswordReset($user));
        $this->guard()->login($user);
    }
}

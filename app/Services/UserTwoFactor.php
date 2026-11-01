<?php
namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\{DB,Hash};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;
class UserTwoFactor
{
    public function secret(): string { return (new Google2FA)->generateSecretKey(32); }
    public function issuer(): string { return app(TrustedDevices::class)->issuer(); }
    public function uri(User $user,string $secret): string { return (new Google2FA)->getQRCodeUrl($this->issuer(),$user->email,$secret); }
    private function step(string $secret,string $code,?int $last): int
    {
        $step=preg_match('/^\d{6}$/D',$code)?(new Google2FA)->verifyKeyNewer($secret,$code,$last??0,1,(int)floor(now()->timestamp/30)):false;
        if ($step===false) { throw ValidationException::withMessages(['code'=>'Código incorrecto, vencido o ya utilizado.']); }
        return (int)$step;
    }
    public function confirm(User $subject,string $secret,string $code): array
    {
        return DB::transaction(function () use ($subject,$secret,$code) {
            $user=User::lockForUpdate()->findOrFail($subject->id);
            if ($user->two_factor_confirmed_at) { throw ValidationException::withMessages(['code'=>'El segundo factor ya está configurado.']); }
            $step=$this->step($secret,$code,null); $codes=[];
            for ($i=0;$i<8;$i++) { $codes[]=Str::random(10).'-'.Str::random(10); }
            $user->two_factor_secret=$secret; $user->two_factor_recovery_codes=array_map(fn($v)=>Hash::make($v),$codes);
            $user->two_factor_confirmed_at=now(); $user->two_factor_last_step=$step;
            app(AccountSecurity::class)->revoke($user);
            app(AccountSecurity::class)->audit($user,'two_factor_enabled');
            return ['user'=>$user,'codes'=>$codes];
        });
    }
    public function verify(User $subject,string $code): User
    {
        return DB::transaction(function () use ($subject,$code) {
            $user=User::lockForUpdate()->findOrFail($subject->id);
            if (!$user->two_factor_confirmed_at) { throw ValidationException::withMessages(['code'=>'Primero configure el segundo factor.']); }
            if (preg_match('/^\d{6}$/D',$code)) { $user->two_factor_last_step=$this->step($user->two_factor_secret,$code,$user->two_factor_last_step); }
            else {
                $codes=$user->two_factor_recovery_codes?:[]; $found=null;
                foreach ($codes as $index=>$hash) { if (Hash::check($code,$hash)) { $found=$index; break; } }
                if ($found===null) { throw ValidationException::withMessages(['code'=>'Código de recuperación incorrecto o ya utilizado.']); }
                unset($codes[$found]); $user->two_factor_recovery_codes=array_values($codes);
                app(AccountSecurity::class)->audit($user,'two_factor_recovery_used');
            }
            User::withoutEvents(fn()=>$user->save());
            return $user;
        });
    }
}

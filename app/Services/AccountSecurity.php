<?php
namespace App\Services;

use App\Models\{User,UserAccessAudit};
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB,Hash,Schema};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccountSecurity
{
    private $ready;
    public function ready(): bool { return $this->ready??($this->ready=Schema::hasColumn('users','auth_version')); }
    public function audit(?User $subject,string $event,array $before=[],array $after=[],?string $reason=null,?int $actor=null): void
    {
        if (!$this->ready()) { return; }
        // Only callers' explicit non-secret fields enter the append-only audit.
        foreach (['password','two_factor_secret','two_factor_recovery_codes','remember_token','api_token'] as $secret) { unset($before[$secret],$after[$secret]); }
        UserAccessAudit::create(['subject_user_id'=>$subject?$subject->id:null,'actor_id'=>$actor??auth()->id(),
            'event'=>$event,'before_values'=>$before,'after_values'=>$after,'reason'=>$reason,'created_at'=>now()]);
    }
    public function family(User $user): bool
    {
        $roles=$user->getRoleNames();
        return $roles->isNotEmpty()&&$roles->diff(config('user_access.family_roles'))->isEmpty();
    }
    public function requiresTwoFactor(User $user): bool
    {
        return $this->ready()&&($user->two_factor_required||$user->hasRole(config('user_access.two_factor_roles'))
            ||$user->getAllPermissions()->pluck('name')->intersect(config('user_access.two_factor_permissions',[]))->isNotEmpty());
    }
    public function allowedNow(User $user,?Carbon $at=null): bool
    {
        $at=($at?:Carbon::now(config('user_access.timezone')))->copy()->timezone(config('user_access.timezone'));
        $exceptions=DB::table('user_access_exceptions')->where('user_id',$user->id)->where('starts_at','<=',$at)->where('ends_at','>',$at)->get();
        if ($exceptions->contains(fn($row)=>!$row->allowed)) { return false; }
        if ($exceptions->isNotEmpty()) { return true; }
        if (!$user->access_schedule_enabled) { return true; }
        foreach (DB::table('user_access_windows')->where('user_id',$user->id)->get() as $window) {
            $start=$at->copy()->startOfDay()->addDays($window->weekday-$at->isoWeekday())->setTimeFromTimeString($window->starts_at);
            $end=$start->copy()->setTimeFromTimeString($window->ends_at);
            if ($end->lte($start)) { $end->addDay(); }
            foreach ([0,-7,7] as $weeks) { if ($at->gte($start->copy()->addDays($weeks))&&$at->lt($end->copy()->addDays($weeks))) { return true; } }
        }
        return false;
    }
    public function rejection(User $user): ?string
    {
        if ($user->access_status==='blocked') { return 'La cuenta está bloqueada. Consulte con administración.'; }
        if ($user->access_status==='suspended'&&(!$user->suspended_until||now()->lt($user->suspended_until))) { return 'La cuenta está suspendida temporalmente.'; }
        if ($user->temporary_password_expires_at&&$user->force_password_change&&now()->gte($user->temporary_password_expires_at)) { return 'La contraseña temporal venció. Solicite un restablecimiento.'; }
        if (!$this->allowedNow($user)) { return 'Acceso fuera del horario autorizado (America/La_Paz).'; }
        return null;
    }
    public function revoke(User $user): void
    {
        $user->auth_version=(int)$user->auth_version+1;
        $user->remember_token=Str::random(60);
        if (Schema::hasColumn('users','api_token')) { $user->api_token=null; }
        User::withoutEvents(fn()=>$user->save());
        app(TrustedDevices::class)->revokeAll($user);
        if (Schema::hasTable('sessions')) { DB::table('sessions')->where('user_id',$user->id)->delete(); }
        if (Schema::hasTable('personal_access_tokens')) { DB::table('personal_access_tokens')->where('tokenable_type',User::class)->where('tokenable_id',$user->id)->delete(); }
    }
    public function password(User $subject,string $password,bool $temporary,string $reason): User
    {
        return DB::transaction(function () use ($subject,$password,$temporary,$reason) {
            $user=User::lockForUpdate()->findOrFail($subject->id);
            $user->password=Hash::make($password); $user->force_password_change=$temporary;
            $user->temporary_password_expires_at=$temporary?now()->addHours(config('user_access.temporary_password_hours')):null;
            $user->password_changed_at=now(); $this->revoke($user);
            $this->audit($user,$temporary?'password_reset':'password_changed',[],['temporary'=>$temporary],$reason);
            return $user;
        });
    }
    public function temporaryPassword(): string { return 'Aa1!'.Str::random(20); }
    public function unrestrictedAdminOtherThan(User $subject): bool
    {
        return User::role('Admin')->where('id','!=',$subject->id)->orderBy('id')->lockForUpdate()->get()->contains(function($user){
            return !$user->access_schedule_enabled&&($user->access_status==='active'||($user->access_status==='suspended'&&$user->suspended_until&&now()->gte($user->suspended_until)))
                &&!DB::table('user_access_exceptions')->where('user_id',$user->id)->where('allowed',false)->where('ends_at','>',now())->exists();
        });
    }
    public function protectLastAdmin(User $subject,array $roles,string $status='active',?string $until=null): void
    {
        // Lock all admin rows, in stable order, to serialize concurrent demotions/blocks.
        $admins=User::role('Admin')->orderBy('id')->lockForUpdate()->get();
        $removing=!in_array('Admin',$roles,true)||!($status==='active'||($status==='suspended'&&$until&&now()->gte(Carbon::parse($until,config('user_access.timezone')))));
        if ($removing&&$subject->hasRole('Admin')&&!$this->unrestrictedAdminOtherThan($subject)) {
            throw ValidationException::withMessages(['roles'=>'Debe conservar al menos un administrador activo.']);
        }
    }
}

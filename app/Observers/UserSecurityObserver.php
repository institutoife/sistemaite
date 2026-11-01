<?php
namespace App\Observers;
use App\Models\User;
use App\Services\AccountSecurity;
use Illuminate\Support\Str;
class UserSecurityObserver
{
    public function creating(User $user): void
    {
        if (!app(AccountSecurity::class)->ready()) { return; }
        if (!array_key_exists('force_password_change',$user->getAttributes())) { $user->force_password_change=true; }
        if ($user->force_password_change) { $user->temporary_password_expires_at=now()->addHours(config('user_access.temporary_password_hours')); }
    }
    public function updating(User $user): void
    {
        if (!app(AccountSecurity::class)->ready()||!$user->isDirty('password')) { return; }
        $user->force_password_change=true; $user->temporary_password_expires_at=now()->addHours(config('user_access.temporary_password_hours'));
        $user->auth_version=(int)$user->auth_version+1; $user->remember_token=Str::random(60); $user->password_changed_at=now();
        if (\Illuminate\Support\Facades\Schema::hasColumn('users','api_token')) { $user->api_token=null; }
    }
    public function updated(User $user): void
    {
        if (app(AccountSecurity::class)->ready()&&$user->wasChanged('password')) { app(\App\Services\TrustedDevices::class)->revokeAll($user);app(AccountSecurity::class)->audit($user,'password_reset_legacy_flow',[],['temporary'=>true],'Restablecimiento desde un flujo existente'); }
    }
}

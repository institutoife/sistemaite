<?php

namespace App\Services;

use App\Models\{User, UserTrustedDevice};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cookie, DB, Schema};
use Symfony\Component\HttpFoundation\Cookie as HttpCookie;

class TrustedDevices
{
    private $ready;

    public function ready(): bool
    {
        return $this->ready ?? ($this->ready = Schema::hasTable('user_trusted_devices'));
    }

    public function environmentHash(): string
    {
        return hash('sha256', app()->environment().'|'.rtrim(strtolower(config('app.url')), '/'));
    }

    public function issuer(): string
    {
        return app()->environment('production') ? 'IFE PRODUCCIÓN' : (app()->environment(['local','testing']) ? 'IFE LOCAL' : 'IFE PRUEBAS');
    }

    public function legacyCookieName(): string
    {
        $localHttp = app()->environment(['local','testing']) && parse_url(config('app.url'), PHP_URL_SCHEME) !== 'https';
        return ($localHttp ? '' : '__Host-').'ife_trust_'.substr($this->environmentHash(), 0, 16);
    }

    public function cookieName(User $user): string
    {
        // Separate accounts sharing one browser without placing account IDs/emails in cookie names.
        return $this->legacyCookieName().'_'.substr($this->digest('cookie-user:'.$user->id),0,24);
    }

    private function transportAllowed(Request $request): bool
    {
        if (app()->environment(['local','testing']) && in_array($request->getHost(), ['localhost','127.0.0.1','::1'], true)) { return true; }
        return $request->isSecure() && parse_url(config('app.url'), PHP_URL_SCHEME)==='https'
            && strtolower($request->getHost())===strtolower((string)parse_url(config('app.url'), PHP_URL_HOST));
    }

    private function digest(string $value): string
    {
        return hash_hmac('sha256', $value, config('app.key').'|'.$this->environmentHash());
    }

    private function queueCookie(Request $request, string $name, string $value, $expires): void
    {
        $secure = !app()->environment(['local','testing']) || $request->isSecure();
        Cookie::queue(new HttpCookie($name, $value, $expires, '/', null, $secure, true, false, 'strict'));
    }

    private function forgetNamedCookie(Request $request, string $name): void
    {
        $this->queueCookie($request, $name, '', now()->subYear());
        $request->cookies->remove($name);
    }

    private function tokenParts($value): ?array
    {
        return is_string($value) && preg_match('/^([a-f0-9]{32}):([a-f0-9]{64})$/D', $value, $parts) ? $parts : null;
    }

    private function forgetOwnLegacyCookie(Request $request, User $user): void
    {
        $parts=$this->tokenParts($request->cookie($this->legacyCookieName()));
        if ($parts && $this->ready() && UserTrustedDevice::where('selector',$parts[1])->where('user_id',$user->id)->exists()) {
            $this->forgetNamedCookie($request,$this->legacyCookieName());
        }
    }

    public function forgetCookie(Request $request, User $user): void
    {
        $this->forgetNamedCookie($request,$this->cookieName($user));
        // An old shared cookie must survive another account's login, reset or refusal to trust.
        $this->forgetOwnLegacyCookie($request,$user);
    }

    public function deviceName(Request $request): string
    {
        $agent = (string)$request->userAgent();
        $browser = str_contains($agent, 'Edg/') ? 'Edge' : (str_contains($agent, 'Firefox/') ? 'Firefox' : (str_contains($agent, 'Chrome/') ? 'Chrome' : (str_contains($agent, 'Safari/') ? 'Safari' : 'Navegador')));
        $system = str_contains($agent, 'Android') ? 'Android' : (preg_match('/iPhone|iPad/', $agent) ? 'iOS' : (str_contains($agent, 'Windows') ? 'Windows' : (str_contains($agent, 'Macintosh') ? 'macOS' : (str_contains($agent, 'Linux') ? 'Linux' : 'Dispositivo'))));
        return $browser.' · '.$system;
    }

    private function agentHash(Request $request): string
    {
        // A routine browser version update must not discard the 30-day consent.
        return $this->digest($this->deviceName($request));
    }

    private function eligible(User $user, UserTrustedDevice $device, Request $request): bool
    {
        return (int)$device->user_id===(int)$user->id && $user->two_factor_confirmed_at && !$user->force_password_change
            && !$device->revoked_at && $device->expires_at->isFuture() && (int)$device->auth_version===(int)$user->auth_version
            && hash_equals($device->environment_hash, $this->environmentHash())
            && hash_equals($device->user_agent_hash, $this->agentHash($request));
    }

    public function offer(User $user, Request $request): void
    {
        $request->session()->put('trusted_device_offer', ['user_id'=>$user->id, 'version'=>(int)$user->auth_version, 'expires'=>now()->addMinutes(5)->timestamp]);
        $request->session()->put('two_factor_fresh_at', now()->timestamp);
        $request->session()->forget('two_factor_via_device');
    }

    public function canOffer(User $user, Request $request): bool
    {
        $offer = $request->session()->get('trusted_device_offer');
        return $this->ready() && $user->two_factor_confirmed_at && !$user->force_password_change && $offer && (int)$offer['user_id']===(int)$user->id
            && (int)$offer['version']===(int)$user->auth_version && $offer['expires']>now()->timestamp
            && (int)$request->session()->get('two_factor_verified', -1)===(int)$user->auth_version
            && (int)$request->session()->get('two_factor_fresh_at', 0)>now()->subMinutes(5)->timestamp;
    }

    public function issue(User $subject, Request $request, ?string $name = null): UserTrustedDevice
    {
        abort_unless($this->canOffer($subject, $request) && $this->transportAllowed($request), 403, 'Verifique nuevamente el segundo factor para confiar en este dispositivo.');
        $device = DB::transaction(function () use ($subject, $request, $name) {
            $user = User::lockForUpdate()->findOrFail($subject->id);
            abort_unless($this->canOffer($user, $request) && !app(AccountSecurity::class)->rejection($user), 403);
            // Replacing this browser's token must not leave the previous token usable.
            $this->revokeCurrent($user, $request);
            $validator = bin2hex(random_bytes(32));
            $device = UserTrustedDevice::create(['user_id'=>$user->id, 'selector'=>bin2hex(random_bytes(16)),
                'token_hash'=>$this->digest($validator), 'environment_hash'=>$this->environmentHash(),
                'user_agent_hash'=>$this->agentHash($request), 'auth_version'=>$user->auth_version,
                'name'=>$name ?: $this->deviceName($request), 'last_used_at'=>now(), 'expires_at'=>now()->addDays(30)]);
            $this->queueCookie($request, $this->cookieName($user), $device->selector.':'.$validator, $device->expires_at);
            app(AccountSecurity::class)->audit($user, 'trusted_device_added', [], ['device_id'=>$device->id, 'expires_at'=>$device->expires_at]);
            return $device;
        });
        $request->session()->forget('trusted_device_offer');
        $request->session()->put('trusted_device_current', $device->id);
        return $device;
    }

    public function restore(User $subject, Request $request): bool
    {
        if (!$this->ready() || !$subject->two_factor_confirmed_at || $subject->force_password_change || !$this->transportAllowed($request)) { return false; }
        $value = $request->cookie($this->cookieName($subject));
        $legacy = $value === null;
        if ($legacy) { $value=$request->cookie($this->legacyCookieName()); }
        $parts=$this->tokenParts($value);
        if (!$parts) { return false; }
        $device = DB::transaction(function () use ($subject, $request, $parts) {
            $user = User::lockForUpdate()->findOrFail($subject->id);
            $device = UserTrustedDevice::where('selector', $parts[1])->where('user_id', $user->id)->lockForUpdate()->first();
            if (!$device || !$this->eligible($user, $device, $request) || app(AccountSecurity::class)->rejection($user) || !hash_equals($device->token_hash, $this->digest($parts[2]))) { return null; }
            $validator = bin2hex(random_bytes(32));
            $device->update(['token_hash'=>$this->digest($validator), 'last_used_at'=>now()]);
            $this->queueCookie($request, $this->cookieName($user), $device->selector.':'.$validator, $device->expires_at);
            return $device;
        });
        if (!$device) { $this->forgetCookie($request,$subject); return false; }
        // Migrate only a valid token owned by this account; keep its original expiration.
        if ($legacy) { $this->forgetOwnLegacyCookie($request,$subject); }
        $request->session()->put(['two_factor_verified'=>(int)$subject->auth_version, 'two_factor_via_device'=>$device->id, 'trusted_device_current'=>$device->id]);
        app(AccountSecurity::class)->audit($subject, 'login_trusted_device', [], ['device_id'=>$device->id]);
        return true;
    }

    public function sessionValid(User $user, Request $request): bool
    {
        $id = $request->session()->get('two_factor_via_device');
        if (!$id) { return true; }
        $device = $this->ready() ? UserTrustedDevice::where('id', $id)->where('user_id', $user->id)->first() : null;
        if (!$device || !$this->transportAllowed($request) || !$this->eligible($user, $device, $request)) { return false; }
        UserTrustedDevice::where('id', $device->id)->where('last_used_at', '<', now()->subMinutes(5))->update(['last_used_at'=>now()]);
        return true;
    }

    public function revokeAll(User $user): void
    {
        if ($this->ready()) { UserTrustedDevice::where('user_id', $user->id)->whereNull('revoked_at')->update(['revoked_at'=>now()]); }
    }

    public function revokeCurrent(User $user, Request $request): void
    {
        $id = $request->session()->get('trusted_device_current');
        if ($id) { UserTrustedDevice::where('id', $id)->where('user_id', $user->id)->whereNull('revoked_at')->update(['revoked_at'=>now()]); }
        foreach ([$this->cookieName($user),$this->legacyCookieName()] as $name) {
            $parts=$this->tokenParts($request->cookie($name));
            if ($parts) { UserTrustedDevice::where('selector',$parts[1])->where('user_id',$user->id)->whereNull('revoked_at')->update(['revoked_at'=>now()]); }
        }
        $request->session()->forget('trusted_device_current');
        $this->forgetCookie($request,$user);
    }
}

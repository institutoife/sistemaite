<?php

namespace Tests\Feature;

use App\Models\{User,UserTrustedDevice};
use App\Services\{AccountSecurity,TrustedDevices,UserTwoFactor};
use Illuminate\Support\Facades\{DB,Hash,Http};
use PragmaRX\Google2FA\Google2FA;
use Tests\CrmTestCase;

class TrustedDevicesTest extends CrmTestCase
{
    private const SECRET='JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';
    private const PASSWORD='Testing!Password#73';
    private const AGENT='Mozilla/5.0 Windows NT 10.0 Chrome/120.0';
    private $browserTrustCookies=[];

    protected function setUp(): void
    {
        parent::setUp();
        foreach(['2026_10_31_000001_add_user_access_controls.php'=>'AddUserAccessControls','2026_11_01_000001_create_user_trusted_devices.php'=>'CreateUserTrustedDevices'] as $file=>$class){require_once database_path('migrations/'.$file);(new $class)->up();}
        app()->forgetInstance(AccountSecurity::class);app()->forgetInstance(TrustedDevices::class);
        User::withoutEvents(fn()=>$this->operator->forceFill(['password'=>Hash::make(self::PASSWORD),'two_factor_secret'=>self::SECRET,'two_factor_confirmed_at'=>now(),'force_password_change'=>false])->save());
        $this->operator->refresh();$this->withSession(['auth_version'=>0,'two_factor_verified'=>0]);$this->withServerVariables(['HTTP_USER_AGENT'=>self::AGENT]);
    }

    private function code(): string { return (new Google2FA)->oathTotp(self::SECRET,(int)floor(now()->timestamp/30)); }
    private function plainCookie($cookie): string
    {
        return \Illuminate\Cookie\CookieValuePrefix::remove(app('encrypter')->decrypt($cookie->getValue(),false));
    }

    private function issue(): string
    {
        $this->post(route('security.two-factor.verify'),['code'=>$this->code()])->assertRedirect(route('security.devices.offer'));
        $response=$this->post(route('security.devices.trust'),['trust_device'=>1,'device_name'=>'PC recepción'])->assertRedirect(route('profile.show'));
        return $this->plainCookie($response->getCookie(app(TrustedDevices::class)->cookieName($this->operator)));
    }

    private function signOut(): void { $this->post(route('logout'))->assertRedirect();$this->app['auth']->forgetGuards(); }
    private function login(?User $user=null,string $password=self::PASSWORD)
    {
        $user=$user?:$this->operator;
        return $this->post('/login',['email'=>$user->email,'password'=>$password]);
    }

    private function coworker(): User
    {
        $user=$this->actor('Secretaria');
        User::withoutEvents(fn()=>$user->forceFill(['password'=>Hash::make(self::PASSWORD),'force_password_change'=>false,
            'temporary_password_expires_at'=>null,'two_factor_secret'=>self::SECRET,'two_factor_confirmed_at'=>now()])->save());
        return $user->fresh();
    }

    private function browserRequest(string $method,string $url,array $data=[])
    {
        // Keep the browser's trust cookie jar between requests, applying Set-Cookie and deletions.
        // Every request still passes through Laravel's real cookie encryption and authentication.
        foreach(array_keys($this->defaultCookies) as $name) if(str_contains($name,'ife_trust_')) unset($this->defaultCookies[$name]);
        $this->withCookies($this->browserTrustCookies);app('cookie')->flushQueuedCookies();
        $response=$this->call($method,$url,$data,$this->prepareCookiesForRequest());
        foreach($response->headers->getCookies() as $cookie) {
            if(!str_contains($cookie->getName(),'ife_trust_')) continue;
            if($cookie->getExpiresTime()<=now()->timestamp) unset($this->browserTrustCookies[$cookie->getName()]);
            else $this->browserTrustCookies[$cookie->getName()]=$this->plainCookie($cookie);
        }
        return $response;
    }

    private function browserLogout(): void
    {
        $this->browserRequest('POST',route('logout'))->assertRedirect();$this->app['auth']->forgetGuards();
    }

    private function browserLogin(User $user,string $password=self::PASSWORD)
    {
        return $this->browserRequest('POST','/login',['email'=>$user->email,'password'=>$password])->assertRedirect();
    }

    private function browserTrust(): void
    {
        $this->browserRequest('POST',route('security.two-factor.verify'),['code'=>$this->code()])->assertRedirect(route('security.devices.offer'));
        $this->browserRequest('POST',route('security.devices.trust'),['trust_device'=>1])->assertRedirect(route('profile.show'));
    }

    private function twoAccountsTrustingOneBrowser(): User
    {
        $other=$this->coworker();$this->browserTrust();$this->browserLogout();
        $this->browserLogin($other);$this->browserTrust();$this->browserLogout();
        return $other;
    }

    private function expirations(): array
    {
        return UserTrustedDevice::orderBy('user_id')->pluck('expires_at','user_id')->map(fn($date)=>$date->toIso8601String())->all();
    }

    public function test_morning_and_afternoon_users_keep_independent_trust_in_one_browser_across_days(): void
    {
        $other=$this->coworker();$this->browserTrust();$this->browserLogout();
        $this->browserLogin($other);$this->browserRequest('GET',route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
        $this->browserTrust();$expires=$this->expirations();$this->browserLogout();
        try {
            for($day=0;$day<3;$day++) {
                $this->travel(1)->days();
                foreach([$this->operator,$other] as $user) {
                    $this->browserLogin($user);$this->browserRequest('GET',route('profile.show'))->assertOk();
                    $this->assertEquals($user->id,auth()->id());$this->browserLogout();
                }
            }
            $this->assertCount(2,$this->browserTrustCookies);
            $this->assertSame(2,UserTrustedDevice::count());
            $this->assertSame($expires,$this->expirations());
        } finally { $this->travelBack(); }
    }

    public function test_coworker_without_trust_does_not_erase_the_previous_users_cookie(): void
    {
        $other=$this->coworker();$this->browserTrust();$this->browserLogout();
        $this->browserLogin($other);$this->browserRequest('GET',route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
        $this->browserLogout();$this->browserLogin($this->operator);
        $this->browserRequest('GET',route('profile.show'))->assertOk();
        $this->assertSame(1,UserTrustedDevice::count());
    }

    public function test_valid_legacy_cookie_is_migrated_only_for_its_owner_without_extending_expiry(): void
    {
        $token=$this->issue();$device=UserTrustedDevice::sole();$expires=$device->expires_at->toIso8601String();
        $trust=app(TrustedDevices::class);$this->browserTrustCookies=[$trust->legacyCookieName()=>$token];
        $this->browserLogout();$this->browserLogin($this->operator);$this->browserRequest('GET',route('profile.show'))->assertOk();
        $this->assertArrayNotHasKey($trust->legacyCookieName(),$this->browserTrustCookies);
        $this->assertArrayHasKey($trust->cookieName($this->operator),$this->browserTrustCookies);
        $this->assertNotSame($token,$this->browserTrustCookies[$trust->cookieName($this->operator)]);
        $this->assertSame($expires,$device->fresh()->expires_at->toIso8601String());
        $this->assertSame(1,UserTrustedDevice::count());
    }

    public function test_coworker_login_and_declining_trust_preserve_another_accounts_legacy_cookie(): void
    {
        $token=$this->issue();$other=$this->coworker();$trust=app(TrustedDevices::class);
        $this->browserTrustCookies=[$trust->legacyCookieName()=>$token];$this->browserLogout();
        $this->browserLogin($other);$this->browserRequest('GET',route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
        $this->assertSame($token,$this->browserTrustCookies[$trust->legacyCookieName()]);
        $this->browserRequest('POST',route('security.two-factor.verify'),['code'=>$this->code()])->assertRedirect();
        $this->browserRequest('POST',route('security.devices.trust'),['trust_device'=>0])->assertRedirect();
        $this->assertSame($token,$this->browserTrustCookies[$trust->legacyCookieName()]);
        $this->browserLogout();$this->browserLogin($this->operator);$this->browserRequest('GET',route('profile.show'))->assertOk();
        $this->assertNull(UserTrustedDevice::sole()->revoked_at);
    }

    public function test_trusting_second_account_preserves_first_legacy_token_until_owner_migrates(): void
    {
        $token=$this->issue();$other=$this->coworker();$trust=app(TrustedDevices::class);
        $this->browserTrustCookies=[$trust->legacyCookieName()=>$token];$this->browserLogout();
        $this->browserLogin($other);$this->browserTrust();
        $this->assertSame($token,$this->browserTrustCookies[$trust->legacyCookieName()]);
        $this->assertArrayHasKey($trust->cookieName($other),$this->browserTrustCookies);
        $this->browserLogout();$this->browserLogin($this->operator);$this->browserRequest('GET',route('profile.show'))->assertOk();
        $this->assertArrayNotHasKey($trust->legacyCookieName(),$this->browserTrustCookies);$this->assertCount(2,$this->browserTrustCookies);
        $this->browserLogout();$this->browserLogin($other);$this->browserRequest('GET',route('profile.show'))->assertOk();
        $this->assertSame(0,UserTrustedDevice::whereNotNull('revoked_at')->count());
    }

    public function test_a_migrated_legacy_token_cannot_be_replayed(): void
    {
        $token=$this->issue();$trust=app(TrustedDevices::class);
        $this->browserTrustCookies=[$trust->legacyCookieName()=>$token];$this->browserLogout();$this->browserLogin($this->operator);
        $this->browserRequest('GET',route('profile.show'))->assertOk();$this->browserLogout();
        $this->browserTrustCookies=[$trust->legacyCookieName()=>$token];$this->browserLogin($this->operator);
        $this->browserRequest('GET',route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
    }

    public function test_invalid_account_cookie_cannot_fall_back_to_an_old_valid_cookie(): void
    {
        $token=$this->issue();$trust=app(TrustedDevices::class);
        $this->browserTrustCookies=[$trust->legacyCookieName()=>$token,$trust->cookieName($this->operator)=>'forged-value'];
        $this->browserLogout();$this->browserLogin($this->operator);
        $this->browserRequest('GET',route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
        $this->assertNull(session('two_factor_via_device'));
    }

    public function test_coworker_declining_trust_does_not_clear_another_accounts_current_cookie(): void
    {
        $other=$this->coworker();$this->browserTrust();$this->browserLogout();$this->browserLogin($other);
        $this->browserRequest('POST',route('security.two-factor.verify'),['code'=>$this->code()])->assertRedirect();
        $this->browserRequest('POST',route('security.devices.trust'),['trust_device'=>0])->assertRedirect();
        $this->browserLogout();$this->browserLogin($this->operator);$this->browserRequest('GET',route('profile.show'))->assertOk();
        $this->assertSame(1,UserTrustedDevice::count());$this->assertNull(UserTrustedDevice::sole()->revoked_at);
    }

    public function test_revoking_one_account_keeps_the_coworkers_cookie_and_trust(): void
    {
        $other=$this->twoAccountsTrustingOneBrowser();$trust=app(TrustedDevices::class);
        $own=UserTrustedDevice::where('user_id',$this->operator->id)->sole();
        $foreign=UserTrustedDevice::where('user_id',$other->id)->sole();
        $this->browserLogin($this->operator);$this->browserRequest('GET',route('profile.show'))->assertOk();
        $this->browserRequest('DELETE',route('security.devices.revoke',$own->id))->assertRedirect();
        $this->assertNotNull($own->fresh()->revoked_at);$this->assertNull($foreign->fresh()->revoked_at);
        $this->assertArrayHasKey($trust->cookieName($other),$this->browserTrustCookies);
        $this->browserLogout();$this->browserLogin($other);$this->browserRequest('GET',route('profile.show'))->assertOk();
    }

    public function test_revoking_all_own_devices_does_not_revoke_or_delete_other_accounts(): void
    {
        $other=$this->twoAccountsTrustingOneBrowser();$this->browserLogin($this->operator);
        $this->browserRequest('DELETE',route('security.devices.revoke-all'),['current_password'=>self::PASSWORD])->assertRedirect();
        $this->assertNotNull(UserTrustedDevice::where('user_id',$this->operator->id)->sole()->revoked_at);
        $this->assertNull(UserTrustedDevice::where('user_id',$other->id)->sole()->revoked_at);
        $this->browserLogout();$this->browserLogin($other);
        $this->browserRequest('GET',route('profile.show'))->assertOk();
    }

    public function test_password_change_clears_only_the_current_accounts_trust(): void
    {
        $other=$this->twoAccountsTrustingOneBrowser();$this->browserLogin($this->operator);
        $password='Changed!Password#74';
        $this->browserRequest('PUT',route('security.password.update'),['current_password'=>self::PASSWORD,
            'password'=>$password,'password_confirmation'=>$password])->assertRedirect(route('profile.show'));
        $this->assertNotNull(UserTrustedDevice::where('user_id',$this->operator->id)->sole()->revoked_at);
        $this->assertNull(UserTrustedDevice::where('user_id',$other->id)->sole()->revoked_at);
        $this->browserLogout();$this->browserLogin($other);$this->browserRequest('GET',route('profile.show'))->assertOk();
        $this->browserLogout();$this->browserLogin($this->operator,$password);
        $this->browserRequest('GET',route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
    }

    public function test_one_accounts_expired_trust_does_not_remove_another_accounts_valid_trust(): void
    {
        $other=$this->twoAccountsTrustingOneBrowser();
        UserTrustedDevice::where('user_id',$this->operator->id)->update(['expires_at'=>now()->subSecond()]);
        $this->browserLogin($this->operator);$this->browserRequest('GET',route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
        $this->browserLogout();$this->browserLogin($other);$this->browserRequest('GET',route('profile.show'))->assertOk();
        $this->assertNull(UserTrustedDevice::where('user_id',$other->id)->sole()->revoked_at);
    }

    public function test_copying_a_token_into_another_accounts_cookie_never_skips_its_second_factor(): void
    {
        $token=$this->issue();$other=$this->coworker();$trust=app(TrustedDevices::class);
        $this->browserTrustCookies=[$trust->cookieName($this->operator) => $token,$trust->cookieName($other) => $token];
        $this->browserLogout();$this->browserLogin($other);
        $this->browserRequest('GET',route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
        $this->assertArrayHasKey($trust->cookieName($this->operator),$this->browserTrustCookies);
        $this->browserLogout();$this->browserLogin($this->operator);$this->browserRequest('GET',route('profile.show'))->assertOk();
    }

    public function test_migration_only_adds_an_empty_table_and_preserves_existing_data(): void
    {
        $this->assertSame(0,UserTrustedDevice::count());$this->assertSame(1,User::count());
        $this->assertTrue(Hash::check(self::PASSWORD,$this->operator->fresh()->password));$this->assertTrue((bool)$this->operator->fresh()->two_factor_confirmed_at);
        (new \CreateUserTrustedDevices)->up();$this->assertSame(0,UserTrustedDevice::count());
    }

    public function test_new_browser_requires_code_then_explicit_unchecked_trust_choice(): void
    {
        $this->signOut();$this->login()->assertRedirect('/home');$this->get(route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
        $this->postJson(route('security.devices.trust'),['trust_device'=>1])->assertStatus(423);
        $this->post(route('security.two-factor.verify'),['code'=>$this->code()])->assertRedirect(route('security.devices.offer'));
        $html=$this->get(route('security.devices.offer'))->assertOk()->getContent();
        $dom=new \DOMDocument;@$dom->loadHTML($html);$xpath=new \DOMXPath($dom);$input=$xpath->query('//input[@type="checkbox" and @name="trust_device"]')->item(0);
        $this->assertNotNull($input);$this->assertFalse($input->hasAttribute('checked'));
        $this->post(route('security.devices.trust'),['trust_device'=>0])->assertRedirect(route('profile.show'));
        $this->assertSame(0,UserTrustedDevice::count());$this->signOut();$this->login()->assertRedirect();$this->get(route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
    }

    public function test_trust_is_hashed_cookie_is_protected_and_password_is_still_mandatory(): void
    {
        $token=$this->issue();$device=UserTrustedDevice::sole();$this->assertMatchesRegularExpression('/^[a-f0-9]{32}:[a-f0-9]{64}$/D',$token);
        $this->assertStringNotContainsString(explode(':',$token)[1],$device->getRawOriginal('token_hash'));
        $this->assertSame(30,$device->created_at->diffInDays($device->expires_at));
        $this->assertArrayNotHasKey('token_hash',$device->toArray());
        $this->assertStringNotContainsString(explode(':',$token)[1],DB::table('user_access_audits')->get()->toJson());
        $this->signOut();$this->withCookie(app(TrustedDevices::class)->cookieName($this->operator),$token);
        $this->get(route('profile.show'))->assertRedirect(route('login'));
        $this->login(null,'wrong-password')->assertSessionHasErrors('email');$this->assertGuest();
        $response=$this->login()->assertRedirect('/home');$cookie=$response->getCookie(app(TrustedDevices::class)->cookieName($this->operator));
        $this->assertTrue($cookie->isHttpOnly());$this->assertSame('strict',$cookie->getSameSite());$this->assertNull($cookie->getDomain());$this->assertSame('/',$cookie->getPath());
        $this->assertNotSame($token,$this->plainCookie($cookie));$this->assertTrue($device->fresh()->expires_at->eq($device->expires_at));
        $this->get(route('profile.show'))->assertOk();$this->assertEquals($device->id,session('two_factor_via_device'));
    }

    public function test_cookie_rotation_rejects_replay_and_other_browser_or_agent(): void
    {
        $token=$this->issue();$name=app(TrustedDevices::class)->cookieName($this->operator);$this->signOut();$this->withCookie($name,$token);$this->login()->assertRedirect();$this->get(route('profile.show'))->assertOk();
        $this->signOut();$this->withCookie($name,$token);$this->login()->assertRedirect();$this->get(route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
    }

    public function test_changed_browser_agent_invalidates_the_cookie_without_using_ip_as_identity(): void
    {
        $token=$this->issue();$this->signOut();$this->withCookie(app(TrustedDevices::class)->cookieName($this->operator),$token);$this->withServerVariables(['HTTP_USER_AGENT'=>'Mozilla/5.0 Firefox/120.0']);
        $this->login()->assertRedirect();$this->get(route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
    }

    public function test_forged_token_other_user_and_environment_cannot_skip_mfa(): void
    {
        $token=$this->issue();$name=app(TrustedDevices::class)->cookieName($this->operator);$this->signOut();
        $this->withCookie($name,substr($token,0,33).str_repeat('0',64));$this->login()->assertRedirect();$this->get(route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
        $this->signOut();$other=$this->actor('Admin');User::withoutEvents(fn()=>$other->forceFill(['password'=>Hash::make(self::PASSWORD),'force_password_change'=>false,'two_factor_secret'=>self::SECRET,'two_factor_confirmed_at'=>now()])->save());
        $this->withCookie($name,$token);$this->login($other)->assertRedirect();$this->get(route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
        $this->signOut();config(['app.url'=>'https://ife.bo']);$this->withCookie(app(TrustedDevices::class)->cookieName($this->operator),$token);$this->login()->assertRedirect();$this->get(route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
    }

    public function test_expiry_and_cookie_deletion_require_code_again(): void
    {
        $token=$this->issue();$name=app(TrustedDevices::class)->cookieName($this->operator);$this->signOut();$this->withCookie($name,$token);$this->travel(30)->days();$this->travel(1)->seconds();
        $this->login()->assertRedirect();$this->get(route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));$this->signOut();$this->travelBack();
        $this->withCookie($name,'');$this->login()->assertRedirect();$this->get(route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
    }

    public function test_device_list_only_contains_own_devices_and_revocation_is_not_horizontal(): void
    {
        $this->issue();$own=UserTrustedDevice::sole();$other=$this->actor('Admin');$foreign=$own->replicate();$foreign->user_id=$other->id;$foreign->selector=bin2hex(random_bytes(16));$foreign->name='Dispositivo ajeno';$foreign->save();
        $this->get(route('security.devices.index'))->assertOk()->assertSee('PC recepción')->assertDontSee('Dispositivo ajeno');
        $this->deleteJson(route('security.devices.revoke',$foreign->id))->assertNotFound();$this->assertNull($foreign->fresh()->revoked_at);
        $this->delete(route('security.devices.revoke',$own->id))->assertRedirect();$this->assertNotNull($own->fresh()->revoked_at);
    }

    public function test_revoked_or_expired_trusted_session_is_rechecked_on_every_request(): void
    {
        $token=$this->issue();$device=UserTrustedDevice::sole();$this->signOut();$this->withCookie(app(TrustedDevices::class)->cookieName($this->operator),$token);$this->login()->assertRedirect();$this->get(route('profile.show'))->assertOk();
        $device->update(['revoked_at'=>now()]);$this->get(route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
        $this->assertNull(session('two_factor_via_device'));
    }

    public function test_no_client_field_can_add_trust_without_recent_real_verification(): void
    {
        $this->postJson(route('security.devices.trust'),['trust_device'=>1,'two_factor_verified'=>0,'code'=>'000000'])->assertForbidden();$this->assertSame(0,UserTrustedDevice::count());
        $this->postJson(route('security.two-factor.verify'),['code'=>'invalid','trust_device'=>1])->assertUnprocessable();
        $this->assertSame(0,UserTrustedDevice::count());$this->assertNull(session('trusted_device_offer'));
        $this->post(route('security.two-factor.verify'),['code'=>$this->code()])->assertRedirect();$this->travel(6)->minutes();
        $this->postJson(route('security.devices.trust'),['trust_device'=>1])->assertForbidden();$this->travelBack();
    }

    public function test_password_reset_block_and_two_factor_reset_revoke_all_trust(): void
    {
        $this->issue();$device=UserTrustedDevice::sole();app(AccountSecurity::class)->password($this->operator,self::PASSWORD,true,'Local test reset');$this->assertNotNull($device->fresh()->revoked_at);
        $other=$this->actor('Admin');User::withoutEvents(fn()=>$other->forceFill(['password'=>Hash::make(self::PASSWORD),'force_password_change'=>false,'two_factor_secret'=>self::SECRET,'two_factor_confirmed_at'=>now()])->save());
        $device->update(['revoked_at'=>null]);app(\App\Services\UserAdministration::class)->state($this->operator,['status'=>'blocked','reason'=>'Prueba autorizada']);$this->assertNotNull($device->fresh()->revoked_at);
        $this->actingAs($other->fresh());$this->withSession(['auth_version'=>0,'two_factor_verified'=>0]);$device->update(['revoked_at'=>null]);
        $this->delete(route('users.access.two-factor',$this->operator),['current_password'=>self::PASSWORD,'reason'=>'Prueba de recuperación autorizada'])->assertRedirect();
        $this->assertNull($this->operator->fresh()->two_factor_confirmed_at);$this->assertNotNull($device->fresh()->revoked_at);
    }

    public function test_revoke_all_and_close_other_sessions_keep_current_login_and_need_password(): void
    {
        $this->issue();$device=UserTrustedDevice::sole();
        $this->deleteJson(route('security.devices.revoke-all'),['current_password'=>'wrong'])->assertUnprocessable();$this->assertNull($device->fresh()->revoked_at);
        $this->post(route('security.sessions.others'),['current_password'=>self::PASSWORD])->assertRedirect();
        $this->assertSame(1,(int)$this->operator->fresh()->auth_version);$this->assertNotNull($device->fresh()->revoked_at);$this->get(route('profile.show'))->assertOk();
        $this->withSession(['auth_version'=>0]);$this->getJson(route('profile.show'))->assertUnauthorized();
    }

    public function test_native_remember_me_cannot_bypass_password_and_trust(): void
    {
        $this->operator->setRememberToken(str_repeat('r',60));User::withoutEvents(fn()=>$this->operator->save());
        $guard=auth()->guard('web');$recaller=$guard->getRecallerName();
        $this->signOut();$user=$this->operator->fresh();$value=$user->id.'|'.$user->getRememberToken().'|'.$user->password;
        $this->withCookie($recaller,$value);$this->get(route('profile.show'))->assertRedirect(route('login'));
        $response=$this->post('/login',['email'=>$this->operator->email,'password'=>self::PASSWORD,'remember'=>1])->assertRedirect();$response->assertCookieMissing($recaller);
    }

    public function test_production_cookie_requires_https_and_label_and_scope_are_distinct(): void
    {
        $trust=app(TrustedDevices::class);$local=$trust->cookieName($this->operator);$this->assertSame('IFE LOCAL',app(UserTwoFactor::class)->issuer());
        $this->app['env']='production';config(['app.url'=>'https://ife.bo']);$this->assertNotSame($local,$trust->cookieName($this->operator));$this->assertStringStartsWith('__Host-',$trust->cookieName($this->operator));
        $this->assertSame('IFE PRODUCCIÓN',app(UserTwoFactor::class)->issuer());
        $insecure=\Illuminate\Http\Request::create('http://ife.bo');$insecure->setLaravelSession(session());$insecure->session()->put('two_factor_verified',0);$trust->offer($this->operator,$insecure);
        try{$trust->issue($this->operator,$insecure);$this->fail('Accepted production trust over HTTP');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(403,$e->getStatusCode());}
        $request=\Illuminate\Http\Request::create('https://ife.bo');$request->setLaravelSession(session());$request->headers->set('User-Agent',self::AGENT);$request->session()->put('two_factor_verified',0);$trust->offer($this->operator,$request);
        $trust->issue($this->operator,$request);$cookie=app('cookie')->queued($trust->cookieName($this->operator));
        $this->assertTrue($cookie->isSecure());$this->assertTrue($cookie->isHttpOnly());$this->assertNull($cookie->getDomain());$this->assertSame('/',$cookie->getPath());
        $this->app['env']='testing';Http::assertNothingSent();
    }

    public function test_csrf_protects_trust_and_revocation_and_malformed_wire_cookie_is_rejected(): void
    {
        $this->post(route('security.two-factor.verify'),['code'=>$this->code()])->assertRedirect();
        $this->partialMock(\App\Http\Middleware\VerifyCsrfToken::class,function($mock){$mock->shouldAllowMockingProtectedMethods()->shouldReceive('runningUnitTests')->andReturn(false);});
        $csrf=str_repeat('t',40);$this->withSession(['_token'=>$csrf]);
        $this->post(route('security.devices.trust'),['trust_device'=>1])->assertStatus(419);$this->assertSame(0,UserTrustedDevice::count());
        $this->post(route('security.devices.trust'),['_token'=>$csrf,'trust_device'=>1])->assertRedirect();$device=UserTrustedDevice::sole();
        $this->delete(route('security.devices.revoke',$device->id))->assertStatus(419);$this->assertNull($device->fresh()->revoked_at);
        $this->delete(route('security.devices.revoke',$device->id),['_token'=>$csrf])->assertRedirect();$this->assertNotNull($device->fresh()->revoked_at);
        $this->post(route('logout'),['_token'=>$csrf])->assertRedirect();$this->app['auth']->forgetGuards();
        $this->withUnencryptedCookie(app(TrustedDevices::class)->cookieName($this->operator),'tampered-encrypted-cookie');
        $this->post('/login',['_token'=>session()->token(),'email'=>$this->operator->email,'password'=>self::PASSWORD])->assertRedirect();
        $this->get(route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
    }

    public function test_ip_change_does_not_replace_or_break_the_secure_device_token(): void
    {
        $token=$this->issue();$this->signOut();$this->withCookie(app(TrustedDevices::class)->cookieName($this->operator),$token);$this->withServerVariables(['REMOTE_ADDR'=>'192.0.2.52','HTTP_USER_AGENT'=>self::AGENT]);
        $this->login()->assertRedirect();$this->get(route('profile.show'))->assertOk();
    }

    public function test_normal_browser_update_keeps_the_fixed_thirty_day_consent(): void
    {
        $token=$this->issue();$expires=UserTrustedDevice::sole()->expires_at;$this->signOut();
        $this->withCookie(app(TrustedDevices::class)->cookieName($this->operator),$token);$this->withServerVariables(['HTTP_USER_AGENT'=>'Mozilla/5.0 Windows NT 10.0 Chrome/121.0']);
        $this->login()->assertRedirect();$this->get(route('profile.show'))->assertOk();$this->assertTrue(UserTrustedDevice::sole()->expires_at->eq($expires));
    }

    public function test_current_password_confirmation_preserves_significant_whitespace(): void
    {
        $password=' Testing!Password#73 ';
        User::withoutEvents(fn()=>$this->operator->forceFill(['password'=>Hash::make($password)])->save());
        $this->post(route('security.sessions.others'),['current_password'=>$password])->assertRedirect(route('security.devices.index'))->assertSessionHasNoErrors();
        $this->assertSame(1,(int)$this->operator->fresh()->auth_version);
    }

    public function test_remember_email_only_prefills_login_without_authenticating_and_can_be_cleared(): void
    {
        $this->signOut();
        $response=$this->post('/login',['email'=>$this->operator->email,'password'=>self::PASSWORD,'remember'=>1])->assertRedirect();
        $cookies=array_values(array_filter($response->headers->getCookies(),fn($cookie)=>str_contains($cookie->getName(),'ife_login_email_')));
        $this->assertCount(1,$cookies);$cookie=$cookies[0];
        $this->assertSame($this->operator->email,$this->plainCookie($cookie));
        $this->assertStringNotContainsString($this->operator->email,$cookie->getValue());
        $this->assertTrue($cookie->isHttpOnly());$this->assertNull($cookie->getDomain());$this->assertSame('lax',$cookie->getSameSite());
        $this->signOut();$this->withCookie($cookie->getName(),$this->operator->email);
        $html=$this->get('/login')->assertOk()->assertSee('Recordar mi correo')->getContent();
        $dom=new \DOMDocument;@$dom->loadHTML($html);$xpath=new \DOMXPath($dom);
        $this->assertSame($this->operator->email,$xpath->query('//input[@name="email"]')->item(0)->getAttribute('value'));
        $this->assertTrue($xpath->query('//input[@name="remember"]')->item(0)->hasAttribute('checked'));
        $this->assertSame('',$xpath->query('//input[@name="password"]')->item(0)->getAttribute('value'));
        $this->assertGuest();$this->get(route('profile.show'))->assertRedirect(route('login'));
        $cleared=$this->login()->assertRedirect()->getCookie($cookie->getName());
        $this->assertNotNull($cleared);$this->assertLessThan(time(),$cleared->getExpiresTime());
    }
}

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
        return $this->plainCookie($response->getCookie(app(TrustedDevices::class)->cookieName()));
    }

    private function signOut(): void { $this->post(route('logout'))->assertRedirect();$this->app['auth']->forgetGuards(); }
    private function login(?User $user=null,string $password=self::PASSWORD)
    {
        $user=$user?:$this->operator;
        return $this->post('/login',['email'=>$user->email,'password'=>$password]);
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
        $this->signOut();$this->withCookie(app(TrustedDevices::class)->cookieName(),$token);
        $this->get(route('profile.show'))->assertRedirect(route('login'));
        $this->login(null,'wrong-password')->assertSessionHasErrors('email');$this->assertGuest();
        $response=$this->login()->assertRedirect('/home');$cookie=$response->getCookie(app(TrustedDevices::class)->cookieName());
        $this->assertTrue($cookie->isHttpOnly());$this->assertSame('strict',$cookie->getSameSite());$this->assertNull($cookie->getDomain());$this->assertSame('/',$cookie->getPath());
        $this->assertNotSame($token,$this->plainCookie($cookie));$this->assertTrue($device->fresh()->expires_at->eq($device->expires_at));
        $this->get(route('profile.show'))->assertOk();$this->assertEquals($device->id,session('two_factor_via_device'));
    }

    public function test_cookie_rotation_rejects_replay_and_other_browser_or_agent(): void
    {
        $token=$this->issue();$name=app(TrustedDevices::class)->cookieName();$this->signOut();$this->withCookie($name,$token);$this->login()->assertRedirect();$this->get(route('profile.show'))->assertOk();
        $this->signOut();$this->withCookie($name,$token);$this->login()->assertRedirect();$this->get(route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
    }

    public function test_changed_browser_agent_invalidates_the_cookie_without_using_ip_as_identity(): void
    {
        $token=$this->issue();$this->signOut();$this->withCookie(app(TrustedDevices::class)->cookieName(),$token);$this->withServerVariables(['HTTP_USER_AGENT'=>'Mozilla/5.0 Firefox/120.0']);
        $this->login()->assertRedirect();$this->get(route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
    }

    public function test_forged_token_other_user_and_environment_cannot_skip_mfa(): void
    {
        $token=$this->issue();$name=app(TrustedDevices::class)->cookieName();$this->signOut();
        $this->withCookie($name,substr($token,0,33).str_repeat('0',64));$this->login()->assertRedirect();$this->get(route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
        $this->signOut();$other=$this->actor('Admin');User::withoutEvents(fn()=>$other->forceFill(['password'=>Hash::make(self::PASSWORD),'force_password_change'=>false,'two_factor_secret'=>self::SECRET,'two_factor_confirmed_at'=>now()])->save());
        $this->withCookie($name,$token);$this->login($other)->assertRedirect();$this->get(route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
        $this->signOut();config(['app.url'=>'https://ife.bo']);$this->withCookie(app(TrustedDevices::class)->cookieName(),$token);$this->login()->assertRedirect();$this->get(route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
    }

    public function test_expiry_and_cookie_deletion_require_code_again(): void
    {
        $token=$this->issue();$name=app(TrustedDevices::class)->cookieName();$this->signOut();$this->withCookie($name,$token);$this->travel(30)->days();$this->travel(1)->seconds();
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
        $token=$this->issue();$device=UserTrustedDevice::sole();$this->signOut();$this->withCookie(app(TrustedDevices::class)->cookieName(),$token);$this->login()->assertRedirect();$this->get(route('profile.show'))->assertOk();
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
        $trust=app(TrustedDevices::class);$local=$trust->cookieName();$this->assertSame('IFE LOCAL',app(UserTwoFactor::class)->issuer());
        $this->app['env']='production';config(['app.url'=>'https://ife.bo']);$this->assertNotSame($local,$trust->cookieName());$this->assertStringStartsWith('__Host-',$trust->cookieName());
        $this->assertSame('IFE PRODUCCIÓN',app(UserTwoFactor::class)->issuer());
        $insecure=\Illuminate\Http\Request::create('http://ife.bo');$insecure->setLaravelSession(session());$insecure->session()->put('two_factor_verified',0);$trust->offer($this->operator,$insecure);
        try{$trust->issue($this->operator,$insecure);$this->fail('Accepted production trust over HTTP');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(403,$e->getStatusCode());}
        $request=\Illuminate\Http\Request::create('https://ife.bo');$request->setLaravelSession(session());$request->headers->set('User-Agent',self::AGENT);$request->session()->put('two_factor_verified',0);$trust->offer($this->operator,$request);
        $trust->issue($this->operator,$request);$cookie=app('cookie')->queued($trust->cookieName());
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
        $this->withUnencryptedCookie(app(TrustedDevices::class)->cookieName(),'tampered-encrypted-cookie');
        $this->post('/login',['_token'=>session()->token(),'email'=>$this->operator->email,'password'=>self::PASSWORD])->assertRedirect();
        $this->get(route('profile.show'))->assertRedirect(route('security.two-factor.challenge'));
    }

    public function test_ip_change_does_not_replace_or_break_the_secure_device_token(): void
    {
        $token=$this->issue();$this->signOut();$this->withCookie(app(TrustedDevices::class)->cookieName(),$token);$this->withServerVariables(['REMOTE_ADDR'=>'192.0.2.52','HTTP_USER_AGENT'=>self::AGENT]);
        $this->login()->assertRedirect();$this->get(route('profile.show'))->assertOk();
    }

    public function test_normal_browser_update_keeps_the_fixed_thirty_day_consent(): void
    {
        $token=$this->issue();$expires=UserTrustedDevice::sole()->expires_at;$this->signOut();
        $this->withCookie(app(TrustedDevices::class)->cookieName(),$token);$this->withServerVariables(['HTTP_USER_AGENT'=>'Mozilla/5.0 Windows NT 10.0 Chrome/121.0']);
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

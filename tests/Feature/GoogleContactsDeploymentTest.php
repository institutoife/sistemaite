<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\GoogleContactsTestCase;

class GoogleContactsDeploymentTest extends GoogleContactsTestCase
{
    public function test_check_is_read_only_and_never_prints_credentials_or_tokens()
    {
        $account = $this->account();
        $before = DB::table('google_contact_accounts')->first();
        $this->assertSame(0, Artisan::call('gcontact:check'));
        $output = Artisan::output();
        foreach (['access-test-only', 'refresh-test-only', 'test-secret', config('app.key')] as $secret) {
            $this->assertStringNotContainsString($secret, $output);
        }
        $this->assertStringContainsString('No se realizarán cambios ni solicitudes externas.', $output);
        $this->assertEquals($before, DB::table('google_contact_accounts')->where('id', $account->id)->first());
        $this->assertSame(0, DB::table('jobs')->count());
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_production_check_rejects_local_settings_and_cannot_activate_a_worker()
    {
        $this->account();
        $this->assertSame(1, Artisan::call('gcontact:check', ['--production' => true]));
        $output = Artisan::output();
        $this->assertStringContainsString('FALTA', $output);
        $this->assertStringContainsString('Modo Cron', $output);
        $this->assertStringContainsString('https://ife.bo/auth/google/callback', $output);
        $this->assertSame(0, DB::table('jobs')->count());
        Http::assertNothingSent();
    }

    public function test_first_install_can_check_before_oauth_but_normal_check_requires_a_connected_account()
    {
        $this->assertSame(1, Artisan::call('gcontact:check'));
        $this->assertSame(0, Artisan::call('gcontact:check', ['--allow-disconnected' => true]));
        $this->assertStringContainsString('Conectar Google desde AdminLTE', Artisan::output());
        $this->assertSame(0, DB::table('google_contact_accounts')->count());
        Http::assertNothingSent();
    }

    public function test_legacy_captcha_rule_uses_configuration_and_keeps_its_score_threshold()
    {
        config(['recaptchav3.secret' => 'captcha-test-secret']);
        $this->fakeHttp(function ($request) {
            $this->assertSame('captcha-test-secret', $request['secret']);
            return Http::response(['success' => true, 'score' => $request['response'] === 'valid-test' ? 0.9 : 0.7]);
        });
        $rule = new \App\Rules\Recaptcha;
        $this->assertTrue($rule->passes('recaptcha', 'valid-test'));
        $this->assertFalse($rule->passes('recaptcha', 'low-score-test'));
    }

    public function test_legacy_captcha_without_configured_secret_never_calls_google()
    {
        config(['recaptchav3.secret' => null]);
        $this->assertFalse((new \App\Rules\Recaptcha)->passes('recaptcha', 'test-token'));
        Http::assertNothingSent();
    }

    public function test_legacy_captcha_connection_failure_is_rejected_without_exposing_credentials()
    {
        config(['recaptchav3.secret' => 'captcha-test-secret']);
        $this->fakeHttp(function () { throw new \Illuminate\Http\Client\ConnectionException('sensitive-connection-detail'); });
        $this->assertFalse((new \App\Rules\Recaptcha)->passes('recaptcha', 'test-token'));
    }
}

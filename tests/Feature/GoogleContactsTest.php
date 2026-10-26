<?php

namespace Tests\Feature;

use App\Exceptions\GoogleContactsException;
use App\Jobs\SyncGoogleContact;
use App\Models\GoogleContactAccount;
use App\Models\Persona;
use App\Services\GoogleContactsService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\GoogleContactsTestCase;

class GoogleContactsTest extends GoogleContactsTestCase
{
    private function googleUser(array $overrides = []): GoogleUser
    {
        $user = (new GoogleUser)->map(['id' => 'test-google-user', 'email' => 'central@example.test']);
        $user->token = 'oauth-access-test';
        $user->refreshToken = 'oauth-refresh-test';
        $user->expiresIn = 3600;
        $user->approvedScopes = [GoogleContactsService::SCOPE];
        foreach ($overrides as $key => $value) {
            $user->{$key} = $value;
        }
        return $user;
    }

    public function test_tokens_are_encrypted_hidden_and_refresh_is_preserved_on_reconnection()
    {
        $actor = $this->actor();
        $this->service()->connect($this->googleUser(), $actor->id);
        $stored = DB::table('google_contact_accounts')->first();
        $this->assertNotSame('oauth-access-test', $stored->access_token);
        $this->assertNotSame('oauth-refresh-test', $stored->refresh_token);
        $this->service()->connect($this->googleUser(['refreshToken' => null]), $actor->id);
        $account = $this->service()->account();
        $this->assertSame('oauth-refresh-test', $account->refresh_token);
        $this->assertArrayNotHasKey('refresh_token', $account->toArray());
        $this->assertArrayNotHasKey('access_token', $account->toArray());
        $this->assertSame(1, GoogleContactAccount::count());
    }

    public function test_cannot_replace_the_central_google_identity()
    {
        $this->account();
        $other = $this->googleUser();
        $other->id = 'another-google-account';
        $this->expectException(GoogleContactsException::class);
        $this->service()->connect($other, $this->actor()->id);
    }

    public function test_initial_connection_requires_refresh_token_and_contacts_scope()
    {
        foreach ([['refreshToken' => null], ['approvedScopes' => ['openid']]] as $override) {
            try {
                $this->service()->connect($this->googleUser($override), $this->actor()->id);
                $this->fail('No debe aceptar permisos incompletos.');
            } catch (GoogleContactsException $e) {
                $this->assertContains($e->reason, ['missing_scope', 'missing_refresh_token']);
            }
        }
        $this->assertSame(0, GoogleContactAccount::count());
    }

    public function test_oauth_routes_require_authentication_and_authorization()
    {
        $this->get('/auth/google')->assertRedirect('/login');
        $this->get('/auth/google/callback')->assertRedirect('/login');
        $this->actingAs($this->actor('Padre'))->get('/auth/google')->assertForbidden();
        $this->get('/auth/google/callback')->assertForbidden();
        $this->get('/contactos/google')->assertForbidden();
        $this->post('/contactos/google/'.$this->persona()->id.'/sincronizar')->assertForbidden();
        $this->get('/debug/gcontact-token')->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_oauth_redirect_requests_offline_access_and_has_state()
    {
        $response = $this->actingAs($this->actor())->get('/auth/google');
        $response->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('offline', $query['access_type']);
        $this->assertSame('consent', $query['prompt']);
        $this->assertNotEmpty($query['state']);
        $this->assertStringContainsString(GoogleContactsService::SCOPE, $query['scope']);
    }

    public function test_callback_stores_connection_and_clears_legacy_session_tokens()
    {
        $provider = \Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($this->googleUser());
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
        $this->actingAs($this->actor())->withSession(['GContactToken' => 'legacy-sensitive-token'])
            ->get('/auth/google/callback?code=simulated')->assertRedirect(route('contactos.index'));
        $this->assertSame(1, GoogleContactAccount::count());
        $this->assertNull(session('GContactToken'));
    }

    public function test_invalid_oauth_state_and_denial_do_not_store_tokens()
    {
        $this->actingAs($this->actor())->get('/auth/google/callback?code=invalid-state')
            ->assertRedirect(route('contactos.index'))->assertSessionHas('error');
        $this->get('/auth/google/callback?error=access_denied')
            ->assertRedirect(route('contactos.index'))->assertSessionHas('error');
        $this->assertSame(0, GoogleContactAccount::count());
        Http::assertNothingSent();
    }

    public function test_panel_renders_adminlte_without_exposing_tokens()
    {
        $this->account();
        $this->persona();
        $this->actingAs($this->actor())->get('/contactos/google')->assertOk()
            ->assertSee('Google Contactos')->assertSee('Lucas')->assertSee('Conectado')
            ->assertDontSee('access-test-only')->assertDontSee('refresh-test-only');
        Http::assertNothingSent();
    }

    public function test_request_only_enqueues_even_if_default_queue_is_sync()
    {
        $this->account();
        $persona = $this->persona();
        $this->actingAs($this->actor())->post('/contactos/google/'.$persona->id.'/sincronizar')
            ->assertRedirect(route('contactos.index'));
        Queue::assertPushed(SyncGoogleContact::class, function ($job) use ($persona) {
            return $job->personaId === $persona->id && $job->connection === 'google_contacts'
                && $job->queue === 'google-contacts' && $job->afterCommit === true;
        });
        $this->assertSame('database', config('queue.connections.google_contacts.driver'));
        Http::assertNothingSent();
    }

    public function test_create_and_ten_repetitions_keep_one_remote_contact()
    {
        $this->account();
        $persona = $this->persona();
        $this->fakeHttp([
            '*people/me/connections*' => Http::response(['connections' => []]),
            '*people:createContact*' => Http::response($this->remote()),
        ]);
        for ($i = 0; $i < 10; $i++) {
            $this->service()->sync($persona->id);
        }
        $this->assertSame('people/contact1', $persona->fresh()->resourseName);
        $this->assertSame('synced', $persona->fresh()->gcontact_sync_status);
        Http::assertSentCount(2);
        Http::assertSent(function ($request) {
            return $request->method() === 'POST' && $request['phoneNumbers'][0]['value'] === '+59160902299'
                && $request['userDefined'][0]['key'] === 'IFE_PERSONA';
        });
    }

    public function test_ten_manual_resyncs_update_the_same_contact()
    {
        $this->account();
        $persona = $this->persona();
        $creates = 0;
        $updates = 0;
        $this->fakeHttp(function ($request) use (&$creates, &$updates) {
            if ($request->method() === 'POST') {
                $creates++;
            }
            if ($request->method() === 'PATCH') {
                $updates++;
            }
            return strpos($request->url(), '/connections') !== false
                ? Http::response(['connections' => []]) : Http::response($this->remote());
        });
        for ($i = 0; $i < 10; $i++) {
            $this->service()->markPending($persona, null, false);
            $this->service()->sync($persona->id);
        }
        $this->assertSame(1, $creates);
        $this->assertSame(9, $updates);
        $this->assertSame('people/contact1', $persona->fresh()->resourseName);
    }

    public function test_database_queue_commits_then_worker_processes_the_job()
    {
        $this->account();
        $persona = $this->persona();
        Queue::swap($this->realQueue);
        $this->fakeHttp([
            '*people/me/connections*' => Http::response(['connections' => []]),
            '*people:createContact*' => Http::response($this->remote()),
        ]);
        DB::beginTransaction();
        $this->service()->markPending($persona);
        $this->assertSame(0, DB::table('jobs')->count());
        Http::assertNothingSent();
        DB::commit();
        $this->assertSame(1, DB::table('jobs')->count());
        $payload = DB::table('jobs')->value('payload');
        $this->assertStringNotContainsString('access-test-only', $payload);
        $this->assertStringNotContainsString('refresh-test-only', $payload);
        $job = Queue::connection('google_contacts')->pop('google-contacts');
        $this->assertNotNull($job);
        $job->fire();
        $this->assertSame('synced', $persona->fresh()->gcontact_sync_status);
        $this->assertSame(0, DB::table('jobs')->count());
    }

    public function test_repeated_clicks_share_one_pending_job()
    {
        $this->account();
        $persona = $this->persona();
        for ($i = 0; $i < 10; $i++) {
            $this->service()->markPending($persona);
        }
        Queue::assertPushed(SyncGoogleContact::class, 1);
        Http::assertNothingSent();
    }

    public function test_rollback_does_not_leave_an_orphaned_unique_job_lock()
    {
        $this->account();
        $persona = $this->persona();
        Queue::swap($this->realQueue);
        DB::beginTransaction();
        $this->service()->markPending($persona);
        DB::rollBack();
        $this->assertSame(0, DB::table('jobs')->count());
        $this->service()->markPending($persona);
        $this->assertSame(1, DB::table('jobs')->count());
        Http::assertNothingSent();
    }

    public function test_pending_command_recovers_preexisting_pending_flags()
    {
        $this->account();
        $this->persona(['gcontact_sync_status' => 'idle', 'gcontact_sync_pending' => true]);
        $this->artisan('gcontact:sync-batch --only-pending')->assertExitCode(0);
        Queue::assertPushed(SyncGoogleContact::class, 1);
        Http::assertNothingSent();
    }

    public function test_legacy_link_without_etag_is_updated_with_fresh_source_metadata()
    {
        $this->account();
        $persona = $this->persona(['resourseName' => 'people/contact1', 'etag' => null]);
        $this->fakeHttp([
            '*:updateContact*' => Http::response($this->remote('people/contact1', 'updated-etag')),
            '*people/contact1*' => Http::response($this->remote('people/contact1', 'fresh-source-etag')),
        ]);
        $this->service()->sync($persona->id);
        $this->assertSame('updated-etag', $persona->fresh()->etag);
        Http::assertSent(function ($request) {
            return $request->method() === 'PATCH'
                && $request['metadata']['sources'][0]['etag'] === 'fresh-source-etag'
                && strpos($request->url(), 'emailAddresses') === false
                && !isset($request['emailAddresses']);
        });
        Http::assertSentCount(2);
    }

    public function test_expired_access_token_is_refreshed_without_browser_session()
    {
        $this->account(['expires_at' => now()->subMinute()]);
        $persona = $this->persona();
        $this->fakeHttp([
            '*oauth2.googleapis.com/token' => Http::response(['access_token' => 'renewed-test', 'expires_in' => 3600]),
            '*people/me/connections*' => Http::response(['connections' => []]),
            '*people:createContact*' => Http::response($this->remote()),
        ]);
        $this->service()->sync($persona->id);
        $this->assertSame('renewed-test', $this->service()->account()->access_token);
        $this->assertSame('refresh-test-only', $this->service()->account()->refresh_token);
        Http::assertSent(function ($request) {
            return strpos($request->url(), 'people.googleapis.com') !== false && $request->hasHeader('Authorization', 'Bearer renewed-test');
        });
    }

    public function test_revoked_authorization_requires_reconnection_and_error_contains_no_secrets()
    {
        $this->account(['expires_at' => now()->subMinute()]);
        $persona = $this->persona();
        $this->fakeHttp(['*oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant', 'detail' => 'refresh-test-only'], 400)]);
        (new SyncGoogleContact($persona->id))->handle($this->service());
        $this->assertTrue($this->service()->account()->requires_reconnection);
        $this->assertSame('error', $persona->fresh()->gcontact_sync_status);
        $this->assertStringNotContainsString('refresh-test-only', $persona->fresh()->gcontact_sync_error);
        Http::assertSentCount(1);
    }

    public function test_blocked_worker_network_is_reported_without_invalidating_google_authorization()
    {
        $this->account(['expires_at' => now()->subMinute()]);
        $persona = $this->persona();
        $this->fakeHttp(function () {
            throw new ConnectionException('cURL error 7: proxy failure with sensitive-test-data');
        });
        try {
            (new SyncGoogleContact($persona->id))->handle($this->service());
            $this->fail('El fallo de red debe reintentarse.');
        } catch (GoogleContactsException $e) {
            $this->assertSame('oauth_network', $e->reason);
        }
        $this->assertFalse($this->service()->account()->requires_reconnection);
        $this->assertSame('refresh-test-only', $this->service()->account()->refresh_token);
        $this->assertStringNotContainsString('sensitive-test-data', $persona->fresh()->gcontact_sync_error);
        $this->assertNull($persona->fresh()->gcontact_create_started_at);
    }

    public function test_certificate_errors_are_distinguished_from_timeouts()
    {
        $this->account(['expires_at' => now()->subMinute()]);
        $persona = $this->persona();
        $this->fakeHttp(function () {
            throw new ConnectionException('cURL error 60: certificate verification failed');
        });
        (new SyncGoogleContact($persona->id))->handle($this->service());
        $this->assertSame('oauth_tls', $persona->fresh()->gcontact_error_code);
        $this->assertSame('error', $persona->fresh()->gcontact_sync_status);
        $this->assertFalse($this->service()->account()->requires_reconnection);
    }

    public function test_create_timeout_blocks_blind_recreation_and_can_recover_by_marker()
    {
        $account = $this->account();
        $persona = $this->persona();
        $this->fakeHttp(function ($request) {
            if ($request->method() === 'POST') {
                throw new ConnectionException('sensitive-response-must-not-be-stored');
            }
            return Http::response(['connections' => []]);
        });
        try {
            $this->service()->sync($persona->id);
            $this->fail('Debe registrar resultado incierto.');
        } catch (GoogleContactsException $e) {
            $this->assertSame('timeout', $e->reason);
        }
        $this->assertNotNull($persona->fresh()->gcontact_create_started_at);
        $this->fakeHttp(['*people/me/connections*' => Http::response(['connections' => []])]);
        try {
            $this->service()->sync($persona->id);
            $this->fail('No debe crear otra vez.');
        } catch (GoogleContactsException $e) {
            $this->assertSame('uncertain_create', $e->reason);
        }
        Http::assertSentCount(1);
        $remote = $this->remote();
        $remote['userDefined'] = [['key' => 'IFE_PERSONA', 'value' => $account->installation_id.':'.$persona->id]];
        $this->fakeHttp([
            '*people/me/connections*' => Http::response(['connections' => [$remote]]),
            '*:updateContact*' => Http::response($this->remote()),
            '*people/contact1*' => Http::response($remote),
        ]);
        $this->service()->sync($persona->id);
        $this->assertSame('people/contact1', $persona->fresh()->resourseName);
        $this->assertNull($persona->fresh()->gcontact_create_started_at);
        Http::assertSentCount(3);
    }

    public function test_deleted_google_contact_is_not_recreated()
    {
        $this->account();
        $persona = $this->persona(['resourseName' => 'people/missing']);
        $this->fakeHttp(['*people/missing*' => Http::response(['error' => 'not found'], 404)]);
        (new SyncGoogleContact($persona->id))->handle($this->service());
        $this->assertSame('people/missing', $persona->fresh()->resourseName);
        $this->assertSame('not_found', $persona->fresh()->gcontact_error_code);
        Http::assertSentCount(1);
    }

    public function test_successful_but_malformed_create_response_does_not_allow_duplicate_create()
    {
        $this->account();
        $persona = $this->persona();
        $this->fakeHttp([
            '*people/me/connections*' => Http::response(['connections' => []]),
            '*people:createContact*' => Http::response('invalid json', 200),
        ]);
        (new SyncGoogleContact($persona->id))->handle($this->service());
        $this->assertNotNull($persona->fresh()->gcontact_create_started_at);
        $this->assertSame('invalid_response', $persona->fresh()->gcontact_error_code);
        $this->fakeHttp(['*people/me/connections*' => Http::response(['connections' => []])]);
        (new SyncGoogleContact($persona->id))->handle($this->service());
        $this->assertSame('uncertain_create', $persona->fresh()->gcontact_error_code);
        Http::assertSentCount(1);
    }

    public function test_data_changed_during_sync_remains_pending()
    {
        $this->account();
        $persona = $this->persona();
        $this->fakeHttp(function ($request) use ($persona) {
            if ($request->method() === 'POST') {
                $fresh = $persona->fresh();
                $fresh->telefono = '71002299';
                $fresh->save();
                return Http::response($this->remote());
            }
            return Http::response(['connections' => []]);
        });
        $this->service()->sync($persona->id);
        $this->assertTrue((bool) $persona->fresh()->gcontact_sync_pending);
        $this->assertSame('pending', $persona->fresh()->gcontact_sync_status);
        $this->assertSame('71002299', $persona->fresh()->telefono);
    }

    public function test_shared_phone_and_multiple_guardians_preserve_separate_person_identities()
    {
        $this->account();
        $student = $this->persona();
        $father = $this->persona(['nombre' => 'Juan']);
        $mother = $this->persona(['nombre' => 'Maria']);
        DB::table('estudiantes')->whereIn('persona_id', [$father->id, $mother->id])->delete();
        $student->apoderados()->attach($father->id, ['parentesco' => 'Padre']);
        $student->apoderados()->attach($mother->id, ['parentesco' => 'Madre']);
        $sequence = 0;
        $this->fakeHttp(function ($request) use (&$sequence) {
            return $request->method() === 'POST' ? Http::response($this->remote('people/person'.(++$sequence)))
                : Http::response(['connections' => []]);
        });
        foreach ([$student, $father, $mother] as $person) {
            $this->service()->sync($person->id);
        }
        $this->assertSame(3, $sequence);
        $this->assertNotSame($father->fresh()->resourseName, $mother->fresh()->resourseName);
    }

    public function test_unlinked_legacy_contact_is_flagged_instead_of_duplicated_or_merged_by_phone()
    {
        $this->account();
        $persona = $this->persona();
        $this->fakeHttp(['*people/me/connections*' => Http::response(['connections' => [array_merge($this->remote(), [
            'names' => [['givenName' => 'Lucas', 'familyName' => 'Mamani']],
            'phoneNumbers' => [['value' => '+59160902299']],
        ])]])]);
        (new SyncGoogleContact($persona->id))->handle($this->service());
        $this->assertSame('possible_legacy_contact', $persona->fresh()->gcontact_error_code);
        $this->assertNull($persona->fresh()->resourseName);
        Http::assertSentCount(1);
    }

    public function test_account_larger_than_twenty_thousand_contacts_is_checked_through_last_page()
    {
        $account = $this->account();
        $persona = $this->persona();
        $remote = $this->remote();
        $remote['userDefined'] = [['key' => 'IFE_PERSONA', 'value' => $account->installation_id.':'.$persona->id]];
        $pagesRead = 0;
        $this->fakeHttp(function ($request) use ($remote, &$pagesRead) {
            if (strpos($request->url(), 'people/contact1') !== false) {
                return Http::response($remote);
            }
            if ($request->method() === 'PATCH') {
                return Http::response($remote);
            }
            $this->assertSame('GET', $request->method(), 'No se debe crear un duplicado antes de completar la paginación.');
            $pagesRead++;
            $page = isset($request['pageToken']) ? (int) str_replace('page-', '', $request['pageToken']) : 1;
            return Http::response($page < 22
                ? ['connections' => [], 'nextPageToken' => 'page-'.($page + 1), 'totalItems' => 21315]
                : ['connections' => [$remote], 'totalItems' => 21315]);
        });
        $this->service()->sync($persona->id);
        $this->assertSame(22, $pagesRead);
        $this->assertSame('people/contact1', $persona->fresh()->resourseName);
        $this->assertSame('synced', $persona->fresh()->gcontact_sync_status);
    }

    public function test_missing_phone_is_supported_and_invalid_phone_stops_before_http()
    {
        $this->account();
        $without = $this->persona(['telefono' => null, 'foto' => null]);
        $this->fakeHttp([
            '*people/me/connections*' => Http::response(['connections' => []]),
            '*people:createContact*' => Http::response($this->remote()),
        ]);
        $this->service()->sync($without->id);
        Http::assertSent(function ($request) {
            return $request->method() === 'POST' && $request['phoneNumbers'] === [] && !isset($request['photos']);
        });
        $invalid = $this->persona(['telefono' => 'invalid']);
        (new SyncGoogleContact($invalid->id))->handle($this->service());
        $this->assertSame('invalid_phone', $invalid->fresh()->gcontact_error_code);
        Http::assertSentCount(2);
    }

    public function test_disconnected_changes_are_marked_pending_without_api_or_queue()
    {
        $persona = $this->persona();
        $persona->nombre = 'Lucas modificado';
        $persona->save();
        $this->assertTrue((bool) $persona->fresh()->gcontact_sync_pending);
        $this->assertGreaterThan(0, $persona->fresh()->gcontact_revision);
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_account_lock_prevents_concurrent_google_writes()
    {
        $this->account();
        $persona = $this->persona();
        $lock = Cache::lock('ife:gcontact:central', 180);
        $this->assertTrue($lock->get());
        try {
            $this->service()->sync($persona->id);
            $this->fail('Debe esperar el bloqueo.');
        } catch (GoogleContactsException $e) {
            $this->assertSame('busy', $e->reason);
        } finally {
            $lock->release();
        }
        Http::assertNothingSent();
    }

    public function test_rate_limit_uses_bounded_retries_and_finishes_in_error()
    {
        $this->account();
        $persona = $this->persona();
        $this->fakeHttp(['*people/me/connections*' => Http::response(['error' => 'quota'], 429)]);
        $job = new SyncGoogleContact($persona->id);
        $this->assertSame(5, $job->tries);
        for ($i = 0; $i < $job->tries; $i++) {
            try {
                $job->handle($this->service());
            } catch (GoogleContactsException $e) {
                $this->assertTrue($e->retryable);
            }
        }
        $job->failed(new \RuntimeException('test'));
        $this->assertSame('error', $persona->fresh()->gcontact_sync_status);
        $this->artisan('gcontact:sync-batch --only-pending')->assertExitCode(0);
        Queue::assertNothingPushed();
    }

    public function test_batch_command_enqueues_without_google_calls_and_secrets_in_payload()
    {
        $this->account();
        $this->persona();
        $this->artisan('gcontact:sync-batch --batch=1')->assertExitCode(0);
        Queue::assertPushed(SyncGoogleContact::class, 1);
        Queue::assertPushed(SyncGoogleContact::class, function ($job) {
            return strpos(serialize($job), 'access-test-only') === false && strpos(serialize($job), 'refresh-test-only') === false;
        });
        Http::assertNothingSent();
    }

    public function test_disconnect_keeps_google_links_and_makes_no_delete_requests()
    {
        $account = $this->account();
        $persona = $this->persona(['resourseName' => 'people/contact1', 'gcontact_account_id' => $account->id]);
        $this->actingAs($this->actor())->post('/contactos/google/desconectar')->assertRedirect(route('contactos.index'));
        $this->assertSame('people/contact1', $persona->fresh()->resourseName);
        $this->assertFalse($this->service()->account()->isConnected());
        Http::assertNothingSent();
    }
}

<?php

namespace Tests\Feature;

use App\Jobs\SyncGoogleContact;
use App\Services\GoogleContactsRuntime;
use Database\Seeders\GoogleContactsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Tests\GoogleContactsTestCase;

class GoogleContactsRuntimeTest extends GoogleContactsTestCase
{
    private $originalStorage;
    private $testStorage;

    protected function setUp(): void
    {
        parent::setUp();
        // The test process must never acquire the running server's OS lock.
        $this->originalStorage = storage_path();
        $this->testStorage = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ife-gcontact-'.bin2hex(random_bytes(8));
        mkdir($this->testStorage.DIRECTORY_SEPARATOR.'framework', 0777, true);
        $this->app->useStoragePath($this->testStorage);
    }

    protected function tearDown(): void
    {
        $this->app->useStoragePath($this->originalStorage);
        $lock = $this->testStorage.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'google-contacts-worker.lock';
        if (is_file($lock)) {
            unlink($lock);
        }
        rmdir($this->testStorage.DIRECTORY_SEPARATOR.'framework');
        rmdir($this->testStorage);
        parent::tearDown();
    }

    public function test_pause_is_shared_and_managed_worker_does_not_reserve_or_exhaust_jobs()
    {
        $account = $this->account();
        $first = $this->persona();
        Queue::swap($this->realQueue);
        $this->service()->markPending($first);
        $admin = $this->actor();
        $this->actingAs($admin)->post('/contactos/google/pausar')->assertRedirect(route('contactos.index'));
        $this->assertTrue($account->fresh()->isPaused());
        $this->assertSame($admin->id, (int) $account->fresh()->sync_paused_by);
        $second = $this->persona();
        $this->service()->markPending($second);
        $this->artisan('gcontact:work --once')->assertExitCode(0);
        $this->assertSame(1, DB::table('jobs')->count());
        $this->assertSame(0, (int) DB::table('jobs')->value('attempts'));
        $this->assertSame('pending', $second->fresh()->gcontact_sync_status);
        $this->actingAs($this->actor())->get('/contactos/google')->assertOk()->assertSee('Pausada para todos')
            ->assertSee('Reanudar sincronización');
        Http::assertNothingSent();
    }

    public function test_resume_dispatches_pending_contacts_once_and_preserves_terminal_errors()
    {
        $this->account();
        $admin = $this->actor();
        $this->service()->setPaused(true, $admin->id);
        $pending = $this->persona();
        $error = $this->persona(['gcontact_sync_status' => 'error', 'gcontact_error_code' => 'invalid_phone']);
        Queue::swap($this->realQueue);
        $this->service()->markPending($pending);
        $this->assertSame(0, DB::table('jobs')->count());
        $this->actingAs($admin)->post('/contactos/google/reanudar')->assertRedirect(route('contactos.index'));
        $this->post('/contactos/google/reanudar')->assertRedirect();
        $this->assertFalse($this->service()->account()->isPaused());
        $this->assertSame(1, DB::table('jobs')->count());
        $this->assertSame('error', $error->fresh()->gcontact_sync_status);
        $this->assertSame('invalid_phone', $error->fresh()->gcontact_error_code);
        Http::assertNothingSent();
    }

    public function test_authorized_secretary_can_request_but_cannot_control_global_processing()
    {
        $this->account();
        $persona = $this->persona();
        $secretary = $this->actor('Secretaria');
        $this->seed(GoogleContactsSeeder::class);
        $this->actingAs($secretary)->get('/contactos/google')->assertOk()
            ->assertDontSee('Pausar sincronización')->assertDontSee('Reconectar Google');
        $this->post('/contactos/google/'.$persona->id.'/sincronizar')->assertRedirect();
        $this->post('/contactos/google/pausar')->assertForbidden();
        $this->post('/contactos/google/reanudar')->assertForbidden();
        // Even accidentally granting setup permission cannot bypass the explicit Admin role check.
        $secretary->givePermissionTo(Permission::findByName('Configurar Google Contacts'));
        $this->post('/contactos/google/pausar')->assertForbidden();
        $this->post('/contactos/google/reanudar')->assertForbidden();
        $this->assertFalse($this->service()->account()->isPaused());
        Http::assertNothingSent();
    }

    public function test_global_controls_require_login_and_post_requests()
    {
        $this->account();
        $this->post('/contactos/google/pausar')->assertRedirect('/login');
        $this->post('/contactos/google/reanudar')->assertRedirect('/login');
        $this->actingAs($this->actor())->get('/contactos/google/pausar')->assertStatus(405);
        $this->get('/contactos/google/reanudar')->assertStatus(405);
        Http::assertNothingSent();
    }

    public function test_legacy_worker_reservation_during_pause_keeps_contact_pending_and_can_resume()
    {
        $this->account();
        $persona = $this->persona();
        Queue::swap($this->realQueue);
        $this->service()->markPending($persona);
        $this->service()->setPaused(true, $this->actor()->id);
        Queue::connection('google_contacts')->pop('google-contacts')->fire();
        $this->assertSame('pending', $persona->fresh()->gcontact_sync_status);
        $this->assertNull($persona->fresh()->gcontact_error_code);
        $this->assertSame(0, (int) $persona->fresh()->gcontact_sync_attempts);
        $this->assertSame(0, DB::table('jobs')->count());
        $this->service()->setPaused(false, $this->actor()->id);
        $this->assertSame(1, DB::table('jobs')->count());
        Http::assertNothingSent();
    }

    public function test_pause_finishes_current_operation_and_defers_following_contact()
    {
        $this->account();
        $first = $this->persona();
        $second = $this->persona();
        $admin = $this->actor();
        $this->fakeHttp(function ($request) use ($admin) {
            if ($request->method() === 'POST') {
                $this->service()->setPaused(true, $admin->id);
                return Http::response($this->remote());
            }
            return Http::response(['connections' => []]);
        });
        (new SyncGoogleContact($first->id))->handle($this->service());
        (new SyncGoogleContact($second->id))->handle($this->service());
        $this->assertSame('synced', $first->fresh()->gcontact_sync_status);
        $this->assertSame('pending', $second->fresh()->gcontact_sync_status);
        $this->assertNull($second->fresh()->resourseName);
        Http::assertSentCount(2);
    }

    public function test_heartbeat_distinguishes_process_liveness_from_pause_and_expires()
    {
        $this->account();
        $runtime = new GoogleContactsRuntime;
        $this->assertFalse($runtime->status()['online']);
        $this->service()->setPaused(true, $this->actor()->id);
        $this->assertFalse($runtime->tick($this->service()));
        $this->assertTrue($runtime->status()['online']);
        $this->travel(181)->seconds();
        $this->assertFalse($runtime->status()['online']);
        $runtime->heartbeat();
        $this->assertTrue($runtime->status()['online']);
        $runtime->heartbeat(false);
        $this->assertFalse($runtime->status()['online']);
        Http::assertNothingSent();
    }

    public function test_managed_worker_processes_one_shared_queue_job_and_releases_os_lock()
    {
        $this->account();
        $persona = $this->persona();
        Queue::swap($this->realQueue);
        $this->service()->markPending($persona);
        $this->fakeHttp([
            '*people/me/connections*' => Http::response(['connections' => []]),
            '*people:createContact*' => Http::response($this->remote()),
        ]);
        $this->artisan('gcontact:work --once')->assertExitCode(0);
        $this->assertSame('synced', $persona->fresh()->gcontact_sync_status);
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertFalse((new GoogleContactsRuntime)->status()['online']);
        $lock = fopen(storage_path('framework/google-contacts-worker.lock'), 'c');
        $this->assertTrue(flock($lock, LOCK_EX | LOCK_NB));
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    public function test_second_worker_cannot_process_jobs_or_overwrite_current_heartbeat()
    {
        $this->account();
        $runtime = new GoogleContactsRuntime;
        $runtime->heartbeat();
        $this->persona();
        $lock = fopen(storage_path('framework/google-contacts-worker.lock'), 'c');
        $this->assertTrue(flock($lock, LOCK_EX | LOCK_NB));
        try {
            $this->artisan('gcontact:work --once')->expectsOutput('El proceso central de Google Contacts ya está iniciado.')->assertExitCode(0);
            $this->assertTrue($runtime->status()['online']);
            Queue::assertNothingPushed();
            Http::assertNothingSent();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function test_counters_are_global_and_tokens_are_never_rendered()
    {
        $this->account();
        $this->persona(['nombre' => 'Visible']);
        $this->persona(['nombre' => 'Otra', 'gcontact_sync_status' => 'processing']);
        $this->persona(['gcontact_sync_status' => 'synced', 'gcontact_sync_pending' => false]);
        $this->persona(['gcontact_sync_status' => 'error']);
        $this->actingAs($this->actor())->get('/contactos/google?q=Visible')->assertOk()
            ->assertViewHas('counts', ['pending' => 1, 'processing' => 1, 'synced' => 1, 'error' => 1])
            ->assertSee('Sin señal del proceso')->assertSee('Pausar sincronización')
            ->assertDontSee('access-test-only')->assertDontSee('refresh-test-only');
        Http::assertNothingSent();
    }

    public function test_sweeper_restores_only_pending_jobs_and_never_sends_http()
    {
        $this->account();
        $this->persona();
        $this->persona(['gcontact_sync_status' => 'error']);
        $this->persona(['gcontact_sync_status' => 'retrying']);
        $runtime = new GoogleContactsRuntime;
        $this->assertTrue($runtime->tick($this->service()));
        Queue::assertPushed(SyncGoogleContact::class, 1);
        $this->assertTrue($runtime->tick($this->service()));
        Queue::assertPushed(SyncGoogleContact::class, 1);
        Http::assertNothingSent();
    }

    public function test_cron_processes_at_most_three_jobs_and_next_run_continues_without_duplicates()
    {
        $this->account();
        $personas = [];
        for ($i = 0; $i < 4; $i++) {
            $personas[] = $this->persona(['nombre' => 'Persona '.$i]);
        }
        Queue::swap($this->realQueue);
        foreach ($personas as $persona) {
            $this->service()->markPending($persona);
        }
        $creates = 0;
        $this->fakeHttp(function ($request) use (&$creates) {
            return $request->method() === 'POST'
                ? Http::response($this->remote('people/cron'.(++$creates)))
                : Http::response(['connections' => []]);
        });
        $this->artisan('gcontact:work --cron')->assertExitCode(0);
        $this->assertSame(3, $creates);
        $this->assertSame(1, DB::table('jobs')->count());
        $this->assertSame('pending', $personas[3]->fresh()->gcontact_sync_status);
        $status = (new GoogleContactsRuntime)->status();
        $this->assertSame('cron', $status['mode']);
        $this->assertTrue($status['healthy']);
        $this->assertFalse($status['online'], 'La tarea terminada no es un proceso permanente.');
        $this->artisan('gcontact:work --cron')->assertExitCode(0);
        $this->assertSame(4, $creates);
        $this->assertSame(0, DB::table('jobs')->count());
        foreach ($personas as $persona) {
            $this->assertSame('synced', $persona->fresh()->gcontact_sync_status);
        }
        Http::assertSentCount(8);
    }

    public function test_cron_exits_when_empty_or_paused_and_does_not_exhaust_attempts()
    {
        $this->account();
        Queue::swap($this->realQueue);
        $this->artisan('gcontact:work --cron')->assertExitCode(0);
        $this->assertTrue((new GoogleContactsRuntime)->status()['healthy']);
        $persona = $this->persona();
        $this->service()->markPending($persona);
        $this->service()->setPaused(true, $this->actor()->id);
        $this->artisan('gcontact:work --cron')->assertExitCode(0);
        $this->assertSame(1, DB::table('jobs')->count());
        $this->assertSame(0, (int) DB::table('jobs')->value('attempts'));
        $this->assertTrue((new GoogleContactsRuntime)->status()['healthy']);
        $this->assertFalse((new GoogleContactsRuntime)->status()['online']);
        Http::assertNothingSent();
    }

    public function test_cron_health_respects_interval_and_marks_failed_execution_for_review()
    {
        config(['google-contacts.cron_interval_minutes' => 5]);
        $runtime = new GoogleContactsRuntime;
        $runtime->setMode('cron');
        $runtime->finish(true);
        $this->assertTrue($runtime->status()['healthy']);
        $this->assertFalse($runtime->status()['online']);
        $this->travel(300)->seconds();
        $this->assertTrue($runtime->status()['healthy'], 'Debe cubrir el intervalo y el margen de ejecución.');
        $this->travel(181)->seconds();
        $this->assertFalse($runtime->status()['healthy']);
        $runtime->heartbeat();
        $this->assertTrue($runtime->status()['online']);
        $runtime->finish(false);
        $this->assertFalse($runtime->status()['healthy']);
        Http::assertNothingSent();
    }

    public function test_panel_distinguishes_scheduled_execution_from_permanent_process()
    {
        config(['google-contacts.process_mode' => 'cron']);
        $this->account();
        $this->actingAs($this->actor())->get('/contactos/google')->assertOk()->assertSee('Tarea programada por revisar');
        $runtime = new GoogleContactsRuntime;
        $runtime->setMode('cron');
        $runtime->finish(true);
        $this->get('/contactos/google')->assertOk()->assertSee('Tarea programada al día')
            ->assertDontSee('Sin señal del proceso')->assertDontSee('access-test-only')->assertDontSee('refresh-test-only');
        Http::assertNothingSent();
    }

    public function test_second_cron_cannot_overwrite_active_worker_signal_or_process_jobs()
    {
        $this->account();
        $runtime = new GoogleContactsRuntime;
        $runtime->heartbeat();
        $this->persona();
        $lock = fopen(storage_path('framework/google-contacts-worker.lock'), 'c');
        $this->assertTrue(flock($lock, LOCK_EX | LOCK_NB));
        try {
            $this->artisan('gcontact:work --cron')->expectsOutput('El proceso central de Google Contacts ya está iniciado.')->assertExitCode(0);
            $this->assertSame('worker', $runtime->status()['mode']);
            $this->assertTrue($runtime->status()['online']);
            Queue::assertNothingPushed();
            Http::assertNothingSent();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}

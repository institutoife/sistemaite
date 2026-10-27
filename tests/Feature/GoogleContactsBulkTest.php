<?php

namespace Tests\Feature;

use App\Models\Comentario;
use App\Models\GoogleContactBulkRun;
use App\Models\Persona;
use App\Services\GoogleContactsBulkService;
use Database\Seeders\GoogleContactsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\GoogleContactsTestCase;

class GoogleContactsBulkTest extends GoogleContactsTestCase
{
    private $originalStorage;
    private $workerStorage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalStorage = storage_path();
        $this->workerStorage = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ife-bulk-'.bin2hex(random_bytes(8));
        mkdir($this->workerStorage.DIRECTORY_SEPARATOR.'framework', 0777, true);
        $this->app->useStoragePath($this->workerStorage);
    }

    protected function tearDown(): void
    {
        $this->app->useStoragePath($this->originalStorage);
        $lock = $this->workerStorage.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'google-contacts-worker.lock';
        if (is_file($lock)) { unlink($lock); }
        rmdir($this->workerStorage.DIRECTORY_SEPARATOR.'framework');
        rmdir($this->workerStorage);
        parent::tearDown();
    }

    private function startData(array $override = []): array
    {
        return array_merge(['source' => 'persona', 'limit' => 0, 'confirm_bulk' => 1], $override);
    }

    public function test_admin_start_is_durable_read_only_on_google_and_duplicate_clicks_keep_one_run(): void
    {
        $this->account();
        $person = $this->persona(['gcontact_sync_pending' => false, 'gcontact_sync_status' => 'idle']);
        $before = $person->fresh()->getAttributes();
        $this->actingAs($this->actor());
        $this->post(route('contactos.bulk.start'), $this->startData())->assertRedirect(route('contactos.index'));
        $this->post(route('contactos.bulk.start'), $this->startData())->assertRedirect();
        $this->assertSame(1, GoogleContactBulkRun::count());
        $this->assertSame(1, GoogleContactBulkRun::first()->candidate_count);
        $this->assertSame($before, $person->fresh()->getAttributes());
        $this->assertSame(0, DB::table('google_contact_bulk_items')->count());
        $this->get(route('contactos.index'))->assertOk()->assertSee('Sincronización masiva')->assertSee('Preparando por turnos');
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_mass_sync_requires_admin_and_explicit_valid_selection(): void
    {
        $this->account();
        $this->persona();
        $secretary = $this->actor('Secretaria');
        $this->seed(GoogleContactsSeeder::class);
        $this->actingAs($secretary)->post(route('contactos.bulk.start'), $this->startData())->assertForbidden();
        $secretary->givePermissionTo('Sincronizar Contactos Masivamente');
        $this->post(route('contactos.bulk.start'), $this->startData())->assertForbidden();
        $this->actingAs($this->actor())->postJson(route('contactos.bulk.start'), $this->startData(['confirm_bulk' => 0]))->assertUnprocessable();
        $this->postJson(route('contactos.bulk.start'), $this->startData(['source' => 'unknown']))->assertUnprocessable();
        $this->assertSame(0, GoogleContactBulkRun::count());
        Http::assertNothingSent();
    }

    public function test_cron_continues_import_across_turns_and_never_recreates_processed_items(): void
    {
        $this->account();
        for ($i = 0; $i < 8; $i++) { $this->persona(['gcontact_sync_status' => 'idle', 'gcontact_sync_pending' => false]); }
        $this->actingAs($this->actor())->post(route('contactos.bulk.start'), $this->startData())->assertRedirect();
        $run = GoogleContactBulkRun::first();
        Queue::swap($this->realQueue);
        $creates = 0;
        $this->fakeHttp(function ($request) use (&$creates) {
            return Http::response($request->method() === 'POST' ? $this->remote('people/bulk'.(++$creates)) : ['connections' => []]);
        });
        $this->artisan('gcontact:work --cron')->assertExitCode(0);
        $this->assertSame(3, $creates);
        $this->assertSame(3, $run->fresh()->queued);
        $this->artisan('gcontact:work --cron')->assertExitCode(0);
        $this->assertSame(6, $creates);
        $this->artisan('gcontact:work --cron')->assertExitCode(0);
        $this->assertSame(8, $creates);
        $this->assertSame(8, Persona::where('gcontact_sync_status', 'synced')->count());
        $this->assertTrue(app(GoogleContactsBulkService::class)->progress($run->fresh())['finished']);
        $this->artisan('gcontact:work --cron')->assertExitCode(0);
        $this->assertSame('completed', $run->fresh()->status);
        $this->assertNull($run->fresh()->active_slot);
        $this->assertSame(8, $creates);
        $this->assertSame(0, DB::table('jobs')->count());
        Http::assertSentCount(16);
    }

    public function test_bad_missing_and_terminal_error_phones_are_not_marked_verified_or_blindly_retried(): void
    {
        $this->account();
        $good = $this->persona(['gcontact_sync_status' => 'idle', 'gcontact_sync_pending' => false]);
        $bad = $this->persona(['telefono' => '123', 'gcontact_sync_status' => 'idle', 'gcontact_sync_pending' => false]);
        $missing = $this->persona(['telefono' => '00000000', 'gcontact_sync_status' => 'idle', 'gcontact_sync_pending' => false]);
        $failed = $this->persona(['gcontact_sync_status' => 'error', 'gcontact_error_code' => 'invalid_birthday']);
        $this->actingAs($this->actor())->post(route('contactos.bulk.start'), $this->startData())->assertRedirect();
        app(GoogleContactsBulkService::class)->advance($this->service());
        $run = GoogleContactBulkRun::first();
        $this->assertSame(1, $run->queued);
        $this->assertSame(1, $run->skipped);
        $this->assertSame([$good->id], DB::table('google_contact_bulk_items')->pluck('record_id')->all());
        $this->assertSame('idle', $bad->fresh()->gcontact_sync_status);
        $this->assertSame('00000000', $missing->fresh()->telefono);
        $this->assertSame('invalid_birthday', $failed->fresh()->gcontact_error_code);
        $this->assertSame(0, DB::table('person_phone_verifications')->count());
        Http::assertNothingSent();
    }

    public function test_daily_requests_take_available_capacity_before_historical_import(): void
    {
        $this->account();
        $historical = $this->persona(['gcontact_sync_status' => 'idle', 'gcontact_sync_pending' => false]);
        $this->actingAs($this->actor())->post(route('contactos.bulk.start'), $this->startData())->assertRedirect();
        Queue::swap($this->realQueue);
        for ($i = 0; $i < 3; $i++) { $this->service()->markPending($this->persona()); }
        app(GoogleContactsBulkService::class)->advance($this->service());
        $this->assertSame(0, GoogleContactBulkRun::first()->queued);
        $this->assertSame('idle', $historical->fresh()->gcontact_sync_status);
        $this->assertSame(3, DB::table('jobs')->count());
        Http::assertNothingSent();
    }

    public function test_pause_and_stop_preserve_daily_processing_and_already_queued_work(): void
    {
        $account = $this->account();
        $person = $this->persona(['gcontact_sync_status' => 'idle', 'gcontact_sync_pending' => false]);
        $admin = $this->actor();
        $this->actingAs($admin)->post(route('contactos.bulk.start'), $this->startData())->assertRedirect();
        $account->update(['sync_paused_at' => now()]);
        app(GoogleContactsBulkService::class)->advance($this->service());
        $this->assertSame(0, GoogleContactBulkRun::first()->queued);
        $account->update(['sync_paused_at' => null]);
        Queue::swap($this->realQueue);
        app(GoogleContactsBulkService::class)->advance($this->service());
        $this->assertSame(1, DB::table('jobs')->count());
        $this->post(route('contactos.bulk.stop', GoogleContactBulkRun::first()))->assertRedirect();
        $this->assertSame(1, DB::table('jobs')->count());
        $this->assertNull(GoogleContactBulkRun::first()->active_slot);
        $this->assertFalse($account->fresh()->isPaused());
        $this->assertSame('pending', $person->fresh()->gcontact_sync_status);
        Http::assertNothingSent();
    }

    public function test_all_sources_keep_distinct_items_and_ignore_records_created_after_snapshot(): void
    {
        $this->account();
        $person = $this->persona(['gcontact_sync_status' => 'idle', 'gcontact_sync_pending' => false]);
        $prospect = \App\Models\Prospecto::withoutEvents(function () {
            $record = new \App\Models\Prospecto;
            $record->forceFill(['telefono' => '70000001'])->save();
            return $record;
        });
        $enquiry = Comentario::withoutEvents(function () {
            $record = new Comentario;
            $record->forceFill(['nombre' => 'Consulta de prueba', 'telefono' => '70000002'])->save();
            return $record;
        });
        $this->actingAs($this->actor())->post(route('contactos.bulk.start'), $this->startData(['source' => 'all']))->assertRedirect();
        $later = $this->persona(['gcontact_sync_status' => 'idle', 'gcontact_sync_pending' => false]);
        app(GoogleContactsBulkService::class)->advance($this->service());
        $this->assertSame(3, GoogleContactBulkRun::first()->queued);
        $this->assertSame(['persona', 'prospecto', 'comentario'], DB::table('google_contact_bulk_items')->orderBy('id')->pluck('source')->all());
        $this->assertSame($person->id, $prospect->id);
        $this->assertSame($person->id, $enquiry->id);
        $this->assertSame('idle', $later->fresh()->gcontact_sync_status);
        $enquiry->persona_id = $person->id;
        $enquiry->save();
        $progress = app(GoogleContactsBulkService::class)->progress(GoogleContactBulkRun::first());
        $this->assertSame(1, $progress['removed']);
        $this->assertSame(2, $progress['pending']);
        Http::assertNothingSent();
    }

    public function test_reimporting_synced_contacts_requires_opt_in_and_terminal_errors_finish_run(): void
    {
        $this->account();
        $person = $this->persona(['gcontact_sync_status' => 'synced', 'gcontact_sync_pending' => false, 'gcontact_synced_at' => now(),
            'resourseName' => 'people/existing']);
        $this->actingAs($this->actor())->postJson(route('contactos.bulk.start'), $this->startData())->assertUnprocessable();
        $this->post(route('contactos.bulk.start'), $this->startData(['include_synced' => 1]))->assertRedirect();
        app(GoogleContactsBulkService::class)->advance($this->service());
        $this->assertSame('people/existing', $person->fresh()->resourseName);
        $this->assertSame('pending', $person->fresh()->gcontact_sync_status);
        DB::table('personas')->where('id', $person->id)->update(['gcontact_sync_status' => 'error', 'gcontact_error_code' => 'invalid_birthday']);
        $progress = app(GoogleContactsBulkService::class)->progress(GoogleContactBulkRun::first());
        $this->assertSame(1, $progress['errors']);
        $this->assertTrue($progress['finished']);
        app(GoogleContactsBulkService::class)->advance($this->service());
        $this->assertSame('completed', GoogleContactBulkRun::first()->status);
        $this->assertSame('invalid_birthday', $person->fresh()->gcontact_error_code);
        Http::assertNothingSent();
    }

    public function test_turn_capacity_is_configurable_with_same_time_limit(): void
    {
        config(['google-contacts.cron_max_jobs' => 5]);
        $this->account();
        for ($i = 0; $i < 6; $i++) { $this->persona(['gcontact_sync_status' => 'idle', 'gcontact_sync_pending' => false]); }
        $this->actingAs($this->actor())->post(route('contactos.bulk.start'), $this->startData())->assertRedirect();
        Queue::swap($this->realQueue);
        $count = 0;
        $this->fakeHttp(function ($request) use (&$count) {
            return Http::response($request->method() === 'POST' ? $this->remote('people/capacity'.(++$count)) : ['connections' => []]);
        });
        $this->artisan('gcontact:work --cron')->assertExitCode(0);
        $this->assertSame(5, $count);
        $this->assertSame(5, GoogleContactBulkRun::first()->queued);
        Http::assertSentCount(10);
    }
}

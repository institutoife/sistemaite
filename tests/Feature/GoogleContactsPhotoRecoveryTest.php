<?php

namespace Tests\Feature;

use App\Exceptions\GoogleContactsException;
use App\Jobs\SyncGoogleContact;
use App\Models\Persona;
use App\Services\GoogleContactProfile;
use App\Services\GoogleContactsBulkService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\GoogleContactsTestCase;

class GoogleContactsPhotoRecoveryTest extends GoogleContactsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $script = file_get_contents(public_path('assets/js/phone-verification.js'));
        Storage::fake('public');
        Storage::disk('public')->makeDirectory('_web/assets/js');
        Storage::disk('public')->put('_web/assets/js/phone-verification.js', $script);
        $this->app->instance('path.public', Storage::disk('public')->path('_web'));
    }

    private function image(string $path): string
    {
        $file = UploadedFile::fake()->image('test.jpg', 60, 30);
        Storage::disk('public')->put($path, file_get_contents($file->getPathname()));
        return $path;
    }

    private function fakeGoogle(): void
    {
        $this->fakeHttp(function ($request) {
            preg_match('~v1/(people/[A-Za-z0-9_-]+)~', $request->url(), $match);
            $remote = $this->remote($match[1]);
            $remote['photos'] = [['url' => 'https://example.test/keep-existing-photo.jpg']];
            return Http::response(strpos($request->url(), 'updateContactPhoto') !== false ? ['person' => $remote] : $remote);
        });
    }

    public function test_photos_in_a_separate_public_storage_directory_sync_manually_and_in_bulk(): void
    {
        $this->account();
        $this->image('_web/storage/estudiantes/old.jpg');
        $manual = $this->persona(['foto' => 'estudiantes/old.jpg', 'resourseName' => 'people/manual']);
        Storage::disk('public')->assertMissing('estudiantes/old.jpg');
        $this->assertSame('public/storage', app(GoogleContactProfile::class)->photoFile($manual)['location']);
        $this->fakeGoogle();
        (new SyncGoogleContact($manual->id))->handle($this->service());
        $this->assertSame('synced', $manual->fresh()->gcontact_sync_status);
        $this->assertNull($manual->fresh()->gcontact_error_code);
        $this->assertSame('people/manual', $manual->fresh()->resourseName);
        $this->assertNotNull($manual->fresh()->gcontact_photo_hash);

        $batch = $this->persona(['foto' => 'estudiantes/old.jpg', 'resourseName' => 'people/bulk',
            'gcontact_sync_pending' => false, 'gcontact_sync_status' => 'idle']);
        $bulk = app(GoogleContactsBulkService::class);
        $run = $bulk->start($this->service(), $this->actor()->id, 'persona', false, 100);
        $bulk->advance($this->service());
        (new SyncGoogleContact($batch->id))->handle($this->service());
        $this->assertSame(1, $bulk->progress($run->fresh())['synced']);
        $this->assertSame(0, $bulk->progress($run->fresh())['photo_warnings']);
        $this->assertSame('people/bulk', $batch->fresh()->resourseName);
        $this->assertNotNull($batch->fresh()->gcontact_photo_hash);
        $this->assertCount(2, Http::recorded(function ($request) { return strpos($request->url(), 'updateContactPhoto') !== false; }));
    }

    public function test_known_local_prefixes_and_legacy_directory_resolve_the_exact_saved_photo(): void
    {
        $this->image('_web/foto/estudiantes/legacy.jpg');
        $profile = app(GoogleContactProfile::class);
        $hash = null;
        foreach (['estudiantes/legacy.jpg', 'storage/estudiantes/legacy.jpg', '/storage/estudiantes/legacy.jpg',
            'public/storage/estudiantes/legacy.jpg', 'storage/app/public/estudiantes/legacy.jpg',
            'foto/estudiantes/legacy.jpg', '/foto/estudiantes/legacy.jpg', 'estudiantes\\legacy.jpg'] as $path) {
            $person = $this->persona(['foto' => $path]);
            $this->assertSame('public/foto', $profile->photoFile($person)['location']);
            $photo = $profile->photo($person);
            $hash = $hash ?: $photo['hash'];
            $this->assertSame($hash, $photo['hash']);
        }
        Http::assertNothingSent();
    }

    public function test_canonical_file_takes_priority_and_unsafe_or_missing_paths_are_not_searched_by_basename(): void
    {
        $this->image('foto/own.jpg');
        Storage::disk('public')->put('own.jpg', 'different file that must not be used');
        $this->image('_web/storage/foto/own.jpg');
        $profile = app(GoogleContactProfile::class);
        $person = $this->persona(['foto' => 'foto/own.jpg']);
        $this->assertSame(realpath(Storage::disk('public')->path('foto/own.jpg')), $profile->photoFile($person)['path']);
        foreach (['../foto/own.jpg', '/etc/passwd', '//example.test/a.jpg', 'https://example.test/a.jpg',
            'storage/../own.jpg', "foto/own.jpg\0", 'another-folder/own.jpg'] as $path) {
            try {
                $profile->photo($this->persona(['foto' => $path]));
                $this->fail('Se aceptó una ruta no permitida o se buscó por nombre de archivo.');
            } catch (GoogleContactsException $exception) { $this->assertSame('invalid_photo', $exception->reason); }
        }
        Http::assertNothingSent();
    }

    public function test_missing_photo_preserves_google_image_and_later_upload_clears_the_warning(): void
    {
        $this->account();
        $person = $this->persona(['foto' => 'estudiantes/lost.jpg', 'resourseName' => 'people/existing', 'gcontact_photo_hash' => 'previous-photo-hash']);
        $this->fakeGoogle();
        (new SyncGoogleContact($person->id))->handle($this->service());
        $this->assertSame('synced', $person->fresh()->gcontact_sync_status);
        $this->assertFalse((bool) $person->fresh()->gcontact_sync_pending);
        $this->assertSame('invalid_photo', $person->fresh()->gcontact_error_code);
        $this->assertSame('previous-photo-hash', $person->fresh()->gcontact_photo_hash);
        $this->assertStringContainsString('No se encontró', $person->fresh()->gcontact_sync_error);
        Http::assertNotSent(function ($request) {
            return strpos($request->url(), 'ContactPhoto') !== false || isset($request['photos']) || isset($request['photoBytes']) || $request->method() === 'POST';
        });
        $this->actingAs($this->actor())->get(route('contactos.index'))->assertOk()->assertSee('Sincronizado · revisar foto');
        $this->image('estudiantes/lost.jpg');
        $this->service()->markPending($person, null, false);
        (new SyncGoogleContact($person->id))->handle($this->service());
        $this->assertSame('people/existing', $person->fresh()->resourseName);
        $this->assertSame('synced', $person->fresh()->gcontact_sync_status);
        $this->assertNull($person->fresh()->gcontact_error_code);
        $this->assertNull($person->fresh()->gcontact_sync_error);
        $this->assertNotSame('previous-photo-hash', $person->fresh()->gcontact_photo_hash);
        $this->assertCount(1, Http::recorded(function ($request) { return strpos($request->url(), 'updateContactPhoto') !== false; }));
    }

    public function test_bulk_finishes_with_explicit_photo_warnings_and_correct_pending_count(): void
    {
        $this->account();
        $bad = $this->persona(['foto' => 'estudiantes/missing.jpg', 'resourseName' => 'people/withoutPhoto', 'gcontact_sync_status' => 'idle']);
        $good = $this->persona(['resourseName' => 'people/noPhotoNeeded', 'gcontact_sync_status' => 'idle']);
        $bulk = app(GoogleContactsBulkService::class);
        $run = $bulk->start($this->service(), $this->actor()->id, 'persona', false, 100);
        $bulk->advance($this->service());
        $this->fakeGoogle();
        (new SyncGoogleContact($bad->id))->handle($this->service());
        $progress = $bulk->progress($run->fresh());
        $this->assertSame(1, $progress['synced']);
        $this->assertSame(1, $progress['photo_warnings']);
        $this->assertSame(1, $progress['pending']);
        $this->assertSame(0, $progress['errors']);
        (new SyncGoogleContact($good->id))->handle($this->service());
        $progress = $bulk->progress($run->fresh());
        $this->assertSame(2, $progress['synced']);
        $this->assertSame(0, $progress['pending']);
        $this->assertTrue($progress['finished']);
        $bulk->advance($this->service());
        $this->assertSame('completed', $run->fresh()->status);
    }

    public function test_photo_check_explains_missing_and_oversized_files_without_changing_records(): void
    {
        $missing = $this->persona(['foto' => 'missing.jpg']);
        $before = $missing->fresh()->getAttributes();
        $this->assertSame(1, Artisan::call('gcontact:photo-check', ['persona' => $missing->id]));
        $output = Artisan::output();
        $this->assertStringContainsString('No se encontró', $output);
        $this->assertStringNotContainsString(base_path(), $output);
        $this->assertSame($before, $missing->fresh()->getAttributes());
        $this->image('_web/storage/own.jpg');
        $good = $this->persona(['foto' => 'own.jpg']);
        $this->assertSame(0, Artisan::call('gcontact:photo-check', ['persona' => $good->id]));
        $this->assertStringContainsString('Ubicación: public/storage', Artisan::output());
        Storage::disk('public')->put('too-large.jpg', str_repeat('x', 5 * 1024 * 1024 + 1));
        $large = $this->persona(['foto' => 'too-large.jpg']);
        $this->assertSame(1, Artisan::call('gcontact:photo-check', ['persona' => $large->id]));
        $this->assertStringContainsString('supera el tamaño permitido', Artisan::output());
        $this->assertSame(1, Artisan::call('gcontact:photo-check', ['persona' => 99999]));
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_recovery_preview_is_read_only_and_retry_targets_only_old_photo_errors(): void
    {
        $account = $this->account();
        $one = $this->persona(['foto' => 'missing.jpg', 'resourseName' => 'people/keepOne', 'gcontact_sync_status' => 'error', 'gcontact_error_code' => 'invalid_photo']);
        $two = $this->persona(['foto' => $this->image('own.jpg'), 'resourseName' => 'people/keepTwo', 'gcontact_sync_status' => 'error', 'gcontact_error_code' => 'photo_extension']);
        $other = $this->persona(['gcontact_sync_status' => 'error', 'gcontact_error_code' => 'invalid_birthday']);
        $before = Persona::orderBy('id')->get()->map->getAttributes()->all();
        $this->assertSame(0, Artisan::call('gcontact:sync-batch', ['--only-photo-errors' => true, '--dry-run' => true, '--batch' => 100]));
        $output = Artisan::output();
        $this->assertStringContainsString('Contactos preparados: 2', $output);
        $this->assertStringContainsString('requieren atención: 1', $output);
        $this->assertSame($before, Persona::orderBy('id')->get()->map->getAttributes()->all());
        Queue::assertNothingPushed();
        $account->update(['sync_paused_at' => now()]);
        $this->artisan('gcontact:sync-batch --only-photo-errors --batch=100')->assertExitCode(0);
        Queue::assertNothingPushed();
        $account->update(['sync_paused_at' => null]);
        Queue::swap($this->realQueue);
        $this->artisan('gcontact:sync-batch --only-photo-errors --batch=100')->assertExitCode(0);
        $this->assertSame(2, DB::table('jobs')->where('queue', 'google-contacts')->count());
        foreach ([$one, $two] as $person) {
            $this->assertSame('pending', $person->fresh()->gcontact_sync_status);
            $this->assertSame($person->resourseName, $person->fresh()->resourseName);
        }
        $this->assertSame('invalid_birthday', $other->fresh()->gcontact_error_code);
        $this->assertSame('error', $other->fresh()->gcontact_sync_status);
        Http::assertNothingSent();
    }

    public function test_recovery_limits_and_invalid_option_combinations_do_not_expand_selection(): void
    {
        $this->account();
        $one = $this->persona(['foto' => 'missing.jpg', 'gcontact_sync_status' => 'error', 'gcontact_error_code' => 'invalid_photo']);
        $two = $this->persona(['foto' => 'missing.jpg', 'gcontact_sync_status' => 'error', 'gcontact_error_code' => 'invalid_photo']);
        $this->artisan('gcontact:sync-batch --only-photo-errors --only-pending')->assertExitCode(1);
        $this->artisan('gcontact:sync-batch --only-photo-errors --source=prospecto')->assertExitCode(1);
        Queue::assertNothingPushed();
        Queue::swap($this->realQueue);
        $this->artisan('gcontact:sync-batch --only-photo-errors --batch=1 --from-id='.$two->id)->assertExitCode(0);
        $this->assertSame('error', $one->fresh()->gcontact_sync_status);
        $this->assertSame('pending', $two->fresh()->gcontact_sync_status);
        $this->assertSame(1, DB::table('jobs')->count());
        Http::assertNothingSent();
    }
}

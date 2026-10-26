<?php

namespace Tests\Feature;

use App\Jobs\SyncGoogleContact;
use App\Models\Computacion;
use App\Models\Inscripcione;
use App\Models\Matriculacion;
use App\Models\Modalidad;
use App\Models\Persona;
use App\Services\GoogleContactProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\GoogleContactsTestCase;

class GoogleContactsProfileTest extends GoogleContactsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function guardian(Persona $student, array $attributes = []): Persona
    {
        $guardian = Persona::withoutEvents(function () use ($attributes) {
            $person = new Persona;
            $person->forceFill(array_merge(['nombre' => 'Juan', 'apellidop' => 'Quispe', 'papelinicial' => 'apoderado',
                'gcontact_sync_pending' => true, 'gcontact_sync_status' => 'pending'], $attributes))->save();
            return $person;
        });
        $student->apoderados()->attach($guardian->id, ['parentesco' => 'PAPA']);
        return $guardian;
    }

    private function photo(string $path = 'apoderados/own.jpg'): string
    {
        $upload = UploadedFile::fake()->image('own.jpg', 900, 450);
        Storage::disk('public')->put($path, file_get_contents($upload->getPathname()));
        return $path;
    }

    public function test_student_keeps_structured_name_and_uses_its_real_code_without_services_in_name()
    {
        $this->account();
        $person = $this->persona();
        $profile = app(GoogleContactProfile::class)->fields($person);
        $this->assertSame('Lucas', $profile['names'][0]['givenName']);
        $this->assertSame('Mamani', $profile['names'][0]['familyName']);
        $this->assertSame('| IFE | '.$person->id, $profile['names'][0]['honorificSuffix']);
        $this->assertStringContainsString('Código de estudiante: '.$person->id, $profile['biographies'][0]['value']);
        $this->assertArrayNotHasKey('emailAddresses', $profile, 'No exportar correos de acceso generados por el sistema.');
    }

    public function test_n_guardians_and_multiple_children_have_one_contact_each_with_complete_notes()
    {
        $student = $this->persona();
        $father = $this->guardian($student);
        foreach (['MAMA', 'ABUELA', 'TUTOR'] as $relationship) {
            $other = $this->guardian($student, ['nombre' => $relationship]);
            $student->apoderados()->updateExistingPivot($other->id, ['parentesco' => $relationship]);
            $profile = app(GoogleContactProfile::class)->fields($other);
            $this->assertStringContainsString('de Lucas Mamani', $profile['names'][0]['honorificSuffix']);
        }
        for ($i = 0; $i < 3; $i++) {
            $child = $this->persona(['nombre' => 'Estudiante '.$i]);
            $child->apoderados()->attach($father->id, ['parentesco' => 'TUTOR']);
        }
        $profile = app(GoogleContactProfile::class)->fields($father);
        $this->assertStringContainsString('+2', $profile['names'][0]['honorificSuffix']);
        $this->assertSame(4, substr_count($profile['biographies'][0]['value'], 'Relación:'));
        $this->assertSame(8, Persona::count());
    }

    public function test_notes_use_both_real_service_relations_without_financial_or_private_observations()
    {
        $student = $this->persona();
        $guardian = $this->guardian($student);
        $academic = Modalidad::create(['modalidad' => 'Apoyo escolar']);
        Inscripcione::create(['estudiante_id' => $student->estudiante->id, 'modalidad_id' => $academic->id]);
        $computer = Computacion::create(['persona_id' => $student->id]);
        $subject = DB::table('asignaturas')->insertGetId(['asignatura' => 'Excel']);
        $enrolment = new Matriculacion;
        $enrolment->forceFill(['computacion_id' => $computer->id, 'asignatura_id' => $subject])->save();
        foreach ([$student, $guardian] as $person) {
            $profile = app(GoogleContactProfile::class)->fields($person);
            $note = $profile['biographies'][0]['value'];
            $this->assertStringContainsString('Apoyo escolar', $note);
            $this->assertStringContainsString('Computación: Excel', $note);
            $this->assertStringNotContainsString('Apoyo escolar', json_encode($profile['names']));
            $this->assertStringNotContainsString('Costo:', $note);
        }
    }

    public function test_managed_note_is_replaced_without_repeating_it_or_erasing_existing_google_notes()
    {
        $this->account();
        $person = $this->persona(['resourseName' => 'people/note']);
        $remote = $this->remote('people/note');
        $remote['biographies'] = [['value' => 'Nota escrita en Google.', 'contentType' => 'TEXT_PLAIN']];
        $this->fakeHttp(function ($request) use (&$remote) {
            if ($request->method() === 'PATCH') {
                $remote['biographies'] = $request['biographies'];
            }
            return Http::response($remote);
        });
        for ($i = 0; $i < 3; $i++) {
            $this->service()->markPending($person, null, false);
            $this->service()->sync($person->id);
        }
        $note = $remote['biographies'][0]['value'];
        $this->assertSame(1, substr_count($note, 'Nota escrita en Google.'));
        $this->assertSame(1, substr_count($note, '--- Datos de IFE ---'));
        Http::assertSentCount(6);
    }

    public function test_reconciliation_reads_current_notes_only_for_matched_contact_before_updating()
    {
        $account = $this->account();
        $person = $this->persona();
        $identity = $this->remote('people/recovered');
        $identity['userDefined'] = [['key' => 'IFE_PERSONA', 'value' => $account->installation_id.':'.$person->id]];
        $full = $identity + ['biographies' => [['value' => 'Nota existente que debe conservarse.']]];
        $this->fakeHttp(function ($request) use ($identity, $full) {
            if (strpos($request->url(), 'connections') !== false) {
                $this->assertStringNotContainsString('biographies', $request['personFields']);
                return Http::response(['connections' => [$identity]]);
            }
            return Http::response($full);
        });
        $this->service()->sync($person->id);
        Http::assertSent(function ($request) {
            return $request->method() === 'PATCH'
                && strpos($request['biographies'][0]['value'], 'Nota existente que debe conservarse.') !== false;
        });
        Http::assertSentCount(3);
    }

    public function test_html_notes_keep_their_content_type_and_ife_text_is_escaped()
    {
        $profile = app(GoogleContactProfile::class);
        $remote = ['biographies' => [['value' => '<b>Nota previa</b>', 'contentType' => 'TEXT_HTML']]];
        $first = $profile->biography('Nombre: <Persona & familia>', $remote);
        $second = $profile->biography('Nombre: actualizado', ['biographies' => $first]);
        $this->assertSame('TEXT_HTML', $second[0]['contentType']);
        $this->assertStringContainsString('<b>Nota previa</b>', $second[0]['value']);
        $this->assertStringContainsString('&lt;Persona &amp; familia&gt;', $first[0]['value']);
        $this->assertStringNotContainsString('Persona &amp;', $second[0]['value']);
    }

    public function test_student_and_guardian_birthdays_update_the_same_contact_using_date_fields()
    {
        $this->account();
        $student = $this->persona(['fechanacimiento' => '2012-02-29', 'resourseName' => 'people/student']);
        $parent = $this->guardian($student, ['fechanacimiento' => '1980-06-15', 'resourseName' => 'people/parent']);
        $this->fakeHttp(function ($request) {
            return Http::response($this->remote(strpos($request->url(), 'people/parent') !== false ? 'people/parent' : 'people/student'));
        });
        foreach ([$student, $parent] as $person) {
            $this->service()->sync($person->id);
        }
        Http::assertSent(function ($request) {
            return $request->method() === 'PATCH' && $request['birthdays'][0]['date'] === ['year' => 2012, 'month' => 2, 'day' => 29];
        });
        $parent->fechanacimiento = '1981-07-16';
        $parent->save();
        $this->service()->sync($parent->id);
        Http::assertSent(function ($request) {
            return $request->method() === 'PATCH' && $request['birthdays'][0]['date'] === ['year' => 1981, 'month' => 7, 'day' => 16];
        });
        $this->assertSame('people/parent', $parent->fresh()->resourseName);
        Http::assertSentCount(6);
    }

    public function test_missing_birthday_and_photo_do_not_clear_remote_fields_or_borrow_childs_photo()
    {
        $this->account();
        $student = $this->persona(['foto' => $this->photo('estudiantes/child.jpg')]);
        $parent = $this->guardian($student, ['resourseName' => 'people/parent']);
        $this->fakeHttp(['*people/parent*' => Http::response($this->remote('people/parent'))]);
        $this->service()->sync($parent->id);
        Http::assertSent(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);
            return $request->method() === 'PATCH' && !isset($request['birthdays']) && !isset($request['photoBytes'])
                && !in_array('birthdays', explode(',', $query['updatePersonFields'] ?? ''), true);
        });
        Http::assertSentCount(2);
    }

    public function test_own_photo_upload_is_resized_and_unchanged_photo_is_not_uploaded_again()
    {
        $this->account();
        $person = $this->persona(['foto' => $this->photo(), 'resourseName' => 'people/photo']);
        $this->fakeHttp(function ($request) {
            $remote = $this->remote('people/photo', strpos($request->url(), 'updateContactPhoto') !== false ? 'after-photo' : 'fresh');
            return Http::response(strpos($request->url(), 'updateContactPhoto') !== false ? ['person' => $remote] : $remote);
        });
        $this->service()->sync($person->id);
        $this->assertSame('after-photo', $person->fresh()->etag);
        $this->assertNotNull($person->fresh()->gcontact_photo_hash);
        $this->service()->markPending($person, null, false);
        $this->service()->sync($person->id);
        $photos = Http::recorded(function ($request) { return strpos($request->url(), 'updateContactPhoto') !== false; });
        $this->assertCount(1, $photos);
        $image = getimagesizefromstring(base64_decode($photos->first()[0]['photoBytes']));
        $this->assertSame([720, 360], [$image[0], $image[1]]);
        Http::assertSentCount(5);
    }

    public function test_photo_failure_after_creation_preserves_link_and_retry_does_not_create_again()
    {
        $this->account();
        $person = $this->persona(['foto' => $this->photo()]);
        $fail = true;
        $posts = 0;
        $this->fakeHttp(function ($request) use (&$fail, &$posts) {
            if (strpos($request->url(), 'connections') !== false) {
                return Http::response(['connections' => []]);
            }
            if ($request->method() === 'POST') {
                $posts++;
            }
            if (strpos($request->url(), 'updateContactPhoto') !== false) {
                return $fail ? Http::response([], 503) : Http::response(['person' => $this->remote('people/created')]);
            }
            return Http::response($this->remote('people/created'));
        });
        try {
            (new SyncGoogleContact($person->id))->handle($this->service());
            $this->fail('El error recuperable de foto debe reintentarse.');
        } catch (\App\Exceptions\GoogleContactsException $exception) {
            $this->assertTrue($exception->retryable);
        }
        $this->assertSame('people/created', $person->fresh()->resourseName);
        $this->assertSame('retrying', $person->fresh()->gcontact_sync_status);
        $this->assertNull($person->fresh()->gcontact_photo_hash);
        $fail = false;
        (new SyncGoogleContact($person->id))->handle($this->service());
        $this->assertSame('synced', $person->fresh()->gcontact_sync_status);
        $this->assertSame(1, $posts);
    }

    public function test_invalid_photo_and_paths_never_trigger_google_requests()
    {
        $this->account();
        Storage::disk('public')->put('apoderados/broken.jpg', 'not an image');
        foreach (['https://example.test/a.jpg', '../secret.jpg', 'apoderados/broken.jpg', 'missing.jpg'] as $path) {
            $person = $this->persona(['foto' => $path]);
            (new SyncGoogleContact($person->id))->handle($this->service());
            $this->assertSame('invalid_photo', $person->fresh()->gcontact_error_code);
        }
        Http::assertNothingSent();
    }

    public function test_default_photos_are_omitted_and_invalid_legacy_birthday_is_reported()
    {
        $profile = app(GoogleContactProfile::class);
        foreach (['estudiantes/sinfoto.jpg', 'estudiantes/foto.jpg', 'estudiantes/sinperfil.png', null] as $path) {
            $this->assertNull($profile->photo($this->persona(['foto' => $path])));
        }
        $this->account();
        $person = $this->persona();
        DB::table('personas')->where('id', $person->id)->update(['fechanacimiento' => '2001-02-29']);
        (new SyncGoogleContact($person->id))->handle($this->service());
        $this->assertSame('invalid_birthday', $person->fresh()->gcontact_error_code);
        Http::assertNothingSent();
    }

    public function test_student_rename_and_contract_changes_mark_its_guardians_pending_after_commit()
    {
        $this->account();
        $student = $this->persona();
        $parent = $this->guardian($student);
        Persona::query()->update(['gcontact_sync_pending' => false, 'gcontact_sync_status' => 'synced']);
        DB::beginTransaction();
        $student->nombre = 'Lucas actualizado';
        $student->save();
        Queue::assertNothingPushed();
        $this->assertTrue((bool) $parent->fresh()->gcontact_sync_pending);
        DB::commit();
        Queue::assertPushed(SyncGoogleContact::class, 2);
        Persona::query()->update(['gcontact_sync_pending' => false]);
        $modality = Modalidad::create(['modalidad' => 'Matemáticas']);
        $enrolment = Inscripcione::create(['estudiante_id' => $student->estudiante->id, 'modalidad_id' => $modality->id]);
        $this->assertTrue((bool) $parent->fresh()->gcontact_sync_pending);
        Persona::query()->update(['gcontact_sync_pending' => false]);
        $modality->update(['modalidad' => 'Matemáticas actualizadas']);
        $this->assertTrue((bool) $parent->fresh()->gcontact_sync_pending);
        Persona::query()->update(['gcontact_sync_pending' => false]);
        $enrolment->delete();
        $this->assertTrue((bool) $parent->fresh()->gcontact_sync_pending);
        Http::assertNothingSent();
    }

    public function test_guardian_optional_birthday_and_photo_are_validated_and_creation_form_has_no_child_values()
    {
        foreach ([\App\Http\Requests\PersonaApoderadaRequestStore::class, \App\Http\Requests\TelefonoUpdateRequest::class] as $class) {
            $rules = (new $class)->rules();
            $subset = array_intersect_key($rules, array_flip(['foto', 'fechanacimiento']));
            $this->assertTrue(Validator::make([], $subset)->passes());
            $this->assertTrue(Validator::make(['fechanacimiento' => '1980-02-29', 'foto' => UploadedFile::fake()->image('parent.png')], $subset)->passes());
            $this->assertFalse(Validator::make(['fechanacimiento' => '1981-02-29'], $subset)->passes());
            $this->assertFalse(Validator::make(['foto' => UploadedFile::fake()->create('fake.pdf', 10, 'application/pdf')], $subset)->passes());
        }
        $html = view('telefono.datos-propios', ['contacto' => null, 'errors' => new \Illuminate\Support\ViewErrorBag])->render();
        $this->assertStringContainsString('id="guardian-birthday" value=""', $html);
    }

    public function test_guardian_edit_saves_its_own_birthday_and_photo_and_preserves_omitted_fields()
    {
        $this->account();
        \Illuminate\Support\Facades\Schema::table('personas', function ($table) { $table->string('genero')->nullable(); });
        $student = $this->persona(['foto' => $this->photo('estudiantes/child.jpg'), 'fechanacimiento' => '2012-02-29']);
        $parent = $this->guardian($student);
        $data = ['nombre' => 'Juan', 'apellidop' => 'Quispe', 'genero' => 'HOMBRE', 'parentesco' => 'TUTOR'];
        $request = \App\Http\Requests\TelefonoUpdateRequest::create('/', 'PUT', $data + ['fechanacimiento' => '1980-06-15'], [],
            ['foto' => UploadedFile::fake()->image('parent.png')]);
        $controller = app(\App\Http\Controllers\TelefonoController::class);
        $controller->actualizar($request, $student->id, $parent->id);
        $parent->refresh();
        $photo = $parent->foto;
        $this->assertStringStartsWith('apoderados/', $photo);
        Storage::disk('public')->assertExists($photo);
        $this->assertSame('1980-06-15', $parent->fechanacimiento->format('Y-m-d'));
        $this->assertSame('estudiantes/child.jpg', $student->fresh()->foto);
        $this->assertTrue((bool) $parent->gcontact_sync_pending);
        $controller->actualizar(\App\Http\Requests\TelefonoUpdateRequest::create('/', 'PUT', $data), $student->id, $parent->id);
        $this->assertSame($photo, $parent->fresh()->foto);
        $this->assertSame('1980-06-15', $parent->fresh()->fechanacimiento->format('Y-m-d'));
        Http::assertNothingSent();
    }

    public function test_computer_service_reassignment_marks_both_previous_and_new_students_and_guardians()
    {
        $this->account();
        $first = $this->persona();
        $second = $this->persona(['nombre' => 'Ana']);
        $firstParent = $this->guardian($first);
        $secondParent = $this->guardian($second);
        $old = Computacion::create(['persona_id' => $first->id]);
        $new = Computacion::create(['persona_id' => $second->id]);
        $enrolment = new Matriculacion;
        $enrolment->forceFill(['computacion_id' => $old->id, 'asignatura_id' => 1])->save();
        Persona::query()->update(['gcontact_sync_pending' => false]);
        $enrolment->computacion_id = $new->id;
        $enrolment->save();
        foreach ([$first, $second, $firstParent, $secondParent] as $person) {
            $this->assertTrue((bool) $person->fresh()->gcontact_sync_pending);
        }
        Http::assertNothingSent();
    }
}

<?php

namespace Tests\Feature;

use App\Exceptions\GoogleContactsException;
use App\Jobs\SyncGoogleContact;
use App\Models\Comentario;
use App\Models\Docente;
use App\Models\Persona;
use App\Models\Prospecto;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Tests\GoogleContactsTestCase;

class GoogleContactsSourcesTest extends GoogleContactsTestCase
{
    use \Tests\CrmFixtures;
    private function person(array $attributes = [], bool $events = false): Persona
    {
        $save = function () use ($attributes) {
            $record = new Persona;
            $record->forceFill(array_merge(['nombre' => 'Ana', 'apellidop' => 'Mamani', 'papelinicial' => 'docente',
                'telefono' => null, 'gcontact_sync_status' => 'pending', 'gcontact_sync_pending' => true], $attributes))->save();
            return $record;
        };
        return $events ? $save() : Persona::withoutEvents($save);
    }

    private function enquiry(array $attributes = [], bool $events = false): Comentario
    {
        $save = function () use ($attributes) {
            $record = new Comentario;
            $record->forceFill(array_merge(['nombre' => 'Ana Mamani', 'telefono' => '60902299',
                'gcontact_sync_status' => 'pending', 'gcontact_sync_pending' => true], $attributes))->save();
            return $record;
        };
        return $events ? $save() : Comentario::withoutEvents($save);
    }

    private function prospect(array $attributes = [], bool $events = false): Prospecto
    {
        $save = function () use ($attributes) {
            $record = new Prospecto;
            $record->forceFill(array_merge(['telefono' => '60902299', 'gcontact_sync_status' => 'pending',
                'gcontact_sync_pending' => true], $attributes))->save();
            return $record;
        };
        return $events ? $save() : Prospecto::withoutEvents($save);
    }

    private function fakeCreates(): void
    {
        $sequence = 0;
        $this->fakeHttp(function ($request) use (&$sequence) {
            return $request->method() === 'POST'
                ? Http::response($this->remote('people/created'.(++$sequence)))
                : Http::response(['connections' => []]);
        });
    }

    public function test_all_person_roles_and_crm_leads_without_academic_rows_enqueue_from_model_events()
    {
        $this->account();
        foreach (['docente', 'administrativo', 'contacto', 'estudiante', 'apoderado', 'proveedor', 'cliservicio'] as $role) {
            $person = $this->person(['papelinicial' => $role, 'nombre' => 'Persona '.$role], true);
            $this->assertTrue($this->service()->eligibleQuery()->whereKey($person->id)->exists());
            $this->assertTrue((bool) $person->fresh()->gcontact_sync_pending);
        }
        $this->assertSame(0, DB::table('estudiantes')->count());
        Queue::assertPushed(SyncGoogleContact::class, 7);
        Http::assertNothingSent();
    }

    public function test_assigning_teacher_role_to_existing_person_marks_it_pending()
    {
        $this->account();
        $person = $this->person(['gcontact_sync_pending' => false, 'gcontact_sync_status' => 'idle']);
        Docente::create(['persona_id' => $person->id]);
        $this->assertTrue((bool) $person->fresh()->gcontact_sync_pending);
        Queue::assertPushed(SyncGoogleContact::class, 1);
        Http::assertNothingSent();
    }

    public function test_teacher_and_migrated_person_can_sync_without_phone_or_dummy_number()
    {
        $this->account();
        $this->fakeCreates();
        foreach ([null, '', ' ', '0', '00000000'] as $missing) {
            $person = $this->person(['telefono' => $missing]);
            $this->service()->sync($person->id);
            $this->assertSame('synced', $person->fresh()->gcontact_sync_status);
            $this->assertSame($missing, $person->fresh()->telefono);
        }
        Http::assertSent(function ($request) {
            return $request->method() === 'POST' && $request['phoneNumbers'] === [];
        });
        Http::assertSentCount(10);
    }

    public function test_missing_imported_phone_is_not_erased_remotely_and_adding_it_updates_same_contact()
    {
        $account = $this->account();
        $person = $this->person(['resourseName' => 'people/kept', 'gcontact_account_id' => $account->id]);
        $remote = array_merge($this->remote('people/kept'), ['phoneNumbers' => [['value' => '+59171002299']]]);
        $this->fakeHttp(['*people/kept*' => Http::response($remote)]);
        $this->service()->sync($person->id);
        Http::assertSent(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);
            return $request->method() === 'PATCH' && !isset($request['phoneNumbers'])
                && !in_array('phoneNumbers', explode(',', $query['updatePersonFields'] ?? ''), true);
        });
        $person->update(['telefono' => '60902299', 'nombre' => 'Ana actualizada']);
        $this->assertSame('60902299', $person->fresh()->telefono, 'La asignación masiva debe guardar el teléfono.');
        $this->assertTrue((bool) $person->fresh()->gcontact_sync_pending);
        $this->service()->sync($person->id);
        Http::assertSent(function ($request) {
            return $request->method() === 'PATCH' && ($request['phoneNumbers'][0]['value'] ?? null) === '+59160902299'
                && preg_match('/updatePersonFields=[^&]*phoneNumbers/', $request->url());
        });
        $this->assertSame('people/kept', $person->fresh()->resourseName);
        Http::assertSentCount(4);
    }

    public function test_name_only_legacy_match_with_google_phone_stops_for_review_instead_of_creating_duplicate()
    {
        $this->account();
        $person = $this->person();
        $this->fakeHttp(['*connections*' => Http::response(['connections' => [array_merge($this->remote(), [
            'names' => [['displayName' => 'Ana Mamani']], 'phoneNumbers' => [['value' => '+59160902299']],
        ])]])]);
        (new SyncGoogleContact($person->id))->handle($this->service());
        $this->assertSame('possible_legacy_contact', $person->fresh()->gcontact_error_code);
        $this->assertNull($person->fresh()->resourseName);
        Http::assertSentCount(1);
    }

    public function test_prospect_and_enquiry_create_and_update_without_colliding_with_person_id()
    {
        $this->account();
        $person = $this->person([], true);
        $prospect = $this->prospect([], true);
        $enquiry = $this->enquiry([], true);
        $this->assertSame($person->id, $prospect->id);
        $this->assertSame($person->id, $enquiry->id);
        Queue::assertPushed(SyncGoogleContact::class, 3);
        $this->assertCount(3, array_unique(array_map(function ($source) use ($person) {
            return (new SyncGoogleContact($person->id, null, $source))->uniqueId();
        }, ['persona', 'prospecto', 'comentario'])));
        $this->fakeCreates();
        foreach ([$person, $prospect, $enquiry] as $record) {
            (new SyncGoogleContact($record->id, null, $this->service()->sourceFor($record)))->handle($this->service());
            $this->assertSame('synced', $record->fresh()->gcontact_sync_status);
        }
        Http::assertSent(function ($request) {
            return $request->method() === 'POST' && $request['names'][0]['givenName'] === 'Prospecto IFE | 60902299'
                && ($request['userDefined'][0]['key'] ?? '') === 'IFE_PROSPECTO';
        });
        $this->assertCount(3, array_unique([$person->fresh()->resourseName, $prospect->fresh()->resourseName, $enquiry->fresh()->resourseName]));
        $prospect->telefono = '71002299';
        $prospect->save();
        $this->assertTrue((bool) $prospect->fresh()->gcontact_sync_pending);
        $resource = $prospect->fresh()->resourseName;
        $this->fakeHttp(['*'.$resource.'*' => Http::response(array_merge($this->remote($resource),['userDefined'=>[['key'=>'IFE_PROSPECTO','value'=>$this->service()->account()->installation_id.':'.$prospect->id]]]))]);
        $this->service()->sync($prospect->id, null, 'prospecto');
        $this->assertSame($resource, $prospect->fresh()->resourseName);
        Http::assertSent(function ($request) {
            return $request->method() === 'PATCH' && $request['names'][0]['givenName']==='Prospecto IFE | 71002299'
                && ($request['phoneNumbers'][0]['value'] ?? '') === '+59171002299';
        });
        Http::assertSentCount(2);
    }

    public function test_invalid_raw_phone_records_error_only_on_its_own_source()
    {
        $this->account();
        $person = $this->person();
        $prospect = $this->prospect(['telefono' => 'invalid']);
        $enquiry = $this->enquiry();
        (new SyncGoogleContact($prospect->id, null, 'prospecto'))->handle($this->service());
        $this->assertSame('invalid_phone', $prospect->fresh()->gcontact_error_code);
        $this->assertNull($person->fresh()->gcontact_error_code);
        $this->assertNull($enquiry->fresh()->gcontact_error_code);
        Http::assertNothingSent();
    }

    public function test_converting_enquiry_preserves_google_link_and_old_queued_job_does_not_create_again()
    {
        $account = $this->account();
        $person = $this->person();
        $enquiry = $this->enquiry(['resourseName' => 'people/enquiry', 'gcontact_account_id' => $account->id]);
        $enquiry->persona_id = $person->id;
        $enquiry->save();
        $remote = $this->remote('people/enquiry');
        $remote['userDefined'] = [['key' => 'IFE_COMENTARIO', 'value' => $account->installation_id.':'.$enquiry->id]];
        $this->fakeHttp(['*people/enquiry*' => Http::response($remote)]);
        (new SyncGoogleContact($enquiry->id, null, 'comentario'))->handle($this->service());
        Http::assertNothingSent();
        $this->service()->sync($person->id);
        $this->assertSame('people/enquiry', $person->fresh()->resourseName);
        Http::assertSent(function ($request) {
            return $request->method() === 'PATCH' && count($request['userDefined']) === 2
                && !isset($request['phoneNumbers']);
        });
        Http::assertSentCount(2);
        $this->assertFalse($this->service()->eligibleQuery('comentario')->whereKey($enquiry->id)->exists());
    }

    public function test_uncertain_enquiry_creation_is_recovered_by_original_marker_after_conversion()
    {
        $account = $this->account();
        $person = $this->person();
        $enquiry = $this->enquiry(['gcontact_create_started_at' => now(), 'gcontact_account_id' => $account->id,
            'persona_id' => $person->id]);
        $remote = $this->remote('people/recovered-enquiry');
        $remote['userDefined'] = [['key' => 'IFE_COMENTARIO', 'value' => $account->installation_id.':'.$enquiry->id]];
        $this->fakeHttp([
            '*connections*' => Http::response(['connections' => [$remote]]),
            '*people/recovered-enquiry*' => Http::response($remote),
        ]);
        $this->service()->sync($person->id);
        $this->assertSame('people/recovered-enquiry', $person->fresh()->resourseName);
        $this->assertNull($person->fresh()->gcontact_create_started_at);
        Http::assertSentCount(3);
        Http::assertNotSent(function ($request) { return $request->method() === 'POST'; });
    }

    public function test_conversion_during_enquiry_creation_adopts_the_result_after_the_request_finishes()
    {
        $account = $this->account();
        $person = $this->person(['gcontact_sync_pending' => false]);
        $enquiry = $this->enquiry();
        $remote = $this->remote('people/inflight-enquiry');
        $remote['userDefined'] = [['key' => 'IFE_COMENTARIO', 'value' => $account->installation_id.':'.$enquiry->id]];
        $this->fakeHttp(function ($request) use ($enquiry, $person, $remote) {
            if ($request->method() === 'POST') {
                // Conversion commits while Google is processing the original enquiry request.
                $enquiry->persona_id = $person->id;
                $enquiry->save();
            }
            return Http::response(strpos($request->url(), 'connections') !== false
                ? ['connections' => []] : $remote);
        });
        $this->service()->sync($enquiry->id, null, 'comentario');
        $this->assertSame('people/inflight-enquiry', $enquiry->fresh()->resourseName);
        $this->service()->sync($person->id);
        $this->assertSame('people/inflight-enquiry', $person->fresh()->resourseName);
        $this->assertSame('synced', $person->fresh()->gcontact_sync_status);
        $posts = Http::recorded(function ($request) { return $request->method() === 'POST'; });
        $this->assertCount(1, $posts);
        Http::assertSentCount(4);
    }

    public function test_phone_only_prospect_with_matching_google_phone_requires_review_without_auto_merging()
    {
        $this->account();
        $prospect = $this->prospect();
        $remote = array_merge($this->remote(), ['names' => [['displayName' => 'Otra persona']],
            'phoneNumbers' => [['value' => '+59160902299']]]);
        $this->fakeHttp(['*connections*' => Http::response(['connections' => [$remote]])]);
        (new SyncGoogleContact($prospect->id, null, 'prospecto'))->handle($this->service());
        $this->assertSame('possible_legacy_contact', $prospect->fresh()->gcontact_error_code);
        $this->assertNull($prospect->fresh()->resourseName);
        Http::assertSentCount(1);
    }

    public function test_unresolved_enquiry_creation_after_conversion_never_blindly_recreates()
    {
        $this->account();
        $person = $this->person();
        $this->enquiry(['gcontact_create_started_at' => now(), 'persona_id' => $person->id]);
        $this->fakeHttp(['*connections*' => Http::response(['connections' => []])]);
        (new SyncGoogleContact($person->id))->handle($this->service());
        $this->assertSame('uncertain_create', $person->fresh()->gcontact_error_code);
        Http::assertSentCount(1);
    }

    public function test_conflicting_enquiry_links_are_not_merged_or_adopted_partially()
    {
        $this->account();
        $person = $this->person();
        $this->enquiry(['persona_id' => $person->id, 'resourseName' => 'people/one']);
        $this->enquiry(['persona_id' => $person->id, 'resourseName' => 'people/two']);
        (new SyncGoogleContact($person->id))->handle($this->service());
        $this->assertSame('contact_link_conflict', $person->fresh()->gcontact_error_code);
        $this->assertNull($person->fresh()->resourseName);
        Http::assertNothingSent();
    }

    public function test_raw_sources_are_recovered_fairly_by_sweeper_and_stay_pending_during_pause()
    {
        $this->account();
        $this->person();
        $this->prospect();
        $this->enquiry();
        $this->service()->setPaused(true, $this->actor()->id);
        $this->assertSame(0, $this->service()->enqueuePending(3));
        Queue::assertNothingPushed();
        $this->service()->setPaused(false, $this->actor()->id);
        Queue::assertPushed(SyncGoogleContact::class, 3);
        foreach (['persona', 'prospecto', 'comentario'] as $source) {
            Queue::assertPushed(SyncGoogleContact::class, function ($job) use ($source) { return $job->source === $source; });
        }
        Http::assertNothingSent();
    }

    public function test_source_panels_search_and_missing_phone_filter_work_without_mixing_ids()
    {
        $this->account();
        $without = $this->person(['telefono' => '00000000']);
        $with = $this->person(['nombre' => 'Tiene numero', 'telefono' => '60902299']);
        $enquiry = $this->enquiry();
        $prospect = $this->prospect();
        $this->actingAs($this->actor())->get('/contactos/google?without_phone=1')->assertOk()
            ->assertSee('Sin teléfono')->assertDontSee('Tiene numero')
            ->assertViewHas('personas', function ($records) use ($without) { return $records->pluck('id')->all() === [$without->id]; });
        $this->get('/contactos/google?source=prospecto&q=60902299')->assertOk()->assertSee('Prospecto #'.$prospect->id);
        $this->get('/contactos/google?source=comentario&q=Ana')->assertOk()->assertSee('Ana Mamani');
        $this->get('/contactos/google?source=not-a-source')->assertSessionHasErrors('source');
        Http::assertNothingSent();
    }

    public function test_raw_sync_routes_are_authorized_and_converted_enquiry_cannot_sync_separately()
    {
        $this->account();
        $prospect = $this->prospect();
        $enquiry = $this->enquiry();
        $this->actingAs($this->actor('Padre'))->post('/contactos/google/prospectos/'.$prospect->id.'/sincronizar')->assertForbidden();
        $this->post('/contactos/google/consultas/'.$enquiry->id.'/sincronizar')->assertForbidden();
        $this->actingAs($this->actor())->post('/contactos/google/prospectos/'.$prospect->id.'/sincronizar')->assertRedirect();
        $this->post('/contactos/google/consultas/'.$enquiry->id.'/sincronizar')->assertRedirect();
        $enquiry->persona_id = $this->person()->id;
        $enquiry->save();
        $this->post('/contactos/google/consultas/'.$enquiry->id.'/sincronizar')->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_real_database_queue_preserves_three_sources_and_rollback_does_not_leak_jobs()
    {
        $this->account();
        Queue::swap($this->realQueue);
        DB::beginTransaction();
        $this->enquiry([], true);
        $this->prospect([], true);
        $this->assertSame(0, DB::table('jobs')->count());
        DB::rollBack();
        $this->assertSame(0, Comentario::count());
        $this->assertSame(0, Prospecto::count());
        $this->person([], true);
        $this->enquiry([], true);
        $this->prospect([], true);
        $this->assertSame(3, DB::table('jobs')->count());
        $this->fakeCreates();
        for ($i = 0; $i < 3; $i++) {
            Queue::connection('google_contacts')->pop('google-contacts')->fire();
        }
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame('synced', Persona::first()->gcontact_sync_status);
        $this->assertSame('synced', Comentario::first()->gcontact_sync_status);
        $this->assertSame('synced', Prospecto::first()->gcontact_sync_status);
    }

    public function test_prospect_edit_updates_existing_record_and_enqueues_it_instead_of_creating_another()
    {
        $this->prepareCrmFixtures();
        $this->account();
        $prospect = $this->prospect(['estado_id' => 1]);
        $this->actingAs($this->actor())->patch(route('prospecto.update', $prospect), ['telefono' => '71002299','version'=>0])
            ->assertRedirect(route('prospecto.view', $prospect));
        $this->assertSame(1, Prospecto::count());
        $this->assertSame('71002299', $prospect->fresh()->telefono);
        $this->assertSame(1, (int) $prospect->fresh()->estado_id);
        Queue::assertPushed(SyncGoogleContact::class, function ($job) use ($prospect) {
            return $job->source === 'prospecto' && $job->personaId === $prospect->id;
        });
        Http::assertNothingSent();
    }

    public function test_previously_early_returning_consultation_route_now_saves_and_enqueues()
    {
        $this->account();
        $response = $this->actingAs($this->actor())->post('/comentario/guardar', [
            'nombre' => 'Consulta de prueba', 'telefono' => '60902299', 'comentario' => 'Me interesa un curso.',
        ])->assertOk()->assertJsonPath('success', true);
        $this->assertArrayNotHasKey('gcontact_sync_pending', $response->json('data'));
        $this->assertSame(1, Comentario::count());
        $this->assertTrue((bool) Comentario::first()->gcontact_sync_pending);
        Queue::assertPushed(SyncGoogleContact::class, function ($job) { return $job->source === 'comentario'; });
        $this->get('/comentario/guardar')->assertStatus(405);
        Http::assertNothingSent();
    }

    public function test_teacher_update_without_phone_field_keeps_existing_number_and_enqueues_changes()
    {
        $this->account();
        Schema::table('personas', function (Blueprint $table) {
            foreach (['direccion', 'carnet', 'expedido', 'genero', 'como_id', 'persona_id', 'pais_id', 'ciudad_id', 'zona_id'] as $field) {
                $table->string($field)->nullable();
            }
        });
        Schema::table('docentes', function (Blueprint $table) {
            foreach (['nombrecorto', 'fecha_inicio', 'dias_prueba', 'sueldo', 'mododocente_id', 'perfil', 'estado_id'] as $field) {
                $table->string($field)->nullable();
            }
        });
        Schema::create('interest_persona', function (Blueprint $table) {
            $table->unsignedBigInteger('persona_id');
            $table->unsignedBigInteger('interest_id');
            $table->timestamps();
        });
        Schema::create('observacions', function (Blueprint $table) {
            $table->id();
            $table->text('observacion');
            $table->boolean('activo');
            $table->string('observable_type');
            $table->unsignedBigInteger('observable_id');
            $table->timestamps();
        });
        $person = $this->person(['telefono' => '60902299', 'gcontact_sync_pending' => false]);
        $teacher = Docente::withoutEvents(function () use ($person) { return Docente::create(['persona_id' => $person->id]); });
        $admin = $this->actor();
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'Editar Docentes', 'guard_name' => 'web']));
        $this->actingAs($admin)->patch(route('docentes.update', $teacher), [
            'nombre' => 'Ana actualizada', 'apellidop' => 'Mamani', 'papel' => 'docente',
            'nombrecorto' => 'Ana docente', 'fecha_inicio' => '2026-01-01', 'dias_prueba' => 3,
            'sueldo' => 2000, 'mododocente_id' => 1, 'estado_id' => 1, 'perfil' => 'Docente',
            'interests' => [1 => 'on'], 'observacion' => 'Actualización de ficha.',
        ])->assertRedirect(route('docentes.show', $teacher));
        $this->assertSame('60902299', $person->fresh()->telefono);
        $this->assertSame('Ana actualizada', $person->fresh()->nombre);
        Queue::assertPushed(SyncGoogleContact::class, function ($job) use ($person) {
            return $job->source === 'persona' && $job->personaId === $person->id;
        });
        Http::assertNothingSent();
    }

    public function test_person_forms_accept_missing_phone_and_keep_other_field_rules()
    {
        $person = $this->person();
        foreach ([\App\Http\Requests\PersonaStoreRequest::class, \App\Http\Requests\PersonaUpdateRequest::class,
            \App\Http\Requests\PersonaApoderadaRequestStore::class, \App\Http\Requests\RequestStoreSoloContacto::class,
            \App\Http\Requests\TelefonoUpdateRequest::class, \App\Http\Requests\GuardarApoderadoExistenteRequest::class,
            \App\Http\Requests\PersonaRapidingoGuardarRequest::class, \App\Http\Requests\DocenteUpdateRequest::class] as $class) {
            $request = new $class;
            if ($request instanceof \App\Http\Requests\PersonaUpdateRequest) {
                $route = \Mockery::mock(\Illuminate\Routing\Route::class);
                $route->shouldReceive('parameter')->with('persona', null)->andReturn($person);
                $request->setRouteResolver(function () use ($route) { return $route; });
            }
            $rules = $request->rules();
            $this->assertTrue(Validator::make([], ['telefono' => $rules['telefono']])->passes(), $class);
            $this->assertTrue(Validator::make(['telefono' => null], ['telefono' => $rules['telefono']])->passes(), $class);
            $this->assertTrue(Validator::make(['telefono' => ''], ['telefono' => $rules['telefono']])->passes(), $class);
        }
        Http::assertNothingSent();
    }

    public function test_public_consultation_is_saved_without_exposing_google_metadata()
    {
        $this->account();
        Schema::create('comos', function (Blueprint $table) { $table->id(); $table->string('como'); });
        DB::table('comos')->insert(['id' => 6, 'como' => 'Sitio web']);
        // Only the external CAPTCHA check is simulated; keep request validation and persistence.
        Validator::extend('recaptchav3', function () { return true; });
        $response = $this->postJson('/comentario/crear', ['nombre' => 'Persona interesada', 'telefono' => '60902299',
            'comentario' => 'Consulta desde la web.', 'como_id' => 6, 'recaptcha_token' => 'simulated-test']);
        $response->assertOk();
        $this->assertSame(1, Comentario::count());
        $this->assertArrayNotHasKey('gcontact_account_id', $response->json('data'));
        Queue::assertPushed(SyncGoogleContact::class, function ($job) { return $job->source === 'comentario'; });
        Http::assertNothingSent();
    }

    public function test_public_consultation_keeps_captcha_validation_and_does_not_save_rejected_requests()
    {
        $this->account();
        Schema::create('comos', function (Blueprint $table) { $table->id(); $table->string('como'); });
        DB::table('comos')->insert(['id' => 6, 'como' => 'Sitio web']);
        // Reject CAPTCHA without making a real verification request.
        Validator::extend('recaptchav3', function () { return false; });
        $data = ['nombre' => 'Persona interesada', 'telefono' => '60902299',
            'comentario' => 'Consulta desde la web.', 'como_id' => 6];
        $this->postJson('/comentario/crear', $data)->assertStatus(422)->assertJsonValidationErrors('recaptcha_token');
        $this->postJson('/comentario/crear', $data + ['recaptcha_token' => 'rejected-test'])
            ->assertStatus(422)->assertJsonValidationErrors('recaptcha_token');
        $this->assertSame(0, Comentario::count());
        Queue::assertNotPushed(SyncGoogleContact::class);
        Http::assertNothingSent();
    }

    public function test_anonymous_enquiry_exports_phone_without_inventing_a_person_name()
    {
        $this->account();
        $enquiry = $this->enquiry(['nombre' => 'anonymous']);
        $this->fakeCreates();
        $this->service()->sync($enquiry->id, null, 'comentario');
        Http::assertSent(function ($request) {
            return $request->method() === 'POST' && $request['names'] === []
                && ($request['phoneNumbers'][0]['value'] ?? '') === '+59160902299';
        });
        $this->assertSame('anonymous', $enquiry->fresh()->nombre);
    }

    public function test_jobs_serialized_before_source_extension_still_default_to_persona()
    {
        $job = new SyncGoogleContact(26);
        unset($job->source);
        $restored = unserialize(serialize($job));
        $this->assertSame('persona', $restored->source);
        $this->assertSame('gcontact:persona:26', $restored->uniqueId());
    }
}

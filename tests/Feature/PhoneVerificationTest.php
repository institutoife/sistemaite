<?php

namespace Tests\Feature;

use App\Models\Persona;
use App\Services\PhoneVerificationService;
use Database\Seeders\GoogleContactsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\GoogleContactsTestCase;

class PhoneVerificationTest extends GoogleContactsTestCase
{
    private function url(Persona $person): string { return route('telefonos.verificacion.show', $person); }
    private function version(Persona $person): string { return app(PhoneVerificationService::class)->fingerprint($person->fresh()); }

    public function test_staff_can_review_family_without_writes_and_parents_cannot_read_or_confirm(): void
    {
        $student = $this->persona();
        $parent = $this->persona(['telefono' => null]);
        $student->apoderados()->attach($parent->id, ['parentesco' => 'Mamá']);
        $this->getJson($this->url($student))->assertUnauthorized();
        $this->actingAs($this->actor('Padre'))->getJson($this->url($student))->assertForbidden();
        $this->postJson($this->url($student), ['action' => 'confirm', 'version' => $this->version($student)])->assertForbidden();
        $staff = $this->actor('Secretaria');
        $this->seed(GoogleContactsSeeder::class);
        $response = $this->actingAs($staff)->getJson($this->url($student))->assertOk()->assertJsonCount(2, 'people');
        $this->assertSame([$student->id, $parent->id], array_column($response->json('people'), 'id'));
        $this->assertFalse($response->json('people.1.valid_phone'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame(0, DB::table('person_phone_verifications')->count());
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_confirmation_is_shared_for_six_months_and_does_not_resync_unchanged_phone(): void
    {
        $person = $this->persona(['gcontact_sync_pending' => false, 'gcontact_sync_status' => 'synced', 'resourseName' => 'people/kept']);
        $admin = $this->actor();
        $this->actingAs($admin)->postJson($this->url($person), ['action' => 'confirm', 'version' => $this->version($person)])->assertOk();
        $this->assertDatabaseHas('person_phone_verifications', ['persona_id' => $person->id, 'verified_by' => $admin->id]);
        $this->actingAs($this->actor())->getJson($this->url($person))->assertJsonCount(0, 'people');
        $this->getJson($this->url($person).'?review_now=1')->assertJsonCount(1, 'people');
        $this->assertSame('synced', $person->fresh()->gcontact_sync_status);
        $this->assertSame('people/kept', $person->fresh()->resourseName);
        $this->travelTo(now()->addMonthsNoOverflow(6));
        $this->getJson($this->url($person))->assertJsonCount(1, 'people');
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_changes_outside_verification_invalidate_previous_confirmation(): void
    {
        $person = $this->persona();
        $this->actingAs($this->actor())->postJson($this->url($person), ['action' => 'confirm', 'version' => $this->version($person)])->assertOk();
        DB::table('personas')->where('id', $person->id)->update(['telefono' => '70000001']);
        $this->getJson($this->url($person))->assertJsonPath('people.0.phone', '70000001')->assertJsonPath('people.0.last_verified_at', null);
    }

    public function test_collecting_missing_parent_phone_updates_all_children_and_enqueues_one_existing_contact(): void
    {
        $this->account();
        $parent = $this->persona(['telefono' => null, 'gcontact_sync_pending' => false, 'resourseName' => 'people/kept']);
        foreach ([$this->persona(), $this->persona()] as $child) {
            $child->apoderados()->attach($parent->id, ['telefono' => null, 'parentesco' => 'Mamá']);
        }
        Queue::swap($this->realQueue);
        $admin = $this->actor();
        $this->actingAs($admin)->postJson($this->url($parent), ['action' => 'update', 'version' => $this->version($parent),
            'phone' => '+591 70000001'])->assertOk();
        $this->assertSame('70000001', $parent->fresh()->telefono);
        $this->assertSame('people/kept', $parent->fresh()->resourseName);
        $this->assertSame('pending', $parent->fresh()->gcontact_sync_status);
        $this->assertSame(['70000001', '70000001'], DB::table('persona_persona')->where('persona_id_apoderado', $parent->id)->pluck('telefono')->all());
        $this->assertDatabaseHas('person_phone_verifications', ['persona_id' => $parent->id, 'verified_by' => $admin->id]);
        $this->getJson($this->url($parent))->assertJsonCount(0, 'people');
        $this->assertSame(1, DB::table('jobs')->where('queue', 'google-contacts')->count());
        $this->assertSame(0, DB::transactionLevel());
        Http::assertNothingSent();
    }

    public function test_invalid_or_missing_phone_cannot_be_confirmed_and_bad_input_does_not_replace_data(): void
    {
        $person = $this->persona(['telefono' => '00000000']);
        $this->actingAs($this->actor());
        $this->postJson($this->url($person), ['action' => 'confirm', 'version' => $this->version($person)])->assertUnprocessable();
        $this->postJson($this->url($person), ['action' => 'update', 'version' => $this->version($person), 'phone' => '111'])->assertUnprocessable();
        $this->assertSame('00000000', $person->fresh()->telefono);
        $this->assertSame(0, DB::table('person_phone_verifications')->count());
        Queue::assertNothingPushed();
    }

    public function test_stale_modal_cannot_overwrite_or_confirm_a_number_changed_by_another_operator(): void
    {
        $person = $this->persona();
        $oldVersion = $this->version($person);
        $second = $this->actor();
        $this->actingAs($second)->postJson($this->url($person), ['action' => 'update', 'version' => $oldVersion, 'phone' => '70000001'])->assertOk();
        $this->actingAs($this->actor())->postJson($this->url($person), ['action' => 'update', 'version' => $oldVersion, 'phone' => '70000002'])->assertStatus(409);
        $this->postJson($this->url($person), ['action' => 'confirm', 'version' => $oldVersion])->assertStatus(409);
        $this->assertSame('70000001', $person->fresh()->telefono);
        $this->assertDatabaseHas('person_phone_verifications', ['persona_id' => $person->id, 'verified_by' => $second->id]);
    }

    public function test_postponement_only_affects_that_operator_and_never_confirms_missing_data(): void
    {
        $person = $this->persona(['telefono' => null]);
        $first = $this->actor();
        $this->actingAs($first)->postJson($this->url($person), ['action' => 'defer', 'version' => $this->version($person)])->assertOk();
        $this->getJson($this->url($person))->assertJsonCount(0, 'people');
        $this->getJson($this->url($person).'?review_now=1')->assertJsonCount(1, 'people');
        $this->actingAs($this->actor())->getJson($this->url($person))->assertJsonCount(1, 'people');
        $this->travel(1)->days();
        $this->actingAs($first)->getJson($this->url($person))->assertJsonCount(1, 'people');
        $this->assertSame(0, DB::table('person_phone_verifications')->count());
        $this->assertNull($person->fresh()->telefono);
        Queue::assertNothingPushed();
    }
}

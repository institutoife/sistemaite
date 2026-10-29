<?php
namespace Tests\Feature;
use App\Models\Prospecto;
use App\Jobs\SyncGoogleContact;
use Illuminate\Support\Facades\{Http,DB};
use Tests\CrmTestCase;
class CrmGoogleIdentityTest extends CrmTestCase
{
    public function test_unnamed_prospect_gets_a_clear_name_and_later_name_updates_the_same_marked_google_contact(): void
    {
        $account=$this->account(); $lead=$this->lead(['gcontact_sync_pending'=>true]); $creates=0; $remote=null;
        $this->fakeHttp(function ($request) use (&$creates,&$remote) {
            if (strpos($request->url(),'connections')!==false) { return Http::response(['connections'=>[]]); }
            if ($request->method()==='POST') { $creates++; $remote=$this->remote('people/lead')+['names'=>$request['names'],'userDefined'=>$request['userDefined'],'phoneNumbers'=>$request['phoneNumbers']]; }
            if ($request->method()==='PATCH') { $remote['names']=$request['names']; }
            return Http::response($remote);
        });
        $this->service()->sync($lead->id,null,'prospecto');
        $this->assertSame('Prospecto IFE | 70000001',$remote['names'][0]['givenName']);
        $lead->refresh(); $lead->nombre='Juan Pérez'; $lead->save(); $this->service()->sync($lead->id,null,'prospecto');
        $this->assertSame('Juan Pérez',$remote['names'][0]['givenName']); $this->assertSame('| Prospecto IFE',$remote['names'][0]['honorificSuffix']);
        $this->assertSame(1,$creates); $this->assertSame('people/lead',$lead->fresh()->resourseName);
        $this->assertSame('IFE_PROSPECTO',$remote['userDefined'][0]['key']);
    }
    public function test_an_unknown_local_name_preserves_an_existing_real_name_owned_by_ife(): void
    {
        $account=$this->account(); $lead=$this->lead(['gcontact_sync_pending'=>true,'resourseName'=>'people/known']);
        $remote=$this->remote('people/known')+['names'=>[['givenName'=>'Nombre real','familyName'=>'Conocido']],
            'userDefined'=>[['key'=>'IFE_PROSPECTO','value'=>$account->installation_id.':'.$lead->id]]];
        $this->fakeHttp(['*people/known*'=>Http::response($remote)]);
        $this->service()->sync($lead->id,null,'prospecto');
        Http::assertSent(function ($request) { return $request->method()==='PATCH'&&!isset($request['names']); });
        $this->assertSame('synced',$lead->fresh()->gcontact_sync_status);
    }
    public function test_a_personal_contact_without_ife_identity_is_never_overwritten_even_when_the_phone_matches(): void
    {
        $this->account(); $lead=$this->lead(['nombre'=>'Nuevo nombre','gcontact_sync_pending'=>true,'resourseName'=>'people/personal']);
        $remote=$this->remote('people/personal')+['names'=>[['givenName'=>'Contacto personal']], 'phoneNumbers'=>[['value'=>'+59170000001']]];
        $this->fakeHttp(['*people/personal*'=>Http::response($remote)]);
        (new SyncGoogleContact($lead->id,null,'prospecto'))->handle($this->service());
        $this->assertSame('unverified_contact_owner',$lead->fresh()->gcontact_error_code);
        Http::assertSentCount(1); Http::assertNotSent(function ($request) { return $request->method()!=='GET'; });
    }
    public function test_a_saved_legacy_phone_only_contact_is_adopted_only_when_the_phone_matches(): void
    {
        $this->account(); $lead=$this->lead(['gcontact_sync_pending'=>true,'resourseName'=>'people/legacy']);
        $remote=$this->remote('people/legacy')+['names'=>[['displayName'=>'70000001']],'phoneNumbers'=>[['value'=>'+59170000001']]];
        $this->fakeHttp(['*people/legacy*'=>Http::response($remote)]);
        $this->service()->sync($lead->id,null,'prospecto');
        Http::assertSent(function ($request) { return $request->method()==='PATCH'&&$request['names'][0]['givenName']==='Prospecto IFE | 70000001'; });
        $this->assertSame('people/legacy',$lead->fresh()->resourseName);
    }
    public function test_an_uncertain_prospect_creation_recovers_its_original_marker_after_linking_to_persona(): void
    {
        $account=$this->account(); $person=$this->persona(['gcontact_sync_pending'=>true]);
        $lead=$this->lead(['persona_id'=>$person->id,'gcontact_create_started_at'=>now(),'gcontact_account_id'=>$account->id]);
        $remote=$this->remote('people/recovered')+['userDefined'=>[['key'=>'IFE_PROSPECTO','value'=>$account->installation_id.':'.$lead->id]]];
        $this->fakeHttp(['*connections*'=>Http::response(['connections'=>[$remote]]),'*people/recovered*'=>Http::response($remote)]);
        $this->service()->sync($person->id);
        $this->assertSame('people/recovered',$person->fresh()->resourseName); $this->assertNull($person->fresh()->gcontact_create_started_at);
        Http::assertNotSent(function ($request) { return $request->method()==='POST'; });
        Http::assertSent(function ($request) { return $request->method()==='PATCH'&&count($request['userDefined'])===2; });
    }
    public function test_a_linked_potencial_keeps_structured_person_name_and_changes_commercial_suffix_after_conversion(): void
    {
        $this->account(); $person=$this->persona(); $lead=$this->lead(['persona_id'=>$person->id,'crm_status'=>'activo']);
        $profile=app(\App\Services\GoogleContactProfile::class);
        $this->assertStringContainsString('Prospecto IFE',$profile->fields($person)['names'][0]['honorificSuffix']);
        DB::table('prospectos')->where('id',$lead->id)->update(['crm_status'=>'convertido']);
        $this->assertStringNotContainsString('Prospecto IFE',$profile->fields($person)['names'][0]['honorificSuffix']);
        $this->assertSame('Lucas',$profile->fields($person)['names'][0]['givenName']);
    }
}

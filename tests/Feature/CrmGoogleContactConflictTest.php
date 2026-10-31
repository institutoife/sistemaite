<?php
namespace Tests\Feature;

use App\Jobs\SyncGoogleContact;
use App\Models\{Prospecto,Persona,CrmActividadDetalle};
use App\Services\{CrmAnalytics,CrmUserAnalytics};
use Illuminate\Support\Facades\{DB,Http,Queue};
use Tests\CrmTestCase;

class CrmGoogleContactConflictTest extends CrmTestCase
{
    private function scenario(bool $knownAccounts=true): array
    {
        $account=$this->account();
        $person=$this->persona(['resourseName'=>'people/client','gcontact_account_id'=>$knownAccounts?$account->id:null]);
        $lead=$this->lead(['resourseName'=>'people/prospect','gcontact_account_id'=>$knownAccounts?$account->id:null,
            'gcontact_sync_pending'=>true,'gcontact_sync_status'=>'synced']);
        $enrollment=DB::table('inscripciones')->insertGetId(['estudiante_id'=>$person->estudiante->id]);
        $proof=['version'=>0,'persona_id'=>$person->id,'estudiante_persona_id'=>$person->id,'tipo'=>'inscripcion',
            'inscripcion_id'=>$enrollment,'confirmar_vinculos'=>1,'conservar_contactos_google'=>1];
        return [$account,$person,$lead,$proof];
    }

    public function test_explicit_confirmation_keeps_both_links_and_only_syncs_persona_without_duplicate_conversions(): void
    {
        [$account,$person,$lead,$proof]=$this->scenario();
        $this->getJson(route('crm.conversion.inscripciones',[$lead,'persona_id'=>$person->id]))->assertOk()->assertJsonPath('google_conflict',true);
        $withoutChoice=$proof; unset($withoutChoice['conservar_contactos_google']);
        $this->postJson(route('crm.convertir',$lead),$withoutChoice)->assertUnprocessable()->assertJsonValidationErrors('conservar_contactos_google');
        $this->assertNull($lead->fresh()->persona_id); $this->assertSame(0,CrmActividadDetalle::count());
        $this->postJson(route('crm.convertir',$lead),$proof)->assertOk()->assertJsonPath('converted',true);
        $this->postJson(route('crm.convertir',$lead),$proof)->assertOk();
        $this->assertSame('people/client',$person->fresh()->resourseName); $this->assertSame('people/prospect',$lead->fresh()->resourseName);
        $this->assertFalse((bool)$lead->fresh()->gcontact_sync_pending);
        $event=CrmActividadDetalle::where('tipo','conversion')->sole();
        $decision=$event->datos['google_contactos'];
        $this->assertSame('conservar_separados',$decision['politica']);
        $this->assertSame('people/prospect',$decision['prospecto_resource']);
        $this->assertSame('people/client',$decision['persona_resource']);
        $this->assertSame($this->operator->id,$event->actor_id);
        $this->assertSame(1,app(CrmAnalytics::class)->report([])['converted']);
        $this->assertSame(1,app(CrmUserAnalytics::class)->report([])['totals']['conversions_done']);
        $this->get(route('prospecto.view',$lead))->assertOk()->assertSee('Se conservaron ambos contactos Google');
        Queue::assertPushed(SyncGoogleContact::class,1); Http::assertNothingSent();
        // Simulate JSON object key reordering by a MySQL server.
        $data=$event->datos; $data['google_contactos']=array_reverse($decision,true); $event->datos=$data; $event->save();
        $remote=$this->remote('people/client')+['names'=>[['givenName'=>'Cliente registrado']],
            'userDefined'=>[['key'=>'IFE_PERSONA','value'=>$account->installation_id.':'.$person->id]],
            'biographies'=>[['value'=>'Nota personal conservada','contentType'=>'TEXT_PLAIN']]];
        $this->fakeHttp(['*people/client*'=>Http::response($remote)]);
        (new SyncGoogleContact($lead->id,null,'prospecto'))->handle($this->service());
        Http::assertNothingSent();
        (new SyncGoogleContact($person->id))->handle($this->service());
        $this->assertSame('synced',$person->fresh()->gcontact_sync_status);
        Http::assertSentCount(2);
        Http::assertSent(function ($request) use ($person,$account) {
            return $request->method()==='PATCH'&&$request['userDefined']===[['key'=>'IFE_PERSONA','value'=>$account->installation_id.':'.$person->id]]
                &&strpos($request['biographies'][0]['value'],'Nota personal conservada')!==false;
        });
        Http::assertNotSent(function ($request) { return strpos($request->url(),'people/prospect')!==false||!in_array($request->method(),['GET','PATCH'],true); });
        $this->assertSame('people/prospect',$lead->fresh()->resourseName);
        $this->assertSame(1,CrmActividadDetalle::where('tipo','conversion')->count());
    }

    public function test_a_converted_record_without_an_explicit_audit_cannot_bypass_the_google_conflict(): void
    {
        [$account,$person,$lead,$proof]=$this->scenario();
        DB::table('prospectos')->where('id',$lead->id)->update(['persona_id'=>$person->id,'crm_status'=>'convertido',
            'convertido_at'=>now(),'inscripcione_id'=>$proof['inscripcion_id']]);
        (new SyncGoogleContact($person->id))->handle($this->service());
        $this->assertSame('contact_link_conflict',$person->fresh()->gcontact_error_code);
        $this->assertSame('people/client',$person->fresh()->resourseName); Http::assertNothingSent();
    }

    /** @dataProvider changedLinks */
    public function test_a_changed_or_lost_link_requires_review_without_adopting_the_old_contact(string $source, ?string $resource): void
    {
        [$account,$person,$lead,$proof]=$this->scenario();
        $this->postJson(route('crm.convertir',$lead),$proof)->assertOk();
        DB::table($source==='persona'?'personas':'prospectos')->where('id',$source==='persona'?$person->id:$lead->id)->update(['resourseName'=>$resource]);
        (new SyncGoogleContact($person->id))->handle($this->service());
        $this->assertSame('contact_link_conflict',$person->fresh()->gcontact_error_code);
        $this->assertSame($source==='persona'?$resource:'people/client',$person->fresh()->resourseName);
        Http::assertNothingSent();
    }

    public static function changedLinks(): array
    {
        return [['persona',null],['persona','people/changed'],['prospecto','people/changed']];
    }

    public function test_legacy_null_account_fields_remain_valid_after_the_worker_fills_the_current_account(): void
    {
        [$account,$person,$lead,$proof]=$this->scenario(false);
        $this->postJson(route('crm.convertir',$lead),$proof)->assertOk();
        $remote=$this->remote('people/client')+['userDefined'=>[['key'=>'IFE_PERSONA','value'=>$account->installation_id.':'.$person->id]]];
        $this->fakeHttp(['*people/client*'=>Http::response($remote)]);
        foreach ([1,2] as $repeat) {
            $this->service()->markPending($person,null,false);
            (new SyncGoogleContact($person->id))->handle($this->service());
            $this->assertSame('synced',$person->fresh()->gcontact_sync_status);
        }
        $this->assertSame($account->id,$person->fresh()->gcontact_account_id); Http::assertSentCount(4);
    }

    public function test_confirming_separate_contacts_does_not_allow_different_google_accounts(): void
    {
        [$account,$person,$lead,$proof]=$this->scenario();
        $other=$this->account(['slot'=>'other','google_id'=>'other-account','installation_id'=>'22222222-2222-4222-8222-222222222222']);
        DB::table('prospectos')->where('id',$lead->id)->update(['gcontact_account_id'=>$other->id]);
        $this->postJson(route('crm.convertir',$lead),$proof)->assertUnprocessable()->assertJsonValidationErrors('conversion');
        $this->assertSame('activo',$lead->fresh()->crm_status); $this->assertNull($lead->fresh()->persona_id);
        $this->assertSame(0,CrmActividadDetalle::count()); Http::assertNothingSent(); Queue::assertNothingPushed();
    }

    public function test_a_confirmed_pair_does_not_bypass_another_unresolved_linked_prospect(): void
    {
        [$account,$person,$lead,$proof]=$this->scenario();
        $this->postJson(route('crm.convertir',$lead),$proof)->assertOk();
        $other=$this->lead(['persona_id'=>$person->id,'resourseName'=>'people/unresolved','gcontact_account_id'=>$account->id]);
        (new SyncGoogleContact($person->id))->handle($this->service());
        $this->assertSame('contact_link_conflict',$person->fresh()->gcontact_error_code);
        $this->assertSame('people/unresolved',$other->fresh()->resourseName); Http::assertNothingSent();
    }
}

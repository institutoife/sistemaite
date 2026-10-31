<?php
namespace App\Services;

use App\Exceptions\GoogleContactsException;
use App\Models\{Prospecto,Persona,GoogleContactAccount};
use Illuminate\Support\Facades\{DB,Schema};

class CrmGoogleContactPolicy
{
    public function differentLinks(Prospecto $lead, Persona $person): bool
    {
        return $lead->resourseName&&$person->resourseName&&$lead->resourseName!==$person->resourseName;
    }

    public function snapshot(Prospecto $lead, Persona $person, ?GoogleContactAccount $account): array
    {
        return ['politica'=>'conservar_separados','prospecto_id'=>(int)$lead->id,'persona_id'=>(int)$person->id,
            'prospecto_resource'=>$lead->resourseName,'persona_resource'=>$person->resourseName,
            'prospecto_account_id'=>(int)($lead->gcontact_account_id?:($account?$account->id:0))?:null,
            'persona_account_id'=>(int)($person->gcontact_account_id?:($account?$account->id:0))?:null,
            'central_account_id'=>$account?(int)$account->id:null,'installation_id'=>$account?$account->installation_id:null];
    }

    public function retainsSeparateContact(Prospecto $lead, Persona $person, GoogleContactAccount $account): bool
    {
        if (!$lead->convertido_at||!Schema::hasTable('crm_actividad_detalles')) { return false; }
        $event=DB::table('crm_actividad_detalles as d')->join('observacions as o','o.id','=','d.observacion_id')
            ->where('o.observable_type',Prospecto::class)->where('o.observable_id',$lead->id)->where('d.tipo','conversion')
            ->orderByDesc('d.observacion_id')->value('d.datos');
        $data=json_decode($event??'',true);
        $decision=$data['google_contactos']??null;
        if (!is_array($decision)||($decision['politica']??null)!=='conservar_separados') { return false; }
        $expected=$this->snapshot($lead,$person,$account);
        // MySQL JSON may reorder object keys; compare values strictly without relying on key order.
        ksort($decision); ksort($expected);
        // The exception applies only to the exact pair explicitly confirmed in the conversion audit.
        // If either link is changed or lost, never adopt the old prospect's contact as a replacement.
        if ((int)$lead->persona_id!==$person->id||(!$lead->inscripcione_id&&!$lead->matriculacion_id)
            ||$decision!==$expected) {
            throw new GoogleContactsException('contact_link_conflict','Los enlaces Google cambiaron después de confirmar la conversión. Revise los contactos conservados antes de sincronizar.');
        }
        return true;
    }
}

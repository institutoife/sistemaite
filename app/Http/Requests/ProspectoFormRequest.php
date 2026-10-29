<?php
namespace App\Http\Requests;
use App\Models\Prospecto;
use App\Services\{CrmService,TelefonoService};
use Illuminate\Foundation\Http\FormRequest;
class ProspectoFormRequest extends FormRequest
{
    public function authorize()
    {
        $record=$this->route('prospecto');
        return $this->user()&&($record?$this->user()->can('update',$record):$this->user()->can('create',Prospecto::class));
    }
    public function rules()
    {
        $record=$this->route('prospecto');
        return ['nombre'=>'nullable|string|max:150','telefono'=>[($record&&$record->persona_id)?'nullable':'required','string','max:20'],
            'estado_id'=>'sometimes|nullable|integer|exists:estados,id','interest_id'=>'nullable|integer|exists:interests,id',
            'como_id'=>'nullable|integer|exists:comos,id','grado_id'=>'nullable|integer|exists:grados,id',
            'crm_sede_id'=>'nullable|integer|exists:crm_sedes,id','responsable_id'=>'nullable|integer|exists:users,id',
            'estudiante_nombre'=>'nullable|string|max:150','observacion'=>'nullable|string|max:400',
            'proximo_contacto_at'=>'nullable|date','version'=>$record?'required|integer|min:0':'sometimes|integer|min:0',
            'confirmar_coincidencias'=>'sometimes|accepted'];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) { return; }
            $phone=$this->input('telefono');
            if ($phone&&!app(TelefonoService::class)->normalize($phone)) { $validator->errors()->add('telefono','Ingrese un teléfono válido; para Bolivia use 8 dígitos o +591.'); return; }
            $record=$this->route('prospecto');
            $changed=!$record||app(TelefonoService::class)->normalize($phone)!==app(TelefonoService::class)->normalize($record->telefono);
            if ($phone&&$changed&&app(CrmService::class)->duplicates($phone,$record?$record->id:null)&&!$this->boolean('confirmar_coincidencias')) {
                $validator->errors()->add('confirmar_coincidencias','Hay registros con este teléfono. Revise las coincidencias y confirme que desea conservar una oportunidad independiente.');
            }
            if ($this->filled('responsable_id')&&!\App\Models\User::role(['Admin','Secretaria'])->whereKey($this->responsable_id)->exists()) {
                $validator->errors()->add('responsable_id','Seleccione un usuario del equipo comercial.');
            }
            if ($this->filled('crm_sede_id')&&(!$record||(int)$record->crm_sede_id!==(int)$this->crm_sede_id)
                &&!\App\Models\CrmSede::whereKey($this->crm_sede_id)->where('activa',true)->exists()) { $validator->errors()->add('crm_sede_id','Seleccione una sede activa.'); }
        });
    }
}

<?php
namespace App\Http\Requests;
use App\Services\CrmService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class CrmActionRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()&&$this->user()->can($this->routeIs('crm.convertir')?'convert':'update',$this->route('prospecto'))
            &&(!$this->routeIs('crm.reasignar')||$this->user()->can('Reasignar Prospectos'));
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->routeIs('crm.reasignar')&&$this->filled('responsable_id')&&!app(CrmService::class)->responsibleUsers()->whereKey($this->responsable_id)->exists()) { $validator->errors()->add('responsable_id','Seleccione un usuario autorizado para el CRM.'); }
        });
    }
    public function rules()
    {
        $rules=['version'=>'required|integer|min:0'];
        if ($this->routeIs('crm.reasignar')) { return $rules+['responsable_id'=>'required|integer|exists:users,id','observacion'=>'required|string|max:400']; }
        if ($this->routeIs('crm.mover')) { return $rules+['etapa_id'=>'required|integer|exists:crm_etapas,id']; }
        if ($this->routeIs('crm.baja')) { return $rules+['motivo'=>['required',Rule::in(array_keys(CrmService::REASONS))],'observacion'=>'required_if:motivo,otro|nullable|string|max:400']; }
        if ($this->routeIs('crm.reactivar')) { return $rules+['autoriza_contacto'=>'sometimes|accepted']; }
        if ($this->routeIs('crm.convertir')) {
            return $rules+['persona_id'=>'required|integer|exists:personas,id','estudiante_persona_id'=>'required|integer|exists:personas,id',
                'parentesco'=>'nullable|string|max:40','tipo'=>'required|in:inscripcion,matriculacion','inscripcion_id'=>'required|integer|min:1',
                'confirmar_vinculos'=>'accepted','etapa_id'=>'nullable|integer|exists:crm_etapas,id'];
        }
        return $rules+['tipo'=>['required',Rule::in(array_keys(CrmService::TYPES))],
            'resultado'=>'required|in:respondio,sin_respuesta,interesado,pendiente,enviado_confirmado,otro',
            'observacion'=>'nullable|string|max:400','proxima_accion'=>'nullable|string|max:200',
            'proximo_contacto_at'=>'required_if:tipo,programado|nullable|date|after:now','cerrar_seguimiento'=>'sometimes|boolean',
            'confirmado'=>in_array($this->input('tipo'),['whatsapp','informacion','cotizacion'],true)?'accepted':'sometimes|accepted'];
    }
}

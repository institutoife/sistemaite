<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class CrmFilterRequest extends FormRequest
{
    public function attributes() { return ['crm_sede_id'=>'sucursal','interest_id'=>'servicio']; }
    public function authorize()
    {
        if (!$this->user()||!$this->user()->can('Listar Prospectos')) { return false; }
        if ($this->filled('asignacion')&&$this->asignacion!=='asignados'&&!$this->user()->can('Configurar CRM')) { return false; }
        if ($this->routeIs('crm.dashboard')&&$this->filled('usuario_id')&&(int)$this->usuario_id!==$this->user()->id&&!$this->user()->can('Ver Estadisticas CRM')) { return false; }
        return true;
    }
    public function rules()
    {
        return ['q'=>'nullable|string|max:100','status'=>'sometimes|in:activo,baja,convertido','agenda'=>'nullable|in:hoy,vencidos,sin_seguimiento,sin_respuesta,nuevos',
            'asignacion'=>'nullable|in:asignados,sin_responsable,todos','usuario_id'=>'nullable|integer|exists:users,id',
            'crm_sede_id'=>'nullable|integer|exists:crm_sedes,id','responsable_id'=>'nullable|integer|exists:users,id',
            'interest_id'=>'nullable|integer|exists:interests,id','como_id'=>'nullable|integer|exists:comos,id','crm_etapa_id'=>'nullable|integer|exists:crm_etapas,id',
            'origen_tipo'=>'nullable|in:telefonico,potencial','desde'=>'nullable|date_format:Y-m-d',
            'hasta'=>'nullable|date_format:Y-m-d'.($this->filled('desde')?'|after_or_equal:desde':'')];
    }
}

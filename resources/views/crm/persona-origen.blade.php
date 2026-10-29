
@if(isset($crmProspecto)&&$crmProspecto)
<div class="alert alert-info">Está registrando al contacto de la oportunidad #{{ $crmProspecto->id }}. Revise y separe su nombre y apellidos. Si es apoderado, registre al estudiante como otra persona. El contacto Google se conservará.</div>
<input type="hidden" name="crm_prospecto_id" value="{{ $crmProspecto->id }}"><input type="hidden" name="crm_prospecto_version" value="{{ $crmProspecto->crm_version }}">
@endif

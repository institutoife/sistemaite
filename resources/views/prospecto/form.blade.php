<div class="row">
    <div class="col-md-6 form-group"><label for="prospect-name">Nombre del contacto</label><input id="prospect-name" class="form-control" name="nombre" maxlength="150" value="{{ old('nombre',$prospecto->nombre) }}"
@if($prospecto->persona_id) readonly
@endif></div>
    <div class="col-md-6 form-group"><label for="prospect-phone">Teléfono {{ $prospecto->persona_id?'':'*' }}</label><input id="prospect-phone" class="form-control" name="telefono" type="tel" maxlength="20" value="{{ old('telefono',$prospecto->telefono) }}"
@if($prospecto->persona_id) readonly
@else required
@endif placeholder="8 dígitos o +591"></div>
    <div class="col-12"><div class="alert alert-warning d-none" data-matches-box><p class="mb-1">Encontramos registros con ese teléfono. Revise sus fichas: un número puede pertenecer a una familia.</p><ul data-matches-list></ul></div>
    <label class="d-block mb-3"><input type="checkbox" name="confirmar_coincidencias" value="1"
@if(old('confirmar_coincidencias')) checked
@endif> Revisé las coincidencias y deseo conservar esta oportunidad comercial.</label></div>

<fieldset class="col-12 form-group"><legend class="h6 font-weight-bold">Servicios de interés</legend>
<input type="hidden" name="interests_present" value="1">
@foreach($interests as $service)
<label class="mr-4"><input type="checkbox" name="interests[]" value="{{ $service->id }}"
@if(in_array($service->id,(array)old('interests',$prospecto->exists?$prospecto->interests->modelKeys():[]))) checked
@endif> {{ $service->interest }}</label>
@endforeach
</fieldset>
<div class="col-12 form-group"><strong>Responsable comercial:</strong> {{ $prospecto->exists?(optional($prospecto->responsable)->name?:'Sin asignar'):auth()->user()->name }}
@if(!$prospecto->exists)<span class="text-muted">Se asigna automáticamente a su usuario.</span>
@endif</div>
@foreach(['grado_id'=>['Curso o nivel',$grados,'grado'],'crm_sede_id'=>['Sucursal de interés',$sedes,'nombre'],'como_id'=>['Origen',$comos,'como']] as $field=>$catalog)
    <div class="col-md-6 form-group"><label for="prospect-{{ $field }}">{{ $catalog[0] }}</label>
@if($field==='grado_id')<input type="search" class="form-control mb-1" data-filter-select="prospect-grado_id" placeholder="Buscar curso o nivel" aria-label="Buscar curso o nivel existente">
@endif
<select class="form-control" id="prospect-{{ $field }}" name="{{ $field }}"><option value="">Por definir</option>
@foreach($catalog[1] as $option)
@if(!isset($option->activa)||$option->activa||(string)$prospecto->$field===(string)$option->id)<option value="{{ $option->id }}"
@if((string)old($field,$prospecto->$field)===(string)$option->id) selected
@endif>{{ $option->{$catalog[2]} }}</option>
@endif
@endforeach</select></div>

@endforeach
    <div class="col-md-6 form-group"><label for="prospect-student">Nombre del estudiante, si corresponde</label><input class="form-control" id="prospect-student" name="estudiante_nombre" maxlength="150" value="{{ old('estudiante_nombre',$prospecto->estudiante_nombre) }}"></div>
    <div class="col-md-6 form-group"><label for="prospect-next">Próximo contacto</label><input class="form-control" id="prospect-next" type="datetime-local" name="proximo_contacto_at" value="{{ old('proximo_contacto_at',$prospecto->proximo_contacto_at?$prospecto->proximo_contacto_at->format('Y-m-d\TH:i'):'') }}"></div>
    <div class="col-12 form-group"><label for="prospect-note">Observaciones</label><textarea class="form-control" id="prospect-note" name="observacion" maxlength="400" rows="3">{{ old('observacion',$prospecto->observacion) }}</textarea></div>
</div>

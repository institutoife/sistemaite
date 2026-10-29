<form method="GET" class="crm-filters card card-body" aria-label="Filtrar oportunidades">
<div class="row">
    <div class="col-md-3"><label for="crm-q">Buscar</label><input class="form-control" id="crm-q" name="q" value="{{ request('q') }}" placeholder="Nombre, estudiante o teléfono"></div>

@foreach(['crm_sede_id'=>['Sede',$sedes,'nombre'],'responsable_id'=>['Responsable',$responsables,'name'],'interest_id'=>['Servicio',$interests,'interest'],'como_id'=>['Origen',$comos,'como']] as $field=>$catalog)
    <div class="col-md-2"><label for="filter-{{ $field }}">{{ $catalog[0] }}</label><select class="form-control" id="filter-{{ $field }}" name="{{ $field }}"><option value="">Todos</option>
@foreach($catalog[1] as $option)<option value="{{ $option->id }}"
@if((string)request($field)===(string)$option->id) selected
@endif>{{ $option->{$catalog[2]} }}</option>
@endforeach</select></div>

@endforeach
@if(!request()->routeIs('crm.dashboard'))
    <div class="col-md-2"><label for="crm-status">Estado comercial</label><select class="form-control" id="crm-status" name="status">
@foreach(['activo'=>'Activos','baja'=>'Bajas','convertido'=>'Convertidos'] as $value=>$label)<option value="{{ $value }}"
@if(request('status','activo')===$value) selected
@endif>{{ $label }}</option>
@endforeach</select></div>
    <div class="col-md-3"><label for="crm-agenda">Seguimiento</label><select class="form-control" id="crm-agenda" name="agenda"><option value="">Todos</option>
@foreach(['hoy'=>'Para hoy','vencidos'=>'Vencidos','sin_seguimiento'=>'Sin próxima actividad','sin_respuesta'=>'Sin respuesta','nuevos'=>'Nuevos sin contactar'] as $value=>$label)<option value="{{ $value }}"
@if(request('agenda')===$value) selected
@endif>{{ $label }}</option>
@endforeach</select></div>
@endif
    <div class="col-md-2"><label for="crm-from">Desde</label><input class="form-control" id="crm-from" type="date" name="desde" value="{{ request('desde') }}"></div>
    <div class="col-md-2"><label for="crm-to">Hasta</label><input class="form-control" id="crm-to" type="date" name="hasta" value="{{ request('hasta') }}"></div>
    <div class="col-md-2"><label for="crm-source">Registro</label><select id="crm-source" name="origen_tipo" class="form-control"><option value="">Ambos circuitos</option><option value="telefonico"
@if(request('origen_tipo')==='telefonico') selected
@endif>Prospectos</option><option value="potencial"
@if(request('origen_tipo')==='potencial') selected
@endif>Potenciales</option></select></div>
    <div class="col-md-2 align-self-end"><button class="btn btn-primary">Aplicar filtros</button></div>
@if(!request()->routeIs('crm.dashboard'))
    <div class="col-md-3"><label for="crm-filter-stage">Etapa</label><select id="crm-filter-stage" class="form-control" name="crm_etapa_id"><option value="">Todas</option>
@foreach($etapas as $stage)
<option value="{{ $stage->id }}" @if((int)request('crm_etapa_id')===$stage->id) selected @endif>{{ $stage->nombre }}</option>
@endforeach
</select></div>
@endif
</div></form>

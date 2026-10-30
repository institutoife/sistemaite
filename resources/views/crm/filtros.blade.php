<form method="GET" class="crm-filters card card-body" aria-label="Filtrar oportunidades">
<div class="row">
@if(!request()->routeIs('crm.dashboard'))
    <div class="col-md-3"><label for="crm-q">Buscar</label><input class="form-control" id="crm-q" name="q" value="{{ request('q') }}" placeholder="Nombre, estudiante o teléfono"></div>
@endif

@foreach(['crm_sede_id'=>['Sucursal',$sedes,'nombre'],'responsable_id'=>['Responsable',$responsables,'name'],'interest_id'=>['Servicio',$interests,'interest'],'como_id'=>['Origen',$comos,'como']] as $field=>$catalog)
@if(!request()->routeIs('crm.dashboard')||!in_array($field,['responsable_id','como_id'],true))
    <div class="col-md-2"><label for="filter-{{ $field }}">{{ $catalog[0] }}</label><select class="form-control" id="filter-{{ $field }}" name="{{ $field }}"><option value="">Todos</option>
@foreach($catalog[1] as $option)<option value="{{ $option->id }}"
@if((string)request($field)===(string)$option->id) selected
@endif>{{ $option->{$catalog[2]} }}</option>
@endforeach</select></div>
@endif
@endforeach
@if(request()->routeIs('crm.dashboard'))
@can('Ver Estadisticas CRM')
<div class="col-md-3"><label for="crm-user">Usuario de las estadísticas</label><select id="crm-user" name="usuario_id" class="form-control"><option value="">Todos los usuarios</option>
@foreach($usuariosEstadisticas as $user)<option value="{{ $user->id }}"
@if((int)request('usuario_id')===$user->id) selected
@endif>{{ $user->name }}</option>
@endforeach</select></div>
@endcan
@endif
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
@if(!request()->routeIs('crm.dashboard'))
    <div class="col-md-2"><label for="crm-source">Registro</label><select id="crm-source" name="origen_tipo" class="form-control"><option value="">Ambos circuitos</option><option value="telefonico"
@if(request('origen_tipo')==='telefonico') selected
@endif>Prospectos</option><option value="potencial"
@if(request('origen_tipo')==='potencial') selected
@endif>Potenciales</option></select></div>
@endif
    <div class="col-md-3 align-self-end"><button class="btn btn-primary">Aplicar filtros</button> <a class="btn btn-outline-secondary" href="{{ request()->url() }}">Limpiar filtros</a></div>
@if(!request()->routeIs('crm.dashboard'))
@can('Configurar CRM')
<div class="col-md-3"><label for="crm-assignment">Asignación administrativa</label><select id="crm-assignment" class="form-control" name="asignacion">
@foreach(['asignados'=>'Con responsable (operativo)','sin_responsable'=>'Históricos sin responsable','todos'=>'Todos, incluidos históricos'] as $value=>$label)
<option value="{{ $value }}"
@if(request('asignacion','asignados')===$value) selected
@endif>{{ $label }}</option>
@endforeach</select></div>
@endcan
    <div class="col-md-3"><label for="crm-filter-stage">Etapa</label><select id="crm-filter-stage" class="form-control" name="crm_etapa_id"><option value="">Todas</option>
@foreach($etapas as $stage)
<option value="{{ $stage->id }}" @if((int)request('crm_etapa_id')===$stage->id) selected @endif>{{ $stage->nombre }}</option>
@endforeach
</select></div>
@endif
</div></form>

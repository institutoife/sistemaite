<article class="crm-card"
@can('update',$record) draggable="{{ $record->crm_status==='activo'?'true':'false' }}"
@endcan
    data-card="{{ $record->id }}" data-version="{{ $record->crm_version }}" data-move="{{ route('crm.mover',$record) }}">
    <a class="crm-card-title" href="{{ route('prospecto.view',$record) }}">{{ $record->identificacion }}</a>
    <p class="mb-1">{{ $record->telefono ?: 'Sin teléfono' }} <span class="crm-code">#{{ $record->id }}</span></p>
    <p class="mb-1">{{ optional($record->interest)->interest ?: 'Servicio por definir' }}</p>
    <p class="small text-muted mb-1">{{ optional($record->responsable)->name ?: 'Sin responsable' }} · {{ optional($record->sede)->nombre ?: 'Sin sede' }}</p>
    <p class="small text-muted mb-1" data-stage-time>En etapa: {{ $record->etapa_desde?$record->etapa_desde->diffForHumans():'Sin fecha anterior' }}</p>

@if($record->proximo_contacto_at)<p class="crm-agenda {{ $record->proximo_contacto_at->isPast()&&$record->crm_status==='activo'?'crm-overdue':'' }}">Próxima actividad: {{ $record->proximo_contacto_at->format('d/m/Y H:i') }}</p>
@else<p class="small">Sin próxima actividad</p>
@endif

@if($record->no_contactar)<p class="badge badge-warning">No contactar</p>
@endif

@can('update',$record)
@if($record->crm_status==='activo')
    <label class="sr-only" for="card-stage-{{ $record->id }}">Cambiar etapa de {{ $record->identificacion }}</label>
    <select id="card-stage-{{ $record->id }}" class="form-control form-control-sm" data-change-stage><option value="">Cambiar etapa…</option>
@foreach($stages as $target)
@if($target->activa&&!$target->conversion)<option value="{{ $target->id }}">{{ $target->nombre }}</option>
@endif
@endforeach</select>

@endif
@endcan
</article>

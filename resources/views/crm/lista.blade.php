
@extends('crm.layout')
@section('crm_content')
@include('crm.filtros')
<div class="card"><div class="table-responsive"><table class="table"><thead><tr><th>Oportunidad</th><th>Servicio y sede</th><th>Etapa</th><th>Responsable</th><th>Próxima actividad</th><th>Estado</th></tr></thead><tbody>
@forelse($records as $record)<tr><td><a href="{{ route('prospecto.view',$record) }}">{{ $record->identificacion }}</a><br>{{ $record->telefono ?: 'Sin teléfono' }}</td><td>{{ optional($record->interest)->interest }}<br>{{ optional($record->sede)->nombre }}</td><td>{{ optional($record->etapa)->nombre }}</td><td>{{ optional($record->responsable)->name }}</td><td>{{ $record->proximo_contacto_at?$record->proximo_contacto_at->format('d/m/Y H:i'):'Sin programar' }}</td><td>{{ ucfirst($record->crm_status) }}
@if($record->no_contactar)<span class="badge badge-warning">No contactar</span>
@endif</td></tr>
@empty<tr><td colspan="6">No hay oportunidades con estos filtros.</td></tr>
@endforelse
</tbody></table></div><div class="card-footer">{{ $records->links() }}</div></div>
@stop


@extends('crm.layout')
@section('crm_content')
@include('crm.filtros')
@include('crm.estadisticas-usuarios')
@if($stats)
<h2 class="h4 mt-4">Resumen general de la cartera operativa</h2>
<p>Período: <strong>{{ $stats['start']->format('d/m/Y') }} al {{ $stats['end']->format('d/m/Y') }}</strong>. Los activos y la agenda reflejan el estado actual de la cartera filtrada.</p>
<div class="crm-summary">
@foreach(['created'=>'Registrados en el período','converted'=>'Conversiones en el período','active'=>'Activos actuales','inactive'=>'Bajas actuales','deactivations'=>'Bajas registradas en el período','scheduled'=>'Seguimientos pendientes','overdue'=>'Seguimientos vencidos'] as $key=>$label)<div><strong>{{ $stats[$key] }}</strong><span>{{ $label }}</span></div>
@endforeach</div>
<div class="row"><div class="col-md-6"><div class="card card-body"><h2 class="h5">Conversión de la cohorte registrada</h2><p class="h3">{{ $stats['rate']===null?'Sin registros':$stats['rate'].' %' }}</p><p>Porcentaje de las oportunidades registradas en el período que se convirtieron hasta su fecha final. Las conversiones de oportunidades anteriores se cuentan por separado.</p></div></div><div class="col-md-6"><div class="card card-body"><h2 class="h5">Tiempo promedio de conversión</h2><p class="h3">{{ $stats['average_days']===null?'Sin conversiones':$stats['average_days'].' días' }}</p><p>Desde el registro en CRM hasta la conversión, para las conversiones ocurridas en el período.</p></div></div></div>
<div class="row">
@foreach(['crm_etapa_id'=>'Registros por etapa actual','como_id'=>'Registros por origen','interest_id'=>'Servicios consultados'] as $field=>$label)<div class="col-md-4"><div class="card card-body"><h2 class="h5">{{ $label }}</h2><table class="table table-sm"><tbody>
@forelse($stats['groups'][$field] as $item)<tr><td>{{ $item['nombre'] }}</td><td>{{ $item['total'] }}</td></tr>
@empty<tr><td>Sin datos</td></tr>
@endforelse</tbody></table></div></div>
@endforeach</div>
<div class="row"><div class="col-md-6"><div class="card card-body"><h2 class="h5">Orígenes de conversiones del período</h2><table class="table table-sm">
@forelse($stats['originsConverted'] as $item)<tr><td>{{ $item['nombre'] }}</td><td>{{ $item['total'] }}</td></tr>
@empty<tr><td>Sin conversiones</td></tr>
@endforelse</table></div></div><div class="col-md-6"><div class="card card-body"><h2 class="h5">Motivos de baja del período</h2><table class="table table-sm">
@forelse($stats['reasons'] as $item)<tr><td>{{ \App\Services\CrmService::REASONS[$item->motivo_baja]??'Sin motivo histórico' }}</td><td>{{ $item->total }}</td></tr>
@empty<tr><td>Sin bajas</td></tr>
@endforelse</table></div></div></div>
@endif
@stop


@extends('crm.layout')
@section('crm_content')
<div class="crm-summary">

@foreach(['today'=>['Para hoy','hoy'],'overdue'=>['Vencidos','vencidos'],'unscheduled'=>['Sin próxima actividad','sin_seguimiento'],'unanswered'=>['Sin respuesta','sin_respuesta'],'uncontacted'=>['Sin contactar','nuevos']] as $key=>$item)
    <a href="{{ route('crm.lista',array_merge(request()->except('page'),['agenda'=>$item[1],'status'=>'activo'])) }}"><strong>{{ $stats[$key] }}</strong><span>{{ $item[0] }}</span></a>

@endforeach
</div>
@include('crm.filtros')
<p class="text-muted">Arrastre una tarjeta o utilice «Cambiar etapa». Se muestran hasta 50 fichas por columna; «Ver todas» abre la lista completa. Las bajas conservan su historial.</p>
<div class="crm-board-controls"><span>Desplace el tablero horizontalmente para ver todas las etapas.</span><div><button type="button" class="btn btn-outline-primary btn-sm" data-board-scroll="-1" aria-label="Ver etapas anteriores">←</button> <button type="button" class="btn btn-outline-primary btn-sm" data-board-scroll="1" aria-label="Ver etapas siguientes">→</button></div></div>
<div class="crm-board" data-board data-token="{{ csrf_token() }}" tabindex="0" aria-label="Etapas comerciales con desplazamiento horizontal">
@foreach($stages as $stage)
<section class="crm-column" style="--stage-color:{{ $stage->color }}" data-stage="{{ $stage->id }}" data-stage-active="{{ $stage->activa&&!$stage->conversion?'1':'0' }}" aria-label="{{ $stage->nombre }}">
    <header><h2>{{ $stage->nombre }}
@if(!$stage->activa)<small>Inactiva</small>
@endif</h2><span data-count>{{ $counts[$stage->id]??0 }}</span></header>
    <div class="crm-cards">
@foreach($cards->get($stage->id,collect()) as $record)
@include('crm.tarjeta')
@endforeach</div>
    <a class="crm-all" href="{{ route('crm.lista',array_merge(request()->except('page'),['crm_etapa_id'=>$stage->id])) }}">Ver todas</a>
</section>
@endforeach
</div>
@stop

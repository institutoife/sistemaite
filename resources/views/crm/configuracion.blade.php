
@extends('crm.layout')
@section('crm_content')
<h2 class="h4">Etapas comerciales</h2><p>El orden define las columnas. Cambie la etapa inicial desde otra fila antes de desactivar la actual. Las etapas inactivas conservan sus fichas e historial.</p>
@foreach($etapas as $stage)
<form class="card card-body" method="POST" action="{{ route('crm.configurar.etapa',$stage) }}">
@csrf<input type="hidden" name="version" value="{{ $stage->version }}"><div class="row">
<div class="col-md-4"><label for="stage-name-{{ $stage->id }}">Nombre</label><input id="stage-name-{{ $stage->id }}" class="form-control" name="nombre" value="{{ $stage->nombre }}" maxlength="80" required></div>
<div class="col-md-2"><label for="stage-color-{{ $stage->id }}">Color</label><input id="stage-color-{{ $stage->id }}" class="form-control" type="color" name="color" value="{{ $stage->color }}" required></div>
<div class="col-md-2"><label for="stage-order-{{ $stage->id }}">Orden</label><input id="stage-order-{{ $stage->id }}" class="form-control" type="number" name="orden" value="{{ $stage->orden }}" min="0" max="10000" required></div>
<div class="col-md-4 align-self-end">
@foreach(['activa'=>'Activa','inicial'=>'Inicial','conversion'=>'Conversión'] as $field=>$label)<input type="hidden" name="{{ $field }}" value="0"><label class="mr-2"><input type="checkbox" name="{{ $field }}" value="1"
@if($stage->$field) checked
@endif> {{ $label }}</label>
@endforeach <button class="btn btn-primary">Guardar</button></div>
</div></form>
@endforeach
<details class="card card-body"><summary>Crear etapa</summary><form method="POST" action="{{ route('crm.configurar.etapa') }}" class="mt-3">
@csrf<div class="row"><div class="col-md-5"><label for="new-stage-name">Nombre</label><input id="new-stage-name" class="form-control" name="nombre" maxlength="80" required></div><div class="col-md-2"><label for="new-stage-color">Color</label><input id="new-stage-color" type="color" class="form-control" name="color" value="#375F7A"></div><div class="col-md-2"><label for="new-stage-order">Orden</label><input id="new-stage-order" type="number" class="form-control" name="orden" value="{{ $etapas->max('orden')+1 }}" min="0" max="10000"></div><div class="col-md-3 align-self-end"><input type="hidden" name="activa" value="1"><input type="hidden" name="inicial" value="0"><input type="hidden" name="conversion" value="0"><button class="btn btn-primary">Crear etapa</button></div></div></form></details>
<h2 class="h4 mt-4">Sedes</h2>
@foreach($sedes as $sede)<form method="POST" action="{{ route('crm.configurar.sede',$sede) }}" class="card card-body">
@csrf<div class="row"><div class="col-md-7"><label for="sede-{{ $sede->id }}">Nombre</label><input id="sede-{{ $sede->id }}" class="form-control" name="nombre" value="{{ $sede->nombre }}" maxlength="100" required></div><div class="col-md-5 align-self-end"><input type="hidden" name="activa" value="0"><label class="mr-3"><input type="checkbox" name="activa" value="1"
@if($sede->activa) checked
@endif> Activa</label><button class="btn btn-primary">Guardar</button></div></div></form>
@endforeach
<form method="POST" action="{{ route('crm.configurar.sede') }}" class="card card-body">
@csrf<label for="new-sede">Nueva sede</label><div class="input-group"><input id="new-sede" name="nombre" class="form-control" maxlength="100" required><input type="hidden" name="activa" value="1"><div class="input-group-append"><button class="btn btn-primary">Crear sede</button></div></div></form>
<p>Servicios y orígenes se administran en los catálogos existentes de Intereses y Cómo nos conoció.</p>
@stop


@extends('crm.layout')
@section('crm_content')
<section class="card card-body"><h2 class="h4">Editar {{ $prospecto->identificacion }}</h2>
@if($prospecto->persona_id)<p>El nombre y teléfono se mantienen en la <a href="{{ route('personas.edit',$prospecto->persona_id) }}">ficha de Persona #{{ $prospecto->persona_id }}</a>.</p>
@endif
<form method="POST" action="{{ route('prospecto.update',$prospecto) }}" data-prospect-form data-matches="{{ route('crm.coincidencias') }}" data-record="{{ $prospecto->id }}">
@csrf
@method('PATCH')<input type="hidden" name="version" value="{{ old('version',$prospecto->crm_version) }}">
@include('prospecto.form')<button class="btn btn-primary">Guardar cambios</button></form></section>
@stop

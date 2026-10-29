
@extends('crm.layout')
@section('crm_content')
<section class="card card-body"><h2 class="h4">Nuevo prospecto</h2><p>Registre el teléfono y complete los demás datos cuando los conozca.</p>
<form method="POST" action="{{ route('prospecto.store') }}" data-prospect-form data-matches="{{ route('crm.coincidencias') }}">
@csrf
@include('prospecto.form')<button class="btn btn-primary">Guardar prospecto</button></form></section>
@stop

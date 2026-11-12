@extends('adminlte::page')
@section('title','Historial de asistencia')
@section('css')
<link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@stop
@section('content_header')<h1>Historial de asistencia</h1>@stop
@section('content')
<div class="card"><div class="card-body">@include('clase.asistencia.panel',['reportOnly'=>true,'historyOpen'=>true])</div></div>
@stop
@section('js')<script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script><script src="{{ asset('vistas/clase/asistencia.js') }}?v={{ is_file(public_path('vistas/clase/asistencia.js')) ? filemtime(public_path('vistas/clase/asistencia.js')) : 1 }}"></script>@stop

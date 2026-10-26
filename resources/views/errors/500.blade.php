@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
@stop

@section('title', 'Error 500')
@section('content')
    
<div class="card">
    <div class="card-header">
        <h1>Error interno del servidor (500)</h1>
    </div>
    <div class="card-body">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{route('home')}}">Volver al inicio</a></li>
            <li class="breadcrumb-item active">Error 500 - Internal server error, es el código de estado HTTP más común, este significa que ha sucedido un error al intentar acceder al servidor, pero no se puede dar mas detalles sobre lo que ha ocurrido.</li>
        </ol>
    </div>
</div>
@stop

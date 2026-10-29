
@extends('adminlte::page')
@section('title','CRM IFE')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/crm-ife.css') }}?v={{ filemtime(public_path('assets/css/crm-ife.css')) }}">
@stop
@section('content')
<div class="ife-crm" data-crm-root>
    <header class="crm-heading"><div><p class="crm-eyebrow">Gestión comercial</p><h1>CRM IFE</h1></div>
        <nav aria-label="Navegación CRM"><a class="btn btn-outline-primary" href="{{ route('crm.tablero') }}">Tablero</a> <a class="btn btn-outline-primary" href="{{ route('crm.lista') }}">Lista</a> <a class="btn btn-outline-primary" href="{{ route('crm.dashboard') }}">Indicadores</a>

@can('Crear Prospectos')<a class="btn btn-primary" href="{{ route('prospecto.create') }}">Nuevo prospecto</a>
@endcan

@can('Configurar CRM')<a class="btn btn-outline-secondary" href="{{ route('crm.configuracion') }}">Configuración</a>
@endcan
        </nav>
    </header>

@foreach(['success'=>'success','error'=>'danger'] as $key=>$color)
@if(session($key))<p class="alert alert-{{ $color }}" role="status">{{ session($key) }}</p>
@endif
@endforeach

@if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">
@foreach($errors->all() as $error)<li>{{ $error }}</li>
@endforeach</ul></div>
@endif
    <p class="alert d-none" data-crm-status role="status" aria-live="polite"></p>
    @yield('crm_content')
</div>
@stop
@section('js')<script src="{{ asset('assets/js/crm-ife.js') }}?v={{ filemtime(public_path('assets/js/crm-ife.js')) }}" defer></script>
@stop

@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
    <link rel="stylesheet" href="{{asset('custom/css/custom.css')}}">
@stop
@section('title', 'Inscripcion Configurar')
@section('content')
    
    @includeif('partials.errors')

    <div class="card card-default">
        <div class="card-header bg-primary">
            <span class="card-title">Actualizar Inscripcion</span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('inscripciones.update', $inscripcione->id) }}"  role="form">
                {{ method_field('PATCH') }}
                @csrf
                @include('inscripcione.form')
                @include('include.botones')
            </form>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('assets/js/ckeditor.js') }}"></script>
    <script src="{{ asset('vistas/inscripcione/form.js') }}"></script>
@endsection

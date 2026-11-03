@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
@stop

@section('title', 'Inscripcion Configurar')
@section('plugins.Jquery', true)

@section('content')

{{-- {{dd($persona)}} --}}
{{ Breadcrumbs::render('inscribir', $persona->estudiante,$persona) }}

    <div class="pt-4">
        <div class="card">
            <div class="card-header bg-secondary">
                <span class="card-title">Formulario Inscripción</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('inscripciones.store') }}"  role="form" enctype="multipart/form-data">
                    @csrf
                    {{-- {{$modalidades}} --}}
                    @include('inscripcione.form')
                    @include('include.botones')
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('assets/js/ckeditor.js') }}"></script>
    <script src="{{ asset('vistas/inscripcione/form.js') }}"></script>
@endsection

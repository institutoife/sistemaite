@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
@stop

@section('title', 'Editar')

@section('content_header')

@stop

@section('content')
    <div class="card">
        <div class="card-header bg-secondary">
            <span class="text-center">FORMULARIO EDITAR CONTACTO TELEFONICO </span> <h3 class="text-white float-right" id="tokenExpirationform"></h3>
        </div>
        <div class="card-body">
            <form action="{{route('telefono.actualizar',['persona_id'=>$registro_pivot[0]->persona_id,'apoderado_id'=>$registro_pivot[0]->persona_id_apoderado])}}" method="POST" enctype="multipart/form-data">
                {{ @method_field('PUT') }}
                @csrf
                @include('telefono.form')
                @include('telefono.datos-propios', ['contacto' => $persona])
                @include('include.botones')
            </form>
        </div>
    </div>

    @if($tiempoToken==0)
        @include('include.modalGContact')
    @endif

@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="{{asset('assets/js/tiempoGcontact.js')}}"></script>
@stop

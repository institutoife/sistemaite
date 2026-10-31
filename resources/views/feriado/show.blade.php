@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
@stop

@section('title', 'Mostrar Feriado')


@section('content_header')
    <h1 class="text-center text-primary">Mostrar Feriado</h1>
@stop


@section('content')
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-secondary">
                        <div class="float-left">
                            <span class="card-title">Show Feriado</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('feriados.index') }}"> Listar Feriados activos </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <table class="table table-striped table-bordered table-hover">
                            <thead class="bg-primary">
                                <tr>
                                    <th>ATRIBUTO</th>
                                    <th>VALOR</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td> <strong>FECHA</strong></td>
                                    <td>{{ $feriado->fecha }}</td>
                                </tr>
                                <tr>
                                    <td> <strong>Festividad:</strong></td>
                                    <td>{{ $feriado->festividad }}</td>
                                </tr>
                                <tr>
                                    <td> <strong>Activo:</strong></td>
                                    @php
                                        $resultado = $feriado->festividad ? "Este feriado esta activo" : "Este feriado ya pasó";
                                    @endphp
                                    <td>{{ $resultado }}</td>
                                </tr>
                                 <tr>
                                    <td>Usuario</td>
                                    <td>
                                        @isset($user)
                                            {{$user->name}}
                                            <img data-profile-photo width="150" src="{{app(\App\Services\ProfilePhotoService::class)->forUser($user)}}" alt="{{$user->name}}" class="rounded img-thumbnail img-fluid border-primary border-5">
                                        @endisset
                                    </td>
                                </tr>
                            </tbody>
                        </table>    
                    </div>
                </div>
            </div>
        </div>
@endsection

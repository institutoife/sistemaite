@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
@stop

@section('title', 'Mostrar Asignatura')


@section('content_header')
    <h1 class="text-center text-primary">Mostrar Asignatura</h1>
@stop

@section('content')
        <div class="card">
            <div class="card-header bg-primary">
                Mostrar Asignatura <a class="btn btn-secondary text-white btn-sm float-right" href="{{route('asignatura.index')}}">Listar Asignaturas</a>
            </div>
            <div class="card-body">
                <table class="table table-light table-striped table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th>ATRIBUTO</th>
                            <th>VALOR</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>ID</td>
                            <td>{{$asignatura->id}}</td>
                        </tr>
                        <tr>
                            <td>ASIGNAURA</td>
                            <td>{{$asignatura->asignatura}}</td>
                        </tr>
                        <tr>
                            <td>CARRERA</td>
                            <td>{{$asignatura->carrera->carrera}}</td>
                        </tr>
                         <tr>
                        <td>Usuario</td>
                        <td>
                            @isset($user)
                                {{$user->name}}
                                <img data-profile-photo width="150" src="{{app(\App\Services\ProfilePhotoService::class)->forUser($user)}}" alt="{{$user->name}}"  class="rounded img-thumbnail img-fluid border-primary border-5">
                            @endisset
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    
@stop

@section('js')

@stop
@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
@stop

@section('title', 'Mostrar Tema')


@section('content_header')
    <div class="card">
        <div class="card-header">
            <h1 class="text-center text-primary">Mostrar Tema</h1>
        </div>
        <div class="card-body">
            <p class="card-text">Lorem ipsum dolor sit amet consectetur adipisicing elit. Autem cum delectus vero atque officiis dolor sint ex, similique natus laboriosam, neque voluptatum ipsum illum optio expedita maxime, quod sit molestias?</p>
        </div>
    </div>
@stop

@section('content')
        <div class="card">
            <div class="card-header bg-primary">
                Mostrar Tema <a class="btn btn-secondary text-white btn-sm float-right" href="{{route('tema.index')}}">Listar Temas</a>
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
                            <td>{{$tema->id}}</td>
                        </tr>
                        <tr>
                            <td>TEMA</td>
                            <td>{{$tema->tema}}</td>
                        </tr>
                        <tr>
                            <td>MATERIA</td>
                            <td>{{$tema->materia->materia}}</td>
                        </tr>

                        <tr>
                            <td>Usuario</td>
                            <td>
                                @isset($user)
                                    {{$user->name}}
                                    <img data-profile-photo  src="{{app(\App\Services\ProfilePhotoService::class)->forUser($user)}}" alt="{{$user->name}}" class="rounded img-thumbnail img-fluid border-primary border-5" width="100">
                                @endisset
                            </td>
                        </tr>
                        
                        <tr>
                            <td>CREADO</td>
                            <td>{{$tema->created_at}}</td>
                        </tr>
                        <tr>
                            <td>ACTUALIZADO</td>
                            <td>{{$tema->updated_at}}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    
@stop

@section('js')

@stop
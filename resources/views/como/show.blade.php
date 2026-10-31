@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
@stop

@section('title', 'Mostrar Cargos')

@section('content')
        <div class="card">
            <div class="card-header bg-primary">
                Mostrar Como <a class="btn btn-secondary text-white btn-sm float-right text-white" href="{{route('como.index')}}">Listar Como se entero</a>
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
                            <td>{{$como->id}}</td>
                        </tr>
                        <tr>
                            <td>Como</td>
                            <td>{{$como->como}}</td>
                        </tr>
                        <tr>
                            <td>Usuario</td>
                            <td>
                                @isset($user)
                                    {{$user->name}}
                                    <img data-profile-photo  src="{{app(\App\Services\ProfilePhotoService::class)->forUser($user)}}" alt="{{$user->name}}" width="100" class="rounded img-thumbnail img-fluid border-primary border-5">
                                @endisset
                            </td>
                        </tr>
                        <tr>
                            <td>CREADO</td>
                            <td>{{$como->created_at}}</td>
                        </tr>
                        <tr>
                            <td>ACTUALIZADO</td>
                            <td>{{$como->updated_at}}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    
@stop

@section('js')

@stop
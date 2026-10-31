@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    
    <div class="d-flex">
        <h1>Pais: {{$pais->nombrepais}}</h1>
        <a href="{{route('paises.index')}}" class="ml-auto">
            <button class="btn btn-primary">
            Listar Paises
        </button>
        </a>
        
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped"> 
                <tr class="bg-primary">
                    
                        <th>ATRIBUTO</th>
                        <th>VALOR</th>
                    
                </tr>
                <tbody>
                    <tr>
                        <td>Id</td>
                        <td>{{$pais->id}}</td>
                    </tr>
                    <tr>
                        <td>Pais</td>
                        <td>{{$pais->nombrepais}}</td>
                    </tr>
                    <tr>
                        <td>Usuario</td>
                        <td>
                            {{$user->name}}
                            <img data-profile-photo  src="{{app(\App\Services\ProfilePhotoService::class)->forUser($user)}}" alt="{{$user->name}}" class="rounded img-thumbnail img-fluid border-primary border-5">
                        </td>
                    </tr>
                    <tr>
                        <td>Creado</td>
                        <td>{{$pais->created_at}}</td>
                    </tr>
                    <tr>
                        <td>Actualizado</td>
                        <td>{{$pais->updated_at}}</td>
                    </tr>
                </tbody>
            </table>

        </div>
    </div>
    
@stop

@section('css')
    <link rel="stylesheet" href="{{asset('bootstrap/css/bootstrap.css')}}">
@stop

@section('js')
    <script>
        $(document).ready(function() {
            
        } );
    </script>
@stop
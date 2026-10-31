@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('bootstrap/css/bootstrap.css')}}">
@stop
@section('title', 'Provincias Mostrar')

@section('content')
  
                <div class="card">
                    <div class="card-header bg-secondary">
                        <div class="float-left">
                            <span class="card-title">Provincia: {{$provincia->provincia}}</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('provincias.index') }}"> Listar Provincias</a>
                        </div>
                    </div>

                    <div class="card-body">
                        
                        <table class="table table-striped table-inverse table-bordered table-hover">
                            <thead class="thead-inverse|thead-default">
                                <tr>
                                    <th>ATRIBUTO</th>
                                    <th>VALOR</th>
                                </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td scope="row">ID</td>
                                        <td>{{ $provincia->id }}</td>
                                    </tr>
                                    <tr>
                                        <td scope="row">PROVINCIA</td>
                                        <td>{{ $provincia->provincia }}</td>
                                    </tr>
                                    <tr>
                                        <td scope="row">DEPARTAMENTO/ESTADO</td>
                                        <td>{{ $provincia->departamento }}</td>
                                    </tr>
                                    <tr>
                                        <td scope="row">PAIS</td>
                                        <td>{{ $provincia->nombrepais }}</td>
                                    </tr>
                                    <tr>
                                        <td>Usuario</td>
                                        <td>
                                            @isset($user)
                                                {{$user->name}}
                                                <img data-profile-photo  src="{{app(\App\Services\ProfilePhotoService::class)->forUser($user)}}" alt="{{$user->name}}" class="rounded img-thumbnail img-fluid border-primary border-5">
                                            @endisset
                                        </td>
                                    </tr>
                                    <tr>
                                        <td scope="row">CREADO</td>
                                        <td>{{ $provincia->created_at }}</td>
                                    </tr>
                                    <tr>
                                        <td scope="row">ACTUALIZADO</td>
                                        <td>{{ $provincia->updated_at }}</td>
                                    </tr>

                                </tbody>
                        </table>
                    </div>
       
    </section>
@endsection

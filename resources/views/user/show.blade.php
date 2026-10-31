@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('bootstrap/css/bootstrap.css')}}">
@stop
@section('title', 'Dashboard')

@section('content_header')
    
@stop

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-secondary">
                        <div class="float-left">
                            <span class="card-title">Mostrar Usuario</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('users.index') }}">Listar usuarios</a>
                        </div>
                    </div>

                    <div class="card-body">
                        <table class="table table-bordered table-hover table-striped">
                            <thead>
                                <th>ATRIBUTO</th>
                                <th>VALOR</th>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>NOMBRE USUARIO</td>
                                    <td>
                                        {{$user->name}}
                                    </td>
                                </tr>
                                <tr>
                                    <td>EMAIL</td>
                                    <td>{{$user->email}}</td>
                                </tr>
                                <tr>
                                    <td>FOTO</td>
                                    <td><img data-profile-photo src="{{app(\App\Services\ProfilePhotoService::class)->forUser($user)}}" alt="{{$user->name}}" height="250px"></td>
                                </tr>
                            </tbody>
                        </table>    
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

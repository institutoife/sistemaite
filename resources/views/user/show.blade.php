@extends('adminlte::page')
@php($ownProfile = $ownProfile ?? false)
@section('css')
    <link rel="stylesheet" href="{{asset('bootstrap/css/bootstrap.css')}}">
@stop
@section('title', $ownProfile ? 'Mi perfil' : 'Mostrar usuario')

@section('content_header')
    
@stop

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-secondary">
                        <div class="float-left">
                            <span class="card-title">{{ $ownProfile ? 'Mi perfil' : 'Mostrar usuario' }}</span>
                        </div>
                        <div class="float-right">
                            @can('Listar Usuarios')
                                <a class="btn btn-primary" href="{{ route('users.index') }}">Listar usuarios</a>
                            @endcan
                        </div>
                    </div>

                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-hover table-striped ife-profile-details">
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
                                @if($ownProfile && $user->persona)
                                    <tr>
                                        <td>NOMBRE COMPLETO</td>
                                        <td>{{ trim($user->persona->nombre.' '.$user->persona->apellidop.' '.$user->persona->apellidom) }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td>FOTO</td>
                                    <td><img data-profile-photo class="ife-profile-detail-photo" src="{{app(\App\Services\ProfilePhotoService::class)->forUser($user)}}" alt="{{$user->name}}" height="250"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

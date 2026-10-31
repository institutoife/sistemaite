@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('bootstrap/css/bootstrap.css')}}">
@stop

@section('title', 'Nivel Ver')


@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-secondary">
                        <div class="float-left">
                            <span class="card-title">Mostrar Nivel</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('nivels.index') }}"> Listar niveles</a>
                        </div>
                    </div>

                    <div class="card-body">
                        
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>ATRIBUTO</th>
                                    <th>VALOR</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td scope="row">Nivel:</td>
                                    <td>{{ $nivel->nivel }}</td>
                                </tr>
                                <tr>
                                    <td scope="row">Usuario:</td>
                                    <td>
                                        @isset($user)
                                            {{$user->name}}
                                            <img data-profile-photo width="150" src="{{app(\App\Services\ProfilePhotoService::class)->forUser($user)}}" alt="{{$user->name}}" class="rounded img-thumbnail img-fluid border-primary border-5">
                                        @endisset
                                    </td>
                                </tr>
                                <tr>
                                    <td scope="row">Creado:</td>
                                    <td>{{ $nivel->created_at }}</td>
                                </tr>
                                <tr>
                                    <td scope="row">Actualizado:</td>
                                    <td>{{ $nivel->created_at }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

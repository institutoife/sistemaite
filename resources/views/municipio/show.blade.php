@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
@stop

@section('title', 'Mostrar Municipio')

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="float-left">
                            <span class="card-title">Mostrar Municipio</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('municipios.index') }}"> Back</a>
                        </div>
                    </div>

                    <div class="card-body">
                         <table id="cambio" class="table table-bordered table-hover table-striped">
                            <thead class="bg-primary">
                                <tr>
                                    <th>ATRIBUTO</th>
                                    <th>VALOR</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-mostrar">
                                <tr>
                                    <td> <strong>ID</strong></td>
                                    <td>{{ $municipio->id }}</td>
                                </tr>
                                <tr>
                                    <td> <strong>Municipio</strong></td>
                                    <td>{{ $municipio->municipio }}</td>
                                </tr>
                                <tr>
                                    <td> <strong>Provincia</strong></td>
                                    <td>{{ $provincia->provincia }}</td>
                                </tr>
                                <tr>
                                    <td> <strong>Departamento</strong></td>
                                    <td>{{ $departamento->departamento }}</td>
                                </tr>
                                <tr>
                                    <td> <strong>Pais</strong></td>
                                    <td>{{ $pais->nombrepais }}</td>
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
                            </tbody>
                        </table>
                        

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

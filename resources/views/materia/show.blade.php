@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
@stop

@section('title', 'Mostrar Feriado')


@section('content_header')
    <h1 class="text-center text-primary">Mostrar Materia</h1>
@stop

@section('content')
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-secondary">
                        <div class="float-left">
                            <span class="card-title">Mostrar Materia</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('materias.index') }}"> Listar Materias </a>
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
                                    <td> <strong>ID</strong></td>
                                    <td>{{ $materia->id }}</td>
                                </tr>
                                <tr>
                                    <td> <strong>Materia</strong></td>
                                    <td>{{ $materia->materia }}</td>
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
                
                        <strong>NIVELES A LOS QUE PERTENCE ESTA MATERIA</strong>
                        <ul class="list-group">
                            @foreach ($niveles as $nivel)
                                <li class="list-group-item text-gray">{{ $nivel->nivel}}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
@endsection

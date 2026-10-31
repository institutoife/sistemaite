@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
@stop

@section('title', 'Mostrar Feriado')


@section('content_header')
    <h1 class="text-center text-primary">Mostrar Archivo</h1>
@stop


@section('content')
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-secondary">
                        <div class="float-left">
                            <span class="card-title">Mostrar archivo</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('files.index') }}"> Listar Archivos </a>
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
                                    <td> <strong>Tipo archivo</strong></td>
                                    <td>{{ $file->tipofile }}</td>
                                </tr>
                                <tr>
                                    <td> <strong>Nombre Archivo:</strong></td>
                                    <td>{{ $file->file }}  </td>
                                </tr>
                                <tr>
                                    <td> <strong> Ver archivo :</strong></td>
                                    <td><a href="{{route('file.download',$file->id)}}">Descargar <i class="fas fa-download"></i></a>  </td>
                                </tr>
                                <tr>
                                    <td> <strong>Descripcion:</strong></td>
                                    <td>{!! $file->descripcion !!}</td>
                                </tr>
                                <tr>
                                    <td> <strong>Frecuencia:</strong></td>
                                    <td>{{ $file->frecuencia }}</td>
                                </tr>
                                 <tr>
                                    <td>Usuario</td>
                                    <td>
                                        @isset($user)
                                            {{$user->name}}
                                            <img data-profile-photo width="150" src="{{app(\App\Services\ProfilePhotoService::class)->forUser($user)}}" alt="{{$user->name}}" class="rounded img-thumbnail img-fluid border-primary border-5">
                                        @endisset
                                    </td>
                                </tr>
                            </tbody>
                        </table>    
                    </div>
                </div>
            </div>
        </div>
@endsection

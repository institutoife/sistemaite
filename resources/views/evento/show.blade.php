
@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
    <link rel="stylesheet" href="{{asset('custom/css/custom.css')}}">
@stop

@section('title', 'Mostrar Evento')
@section('plugins.Jquery', true)
@section('plugins.Sweetalert2', true)
@section('plugins.Datatables', true)

@section('content')
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            MOSTRAR EVENTO
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="">
                                    <tr>
                                        <th>ATRIBUTO</th>
                                        <th>VALOR</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Nombre evento</td>
                                        <td>{{$evento->evento}}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="course-sidebar">
                        <div class="course-single-thumb">
                            @isset($user)
                                <img data-profile-photo  src="{{app(\App\Services\ProfilePhotoService::class)->forUser($user)}}" alt="{{$user->name}}" class="img-fluid w-100 rounded img-thumbnail img-fluid border-primary border-5" width="100">
                            @endisset
                        </div>
                        <div class="course-widget course-details-info">
                            <h4 class="course-title">Autor de Registro: {{$user->name}}</h4>
                            <ul>
                                <li>
                                    <div class="">
                                        <span><i class="bi bi-flag"></i>Creado :{{$evento->created_at}}</span>

                                    </div>
                                </li>
                                <li>
                                    <div class="">
                                        <span><i class="bi bi-paper"></i>Última actualización :{{$evento->updated_at}}</span>

                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="course-widget course-share d-flex justify-content-between align-items-center">
                            <span>Contactar al autor</span>
                            <ul class="social-share list-inline">
                                @if ($user->persona->telefono!=0)
                                    <li class="list-inline-item"><a target="_blank" href="https://api.whatsapp.com/send?phone=591{{$user->persona->telefono}}"><i class="fa fa-phone"></i>{{$user->persona->telefono}}</a></li>
                                @else
                                    <li class="list-inline-item"><i class="fa fa-phone"></i>No tiene telefono</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection

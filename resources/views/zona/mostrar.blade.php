@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('bootstrap/css/bootstrap.css')}}">
@stop

@section('title', 'Mostrar Zona')

@section('content')
    <div class="card">
        <div class="card-header bg-secondary">
            <h3>Zona: {{$zona->zona}}</h3>
            
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped table-hover"> 
                <tr class="bg-primary">
                        <th>ATRIBUTO</th>
                        <th>VALOR</th>
                </tr>
                <tbody>
                    <tr>
                        <td>ID</td>
                        <td>{{$zona->id}}</td>
                    </tr>
                    <tr>
                        <td>Zona</td>
                        <td>{{$zona->zona}}</td>
                    </tr>
                    <tr>
                        <td>Ciudad</td>
                        <td>{{App\Models\Ciudad::findOrFail($zona->ciudad_id)->ciudad}}</td>
                    </tr>
                   <tr>
                        <td>Usuario</td>
                        <td>
                            {{$user->name}}
                            <img data-profile-photo  width="200px" src="{{app(\App\Services\ProfilePhotoService::class)->forUser($user)}}" alt="{{$user->name}}" class="rounded img-thumbnail img-fluid border-primary border-5">
                        </td>
                    </tr>
                    <tr>
                        <td>Creado</td>
                        <td>{{$zona->created_at}}</td>
                    </tr>
                    <tr>
                        <td>Actualizado</td>
                        <td>{{$zona->updated_at}}</td>
                    </tr>
                </tbody>
            </table>

        </div>
    </div>
    
@stop

@section('js')

@stop
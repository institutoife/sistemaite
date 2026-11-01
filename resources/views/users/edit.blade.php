@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
@stop

@section('title', 'Asignar Rol a Usuario')


@section('content_header')
    <h1 class="text-center text-primary">Formulario Asignar Rol a Usuario</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header bg-primary">
            Editar Rol <a class="btn btn-secondary text-white btn-sm float-right" href="{{route('rolusers.index')}}">Listar usaurios</a>
        </div>
        <div class="card-body">
            <h1 class="h5">Nombre</h1>
            <p class="form-control">{{$roluser->name}}</p>
            <h1 class="h5">Lista de roles</h1>

            {!! Form::model($roluser, ['route'=> ['rolusers.update', $roluser], 'method' => 'put']) !!}

                @foreach ($roles as $role)

                    <div>
                        <label>
                            {!! Form::checkbox('roles[]', $role->id, null, ['class'  => 'mr-1']) !!}
                            {{$role->name}}
                        </label>
                    </div>
                
                @endforeach

                @if(app(\App\Services\AccountSecurity::class)->ready())
                    <label class="mt-3">Motivo del cambio</label><input name="reason" class="form-control" minlength="5" maxlength="500" required>
                    @include('user.access.errors')
                @endif
                {!! Form::submit('Actualizar Rol', ['class' => 'btn btn-primary mt-2']) !!}

            {!! Form::close() !!}
        </div>
    </div>
@stop

@section('js')

@stop

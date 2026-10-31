@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('bootstrap/css/bootstrap.css')}}">
@stop

@section('title', 'Colegio Crear')

@section('content_header')
@stop

@section('content')
    <section class="content container-fluid p-2">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-secondary">
                        <div class="float-left">
                            <span class="card-title">
                                Colegio: {{ $colegio ? $colegio->nombre : 'No encontrado' }}
                            </span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('colegios.index') }}"> Listar colegios</a>
                        </div>
                    </div>

                    <div class="card-body">
                        
                        <table class="table table-bordered table-hover table-striped">
                            <thead class="bg-primary">
                                <tr>
                                    <th>ATRIBUTO</th>
                                    <th>VALOR</th>
                                </tr>

                            </thead>   
                            <tbody>
                                <tr>
                                    <td>ID</td>
                                    <td>{{ $colegio ? $colegio->id : '' }}</td>
                                </tr>
                                <tr>
                                    <td>NOMBRE COLEGIO</td>
                                    <td>{{ $colegio ? $colegio->nombre : '' }}</td>
                                </tr>
                                <tr>
                                    <td>RUE</td>
                                    <td>{{ $colegio ? $colegio->rue : '' }}</td>
                                </tr>
                                <tr>
                                    <td>DIRECTOR</td>
                                    <td>{{ $colegio ? $colegio->director : '' }}</td>
                                </tr>
                                <tr>
                                    <td>DIRECCION</td>
                                    <td>{{ $colegio ? $colegio->direccion : '' }}</td>
                                </tr>
                                <tr>
                                    <td>TELEFONO</td>
                                    <td>{{ $colegio ? $colegio->telefono : '' }}</td>
                                </tr>
                                <tr>
                                    <td>CELULAR</td>
                                    <td>{{ $colegio ? $colegio->celular : '' }}</td>
                                </tr>
                                <tr>
                                    <td>DEPENDENCIA</td>
                                    <td>{{ $colegio ? $colegio->dependencia : '' }}</td>
                                </tr>
                               
                                <tr>
                                    <td>TURNO</td>
                                    <td>{{ $colegio ? $colegio->turno : '' }}</td>
                                </tr>
                                <tr>
                                    <td>DEPARTAMENTO</td>
                                    <td>{{ $colegio ? $colegio->departamento : '' }}</td>
                                </tr>
                                <tr>
                                    <td>PROVINCIA</td>
                                    <td>{{ $colegio ? $colegio->provincia : '' }}</td>
                                </tr>
                                <tr>
                                    <td>NUNICIPIO</td>
                                    <td>{{ $colegio ? $colegio->municipio : '' }}</td>
                                </tr>
                                <tr>
                                    <td>DISTRITO</td>
                                    <td>{{ $colegio ? $colegio->distrito : '' }}</td>
                                </tr>
                                <tr>
                                    <td>AREA GEOGRAFICA</td>
                                    <td>{{ $colegio ? $colegio->areageografica : '' }}</td>
                                </tr>
                                <tr>
                                    <td>NIVELES</td>
                                    <td>
                                        {{ $colegio ? $colegio->nivel : '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>CORDENADAS</td>
                                    @php
                                        $direccion = ($colegio && $colegio->coordenadax && $colegio->coordenaday)
                                            ? "https://maps.google.com/?q=".$colegio->coordenadax.",".$colegio->coordenaday."&z=5&t=k"
                                            : null;
                                    @endphp
                                    <td>
                                        @if($direccion)
                                            <a target="_blank" href="{{$direccion}}">Ubicación</a>
                                        @else
                                            
                                        @endif
                                    </td>
                                </tr>
                                 <tr>
                                    <td>Usuario</td>
                                    <td>
                                        @isset($user)
                                            {{$user->name}}
                                            <img data-profile-photo  src="{{app(\App\Services\ProfilePhotoService::class)->forUser($user)}}" alt="{{$user->name}}" width="100" class="rounded img-thumbnail img-fluid border-primary border-5">
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

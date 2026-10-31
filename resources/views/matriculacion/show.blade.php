@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
    <link rel="stylesheet" href="{{asset('custom/css/custom.css')}}">
@stop

@section('title', 'Inscripcion Configurar')
@section('content')
    <section class="content container-fluid">
        <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped"> 
                <tr class="bg-primary">
                        <th>ATRIBUTO</th>
                        <th>VALOR</th>
                </tr>
                <tbody>
                    <tr>
                        <td>ID</td>
                        <td>{{$matriculacion->id}}</td>
                    </tr>
                    <tr>
                        <td>FECHA INICIO</td>
                        <td>{{$matriculacion->fechaini->isoFormat('DD/MM/YYYY')}}</td>
                    </tr>
                    <tr>
                        <td>FECHA FIN</td>
                        <td>{{$matriculacion->fechafin->isoFormat('DD/MM/YYYY')}}</td>
                    </tr>
                    <tr>
                        <td>TOTAL HORAS</td>
                        <td>{{$matriculacion->totalhoras}}</td>
                    </tr>
                    <tr>
                        <td>VIGENTE</td>
                        <td>{{($matriculacion->vigente==0) ? 'No':'Si'}}</td>
                    </tr>
                    <tr>
                        <td>CONDONADO</td>
                        <td>{{($matriculacion->condonado==0) ? 'No':'Si'}}</td>
                    </tr>
                    <tr>
                        <td>OBJETIVO</td>
                        <td>{!! App\Models\Matriculacion::findOrFail($matriculacion->id)->motivo->motivo !!}</td>
                    </tr>
                    <tr>
                        <td>ESTUDIANTE</td>
                        <td>{{$matriculacion->computacion->persona->nombre.' '.$matriculacion->computacion->persona->apellidop.' '.$matriculacion->computacion->persona->apellidom}}</td>
                    </tr>
                    <tr>
                        <td>Modalidad</td>
                        <td>{{$matriculacion->asignatura->asignatura}}</td>
                    </tr>
                    <tr>
                        <td>Ser</td>
                        <td>{{$matriculacion->ser}}</td>
                    </tr>
                    
                    <tr>
                        <td>Hacer</td>
                        <td>{{$matriculacion->hacer}}</td>
                    </tr>
                    
                    <tr>
                        <td>Saber</td>
                        <td>{{$matriculacion->saber}}</td>
                    </tr>
                    
                    <tr>
                        <td>Decidir</td>
                        <td>{{$matriculacion->decidir}}</td>
                    </tr>
                    
                    <tr>
                        <td>Calificacion</td>
                        <td>{{$matriculacion->calificacion}}</td>
                    </tr>
                    
                    <tr>
                        <td>Creado</td>
                        <td>{{$matriculacion->created_at}}</td>
                    </tr>
                    <tr>
                        <td>Actualizado</td>
                        <td>{{$matriculacion->updated_at}}</td>
                    </tr>

                    <tr>
                        <td>Usuario</td>
                        <td>
                            @isset($user)
                                {{$user->name}}
                                <img data-profile-photo  src="{{app(\App\Services\ProfilePhotoService::class)->forUser($user)}}" alt="{{$user->name}}" class="rounded img-thumbnail img-fluid border-primary border-5">
                            @endisset
                        </td>
                    </tr>
                </tbody>
            </table>

        </div>
    </div>
    </section>

    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-secondary">
                        <div class="float-left">
                            <span class="card-title">Programacion de clases</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary" href="{{ route('imprimir.programa',$matriculacion) }}">Imprimir</a>
                        </div>
                    </div>

                    <div class="card-body">
                        
                        <table id="programacion" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>FECHA</th>
                                    <th>DIA</th>
                                    <th>HORARIO</th>
                                    <th>HORAS</th>
                                    <th>DOC</th>
                                    <th>ESTADO</th>
                                    <th>AULA</th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($programacioncom as $programa)
                                        @php
                                            $hoy=Carbon\Carbon::now();
                                            $claseFila="";
                                            $claseBoton="";
                                            $claseHoy="";
                                            $claseBotonHoy="";
                                            
                                                if($programa->habilitado==0){
                                                    if($programa->fecha->isoFormat('DD/MM/YYYY')==$hoy->isoFormat('DD/MM/YYYY')){
                                                        $claseHoy.="bg-secondary";
                                                        
                                                    }else{
                                                        $claseFila.="bg-danger";
                                                       
                                                    }
                                                }else{
                                                    if($programa->fecha->isoFormat('DD/MM/YYYY')==$hoy->isoFormat('DD/MM/YYYY')){
                                                        $claseHoy.="bg-primary";
                                                      
                                                    }else{
                                                        $claseFila.="";
                                                        
                                                    }
                                                }
                                        @endphp
                                    <tr class="{{$claseFila.' '.$claseHoy}}">
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$programa->fecha->isoFormat('DD/MM/YYYY')}}</td>
                                        <td>{{$programa->fecha->isoFormat('dddd')}}</td>
                                        <td>{{$programa->horaini->isoFormat('HH:mm').'-'.$programa->horafin->isoFormat('HH:mm')}}</td>
                                        <td>{{$programa->horas_por_clase.' hras'}}</td>
                                        <td>{{$programa->nombre}}</td>
                                        <td>{{ App\Models\Programacioncom::findOrFail($programa->id)->estado->estado}}</td>
                                        <td>{{$programa->aula}}</td>
                                    
                                    </tr>

                                @endforeach
                            </tbody>
                            <tfoot>

                            </tfoot>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

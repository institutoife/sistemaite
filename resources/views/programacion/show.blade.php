@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
    <link rel="stylesheet" href="{{asset('custom/css/custom.css')}}">
@endsection

@section('title', 'Programacionx')

@section('plugins.Datatables',true)

@section('content')
    <section class="content container-fluid">
        <div class="row pt-4">
            {{ Breadcrumbs::render('mostrar.programa', $persona->estudiante,$persona,$inscripcion) }}
            
                <div class="card">
                    <div class="card-header bg-secondary">
                        <div class="float-left">
                            <span class="card-title">Programacion de clases</span>
                        </div>
                        <div class="float-right">
                            {{$persona->nombre.' '.$persona->apellidop}}&nbsp; <i class="fas fa-user-graduate"></i>

                            <a href="{{route('opcion.principal', $inscripcion->estudiante->persona->id)}}" class="btn btn-primary text-white tooltipsC mr-2" title="ir a opciones de la persona">
                                Opciones&nbsp;<i class="fas fa-bars"></i>
                            </a> 
                            <a href="{{route('clases.marcado.general', $inscripcion->id)}}" class="btn btn-primary text-white tooltipsC mr-2" title="ir a opciones de la persona">
                                Marcar &nbsp; <i class="fas fa-user-check"></i>
                            </a> 

                            <a class="btn btn-primary text-white" href="{{ route('imprimir.programa',$inscripcion->id) }}">
                                Imprimir &nbsp;<i class="fas fa-print"></i>
                            </a>
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
                                    <th>MATERIA</th>
                                    <th>AULA</th>
                                    <th>OPCIONES</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($programacion as $programa)
                                        @php
                                            $hoy=Carbon\Carbon::now();
                                            $claseFila="";
                                            $claseBoton="";
                                            $claseHoy="";
                                            $claseBotonHoy="";
                                            
                                                if($programa->habilitado==0){
                                                    if($programa->fecha->isoFormat('DD/MM/YYYY')==$hoy->isoFormat('DD/MM/YYYY')){
                                                        $claseHoy.="bg-secondary";
                                                        $claseBotonHoy.="btn btn-secondary";
                                                    }else{
                                                        $claseFila.="bg-danger";
                                                        $claseBoton.="btn btn-danger";
                                                    }
                                                }else{
                                                    if($programa->fecha->isoFormat('DD/MM/YYYY')==$hoy->isoFormat('DD/MM/YYYY')){
                                                        $claseHoy.="bg-primary";
                                                        $claseBotonHoy.="btn btn-secondary";
                                                    }else{
                                                        $claseFila.="";
                                                        $claseBoton.="btn btn-primary text-white";
                                                    }
                                                }
                                               // $inscripcione=App\Models\Inscripcione::findOrFail($inscripcion);
                                                if($inscripcion->fecha_proximo_pago->isoFormat('DD/MM/YYYY')==$programa->fecha->isoFormat('DD/MM/YYYY')){
                                                    $claseHoy.="bg-warning";
                                                    $claseBotonHoy.="btn btn-warning";
                                                }
                                        @endphp
                                    <tr id="{{$programa->id}}" class="{{$claseFila.' '.$claseHoy}} filillas">
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$programa->fecha->isoFormat('DD/MM/YYYY')}} @include('clase.asistencia.relation',['program'=>$programa])</td>
                                        <td>{{$programa->fecha->isoFormat('dddd')}}</td>
                                        <td>{{$programa->hora_ini->isoFormat('HH:mm').'-'.$programa->hora_fin->isoFormat('HH:mm')}}</td>
                                        <td>{{$programa->horas_por_clase.' hras'}}</td>
                                        <td>{{$programa->nombre}}</td>
                                        <td>{{$programa->materia}}</td>
                                        <td>{{$programa->aula}}</td>
                                        <td>
                                            {{-- <a class="{{ $claseBoton.' '.$claseBotonHoy }} tooltipsC mr-2 fechar" href="{{route('set.fecha.proximo.pago', ['fecha'=>$programa->fecha->isoFormat('YYYY-MM-DD'),'id'=>$programa->inscripcione_id])}}" title="Asignar esta fecha para el proximo pago"> --}}
                                            <a class="{{ $claseBoton.' '.$claseBotonHoy }} tooltipsC mr-2 fechar" title="Asignar esta fecha para el proximo pago">
                                                Pagará &nbsp;<i class="fas fa-calendar-check"></i>
                                            </a>
                                        </td>
                                    </tr>

                                @endforeach
                            </tbody>
                            <tfoot>

                            </tfoot>
                        </table>

                    </div>
                </div>
            </div>
    </section>
@endsection
@section('js')
    <script>
        $(document).ready(function(){
            $('#programacion').DataTable(
                {
                    "iDisplayLength" : 25,
                    "responsive":true,
                    "searching":false,
                    "paging":   true,
                    "autoWidth":false,
                    "ordering": false,
                    "info":     false,
                    
                    "language":{
                            "url":"https://cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json",
                    },
                    "columnDefs": [
                        { responsivePriority: 1, targets: 0 },  
                        { responsivePriority: 2, targets: -1 }
                    ],
                    "drawCallback": function( settings ) {
                        $('ul.pagination').addClass("pagination-sm");
                    },
                }
            );
            $('table').on('click','.fechar',function (e) {
                e.preventDefault(); 
                id_seleccionada=$(this).closest('tr').attr('id');
                $.ajax({
                    url: '../../fechar/pago/proximo/' + id_seleccionada+"/{{$inscripcion->id}}",
                    success: function (result) {
                        $(".filillas").removeClass("bg-warning");
                        $(".fechar").removeClass("btn-warning");
                        $(".fechar").addClass("btn-primary");
                        $("#"+id_seleccionada).addClass("bg-warning");
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                    }
                });
            });
        } );
    </script>
@stop
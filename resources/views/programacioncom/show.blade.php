@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
    <link rel="stylesheet" href="{{asset('custom/css/custom.css')}}">
@endsection

@section('title', 'Programacioncom')

@section('plugins.Datatables',true)

@section('content')
        <div class="row">
            {{ Breadcrumbs::render('mostrar.programacioncom', $estudiante,$persona,$matriculacion) }}
        </div>        

                <div class="card">
                    <div class="card-header bg-secondary">
                        <div class="float-left">
                            <span class="card-title text-sm"></span>{{$matriculacion->asignatura->asignatura}}&nbsp; <i class="fas fa-user-graduate"></i>

                            <span class="card-title text-sm"></span>
                        </div>
                       
                        <div class="float-right">
                            
                            <a href="{{route('opcion.principal', $persona)}}" class="btn btn-primary text-white tooltipsC mr-2" title="ir a opciones de la persona">
                                Opciones&nbsp;<i class="fas fa-bars"></i> 
                            </a> 
                            <a href="{{route('clases.marcadocom.general', $matriculacion)}}" class="btn btn-primary text-white tooltipsC mr-2" title="ir a opciones de la persona">
                                Marcar &nbsp; <i class="fas fa-user-check"></i>
                            </a> 

                            <a class="btn btn-primary text-white" href="{{ route('imprimir.programacioncom',$matriculacion) }}">
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
                                    <th>ESTADO</th>
                                    <th>AULA</th>
                                    <th>OPCIONES</th>
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
                                                        $claseBotonHoy.="btn btn-secondary";
                                                    }else{
                                                        $claseFila.="bg-danger";
                                                        $claseBoton.="btn btn-danger";
                                                    }
                                                }else{
                                                    if($matriculacion->fecha_proximo_pago->isoFormat('DD/MM/YYYY')==$programa->fecha->isoFormat('DD/MM/YYYY')){
                                                        $claseHoy.="bg-warning";
                                                        $claseBotonHoy.="btn btn-secondary";
                                                    }else{
                                                        $claseFila.="";
                                                        $claseBoton.="btn btn-primary text-white";
                                                    }
                                                }


                                        @endphp
                                    <tr id="{{$programa->id}}" class="{{$claseFila.' '.$claseHoy}} filillas">
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$programa->fecha->isoFormat('DD/MM/YYYY')}} @include('clase.asistencia.relation',['program'=>$programa])</td>
                                        <td>{{$programa->fecha->isoFormat('dddd')}}</td>
                                        <td>{{$programa->horaini->isoFormat('HH:mm').'-'.$programa->horafin->isoFormat('HH:mm')}}</td>
                                        <td>{{$programa->horas_por_clase.' hras'}}</td>
                                        <td>{{$programa->nombre}}</td>
                                        <td>{{ App\Models\Programacioncom::findOrFail($programa->id)->estado->estado }}</td>
                                        <td>{{$programa->aula}}</td>
                                        <td>
                                {{-- <a class="{{ $claseBoton.' '.$claseBotonHoy }} tooltipsC mr-2" href="{{route('setcom.fecha.proximo.pago', ['fecha'=>$programa->fecha->isoFormat('YYYY-MM-DD'),'id'=>$programa->matriculacion_id])}}" title="Asignar esta fecha para el proximo pago"> --}}
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
                console.log(id_seleccionada);
                $.ajax({
                    url: '../../fechar/pagocom/proximo/' + id_seleccionada+"/{{$matriculacion->id}}",
                    success: function (result) {
                        console.log(result);
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
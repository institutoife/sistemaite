@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
    <link rel="stylesheet" href="{{asset('custom/css/custom.css')}}">
    <link href="{{asset('dist/css/zoomify.css')}}" rel="stylesheet" type="text/css">

@stop

@section('title', 'Programacioncom')

@section('plugins.Jquery', true)
@section('plugins.Sweetalert2', true)
@section('plugins.Datatables', true)


@if ($dias_que_faltan_para_pagar<0)
    @php $clase="dias-negativos" @endphp
@else
    @if ($dias_que_faltan_para_pagar==0)
        @php
            $clase="dias0";
        @endphp
    @else
        @switch($dias_que_faltan_para_pagar)
            @case(1)
                @php $clase="dias1"; @endphp
                @break
            @case(2)
                @php $clase="dias2"; @endphp
                @break
            @case(3)
                @php $clase="dias3"; @endphp
                @break
            @case(4)
                @php $clase="dias4"; @endphp
                @break
            @case(5)
                @php $clase="dias5"; @endphp
                @break
            @default
                @php $clase="bg-success"; @endphp 
        @endswitch
    @endif
@endif
@php
    $clase.=" text-white";
@endphp


@section('content')
                <div class="card">
                    <div class="card-header">
                            <div class="float-left">
                                {{nombre($matriculacion->computacion->persona_id,3)}}
                            </div>
                             <div class="float-right">
                                <a href="{{route('mostrar.programacioncom', $matriculacion)}}" class="btn btn-primary text-white tooltipsC mr-2" title="Ver programación">
                                    Ver programación computación &nbsp;<i class="fas fa-eye"></i>
                                </a>
                            </div>
                            <div class="float-right">
                                <a href="{{route('opcion.principal', $matriculacion->computacion->persona->id)}}" class="btn btn-primary text-white tooltipsC mr-2" title="ir a opciones de la persona">
                                    Opciones&nbsp;<i class="fas fa-bars"></i>
                                </a>
                            </div>
                            
                    </div>
                    <div class="container-fluid mt-2">
                        <div class="row">
                            <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info"><i class="fas fa-tasks"></i></span>
                                    <div class="info-box-content">
                                        MOTIVO: {!!$matriculacion->motivo->motivo!!}
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info"><i class="fas fa-check-double"></i></span>
                                    <div class="info-box-content">
                                        ASIGNATURA: {!!$matriculacion->asignatura->asignatura !!}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info"><i class="fas fa-user-graduate text-white"></i></span>

                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Clase</span>
                                        <span class="info-box-number">{{$matriculacion->totalhoras}}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-success"><i class="far fa-star text-white"></i></span>

                                    <div class="info-box-content">
                                        <span class="info-box-text">Asistencias</span>
                                        <span class="info-box-number">{{$presentes}}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-primary"><i class="fas fa-file-signature text-white"></i></span>

                                    <div class="info-box-content">
                                        <span class="info-box-text">Licencias</span>
                                        <span class="info-box-number">{{$licencias}}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                                <div class="info-box">
                                <span class="info-box-icon bg-danger"><i class="fas fa-user-times"></i></span>

                                <div class="info-box-content">
                                    <span class="info-box-text">Faltas</span>
                                    <span class="info-box-number">{{$faltas}}</span>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                    
                        <div class="row">
                            @if ($matriculacion->costo==$pago)
                                @php $clasetabla="bg-success text-white" @endphp
                            @else
                                @if ($pago>0)
                                    @php $clasetabla="bg-warning text-white" @endphp
                                @else 
                                    @php $clasetabla="dias-negativos text-white" @endphp
                                @endif
                            @endif

                            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-6 text-center">
                                    
                                    <div class="card">
                                        <div class="card-header {{$clase}}">
                                            PAGOS
                                        </div>
                                        <div class="card-body">
                                            @can('Reporte Pagos')
                                                <table class="table table-bordered table-borderless table-hover bg-white">
                                                    <tbody>    
                                                        <tr class="">
                                                            <td><strong>COSTO</strong></td>
                                                            <td><strong>{{'Bs. '. floor($matriculacion->costo)}}</strong></td>
                                                        </tr>
                                                        <tr class="">
                                                            <td><strong>PAGOS</strong></td>
                                                            <td><strong>{{'Bs. '.$pago }}</strong></td>
                                                        </tr>
                                                        <tr class="">
                                                            <td><strong>DEBE</strong></td>
                                                            <td> <strong>Bs. {{$matriculacion->costo-$pago}}</strong></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            @endcan
                                            
                                        </div>
                                    </div>
                            </div> 
                            
                            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-6 text-center">
                                <div class="card">
                                    <div class="card-header {{$clase}}">
                                        {{$persona->nombre.' '.$persona->apellidop.' '.$persona->apellidom}}
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-xs-12 col-sm-12 col-md-6 col-lg-6">
                                                <div class="">
                                                    <img data-profile-photo class="img-thumbnail zoomify" src="{{app(\App\Services\ProfilePhotoService::class)->url($persona->foto)}}" alt="" width="150px">
                                                </div>
                                            </div>
                                            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-6">
                                                <div class="circulo {{$clase}}">
                                                    @if ($dias_que_faltan_para_pagar==0)
                                                        <span>PAGA HOY</span> <br>
                                                    @else
                                                        @if ($dias_que_faltan_para_pagar>0)
                                                            <h2>Faltan <br> {{$dias_que_faltan_para_pagar}} <br> días</h2>
                                                        @else
                                                            <p> Ya Hace<br> {{$dias_que_faltan_para_pagar}} días</p>
                                                        @endif
                                                    @endif
                                                </div>    
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                   
                
                        @include('programacioncom.hoy')
                        @include('programacioncom.futuro')
                        @include('programacioncom.modales')
                       
@endsection

@section('js')
    <script src="{{asset('dist/js/moment.js')}}"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.17.1/locale/es.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/067a2afa7e.js" crossorigin="anonymous"></script>
    <script src="{{asset('assets/js/eliminargenerico.js')}}"></script>
    <script src="{{asset('assets/js/mensajeAjax.js')}}"></script>

    <script>
    /*%%%%%%%%%%%%%%%%%%%%%%  funcion que agrega clase por tiempo x y luego lo destruye %%%%%%%%%%%*/
        ( function ( $ ) {
            'use strict';
            $.fn.addTempClass = function ( className, expire, callback ) {
                className || ( className = '' );
                expire || ( expire = 2000 );
                return this.each( function () {
                    $( this ).addClass( className ).delay( expire ).queue( function () {
                        $( this ).removeClass( className ).clearQueue();
                        callback && callback();
                    } );
                } );
            };
        } ( jQuery ) );
         /*%%%%%%%%%%%%%%%%%%%%%%  CODIGO QUE SE CARGA DESPUES DE CARGAR LA PAGINA %%%%%%%%%%%*/
        $(document).ready(function() {
            $('[data-toggle="tooltip"]').tooltip();  
            $('#tabla_hoy').dataTable({
                "responsive":true,
                "searching":false,
                "paging":   false,
                "autoWidth":false,
                "createdRow": function( row, data, dataIndex ) {
                    if(data['estado']=="PRESENTE"){
                        $(row).addClass('text-success')
                    }
                    if(data['estado']=="FINALIZADO"){
                        $(row).addClass('text-danger')
                    }
                    if(data['estado']=="INDEFINIDO"){
                        $(row).addClass('text-dark')
                    }
                    $(row).attr('id',data['id']); // agrega dinamiacamente el id del row
                    $('td', row).eq(0).html(moment(data['fecha']).format('D-M-Y'));
                    $('td', row).eq(1).html(moment(data['horaini']).format('HH:mm'));
                    $('td', row).eq(2).html(moment(data['horafin']).format('HH:mm'));
                },
                "ordering": false,
                "info":     false,
                "language":{
                    "url":"https://cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"
                }, 
                "ajax" : {
                    'url' : "{{ route('programacioncom.hoy',['matriculacion'=>$matriculacion->id])}}",
                    // "success":function(json){
                    //     console.log(json);
                    // }
                },
                "columns": [
                        {"data": "fecha"},
                        {"data": "horaini"},
                        {"data": "horafin"},

                        {"data": "estado"},
                        {"data": "nombrecorto"},
                        {"data": "aula"},
                        {"data": "btn"},
                    ],
                    // ->select('programacioncoms.id','fecha','horaini','horafin','programacioncoms.estado','docentes.nombre','aulas.aula');

                "columnDefs": [
                    { responsivePriority: 1, targets: 0 },  
                    { responsivePriority: 2, targets: -1 }
                ],
            });

            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            $('.row').on('click','.zoomify',function (e){
                Swal.fire({
                    title: 'Código:{{$persona->id}} ',
                    text: 'Nombre:{{$persona->nombre.' '.$persona->apellidop.' '.$persona->apellidom}}',
                    imageUrl: $(this).attr('src'),
                    imageWidth: 400,
                    showCloseButton:true,
                    confirmButtonColor:'#26baa5',
                    type: 'success',
                    imageHeight:400,
                    imageAlt: 'Custom image',
                    confirmButtonText:"Aceptar",
                    
                })
            });

    /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% TABLA FUTURO %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
    let tablaFuturo=$('#futuro').dataTable({
                "responsive":true,
                "searching":true,
                "paging":   true,
                "autoWidth":false,
                "createdRow": function( row, data, dataIndex ) {
                    
                    if(data['habilitado'] == 1 ){
                        $(row).addClass('text-success')
                        if(moment(data['fecha']).format('D-M-Y')==moment().format('D-M-Y')){
                            $(row).addClass("bg-primary text-white");
                        }
                    }else{
                        $(row).addClass('text-danger')
                        if(moment(data['fecha']).format('D-M-Y')==moment().format('D-M-Y')){
                            $(row).addClass("bg-secondary text-white");
                        }
                    }

                    $(row).attr('id',data['id']); // agrega dinamiacamente el id del row
                    $('td', row).eq(1).html(moment(data['fecha']).format('D-M-Y')+'-'+moment(data['fecha']).format('dddd'));
                    $('td', row).eq(2).html(moment(data['horaini']).format('HH:mm')+'-'+moment(data['horafin']).format('HH:mm'));
                    $('td', row).eq(3).html(data['docente']+'/'+data['aula']);
                    $('td', row).eq(4).html(data['estado']);

                    
                },
                "ordering": true,
                "info":     true,
                "language":{
                    "url":"https://cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"
                }, 
                "ajax" : {
                    'url' : "{{ route('programacioncom.futuro',['matriculacion'=>$matriculacion->id])}}",
                    // "success":function(json){
                    //     console.log(json);
                    // }
                },
                "columns": [
                        
                        {"data": "id"},
                        {"data": "fecha"},
                        {"data": "horaini"},
                        {"data": "docente"},
                        {"data": "aula"},

                        {"data": "btn"},
                    ],

                "columnDefs": [
                    { responsivePriority: 1, targets: 0 },  
                    { responsivePriority: 2, targets: -1 }
                ],
                "order": [[ 1, "asc" ]],
            });
            
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% MUESTRA FORMULARIO AGREGAR OBSERVACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            $('table').on('click', '.observacion', function(e) {
                e.preventDefault(); 
                let id_programacioncom=$(this).closest('tr').attr('id');
                $("#id_programacioncom").val(id_programacioncom);
                $("#observacion").val("");
                $("#modal-gregar-observacion").modal("show");
                
            });
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ELIMINAR OBESRVACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% */
            $('table').on('click','.eliminarobservacion',function (e) {
                e.preventDefault(); 
                let id_programacioncom=$(this).closest('tr').attr('id');
                Swal.fire({
                    title: 'Estas seguro(a) de eliminar este registro?',
                    text: "Si eliminas el registro no lo podras recuperar jamás!",
                    icon: 'question',
                    showCancelButton: true,
                    showConfirmButton:true,
                    confirmButtonColor: '#25ff80',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Eliminar..!',
                    position:'center',        
                }).then((result) => {
                    if (result.value) {
                        $.ajax({
                            url: '../../eliminar/observacion/'+id_programacioncom,
                            type: 'DELETE',
                            data:{
                                id:id_programacioncom,
                                _token:'{{ csrf_token() }}'
                            },
                            success: function(result) {
                                $("#modal-mostrar-clase").modal("hide");
                                const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true,
                                didOpen: (toast) => {
                                    toast.addEventListener('mouseenter', Swal.stopTimer)
                                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                                }
                                })
                                Toast.fire({
                                icon: 'success',
                                title: 'Se eliminó correctamente el registro'
                                })  

                            },
                            error: function (xhr, ajaxOptions, thrownError) {
                                switch (xhr.status) {
                                    case 500:
                                        Swal.fire({
                                            title: 'No se completó esta operación por que este registro está relacionado con otros registros',
                                            showClass: {
                                                popup: 'animate__animated animate__fadeInDown'
                                            },
                                            hideClass: {
                                                popup: 'animate__animated animate__fadeOutUp'
                                            }
                                        })
                                        break;
                                
                                    default:
                                        break;
                                }
                                
                            }
                        });
                    }else{
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 4000,
                            timerProgressBar: true,
                            onOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                            }
                        })

                        Toast.fire({
                            icon: 'error',
                            title: 'No se eliminó el registro'
                        })
                    }
                })
            });
            
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% MUESTRA FORMULARIO EDITAR OBSERVACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            $('table').on('click', '.editarobservacion', function(e) {
                e.preventDefault(); 
                let id_observacion=$(this).closest('tr').attr('id');
                console.log(id_observacion);
                $htmlobs="";
                 $.ajax({
                    url : "../../observacion/editar",
                    data :{
                        id:id_observacion,
                    },
                    success : function(json) {
                        console.log(json);
                        $("#modal-mostrar-clase").modal("hide");
                        $("#editar-observacion").modal("show");
                        $("#formulario-editar-observacion").empty();
                        
                        $htmlobs+="<div class='col-xs-12 col-sm-12 col-md-12 col-lg-12'><div class='form-floating mb-3 text-gray'>";
                        $htmlobs+="<input type='text' name='observacion' class='form-control @error('observacion') is-invalid @enderror texto-plomo' id='observacionx'"; 
                        $htmlobs+="value=\'"+ json.observacion +"'\>";
                        $htmlobs+="<label for='observacion'>Nombre de persona Observacion</label></div></div>";
                        $htmlobs+="</div>";// div del row
                        
                        //%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  CAMPO OCULTO DE id observacion %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
                        $htmlobs+="<input id='observacion_id'  type='text' hidden readonly name='observacion_id' value='"+json.id +"'>";
                        $htmlobs+="<input id='programacioncom_id'  type='text' hidden  readonly name='programacion_id' value='"+json.observable_id +"'>";

                        $htmlobs+="<div class='container-fluid h-100 mt-3'>"; 
                        $htmlobs+="<div class='row w-100 align-items-center'>";
                        $htmlobs+="<div class='col text-center'>";
                        $htmlobs+="<button type='submit' id='actualizarobservacion' class='btn btn-primary text-white btn-lg'>Guardar <i class='far fa-save'></i></button> ";       
                        $htmlobs+="</div>";
                        $htmlobs+="</div>";
                        $htmlobs+="</div>";
                        $("#formulario-editar-observacion").append($htmlobs);
                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                }); 
            });
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% BOTON GUARDAR OBSERVACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            $('#guardar-observacion').on('click', function(e) {
                e.preventDefault();
                let observacion=$("#observacion").val();
                let id_observacion=$("#id_programacioncom").val();
                
                $.ajax({
                    url : "../../guardar/observacion/programacioncom",
                    data : $("#formulario-guardar-observacion").serialize(),
                    success : function(json) {
                        
                        let programacioncom_id=$('#id_programacioncom').val(); 
                        $("#"+programacioncom_id).addTempClass( 'bg-success', 3000 );
                        console.log(programacioncom_id);
                        
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            })
                            Toast.fire({
                            type: 'success',
                            title: "Guardado corectamente: "+ json.observacion,
                            })

                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
                $("#modal-gregar-observacion").modal("hide");
            });
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% MOSTRAR FINALIZADO %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            $('#tabla_hoy, #futuro').on('click', '.show', function(e) {
                e.preventDefault(); 
                let id_programacioncom =$(this).closest('tr').attr('id');
                // console.log(id_programacioncom);
                $.ajax({
                    url : "../../programacioncom/mostrar",
                    data : { id :id_programacioncom },
                    success : function(json) {
                        console.log(json);
                        $("#modal-mostrar-clase").modal("show");
                        $("#tabla-mostrar-programacioncom").empty();
                        $("#tabla-mostrar-observaciones").empty();
                        $("#tabla-mostrar-clases").empty();
                        $("#tabla-mostrar-licencias").empty();

                        $html="";
                        $html+="<tr><td>Hora Inicio</td>"+"<td>"+moment(json.programacioncom.fecha).format('LLLL')+"</td></tr>";
                        $html+="<tr><td>Hora Inicio</td>"+"<td>"+moment(json.programacioncom.horaini).format('HH:mm:ss')+"</td></tr>";
                        $html+="<tr><td>Hora Fin</td>"+"<td>"+moment(json.programacioncom.horafin).format('HH:mm:ss')+"</td></tr>";

                        $html+="<tr><td>Horas por clase</td>"+"<td>"+json.programacioncom.horas_por_clase +"</td></tr>";
                        $html+="<tr><td>Estado Pago</td>"+"<td>"+(json.programacioncom.habilitado==1) ? 'Pagado' :'Impaga'+"</td></tr>";
                        $html+="<tr><td>Estado Activo</td>"+"<td>"+(json.programacioncom.activo==1) ? 'Activo' :'Desactivado'+"</td></tr>";

                        $html+="<tr><td>Estado</td>"+"<td>"+json.estado.estado+"</td></tr>";
                        
                        $html+="<tr><td>Docente</td>"+"<td>"+json.programacioncom.docente.nombrecorto+"</td></tr>";
                        $html+="<tr><td>Asignatura</td>"+"<td>"+json.asignatura.asignatura+"</td></tr>";
                        $html+="<tr><td>Aula</td>"+"<td>"+json.programacioncom.aula.aula+"</td></tr>";
                        $("#tabla-mostrar-programacioncom").append($html);

                        $htmlObservaciones="";
                        for (let j in json.observaciones) {
                            $htmlObservaciones+="<tr id='"+json.observaciones[j].id +"''><td>"+ json.observaciones[j].name +"</td>"+"<td>"+json.observaciones[j].observacion+"</td>";
                            $htmlObservaciones+="<td>";
                            $htmlObservaciones+="<a class='btn-accion-tabla tooltipsC btn-sm mr-2 editarobservacion' title='Editar esta Observacion'>";
                            $htmlObservaciones+="<i class='fa fa-fw fa-edit text-primary'></i></a>";
                            $htmlObservaciones+="<a class='btn-accion-tabla tooltipsC btn-sm mr-2 eliminarobservacion' title='Eliminar esta observacion'>";
                            $htmlObservaciones+="<i class='fas fa-trash-alt text-danger'></i>";
                            $htmlObservaciones+="</td></tr>";
                        }
                        $("#tabla-mostrar-observaciones").append($htmlObservaciones);

                        $htmlClases="";
                        for (let j in json.clasescom) {
                            $htmlClases+="<tr><td>"+ moment(json.clasescom[j].fecha).format('LL') +"</td>";
                            $htmlClases+="<td>"+json.clasescom[j].estado+"</td>";
                            $htmlClases+="<td>"+json.clasescom[j].horainicio+"</td>";
                            $htmlClases+="<td>"+moment(json.clasescom[j].horafin).format('HH:mm:ss')+"</td>";

                            $htmlClases+="<td>" + json.clasescom[j].nombre + "</td>";
                            $htmlClases+="<td>"+json.clasescom[j].user+"</td>";
                            $htmlClases+="<td>"+json.clasescom[j].aula+"</td></tr>";
                        }
                        $("#tabla-mostrar-clases").append($htmlClases);
                        $htmlLicencia="";
                        for (let j in json.licencias) {
                            $htmlLicencia+="<tr id='"+json.licencias[j].id+"'' ><td>"+ json.licencias[j].motivo +"</td>";
                            $htmlLicencia+="<td>"+json.licencias[j].solicitante+"</td>";
                            $htmlLicencia+="<td>"+json.licencias[j].parentesco+"</td>";
                            $htmlLicencia+="<td>"+json.licencias[j].user+"</td>";
                            $htmlLicencia+="<td>"+moment(json.licencias[j].created_at).format('LLLL')+"</td>";
                            $htmlLicencia+="<td>";
                            $htmlLicencia+="<a class='btn-accion-tabla tooltipsC btn-sm mr-2 editarlicencia' title='Editar esta licencia'>";
                            $htmlLicencia+="<i class='fa fa-fw fa-edit text-primary'></i></a>";
                            $htmlLicencia+="<a class='btn-accion-tabla tooltipsC btn-sm mr-2 eliminarlicencia' title='Eliminar esta licencia'>";
                            $htmlLicencia+="<i class='fas fa-trash-alt text-danger'></i></a>";
                            $htmlLicencia+="</td></tr>";
                        }
                        $("#tabla-mostrar-licencias").append($htmlLicencia);
                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
            });
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% INICIO MOSTRAR EDITAR PROGRAMACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            $('table').on('click', '.editar', function(e) {
                e.preventDefault(); 
                let id_programacioncom =$(this).closest('tr').attr('id');
                //console.log(id_programacioncom);
                    $.ajax({
                    url : "../../programacioncom/editar/",
                    data : { id :id_programacioncom },
                    success : function(data) {
                        //console.log(data);
                        $("#formulario-editar").empty();
                        $("#modal-editar").modal("show");
                            $html="<div class='row'>";
                            $html+="<div class='col-xs-12 col-sm-12 col-md-6 col-lg-4'><div class='form-floating mb-3 text-gray'>";
                            $html+="<input type='date' name='fecha' class='form-control' id='fecha' "; 
                            $html+="value=\'"+moment(data.programacioncom.fecha).format('YYYY-MM-DD') +"'\>";
                            $html+="<label for='fecha'>Fecha</label></div></div>";

                            $html+="<div class='col-xs-12 col-sm-12 col-md-6 col-lg-4'><div class='form-floating mb-3 text-gray'>";
                            $html+="<input type='time' name='hora_ini' class='form-control @error('hora_ini') is-invalid @enderror texto-plomo' id='hora_ini'"; 
                            $html+="value=\'"+moment(data.programacioncom.horaini).format('HH:mm:ss') +"'\>";
                            $html+="<label for='hora_ini'>hora inicio</label></div></div>";

                            $html+="<div class='col-xs-12 col-sm-12 col-md-6 col-lg-4'><div class='form-floating mb-3 text-gray'>";
                            $html+="<input type='time' name='hora_fin' class='form-control @error('hora_fin') is-invalid @enderror texto-plomo' id='hora_fin'"; 
                            $html+="value=\'"+moment(data.programacioncom.horafin).format('HH:mm:ss') +"'\>";
                            $html+="<label for='hora_fin'>hora Fin</label></div></div>";
                            $html+="</div>";// div del row
                            

                            //%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  CAMPO OCULTO DE MATRICULACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
                            $html+="<input id='matriculacion_id'  type='text' hidden readonly name='inscripcione_id' value='"+data.programacioncom.matriculacion_id +"'>";
                            $html+="<input id='programacioncom_id'  type='text' hidden readonly name='programacion_id' value='"+data.programacioncom.id +"'>";
                            //%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  CAMPO OCULTO DE DOCENTE %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
                            $html+="<div class='row'>";
                            $html+="<div class='col-xs-12 col-sm-12 col-md-6 col-lg-4'>";
                            $html+="<div class='form-floating mb-3 text-gray'>";
                            $html+="<select class='form-control @error('docente_id') is-invalid @enderror' name='docente_id' id='docente_id'>";
                            
                            for (let j in data.docentes) {
                                if(data.docentes[j].id==data.programacioncom.docente_id){
                                    $html+="<option  value='"+data.docentes[j].id +"' selected >"+data.docentes[j].nombrecorto+"</option>";
                                }else{
                                    $html+="<option  value='"+data.docentes[j].id +"'>"+data.docentes[j].nombrecorto+"</option>";
                                }
                            }
                            $html+="</select>";                
                            $html+="<label for='docente_id'>Docente</label></div></div>";

                            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  CAMPO AULA %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
                            $html+="<div class='col-xs-12 col-sm-12 col-md-6 col-lg-4'>";
                            $html+="<div class='form-floating mb-3 text-gray'>";
                            $html+="<select class='form-control @error('aula_id') is-invalid @enderror' name='aula_id' id='aula_id'>";
                            for (let j in data.aulas) {
                                if(data.aulas[j].id==data.programacioncom.aula_id){
                                    $html+="<option  value='"+data.aulas[j].id +"' selected >"+data.aulas[j].aula+"</option>";
                                }else{
                                    $html+="<option  value='"+data.aulas[j].id +"'>"+data.aulas[j].aula+"</option>";
                                }
                            }
                            $html+="</select>";                
                            $html+="<label for='aula_id'>Aula</label></div></div>";
                            $html+="</div>";// fin de row

                            $html+="<div class='row'>";
                            // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  CAMPO ESTADO EN VENTANA MODAL %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%                            
                            $html+="<div class='col-xs-12 col-sm-12 col-md-6 col-lg-6'>";
                            $html+="<div class='form-floating mb-3 text-gray'>";
                            $html+="<select class='form-control @error('estado_id') is-invalid @enderror'  name='estado_id' id='estado_id'>";
                            $html+="<option value=''> Elija estado</option>";

                             for (let k in data.estados) {
                                if(data.estados[k].id==data.programacioncom.estado_id){
                                    $html+="<option  value='"+data.estados[k].id +"' selected >"+data.estados[k].estado+"</option>";
                                }else{
                                    $html+="<option  value='"+data.estados[k].id +"'>"+data.estados[k].estado+"</option>";
                                }
                            }
                           
                            $html+="</select>";                
                            $html+="<label for='estado'>Estado</label></div></div>";
                        // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  CAMPO ACTIVO EN VENTANA MODAL %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
                            $html+="<div class='col-xs-12 col-sm-12 col-md-6 col-lg-6   '>";
                            $html+="<div class='form-floating mb-3 text-gray'>";
                            $html+="<select class='form-control @error('activo') is-invalid @enderror'  name='activo' id='activo'>";
                            $html+="<option value=''> Elija activo</option>";
                            if(data.programacioncom.activo == 1){
                                $html+="<option value='1'"+" selected>Activo</option>";
                            }else{
                                $html+="<option value='1'>Activo</option>";
                            }
                            if(data.programacioncom.activo == 0){
                                $html+="<option value='0'"+" selected>Inactivo</option>";
                            }else{
                                $html+="<option value='0'>Inactivo</option>"; 
                            }
                            $html+="</select>";                
                            $html+="<label for='activo'>activo</label></div></div>";

                            $html+="</div>";// div del row
                            
                            $html+="<div class='container-fluid h-100 mt-3'>"; 
                            $html+="<div class='row w-100 align-items-center'>";
                            $html+="<div class='col text-center'>";
                            $html+="<button type='submit' id='guardar' class='btn btn-primary text-white btn-lg'>Guardar <i class='far fa-save'></i></button> ";       
                            $html+="</div>";
                            $html+="</div>";
                            $html+="</div>";
                        
                            $("#formulario-editar").append($html);
                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },  
                });
            });
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% INICIO CREAR LICENCIA %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            $('table').on('click', '.licencia', function(e) {
                e.preventDefault(); 
                $html="";
                let id_programacioncom =$(this).closest('tr').attr('id');
                    $.ajax({
                        url : "../../licenciacom/crear/",
                        data : { id :id_programacioncom },
                        success : function(data) {
                            $("#formulario-licencia").empty();
                            $("#licencia-crear").modal("show");
                            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  CAMPO AULA %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
                            $html+="<div class='col-xs-12 col-sm-12 col-md-12 col-lg-12'>";
                            $html+="<div class='form-floating mb-3 text-gray'>";
                            $html+="<select class='form-control @error('motivo_id') is-invalid @enderror' name='motivo_id' id='motivo_id'>";
                            $html+="<option  value='' >Elije el motivo de la licencia </option>";
                            for (let j in data.motivos) {
                                // console.log(data.motivos[j]);
                                if(data.motivos[j].id==data.motivos.motivo_id){
                                    $html+="<option  value='"+data.motivos[j].id +"' selected >"+data.motivos[j].motivo+"</option>";
                                }else{
                                    $html+="<option  value='"+data.motivos[j].id +"'>"+data.motivos[j].motivo+"</option>";
                                }
                            }
                            $html+="</select>";                
                            $html+="<label for='motivo_id'>Motivo</label></div></div>";
                            $html+="</div>";// fin de row
                            $html+="<div class='row'>";
                            
                            $html+="<div class='col-xs-12 col-sm-12 col-md-12 col-lg-12'><div class='form-floating mb-3 text-gray'>";
                            $html+="<input type='text' autocomplete='off' list='apoderados' name='solicitante' class='form-control @error('solicitante') is-invalid @enderror texto-plomo' id='solicitante'"; 
                            $html+="value=\''\>";
                            $html+="<datalist id='apoderados'></datalist>";
                            
                            $html+="<label for='solicitante'>Nombre de persona Solicitante</label></div></div>";
                            $html+="</div>";// div del row


                            $html+="<div class='col-xs-12 col-sm-12 col-md-12 col-lg-12'>";
                            $html+="<div class='form-floating mb-3 text-gray'>";
                            $html+="<select class='form-control @error('parentesco') is-invalid @enderror' name='parentesco' id='parentesco'>";
                                $html+="<option  value='' >Elije un parentesco</option>";
                                $html+="<option  value='PAPA' >PAPA</option>";
                                $html+="<option  value='MAMA' >MAMA</option>";
                                $html+="<option  value='ESPOSO' >ESPOSO</option>";
                                $html+="<option  value='ESPOSA' >ESPOSA</option>";
                                $html+="<option  value='TIO' >TIO</option>";
                                $html+="<option  value='TIA' >TIA</option>";
                                $html+="<option  value='OTRO' >OTRO</option>";
                                $html+="<option  value='ELMISMO' >EL O ELLA MISMA</option>";
                            $html+="</select>";                
                            $html+="<label for='parentesco'>Parentesco</label></div></div>";
                            $html+="</div>";// fin de row
                            $html+="<div class='row'>";

                            //%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  CAMPO OCULTO DE MATRICULACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
                            $html+="<input id='matriculacion_id'  type='text' hidden readonly name='inscripcione_id' value='"+data.programacioncom.matriculacion_id +"'>";
                            $html+="<input id='programacioncom_id'  type='text' hidden readonly name='programacion_id' value='"+data.programacioncom.id +"'>";

                            $html+="<div class='container-fluid h-100 mt-3'>"; 
                            $html+="<div class='row w-100 align-items-center'>";
                            $html+="<div class='col text-center'>";
                            $html+="<button type='submit' id='guardar' class='btn btn-primary text-white btn-lg'>Guardar <i class='far fa-save'></i></button> ";       
                            $html+="</div>";
                            $html+="</div>";
                            $html+="</div>";
                            
                            $("#formulario-licencia").append($html);
                            $lista="";
                            for (let k in data.apoderados) {
                                $lista+="<option  value='"+data.apoderados[k].nombre+" "+data.apoderados[k].apellidop+" "+data.apoderados[k].apellidom+"("+data.apoderados[k].pivot.parentesco+")" +"'></option>";
                            }
                            console.log($("#apoderados").html());
                            $("#apoderados").append($lista);

                        },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },  
                });
            });
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% INICIO EDITAR LICENCIA %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            $('table').on('click', '.editarlicencia', function(e) {
                e.preventDefault(); 
                $htmleditar="";
                var licencia_id =$(this).closest('tr').attr('id');
                    console.log(licencia_id);

                    $.ajax({
                        url : "../../licenciacom/editar/"+licencia_id,
                        success : function(data) {
                            $("#formulario-licencia-editar").empty();
                            $("#licencia-editar").modal("show");
                            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  CAMPO AULA %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
                            $htmleditar+="<div class='col-xs-12 col-sm-12 col-md-12 col-lg-12'>";
                            $htmleditar+="<div class='form-floating mb-3 text-gray'>";
                            $htmleditar+="<select class='form-control @error('motivo_id') is-invalid @enderror' name='motivo_id' id='motivo_id'>";
                            $htmleditar+="<option  value='' >Elije el motivo de la licencia </option>";
                            for (let j in data.motivos) {
                                // console.log(data.motivos[j]);
                                if(data.motivos[j].id==data.motivo.id){
                                    $htmleditar+="<option  value='"+data.motivos[j].id +"' selected >"+data.motivos[j].motivo+"</option>";
                                }else{
                                    $htmleditar+="<option  value='"+data.motivos[j].id +"'>"+data.motivos[j].motivo+"</option>";
                                }
                            }
                            $htmleditar+="</select>";                
                            $htmleditar+="<label for='motivo_id'>Motivo</label></div></div>";
                            $htmleditar+="</div>";// fin de row
                            $htmleditar+="<div class='row'>";
                            $htmleditar+="<input type='number' hidden name='licencia_id' id='licencia_id' value='"+data.licencia.id+"'>";
                            
                            $htmleditar+="<div class='col-xs-12 col-sm-12 col-md-12 col-lg-12'><div class='form-floating mb-3 text-gray'>";
                            $htmleditar+="<input type='text' autocomplete='off' list='apoderados' name='solicitante' class='form-control @error('solicitante') is-invalid @enderror texto-plomo' id='solicitante'"; 
                            $htmleditar+="value='"+data.licencia.solicitante+"'>";
                            $htmleditar+="<datalist id='apoderados'></datalist>";
                            
                            $htmleditar+="<label for='solicitante'>Nombre de persona Solicitante</label></div></div>";
                            $htmleditar+="</div>";// div del row


                            $htmleditar+="<div class='col-xs-12 col-sm-12 col-md-12 col-lg-12'>";
                            $htmleditar+="<div class='form-floating mb-3 text-gray'>";
                            $htmleditar+="<select class='form-control @error('parentesco') is-invalid @enderror' name='parentesco' id='parentesco'>";
                                $htmleditar+="<option  value='' >Elije un parentesco</option>";
                                $htmleditar+="<option  value='"+data.licencia.parentesco+"' selected>"+data.licencia.parentesco+"</option>";
                                $htmleditar+="<option  value='PAPA' >PAPA</option>";
                                $htmleditar+="<option  value='MAMA' >MAMA</option>";
                                $htmleditar+="<option  value='ESPOSO' >ESPOSO</option>";
                                $htmleditar+="<option  value='ESPOSA' >ESPOSA</option>";
                                $htmleditar+="<option  value='TIO' >TIO</option>";
                                $htmleditar+="<option  value='TIA' >TIA</option>";
                                $htmleditar+="<option  value='OTRO' >OTRO</option>";
                                $htmleditar+="<option  value='ELMISMO' >EL O ELLA MISMA</option>";
                            $htmleditar+="</select>";                
                            $htmleditar+="<label for='parentesco'>Parentesco</label></div></div>";
                            $htmleditar+="</div>";// fin de row
                            $htmleditar+="<div class='row'>";

                            //%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  CAMPO OCULTO DE MATRICULACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
                            $htmleditar+="<input id='matriculacion_id'  type='text' hidden readonly name='inscripcione_id' value='"+data.programacioncom.matriculacion_id +"'>";
                            $htmleditar+="<input id='programacioncom_id'  type='text' hidden readonly name='programacion_id' value='"+data.programacioncom.id +"'>";

                            $htmleditar+="<div class='container-fluid h-100 mt-3'>"; 
                            $htmleditar+="<div class='row w-100 align-items-center'>";
                            $htmleditar+="<div class='col text-center'>";
                            $htmleditar+="<button type='submit' id='guardar' class='btn btn-primary text-white btn-lg'>Guardar <i class='far fa-save'></i></button> ";       
                            $htmleditar+="</div>";
                            $htmleditar+="</div>";
                            $htmleditar+="</div>";
                            
                            $("#formulario-licencia-editar").append($htmleditar);
                            $lista="";
                            for (let k in data.apoderados) {
                                $lista+="<option  value='"+data.apoderados[k].nombre+" "+data.apoderados[k].apellidop+" "+data.apoderados[k].apellidom+"("+data.apoderados[k].pivot.parentesco+")" +"'></option>";
                            }
                            //console.log($("#apoderados").html());
                            $("#apoderados").append($lista);

                        },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },  
                });
            });
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ACTUALIZAR ENVIO DE FORMULARIO PROGRAMACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            $(document).on("submit","#formulario-licencia",function(e){
                e.preventDefault();//detenemos el envio
            
                $motivo_id=$('#motivo_id').val();
                $solicitante=$('#solicitante').val();
                $parentesco=$('#parentesco').val();
                
                $matriculacion_id=$('#matriculacion_id').val();
                $programacioncom_id=$('#programacioncom_id').val();
                
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                $.ajax({
                    url : "../../licenciacom/guardar/",
                    data:{
                            motivo_id:$motivo_id,
                            solicitante:$solicitante,
                            parentesco:$parentesco,
                            matriculacion_id:$matriculacion_id,
                            programacioncom_id:$programacioncom_id,
                        },
                    
                    success : function(json) {
                         $("#error").empty();
                        if(json.errores.length>0){
                            $html="";
                             for (let j in json.errores) {
                                 $html+="<li>"+ json.errores[j] +"</li>";
                            }
                            $("#errordiv").removeClass('d-none');
                            $("#error").append($html);
                        }else{
                            let programacion_actualizar=$('#programacioncom_id').val(); 
                            $('#licencia-crear').modal('hide');
                            $('#futuro').DataTable().ajax.reload();
                            $("#"+programacion_actualizar).addTempClass( 'bg-success', 3000 );
                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 1500,
                                })
                                Toast.fire({
                                type: 'success',
                                title: 'Se guardó correctamente el registro'
                            })   
                        } 
                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
            });
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ACTUALIZAR ENVIO DE FORMULARIO LICENCIA %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            $(document).on("submit","#formulario-licencia-editar",function(e){
                e.preventDefault();//detenemos el envio
                $parentesco=$('#parentesco').val();
                $motivo_id=$('#motivo_id').val();
                $solicitante=$('#solicitante').val();
                $licencia_id=$('#licencia_id').val();
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url : "../../licencia/actualizar",
                    data:{
                            parentesco:$parentesco,
                            motivo_id:$motivo_id,
                            solicitante:$solicitante,
                            licencia_id:$licencia_id,
                        },
                    success : function(json) {
                        $("#licencia-editar").modal("hide");
                        $("#modal-mostrar-clase").modal("hide");

                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
            });
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ACTUALIZAR ENVIO DE FORMULARIO OBSERVACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            $(document).on("submit","#formulario-editar-observacion",function(e){
                e.preventDefault();//detenemos el envio
                $observacion=$('#observacionx').val();
                console.log($observacion);
                $observacion_id=$('#observacion_id').val();
                $programacioncom_id=$('#programacioncom_id').val();
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url : "../../observacion/actualizar/",
                    data:{
                            observacion:$observacion,
                            observacion_id:$observacion_id,
                            programacioncom_id:$programacioncom_id,
                        },
                    
                    success : function(json) {
                        let programacioncom_id=$('#programacioncom_id').val(); 
                        console.log(programacioncom_id);
                        
                        $('#editar-observacion').modal('hide');
                        $("#"+programacioncom_id).addTempClass( 'bg-success', 3000 );
                        //$('#futuro').DataTable().ajax.reload();
                       
                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
            });
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ACTUALIZAR ENVIO DE FORMULARIO PROGRAMACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            $(document).on("submit","#formulario-editar",function(e){
                e.preventDefault();//detenemos el envio
            
                $hora_inicio=$('#hora_ini').val();
                $hora_fin=$('#hora_fin').val();
                $fecha=$('#fecha').val();
                
                $habilitado=$('#habilitado').val();
                $estado_id=$('#estado_id').val();
                $activo=$('#activo').val();
                
                $horas_por_clase=$('#hora_por_clase').val();
                $docente_id=$('#docente_id').val();
               
                $aula_id=$('#aula_id').val();
                $matriculacion_id=$('#matriculacion_id').val();
                $programacioncom_id=$('#programacioncom_id').val();
                
                
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                $.ajax({
                    url : "../../programacioncom/actualizar/",
                    data:{
                            hora_ini:$hora_inicio,
                            hora_fin:$hora_fin,
                            fecha:$fecha,
                            habilitado:$habilitado,
                            estado_id:$estado_id,
                            activo:$activo,
                            horas_por_clase:$horas_por_clase,
                            docente_id:$docente_id,
                            aula_id:$aula_id,
                            matriculacion_id:$matriculacion_id,
                            programacioncom_id:$programacioncom_id,
                        },
                    
                    success : function(json) {
                        console.log(json);
                        let programacion_actualizar=$('#programacioncom_id').val(); 
                        $('#modal-editar').modal('hide');
                        $("#"+programacion_actualizar).addTempClass( 'bg-success', 3000 );
                        $('#futuro').DataTable().ajax.reload();
                        $('#tabla_hoy').DataTable().ajax.reload();
                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
            });
            /* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ELIMINAR  LICENCIA %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            $('table').on('click','.eliminarlicencia',function (e) {
                e.preventDefault(); 
                licencia_id=$(this).closest('tr').attr('id');
                console.log(licencia_id);
                Swal.fire({
                    title:'Estas seguro(a) de eliminar este registro?',
                    text:"Si eliminas el registro no lo podras recuperar jamás!",
                    type:'question',
                    showCancelButton: true,
                    showConfirmButton:true,
                    confirmButtonColor: '#25ff80',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Eliminar..!',
                    position:'center',        
                }).then((result) => {
                    if (result.value) {
                        $.ajax({
                            url: '../../eliminar/licencia/'+licencia_id,
                            type: 'DELETE',
                            data:{
                                _token:'{{csrf_token()}}'
                            },
                            success: function(result) {
                                console.log(result);
                                $("#"+licencia_id).remove();
                                const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 1500,
                                })
                                Toast.fire({
                                type: 'success',
                                title: 'Se eliminó correctamente el registro'
                                })   
                            },
                            error: function (xhr, ajaxOptions, thrownError) {
                                switch (xhr.status) {
                                    case 500:
                                        Swal.fire({
                                            title: 'No se completó esta operación por que este registro está relacionado con otros registros',
                                            showClass: {
                                                popup: 'animate__animated animate__fadeInDown'
                                            },
                                            hideClass: {
                                                popup: 'animate__animated animate__fadeOutUp'
                                            }
                                        })
                                        break;
                                
                                    default:
                                        break;
                                }
                                
                            }
                        });
                    }else{
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 4000,
                           //type
                            onOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                            }
                        })
                        Toast.fire({
                            type: 'error',
                            title: 'No se eliminó el registro'
                        })
                    }
                })
            });
             $('table').on('click','.eliminargenerico',function (e) {
                e.preventDefault(); 
                registro_id=$(this).closest('tr').attr('id');
                console.log(registro_id);
                url="../../eliminar/programacioncom/"+registro_id;
                eliminarRegistroURL(url,tablaFuturo);
            });

        });
</script>

@endsection

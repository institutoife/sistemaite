@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
    <link rel="stylesheet" href="{{asset('custom/css/custom.css')}}">
    <style>
        .insc-card { border: 1px solid #e9ecef; border-radius: .65rem; padding: .85rem; background: #fff; }
        .insc-card + .insc-card { margin-top: .75rem; }
        .insc-card-title { font-size: 1rem; font-weight: 700; color: #1f2d3d; }
        .insc-card-meta { font-size: .82rem; color: #6c757d; }
        .insc-chip { font-size: .75rem; padding: .2rem .5rem; border-radius: 999px; }
        .insc-card-actions a { font-size: .85rem; }
        .soft-danger { color: rgba(220, 53, 69, 0.75) !important; }
        .soft-danger-row { background-color: rgba(220, 53, 69, 0.12) !important; }
    </style>
@stop
@section('title', 'Inscripciones')

@section('plugins.Jquery',true)
@section('plugins.SweetAlert2',true)
@section('plugins.Datatables',true)

@section('content')
@php
    $esPadre = auth()->check() && auth()->user()->hasRole(['Padre']);
@endphp
{{ Breadcrumbs::render('inscripciones_estudiante', $persona->estudiante,$persona) }}

    <div class="card mb-3">
        <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between">
            <div>
                <h4 class="mb-1">Inscripciones de <strong>{{$persona->nombre.' '.$persona->apellidop.' '.$persona->apellidom }}</strong></h4>
                <div class="text-muted">Resumen de inscripciones vigentes y detalle de pagos.</div>
            </div>
            @if($esPadre)
                <span class="badge badge-info mt-2 mt-md-0"><i class="fas fa-eye mr-1"></i>Vista solo lectura</span>
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card-header bg-primary" >
            <div style="display: flex; justify-content: space-between; align-items: center;">

                <span id="card_title">
                    <i class="fas fa-book-open mr-2"></i>{{ __('Inscripciones VIGENTES') }}
                </span>

                @if(!$esPadre)
                    <div class="float-right">
                        <a href="{{ route('inscribir',$persona) }}" class="btn btn-secondary btn-sm float-right"  data-placement="left">
                        {{ __('Inscribir') }} <i class="fa fa-plus-circle text-white"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="d-block d-md-none mb-3" id="inscripciones-cards"></div>
            <div class="table-responsive d-none d-md-block">
                <table id="inscripcionesVigentes" class="table table-striped table-hover">
                    <thead class="thead">
                        <tr>
                            <th>#</th>
                            <th>Objetivo</th>
                            <th>PAGO</th>
                            <th>Fecha Pago</th>
                            <th>Modalidades</th>
                            <th>Opciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody> 
                </table>
            </div>
        </div>
    </div>
    @if($persona->computacion)
        <div class="card"> 
            <div class="card-header bg-primary" >
                <div style="display: flex; justify-content: space-between; align-items: center;">

                    <span id="card_title">
                        {{ __('MATRICULACIONES VIGENTES de: ')}} <strong> {{$persona->nombre.' '.$persona->apellidop.' '.$persona->apellidom }}</strong>
                    </span>

                    @if(!$esPadre)
                        <div class="float-right">
                            <a href="{{route('miscarreras.listar',$persona->computacion)}}" class="btn btn-primary btn-sm float-right"  data-placement="left">
                            {{ __('Matricular') }} <i class="fa fa-plus-circle text-white"></i>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="d-block d-md-none mb-3" id="matriculaciones-cards"></div>
                <div class="table-responsive d-none d-md-block">
                    <table id="matriculacionesVigentes" class="table table-striped table-hover">
                        <thead class="thead">
                            <tr>
                                <th>#</th>
                                <th>Objetivo</th>
                                <th>acuenta</th>
                                <th>costo</th>
                                <th>Nota</th>
                                <th>Opciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                        </tbody>
                    </table>
                    
                    @include('observacion.modalcreate')
                    @include('matriculacion.modaleditarnotas')
                    {{-- @include('inscripcione.modales') --}}
                    {{-- @include('observacion.modaleditar') --}}
                </div>
            </div>
        </div>
    @endif
@endsection
@section('js')

<script src="https://code.jquery.com/jquery-3.5.1.js"></script>

<script src="https://cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.23/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.7/js/responsive.bootstrap4.min.js"></script> 
<script src="{{asset('vendor/sweetalert/sweetalert.all.js')}}"></script>
<script src="{{asset('dist/js/moment.js')}}"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.17.1/locale/es.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>


    <script type="text/javascript" src="{{ asset('dist/js/jquery.leanModal.min.js')}}"></script>
    <script src="{{ asset('ckeditor/ckeditor.js') }}"></script>
    <script src="{{asset('assets/js/observacion.js')}}"></script>
    <script src="{{asset('assets/js/mensajeAjax.js')}}"></script>
    
    <script>
        let ser;
        let hacer;
        let saber;
        let decidir;
     //%%%%%%%%%%%%%%%%%%%%%%% INICIALIZA EL CKEDITOR CREAR OBSERVACION  %%%%%%%%%%%%%%%%%%%%%%%%%%%
        CKEDITOR.replace('editorguardar', {
            height: 120,
            width: "100%",
            removeButtons: 'PasteFromWord'
        });
        //%%%%%%%%%%%%%%%%%%%%%%% INICIALIZA EL CKEDITOR EDITAR OBSERVACION %%%%%%%%%%%%%%%%%%%%%%%%%%%
        CKEDITOR.replace('editoreditar', {
            height: 120,
            width: "100%",
            removeButtons: 'PasteFromWord'
        });

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
        %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%       JS INSCRIPCION   %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
        %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/


        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% CREAR OBSERVACION INSCIRPCION  %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.observacion', function (e) {
            e.preventDefault();
            let objeto_id = $(this).closest('tr').attr('id');
            console.log(objeto_id);
            $("#observable_id").val(objeto_id);
            $("#observable_type").val($(this).attr('id'));
            console.log("click en observacion crear");
            CKEDITOR.instances.editorguardar.setData("");
            $("diverror").addClass("d-none");
            $("#modal-agregar-observacion").modal("show");
        });
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% CLICK BOTON GUARDAR OBSERVACION INSCRIPCION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('#guardar-observacion').on('click', function (e) {
            e.preventDefault();
            console.log("click en guardar obsrvacion");
            let observable_id = $("#observable_id").val();
            console.log(observable_id);
            let observable_type = $("#observable_type").val();
            console.log(observable_type);
            for (instance in CKEDITOR.instances) { CKEDITOR.instances[instance].updateElement() }
            observacion=$("#editorguardar").val();
            console.log(observacion);
            url ="../guardar/observacion"
            guardarObservacion(observacion,observable_id,observable_type,url);
            
        });
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% MOSTRAR OBSERVACIONES INSCRIPCION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.mostrarobservacionesinscripcion', function(e) {
            e.preventDefault();
                observable_id =$(this).closest('tr').attr('id');
                console.log(observable_id);
                observable_type ="Inscripcione";
                url="../observaciones/" + observable_id + "/" + observable_type,
                console.log(url);
                mostrarCrudObservaciones(url);
                $("#modal-mostrar-observaciones").modal("show");
        });
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% DAR BAJA OBSERVACION INSCRIPCION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/

        $('table').on('click', '.bajaobservacion', function (e) {
            e.preventDefault();
            let observacion_id = $(this).closest('tr').attr('id');
            console.log(observacion_id);
            url="../darbaja/observacion";
            darBaja(observacion_id,url);
        });
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% DAR ALTA OBSERVACION INSCRIPCION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.altaobservacion', function (e) {
            e.preventDefault();
            let observacion_id = $(this).closest('tr').attr('id');
            url="../daralta/observacion";
            darAlta(observacion_id,url);
        });

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ELIMINAR OBSERVACION INSCRIPCION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.eliminarobservacion', function (e) {
            e.preventDefault();
            let observacion_id = $(this).closest('tr').attr('id');
            url="../eliminar/general"
            eliminarObservacion(observacion_id,url);
        });
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% EDITAR OBSERVACION INSCRIPCION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.editarobservacion', function (e) {
            e.preventDefault();
            observacion_id =$(this).closest('tr').attr('id');
            url="../observacion/editar";
            editarObservacion(observacion_id,url);
            $("#modal-mostrar-observaciones").modal("hide");
            $("#modal-editar-observacion").modal("show");
        });
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ACTUALIZAR OBSERVACION INSCRIPCION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('#actualizar-observacion').on('click', function (e){ 
            e.preventDefault();
            observacion_id =$("#observable_id").val();
            observacion=CKEDITOR.instances.editoreditar.getData();
            console.log(observacion);
            url="../observacion/actualizar";
            actualizarObservacion(observacion_id,observacion,url)
        });

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
        %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%       JS MATRICULACION   %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
        %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/

       
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% MOSTRAR OBSERVACIONES MATRICULACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.mostrarobservacionesmatriculacion', function(e) {
            e.preventDefault();
                observable_id =$(this).closest('tr').attr('id');
                console.log(observable_id);
                observable_type ="Matriculacion";
                url="../observaciones/" + observable_id + "/" + observable_type,
                console.log(url);
                mostrarCrudObservaciones(url);
                $("#modal-mostrar-observaciones").modal("show");
        });
    
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% CALIFICAR MATRICULACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.editarnotas', function(e) {
            e.preventDefault();
            matriculacion_id =$(this).closest('tr').attr('id');
            $.ajax({
                url:"{{url('editarnotas')}}",
                data:{
                    matriculacion_id:matriculacion_id,
                },
                success : function(json) {
                    $("#matriculacion_id").val(matriculacion_id);
                    $("#ser").val(json.matriculacion.ser);
                    $("#hacer").val(json.matriculacion.hacer);
                    saber=$("#saber").val(json.matriculacion.saber);
                    decidir=$("#decidir").val(json.matriculacion.decidir);
                    
                },
            }); 
            $("#modal-editarnotas").modal("show");
        });

        $("#form-editar-nota").on("submit", function( e ) {
            e.preventDefault();
            console.log("click");
            
                $.ajax({
                    url : "{{url('actualizarnota')}}",
                    data : $(this).serialize(),
                    success : function(json) {
                        console.log(json);
                        $("#modal-editarnotas").modal("hide");
                        mensajeGrande(json.mensaje,"success",2000)
                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
        });

         // if (json.errores) {
        //     $(".error").html(json.errores.observacion);
        //     $(".diverror").removeClass('d-none');
        // } else {
            
        //     const Toast = Swal.mixin({
        //         toast: true,
        //         position: 'top-end',
        //         showConfirmButton: false,
        //         timer: 3000,
        //     })
        //     Toast.fire({
        //         type: 'success',
        //         title: "Guardado corectamente: " + json.mensaje,
        //     })
        //     $("#modal-editar-observacion").modal("hide"); 
        // }
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
        %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%       JS GENERAL       %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
        %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        
        /*%%%%%%%%%%%%%%%%%%%%%  OCULTA DIVERROR DE MODAL CUANDO SE CIERRA MODAL GUARDAR %%%%%%*/
        $('#modal-agregar-observacion').on('hidden.bs.modal', function (e) {
            $("#diverror").addClass("d-none");
        });
        
        /*%%%%%%%%%%%%%%%%%%%%%  OCULTA DIVERROR DE MODAL CUANDO SE CIERRA MODAL EDITAR %%%%%%*/
        $('#modal-editar-observacion').on('hidden.bs.modal', function (e) {
            $(".diverror").addClass("d-none");
        });
        
        /*%%%%%%%%%%%%%%%%%%% JS GENERAL AGREGA UNA CLASE DE FORMA TEMPORAL %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
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

        $(document).ready(function() {
            function escapeEnrollmentText(value) { return $('<span>').text(value == null ? '' : String(value)).html(); }
            function buildEstadoBadge(estado, sucursal) {
                let clase = 'badge-secondary';
                let icono = "<i class='fas fa-info-circle mr-1'></i>";
                switch (estado) {
                    case "RESERVADO":
                        clase = "badge-warning";
                        icono = "<i class='fas fa-exclamation-triangle mr-1'></i>";
                        break;
                    case "CORRIENDO":
                        clase = "badge-success";
                        icono = "<i class='fas fa-running mr-1'></i>";
                        break;
                    case "CONGELADO":
                        clase = "badge-secondary";
                        icono = "<i class='fas fa-stop mr-1'></i>";
                        break;
                    case "FINALIZADO":
                        clase = "badge-success";
                        icono = "<i class='fas fa-hourglass-end mr-1'></i>";
                        break;
                    case "DESVIGENTE":
                        clase = "badge-danger";
                        icono = "<i class='fas fa-user-lock mr-1'></i>";
                        break;
                }
                return `<div class="d-inline-flex flex-wrap align-items-center"><span class="badge ${clase} insc-chip mr-1">${icono}${escapeEnrollmentText(estado || 'SIN ESTADO')}</span><span class="badge badge-light insc-chip" style="color:#375F7A">Sucursal: ${escapeEnrollmentText(sucursal || 'Sin registrar')}</span></div>`;
            }

            function renderInscripcionesCards(rows) {
                const $container = $('#inscripciones-cards');
                if (!$container.length) return;
                if (!rows || !rows.length) {
                    $container.html('<div class="text-muted">Sin inscripciones vigentes.</div>');
                    return;
                }

                const cards = rows.map(item => {
                    const detalleUrl = `/inscripciones/${item.id}`;
                    const imprimirUrl = `/imprimir/programa/${item.id}`;
                    const estadoBadge = buildEstadoBadge(item.estado, item.sucursal_origen);
                    const fechaPago = item.fecha_proximo_pago ? moment(item.fecha_proximo_pago).format('DD-MM-YYYY') : '-';
                    return `
                        <div class="insc-card">
                            <div class="d-flex flex-wrap align-items-center justify-content-between">
                                <div class="insc-card-title">${item.modalidad || 'Inscripción'} #${item.id}</div>
                                ${estadoBadge}
                            </div>
                            <div class="mt-2">${item.objetivo || ''}</div>
                            <div class="insc-card-meta mt-2">Próximo pago: ${fechaPago}</div>
                            <div class="insc-card-meta" id="insc-saldo-${item.id}">Cargando pagos...</div>
                            <div class="insc-card-actions mt-2">
                                <a class="btn btn-outline-primary btn-sm" href="${detalleUrl}"><i class="fa fa-eye mr-1"></i>Ver detalle</a>
                                <a class="btn btn-outline-secondary btn-sm" href="${imprimirUrl}"><i class="fas fa-print mr-1"></i>Imprimir</a>
                            </div>
                        </div>
                    `;
                }).join('');

                $container.html(cards);

                rows.forEach(item => {
                    $.ajax({
                        url: "{{url('saldo/inscripcion')}}",
                        data: { inscripcion_id: item.id },
                        success: function(json) {
                            $(`#insc-saldo-${item.id}`).html(`Acuenta: ${json.acuenta} • Costo: ${json.costo} • Saldo: ${json.saldo}`);
                        }
                    });
                });
            }

            function renderMatriculacionesCards(rows) {
                const $container = $('#matriculaciones-cards');
                if (!$container.length) return;
                if (!rows || !rows.length) {
                    $container.html('<div class="text-muted">Sin matriculaciones vigentes.</div>');
                    return;
                }

                const cards = rows.map(item => {
                    const detalleUrl = `/matriculacion/${item.id}`;
                    const imprimirUrl = `/imprimir/programacom/${item.id}`;
                    const estadoBadge = buildEstadoBadge(item.estado, item.sucursal_origen);
                    const fechaPago = item.fecha_proximo_pago ? moment(item.fecha_proximo_pago).format('DD-MM-YYYY') : '-';
                    return `
                        <div class="insc-card">
                            <div class="d-flex flex-wrap align-items-center justify-content-between">
                                <div class="insc-card-title">${item.asignatura || 'Matriculación'} #${item.id}</div>
                                ${estadoBadge}
                            </div>
                            <div class="insc-card-meta mt-2">Próximo pago: ${fechaPago}</div>
                            <div class="insc-card-meta" id="mat-saldo-${item.id}">Cargando pagos...</div>
                            <div class="insc-card-meta">Nota: ${item.calificacion ?? '-'}</div>
                            <div class="insc-card-actions mt-2">
                                <a class="btn btn-outline-primary btn-sm" href="${detalleUrl}"><i class="fa fa-eye mr-1"></i>Ver detalle</a>
                                <a class="btn btn-outline-secondary btn-sm" href="${imprimirUrl}"><i class="fas fa-print mr-1"></i>Imprimir</a>
                            </div>
                        </div>
                    `;
                }).join('');

                $container.html(cards);

                rows.forEach(item => {
                    $.ajax({
                        url: "{{url('saldo/matriculacion')}}",
                        data: { matriculacion_id: item.id },
                        success: function(json) {
                            $(`#mat-saldo-${item.id}`).html(`Acuenta: ${json.acuenta} • Costo: ${json.costo} • Saldo: ${json.saldo}`);
                        }
                    });
                });
            }
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%% DATATABLE INSCRIPCIONES VIGENTES %%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            let tabla=$('#inscripcionesVigentes').DataTable(
                {
                    "serverSide": true,
                    "responsive":true,
                    "autoWidth":false,
                    "ajax":{ 
                        "url":'../tusinscripciones',
                        "data":{
                            estudiante_id:"{{ $persona->estudiante->id }}",
                        },
                        // "success":function(json) {
                        //     console.log(json);
                        // }
                    },
                    "createdRow": function( row, data, dataIndex ) {
                    $(row).attr('id',data['id']); 
                        if (moment(data['fecha_proximo_pago']).format('YY-MM-DD')>moment().format('YY-MM-DD')){
                            $(row).addClass('text-success')
                            
                        }else{
                            $(row).addClass('text-success')
                            $('td', row).eq(2).addClass('soft-danger');
                            $('td', row).eq(3).addClass('soft-danger');
                        }

                        if (data['vigente']==0){
                            $(row).addClass('soft-danger-row')
                        }
                        $clase="";
                        switch (data['estado']) {
                            case "RESERVADO":
                                $clase="warning text-warning";
                                $icono="<i class='fas fa-exclamation-triangle'></i>";
                                break;
                            case "CORRIENDO":
                                $clase="success text-success";
                                $icono="<i class='fas fa-running'></i>";
                                break;
                            case "CONGELADO":
                                $clase="secondary text-secondary";
                                $icono="<i class='fas fa-stop'></i>";
                                break;
                            case "FINALIZADO":
                                $clase="success text-success";
                                $icono="<i class='fas fa-hourglass-end'></i>";
                                break;
                            case "DESVIGENTE":
                                $clase="danger text-danger";
                                $icono="<i class='fas fa-user-lock'></i>";
                                break;
                            default:
                                $clase="danger text-danger";
                                $icono="<i class='fas fa-info-circle'></i>";
                                break;
                        }

                        $('td', row).eq(1).html(data['objetivo']+"<br>"+buildEstadoBadge(data.estado, data.sucursal_origen));
                            $.ajax({
                                url:"{{url('saldo/inscripcion')}}",
                                data:{inscripcion_id:data['id']},
                                success : function(json) {
                                    $('td', row).eq(2).html('<strong>Acuenta: </strong>'+json.acuenta+'<br>'+'<strong>Costo: </strong>'+json.costo+'<br><strong>Saldo: </strong>'+json.saldo);  
                                    $('td', row).eq(3).html(json.fechaHumamizado+'<br>'+moment(data['fecha_proximo_pago']).format('DD-MM-YYYY'));  
                                },
                            }); 
                    },
                    "columns": [
                        {data:'id'},
                        {data:'objetivo'},
                        {data:'costo'},
                        {data:'fecha_proximo_pago'},
                        {data:'modalidad'},
                        {
                            "name":"btn",
                            "data": 'btn',
                            "orderable": false,
                        },
                    ],
                    order: [[0, 'desc']],
                    "columnDefs": [
                        { responsivePriority: 1, targets: 0 },  
                        { responsivePriority: 2, targets: -1 }
                    ],
                    "language":{
                        "url":"https://cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"
                    },
                    "paging":   true,
                }
            );
            $('#inscripcionesVigentes').on('xhr.dt', function(e, settings, json) {
                renderInscripcionesCards(json.data || []);
            });
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%% DATATABLE MATRICULACIONES VIGENTES %%%%%%%%%%%%%%%%%%%%%%%%%%%%*/    
            var tablamatriculaciones=$('#matriculacionesVigentes').dataTable(
                {
                    "serverSide":true,
                    "responsive":true,
                    "autoWidth":false,
                    "ajax":{
                        "url": '../tusmatriculaciones',
                        "data": function(d) {
                            d.estudiante_id = "{{$persona->estudiante->id}}";
                        }
                    },
                    "createdRow": function( row, data, dataIndex ) {
                    $(row).attr('id',data['id']); 
                        if (moment(data['fecha_proximo_pago']).format('YY-MM-DD')>moment().format('YY-MM-DD')){
                            $(row).addClass('text-success')
                        }else{
                            $(row).addClass('text-success')
                            $('td', row).eq(2).addClass('soft-danger');
                            $('td', row).eq(3).addClass('soft-danger');
                        }
                        if (data['vigente']==0){
                            $(row).addClass('soft-danger-row')
                        }
                         $clase="";
                        switch (data['estado']) {
                            case "RESERVADO":
                                $clase="warning text-warning";
                                $icono="<i class='fas fa-exclamation-triangle'></i>";
                                break;
                            case "CORRIENDO":
                                $clase="success text-success";
                                $icono="<i class='fas fa-running'></i>";
                                break;
                            case "CONGELADO":
                                $clase="secondary text-secondary";
                                $icono="<i class='fas fa-stop'></i>";
                                break;
                            case "FINALIZADO":
                                $clase="success text-success";
                                $icono="<i class='fas fa-hourglass-end'></i>";
                                break;
                            case "DESVIGENTE":
                                $clase="danger text-danger";
                                $icono="<i class='fas fa-user-lock'></i>";
                                break;
                            default:
                                $clase="danger text-danger";
                                $icono="<i class='fas fa-info-circle'></i>";
                                break;
                        }

                        $('td', row).eq(1).html(escapeEnrollmentText(data.asignatura)+"<br>"+buildEstadoBadge(data.estado, data.sucursal_origen));
                            $.ajax({
                                url:"{{url('saldo/matriculacion')}}",
                                data:{matriculacion_id:data['id']},
                                success : function(json) {
                                    $('td', row).eq(2).html('<strong>Acuenta:</strong>'+json.acuenta+'<br>'+'<strong>Costo: </strong>'+json.costo+'<br><strong>Saldo: </strong>'+json.saldo);  
                                    $('td', row).eq(3).html(json.fechaHumamizado+'<br>'+moment(data['fecha_proximo_pago']).format('DD-MM-YYYY'));  
                                },
                            }); 
                    },
                    "columns": [
                        {data:'id'},
                        {data:'asignatura'},
                        {data:'costo'},
                        {data:'fecha_proximo_pago'},
                        {data:'calificacion'},
                        {
                            "name":"btn",
                            "data": 'btn',
                            "orderable": false,
                        },
                    ],
                    "columnDefs": [
                        { responsivePriority: 1, targets: 1 },  
                        { responsivePriority: 2, targets: -1 }
                    ],
                    "language":{
                        "url":"https://cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"
                    },
                    "info":false, 
                    "searching":false,
                    "paging":true, 
                }
            );
            $('#matriculacionesVigentes').on('xhr.dt', function(e, settings, json) {
                renderMatriculacionesCards(json.data || []);
            });
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%  ELIMINAR INSCRIPCION %%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            $('table').on('click','.eliminarinscripcion',function (e) {
                e.preventDefault(); 
                id=$(this).parent().parent().parent().find('td').first().html();
                //console.log(id);
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
                            url: '../eliminar/inscripcion/'+id,
                            type: 'DELETE',
                            data:{
                                id:id,
                                _token:'{{ csrf_token() }}'
                            },
                            success: function(result) {
                                tabla.ajax.reload();
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
                                            title: 'No se pudo eliminar el registro Codigo error:500',
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
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%  ELIMINAR MATRICULACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            $('table').on('click','.eliminarmatriculacion',function (e) {
                e.preventDefault(); 
                id=$(this).parent().parent().parent().find('td').first().html();
                //console.log(id);
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
                            url: '../eliminar/matriculacion/'+id,
                            type: 'DELETE',
                            data:{
                                id:id,
                                _token:'{{ csrf_token() }}'
                            },
                            success: function(result) {
                                console.log(result);
                                tablamatriculaciones.api().ajax.reload();
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
                                            title: 'No se pudo eliminar el registro Codigo error:500',
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
        } );
    </script>
@stop

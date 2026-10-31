@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
    <link rel="stylesheet" href="{{asset('custom/css/custom.css')}}">
    {{-- <link href="{{asset('dist/css/zoomify.css')}}" rel="stylesheet" type="text/css"> --}}
@stop

@section('title', 'Estudiantes')
@section('plugins.Sweetalert2',true)
@section('plugins.Datatables',true)
@section('content')
    <div class="pt-4">
        <div class="card">
            <div class="card-header bg-primary">
                Lista de estudiantes ordenados <strong>Descendentemente </strong> por calificación
            </div>
            <div class="card-body">
                @isset($evento)
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <h4 class="alert-heading text-success">Evento seleccionado!</h4>
                        <hr>
                        <p class="mb-0">El evento seleccionado es {{$evento->evento}}</p>
                    </div>
                @else
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h4 class="alert-heading text-danger">No selecciono ningun evento!</h4>
                        <p class="text-danger">Es necesario seleeciona un evento click en el boton siguiente para seleccionar un evento </p>
                        <hr>
                        <a href="{{route('eventos.index')}}"> <button class="btn btn-primary" type="button">Ir a Eventos</button> </a>
                    </div>
                @endisset
                    
                <table id="mensajeados" class="text-center table table-bordered table-hover table-striped">
                    <thead class="text-center">
                        <tr>
                            <th>ID</th>
                            <th>NOMBRE</th>
                            <th>APELLIDOP</th>
                            <th>APELLIDOM</th>
                            <th>FOTO</th>
                            <th>VUELVE</th>
                            <th>OBSERVACION</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
        @include('telefono.modales')
        @include('observacion.modalcreate')
        @include('telefono.modales')
        @include('mensaje.masivo.modales')
        
    </div>
    
    {{-- <x-header variable="Una Variable" >
        Lorem ipsum dolor sit amet consectetur adipisicing elit. Veritatis sed ipsum, voluptates perspiciatis cupiditate est ex aliquid in repellendus tenetur aut libero hic natus, dolor inventore consectetur sequi labore asperiores!
    </x-header> --}}
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="http://cdnjs.cloudflare.com/ajax/libs/jquery/1.11.1/jquery.js"></script>
    
    
    {{-- <script src="js/star-rating.js" type="text/javascript"></script> --}}
    <script src="http://libs.baidu.com/jquery/1.10.2/jquery.min.js"></script>
    <script src="{{asset('dist/js/starrr.js')}}"></script>

    <script src="https://cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.23/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/responsive.bootstrap4.min.js"></script> 

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.1.5/js/plugins/piexif.min.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.1.5/js/plugins/sortable.min.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.1.5/js/fileinput.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.1.5/themes/fas/theme.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.1.5/js/locales/es.js"></script>
    
    <script type="text/javascript" src="{{ asset('dist/js/jquery.leanModal.min.js')}}"></script>
    
    <script src="{{ asset('ckeditor/ckeditor.js') }}"></script>
    <script src="{{asset('assets/js/observacion.js')}}"></script>

    <script src="{{asset('dist/js/moment.js')}}"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.17.1/locale/es.js"></script>
    
    <script>
        let tablacontactos;
        let tablamensajeados;
        //let tabla_deudores_matriculacion;
        //%%%%%%%%%%%%%%%%%%%%%%% INICIALIZA EL CKEDITOR %%%%%%%%%%%%%%%%%%%%%%%%%%%
        CKEDITOR.replace('editorguardar', {
            height: 120,
            width: "100%",
            removeButtons: 'PasteFromWord'
        });
        //%%%%%%%%%%%%%%%%%%%%%%% INICIALIZA EL CKEDITOR %%%%%%%%%%%%%%%%%%%%%%%%%%%
        CKEDITOR.replace('editoreditar', {
            height: 120,
            width: "100%",
            removeButtons: 'PasteFromWord'
        });
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% CREAR OBSERVACION  %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.observacion', function (e) {
            e.preventDefault();
            let objeto_id = $(this).closest('tr').attr('id');
            $("#observable_id").val(objeto_id);
            $("#observable_type").val($(this).attr('id'));
            CKEDITOR.instances.editorguardar.setData("");
            $("#modal-agregar-observacion").modal("show");
        });
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% CLICK BOTON GUARDAR OBSERVACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('#guardar-observacion').on('click', function (e) {
            e.preventDefault();
            let observable_id = $("#observable_id").val();
            let observable_type = $("#observable_type").val();
            for (instance in CKEDITOR.instances) { CKEDITOR.instances[instance].updateElement() }
            observacion=$("#editorguardar").val();
            url = "../../guardar/observacion"
            guardarObservacion(observacion,observable_id,observable_type,url);
            
        });
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% MOSTRAR OBSERVACIONES %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.mostrarobservacionespersona', function(e) {
            e.preventDefault();
                observable_id =$(this).closest('tr').attr('id');
                observable_type ="Persona";
                url="../../observaciones/" + observable_id + "/" + observable_type,
                mostrarCrudObservaciones(url);
                $("#modal-mostrar-observaciones").modal("show");
        });


        $('table').on('click', '.bajaobservacion', function (e) {
            e.preventDefault();
            let observacion_id = $(this).closest('tr').attr('id');
            url="../../darbaja/observacion";
            darBaja(observacion_id,url);
        });
        
        $('table').on('click', '.altaobservacion', function (e) {
            e.preventDefault();
            let observacion_id = $(this).closest('tr').attr('id');
            url="../../daralta/observacion";
            darAlta(observacion_id,url);
        });

        $('table').on('click', '.eliminarobservacion', function (e) {
            e.preventDefault();
            let observacion_id = $(this).closest('tr').attr('id');
            url="../../eliminar/general"
            eliminarObservacion(observacion_id,url);
        });
        $('table').on('click', '.editarobservacion', function (e) {
            e.preventDefault();
            observacion_id =$(this).closest('tr').attr('id');
            url="../../observacion/editar";
            editarObservacion(observacion_id,url);
            $("#modal-mostrar-observaciones").modal("hide");
            $("#modal-editar-observacion").modal("show");
        });

        $('#actualizar-observacion').on('click', function (e){ 
            e.preventDefault();
            observacion_id =$("#observable_id").val();
            observacion=CKEDITOR.instances.editoreditar.getData();
            url="../../observacion/actualizar";
            actualizarObservacion(observacion_id,observacion,url);
        });
        /**%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% FECHAR PROXIMO PAGO DE INSCRIPCION  %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.recalificar', function(e) {
            e.preventDefault();
            persona_id =$(this).closest('tr').attr('id');
            $.ajax({
                url : "../../get/calificacion",
                data:{
                    persona_id:persona_id,
                },
                success : function(json) {
                    if(json.calificado=="SI"){
                        /*%%%%%% actualiza %%%%%%%%%%%%%%%%%%*/
                        $("#id_persona").val(json.calificacion.persona_id);
                        $("#calificacion_id").val(json.calificacion.id);
                        $("#calificacion option[value="+ json.calificacion.calificacion +"]").attr("selected",true);
                        $("#storeupdate").val("actualizar");
                        $("#modal-editar-calificacion").modal("show");
                    }else{
                        /*%%%%%%%%%%%%%%%%%  por este lado guarda nuevo %%%%%%%%%%%%*/
                        $("#id_persona").val(persona_id);
                        $("#storeupdate").val("guardar");
                        $("#modal-editar-calificacion").modal("show");
                        
                    }
                },
                error : function(xhr, status) {
                    alert('Disculpe, existió un problema');
                },
            });
        }); 

        $('#btn-calificar').on('click', function (e) {
            e.preventDefault();
            persona_id=$("#id_persona").val();
            calificacion=$("#calificacion").val();
            calificacion_id=$("#calificacion_id").val();
            storeupdate=$("#storeupdate").val();
            $.ajax({
                url : "../../set/calificacion",
                data:{
                    persona_id:persona_id,
                    calificacion:calificacion,
                    calificacion_id:calificacion_id,
                    storeupdate:storeupdate,
                },
                success : function(json) {
                    $("#modal-editar-calificacion").modal("hide");
                },
                error : function(xhr, status) {
                    alert('Disculpe, existió un problema');
                },
            });
        })
        

        $('table').on('click', '.fechar', function(e) {
            e.preventDefault();
            persona_id =$(this).closest('tr').attr('id');
            $(this).closest('tr').addTempClass('bg-success', 3000)
            $("#modal-fechar").modal("show");
            $("#persona_id").val(persona_id);
        }); 

        // $('table').on('click', '.mensajeado', function(e) {
        //     e.preventDefault();
        //     persona_id =$(this).closest('tr').attr('id');
        //     $(this).closest('tr').addTempClass('bg-success', 3000)
        //     $.ajax({
        //         url : "../store/mensajeado",
        //         data:{
        //             persona_id:persona_id,
        //         },
        //         success : function(json) {
        //             masivocontactar.ajax.reload();

        //         },
        //         error : function(xhr, status) {
        //             alert('Disculpe, existió un problema');
        //         },
        //     });
        // }); 
        
        /**%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% GUARDAR LA FECHA O HACE AGENDAR INSCRIPCION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        
        /**%%%%%%%%%%%%%%%% GUARDAR LA FECHA O HACE AGENDAR DE MATRICULACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('#agendar').on('click', function (e) {
            e.preventDefault();
            vuelvefecha = $("#vuelvefecha").val();
            persona_id = $("#persona_id").val();
            $.ajax({
                url: "../../persona/actualizar/vuelvefecha",
                data: {
                    vuelvefecha:vuelvefecha,
                    persona_id:persona_id,
                },
                success: function (json) {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                        })
                        Toast.fire({
                            type: 'success',
                            title: "Se actualizó correctamente la fecha próximo pago ",
                        })
                        $("#modal-fechar").modal("hide");
                        tablamensajeados.ajax.reload();
                    
                },
                error: function (xhr, status) {
                    alert('Disculpe, existió un problema');
                },
            });
            
        });
       

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% mostrar contacto con componentes  INICIO %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        
        $('table').on('click', '.enviarmensaje', function(e) {
            e.preventDefault();
                persona_id =$(this).closest('tr').attr('id');
                url="../../persona/enviar/mensaje/componente",
                mensaje_id=5;
                mostrarContactos(url,persona_id,mensaje_id);
                $("#modal-listar-contactos-component").modal("show");
        });

        function mostrarContactos(url,persona_id,mensaje_id) {
            let mensaje;
            $.ajax({
                url : "../../mensaje/generico",
                data:{
                    mensaje_id:mensaje_id,
                    persona_id:persona_id,
                },
                success : function(json) {
                    mensaje=json.mensaje;
                    if(json.persona.telefono!=0){
                        $("#personal").attr('href','https://api.whatsapp.com/send?phone=591'+json.persona.telefono+'&text='+mensaje.mensaje);
                        $("#personal").attr('target','_blank');
                        $("#personal").text('télefono personal '+json.persona.telefono);
                        $("#personal").show().fadeIn(2000);
                    }else{
                        $("#personal").hide().slideUp(2000);
                    }
                },
                error : function(xhr, status) {
                    alert('Disculpe, existió un problema');
                },
            });

            $("#contactos").dataTable().fnDestroy();
                tablacontactos = $('#contactos').DataTable(
                {
                    "serverSide": true,
                    "responsive": true,
                    "autoWidth": false,
                    "targets": 0,
                    "ajax": {
                        "url": url,
                        "data":{
                            persona_id:persona_id,
                        },
                    },
                    "createdRow": function (row, data, dataIndex) {
                        $(row).attr('id', data['id']); // agrega dinamiacamente el id del row
                        $('td', row).eq(3).html(data.pivot.parentesco);
                        $('td', row).eq(5).children('.cargarmensaje').attr('href','https://api.whatsapp.com/send?phone=591'+data['telefono']+'&text='+mensaje.mensaje);
                        $('td', row).eq(4).html(moment(data['updated_at']).format('DD-MM-YYYY'));
                        if (data['telefono'] == 0) {
                            $(row).addClass('text-danger');
                        } else {
                            $(row).addClass('text-success');
                        }
                    },
                    "columns": [
                        { data: 'id' },
                        { data: 'nombre' },
                        { data: 'apellidop' },
                        { data: 'telefono' },
                        { data: 'telefono' },
                        {
                            "name": "btn",
                            "data": 'btn',
                            "orderable": false,
                        },
                    ],
                    "columnDefs": [
                        { responsivePriority: 1, targets: 0 },  
                        { responsivePriority: 2, targets: -1 }
                    ],
                    "language": {
                        "url": "https://cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"
                    },
                    "order": [[3, "desc"]]
                });
            /*%%%%%%%%%%%%%%% ENUMARA LA PRIMER COLUMNA %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            tablacontactos.on('order.dt search.dt', function () {
                tablacontactos.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
                    cell.innerHTML = i + 1;
                });
            }).draw();
        }
        /*%%%%%%%%%%%%%%% FIN MOSTRAR CONTACTOS CON COMPONENTES %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        

        $('#modal-agregar-observacion').on('hidden.bs.modal', function (e) {
            $("#diverror").addClass("d-none");
        });
        
        $('#modal-editar-observacion').on('hidden.bs.modal', function (e) {
            $(".diverror").addClass("d-none");
        });
        

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
        
        $('.starrr').starrr({
            max: 5,
            change: function(e, value){
                if (value) {
                    $("#calificacion").val(value);
                } else {
                    $('.your-choice-was').hide();
                }
                
            }
        });
        /*%%%%%%%%%%%%%%%%%%%%%%%%%% DATATABLE PERSONAS ESTUDIANTES %%%%%%%%%%%%%%%%%%%%*/
        $(document).ready(function() {
            tablamensajeados=$('#mensajeados').DataTable(
                {
                    "serverSide": true,
                    "responsive":true,
                    "autoWidth":false,

                    "ajax": "../../mensajeados/{{$evento->id}}",
                    "createdRow": function( row, data, dataIndex ) {
                        $(row).attr('id',data['id']); 
                        if (moment(data['vuelvefecha']).format('YY-MM-DD')>moment().format('YY-MM-DD')){
                            $(row).addClass('text-success')
                        }else{
                            $(row).addClass('text-danger')
                        }
                        persona_id = data['id'];
                        $.ajax({
                            url:"{{url('persona/ultimaobservacion')}}",
                            data:{persona_id:persona_id},
                            success : function(json) {
                                $('td', row).eq(6).html(json.observacion.observacion +'('+ json.usuario.name +')');  
                            },
                        });
          
                    },
                    "columns": [
                        {data: 'id'},
                        {data: 'nombre'},
                        {data: 'apellidop'},
                        {data: 'apellidom'},
                        
                        {
                            "name": "foto",
                            "data": "foto",
                            "render": function (data, type, full, meta) {
                                return IFEPhotos.render(data,type,full,{height:50});
                            },
                            "title": "FOTO",
                            "orderable": false,
            
                        },
                        {data: 'vuelvefecha'},
                        {data: 'volvera'},
                        {
                            "name":"btn",
                            "data": 'btn',
                            "orderable": false,
                        },
                    ],

                    "language":{
                        "url":"https://cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"
                    },  
                }
            );

            $('table').on('click','.zoomify',function (e){
                Swal.fire({
                    title: 'Codigo: '+ $(this).closest('tr').find('td').eq(0).text(),
                    text: $(this).closest('tr').find('td').eq(1).text(),
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
        });
    </script>
@stop
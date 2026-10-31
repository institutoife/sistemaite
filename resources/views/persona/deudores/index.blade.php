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
                Lista de deudores de cursos de nivelación: <strong> Inscripciones </strong>
            </div>
            
            <div class="card-body">
                <table id="deudores" class="text-center table table-bordered table-hover table-striped">
                    <thead class="text-center">
                        <tr>
                            <th>ID</th>
                            <th>NOMBRE</th>
                            <th>APELLIDOP</th>
                            <th><strong class="text-success">ACUENTA</strong>+<strong class="text-danger">SALDO</strong>=TOTAL</th>
                            <th>FECHAPAGO</th>
                            <th>FOTO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-primary">
                Lista de deudores de computación <strong> Matriculaciones </strong>
            </div>
            
            <div class="card-body">
                <table id="deudorescomputacion" class="text-center table table-bordered table-hover table-striped">
                    <thead class="text-center">
                        <tr>
                            <th>ID</th>
                            <th>NOMBRE</th>
                            <th>APELLIDOP</th>
                            <th><strong class="text-success">ACUENTA</strong>+<strong class="text-danger">SALDO</strong>=TOTAL</th>
                            <th>FECHAPAGO</th>
                            <th>ASIGNAURA</th>
                            <th>FOTO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
        @include('observacion.modalcreate')
        @include('telefono.modales')
        @include('persona.deudores.modales')
       
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
        let tabla_deudores_inscripcion;
        let tabla_deudores_matriculacion;
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
            console.log(objeto_id);
            $("#observable_id").val(objeto_id);
            $("#observable_type").val($(this).attr('id'));
            CKEDITOR.instances.editorguardar.setData("");
            console.log("Click en Observacion crear");
            $("#modal-agregar-observacion").modal("show");
        });
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% CLICK BOTON GUARDAR OBSERVACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('#guardar-observacion').on('click', function (e) {
            e.preventDefault();
            console.log("click en guardar obsrvacion");
            let observable_id = $("#observable_id").val();
            console.log(observable_id);
            let observable_type = $("#observable_type").val();
            console.log(observable_type);
            for (instance in CKEDITOR.instances) { CKEDITOR.instances[instance].updateElement() }
            observacion=$("#editorguardar").val();
            url = "../guardar/observacion"
            guardarObservacion(observacion,observable_id,observable_type,url);
            
        });
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% MOSTRAR OBSERVACIONES %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.mostrarobservacionespersona', function(e) {
            e.preventDefault();
                observable_id =$(this).closest('tr').attr('id');
                observable_type ="Persona";
                url="../observaciones/" + observable_id + "/" + observable_type,
                mostrarCrudObservaciones(url);
                $("#modal-mostrar-observaciones").modal("show");
        });


        $('table').on('click', '.bajaobservacion', function (e) {
            e.preventDefault();
            let observacion_id = $(this).closest('tr').attr('id');
            console.log(observacion_id);
            url="../darbaja/observacion";
            darBaja(observacion_id,url);
        });
        
        $('table').on('click', '.altaobservacion', function (e) {
            e.preventDefault();
            let observacion_id = $(this).closest('tr').attr('id');
            url="../daralta/observacion";
            darAlta(observacion_id,url);
        });

        $('table').on('click', '.eliminarobservacion', function (e) {
            e.preventDefault();
            let observacion_id = $(this).closest('tr').attr('id');
            url="../eliminar/general"
            eliminarObservacion(observacion_id,url);
        });
        $('table').on('click', '.editarobservacion', function (e) {
            e.preventDefault();
            observacion_id =$(this).closest('tr').attr('id');
            url="../observacion/editar";
            editarObservacion(observacion_id,url);
            $("#modal-mostrar-observaciones").modal("hide");
            $("#modal-editar-observacion").modal("show");
        });

        $('#actualizar-observacion').on('click', function (e){ 
            e.preventDefault();
            observacion_id =$("#observable_id").val();
            observacion=CKEDITOR.instances.editoreditar.getData();
            url="../observacion/actualizar";
            actualizarObservacion(observacion_id,observacion,url);
        });
        /**%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% FECHAR PROXIMO PAGO DE INSCRIPCION  %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.fechar', function(e) {
            e.preventDefault();
            inscripcione_id =$(this).attr('id');
            $(this).closest('tr').addTempClass('bg-success', 3000)
            $("#fecha_proximo_pago").val()
            $("#modal-fechar").modal("show");
            $("#inscripcione_id").val(inscripcione_id);
            
        }); 
           /**%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% GUARDAR LA FECHA O HACE AGENDAR INSCRIPCION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('#agendar').on('click', function (e) {
            e.preventDefault();
            $("#errores").empty();
            let fecha_proximo_pago = $("#fecha_proximo_pago").val();
            console.log(fecha_proximo_pago);
            let inscripcione_id = $("#inscripcione_id").val();
            console.log(inscripcione_id);
            $.ajax({
                url: "../inscripcion/actualizar/fecha_proximo_pago",
                data: {
                    fecha_proximo_pago:fecha_proximo_pago,
                    inscripcione_id:inscripcione_id,
                },
                success: function (json) {
                        console.log(json);
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
                        
                           
                        tabla_deudores_inscripcion.ajax.reload();
                    
                },
                error: function (xhr, status) {
                    alert('Disculpe, existió un problema');
                },
            });
            
        });
        /*%%%%%%%%%%%%%%%%%%%%%%%%% FECHAR MATRICULACION PROXIMO PAGO %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.fechar', function(e) {
            e.preventDefault();
            inscripcione_id =$(this).attr('id');
            $(this).closest('tr').addTempClass('bg-success', 3000)
            $("#fecha_proximo_pago").val()
            $("#modal-fechar").modal("show");
            $("#inscripcione_id").val(inscripcione_id);
        }); 
           /**%%%%%%%%%%%%%%%% GUARDAR LA FECHA O HACE AGENDAR DE MATRICULACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('#agendar').on('click', function (e) {
            e.preventDefault();
            $("#errores").empty();
            let fecha_proximo_pago = $("#fecha_proximo_pago").val();
            console.log(fecha_proximo_pago);
            let inscripcione_id = $("#inscripcione_id").val();
            console.log(inscripcione_id);
            $.ajax({
                url: "../inscripcion/actualizar/fecha_proximo_pago",
                data: {
                    fecha_proximo_pago:fecha_proximo_pago,
                    inscripcione_id:inscripcione_id,
                },
                success: function (json) {
                        console.log(json);
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
                        
                           
                        tabla_deudores_inscripcion.ajax.reload();
                    
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
                url="../persona/enviar/mensaje/componente",
                mensaje_id=4;
                mostrarContactos(url,persona_id,mensaje_id);
                $("#modal-listar-contactos-component").modal("show");
        });

        function mostrarContactos(url,persona_id,mensaje_id) {
            let mensaje;
            $.ajax({
                url : "../mensaje/generico",
                data:{
                    mensaje_id:mensaje_id,
                    persona_id:persona_id,
                },
                success : function(json) {
                    console.log(json);
                    mensaje=json.mensaje;
                    $("#personal").attr('href','https://api.whatsapp.com/send?phone=591'+json.persona.telefono+'&text='+mensaje.mensaje);
                    $("#personal").attr('target','_blank');
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
            tabla_deudores_inscripcion=$('#deudores').DataTable(
                {
                    "serverSide": true,
                    "responsive":true,
                    "autoWidth":false,

                    "ajax": "{{ url('deudores/inscripcion') }}",
                    "createdRow": function( row, data, dataIndex ) {
                        $(row).attr('id',data['id']); // agrega dinamiacamente el id del row
                        $('td', row).eq(3).html("<strong class='text-success'>"+data['acuenta']+"</strong>+"+"<strong class='text-danger'>"+parseInt(data['costo']-data['acuenta'])+"</strong>"+"="+data['costo']);
                        // $('td', row).eq(5).html(data['fechafin']);
                        if(moment(data['fecha_proximo_pago']).format('YY-MM-DD')>moment().format('YY-MM-DD'))
                            $(row).addClass('text-success');
                        else
                            $(row).addClass('text-danger');
                        
                    },
                    "columns": [
                        {data: 'id'},
                        {data: 'nombre'},
                        {data: 'apellidop'},
                        {data: 'acuenta'},
                        {data: 'fecha_proximo_pago'},
                        {
                            "name": "foto",
                            "data": "foto",
                            "render": function (data, type, full, meta) {
                                return IFEPhotos.render(data,type,full,{height:50});
                            },
                            "title": "FOTO",
                            "orderable": false,
            
                        },     
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
         
            tabla_deudores_matriculacion=$('#deudorescomputacion').DataTable(
                {
                    "serverSide": true,
                    "responsive":true,
                    "autoWidth":false,

                    "ajax": "{{ url('deudores/matriculacion') }}",
                    "createdRow": function( row, data, dataIndex ) {
                        $(row).attr('id',data['id']); // agrega dinamiacamente el id del row
                        $('td', row).eq(3).html("<strong class='text-success'>"+data['acuenta']+"</strong>+"+"<strong class='text-danger'>"+parseInt(data['costo']-data['acuenta'])+"</strong>"+"="+data['costo']);
                        // $('td', row).eq(5).html(data['fechafin']);
                        if(moment(data['fecha_proximo_pago']).format('YY-MM-DD')>moment().format('YY-MM-DD'))
                            $(row).addClass('text-success');
                        else
                            $(row).addClass('text-danger');
                        
                    },
                    "columns": [
                        {data: 'id'},
                        {data: 'nombre'},
                        {data: 'apellidop'},
                        {data: 'acuenta'},
                        {data: 'fecha_proximo_pago'},
                        {data: 'asignatura'},
                        {
                            "name": "foto",
                            "data": "foto",
                            "render": function (data, type, full, meta) {
                                return IFEPhotos.render(data,type,full,{height:50});
                            },
                            "title": "FOTO",
                            "orderable": false,
            
                        },     
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
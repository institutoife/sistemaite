@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
    <link rel="stylesheet" href="{{asset('custom/css/custom.css')}}">
@stop
@section('title', 'Finalizando')
@section('plugins.Jquery',true)
@section('plugins.SweetAlert2',true)
@section('plugins.Datatables',true)

@section('content')
    <div class="card">
        <div class="card-header bg-primary" >
            <div style="display: flex; justify-content: space-between; align-items: center;">
                ESTUDIANTES ESTAN EMPEZANDO SUS INSCRIPCIONES
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="inscripcionesempezando" class="table table-striped table-hover">
                    <thead class="thead">
                        <tr>
                            <th>NOMBRE</th>
                            <th>APELLIDOP</th>
                            <th>APELLIDOM</th>
                            <th>FECHAINI</th>
                            <th>USUARIO</th>
                            <th>FOTO</th>
                            <th>OPTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody> 
                </table>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header bg-primary" >
            <div style="display: flex; justify-content: space-between; align-items: center;">
                ITENAUTAS QUE ESTAN EMPEZANDO
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="matriculacionempezando" class="table table-striped table-hover">
                    <thead class="thead">
                        <tr>
                            <th>NOMBRE</th>
                            <th>APELLIDOP</th>
                            <th>ASIGNATURA</th>
                            <th>FECHAINI</th>
                            <th>USUARIO</th>
                            <th>FOTO</th>
                            <th>OPTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody> 
                </table>
            </div>
        </div>
    </div>
        @include('estudiantes.modal')
        @include('telefono.modales')
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
<script src="https://cdn.ckeditor.com/4.19.0/standard-all/ckeditor.js"></script>
<script src="{{asset('assets/js/observacion.js')}}"></script>
<script src="{{asset('assets/js/mensajeAjax.js')}}"></script>
    
    <script>

       
        
        $(document).ready(function() {
            let tablainscripciones;
            let tablamatriculaciones;
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%% DATATABLE INSCRIPCIONES VIGENTES %%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
                tablainscripciones=$('#inscripcionesempezando').DataTable(
                    {
                        "serverSide": true,
                        "responsive":true,
                        "autoWidth":false,
                        "ajax":{ 
                            "url":'../estudiantes/empezando',
                        },
                        "createdRow": function( row, data, dataIndex ) {
                            $(row).attr('id',data['id']); 
                            $('td',row).eq(3).html(moment(data['fechaini']).format('DD-MM-YYYY'));
                        },
                        "columns": [
                            {data:'nombre'},
                            {data:'apellidop'},
                            {data:'apellidom'},
                            {data:'fechaini'},
                            {data:'usuario'},
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
                        order: [[0, 'desc']],
                        "language":{
                            "url":"https://cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"
                        },
                        "paging":   true,
                    }
                );
                /*%%%%%%%%%%%%%%%%%%%%%%%%%%% DATATABLE MATRICULACIONES VIGENTES %%%%%%%%%%%%%%%%%%%%%%%%%%%%*/    
                tablamatriculaciones=$('#matriculacionempezando').dataTable(
                    {
                        "serverSide":true,
                        "responsive":true,
                        "autoWidth":false,
                        "ajax":{ 
                            "url":'../computacion/empezando',
                        },
                        "createdRow": function( row, data, dataIndex ) {
                            $(row).attr('id',data['id']); 
                            $('td',row).eq(3).html(moment(data['fechaini']).format('DD-MM-YYYY'));
                        },
                        "columns": [
                            {data:'nombre'},
                            {data:'apellidop'},
                            {data:'asignatura'},
                            {data:'fechaini'},
                            {data:'usuario'},
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
                        "columnDefs": [
                            { responsivePriority: 1, targets: 0 },  
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

                /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
                /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
                /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% mostrar contacto con componentes  INICIO %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
                /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
                /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
                $('table').on('click', '.mensajeable_inscripcion', function(e) {
                    e.preventDefault();
                        persona_id =$(this).closest('tr').attr('id');
                        mensaje_id=8;
                        mensajeable_id = $(this).attr('id');
                        mensajeable_type='Inscripcione';
                        url="../mensajeble/store";
                        $.ajax({
                            url :url,
                            data:{
                                mensaje_id:mensaje_id,
                                mensajeable_id:mensajeable_id,
                                mensajeable_type:mensajeable_type,
                                persona_id:persona_id,
                            },
                            success : function(json) {
                                mensajeGrande(json.mensaje,'success',2000)
                                tablainscripciones.ajax.reload();
                            },
                            error : function(xhr, status) {
                                mensajeGrande("Ocurrio un problema...",'error',2000)
                            },
                        });
                });
                $('table').on('click', '.mensajeable_matriculacion', function(e) {
                    e.preventDefault();
                        persona_id =$(this).closest('tr').attr('id');
                        mensaje_id=9;
                        mensajeable_id = $(this).attr('id');
                        mensajeable_type='Matriculacion';
                        url="../mensajeble/store";
                        $.ajax({
                            url :url,
                            data:{
                                mensaje_id:mensaje_id,
                                mensajeable_id:mensajeable_id,
                                mensajeable_type:mensajeable_type,
                                persona_id:persona_id,
                            },
                            success : function(json) {
                                mensajeGrande(json.mensaje,'success',1200)
                                tablamatriculaciones.api().ajax.reload();
                            },
                            error : function(xhr, status) {
                                mensajeGrande("Ocurrio un problema...",'error',2000);
                            },
                        });
                });
                $('table').on('click', '.enviarmensaje', function(e) {
                    e.preventDefault();
                        persona_id =$(this).closest('tr').attr('id');
                        url="../persona/enviar/mensaje/componente",
                        mensaje_id=8;
                        mostrarContactos(url,persona_id,mensaje_id);
                        $("#modal-listar-contactos-component").modal("show");
                });

                function mostrarContactos(url,persona_id,mensaje_id) {
                    let mensaje;
                    let texto;
                    $.ajax({
                        url : "../mensaje/generico",
                        data:{
                            mensaje_id:mensaje_id,
                            persona_id:persona_id,
                        },
                        success : function(json) {
                            console.log(json);
                            mensaje=json.mensaje;
                            texto=json.texto;
                            if(json.persona.telefono!=0){
                                $("#personal").attr('href','https://api.whatsapp.com/send?phone=591'+json.persona.telefono+'&text='+texto);
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
                                $('td', row).eq(5).children('.cargarmensaje').attr('href','https://api.whatsapp.com/send?phone=591'+data['telefono']+'&text='+texto);
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
                                { responsivePriority: 2, targets: -1 },
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
                
                /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
                /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
                /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% mostrar contacto con componentes  FIN %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
                /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
                /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        } );
    </script>
@stop

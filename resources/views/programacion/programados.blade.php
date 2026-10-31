@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
    <link href="{{asset('dist/css/zoomify.css')}}" rel="stylesheet" type="text/css">
@stop

@section('title', 'Personas')
@section('plugins.Sweetalert2',true)
@section('plugins.Datatables',true)

@section('content')
    <div class="pt-4">
        <div class="card">
            <div class="card-header bg-primary">
                Lista de Estudiantes programados para hoy
            </div>
            
            <div class="card-body">
                
                <table id="programados" class="table table-bordered table-hover table-striped">
                    <thead class="bg-primary text-center">
                        <tr>
                            <th>ID</th>
                            <th>CODIGO</th>
                            <th>ESTUDIANTE</th>
                            <th>DOCENTE</th>
                            <th>HORARIO</th>
                            <th>ESTADO</th>
                            <th>MATERIA</th>
                            <th>FOTO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-primary">
                Lista de Computación programados para hoy
            </div>
            
            <div class="card-body">
                
                <table id="programadoscom" class="table table-bordered table-hover table-striped">
                    <thead class="bg-primary text-center">
                        <tr>
                            <th>ID</th>
                            <th>ESTUDIANTE</th>
                            <th>DOCENTE</th>
                            <th>H INICIO</th>
                            <th>H FIN</th>
                            <th>ASIGNATURA</th>
                            <th>FOTO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="{{asset('dist/js/moment.js')}}"></script>
    <script src="https://cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.23/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/responsive.bootstrap4.min.js"></script> 
    
    <script>
        $(document).ready(function() {
        var tabla=$('#programados').DataTable(
                {
                    "responsive":true,
                    "searching":true,
                    "paging":   true,
                    "autoWidth":false,
                    "ordering": true,
                    "info":     true,
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

                    $('td', row).eq(3).html(moment(data['hora_ini']).format('HH:mm')+'-'+moment(data['hora_fin']).format('HH:mm'));
                    $('td', row).eq(4).html(data['estado']);

                },
                    "ajax": "{{ url('hoy') }}",
                    "columns": [
                        {data: 'id'},
                        {data: 'persona_id'},
                        {data: 'estudiante'},
                        {data: 'docente'},
                        {data: 'hora_ini'},
                        {data: 'hora_fin'},
                        {data: 'materia'},
                        {
                            "name": "foto",
                            "data": "foto",
                            "render": function (data, type, full, meta) {
                                return IFEPhotos.render(data,type,full,{height:50});
                            },
                            
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
                        { responsivePriority: 2, targets: -1 },
                        {
                            "targets": [0],
                            "visible": false,
                            "searchable": false
                        },
                    ],
                    "language":{
                        "url":"https://cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"
                    },  
                }
            );
        var tabla=$('#programadoscom').DataTable(
                {
                    "responsive":true,
                    "searching":true,
                    "paging":   true,
                    "autoWidth":false,
                    "ordering": true,
                    "info":     true,
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

                    $('td', row).eq(3).html(moment(data['horaini']).format('HH:mm')+'-'+moment(data['horafin']).format('HH:mm'));
                    $('td', row).eq(4).html(data['estado']);

                },
                    "ajax":'../hoycom',
                    "columns": [
                        {data: 'id'},
                        {data: 'estudiante'},
                        {data: 'docente'},
                        {data: 'horaini'},
                        {data: 'horafin'},
                        {data: 'asignatura'},
                        {
                            "name": "foto",
                            "data": "foto",
                            "render": function (data, type, full, meta) {
                                return IFEPhotos.render(data,type,full,{height:50});
                            },
                            
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

            

            $('table').on('click','.eliminar',function (e) {
                e.preventDefault(); 
                id=$(this).parent().parent().parent().find('td').first().html();
                Swal.fire({
                    title: 'Estas seguro(a) de eliminar este registro?',
                    text: "Si eliminas el registro no lo podras recuperar jamás!",
                    type: 'question',
                    showCancelButton: true,
                    showConfirmButton:true,
                    confirmButtonColor: '#25ff80',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Eliminar..!',
                    position:'center',        
                    }).then((result) => {
                    if (result.value) {
                        $.ajax({
                            url: 'eliminar/persona/'+id,
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
                            timerProgressBar: true,
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
        });
        
    </script>
@stop
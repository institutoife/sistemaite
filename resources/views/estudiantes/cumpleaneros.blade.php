@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
    <link rel="stylesheet" href="{{asset('custom/css/custom.css')}}">
    <link href="{{asset('dist/css/zoomify.css')}}" rel="stylesheet" type="text/css">
@stop

@section('title', 'Cumpleañeros')
@section('plugins.Jquery', true)
@section('plugins.Sweetalert2', true)
@section('plugins.Datatables', true)

@section('content')
    <div class="row">
        <div class="card">
            <div class="card-header">
                CUMPLEAÑEROS
            </div>
            <div class="card-body">
                <table id="cumpleaneros" class="table table-striped table-hover table-bordered table-borderless">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>NOMBRE</th>
                            <th>APELLIDOP</th>
                            <th>APELLIDOM</th>
                            <th>TELEFONO</th>
                            <th>FOTO</th>
                            <th>EDAD</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                    </tbody>
                </table>
            </div>
        </div>   
    </div>
    @include('estudiantes.modal')
@endsection
@section('js')
    <script src="{{asset('dist/js/moment.js')}}"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.17.1/locale/es.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
    

<script>

    $(document).ready(function() {
        
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  DATA TABLE  %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        var tabla =  $('#cumpleaneros').dataTable({
                "responsive":true,
                "searching":true,
                "paging":   true,
                "autoWidth":false,
                "ordering": true,
                "info":     true,
                "targets": 0,
                "createdRow": function( row, data, dataIndex ) {
                    $(row).attr('id',data['id']);
                },
                "ajax":{
                    "url":"../cumpleaneros",
                    "data":{
                        dias:0,
                    },
                },
                "columns": [
                    {data: 'id'},
                    {data: 'nombre'},
                    {data: 'apellidop'},
                    {data: 'apellidom'},
                    {data: 'telefono'},
                     {
                        "name": "foto",
                        "data": "foto",
                        "render": function (data, type, full, meta) {
                            return IFEPhotos.render(data,type,full,{height:50});
                        },
                        "title": "FOTO",
                        "orderable": false,
                        },    
                    {data: 'edad'},
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
            });
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ENVIAR MENSAJES %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            
            // $("#modal-mostrar-contactos").on("hidden.bs.modal", function () {
            //     //tabla.ajax.reload();
            // });

            $('table').on('click', '.enviarmensaje', function(e) {
                e.preventDefault();
                persona_id =$(this).closest('tr').attr('id');
                    $("#modal-mostrar-contactos").modal("show");
                    $("#tabla-contactos").empty();
                            $.ajax({
                            url :"../persona/enviar/mensaje/cumpleaneros",
                            data:{
                                persona_id:persona_id,
                            },
                            success : function(json) {
                                tabla.api().ajax.reload();
                                $html="<tr id='"+ json.persona.telefono +"'><td>"+ json.persona.nombre +"</td>";
                                $html+="<td>Teléfono personal</td>";
                                $html+="<td>"+json.persona.telefono+"</td>";
                                $html+="<td>"+moment(json.persona.created_at).format('L') +"</td>";
                                $html+="<td>"+moment(json.persona.updated_at).format('L') +"</td>";
                                if (json.persona.telefono!=0)
                                    $html+="<td><a target='_blank' href='https://api.whatsapp.com/send?phone=591"+ json.persona.telefono+"&text="+ json.mensaje +"' class=''><i class='fab fa-whatsapp'></i></a></td></tr>";
                                else
                                    $html+="<td><a class=''>No tiene número</a></td></tr>";
                                for (let j in json.apoderados) {
                                    $html+="<tr id='"+ json.apoderados[j].telefono +"'><td>"+ json.apoderados[j].nombre +"</td>";
                                    $html+="<td>"+json.apoderados[j].pivot.parentesco+"</td>";
                                    $html+="<td>"+json.apoderados[j].telefono+"</td>";
                                    $html+="<td>"+moment(json.apoderados[j].created_at).format('LLL') +"</td>";
                                    $html+="<td>"+moment(json.apoderados[j].updated_at).format('LLL') +"</td>";
                                    $html+="<td><a target='_blank' href='https://api.whatsapp.com/send?phone=591"+ json.apoderados[j].telefono +"&text="+ json.mensaje +"' class=''><i class='fab fa-whatsapp'></i></a></td></tr>";
                                }
                                $("#tabla-contactos").append($html);
                            },
                            error : function(xhr, status) {
                                alert('Disculpe, existió un problema');
                            },
                        });
                });
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  guarda que ya se felicito este año %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            // $.ajaxSetup({
            // headers:{
            //     'X-CSRF-TOKEN' : $('meta[name="_token"]').attr('content')
            //     }
            // });
            $('table').on('click', '.felicitado', function(e) {
                e.preventDefault();    
                persona_id =$(this).closest('tr').attr('id');
                
                $.ajax({
                    url :"../persona/felicitado",
                    data:{
                        persona_id:persona_id,
                        _token: '{{csrf_token()}}'
                    },
                    type: "POST",
                    success : function(json) {
                        
                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
            });
             
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
@endsection



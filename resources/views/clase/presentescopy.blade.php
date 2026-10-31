@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.rtl.min.css" />
    <link href="{{asset('dist/css/zoomify.css')}}" rel="stylesheet" type="text/css">
@endsection

@section('title', 'Estudiantes Presentes')
@section('content_header')
    
@stop
@section('plugins.Jquery',true)
@section('plugins.Datatables',true)
@section('plugins.Sweetalert2',true)
@section('plugins.Select2',true)

@section('content')
    <div class="content pt-4">
        <div class="card">
            <div class="card-header bg-secondary">
                ESTUDIANTES DE NIVELACION PRESENTES AHORITA
            </div>
            <div class="card-body">
                <table id="presentes" class="table table-hover table-bordered table-striped display" width="100%">
                    <thead class="bg-primary">
                        <tr>

                            <th>COD</th>
                            <th>ESTUDIANTE</th>
                            {{-- <th>MATERIA</th> --}}
                            <th>AULA</th>
                            <th>DOCENTE</th>
                            <th>HORARIO</th>
                            <th>TIEMPO</th>
                            <th>FOTO</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                </table >
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-secondary">
                ESTUDIANTES DE COMPUTACION PRESENTES AHORITA
            </div>
            <div class="card-body">
                 <table id="presentescom" class="table table-hover table-bordered table-striped display" width="100%">
                    <thead class="bg-primary">
                        <tr>
                            <th>COD</th>
                            <th>ESTUDIANTE</th>
                            <th>ASIGNATURA</th>
                            <th>AULA</th>
                            {{-- <th>DOCENTE</th> --}}
                            <th>HORARIO</th>
                            <th>TIEMPO</th>
                            <th>FOTO</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                </table >
            </div>
        </div>
    </div>
    @include('clase.modalmostrar') 
    @include('clase.modaleditar') 

@stop
{{-- %%%%%%%%%%%%%%%%%%%%%%% inicio seccion JS --}}
@section('js')  
    <script src="{{asset('dist/js/moment.js')}}"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.17.1/locale/es.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#tema").select2({
                dropdownParent: $("#modal-editar"),
                placeholder: "Seleccione un tema",
                theme: "bootstrap-5",
                containerCssClass: "select2--large", // For Select2 v4.0
                selectionCssClass: "select2--large", // For Select2 v4.1
                dropdownCssClass: "select2--large",
            });

            $('#presentes').on('click', '.finalizar', function(e) { 
                e.preventDefault(); 
                var id_estudiante =$(this).closest('tr').attr('id');
                var           fila=$(this).closest('tr');
                
		        $.ajax({
                    url : "clase/finalizar/",
                    data : { id :id_estudiante },
                    success : function(json) {
                            
                            fila.remove();
                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000,
                                })

                                Toast.fire({
                                type: 'success',
                                title: json.message
                                })
                    },
                    error : function(xhr, status) {
                        Swal.fire({
                        type: 'error',
                        title: 'Ocurrio un Error',
                        text: 'Saque una captura para mostrar al servicio Técnico!',
                        })
                    },
                });
	        });

            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% MUESTRA FORMULARIO EDITAR %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            $('table').on('click', '.editar', function(e) {
                e.preventDefault(); 
                let id_clase=$(this).closest('tr').attr('id');
                
                $.ajax({
                    url : "clase/editar",
                    data : { id :id_clase },
                    success : function(json) {
                            $("#modal-editar").modal("show");
                            $("#inputs-creados").empty();
                                //%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  CAMPO OCULTO DE DOCENTE %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%


                            $("#fecha").val(moment(json.clase.fecha).format('YYYY-MM-DD'));
                            $("#horainicio").val(moment(json.clase.horainicio).format('HH:mm'));
                            $("#horafin").val(moment(json.clase.horafin).format('HH:mm'));

                            $html="<div id='inputs-creados'>";
                            $html+="<div class='row'>";
                            $html+="<div class='col-xs-12 col-sm-12 col-md-6 col-lg-4'>";
                            $html+="<div class='form-floating mb-3 text-gray'>";
                            $html+="<select class='form-control @error('docente_id') is-invalid @enderror' name='docente_id' id='docente_id'>";
                            
                            for (let j in json.docentes) {
                                if(json.docentes[j].id==json.clase.docente_id){
                                    $html+="<option  value='"+json.docentes[j].id +"' selected >"+json.docentes[j].nombre+"</option>";
                                }else{
                                    $html+="<option  value='"+json.docentes[j].id +"'>"+json.docentes[j].nombre+"</option>";
                                }
                            }
                            $html+="</select>";                
                            $html+="<label for='docente_id'>Docente</label></div></div>";
                            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  CAMPO MATERIA %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
                            $html+="<div class='col-xs-12 col-sm-12 col-md-6 col-lg-4'>";
                            $html+="<div class='form-floating mb-3 text-gray'>";
                            $html+="<select class='form-control @error('materia_id') is-invalid @enderror' name='materia_id' id='materia_id'>";
                            for (let k in json.materias) {
                                if(json.materias[k].id==json.clase.materia_id){
                                    $html+="<option  value='"+json.materias[k].id +"' selected >"+json.materias[k].materia+"</option>";
                                }else{
                                    $html+="<option  value='"+json.materias[k].id +"'>"+json.materias[k].materia+"</option>";
                                }
                            }
                            $html+="</select>";                
                            $html+="<label for='materia_id'>Materia</label></div></div>";
                            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  CAMPO AULA %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
                            $html+="<div class='col-xs-12 col-sm-12 col-md-6 col-lg-4'>";
                            $html+="<div class='form-floating mb-3 text-gray'>";
                            $html+="<select class='form-control @error('aula_id') is-invalid @enderror' name='aula_id' id='aula_id'>";
                            for (let m in json.aulas) {
                                if(json.aulas[m].id==json.clase.aula_id){
                                    $html+="<option  value='"+json.aulas[m].id +"' selected >"+json.aulas[m].aula+"</option>";
                                }else{
                                    $html+="<option  value='"+json.aulas[m].id +"'>"+json.aulas[m].aula+"</option>";
                                }
                            }
                            $html+="</select>";                
                            $html+="<label for='aula_id'>Aula</label></div></div>";
                            $html+="</div>";// fin de row
                            $html+="<input id='clase_id'  type='text' hidden readonly name='clase_id' value='"+json.clase.id +"'>";
                            $html+="</div>";
                            $("#inputs").after($html);
                            $htmltemas="";
                            for (let n in json.temas) {
                                if(json.temas[n].id==json.clase.tema_id){
                                    $htmltemas+="<option  value='"+json.temas[n].id +"' selected >"+json.temas[n].tema+"</option>";
                                }else{
                                    $htmltemas+="<option  value='"+json.temas[n].id +"'>"+json.temas[n].tema+"</option>";
                                }
                            }
                            $('#tema').empty();
                            $("#tema").append($htmltemas);
                    },
                    error : function(xhr, status) {
                        Swal.fire({
                        type: 'error',
                        title: 'Ocurrio un Error',
                        text: 'Saque una captura para mostrar al servicio Técnico!',
                        })
                    },  
                });
               
            });


            /* %%%%  C A R G A   L OS   O P C TI O N   C O N   A J A X  A L S E L E C T  T E M A   %%%%%%%%%%%%%%%%%%%%%%%%% */
            $("#formulario-editar-clase").on("change","#materia_id", function() {
                $.ajax({
                    url:'temas/'+$("#materia_id").val(),
                    success: function(json) {
                            $htmlTemas="<option value=''>Elija un tema</option>";
                            var id_tema=$("#tema_id").val();
                            for (let temas_j in json) {
                                
                                if(json[temas_j].id==id_tema){
                                    $htmlTemas+="<option  value='"+json[temas_j].id +"' selected >"+json[temas_j].tema+"</option>";
                                }else{
                                    $htmlTemas+="<option  value='"+json[temas_j].id +"'>"+json[temas_j].tema+"</option>";
                                }
                            }
                        //$htmlTemas="<option>"+ json.tema +"</option>";
                        
                        $("#tema").empty();
                        $("#tema").append($htmlTemas);
                        
                    },
                    error: function() {
                        Swal.fire({
                        type: 'error',
                        title: 'Ocurrio un Error',
                        text: 'Saque una captura para mostrar al servicio Técnico!',
                        })
                    }
                });
            });

            /* %%%%%%% ACTUALIZA LA CLASE EN LA BASE DE DATOS %%%%%%%%%%%%%%%%%%%%%% */
            $("#actualizar-clase").on("click", function(e) {
                e.preventDefault();
                

                $.ajax({
                    url:'clase/actualizar/'+$("#clase_id").val(),
                    data : { 
                        fecha :$("#fecha").val(),
                        horainicio :$("#horainicio").val(),
                        horafin :$("#horafin").val(),
                        docente_id :$("#docente_id").val(),
                        materia_id :$("#docente_id").val(),
                        aula_id :$("#aula_id").val(),
                        tema_id :$("#tema_id").val(),
                    },
                    success: function(response) {
                            $("#modal-editar").modal("hide");
                            
                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-center',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                                didOpen: (toast) => {
                                    toast.addEventListener('mouseenter', Swal.stopTimer)
                                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                                }
                                })

                                Toast.fire({
                                type: 'success',
                                title: response.mensaje
                                })
                            $('#presentes').DataTable().ajax.reload();
                    },
                    error: function() {
                        Swal.fire({
                        type: 'error',
                        title: 'Ocurrio un Error',
                        text: 'Saque una captura para mostrar al servicio Técnico!',
                        })
                    }
                });
            });
            
        
            
            /* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  zomify %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% */
            $('table').on('click','.zoomify',function (e){
                
                Swal.fire({
                    title: 'Estudiantex: '+ $(this).closest('tr').find('td').eq(1).text(),
                    text: 'Materia:'+$(this).closest('tr').find('td').eq(2).text(),
                    imageUrl: $(this).attr('src'),
                    imageWidth: 400,
                    imageHeight:400,
                    showCloseButton:true,
                    confirmButtonColor:'#26baa5',
                    confirmButtonText:"Aceptar",
                    
                })
            });
            $('table').on('click','.zoom',function (e){
                Swal.fire({
                    imageWidth: 400,
                    imageHeight:400,
                    imageUrl: $(this).attr('src'),
                    showCloseButton:true,
                    confirmButtonColor:'#26baa5',
                    confirmButtonText:"Aceptar",
                    
                })
            });


            let tablaPresentes=$('#presentes').dataTable({
                "createdRow": function( row, data, dataIndex){
                    var horainicio=moment.duration(data['horainicio']);
                    var horafin=moment.duration(data['horafin']);
                    
                    let hinicio=moment(data['horainicio']);
                    let hfin=moment(data['horafin']);
                    let ahora=moment();
                    let minutosRestantantes=hfin.diff(ahora,'minutes');
                        
                        if(minutosRestantantes<-10){
                            $(row).addClass('text-bold text-danger');
                        }
                        if((minutosRestantantes<=0)&&(minutosRestantantes>=-10)){
                            $(row).addClass('text-danger');
                        }
                        if((minutosRestantantes>=0)&&(minutosRestantantes<15)){
                            $(row).addClass('text-warning');
                        }
                        if(minutosRestantantes>15){
                            $(row).addClass('text-success');
                        }
                    $(row).attr('id',data['id']);
                    
                    $('td', row).eq(4).html(moment(data['horainicio']).format('HH:mm')+'-'+moment(data['horafin']).format('HH:mm'));
                    $('td', row).eq(5).html( hfin.from(ahora)+'('+hfin.diff(ahora,'minutes'));

                },
                
                "serverSide": true,
                "ordering":true,
                "responsive":true,
                "paging":   true,
                "info":     false,
                "autoWidth":false,
                "columns": [
                        {data: 'codigo'},
                        {data: 'name'},
                        // {data: 'materia'},
                        {data: 'aula'},
                        
                        {data: 'nombre'},
                        {data: 'horainicio'},
                        {data: 'horafin'},
                        {
                            "name": "foto",
                            "data": "foto",
                            "render": function (data, type, full, meta) {
                                return IFEPhotos.render(data,type,full,{height:75});
                            },
                            "title": "FOTO",
                            "orderable": false,
                        },  
                        {   
                            "data": "btn"
                        },
                    ],
                "ajax": "{{ url('clases/presentes/ahorita') }}",
                "columnDefs": [
                    { responsivePriority: 1, targets: 0 },  
                    { responsivePriority: 2, targets: 7 },
                    { responsivePriority: 3, targets: -1 },
                ],
                "language":{
                        "url":"https://cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"
                },  
            });
            /* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  MOSTRAR CLASE %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            $('table').on('click', 'a .mostrar', function(e) {
                e.preventDefault(); 
                var id_clase =$(this).closest('tr').attr('id');
                var fila=$(this).closest('tr');
                
                $.ajax({
                    url : "clase/mostrar/",
                    data : { id :id_clase },
                    success : function(json) {
                        
                        $("#modal-mostrar").modal("show");
                        $("#tabla-modal").empty();
                        $html="";
                        $html+="<tr><td>DOCENTE</td>"+"<td>"+json.nombre+' '+json.apellidop+' '+json.apellidom+"</td></tr>";
                        $html+="<tr><td>ESTUDIANTE</td>"+"<td>"+fila.find('td').eq(1).html()+"</td></tr>";
                        $html+="<tr><td>AULA</td>"+"<td>"+json.aula+"</td></tr>";
                        $html+="<tr><td>MATERIA</td>"+"<td>"+json.materia+"</td></tr>";
                        $html+="<tr><td>TEMA</td>"+"<td>"+json.tema+"</td></tr>";
                        $html+="<tr><td>FOTO ESTUDIANTE</td>"+"<td>"+fila.find('td').eq(8).html()+"</td></tr>";

                        $html+="<tr><td>FOTO DOCENTE</td><td>"+IFEPhotos.render(json.foto,'display',json,{height:150})+"</td></tr>";
                        $html+="<tr><td>CREADO</td>"+"<td>"+moment(json.created_at).format('LLLL')+"</td></tr>";
                        $html+="<tr><td>ACTUALIZADO</td>"+"<td>"+moment(json.updated_at).format('LLLL')+"</td></tr>";
                        $("#tabla-modal").append($html);

                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
                

            });
            /* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  MOSTRAR CLASE %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            $('table').on('click', 'a .mostrarcom', function(e) {
                e.preventDefault(); 
                var id_clase =$(this).closest('tr').attr('id');
                var fila=$(this).closest('tr');
                $.ajax({
                    url : "clasecom/mostrar/",
                    data : { id :id_clase },
                    success : function(json) {
                        
                        $("#modal-mostrar").modal("show");
                        $("#tabla-modal").empty();
                        $html="";
                        $html+="<tr><td>DOCENTE</td>"+"<td>"+json.nombre+' '+json.apellidop+' '+json.apellidom+"</td></tr>";
                        $html+="<tr><td>ESTUDIANTE</td>"+"<td>"+fila.find('td').eq(1).html()+"</td></tr>";
                        $html+="<tr><td>AULA</td>"+"<td>"+json.aula+"</td></tr>";
                        $html+="<tr><td>MATERIA</td>"+"<td>"+json.materia+"</td></tr>";
                        $html+="<tr><td>TEMA</td>"+"<td>"+json.tema+"</td></tr>";
                        $html+="<tr><td>FOTO ESTUDIANTE</td>"+"<td>"+fila.find('td').eq(8).html()+"</td></tr>";

                        $html+="<tr><td>FOTO DOCENTE</td><td>"+IFEPhotos.render(json.foto,'display',json,{height:150})+"</td></tr>";
                        $html+="<tr><td>CREADO</td>"+"<td>"+moment(json.created_at).format('LLLL')+"</td></tr>";
                        $html+="<tr><td>ACTUALIZADO</td>"+"<td>"+moment(json.updated_at).format('LLLL')+"</td></tr>";
                        $("#tabla-modal").append($html);

                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
                

            });
            
            setInterval(function(){
                $('#presentes').DataTable().ajax.reload();
		    },60000);
        /**%%%%%%%%%%%%%%%%%%%% JS PARA COMPUTACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        /**%%%%%%%%%%%%%%%%%%%% DATATABLE PRESENTESCOM INICIO %%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            let tablaPresentescom=$('#presentescom').dataTable({
                "createdRow": function( row, data, dataIndex){
                    var horainicio=moment.duration(data['horainicio']);
                    var horafin=moment.duration(data['horafin']);
                    let hinicio=moment(data['horainicio']);
                    
                    let hfin=moment(data['horafin']);
                    let ahora=moment();
                    let minutosRestantantes=hfin.diff(ahora,'minutes');
                    
                        
                        if(minutosRestantantes<-10){
                            $(row).addClass('text-bold text-danger');
                        }
                        if((minutosRestantantes<=0)&&(minutosRestantantes>=-10)){
                            $(row).addClass('text-danger');
                        }
                        if((minutosRestantantes>=0)&&(minutosRestantantes<15)){
                            $(row).addClass('text-warning');
                        }
                        if(minutosRestantantes>15){
                            $(row).addClass('text-success');
                        }
                    $(row).attr('id',data['id']);
                    
                    $('td', row).eq(4).html(moment(data['horainicio']).format('HH:mm')+'-'+moment(data['horafin']).format('HH:mm'));
                    $('td', row).eq(5).html( hfin.from(ahora)+'('+hfin.diff(ahora,'minutes'));

                },
                
                "serverSide": true,
                "ordering":true,
                "responsive":true,
                "paging":   true,
                "info":     false,
                "autoWidth":false,
                "columns": [
                        {
                            data: 'codigo',
                            searchable:true
                        },
                        {data: 'name'},
                        {data: 'asignatura'},
                        {data: 'aula'},
                        
                        // {data: 'nombre'},
                        {data: 'horainicio'},
                        {data: 'horafin'},
                        {
                            "name": "foto",
                            "data": "foto",
                            "render": function (data, type, full, meta) {
                                return IFEPhotos.render(data,type,full,{height:75});
                            },
                            "title": "FOTO",
                            "orderable": false,
                        },  
                        {   
                            "data": "btn"
                        },
                    ],
                "ajax": "{{ url('clasescom/presentes/ahorita') }}",
                "columnDefs": [
                    { responsivePriority: 1, targets: 0 },  
                    { responsivePriority: 2, targets: 7 },
                    { responsivePriority: 3, targets: -1 },
                ],
                "language":{
                        "url":"https://cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"
                },  
            });
            /**%%%%%%%%%%%%%%%%%%%% DATATABLE PRESENTESCOM FIN %%%%%%%%%%%%%%%%%%%%%%%%%%%%*/

    });

        

    </script>


@stop


{{-- %%%%%%%%%%fin seccion JS --}}

@extends('adminlte::page')

@section('title', 'Persona Mostrar')

@section('css')
    <link rel="stylesheet" href="{{asset('bootstrap/css/bootstrap.css')}}">
@endsection

@section('content_header')
    
@stop

@section('content')
{{-- $$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$ INICIO DATOS DE PERSONA %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% --}}

<div class="card">
        <div class="card-header">
            CALIFICACION
        </div>
        <div class="card-body">
            
            @if ($calificado==0)
                <x-calificacion color="primary" :personaid="$persona->id">
                    <x-slot name="title">
                        Calificar este Modelo
                    </x-slot>
                </x-calificacion>
            @else
            <div class="row">
                <div class="col-10">
                    <div class="border  position-relative">
                        <div class="text-center col-auto p-5">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="width: {{($promedio/5)*100}}%"></div>
                            </div>
                            <a href="" class="editar_calificacion" ><i class="fa fa-fw fa-edit text-primary"></i></a>
                            Mi calificaion {{$micalificacion}}
                        </div>
                        
                    </div>
                </div>
                <div class="col-2">
                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 text-center border">
                        <div class="circulo bg-primary">
                            <h1>{{ $promedio }}</h1> 
                        </div>
                        Calificación    
                    </div>
                </div>
            </div>
                
                
            @endif
        </div>
    </div>

    <div class="card">
        @if(isset($persona) && $persona->foto)
            <img data-profile-photo class="rounded img-thumbnail img-fluid border-primary border-5" src="{{ app(\App\Services\ProfilePhotoService::class)->url($persona->foto) }}" alt="{{$persona->nombre.' '.$persona->apellidop}}" width="100">
        @else
            <img data-profile-photo class="rounded img-thumbnail img-fluid border-primary border-5" src="{{ app(\App\Services\ProfilePhotoService::class)->placeholder() }}" alt="Sin foto" width="100">
        @endif
            <table class="table table-bordered table-striped"> 
                <tr class="bg-primary">
                        <th>ATRIBUTO</th>
                        <th>VALOR</th>
                </tr>
                <tbody>
                    <tr>
                        <td>Codigo</td>
                        @if(isset($user) && $user->foto)
                            <img data-profile-photo src="{{ app(\App\Services\ProfilePhotoService::class)->forUser($user) }}" alt="{{$user->name}}" class="rounded img-thumbnail img-fluid border-primary border-5" width="100">
                        @else
                            <img data-profile-photo src="{{ app(\App\Services\ProfilePhotoService::class)->placeholder() }}" alt="Sin foto" class="rounded img-thumbnail img-fluid border-primary border-5" width="100">
                        @endif
                    </tr>
                    <tr>
                        <td>Fotografía
                        </td>
                        <td> 
                            <div class="text-center">
                                @if(isset($persona) && $persona->foto)
                                    <img data-profile-photo class="rounded img-thumbnail img-fluid border-primary border-5" src="{{app(\App\Services\ProfilePhotoService::class)->url($persona->foto)}}" alt="{{$persona->nombre.' '.$persona->apellidop}}" width="100">
                                @else
                                    <img data-profile-photo class="rounded img-thumbnail img-fluid border-primary border-5" src="{{ app(\App\Services\ProfilePhotoService::class)->placeholder() }}" alt="Sin foto" width="100">
                                @endif
                                <p>
                                    @isset($observacion)
                                        {!!$observacion->observacion!!}</p>
                                    @endisset
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>Nombre</td>
                        <td>{{$persona->nombre.' '.$persona->apellidop.' '.$persona->apellidom}}</td>
                    </tr>
                    <tr>
                        <td>fechanacimiento</td>
                        <td>'Nacido el: <strong> {{$persona->fechanacimiento}} </strong> Nació <strong>{{ $persona->fechanacimiento->diffForHumans()}} </strong> </td>
                    </tr>
                    <tr>
                        <td>Carnet</td>
                        <td>{{$persona->carnet." ".$persona->expedido}} </td>
                    </tr>
                    <tr>
                        <td>Género</td>
                        <td>{{$persona->genero}} </td>
                    </tr>
                    <tr>
                        <td>Teléfono</td>
                        <td>{{$persona->telefono}} </td>
                    </tr>
                    <tr>
                        <td>Pais</td>
                        <td>{{ $pais ? $pais->nombrepais : 'Sin país' }} </td>
                    </tr>
                    <tr>
                        <td>Ciudad</td>
                        <td>{{ $ciudad ? $ciudad->ciudad : 'Sin ciudad' }} </td>
                    </tr>
                    <tr>
                        <td>Zona</td>
                        <td>{{ $zona ? $zona->zona : 'Sin zona' }} </td>
                    </tr>
                    <tr>
                        <td>Dirección</td>
                        <td>{{$persona->direccion}} </td>
                    </tr>
                    <tr>
                        <td>Como se enteró</td>
                        <td>{{$persona->como->como}} </td>
                    </tr>
                    <tr>
                        <td>Papel Inicial</td>
                        <td>{{$persona->papelinicial}} </td>
                    </tr>
                    <tr>
                        <td>Recomendado por</td>
                        @isset($recomendado)
                            <td> <a href="{{route('personas.show',$recomendado)}}">{{$recomendado->nombre.' '.$recomendado->apellidop}}</a> </td>
                        @endisset
                    </tr>
                    <tr>
                        <td>Actualizado</td>
                        <td>{{$persona->updated_at}}</td>
                    </tr>
                    <tr>
                        <td>vino por primera vez</td>
                        <td>{{$persona->created_at}} </td>
                    </tr>
                    <tr>
                        <td>Usuario</td>
                        <td>
                            @isset($user)
                                {{$user->name}}
                                <img data-profile-photo  src="{{ app(\App\Services\ProfilePhotoService::class)->forUser($user) }}" alt="{{$user->name}}" class="rounded img-thumbnail img-fluid border-primary border-5" width="100">
                            @endisset
                        </td>
                    </tr>
                </tbody>
            </table>

        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Apoderados
        </div>
        <div class="card-body">
            <table id="telefonos" class="table table-hover table-bordered table-striped display responsive nowrap" width="100%">
                <thead class="bg-primary">
                    <th>#</th>
                    <th>APODERADOS</th>
                    <th>NUMERO</th>
                    <th>PARENTESCO</th>
                    <th>ACTUALIZADO</th>
                </thead>
                <tbody>
                    <td>1</td>
                    <td>
                        {{$persona->nombre.' '.$persona->apellidop.' '.$persona->apellidom}}
                    </td>
                    <td>
                       <a class="text-bold" href="tel:{{$persona->telefono}}">{{$persona->telefono}}</a> 
                    </td>
                    <td>
                        Personal
                    </td>
                    <td>{{$persona->updated_at->diffForHumans()}}</td>
                

                    @foreach ($apoderados as $apoderado)
                        <tr>
                            <td>{{$loop->iteration+1}}</td>
                            <td>
                                {{$apoderado->nombre.' '.$apoderado->apellidop.' '.$apoderado->apellidom}}
                            </td>
                            <td>
                                <a class="text-bold" href="tel:{{$apoderado->telefono}}">{{$apoderado->pivot->telefono}}</a>
                            </td>
                            <td>
                                {{$apoderado->pivot->parentesco}}
                            </td>
                            <td>{{$apoderado->updated_at->diffForHumans()}}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-secondary">
            Observaciones de esta persona
                {{-- <a href="" class="btn btn-primary tooltipsC btn-sm mr-2 float-right observacion"  id="Persona" title="Agregar Observacion">
                    Agreagar Observación <i class="fas fa-comment-medical fa-2x"></i>
                </a> --}}
        </div>
        <div class="card-body">
            <table id="observaciones" class="table table-bordered table-striped table-hover">
                <thead class="bg-secondary">
                    <tr>
                        <th>ID</th>
                        <th>Observacion</th>
                        <th>Activo</th>
                        <th>Usuario</th>
                        <th>Actualizado</th>
                        <th>Options</th>
                    </tr>
                </thead>
                
            </table>
        </div>
    </div>
    @include('persona.modalescalificacion')
    {{-- para observacion --}}
    @include('observacion.modalcreate')
    @include('observacion.modaleditar')
{{-- $$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$ FIN DATOS DE PERSONA %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% --}}


    <div class="card">
            <div class="card-header">
                    Mostrar Docente <a class="btn btn-secondary text-white btn-sm float-right" href="{{route('docentes.index')}}">Listar docentes</a>
            </div>
        <div class="card-body">
            <table class="table table-bordered table-striped"> 
                <tr class="bg-primary">
                    
                        <th>ATRIBUTO</th>
                        <th>VALOR</th>
                    
                </tr>
                <tbody>
                    <tr>
                        <td>Codigo</td>
                        <td>{{$docente->id}}</td>
                    </tr>
                    
                    <tr>
                        <td>Fotografía</td>
                            
                        <td> 
                            
                            <div class="text-center">
                                
                                {{-- <img data-profile-photo class="rounded img-thumbnail img-fluid border-primary border-5" src="{{app(\App\Services\ProfilePhotoService::class)->url($persona->foto)}}" alt="{{$persona->nombre.' '.$persona->apellidop}}">  --}}
                                <p>{!!$docente->perfil!!}</p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>Nombre</td>
                        <td>{{$persona->nombre.' '.$persona->apellidop.' '.$persona->apellidom}}</td>
                    </tr>
                    <tr>
                        <td>Nombre corto</td>
                        <td>{{$docente->nombrecorto}}</td>
                    </tr>


                    <tr>
                        <td>Fecha Inicio</td>
                        <td>'Empezo a trabajar: <strong> {{$docente->fecha_inicio}} </strong> Hace <strong>{{ $docente->fecha_inicio->diffForHumans()}} </strong> </td>
                    </tr>
                    <tr>
                        <td>Dias prueba</td>
                        <td>{{$docente->dias_prueba}} </td>
                    </tr>
                    <tr>
                        <td>Sueldo</td>
                        <td>{{$docente->sueldo}} </td>
                    </tr>
                    <tr>
                        <td>Mododocente</td>
                        <td>{{$docente->mododocente->mododocente}} </td>
                    </tr>
                    <tr>
                        <td>Estado</td>
                        <td>
                            @if(strtoupper($docente->estado->estado) == 'DESHABILITADO')
                                <span style="color: #d9534f; font-size: 1.5em; font-weight: bold;">{{$docente->estado->estado}}</span>
                            @elseif(strtoupper($docente->estado->estado) == 'HABILITADO')
                                <span style="color: #28a745; font-size: 1.5em; font-weight: bold;">{{$docente->estado->estado}}</span>
                            @else
                                <span style="font-size: 1.5em; font-weight: bold;">{{$docente->estado->estado}}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>Actualizado</td>
                        <td>{{$docente->updated_at}}</td>                       
                    </tr>
                    <tr>
                        <td>Registro</td>
                        <td>{{$docente->created_at}} </td>
                    </tr>

                </tbody>
            </table>

        </div>
    </div>
@stop


@section('js')
    {{-- <script src="https://code.jquery.com/jquery-3.5.1.js"></script> --}}
    <script src="http://cdnjs.cloudflare.com/ajax/libs/jquery/1.11.1/jquery.js"></script>
    <script src="http://libs.baidu.com/jquery/1.10.2/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.23/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/responsive.bootstrap4.min.js"></script>
    <script src="https://kit.fontawesome.com/067a2afa7e.js" crossorigin="anonymous"></script>
    <script src="{{asset('dist/js/moment.js')}}"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.17.1/locale/es.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script type="text/javascript" src="{{ asset('dist/js/jquery.leanModal.min.js')}}"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>    
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>    
    {{--  para observacion --}}
    <script src="{{asset('dist/js/starrr.js')}}"></script>
    <script src="{{ asset('ckeditor/ckeditor.js') }}"></script>
    {{-- <script src="{{ asset('assets/js/observacionshow.js') }}"></script> --}}
    <script>
    
        /**%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  CONFIGURARION DE CKEDITOR %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
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
        /**%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  MUESTRA CONFIGURARCION DE CALIFICACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
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
        /**%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  EDITAR CONFIGURARCION DE CALIFICACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('.editar_calificacion').on('click', function (e) {
            e.preventDefault();
            let persona_id = "{{ $persona->id }}";
            $.ajax({
                url: "../calificacion/editar",
                data: { persona_id: persona_id },
                success: function (json) {
                    $("#editar-calificacion").modal("show");
                    $('#calificacion').val(json.calificacion.calificacion);
                    $('#calificacion_id').val(json.calificacion.id);
                },
                error: function (xhr, status) {
                    alert('Disculpe, existió un problema');
                },
            });
        });
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
        
        $(document).ready(function(){
            /**%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  VALORES DE LOS PARAMETROS %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            var observable_id="{{ $persona->id }}";
            var observable_type="Persona";

        /**%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  DATATABLE DE OBSERVACIONES %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        let tabla=$('#observaciones').DataTable(
            {
                "serverSide": true,
                "responsive":true,
                "autoWidth":false,
                "ajax":{
                    "url":"../observaciones/"+observable_id+"/"+observable_type,
                }, 
                "createdRow": function( row, data, dataIndex ) {
                    $(row).attr('id',data['id']); // agrega dinamiacamente el id del row
                    $('td', row).eq(4).html(moment(data['updated_at']).format('D-M-Y h:mm'));
                    if(data['activo']==1){
                        $(row).addClass('text-success');
                    }else{
                        $(row).addClass('text-danger');
                    }
            },
                "columns": [
                    {data: 'id'},
                    {data: 'observacion'},
                    {data: 'activo'},
                    {data: 'name'},
                    {data: 'updated_at'},
                    {
                        "name":"btn",
                        "data": 'btn',
                        "orderable": false,
                    },
                ],
                "language":{
                    "url":"https://cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"
                }, 
                "order": [[ 2, "desc" ]] 
            }
        );
        /**%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  MUESTRA LA VENTAN MODAL DE CREAR NUEVO OBSERVACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('.observacion').on('click', function (e) {
            e.preventDefault();
            $("#editor1").val("");
            let objeto_id = "{{ $persona->id }}";
            $("#observable_id").val(objeto_id);
            $("#observable_type").val($(this).attr("id"));
            CKEDITOR.instances.editor1.setData('');
            $("#modal-gregar-observacion").modal("show");
            // $("#formulario-guardar-observacion").empty();
        });
        
        /**%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  GUARDA OBSERVACION CON AJAX %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('#guardar-observacion').on('click', function (e) {
            e.preventDefault();
            let observable_id = $("#observable_id").val();
            let observable_type = $("#observable_type").val();
            for (instance in CKEDITOR.instances) { CKEDITOR.instances[instance].updateElement() }
            $.ajax({
                url: "../guardar/observacion",
                data: {
                    //obs: $("#observacionx").val(),
                    observacion: $("#editor1").val(),
                    observable_id: observable_id,
                    observable_type: observable_type,
                },
                success: function (json) {
                    tabla.ajax.reload();
                    $("#" + observable_id).addTempClass('bg-success', 3000);
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                    })
                    Toast.fire({
                        type: 'success',
                        title: "Guardado corectamente: " + json.observacion,
                    })
                },
                error: function (xhr, status) {
                    alert('Disculpe, existió un problema');
                },
            });
            $("#modal-gregar-observacion").modal("hide");
        });
        /* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  MUESTRA EDITAR OBSERVACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% */
        $('table').on('click', '.editarobservacion', function (e) {
            e.preventDefault();
            let id_observacion = $(this).closest('tr').attr('id');
            $htmlobs = "";
            $.ajax({
                url: "../observacion/editar",
                data: {
                    id: id_observacion,
                },
                success: function (json) {
                    $("#editor1").html(json.observacion);
                    //$("#modal-mostrar").modal("hide");
                    $("#formulario-editar-observacion").empty();
                    $("#editar-observacion").modal("show");
                    $htmlobs += "<textarea cols='80' id='editor2' name='editor2' rows='10' data-sample-short>" + json.observacion + "</textarea>";
                    $htmlobs += "<input hidden class='form-control' type='text' name='observacion_id' value='" + json.id + "' id='observacion_id'>";
                    $htmlobs += "<div class='container-fluid h-100 mt-3'>";
                    $htmlobs += "<div class='row w-100 align-items-center'>";
                    $htmlobs += "<div class='col text-center'>";
                    $htmlobs += "<button type='submit' id='actualizarobservacion' class='btn btn-primary text-white btn-lg'>Guardar <i class='far fa-save'></i></button> ";
                    $htmlobs += "</div>";
                    $htmlobs += "</div>";
                    $htmlobs += "</div>";
                    $("#formulario-editar-observacion").append($htmlobs);
                    CKEDITOR.replace('editor2', {
                        height: 120,
                        width: "100%",
                        removeButtons: 'PasteFromWord'
                    });
                },
                error: function (xhr, status) {
                    alert('Disculpe, existió un problema');
                },
            });
        });
                
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ACTUALIZAR ENVIO DE FORMULARIO OBSERVACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $(document).on("submit", "#formulario-editar-observacion", function (e) {
            e.preventDefault();//detenemos el envio
            $observacion = $('#editor2').val();
            $observacion_id = $('#observacion_id').val();
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                url: "../observacion/actualizar/",
                data: {
                    observacion: $observacion,
                    observacion_id: $observacion_id,
                },
                success: function (json) {
                    tabla.ajax.reload();
                    $('#editar-observacion').modal('hide');
                },
                error: function (xhr, status) {
                    alert('Disculpe, existió un problema');
                },
            });
        });
        
        /**%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  DA DE BAJA UNA OBSERVACION UTILIZA AJAX %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.bajaobservacion', function (e) {
            e.preventDefault();
            let observacion_id = $(this).closest('tr').attr('id');
            $.ajax({
                url: "../darbaja/observacion",
                data: {
                    //obs: $("#observacionx").val(),
                    observacion_id: observacion_id,
                },
                success: function (json) {
                    $("#" + observacion_id).addTempClass('bg-success text-danger', 3000);
                    tabla.ajax.reload();
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                    })
                    Toast.fire({
                        type: 'success',
                        title: "Actualizado corectamente",
                    })

                },
                error: function (xhr, status) {
                    alert('Disculpe, existió un problema');
                },
            });
        });
        
        /**%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  DA DE ALTA UNA OBSERVACION QUE ESTA CON BAJA %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.altaobservacion', function (e) {
            e.preventDefault();
            let observacion_id = $(this).closest('tr').attr('id');
            $.ajax({
                url: "../daralta/observacion",
                data: {
                    //obs: $("#observacionx").val(),
                    observacion_id: observacion_id,
                },
                success: function (json) {
                    $("#" + observacion_id).addTempClass('bg-success', 3000);
                    tabla.ajax.reload();
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                    })
                    Toast.fire({
                        type: 'success',
                        title: "Actualizado corectamente",
                    })
                },
                error: function (xhr, status) {
                    alert('Disculpe, existió un problema');
                },
            });
        });

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ELIMINAR OBSERVACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% */
        $('table').on('click', '.eliminarobservacion', function (e) {
            e.preventDefault();
            let observacion_id = $(this).closest('tr').attr('id');
            Swal.fire({
                title: 'Estas seguro(a) de eliminar este registro?',
                text: "Si eliminas el registro no lo podras recuperar jamás!",
                icon: 'question',
                showCancelButton: true,
                showConfirmButton: true,
                confirmButtonColor: '#25ff80',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Eliminar..!',
                position: 'center',
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: '../eliminar/general',
                        type: 'DELETE',
                        data: {
                            observacion_id: observacion_id,
                            "_token": $("meta[name='csrf-token']").attr("content")
                        },
                        success: function (result) {
                            tabla.ajax.reload();
                            $("#modal-mostrar").modal("hide");
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
                } else {
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
    });    

    </script>
@stop
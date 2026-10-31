@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
    <link rel="stylesheet" href="{{asset('custom/css/custom.css')}}">
@stop

@section('plugins.Jquery', true)
@section('plugins.Sweetalert2', true)
@section('plugins.Datatables', true)
@section('title', 'Asistencia')

@section('content')
   
                <div class="card">
                    <div class="card-header bg-primary">
                        <div style="display: flex; justify-content: space-between; align-items: center;">

                            <span id="card_title">
                                {{ __('Inscripcione') }}
                            </span>

                            <div class="float-right">
                                
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="inscripciones_vigentes" class="table-bordered table-hover table-striped">
                                <thead class="thead">
                                    <tr>
										<th>Nombre</th>
                                    </tr>
                                </thead>
                                
                            </table>
                        </div>
                    </div>
                </div>
{{-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% TABLA MATRICULACION %%%%%%%%%%%%%%%%%%%%%%%%%% --}}
                <div class="card">
                    <div class="card-header bg-secondary">
                        <div style="display: flex; justify-content: space-between; align-items: center;">

                            <span id="card_title">
                                {{ __('Matriculaciones') }}
                            </span>

                            <div class="float-right">
                                {{-- <a href="{{ route('inscripciones.create') }}" class="btn btn-primary btn-sm float-right"  data-placement="left">
                                    {{ __('Nueva Inscripción') }}
                                </a> --}}
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="matriculaciones_vigentes" class="table-bordered table-hover table-striped">
                                <thead class="thead">
                                    <tr>
										<th>Nombre</th>
                                    </tr>
                                </thead>
                                
                            </table>
                        </div>
                    </div>
                </div>
            
    @include('cartera.modalesmatriculacions')
    @include('observacion.modalcreate')
    @include('cartera.modales')
    @include('whatsapp.modalmensajes')
@endsection
@section('js')
    <script src="https://cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.23/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/responsive.bootstrap4.min.js"></script> 
    <script src="{{asset('dist/js/moment.js')}}"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.17.1/locale/es.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
    <script src="{{ asset('ckeditor/ckeditor.js') }}"></script>
    <script src="{{asset('assets/js/observacion.js')}}"></script>
    <script src="{{asset('assets/js/configinscripcion.js')}}"></script>
    <script src="{{asset('assets/js/configmatriculacion.js')}}"></script>
    <script>
        let tablamensajes;
        let tablainscripciones;
        let tablamatriculaciones;
        let tablainscripcionesdesvigentes;
        let tablamatriculacionesdesvigentes;
    $(document).ready(function() {
        
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
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% CREAR OBSERVACION INSCIRPCION  %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.observacion', function (e) {
            e.preventDefault();
            let objeto_id = $(this).closest('tr').attr('id');
            
            $("#observable_id").val(objeto_id);
            $("#observable_type").val($(this).attr('id'));
            
            CKEDITOR.instances.editorguardar.setData("");
            $("#modal-agregar-observacion").modal("show");
        });
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% CLICK BOTON GUARDAR OBSERVACION INSCRIPCION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('#guardar-observacion').on('click', function (e) {
            e.preventDefault();
            
            let observable_id = $("#observable_id").val();
            
            let observable_type = $("#observable_type").val();
            //alert(observable_id);
            for (instance in CKEDITOR.instances) { CKEDITOR.instances[instance].updateElement() }
            observacion=$("#editorguardar").val();
            
            url ="../../guardar/observacion"
            guardarObservacion(observacion,observable_id,observable_type,url);
            
        });
        $('table').on('click', '.mostrarobservacionespersona', function(e) {
            e.preventDefault();
                observable_id =$(this).closest('tr').attr('id');
                observable_type ="Persona";
                url="../observaciones/" + observable_id + "/" + observable_type,
                mostrarCrudObservaciones(url);
                $("#modal-mostrar-observaciones").modal("show");
        });
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% DAR BAJA OBSERVACION INSCRIPCION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/

        $('table').on('click', '.bajaobservacion', function (e) {
            e.preventDefault();
            let observacion_id = $(this).closest('tr').attr('id');
            
            url="../../darbaja/observacion";
            darBaja(observacion_id,url);
        });
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% DAR ALTA OBSERVACION INSCRIPCION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.altaobservacion', function (e) {
            e.preventDefault();
            let observacion_id = $(this).closest('tr').attr('id');
            url="../../daralta/observacion";
            darAlta(observacion_id,url);
        });

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ELIMINAR OBSERVACION INSCRIPCION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.eliminarobservacion', function (e) {
            e.preventDefault();
            let observacion_id = $(this).closest('tr').attr('id');
            url="../../eliminar/general"
            eliminarObservacion(observacion_id,url);
        });
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% EDITAR OBSERVACION INSCRIPCION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.editarobservacion', function (e) {
            e.preventDefault();
            observacion_id =$(this).closest('tr').attr('id');
            url="../../observacion/editar";
            
            editarObservacion(observacion_id,url);
            $("#modal-mostrar-observaciones").modal("hide");
            $("#modal-editar-observacion").modal("show");
        });
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ACTUALIZAR OBSERVACION INSCRIPCION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('#actualizar-observacion').on('click', function (e){ 
            e.preventDefault();
            observacion_id =$("#observable_id").val();
            observacion=CKEDITOR.instances.editoreditar.getData();
            
            url="../../observacion/actualizar";
            actualizarObservacion(observacion_id,observacion,url)
            $("#modal-editar-observacion").modal("hide");
            // $("#modal-editar-observacion").modal("show");
        });
        /*%%%%%%%%%%%%%%%%%%%%% BAJA DE INSCRIPCION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.bajainscripcion', function (e) {
            e.preventDefault();
            Swal.fire({
                title: 'Estas seguro(a) de dar Baja esta inscripción?',
                text: "Si le das de baja el registro sera complicado recuperarlo!",
                icon: 'question',
                showCancelButton: true,
                showConfirmButton: true,
                confirmButtonColor: '#25ff80',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Dar Baja..!',
                position: 'center',
            }).then((result) => {
                if (result.value) {
                    let inscripcion_id = $(this).closest('tr').attr('id');
                    url="../../darbaja/inscripcion";
                    darBajaInscripcion(inscripcion_id,url);
                } else {
                    mensajePequenio('El registro NO se eliminó', 'error', 2000);
                }
            })
            
        });
        /*%%%%%%%%%%%%%%%%%%%%% BAJA DE MATRICULACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.bajamatriculacion', function (e) {
            e.preventDefault();
                Swal.fire({
                    title: 'Estas seguro(a) de dar Baja esta inscripción?',
                    text: "Si le das de baja el registro sera complicado recuperarlo!",
                    icon: 'question',
                    showCancelButton: true,
                    showConfirmButton: true,
                    confirmButtonColor: '#25ff80',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Dar Baja..!',
                    position: 'center',
                }).then((result) => {
                    if (result.value) {
                        let matriculacion_id = $(this).closest('tr').attr('id');
                        url="../../darbaja/matriculacion";
                        darBajaMatriculacionv(matriculacion_id,url);
                    } else {
                        mensajePequenio('El registro NO se eliminó', 'error', 2000);
                    }
                })
            
        });

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% MOSTRAR OBSERVACIONES MATRICULACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.mostrarobservacionesmatriculacion', function(e) {
            e.preventDefault();
                observable_id =$(this).closest('tr').attr('id');
                
                observable_type ="Matriculacion";
                url="../../observaciones/" + observable_id + "/" + observable_type,
                
                mostrarCrudObservaciones(url);
                $("#modal-mostrar-observaciones").modal("show");
        });
        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% MOSTRAR OBSERVACIONES INSCRIPCION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        $('table').on('click', '.mostrarobservaciones', function(e) {
            e.preventDefault();
                observable_id =$(this).closest('tr').attr('id');
                observable_type ="Inscripcione";
                url="../../observaciones/" + observable_id + "/" + observable_type,
                
                mostrarCrudObservaciones(url);
                $("#modal-mostrar-observaciones").modal("show");
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

        $('#modal-agregar-observacion').on('hidden.bs.modal', function (e) {
            $(".diverror").addClass("d-none");
        });
        
        $('#modal-editar-observacion').on('hidden.bs.modal', function (e) {
            $(".diverror").addClass("d-none");
        });
        function darBajaMatriculacionv(matriculacion_id,url) {
            $.ajax({
                url: url,
                data: {
                    matriculacion_id: matriculacion_id,
                },
                success: function (json) {
                    //$("#" + matriculacion_id).addTempClass('bg-success', 3000);
                    tablamatriculaciones.ajax.reload();
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 1500,
                    })
                    Toast.fire({
                        icon: 'success',
                        title: "Actualizado corectamente",
                    })

                },
                error: function (xhr, status) {
                    alert('Disculpe, existió un problema');
                },
            });
        }

        /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% MOSTRAR PERSONA DE INSCRIPCIONES %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/    
            $('table').on('click', '.mostrarpersona', function(e) {
                e.preventDefault();
                let inscripcion_id=$(this).closest('tr').attr('id'); 
                
                $("#tabla-mostrar-persona").empty();
                $html="";
                $.ajax({
                    url:"{{url('persona/mostrar/ajax')}}",
                    data:{
                        inscripcion_id:inscripcion_id,
                    },
                    success : function(json) {
                        $html+="<tr>"+"<td>NOMBRE</td><td>"+ json.persona.nombre+" "+json.persona.apellidop+" "+json.persona.apellidom+"</td><td rowspan='4'>"+IFEPhotos.render(json.persona.foto,'display',json.persona,{height:100})+"</td></tr>";
                        $html+="<tr>"+"<td>CARNET</td><td>"+ json.persona.carnet+" "+json.persona.expedido+"</td></tr>";
                        $html+="<tr>"+"<td>PAIS</td><td>"+ json.pais.nombrepais +", "+json.ciudad.ciudad+","+json.zona.zona+","+json.persona.direccion+"</td></tr>";
                        $html+="<tr>"+"<td>COMO LLEGOx</td><td>"+ json.persona.como +"</td></tr>";
                        $html+="<tr>"+"<td>FECHA NACIMIENTO</td><td colspan='2'>"+ moment(json.persona.fechanacimiento).format('DD-MM-YYYY')+ " "+json.edad+"</td></tr>";
                        $html+="<tr>"+"<td>GENERO</td><td colspan='2'>"+ json.persona.genero +"</td></tr>";
                        $html+="<tr>"+"<td>HABILITADO</td><td colspan='2'>"+ json.persona.habilitado +"</td></tr>";
                        $html+="<tr>"+"<td>PAPEL INICIAL</td><td colspan='2'>"+ json.persona.papelinicial +"</td></tr>";
                        $html+="<tr>"+"<td>TELEFONO</td><td colspan='2'>"+ json.persona.telefono +"</td></tr>";
                        $html+="<tr>"+"<td>VOTOS</td><td colspan='2'>"+ json.persona.votos +"</td></tr>";                        
                        $html+="<tr>"+"<td>CREADO</td><td colspan='2'>"+ moment(json.persona.created_at).format('DD-MM-YYYY') + " "+ json.creado+"</td></tr>";                        
                        $html+="<tr>"+"<td>ACTUALIZADO</td><td colspan='2'>"+ moment(json.persona.updated_at).format('DD-MM-YYYY')+ " "+ json.actualizado +"</td></tr>";  
                        $('#tabla-mostrar-persona').append($html); 
                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
                $("#modal-mostrar-persona").modal("show");
            });
           
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% MOSTRAR INSCRIPCION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/    
            $('table').on('click', '.mostrarinscripcion', function(e) {
                e.preventDefault();
                let inscripcion_id=$(this).closest('tr').attr('id'); 
                
                $("#tabla-mostrar-inscripcion").empty();
                $html="";
                $.ajax({
                    url:"{{url('inscripcion/mostrar/ajax')}}",
                    data:{
                        inscripcion_id:inscripcion_id,
                    },
                    success : function(json) {
                        $html+="<tr>"+"<td>NOMBRE</td><td>"+ json.persona.nombre+" "+json.persona.apellidop+" "+json.persona.apellidom+"</td><td rowspan='4'>"+IFEPhotos.render(json.persona.foto,'display',json.persona,{height:100})+"</td></tr>";
                        $html+="<tr>"+"<td>OBJETIVO</td><td>"+ json.inscripcion.objetivo+"</td></tr>";
                        $html+="<tr>"+"<td>COSTO</td><td>"+ json.inscripcion.costo+"</td></tr>";
                        $html+="<tr>"+"<td>TOTAL HORAS</td><td>"+ json.inscripcion.totalhoras +"</td></tr>";
                        $html+="<tr>"+"<td>INICIO</td><td colspan='2'>"+ moment(json.inscripcion.fechaini).format('DD-MM-YYYY')+ " "+json.empezo+"</td></tr>";
                        $html+="<tr>"+"<td>FINALIZA</td><td colspan='2'>"+ moment(json.inscripcion.fechafin).format('DD-MM-YYYY')+ " "+json.finaliza+"</td></tr>";
                        $html+="<tr>"+"<td>FECHA PAGO</td><td colspan='2'>"+ moment(json.inscripcion.fecha_proximo_pago).format('DD-MM-YYYY')+ " "+json.proximo_pago+"</td></tr>";
                        $html+="<tr>"+"<td>CONDONADO</td><td colspan='2'>"+ json.inscripcion.condonado +"</td></tr>";
                        $html+="<tr>"+"<td>OBJETIVO</td><td colspan='2'>"+ json.inscripcion.objetivo +"</td></tr>";;
                        $html+="<tr>"+"<td>MODALIDAD</td><td colspan='2'>"+ json.modalidad.modalidad +"</td></tr>";
                        $html+="<tr>"+"<td>MOTIVO</td><td colspan='2'>"+ json.motivo.motivo +"</td></tr>";
                        $html+="<tr>"+"<td>CREADO</td><td colspan='2'>"+ moment(json.persona.created_at).format('DD-MM-YYYY') + " "+ json.creado+"</td></tr>";                        
                        $html+="<tr>"+"<td>ACTUALIZADO</td><td colspan='2'>"+ moment(json.persona.updated_at).format('DD-MM-YYYY')+ " "+ json.actualizado +"</td></tr>";  
                        $('#tabla-mostrar-inscripcion').append($html); 
                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
                $("#modal-mostrar-inscripcion").modal("show");
            });
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% MOSTRAR PAGOS %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/    
            $('table').on('click', '.mostrarpagos', function(e) {
                e.preventDefault();
                let inscripcion_id=$(this).closest('tr').attr('id');
                $("#tabla-mostrar-pagos").empty();
                $("#pagos").empty();
                $("#modal-mostrar-pagos").modal("show");
                $.ajax({
                    url:"../../pagos/mostrar/ajax",
                    data:{
                        inscripcion_id:inscripcion_id,
                    },
                    success : function(json) {
                            $html="";
                            $html+="<tr>"+"<td>NOMBRE</td><td>"+ json.persona.nombre+" "+json.persona.apellidop+" "+json.persona.apellidom+"</td><td rowspan='4'>"+IFEPhotos.render(json.persona.foto,'display',json.persona,{height:100})+"</td></tr>";
                            $html+="<tr>"+"<td>ACUENTA</td><td>"+ json.acuenta+"</td></tr>";
                            $html+="<tr>"+"<td>SALDO</td><td>"+ json.saldo+"</td></tr>";
                            $html+="<tr>"+"<td>TOTAL</td><td>"+ json.total+"</td></tr>";
                            $('#tabla-mostrar-pagos').append($html); 
                            
                            $pagosHtml="";
                             for (let j in json.pagos) {
                                $pagosHtml+="<tr id='"+ json.pagos[j].id +"' ><td>"+ json.pagos[j].id +"</td>";
                                $pagosHtml+="<td>"+json.pagos[j].monto+"</td>";
                                $pagosHtml+="<td>"+json.pagos[j].pagocon+"</td>";
                                $pagosHtml+="<td>"+json.pagos[j].cambio+"</td>";
                                $pagosHtml+="<td>"+moment(json.pagos[j].created_at).format('LLL')+"</td>";
                                $pagosHtml+="<td><a class='detallarpago btn-accion-tabla tooltipsC mr-2' title='Ver este pago'><i class='fa fa-fw fa-eye text-primary'></i></a></td>";
                            }
                            $('#pagos').append($pagosHtml); 
                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
               
            });

            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% DETALLA PAGO DE INSCRIPCION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
               $('table').on('click', '.detallarpago', function(e) {
                e.preventDefault(); 
                var pago_id =$(this).closest('tr').attr('id');

                var fila=$(this).closest('tr');
                
                $.ajax({
                    url : "../../pago/mostrar/"+pago_id,
                    
                    success : function(json) {
                        $("#modal-detallar-pago").modal("show");
                        $("#tabla-billete-pago").empty();
                        $("#tabla-billete-cambio").empty();
                        $("#tabla-detalle-pago").empty();
                        $html="";
                        $html+="<tr><td>Monto</td>"+"<td>Bs. "+json.pago.monto+"</td></tr>";
                        $html+="<tr><td>Pago Con</td>"+"<td>Bs. "+json.pago.pagocon+"</td></tr>";
                        $html+="<tr><td>Cambio</td>"+"<td>Bs. "+json.pago.cambio+"</td></tr>";
                        $html+="<tr><td>Usuario</td>"+"<td>"+json.user.name+"</td></tr>";
                        $html+="<tr><td>Fecha y hora Pago</td>"+"<td>"+moment(json.pago.created_at).format('LLLL')+"</td></tr>";
                        $html+="<tr><td>Ultima Actualizacion</td>"+"<td>"+moment(json.pago.updated_at).format('LLLL')+"</td></tr>";
                        $("#tabla-detalle-pago").append($html);    
                        
                        $htmlBilletesPago="";
                        $htmlBilletesCambio="";
                        $sumaPago=0;
                        $sumaCambio=0;
                        for (let j in json.billetes) {
                            if(json.billetes[j].pivot.tipo=='pago'){
                                $htmlBilletesPago+="<tr><td>Corte de Bs. "+json.billetes[j].corte+"</td>"+"<td>"+json.billetes[j].pivot.cantidad+"</td><td>"+ json.billetes[j].pivot.tipo +"</td><td>Bs. "+  json.billetes[j].pivot.cantidad*json.billetes[j].corte +"</td></tr>";
                                $sumaPago+=json.billetes[j].corte*json.billetes[j].pivot.cantidad;
                                
                            }else{
                                $htmlBilletesCambio+="<tr><td>Corte de Bs. "+json.billetes[j].corte+"</td>"+"<td>"+json.billetes[j].pivot.cantidad+"</td><td>"+ json.billetes[j].pivot.tipo +"</td><td>Bs. "+  json.billetes[j].pivot.cantidad*json.billetes[j].corte +"</td></tr>";
                                $sumaCambio+=json.billetes[j].corte*json.billetes[j].pivot.cantidad;
                            }
                        }
                        $htmlBilletesPago+="<tr><td colspan='3'>T&nbsp;&nbsp;O&nbsp;&nbsp;T&nbsp;&nbsp;A&nbsp;&nbsp;L&nbsp;&nbsp;&nbsp;&nbsp;P&nbsp;&nbsp;A&nbsp;&nbsp;G&nbsp;&nbsp;O&nbsp;&nbsp; </td>"+"<td>Bs. "+$sumaPago+"</td></tr>";
                        $htmlBilletesCambio+="<tr><td colspan='3'>T&nbsp;&nbsp;O&nbsp;&nbsp;T&nbsp;&nbsp;A&nbsp;&nbsp;L&nbsp;&nbsp;&nbsp;&nbsp;C&nbsp;&nbsp;A&nbsp;&nbsp;M&nbsp;&nbsp;B&nbsp;&nbsp;I&nbsp;&nbsp;O&nbsp;&nbsp;</td>"+"<td>Bs. "+$sumaCambio+"</td></tr>";
                        $("#tabla-billete-pago").append($htmlBilletesPago);
                        $("#tabla-billete-cambio").append($htmlBilletesCambio);

                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
            });


            (function($, window) {
                'use strict';

                var MultiModal = function(element) {
                    this.$element = $(element);
                    this.modalCount = 0;
                };

                MultiModal.BASE_ZINDEX = 1040;

                MultiModal.prototype.show = function(target) {
                    var that = this;
                    var $target = $(target);
                    var modalIndex = that.modalCount++;

                    $target.css('z-index', MultiModal.BASE_ZINDEX + (modalIndex * 20) + 10);

                
                    window.setTimeout(function() {
                        // we only want one backdrop; hide any extras
                        if(modalIndex > 0)
                            $('.modal-backdrop').not(':first').addClass('hidden');

                        that.adjustBackdrop();
                    });
                };

                MultiModal.prototype.hidden = function(target) {
                    this.modalCount--;

                    if(this.modalCount) {
                    this.adjustBackdrop();
                        // bootstrap removes the modal-open class when a modal is closed; add it back
                        $('body').addClass('modal-open');
                    }
                };

                MultiModal.prototype.adjustBackdrop = function() {
                    var modalIndex = this.modalCount - 1;
                    $('.modal-backdrop:first').css('z-index', MultiModal.BASE_ZINDEX + (modalIndex * 20));
                };

                function Plugin(method, target) {
                    return this.each(function() {
                        var $this = $(this);
                        var data = $this.data('multi-modal-plugin');

                        if(!data)
                            $this.data('multi-modal-plugin', (data = new MultiModal(this)));

                        if(method)
                            data[method](target);
                    });
                }

                $.fn.multiModal = Plugin;
                $.fn.multiModal.Constructor = MultiModal;

                $(document).on('show.bs.modal', function(e) {
                    $(document).multiModal('show', e.target);
                });

                $(document).on('hidden.bs.modal', function(e) {
                    $(document).multiModal('hidden', e.target);
                });
            }(jQuery, window));
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% MOSTRAR PROGRAMACION DE INSCRIPCION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            $('table').on('click', '.mostrarprogramacion', function(e) {
                 e.preventDefault();
                let inscripcion_id=$(this).closest('tr').attr('id'); 
                
                $programacionHtml="";
                $("#tabla-programacion").empty();
                $.ajax({
                    url:"{{url('programacion/mostrar/ajax')}}",
                    data:{
                        inscripcion_id:inscripcion_id,
                    },
                    success : function(json) {
                            clase="";
                            for (let j in json) {
                                if(moment(json[j].fecha)<moment.now())
                                    clase="table-success text-success";
                                else 
                                    clase="table-danger text-danger";
                                if(moment(json[j].fecha).format('Y-MM-DD')==(moment().format('Y-MM-DD')))
                                    clase="bg-success text-white";
                                $programacionHtml+="<tr class='"+clase+"' id='"+ json[j].id +"' ><td>"+ (parseInt(j)+1) +"</td>";
                                $programacionHtml+="<td>"+moment(json[j].fecha).format('DD-MM-YYYY')+"</td>";
                                $programacionHtml+="<td>"+moment(json[j].hora_ini).format('hh:mm:ss')+"-"+moment(json[j].hora_fin).format('hh:mm:ss')+"</td>";
                                $programacionHtml+="<td>"+json[j].nombre+'/'+json[j].aula+"</td>";
                                $programacionHtml+="<td>"+json[j].estado+"</td>";
                                $programacionHtml+="<td>"+json[j].materia+"</td>";
                            }
                            $('#tabla-programacion').append($programacionHtml); 
                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
                $("#modal-mostrar-programacion").modal("show");
            });

            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% MATRICULACION JS %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% MOSTRAR PERSONA DE MATRICULACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/    
            $('table').on('click', '.mostrarpersonamatriculacion', function(e) {
                e.preventDefault();
                let matriculacion_id=$(this).closest('tr').attr('id'); 
                
                $("#tabla-mostrar-persona").empty();
                $html="";
                $.ajax({
                    url:"{{url('persona/mostrar/ajax/matriculacion')}}",
                    data:{
                        matriculacion_id:matriculacion_id,
                    },
                    success : function(json) {
                        //
                        $html+="<tr>"+"<td>NOMBRE</td><td>"+ json.persona.nombre+" "+json.persona.apellidop+" "+json.persona.apellidom+"</td><td rowspan='4'>"+IFEPhotos.render(json.persona.foto,'display',json.persona,{height:100})+"</td></tr>";
                        $html+="<tr>"+"<td>CARNET</td><td>"+ json.persona.carnet+" "+json.persona.expedido+"</td></tr>";
                        $html+="<tr>"+"<td>PAIS</td><td>"+ json.pais.nombrepais +", "+json.ciudad.ciudad+","+json.zona.zona+","+json.persona.direccion+"</td></tr>";
                        $html+="<tr>"+"<td>COMO LLEGO</td><td>"+ json.persona.como +"</td></tr>";
                        $html+="<tr>"+"<td>FECHA NACIMIENTO</td><td colspan='2'>"+ moment(json.persona.fechanacimiento).format('DD-MM-YYYY')+ " "+json.edad+"</td></tr>";
                        $html+="<tr>"+"<td>GENERO</td><td colspan='2'>"+ json.persona.genero +"</td></tr>";
                        $html+="<tr>"+"<td>HABILITADO</td><td colspan='2'>"+ json.persona.habilitado +"</td></tr>";
                        $html+="<tr>"+"<td>PAPEL INICIAL</td><td colspan='2'>"+ json.persona.papelinicial +"</td></tr>";
                        $html+="<tr>"+"<td>TELEFONO</td><td colspan='2'>"+ json.persona.telefono +"</td></tr>";
                        $html+="<tr>"+"<td>VOTOS</td><td colspan='2'>"+ json.persona.votos +"</td></tr>";                        
                        $html+="<tr>"+"<td>CREADO</td><td colspan='2'>"+ moment(json.persona.created_at).format('DD-MM-YYYY') + " "+ json.creado+"</td></tr>";                        
                        $html+="<tr>"+"<td>ACTUALIZADO</td><td colspan='2'>"+ moment(json.persona.updated_at).format('DD-MM-YYYY')+ " "+ json.actualizado +"</td></tr>";  
                        $('#tabla-mostrar-persona').append($html); 
                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
                $("#modal-mostrar-persona").modal("show");
            });

            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% MOSTRAR PERSONA DE MATRICULACION DESVIGENTE %%%%%%%%%%%%%%%%%%%%%%%%%%% */
            $('table').on('click', '.mostrarpersonamatriculaciondesvigente', function(e) {
                e.preventDefault();
                let persona_id=$(this).closest('tr').attr('id'); 
                
                $("#tabla-mostrar-persona").empty();
                $html="";
                $.ajax({
                    url:"{{url('persona/mostrar')}}",
                    data:{
                        persona_id:persona_id,
                    },
                    success : function(json) {
                        $html+="<tr>"+"<td>NOMBRE</td><td>"+ json.persona.nombre+" "+json.persona.apellidop+" "+json.persona.apellidom+"</td><td rowspan='4'>"+IFEPhotos.render(json.persona.foto,'display',json.persona,{height:100})+"</td></tr>";
                        $html+="<tr>"+"<td>CARNET</td><td>"+ json.persona.carnet+" "+json.persona.expedido+"</td></tr>";
                        $html+="<tr>"+"<td>PAIS</td><td>"+ json.pais.nombrepais +", "+json.ciudad.ciudad+","+json.zona.zona+","+json.persona.direccion+"</td></tr>";
                        $html+="<tr>"+"<td>COMO LLEGO</td><td>"+ json.persona.como +"</td></tr>";
                        $html+="<tr>"+"<td>FECHA NACIMIENTO</td><td colspan='2'>"+ moment(json.persona.fechanacimiento).format('DD-MM-YYYY')+ " "+json.edad+"</td></tr>";
                        $html+="<tr>"+"<td>GENERO</td><td colspan='2'>"+ json.persona.genero +"</td></tr>";
                        $html+="<tr>"+"<td>HABILITADO</td><td colspan='2'>"+ json.persona.habilitado +"</td></tr>";
                        $html+="<tr>"+"<td>PAPEL INICIAL</td><td colspan='2'>"+ json.persona.papelinicial +"</td></tr>";
                        $html+="<tr>"+"<td>TELEFONO</td><td colspan='2'>"+ json.persona.telefono +"</td></tr>";
                        $html+="<tr>"+"<td>VOTOS</td><td colspan='2'>"+ json.persona.votos +"</td></tr>";                        
                        $html+="<tr>"+"<td>CREADO</td><td colspan='2'>"+ moment(json.persona.created_at).format('DD-MM-YYYY') + " "+ json.creado+"</td></tr>";                        
                        $html+="<tr>"+"<td>ACTUALIZADO</td><td colspan='2'>"+ moment(json.persona.updated_at).format('DD-MM-YYYY')+ " "+ json.actualizado +"</td></tr>";  
                        $('#tabla-mostrar-persona').append($html); 
                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
                $("#modal-mostrar-persona").modal("show");
            });
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% MOSTRAR MATRICULACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/    
            $('table').on('click', '.mostrarmatriculacion', function(e) {
                e.preventDefault();
                let matriculacion_id=$(this).closest('tr').attr('id'); 
                
                $("#tabla-mostrar-matriculacion").empty();
                $html="";
                $.ajax({
                    url:"{{url('matriculacioncita/ajax/show')}}",
                    data:{
                        matriculacion_id:matriculacion_id,
                    },
                    success : function(json) {
                        
                        $html+="<tr>"+"<td>NOMBRE</td><td>"+ json.persona.nombre+" "+json.persona.apellidop+" "+json.persona.apellidom+"</td><td rowspan='4'>"+IFEPhotos.render(json.persona.foto,'display',json.persona,{height:100})+"</td></tr>";
                        $html+="<tr>"+"<td>OBJETIVO</td><td>"+ json.matriculacion.objetivo+"</td></tr>";
                        $html+="<tr>"+"<td>COSTO</td><td>"+ json.matriculacion.costo+"</td></tr>";
                        $html+="<tr>"+"<td>TOTAL HORAS</td><td>"+ json.matriculacion.totalhoras +"</td></tr>";
                        $html+="<tr>"+"<td>INICIO</td><td colspan='2'>"+ moment(json.matriculacion.fechaini).format('DD-MM-YYYY')+ " "+json.empezo+"</td></tr>";
                        $html+="<tr>"+"<td>FINALIZA</td><td colspan='2'>"+ moment(json.matriculacion.fechafin).format('DD-MM-YYYY')+ " "+json.finaliza+"</td></tr>";
                        $html+="<tr>"+"<td>FECHA PAGO</td><td colspan='2'>"+ moment(json.matriculacion.fecha_proximo_pago).format('DD-MM-YYYY')+ " "+json.proximo_pago+"</td></tr>";
                        $html+="<tr>"+"<td>CONDONADO</td><td colspan='2'>"+ json.matriculacion.condonado +"</td></tr>";
                        $html+="<tr>"+"<td>MODALIDAD</td><td colspan='2'>"+ json.asignatura.asignatura +"</td></tr>";
                        $html+="<tr>"+"<td>MOTIVO</td><td colspan='2'>"+ json.motivo.motivo +"</td></tr>";
                        $html+="<tr>"+"<td>CREADO</td><td colspan='2'>"+ moment(json.persona.created_at).format('DD-MM-YYYY') + " "+ json.creado+"</td></tr>";                        
                        $html+="<tr>"+"<td>ACTUALIZADO</td><td colspan='2'>"+ moment(json.persona.updated_at).format('DD-MM-YYYY')+ " "+ json.actualizado +"</td></tr>";  
                        $('#tabla-mostrar-matriculacion').append($html); 
                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
                $("#modal-mostrar-matriculacion").modal("show");
            });
            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% MOSTRAR PAGOS %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/    
            $('table').on('click', '.mostrarpagosmatriculacion', function(e) {
                e.preventDefault();
                let matriculacion_id=$(this).closest('tr').attr('id'); 
                
                
                $("#modal-mostrar-pagoscom").modal("show");
                $("#tabla-mostrar-pagoscom").empty();
                $("#pagoscom").empty();
                $html="";
                $.ajax({
                    url:"{{url('pagoscom/mostrar/ajax')}}",
                    data:{
                        matriculacion_id:matriculacion_id,
                    },
                    success : function(json) {
                        
                            $html+="<tr>"+"<td>NOMBRE</td><td>"+ json.persona.nombre+" "+json.persona.apellidop+" "+json.persona.apellidom+"</td><td rowspan='4'>"+IFEPhotos.render(json.persona.foto,'display',json.persona,{height:100})+"</td></tr>";
                            $html+="<tr>"+"<td>ACUENTA</td><td>"+ json.acuenta+"</td></tr>";
                            $html+="<tr>"+"<td>SALDO</td><td>"+ json.saldo+"</td></tr>";
                            $html+="<tr>"+"<td>TOTAL</td><td>"+ json.total+"</td></tr>";
                            $('#tabla-mostrar-pagoscom').append($html); 
                            $pagosHtml="";
                            for (let j in json.pagos) {
                                $pagosHtml+="<tr id='"+ json.pagos[j].id +"' ><td>"+ json.pagos[j].id +"</td>";
                                $pagosHtml+="<td>"+json.pagos[j].monto+"</td>";
                                $pagosHtml+="<td>"+json.pagos[j].pagocon+"</td>";
                                $pagosHtml+="<td>"+json.pagos[j].cambio+"</td>";
                                $pagosHtml+="<td>"+moment(json.pagos[j].created_at).format('LLL')+"</td>";
                                $pagosHtml+="<td><a class='detallarpago btn-accion-tabla tooltipsC mr-2' title='Ver este pago'><i class='fa fa-fw fa-eye text-primary'></i></a></td>";
                            }
                            $('#pagoscom').append($pagosHtml); 

                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
                
            });

            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% DETALLA PAGO %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
                $('table').on('click', '.detallarpagomatriculacion', function(e) {
                e.preventDefault(); 
                var pago_id =$(this).closest('tr').attr('id');

                var fila=$(this).closest('tr');
                
                $.ajax({
                    url : "../../pago/mostrar/"+pago_id,
                    
                    success : function(json) {
                        //
                        $("#modal-detallar-pago").modal("show");
                        $("#tabla-billete-pago").empty();
                        $("#tabla-billete-cambio").empty();
                        $("#tabla-detalle-pago").empty();
                        $html="";
                        $html+="<tr><td>Monto</td>"+"<td>Bs. "+json.pago.monto+"</td></tr>";
                        $html+="<tr><td>Pago Con</td>"+"<td>Bs. "+json.pago.pagocon+"</td></tr>";
                        $html+="<tr><td>Cambio</td>"+"<td>Bs. "+json.pago.cambio+"</td></tr>";
                        $html+="<tr><td>Usuario</td>"+"<td>"+json.user.name+"</td></tr>";
                        $html+="<tr><td>Fecha y hora Pago</td>"+"<td>"+moment(json.pago.created_at).format('LLLL')+"</td></tr>";
                        $html+="<tr><td>Ultima Actualizacion</td>"+"<td>"+moment(json.pago.updated_at).format('LLLL')+"</td></tr>";
                        $("#tabla-detalle-pago").append($html);    
                        
                        $htmlBilletesPago="";
                        $htmlBilletesCambio="";
                        $sumaPago=0;
                        $sumaCambio=0;
                        for (let j in json.billetes) {
                            if(json.billetes[j].pivot.tipo=='pago'){
                                $htmlBilletesPago+="<tr><td>Corte de Bs. "+json.billetes[j].corte+"</td>"+"<td>"+json.billetes[j].pivot.cantidad+"</td><td>"+ json.billetes[j].pivot.tipo +"</td><td>Bs. "+  json.billetes[j].pivot.cantidad*json.billetes[j].corte +"</td></tr>";
                                $sumaPago+=json.billetes[j].corte*json.billetes[j].pivot.cantidad;
                                
                            }else{
                                $htmlBilletesCambio+="<tr><td>Corte de Bs. "+json.billetes[j].corte+"</td>"+"<td>"+json.billetes[j].pivot.cantidad+"</td><td>"+ json.billetes[j].pivot.tipo +"</td><td>Bs. "+  json.billetes[j].pivot.cantidad*json.billetes[j].corte +"</td></tr>";
                                $sumaCambio+=json.billetes[j].corte*json.billetes[j].pivot.cantidad;
                            }
                        }
                        $htmlBilletesPago+="<tr><td colspan='3'>T&nbsp;&nbsp;O&nbsp;&nbsp;T&nbsp;&nbsp;A&nbsp;&nbsp;L&nbsp;&nbsp;&nbsp;&nbsp;P&nbsp;&nbsp;A&nbsp;&nbsp;G&nbsp;&nbsp;O&nbsp;&nbsp; </td>"+"<td>Bs. "+$sumaPago+"</td></tr>";
                        $htmlBilletesCambio+="<tr><td colspan='3'>T&nbsp;&nbsp;O&nbsp;&nbsp;T&nbsp;&nbsp;A&nbsp;&nbsp;L&nbsp;&nbsp;&nbsp;&nbsp;C&nbsp;&nbsp;A&nbsp;&nbsp;M&nbsp;&nbsp;B&nbsp;&nbsp;I&nbsp;&nbsp;O&nbsp;&nbsp;</td>"+"<td>Bs. "+$sumaCambio+"</td></tr>";
                        $("#tabla-billete-pago").append($htmlBilletesPago);
                        $("#tabla-billete-cambio").append($htmlBilletesCambio);

                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
            });

            /**%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  GUARDA OBSERVACION CON DE UNA INSCRIPCION  AJAX %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            $('#guardar-observacion-inscripcion').on('click', function (e) {
                e.preventDefault();
                $("#errores").empty();
                let observable_id = $("#observable_id_inscripcion").val();
                let observable_type = $("#observable_type_inscripcion").val();
                for (instance in CKEDITOR.instances) { CKEDITOR.instances[instance].updateElement() }
                $.ajax({
                    url: "../../guardar/observacion",
                    data: {
                        //obs: $("#observacionx").val(),
                        observacion: $("#editor3").val(),
                        observable_id: observable_id,
                        observable_type: observable_type,
                    },
                    success: function (json) {
                        if(json.errores){
                            
                            $html="";
                            for (let j in json.errores) {
                                $html+="<li>"+ json.errores.observacion[0] +"</li>";
                            }
                            $("#erroresdiv").removeClass('d-none');
                            $("#errores").append($html);
                        }else{
                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000,
                            })
                            Toast.fire({
                                type: 'success',
                                title: "Guardado correctamente: " + json.observacion,
                            })
                            $("#modal-agregar-observacion-inscripcion").modal("hide");
                        }
                    },
                    error: function (xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
                
            });

             CKEDITOR.replace('editor4', {
                height: 120,
                width: "100%",
                removeButtons: 'PasteFromWord'
            });
            /**%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  GUARDA OBSERVACION DE MATRICULACION %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            $('#guardar-observacion-matriculacion').on('click', function (e) {
                e.preventDefault();
                $("#errores").empty();
                let observable_id = $("#observable_id_matriculacion").val();
                let observable_type = $("#observable_type_matriculacion").val();
                alert(observable_type);
                for (instance in CKEDITOR.instances) { CKEDITOR.instances[instance].updateElement() }
                
                $.ajax({
                    url: "../../guardar/observacion",
                    data: {
                        //obs: $("#observacionx").val(),
                        observacion: $("#editor4").val(),
                        observable_id: observable_id,
                        observable_type: observable_type,
                    },
                    success: function (json) {
                        
                        if(json.errores){
                            
                            $html="";
                            for (let j in json.errores) {
                                $html+="<li>"+ json.errores.observacion[0] +"</li>";
                            }
                            $("#erroresdiv").removeClass('d-none');
                            $("#errores").append($html);
                        }else{
                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000,
                            })
                            Toast.fire({
                                icon: 'success',
                                title: "Guardado correctamente: " + json.observacion,
                            })
                            $("#modal-agregar-observacion-matriculacion").modal("hide");
                        }
                    },
                    error: function (xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
                
            });
         

            /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% AGREAGAR MOSTRAR PROGRAMACIONCOM %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
            $('table').on('click', '.mostrarprogramacionmatriculacion', function(e) {
                 e.preventDefault();
                let matriculacion_id=$(this).closest('tr').attr('id'); 
                
                $programacionHtml="";
                $("#tabla-programacioncom").empty();
                $.ajax({
                    url:"{{url('programacioncom/mostrar/ajax')}}",
                    data:{
                        matriculacion_id:matriculacion_id,
                    },
                    success : function(json) {
                            clase="";
                            for (let j in json) {
                                if(moment(json[j].fecha)<moment.now())
                                    clase="table-success text-success";
                                else 
                                    clase="table-danger text-danger";
                                if(moment(json[j].fecha).format('Y-MM-DD')==(moment().format('Y-MM-DD')))
                                    clase="bg-success text-white";
                                $programacionHtml+="<tr class='"+ clase +"' id='"+ json[j].id +"' ><td>"+ (parseInt(j)+1) +"</td>";
                                $programacionHtml+="<td>"+moment(json[j].fecha).format('DD-MM-YYYY')+"</td>";
                                $programacionHtml+="<td>"+moment(json[j].horaini).format('hh:mm:ss')+"-"+moment(json[j].horafin).format('hh:mm:ss')+"</td>";
                                $programacionHtml+="<td>"+json[j].nombrecorto+'/'+json[j].aula+"</td>";
                                $programacionHtml+="<td>"+json[j].estado+"</td>";
                            }
                            $('#tabla-programacioncom').append($programacionHtml); 

                    },
                    error : function(xhr, status) {
                        alert('Disculpe, existió un problema');
                    },
                });
                $("#modal-mostrar-programacioncom").modal("show");
                
            });


        //%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  script %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
        /* este codigo o algoritmo coloca falta a todas las clases anterior que no hayan sido marcados*/
        $.ajax({
            url : '../../programacion/asignarfalta/ajax',
            success : function(json) {

            },
            error : function(xhr, status) {
                alert('Disculpe, existió un SXXX problema');
            },
        });
        $.ajax({
            url : '../../programacioncom/asignarfalta/ajax',
            success : function(json) {
            },
            error : function(xhr, status) {
                alert('Disculpe, existió un SXXX problema');
            },
        });


        tablainscripciones=$('#inscripciones_vigentes').DataTable(
                {
                    "serverSide": true,
                    "responsive":true,
                    "autoWidth":false, 
                    "ajax": {
                            "url":"{{ url('inscripciones/vigentes/ajax')}}",
                        },
                        "createdRow": function( row, data, dataIndex ) {
                            $(row).attr('id',data['id']); // agrega dinamiacamente el id del row

                            $.ajax({
                                url : '../../programacion/asistencia/ajax',
                                data : { inscripcione_id : data['id'] },
                                type : 'GET',
                                dataType : 'json',
                                success : function(json) {
                                    $.each(json, function(i, item) {
                                        
                                        var clase=ConfiguraClase(item.estado);
                                        switch (item.estado) {
                                                case "FINALIZADO":
                                                    $icono ="<i class='"+clase+" far fa-check-circle'></i>";
                                                    break;
                                                case "PRESENTE":
                                                    $icono ="<i class='"+clase+" far fa-user-check'></i>";
                                                    break;
                                                case "FALTA":
                                                     $icono ="<i class='"+clase+" far fa-times-circle'></i>";
                                                    break;
                                            
                                                case "LICENCIA":
                                                     $icono ="<i class='"+clase+" fas fa-user-injured'></i>";
                                                    break;
                                            
                                                case "INDEFINIDO":
                                                    // $icono ="<i class='"+clase+" far fa-calendar-check'></i>";
                                                    $icono ="<i class='"+clase+" far fa-question-circle'></i>";
                                                    break;
                                            
                                                default:
                                                    break;
                                            }
                                            $("#"+data['id']).append("<td>"+$icono+"</td>")
                                        //$("#"+data['id']).find("td:last").addClass(clase);
                                        
                                    });
                                },
                                error : function(xhr, status) {
                                    alert('Disculpe, existió un problema normal');
                                },
                            });
                        },
                    "columns": [
                        {data:'persona_id'},
						{data: 'btn'},
                    ],
                    "language":{
                        "url":"https://cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"
                    },  
                }
            );
        tablamatriculaciones=$('#matriculaciones_vigentes').DataTable(
                {
                    "serverSide": true,
                    "responsive":true,
                    "autoWidth":false, 
                    "ajax": {
                            "url":"{{ url('matriculaciones/vigentes/ajax')}}",
                        },
                        "createdRow": function( row, data, dataIndex ) {
                            $(row).attr('id',data['id']); // agrega dinamiacamente el id del row
                            // estudiar este com
                            $.ajax({
                                url : '../../programacioncom/asistencia/ajax',
                                data : { matriculacion_id : data['id'] },
                                type : 'GET',
                                dataType : 'json',
                                success : function(json) {

                                    $.each(json, function(i, item) {
                                        var clase=ConfiguraClase(item.estado);
                                        
                                        switch (item.estado) {
                                                case "FINALIZADO":
                                                    $icono ="<i class='"+clase+" far fa-check-circle'></i>";
                                                    break;
                                                case "PRESENTE":
                                                    $icono ="<i class='"+clase+" far fa-user-check'></i>";
                                                    break;
                                                case "FALTA":
                                                     $icono ="<i class='"+clase+" far fa-times-circle'></i>";
                                                    break;
                                            
                                                case "LICENCIA":
                                                     $icono ="<i class='"+clase+" fas fa-user-injured'></i>";
                                                    break;
                                            
                                                case "INDEFINIDO":
                                                    // $icono ="<i class='"+clase+" far fa-calendar-check'></i>";
                                                    $icono ="<i class='"+clase+" far fa-question-circle'></i>";
                                                    break;
                                            
                                                default:
                                                    break;
                                            }
                                            $("#"+data['id']).append("<td>"+$icono+"</td>");
                                        
                                    });
                                },
                                error : function(xhr, status) {
                                    alert('Disculpe, existió un problema com');
                                },
                            });
                        },
                    "columns": [
                        {data:'persona_id'},
                        {data:'btn'},
						
                    ],
                    "language":{
                        "url":"https://cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"
                    },  
                }
            );

            function ConfiguraClase(unEstado) {
                var clase="";
                    if(unEstado =="FINALIZADO"){
                        clase="text-success";
                    }
                    if(unEstado =="FALTA"){
                        clase="text-danger";
                    }
                    if(unEstado =="LICENCIA"){
                        clase="text-blue";
                    }
                    if(unEstado =="INDEFINIDO"){
                        clase="text-gray"; 
                    }
                return clase;
            }
        } );
    </script>
@stop

{{--crear otra vista para la asistencia de computacion y tratar de importar en la misma vista de inscripciones vigentes %%%%%%%%%%%%%%%%%%%% --}}
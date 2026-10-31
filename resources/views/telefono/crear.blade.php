@extends('adminlte::page')
@section('css')
    {{-- <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}"> --}}
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
@stop

@section('title', 'Crear apoderado')

@section('plugins.Jquery', true)
@section('plugins.Sweetalert2', true)
@section('plugins.Datatables', true)

@section('content')
    
    <div class="card">
        <div class="card-header bg-primary">
            <span class="text-center">{{$persona->nombre.' '.$persona->apellidop.' '.$persona->apellidom}}</span>
            @can('Listar Contactos')<a href="{{ route('contactos.index') }}" class="btn btn-sm btn-light float-right ml-2">Google Contactos</a>@endcan
            <h3 class="text-white float-right" id="tokenExpirationform"></h3>
           
            <div class="float-right">
                
                @isset($persona->estudiante)
                    <a href="{{route('opcion.principal', $persona->estudiante->id)}}" class="btn btn-success btn-sm float-right"  data-placement="left">
                    {{ __('Inscribir ') }}<i class="fas fa-arrow-circle-right fa-2x"></i>
                    </a>
                @endisset
                
            </div>
            <div class="float-right mr-3">
           
                <a href="{{ route('telefonos.persona',$persona) }}" class="btn btn-secondary btn-sm float-right ml-1" data-placement="left">
                    {{ __('Listar telefonos ') }} <i class="fas fa-phone"></i>
                </a>
                <a href="" class="btn btn-warning btn-sm float-right" id="existente"  data-placement="left">
                    {{ __('Familiar Existente ') }}<i class="fas fa-arrow-circle-right fa-2x"></i>
                </a>

            </div>
        </div>
        <div class="card-body">
            <form action="{{route('persona.storeContacto',$persona)}}" method="POST" enctype="multipart/form-data">
            @csrf
                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4">
                        @if($errors->has('nombre'))
                            <span class="text-danger"> {{ $errors->first('nombre')}}</span>
                        @endif
                    </div>
                    <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4">    
                        @if($errors->has('apellidop'))
                            <span class="text-danger"> {{ $errors->first('apellidop')}}</span>
                        @endif
                    </div>
                    <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4">    
                        @if($errors->has('apellidom'))
                            <span class="text-danger"> {{ $errors->first('apellidom')}}</span>
                        @endif
                    </div>
                </div>


                {{-- $$$$$$$$$$$ CAMPO NOMBRE INICIO --}}
                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4" > 
                        <div class="form-floating mb-3 text-gray">
                            <input  type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{old('nombre')}}" placeholder="Ingrese un  nombre">
                            <label class="text-secondary" for="nombre">NOMBRE*</label>
                        </div>
                    </div>
                    {{-- %%%%%%%%%%%%%%% CAMPO APELLIDO PATERNO --}}
                    <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4" >
                        <div class="form-floating mb-3 text-gray">
                            <input  type="text" name="apellidop" class="form-control @error('apellidop') is-invalid @enderror" value="{{old('apellidop')}}" placeholder="Ingrese  apellido Paterno(Obligatorio)">
                            <label class="text-secondary" for="nombre">Paterno*</label>
                        </div>    
                    </div>
                    {{-- %%%%%%%%%%%%%%% CAMPO APELLIDO MATERNO --}}
                    <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4" >
                        <div class="form-floating mb-3 text-gray">
                            <input  type="text" name="apellidom" class="form-control @error('apellidom') is-invalid @enderror" value="{{old('apellidom')}}" placeholder="Ingrese  apellido Paterno(Obligatorio)">
                            <label class="text-secondary" for="apellidom">Materno*</label>
                        </div>    
                    </div>
                </div>


                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" >
                        @if($errors->has('telefono'))
                            <span class="text-danger"> {{ $errors->first('telefono')}}</span>
                        @endif
                    </div>
                    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" >
                        @if($errors->has('parentesco'))
                            <span class="text-danger"> {{ $errors->first('parentesco')}}</span>
                        @endif
                    </div>
                    <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4" >
                        @if($errors->has('genero'))
                            <span class="text-danger"> {{ $errors->first('genero')}}</span>
                        @endif
                    </div>
                </div>
                {{-- %%%%%%%%%%%%%%%%%%%%%%% CAMPO GENERO  %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% --}}

                <div class="row"> 
                    <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4" >
                        <div class="form-floating mb-3 text-gray">
                            <input class="form-control" type="tel" id="phone" name="telefono" placeholder="introduzca un numero telefonico" pattern="[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]" value="{{old('telefono')}}">
                            <label class="text-secondary" for="phone">Número*</label>
                        </div>
                    </div>
                    {{--%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% CAMPO COMO SE INFORMO  --}}

                    
                    <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4" >
                        <div class="form-floating mb-3 text-gray">
                            <select class="form-control @error('parentesco') is-invalid @enderror"  name="parentesco" id="parentesco">
                                <option value="">Grado Parentesco</option>
                                    
                                    
                                        <option value="PAPA" @if(old('parentesco') == 'PAPA') {{'selected'}} @endif>PAPA</option>
                                        <option value="MAMA" @if(old('parentesco') == 'MAMA') {{'selected'}} @endif>MAMA</option>    
                                    
                                        <option value="ABUELO" @if(old('parentesco') == 'ABUELO') {{'selected'}} @endif>ABUELO</option>
                                        <option value="ABUELA" @if(old('parentesco') == 'ABUELA') {{'selected'}} @endif>ABUELA</option>
                                    
                                        <option value="HERMANO" @if(old('parentesco') == 'HERMANO') {{'selected'}} @endif>HERMANO</option>
                                        <option value="HERMANA" @if(old('parentesco') == 'HERMANA') {{'selected'}} @endif>HERMANA</option>
                                    
                                        <option value="TIO" @if(old('parentesco') == 'TIO') {{'selected'}} @endif>TIO</option>
                                        <option value="TIA" @if(old('parentesco') == 'TIA') {{'selected'}} @endif>TIA</option>
                                    
                                        <option value="ESPOSO" @if(old('parentesco') == 'ESPOSO') {{'selected'}} @endif>ESPOSO</option>
                                        <option value="ESPOSA" @if(old('parentesco') == 'ESPOSA') {{'selected'}} @endif>ESPOSA</option>
                                    
                                        <option value="OTRO" @if(old('parentesco') == 'OTRO') {{'selected'}} @endif>OTRO</option>
                                  
                            </select>
                            <label class="text-secondary" for="parentesco">Papel*</label>
                        </div>
                    </div>
            {{-- %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% CAMPO GENERO DEL FAMILIAR ---}}
                    <div class="col-xs-12 col-sm-12 col-md-4 col-lg-4" >
                        <div class="form-floating mb-3 text-gray">
                            <select class="form-control @error('genero') is-invalid @enderror" name="genero" id="genero">
                                <option value=""> Elija tu género</option>
                                <option value="MUJER" @if(old('genero') == 'MUJER') {{'selected'}} @endif>MUJER</option>
                                <option value="HOMBRE" @if(old('genero') == 'HOMBRE') {{'selected'}} @endif>HOMBRE</option> 
                            </select>
                            <label class="text-secondary" for="genero">Género*</label> 
                        </div>
                    </div>
            </div>

         
            
            @include('telefono.datos-propios', ['contacto' => null])
            <div class="container-fluid h-100 mt-3"> 
                <div class="row w-100 align-items-center">
                    <div class="col text-center">
                        <button class="btn btn-primary text-white" type="submit" id="guardar">Guardar <i class="far fa-save fa-2x"></i></button>        
                    </div>	
                </div>
            </div>
            
            
            </form>
        </div>
    </div>


    <div class="modal" tabindex="-1" id="modal-existente">
        <div class="modal-dialog modal-lg modalito">
            <div class="modal-content">
                <div class="modal-header">
                    Seleccione la persona referenciadora
                    <button class="btn btn-danger close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <table id="personas" class="table table-bordered table-hover table-striped">
                        <thead class="bg-primary">
                            <tr>
                                <th>ID</th>
                                <th>OLD</th>
                                <th>NOMBRE</th>
                                <th>APATERNO</th>
                                <th>AMATERNO</th>
                                <th>FOTO</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>

                </div>
                <div class="modal-footer">
                    pie del modal 
                </div>
            </div>
        </div>
    </div>
    <div class="modal" tabindex="-1" id="modal-formulario-existente">
        <div class="modal-dialog modal-lg modalito">
            <div class="modal-content">
                <div class="modal-header">
                    Seleccione la persona referenciadora
                    <button class="btn btn-danger close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="formapoderado" action="" method="POST">
                        @csrf 
                        {{ @method_field('PUT') }}
                        <input hidden class="form-control mb-3" type="text" name="" value="" id="apoderado_id">
                        <input hidden class="form-control mb-3" type="text" name="" value="{{$persona->id}}" id="persona_id">
                        
                        <label for="pais">Teléfono*</label>
                        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" >
                            <div class="form-floating mb-3 text-gray">
                                <input class="form-control mb-3" type="text" name="" value="" id="telefono">
                                
                            </div>
                        </div>
                        
                        <label class="text-danger" for="pais">Parentesco*</label>
                        <select name="" id="pariente" class="form-control">
                            <option value="">Seleccion parentesco</option>
                            <option value="PAPA" @if(old('parentesco') == 'PAPA') {{'selected'}} @endif>PAPA</option>
                            <option value="MAMA" @if(old('parentesco') == 'MAMA') {{'selected'}} @endif>MAMA</option>    
                            <option value="ABUELO" @if(old('parentesco') == 'ABUELO') {{'selected'}} @endif>ABUELO</option>
                            <option value="ABUELA" @if(old('parentesco') == 'ABUELA') {{'selected'}} @endif>ABUELA</option>
                            <option value="HERMANO" @if(old('parentesco') == 'HERMANO') {{'selected'}} @endif>HERMANO</option>
                            <option value="HERMANA" @if(old('parentesco') == 'HERMANA') {{'selected'}} @endif>HERMANA</option>
                        
                            <option value="TIO" @if(old('parentesco') == 'TIO') {{'selected'}} @endif>TIO</option>
                            <option value="TIA" @if(old('parentesco') == 'TIA') {{'selected'}} @endif>TIA</option>
                        
                            <option value="ESPOSO" @if(old('parentesco') == 'ESPOSO') {{'selected'}} @endif>ESPOSO</option>
                            <option value="ESPOSA" @if(old('parentesco') == 'ESPOSA') {{'selected'}} @endif>ESPOSA</option>
                        
                            <option value="OTRO" @if(old('parentesco') == 'OTRO') {{'selected'}} @endif>OTRO</option>
                        </select>
                        <div class="container-fluid h-100 mt-3"> 
                            <div class="row w-100 align-items-center">
                                <div class="col text-center">
                                    <button id="guardarapoderado" class="btn btn-primary text-white btn-lg">Guardar <i class="far fa-save"></i></button>        
                                </div>	
                            </div>
                        </div>


                    </form> 
                </div>
                <div class="modal-footer">
                    pie del modal 
                </div>
            </div>
        </div>
    </div>

    {{-- @if($tiempoToken==0)
        @include('include.modalGContact')
    @endif --}}

@stop



@section('js')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" type="text/javascript"></script>
    
    {{-- <script type="text/javascript" src="{{ asset('dist/js/jquery.leanModal.min.js')}}"></script> --}}


    <script src="{{asset('dist/js/steepfocus.js') }}"></script>
    <script type="text/javascript" src="{{ asset('dist/js/jquery.leanModal.min.js')}}"></script>
    <script src="{{asset('assets/js/mensajeAjax.js')}}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    {{-- <script src="{{asset('assets/js/tiempoGcontact.js')}}"></script> --}}
    
    <script>
        // $(document).ready(function() {
        //     var intervalId;
        //     $('#modalGcontact').modal('show');
        //     function actualizarTokenExpiration() {
        //         $.ajax({
        //             url: "{{ route('token-expiration') }}",
        //             type: "GET",
        //             success: function(response) {

        //                 console.log(response);
        //                 $('#tokenExpiration').text('Tiempo Restante: ' + response);
        //                 $('#tokenExpirationform').text('Tiempo Restante: ' + response);
        //                 $('#signIn').show();
        //             },
        //             error: function(xhr, status, error) {
        //                 console.error(error);
        //             }
        //         });
        //     }
        //     intervalId = setInterval(actualizarTokenExpiration, 1000);
        // });
    </script>

    <script>
        $(document).ready(function() {
            $('#personas').DataTable(
                {
                    "serverSide": true,
                    "responsive":true,
                    "autoWidth":false,
                    "ajax": "{{ url('listar/referencias') }}",
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
                            "title": "Image",
                            "orderable": true,
            
                        }, 
                        {
                            data: 'btn'
                        },  
                    ],
                    "language":{
                        "url":"https://cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"
                    },  
                });
            $('table').on('click','#ok',function(e) {
                 persona_id=$(this).closest('tr').children(0).html();
                    $.ajax({
                        url:"{{url('persona/get/')}}/"+persona_id,
                        success: function(persona){
                            $("#apoderado_id").val(persona.id);
                            $("#telefono").val(persona.telefono);
                            $("#modal-formulario-existente").modal("show");
                        }
                    })
                    //$("#modal-existente").modal("hide");
            });
            
            $("#existente").on("click", function(e){
                e.preventDefault();
                $("#modal-existente").modal("show");
                
            })
            $(document).on("submit","#formapoderado",function(e){
                e.preventDefault();
                persona_id=$("#persona_id").val();
                apoderado_id=$("#apoderado_id").val();
                telefono=$("#telefono").val();
                pariente=$("#pariente").val();
                //console.log(persona_id,apoderado_id);
                 $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url:"{{url('guardar/apoderado/existente/ajax')}}",
                    data:{
                        persona_id:persona_id,
                        apoderado_id:apoderado_id,
                        telefono:telefono,
                        pariente:pariente,
                        // parentesco:parentesco,
                    },
                    success: function(json){
                        $("#modal-existente").modal("hide");
                        $("#modal-formulario-existente").modal("hide");
                        mensajePequenio("Apoderado guradado correctamente..",'success', 1000);
                        
                    },
                });
            });
        } );
    </script>

@stop

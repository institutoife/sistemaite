@extends('adminlte::page')

@section('title', 'Persona Mostrar')

@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.1.5/css/fileinput.min.css" media="all" rel="stylesheet" type="text/css" />
@endsection

@section('content_header')
    
@stop

@section('content')
    <div id="contenedor" class="container border border-primary p-2">
        <div class="row">    
            <div class="col-1"></div>
            <div class="col-4 p-2 bg-gray-light" style='border-radius:3px;border: 1px solid rgb(186, 38, 129);'>
                <video playsinline autoplay width="100%" height="100%"></video>
            </div>
            <div class="col-2"></div>
            <div class="col-4 p-lg-4">
                <canvas id='canvas' style='max-width:100%;height:auto;border-radius:3px; border: 1px solid rgb(38, 186, 165);'>
                    
                </canvas>
            </div>
            <div class="col-1"></div>
        </div>
        <div class="row text-center">
            <div class="col-6">
                <button class="btn btn-outline-primary" id="boton-tomar-foto">Tomar Foto</button>
            </div>
            <div class="col-6">
                <form id='formCanvas' method='post' action='{{route('guardarfoto',$persona)}}' ENCTYPE='multipart/form-data'>
                    {{ @method_field('PUT') }}
                    @csrf
                    <button type="submit" class="btn btn-primary d-none" id="boton-guardar">Guardar Foto tomada</button>
                    <input type='hidden' name='imagen' id='imagen' />
                </form>
            </div>
        </div>
        <p class="text-danger" data-camera-error role="alert"></p>
        <a class="btn btn-warning" href="{{route('telefonos.crear',$persona)}}">Omitir<i class="fas fa-arrow-circle-right fa-2x"></i></a>
    </div>
    <div id="sincamera" class="d-none">
        <div class="alert alert-danger" role="alert">
            <h4 class="alert-heading">Ninguna cámara detectada</h4>
            <p>No pude detectar ninguna camara, puedes saltar este paso o puedes subir una imagen</p>
        </div>
        <div class="card">
            <div class="card-header bg-secondary    ">
                FORMULARIO PARA SUBIR UNA FOTOGRAFIA
            </div>
            <div class="card-body justify-content-center">
                
                <form action="{{route('guardarfotojpg',$persona)}}" method="post" enctype='multipart/form-data' >
                     {{ @method_field('PUT') }}
                     @csrf
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4 input-group text-sm" >
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4 input-group text-sm" >
                            <div class="border-danger input-group">
                                <input  type="file" accept=".png, .jpg, .jpeg, .gif, .webp" name="foto" id="foto" style="text-align:center; margin: auto" data-classButton="btn btn-success"  data-input="false" data-classIcon="icon-plus">
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4 input-group text-sm" >
                        </div>
                    </div>
                    <div class="row justify-content-center pt-3 p-5" >
                        <button class="btn btn-primary mr-auto" type="submit" id="guardar">Guardar <i class="far fa-save fa-2x"></i></button>        
                        <a class="btn btn-warning" href="{{route('telefonos.crear',$persona)}}">Omitir<i class="fas fa-arrow-circle-right fa-2x"></i></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
@stop


@section('js')
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
       <script src="{{asset('dist/js/steepfocus.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.1.5/js/plugins/piexif.min.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.1.5/js/plugins/sortable.min.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.1.5/js/fileinput.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.1.5/themes/fas/theme.js"></script>
    <script src="{{asset('vendor/inputfile/locales/es.js')}}"></script>
    
    
    
    <script>

        $(document).ready(function() {
            $("#foto").fileinput(
                {
                    initialPreview: [],
                    initialPreviewAsData: true,
                    initialPreviewConfig: [
                    ],
                    //deleteUrl: "/site/file-delete",
                    overwriteInitial: true,
                    maxFileSize: 2000,
                    initialCaption: "Click en examinar para cambiar imagen",
                    language:'es',
                    theme:'fas',
                    showRemove: false,
                    showUpload: false,
                    showCancel: false,
                    
                }
            ); 
        });

    </script>
   
    <script src="{{ asset('assets/js/profile-camera.js') }}?v=20261031" defer></script>
@stop




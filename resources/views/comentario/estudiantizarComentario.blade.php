
@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
    <link rel="stylesheet" href="{{asset('custom/css/custom.css')}}">
@stop

@section('title', 'Motivos Create')
@section('plugins.Jquery', true)
@section('plugins.Sweetalert2', true)
@section('plugins.Datatables', true)


@section('content')
    <section class="content container-fluid pt-4">
        <div class="row">
            <div class="col-md-12">

                @includeif('partials.errors')

                <div class="card card-default">
                    <div class="card-header bg-primary">
                        <span class="card-title">Crear Mensaje</span>
                    </div>
                    <div class="card-body">
                        <form action="{{route('personas.store')}}" id="formulario" method="post" enctype="multipart/form-data" class="form-horizontal" autocomplete="off">
                            @csrf
                            
                            @include('comentario.personaform')
                            @include('include.botones')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.1.5/js/plugins/piexif.min.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.1.5/js/plugins/sortable.min.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.1.5/js/fileinput.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.1.5/themes/fas/theme.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.1.5/js/locales/es.js"></script>
    
    <script type="text/javascript" src="{{ asset('dist/js/jquery.leanModal.min.js')}}"></script>
    <script src="https://cdn.ckeditor.com/4.19.0/standard-all/ckeditor.js"></script>
    {{-- %%%%%%%%%%%%%%%%%%%%%%%%%% CKEDITOR --}}
    <script>
        CKEDITOR.replace('observacion', {
            height: 150,
            width: "100%",
            removeButtons: 'PasteFromWord'
        });
    </script>
     <script>
        $(document).ready(function(){
             $('#personas').DataTable(
                {
                    "serverSide": true,
                    "responsive":true,
                    "autoWidth":false,
                    "ajax": "{{ url('./api/referencias') }}",
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
            var url1 = 'http://upload.wikimedia.org/wikipedia/commons/thumb/e/e1/FullMoon2010.jpg/631px-FullMoon2010.jpg',
                url2 = 'http://upload.wikimedia.org/wikipedia/commons/thumb/6/6f/Earth_Eastern_Hemisphere.jpg/600px-Earth_Eastern_Hemisphere.jpg';
            $("#foto").fileinput(
                {
                initialPreview: [url1, url2],
                initialPreviewAsData: true,
                initialPreviewConfig: [
                    
                    {caption: "Earth.jpg", downloadUrl: url2, size: 1218822, width: "120px", key: 2}
                ],
                deleteUrl: "/site/file-delete",
                overwriteInitial: true,
                maxFileSize: 2000,
                initialCaption: "The Moon and the Earth",
                language:'es',
                theme:'fas',

                }
            );
            $('#formulario').trigger("reset");
            function cargarciudades(){
                var country_id = $('#country').val();
                console.log(country_id);
                if(!country_id){
                $('#city').html('<option value="6" required selected>Santa Cruz de la Sierra </option>');
                    return;
                }
                $.get('../../api/pais/'+ country_id +'/ciudades',function (data) {
                    var html_select='<option value="6" required selected>Santa Cruz de la Sierra </option>';
                    for (var i = 0; i < data.length; i++) {
                        html_select+='<option value="'+ data[i].id +'">' +data[i].ciudad +'</option>';
                    //console.log(html_select);
                    }
                    $('#city').html(html_select);
                });
            }
            function cargarzonas(){
                var city_id = $('#city').val();
                if(!city_id){
                $('#zona').html('<option value="" required>Seleccione una Zona </option>');
                    return;
                }
                $.get('../api/ciudad/'+ city_id +'/zonas',function (data) {
                    var html_select='<option value="">Seleccione una Ciudad </option>';
                    for (var i = 0; i < data.length; i++) {
                        html_select+='<option value="'+ data[i].id +'">' +data[i].zona +'</option>';
                    //console.log(html_select);
                    }
                    $('#zona').html(html_select);
                });
            }
        cargarciudades();
        $('#country').on('change', cargarciudades); 
        $('#city').on('change', cargarzonas);
        $('table').on('click','#ok',selecciona);
        function selecciona() {
            console.log("clickeaste");
            $("#persona_id").val($(this).closest('tr').children(0).html());
            $("#persona_id").addClass('bg-primary');
            $('#modal-ife').modal('toggle');
            $('#modal-ife .close').remove();
        }
        
    });	
    function  mostrarModal(){
            var ElementoSeleccionado=$('#como_id option:selected').val();
                if(ElementoSeleccionado==2){
                    $("#modal-ife").modal("show");
                }else{
                    $("#persona_id").val('');  
                }
        }
    </script>
@endsection



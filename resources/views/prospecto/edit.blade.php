
@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
    <link rel="stylesheet" href="{{asset('custom/css/custom.css')}}">
@stop

@section('title', 'Editar prospecto')
@section('plugins.Jquery', true)
@section('plugins.Sweetalert2', true)
@section('plugins.Datatables', true)

@section('content')
    <section class="content container-fluid">
        <div class="">
            <div class="col-md-12">

                <div class="card card-default">
                    <div class="card-header bg-secondary">
                        <span class="card-title">Actualizar Prospecto</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('prospecto.update', $prospecto->id) }}"  role="form">
                            {{ method_field('PATCH') }}
                            @csrf

                            @include('prospecto.form')
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
    
    <script src="https://cdn.ckeditor.com/ckeditor5/28.0.0/classic/ckeditor.js"></script>
<script src="{{asset('dist/js/steepfocus.js') }}"></script>
<script>
    $(document).ready(function(){
        function cargardepartamentos(){
            var pais_id = $('#pais_id').val();
            console.log(pais_id);
        
            html_select="";
            $.get('../../api/pais/'+ pais_id +'/departamentos',function (data) {
                for (var i = 0; i < data.length; i++) {
                    html_select+='<option value="'+ data[i].id +'">' +data[i].departamento +'</option>';
                }
                $('#departamento_id').html(html_select);
            });
        }
        $('#pais_id').on('change', cargardepartamentos); 
    });
</script>
@endsection

@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.rtl.min.css" />
@endsection

@section('title', 'Crear Clase')
@section('plugins.Jquery',true)
@section('plugins.Datatables',true)
@section('plugins.Sweetalert2',true)
@section('plugins.Select2',true)

@section('content_header')
    <meta name="csrf-token" content="{{ csrf_token() }}" />
@stop
@section('content')
    <section class="content container-fluid">
       
            <div class="card">
                <div class="card-body">
                    <canvas id="chart-rea3" class="pie"></canvas>
                </div>
            </div>
        
        
        <div class="card card-default">
            <div class="card-header bg-primary">
                <span class="card-title">CREAR CLASES</span>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped table-hover table-responsive-sm">
                    <thead>
                        <tr>
                            <th>ESTADO</th>
                            <th>FECHA</th>
                            <th>ASISTIO</th>
                            <th>DOCENTE</th>
                            <th>MATERIA</th>
                            <th>TEMA</th>
                            <th>AULA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($historiaclases as $clase)
                            <tr>
                                <td>{{$clase->estado}}</td>
                                <td>{{$clase->fecha->isoFormat("L")}} @include('clase.asistencia.relation',['program'=>$clase->programacion])</td>
                                <td>{{$clase->horainicio->isoFormat('HH:mm:ss').'-'.$clase->horafin->isoFormat('HH:mm:ss')}}</td>
                                <td>{{$clase->nombrecorto}}</td>
                                <td>{{$clase->materia}}</td>
                                <td>{{$clase->tema}}</td>
                                <td>{{$clase->aula}}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
               
                <div class="alert alert-primary" role="alert">
                    <h4 class="alert-heading">{!! "Horario: ".$programa->hora_ini->toTimeString().' '.($programa->hora_fin)->toTimeString() !!}</h4>
                    {!! $inscripcion->objetivo !!}
                </div>
                <form method="POST" action="{{ route('clases.guardar',$programa->id) }}"  role="form" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        @include('clase.form')    
                    </div>
                    @include('include.botones')
                </form>
            </div>
        </div>
        <table class="table table-bordered table-striped table-hover table-responsive-sm">
                    <thead>
                        <tr>
                            <th>ESTADO</th>
                            <th>FECHA</th>
                            <th>ASISTIO</th>
                            <th>DOCENTE</th>
                            <th>MATERIA</th>
                            <th>TEMA</th>
                            <th>AULA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($historiaprogramas as $programa)
                            <tr>
                                <td>{{$programa->estado}}</td>
                                <td>{{$programa->fecha->isoFormat("L")}} @include('clase.asistencia.relation',['program'=>$programa])</td>
                                <td>{{$programa->hora_ini->isoFormat('HH:mm:ss').'-'.$programa->hora_fin->isoFormat('HH:mm:ss')}}</td>
                                <td>{{$programa->nombrecorto}}</td>
                                <td>{{$programa->materia}}</td>
                                <td>{{$programa->aula}}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
    </section>
@endsection

@section('js')
    <script src="{{asset('dist/js/moment.js')}}"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.17.1/locale/es.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/1.0.1/Chart.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  
    
    <script>
        $(document).ready(function() {
            //%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% CONFIGURACION DE GRAFICO %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
            const cData = JSON.parse('<?php echo $data ?>');
            
            var barChartData = {
               labels: cData.label,

                datasets: [
                     {
                        fillColor: "rgba(250, 0, 0 ,0.3)",
                        strokeColor: "rgba(250,0,0,0.5)",
                        highlightFill: "#ee7f49",
                        highlightStroke: "#ffffff",
                        data: cData.finalizado,
                    },
                    {
                        fillColor: "rgba(13,185,55,0.5)",
                        strokeColor: "rgba(13,185,55,0.6)",//bordes
                        highlightFill: "#1864f2",
                        highlightStroke: "#ff0000",
                        data: cData.presente,
                    },
                    {
                        fillColor: "rgba(204, 209, 209 ,0.5)",
                        strokeColor: "rgba(133, 146, 158 ,0.6)",
                        highlightFill: "#ee7f49",
                        highlightStroke: "#ffffff",
                        data: cData.indefinido,
                    },
                ]

            }
            
            var options = {
            responsive: true,
            showTooltips: false,
            onAnimationComplete: function() {

                var ctx = this.chart.ctx;
                ctx.font = this.scale.font;
                
                ctx.fillStyle = this.scale.textColor
                ctx.textAlign = "center";
                ctx.textBaseline = "bottom";
                

                this.datasets.forEach(function(dataset) {
                    
                dataset.bars.forEach(function(bar) {
                    ctx.fillText(bar.value, bar.x, bar.y +3 );
                    
                });
                })
            }
            };
        Mycanva=document.getElementById("chart-rea3");  
        var ctx3 = document.getElementById("chart-rea3").getContext("2d");
        Mycanva.height=100;
        window.myPie = new Chart(ctx3).Bar(barChartData, options);
            //%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% FIN GRAFICO %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%    

            $("#tema_id").select2({
                //dropdownParent: $("#modal-editar"),
                placeholder: "Seleccione un tema (Opcional)",
                theme: "bootstrap-5",
                language: "es",
                containerCssClass: "select2--large", // For Select2 v4.0
                selectionCssClass: "select2--large", // For Select2 v4.1
                dropdownCssClass: "select2--large",
            });
            function cargarTemas(){
                var materia_id = $('#materia_id').val();
                if(!materia_id){
                $('#tema_id').html('<option value="" required>Seleccione un tema </option>');
                    return;
                }
                var html_select="";
                $.get('../../../api/temas/'+ materia_id,function (data) {
                    
                    html_select='<option value="">Seleccione un tema </option>';
                    for (var i = 0; i < data.length; i++) {
                        html_select+='<option value="'+ data[i].id +'">' +data[i].tema +'</option>';
                    }
                    
                    //$('#tema_id').html("<option value="">Si tema</option>");
                    $('#tema_id').html(html_select);
                    //console.log(html_select);
                });
            }
            $('#materia_id').on('change', cargarTemas);
            cargarTemas();
        });
    </script>
@endsection

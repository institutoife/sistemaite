@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
    <link href="{{asset('dist/css/zoomify.css')}}" rel="stylesheet" type="text/css">
@endsection

@section('title', 'Estado actual')
@section('plugins.Jquery',true)
@section('plugins.Datatables',true)
@section('plugins.Sweetalert2',true)
@section('content_header')
    <meta name="csrf-token" content="{{ csrf_token() }}" />
@stop
@section('content')
{{-- {{ $data }} --}}
    <section class="content container-fluid">
        <div class="card">
            <div class="card-body">
                <canvas id="chart-rea3" class="pie"></canvas>
            </div>
        </div>
        @php
            $i=-1;
        @endphp
        @foreach ($docenteshabilitados as $docente)
            @php
                $i=$i+1;
            @endphp
            @if (count($estudiantes[$i])>0)
                <div class="card">
                    <div class="card-header {{ $i%2 == 0 ? "bg-primary" : "bg-secondary" }}">
                        <h4>{{$docente->nombrecorto}}</h4> 
                    </div>
                    <div class="card-body">
                        <table class="table table-hover table-striped table-bordered table-borderless">
                            <thead>
                                <th>COD</th>
                                <th>NOMBRE</th>
                                <th>MATERIA</th>
                                <th>HORARIO</th>
                                <th>FOTO</th>
                                <th>TEMA</th>
                                <th>ACTION</th>
                            </thead>
                            <tbody>
                                
                                @foreach ($estudiantes[$i] as $estudent)
                                    @php
                                        $hora_inicio=new Carbon\Carbon($estudent->horafin);
                                        $hora_fin=Carbon\Carbon::now();
                                        $minutosRestantes=$hora_fin->diffInMinutes($hora_inicio,false);
                                        // dd($minutosRestantes);
                                        if ($minutosRestantes<=-10){
                                            $clase="danger";
                                        }
                                        if($minutosRestantes>15){
                                            $clase="success";
                                        }
                                        if(($minutosRestantes>0)&&($minutosRestantes<15)){
                                            $clase="warning";
                                        }
                                        if(($minutosRestantes<=0)&&($minutosRestantes>-10)){
                                            $clase="danger";
                                        }
                                    @endphp
                                    <tr class="{{'text-'.$clase}}" id="{{$estudent->clase_id}}">
                                        <td>{{$estudent->id}}</td>
                                        <td>{{$estudent->nombre.' '.$estudent->apellidop.' '.$estudent->apellidom}}</td>
                                        <td>{{$estudent->materia}}</td>
                                        <td>{{$estudent->horainicio.'-'.$estudent->horafin}}</td>
                                        <td>
                                            <img class="rounded img-thumbnail img-fluid zoomify" data-profile-photo src="{{ app(\App\Services\ProfilePhotoService::class)->url($estudent->foto) }}" width="80">
                                        </td>
                                        <td>{{$estudent->tema}}</td>
                                        <td>
                                            <button class="mr-1 finalizar btn {{'btn-'.$clase}}" title="Finalizar esta clase">
                                               Finalizar
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        
                    </div>
                </div>
            @endif
        @endforeach
    </section>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/1.0.1/Chart.min.js"></script>
    
    <script>
        $(document).ready(function() {
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
            $('table').on('click','.zoomify',function (e){
                
                Swal.fire({
                    title: 'Estudiante: '+ $(this).closest('tr').find('td').eq(1).text(),
                    text: 'Materia:'+$(this).closest('tr').find('td').eq(4).text(),
                    imageUrl: $(this).attr('src'),
                    imageWidth: 400,
                    imageHeight:400,
                    showCloseButton:true,
                    confirmButtonColor:'#26baa5',
                    confirmButtonText:"Aceptar",
                    
                })
            });
            $('table').on('click', '.finalizar', function(e) { 
                e.preventDefault(); 
                var id_estudiante =$(this).closest('tr').attr('id');
                var           fila=$(this).closest('tr');
		        $.ajax({
                    url : "{{url('clase/finalizar')}}",
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
                                icon: 'success',
                                title: "Clase finalizada correctamente"
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

        });
    </script>
@endsection

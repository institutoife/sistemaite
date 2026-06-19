
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $logoPath = public_path('assetpublic/images/logo.png');
        $fontPath = public_path('assetpublic/fonts/GlyphaLTStd-Bold.otf');
        $fechaProximoPago = $inscripcion->fecha_proximo_pago;
    @endphp
    <style>
    body {
    
    font-family: helvetica, arial, sans-serif, Montserrat;
    text-rendering: optimizeLegibility;
    }
    body {
    margin: 0;
    }

    .titulo{
        font-size:10px;
        font-weight:12px; 
    }
    .dato{
        font-size:9px;
        background-color:white; 
    }
    .tabla{
        border-collapse: collapse;
        width: 100%;
    }

    div.table-title {
    display: block;
    margin: auto; 
    width: 100%;
    }

    .table-title h3 {
    color: #fafafa;
    font-size: 13px;
    font-weight:300;
    font-style:normal;
    font-family: helvetica, arial, sans-serif, Montserrat;
    text-transform:uppercase;
    }
    .table-fill {
    background: white;
    border-radius:3px;
    border-collapse: collapse;
    margin: auto;
    width: 100%;
    }


    .table{
    border:1px solid #C1C3D1;
    position:relative;
    }
    
    th {
    color:#4B4B4B;
    border:1px solid #9ea7af;
    
    font-size:12px;
    font-weight: bold;

    text-align:center;
    vertical-align:middle;
    background-color:#ddd;
    }

   
    
    
    tr {
    border:1px solid #d1d1d1;
    border-bottom:1px solid #ddd;
    border-bottom-color: 1px solid #C1C3D1;
    color:#1f1e1e;
    font-size:12px;
    }

  
    
    .table tr:nth-child(odd) td {
        background:#eee;
    }
    
    .table tr:nth-child(odd):hover td {
        background:#000000;
    }

    tr:last-child td:first-child {
    border-bottom-left-radius:3px;
    }
    
    tr:last-child td:last-child {
    border-bottom-right-radius:3px;
    }
    
    td {
    background:#FFFFFF;
    text-align:center;
    vertical-align:middle;
    font-size:11px;
    font-weight:300;
    border-right: 1px solid #C1C3D1;
    border-left: 1px solid #C1C3D1;
    border-top: 1px solid #C1C3D1;
    border-bottom: 1px solid #C1C3D1;
    width: auto;
    }

    td:last-child {
    border-right: 0px;
    }

    th.text-left {
    text-align: left;
    }

    th.text-center {
    text-align: center;
    }

    th.text-right {
    text-align: right;
    }

    td.text-left {
    text-align: left;
    }

    td.text-center {
    text-align: center;
    }

    td.text-right {
    text-align: right;
    }

    .pdf-header {
        width: 100%;
        margin: 0 0 6px 0;
        border-collapse: collapse;
        border: 0;
    }

    .pdf-header td {
        border: 0;
        background: transparent;
        padding: 0;
        vertical-align: middle;
    }

    .pdf-header tr {
        border: 0;
    }

    .pdf-header .logo {
        width: 100px;
        height: auto;
    }

    .pdf-header .contact-info {
        text-align: right;
        font-size: 12px;
        line-height: 1.4;
        color: rgb(55, 95, 122);
        font-family: 'Montserrat', Arial, Helvetica, sans-serif;
        padding: 6px 0 6px 0;
        white-space: normal;
    }

    .pdf-header .contact .line strong {
        font-weight: bold;
    }

    .pdf-header .divider {
        margin-top: 4px;
    }

        @if(file_exists($fontPath))
            @font-face {
                font-family: 'GlyphaBold';
                src: url('{{ $fontPath }}') format('opentype');
            }
        @endif
        .logo-text {
            color: rgb(38,186,165);
            font-weight: bold;
            font-size: 26px;
            font-family: 'GlyphaBold', Arial, Helvetica, sans-serif !important;
        }
        .codigo-persona {
            color: rgb(55, 95, 122);
            font-weight: bold;
            font-size: 18px;
            font-family: 'GlyphaBold', Arial, Helvetica, sans-serif !important;
            background-color: #99dbd1;
            padding: 6px 18px;
            border-radius: 6px;
            display: inline-block;
        }
        .fecha-proximo-pago {
            color: rgb(55, 95, 122);
            font-weight: bold;
            font-size: 12px;
            font-family: 'GlyphaBold', Arial, Helvetica, sans-serif !important;
            background-color: #99dbd1;
            padding: 3px 3px;
            border-radius: 4px;
            display: inline-block;
        }
        .contact-info strong {
            font-weight: bold;
        }
        .contact-info .dato-contacto {
            font-family:  Arial, Helvetica, sans-serif !important;
            font-weight: bold;
            font-size: 13px;
            color: rgb(38,186,165);
        }
        .estado-activado {
            color: #26baa5;
            font-weight: bold;
        }
        .estado-inactivo {
            color: #d11a1a;
            font-weight: normal;
        }
   
    </style>
    
    {{-- <link rel="stylesheet" href="{{asset('custom/css/custom.css')}}"> --}}
    {{-- <link rel="stylesheet" href="{{asset('custom/css/reporte.css')}}"> --}}
    
</head>
<body>
<table class="pdf-header">
    <tr>
        <td>
            @if(file_exists($logoPath))
                <img class="logo" src="{{ $logoPath }}" alt="IFE">
            @else
                <span class="logo-text">IFE</span>
            @endif
        </td>
        <td class="contact-info">
            <div>
                <strong>Telefonos:</strong> <span class="dato-contacto">71039910 - 75553338 - 71324941</span> |
                <strong>Web:</strong> <span class="dato-contacto">ife.bo</span> |
                <strong>Servicios:</strong> <span class="dato-contacto">ife.bo</span>
            </div>
            <div>
                <strong>TikTok:</strong> <span class="dato-contacto">@ife_educabol</span> |
                <strong>YouTube:</strong> <span class="dato-contacto">@ife_educabol</span> |
                <strong>Instagram:</strong> <span class="dato-contacto">ife_educabol</span>
            </div>
        </td>
       
    </tr>
</table>
<div class="float-right">
    <span class="codigo-persona">CÓDIGO:{{$persona->id}}</span>
</div>
    <style>
    
    </style>
<div class="">
   <table class="tabla">
    <tbody>
        <tr>
            <td class="titulo">ESTUDIANTE</td>
            <td class="dato">{{$persona->nombre.' '.$persona->apellidop.' '.$persona->apellidom}}</td>
            <td class="titulo">Documento CI</td>
            <td class="dato">{{$persona->carnet}}</td>
            <td class="titulo">USUARIO</td>
            <td class="dato">{{$usuario->name}}</td>
        </tr>
        <tr>
            <td class="titulo">Modalidad</td>
            <td class="dato">{{$modalidad->modalidad}}</td>
            <td class="titulo">Total horas</td>
            <td class="dato">{{$inscripcion->totalhoras}}</td>
            <td class="titulo">Nivel</td>
            <td class="dato">{{$nivel->nivel}}</td>
        </tr>
        <tr>
            <td class="titulo">Colegio</td>
            <td class="dato" colspan="3">{{ optional($colegio)->nombre ?? 'Sin colegio registrado' }}</td>
            <td class="titulo">Grado</td>
            <td class="dato">{{$grado->grado}}</td>
        </tr>
        <tr>
            <td class="titulo">Edad</td>
            <td class="dato" colspan="2">{!! $edad !!} años</td>
            <td class="titulo" colspan="2">FechaPago</td>
            <td class="dato">
                <span class="fecha-proximo-pago">
                    @if($fechaProximoPago)
                        {{$fechaProximoPago->translatedFormat('d F Y')}}
                        ({{$fechaProximoPago->diffForHumans()}})
                    @else
                        Sin fecha registrada
                    @endif
                </span>
            </td>

        </tr>
        <tr>
            <td class="titulo">Objetivo</td>
            <td class="dato" colspan="5">{!! strip_tags($inscripcion->objetivo).$pago."/".$inscripcion->costo!!}</td>
        </tr>
        <tr>
            <td class="titulo">Motivo</td>
            <td class="dato" colspan="5">{!! $inscripcion->motivo->motivo !!}</td>
        </tr>
    </tbody>
   </table>
</div>
<hr style="height:0.2px;border-width:0;color:rgba(214, 213, 213, 0.1);background-color:gray">
    <div class="divtabla" styker="page-break-inside: auto;">
        <table class="table-fill table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>FECHA</th>
                    <th>DIA</th>
                    <th>HORARIO</th>
                    <th>HRAS</th>
                    <th>DOCENTE/MATERIA/AULA</th>
                    <th>FRAC</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($programacion as $programa)
                @php
                    echo $programa->materia;
                    $hoy=Carbon\Carbon::now();
                    $clase="";
                    if($programa->fecha->isoFormat('DD/MM/YYYY')==$hoy->isoFormat('DD/MM/YYYY')){
                        $clase .= ''; 
                    }else{
                        if($programa->habilitado==0){
                            $clase .= ''; 
                        }
                        }
                @endphp
                <tr class="{{$clase}}">
                    <td>{{$loop->iteration}}</td>
                    <td>{{$programa->fecha->isoFormat('DD/MM/YYYY')}}</td>
                    <td>{{$programa->fecha->isoFormat('dddd')}}</td>
                    <td>{{$programa->hora_ini->isoFormat('HH:mm').'-'.$programa->hora_fin->isoFormat('HH:mm')}}</td>
                    <td>{{$programa->horas_por_clase}}</td>
                    <td>{{$programa->nombre.'/'.$programa->materia.'/'.$programa->aula}}</td>
                    <td>
                        @if($programa->habilitado==1)
                            <span class="estado-activado">activado</span>
                        @else
                            <span class="estado-inactivo">inactivo</span>
                        @endif
                    </td>
                </tr>
                
                @if($loop->iteration%38==0)
                    <tr> <td colspan="7"> <div style="page-break-before:always;"> </div> </td></tr>
                @endif
            @endforeach

            <tfoot>
                <tr class={{$clase}}>
                    <th colspan="3"></th>
                    <th colspan="2">Total:{{round($inscripcion->totalhoras,1)}} Horas</th>
                    <th></th>
                    <th></th>
                </tr>
            </tfoot>

            </tbody>
            <tfoot>
                este es el pie de pagina
            </tfoot>
        </table>
    </div>
    <hr style="height:0.2px;border-width:0;color:rgba(214, 213, 213, 0.1);background-color:gray">
    <div style="font-size:10px; line-height:1.4; margin-top:6px;">
        <strong>Normas y condiciones importantes</strong>
        <ol style="margin:6px 0 0 16px; padding:0;">
            <li><strong>No reembolsos:</strong> No se realizan devoluciones de dinero. En su lugar, las clases pueden congelarse o transferirse a otro estudiante, con autorización del padre, madre o tutor legal.</li>
            <li><strong>Puntualidad:</strong> El estudiante debe llegar con 5 minutos de anticipación. Las clases iniciadas se consideran dictadas.</li>
            <li><strong>Licencias:</strong> Se permiten hasta tres (3) licencias durante el programa. Solo el padre, madre o tutor legal registrado puede solicitarlas.</li>
            <li><strong>Asistencia:</strong> Las inasistencias sin previo aviso implican la pérdida de la clase correspondiente.</li>
            <li><strong>Convivencia y cuidado:</strong> El estudiante debe respetar las normas internas y hacer uso adecuado de los materiales e instalaciones.</li>
            <li><strong>Aceptación de condiciones:</strong> La continuidad en el programa implica la aceptación de estas normas y condiciones.</li>
        </ol>
        <div style="margin-top:6px;">
            <em>acepta estas condiciones.(padre, madre o tutor)</em>
        </div>
    </div>
    <div style="margin-top:18px; font-size:10px; text-align:center;">
        <div style="width:70%; margin:28px auto 0; border-top:1px solid #444; padding-top:6px;">
            Firma del padre/madre/tutor legal
        </div>
    </div>
</body>
</html>



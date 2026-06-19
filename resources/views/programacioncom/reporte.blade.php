
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    @php
        $logoPath = public_path('assetpublic/images/logo.png');
        $fontPath = public_path('assetpublic/fonts/GlyphaLTStd-Bold.otf');
        $fechaProximoPago = $matriculacion->fecha_proximo_pago;
    @endphp
    
    {{-- <link rel="stylesheet" href="{{asset('custom/css/custom.css')}}">
    <link rel="stylesheet" href="{{asset('custom/css/reporte.css')}}"> --}}
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
    position:absolute;
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
        font-size: 10px;
        line-height: 1.4;
        color: #1a4d7a;
        font-family: 'Montserrat', Arial, Helvetica, sans-serif;
        font-weight: bold;
        padding: 6px 0 6px 0;
        white-space: normal;
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
    .estado-activado {
        color: #26baa5;
        font-weight: bold;
    }
    .estado-inactivo {
        color: #d11a1a;
        font-weight: normal;
    }

    .pdf-header .contact .line strong {
        font-weight: bold;
    }

    .pdf-header .divider {
        margin-top: 4px;
    }
    </style>
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
    

<div style="display: flex; flex-direction: row; width: 100%; margin-bottom: 8px;">
    <div style="flex:1; min-width:0;">
        <span class="codigo-persona" style="font-size:18px;">CÓDIGO:{{$persona->id}}</span>
    </div>
    
</div>

<div class="">
   <table class="tabla">
    <tbody>
        <tr>
            <td class="titulo">Estudiante</td>
            <td class="dato">{{$persona->nombre.' '.$persona->apellidop.' '.$persona->apellidom}}</td>
            <td class="titulo">FechaPago</td>
            <td class="dato"><span class="fecha-proximo-pago" style="font-size:18px;">{{$fechaProximoPago ? $fechaProximoPago->translatedFormat('d F Y') : 'Sin fecha registrada'}}</span></td>
            <td class="titulo">Usuario</td>
            <td class="dato">{{ optional($usuario)->name ?? 'Sin usuario registrado' }}</td>
        </tr>
        <tr>
            <td class="titulo">Carrera</td>
            <td class="dato">{{$carrera->carrera}}</td>
            <td class="titulo">Total horas</td>
            <td class="dato">{{$matriculacion->totalhoras}}</td>
            <td class="titulo">Asignatura</td>
            <td class="dato">{{$asignatura->asignatura}}</td>
        </tr>
        
        
    </tbody>
   </table>
</div>
<hr style="height:0.2px;border-width:0;color:rgba(214, 213, 213, 0.1);background-color:gray">

    <div class="divtabla">
        <table class="table-fill tabla1" style="position:relative;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>FECHA</th>
                    <th>DIA</th>
                <th>HORARIO</th>
                <th>HRAS</th>
                <th>DOCENTE/MATERIA/AULA</th>
                <th>PAGO</th>
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
                    <td><span class="">{{$programa->fecha->isoFormat('DD/MM/YYYY')}}</span></td>
                    <td>{{$programa->fecha->isoFormat('dddd')}}</td>
                    <td>{{$programa->horaini->isoFormat('HH:mm').'-'.$programa->horafin->isoFormat('HH:mm')}}</td>
                    <td>{{$programa->horas_por_clase}}</td>
                    <td>{{$programa->nombre.'/'.$programa->aula}}</td>
                    <td>
                        @if($programa->habilitado==1)
                            <span class="estado-activado">activado</span>
                        @else
                            <span class="estado-inactivo">inactivo</span>
                        @endif
                    </td>
                </tr>
                    
                @endforeach
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
            <li><strong>Puntualidad:</strong> Se solicita llegar con 5 minutos de anticipación. Las clases iniciadas se consideran dictadas.</li>
            <li><strong>Licencias:</strong> Se permiten hasta tres (3) licencias durante el programa. Solo el padre, madre o tutor legal registrado puede solicitarlas.</li>
            <li><strong>Asistencia:</strong> Las inasistencias sin previo aviso implican la pérdida de la clase correspondiente.</li>
            <li><strong>Convivencia y cuidado:</strong> El estudiante debe respetar las normas internas y hacer uso adecuado de los materiales e instalaciones.</li>
            <li><strong>Aceptación de condiciones:</strong> La continuidad en el programa implica la aceptación de estas normas y condiciones.</li>
        </ol>
    </div>
    <div style="margin-top:18px; font-size:10px; text-align:center;">
        <div style="width:70%; margin:28px auto 0; border-top:1px solid #444; padding-top:6px;">
            Firma del padre/madre/tutor legal
        </div>
    </div>
</body>
</html>



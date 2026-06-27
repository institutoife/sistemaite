
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300&display=swap');

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
    }
    
    th {
    color:#FFFFFF;
    border:1px solid rgb(38, 186,165);
    
    font-size:12px;
    font-weight: bold;

    text-align:center;
    vertical-align:middle;
    background-color:#26BAA5;
    }

    tr {
    border:1px solid #d1d1d1;
    border-bottom:1px solid #ddd;
    border-bottom-color: 1px solid #C1C3D1;
    color:#1f1e1e;
    font-size:12px;
    }

	.modalidad{
		color: #535252;
	}
    
    .table tr:nth-child(odd) td {
        background:rgba(202, 200, 200, 0.05);
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

	section {
            padding: 20px;
            background-color: #fff;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
			color: rgb(55, 95,122);
        }

		.map-image {
            width: 100%;
            height: auto;
            border-radius: 10px;
        }

		p{
			color: #7A7979
		}
		h1{
			color: rgb(38, 186,165);
            font-size: 36px;
            font-weight: bold;
		}
	
    </style>
    
    
</head>
<body>
	
        <h1>Nivel Secundaria</h1>
        <p class="contact-info">Av. 3 pasos al frente y Che Guevara, Santa Cruz</p>
        <p class="contact-info">Teléfono: +591 71039910 / +591 75553338 / +591 71324941</p>
 
	{{-- <img src="{{ asset('assetshome/images/logo-3.png') }}"> --}}

<div class="">
	<h2 class="wow fadeInUp" data-wow-delay=".4s">Elige tus horarios, aprende a tu ritmo.</h2>
	<p class="wow fadeInUp" data-wow-delay=".6s">
		¡Horarios adaptados a ti! Tú decides cuándo aprender.
	</p>
	<table class="tabla">
		<thead>
			<tr>
				<th>Turno Mañana</th>
				<th>Turno Tarde</th>
				<th>Turno Noche</th>
				<th>Horario Especial</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>07:30 - 09:00</td>
				<td>14:00 - 15:30</td>
				<td>18:30 - 20:00</td>
				<td>05:00 - 06:30</td>
			</tr>
			<tr>
				<td>09:00 - 10:30</td>
				<td>15:30 - 17:00</td>
				<td>19:00 - 20:30</td>
				<td>05:30 - 07:00</td>
				
			</tr>
			<tr>
				<td>10:30 - 12:00</td>
				<td>17:00 - 18:30</td>
				<td>19:30 - 21:00</td>
				<td></td>
			</tr>
			<tr>
				<td>Lun a Sab</td>
				<td>Lun a Sab</td>
				<td>Excepto Miércoles</td>
				<td>Sujeto a reserva</td>
			</tr>
			<tr>
				<td class="signup">
					<a href="https://api.whatsapp.com/send?phone=59171039910&text=Hola, estoy interesado en el Turno Mañana.">
						¡Empieza Turno Mañana! <i class="fa-brands fa-whatsapp fa-beat"></i>
					</a>
				</td>
				<td>
					<a href="https://api.whatsapp.com/send?phone=59171039910&text=Hola, estoy interesado en el Turno Tarde.">
						¡Empieza Turno Tarde! <i class="fa-brands fa-whatsapp fa-beat"></i>
					</a>
				</td>
				<td>
					<a href="https://api.whatsapp.com/send?phone=59171039910&text=Hola, estoy interesado en el Turno Noche.">
						¡Empieza Turno Noche! <i class="fa-brands fa-whatsapp fa-beat"></i>
					</a>
				</td>
				<td>
					<a href="https://api.whatsapp.com/send?phone=59171039910&text=Hola, estoy interesado en el Horario Especial.">
						¡Empieza Horario Especial! <i class="fa-brands fa-whatsapp fa-beat"></i>
					</a>
				</td>
			</tr>
		</tbody>
	</table>
</div>
<hr style="height:0.2px;border-width:0;color:rgba(214, 213, 213, 0.1);background-color:gray">

<section class="speakers-section">
	
	<h2>¡Elige tus días de clase, según tu agenda!</h2>
	<p>En nuestra institución, te damos la libertad de personalizar tu experiencia de aprendizaje. Selecciona los días de la semana y los horarios que mejor se adapten a tu agenda para asistir a nuestras clases.</p>
	<div class="table-container">
		<table class="tabla">
			<thead>
				<tr>
					<th>Días de la Semana</th>
					<th>Días de la Semana</th>
					<th>Días de la Semana</th>
					<th>Días de la Semana</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>Lunes</td>
					<td>Martes</td>
					<td>Lunes</td>
					<td>Sólo</td>
				</tr>
				<tr>
					<td>Miércoles</td>
					<td>Jueves</td>
					<td>Martes</td>
					<td>Sábados</td>
				</tr>
				<tr>
					<td>Viernes</td>
					<td>Sábado</td>
					<td>Miércoles</td>
					<td>
						<a class="reservar-button" target="_blank" href="https://api.whatsapp.com/send?phone=59171039910&text=Hola, me gustaría reservar para los días Sábados.">¡Reservar!</a>
					</td>
				</tr>
				<tr>
					<td>
						<a class="reservar-button" target="_blank" href="https://api.whatsapp.com/send?phone=59171039910&text=Hola, me gustaría reservar para los días Lunes, Miércoles y Viernes.">¡Reservar!</a>
					</td>
					<td>
						<a class="reservar-button" target="_blank" href="https://api.whatsapp.com/send?phone=59171039910&text=Hola, me gustaría reservar para los días Martes, Jueves y Sábado.">¡Reservar!</a>
					</td>
					<td>Jueves</td>
					<td></td>
				</tr>
				<tr>
					<td></td>
					<td></td>
					<td>Viernes</td>
					<td></td>
				</tr>
				<tr>
					<td></td>
					<td></td>
					<td>
						<a class="reservar-button" target="_blank" href="https://api.whatsapp.com/send?phone=59171039910&text=Hola, me gustaría reservar para los días Lunes, Martes, Miércoles, Jueves y Viernes.">¡Reservar!</a>
					</td>
					<td></td>
				</tr>
			</tbody>
		</table>
	</div>
</section>

<div class="float-right">
	<h2>Ahora estamos en estos lugares para ti, más cerca que nunca.</h2>
	<p>Estamos cerca para brindarte todo el apoyo y la atención que necesitas, con ubicaciones accesibles y personal dispuesto a ayudarte en cada paso.</p>

	<table class="tabla">
		<tr>
			<th>Agencia</th>
			<th>Dirección</th>
			<th>Teléfono</th>
		</tr>
		<tr>
			<td>Agencia Central</td>
			<td>Santa Cruz, Villa 1 de mayo, calle 16 oeste #9</td>
			<td>75553338 / 3-219050</td>
		</tr>
		<tr>
			<td>Agencia 1º de Mayo</td>
			<td>Av. Tres pasos al frente esquina Che Guevara #4710</td>
			<td>71324941</td>
		</tr>
		<tr>
			<td>Agencia Plan 3000</td>
			<td>Av. 18 de marzo esquina Che Guevara</td>
			<td>+71039910</td>
		</tr>
	</table>
	
	
</div>


    <div class="divtabla" styker="page-break-inside: auto;">
		<h2>Modalidades adaptadas a ti: desde por hora hasta trimestral, tú decides</h2>
		<p>Elige tu modalidad de estudio: por hora, semanal, quincenal, mensual, bimestral o trimestral. Adapta tu aprendizaje a tu ritmo en IFE.</p>
	
        <table class="table-fill table">
			<thead class="table-head">
				<tr>
					<th class="text-center">MODALIDAD</th>
					<th class="text-center">INVERSION</th>
					<th class="text-center">ACTION</th>
				</tr>
			</thead>
			<tbody class="">
				@foreach ($modalidades as $modalidad)
				<tr>
                    <td class="text-left">
                        <div class="info-container">
                            <h4 class="modalidad">{{ $modalidad->modalidad }}</h4>
                            <p class="info-description">{{ $modalidad->descripcion }}</p>
                        </div>
                    </td>
                    <td class="text-center">{{ "Bs. ".$modalidad->costo }}</td>
                    <td class="text-center">
						<a target="_blank" href="https://api.whatsapp.com/send?phone=59171039910&text=Quisiera, reservar *Modalidad*:{{ $modalidad->modalidad." *Descripción* :".$modalidad->descripcion." *Inversion:* ".$modalidad->costo }}">Reservar</a>
					</td>
                </tr>
				@if($loop->iteration%10==0)
                    <tr> <td colspan="7"> <div style="page-break-before:always;"> </div> </td></tr>
                @endif
				@endforeach
			</tbody>
		</table>
    </div>

	<section class="corporate-plans">
		<div class="content-container">
			<div class="section-title">
				<h1 class="section-heading">Planes Corporativos</h1>
				<h2 class="discount-info">Descuentos del <span>20%</span> en nuestros planes corporativos</h2>
			</div>
	
			<table class="tabla">
				<thead>
					<tr>
						<th>Modalidad</th>
						<th>1er estudiante</th>
						<th>2do estudiante</th>
						<th>3er estudiante</th>
						<th>4to estudiante</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>Modalidad Hora Libre</td>
						<td>Bs.40</td>
						<td>Bs.32</td>
						<td>Bs.25</td>
						<td>Bs.20</td>
					</tr>
					<tr>
						<td>Modalidad Semanal 7.5 hras</td>
						<td>Bs.216</td>
						<td>Bs.172</td>
						<td>Bs.138</td>
						<td>Bs.110</td>
					</tr>
					<tr>
						<td>Modalidad Quincenal 15 Hras </td>
						<td>Bs.350</td>
						<td>Bs.280</td>
						<td>Bs.224</td>
						<td>Bs.179</td>
					</tr>
					<tr>
						<td>Modalidad Mensual 30 Hras</td>
						<td>Bs.550</td>
						<td>Bs.440</td>
						<td>Bs.352</td>
						<td>Bs.281</td>
					</tr>
				</tbody>
			</table>
		</div>
	</section> 
</body>
</html>



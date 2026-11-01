@extends('adminlte::page')
@section('title','Códigos de recuperación')
@section('content')
<div class="card mx-auto" style="max-width:700px"><div class="card-header" style="background:#375F7A;color:#fff">Segundo factor activado</div><div class="card-body">
<p>Guarde estos códigos en un lugar seguro. Cada código sirve una sola vez si pierde acceso a la aplicación. Esta lista se muestra una sola vez.</p>
<p>Su contraseña de ingreso sigue siendo la misma. Estos códigos se utilizan únicamente en la pantalla de segundo factor.</p>
<ul>@foreach($codes as $code)<li><code>{{ $code }}</code></li>@endforeach</ul>
<a href="{{ ($trustOffer??false)?route('security.devices.offer'):route('profile.show') }}" class="btn btn-primary">Ya guardé mis códigos</a>
</div></div>
@endsection

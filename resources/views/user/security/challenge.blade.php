@extends('adminlte::page')
@section('title','Verificar segundo factor')
@section('content')
<div class="card mx-auto" style="max-width:500px"><div class="card-header" style="background:#375F7A;color:#fff">Verifique su segundo factor</div><div class="card-body">
@include('user.access.errors')
<p>Su correo y contraseña ya fueron aceptados. Ahora ingrese el código del autenticador o uno de sus códigos de recuperación.</p>
<form method="POST" action="{{ route('security.two-factor.verify') }}">@csrf
    <div class="form-group"><label for="code">Código de su aplicación o código de recuperación</label><input class="form-control" id="code" name="code" autocomplete="one-time-code" maxlength="64" required autofocus></div>
    <button class="btn btn-primary">Continuar</button>
</form></div></div>
@endsection

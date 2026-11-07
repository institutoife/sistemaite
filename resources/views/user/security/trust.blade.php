@extends('adminlte::page')
@section('title','Dispositivo de confianza')
@section('content')
<div class="card mx-auto" style="max-width:650px">
<div class="card-header" style="background:#375F7A;color:#fff"><h1 class="h5 mb-0">Segundo factor verificado</h1></div>
<div class="card-body">@include('user.access.errors')
<p>Puede recordar este navegador para omitir el código 2FA en sus próximos ingresos. Su contraseña seguirá siendo obligatoria.</p>
<p class="small text-muted">La confianza se guarda para su cuenta en este navegador. Cada persona debe verificar su propio 2FA la primera vez; cambiar de usuario conserva la confianza de las demás cuentas.</p>
<form method="POST" action="{{ route('security.devices.trust') }}">@csrf
<div class="form-group"><input type="hidden" name="trust_device" value="0"><label><input type="checkbox" name="trust_device" value="1"> Confiar en este dispositivo durante 30 días</label></div>
<div class="form-group"><label for="device_name">Nombre para reconocerlo (opcional)</label><input class="form-control" id="device_name" name="device_name" maxlength="100" value="{{ old('device_name',$deviceName) }}" placeholder="Por ejemplo, PC de recepción"></div>
<p class="small text-muted">Active esta opción solo en un equipo de confianza y cierre sesión al terminar su turno. Puede revocarla desde Mi perfil → Seguridad.</p>
<button class="btn btn-primary">Continuar</button>
</form></div></div>
@endsection

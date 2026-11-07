@extends('adminlte::page')
@section('title','Seguridad · Dispositivos y sesiones')
@section('content')
<h1 class="h4">Seguridad · Dispositivos y sesiones</h1>
@include('user.access.errors')
<p><a href="{{ route('profile.show') }}" class="btn btn-outline-secondary">Mi perfil</a> <a href="{{ route('security.two-factor.show') }}" class="btn btn-outline-secondary">Configurar segundo factor</a>
@if($user->two_factor_confirmed_at)<a href="{{ route('security.two-factor.challenge') }}" class="btn btn-outline-primary">Verificar 2FA para confiar en este navegador</a>@endif</p>
<div class="card"><div class="card-header" style="background:#375F7A;color:#fff">Mis dispositivos de confianza</div><div class="card-body">
<p>Recordar un navegador evita repetir el código durante 30 días. No sustituye la contraseña. Borrar sus cookies o usar otro navegador requiere verificar nuevamente 2FA.</p>
<p class="small text-muted">La confianza y las revocaciones corresponden únicamente a su cuenta. En una PC compartida, cierre sesión al terminar su turno; el siguiente usuario debe ingresar con su propia cuenta.</p>
<div class="table-responsive"><table class="table table-striped"><thead><tr><th>Dispositivo</th><th>Registrado</th><th>Última actividad</th><th>Vencimiento</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
@forelse($devices as $device)<tr><td>{{ $device->name }} @if((int)$current===(int)$device->id)<span class="badge" style="background:#26BAA5;color:#fff">Este navegador</span>@endif</td>
<td>{{ $device->created_at->format('d/m/Y H:i') }}</td><td>{{ $device->last_used_at->format('d/m/Y H:i') }}</td><td>{{ $device->expires_at->format('d/m/Y H:i') }}</td>
<td>{{ $device->revoked_at?'Revocado':($device->expires_at->isPast()?'Vencido':'Activo') }}</td><td>@if(!$device->revoked_at&&$device->expires_at->isFuture())<form method="POST" action="{{ route('security.devices.revoke',$device->id) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Revocar</button></form>@endif</td></tr>
@empty<tr><td colspan="6">No tiene dispositivos de confianza registrados.</td></tr>@endforelse
</tbody></table></div>{{ $devices->links() }}<p class="small text-muted">Fechas en America/La_Paz. La última actividad puede actualizarse cada cinco minutos.</p>
</div></div>
<div class="row"><div class="col-md-6"><div class="card"><div class="card-header" style="background:#375F7A;color:#fff">Revocar todos los dispositivos</div><div class="card-body">
<p>Todos los navegadores deberán verificar de nuevo el código 2FA.</p><form method="POST" action="{{ route('security.devices.revoke-all') }}">@csrf @method('DELETE')
<div class="form-group"><label>Contraseña actual</label><input type="password" name="current_password" class="form-control" autocomplete="current-password" required></div><button class="btn btn-outline-danger">Revocar toda la confianza</button></form>
</div></div></div><div class="col-md-6"><div class="card"><div class="card-header" style="background:#375F7A;color:#fff">Cerrar las demás sesiones</div><div class="card-body">
<p>Cierra los otros ingresos y revoca todos los dispositivos de confianza. Esta sesión permanece abierta.</p><form method="POST" action="{{ route('security.sessions.others') }}">@csrf
<div class="form-group"><label>Contraseña actual</label><input type="password" name="current_password" class="form-control" autocomplete="current-password" required></div><button class="btn btn-outline-primary">Cerrar las demás sesiones</button></form>
</div></div></div></div>
@endsection

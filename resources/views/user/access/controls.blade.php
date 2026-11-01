@extends('adminlte::page')
@section('title','Accesos de usuario')
@section('content')
<h1 class="h4">Accesos de {{ $user->name }}</h1>
@include('user.access.errors')
<p><a class="btn btn-outline-primary" href="{{ route('users.edit',$user) }}">Editar usuario, roles y sucursales</a> <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">Volver a usuarios</a></p>
<div class="row"><div class="col-lg-6">
<div class="card"><div class="card-header" style="background:#375F7A;color:#fff">Estado de la cuenta</div><div class="card-body">
<p>Estado actual: <strong>{{ ['active'=>'Activo','suspended'=>'Suspendido','blocked'=>'Bloqueado'][$user->effective_access_status]??$user->access_status }}</strong>@if($user->access_status==='suspended'&&$user->effective_access_status==='active') · Suspensión finalizada @endif</p>
<form method="POST" action="{{ route('users.access.state',$user) }}">@csrf @method('PUT')
<div class="form-group"><label>Suspender hasta (opcional)</label><input class="form-control" type="datetime-local" name="suspended_until"></div>
<div class="form-group"><label>Motivo</label><input class="form-control" name="reason" minlength="5" maxlength="500" required></div>
<button name="status" value="suspended" class="btn btn-warning mb-1">Suspender</button> <button name="status" value="blocked" class="btn btn-danger mb-1">Bloquear</button> <button name="status" value="active" class="btn btn-success mb-1">Reactivar</button>
</form><p class="small text-muted mt-2">El bloqueo conserva el historial y revoca sesiones y tokens. Una suspensión sin fecha termina con una reactivación autorizada.</p>
</div></div>
<div class="card"><div class="card-header" style="background:#375F7A;color:#fff">Restablecer contraseña</div><div class="card-body">
<p>Genera una nueva contraseña temporal de 24 horas y exige elegir una propia al ingresar. No permite consultar la contraseña actual.</p>
<form method="POST" action="{{ route('users.access.password',$user) }}">@csrf
<div class="form-group"><label>Motivo del restablecimiento</label><input class="form-control" name="reason" minlength="5" maxlength="500" required></div><button class="btn btn-primary">Generar contraseña temporal e instrucciones</button></form>
</div></div>
<div class="card"><div class="card-header" style="background:#375F7A;color:#fff">Segundo factor</div><div class="card-body">
<p>{{ $user->two_factor_confirmed_at?'Configurado':'Sin configurar' }} · {{ app(\App\Services\AccountSecurity::class)->requiresTwoFactor($user)?'Obligatorio':'Voluntario' }}</p>
@if($user->id!==auth()->id())<form method="POST" action="{{ route('users.access.two-factor',$user) }}">@csrf @method('DELETE')
<div class="form-group"><label>Contraseña del administrador conectado</label><input class="form-control" type="password" name="current_password" autocomplete="current-password" required></div>
<div class="form-group"><label>Motivo de recuperación</label><input class="form-control" name="reason" minlength="5" maxlength="500" required></div><button class="btn btn-outline-danger">Restablecer segundo factor perdido</button></form>@else<p>Para su propia cuenta, utilice sus códigos de recuperación.</p>@endif
</div></div>
<div class="card"><div class="card-header" style="background:#375F7A;color:#fff">Responsabilidades pendientes del CRM</div><div class="card-body">
<p>{{ $pending }} prospectos activos asignados. Las actividades y conversiones anteriores conservarán sus autores y responsables históricos.</p>
<form method="POST" action="{{ route('users.access.reassign',$user) }}">@csrf
<div class="form-group"><label>Nuevo responsable</label><select class="form-control" name="responsable_id" required><option value="">Seleccione personal activo</option>@foreach($responsibles as $responsible)<option value="{{ $responsible->id }}">{{ $responsible->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Motivo</label><input class="form-control" name="reason" minlength="5" maxlength="500" required></div><button class="btn btn-outline-primary">Reasignar pendientes</button></form>
</div></div>
</div><div class="col-lg-6">
<div class="card"><div class="card-header" style="background:#375F7A;color:#fff">Horario de acceso · America/La_Paz</div><div class="card-body">
<form method="POST" action="{{ route('users.access.schedule',$user) }}">@csrf @method('PUT')
<label><input type="hidden" name="enabled" value="0"><input type="checkbox" name="enabled" value="1" @if($user->access_schedule_enabled) checked @endif> Restringir acceso por horario</label>
<p class="small">Desmarcado: sin horario semanal. Una franja que termina antes de su inicio continúa al día siguiente. Las excepciones de denegación tienen prioridad, incluso sin horario semanal.</p>
<div id="access-windows">
@foreach($windows as $index=>$window)<div class="border rounded p-2 mb-2 access-row"><div class="form-row"><div class="col-4"><label>Día</label><select name="windows[{{ $index }}][weekday]" class="form-control">@foreach([1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'] as $day=>$label)<option value="{{ $day }}" @if($window->weekday==$day) selected @endif>{{ $label }}</option>@endforeach</select></div><div class="col-4"><label>Desde</label><input class="form-control" type="time" name="windows[{{ $index }}][starts_at]" value="{{ substr($window->starts_at,0,5) }}" required></div><div class="col-4"><label>Hasta</label><input class="form-control" type="time" name="windows[{{ $index }}][ends_at]" value="{{ substr($window->ends_at,0,5) }}" required></div></div><button class="btn btn-sm btn-outline-danger mt-2 remove-row" type="button">Quitar franja</button></div>@endforeach
</div><button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="add-window">Agregar franja</button>
<h2 class="h6">Excepciones temporales</h2><div id="access-exceptions">
@foreach($exceptions as $index=>$exception)<div class="border rounded p-2 mb-2 access-row"><label>Desde</label><input class="form-control" type="datetime-local" name="exceptions[{{ $index }}][starts_at]" value="{{ str_replace(' ','T',substr($exception->starts_at,0,16)) }}" required><label>Hasta</label><input class="form-control" type="datetime-local" name="exceptions[{{ $index }}][ends_at]" value="{{ str_replace(' ','T',substr($exception->ends_at,0,16)) }}" required><label>Acceso</label><select class="form-control" name="exceptions[{{ $index }}][allowed]"><option value="1" @if($exception->allowed) selected @endif>Permitir</option><option value="0" @if(!$exception->allowed) selected @endif>Denegar</option></select><label>Motivo de la excepción</label><input class="form-control" name="exceptions[{{ $index }}][reason]" value="{{ $exception->reason }}" maxlength="500" required><button class="btn btn-sm btn-outline-danger mt-2 remove-row" type="button">Quitar excepción</button></div>@endforeach
</div><button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="add-exception">Agregar excepción</button>
<div class="form-group"><label>Motivo del cambio de horario</label><input class="form-control" name="reason" minlength="5" maxlength="500" required></div><button class="btn btn-primary">Guardar horario</button>
</form></div></div>
</div></div>
<div class="card"><div class="card-header" style="background:#375F7A;color:#fff">Historial de administración de la cuenta</div><div class="card-body table-responsive"><table class="table table-striped"><thead><tr><th>Fecha (La Paz)</th><th>Administrador / actor</th><th>Acción</th><th>Motivo y cambios</th></tr></thead><tbody>@foreach($audits as $audit)<tr><td>{{ $audit->created_at->format('d/m/Y H:i:s') }}</td><td>{{ optional($audit->actor)->name?:'Sistema' }}</td><td>{{ $audit->event }}</td><td>{{ $audit->reason }}<details><summary>Ver cambios</summary><pre style="white-space:pre-wrap;overflow-wrap:anywhere">{{ json_encode(['antes'=>$audit->before_values,'después'=>$audit->after_values],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></details></td></tr>@endforeach</tbody></table>{{ $audits->links() }}</div></div>
<template id="window-template"><div class="border rounded p-2 mb-2 access-row"><div class="form-row"><div class="col-4"><label>Día</label><select class="form-control" name="windows[__INDEX__][weekday]">@foreach([1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'] as $day=>$label)<option value="{{ $day }}">{{ $label }}</option>@endforeach</select></div><div class="col-4"><label>Desde</label><input class="form-control" type="time" name="windows[__INDEX__][starts_at]" required></div><div class="col-4"><label>Hasta</label><input class="form-control" type="time" name="windows[__INDEX__][ends_at]" required></div></div><button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-row">Quitar franja</button></div></template>
<template id="exception-template"><div class="border rounded p-2 mb-2 access-row"><label>Desde</label><input class="form-control" type="datetime-local" name="exceptions[__INDEX__][starts_at]" required><label>Hasta</label><input class="form-control" type="datetime-local" name="exceptions[__INDEX__][ends_at]" required><label>Acceso</label><select class="form-control" name="exceptions[__INDEX__][allowed]"><option value="1">Permitir</option><option value="0">Denegar</option></select><label>Motivo</label><input class="form-control" name="exceptions[__INDEX__][reason]" maxlength="500" required><button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-row">Quitar excepción</button></div></template>
@endsection
@section('js')
<script>
let windowIndex=@json(count($windows)),exceptionIndex=@json(count($exceptions));
function accessRow(template,target,index){const row=document.getElementById(template).content.cloneNode(true);row.querySelectorAll('[name]').forEach(input=>input.name=input.name.replace('__INDEX__',index));document.getElementById(target).append(row);}
document.getElementById('add-window').onclick=()=>accessRow('window-template','access-windows',windowIndex++);
document.getElementById('add-exception').onclick=()=>accessRow('exception-template','access-exceptions',exceptionIndex++);
document.addEventListener('click',event=>{if(event.target.matches('.remove-row'))event.target.closest('.access-row').remove();});
</script>
@endsection

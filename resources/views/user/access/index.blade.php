@extends('adminlte::page')
@section('title','Usuarios')
@section('content')
<div class="card"><div class="card-header d-flex align-items-center" style="background:#375F7A;color:#fff"><h1 class="h5 mb-0">Administración de usuarios</h1>@can('administer',\App\Models\User::class)<a class="btn btn-light btn-sm ml-auto" href="{{ route('users.create') }}">Crear usuario</a>@endcan</div>
<div class="card-body">@include('user.access.errors')
<form method="GET" class="form-inline mb-3"><label class="sr-only" for="q">Buscar</label><input id="q" name="q" value="{{ request('q') }}" class="form-control mr-2" placeholder="Nombre o correo"><select name="status" class="form-control mr-2" aria-label="Estado"><option value="">Todos los estados</option>@foreach(['active'=>'Activo','suspended'=>'Suspendido','blocked'=>'Bloqueado'] as $key=>$label)<option value="{{ $key }}" @if(request('status')===$key) selected @endif>{{ $label }}</option>@endforeach</select><button class="btn btn-primary">Filtrar</button></form>
<div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr><th>Usuario</th><th>Fotografía</th><th>Roles</th><th>Sucursal principal</th><th>Sucursales adicionales</th><th>Horario</th><th>Estado</th><th>Último acceso</th><th>Acciones</th></tr></thead><tbody>
@forelse($users as $user)<tr>
<td><a href="{{ route('users.show',$user) }}">{{ $user->name }}</a><small class="d-block">{{ $user->email }}</small></td>
<td><img data-profile-photo class="img-thumbnail ife-profile-photo" src="{{ app(\App\Services\ProfilePhotoService::class)->forUser($user) }}" alt="{{ $user->name }}" height="50"></td>
<td>{{ $user->getRoleNames()->implode(', ') }}</td><td>{{ optional($user->sede)->nombre?:'Sin asignar' }}</td><td>{{ $user->sedes->where('id','!=',$user->sede_id)->pluck('nombre')->implode(', ')?:'—' }}</td>
<td>{{ $user->access_schedule_enabled?'Configurado · La Paz':'Sin horario semanal' }}</td><td>{{ ['active'=>'Activo','suspended'=>'Suspendido','blocked'=>'Bloqueado'][$user->effective_access_status]??$user->access_status }}@if($user->suspended_until)<small class="d-block">{{ $user->effective_access_status==='active'?'Suspensión finalizada el':'Hasta' }} {{ $user->suspended_until->format('d/m/Y H:i') }}</small>@endif</td><td>{{ optional($user->last_login_at)->format('d/m/Y H:i')?:'Sin registro' }}</td>
<td>@can('manageAccess',$user)<a class="btn btn-sm btn-outline-primary mb-1" href="{{ route('users.edit',$user) }}">Editar / sucursales</a><a class="btn btn-sm btn-outline-secondary mb-1" href="{{ route('users.access.edit',$user) }}">Horario / estado / contraseña</a>@endcan</td></tr>
@empty<tr><td colspan="9">No hay usuarios para estos filtros.</td></tr>@endforelse
</tbody></table></div>{{ $users->links() }}</div></div>
@endsection

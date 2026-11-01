@extends('adminlte::page')
@section('title',$user->exists?'Editar usuario':'Crear usuario')
@section('content')
<div class="card"><div class="card-header" style="background:#375F7A;color:#fff">{{ $user->exists?'Editar usuario':'Creación rápida de usuario' }}</div><div class="card-body">
@include('user.access.errors')
<form method="POST" action="{{ $user->exists?route('users.update',$user):route('users.store') }}" enctype="multipart/form-data">
@csrf @if($user->exists) @method('PATCH') @endif
<div class="row">
    <div class="form-group col-md-6"><label for="name">Nombre de usuario</label><input id="name" name="name" class="form-control" maxlength="64" value="{{ old('name',$user->name) }}" required></div>
    <div class="form-group col-md-6"><label for="email">Correo para iniciar sesión</label><input id="email" name="email" type="email" class="form-control" maxlength="64" value="{{ old('email',$user->email) }}" required></div>
</div>
<div class="form-group"><label for="person-search">Vincular Persona existente: docente, trabajador, estudiante o padre</label>
    <div class="input-group"><input id="person-search" class="form-control" placeholder="Nombre, apellido o código" autocomplete="off"><div class="input-group-append"><button id="person-find" type="button" class="btn btn-outline-secondary">Buscar</button></div></div>
    <select id="persona_id" name="persona_id" class="form-control mt-2"><option value="">Cuenta sin Persona vinculada</option>@if($persona)<option value="{{ $persona->id }}" selected>#{{ $persona->id }} · {{ trim($persona->nombre.' '.$persona->apellidop.' '.$persona->apellidom) }}</option>@endif</select>
    <small class="form-text text-muted">Se reutiliza la ficha existente. Una Persona que ya tiene cuenta debe administrarse desde esa cuenta.</small><p id="person-result" role="status"></p>
</div>
<fieldset class="form-group"><legend class="h6">Roles</legend>@foreach($roles as $role)<label class="mr-3"><input type="checkbox" name="roles[]" value="{{ $role->name }}" @if(in_array($role->name,old('roles',$user->exists?$user->getRoleNames()->all():array_filter([request('role')])))) checked @endif> {{ $role->name }}</label>@endforeach</fieldset>
<div class="row"><div class="form-group col-md-6"><label for="sede_id">Sucursal principal</label><select class="form-control" id="sede_id" name="sede_id"><option value="">Sin asignación (padres o estudiantes)</option>@foreach($sedes as $sede)<option value="{{ $sede->id }}" @if((string)old('sede_id',$user->sede_id)===(string)$sede->id) selected @endif>{{ $sede->nombre }}</option>@endforeach</select></div>
<fieldset class="form-group col-md-6"><legend class="h6">Sucursales adicionales autorizadas</legend>@foreach($sedes as $sede)<label class="d-block"><input type="checkbox" name="sedes[]" value="{{ $sede->id }}" @if(in_array($sede->id,old('sedes',$user->exists?$user->sedes->pluck('id')->all():[]))) checked @endif> {{ $sede->nombre }}</label>@endforeach</fieldset></div>
<p class="text-muted">El personal necesita Sucursal principal. Padres y estudiantes acceden según sus vínculos reales con estudiantes e inscripciones.</p>
<div class="form-group"><label><input type="hidden" name="two_factor_required" value="0"><input type="checkbox" name="two_factor_required" value="1" @if(old('two_factor_required',$user->two_factor_required)) checked @endif> Exigir segundo factor adicionalmente a las reglas del rol</label><small class="form-text text-muted">Obligatorio para Admin y Secretaria. Otros usuarios pueden activarlo voluntariamente desde Mi perfil.</small></div>
<div class="form-group"><label for="foto">Fotografía (opcional)</label><input id="foto" name="foto" class="form-control-file" type="file" accept="image/jpeg,image/png,image/gif,image/webp"></div>
@if($user->exists)<div class="form-group"><label for="reason">Motivo del cambio</label><input id="reason" class="form-control" name="reason" maxlength="500" value="{{ old('reason') }}" required></div>@else<p>Se generará una contraseña temporal aleatoria, válida por 24 horas, con cambio obligatorio al ingresar.</p>@endif
<button class="btn btn-primary">{{ $user->exists?'Guardar cambios':'Crear y preparar instrucciones' }}</button> <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">Cancelar</a>
</form></div></div>
@endsection
@section('js')
<script>
document.getElementById('person-find').addEventListener('click',async()=>{
    const status=document.getElementById('person-result'),select=document.getElementById('persona_id');
    try{const url=new URL(@json(route('users.people')),location.origin);url.searchParams.set('q',document.getElementById('person-search').value);const response=await fetch(url,{headers:{Accept:'application/json'}});const rows=await response.json();if(!response.ok)throw Error(rows.message||'No se pudo buscar');
    select.replaceChildren(new Option('Cuenta sin Persona vinculada',''));for(const person of rows){const own=person.user_id===@json($user->id),option=new Option(person.label+(person.user_id?(own?' · cuenta actual':' · ya tiene cuenta'):''),person.id);option.disabled=!!person.user_id&&!own;select.add(option);}status.textContent=rows.length?'Seleccione la ficha correcta.':'Sin coincidencias. Verifique los datos antes de registrar otra Persona.';
    }catch(error){status.textContent=error.message;}
});
</script>
@endsection

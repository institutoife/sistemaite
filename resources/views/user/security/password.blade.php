@extends('adminlte::page')
@section('title','Cambiar contraseña')
@section('content')
<div class="card mx-auto" style="max-width:600px">
    <div class="card-header" style="background:#375F7A;color:#fff"><h1 class="h5 mb-0">{{ $forced ? 'Cambie su contraseña temporal' : 'Cambiar mi contraseña' }}</h1></div>
    <div class="card-body">
        @include('user.access.errors')
        <p>Elija una contraseña propia de al menos 12 caracteres, con mayúsculas, minúsculas, número y símbolo. Se cerrarán las otras sesiones.</p>
        <form method="POST" action="{{ route('security.password.update') }}">@csrf @method('PUT')
            <div class="form-group"><label for="current_password">Contraseña actual o temporal</label><input id="current_password" class="form-control" type="password" name="current_password" autocomplete="current-password" required></div>
            <div class="form-group"><label for="password">Nueva contraseña</label><input id="password" class="form-control" type="password" name="password" autocomplete="new-password" minlength="12" maxlength="64" required></div>
            <div class="form-group"><label for="password_confirmation">Confirmar nueva contraseña</label><input id="password_confirmation" class="form-control" type="password" name="password_confirmation" autocomplete="new-password" required></div>
            <button class="btn btn-primary">Guardar contraseña</button>
        </form>
    </div>
</div>
@endsection

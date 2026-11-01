@extends('adminlte::page')
@section('title','Autenticación de dos factores')
@section('content')
<div class="card mx-auto" style="max-width:700px">
    <div class="card-header" style="background:#375F7A;color:#fff"><h1 class="h5 mb-0">Autenticación de dos factores</h1></div>
    <div class="card-body">@include('user.access.errors')
    <p class="small text-muted">Configurar el segundo factor no cambia su contraseña. Al ingresar seguirá usando su correo y contraseña; después se solicitará el código de la aplicación autenticadora.</p>
    @if($user->two_factor_confirmed_at)
        <p class="text-success">Segundo factor activado.</p>
        <p>Use su aplicación autenticadora al iniciar sesión. Los códigos de recuperación sustituyen un código de la aplicación y se consumen una sola vez.</p>
        @unless(app(\App\Services\AccountSecurity::class)->requiresTwoFactor($user))
        <form method="POST" action="{{ route('security.two-factor.disable') }}">@csrf @method('DELETE')
            <div class="form-group"><label>Contraseña actual</label><input class="form-control" type="password" name="current_password" autocomplete="current-password" required></div>
            <div class="form-group"><label>Código de la aplicación o de recuperación</label><input class="form-control" name="code" autocomplete="one-time-code" required></div>
            <button class="btn btn-outline-danger">Desactivar segundo factor</button>
        </form>
        @endunless
        <a class="btn btn-primary mt-3" href="{{ route('profile.show') }}">Mi perfil</a>
    @elseif($secret)
        <ol><li>En su aplicación autenticadora, agregue una cuenta mediante clave de configuración.</li><li>Nombre: <strong>{{ app(\App\Services\UserTwoFactor::class)->issuer() }} · {{ $user->email }}</strong>. Tipo: basado en tiempo.</li><li>Copie esta clave y confirme el código de seis dígitos.</li></ol>
        <p><code class="d-block p-3 bg-light" style="overflow-wrap:anywhere">{{ $secret }}</code></p>
        <p>Esta clave es privada. La configuración vence en diez minutos.</p>
        <form method="POST" action="{{ route('security.two-factor.confirm') }}">@csrf
            <div class="form-group"><label for="code">Código de la aplicación</label><input id="code" class="form-control" name="code" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" required autofocus></div>
            <button class="btn btn-primary">Confirmar y activar</button>
        </form>
    @else
        <p>Agregue esta cuenta a una aplicación autenticadora compatible con TOTP. Necesitará confirmar un código antes de activar la protección.</p>
        <form method="POST" action="{{ route('security.two-factor.begin') }}">@csrf
            <div class="form-group"><label for="current_password">Confirme su contraseña actual</label><input id="current_password" class="form-control" type="password" name="current_password" autocomplete="current-password" required></div>
            <button class="btn btn-primary">Configurar segundo factor</button>
        </form>
    @endif
    </div>
</div>
@endsection

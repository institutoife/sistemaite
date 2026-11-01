@extends('adminlte::page')
@section('title','Instrucciones de acceso')
@section('content')
<div class="card mx-auto" style="max-width:750px"><div class="card-header" style="background:#375F7A;color:#fff">Credenciales temporales para entregar al titular</div><div class="card-body">
<p>Cuenta: <strong>{{ $user->name }}</strong></p>
<dl><dt>Dirección de acceso</dt><dd>{{ route('login') }}</dd><dt>Correo de acceso</dt><dd>{{ $user->email }}</dd><dt>Contraseña temporal</dt><dd><code>{{ $password }}</code></dd><dt>Vencimiento</dt><dd>{{ optional($user->temporary_password_expires_at)->format('d/m/Y H:i') }} (America/La_Paz)</dd></dl>
<ol><li>Ingrese con el correo y la contraseña temporal.</li><li>El sistema le pedirá elegir una contraseña propia.</li><li>Si la cuenta lo requiere, configure el segundo factor y guarde sus códigos de recuperación.</li></ol>
<p>Esta contraseña temporal se muestra únicamente en esta respuesta. Entréguela personalmente al titular. La contraseña que elija después será privada.</p>
<button type="button" onclick="window.print()" class="btn btn-primary d-print-none">Imprimir instrucciones</button>
<a class="btn btn-outline-secondary d-print-none" href="{{ route('users.index') }}">Volver a usuarios</a>
</div></div>
@endsection

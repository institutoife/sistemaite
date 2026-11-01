@extends('adminlte::auth.login')

@section('css')
    @parent
    <style>
        .login-box .ife-login-form .form-control,
        .login-box .ife-login-form .form-control::placeholder {
            font-size: 15px;
        }
        .login-box .ife-login-form .input-group-text {
            font-size: 1rem;
        }
        .ife-login-form .ife-password-toggle:focus-visible {
            outline: 2px solid #375F7A;
            outline-offset: 2px;
        }
    </style>
@stop

@section('auth_body')
    <form class="ife-login-form" action="{{ route('login') }}" method="POST">@csrf
        <div class="form-group">
            <label for="email">Correo</label>
            <div class="input-group">
                <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" autocomplete="username" value="{{ old('email', $rememberedEmail ?? '') }}" placeholder="Correo electrónico" required autofocus>
                <div class="input-group-append">
                    <span class="input-group-text"><i class="fas fa-envelope" aria-hidden="true"></i></span>
                </div>
                @error('email')<span class="invalid-feedback" role="alert">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="form-group">
            <label for="password">Contraseña</label>
            <div class="input-group">
                <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" autocomplete="current-password" placeholder="Contraseña" required>
                <div class="input-group-append">
                    <button id="togglePassword" class="input-group-text ife-password-toggle" type="button" aria-label="Mostrar contraseña" aria-controls="password" aria-pressed="false">
                        <i id="togglePasswordIcon" class="fas fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
                @error('password')<span class="invalid-feedback" role="alert">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="icheck-primary mb-3">
            <input type="checkbox" name="remember" id="remember" value="1" {{ old('remember', !empty($rememberedEmail)) ? 'checked' : '' }}>
            <label for="remember">Recordar mi correo</label>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Ingresar</button>
    </form>
    <p class="small text-muted mt-3 mb-0">La contraseña se solicita en cada nuevo inicio de sesión. Si tiene segundo factor, el código de la aplicación se solicitará después.</p>
@stop

@section('js')
    @parent
    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const password = document.getElementById('password');
            const visible = password.type === 'password';
            password.type = visible ? 'text' : 'password';
            this.setAttribute('aria-pressed', String(visible));
            this.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
            const icon = document.getElementById('togglePasswordIcon');
            icon.classList.toggle('fa-eye', !visible);
            icon.classList.toggle('fa-eye-slash', visible);
        });
    </script>
@stop

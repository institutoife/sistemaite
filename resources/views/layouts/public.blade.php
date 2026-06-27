<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('meta_description', 'IFE Educabol: formación, apoyo académico y tecnología para aprender con confianza.')">
    <meta name="author" content="IFE Educabol">
    <meta name="theme-color" content="#26baa5">
    <link rel="icon" type="image/png" href="{{ asset('assetpublic/images/logo.png') }}">
    <title>@yield('title', 'IFE Educabol')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="{{ asset('assetpublic/css/ife-public.css') }}">
    @stack('styles')
</head>
<body>
    <a class="ife-skip-link" href="#contenido">Saltar al contenido</a>
    <header class="ife-header" data-header>
        <div class="ife-shell ife-header-inner">
            <a class="ife-brand" href="{{ url('/') }}" aria-label="IFE Educabol, inicio">
                <img src="{{ asset('assetpublic/images/logo.png') }}" alt="IFE Educabol">
            </a>
            <button class="ife-menu-toggle" type="button" aria-expanded="false" aria-controls="ife-navigation" data-menu-toggle>
                <span></span><span></span><span></span>
                <span class="sr-only">Abrir menú</span>
            </button>
            <nav class="ife-navigation" id="ife-navigation" aria-label="Navegación principal" data-menu>
                <a href="{{ url('/') }}">Inicio</a>
                <a href="{{ route('primaria') }}">Primaria</a>
                <a href="{{ route('secundaria') }}">Secundaria</a>
                <a href="{{ route('computacion') }}">Computación</a>
                <a href="{{ route('cursos') }}">Servicios</a>
                <a href="{{ route('about') }}">Nosotros</a>
                <a href="{{ route('contact') }}">Contacto</a>
            </nav>
            <a class="ife-button ife-button-small ife-header-action" href="{{ route('login') }}">Ingresar</a>
        </div>
    </header>

    <main id="contenido">
        @yield('content')
    </main>

    <footer class="ife-footer">
        <div class="ife-shell ife-footer-grid">
            <div class="ife-footer-brand">
                <img src="{{ asset('assetpublic/images/logo.png') }}" alt="IFE Educabol">
                <p>Educación cercana, tecnología útil y acompañamiento para avanzar.</p>
            </div>
            <div>
                <h2>Programas</h2>
                <a href="{{ route('primaria') }}">Primaria</a>
                <a href="{{ route('secundaria') }}">Secundaria</a>
                <a href="{{ route('computacion') }}">Computación</a>
                <a href="{{ route('programacion') }}">Programación</a>
            </div>
            <div>
                <h2>IFE Educabol</h2>
                <a href="{{ route('about') }}">Nosotros</a>
                <a href="{{ route('preguntasfrecuentes') }}">Preguntas frecuentes</a>
                <a href="{{ route('privacy') }}">Privacidad</a>
                <a href="{{ route('termscondition') }}">Términos</a>
            </div>
            <div>
                <h2>Contacto</h2>
                <a href="mailto:info@ife.bo">info@ife.bo</a>
                <a href="tel:+59171324941">+591 71324941</a>
                <span>Santa Cruz de la Sierra, Bolivia</span>
                <div class="ife-footer-socials" aria-label="Redes sociales">
                    @foreach(config('public_site.socials', []) as $social)
                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $social['label'] }}">
                            <i class="{{ $social['icon'] }}" aria-hidden="true"></i>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="ife-shell ife-footer-bottom">
            <span>&copy; {{ date('Y') }} IFE Educabol</span>
            <span>ife.bo</span>
        </div>
    </footer>
    <script src="{{ asset('assetpublic/js/ife-public.js') }}" defer></script>
    @stack('scripts')
</body>
</html>

@extends('layouts.public')

@section('title', 'IFE Educabol | Educación y tecnología')
@section('meta_description', 'IFE Educabol ofrece apoyo académico, tecnología y formación práctica para estudiantes de todas las edades.')

@section('content')
<section class="ife-hero">
    <div class="ife-shell ife-hero-inner">
        <img class="ife-hero-logo" src="{{ asset('assetpublic/images/logo.png') }}" alt="IFE Educabol">
        <span class="ife-eyebrow">Educación que acompaña</span>
        <h1>Aprender con claridad cambia lo que viene.</h1>
        <p>Apoyo académico, tecnología y formación práctica con atención cercana, objetivos concretos y seguimiento real.</p>
        <div class="ife-actions">
            <a class="ife-button" href="{{ route('primaria') }}">Explorar programas</a>
            <a class="ife-button ife-button-secondary" href="{{ route('contact') }}">Hablar con IFE</a>
        </div>
    </div>
</section>

<section class="ife-section">
    <div class="ife-shell">
        <div class="ife-section-heading">
            <div>
                <span class="ife-eyebrow">Programas</span>
                <h2>Un camino para cada etapa.</h2>
            </div>
            <p>Elige el área que necesitas. Cada programa combina explicación, práctica y acompañamiento personalizado.</p>
        </div>
        <div class="ife-grid">
            @foreach([
                ['Primaria', 'Bases sólidas y confianza para avanzar.', route('primaria')],
                ['Secundaria', 'Refuerzo claro en materias exigentes.', route('secundaria')],
                ['Computación', 'Competencias digitales para estudiar y trabajar.', route('computacion')],
                ['Programación', 'Lógica, proyectos y creación con tecnología.', route('programacion')],
                ['Robótica', 'Ingenio, electrónica y resolución de retos.', route('robotica')],
                ['Preuniversitario', 'Preparación estratégica para el siguiente paso.', route('preuniversitario')],
                ['Inglés', 'Comunicación progresiva y práctica útil.', route('ingles')],
                ['Ajedrez', 'Concentración, planificación y estrategia.', route('ajedrez')],
            ] as $index => $program)
                <a class="ife-card ife-card-link" href="{{ $program[2] }}">
                    <span class="ife-card-number">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <h3>{{ $program[0] }}</h3>
                    <p>{{ $program[1] }}</p>
                </a>
            @endforeach
        </div>
    </div>
</section>

<section class="ife-section ife-section-soft">
    <div class="ife-shell">
        <div class="ife-section-heading">
            <div>
                <span class="ife-eyebrow">Método IFE</span>
                <h2>Menos ruido. Más comprensión.</h2>
            </div>
            <p>Trabajamos con objetivos claros y un proceso sencillo que permite medir avances sin perder de vista a la persona.</p>
        </div>
        <div class="ife-grid">
            @foreach([
                ['Diagnóstico', 'Identificamos el punto de partida y las prioridades.'],
                ['Plan', 'Definimos contenidos, ritmo y objetivos alcanzables.'],
                ['Práctica', 'Aplicamos lo aprendido con ejercicios y proyectos.'],
                ['Seguimiento', 'Revisamos avances y ajustamos el acompañamiento.'],
            ] as $index => $step)
                <article class="ife-card">
                    <span class="ife-card-number">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <h3>{{ $step[0] }}</h3>
                    <p>{{ $step[1] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="ife-cta">
    <div class="ife-shell ife-cta-inner">
        <div>
            <h2>Conversemos sobre lo que necesitas.</h2>
            <p>Te orientamos para elegir el programa y modalidad adecuados.</p>
        </div>
        <a class="ife-button ife-button-secondary" href="https://wa.me/59171324941?text=Hola%20IFE%20Educabol,%20quiero%20información" target="_blank" rel="noopener">Contactar por WhatsApp</a>
    </div>
</section>
@endsection

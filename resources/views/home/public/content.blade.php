@php
    $titles = [
        'about' => ['Nosotros', 'Educación cercana con mirada de futuro.'],
        'contact' => ['Contacto', 'Hablemos sobre tu próximo paso.'],
        'privacy' => ['Política de privacidad', 'Cómo cuidamos tu información.'],
        'termscondition' => ['Términos y condiciones', 'Reglas claras para nuestros servicios.'],
        'preguntasfrecuentes' => ['Preguntas frecuentes', 'Respuestas para decidir con confianza.'],
    ];
    $heading = $titles[$pageKey] ?? ['IFE Educabol', 'Información'];
@endphp

@extends('layouts.public')

@section('title', $heading[0].' | IFE Educabol')
@section('meta_description', $heading[1])

@section('content')
<section class="ife-hero ife-hero-compact">
    <div class="ife-shell ife-hero-inner">
        <img class="ife-hero-logo" src="{{ asset('assetpublic/images/logo.png') }}" alt="IFE Educabol">
        <span class="ife-eyebrow">IFE Educabol</span>
        <h1>{{ $heading[0] }}</h1>
        <p>{{ $heading[1] }}</p>
    </div>
</section>

<article class="ife-content">
    @if($pageKey === 'about')
        <span class="ife-eyebrow">Quiénes somos</span>
        <h1>Aprender bien abre posibilidades.</h1>
        <p>IFE Educabol es un centro de formación y acompañamiento educativo en Santa Cruz de la Sierra. Trabajamos con estudiantes, familias y organizaciones que buscan avanzar con objetivos claros.</p>
        <h2>Nuestra misión</h2>
        <p>Facilitar experiencias de aprendizaje comprensibles, prácticas y cercanas, integrando educación y tecnología de manera responsable.</p>
        <h2>Nuestra visión</h2>
        <p>Ser una comunidad educativa reconocida por acompañar procesos reales de crecimiento académico, personal y digital.</p>
        <h2>Nuestros principios</h2>
        <ul><li>Claridad antes que complejidad.</li><li>Seguimiento antes que improvisación.</li><li>Tecnología con propósito.</li><li>Respeto por el ritmo de cada persona.</li></ul>
    @elseif($pageKey === 'contact')
        <span class="ife-eyebrow">Estamos cerca</span>
        <h1>Conversemos.</h1>
        <p>Cuéntanos qué necesitas y te orientaremos hacia el programa, horario o modalidad más conveniente.</p>
        <h2>Canales de atención</h2>
        <p><strong>WhatsApp:</strong> <a href="https://wa.me/59171324941">+591 71324941</a><br><strong>Teléfonos:</strong> 71039910 · 75553338<br><strong>Correo:</strong> <a href="mailto:info@ife.bo">info@ife.bo</a></p>
        <h2>Ubicación</h2>
        <p>Santa Cruz de la Sierra, Bolivia. Escríbenos antes de visitarnos para confirmar atención y horarios.</p>
        <div class="ife-actions"><a class="ife-button" href="https://wa.me/59171324941?text=Hola%20IFE%20Educabol,%20quiero%20información" target="_blank" rel="noopener">Escribir por WhatsApp</a></div>
    @elseif($pageKey === 'privacy')
        <span class="ife-eyebrow">Privacidad</span>
        <h1>Tu información merece cuidado.</h1>
        <p>IFE Educabol utiliza los datos proporcionados para gestionar solicitudes, inscripciones, comunicaciones y servicios educativos.</p>
        <h2>Datos que recopilamos</h2><p>Información de contacto, datos académicos y registros necesarios para prestar nuestros servicios.</p>
        <h2>Cómo los utilizamos</h2><p>Los usamos únicamente para atención, seguimiento educativo, administración y comunicaciones relacionadas con IFE Educabol.</p>
        <h2>Tus derechos</h2><p>Puedes solicitar actualización o revisión de tus datos escribiendo a info@ife.bo.</p>
    @elseif($pageKey === 'termscondition')
        <span class="ife-eyebrow">Condiciones</span>
        <h1>Acuerdos simples y transparentes.</h1>
        <p>Al contratar o utilizar los servicios de IFE Educabol, aceptas las condiciones operativas informadas al momento de la inscripción.</p>
        <h2>Servicios</h2><p>Los contenidos, horarios y modalidades se confirman según disponibilidad y programa contratado.</p>
        <h2>Pagos y reprogramaciones</h2><p>Las condiciones específicas se comunican antes de iniciar el servicio y pueden variar según modalidad.</p>
        <h2>Uso responsable</h2><p>Los recursos educativos se ofrecen para uso personal y no deben redistribuirse sin autorización.</p>
    @elseif($pageKey === 'preguntasfrecuentes')
        <span class="ife-eyebrow">Respuestas rápidas</span>
        <h1>Lo esencial antes de empezar.</h1>
        <div class="ife-faq">
            @forelse($preguntasfrecuentes ?? [] as $question)
                <details>
                    <summary>{{ $question->question }}</summary>
                    <p>{{ $question->answer }}</p>
                </details>
            @empty
                <details open><summary>¿Cómo solicito información?</summary><p>Escríbenos por WhatsApp al +591 71324941 y te orientaremos.</p></details>
                <details><summary>¿Las clases son personalizadas?</summary><p>La modalidad depende del programa. Confirmamos la opción adecuada según tus objetivos.</p></details>
            @endforelse
        </div>
    @endif
</article>
@endsection

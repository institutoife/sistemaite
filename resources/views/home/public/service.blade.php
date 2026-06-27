@php
    $page = config('public_pages.'.$pageKey, [
        'title' => 'Formación IFE',
        'eyebrow' => 'IFE Educabol',
        'description' => 'Aprendizaje práctico y acompañamiento personalizado.',
        'topics' => [],
    ]);
    $modalidades = isset($modalidades) ? collect($modalidades) : collect();
    $catalogItems = isset($catalogItems) ? collect($catalogItems) : collect();
    $whatsappText = rawurlencode('Hola IFE Educabol, quiero información sobre '.$page['title']);
@endphp

@extends('layouts.public')

@section('title', $page['title'].' | IFE Educabol')
@section('meta_description', $page['description'])

@section('content')
<section class="ife-hero">
    <div class="ife-shell ife-hero-inner">
        <img class="ife-hero-logo" src="{{ asset('assetpublic/images/logo.png') }}" alt="IFE Educabol">
        <span class="ife-eyebrow">{{ $page['eyebrow'] }}</span>
        <h1>{{ $page['title'] }}</h1>
        <p>{{ $page['description'] }}</p>
        <div class="ife-actions">
            <a class="ife-button" href="https://wa.me/59171324941?text={{ $whatsappText }}" target="_blank" rel="noopener">Solicitar información</a>
            <a class="ife-button ife-button-secondary" href="#contenido-programa">Ver el programa</a>
        </div>
    </div>
</section>

<section class="ife-section" id="contenido-programa">
    <div class="ife-shell">
        <div class="ife-section-heading">
            <div>
                <span class="ife-eyebrow">Contenido</span>
                <h2>Aprendizaje útil y bien acompañado.</h2>
            </div>
            <p>El programa se adapta al punto de partida del estudiante, con explicaciones claras, práctica guiada y seguimiento.</p>
        </div>
        <ul class="ife-topic-list">
            @foreach($page['topics'] as $topic)
                <li>{{ $topic }}</li>
            @endforeach
        </ul>
    </div>
</section>

<section class="ife-section ife-section-soft">
    <div class="ife-shell">
        <div class="ife-section-heading">
            <div>
                <span class="ife-eyebrow">Cómo trabajamos</span>
                <h2>Un proceso simple y medible.</h2>
            </div>
            <p>Cada etapa tiene un propósito concreto y permite ajustar el acompañamiento según los avances.</p>
        </div>
        <div class="ife-grid">
            @foreach([
                ['Evaluamos', 'Reconocemos conocimientos previos y necesidades.'],
                ['Explicamos', 'Presentamos conceptos con claridad y ejemplos.'],
                ['Practicamos', 'Aplicamos lo aprendido con guía cercana.'],
                ['Revisamos', 'Medimos avances y reforzamos puntos clave.'],
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

@if($modalidades->isNotEmpty() || $catalogItems->isNotEmpty())
<section class="ife-section">
    <div class="ife-shell">
        <div class="ife-section-heading">
            <div>
                <span class="ife-eyebrow">Opciones disponibles</span>
                <h2>Elige el formato adecuado.</h2>
            </div>
            <p>La disponibilidad puede variar. Confirma horarios y detalles con nuestro equipo.</p>
        </div>
        <div class="ife-offers">
            @foreach($modalidades->take(9) as $item)
                <article class="ife-offer">
                    <h3>{{ $item->modalidad ?? 'Modalidad personalizada' }}</h3>
                    <p>{{ $item->descripcion ?? 'Consulta disponibilidad, horarios y alcance.' }}</p>
                </article>
            @endforeach
            @foreach($catalogItems->take(9) as $item)
                <article class="ife-offer">
                    <h3>{{ $item->asignatura ?? $item->nombre ?? 'Programa disponible' }}</h3>
                    <p>{{ $item->descripcion ?? 'Consulta contenidos, requisitos y horarios.' }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="ife-cta">
    <div class="ife-shell ife-cta-inner">
        <div>
            <h2>Empieza con una orientación clara.</h2>
            <p>Cuéntanos tu objetivo y te ayudamos a elegir el siguiente paso.</p>
        </div>
        <a class="ife-button ife-button-secondary" href="https://wa.me/59171324941?text={{ $whatsappText }}" target="_blank" rel="noopener">Hablar con IFE</a>
    </div>
</section>
@endsection

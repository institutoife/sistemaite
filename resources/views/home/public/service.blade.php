@php
    $page = config('public_pages.'.$pageKey, [
        'title' => 'Formación IFE',
        'eyebrow' => 'IFE Educabol',
        'description' => 'Aprendizaje práctico y acompañamiento personalizado.',
        'topics' => [],
    ]);
    $modalidades = isset($modalidades) ? collect($modalidades) : collect();
    $catalogItems = isset($catalogItems) ? collect($catalogItems) : collect();
    $serviceNames = ['primaria' => 'Primaria', 'secundaria' => 'Secundaria', 'computacion' => 'Computación'];
    $serviceName = $serviceNames[$pageKey] ?? $page['title'];
    $whatsappMessage = 'Hola, vengo de la página '.$serviceName.' y quisiera más información.';
    $whatsappUrl = 'https://wa.me/59171324941?text='.rawurlencode($whatsappMessage);
    $benefitDescriptions = [
        'Explicaciones claras y adaptadas al nivel de cada estudiante.',
        'Práctica guiada para convertir conceptos en habilidades reales.',
        'Seguimiento cercano para reconocer avances y reforzar dificultades.',
        'Metodología flexible enfocada en objetivos concretos.',
    ];
    $benefitIcons = ['fa-lightbulb', 'fa-pen-ruler', 'fa-chart-line', 'fa-bullseye'];
    $otherServices = collect(config('public_site.services', []))->reject(fn ($service) => $service['route'] === $pageKey);
@endphp

@extends('layouts.public')

@section('title', $page['title'].' | IFE Educabol')
@section('meta_description', $page['description'])

@section('content')
<section class="ife-service-hero">
    <div class="ife-shell ife-service-hero-inner">
        <div class="ife-service-hero-copy">
        <span class="ife-eyebrow">{{ $page['eyebrow'] }}</span>
        <h1>{{ $page['title'] }}</h1>
        <p>{{ $page['description'] }}</p>
        <div class="ife-actions">
            <a class="ife-button" href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i> WhatsApp</a>
            <a class="ife-button ife-button-secondary" href="#solicitar-informacion">Solicitar información</a>
        </div>
        <div class="ife-hero-proof" aria-label="Características del servicio">
            <span><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Atención personalizada</span>
            <span><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Docentes con experiencia</span>
        </div>
        </div>
        <div class="ife-david-stage" aria-label="Representante de IFE Educabol">
            @if(file_exists(public_path('images/david.png')))
                <img src="{{ asset('images/david.png') }}" alt="David, representante de IFE Educabol">
            @endif
        </div>
    </div>
</section>

<section class="ife-section" id="beneficios">
    <div class="ife-shell">
        <div class="ife-section-heading">
            <div>
                <span class="ife-eyebrow">Por qué elegirnos</span>
                <h2>Aprender con apoyo cambia el resultado.</h2>
            </div>
            <p>Creamos una experiencia cercana, práctica y enfocada en que cada estudiante avance con seguridad.</p>
        </div>
        <div class="ife-benefit-grid">
            @foreach($page['topics'] as $topic)
                <article class="ife-benefit-card">
                    <span class="ife-icon-box"><i class="fa-solid {{ $benefitIcons[$loop->index % count($benefitIcons)] }}" aria-hidden="true"></i></span>
                    <h3>{{ $topic }}</h3>
                    <p>{{ $benefitDescriptions[$loop->index % count($benefitDescriptions)] }}</p>
                </article>
            @endforeach
        </div>
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

<section class="ife-section ife-services-band">
    <div class="ife-shell">
        <div class="ife-section-heading">
            <div><span class="ife-eyebrow">Más para aprender</span><h2>Otros servicios de IFE.</h2></div>
            <p>Explora programas académicos y tecnológicos pensados para distintas etapas y objetivos.</p>
        </div>
        <div class="ife-service-links">
            @foreach($otherServices->take(6) as $service)
                <a class="ife-service-link" href="{{ route($service['route']) }}">
                    <span class="ife-icon-box"><i class="fa-solid {{ $service['icon'] }}" aria-hidden="true"></i></span>
                    <strong>{{ $service['label'] }}</strong>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            @endforeach
        </div>
    </div>
</section>

<section class="ife-section ife-section-soft">
    <div class="ife-shell">
        <div class="ife-section-heading">
            <div><span class="ife-eyebrow">Aprende con nosotros</span><h2>Nuestros videos</h2></div>
            <p>Ideas breves, consejos y momentos de nuestras clases en TikTok.</p>
        </div>
        <div class="ife-video-grid">
            @foreach(config('public_site.tiktok_videos', []) as $video)
                <a class="ife-video-card" href="{{ $video['url'] }}" target="_blank" rel="noopener noreferrer">
                    <span class="ife-video-index">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <i class="fa-brands fa-tiktok" aria-hidden="true"></i>
                    <h3>{{ $video['title'] }}</h3>
                    <span>Ver en TikTok <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
                </a>
            @endforeach
        </div>
    </div>
</section>

<section class="ife-social-section">
    <div class="ife-shell">
        <div class="ife-social-heading"><span class="ife-eyebrow">Comunidad IFE</span><h2>Síguenos y aprende cada día.</h2></div>
        <div class="ife-social-grid">
            @foreach(config('public_site.socials', []) as $social)
                <a class="ife-social-link" href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer">
                    <i class="{{ $social['icon'] }}" aria-hidden="true"></i>
                    <span><strong>{{ $social['label'] }}</strong><small>{{ $social['handle'] }}</small></span>
                </a>
            @endforeach
        </div>
    </div>
</section>

<section class="ife-cta" id="solicitar-informacion">
    <div class="ife-shell ife-cta-inner">
        <div>
            <span class="ife-eyebrow">Tu siguiente paso</span>
            <h2>Conversemos sobre {{ $serviceName }}.</h2>
            <p>Recibe orientación sobre modalidad, horarios y disponibilidad.</p>
        </div>
        <a class="ife-button ife-button-secondary" href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i> Solicitar información</a>
    </div>
</section>
@endsection

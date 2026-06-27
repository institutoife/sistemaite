@extends('layouts.public')

@section('title', $nivel->nivel.' | IFE Educabol')
@section('meta_description', 'Modalidades disponibles para '.$nivel->nivel.' en IFE Educabol.')

@section('content')
<section class="ife-hero ife-hero-compact">
    <div class="ife-shell ife-hero-inner">
        <img class="ife-hero-logo" src="{{ asset('assetpublic/images/logo.png') }}" alt="IFE Educabol">
        <span class="ife-eyebrow">Programas académicos</span>
        <h1>{{ $nivel->nivel }}</h1>
        <p>Conoce las modalidades disponibles y elige el acompañamiento adecuado para avanzar.</p>
    </div>
</section>

<section class="ife-section">
    <div class="ife-shell">
        <div class="ife-section-heading">
            <div><span class="ife-eyebrow">Modalidades</span><h2>Opciones claras para aprender.</h2></div>
            <p>Consulta disponibilidad, horarios y condiciones directamente con nuestro equipo.</p>
        </div>
        <div class="ife-offers">
            @forelse($modalidades as $modalidad)
                <article class="ife-offer">
                    <h3>{{ $modalidad->modalidad }}</h3>
                    <p>{{ $modalidad->descripcion }}</p>
                    <div class="ife-detail-list">
                        <div class="ife-detail-row"><span>Costo</span><strong>Bs. {{ $modalidad->costo }}</strong></div>
                        <div class="ife-detail-row"><span>Carga horaria</span><strong>{{ $modalidad->cargahoraria }} h</strong></div>
                        <div class="ife-detail-row"><span>Estado</span><strong>{{ $modalidad->vigente == 1 ? 'Disponible' : 'No disponible' }}</strong></div>
                    </div>
                    @if($modalidad->vigente == 1)
                        <div class="ife-actions">
                            <a class="ife-button ife-button-small" href="https://wa.me/59171324941?text={{ rawurlencode('Hola IFE Educabol, quiero información sobre '.$modalidad->modalidad) }}" target="_blank" rel="noopener">Consultar</a>
                        </div>
                    @endif
                </article>
            @empty
                <article class="ife-offer"><h3>Próximamente</h3><p>Estamos actualizando las modalidades disponibles. Contáctanos para recibir orientación.</p></article>
            @endforelse
        </div>
    </div>
</section>
@endsection

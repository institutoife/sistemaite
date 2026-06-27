@extends('layouts.public')

@section('title', 'Planes | IFE Educabol')
@section('meta_description', 'Planes de formación disponibles en IFE Educabol.')

@section('content')
<section class="ife-hero ife-hero-compact">
    <div class="ife-shell ife-hero-inner">
        <img class="ife-hero-logo" src="{{ asset('assetpublic/images/logo.png') }}" alt="IFE Educabol">
        <span class="ife-eyebrow">Planes IFE</span>
        <h1>Opciones claras para avanzar.</h1>
        <p>Revisa los planes disponibles y consulta con nuestro equipo la modalidad que mejor se adapta a tu objetivo.</p>
    </div>
</section>
<section class="ife-section">
    <div class="ife-shell">
        <div class="ife-offers">
            @forelse($planes as $plan)
                <article class="ife-offer">
                    <h3>{{ $plan->titulo }}</h3>
                    <p>{!! strip_tags($plan->descripcion) !!}</p>
                    @if(!is_null($plan->costo))
                        <p><strong>Bs. {{ number_format($plan->costo, 2) }}</strong></p>
                    @endif
                    <div class="ife-actions">
                        <a class="ife-button ife-button-small" href="https://wa.me/59171324941?text={{ rawurlencode('Hola IFE Educabol, quiero información sobre el plan '.$plan->titulo) }}" target="_blank" rel="noopener">Consultar</a>
                    </div>
                </article>
            @empty
                <article class="ife-offer"><h3>Planes a medida</h3><p>Contacta a nuestro equipo para recibir una propuesta según tus necesidades.</p></article>
            @endforelse
        </div>
    </div>
</section>
@endsection

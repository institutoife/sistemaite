@extends('layouts.public')

@section('title', $interest->interest.' | IFE Educabol')
@section('meta_description', 'Información sobre '.$interest->interest.' en IFE Educabol.')

@section('content')
<section class="ife-hero ife-hero-compact">
    <div class="ife-shell ife-hero-inner">
        <img class="ife-hero-logo" src="{{ asset('assetpublic/images/logo.png') }}" alt="IFE Educabol">
        <span class="ife-eyebrow">Área de interés</span>
        <h1>{{ $interest->interest }}</h1>
        <p>Información práctica para ayudarte a elegir tu siguiente paso.</p>
    </div>
</section>

<section class="ife-section">
    <div class="ife-content">
        <span class="ife-eyebrow">Conoce el programa</span>
        @forelse($observaciones as $observacion)
            <div class="ife-richtext">{!! $observacion->observacion !!}</div>
        @empty
            <h1>Te orientamos personalmente.</h1>
            <p>Escríbenos para conocer contenidos, horarios y modalidades disponibles.</p>
        @endforelse
        <div class="ife-actions">
            <a class="ife-button" href="https://wa.me/59171324941?text={{ rawurlencode('Hola IFE Educabol, quiero información sobre '.$interest->interest) }}" target="_blank" rel="noopener">Solicitar información</a>
        </div>
    </div>
</section>
@endsection

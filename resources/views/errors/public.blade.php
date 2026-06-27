@extends('layouts.public')

@php
    $status = $status ?? 404;
    $messages = [
        403 => ['Acceso restringido', 'No tienes permiso para abrir esta página.'],
        404 => ['Página no encontrada', 'La dirección cambió o el contenido ya no está disponible.'],
    ];
    $error = $messages[$status] ?? ['Algo no salió como esperábamos', 'Vuelve al inicio e inténtalo nuevamente.'];
@endphp

@section('title', $status.' | IFE Educabol')
@section('meta_description', $error[1])

@section('content')
<section class="ife-hero ife-hero-compact">
    <div class="ife-shell ife-hero-inner">
        <img class="ife-hero-logo" src="{{ asset('assetpublic/images/logo.png') }}" alt="IFE Educabol">
        <span class="ife-eyebrow">Error {{ $status }}</span>
        <h1>{{ $error[0] }}</h1>
        <p>{{ $error[1] }}</p>
        <div class="ife-actions">
            <a class="ife-button" href="{{ url('/') }}">Volver al inicio</a>
            <a class="ife-button ife-button-secondary" href="{{ route('contact') }}">Contactar a IFE</a>
        </div>
    </div>
</section>
@endsection

@extends('layouts.public')

@section('title', 'Programas | IFE Educabol')
@section('meta_description', 'Conoce los programas académicos, tecnológicos y formativos de IFE Educabol.')

@section('content')
<section class="ife-hero ife-hero-compact">
    <div class="ife-shell ife-hero-inner">
        <img class="ife-hero-logo" src="{{ asset('assetpublic/images/logo.png') }}" alt="IFE Educabol">
        <span class="ife-eyebrow">Programas IFE</span>
        <h1>Encuentra tu próximo aprendizaje.</h1>
        <p>Opciones académicas y tecnológicas organizadas para distintas etapas, intereses y objetivos.</p>
    </div>
</section>
<section class="ife-section">
    <div class="ife-shell">
        <div class="ife-grid">
            @foreach(config('public_pages') as $key => $page)
                @if(Route::has($key))
                    <a class="ife-card ife-card-link" href="{{ route($key) }}">
                        <span class="ife-card-number">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <h3>{{ $page['title'] }}</h3>
                        <p>{{ $page['description'] }}</p>
                    </a>
                @endif
            @endforeach
        </div>
    </div>
</section>
@endsection

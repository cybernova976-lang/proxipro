@extends('layouts.app')

@section('title', $guide['title'].' - Prokejem')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/guides.css') }}?v={{ @filemtime(public_path('css/guides.css')) ?: 1 }}">
@endpush

@section('content')
<main class="guide-page">
    <div class="guide-shell guide-shell--article">
        <nav class="guide-breadcrumb" aria-label="Fil d’Ariane">
            <a href="{{ url('/') }}">Accueil</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <a href="{{ route('guides.index') }}">Conseils</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <span>{{ $guide['title'] }}</span>
        </nav>

        <header class="guide-article-hero">
            <span class="guide-article-icon"><i class="{{ $guide['icon'] }}"></i></span>
            <div>
                <div class="guide-article-meta">
                    <span>{{ $guide['kicker'] }}</span>
                    <span><i class="far fa-clock"></i> {{ $guide['reading_time'] }} min</span>
                </div>
                <h1>{{ $guide['title'] }}</h1>
                <p>{{ $guide['summary'] }}</p>
            </div>
        </header>

        <div class="guide-article-layout">
            <article class="guide-article" aria-label="Contenu du guide">
                @foreach($guide['sections'] as $index => $section)
                    <section class="guide-article-section">
                        <span class="guide-article-number" aria-hidden="true">{{ $index + 1 }}</span>
                        <div>
                            <h2>{{ $section['title'] }}</h2>
                            <p>{{ $section['text'] }}</p>
                            <ul>
                                @foreach($section['bullets'] as $bullet)
                                    <li><i class="fas fa-check" aria-hidden="true"></i><span>{{ $bullet }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                    </section>
                @endforeach

                <div class="guide-article-cta">
                    <div>
                        <span>Passer à l’action</span>
                        <strong>{{ $guide['cta_label'] }}</strong>
                    </div>
                    <a href="{{ route($guide['cta_route'], $guide['cta_route_params'] ?? []) }}">
                        {{ $guide['cta_label'] }} <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </article>

            <aside class="guide-checklist" aria-labelledby="guideChecklistTitle">
                <span class="guide-checklist__icon"><i class="fas fa-list-check"></i></span>
                <h2 id="guideChecklistTitle">À retenir</h2>
                <ul>
                    @foreach($guide['checklist'] as $item)
                        <li><i class="fas fa-circle-check"></i><span>{{ $item }}</span></li>
                    @endforeach
                </ul>
                <a href="{{ route('legal.platform-rules') }}">Consulter les règles <i class="fas fa-arrow-right"></i></a>
            </aside>
        </div>

        @if($relatedGuides->isNotEmpty())
            <section class="guide-related" aria-labelledby="relatedGuidesTitle">
                <div class="guide-section-head">
                    <div>
                        <span class="guide-section-label">Continuer</span>
                        <h2 id="relatedGuidesTitle">Guides liés à votre parcours</h2>
                    </div>
                    <a href="{{ route('guides.index') }}">Tous les conseils <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="guide-grid">
                    @foreach($relatedGuides as $relatedGuide)
                        @include('guides.partials.card', ['guide' => $relatedGuide])
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</main>
@endsection

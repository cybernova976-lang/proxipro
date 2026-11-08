{{-- Parcours guidé « Publier sans écrire » : une question par écran, texte composé à partir des réponses. --}}
{{-- Sans JavaScript, toutes les étapes restent visibles sur une longue page et le serveur compose le texte. --}}
@extends('layouts.app')

@section('title', 'Décrire mon besoin - Prokejem')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/demand-guided.css') }}?v={{ @filemtime(public_path('css/demand-guided.css')) ?: 1 }}">
@endpush

@section('content')
@php
    $pkQuestionCount = count($pkQuestions);
    $pkConfigJson = Illuminate\Support\Js::encode($pkGuidedConfig);
@endphp
<div class="pk-guided" id="pkGuided">
    <form id="pkGuidedForm" class="pk-guided__form" method="POST" action="{{ route('demand.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        <input type="hidden" name="situation_key" value="{{ $pkSituation['key'] }}">
        <input type="hidden" name="guided" value="1">

        @include('demands.partials.guided.header')

        @include('demands.partials.guided.situation', ['n' => 1])

        @foreach($pkQuestions as $qKey => $question)
            @include('demands.partials.guided.question', ['n' => $loop->iteration + 1, 'qKey' => $qKey, 'question' => $question])
        @endforeach

        @include('demands.partials.guided.photo', ['n' => $pkQuestionCount + 2])
        @include('demands.partials.guided.place', ['n' => $pkQuestionCount + 3])
        @include('demands.partials.guided.when', ['n' => $pkQuestionCount + 4])
        @include('demands.partials.guided.budget', ['n' => $pkQuestionCount + 5])
        @include('demands.partials.guided.review', ['n' => $pkQuestionCount + 6])

        <div class="pk-guided__bar" data-pk-nav hidden>
            <button type="button" class="pk-guided__next" data-pk-next>Suivant</button>
        </div>
    </form>

    @include('demands.partials.guided.assist')

    <script type="application/json" id="pkGuidedConfig">{!! $pkConfigJson !!}</script>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/demand-text-composer.js') }}?v={{ @filemtime(public_path('js/demand-text-composer.js')) ?: 1 }}" defer></script>
<script src="{{ asset('js/demand-guided.js') }}?v={{ @filemtime(public_path('js/demand-guided.js')) ?: 1 }}" defer></script>
@endpush

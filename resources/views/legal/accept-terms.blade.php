@extends('layouts.app')

@section('title', 'Nouvelles conditions d’utilisation - ' . config('app.name', 'Prokejem'))

@push('styles')
<link rel="stylesheet" href="{{ asset('css/terms-accept.css') }}?v={{ filemtime(public_path('css/terms-accept.css')) }}">
@endpush

@section('content')
<div class="pk-terms">
    <div class="pk-terms__card">
        <h1 class="pk-terms__title">Nos conditions d’utilisation ont changé</h1>
        <p class="pk-terms__lead">
            @if($version->isMandatory())
                Pour continuer à utiliser {{ config('app.name', 'Prokejem') }}, merci de prendre connaissance de la nouvelle version et de l’accepter.
            @else
                Une nouvelle version s’appliquera à partir du <strong>{{ $version->effective_at->format('d/m/Y') }}</strong>. Vous pouvez l’accepter dès maintenant ; à partir de cette date, elle sera nécessaire pour continuer à utiliser votre compte.
            @endif
        </p>

        <p class="pk-terms__label">Ce qui change</p>
        <p class="pk-terms__summary">{{ $version->summary }}</p>
        <a class="pk-terms__read" href="{{ route('legal.terms') }}" target="_blank" rel="noopener">Lire les conditions complètes</a>

        <form action="{{ route('terms.accept.store') }}" method="POST">
            @csrf
            <label class="pk-terms__check">
                <input type="checkbox" name="accept" value="1" required>
                <span>J’ai lu et j’accepte les conditions générales d’utilisation.</span>
            </label>
            @error('accept')
                <p class="pk-terms__error" role="alert">{{ $message }}</p>
            @enderror
            <div class="pk-terms__actions">
                <button type="submit" class="pk-terms__btn">J’accepte et je continue</button>
                @unless($version->isMandatory())
                    <a class="pk-terms__later" href="{{ $continueUrl }}" data-terms-later>Plus tard</a>
                @endunless
            </div>
        </form>

        <div class="pk-terms__refuse">
            Vous ne souhaitez pas accepter ? Vous pouvez
            <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit">vous déconnecter</button></form>,
            ou nous écrire depuis la page <a href="{{ route('contact.index') }}">Contact</a> pour fermer votre compte.
        </div>
    </div>
</div>
@endsection

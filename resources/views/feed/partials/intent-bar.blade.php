{{-- Deux parcours distincts : choisir une personne ou publier pour recevoir des propositions. --}}
@php
    $pkIsProvider = ($pkRole ?? 'client') === 'provider';
    $pkIntentUrl = $pkIsProvider
        ? route('ads.index', ['type' => 'demandes'])
        : route('feed.professionals');
@endphp

<div class="pk-intent">
    <form class="pk-intent__form" id="pkIntentForm" action="{{ $pkIntentUrl }}" method="GET" role="search">
        @if($pkIsProvider)
            <input type="hidden" name="type" value="demandes">
        @else
            @if($geoCity)<input type="hidden" name="city" value="{{ $geoCity }}">@endif
            @if($geoCountry)<input type="hidden" name="country" value="{{ $geoCountry }}">@endif
        @endif
        <div class="pk-intent__wrap">
            <i class="fas fa-search pk-intent__icon" aria-hidden="true"></i>
            <label class="pk-sr" for="pkIntentField">
                {{ $pkIsProvider ? 'Quelle demande recherchez-vous ?' : 'De quoi avez-vous besoin ?' }}
            </label>
            <input type="text"
                   class="pk-intent__field"
                   id="pkIntentField"
                   name="{{ $pkIsProvider ? 'search' : 'q' }}"
                   maxlength="100"
                   autocomplete="off"
                   role="combobox"
                   aria-expanded="false"
                   aria-controls="pkSuggest"
                   aria-autocomplete="list"
                   placeholder="{{ $pkIsProvider ? 'Métier ou service…' : 'Ex. plomberie…' }}">
            <div class="pk-suggest" id="pkSuggest" role="listbox" hidden></div>
        </div>
    </form>

    <button type="submit" form="pkIntentForm" class="pk-btn" aria-label="{{ $pkIsProvider ? 'Voir les demandes compatibles' : 'Trouver un prestataire' }}">
        <i class="fas {{ $pkIsProvider ? 'fa-bullseye' : 'fa-search' }}" aria-hidden="true"></i>
        <span class="pk-intent__submit-full">{{ $pkIsProvider ? 'Voir les demandes' : 'Trouver un prestataire' }}</span>
        <span class="pk-intent__submit-short" aria-hidden="true">Chercher</span>
    </button>
</div>

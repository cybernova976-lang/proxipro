{{-- Zone 1 · barre d'intention — unique porte d'entree vers la publication --}}
@php
    $pkIsProvider = ($pkRole ?? 'client') === 'provider';
    $pkPublishUrl = route('demand.create');
    $pkIntentUrl = $pkIsProvider
        ? route('ads.index', ['type' => 'demandes'])
        : $pkPublishUrl;
@endphp

<div class="pk-intent">
    <form class="pk-intent__form" id="pkIntentForm" action="{{ $pkIntentUrl }}" method="GET" role="search">
        <div class="pk-intent__wrap">
            <i class="fas fa-search pk-intent__icon" aria-hidden="true"></i>
            <label class="pk-sr" for="pkIntentField">
                {{ $pkIsProvider ? 'Quelle demande recherchez-vous ?' : 'De quoi avez-vous besoin ?' }}
            </label>
            <input type="text"
                   class="pk-intent__field"
                   id="pkIntentField"
                   name="q"
                   autocomplete="off"
                   role="combobox"
                   aria-expanded="false"
                   aria-controls="pkSuggest"
                   aria-autocomplete="list"
                   placeholder="{{ $pkIsProvider ? 'Métier ou service…' : 'Ex. plomberie…' }}">
            <div class="pk-suggest" id="pkSuggest" role="listbox" hidden></div>
        </div>
    </form>

    <button type="submit" form="pkIntentForm" class="pk-btn" aria-label="{{ $pkIsProvider ? 'Voir les demandes compatibles' : 'Publier une demande' }}">
        <i class="fas {{ $pkIsProvider ? 'fa-bullseye' : 'fa-plus' }}"></i>
        <span class="pk-intent__submit-full">{{ $pkIsProvider ? 'Voir les demandes' : 'Publier une demande' }}</span>
        <span class="pk-intent__submit-short" aria-hidden="true">{{ $pkIsProvider ? 'Chercher' : 'Publier' }}</span>
    </button>
</div>

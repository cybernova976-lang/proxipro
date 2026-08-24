{{-- Zone 1 · barre d'intention — unique porte d'entree vers la publication --}}
@php
    $pkUser = Auth::user();
    $pkIsProvider = ($pkRole ?? 'client') === 'provider';
    $pkPublishUrl = $pkIsProvider
        ? route('ads.create', ['type' => 'service'])
        : route('demand.create');
@endphp

<div class="pk-card pk-intent">
    @auth
        <span class="pk-intent__av">
            @if($pkUser->avatar)
                <img src="{{ storage_url($pkUser->avatar) }}" alt="">
            @else
                {{ Str::upper(Str::substr($pkUser->name ?? 'U', 0, 1)) }}
            @endif
        </span>
    @endauth

    <form class="pk-intent__form" id="pkIntentForm" action="{{ $pkPublishUrl }}" method="GET" role="search">
        <div class="pk-intent__wrap">
            <i class="fas fa-search pk-intent__icon" aria-hidden="true"></i>
            <label class="pk-sr" for="pkIntentField">
                {{ $pkIsProvider ? 'Quel service proposez-vous ?' : 'De quoi avez-vous besoin ?' }}
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
                   placeholder="{{ $pkIsProvider ? 'Quel service proposez-vous ?' : 'De quoi avez-vous besoin ? Ex. fuite d\'eau' }}">
            <div class="pk-suggest" id="pkSuggest" role="listbox" hidden></div>
        </div>
    </form>

    <a href="{{ $pkPublishUrl }}" class="pk-btn">
        <i class="fas fa-plus"></i>
        <span>{{ $pkIsProvider ? 'Publier une offre' : 'Publier une demande' }}</span>
    </a>
</div>

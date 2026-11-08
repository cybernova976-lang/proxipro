{{-- Lieu : la commune du compte est proposée d'abord ; sinon des tuiles (20 communes au plus) ou un champ avec suggestions. --}}
@php
    $pkKnownCity = $pkPrefill['city'];
    $pkOldCity = old('city', $pkKnownCity);
    $pkTileCommunes = $pkCommuneTiles ? $pkCommunes : [];
    if ($pkKnownCity !== null && $pkTileCommunes !== [] && ! in_array($pkKnownCity, $pkTileCommunes, true)) {
        array_unshift($pkTileCommunes, $pkKnownCity);
    }
@endphp
<fieldset class="pk-guided__step" data-pk-step="{{ $n }}" data-pk-place>
    <legend class="pk-guided__legend" tabindex="-1">{{ $pkKnownCity !== null ? 'C’est à '.$pkKnownCity.' ?' : 'Dans quelle commune ?' }}</legend>
    <button type="button" class="pk-listen" data-pk-listen hidden>Écouter</button>

    @if($pkPrefill['country'] !== null)
        <input type="hidden" name="country" value="{{ $pkPrefill['country'] }}">
    @else
        <p class="pk-guided__hint">Votre pays ou territoire :</p>
        <div class="pk-answers pk-answers--text">
            @foreach($pkCountries as $pkCountry)
                <label class="pk-answer pk-answer--text">
                    <input type="radio" name="country" value="{{ $pkCountry }}" @checked(old('country') === $pkCountry)>
                    <span class="pk-answer__label">{{ $pkCountry }}</span>
                </label>
            @endforeach
        </div>
    @endif

    @if($pkTileCommunes !== [])
        <div class="pk-answers pk-answers--text pk-answers--many">
            @foreach($pkTileCommunes as $pkCommune)
                <label class="pk-answer pk-answer--text">
                    <input type="radio" name="city" value="{{ $pkCommune }}" @checked($pkOldCity === $pkCommune)>
                    <span class="pk-answer__label">{{ $pkCommune === $pkKnownCity ? 'Oui, '.$pkCommune : $pkCommune }}</span>
                </label>
            @endforeach
        </div>
    @else
        @if($pkKnownCity !== null)
            <div class="pk-answers pk-answers--text">
                <label class="pk-answer pk-answer--text">
                    <input type="radio" name="city" value="{{ $pkKnownCity }}" @checked($pkOldCity === $pkKnownCity)>
                    <span class="pk-answer__label">Oui</span>
                </label>
                <label class="pk-answer pk-answer--text">
                    <input type="radio" name="city" value="__other__" data-pk-city-other @checked($pkOldCity === '__other__')>
                    <span class="pk-answer__label">Autre commune</span>
                </label>
            </div>
        @endif
        <div class="pk-guided__field" data-pk-city-typed>
            <label for="pkCityTyped">Votre commune</label>
            <input type="text" id="pkCityTyped" name="city_typed" value="{{ old('city_typed') }}" list="pkCommunes" autocomplete="address-level2" maxlength="120">
            <datalist id="pkCommunes">
                @foreach($pkCommunes as $pkCommune)
                    <option value="{{ $pkCommune }}"></option>
                @endforeach
            </datalist>
        </div>
    @endif
</fieldset>

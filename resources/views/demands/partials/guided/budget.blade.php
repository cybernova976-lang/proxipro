{{-- Prix : « À discuter » est coché ; « Je propose un prix » ouvre le champ en euros. --}}
@php
    $pkOldPriceType = old('price_type', 'negotiable');
@endphp
<fieldset class="pk-guided__step" data-pk-step="{{ $n }}" data-pk-budget>
    <legend class="pk-guided__legend" tabindex="-1">Votre prix ?</legend>
    <button type="button" class="pk-listen" data-pk-listen hidden>Écouter</button>
    <div class="pk-answers pk-answers--text">
        <label class="pk-answer pk-answer--text">
            <input type="radio" name="price_type" value="negotiable" @checked($pkOldPriceType === 'negotiable')>
            <img src="{{ asset('img/pk-situations/prix.svg') }}" alt="" width="40" height="40">
            <span class="pk-answer__label">À discuter</span>
        </label>
        <label class="pk-answer pk-answer--text">
            <input type="radio" name="price_type" value="fixed" @checked($pkOldPriceType === 'fixed')>
            <img src="{{ asset('img/pk-situations/prix.svg') }}" alt="" width="40" height="40">
            <span class="pk-answer__label">Je propose un prix</span>
        </label>
    </div>
    <div class="pk-guided__field" data-pk-price>
        <label for="pkPrice">Prix en euros</label>
        <input type="text" id="pkPrice" name="price" inputmode="numeric" pattern="[0-9]*" min="1" value="{{ old('price') }}" autocomplete="off">
    </div>
</fieldset>

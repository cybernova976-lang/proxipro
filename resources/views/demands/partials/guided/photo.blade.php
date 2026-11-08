{{-- Photo facultative : appareil photo ou galerie, réduite dans le navigateur avant l'envoi (2 au plus). --}}
<fieldset class="pk-guided__step" data-pk-step="{{ $n }}" data-pk-photo>
    <legend class="pk-guided__legend" tabindex="-1">Montrez le problème (si vous voulez)</legend>
    <button type="button" class="pk-listen" data-pk-listen hidden>Écouter</button>
    <div class="pk-answers" data-pk-photo-actions hidden>
        <label class="pk-answer">
            <input type="file" class="pk-guided__file" accept="image/jpeg,image/png,image/webp" capture="environment" data-pk-photo-camera>
            <img src="{{ asset('img/pk-situations/photo.svg') }}" alt="" width="40" height="40">
            <span class="pk-answer__label">Prendre une photo</span>
        </label>
        <label class="pk-answer">
            <input type="file" class="pk-guided__file" accept="image/jpeg,image/png,image/webp" multiple data-pk-photo-pick>
            <img src="{{ asset('img/pk-situations/photo.svg') }}" alt="" width="40" height="40">
            <span class="pk-answer__label">Choisir une photo</span>
        </label>
        <button type="button" class="pk-answer pk-answer--unknown" data-pk-skip>
            <span class="pk-answer__label">Passer</span>
        </button>
    </div>
    <ul class="pk-photo__list" data-pk-photo-list></ul>
    <p class="pk-photo__msg" role="status" data-pk-photo-msg></p>
    <div class="pk-photo__fallback" data-pk-photo-fallback>
        <label for="pkPhotoSubmit">Ajouter jusqu’à 2 photos (JPEG, PNG ou WebP, 5 Mo au plus)</label>
        <input type="file" id="pkPhotoSubmit" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple data-pk-photo-submit>
    </div>
</fieldset>

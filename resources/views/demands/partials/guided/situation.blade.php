{{-- Écran 1 : le besoin choisi, avec son pictogramme et ce qui va suivre. --}}
<fieldset class="pk-guided__step pk-guided__step--intro" data-pk-step="{{ $n }}">
    <legend class="pk-guided__legend" tabindex="-1">{{ $pkSituation['label'] }}</legend>
    <button type="button" class="pk-listen" data-pk-listen hidden>Écouter</button>
    <div class="pk-guided__intro">
        <img src="{{ $pkSituation['picto_url'] }}" alt="" width="72" height="72">
        <p>Répondez en touchant les images. Vous pouvez toujours choisir « Je ne sais pas ».</p>
        <p class="pk-guided__hint">Ensuite : une photo si vous voulez, le lieu, le moment et le prix.</p>
    </div>
</fieldset>

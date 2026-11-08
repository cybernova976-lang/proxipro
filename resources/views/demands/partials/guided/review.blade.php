{{-- Vérification : aperçu du texte écrit à partir des réponses, modification facultative, un seul bouton plein. --}}
<fieldset class="pk-guided__step pk-guided__step--review" data-pk-step="{{ $n }}" data-pk-review>
    <legend class="pk-guided__legend" tabindex="-1">Vérifiez votre demande</legend>
    <button type="button" class="pk-listen" data-pk-listen hidden>Écouter</button>

    <section class="pk-guided__preview" data-pk-preview aria-labelledby="pkPreviewTitle">
        <p class="pk-guided__preview-kicker" id="pkPreviewTitle">Ce que verront les prestataires</p>
        <p class="pk-guided__preview-empty" data-pk-preview-empty>Le texte sera écrit à partir de vos réponses.</p>
        <div class="pk-guided__preview-body" data-pk-preview-body hidden>
            <img class="pk-guided__preview-picto" src="{{ $pkSituation['picto_url'] }}" alt="" width="40" height="40">
            <div>
                <p class="pk-guided__preview-title" data-pk-preview-title></p>
                <p class="pk-guided__preview-text" data-pk-preview-text></p>
                <p class="pk-guided__hint">Texte écrit à partir de vos réponses.</p>
            </div>
        </div>
        <button type="button" class="pk-guided__link" data-pk-edit-text hidden>Modifier le texte</button>
        <div class="pk-guided__field" data-pk-override hidden>
            <label for="pkDescriptionOverride">Votre texte</label>
            <textarea id="pkDescriptionOverride" name="description_override" maxlength="2000" rows="6">{{ old('description_override') }}</textarea>
        </div>
    </section>

    <details class="pk-guided__details">
        <summary>Ajouter un détail (optionnel)</summary>
        <label for="pkDetail" class="pk-guided__hint">Pour écrire sans clavier, appuyez sur le micro de votre clavier.</label>
        <textarea id="pkDetail" name="detail" maxlength="500" rows="3">{{ old('detail') }}</textarea>
    </details>

    <label class="pk-guided__confirm">
        <input type="checkbox" name="publication_confirmed" value="1" @checked(old('publication_confirmed'))>
        <span>Ma demande sera visible pendant 30 jours. <a href="{{ route('legal.platform-rules') }}" target="_blank" rel="noopener">Les règles</a></span>
    </label>

    <div class="pk-guided__bar pk-guided__bar--publish">
        <button type="submit" class="pk-btn pk-guided__publish" data-pk-publish>{{ $pkIsGuest ? 'Me connecter pour publier' : 'Publier ma demande' }}</button>
    </div>
</fieldset>

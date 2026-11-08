{{-- Aide à la publication par WhatsApp (G8) : seulement si le drapeau, un numéro valide et des horaires sont configurés. --}}
@if($pkAssist !== null)
    <aside class="pk-guided__assist" aria-label="Aide à la publication">
        <a class="pk-btn-outline" href="{{ $pkAssist['url'] }}" target="_blank" rel="noopener">Publier avec de l’aide</a>
        <p class="pk-guided__hint">Réponse {{ $pkAssist['hours'] }}</p>
    </aside>
@endif

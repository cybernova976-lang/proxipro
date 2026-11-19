@php
    // Réponse du client : ce qu'il a répondu en ligne, ou le lien à lui transmettre.
    $pkResponseLabel = $quote->onlineResponseLabel();
    $pkResponseUrl = $quote->isAwaitingResponse() ? $quote->responseUrl() : null;
@endphp
@if($pkResponseLabel || $pkResponseUrl)
    @push('head')
        <link rel="stylesheet" href="{{ asset('css/pro-premium.css') }}?v={{ @filemtime(public_path('css/pro-premium.css')) ?: 1 }}">
    @endpush
    <section class="pk-templates mt-3" aria-labelledby="pkQuoteResponseTitle" data-quote-response>
        <div class="pk-templates__head">
            <h2 id="pkQuoteResponseTitle">Réponse du client</h2>
        </div>
        @if($pkResponseLabel)
            <p><i class="fas {{ $quote->status === 'accepted' ? 'fa-check-circle' : 'fa-times-circle' }}" aria-hidden="true"></i> {{ $pkResponseLabel }}. Cette mention figure aussi sur le PDF du devis.</p>
        @else
            <p>Votre client peut accepter ce devis (« Bon pour accord ») ou le refuser en ligne, sans créer de compte. Le lien est joint à l’e-mail et au message d’envoi ; vous pouvez aussi le lui transmettre vous-même.</p>
            <div class="pk-templates__actions mt-2">
                <label for="pkQuoteResponseUrl" class="visually-hidden">Lien de réponse du client</label>
                <input type="text" id="pkQuoteResponseUrl" class="form-control pk-templates__field" value="{{ $pkResponseUrl }}" readonly>
                <button type="button" class="pk-templates__use" data-pk-copy="#pkQuoteResponseUrl" data-pk-copied="Lien copié">Copier le lien</button>
            </div>
        @endif
    </section>
    <script src="{{ asset('js/copy-field.js') }}?v={{ @filemtime(public_path('js/copy-field.js')) ?: 1 }}" defer></script>
@endif

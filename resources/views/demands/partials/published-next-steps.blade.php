{{-- Écran « Demande publiée » : une seule phrase mesurée sur les prestataires prévenus, un seul bouton plein. --}}
{{-- notified_providers_count : null = pas de mesure (aucune phrase), 0 = personne d'inscrit, N = prestataires réellement prévenus. --}}
@php
    $pkDuplicate = (bool) session('demand_duplicate');
    $pkNotified = $ad->notified_providers_count;
    $pkAdTerritory = app(\App\Support\TerritoryResolver::class)->forAd($ad);
    $pkZone = $pkAdTerritory?->withPreposition();
    $pkAdUrl = route('ads.show', $ad);
@endphp
<section class="pk-published" data-pk-published aria-labelledby="pkPublishedTitle">
    @if($pkDuplicate)
        <h2 class="pk-published__title" id="pkPublishedTitle">Vous avez déjà publié cette demande aujourd’hui.</h2>
        <div class="pk-published__actions">
            <a class="pk-btn" href="{{ $pkAdUrl }}">Voir ma demande</a>
        </div>
    @else
        <h2 class="pk-published__title" id="pkPublishedTitle">Demande publiée</h2>
        @if($pkNotified === 0)
            <p class="pk-published__reach">Aucun prestataire de ce métier n’est encore inscrit {{ $pkZone ?? 'dans votre zone' }}. Partagez votre demande.</p>
        @elseif(is_int($pkNotified) && $pkNotified > 0)
            <p class="pk-published__reach">Envoyée à {{ $pkNotified }} prestataire{{ $pkNotified > 1 ? 's' : '' }}{{ $pkZone ? ' '.$pkZone : '' }}.</p>
        @endif
        <div class="pk-published__actions">
            <a class="pk-btn" href="{{ $pkAdUrl }}">Voir ma demande</a>
            <button type="button" class="pk-btn-outline" data-pk-share data-url="{{ $pkAdUrl }}" data-text="{{ $ad->title }}">Envoyer à un artisan que je connais</button>
            <button type="button" class="pk-btn-outline" data-push-enable data-pk-push-offer hidden>Activer les notifications pour recevoir les réponses</button>
        </div>
        @include('demands.partials.urgent-offer', ['ad' => $ad])
    @endif
</section>
@push('scripts')
<script src="{{ asset('js/demand-guided.js') }}?v={{ @filemtime(public_path('js/demand-guided.js')) ?: 1 }}" defer></script>
@endpush

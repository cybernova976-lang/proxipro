{{--
    Mise en avant proposée après la publication d'une demande.
    Pour une demande, la seule mise en avant qui a un effet réel est l'option
    « Urgent » : la demande passe avant les autres demandes du même métier dans
    l'accueil des prestataires (FeedController, tri par is_urgent) et porte la
    mention « Urgent · option payante ». Rien d'autre n'est promis.
    Elle n'est pas proposée quand elle n'apporterait rien : option déjà en cours,
    ou demande déjà signalée « Urgent » gratuitement au dépôt (Ad::canBuyUrgentOption).
--}}
@php
    $pkUrgent = \App\Support\AdPromotionCatalog::urgent();
    $pkUrgentIsPro = (bool) auth()->user()?->hasActiveProSubscription();
    $pkUrgentEuros = \App\Support\AdPromotionCatalog::discountedCents((float) $pkUrgent['price_euros'], $pkUrgentIsPro) / 100;
@endphp
@if($ad->canBuyUrgentOption())
    <aside class="pk-urgent-offer" aria-labelledby="pkUrgentOfferTitle">
        <h3 id="pkUrgentOfferTitle"><i class="fas fa-bolt" aria-hidden="true"></i> Besoin de réponses plus vite ?</h3>
        <p>Avec l’option <strong>Urgent</strong>, {{ $pkUrgent['duration_days'] !== null ? 'pendant '.$pkUrgent['duration_days'].' jours' : 'pendant toute la durée de votre demande' }} :</p>
        <ul>
            <li>votre demande passe avant les autres demandes du même métier dans l’accueil des prestataires ;</li>
            <li>elle porte la mention « Urgent · option payante »{{ $pkUrgent['duration_days'] === null ? ' et apparaît dans le filtre « Urgentes » de la liste des annonces' : '' }}.</li>
        </ul>
        <p class="pk-urgent-offer__price">{{ number_format($pkUrgentEuros, 2, ',', ' ') }} €</p>
        <a class="pk-btn-outline" href="{{ route('boost.after-creation', $ad) }}">Choisir l’option Urgent</a>
    </aside>
@endif

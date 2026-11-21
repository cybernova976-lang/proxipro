@php
    // Bloc du propriétaire sur la fiche de son annonce (nouvelles règles de mise
    // en avant) : ce qui est en cours, et un seul lien vers la page d'achat.
    $pkPromoLines = [];
    if ($ad->isCurrentlyBoosted() && $ad->boost_end) {
        $pkPromoLines[] = 'À la une jusqu’au '.$ad->boost_end->format('d/m/Y');
    }
    if ($ad->service_type === 'offre') {
        if ($ad->hasActiveBump()) {
            $pkPromoLines[] = 'Remontée automatique jusqu’au '.$ad->bump_until->format('d/m/Y');
        }
    } elseif ($ad->hasPromotedUrgency()) {
        $pkPromoLines[] = 'Option Urgent jusqu’au '.$ad->urgent_until->format('d/m/Y');
    }
    // « À la une » se prolonge et se vend pour toute annonce : le lien reste toujours proposé.
    $pkPromoCanBuy = true;
@endphp
@foreach($pkPromoLines as $pkPromoLine)
    <p class="small text-muted mt-2 mb-0" data-promotion-state><i class="fas fa-check me-1" aria-hidden="true"></i>{{ $pkPromoLine }}</p>
@endforeach
@if($pkPromoCanBuy)
    <a href="{{ route('boost.show', $ad) }}" class="btn btn-contact btn-contact-secondary mt-2">
        <i class="fas fa-arrow-up me-2" aria-hidden="true"></i>{{ $ad->service_type === 'offre' ? 'Mettre en avant cette offre' : 'Mettre en avant cette demande' }}
    </a>
@endif

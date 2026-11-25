@php
    // Accès à paiement unique réellement en vente : prix lus dans le catalogue, jamais écrits ici.
    $pkOnceSentence = \App\Support\PlatformFeatures::proSubscriptionsEnabled()
        ? \App\Support\ProviderSubscriptionPlans::oneTimeSentence()
        : null;
    // Membre en accès à paiement unique : rien à résilier, rien ne se renouvelle.
    $pkOnceAccess = isset($subscription) && $subscription instanceof \App\Models\ProSubscription
        && $subscription->isOneTimeAccess() && ($subscription->isActive() || $subscription->isPaidAhead());
    $pkOnceUntil = null;
    if ($pkOnceAccess && $subscription->user) {
        // Fin de toute la couverture, accès déjà payés d'avance compris.
        $pkOnceUntil = app(\App\Services\ProviderSubscriptionService::class)->oneTimeAccessEnd($subscription->user);
    }
    $pkOnceUntil = $pkOnceUntil ?? ($pkOnceAccess ? $subscription->ends_at : null);
@endphp
<div class="subscription-transparency" style="margin:18px 0;padding:18px 20px;border:1px solid #e0e7ff;border-radius:14px;background:#f8fafc;color:#334155;line-height:1.6;">
    @if($pkOnceAccess)
        <strong>Aucun renouvellement : l’accès s’arrête seul{{ $pkOnceUntil ? ' le '.$pkOnceUntil->format('d/m/Y') : ' à sa date de fin' }}.</strong>
        <p style="margin:6px 0 0;font-size:.9rem;">Vous avez réglé Premium en une seule fois par carte : aucun prélèvement ne suivra et vous n’avez aucune démarche à faire pour l’arrêter. Vos paiements et leurs justificatifs sont sur la page <a href="{{ route('billing.index') }}">Facturation</a>.</p>
        <p style="margin:6px 0 0;font-size:.85rem;">Pour continuer après cette date, un nouveau paiement unique prolonge votre accès à partir de sa fin. Vous disposez d’un délai de rétractation de 14 jours après l’achat ; si vous vous rétractez après l’ouverture de l’accès, la part déjà utilisée reste due.</p>
        <p style="margin:6px 0 0;font-size:.85rem;">La visibilité Premium ne garantit ni contact, ni mission, ni revenu. Le badge Premium indique un accès Premium en cours ; le contrôle d’identité reste distinct.</p>
    @else
        <strong>Vous pouvez résilier à tout moment.</strong>
        <p style="margin:6px 0 0;font-size:.9rem;">Depuis la page <a href="{{ route('billing.index') }}">Facturation</a>, arrêtez le renouvellement en ligne. Vous conservez vos avantages jusqu’à la fin de la période payée, puis votre compte reste gratuit.</p>
        <p style="margin:6px 0 0;font-size:.85rem;">L’abonnement est prélevé chaque mois, sans engagement de durée. L’arrêt du renouvellement ne rembourse pas automatiquement la période en cours. Le montant est confirmé avant paiement.</p>
        @if($pkOnceSentence)
            <p style="margin:6px 0 0;font-size:.85rem;">Sans abonnement, Premium se règle aussi en une fois par carte : {{ $pkOnceSentence }}, avec les mêmes avantages. Ce paiement unique n’est jamais renouvelé ni prélevé de nouveau : l’accès s’arrête seul à la fin de la période. Il ne se cumule pas avec un abonnement en cours.</p>
        @endif
        <p style="margin:6px 0 0;font-size:.85rem;">La visibilité Premium ne garantit ni contact, ni mission, ni revenu. Le badge Premium indique un abonnement ; le contrôle d’identité reste distinct.</p>
    @endif
</div>

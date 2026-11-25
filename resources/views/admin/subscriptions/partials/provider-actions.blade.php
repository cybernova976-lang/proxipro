@php
    // Actions de l'administrateur sur un abonnement Premium.
    // $compact : boutons en icônes (liste) ; sinon boutons avec libellé (fiche du membre).
    $compact = $compact ?? false;
    $actionLate = $subscription->isPaymentLate();
    $actionStripe = $subscription->hasOpenBilling();
    $actionEnded = in_array($subscription->status, ['cancelled', 'expired'], true);
    // Accès réglé en une fois : il n'y a ni prélèvement à suspendre, ni abonnement Stripe à couper.
    $actionOneTime = $subscription->isOneTimeAccess();
    // Accès lui-même payé d'avance (il n'a pas encore commencé), ou accès en cours suivi d'un accès déjà payé.
    $actionQueued = $actionOneTime && $subscription->isPaidAhead();
    $actionNext = $actionOneTime && ! $actionQueued && $subscription->user
        ? $subscription->user->proSubscriptions()->paidAhead()->whereKeyNot($subscription->id)->orderBy('starts_at')->get()
        : collect();
    $actionNextNote = $actionNext->isNotEmpty()
        ? ' Attention : un accès déjà payé (du '.$actionNext->first()->starts_at->format('d/m').' au '.$actionNext->max('ends_at')->format('d/m').') existe en file : il commencera quand même à sa date, sauf si vous l’annulez aussi.'
        : '';
    $actionDays = 7;
    $actions = [];

    if ($actionLate) {
        $actions[] = ['route' => 'admin.subscriptions.provider.extend-grace', 'label' => 'Prolonger le délai', 'icon' => 'fa-hourglass-half', 'color' => 'primary', 'days' => true,
            'confirm' => $compact ? 'Accorder '.$actionDays.' jours de plus à ce membre pour régler son paiement ?' : null];
        if ($actionStripe) {
            $actions[] = ['route' => 'admin.subscriptions.provider.retry-payment', 'label' => 'Relancer le prélèvement', 'icon' => 'fa-rotate-right', 'color' => 'success',
                'confirm' => 'Présenter de nouveau la facture au moyen de paiement enregistré par ce membre ?'];
        }
    } elseif ($subscription->status === 'active') {
        $actions[] = ['route' => 'admin.subscriptions.provider.suspend', 'label' => 'Suspendre', 'icon' => 'fa-pause', 'color' => 'warning',
            'confirm' => match (true) {
                $actionQueued => 'Suspendre cet accès payé d’avance (du '.$subscription->starts_at->format('d/m').' au '.$subscription->ends_at?->format('d/m').') ? Il ne commencera pas à sa date tant qu’il n’est pas réactivé ; ses dates ne changent pas.',
                $actionOneTime => 'Suspendre cet accès à paiement unique ? Le membre perd ses avantages jusqu’à sa réactivation ; la date de fin ne change pas.'.$actionNextNote,
                default => 'Suspendre cet abonnement et les prochains prélèvements ?',
            }];
        if ($actionStripe && $subscription->auto_renew) {
            $actions[] = ['route' => 'admin.subscriptions.provider.end-at-period-end', 'label' => 'Résilier à l’échéance', 'icon' => 'fa-calendar-xmark', 'color' => 'secondary',
                'confirm' => 'Arrêter cet abonnement à la fin de la période payée ? Le membre garde ses avantages jusque-là.'];
        } elseif ($actionStripe && $subscription->isActive()) {
            $actions[] = ['route' => 'admin.subscriptions.provider.resume-renewal', 'label' => 'Rétablir le renouvellement', 'icon' => 'fa-rotate', 'color' => 'success',
                'confirm' => 'Rétablir le renouvellement automatique ? Le membre sera de nouveau prélevé à l’échéance.'];
        }
    } elseif ($subscription->status === 'pending') {
        $actions[] = ['route' => 'admin.subscriptions.provider.resume', 'label' => 'Réactiver', 'icon' => 'fa-play', 'color' => 'success', 'confirm' => null];
    }

    if (! $actionEnded) {
        $actions[] = ['route' => 'admin.subscriptions.provider.cancel', 'label' => 'Annuler immédiatement', 'icon' => 'fa-ban', 'color' => 'danger',
            'confirm' => match (true) {
                $actionLate => 'Annuler immédiatement cet abonnement ? La facture impayée sera annulée chez Stripe et le compte repassera en gratuit.',
                $actionQueued => 'Annuler cet accès payé d’avance (du '.$subscription->starts_at->format('d/m').' au '.$subscription->ends_at?->format('d/m').') ? Il ne commencera pas. Son paiement n’est pas remboursé par cette action : un remboursement se fait depuis Stripe.',
                $actionOneTime => 'Annuler immédiatement cet accès à paiement unique ? Le membre perd ses avantages tout de suite. Son paiement n’est pas remboursé par cette action : un remboursement se fait depuis Stripe.'.$actionNextNote,
                default => 'Annuler immédiatement cet abonnement ? Cette action coupe aussi Stripe.',
            }];
    }
@endphp
@foreach($actions as $action)
    @php $actionTitle = $action['label'].(! empty($action['days']) && $compact ? ' de '.$actionDays.' jours' : ''); @endphp
    <form method="POST" action="{{ route($action['route'], $subscription) }}" class="{{ ! empty($action['days']) && ! $compact ? 'd-flex w-100' : 'd-inline-flex' }} align-items-center gap-1"
          @if($action['confirm']) onsubmit="return confirm({{ \Illuminate\Support\Js::from($action['confirm']) }});" @endif>
        @csrf
        @if(! empty($action['days']))
            @if($compact)
                <input type="hidden" name="days" value="{{ $actionDays }}">
            @else
                <label class="visually-hidden" for="graceDays{{ $subscription->id }}">Jours accordés en plus</label>
                <input type="number" name="days" id="graceDays{{ $subscription->id }}" class="form-control form-control-sm w-25"
                       min="1" max="{{ \App\Services\ProviderSubscriptionService::MAX_GRACE_EXTENSION_DAYS }}" value="{{ $actionDays }}" required>
                <span class="small text-muted">jours</span>
            @endif
        @endif
        @if($compact)
            <button class="btn btn-sm btn-outline-{{ $action['color'] }}" title="{{ $actionTitle }}" aria-label="{{ $actionTitle }}"><i class="fas {{ $action['icon'] }}"></i></button>
        @else
            <button class="btn btn-sm btn-outline-{{ $action['color'] }}"><i class="fas {{ $action['icon'] }} me-1"></i>{{ $action['label'] }}</button>
        @endif
    </form>
@endforeach

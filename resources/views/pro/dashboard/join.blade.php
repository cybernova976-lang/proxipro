{{-- Membre sans activité de prestataire : le parcours pour en devenir un, avec ses vraies étapes. --}}
@php
    $pkNextIndex = collect($joinSteps)->search(fn ($step) => ! $step['done']);
    $pkJoinCta = match ($pkNextIndex) {
        0 => 'Compléter mon profil',
        1 => 'Faire vérifier mon profil',
        default => 'Choisir mon métier',
    };
@endphp
<div class="pk-join">
    <header class="pk-join__head">
        <p class="pk-dash__eyebrow">Espace Pro</p>
        <h1>Proposez vos services sur Prokejem</h1>
        <p>Recevez les demandes des clients qui cherchent votre métier, répondez-leur et suivez vos missions au même endroit.</p>
    </header>

    <section class="pk-join__card" aria-labelledby="pkJoinStepsTitle">
        <h2 id="pkJoinStepsTitle" class="pk-dash__title">Trois étapes pour devenir prestataire</h2>
        <ol class="pk-join__steps">
            @foreach($joinSteps as $index => $step)
                @php($pkState = $step['done'] ? 'done' : ($index === $pkNextIndex ? 'current' : 'todo'))
                <li class="pk-join__step pk-join__step--{{ $pkState }}" data-join-step="{{ $index + 1 }}">
                    <span class="pk-join__marker" aria-hidden="true">
                        @if($step['done'])
                            <i class="fas fa-check"></i>
                        @else
                            {{ $index + 1 }}
                        @endif
                    </span>
                    <span>
                        <span class="pk-join__label">
                            {{ $step['label'] }}
                            <span class="visually-hidden">{{ $pkState === 'done' ? '(fait)' : ($pkState === 'current' ? '(étape suivante)' : '(à venir)') }}</span>
                        </span>
                        <span class="pk-join__detail">{{ $step['detail'] }}</span>
                    </span>
                </li>
            @endforeach
        </ol>
        <div class="pk-join__action">
            <a href="{{ route('service-provider.start') }}" class="btn btn-pro-primary">{{ $pkJoinCta }}</a>
            <a href="{{ route('pricing.index') }}" class="pk-join__secondary">L’abonnement Premium est facultatif : voir les tarifs</a>
        </div>
    </section>

    <section class="pk-join__card" aria-labelledby="pkJoinGainsTitle">
        <h2 id="pkJoinGainsTitle" class="pk-dash__title">Ce que l’espace Pro vous apporte</h2>
        <ul class="pk-join__gains">
            <li><i class="fas fa-magnifying-glass" aria-hidden="true"></i><span><strong>Les demandes de votre métier</strong> près de chez vous, triées pour vous.</span></li>
            <li><i class="fas fa-comments" aria-hidden="true"></i><span><strong>Messagerie et suivi des missions</strong>, de la proposition au paiement.</span></li>
            <li><i class="fas fa-file-invoice" aria-hidden="true"></i><span><strong>{{ \App\Services\CommercialDocumentAllowance::FREE['quote'] }} devis et {{ \App\Services\CommercialDocumentAllowance::FREE['invoice'] }} factures offerts</strong>, sans justificatif, dès maintenant : <a href="{{ route('pro.quotes') }}">créer mes documents</a>.</span></li>
            <li><i class="fas fa-users" aria-hidden="true"></i><span><strong>Un carnet de clients</strong> pour garder l’historique de chaque échange.</span></li>
        </ul>
    </section>
</div>

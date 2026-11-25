{{-- Accès Premium à paiement unique : un paiement par carte, sans abonnement.
     Deux durées, boutons en contour : le bouton plein de la page reste celui de la formule mensuelle.
     Avant le paiement, une case obligatoire recueille la demande d'ouverture immédiate et
     l'information sur le délai de rétractation de 14 jours (vérifiée aussi par BillingController).
     Variables : $oneTimeOffers (durée, prix, équivalent par mois), $oneTimeExtendsFrom (date ou null),
     $oneTimeOrigin (billing ou subscription), $oneTimeFramed (cadre propre, page Facturation). --}}
@if(! empty($oneTimeOffers))
<section class="pk-once{{ ! empty($oneTimeFramed) ? ' pk-once--card' : '' }}" aria-labelledby="pk-once-title">
    <h3 class="pk-once__title" id="pk-once-title">{{ ! empty($oneTimeExtendsFrom) ? 'Prolonger sans abonnement : paiement unique' : 'Sans abonnement : paiement unique' }}</h3>
    <p class="pk-once__lead">Un seul paiement par carte. Aucun prélèvement ensuite : l’accès Premium s’arrête seul à la fin de la période.</p>
    @if(! empty($oneTimeExtendsFrom))
        <p class="pk-once__lead">La nouvelle période commencera le {{ $oneTimeExtendsFrom->format('d/m/Y') }}, à la fin de votre accès en cours.</p>
    @endif
    <form method="POST" action="{{ route('billing.one-time') }}" class="pk-once__form">
        @csrf
        <input type="hidden" name="from" value="{{ $oneTimeOrigin ?? 'billing' }}">
        <div class="pk-once__consent{{ $errors->has('immediate_start') ? ' pk-once__consent--error' : '' }}">
            <input type="checkbox" name="immediate_start" value="1" id="pk-once-consent-{{ $oneTimeOrigin ?? 'billing' }}" required
                   @if($errors->has('immediate_start')) aria-invalid="true" aria-describedby="pk-once-consent-error" @endif>
            <label for="pk-once-consent-{{ $oneTimeOrigin ?? 'billing' }}">Je demande l’ouverture immédiate de mon accès Premium. Je sais que je dispose d’un délai de rétractation de 14 jours et que, si je me rétracte après l’ouverture, la part déjà utilisée reste due.</label>
        </div>
        @error('immediate_start')
            <p class="pk-once__error" id="pk-once-consent-error" role="alert">{{ $message }}</p>
        @enderror
        <ul class="pk-once__list">
            @foreach($oneTimeOffers as $pkOnceKey => $pkOnce)
                <li class="pk-once__item">
                    <p class="pk-once__duration">{{ $pkOnce['duration'] }}</p>
                    <p class="pk-once__price">{{ $pkOnce['price'] }}</p>
                    <p class="pk-once__per-month">soit {{ $pkOnce['per_month'] }} par mois</p>
                    <button type="submit" name="plan" value="{{ $pkOnceKey }}" class="pk-once__btn">Prendre {{ $pkOnce['duration'] }}</button>
                </li>
            @endforeach
        </ul>
    </form>
    <p class="pk-once__note">Mêmes avantages que l’abonnement Premium. Paiement sécurisé par carte bancaire ; le montant est confirmé avant paiement. <a href="{{ route('legal.pro-terms') }}#acces-paiement-unique">Conditions et rétractation</a></p>
</section>
@endif

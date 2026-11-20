{{-- Aucun abonnement en cours : les formules réellement en vente, avec leur prix. --}}
@if(empty($plans))
    <p class="pk-billing__empty">L’abonnement Premium n’est pas ouvert à la souscription pour le moment. Aucun paiement ne vous est demandé.</p>
@else
    <h3 class="pk-billing__subtitle">{{ count($plans) > 1 ? 'Abonnements disponibles' : 'Abonnement disponible' }}</h3>
    @foreach($plans as $pkPlanKey => $pkPlan)
        @php
            $pkFeatures = array_values((array) ($pkPlan['features'] ?? []));
        @endphp
        <article class="pk-billing-card pk-billing-card--offer">
            <header class="pk-billing-card__head">
                <div>
                    <h3>Premium · {{ $pkPlan['label'] }}</h3>
                    <p class="pk-billing-card__price">{{ $pkPlan['price'] }} {{ $pkPlan['period'] }} · sans engagement de durée</p>
                </div>
            </header>
            @if($pkFeatures)
                <ul class="pk-billing-card__features">
                    @foreach(array_slice($pkFeatures, 0, 4) as $pkFeature)
                        <li>{{ $pkFeature }}</li>
                    @endforeach
                </ul>
                @if(count($pkFeatures) > 4)
                    <details class="pk-billing-card__more">
                        <summary>Voir tous les avantages</summary>
                        <ul class="pk-billing-card__features">
                            @foreach(array_slice($pkFeatures, 4) as $pkFeature)
                                <li>{{ $pkFeature }}</li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            @endif
            <div class="pk-billing-card__actions">
                <form method="POST" action="{{ route('pro.onboarding.subscribe') }}">
                    @csrf
                    <input type="hidden" name="plan" value="{{ $pkPlanKey }}">
                    <button type="submit" class="pk-billing-btn pk-billing-btn--primary">Choisir cette formule</button>
                </form>
                <a class="pk-billing-link" href="{{ route('pro.subscription') }}">Comparer avec le compte gratuit</a>
            </div>
            <p class="pk-billing-card__note">Paiement sécurisé par carte bancaire. Renouvellement chaque mois, résiliable à tout moment depuis cette page.</p>
        </article>
    @endforeach
@endif

@php
    // Accès Premium à paiement unique : seuls la mise en vente, le prix et la description se règlent ici.
    // La durée et la nature de la formule viennent de config/provider_subscriptions.php et ne se perdent jamais.
    $oneTimePlans = \App\Support\ProviderSubscriptionPlans::oneTime();
@endphp
@if($oneTimePlans)
    <div class="border-top mt-4 pt-4" id="oneTimePlans">
        <h6 class="fw-bold mb-1"><i class="fas fa-hourglass-half text-primary me-2"></i>Accès à paiement unique (sans abonnement)</h6>
        <p class="text-muted small mb-3">Un seul paiement par carte, aucun prélèvement ensuite : l’accès ouvre les mêmes avantages que la formule mensuelle et s’arrête seul à la fin de sa durée. Un nouveau prix s’applique aux prochains achats, jamais aux accès déjà payés.</p>
        <div class="row g-4">
            @foreach($oneTimePlans as $planKey => $oneTimePlan)
                <div class="col-lg-6">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">{{ \App\Support\ProviderSubscriptionPlans::durationLabel($planKey) }} · paiement unique</h6>
                            <label class="form-check-label small">
                                <input type="checkbox" class="form-check-input me-1" name="plans[{{ $planKey }}][enabled]" value="1" {{ ! empty($oneTimePlan['enabled']) ? 'checked' : '' }}>
                                En vente
                            </label>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold" for="oneTimeAmount-{{ $planKey }}">Prix en euros, payé en une fois</label>
                                <input type="number" class="form-control" id="oneTimeAmount-{{ $planKey }}" name="plans[{{ $planKey }}][amount]" step="0.01" min="0.50" value="{{ $oneTimePlan['amount'] ?? 0 }}" required>
                                <small class="text-muted">Soit {{ \App\Support\ProviderSubscriptionPlans::euros(\App\Support\ProviderSubscriptionPlans::monthlyEquivalent($planKey)) }} par mois au prix enregistré.</small>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small fw-semibold" for="oneTimeDescription-{{ $planKey }}">Description sur la page de paiement</label>
                                <input type="text" class="form-control" id="oneTimeDescription-{{ $planKey }}" name="plans[{{ $planKey }}][description]" value="{{ $oneTimePlan['description'] ?? '' }}" maxlength="255">
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

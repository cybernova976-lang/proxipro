@php
    $premiumActive = $user->hasActiveProSubscription();
@endphp
<div class="card border-0 shadow-sm h-100" id="adminUserPremium">
    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fas fa-crown me-2 text-warning"></i>Abonnement Premium</h6>
        @if($premiumActive)
            <span class="badge bg-success">Premium actif</span>
        @else
            <span class="badge bg-secondary">Pas de Premium</span>
        @endif
    </div>
    <div class="card-body">
        @if($providerSubscription)
            <ul class="list-unstyled small mb-3">
                <li class="mb-1"><span class="text-muted">Formule :</span> {{ $providerSubscription->getPlanLabel() }}</li>
                <li class="mb-1">
                    <span class="text-muted">État :</span>
                    @include('admin.subscriptions.partials.provider-state', ['subscription' => $providerSubscription])
                </li>
                <li class="mb-1"><span class="text-muted">Période :</span> {{ optional($providerSubscription->starts_at)->format('d/m/Y') ?: '—' }} → {{ optional($providerSubscription->ends_at)->format('d/m/Y') ?: '—' }}</li>
                <li><span class="text-muted">Origine :</span> {{ $providerSubscription->stripe_subscription_id ? 'Paiement Stripe' : 'Accès accordé par un administrateur' }}</li>
            </ul>

            <div class="d-flex flex-wrap gap-2 mb-3">
                @include('admin.subscriptions.partials.provider-actions', ['subscription' => $providerSubscription, 'compact' => false])
            </div>
        @else
            <p class="text-muted small">Aucun abonnement Premium enregistré pour ce compte.</p>
        @endif

        @if(! $user->isServiceProvider())
            <div class="alert alert-light border small mb-0">
                <i class="fas fa-circle-info me-1"></i>
                Le Premium est réservé aux prestataires. Ce compte n’a pas encore de profil prestataire : l’utilisateur doit d’abord l’activer depuis son espace.
            </div>
        @elseif(! $premiumActive)
            <form method="POST" action="{{ route('admin.subscriptions.provider.grant') }}" class="border-top pt-3">
                @csrf
                <input type="hidden" name="email" value="{{ $user->email }}">
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label small fw-semibold" for="premiumPlan">Formule</label>
                        <select name="plan" id="premiumPlan" class="form-select form-select-sm">
                            <option value="monthly">Mensuelle</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold" for="premiumDuration">Durée (jours)</label>
                        <input type="number" name="duration" id="premiumDuration" min="1" max="730" value="30" class="form-control form-control-sm" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-warning w-100 mt-3">
                    <i class="fas fa-gift me-2"></i>Accorder le Premium sans paiement
                </button>
                <p class="text-muted small mt-2 mb-0">Aucun prélèvement Stripe n’est créé. L’accès s’arrête seul à la fin de la durée.</p>
            </form>
        @endif
    </div>
</div>

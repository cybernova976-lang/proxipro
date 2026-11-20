@php
    // Compteurs cliquables : chacun filtre la liste des abonnements Premium.
    $providerCounters = [
        ['late', 'Paiement en retard', $providerStats['late'], 'danger', 'fa-triangle-exclamation'],
        ['active', 'Actifs', $providerStats['active'], 'success', 'fa-circle-check'],
        ['pending', 'En attente / suspendus', $providerStats['pending'], 'warning', 'fa-pause-circle'],
        ['ending', 'Fin programmée', $providerStats['ending'], 'info', 'fa-calendar-xmark'],
        ['stripe', 'Gérés par Stripe', $providerStats['stripe'], 'primary', 'fa-credit-card'],
    ];
    $providerStates = ['' => 'Tous les états', 'late' => 'Paiement en retard', 'active' => 'Actifs', 'pending' => 'En attente ou suspendus',
        'ending' => 'Fin programmée', 'stripe' => 'Gérés par Stripe', 'ended' => 'Terminés'];
    $providerFiltered = $providerState !== '' || $providerSearch !== '';
@endphp
<div class="row g-3 mb-4">
    @foreach($providerCounters as [$state, $label, $value, $color, $icon])
        <div class="col-md-6 col-xl">
            <a href="{{ route('admin.subscriptions', ['provider_state' => $state]) }}#providerSubscriptions" class="card shadow-sm h-100 text-decoration-none text-reset {{ $providerState === $state ? 'border border-'.$color : 'border-0' }}">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div><div class="small text-muted">{{ $label }}</div><div class="h4 fw-bold mb-0" data-provider-counter="{{ $state }}">{{ $value }}</div></div>
                    <i class="fas {{ $icon }} fa-2x text-{{ $color }} opacity-75"></i>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="card border-0 shadow-sm mb-4" id="providerSubscriptions">
    <div class="card-header bg-white border-0 pt-4 px-4 d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h5 class="fw-bold mb-1"><i class="fas fa-repeat text-primary me-2"></i>Abonnements prestataires</h5>
            <p class="text-muted mb-0">État local synchronisé avec Stripe. Un accès manuel ne crée aucun prélèvement.</p>
        </div>
        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#manualProviderGrant">
            <i class="fas fa-gift me-1"></i>Accorder un accès manuel
        </button>
    </div>
    <div class="collapse" id="manualProviderGrant">
        <div class="card-body border-top mx-4 px-0">
            <form method="POST" action="{{ route('admin.subscriptions.provider.grant') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-lg-5"><label class="form-label small fw-semibold">Email utilisateur</label><input type="email" name="email" class="form-control" required></div>
                <div class="col-lg-2"><label class="form-label small fw-semibold">Plan</label><select name="plan" class="form-select"><option value="monthly">Mensuel</option></select></div>
                <div class="col-lg-2"><label class="form-label small fw-semibold">Durée (jours)</label><input type="number" name="duration" min="1" max="730" value="30" class="form-control" required></div>
                <div class="col-lg-3"><button class="btn btn-primary w-100"><i class="fas fa-check me-1"></i>Accorder sans paiement</button></div>
            </form>
        </div>
    </div>
    <div class="card-body border-top mx-4 px-0">
        <form method="GET" action="{{ route('admin.subscriptions') }}#providerSubscriptions" class="row g-2 align-items-end">
            <div class="col-lg-5">
                <label class="form-label small fw-semibold" for="providerSearch">Membre</label>
                <input type="search" name="provider_search" id="providerSearch" class="form-control" placeholder="Nom ou adresse e-mail" value="{{ $providerSearch }}">
            </div>
            <div class="col-lg-4">
                <label class="form-label small fw-semibold" for="providerState">État</label>
                <select name="provider_state" id="providerState" class="form-select">
                    @foreach($providerStates as $state => $label)
                        <option value="{{ $state }}" {{ $providerState === $state ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3 d-flex gap-2">
                <button class="btn btn-outline-primary flex-fill"><i class="fas fa-filter me-1"></i>Filtrer</button>
                @if($providerFiltered)
                    <a href="{{ route('admin.subscriptions') }}#providerSubscriptions" class="btn btn-outline-secondary" title="Retirer le filtre" aria-label="Retirer le filtre"><i class="fas fa-times"></i></a>
                @endif
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light"><tr><th class="ps-4">Utilisateur</th><th>Plan</th><th>État</th><th>Période</th><th>Origine</th><th class="text-end pe-4">Actions</th></tr></thead>
            <tbody>
                @forelse($providerSubscriptions as $providerSubscription)
                    <tr>
                        <td class="ps-4"><x-admin.user-link :user="$providerSubscription->user" class="fw-semibold" :fiche="true" /><div class="small text-muted">{{ $providerSubscription->user?->email }}</div></td>
                        <td>{{ $providerSubscription->getPlanLabel() }}<div class="small text-muted">{{ number_format((float) $providerSubscription->amount, 2, ',', ' ') }} €</div></td>
                        <td>@include('admin.subscriptions.partials.provider-state', ['subscription' => $providerSubscription])</td>
                        <td class="small">{{ optional($providerSubscription->starts_at)->format('d/m/Y') ?: '—' }} → {{ optional($providerSubscription->ends_at)->format('d/m/Y') ?: '—' }}</td>
                        <td>
                            @if($providerSubscription->stripe_subscription_id)
                                <span class="badge bg-primary-subtle text-primary border">Stripe</span><div class="small text-muted">{{ \App\Support\StatusLabels::stripeSubscription($providerSubscription->stripe_status) }}</div>
                            @else
                                <span class="badge bg-light text-dark border">Manuel</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-inline-flex gap-1">
                                @include('admin.subscriptions.partials.provider-actions', ['subscription' => $providerSubscription, 'compact' => true])
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ $providerFiltered ? 'Aucun abonnement ne correspond à ce filtre.' : 'Aucun abonnement prestataire enregistré.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($providerSubscriptions->hasPages())<div class="card-footer bg-white">{{ $providerSubscriptions->links('admin.partials.pagination') }}</div>@endif
</div>

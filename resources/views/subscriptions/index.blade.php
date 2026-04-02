@extends('layouts.app')

@section('title', 'Abonnements Premium - ProxiPro')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold mb-3" style="color: #2d3748;">
            <i class="fas fa-crown text-warning me-3"></i>Abonnements Premium
        </h1>
        <p class="lead" style="color: #718096;">Choisissez le plan qui correspond à vos besoins et boostez votre visibilité</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Current Subscription Status -->
    @if($currentPlan)
        <div class="card bg-gradient-success text-white mb-5" style="background: linear-gradient(135deg, #28a745, #20c997);">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h4 class="mb-2">
                            <i class="fas fa-check-circle me-2"></i>
                            Abonnement actif : {{ $plans[$currentPlan]['name'] }}
                        </h4>
                        <p class="mb-0 opacity-75">
                            @if($subscription->onGracePeriod())
                                Annulé - Expire le {{ $subscription->ends_at->format('d/m/Y') }}
                            @else
                                Prochain renouvellement : {{ $subscription->asStripeSubscription()->current_period_end ? date('d/m/Y', $subscription->asStripeSubscription()->current_period_end) : 'N/A' }}
                            @endif
                        </p>
                    </div>
                    <div>
                        @if($subscription->onGracePeriod())
                            <form action="{{ route('subscriptions.resume') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-light">
                                    <i class="fas fa-redo me-2"></i>Reprendre l'abonnement
                                </button>
                            </form>
                        @else
                            <form action="{{ route('subscriptions.cancel') }}" method="POST" class="d-inline" 
                                  onsubmit="return confirm('Êtes-vous sûr de vouloir annuler votre abonnement ?')">
                                @csrf
                                <button type="submit" class="btn btn-outline-light">
                                    <i class="fas fa-times me-2"></i>Annuler
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Pricing Cards -->
    <div class="row g-4 mb-5">
        @foreach($plans as $key => $plan)
            <div class="col-lg-4">
                <div class="card h-100 border-0 shadow-lg {{ $key === 'pro' ? 'border border-warning border-3' : '' }} {{ $currentPlan === $key ? 'border border-primary border-3' : '' }}" 
                     style="border-radius: 20px; overflow: hidden; background: #ffffff;">
                    
                    @if($key === 'pro')
                        <div class="bg-warning text-dark text-center py-2 fw-bold">
                            <i class="fas fa-star me-1"></i> POPULAIRE
                        </div>
                    @endif

                    <div class="card-body p-4 text-dark">
                        <!-- Plan Header -->
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                @if($key === 'basic')
                                    <i class="fas fa-seedling fa-3x text-success"></i>
                                @elseif($key === 'pro')
                                    <i class="fas fa-rocket fa-3x text-warning"></i>
                                @else
                                    <i class="fas fa-building fa-3x text-info"></i>
                                @endif
                            </div>
                            <h3 class="fw-bold mb-1">{{ $plan['name'] }}</h3>
                            <div class="display-5 fw-bold my-3 text-dark">
                                {{ number_format($plan['price'], 2, ',', ' ') }}€
                                <small class="fs-6 fw-normal opacity-75">/mois</small>
                            </div>
                        </div>

                        <!-- Features -->
                        <ul class="list-unstyled mb-4">
                            @foreach($plan['features'] as $feature)
                                <li class="mb-3 d-flex align-items-center">
                                    <span class="badge bg-success rounded-circle me-3" style="width: 24px; height: 24px; line-height: 18px;">
                                        <i class="fas fa-check fa-xs"></i>
                                    </span>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>

                        <!-- Action Button -->
                        @if($currentPlan === $key)
                            <button class="btn btn-primary btn-lg w-100 disabled" disabled>
                                <i class="fas fa-check-circle me-2"></i>Plan actuel
                            </button>
                        @elseif($currentPlan)
                            <button type="button" class="btn {{ $key === 'pro' ? 'btn-warning' : 'btn-outline-primary' }} btn-lg w-100"
                                    data-bs-toggle="modal" data-bs-target="#upgradeModal"
                                    data-plan="{{ $key }}" data-plan-name="{{ $plan['name'] }}" data-price="{{ $plan['price'] }}">
                                <i class="fas fa-arrow-up me-2"></i>
                                @if($plans[$currentPlan]['price'] < $plan['price'])
                                    Passer au {{ $plan['name'] }}
                                @else
                                    Rétrograder vers {{ $plan['name'] }}
                                @endif
                            </button>
                        @else
                            <button type="button" class="btn {{ $key === 'pro' ? 'btn-warning' : 'btn-outline-primary' }} btn-lg w-100"
                                    data-bs-toggle="modal" data-bs-target="#subscribeModal"
                                    data-plan="{{ $key }}" data-plan-name="{{ $plan['name'] }}" data-price="{{ $plan['price'] }}">
                                <i class="fas fa-credit-card me-2"></i>S'abonner
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Features Comparison -->
    <div class="card border-0 shadow-lg mb-5" style="border-radius: 20px; overflow: hidden;">
        <div class="card-header border-0 py-4" style="background: linear-gradient(135deg, #1e293b, #334155);">
            <h4 class="mb-0 text-white">
                <i class="fas fa-table me-2" style="color: #818cf8;"></i>Comparaison des fonctionnalités
            </h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead style="background: #f8fafc;">
                        <tr>
                            <th class="border-0 py-3 ps-4 fw-semibold text-muted">Fonctionnalité</th>
                            <th class="border-0 py-3 text-center fw-semibold text-muted">Basic</th>
                            <th class="border-0 py-3 text-center fw-semibold" style="background: rgba(245,158,11,0.06); color: #b45309;">Pro</th>
                            <th class="border-0 py-3 text-center fw-semibold text-muted">Business</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="py-3 ps-4">Annonces par mois</td>
                            <td class="text-center py-3">10</td>
                            <td class="text-center py-3" style="background: rgba(245,158,11,0.03);">50</td>
                            <td class="text-center py-3"><i class="fas fa-infinity text-success"></i></td>
                        </tr>
                        <tr>
                            <td class="py-3 ps-4">Points bonus/mois</td>
                            <td class="text-center py-3">50</td>
                            <td class="text-center py-3" style="background: rgba(245,158,11,0.03);">150</td>
                            <td class="text-center py-3">500</td>
                        </tr>
                        <tr>
                            <td class="py-3 ps-4">Badge premium</td>
                            <td class="text-center py-3"><i class="fas fa-times text-danger opacity-50"></i></td>
                            <td class="text-center py-3" style="background: rgba(245,158,11,0.03);"><i class="fas fa-check text-success"></i></td>
                            <td class="text-center py-3"><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td class="py-3 ps-4">Support prioritaire</td>
                            <td class="text-center py-3"><i class="fas fa-times text-danger opacity-50"></i></td>
                            <td class="text-center py-3" style="background: rgba(245,158,11,0.03);"><i class="fas fa-check text-success"></i></td>
                            <td class="text-center py-3"><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td class="py-3 ps-4">Statistiques avancées</td>
                            <td class="text-center py-3"><i class="fas fa-times text-danger opacity-50"></i></td>
                            <td class="text-center py-3" style="background: rgba(245,158,11,0.03);"><i class="fas fa-check text-success"></i></td>
                            <td class="text-center py-3"><i class="fas fa-check text-success"></i></td>
                        </tr>
                        <tr>
                            <td class="py-3 ps-4">Accès API</td>
                            <td class="text-center py-3"><i class="fas fa-times text-danger opacity-50"></i></td>
                            <td class="text-center py-3" style="background: rgba(245,158,11,0.03);"><i class="fas fa-times text-danger opacity-50"></i></td>
                            <td class="text-center py-3"><i class="fas fa-check text-success"></i></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Invoices Link -->
    <div class="text-center">
        <a href="{{ route('subscriptions.invoices') }}" class="btn btn-outline-primary">
            <i class="fas fa-file-invoice me-2"></i>Voir mes factures
        </a>
    </div>
</div>

<!-- Subscribe Modal -->
<div class="modal fade" id="subscribeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-0" style="border-radius: 20px;">
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <i class="fas fa-credit-card me-2 text-primary"></i>
                    S'abonner au plan <span id="modalPlanName"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="subscribeForm" action="{{ route('subscriptions.subscribe') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="plan" id="selectedPlan">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Montant : <strong><span id="modalPrice"></span>€/mois</strong>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Informations de paiement</label>
                        <div id="card-element" class="form-control bg-white text-dark py-3" style="min-height: 50px;"></div>
                        <div id="card-errors" class="text-danger mt-2 small"></div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success" id="submitBtn">
                        <i class="fas fa-lock me-2"></i>Confirmer l'abonnement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upgrade Modal -->
<div class="modal fade" id="upgradeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-0" style="border-radius: 20px;">
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <i class="fas fa-arrow-up me-2 text-warning"></i>
                    Changer de plan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('subscriptions.subscribe') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="plan" id="upgradePlan">
                    <input type="hidden" name="payment_method" value="existing">
                    
                    <p>Vous êtes sur le point de changer votre abonnement vers le plan <strong id="upgradePlanName"></strong>.</p>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Nouveau montant : <strong><span id="upgradePrice"></span>€/mois</strong>
                    </div>
                    
                    <p class="text-white-50 small">Le changement prendra effet immédiatement et sera proratisé.</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-check me-2"></i>Confirmer le changement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
    const stripe = Stripe('{{ config('services.stripe.key') }}');
    const elements = stripe.elements();
    const cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#32325d',
            }
        }
    });
    
    cardElement.mount('#card-element');

    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });

    // Handle modal data
    document.querySelectorAll('[data-bs-target="#subscribeModal"]').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('selectedPlan').value = this.dataset.plan;
            document.getElementById('modalPlanName').textContent = this.dataset.planName;
            document.getElementById('modalPrice').textContent = this.dataset.price;
        });
    });

    document.querySelectorAll('[data-bs-target="#upgradeModal"]').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('upgradePlan').value = this.dataset.plan;
            document.getElementById('upgradePlanName').textContent = this.dataset.planName;
            document.getElementById('upgradePrice').textContent = this.dataset.price;
        });
    });

    // Handle form submission
    const form = document.getElementById('subscribeForm');
    form.addEventListener('submit', async function(event) {
        event.preventDefault();
        
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Traitement...';

        const { setupIntent, error } = await stripe.confirmCardSetup(
            '{{ $intent->client_secret }}',
            {
                payment_method: {
                    card: cardElement,
                }
            }
        );

        if (error) {
            document.getElementById('card-errors').textContent = error.message;
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-lock me-2"></i>Confirmer l\'abonnement';
        } else {
            const hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'payment_method');
            hiddenInput.setAttribute('value', setupIntent.payment_method);
            form.appendChild(hiddenInput);
            form.submit();
        }
    });
</script>
@endsection

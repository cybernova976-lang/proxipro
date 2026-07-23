@extends('layouts.app')

@section('title', 'Acheter des Points - Lunamars')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold text-dark mb-3">
            <i class="fas fa-coins text-warning me-3"></i>Acheter des Points
        </h1>
        <p class="lead text-muted">Boostez votre visibilité et débloquez des fonctionnalités premium</p>
        
        <!-- Current Points Balance -->
        <div class="d-inline-block bg-gradient-primary rounded-pill px-4 py-3 mt-3" 
             style="background: linear-gradient(135deg, #7c3aed 0%, #9333ea 100%);">
            <span class="fs-5 text-white">
                <i class="fas fa-wallet me-2"></i>
                Votre solde : <strong>{{ number_format($userPoints, 0, ',', ' ') }}</strong> points
            </span>
        </div>
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

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Points Packs -->
    <div class="row g-3 mb-4">
        @foreach($pointPacks as $key => $pack)
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm position-relative" 
                     style="border-radius: 14px; overflow: hidden; background: #ffffff; 
                            {{ isset($pack['popular']) ? 'border: 2px solid #ffc107 !important;' : '' }}
                            {{ isset($pack['best_value']) ? 'border: 2px solid #28a745 !important;' : '' }}">
                    
                    @if(isset($pack['popular']))
                        <div class="position-absolute" style="top: -1px; right: 12px;">
                            <span class="badge bg-warning text-dark px-2 py-1" style="font-size: 0.65rem; border-radius: 0 0 8px 8px;">
                                <i class="fas fa-star me-1"></i>POPULAIRE
                            </span>
                        </div>
                    @endif
                    
                    @if(isset($pack['best_value']))
                        <div class="position-absolute" style="top: -1px; right: 12px;">
                            <span class="badge bg-success px-2 py-1" style="font-size: 0.65rem; border-radius: 0 0 8px 8px;">
                                <i class="fas fa-gem me-1"></i>MEILLEUR
                            </span>
                        </div>
                    @endif

                    <div class="card-body p-3 text-center text-dark">
                        <!-- Pack Icon -->
                        <div class="mb-2">
                            @if($key === 'small')
                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center" 
                                     style="width: 48px; height: 48px; background: linear-gradient(135deg, #6c757d, #495057);">
                                    <i class="fas fa-coins text-white"></i>
                                </div>
                            @elseif($key === 'medium')
                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center" 
                                     style="width: 48px; height: 48px; background: linear-gradient(135deg, #ffc107, #fd7e14);">
                                    <i class="fas fa-coins text-white"></i>
                                </div>
                            @elseif($key === 'large')
                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center" 
                                     style="width: 48px; height: 48px; background: linear-gradient(135deg, #7c3aed, #9333ea);">
                                    <i class="fas fa-gem text-white"></i>
                                </div>
                            @else
                                <div class="rounded-circle d-inline-flex align-items-center justify-content-center" 
                                     style="width: 48px; height: 48px; background: linear-gradient(135deg, #28a745, #20c997);">
                                    <i class="fas fa-crown text-white"></i>
                                </div>
                            @endif
                        </div>

                        <!-- Pack Name -->
                        <h6 class="fw-bold mb-1">{{ $pack['name'] }}</h6>

                        <!-- Points -->
                        <div class="fw-bold text-warning mb-0" style="font-size: 1.5rem;">
                            {{ number_format($pack['points'], 0, ',', ' ') }}
                        </div>
                        <p class="text-muted mb-1" style="font-size: 0.75rem;">points</p>

                        <!-- Bonus -->
                        @if($pack['bonus'] > 0)
                            <div class="badge bg-success mb-2 px-2 py-1" style="font-size: 0.7rem;">
                                <i class="fas fa-gift me-1"></i>+{{ number_format($pack['bonus'], 0, ',', ' ') }} BONUS
                            </div>
                        @else
                            <div class="mb-2" style="height: 20px;"></div>
                        @endif

                        <!-- Price -->
                        <div class="fw-bold mb-1 text-dark" style="font-size: 1.3rem;">
                            {{ number_format($pack['price'], 2, ',', ' ') }}€
                        </div>

                        <!-- Price per point -->
                        <p class="text-muted mb-2" style="font-size: 0.7rem;">
                            ≈ {{ number_format($pack['price'] / ($pack['points'] + $pack['bonus']) * 100, 2, ',', ' ') }} cts/pt
                        </p>

                        <!-- Buy Button -->
                        <button type="button" 
                                class="btn btn-sm {{ isset($pack['popular']) ? 'btn-warning' : (isset($pack['best_value']) ? 'btn-success' : 'btn-outline-dark') }} w-100"
                                style="border-radius: 10px; font-size: 0.82rem;"
                                data-bs-toggle="modal" data-bs-target="#purchaseModal"
                                data-pack="{{ $key }}" 
                                data-name="{{ $pack['name'] }}" 
                                data-points="{{ $pack['points'] }}"
                                data-bonus="{{ $pack['bonus'] }}"
                                data-price="{{ $pack['price'] }}">
                            <i class="fas fa-shopping-cart me-1"></i>Acheter
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- What can you do with points -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px; background: #0f172a;">
        <div class="card-header bg-transparent border-0 py-3">
            <h6 class="mb-0 text-white fw-bold">
                <i class="fas fa-magic me-2 text-warning"></i>Que pouvez-vous faire avec les points ?
            </h6>
        </div>
        <div class="card-body py-2">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="d-flex align-items-start">
                        <div class="rounded-circle bg-primary bg-opacity-25 p-2 me-2" style="flex-shrink: 0;">
                            <i class="fas fa-thumbtack text-primary"></i>
                        </div>
                        <div>
                            <h6 class="text-white mb-1" style="font-size: 0.85rem;">Épingler vos annonces</h6>
                            <p class="text-white-50 mb-0" style="font-size: 0.75rem;">Gardez vos annonces en haut de liste. <strong class="text-warning">50 pts/jour</strong></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-start">
                        <div class="rounded-circle bg-warning bg-opacity-25 p-2 me-2" style="flex-shrink: 0;">
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <div>
                            <h6 class="text-white mb-1" style="font-size: 0.85rem;">Mettre en avant</h6>
                            <p class="text-white-50 mb-0" style="font-size: 0.75rem;">Annonces en priorité dans les résultats. <strong class="text-warning">100 pts/sem</strong></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-start">
                        <div class="rounded-circle bg-success bg-opacity-25 p-2 me-2" style="flex-shrink: 0;">
                            <i class="fas fa-gem text-success"></i>
                        </div>
                        <div>
                            <h6 class="text-white mb-1" style="font-size: 0.85rem;">Badges exclusifs</h6>
                            <p class="text-white-50 mb-0" style="font-size: 0.75rem;">Débloquez des badges qui inspirent confiance. <strong class="text-warning">Varie</strong></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-start">
                        <div class="rounded-circle bg-info bg-opacity-25 p-2 me-2" style="flex-shrink: 0;">
                            <i class="fas fa-bolt text-info"></i>
                        </div>
                        <div>
                            <h6 class="text-white mb-1" style="font-size: 0.85rem;">Boost de visibilité</h6>
                            <p class="text-white-50 mb-0" style="font-size: 0.75rem;">Augmentez la visibilité de vos annonces. <strong class="text-warning">200 pts/24h</strong></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-start">
                        <div class="rounded-circle bg-danger bg-opacity-25 p-2 me-2" style="flex-shrink: 0;">
                            <i class="fas fa-envelope text-danger"></i>
                        </div>
                        <div>
                            <h6 class="text-white mb-1" style="font-size: 0.85rem;">Messages prioritaires</h6>
                            <p class="text-white-50 mb-0" style="font-size: 0.75rem;">Vos messages en premier dans la boîte. <strong class="text-warning">25 pts/msg</strong></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-start">
                        <div class="rounded-circle p-2 me-2" style="background: rgba(124, 58, 237, 0.25); flex-shrink: 0;">
                            <i class="fas fa-gift" style="color: #7c3aed;"></i>
                        </div>
                        <div>
                            <h6 class="text-white mb-1" style="font-size: 0.85rem;">Offrir des points</h6>
                            <p class="text-white-50 mb-0" style="font-size: 0.75rem;">Transférez des points à d'autres utilisateurs. <strong class="text-warning">Min. 10 pts</strong></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction History Link -->
    <div class="text-center">
        <a href="{{ route('points.index') }}" class="btn btn-outline-primary">
            <i class="fas fa-history me-2"></i>Voir l'historique de mes points
        </a>
    </div>
</div>

<!-- Purchase Modal -->
<div class="modal fade" id="purchaseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-0" style="border-radius: 20px;">
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <i class="fas fa-shopping-cart me-2 text-warning"></i>
                    Acheter <span id="modalPackName"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="purchaseForm" action="{{ route('purchase-points') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="pack" id="selectedPack">
                    
                    <div class="text-center mb-4 p-4 rounded-3" style="background: rgba(255,255,255,0.05);">
                        <div class="display-5 fw-bold text-warning mb-2">
                            <span id="modalPoints"></span> points
                        </div>
                        <p class="text-white-50 mb-0" id="bonusText"></p>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Montant à payer : <strong><span id="modalPrice"></span>€</strong>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Informations de paiement</label>
                        <div id="card-element-points" class="form-control bg-white text-dark py-3" style="min-height: 50px;"></div>
                        <div id="card-errors-points" class="text-danger mt-2 small"></div>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="termsCheck" required>
                        <label class="form-check-label text-white-50 small" for="termsCheck">
                            J'accepte les <a href="#" class="text-primary">conditions générales de vente</a>
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success" id="submitBtnPoints">
                        <i class="fas fa-lock me-2"></i>Payer maintenant
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
    
    cardElement.mount('#card-element-points');

    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors-points');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });

    // Handle modal data
    document.querySelectorAll('[data-bs-target="#purchaseModal"]').forEach(button => {
        button.addEventListener('click', function() {
            const pack = this.dataset.pack;
            const name = this.dataset.name;
            const points = parseInt(this.dataset.points);
            const bonus = parseInt(this.dataset.bonus);
            const price = this.dataset.price;
            
            document.getElementById('selectedPack').value = pack;
            document.getElementById('modalPackName').textContent = name;
            document.getElementById('modalPoints').textContent = new Intl.NumberFormat('fr-FR').format(points);
            document.getElementById('modalPrice').textContent = price;
            
            if (bonus > 0) {
                document.getElementById('bonusText').innerHTML = '<i class="fas fa-gift text-success me-1"></i>+ ' + 
                    new Intl.NumberFormat('fr-FR').format(bonus) + ' points bonus = <strong class="text-white">' + 
                    new Intl.NumberFormat('fr-FR').format(points + bonus) + ' points</strong>';
            } else {
                document.getElementById('bonusText').textContent = '';
            }
        });
    });

    // Handle form submission
    const form = document.getElementById('purchaseForm');
    form.addEventListener('submit', async function(event) {
        event.preventDefault();
        
        const submitBtn = document.getElementById('submitBtnPoints');
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
            document.getElementById('card-errors-points').textContent = error.message;
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-lock me-2"></i>Payer maintenant';
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

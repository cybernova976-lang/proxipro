@extends('layouts.app')

@section('title', 'Mes Services - Prestataire')

@section('content')
<div class="container py-4" style="max-width: 960px;">
    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h2 class="fw-bold mb-1" style="color: #1e293b;">
                <i class="fas fa-briefcase text-success me-2"></i>Mes Services
            </h2>
            <p class="text-muted mb-0">Gérez vos services et votre profil prestataire</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#becomeProviderModal" style="border-radius: 10px; padding: 8px 18px; background: linear-gradient(135deg, #10b981, #059669); border: none;">
                <i class="fas fa-plus me-1"></i> Ajouter des services
            </button>
            <a href="{{ route('feed') }}" class="btn btn-outline-secondary" style="border-radius: 10px; padding: 8px 18px;">
                <i class="fas fa-arrow-left me-1"></i> Retour
            </a>
        </div>
    </div>

    {{-- Profile Summary Card --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px; overflow: hidden;">
        <div style="background: linear-gradient(135deg, #10b981, #059669); padding: 20px 24px; color: white;">
            <div class="d-flex align-items-center gap-3">
                @if($user->avatar)
                    <img src="{{ storage_url($user->avatar) }}" alt="" style="width: 60px; height: 60px; border-radius: 14px; object-fit: cover; border: 3px solid rgba(255,255,255,0.3);">
                @else
                    <div style="width: 60px; height: 60px; border-radius: 14px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <h5 class="fw-bold mb-0">{{ $user->name }}</h5>
                    <div style="opacity: 0.9; font-size: 0.88rem;">
                        @if($user->is_service_provider)
                            <i class="fas fa-check-circle me-1"></i>Prestataire actif
                        @endif
                        @if($subscription)
                            · <i class="fas fa-crown me-1"></i>{{ $subscription->plan === 'annual' ? 'Abonnement Annuel' : 'Abonnement Mensuel' }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body" style="padding: 20px 24px;">
            <div class="row g-3" style="font-size: 0.9rem;">
                <div class="col-md-4">
                    <div class="text-muted small">Email</div>
                    <div class="fw-semibold">{{ $user->email }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Téléphone</div>
                    <div class="fw-semibold">{{ $user->phone ?: '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Localisation</div>
                    <div class="fw-semibold">{{ $user->city ?: ($user->detected_city ?: '—') }}{{ $user->country ? ', ' . $user->country : '' }}</div>
                </div>
            </div>
            @if($user->address)
                <div class="mt-2" style="font-size: 0.85rem;">
                    <span class="text-muted">Adresse :</span> {{ $user->address }}
                </div>
            @endif
            <div class="mt-3">
                <a href="{{ route('profile.show') }}" class="btn btn-sm btn-outline-primary" style="border-radius: 8px;">
                    <i class="fas fa-user-edit me-1"></i> Modifier mon profil
                </a>
                <a href="{{ route('profile.public', $user->id) }}" class="btn btn-sm btn-outline-secondary ms-1" style="border-radius: 8px;">
                    <i class="fas fa-eye me-1"></i> Voir mon profil public
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                <div class="card-body text-center py-3">
                    <div class="h3 fw-bold mb-0" style="color: #10b981;">{{ $services->count() }}</div>
                    <div class="text-muted small">Services actifs</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                <div class="card-body text-center py-3">
                    <div class="h3 fw-bold mb-0" style="color: #6366f1;">{{ $categoriesCount }}</div>
                    <div class="text-muted small">Catégories</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                <div class="card-body text-center py-3">
                    <div class="h3 fw-bold mb-0" style="color: #f59e0b;">
                        @if($subscription && $subscription->status === 'active')
                            <i class="fas fa-crown" style="font-size: 1.5rem;"></i>
                        @else
                            <i class="fas fa-times-circle text-muted" style="font-size: 1.5rem;"></i>
                        @endif
                    </div>
                    <div class="text-muted small">Abonnement</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                <div class="card-body text-center py-3">
                    <div class="h3 fw-bold mb-0" style="color: #ef4444;">
                        {{ $user->service_provider_since ? $user->service_provider_since->diffForHumans(null, true) : '—' }}
                    </div>
                    <div class="text-muted small">Ancienneté</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Services List --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
        <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between" style="padding: 18px 24px;">
            <h5 class="fw-bold mb-0"><i class="fas fa-list-check text-primary me-2"></i>Mes services enregistrés</h5>
        </div>
        <div class="card-body" style="padding: 0 24px 24px;">
            @if($services->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-briefcase fa-3x text-muted mb-3 d-block" style="opacity: 0.3;"></i>
                    <p class="text-muted mb-3">Vous n'avez pas encore de services enregistrés.</p>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#becomeProviderModal" style="border-radius: 10px; background: linear-gradient(135deg, #10b981, #059669); border: none;">
                        <i class="fas fa-plus me-1"></i> Ajouter des services
                    </button>
                </div>
            @else
                @php
                    $grouped = $services->groupBy('main_category');
                    $allCategories = config('categories.services');
                @endphp
                
                @foreach($grouped as $categoryName => $categoryServices)
                    @php
                        $catConfig = $allCategories[$categoryName] ?? null;
                        $catColor = $catConfig['color'] ?? '#6366f1';
                        $catIcon = $catConfig['fa_icon'] ?? 'fas fa-briefcase';
                    @endphp
                    <div class="mb-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div style="width: 36px; height: 36px; border-radius: 10px; background: {{ $catColor }}; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.9rem;">
                                <i class="{{ $catIcon }}"></i>
                            </div>
                            <h6 class="fw-bold mb-0">{{ $categoryName }}</h6>
                            <span class="badge bg-secondary ms-auto">{{ $categoryServices->count() }} service(s)</span>
                        </div>
                        
                        <div class="d-flex flex-column gap-2">
                            @foreach($categoryServices as $service)
                                <div class="service-item d-flex align-items-center justify-content-between p-3" 
                                     style="background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; transition: all 0.2s;"
                                     id="service-{{ $service->id }}">
                                    <div class="d-flex align-items-center gap-3">
                                        <div style="width: 8px; height: 8px; border-radius: 50%; background: {{ $service->is_active ? '#10b981' : '#94a3b8' }};"></div>
                                        <div>
                                            <div class="fw-semibold" style="font-size: 0.92rem;">{{ $service->subcategory }}</div>
                                            @if($service->experience_years > 0)
                                                <small class="text-muted">{{ $service->experience_years }} ans d'expérience</small>
                                            @endif
                                            @if($service->description)
                                                <small class="text-muted d-block" style="max-width: 400px;">{{ Str::limit($service->description, 80) }}</small>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1">
                                        @if($service->is_active)
                                            <button class="btn btn-sm btn-outline-warning" onclick="toggleServiceStatus({{ $service->id }}, false)" title="Désactiver" style="border-radius: 8px;">
                                                <i class="fas fa-pause"></i>
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-outline-success" onclick="toggleServiceStatus({{ $service->id }}, true)" title="Activer" style="border-radius: 8px;">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        @endif
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteService({{ $service->id }})" title="Supprimer" style="border-radius: 8px;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    {{-- Subscription Info --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
        <div class="card-body" style="padding: 20px 24px;">
            @if($subscription && $subscription->status === 'active')
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem;">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="fw-bold mb-0">Abonnement {{ $subscription->plan === 'annual' ? 'Annuel' : 'Mensuel' }} actif</h6>
                        <small class="text-muted">Expire le {{ $subscription->ends_at->format('d/m/Y') }} · {{ $subscription->plan === 'annual' ? '85€/an' : '9,99€/mois' }}</small>
                    </div>
                    <a href="{{ route('pro.subscription') }}" class="btn btn-sm btn-outline-warning" style="border-radius: 8px;">
                        <i class="fas fa-cog me-1"></i> Gérer
                    </a>
                </div>
            @else
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 48px; height: 48px; background: #f1f5f9; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 1.2rem;">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="fw-bold mb-0">Pas d'abonnement actif</h6>
                        <small class="text-muted">Souscrivez pour débloquer les fonctionnalités pro et gagner en visibilité</small>
                    </div>
                    <a href="{{ route('pro.subscription') }}" class="btn btn-sm btn-success" style="border-radius: 8px; background: linear-gradient(135deg, #10b981, #059669); border: none;">
                        <i class="fas fa-rocket me-1"></i> S'abonner
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Actions --}}
    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
        <div class="card-body" style="padding: 20px 24px;">
            <h6 class="fw-bold mb-3"><i class="fas fa-bolt text-warning me-2"></i>Actions rapides</h6>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('pro.dashboard') }}" class="btn btn-outline-primary" style="border-radius: 10px;">
                    <i class="fas fa-tachometer-alt me-1"></i> Espace Pro
                </a>
                <a href="{{ route('profile.public', $user->id) }}" class="btn btn-outline-info" style="border-radius: 10px;">
                    <i class="fas fa-eye me-1"></i> Profil public
                </a>
                <button class="btn btn-outline-danger" onclick="deactivateProvider()" style="border-radius: 10px;">
                    <i class="fas fa-power-off me-1"></i> Désactiver prestataire
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Toast notification --}}
<div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
    <div id="mesServicestoast" class="toast align-items-center border-0" role="alert" style="border-radius: 12px;">
        <div class="d-flex">
            <div class="toast-body" id="mesServicesToastBody"></div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 9999;">
        <div class="alert alert-success alert-dismissible fade show shadow-lg" style="border-radius: 14px; border: none; background: linear-gradient(135deg, #10b981, #059669); color: white; min-width: 350px;">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    </div>
@endif

<script>
    const csrfToken = '{{ csrf_token() }}';
    
    function showToast(message, type = 'success') {
        const toast = document.getElementById('mesServicestoast');
        const body = document.getElementById('mesServicesToastBody');
        body.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle text-success' : 'exclamation-circle text-danger'} me-2"></i>${message}`;
        toast.classList.remove('bg-success', 'bg-danger', 'text-white');
        if (type === 'danger') toast.classList.add('bg-danger', 'text-white');
        const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
        bsToast.show();
    }
    
    async function toggleServiceStatus(serviceId, activate) {
        if (!confirm(activate ? 'Réactiver ce service ?' : 'Désactiver ce service ?')) return;
        
        try {
            const response = await fetch(`/service-provider/service/${serviceId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ is_active: activate })
            });
            const data = await response.json();
            if (data.success) {
                showToast(activate ? 'Service réactivé !' : 'Service désactivé.');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(data.message || 'Erreur', 'danger');
            }
        } catch (e) {
            showToast('Erreur réseau', 'danger');
        }
    }
    
    async function deleteService(serviceId) {
        if (!confirm('Supprimer définitivement ce service ?')) return;
        
        try {
            const response = await fetch(`/service-provider/service/${serviceId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            if (data.success) {
                const el = document.getElementById(`service-${serviceId}`);
                if (el) {
                    el.style.transition = 'all 0.3s';
                    el.style.opacity = '0';
                    el.style.transform = 'translateX(30px)';
                    setTimeout(() => el.remove(), 300);
                }
                showToast('Service supprimé.');
                setTimeout(() => location.reload(), 1200);
            } else {
                showToast(data.message || 'Erreur', 'danger');
            }
        } catch (e) {
            showToast('Erreur réseau', 'danger');
        }
    }
    
    async function deactivateProvider() {
        if (!confirm('Désactiver votre statut prestataire ? Vous ne serez plus visible dans les recherches.')) return;
        
        try {
            const response = await fetch('/service-provider/deactivate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            if (data.success) {
                showToast('Statut prestataire désactivé.');
                setTimeout(() => window.location.href = '{{ route("feed") }}', 1200);
            } else {
                showToast(data.message || 'Erreur', 'danger');
            }
        } catch (e) {
            showToast('Erreur réseau', 'danger');
        }
    }
</script>

<style>
    .service-item:hover {
        background: #f0fdf4 !important;
        border-color: #a7f3d0 !important;
    }
</style>
@endsection

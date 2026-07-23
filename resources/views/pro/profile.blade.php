@extends('pro.layout')
@section('title', 'Mon Profil Pro - Lunamars')

@section('content')
<div class="pro-content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 0.8rem;">
                <li class="breadcrumb-item"><a href="{{ route('pro.dashboard') }}" style="color: var(--pro-primary);">Espace Pro</a></li>
                <li class="breadcrumb-item active">Mon profil</li>
            </ol>
        </nav>
        <h1>Mon profil professionnel</h1>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('pro.profile.edit') }}" class="btn btn-pro-primary">
            <i class="fas fa-edit me-1"></i> Modifier
        </a>
        <button class="btn btn-pro-outline" id="shareProfileBtn" @disabled(!$user->profile_public) title="{{ $user->profile_public ? 'Partager mon profil public' : 'Activez la visibilité publique du profil pour le partager' }}">
            <i class="fas fa-share-alt me-1"></i> Partager
        </button>
    </div>
</div>

{{-- Profile card --}}
<div class="pro-card">
    <div class="d-flex align-items-start gap-4 flex-wrap">
        <div class="text-center">
            @if($user->avatar)
                <img src="{{ storage_url($user->avatar) }}" alt="" style="width: 100px; height: 100px; border-radius: 16px; object-fit: cover; border: 3px solid var(--pro-primary);">
            @else
                <div style="width: 100px; height: 100px; border-radius: 16px; background: var(--pro-gradient); color: white; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 800;">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            @endif
            <div class="mt-2">
                @if($user->isProVerified())
                    <span class="pro-status pro-status-success"><i class="fas fa-check-circle me-1"></i>Vérifié</span>
                @else
                    <span class="pro-status pro-status-warning"><i class="fas fa-clock me-1"></i>Non vérifié</span>
                @endif
            </div>
        </div>
        <div class="flex-grow-1">
            <h3 class="fw-bold mb-1">{{ $user->company_name ?? $user->name }}</h3>
            <p class="text-muted mb-2">{{ $user->getAccountTypeLabel() }} · {{ $user->profession ?? 'Professionnel' }}</p>
            
            <div class="d-flex flex-wrap gap-4 mb-3" style="font-size: 0.88rem;">
                @if($user->city || $user->detected_city)
                    <div><i class="fas fa-map-marker-alt text-danger me-1"></i>{{ $user->getLocationLabel() }}</div>
                @endif
                @if($user->phone)
                    <div><i class="fas fa-phone text-success me-1"></i>{{ $user->phone }}</div>
                @endif
                @if($user->email)
                    <div><i class="fas fa-envelope text-primary me-1"></i>{{ $user->email }}</div>
                @endif
                @if($user->website_url)
                    <div><i class="fas fa-globe text-info me-1"></i><a href="{{ $user->website_url }}" target="_blank">Site web</a></div>
                @endif
            </div>
            
            @if($user->bio)
                <p style="font-size: 0.9rem; color: var(--pro-text-secondary); max-width: 600px;">{{ $user->bio }}</p>
            @endif

            <div class="d-flex flex-wrap gap-3 mt-3">
                @if($user->hourly_rate)
                    <div class="pro-card py-2 px-3 mb-0">
                        <small class="text-muted d-block">Tarif horaire</small>
                        <strong class="text-primary">{{ $user->hourly_rate }}€/h</strong>
                    </div>
                @endif
                @if($user->years_experience)
                    <div class="pro-card py-2 px-3 mb-0">
                        <small class="text-muted d-block">Expérience</small>
                        <strong>{{ $user->years_experience }} ans</strong>
                    </div>
                @endif
                @if($user->insurance_number)
                    <div class="pro-card py-2 px-3 mb-0">
                        <small class="text-muted d-block">Assurance</small>
                        <strong class="text-success"><i class="fas fa-shield-alt me-1"></i>Assuré</strong>
                    </div>
                @endif
            </div>
        </div>

        {{-- Rating summary --}}
        <div class="text-center p-3" style="background: #f8fafc; border-radius: 14px; min-width: 150px;">
            <div style="font-size: 2.5rem; font-weight: 800; color: #f59e0b;">
                {{ number_format($user->reviewsReceived()->avg('rating') ?? 0, 1) }}
            </div>
            <div class="mb-1">
                @for($i = 1; $i <= 5; $i++)
                    <i class="fas fa-star {{ $i <= round($user->reviewsReceived()->avg('rating') ?? 0) ? 'text-warning' : 'text-muted' }}" style="font-size: 0.9rem;"></i>
                @endfor
            </div>
            <div class="text-muted" style="font-size: 0.8rem;">{{ $user->reviewsReceived()->count() }} avis</div>
        </div>
    </div>
</div>

{{-- Services --}}
<div class="pro-card">
    <div class="pro-card-title"><i class="fas fa-briefcase text-primary"></i> Mes services</div>
    @if($services->isEmpty())
        <p class="text-muted">Aucun service renseigné.</p>
    @else
        <div class="d-flex flex-wrap gap-2">
            @foreach($services as $service)
                <div class="d-flex align-items-center gap-2" style="padding: 8px 14px; background: rgba(99,102,241,0.06); border-radius: 10px; border: 1px solid rgba(99,102,241,0.1);">
                    <span style="font-size: 0.88rem; font-weight: 500;">{{ $service->subcategory ?? $service->main_category }}</span>
                    @if($service->experience_years > 0)
                        <span class="text-muted" style="font-size: 0.75rem;">· {{ $service->experience_years }} ans</span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Reviews --}}
<div class="pro-card">
    <div class="pro-card-title">
        <i class="fas fa-star text-warning"></i> Avis clients
        <span class="ms-auto text-muted" style="font-size: 0.8rem;">{{ $reviews->total() }} avis</span>
    </div>
    @if($reviews->isEmpty())
        <div class="pro-empty py-4">
            <div class="pro-empty-icon">⭐</div>
            <h5>Pas encore d'avis</h5>
            <p>Les avis de vos clients apparaîtront ici</p>
        </div>
    @else
        @foreach($reviews as $review)
            <div class="d-flex gap-3 p-3 mb-2" style="background: #f8fafc; border-radius: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(245,158,11,0.1); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                    {{ strtoupper(substr($review->reviewer?->name ?? 'A', 0, 1)) }}
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div>
                            <strong style="font-size: 0.88rem;">{{ $review->reviewer?->name ?? 'Anonyme' }}</strong>
                            <div>
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star {{ $i <= $review->rating ? 'text-warning' : 'text-muted' }}" style="font-size: 0.7rem;"></i>
                                @endfor
                            </div>
                        </div>
                        <small class="text-muted">{{ $review->created_at->diffForHumans() }}</small>
                    </div>
                    @if($review->comment)
                        <p class="mb-0" style="font-size: 0.88rem; color: var(--pro-text-secondary);">{{ $review->comment }}</p>
                    @endif
                </div>
            </div>
        @endforeach
        <div class="mt-3">
            {{ $reviews->links() }}
        </div>
    @endif
</div>

@if($user->profile_public)
    @include('profile.partials.share-modal', [
        'triggerId' => 'shareProfileBtn',
        'modalId' => 'proProfileShareModal',
    ])
@endif
@endsection

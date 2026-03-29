@extends('layouts.app')

@section('title', 'Professionnels disponibles - ProxiPro')

@push('styles')
<style>
body { background: #f0f2f5; }

.matching-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px 16px 60px;
}

/* Demand summary */
.matching-demand-card {
    background: white; border-radius: 20px; padding: 24px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06); margin-bottom: 24px;
    position: relative; overflow: hidden;
}
.matching-demand-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(135deg, #3b82f6, #6366f1);
}
.matching-demand-header {
    display: flex; align-items: center; gap: 14px; margin-bottom: 16px;
}
.matching-demand-icon {
    width: 48px; height: 48px; border-radius: 14px;
    background: linear-gradient(135deg, #eff6ff, #e0e7ff);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; color: #3b82f6;
}
.matching-demand-title { font-size: 1.15rem; font-weight: 700; color: #111827; }
.matching-demand-meta {
    display: flex; flex-wrap: wrap; gap: 12px; font-size: 0.85rem; color: #6b7280;
}
.matching-demand-meta span { display: flex; align-items: center; gap: 5px; }
.matching-demand-meta .badge-cat {
    background: #eff6ff; color: #3b82f6; padding: 3px 10px; border-radius: 8px; font-weight: 600;
}
.matching-demand-meta .badge-urgent {
    background: #fef2f2; color: #ef4444; padding: 3px 10px; border-radius: 8px; font-weight: 600;
}
.matching-demand-desc {
    color: #374151; font-size: 0.9rem; line-height: 1.5; margin-top: 12px;
    background: #f8fafc; border-radius: 10px; padding: 12px 16px; border-left: 3px solid #3b82f6;
}

/* Success banner */
.matching-success {
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border: 1px solid #86efac; border-radius: 16px; padding: 20px 24px;
    display: flex; align-items: flex-start; gap: 14px; margin-bottom: 24px;
}
.matching-success-icon {
    width: 44px; height: 44px; border-radius: 50%;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 1.2rem; flex-shrink: 0;
}
.matching-success h3 { font-size: 1rem; font-weight: 700; color: #166534; margin-bottom: 4px; }
.matching-success p { font-size: 0.85rem; color: #15803d; margin: 0; }

/* Results header */
.matching-results-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 18px; flex-wrap: wrap; gap: 12px;
}
.matching-results-header h2 {
    font-size: 1.2rem; font-weight: 700; color: #111827; margin: 0;
}
.matching-results-count {
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    color: #fff; padding: 6px 14px; border-radius: 20px;
    font-size: 0.82rem; font-weight: 600;
}

/* Pro cards grid */
.matching-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;
}
@media (max-width: 640px) { .matching-grid { grid-template-columns: 1fr; } }

.pro-card {
    background: #fff; border-radius: 18px; overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06); transition: all 0.2s;
    border: 1px solid #f0f0f0; position: relative;
}
.pro-card:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,0.1); }
.pro-card-badge {
    position: absolute; top: 12px; right: 12px; z-index: 2;
    background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff;
    padding: 3px 10px; border-radius: 8px; font-size: 0.72rem; font-weight: 700;
    display: flex; align-items: center; gap: 4px;
}
.pro-card-header {
    display: flex; align-items: center; gap: 14px; padding: 18px 18px 0;
}
.pro-card-avatar {
    width: 56px; height: 56px; border-radius: 50%; object-fit: cover;
    border: 3px solid #e5e7eb; flex-shrink: 0;
}
.pro-card-avatar-placeholder {
    width: 56px; height: 56px; border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 1.2rem; font-weight: 700; flex-shrink: 0;
}
.pro-card-name {
    font-size: 1rem; font-weight: 700; color: #111827; line-height: 1.2;
}
.pro-card-profession {
    font-size: 0.82rem; color: #6b7280; margin-top: 2px;
}
.pro-card-verified {
    display: inline-flex; align-items: center; gap: 3px; margin-top: 3px;
    font-size: 0.75rem; color: #3b82f6;
}
.pro-card-body { padding: 14px 18px; }
.pro-card-bio {
    font-size: 0.85rem; color: #6b7280; line-height: 1.45; margin-bottom: 10px;
    display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
}
.pro-card-details {
    display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px;
}
.pro-card-detail {
    display: flex; align-items: center; gap: 5px;
    font-size: 0.78rem; color: #6b7280;
    background: #f3f4f6; padding: 4px 10px; border-radius: 8px;
}
.pro-card-detail i { color: #9ca3af; font-size: 0.72rem; }
.pro-card-actions {
    display: flex; gap: 8px; padding: 0 18px 18px;
}
.pro-card-btn {
    flex: 1; padding: 10px 14px; border-radius: 10px; font-size: 0.85rem;
    font-weight: 600; border: none; cursor: pointer; text-align: center;
    text-decoration: none; display: inline-flex; align-items: center;
    justify-content: center; gap: 6px; transition: all 0.2s;
}
.pro-card-btn-profile {
    background: #f3f4f6; color: #374151;
}
.pro-card-btn-profile:hover { background: #e5e7eb; color: #111827; text-decoration: none; }
.pro-card-btn-contact {
    background: linear-gradient(135deg, #3b82f6, #6366f1); color: #fff;
}
.pro-card-btn-contact:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59,130,246,0.3); color: #fff; text-decoration: none; }

/* Empty state */
.matching-empty {
    text-align: center; padding: 48px 24px;
    background: #fff; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}
.matching-empty-icon {
    width: 72px; height: 72px; border-radius: 50%;
    background: #f3f4f6; display: flex; align-items: center; justify-content: center;
    margin: 0 auto 18px; font-size: 1.8rem; color: #9ca3af;
}
.matching-empty h3 { font-size: 1.1rem; font-weight: 700; color: #374151; margin-bottom: 8px; }
.matching-empty p { font-size: 0.9rem; color: #6b7280; max-width: 400px; margin: 0 auto 20px; }
.matching-empty-btn {
    padding: 12px 24px; border-radius: 12px;
    background: linear-gradient(135deg, #3b82f6, #6366f1); color: #fff;
    font-weight: 600; border: none; cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; gap: 8px; font-size: 0.92rem;
}
.matching-empty-btn:hover { color: #fff; text-decoration: none; transform: translateY(-1px); }

/* Additional section */
.matching-additional {
    margin-top: 32px; padding-top: 24px; border-top: 2px solid #e5e7eb;
}
.matching-additional h3 {
    font-size: 1.05rem; font-weight: 700; color: #374151; margin-bottom: 6px;
}
.matching-additional p {
    font-size: 0.85rem; color: #6b7280; margin-bottom: 16px;
}

/* Actions bar */
.matching-actions {
    display: flex; justify-content: center; gap: 12px; margin-top: 28px; flex-wrap: wrap;
}
.matching-action-btn {
    padding: 12px 24px; border-radius: 12px; font-size: 0.9rem; font-weight: 600;
    border: none; cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s;
}
.matching-action-feed { background: #f3f4f6; color: #374151; }
.matching-action-feed:hover { background: #e5e7eb; color: #111827; text-decoration: none; }
.matching-action-boost {
    background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff;
}
.matching-action-boost:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(245,158,11,0.3); color: #fff; text-decoration: none; }
</style>
@endpush

@section('content')
<div class="matching-container">

    <!-- Success banner -->
    <div class="matching-success">
        <div class="matching-success-icon">
            <i class="fas fa-check"></i>
        </div>
        <div>
            <h3>Votre demande a été publiée avec succès !</h3>
            <p>Les professionnels correspondants ont été notifiés. Voici ceux qui correspondent à votre besoin.</p>
        </div>
    </div>

    <!-- Demand summary -->
    <div class="matching-demand-card">
        <div class="matching-demand-header">
            <div class="matching-demand-icon">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div>
                <div class="matching-demand-title">{{ $ad->title }}</div>
                <div class="matching-demand-meta">
                    <span class="badge-cat"><i class="fas fa-tag"></i> {{ $ad->category }}</span>
                    <span><i class="fas fa-map-marker-alt"></i> {{ $ad->location }}{{ $ad->country ? ', ' . $ad->country : '' }}</span>
                    @if($ad->is_urgent)
                        <span class="badge-urgent"><i class="fas fa-bolt"></i> Urgent</span>
                    @endif
                    @if($ad->price)
                        <span><i class="fas fa-euro-sign"></i> Budget : {{ number_format($ad->price, 0) }} €</span>
                    @endif
                </div>
            </div>
        </div>
        @if($ad->description)
            <div class="matching-demand-desc">{{ Str::limit($ad->description, 250) }}</div>
        @endif
    </div>

    @if($professionals->count() > 0)
        <!-- Results header -->
        <div class="matching-results-header">
            <h2><i class="fas fa-users" style="color:#3b82f6; margin-right:8px;"></i> Professionnels disponibles</h2>
            <span class="matching-results-count">{{ $professionals->count() }} professionnel{{ $professionals->count() > 1 ? 's' : '' }} trouvé{{ $professionals->count() > 1 ? 's' : '' }}</span>
        </div>

        <!-- Pro cards -->
        <div class="matching-grid">
            @foreach($professionals as $pro)
            <div class="pro-card">
                @if($pro->hasActiveProSubscription())
                    <div class="pro-card-badge"><i class="fas fa-crown"></i> PRO</div>
                @endif

                <div class="pro-card-header">
                    @if($pro->avatar)
                        <img src="{{ asset('storage/' . $pro->avatar) }}" alt="{{ $pro->name }}" class="pro-card-avatar">
                    @else
                        <div class="pro-card-avatar-placeholder">{{ strtoupper(substr($pro->name, 0, 1)) }}</div>
                    @endif
                    <div>
                        <div class="pro-card-name">{{ $pro->name }}</div>
                        @if($pro->profession)
                            <div class="pro-card-profession">{{ $pro->profession }}</div>
                        @endif
                        @if($pro->is_verified ?? false)
                            <div class="pro-card-verified"><i class="fas fa-check-circle"></i> Vérifié</div>
                        @endif
                    </div>
                </div>

                <div class="pro-card-body">
                    @if($pro->bio)
                        <div class="pro-card-bio">{{ $pro->bio }}</div>
                    @endif
                    <div class="pro-card-details">
                        @if($pro->city)
                            <span class="pro-card-detail"><i class="fas fa-map-marker-alt"></i> {{ $pro->city }}</span>
                        @endif
                        @if(isset($pro->active_ads_count) && $pro->active_ads_count > 0)
                            <span class="pro-card-detail"><i class="fas fa-briefcase"></i> {{ $pro->active_ads_count }} annonce{{ $pro->active_ads_count > 1 ? 's' : '' }}</span>
                        @endif
                        @if($pro->services && $pro->services->count() > 0)
                            <span class="pro-card-detail"><i class="fas fa-tools"></i> {{ $pro->services->count() }} service{{ $pro->services->count() > 1 ? 's' : '' }}</span>
                        @endif
                    </div>
                </div>

                <div class="pro-card-actions">
                    <a href="{{ route('profile.public', $pro->id) }}" class="pro-card-btn pro-card-btn-profile">
                        <i class="fas fa-user"></i> Profil
                    </a>
                    <a href="{{ route('profile.public', $pro->id) }}#contact" class="pro-card-btn pro-card-btn-contact">
                        <i class="fas fa-comment-dots"></i> Contacter
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        @if($additionalPros->count() > 0 && $parentCategory)
        <div class="matching-additional">
            <h3><i class="fas fa-lightbulb" style="color:#f59e0b; margin-right:6px;"></i> Autres professionnels dans « {{ $parentCategory }} »</h3>
            <p>Ces prestataires proposent des services similaires dans la même catégorie.</p>
            <div class="matching-grid">
                @foreach($additionalPros as $pro)
                <div class="pro-card">
                    @if($pro->hasActiveProSubscription())
                        <div class="pro-card-badge"><i class="fas fa-crown"></i> PRO</div>
                    @endif

                    <div class="pro-card-header">
                        @if($pro->avatar)
                            <img src="{{ asset('storage/' . $pro->avatar) }}" alt="{{ $pro->name }}" class="pro-card-avatar">
                        @else
                            <div class="pro-card-avatar-placeholder">{{ strtoupper(substr($pro->name, 0, 1)) }}</div>
                        @endif
                        <div>
                            <div class="pro-card-name">{{ $pro->name }}</div>
                            @if($pro->profession)
                                <div class="pro-card-profession">{{ $pro->profession }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="pro-card-body">
                        @if($pro->bio)
                            <div class="pro-card-bio">{{ $pro->bio }}</div>
                        @endif
                        <div class="pro-card-details">
                            @if($pro->city)
                                <span class="pro-card-detail"><i class="fas fa-map-marker-alt"></i> {{ $pro->city }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="pro-card-actions">
                        <a href="{{ route('profile.public', $pro->id) }}" class="pro-card-btn pro-card-btn-profile">
                            <i class="fas fa-user"></i> Profil
                        </a>
<a href="{{ route('profile.public', $pro->id) }}#contact" class="pro-card-btn pro-card-btn-contact">
                            <i class="fas fa-comment-dots"></i> Contacter
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    @else
        <!-- Empty state -->
        <div class="matching-empty">
            <div class="matching-empty-icon">
                <i class="fas fa-search"></i>
            </div>
            <h3>Aucun professionnel trouvé pour le moment</h3>
            <p>Pas de souci ! Votre demande reste visible et les professionnels seront notifiés. Ils pourront vous contacter directement.</p>
            <a href="{{ route('feed') }}" class="matching-empty-btn">
                <i class="fas fa-home"></i> Retour au fil d'actualité
            </a>
        </div>
    @endif

    <!-- Bottom actions -->
    <div class="matching-actions">
        <a href="{{ route('feed') }}" class="matching-action-btn matching-action-feed">
            <i class="fas fa-home"></i> Retour au feed
        </a>
        @if($ad && !$ad->is_boosted)
        <a href="{{ route('ads.show', $ad->id) }}?boost=1" class="matching-action-btn matching-action-boost">
            <i class="fas fa-rocket"></i> Booster ma demande
        </a>
        @endif
    </div>
</div>
@endsection

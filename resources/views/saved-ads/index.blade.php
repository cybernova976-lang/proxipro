@extends('layouts.app')

@section('title', 'Mes annonces sauvegardées - Lunamars')

@push('styles')
<style>
    .saved-ads-page {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 20px;
    }

    .page-header {
        margin-bottom: 30px;
    }

    .page-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: #111827;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .page-title i {
        color: #ef4444;
    }

    .page-subtitle {
        color: #6b7280;
        font-size: 1rem;
    }

    .saved-ads-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 24px;
    }

    .saved-ad-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
    }

    .saved-ad-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.12);
    }

    .saved-ad-image {
        height: 180px;
        background: #f3f4f6;
        position: relative;
        overflow: hidden;
    }

    .saved-ad-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .saved-ad-image .placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        font-size: 3rem;
    }

    .saved-ad-badge {
        position: absolute;
        top: 12px;
        left: 12px;
        background: rgba(79, 70, 229, 0.9);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .saved-ad-remove {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: rgba(255,255,255,0.95);
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ef4444;
        font-size: 1rem;
        transition: all 0.2s;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .saved-ad-remove:hover {
        background: #ef4444;
        color: white;
        transform: scale(1.1);
    }

    .saved-ad-content {
        padding: 20px;
    }

    .saved-ad-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #111827;
        margin-bottom: 8px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .saved-ad-title a {
        color: inherit;
        text-decoration: none;
    }

    .saved-ad-title a:hover {
        color: #4f46e5;
    }

    .saved-ad-meta {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
        font-size: 0.85rem;
        color: #6b7280;
    }

    .saved-ad-meta i {
        margin-right: 4px;
    }

    .saved-ad-price {
        font-size: 1.2rem;
        font-weight: 700;
        color: #4f46e5;
        margin-bottom: 16px;
    }

    .saved-ad-actions {
        display: flex;
        gap: 10px;
    }

    .saved-ad-actions .btn {
        flex: 1;
        padding: 10px 16px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        text-align: center;
        transition: all 0.2s;
    }

    .btn-view {
        background: #4f46e5;
        color: white;
    }

    .btn-view:hover {
        background: #4338ca;
        color: white;
    }

    .btn-contact {
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #e5e7eb;
    }

    .btn-contact:hover {
        background: #e5e7eb;
    }

    .empty-state {
        text-align: center;
        padding: 80px 20px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    }

    .empty-state i {
        font-size: 4rem;
        color: #d1d5db;
        margin-bottom: 20px;
    }

    .empty-state h3 {
        font-size: 1.3rem;
        color: #374151;
        margin-bottom: 10px;
    }

    .empty-state p {
        color: #6b7280;
        margin-bottom: 24px;
    }

    .empty-state .btn-explore {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 14px 28px;
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: white;
        border-radius: 12px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
    }

    .empty-state .btn-explore:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(79, 70, 229, 0.3);
    }

    .saved-date {
        font-size: 0.8rem;
        color: #9ca3af;
        margin-top: 8px;
    }
</style>
@endpush

@section('content')
<div class="saved-ads-page">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-heart"></i>
            Mes annonces sauvegardées
        </h1>
        <p class="page-subtitle">
            Retrouvez toutes les annonces que vous avez sauvegardées
        </p>
    </div>

    @if($savedAds->count() > 0)
        <div class="saved-ads-grid">
            @foreach($savedAds as $ad)
                <div class="saved-ad-card" data-ad-id="{{ $ad->id }}">
                    <div class="saved-ad-image">
                        @if(!empty($ad->photos) && isset($ad->photos[0]))
                            <img src="{{ storage_url($ad->photos[0]) }}" alt="{{ $ad->title }}">
                        @else
                            <div class="placeholder">
                                <i class="fas fa-image"></i>
                            </div>
                        @endif
                        
                        <span class="saved-ad-badge">{{ $ad->category }}</span>
                        
                        <button class="saved-ad-remove" onclick="removeSavedAd({{ $ad->id }}, this)" title="Retirer des favoris">
                            <i class="fas fa-heart-broken"></i>
                        </button>
                    </div>
                    
                    <div class="saved-ad-content">
                        <h3 class="saved-ad-title">
                            <a href="{{ route('ads.show', $ad) }}">{{ $ad->title }}</a>
                        </h3>
                        
                        <div class="saved-ad-meta">
                            <span><i class="fas fa-map-marker-alt"></i>{{ $ad->location ?? 'Non précisé' }}</span>
                            <span><i class="fas fa-user"></i>{{ $ad->user->name ?? 'Anonyme' }}</span>
                        </div>
                        
                        <div class="saved-ad-price">
                            @if($ad->price)
                                {{ number_format($ad->price, 0, ',', ' ') }} €
                            @else
                                Sur devis
                            @endif
                        </div>
                        
                        <div class="saved-ad-actions">
                            <a href="{{ route('ads.show', $ad) }}" class="btn btn-view">
                                <i class="fas fa-eye"></i> Voir
                            </a>
                            <a href="{{ route('ads.show', $ad) }}#contact" class="btn btn-contact">
                                <i class="fas fa-envelope"></i> Contacter
                            </a>
                        </div>
                        
                        <div class="saved-date">
                            <i class="fas fa-bookmark"></i> Sauvegardée {{ $ad->pivot->created_at->locale('fr')->diffForHumans() }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $savedAds->links() }}
        </div>
    @else
        <div class="empty-state">
            <i class="fas fa-heart"></i>
            <h3>Aucune annonce sauvegardée</h3>
            <p>Vous n'avez pas encore sauvegardé d'annonces. Explorez les offres et sauvegardez celles qui vous intéressent !</p>
            <a href="{{ route('feed') }}" class="btn-explore">
                <i class="fas fa-search"></i>
                Explorer les annonces
            </a>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
function removeSavedAd(adId, btn) {
    if (!confirm('Voulez-vous retirer cette annonce de vos favoris ?')) return;
    
    fetch(`/ads/${adId}/toggle-save`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && !data.saved) {
            const card = btn.closest('.saved-ad-card');
            card.style.transition = 'all 0.3s ease';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.9)';
            setTimeout(() => card.remove(), 300);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue');
    });
}
</script>
@endsection

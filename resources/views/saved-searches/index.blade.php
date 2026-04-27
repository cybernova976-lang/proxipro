@extends('layouts.app')

@section('title', 'Mes alertes - ProxiPro')

@push('styles')
<style>
    .saved-searches-page {
        max-width: 1120px;
        margin: 0 auto;
        padding: 32px 20px 48px;
    }
    .saved-searches-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 24px;
    }
    .saved-searches-title {
        margin: 0 0 8px;
        font-size: 1.9rem;
        font-weight: 800;
        color: #0f172a;
    }
    .saved-searches-subtitle {
        margin: 0;
        color: #64748b;
    }
    .saved-searches-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 18px;
    }
    .saved-search-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 20px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    }
    .saved-search-card-top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
    }
    .saved-search-name {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
    }
    .saved-search-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 14px;
    }
    .saved-search-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        font-size: 0.78rem;
        font-weight: 600;
        color: #334155;
    }
    .saved-search-stats {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        font-size: 0.84rem;
        color: #475569;
        margin-bottom: 16px;
    }
    .saved-search-actions {
        display: flex;
        gap: 10px;
    }
    .saved-search-actions .btn {
        flex: 1;
        border-radius: 12px;
        font-weight: 700;
    }
    .saved-search-empty {
        text-align: center;
        padding: 72px 20px;
        background: white;
        border-radius: 24px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    }
    .saved-search-empty i {
        font-size: 3rem;
        color: #cbd5e1;
        margin-bottom: 14px;
    }
</style>
@endpush

@section('content')
<div class="saved-searches-page">
    <div class="saved-searches-header">
        <div>
            <h1 class="saved-searches-title"><i class="fas fa-bell me-2" style="color:#f97316;"></i>Mes alertes</h1>
            <p class="saved-searches-subtitle">Retrouvez vos recherches sauvegardees et les nouvelles annonces qui y correspondent.</p>
        </div>
        <a href="{{ route('feed') }}" class="btn btn-primary" style="border-radius:12px; font-weight:700;">
            <i class="fas fa-plus me-2"></i>Nouvelle alerte
        </a>
    </div>

    @if($savedSearches->count() > 0)
        <div class="saved-searches-grid">
            @foreach($savedSearches as $savedSearch)
                <div class="saved-search-card">
                    <div class="saved-search-card-top">
                        <div>
                            <h2 class="saved-search-name">{{ $savedSearch->name }}</h2>
                            <small class="text-muted">Creee {{ $savedSearch->created_at->diffForHumans() }}</small>
                        </div>
                        <span class="saved-search-pill" style="background:{{ $savedSearch->is_active ? '#dcfce7' : '#f1f5f9' }}; border-color:{{ $savedSearch->is_active ? '#86efac' : '#e2e8f0' }}; color:{{ $savedSearch->is_active ? '#166534' : '#475569' }};">
                            <i class="fas fa-{{ $savedSearch->is_active ? 'check' : 'pause' }}-circle"></i>
                            {{ $savedSearch->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="saved-search-meta">
                        @if($savedSearch->category)
                            <span class="saved-search-pill"><i class="fas fa-folder"></i>{{ $savedSearch->category }}</span>
                        @endif
                        @if($savedSearch->search_term)
                            <span class="saved-search-pill"><i class="fas fa-search"></i>{{ $savedSearch->search_term }}</span>
                        @endif
                        <span class="saved-search-pill"><i class="fas fa-list"></i>{{ ucfirst($savedSearch->service_type) }}</span>
                        @if($savedSearch->city)
                            <span class="saved-search-pill"><i class="fas fa-map-marker-alt"></i>{{ $savedSearch->city }}</span>
                        @endif
                        @if($savedSearch->radius_km)
                            <span class="saved-search-pill"><i class="fas fa-bullseye"></i>{{ $savedSearch->radius_km }} km</span>
                        @endif
                    </div>

                    <div class="saved-search-stats">
                        <span><strong>{{ $savedSearch->matches_count }}</strong> correspondances</span>
                        <span>
                            Derniere activite:
                            <strong>{{ $savedSearch->last_matched_at ? $savedSearch->last_matched_at->diffForHumans() : 'Aucune' }}</strong>
                        </span>
                    </div>

                    <div class="saved-search-actions">
                        <a href="{{ $savedSearch->buildFeedUrl() }}" class="btn btn-outline-primary">
                            <i class="fas fa-eye me-2"></i>Ouvrir
                        </a>
                        <form method="POST" action="{{ route('saved-searches.destroy', $savedSearch) }}" style="flex:1;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="fas fa-trash me-2"></i>Supprimer
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $savedSearches->links() }}
        </div>
    @else
        <div class="saved-search-empty">
            <i class="fas fa-bell-slash"></i>
            <h3>Aucune alerte enregistree</h3>
            <p class="text-muted mb-4">Sauvegardez une recherche depuis le feed pour etre notifie des nouvelles annonces correspondantes.</p>
            <a href="{{ route('feed') }}" class="btn btn-primary" style="border-radius:12px; font-weight:700;">
                <i class="fas fa-compass me-2"></i>Retour au feed
            </a>
        </div>
    @endif
</div>
@endsection
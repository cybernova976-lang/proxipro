@extends('layouts.app')

@section('title', 'Objets perdus & trouvés - Lunamars')

@push('styles')
<style>
    .hero-gradient {
        background: linear-gradient(135deg, #f97316, #ea580c, #dc2626);
        position: relative;
        overflow: hidden;
    }
    .hero-gradient::before {
        content: '';
        position: absolute;
        top: 10px;
        left: 10px;
        width: 288px;
        height: 288px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
        filter: blur(48px);
    }
    .hero-gradient::after {
        content: '';
        position: absolute;
        bottom: 10px;
        right: 10px;
        width: 384px;
        height: 384px;
        background: rgba(234, 179, 8, 0.2);
        border-radius: 50%;
        filter: blur(48px);
    }
    .search-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        padding: 12px;
    }
    .tab-btn {
        padding: 12px 32px;
        border-radius: 12px;
        font-weight: 700;
        transition: all 0.2s;
        border: none;
        text-decoration: none;
        display: inline-block;
    }
    .tab-btn.active-lost {
        background: white;
        color: #ea580c;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    .tab-btn.active-found {
        background: white;
        color: #16a34a;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    .tab-btn:not(.active-lost):not(.active-found) {
        background: transparent;
        color: white;
    }
    .tab-btn:not(.active-lost):not(.active-found):hover {
        background: rgba(255,255,255,0.1);
    }
    .tabs-container {
        display: inline-flex;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(8px);
        border-radius: 16px;
        padding: 6px;
    }
    .category-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        color: #374151;
    }
    .category-item:hover {
        background: #f9fafb;
        color: #374151;
    }
    .category-item.active {
        background: #fff7ed;
        color: #c2410c;
        font-weight: 600;
    }
    .category-icon {
        font-size: 1.25rem;
    }
    .item-card {
        background: white;
        border-radius: 16px;
        border: 1px solid #f3f4f6;
        overflow: hidden;
        transition: all 0.3s;
        height: 100%;
    }
    .item-card:hover {
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        transform: translateY(-4px);
    }
    .item-image {
        height: 192px;
        background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
        position: relative;
    }
    .item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .badge-lost {
        background: #ef4444;
        color: white;
        padding: 6px 12px;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
    }
    .badge-found {
        background: #22c55e;
        color: white;
        padding: 6px 12px;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
    }
    .badge-pinned {
        background: #f59e0b;
        color: white;
        padding: 6px 12px;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
    }
    .btn-gradient {
        background: linear-gradient(135deg, #f97316, #dc2626);
        color: white;
        font-weight: 700;
        padding: 12px 24px;
        border-radius: 12px;
        border: none;
        transition: all 0.2s;
        box-shadow: 0 4px 6px -1px rgba(249, 115, 22, 0.3);
        text-decoration: none;
        display: inline-block;
    }
    .btn-gradient:hover {
        background: linear-gradient(135deg, #ea580c, #b91c1c);
        color: white;
    }
    .btn-contact {
        background: #fff7ed;
        color: #ea580c;
        font-weight: 600;
        padding: 10px 16px;
        border-radius: 12px;
        border: none;
        width: 100%;
        transition: all 0.2s;
        text-decoration: none;
        display: block;
        text-align: center;
    }
    .btn-contact:hover {
        background: #ffedd5;
        color: #c2410c;
    }
    .sidebar-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid #f3f4f6;
        position: sticky;
        top: 96px;
    }
    .free-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(8px);
        padding: 8px 16px;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 500;
        color: white;
    }
    .empty-state {
        background: white;
        border-radius: 16px;
        padding: 64px;
        text-align: center;
        border: 1px solid #f3f4f6;
    }
    .input-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        z-index: 5;
    }
    .search-input {
        width: 100%;
        padding: 12px 16px 12px 48px;
        border: none;
        background: transparent;
        color: #111827;
        outline: none;
    }
    .search-input::placeholder {
        color: #9ca3af;
    }
    .search-row {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }
    .search-field {
        flex: 1;
        min-width: 200px;
        position: relative;
    }
    @media (max-width: 768px) {
        .search-row {
            flex-direction: column;
        }
        .search-field {
            min-width: 100%;
        }
    }
</style>
@endpush

@section('content')
@php
    $categoryIcons = [
        'documents' => '📄',
        'phones' => '📱',
        'keys' => '🔑',
        'wallets' => '👛',
        'jewelry' => '💍',
        'glasses' => '👓',
        'bags' => '👜',
        'clothes' => '👕',
        'animals' => '🐕',
        'electronics' => '💻',
        'other' => '📦'
    ];
@endphp

<div style="min-height: 100vh; background: #f9fafb;">
    <!-- HERO -->
    <section class="hero-gradient" style="padding: 64px 16px;">
        <div style="position: relative; z-index: 10; max-width: 896px; margin: 0 auto; text-align: center; color: white;">
            <!-- Badge gratuit -->
            <div class="free-badge" style="margin-bottom: 24px;">
                🔑 Module Gratuit
            </div>
            
            <h1 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 16px;">Objets Perdus & Trouvés</h1>
            <p style="font-size: 1.25rem; color: #fed7aa; margin-bottom: 32px;">
                Vous avez perdu ou trouvé quelque chose ? Publiez une annonce gratuitement.
            </p>
            
            <!-- Onglets -->
            <div class="tabs-container" style="margin-bottom: 32px;">
                <a href="{{ route('lost-items.index', array_merge(request()->except('type'), ['type' => 'lost'])) }}" 
                   class="tab-btn {{ request('type', 'lost') == 'lost' ? 'active-lost' : '' }}">
                    🔍 Objets Perdus
                </a>
                <a href="{{ route('lost-items.index', array_merge(request()->except('type'), ['type' => 'found'])) }}" 
                   class="tab-btn {{ request('type') == 'found' ? 'active-found' : '' }}">
                    ✅ Objets Trouvés
                </a>
            </div>
            
            <!-- Barre de recherche -->
            <form action="{{ route('lost-items.index') }}" method="GET" class="search-card" style="max-width: 768px; margin: 0 auto;">
                <input type="hidden" name="type" value="{{ request('type', 'lost') }}">
                <div class="search-row">
                    <div class="search-field">
                        <span class="input-icon">🔍</span>
                        <input type="text" name="search" value="{{ request('search') }}" 
                               placeholder="Description de l'objet..." class="search-input">
                    </div>
                    <div class="search-field">
                        <span class="input-icon">📍</span>
                        <input type="text" name="location" value="{{ request('location') }}" 
                               placeholder="Ville ou lieu..." class="search-input">
                    </div>
                    <button type="submit" class="btn-gradient">
                        Rechercher
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- Contenu principal -->
    <div style="max-width: 1280px; margin: 0 auto; padding: 40px 16px;">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            <!-- Sidebar - Catégories (Desktop) -->
            <aside class="col-lg-3 d-none d-lg-block">
                <div class="sidebar-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <h3 style="font-weight: 700; color: #111827; margin: 0; font-size: 1rem;">Catégories</h3>
                        @if(request('category'))
                            <a href="{{ route('lost-items.index', request()->except('category')) }}" 
                               style="font-size: 0.75rem; color: #ea580c; text-decoration: none;">
                                Effacer
                            </a>
                        @endif
                    </div>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        @foreach($categories as $key => $label)
                            <li>
                                <a href="{{ route('lost-items.index', array_merge(request()->all(), ['category' => $key])) }}" 
                                   class="category-item {{ request('category') == $key ? 'active' : '' }}">
                                    <span class="category-icon">{{ $categoryIcons[$key] ?? '📦' }}</span>
                                    <span style="font-size: 0.875rem;">{{ $label }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Bouton Signaler -->
                    <a href="{{ route('lost-items.create') }}" class="btn-gradient" 
                       style="display: block; text-align: center; margin-top: 24px;">
                        + Signaler un objet
                    </a>
                </div>
            </aside>

            <!-- Liste des objets -->
            <main class="col-lg-9">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <div>
                        <h2 style="font-size: 1.5rem; font-weight: 700; color: #111827; margin: 0;">
                            {{ request('type') == 'found' ? 'Objets trouvés' : 'Objets perdus' }}
                        </h2>
                        <p style="color: #64748b; margin: 0;">{{ $items->total() }} signalement(s)</p>
                    </div>
                    <!-- Mobile: bouton signaler -->
                    <a href="{{ route('lost-items.create') }}" class="btn-gradient d-lg-none">
                        + Signaler
                    </a>
                </div>

                @if($items->count() > 0)
                    <div class="row g-4">
                        @foreach($items as $item)
                            <div class="col-md-6 col-xl-4">
                                <div class="item-card">
                                    <!-- Image -->
                                    <div class="item-image">
                                        @php
                                            $images = is_array($item->images) ? $item->images : json_decode($item->images, true);
                                        @endphp
                                        @if($images && count($images) > 0)
                                            <img src="{{ storage_url($images[0]) }}" alt="{{ $item->title }}">
                                        @else
                                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: #d1d5db;">
                                                {{ $categoryIcons[$item->category] ?? '📦' }}
                                            </div>
                                        @endif
                                        
                                        <!-- Type Badge -->
                                        <span class="{{ $item->type == 'lost' ? 'badge-lost' : 'badge-found' }}" 
                                              style="position: absolute; top: 12px; left: 12px;">
                                            {{ $item->type == 'lost' ? '🔍 Perdu' : '✅ Trouvé' }}
                                        </span>
                                        
                                        <!-- Reward/Pinned Badge -->
                                        @if($item->reward && $item->reward > 0)
                                            <span class="badge-pinned" style="position: absolute; top: 12px; right: 12px;">
                                                🎁 {{ number_format($item->reward, 0, ',', ' ') }}€
                                            </span>
                                        @elseif($item->is_pinned ?? false)
                                            <span class="badge-pinned" style="position: absolute; top: 12px; right: 12px;">
                                                ⭐ Épinglé
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Contenu -->
                                    <div style="padding: 20px;">
                                        <span style="font-size: 0.75rem; color: #9ca3af; display: block; margin-bottom: 8px;">
                                            {{ $categories[$item->category] ?? $item->category }}
                                        </span>
                                        <h3 style="font-weight: 700; color: #111827; font-size: 1.125rem; margin-bottom: 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ $item->title }}
                                        </h3>
                                        <p style="color: #64748b; font-size: 0.875rem; margin-bottom: 12px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                            {{ $item->description }}
                                        </p>
                                        
                                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.875rem; color: #9ca3af;">
                                            <span>📍 {{ $item->location }}</span>
                                            <span>{{ $item->date ? $item->date->format('d/m/Y') : $item->created_at->format('d/m/Y') }}</span>
                                        </div>

                                        @auth
                                            <a href="{{ route('messages.show', $item->user_id) }}" class="btn-contact" style="margin-top: 16px;">
                                                Contacter
                                            </a>
                                        @else
                                            <a href="{{ route('lost-items.show', $item->id) }}" class="btn-contact" style="margin-top: 16px;">
                                                Voir détails
                                            </a>
                                        @endauth
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-5">
                        {{ $items->withQueryString()->links() }}
                    </div>
                @else
                    <div class="empty-state">
                        <div style="font-size: 3rem; margin-bottom: 16px;">
                            {{ request('type') == 'found' ? '📦' : '🔍' }}
                        </div>
                        <h3 style="font-size: 1.25rem; font-weight: 700; color: #111827; margin-bottom: 8px;">
                            Aucun objet signalé
                        </h3>
                        <p style="color: #64748b; margin-bottom: 24px;">
                            Soyez le premier à publier un signalement dans cette catégorie.
                        </p>
                        <a href="{{ route('lost-items.create') }}" style="color: #ea580c; font-weight: 600; text-decoration: none;">
                            Signaler un objet →
                        </a>
                    </div>
                @endif
            </main>
        </div>
    </div>
</div>
@endsection

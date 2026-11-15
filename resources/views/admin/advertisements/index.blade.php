@extends('admin.layouts.app')

@section('title', 'Gestion des Publicités')

@section('content')
<div class="row mb-4">
    <div class="col">
        <h2 class="h4 fw-bold">
            <i class="fas fa-ad text-warning me-2"></i>Gestion des Publicités
        </h2>
        <p class="text-muted mb-0">Gérez les espaces publicitaires de la plateforme</p>
    </div>
    <div class="col-auto">
        <a href="{{ route('admin.advertisements.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Nouvelle publicité
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Statistiques rapides -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="h3 mb-1 text-primary">{{ $advertisements->total() }}</div>
                <small class="text-muted">Total publicités</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="h3 mb-1 text-success">{{ $advertisements->where('is_active', true)->count() }}</div>
                <small class="text-muted">Actives</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="h3 mb-1 text-info">{{ $advertisements->sum('impressions') }}</div>
                <small class="text-muted">Impressions</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="h3 mb-1 text-warning">{{ $advertisements->sum('clicks') }}</div>
                <small class="text-muted">Clics</small>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="border-0 ps-4">Image</th>
                        <th class="border-0">Titre</th>
                        <th class="border-0">Position</th>
                        <th class="border-0 text-center">Priorité</th>
                        <th class="border-0 text-center">Statut</th>
                        <th class="border-0 text-center">Impressions</th>
                        <th class="border-0 text-center">Clics</th>
                        <th class="border-0 text-center">CTR</th>
                        <th class="border-0 text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($advertisements as $ad)
                    <tr>
                        <td class="ps-4">
                            @if($ad->image)
                                <img src="{{ storage_url($ad->image) }}" alt="{{ $ad->title }}" 
                                     style="width: 60px; height: 40px; object-fit: cover; border-radius: 6px;">
                            @else
                                <div class="bg-light d-flex align-items-center justify-content-center" 
                                     style="width: 60px; height: 40px; border-radius: 6px;">
                                    <i class="fas fa-image text-muted"></i>
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold">{{ Str::limit($ad->title, 30) }}</div>
                            @if($ad->link)
                                <small class="text-muted">{{ Str::limit($ad->link, 40) }}</small>
                            @endif
                        </td>
                        <td>
                            @if($ad->position === 'sidebar')
                                <span class="badge bg-info">Sidebar</span>
                            @elseif($ad->position === 'banner')
                                <span class="badge bg-primary">Bannière</span>
                            @else
                                <span class="badge bg-warning">Popup</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary">{{ $ad->priority }}</span>
                        </td>
                        <td class="text-center">
                            <form action="{{ route('admin.advertisements.toggle', $ad->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $ad->is_active ? 'btn-success' : 'btn-outline-secondary' }}">
                                    <i class="fas {{ $ad->is_active ? 'fa-check' : 'fa-times' }}"></i>
                                    {{ $ad->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td class="text-center">{{ number_format($ad->impressions) }}</td>
                        <td class="text-center">{{ number_format($ad->clicks) }}</td>
                        <td class="text-center">
                            @if($ad->impressions > 0)
                                {{ number_format(($ad->clicks / $ad->impressions) * 100, 2) }}%
                            @else
                                0%
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('admin.advertisements.edit', $ad->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.advertisements.delete', $ad->id) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette publicité ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-ad fa-3x mb-3 opacity-25"></i>
                                <p class="mb-2">Aucune publicité créée</p>
                                <a href="{{ route('admin.advertisements.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-2"></i>Créer une publicité
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    @if($advertisements->hasPages())
    <div class="card-footer bg-white border-0">
        {{ $advertisements->links('admin.partials.pagination') }}
    </div>
    @endif
</div>
@endsection

{{-- My Ads Partial --}}
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Mes annonces</h1>
            <p class="text-muted mb-0">Gérez toutes vos annonces publiées</p>
        </div>
        <a href="{{ route('ads.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Publier une offre
        </a>
    </div>

    @if($ads->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-bullhorn fa-3x text-muted mb-3 opacity-50"></i>
                <h5 class="fw-bold text-muted">Aucune annonce pour le moment</h5>
                <p class="text-muted">Publiez votre première annonce pour commencer.</p>
                <a href="{{ route('ads.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Publier une annonce
                </a>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Annonce</th>
                            <th>Catégorie</th>
                            <th>Statut</th>
                            <th>Vues</th>
                            <th>Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ads as $ad)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    @if($ad->photos && count($ad->photos) > 0)
                                        <img src="{{ storage_url($ad->photos[0]) }}" alt="" 
                                             class="rounded" style="width: 45px; height: 45px; object-fit: cover;">
                                    @else
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                             style="width: 45px; height: 45px;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <a href="{{ route('ads.show', $ad->id) }}" class="fw-semibold text-decoration-none text-dark">
                                            {{ Str::limit($ad->title, 40) }}
                                        </a>
                                        @if($ad->is_boosted && $ad->boost_end > now())
                                            <span class="badge bg-warning text-dark ms-1" style="font-size: 0.65rem;"><i class="fas fa-rocket"></i> Boosté</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ $ad->category }}</span></td>
                            <td>
                                <span class="badge {{ $ad->status == 'active' ? 'bg-success' : ($ad->status == 'pending' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                    {{ $ad->status == 'active' ? 'Active' : ($ad->status == 'pending' ? 'En attente' : 'Expirée') }}
                                </span>
                            </td>
                            <td><i class="fas fa-eye text-muted me-1" style="font-size: 0.8rem;"></i>{{ $ad->views ?? 0 }}</td>
                            <td class="text-muted small">{{ $ad->created_at->format('d/m/Y') }}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="{{ route('ads.show', $ad->id) }}" class="btn btn-sm btn-outline-secondary" title="Voir"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('ads.edit', $ad->id) }}" class="btn btn-sm btn-outline-primary" title="Modifier"><i class="fas fa-pen"></i></a>
                                    <a href="{{ route('boost.show', $ad->id) }}" class="btn btn-sm btn-outline-warning" title="Booster"><i class="fas fa-rocket"></i></a>
                                    <form action="{{ route('ads.destroy', $ad->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette annonce ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@extends('admin.layouts.app')
@section('title', 'Signalements')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="fas fa-flag text-danger me-2"></i> Signalements</h2>
            <p class="text-muted mb-0">Gérez les publications signalées par les utilisateurs</p>
        </div>
    </div>

    {{-- Stats cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="mb-0 fw-bold">{{ $stats['total'] }}</h3>
                    <small class="text-muted">Total signalements</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card border-0 shadow-sm" style="border-left: 4px solid #f59e0b !important;">
                <div class="card-body text-center">
                    <h3 class="mb-0 fw-bold text-warning">{{ $stats['pending'] }}</h3>
                    <small class="text-muted">En attente</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card border-0 shadow-sm" style="border-left: 4px solid #10b981 !important;">
                <div class="card-body text-center">
                    <h3 class="mb-0 fw-bold text-success">{{ $stats['resolved'] }}</h3>
                    <small class="text-muted">Résolus</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card border-0 shadow-sm" style="border-left: 4px solid #94a3b8 !important;">
                <div class="card-body text-center">
                    <h3 class="mb-0 fw-bold text-secondary">{{ $stats['dismissed'] }}</h3>
                    <small class="text-muted">Rejetés</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Statut</label>
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Résolus</option>
                        <option value="dismissed" {{ request('status') === 'dismissed' ? 'selected' : '' }}>Rejetés</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Raison</label>
                    <select name="reason" class="form-select">
                        <option value="">Toutes les raisons</option>
                        <option value="spam" {{ request('reason') === 'spam' ? 'selected' : '' }}>Spam</option>
                        <option value="fausse_annonce" {{ request('reason') === 'fausse_annonce' ? 'selected' : '' }}>Fausse annonce</option>
                        <option value="contenu_inapproprie" {{ request('reason') === 'contenu_inapproprie' ? 'selected' : '' }}>Contenu inapproprié</option>
                        <option value="harcelement" {{ request('reason') === 'harcelement' ? 'selected' : '' }}>Harcèlement</option>
                        <option value="usurpation" {{ request('reason') === 'usurpation' ? 'selected' : '' }}>Usurpation d'identité</option>
                        <option value="contenu_illegal" {{ request('reason') === 'contenu_illegal' ? 'selected' : '' }}>Contenu illégal</option>
                        <option value="doublon" {{ request('reason') === 'doublon' ? 'selected' : '' }}>Doublon</option>
                        <option value="mauvaise_categorie" {{ request('reason') === 'mauvaise_categorie' ? 'selected' : '' }}>Mauvaise catégorie</option>
                        <option value="autre" {{ request('reason') === 'autre' ? 'selected' : '' }}>Autre</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Rechercher</label>
                    <input type="text" name="search" class="form-control" placeholder="Nom, titre d'annonce..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Reports table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($reports->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5 class="text-muted">Aucun signalement</h5>
                    <p class="text-muted">Tout est en ordre !</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px">#</th>
                                <th>Publication signalée</th>
                                <th>Signalé par</th>
                                <th>Raison</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th style="width:180px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reports as $report)
                            <tr>
                                <td class="align-middle"><strong>{{ $report->id }}</strong></td>
                                <td class="align-middle">
                                    @if($report->ad)
                                        <div class="d-flex align-items-center gap-2">
                                            @if($report->ad->user && $report->ad->user->avatar)
                                                <img src="{{ storage_url($report->ad->user->avatar) }}" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">
                                            @else
                                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:0.75rem;">{{ strtoupper(substr($report->ad->user->name ?? 'U', 0, 1)) }}</div>
                                            @endif
                                            <div>
                                                <div class="fw-semibold" style="font-size:0.88rem;">{{ Str::limit($report->ad->title, 40) }}</div>
                                                <small class="text-muted">par <x-admin.user-link :user="$report->ad->user" fallback="Inconnu" :fiche="true" /></small>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted fst-italic">Annonce supprimée</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @if($report->reporter)
                                        <div class="fw-semibold" style="font-size:0.88rem;"><x-admin.user-link :user="$report->reporter" fallback="Inconnu" :fiche="true" /></div>
                                        <small class="text-muted">{{ $report->reporter->email }}</small>
                                    @else
                                        <span class="text-muted">Utilisateur supprimé</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @php
                                        $reasonLabels = [
                                            'spam' => ['Spam', 'bg-warning text-dark'],
                                            'fausse_annonce' => ['Fausse annonce', 'bg-danger'],
                                            'contenu_inapproprie' => ['Contenu inapproprié', 'bg-danger'],
                                            'harcelement' => ['Harcèlement', 'bg-danger'],
                                            'usurpation' => ['Usurpation', 'bg-dark'],
                                            'contenu_illegal' => ['Contenu illégal', 'bg-danger'],
                                            'doublon' => ['Doublon', 'bg-info'],
                                            'mauvaise_categorie' => ['Mauvaise catégorie', 'bg-secondary'],
                                            'autre' => ['Autre', 'bg-secondary'],
                                        ];
                                        $label = $reasonLabels[$report->reason] ?? [$report->reason, 'bg-secondary'];
                                    @endphp
                                    <span class="badge {{ $label[1] }}">{{ $label[0] }}</span>
                                </td>
                                <td class="align-middle">
                                    @if($report->status === 'pending')
                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> En attente</span>
                                    @elseif($report->status === 'resolved')
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i> Résolu</span>
                                    @else
                                        <span class="badge bg-secondary"><i class="fas fa-times me-1"></i> Rejeté</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <span style="font-size:0.85rem;">{{ $report->created_at->format('d/m/Y') }}</span><br>
                                    <small class="text-muted">{{ $report->created_at->diffForHumans() }}</small>
                                </td>
                                <td class="align-middle">
                                    <div class="d-flex gap-1 flex-wrap">
                                        <a href="{{ route('admin.reports.show', $report->id) }}" class="btn btn-sm btn-outline-primary" title="Détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($report->status === 'pending')
                                        <form method="POST" action="{{ route('admin.reports.resolve', $report->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Résoudre"><i class="fas fa-check"></i></button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.reports.dismiss', $report->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-secondary" title="Rejeter"><i class="fas fa-times"></i></button>
                                        </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.reports.delete', $report->id) }}" class="d-inline" onsubmit="return confirm('Supprimer ce signalement ?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center p-3">
                    {{ $reports->appends(request()->query())->links('admin.partials.pagination') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

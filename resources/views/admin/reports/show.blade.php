@extends('admin.layouts.app')
@section('title', 'Détail du signalement #' . $report->id)

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <a href="{{ route('admin.reports') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Retour aux signalements
        </a>
    </div>

    <div class="row">
        {{-- Left: Report details --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                    <h5 class="mb-0"><i class="fas fa-flag text-danger me-2"></i> Signalement #{{ $report->id }}</h5>
                    @if($report->status === 'pending')
                        <span class="badge bg-warning text-dark fs-6"><i class="fas fa-clock me-1"></i> En attente</span>
                    @elseif($report->status === 'resolved')
                        <span class="badge bg-success fs-6"><i class="fas fa-check me-1"></i> Résolu</span>
                    @else
                        <span class="badge bg-secondary fs-6"><i class="fas fa-times me-1"></i> Rejeté</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Raison du signalement</h6>
                            @php
                                $reasonLabels = [
                                    'spam' => ['Spam ou publicité non sollicitée', 'fas fa-ban', '#f59e0b'],
                                    'fausse_annonce' => ['Fausse annonce ou arnaque', 'fas fa-mask', '#ef4444'],
                                    'contenu_inapproprie' => ['Contenu inapproprié ou offensant', 'fas fa-exclamation-triangle', '#ef4444'],
                                    'harcelement' => ['Harcèlement ou intimidation', 'fas fa-user-slash', '#ef4444'],
                                    'usurpation' => ['Usurpation d\'identité', 'fas fa-user-secret', '#1e293b'],
                                    'contenu_illegal' => ['Contenu illégal', 'fas fa-gavel', '#ef4444'],
                                    'doublon' => ['Publication en double', 'fas fa-clone', '#3b82f6'],
                                    'mauvaise_categorie' => ['Mauvaise catégorie', 'fas fa-folder-minus', '#64748b'],
                                    'autre' => ['Autre raison', 'fas fa-ellipsis-h', '#64748b'],
                                ];
                                $info = $reasonLabels[$report->reason] ?? [$report->reason, 'fas fa-flag', '#64748b'];
                            @endphp
                            <div class="d-flex align-items-center gap-2 p-3 rounded" style="background: #fef2f2;">
                                <i class="{{ $info[1] }}" style="color: {{ $info[2] }}; font-size: 1.2rem;"></i>
                                <strong style="color: {{ $info[2] }};">{{ $info[0] }}</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Date du signalement</h6>
                            <div class="p-3 rounded" style="background: #f8fafc;">
                                <i class="fas fa-calendar me-2 text-muted"></i>
                                {{ $report->created_at->format('d/m/Y à H:i') }}
                                <span class="text-muted">({{ $report->created_at->diffForHumans() }})</span>
                            </div>
                        </div>
                    </div>

                    @if($report->message)
                    <div class="mb-4">
                        <h6 class="text-muted mb-1">Message complémentaire</h6>
                        <div class="p-3 rounded" style="background: #f8fafc; border-left: 4px solid #e41e3f;">
                            <p class="mb-0" style="white-space: pre-wrap;">{{ $report->message }}</p>
                        </div>
                    </div>
                    @endif

                    {{-- Ad details --}}
                    <h6 class="text-muted mb-2 mt-4"><i class="fas fa-bullhorn me-1"></i> Publication signalée</h6>
                    @if($report->ad)
                    <div class="card border">
                        <div class="card-body">
                            <div class="d-flex align-items-start gap-3">
                                @if($report->ad->user && $report->ad->user->avatar)
                                    <img src="{{ storage_url($report->ad->user->avatar) }}" class="rounded-circle" style="width:48px;height:48px;object-fit:cover;">
                                @else
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:48px;height:48px;font-size:1rem;font-weight:700;">{{ strtoupper(substr($report->ad->user->name ?? 'U', 0, 1)) }}</div>
                                @endif
                                <div class="flex-1">
                                    <h5 class="mb-1">{{ $report->ad->title }}</h5>
                                    <p class="text-muted mb-2" style="font-size:0.88rem;">par <strong><x-admin.user-link :user="$report->ad?->user" fallback="Inconnu" :fiche="true" /></strong> · {{ $report->ad->created_at->diffForHumans() }} · {{ $report->ad->location ?? 'N/A' }}</p>
                                    <p class="mb-2">{{ Str::limit($report->ad->description, 500) }}</p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        @if($report->ad->price)
                                            <span class="badge bg-primary">{{ number_format($report->ad->price, 0, ',', ' ') }} €</span>
                                        @endif
                                        @if($report->ad->category)
                                            <span class="badge bg-info">{{ $report->ad->category }}</span>
                                        @endif
                                        @if($report->ad->is_urgent)
                                            <span class="badge bg-danger"><i class="fas fa-bolt"></i> Urgent</span>
                                        @endif
                                        @if($report->ad->is_boosted)
                                            <span class="badge bg-warning text-dark"><i class="fas fa-rocket"></i> Boosté</span>
                                        @endif
                                    </div>
                                    @php
                                        $photos = $report->ad->photos ?? [];
                                        if (is_string($photos)) { $photos = json_decode($photos, true) ?: []; }
                                    @endphp
                                    @if(!empty($photos))
                                    <div class="d-flex gap-2 mt-3 flex-wrap">
                                        @foreach(array_slice($photos, 0, 4) as $photo)
                                            <img src="{{ storage_url($photo) }}" class="rounded" style="width:100px;height:80px;object-fit:cover;" onerror="this.style.display='none'">
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-3">
                                <a href="{{ route('ads.show', $report->ad->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-external-link-alt me-1"></i> Voir l'annonce
                                </a>
                                <a href="{{ route('admin.ads.show', $report->ad->id) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-cog me-1"></i> Gérer dans l'admin
                                </a>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-secondary">
                        <i class="fas fa-info-circle me-1"></i> L'annonce signalée a été supprimée.
                    </div>
                    @endif

                    {{-- Nombre total de signalements pour cette annonce --}}
                    @if($report->ad)
                    @php
                        $totalReportsForAd = \App\Models\Report::where('ad_id', $report->ad_id)->count();
                        $otherReports = \App\Models\Report::where('ad_id', $report->ad_id)->where('id', '!=', $report->id)->with('reporter')->latest()->limit(5)->get();
                    @endphp
                    @if($totalReportsForAd > 1)
                    <div class="alert alert-danger mt-3">
                        <i class="fas fa-exclamation-circle me-1"></i>
                        <strong>Attention :</strong> cette annonce a reçu {{ $totalReportsForAd }} signalement(s) au total.
                    </div>
                    @if($otherReports->isNotEmpty())
                    <h6 class="text-muted mt-3">Autres signalements pour cette annonce</h6>
                    <ul class="list-group">
                        @foreach($otherReports as $other)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><x-admin.user-link :user="$other->reporter" fallback="Inconnu" :fiche="true" /></strong> —
                                {{ $reasonLabels[$other->reason][0] ?? $other->reason }}
                                <small class="text-muted ms-2">{{ $other->created_at->diffForHumans() }}</small>
                            </div>
                            <a href="{{ route('admin.reports.show', $other->id) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                    @endif
                    @endif
                </div>
            </div>
        </div>

        {{-- Right: Reporter + Actions --}}
        <div class="col-lg-4">
            {{-- Reporter info --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0"><i class="fas fa-user me-2"></i> Signalé par</h6>
                </div>
                <div class="card-body text-center">
                    @if($report->reporter)
                        @if($report->reporter->avatar)
                            <img src="{{ storage_url($report->reporter->avatar) }}" class="rounded-circle mb-2" style="width:64px;height:64px;object-fit:cover;">
                        @else
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-2" style="width:64px;height:64px;font-size:1.3rem;font-weight:700;">{{ strtoupper(substr($report->reporter->name, 0, 1)) }}</div>
                        @endif
                        <h6 class="mb-0"><x-admin.user-link :user="$report->reporter" fallback="Inconnu" :fiche="true" /></h6>
                        <small class="text-muted">{{ $report->reporter->email }}</small>
                        <div class="mt-2">
                            <a href="{{ route('admin.users.show', $report->reporter->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-user me-1"></i> Voir le profil
                            </a>
                        </div>
                    @else
                        <p class="text-muted">Utilisateur supprimé</p>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            @if($report->status === 'pending')
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0"><i class="fas fa-gavel me-2"></i> Actions</h6>
                </div>
                <div class="card-body d-grid gap-2">
                    <form method="POST" action="{{ route('admin.reports.resolve', $report->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-check me-1"></i> Marquer comme résolu
                        </button>
                    </form>
                    @if($report->ad)
                    <form method="POST" action="{{ route('admin.reports.resolve', $report->id) }}" onsubmit="return confirm('Êtes-vous sûr ? L\'annonce sera supprimée définitivement.')">
                        @csrf
                        <input type="hidden" name="delete_ad" value="1">
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-trash me-1"></i> Résoudre + Supprimer l'annonce
                        </button>
                    </form>
                    @endif
                    <form method="POST" action="{{ route('admin.reports.dismiss', $report->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-times me-1"></i> Rejeter le signalement
                        </button>
                    </form>
                </div>
            </div>
            @endif

            {{-- Delete --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.reports.delete', $report->id) }}" onsubmit="return confirm('Supprimer définitivement ce signalement ?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="fas fa-trash me-1"></i> Supprimer le signalement
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

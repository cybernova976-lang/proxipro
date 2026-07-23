@extends('layouts.app')

@section('title', 'Mon système de points - Lunamars')

@section('content')
<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 fw-bold">Mon système de points</h1>
            <p class="text-muted">Gagnez des points, montez de niveau et collectionnez des badges !</p>
        </div>
    </div>

    <!-- Carte de progression -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4 text-center">
                    <div class="display-4 fw-bold text-primary">{{ $user->level }}</div>
                    <div class="text-muted">Niveau</div>
                </div>
                <div class="col-md-8">
                    <h5 class="mb-3">Progression vers le niveau {{ $user->level + 1 }}</h5>
                    <div class="progress mb-2" style="height: 20px;">
                        @php
                            $currentLevelPoints = ($user->level - 1) * 100;
                            $nextLevelPoints = $user->level * 100;
                            $progress = min(100, max(0, ($user->total_points - $currentLevelPoints) / max(1, ($nextLevelPoints - $currentLevelPoints)) * 100));
                        @endphp
                        <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: {{ $progress }}%">
                            {{ number_format($progress, 1) }}%
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>{{ $user->total_points }} points</span>
                        <span>{{ $nextLevelPoints }} points requis</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques de points -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 bg-primary bg-gradient text-white">
                <div class="card-body text-center">
                    <h6 class="card-subtitle mb-2">Points totaux</h6>
                    <h2 class="card-title mb-0">{{ $user->total_points }}</h2>
                    <small>depuis l'inscription</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-0 bg-success bg-gradient text-white">
                <div class="card-body text-center">
                    <h6 class="card-subtitle mb-2">Points disponibles</h6>
                    <h2 class="card-title mb-0">{{ $user->available_points }}</h2>
                    <small>à dépenser</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-0 bg-warning bg-gradient text-white">
                <div class="card-body text-center">
                    <h6 class="card-subtitle mb-2">Points de parrainage</h6>
                    <h2 class="card-title mb-0">{{ $referralStats['points_earned'] }}</h2>
                    <small>bonus réellement validés</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card border-0 bg-info bg-gradient text-white">
                <div class="card-body text-center">
                    <h6 class="card-subtitle mb-2">Badges</h6>
                    <h2 class="card-title mb-0">{{ $badges->count() }}</h2>
                    <small>badges gagnés</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Comment gagner des points -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0"><i class="fas fa-coins text-warning me-2"></i>Gagner des points</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                            <div><i class="fas fa-envelope-circle-check text-primary me-2"></i><strong>Inscription validée</strong><p class="mb-0 text-muted small">Après validation de l’adresse e-mail</p></div>
                            <span class="badge bg-primary text-nowrap">+5 points</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                            <div><i class="fas fa-shield-check text-success me-2"></i><strong>Profil vérifié</strong><p class="mb-0 text-muted small">Après approbation par l’administration</p></div>
                            <span class="badge bg-success text-nowrap">+50 points</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                            <div><i class="fas fa-user-friends text-info me-2"></i><strong>Parrainage réussi</strong><p class="mb-0 text-muted small">Après le premier achat validé du filleul</p></div>
                            <span class="badge bg-info text-nowrap">+50 / +20</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                            <div><i class="fas fa-cart-shopping text-warning me-2"></i><strong>Achat d’un pack</strong><p class="mb-0 text-muted small">Crédit immédiat après paiement confirmé</p></div>
                            <a href="{{ route('pricing.index') }}" class="btn btn-sm btn-outline-primary text-nowrap">Voir les packs</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Badges -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-medal text-warning me-2"></i>Mes badges</h5>
                    <span class="badge bg-secondary">{{ $badges->count() }} / 6</span>
                </div>
                <div class="card-body">
                    @if($badges->isEmpty())
                        <div class="text-center py-4">
                            <i class="fas fa-medal fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Aucun badge encore. Gagnez des points pour débloquer des badges !</p>
                        </div>
                    @else
                        <div class="row">
                            @foreach($badges as $badge)
                            <div class="col-6 mb-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="mb-2">
                                        <i class="{{ $badge->icon }} fa-2x text-{{ $badge->color }}"></i>
                                    </div>
                                    <h6 class="mb-1">{{ $badge->name }}</h6>
                                    <p class="small text-muted mb-0">{{ $badge->description }}</p>
                                    <small class="text-muted">Obtenu le {{ \Carbon\Carbon::parse($badge->pivot->earned_at)->format('d/m/Y') }}</small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-user-friends text-info me-2"></i>Parrainage</h5>
            <span class="badge bg-info">+50 / +20 points</span>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded h-100">
                        <div class="text-muted small mb-1">Mon code</div>
                        <div class="fw-bold fs-5">{{ $referralStats['code'] ?: 'Bientôt disponible' }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded h-100">
                        <div class="text-muted small mb-1">Filleuls inscrits</div>
                        <div class="fw-bold fs-5">{{ $referralStats['referred_count'] }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded h-100">
                        <div class="text-muted small mb-1">Points gagnés</div>
                        <div class="fw-bold fs-5">{{ number_format($referralStats['points_earned'], 0, ',', ' ') }}</div>
                    </div>
                </div>
            </div>

            <label for="referral-link" class="form-label fw-semibold">Mon lien d'invitation</label>
            <div class="input-group mb-3">
                <input id="referral-link" type="text" class="form-control" value="{{ $referralStats['link'] }}" readonly>
                <button class="btn btn-outline-primary" type="button" id="copy-referral-link">
                    <i class="fas fa-copy me-1"></i>Copier
                </button>
            </div>

            <p class="text-muted small mb-3">Le filleul reçoit 20 points après son premier achat validé. Le parrain reçoit 50 points au même moment.</p>

            @if($referralHistory->isEmpty())
                <div class="text-center py-3 bg-light rounded">
                    <i class="fas fa-gift fa-2x text-muted mb-2"></i>
                    <p class="mb-0 text-muted">Aucun bonus de parrainage pour le moment.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Utilisateur lié</th>
                                <th class="text-end">Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($referralHistory as $reward)
                                @php
                                    $isReferrerReward = $reward->referrer_user_id === $user->id && $reward->reward_type === 'first_purchase_referrer';
                                    $linkedUser = $isReferrerReward ? optional($reward->referee)->name : optional($reward->referrer)->name;
                                @endphp
                                <tr>
                                    <td>{{ optional($reward->granted_at)->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $isReferrerReward ? 'primary' : 'success' }}">
                                            {{ $isReferrerReward ? 'Bonus parrain' : 'Bonus filleul' }}
                                        </span>
                                    </td>
                                    <td>{{ $linkedUser ?: 'Utilisateur supprimé' }}</td>
                                    <td class="text-end text-success fw-bold">+{{ $reward->points }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Dernières transactions -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-history text-secondary me-2"></i>Dernières transactions</h5>
            <a href="{{ route('points.transactions') }}" class="btn btn-sm btn-outline-primary">
                Voir tout <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body">
            @if($transactions->isEmpty())
                <p class="text-muted text-center py-3">Aucune transaction pour le moment.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th class="text-end">Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $transaction->description }}</td>
                                <td>
                                    @php
                                        $typeColors = [
                                            'share' => 'primary',
                                            'daily' => 'success',
                                            'level_up' => 'warning',
                                            'purchase' => 'info',
                                            'referral_bonus' => 'primary',
                                            'subscription' => 'danger',
                                            'welcome' => 'secondary'
                                        ];
                                        $color = $typeColors[$transaction->type] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $color }}">
                                        {{ $transaction->type }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @if($transaction->points > 0)
                                        <span class="text-success fw-bold">+{{ $transaction->points }}</span>
                                    @else
                                        <span class="text-danger fw-bold">{{ $transaction->points }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const copyButton = document.getElementById('copy-referral-link');
    const referralInput = document.getElementById('referral-link');

    if (copyButton && referralInput) {
        copyButton.addEventListener('click', async function() {
            try {
                await navigator.clipboard.writeText(referralInput.value);
            } catch (error) {
                referralInput.select();
                document.execCommand('copy');
            }

            copyButton.innerHTML = '<i class="fas fa-check me-1"></i>Copié';
            setTimeout(() => {
                copyButton.innerHTML = '<i class="fas fa-copy me-1"></i>Copier';
            }, 1800);
        });
    }

});
</script>
@endsection

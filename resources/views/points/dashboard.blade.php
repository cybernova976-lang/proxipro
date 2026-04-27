@extends('layouts.app')

@section('title', 'Mon système de points - ProxiPro')

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
                    <h6 class="card-subtitle mb-2">Points journaliers</h6>
                    <h2 class="card-title mb-0">{{ $user->daily_points }}/10</h2>
                    <small>réinitialisés chaque jour</small>
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
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-share-alt text-primary me-2"></i>
                                <strong>Partager une annonce</strong>
                                <p class="mb-0 text-muted small">Sur les réseaux sociaux</p>
                            </div>
                            <span class="badge bg-primary">+5 points</span>
                        </div>
                        
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-heart text-danger me-2"></i>
                                <strong>Liker une annonce</strong>
                                <p class="mb-0 text-muted small">Jusqu'à 10 points/jour</p>
                            </div>
                            <span class="badge bg-success">+1 point</span>
                        </div>
                        
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-comment text-info me-2"></i>
                                <strong>Commenter</strong>
                                <p class="mb-0 text-muted small">Jusqu'à 10 points/jour</p>
                            </div>
                            <span class="badge bg-success">+1 point</span>
                        </div>
                        
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-bullhorn text-warning me-2"></i>
                                <strong>Publier une annonce</strong>
                                <p class="mb-0 text-muted small">Par publication valide</p>
                            </div>
                            <span class="badge bg-warning text-dark">+10 points</span>
                        </div>
                        
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-star text-success me-2"></i>
                                <strong>Monter de niveau</strong>
                                <p class="mb-0 text-muted small">Bonus à chaque niveau</p>
                            </div>
                            <span class="badge bg-info">Niveau × 10 points</span>
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

    <!-- Partager pour gagner des points -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0">
            <h5 class="mb-0"><i class="fas fa-share text-primary me-2"></i>Partager pour gagner 5 points</h5>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">Partagez votre annonce favorite sur les réseaux sociaux :</p>
            <div class="d-flex flex-wrap gap-2" id="share-buttons">
                <button class="btn btn-outline-primary share-btn" data-platform="facebook">
                    <i class="fab fa-facebook me-2"></i>Facebook
                </button>
                <button class="btn btn-outline-info share-btn" data-platform="twitter">
                    <i class="fab fa-twitter me-2"></i>Twitter
                </button>
                <button class="btn btn-outline-primary share-btn" data-platform="linkedin">
                    <i class="fab fa-linkedin me-2"></i>LinkedIn
                </button>
                <button class="btn btn-outline-success share-btn" data-platform="whatsapp">
                    <i class="fab fa-whatsapp me-2"></i>WhatsApp
                </button>
                <button class="btn btn-outline-primary share-btn" data-platform="telegram">
                    <i class="fab fa-telegram me-2"></i>Telegram
                </button>
            </div>
            <div id="share-result" class="mt-3"></div>
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

    // Gestion des partages
    document.querySelectorAll('.share-btn').forEach(button => {
        button.addEventListener('click', function() {
            const platform = this.dataset.platform;
            const btn = this;
            
            // Désactiver le bouton pendant la requête
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>En cours...';
            
            fetch('{{ route("points.share") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ platform: platform })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('share-result').innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>${data.message}<br>
                            <strong>Points disponibles :</strong> ${data.points} | 
                            <strong>Points totaux :</strong> ${data.total_points}
                        </div>
                    `;
                    
                    // Recharger la page après 2 secondes pour mettre à jour les stats
                    setTimeout(() => location.reload(), 2000);
                } else {
                    document.getElementById('share-result').innerHTML = `
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>${data.message}</div>
                    `;
                    btn.disabled = false;
                    btn.innerHTML = `<i class="fab fa-${platform} me-2"></i>${platform.charAt(0).toUpperCase() + platform.slice(1)}`;
                }
            })
            .catch(error => {
                document.getElementById('share-result').innerHTML = `
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>Erreur lors du partage</div>
                `;
                btn.disabled = false;
                btn.innerHTML = `<i class="fab fa-${platform} me-2"></i>${platform.charAt(0).toUpperCase() + platform.slice(1)}`;
            });
        });
    });
});
</script>
@endsection

@extends('layouts.app')

@section('title', 'Historique des Points - Lunamars')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="display-6 fw-bold text-white mb-2">
                <i class="fas fa-history me-3 text-primary"></i>Historique des Points
            </h1>
            <p class="text-white-50">Tous vos mouvements de points</p>
        </div>
        <div>
            <span class="badge bg-success fs-5 px-4 py-2">
                <i class="fas fa-wallet me-2"></i>{{ number_format($userPoints, 0, ',', ' ') }} points
            </span>
        </div>
    </div>

    <div class="card border-0 shadow-lg" style="border-radius: 20px; background: rgba(255,255,255,0.05);">
        <div class="card-body p-0">
            @if($transactions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="border-0 py-3">Date</th>
                                <th class="border-0 py-3">Type</th>
                                <th class="border-0 py-3">Description</th>
                                <th class="border-0 py-3 text-end">Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $transaction)
                                <tr>
                                    <td class="py-3">
                                        <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                        {{ $transaction->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="py-3">
                                        @switch($transaction->type)
                                            @case('purchase')
                                                <span class="badge bg-success">
                                                    <i class="fas fa-shopping-cart me-1"></i>Achat
                                                </span>
                                                @break
                                            @case('subscription_bonus')
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-crown me-1"></i>Bonus Abonnement
                                                </span>
                                                @break
                                            @case('daily_engagement')
                                                <span class="badge bg-info">
                                                    <i class="fas fa-calendar-check me-1"></i>Engagement quotidien
                                                </span>
                                                @break
                                            @case('share_sent')
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-arrow-right me-1"></i>Transfert envoyé
                                                </span>
                                                @break
                                            @case('share_received')
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-arrow-left me-1"></i>Transfert reçu
                                                </span>
                                                @break
                                            @case('ad_creation')
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-bullhorn me-1"></i>Création annonce
                                                </span>
                                                @break
                                            @case('badge_earned')
                                                <span class="badge bg-purple" style="background: #7c3aed;">
                                                    <i class="fas fa-medal me-1"></i>Badge obtenu
                                                </span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-coins me-1"></i>{{ ucfirst($transaction->type) }}
                                                </span>
                                        @endswitch
                                    </td>
                                    <td class="py-3 text-white-50">
                                        {{ $transaction->description }}
                                    </td>
                                    <td class="py-3 text-end">
                                        @if($transaction->points > 0)
                                            <span class="text-success fw-bold">
                                                +{{ number_format($transaction->points, 0, ',', ' ') }}
                                            </span>
                                        @else
                                            <span class="text-danger fw-bold">
                                                {{ number_format($transaction->points, 0, ',', ' ') }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="p-4">
                    {{ $transactions->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-history fa-4x text-white-50 mb-4"></i>
                    <h4 class="text-white mb-2">Aucune transaction</h4>
                    <p class="text-white-50 mb-4">Vous n'avez pas encore de transactions de points.</p>
                    <a href="{{ route('buy-points') }}" class="btn btn-primary">
                        <i class="fas fa-coins me-2"></i>Acheter des Points
                    </a>
                </div>
            @endif
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="{{ route('buy-points') }}" class="btn btn-outline-light me-2">
            <i class="fas fa-coins me-2"></i>Acheter des Points
        </a>
        <a href="{{ route('points.dashboard') }}" class="btn btn-outline-light">
            <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord Points
        </a>
    </div>
</div>
@endsection

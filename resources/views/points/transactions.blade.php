@extends('layouts.app')

@section('title', 'Historique des transactions - Lunamars')

@section('content')
<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('points.dashboard') }}">Mes Points</a></li>
                    <li class="breadcrumb-item active">Historique</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold">Historique des transactions</h1>
            <p class="text-muted">Tous vos gains et dépenses de points</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('points.dashboard') }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Retour
            </a>
        </div>
    </div>

    <!-- Résumé rapide -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-0 bg-primary bg-gradient text-white">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">Points totaux</h6>
                    <h3 class="mb-0">{{ Auth::user()->total_points }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 bg-success bg-gradient text-white">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">Points disponibles</h6>
                    <h3 class="mb-0">{{ Auth::user()->available_points }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 bg-info bg-gradient text-white">
                <div class="card-body text-center py-3">
                    <h6 class="mb-1">Niveau actuel</h6>
                    <h3 class="mb-0">{{ Auth::user()->level }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des transactions -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Toutes les transactions</h5>
        </div>
        <div class="card-body p-0">
            @if($transactions->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Aucune transaction pour le moment.</p>
                    <a href="{{ route('points.dashboard') }}" class="btn btn-primary">
                        Commencer à gagner des points
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 ps-4">Date</th>
                                <th class="border-0">Description</th>
                                <th class="border-0">Type</th>
                                <th class="border-0">Source</th>
                                <th class="border-0 text-end pe-4">Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $transaction)
                            <tr>
                                <td class="ps-4">
                                    <small class="text-muted">{{ $transaction->created_at->format('d/m/Y') }}</small>
                                    <br>
                                    <small>{{ $transaction->created_at->format('H:i') }}</small>
                                </td>
                                <td>{{ $transaction->description }}</td>
                                <td>
                                    @php
                                        $typeLabels = [
                                            'share' => 'Partage',
                                            'daily' => 'Quotidien',
                                            'level_up' => 'Niveau',
                                            'purchase' => 'Achat',
                                            'subscription' => 'Abonnement',
                                            'welcome' => 'Bienvenue',
                                            'ad_publish' => 'Annonce'
                                        ];
                                        $typeColors = [
                                            'share' => 'primary',
                                            'daily' => 'success',
                                            'level_up' => 'warning',
                                            'purchase' => 'info',
                                            'subscription' => 'danger',
                                            'welcome' => 'secondary',
                                            'ad_publish' => 'info'
                                        ];
                                        $label = $typeLabels[$transaction->type] ?? $transaction->type;
                                        $color = $typeColors[$transaction->type] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $color }}">
                                        {{ $label }}
                                    </span>
                                </td>
                                <td>
                                    @if($transaction->source)
                                        <span class="text-muted">
                                            <i class="fab fa-{{ $transaction->source }} me-1"></i>
                                            {{ ucfirst($transaction->source) }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @if($transaction->points > 0)
                                        <span class="text-success fw-bold">
                                            <i class="fas fa-arrow-up me-1"></i>+{{ $transaction->points }}
                                        </span>
                                    @else
                                        <span class="text-danger fw-bold">
                                            <i class="fas fa-arrow-down me-1"></i>{{ $transaction->points }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @if($transactions->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

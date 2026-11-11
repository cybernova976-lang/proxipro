@extends('pro.layout')

@section('title', 'Statistiques')

@section('content')
<div class="analytics-page">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1" style="color: #1e293b;">
                <i class="fas fa-chart-line me-2" style="color: var(--pro-primary);"></i>Statistiques & Analyses
            </h4>
            <p class="text-muted mb-0 small">Vue d'ensemble de votre activité professionnelle</p>
        </div>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" id="periodFilter" style="width: auto; border-radius: 10px;">
                <option value="6">6 derniers mois</option>
                <option value="12" selected>12 derniers mois</option>
                <option value="all">Tout</option>
            </select>
        </div>
    </div>

    {{-- KPI Cards Row --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="analytics-kpi-card" style="border-left: 4px solid #6366f1;">
                <div class="kpi-icon" style="background: rgba(99,102,241,0.1); color: #6366f1;">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <div class="kpi-content">
                    <span class="kpi-value">{{ number_format($stats['total_revenue'], 2, ',', ' ') }} €</span>
                    <span class="kpi-label">Revenu total</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="analytics-kpi-card" style="border-left: 4px solid #22c55e;">
                <div class="kpi-icon" style="background: rgba(34,197,94,0.1); color: #22c55e;">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="kpi-content">
                    <span class="kpi-value">{{ number_format($stats['monthly_revenue'], 2, ',', ' ') }} €</span>
                    <span class="kpi-label">Revenu ce mois</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="analytics-kpi-card" style="border-left: 4px solid #f59e0b;">
                <div class="kpi-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <div class="kpi-content">
                    <span class="kpi-value">{{ $stats['conversion_rate'] }}%</span>
                    <span class="kpi-label">Taux de conversion</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="analytics-kpi-card" style="border-left: 4px solid #ef4444;">
                <div class="kpi-icon" style="background: rgba(239,68,68,0.1); color: #ef4444;">
                    <i class="fas fa-star"></i>
                </div>
                <div class="kpi-content">
                    <span class="kpi-value">{{ $stats['average_rating'] }}<small>/5</small></span>
                    <span class="kpi-label">Note moyenne ({{ $stats['reviews_count'] }} avis)</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row g-3 mb-4">
        {{-- Revenue Chart --}}
        <div class="col-lg-8">
            <div class="analytics-card">
                <div class="analytics-card-header">
                    <h6 class="fw-bold mb-0"><i class="fas fa-chart-area me-2 text-primary"></i>Évolution du chiffre d'affaires</h6>
                </div>
                <div class="analytics-card-body">
                    <canvas id="revenueChart" height="280" hidden aria-hidden="true"></canvas>
                    <table class="table table-sm mb-0" data-chart-values="revenueChart">
                        <caption class="visually-hidden">Évolution mensuelle du chiffre d’affaires</caption>
                        <thead><tr><th scope="col">Mois</th><th scope="col">Chiffre d’affaires</th></tr></thead>
                        <tbody>
                            @foreach ($revenueData as $entry)
                                <tr><th scope="row">{{ $entry['label'] }}</th><td>{{ number_format($entry['revenue'], 2, ',', ' ') }} €</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Conversion Donut --}}
        <div class="col-lg-4">
            <div class="analytics-card h-100">
                <div class="analytics-card-header">
                    <h6 class="fw-bold mb-0"><i class="fas fa-chart-pie me-2 text-warning"></i>Conversion devis</h6>
                </div>
                <div class="analytics-card-body d-flex flex-column align-items-center justify-content-center">
                    <canvas id="conversionChart" height="200" hidden aria-hidden="true"></canvas>
                    <div class="mt-3 text-center" data-chart-summary="conversionChart">
                        <div class="d-flex gap-3 justify-content-center flex-wrap">
                            <span class="badge" style="background: #6366f1; font-size: .75rem;">
                                {{ $stats['accepted_quotes'] }} acceptés
                            </span>
                            <span class="badge bg-secondary" style="font-size: .75rem;">
                                {{ $stats['total_quotes'] - $stats['accepted_quotes'] }} autres
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Second Row Charts --}}
    <div class="row g-3 mb-4">
        {{-- New Clients Bar Chart --}}
        <div class="col-lg-6">
            <div class="analytics-card">
                <div class="analytics-card-header">
                    <h6 class="fw-bold mb-0"><i class="fas fa-user-plus me-2 text-success"></i>Nouveaux clients / mois</h6>
                </div>
                <div class="analytics-card-body">
                    <canvas id="clientsChart" height="220" hidden aria-hidden="true"></canvas>
                    <table class="table table-sm mb-0" data-chart-values="clientsChart">
                        <caption class="visually-hidden">Nouveaux clients par mois</caption>
                        <thead><tr><th scope="col">Mois</th><th scope="col">Nouveaux clients</th></tr></thead>
                        <tbody>
                            @foreach ($clientsData as $entry)
                                <tr><th scope="row">{{ $entry['month'] }}</th><td>{{ $entry['count'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Payment Stats --}}
        <div class="col-lg-6">
            <div class="analytics-card">
                <div class="analytics-card-header">
                    <h6 class="fw-bold mb-0"><i class="fas fa-file-invoice-dollar me-2 text-info"></i>Facturation</h6>
                </div>
                <div class="analytics-card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <div class="mini-stat-card">
                                <span class="mini-stat-value text-success">{{ $stats['paid_invoices'] }}</span>
                                <span class="mini-stat-label">Factures payées</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mini-stat-card">
                                <span class="mini-stat-value text-warning">{{ $stats['total_invoices'] - $stats['paid_invoices'] }}</span>
                                <span class="mini-stat-label">En attente</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mini-stat-card">
                                <span class="mini-stat-value text-primary">{{ number_format($stats['avg_invoice_value'], 0, ',', ' ') }} €</span>
                                <span class="mini-stat-label">Valeur moy. facture</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mini-stat-card">
                                <span class="mini-stat-value text-danger">{{ number_format($stats['pending_amount'], 0, ',', ' ') }} €</span>
                                <span class="mini-stat-label">Montant en attente</span>
                            </div>
                        </div>
                    </div>
                    <div class="progress" style="height: 12px; border-radius: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $stats['payment_rate'] }}%;">
                            {{ $stats['payment_rate'] }}%
                        </div>
                    </div>
                    <small class="text-muted d-block mt-1">Taux de paiement</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Activity Summary --}}
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="analytics-card">
                <div class="analytics-card-header">
                    <h6 class="fw-bold mb-0"><i class="fas fa-users me-2" style="color: #14b8a6;"></i>Clients</h6>
                </div>
                <div class="analytics-card-body">
                    <div class="activity-metric">
                        <span class="activity-metric-label">Total clients</span>
                        <span class="activity-metric-value">{{ $stats['total_clients'] }}</span>
                    </div>
                    <div class="activity-metric">
                        <span class="activity-metric-label">Clients actifs</span>
                        <span class="activity-metric-value text-success">{{ $stats['active_clients'] }}</span>
                    </div>
                    <div class="activity-metric">
                        <span class="activity-metric-label">Taux d'activité</span>
                        <span class="activity-metric-value">
                            {{ $stats['total_clients'] > 0 ? round(($stats['active_clients'] / $stats['total_clients']) * 100) : 0 }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="analytics-card">
                <div class="analytics-card-header">
                    <h6 class="fw-bold mb-0"><i class="fas fa-file-alt me-2" style="color: #6366f1;"></i>Devis</h6>
                </div>
                <div class="analytics-card-body">
                    <div class="activity-metric">
                        <span class="activity-metric-label">Devis créés</span>
                        <span class="activity-metric-value">{{ $stats['total_quotes'] }}</span>
                    </div>
                    <div class="activity-metric">
                        <span class="activity-metric-label">Devis acceptés</span>
                        <span class="activity-metric-value text-success">{{ $stats['accepted_quotes'] }}</span>
                    </div>
                    <div class="activity-metric">
                        <span class="activity-metric-label">Valeur moyenne</span>
                        <span class="activity-metric-value">{{ number_format($stats['avg_quote_value'], 0, ',', ' ') }} €</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="analytics-card">
                <div class="analytics-card-header">
                    <h6 class="fw-bold mb-0"><i class="fas fa-bullhorn me-2" style="color: #f97316;"></i>Visibilité</h6>
                </div>
                <div class="analytics-card-body">
                    <div class="activity-metric">
                        <span class="activity-metric-label">Annonces actives</span>
                        <span class="activity-metric-value">{{ $stats['active_ads'] }}</span>
                    </div>
                    <div class="activity-metric">
                        <span class="activity-metric-label">Avis reçus</span>
                        <span class="activity-metric-value">{{ $stats['reviews_count'] }}</span>
                    </div>
                    <div class="activity-metric">
                        <span class="activity-metric-label">Note moyenne</span>
                        <span class="activity-metric-value">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star {{ $i <= round($stats['average_rating']) ? 'text-warning' : 'text-muted' }}" style="font-size: .75rem;"></i>
                            @endfor
                            <span class="ms-1">{{ $stats['average_rating'] }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.analytics-kpi-card {
    background: #fff;
    border-radius: 14px;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    transition: transform .2s, box-shadow .2s;
}
.analytics-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}
.kpi-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.kpi-content {
    display: flex;
    flex-direction: column;
}
.kpi-value {
    font-size: 1.2rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.2;
}
.kpi-value small {
    font-size: .7em;
    color: #94a3b8;
}
.kpi-label {
    font-size: .75rem;
    color: #94a3b8;
    margin-top: 2px;
}

.analytics-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    overflow: hidden;
}
.analytics-card-header {
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
}
.analytics-card-body {
    padding: 20px;
}

.mini-stat-card {
    background: #f8fafc;
    border-radius: 10px;
    padding: 14px;
    text-align: center;
}
.mini-stat-value {
    font-size: 1.4rem;
    font-weight: 700;
    display: block;
}
.mini-stat-label {
    font-size: .72rem;
    color: #94a3b8;
    margin-top: 4px;
    display: block;
}

.activity-metric {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
}
.activity-metric:last-child {
    border-bottom: none;
}
.activity-metric-label {
    color: #64748b;
    font-size: .85rem;
}
.activity-metric-value {
    font-weight: 600;
    color: #1e293b;
}
</style>

{{-- Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') return;

    document.querySelectorAll('#revenueChart, #conversionChart, #clientsChart').forEach(canvas => {
        canvas.hidden = false;
    });
    document.querySelectorAll('[data-chart-values]').forEach(table => table.classList.add('visually-hidden'));

    const revenueData = @json($revenueData);
    const clientsData = @json($clientsData);

    // Revenue Area Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: revenueData.map(d => d.month),
            datasets: [{
                label: 'Chiffre d\'affaires (€)',
                data: revenueData.map(d => d.revenue),
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99,102,241,0.08)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#6366f1',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { family: 'Inter' },
                    bodyFont: { family: 'Inter' },
                    callbacks: {
                        label: ctx => ctx.parsed.y.toLocaleString('fr-FR') + ' €'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        callback: v => v.toLocaleString('fr-FR') + ' €',
                        font: { family: 'Inter', size: 11 }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Inter', size: 11 } }
                }
            }
        }
    });

    // Conversion Donut
    new Chart(document.getElementById('conversionChart'), {
        type: 'doughnut',
        data: {
            labels: ['Acceptés', 'Autres'],
            datasets: [{
                data: [{{ $stats['accepted_quotes'] }}, {{ $stats['total_quotes'] - $stats['accepted_quotes'] }}],
                backgroundColor: ['#6366f1', '#e2e8f0'],
                borderWidth: 0,
                cutout: '72%',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { family: 'Inter' },
                    bodyFont: { family: 'Inter' },
                }
            }
        }
    });

    // Clients Bar Chart
    new Chart(document.getElementById('clientsChart'), {
        type: 'bar',
        data: {
            labels: clientsData.map(d => d.month),
            datasets: [{
                label: 'Nouveaux clients',
                data: clientsData.map(d => d.count),
                backgroundColor: 'rgba(34,197,94,0.2)',
                borderColor: '#22c55e',
                borderWidth: 2,
                borderRadius: 8,
                barThickness: 32,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { family: 'Inter' },
                    bodyFont: { family: 'Inter' },
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: { stepSize: 1, font: { family: 'Inter', size: 11 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Inter', size: 11 } }
                }
            }
        }
    });
});
</script>
@endsection

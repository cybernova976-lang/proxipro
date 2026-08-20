@extends('admin.layouts.app')

@section('title', 'Utilisation réelle')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1"><i class="fas fa-chart-line text-primary me-2"></i>Utilisation réelle</h1>
        <p class="text-muted mb-0">Fréquentation de Prokejem et actions utiles, du {{ $start->format('d/m/Y') }} au {{ $end->format('d/m/Y') }}.</p>
    </div>
    <div class="btn-group" role="group" aria-label="Période analysée">
        @foreach([7 => '7 jours', 30 => '30 jours', 90 => '90 jours'] as $days => $label)
            <a href="{{ route('admin.usage', ['period' => $days]) }}"
               class="btn {{ $period === $days ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }}</a>
        @endforeach
    </div>
</div>

<div class="alert alert-info border-0 shadow-sm mb-4">
    <div class="d-flex gap-3">
        <i class="fas fa-shield-alt fs-4 mt-1"></i>
        <div>
            <strong>Mesure interne et agrégée.</strong>
            Aucun compte, adresse IP, user-agent complet, recherche, message ou paramètre d’URL n’est conservé dans ces statistiques.
            Les données de fréquentation commencent à s’accumuler à partir de l’activation de cette fonctionnalité.
        </div>
    </div>
</div>

<div class="usage-kpi-grid mb-4">
    <div class="usage-kpi">
        <span class="usage-kpi-icon bg-primary-subtle text-primary"><i class="fas fa-eye"></i></span>
        <div><strong>{{ number_format($summary['page_views']) }}</strong><span>Pages vues</span></div>
    </div>
    <div class="usage-kpi">
        <span class="usage-kpi-icon bg-success-subtle text-success"><i class="fas fa-door-open"></i></span>
        <div><strong>{{ number_format($summary['sessions']) }}</strong><span>Sessions quotidiennes</span></div>
    </div>
    <div class="usage-kpi">
        <span class="usage-kpi-icon bg-info-subtle text-info"><i class="fas fa-mobile-alt"></i></span>
        <div><strong>{{ number_format($summary['pwa_page_views']) }}</strong><span>Vues dans l’application ({{ $summary['pwa_share'] }} %)</span></div>
    </div>
    <div class="usage-kpi">
        <span class="usage-kpi-icon bg-warning-subtle text-warning"><i class="fas fa-download"></i></span>
        <div><strong>{{ number_format($summary['pwa_installs']) }}</strong><span>Installations détectées</span></div>
    </div>
    <div class="usage-kpi">
        <span class="usage-kpi-icon bg-danger-subtle text-danger"><i class="fas fa-bell"></i></span>
        <div><strong>{{ number_format($summary['push_devices']) }}</strong><span>Appareils avec notifications</span></div>
    </div>
    <div class="usage-kpi">
        <span class="usage-kpi-icon bg-secondary-subtle text-secondary"><i class="fas fa-users"></i></span>
        <div><strong>{{ number_format($summary['push_users']) }}</strong><span>Comptes joignables par notification</span></div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h2 class="h5 fw-bold mb-1">Évolution quotidienne</h2>
                <p class="small text-muted mb-0">Pages vues, sessions et utilisation de l’application installée.</p>
            </div>
            <div class="card-body px-4">
                @php($maxDailyViews = max(1, (int) $dailyUsage->max('page_views')))
                <div class="usage-bars" aria-label="Graphique de fréquentation quotidienne">
                    @foreach($dailyUsage as $day)
                        <div class="usage-bar-day" title="{{ $day['date']->format('d/m') }} : {{ $day['page_views'] }} pages, {{ $day['sessions'] }} sessions">
                            <div class="usage-bar-stack">
                                <span class="usage-bar usage-bar-pwa" style="height: {{ min(100, round(($day['pwa_views'] / $maxDailyViews) * 100)) }}%"></span>
                                <span class="usage-bar usage-bar-pages" style="height: {{ min(100, round(($day['page_views'] / $maxDailyViews) * 100)) }}%"></span>
                            </div>
                            <small>{{ $period <= 30 || $loop->iteration % 7 === 1 ? $day['date']->format('d/m') : '' }}</small>
                        </div>
                    @endforeach
                </div>
                <div class="d-flex flex-wrap gap-3 small mt-3">
                    <span><i class="usage-legend bg-primary"></i> Pages vues</span>
                    <span><i class="usage-legend bg-info"></i> Vues PWA</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h2 class="h5 fw-bold mb-1">Appareils</h2>
                <p class="small text-muted mb-0">Répartition des pages vues.</p>
            </div>
            <div class="card-body px-4">
                @php($deviceTotal = max(1, (int) $deviceBreakdown->sum()))
                @foreach(['mobile' => ['Mobile', 'fa-mobile-alt', 'primary'], 'tablet' => ['Tablette', 'fa-tablet-alt', 'info'], 'desktop' => ['Ordinateur', 'fa-desktop', 'secondary']] as $key => [$label, $icon, $color])
                    @php($value = (int) $deviceBreakdown->get($key, 0))
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="fas {{ $icon }} text-{{ $color }} me-2"></i>{{ $label }}</span>
                            <strong>{{ number_format($value) }}</strong>
                        </div>
                        <div class="progress" style="height: 9px;">
                            <div class="progress-bar bg-{{ $color }}" style="width: {{ round(($value / $deviceTotal) * 100, 1) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h2 class="h5 fw-bold mb-0">Pages les plus consultées</h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th class="ps-4">Page</th><th>Route</th><th class="text-end pe-4">Vues</th></tr></thead>
                        <tbody>
                        @forelse($topPages as $page)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $page['label'] }}</td>
                                <td><code>{{ $page['route_name'] }}</code></td>
                                <td class="text-end pe-4 fw-bold">{{ number_format($page['count']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-5">Les premières visites apparaîtront ici après le déploiement.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h2 class="h5 fw-bold mb-1">Actions métier</h2>
                <p class="small text-muted mb-0">Calculées depuis les données fonctionnelles existantes, sans duplication.</p>
            </div>
            <div class="card-body">
                <div class="business-grid">
                    <div><strong>{{ number_format($businessStats['registrations']) }}</strong><span>Inscriptions</span></div>
                    <div><strong>{{ number_format($businessStats['ads']) }}</strong><span>Annonces publiées</span></div>
                    <div><strong>{{ number_format($businessStats['messages']) }}</strong><span>Messages envoyés</span></div>
                    <div><strong>{{ number_format($businessStats['proposals']) }}</strong><span>Propositions</span></div>
                    <div><strong>{{ number_format($businessStats['orders']) }}</strong><span>Commandes créées</span></div>
                    <div><strong>{{ number_format($businessStats['paid_orders']) }}</strong><span>Commandes payées</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4 d-flex flex-wrap justify-content-between gap-3 align-items-center">
        <div>
            <h2 class="h6 fw-bold mb-1">Conservation et portée</h2>
            <p class="small text-muted mb-0">Une session est comptée au maximum une fois par jour. Les compteurs agrégés sont automatiquement supprimés après 25 mois.</p>
        </div>
        <span class="badge bg-success-subtle text-success px-3 py-2"><i class="fas fa-check-circle me-1"></i>Aucune donnée sensible collectée</span>
    </div>
</div>

<style>
.bg-primary-subtle { background:rgba(13,110,253,.12) !important; }
.bg-success-subtle { background:rgba(25,135,84,.12) !important; }
.bg-info-subtle { background:rgba(13,202,240,.14) !important; }
.bg-warning-subtle { background:rgba(255,193,7,.18) !important; }
.bg-danger-subtle { background:rgba(220,53,69,.12) !important; }
.bg-secondary-subtle { background:rgba(108,117,125,.12) !important; }
.usage-kpi-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; }
.usage-kpi { display:flex; align-items:center; gap:14px; padding:18px; border-radius:14px; background:#fff; box-shadow:0 2px 12px rgba(15,23,42,.06); }
.usage-kpi-icon { width:46px; height:46px; border-radius:12px; display:grid; place-items:center; flex:0 0 auto; font-size:1.1rem; }
.usage-kpi strong { display:block; font-size:1.45rem; line-height:1.1; color:#0f172a; }
.usage-kpi span { color:#64748b; font-size:.82rem; }
.usage-bars { height:240px; display:flex; align-items:flex-end; gap:5px; overflow-x:auto; padding:12px 2px 0; border-bottom:1px solid #e2e8f0; }
.usage-bar-day { min-width:18px; flex:1 0 18px; height:100%; display:flex; flex-direction:column; justify-content:flex-end; align-items:center; }
.usage-bar-stack { width:100%; height:200px; display:flex; align-items:flex-end; justify-content:center; position:relative; }
.usage-bar { position:absolute; bottom:0; border-radius:5px 5px 0 0; min-height:2px; }
.usage-bar-pages { width:75%; background:#4f46e5; opacity:.78; }
.usage-bar-pwa { width:36%; background:#06b6d4; z-index:2; }
.usage-bar-day small { font-size:.62rem; color:#94a3b8; min-height:20px; margin-top:5px; white-space:nowrap; }
.usage-legend { width:10px; height:10px; border-radius:3px; display:inline-block; margin-right:5px; }
.business-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
.business-grid div { padding:18px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; text-align:center; }
.business-grid strong { display:block; font-size:1.35rem; color:#0f172a; }
.business-grid span { font-size:.78rem; color:#64748b; }
@media (max-width: 991.98px) { .usage-kpi-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media (max-width: 575.98px) {
    .usage-kpi-grid, .business-grid { grid-template-columns:1fr; }
    .usage-kpi { padding:15px; }
    .usage-bars { height:210px; }
    .usage-bar-stack { height:170px; }
}
</style>
@endsection

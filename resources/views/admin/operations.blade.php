@extends('admin.layouts.app')

@section('title', 'À traiter')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1"><i class="fas fa-list-check text-danger me-2"></i>À traiter</h1>
        <p class="text-muted mb-0">Une file unique des situations qui demandent une décision ou un contrôle humain.</p>
    </div>
    <span class="operations-total">{{ number_format($counts->get('total', 0)) }} dossier{{ $counts->get('total', 0) > 1 ? 's' : '' }}</span>
</div>

<div class="alert alert-light border shadow-sm mb-4">
    <i class="fas fa-circle-info text-primary me-2"></i>
    Cette page ne relance, ne modifie et ne clôture aucun dossier automatiquement. Ouvrez le dossier source, vérifiez son historique puis appliquez la procédure correspondante.
</div>

<div class="operations-summary mb-4" aria-label="Résumé des dossiers à traiter">
    @foreach([
        'payment' => ['Paiements', 'fa-credit-card', 'danger'],
        'dispute' => ['Litiges', 'fa-scale-balanced', 'danger'],
        'report' => ['Signalements', 'fa-flag', 'warning'],
        'verification' => ['Vérifications', 'fa-shield-halved', 'info'],
        'unanswered' => ['Sans réponse', 'fa-bullhorn', 'primary'],
    ] as $type => [$label, $icon, $color])
        <div>
            <i class="fas {{ $icon }} text-{{ $color }}"></i>
            <strong>{{ number_format($counts->get($type, 0)) }}</strong>
            <span>{{ $label }}</span>
        </div>
    @endforeach
</div>

<section class="operations-list" aria-label="Dossiers prioritaires">
    @forelse($items as $item)
        <article class="operation-card operation-card--{{ $item['tone'] }}">
            <div class="operation-icon"><i class="fas {{ $item['icon'] }}"></i></div>
            <div class="operation-main">
                <div class="operation-eyebrow">
                    <span>{{ $item['label'] }}</span>
                    <time datetime="{{ $item['occurred_at']?->toIso8601String() }}">{{ $item['occurred_at']?->diffForHumans() }}</time>
                </div>
                <h2>{{ $item['title'] }}</h2>
                <p class="operation-context">{{ $item['context'] }}</p>
                <p class="operation-next"><strong>Prochaine action :</strong> {{ $item['next_action'] }}</p>
                <div class="operation-meta">
                    <span><i class="fas fa-user-gear"></i> {{ $item['owner'] }}</span>
                    <span><i class="far fa-clock"></i> {{ $item['deadline'] }}</span>
                </div>
            </div>
            <a class="btn btn-primary operation-open" href="{{ $item['url'] }}">Ouvrir le dossier <i class="fas fa-arrow-right ms-1"></i></a>
        </article>
    @empty
        <div class="operations-empty">
            <i class="fas fa-circle-check"></i>
            <h2>Aucun dossier prioritaire</h2>
            <p>Les files surveillées ne contiennent actuellement aucune action en attente.</p>
        </div>
    @endforelse
</section>

<style>
.operations-total{padding:10px 16px;border-radius:999px;background:#fee2e2;color:#991b1b;font-weight:800}.operations-summary{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}.operations-summary>div{display:grid;grid-template-columns:auto 1fr;align-items:center;gap:2px 10px;padding:16px;border-radius:14px;background:#fff;box-shadow:0 2px 12px rgba(15,23,42,.06)}.operations-summary i{grid-row:1/3;font-size:1.2rem}.operations-summary strong{font-size:1.3rem;line-height:1}.operations-summary span{color:#64748b;font-size:.75rem}.operations-list{display:grid;gap:12px}.operation-card{display:grid;grid-template-columns:auto minmax(0,1fr) auto;gap:16px;align-items:center;padding:18px;border:1px solid #e2e8f0;border-left:5px solid #94a3b8;border-radius:16px;background:#fff;box-shadow:0 4px 16px rgba(15,23,42,.05)}.operation-card--danger{border-left-color:#dc2626}.operation-card--warning,.operation-card--attention{border-left-color:#f59e0b}.operation-card--info{border-left-color:#0891b2}.operation-icon{width:42px;height:42px;display:grid;place-items:center;border-radius:12px;background:#f1f5f9;color:#334155}.operation-eyebrow{display:flex;flex-wrap:wrap;gap:8px;color:#64748b;font-size:.72rem}.operation-eyebrow span{font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#334155}.operation-card h2{margin:5px 0;font-size:1rem;font-weight:800}.operation-context,.operation-next{margin:0;color:#64748b;font-size:.82rem}.operation-next{margin-top:5px;color:#334155}.operation-meta{display:flex;flex-wrap:wrap;gap:14px;margin-top:10px;color:#64748b;font-size:.72rem}.operation-open{white-space:nowrap}.operations-empty{padding:60px 20px;border:1px dashed #cbd5e1;border-radius:16px;background:#fff;text-align:center}.operations-empty>i{font-size:2rem;color:#16a34a}.operations-empty h2{margin:12px 0 5px;font-size:1.2rem}.operations-empty p{margin:0;color:#64748b}@media(max-width:991.98px){.operations-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.operation-card{grid-template-columns:auto minmax(0,1fr)}.operation-open{grid-column:2;width:max-content}}@media(max-width:575.98px){.operations-summary{grid-template-columns:1fr}.operation-card{grid-template-columns:1fr}.operation-icon{display:none}.operation-open{grid-column:1;width:100%}}
</style>
@endsection

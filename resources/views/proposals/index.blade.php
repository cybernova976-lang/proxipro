@extends('layouts.app')

@section('title', 'Mes propositions - ' . config('app.name'))

@section('content')
<style>
    .proposal-page{max-width:1180px;margin:0 auto;padding:32px 20px 56px}.proposal-hero{padding:28px;border-radius:22px;background:linear-gradient(135deg,#0f172a,#1d4ed8);color:#fff;margin-bottom:26px}.proposal-hero h1{font-size:clamp(1.65rem,4vw,2.35rem);font-weight:850;margin:0 0 8px}.proposal-hero p{margin:0;color:#dbeafe}.proposal-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:16px}.proposal-card{background:#fff;border:1px solid #e2e8f0;border-radius:18px;padding:20px;box-shadow:0 8px 28px rgba(15,23,42,.06)}.proposal-card h3{font-size:1.02rem;font-weight:800;color:#0f172a}.proposal-amount{font-size:1.45rem;font-weight:850;color:#1d4ed8}.proposal-meta{font-size:.85rem;color:#64748b}.proposal-message{color:#334155;font-size:.92rem;line-height:1.55;white-space:pre-line}.proposal-badge{display:inline-flex;padding:5px 10px;border-radius:999px;background:#eff6ff;color:#1d4ed8;font-size:.76rem;font-weight:750}.proposal-empty{border:1px dashed #cbd5e1;border-radius:18px;padding:34px;text-align:center;color:#64748b;background:#f8fafc}@media(max-width:640px){.proposal-page{padding:18px 12px 40px}.proposal-hero{padding:22px}.proposal-grid{grid-template-columns:1fr}}
</style>

<main class="proposal-page">
    <header class="proposal-hero">
        <h1><i class="fas fa-file-signature me-2"></i>Propositions et devis</h1>
        <p>Comparez les offres reçues ou suivez celles que vous avez envoyées.</p>
    </header>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 fw-bold mb-0">Propositions reçues</h2>
            <span class="text-muted small">Pour vos demandes publiées</span>
        </div>
        @if($receivedProposals->count())
            <div class="proposal-grid">
                @foreach($receivedProposals as $proposal)
                    <article class="proposal-card">
                        <div class="d-flex justify-content-between gap-3 mb-3">
                            <div>
                                <h3 class="mb-1">{{ $proposal->ad->title }}</h3>
                                <a href="{{ route('profile.public', $proposal->provider) }}" class="proposal-meta text-decoration-none">
                                    {{ $proposal->provider->name }}
                                    @if($proposal->provider->is_verified)<i class="fas fa-check-circle text-success ms-1" title="Profil vérifié"></i>@endif
                                </a>
                            </div>
                            <span class="proposal-badge">{{ $proposal->status_label }}</span>
                        </div>
                        <div class="proposal-amount mb-2">{{ number_format((float)$proposal->amount, 2, ',', ' ') }} €</div>
                        @if($proposal->scheduled_for)
                            <div class="proposal-meta mb-2"><i class="far fa-calendar me-1"></i>{{ $proposal->scheduled_for->format('d/m/Y') }}</div>
                        @endif
                        <p class="proposal-message">{{ $proposal->message }}</p>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <a href="{{ route('ads.show', $proposal->ad) }}" class="btn btn-sm btn-outline-secondary">Voir la demande</a>
                            @if($proposal->status === \App\Models\ServiceProposal::STATUS_PENDING)
                                <form method="POST" action="{{ route('proposals.accept', $proposal) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-success"><i class="fas fa-check me-1"></i>Accepter et payer</button>
                                </form>
                                <form method="POST" action="{{ route('proposals.refuse', $proposal) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger">Refuser</button>
                                </form>
                            @elseif($proposal->serviceOrder)
                                <a href="{{ route('service-orders.index') }}" class="btn btn-sm btn-primary">Voir la commande</a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-3">{{ $receivedProposals->links() }}</div>
        @else
            <div class="proposal-empty"><i class="fas fa-inbox fa-2x mb-3"></i><div>Aucune proposition reçue pour le moment.</div></div>
        @endif
    </section>

    <section>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 fw-bold mb-0">Propositions envoyées</h2>
            <span class="text-muted small">Vos réponses aux demandes</span>
        </div>
        @if($sentProposals->count())
            <div class="proposal-grid">
                @foreach($sentProposals as $proposal)
                    <article class="proposal-card">
                        <div class="d-flex justify-content-between gap-3 mb-3">
                            <h3 class="mb-0">{{ $proposal->ad->title }}</h3>
                            <span class="proposal-badge">{{ $proposal->status_label }}</span>
                        </div>
                        <div class="proposal-amount mb-2">{{ number_format((float)$proposal->amount, 2, ',', ' ') }} €</div>
                        <div class="proposal-meta mb-2">Client : {{ $proposal->ad->user->name }}</div>
                        <p class="proposal-message">{{ $proposal->message }}</p>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <a href="{{ route('ads.show', $proposal->ad) }}" class="btn btn-sm btn-outline-secondary">Voir la demande</a>
                            @if($proposal->status === \App\Models\ServiceProposal::STATUS_PENDING)
                                <form method="POST" action="{{ route('proposals.withdraw', $proposal) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger">Retirer</button>
                                </form>
                            @elseif($proposal->serviceOrder)
                                <a href="{{ route('service-orders.index') }}" class="btn btn-sm btn-primary">Voir la commande</a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-3">{{ $sentProposals->links() }}</div>
        @else
            <div class="proposal-empty"><i class="fas fa-paper-plane fa-2x mb-3"></i><div>Vous n’avez encore envoyé aucune proposition.</div></div>
        @endif
    </section>
</main>
@endsection

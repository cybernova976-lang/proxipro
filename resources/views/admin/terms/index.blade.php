@extends('admin.layouts.app')

@section('title', 'Conditions d’utilisation')

@section('content')
<div class="row mb-4 align-items-center">
    <div class="col">
        <h2 class="h4 fw-bold mb-1">Conditions d’utilisation (CGU)</h2>
        <p class="text-muted mb-0">Publiez une nouvelle version pour la faire accepter à tous les membres, administrateurs compris.</p>
    </div>
    <div class="col-auto">
        <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">Lire le texte en ligne</a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4" id="termsCurrent">
    <div class="card-body">
        @if($current)
            <div class="d-flex flex-wrap gap-4 align-items-center">
                <div><div class="text-muted small">Version en cours</div><div class="fs-5 fw-bold">{{ $current->version }}</div></div>
                <div><div class="text-muted small">{{ $current->isMandatory() ? 'Obligatoire depuis le' : 'Obligatoire à partir du' }}</div><div class="fs-5 fw-bold">{{ $current->effective_at->format('d/m/Y') }}</div></div>
                <div><div class="text-muted small">Membres l’ayant acceptée</div><div class="fs-5 fw-bold" data-terms-accepted>{{ $accepted }} sur {{ $members }}</div></div>
            </div>
            <p class="small text-muted mt-3 mb-1">Résumé montré aux membres :</p>
            <p class="mb-0">{!! nl2br(e($current->summary)) !!}</p>
        @else
            <p class="mb-0">Aucune version n’a encore été publiée : aucune acceptation n’est demandée aux membres pour le moment.</p>
        @endif
    </div>
</div>

<div class="card border-0 shadow-sm mb-4" id="termsSwitch" data-terms-paused="{{ $paused ? '1' : '0' }}">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h3 class="h6 fw-bold mb-1">Demande d’acceptation : {{ $paused ? 'suspendue' : 'active' }}</h3>
            <p class="small text-muted mb-0">
                @if($paused)
                    Plus aucun membre n’est sollicité, même après la date d’entrée en vigueur. Les acceptations déjà données sont conservées.
                @else
                    Les membres qui n’ont pas accepté la version en cours sont sollicités. Suspendez en cas de problème : rien n’est effacé.
                @endif
            </p>
        </div>
        <form action="{{ route('admin.terms.pause') }}" method="POST">
            @csrf
            <input type="hidden" name="paused" value="{{ $paused ? '0' : '1' }}">
            <button type="submit" class="btn {{ $paused ? 'btn-success' : 'btn-outline-danger' }}">{{ $paused ? 'Reprendre la demande' : 'Suspendre la demande' }}</button>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h3 class="h6 fw-bold mb-3">Publier une nouvelle version</h3>
        <p class="small text-muted">Le texte des conditions se modifie dans le code du site. Publiez ici <strong>après</strong> la mise en ligne du nouveau texte : cette action ne change pas le texte, elle demande à chaque membre de l’accepter.</p>
        <form action="{{ route('admin.terms.publish') }}" method="POST" id="termsPublishForm">
            @csrf
            <div class="mb-3">
                <label for="termsSummary" class="form-label">Ce qui change (montré à chaque membre)</label>
                <textarea id="termsSummary" name="summary" class="form-control" rows="5" minlength="20" maxlength="2000" required placeholder="Exemple : la fin d’une prestation payée en ligne doit être confirmée par le client ; sans confirmation après nos rappels, Prokejem décide du versement ou du remboursement.">{{ old('summary') }}</textarea>
            </div>
            <div class="mb-3 col-md-5 px-0">
                <label for="termsEffectiveDate" class="form-label">Date d’entrée en vigueur</label>
                <input id="termsEffectiveDate" type="date" name="effective_date" class="form-control" min="{{ now()->format('Y-m-d') }}" value="{{ old('effective_date', $defaultEffectiveDate) }}" required>
                <div class="form-text">Avant cette date, l’acceptation est proposée une fois par connexion. À partir de cette date, elle est obligatoire pour continuer à utiliser le site. Un préavis de 15 jours est proposé par défaut : faites valider un délai plus court par votre conseil juridique.</div>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="confirm" value="1" id="termsConfirm" required>
                <label class="form-check-label" for="termsConfirm">Le nouveau texte est en ligne, et je confirme que tous les membres devront l’accepter.</label>
            </div>
            <button type="submit" class="btn btn-primary">Publier cette version</button>
        </form>
    </div>
</div>

@if($versions->isNotEmpty())
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr><th>Version</th><th>Publiée le</th><th>Par</th><th>Obligatoire le</th><th>Acceptations enregistrées</th></tr>
            </thead>
            <tbody>
                @foreach($versions as $version)
                    <tr>
                        <td class="fw-semibold">{{ $version->version }}</td>
                        <td>{{ $version->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $version->publisher?->name ?? '—' }}</td>
                        <td>{{ $version->effective_at->format('d/m/Y') }}</td>
                        <td>{{ $version->acceptances_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

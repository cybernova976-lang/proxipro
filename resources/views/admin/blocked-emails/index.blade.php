@extends('admin.layouts.app')

@section('title', 'E-mails bloqués')

@section('content')
<div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h4 fw-bold mb-1">
            <i class="fas fa-envelope-circle-xmark text-danger me-2"></i>E-mails bloqués
        </h2>
        <p class="text-muted mb-0">Contrôlez les adresses qui ne peuvent plus créer ou réactiver un compte.</p>
    </div>
    <span class="badge bg-light text-danger border border-danger fs-6 px-3 py-2">
        {{ $blockedEmailsCount }} {{ Str::plural('adresse', $blockedEmailsCount) }}
    </span>
</div>

<div class="alert alert-warning border-0 shadow-sm mb-4">
    <div class="d-flex gap-3">
        <i class="fas fa-shield-halved mt-1"></i>
        <div>
            <strong>Blocage ciblé :</strong> cette liste empêche uniquement une nouvelle inscription ou la restauration automatique par Google/Facebook.
            Elle ne suspend pas un compte encore actif. Pour vos tests, une suppression simple reste donc réversible tant que l’adresse n’est pas ajoutée ici.
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-4 px-4">
        <h5 class="mb-1"><i class="fas fa-user-lock text-danger me-2"></i>Bloquer une adresse</h5>
        <p class="text-muted small mb-0">Le motif reste visible uniquement dans l’administration.</p>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('admin.blocked-emails.store') }}" method="POST" class="row g-3 align-items-end">
            @csrf
            <div class="col-lg-4">
                <label for="blocked-email" class="form-label fw-semibold">Adresse e-mail</label>
                <input
                    type="email"
                    class="form-control @error('email') is-invalid @enderror"
                    id="blocked-email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="utilisateur@exemple.com"
                    autocomplete="off"
                    required
                >
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-lg-6">
                <label for="blocked-reason" class="form-label fw-semibold">Motif interne <span class="text-muted fw-normal">(facultatif)</span></label>
                <input
                    type="text"
                    class="form-control @error('reason') is-invalid @enderror"
                    id="blocked-reason"
                    name="reason"
                    value="{{ old('reason') }}"
                    maxlength="1000"
                    placeholder="Fraude, abus répétés, usurpation…"
                >
                @error('reason')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-lg-2 d-grid">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-ban me-2"></i>Bloquer
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 p-4 pb-3">
        <form action="{{ route('admin.blocked-emails.index') }}" method="GET" class="row g-2">
            <div class="col-md-8 col-lg-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Rechercher une adresse…">
                    <button class="btn btn-outline-primary" type="submit">Rechercher</button>
                </div>
            </div>
            @if(request()->filled('search'))
                <div class="col-auto">
                    <a href="{{ route('admin.blocked-emails.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
                </div>
            @endif
        </form>
    </div>

    <div class="card-body p-0">
        @if($blockedEmails->count())
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 py-3 ps-4">Adresse e-mail</th>
                            <th class="border-0 py-3">Motif</th>
                            <th class="border-0 py-3">Décision</th>
                            <th class="border-0 py-3">Date</th>
                            <th class="border-0 py-3 pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($blockedEmails as $blockedEmail)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold text-dark">{{ $blockedEmail->email }}</div>
                                    @if($blockedEmail->sourceUser)
                                        <small class="text-muted">Ancien compte #{{ $blockedEmail->source_user_id }}</small>
                                    @else
                                        <small class="text-muted">Ajout manuel</small>
                                    @endif
                                </td>
                                <td style="max-width: 340px;">
                                    <span class="text-muted">{{ $blockedEmail->reason ?: 'Aucun motif renseigné' }}</span>
                                </td>
                                <td>
                                    @if($blockedEmail->blockedBy)
                                        <span class="d-block">{{ $blockedEmail->blockedBy->name }}</span>
                                        <small class="text-muted">{{ $blockedEmail->blockedBy->email }}</small>
                                    @else
                                        <span class="text-muted">Administrateur supprimé</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="d-block">{{ $blockedEmail->created_at->format('d/m/Y H:i') }}</span>
                                    <small class="text-muted">{{ $blockedEmail->created_at->diffForHumans() }}</small>
                                </td>
                                <td class="pe-4 text-end">
                                    <form
                                        action="{{ route('admin.blocked-emails.destroy', $blockedEmail) }}"
                                        method="POST"
                                        onsubmit="return confirm(@js('Autoriser à nouveau '.$blockedEmail->email.' à créer un compte ?'));"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-lock-open me-1"></i>Autoriser
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($blockedEmails->hasPages())
                <div class="card-footer bg-white border-0 py-3 px-4">
                    {{ $blockedEmails->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-5 px-3">
                <i class="fas fa-envelope-open-text fa-3x text-success mb-3"></i>
                <h5>{{ request()->filled('search') ? 'Aucun résultat' : 'Aucune adresse bloquée' }}</h5>
                <p class="text-muted mb-0">
                    {{ request()->filled('search') ? 'Essayez une autre recherche.' : 'Les comptes supprimés peuvent encore être recréés pour vos tests.' }}
                </p>
            </div>
        @endif
    </div>
</div>
@endsection

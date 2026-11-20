@extends('admin.layouts.app')

@section('title', 'Message de contact #' . $contactMessage->id)

@section('content')
<div class="row mb-4">
    <div class="col">
        <a href="{{ route('admin.contact-messages') }}" class="btn btn-outline-secondary btn-sm mb-3">
            <i class="fas fa-arrow-left me-1"></i>Retour aux messages
        </a>
        <h2 class="h4 fw-bold"><i class="fas fa-envelope-open me-2"></i>Message #{{ $contactMessage->id }}</h2>
    </div>
</div>

<div class="row">
    <!-- Message principal -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">{{ $contactMessage->subject }}</h5>
                @switch($contactMessage->status)
                    @case('pending')
                        <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>En attente</span>
                        @break
                    @case('read')
                        <span class="badge bg-info"><i class="fas fa-eye me-1"></i>Lu</span>
                        @break
                    @case('replied')
                        <span class="badge bg-success"><i class="fas fa-reply me-1"></i>Répondu</span>
                        @break
                    @case('closed')
                        <span class="badge bg-secondary"><i class="fas fa-check-circle me-1"></i>Fermé</span>
                        @break
                @endswitch
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                        <i class="fas fa-user text-primary"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">{{ $contactMessage->name }}</div>
                        <small class="text-muted">{{ $contactMessage->email }}</small>
                        @if($contactMessage->user)
                            <span class="ms-2"><a href="{{ route('admin.users.show', $contactMessage->user->id) }}" class="text-decoration-none small"><i class="fas fa-external-link-alt me-1"></i>Voir le profil</a></span>
                        @endif
                    </div>
                </div>
                <hr>
                <div class="message-content" style="white-space: pre-wrap; line-height: 1.7;">{{ $contactMessage->message }}</div>
                <hr>
                <small class="text-muted"><i class="fas fa-calendar me-1"></i>Envoyé le {{ $contactMessage->created_at->format('d/m/Y à H:i') }}</small>
            </div>
        </div>

        <!-- Réponse admin existante -->
        @if($contactMessage->admin_reply)
            <div class="card border-0 shadow-sm mb-4 border-start border-success border-3">
                <div class="card-header bg-success bg-opacity-10">
                    <h6 class="mb-0 text-success fw-bold"><i class="fas fa-reply me-2"></i>Réponse de l'administrateur</h6>
                </div>
                <div class="card-body">
                    <div style="white-space: pre-wrap; line-height: 1.7;">{{ $contactMessage->admin_reply }}</div>
                    <hr>
                    <small class="text-muted"><i class="fas fa-calendar me-1"></i>Répondu le {{ $contactMessage->replied_at?->format('d/m/Y à H:i') }}</small>
                </div>
            </div>
        @endif

        <!-- Formulaire de réponse -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold"><i class="fas fa-pen me-2"></i>{{ $contactMessage->admin_reply ? 'Modifier la réponse' : 'Répondre' }}</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.contact-messages.reply', $contactMessage->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <textarea name="admin_reply" class="form-control" rows="8" placeholder="Rédigez votre réponse..." style="resize: vertical; min-height: 120px;">{{ old('admin_reply', $contactMessage->admin_reply) }}</textarea>
                        @error('admin_reply')
                            <div class="text-danger small mt-1">{{ $errors->first('admin_reply') }}</div>
                        @enderror
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-paper-plane me-2"></i>{{ $contactMessage->admin_reply ? 'Mettre à jour la réponse' : 'Envoyer la réponse' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar infos -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold"><i class="fas fa-info-circle me-2"></i>Informations</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted small d-block">Nom</label>
                    <span class="fw-semibold">{{ $contactMessage->name }}</span>
                </div>
                <div class="mb-3">
                    <label class="text-muted small d-block">Email</label>
                    <a href="mailto:{{ $contactMessage->email }}" class="text-decoration-none">{{ $contactMessage->email }}</a>
                </div>
                <div class="mb-3">
                    <label class="text-muted small d-block">Sujet</label>
                    <span>{{ $contactMessage->subject }}</span>
                </div>
                <div class="mb-3">
                    <label class="text-muted small d-block">Utilisateur inscrit</label>
                    @if($contactMessage->user)
                        <x-admin.user-link :user="$contactMessage->user" :fiche="true" />
                    @else
                        <span class="text-muted">Non inscrit / Anonyme</span>
                    @endif
                </div>
                <div class="mb-3">
                    <label class="text-muted small d-block">Date de réception</label>
                    <span>{{ $contactMessage->created_at->format('d/m/Y H:i') }}</span>
                </div>
                @if($contactMessage->replied_at)
                    <div class="mb-3">
                        <label class="text-muted small d-block">Date de réponse</label>
                        <span>{{ $contactMessage->replied_at->format('d/m/Y H:i') }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Changer le statut -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold"><i class="fas fa-tag me-2"></i>Changer le statut</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.contact-messages.status', $contactMessage->id) }}" method="POST">
                    @csrf
                    <select name="status" class="form-select mb-3">
                        <option value="pending" {{ $contactMessage->status === 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="read" {{ $contactMessage->status === 'read' ? 'selected' : '' }}>Lu</option>
                        <option value="replied" {{ $contactMessage->status === 'replied' ? 'selected' : '' }}>Répondu</option>
                        <option value="closed" {{ $contactMessage->status === 'closed' ? 'selected' : '' }}>Fermé</option>
                    </select>
                    <button type="submit" class="btn btn-outline-primary w-100"><i class="fas fa-save me-2"></i>Mettre à jour</button>
                </form>
            </div>
        </div>

        <!-- Supprimer -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form action="{{ route('admin.contact-messages.delete', $contactMessage->id) }}" method="POST" onsubmit="return confirm('Supprimer définitivement ce message ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger w-100"><i class="fas fa-trash me-2"></i>Supprimer le message</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

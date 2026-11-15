@extends('admin.layouts.app')

@section('title', 'Messages de contact')

@section('content')
<div class="row mb-4">
    <div class="col">
        <h2 class="h4 fw-bold"><i class="fas fa-envelope me-2"></i>Messages de contact</h2>
        <p class="text-muted mb-0">Messages reçus via le formulaire « Nous contacter »</p>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3">
                    <i class="fas fa-clock text-warning fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small">En attente</div>
                    <div class="h4 mb-0 fw-bold">{{ $stats['pending'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                    <i class="fas fa-eye text-info fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small">Lus</div>
                    <div class="h4 mb-0 fw-bold">{{ $stats['read'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                    <i class="fas fa-reply text-success fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small">Répondus</div>
                    <div class="h4 mb-0 fw-bold">{{ $stats['replied'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-secondary bg-opacity-10 p-3 me-3">
                    <i class="fas fa-check-circle text-secondary fa-lg"></i>
                </div>
                <div>
                    <div class="text-muted small">Fermés</div>
                    <div class="h4 mb-0 fw-bold">{{ $stats['closed'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.contact-messages') }}">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Rechercher par nom, email, sujet..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>Lus</option>
                        <option value="replied" {{ request('status') == 'replied' ? 'selected' : '' }}>Répondus</option>
                        <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Fermés</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>Filtrer</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.contact-messages') }}" class="btn btn-outline-secondary w-100">Réinitialiser</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tableau -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4" style="width: 50px;">#</th>
                        <th>Expéditeur</th>
                        <th>Sujet</th>
                        <th>Extrait</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($messages as $msg)
                        <tr class="{{ $msg->status === 'pending' ? 'table-warning' : '' }}">
                            <td class="ps-4 text-muted">{{ $msg->id }}</td>
                            <td>
                                <div class="fw-semibold">{{ $msg->name }}</div>
                                <small class="text-muted">{{ $msg->email }}</small>
                                @if($msg->user)
                                    <br><a href="{{ route('admin.users.show', $msg->user->id) }}" class="text-decoration-none small"><i class="fas fa-user me-1"></i>Profil</a>
                                @endif
                            </td>
                            <td>
                                <span class="fw-semibold">{{ Str::limit($msg->subject, 30) }}</span>
                            </td>
                            <td>
                                <span class="text-muted small">{{ Str::limit($msg->message, 50) }}</span>
                            </td>
                            <td>
                                @switch($msg->status)
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
                            </td>
                            <td>
                                <span class="text-muted small">{{ $msg->created_at->format('d/m/Y H:i') }}</span>
                                @if($msg->replied_at)
                                    <br><small class="text-success"><i class="fas fa-reply me-1"></i>{{ $msg->replied_at->format('d/m/Y') }}</small>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('admin.contact-messages.show', $msg->id) }}" class="btn btn-sm btn-outline-primary" title="Voir"><i class="fas fa-eye"></i></a>
                                    <form action="{{ route('admin.contact-messages.delete', $msg->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce message ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="fas fa-envelope-open fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted">Aucun message de contact trouvé.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($messages->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $messages->withQueryString()->links('admin.partials.pagination') }}
    </div>
@endif
@endsection

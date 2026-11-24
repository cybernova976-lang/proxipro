@extends('pro.layout')
@section('title', 'Documents - Espace Pro')

@section('content')
<div class="pro-content-header">
    <div>
        <h1>Mes documents</h1>
        <p class="text-muted mb-0" style="font-size: 0.88rem;">Uploadez vos attestations d'assurance, diplômes, certifications, etc.</p>
    </div>
    <button class="btn btn-pro-primary" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
        <i class="fas fa-upload me-1"></i> Uploader un document
    </button>
</div>

@if($documents->isEmpty())
    <div class="pro-card">
        <div class="pro-empty">
            <div class="pro-empty-icon" aria-hidden="true"><i class="fas fa-folder-open"></i></div>
            <h5>Aucun document</h5>
            <p>Uploadez vos documents professionnels pour renforcer la confiance de vos clients.</p>
            <button class="btn btn-pro-outline mt-2" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                <i class="fas fa-upload me-1"></i> Uploader
            </button>
        </div>
    </div>
@else
    <div class="row g-3">
        @foreach($documents as $doc)
        <div class="col-md-6 col-lg-4">
            <div class="pro-card mb-0">
                <div class="d-flex align-items-start gap-3">
                    <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(168,85,247,0.1); color: #a855f7; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                        <i class="{{ $doc->getTypeIcon() }}"></i>
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <h6 class="fw-bold mb-1 text-truncate">{{ $doc->title }}</h6>
                        <div class="text-muted" style="font-size: 0.78rem;">
                            {{ $doc->getTypeLabel() }} · {{ $doc->getFileSizeFormatted() }}
                        </div>
                        <div class="mt-1">
                            <span class="pro-status pro-status-{{ $doc->status === 'approved' ? 'success' : ($doc->status === 'rejected' ? 'danger' : 'warning') }}">
                                {{ $doc->status === 'approved' ? 'Validé' : ($doc->status === 'rejected' ? 'Refusé' : 'En attente') }}
                            </span>
                            @if($doc->isExpired())
                                <span class="pro-status pro-status-danger ms-1">Expiré</span>
                            @elseif($doc->isExpiringSoon())
                                <span class="pro-status pro-status-warning ms-1">Expire bientôt</span>
                            @endif
                        </div>
                        @if($doc->expiry_date)
                            <div class="text-muted mt-1" style="font-size: 0.75rem;">Expire le {{ $doc->expiry_date->format('d/m/Y') }}</div>
                        @endif
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown" style="border-radius: 8px;">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ storage_url($doc->file_path) }}" target="_blank"><i class="fas fa-eye me-2"></i>Voir</a></li>
                            <li><a class="dropdown-item" href="{{ storage_url($doc->file_path) }}" download><i class="fas fa-download me-2"></i>Télécharger</a></li>
                            <li>
                                <form method="POST" action="{{ route('pro.documents.delete', $doc->id) }}" onsubmit="return confirm('Supprimer ce document ?')">
                                    @csrf @method('DELETE')
                                    <button class="dropdown-item text-danger"><i class="fas fa-trash me-2"></i>Supprimer</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <div class="mt-3">{{ $documents->links() }}</div>
@endif

{{-- Upload Modal --}}
<div class="modal fade" id="uploadDocModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-header" style="border-bottom: 1px solid var(--pro-border);">
                <h5 class="modal-title fw-bold"><i class="fas fa-upload me-2 text-primary"></i>Uploader un document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('pro.documents.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Titre du document *</label>
                        <input type="text" name="title" class="form-control" required style="border-radius: 10px;" placeholder="Ex: Attestation d'assurance RC Pro">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Type *</label>
                        <select name="type" class="form-select" required style="border-radius: 10px;">
                            <option value="insurance">Attestation d'assurance</option>
                            <option value="kbis">Extrait KBIS</option>
                            <option value="identity">Pièce d'identité</option>
                            <option value="diploma">Diplôme</option>
                            <option value="certification">Certification / Label</option>
                            <option value="other">Autre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fichier *</label>
                        <input type="file" name="file" class="form-control" required style="border-radius: 10px;" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <small class="text-muted">PDF, JPG, PNG, DOC — Max 10 Mo</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Date d'expiration</label>
                        <input type="date" name="expiry_date" class="form-control" style="border-radius: 10px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" style="border-radius: 10px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius: 10px;">Annuler</button>
                    <button type="submit" class="btn btn-pro-primary">Uploader</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

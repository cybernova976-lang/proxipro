@extends('layouts.app')

@section('title', 'Vérification d\'identité - ProxiPro')

@push('styles')
<style>
    .verification-header {
        padding: 30px 0 10px;
        margin-bottom: 20px;
        text-align: center;
    }
    .verification-header h1 {
        font-size: 1.6rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 6px;
    }
    .verification-header p {
        color: #64748b;
        font-size: 0.95rem;
        margin: 0;
    }
    .verification-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        border: 1px solid var(--border-subtle);
    }
    .verification-success-alert {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 15px 18px;
        border: 1px solid #10b981;
        border-radius: 14px;
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        box-shadow: 0 6px 18px rgba(16, 185, 129, 0.1);
    }
    .verification-success-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        border-radius: 50%;
        background: rgba(16, 185, 129, 0.18);
        color: #059669;
        font-size: 1.15rem;
    }
    .verification-success-alert h5 {
        color: #166534;
        font-size: 1rem;
        line-height: 1.35;
        margin: 0;
    }
    .verification-success-alert p {
        color: #64748b;
        font-size: 0.875rem;
        line-height: 1.4;
        margin: 4px 0 0;
    }
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.95rem;
    }
    .status-pending { background: #fef3c7; color: #b45309; }
    .status-approved { background: #dcfce7; color: #166534; }
    .status-rejected { background: #fee2e2; color: #dc2626; }
    .status-returned { background: #fef3c7; color: #d97706; border: 2px solid #f59e0b; }
    .status-none { background: #f1f5f9; color: #64748b; }

    .document-type-card {
        border: 2px solid var(--border-subtle);
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .document-type-card:hover { border-color: var(--primary); background: var(--primary-light); }
    .document-type-card.selected {
        border-color: var(--primary);
        background: var(--primary-light);
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    }
    .document-type-card i { font-size: 2.5rem; color: var(--primary); margin-bottom: 10px; }
    .document-type-card h5 { font-weight: 600; color: var(--text-main); margin-bottom: 5px; }
    .document-type-card p { color: var(--text-secondary); font-size: 0.85rem; margin: 0; }

    .upload-zone {
        border: 2px dashed var(--border-subtle);
        border-radius: 16px;
        padding: 40px 20px;
        text-align: center;
        background: #fafafa;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .upload-zone:hover { border-color: var(--primary); background: var(--primary-light); }
    .upload-zone.has-file { border-color: #10b981; background: #dcfce7; }
    .upload-zone i { font-size: 3rem; color: #94a3b8; margin-bottom: 15px; }
    .upload-zone.has-file i { color: #10b981; }
    .upload-zone.validation-error { border-color: #ef4444 !important; background: #fef2f2 !important; }
    .upload-zone.validation-error i { color: #ef4444; }
    .document-type-card.validation-error { border-color: #ef4444 !important; background: #fef2f2 !important; }

    .upload-preview {
        max-width: 200px;
        max-height: 150px;
        border-radius: 12px;
        margin-top: 15px;
        display: none;
    }

    .btn-verify {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border: none;
        padding: 16px 40px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1.05rem;
        transition: all 0.2s ease;
    }
    .btn-verify:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        color: white;
    }
    .btn-verify:disabled, .btn-verify[disabled] {
        background: #94a3b8;
        cursor: not-allowed;
        opacity: 0.7;
        transform: none;
        box-shadow: none;
    }
    .info-box {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        padding: 20px;
        color: #1e40af;
    }
    .info-box i { color: #3b82f6; }

    /* Per-document status indicators */
    .doc-status-card {
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 16px;
        border: 2px solid;
        position: relative;
    }
    .doc-status-card.doc-approved {
        border-color: #10b981;
        background: #f0fdf4;
    }
    .doc-status-card.doc-rejected {
        border-color: #ef4444;
        background: #fef2f2;
    }
    .doc-status-card.doc-pending {
        border-color: #e2e8f0;
        background: #fafafa;
    }
    .doc-status-card .doc-status-icon {
        position: absolute;
        top: 12px;
        right: 12px;
        font-size: 1.2rem;
    }
    .doc-status-card.doc-approved .doc-status-icon { color: #10b981; }
    .doc-status-card.doc-rejected .doc-status-icon { color: #ef4444; }
    .doc-status-card .rejection-reason {
        background: #fee2e2;
        border: 1px solid #fca5a5;
        border-radius: 8px;
        padding: 10px 14px;
        margin-top: 10px;
        color: #dc2626;
        font-size: 0.9rem;
    }

    .admin-message-box {
        background: #fff7ed;
        border: 2px solid #fb923c;
        border-radius: 12px;
        padding: 20px;
        color: #9a3412;
    }
    .admin-message-box i { color: #f97316; }

    .missing-fields-box {
        background: #fefce8;
        border: 2px solid #fde047;
        border-radius: 12px;
        padding: 20px;
        color: #854d0e;
    }
    .missing-fields-box i { color: #eab308; }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.02); box-shadow: 0 0 15px rgba(234, 179, 8, 0.4); }
    }

    .existing-doc-preview {
        max-width: 120px;
        max-height: 90px;
        border-radius: 8px;
        object-fit: cover;
        border: 2px solid #e2e8f0;
    }

    .verification-price-note {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 16px;
        margin-bottom: 22px;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        background: #eff6ff;
        color: #1e3a8a;
    }

    .verification-price-note strong {
        color: #1d4ed8;
        font-size: 1rem;
        white-space: nowrap;
    }

    .verification-upload-actions {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 14px;
    }

    .verification-file-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 40px;
        padding: 8px 13px;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        background: #fff;
        color: #334155;
        font-size: 0.82rem;
        font-weight: 700;
    }

    .verification-file-action.is-camera {
        border-color: #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .verification-file-selected {
        margin-top: 10px;
        color: #047857;
        font-size: 0.78rem;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .verification-camera-overlay {
        position: fixed;
        inset: 0;
        z-index: 1200;
        display: grid;
        place-items: center;
        padding: 16px;
        background: rgba(15, 23, 42, 0.88);
    }

    .verification-camera-overlay[hidden] {
        display: none;
    }

    .verification-camera-panel {
        width: min(100%, 520px);
        overflow: hidden;
        border-radius: 14px;
        background: #0f172a;
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.36);
    }

    .verification-camera-header,
    .verification-camera-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px;
        background: #fff;
    }

    .verification-camera-header {
        justify-content: space-between;
    }

    .verification-camera-header strong {
        color: #0f172a;
        font-size: 0.95rem;
    }

    .verification-camera-close,
    .verification-camera-switch {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        background: #fff;
        color: #334155;
    }

    .verification-camera-video {
        display: block;
        width: 100%;
        max-height: 64vh;
        aspect-ratio: 3 / 4;
        object-fit: cover;
        background: #020617;
    }

    .verification-camera-actions {
        justify-content: center;
    }

    .verification-camera-capture {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 44px;
        padding: 10px 18px;
        border: 0;
        border-radius: 9px;
        background: #2563eb;
        color: #fff;
        font-weight: 750;
    }

    body.verification-camera-open {
        overflow: hidden;
    }

    .upload-zone .verification-file-action i {
        margin: 0 !important;
        color: currentColor;
        font-size: 0.9rem !important;
        line-height: 1;
    }

    .verification-form-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding-top: 16px;
    }

    .verification-back-btn,
    .verification-submit-btn {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 44px;
        line-height: 1.1;
    }

    .verification-back-btn i,
    .verification-submit-btn i {
        margin: 0 !important;
        line-height: 1;
    }

    @media (max-width: 576px) {
        .verification-header {
            padding: 22px 12px 8px;
        }

        .verification-header h1 {
            font-size: 1.35rem;
        }

        .verification-card {
            padding: 22px 16px;
            border-radius: 14px;
        }

        .verification-success-alert {
            align-items: flex-start;
            gap: 11px;
            padding: 13px 14px;
            border-radius: 12px;
        }

        .verification-success-icon {
            width: 36px;
            height: 36px;
            flex-basis: 36px;
            font-size: 1rem;
        }

        .verification-success-alert h5 {
            font-size: 0.95rem;
        }

        .upload-zone {
            padding: 24px 14px;
        }

        .verification-price-note {
            display: block;
            padding: 13px 14px;
        }

        .verification-form-actions {
            flex-direction: column-reverse;
            justify-content: center;
            gap: 10px;
        }

        .verification-form-actions > *,
        .verification-submit-wrap {
            width: 100%;
            max-width: 280px;
            margin-inline: auto;
        }

        .verification-back-btn,
        .verification-submit-btn {
            width: 100%;
            min-height: 44px;
            padding: 11px 16px !important;
            font-size: 0.9rem !important;
            border-radius: 10px !important;
        }
    }
</style>
@endpush

@section('content')
<div class="verification-header">
    <div class="container">
        <h1><i class="fas fa-shield-alt me-2" style="color: #10b981;"></i>Vérification d'identité</h1>
        <p>Obtenez le badge vérifié et gagnez la confiance des autres utilisateurs</p>
    </div>
</div>

<div class="container pb-5">
    @if(session('success'))
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="verification-success-alert mb-4" role="status">
                    <span class="verification-success-icon" aria-hidden="true">
                        <i class="fas fa-check"></i>
                    </span>
                    <div>
                        <h5 class="fw-bold">{{ session('success') }}</h5>
                        @if(isset($verification) && $verification && $verification->isPending())
                            <p>Notre équipe va examiner vos documents. Vous recevrez une notification dès que la vérification sera terminée.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            {{ session('error') }}
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Current Status -->
            @unless($verification && $verification->status === 'awaiting_payment')
            <div class="verification-card mb-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="mb-2" style="font-weight: 700;">Statut de vérification</h4>
                        <p class="text-muted mb-0">
                            @if($user->identity_verified)
                                Votre identité a été vérifiée le {{ $user->identity_verified_at?->format('d/m/Y') }}
                            @elseif($verification && $verification->isReturned())
                                Votre demande nécessite des corrections
                            @elseif($verification && $verification->status === 'awaiting_payment')
                                Vos documents sont prêts à être transmis
                            @elseif($verification && $verification->isPending())
                                Votre demande est en cours d'examen
                            @elseif($verification && $verification->isRejected())
                                Votre dernière demande a été refusée
                            @else
                                Vous n'avez pas encore vérifié votre identité
                            @endif
                        </p>
                    </div>
                    <div>
                        @if($user->identity_verified)
                            <span class="status-badge status-approved">
                                <i class="fas fa-check-circle"></i> Vérifié
                            </span>
                        @elseif($verification && $verification->isReturned())
                            <span class="status-badge status-returned">
                                <i class="fas fa-undo-alt"></i> Corrections requises
                            </span>
                        @elseif($verification && $verification->status === 'awaiting_payment')
                            <span class="status-badge status-pending">
                                <i class="fas fa-check-circle"></i> Documents enregistrés
                            </span>
                        @elseif($verification && $verification->isPending())
                            <span class="status-badge status-pending">
                                <i class="fas fa-clock"></i> En attente
                            </span>
                        @elseif($verification && $verification->isRejected())
                            <span class="status-badge status-rejected">
                                <i class="fas fa-times-circle"></i> Refusé
                            </span>
                        @else
                            <span class="status-badge status-none">
                                <i class="fas fa-user-times"></i> Non vérifié
                            </span>
                        @endif
                    </div>
                </div>
                
                @if($verification && $verification->isRejected() && $verification->rejection_reason)
                    <div class="alert alert-danger mt-3 mb-0">
                        <strong><i class="fas fa-times-circle me-1"></i>Raison du refus :</strong> {{ $verification->rejection_reason }}
                    </div>
                @endif
            </div>
            @endunless

            {{-- Admin message when returned --}}
            @if($verification && $verification->isReturned() && $verification->admin_message)
                <div class="admin-message-box mb-4">
                    <h5 class="fw-bold mb-2"><i class="fas fa-comment-alt me-2"></i>Message de l'administrateur</h5>
                    <p class="mb-0">{{ $verification->admin_message }}</p>
                </div>
            @endif

            {{-- Missing profile fields warning --}}
            @if(isset($missingFields) && count($missingFields) > 0)
                <div class="missing-fields-box mb-4">
                    <h5 class="fw-bold mb-2"><i class="fas fa-exclamation-triangle me-2"></i>Profil incomplet</h5>
                    <p class="mb-2">Veuillez compléter les champs suivants de votre profil avant de soumettre votre vérification :</p>
                    <ul class="mb-2">
                        @foreach($missingFields as $field)
                            <li>{{ $field['label'] }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit me-1"></i>Compléter mon profil
                    </a>
                </div>
            @endif

            {{-- ===============================
                 RETURNED FORM - Resubmission  
                 =============================== --}}
            @if($verification && $verification->isReturned())
                <div class="verification-card mb-4">
                    <h4 class="mb-4" style="font-weight: 700;">
                        <i class="fas fa-redo me-2" style="color: #f59e0b;"></i>
                        Corriger et resoumettre
                    </h4>

                    <p class="text-muted mb-4">
                        Certains de vos documents ont été refusés. Veuillez les remplacer par de nouvelles versions.
                        Les documents approuvés sont conservés.
                    </p>

                    {{-- Per-document status display --}}
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">État de vos documents :</h6>

                        {{-- Document Front --}}
                        <div class="doc-status-card {{ $verification->document_front_status === 'approved' ? 'doc-approved' : ($verification->document_front_status === 'rejected' ? 'doc-rejected' : 'doc-pending') }}">
                            <span class="doc-status-icon">
                                @if($verification->document_front_status === 'approved')
                                    <i class="fas fa-check-circle"></i>
                                @elseif($verification->document_front_status === 'rejected')
                                    <i class="fas fa-times-circle"></i>
                                @else
                                    <i class="fas fa-clock"></i>
                                @endif
                            </span>
                            <div class="d-flex align-items-center gap-3">
                                @if($verification->document_front && $verification->document_front_status === 'approved')
                                    <img src="{{ storage_url($verification->document_front) }}" class="existing-doc-preview" alt="Recto">
                                @endif
                                <div>
                                    <h6 class="fw-bold mb-1">Recto du document</h6>
                                    <span class="badge {{ $verification->document_front_status === 'approved' ? 'bg-success' : ($verification->document_front_status === 'rejected' ? 'bg-danger' : 'bg-warning') }}">
                                        {{ $verification->document_front_status === 'approved' ? 'Approuvé' : ($verification->document_front_status === 'rejected' ? 'Refusé' : 'En attente') }}
                                    </span>
                                </div>
                            </div>
                            @if($verification->document_front_status === 'rejected' && $verification->document_front_rejection_reason)
                                <div class="rejection-reason">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    {{ $verification->document_front_rejection_reason }}
                                </div>
                            @endif
                        </div>

                        {{-- Document Back --}}
                        @if($verification->document_back)
                        <div class="doc-status-card {{ $verification->document_back_status === 'approved' ? 'doc-approved' : ($verification->document_back_status === 'rejected' ? 'doc-rejected' : 'doc-pending') }}">
                            <span class="doc-status-icon">
                                @if($verification->document_back_status === 'approved')
                                    <i class="fas fa-check-circle"></i>
                                @elseif($verification->document_back_status === 'rejected')
                                    <i class="fas fa-times-circle"></i>
                                @else
                                    <i class="fas fa-clock"></i>
                                @endif
                            </span>
                            <div class="d-flex align-items-center gap-3">
                                @if($verification->document_back_status === 'approved')
                                    <img src="{{ storage_url($verification->document_back) }}" class="existing-doc-preview" alt="Verso">
                                @endif
                                <div>
                                    <h6 class="fw-bold mb-1">Verso du document</h6>
                                    <span class="badge {{ $verification->document_back_status === 'approved' ? 'bg-success' : ($verification->document_back_status === 'rejected' ? 'bg-danger' : 'bg-warning') }}">
                                        {{ $verification->document_back_status === 'approved' ? 'Approuvé' : ($verification->document_back_status === 'rejected' ? 'Refusé' : 'En attente') }}
                                    </span>
                                </div>
                            </div>
                            @if($verification->document_back_status === 'rejected' && $verification->document_back_rejection_reason)
                                <div class="rejection-reason">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    {{ $verification->document_back_rejection_reason }}
                                </div>
                            @endif
                        </div>
                        @endif

                        {{-- Selfie --}}
                        <div class="doc-status-card {{ $verification->selfie_status === 'approved' ? 'doc-approved' : ($verification->selfie_status === 'rejected' ? 'doc-rejected' : 'doc-pending') }}">
                            <span class="doc-status-icon">
                                @if($verification->selfie_status === 'approved')
                                    <i class="fas fa-check-circle"></i>
                                @elseif($verification->selfie_status === 'rejected')
                                    <i class="fas fa-times-circle"></i>
                                @else
                                    <i class="fas fa-clock"></i>
                                @endif
                            </span>
                            <div class="d-flex align-items-center gap-3">
                                @if($verification->selfie && $verification->selfie_status === 'approved')
                                    <img src="{{ storage_url($verification->selfie) }}" class="existing-doc-preview" alt="Selfie">
                                @endif
                                <div>
                                    <h6 class="fw-bold mb-1">Selfie avec document</h6>
                                    <span class="badge {{ $verification->selfie_status === 'approved' ? 'bg-success' : ($verification->selfie_status === 'rejected' ? 'bg-danger' : 'bg-warning') }}">
                                        {{ $verification->selfie_status === 'approved' ? 'Approuvé' : ($verification->selfie_status === 'rejected' ? 'Refusé' : 'En attente') }}
                                    </span>
                                </div>
                            </div>
                            @if($verification->selfie_status === 'rejected' && $verification->selfie_rejection_reason)
                                <div class="rejection-reason">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    {{ $verification->selfie_rejection_reason }}
                                </div>
                            @endif
                        </div>

                        {{-- Professional Document --}}
                        @if($verification->professional_document)
                        <div class="doc-status-card {{ $verification->professional_document_status === 'approved' ? 'doc-approved' : ($verification->professional_document_status === 'rejected' ? 'doc-rejected' : 'doc-pending') }}">
                            <span class="doc-status-icon">
                                @if($verification->professional_document_status === 'approved')
                                    <i class="fas fa-check-circle"></i>
                                @elseif($verification->professional_document_status === 'rejected')
                                    <i class="fas fa-times-circle"></i>
                                @else
                                    <i class="fas fa-clock"></i>
                                @endif
                            </span>
                            <div class="d-flex align-items-center gap-3">
                                @if($verification->professional_document_status === 'approved')
                                    <img src="{{ storage_url($verification->professional_document) }}" class="existing-doc-preview" alt="Doc pro">
                                @endif
                                <div>
                                    <h6 class="fw-bold mb-1">{{ $verification->getProfessionalDocumentLabel() }}</h6>
                                    <span class="badge {{ $verification->professional_document_status === 'approved' ? 'bg-success' : ($verification->professional_document_status === 'rejected' ? 'bg-danger' : 'bg-warning') }}">
                                        {{ $verification->professional_document_status === 'approved' ? 'Approuvé' : ($verification->professional_document_status === 'rejected' ? 'Refusé' : 'En attente') }}
                                    </span>
                                </div>
                            </div>
                            @if($verification->professional_document_status === 'rejected' && $verification->professional_document_rejection_reason)
                                <div class="rejection-reason">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    {{ $verification->professional_document_rejection_reason }}
                                </div>
                            @endif
                        </div>
                        @endif
                    </div>

                    {{-- Resubmission form - only for rejected documents --}}
                    @if($verification->hasRejectedDocuments())
                    <form action="{{ route('verification.resubmit') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <h6 class="fw-bold mb-3"><i class="fas fa-cloud-upload-alt me-2 text-primary"></i>Remplacer les documents refusés :</h6>

                        @if($verification->document_front_status === 'rejected')
                        <div class="mb-3">
                            <label class="upload-zone w-100" id="resubFrontZone">
                                <input type="file" name="document_front" accept="image/*,.pdf,application/pdf" class="d-none" id="resub_document_front" required>
                                <i class="fas fa-camera d-block"></i>
                                <h5 class="mb-1">Nouveau recto du document</h5>
                                <p class="text-danger small mb-0">Ce document a été refusé - veuillez le remplacer</p>
                                <img id="resubFrontPreview" class="upload-preview">
                            </label>
                            @error('document_front')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        @if($verification->document_back_status === 'rejected')
                        <div class="mb-3">
                            <label class="upload-zone w-100" id="resubBackZone">
                                <input type="file" name="document_back" accept="image/*,.pdf,application/pdf" class="d-none" id="resub_document_back" required>
                                <i class="fas fa-camera d-block"></i>
                                <h5 class="mb-1">Nouveau verso du document</h5>
                                <p class="text-danger small mb-0">Ce document a été refusé - veuillez le remplacer</p>
                                <img id="resubBackPreview" class="upload-preview">
                            </label>
                            @error('document_back')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        @if($verification->selfie_status === 'rejected')
                        <div class="mb-3">
                            <label class="upload-zone w-100" id="resubSelfieZone">
                                <input type="file" name="selfie" accept="image/*" class="d-none" id="resub_selfie" required>
                                <i class="fas fa-user-circle d-block"></i>
                                <h5 class="mb-1">Nouveau selfie avec document</h5>
                                <p class="text-danger small mb-0">Ce document a été refusé - veuillez le remplacer</p>
                                <img id="resubSelfiePreview" class="upload-preview">
                            </label>
                            @error('selfie')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        @if($verification->professional_document_status === 'rejected')
                        <div class="mb-3">
                            <label class="upload-zone w-100" id="resubProDocZone">
                                <input type="file" name="professional_document" accept="image/*,.pdf" class="d-none" id="resub_professional_document" required>
                                <i class="fas fa-building d-block"></i>
                                <h5 class="mb-1">Nouveau {{ $verification->getProfessionalDocumentLabel() }}</h5>
                                <p class="text-danger small mb-0">Ce document a été refusé - veuillez le remplacer</p>
                                <img id="resubProDocPreview" class="upload-preview">
                            </label>
                            @error('professional_document')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        <div class="verification-form-actions">
                            <a href="{{ route('home') }}" class="btn btn-outline-secondary verification-back-btn">
                                <i class="fas fa-arrow-left"></i><span>Retour</span>
                            </a>
                            <button type="submit" class="btn-verify verification-submit-btn">
                                <i class="fas fa-paper-plane"></i>
                                <span>Resoumettre ma demande</span>
                            </button>
                        </div>
                    </form>
                    @else
                    {{-- Fallback: no specific documents rejected, allow resubmitting all --}}
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        L'administrateur a renvoyé votre demande. Veuillez resoumettre tous vos documents.
                    </div>
                    <form action="{{ route('verification.resubmit') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="upload-zone w-100" id="resubFrontZoneAll">
                                <input type="file" name="document_front" accept="image/*,.pdf,application/pdf" class="d-none" id="resub_all_document_front" required>
                                <i class="fas fa-camera d-block"></i>
                                <h5 class="mb-1">Recto du document</h5>
                                <p class="text-muted small mb-0">Téléchargez une photo ou PDF du recto de votre document</p>
                                <img id="resubAllFrontPreview" class="upload-preview">
                            </label>
                            @error('document_front')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="upload-zone w-100" id="resubSelfieZoneAll">
                                <input type="file" name="selfie" accept="image/*" class="d-none" id="resub_all_selfie" required>
                                <i class="fas fa-user-circle d-block"></i>
                                <h5 class="mb-1">Selfie avec document</h5>
                                <p class="text-muted small mb-0">Photo de vous tenant votre document d'identité</p>
                                <img id="resubAllSelfiePreview" class="upload-preview">
                            </label>
                            @error('selfie')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="verification-form-actions">
                            <a href="{{ route('home') }}" class="btn btn-outline-secondary verification-back-btn">
                                <i class="fas fa-arrow-left"></i><span>Retour</span>
                            </a>
                            <button type="submit" class="btn-verify verification-submit-btn">
                                <i class="fas fa-paper-plane"></i>
                                <span>Resoumettre ma demande</span>
                            </button>
                        </div>
                    </form>
                    @endif
                </div>

            {{-- ===============================
                 AWAITING PAYMENT
                 =============================== --}}
            @elseif($verification && $verification->status === 'awaiting_payment')
            <div class="verification-card">
                <div class="text-center mb-4">
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 78px; height: 78px; background: rgba(37, 99, 235, 0.12);">
                        <i class="fas fa-shield-alt fa-2x" style="color: #2563eb;"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Confirmer votre demande</h4>
                    <p class="text-muted mb-0">
                        Vos documents ont bien été enregistrés. Confirmez cette dernière étape pour les transmettre à l’administration.
                    </p>
                </div>

                <div class="p-4 mb-4" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <div class="fw-bold mb-1">Frais de vérification</div>
                            <div class="text-muted small">Badge public “Profil vérifié” après validation administrateur.</div>
                        </div>
                        <div class="fs-4 fw-bold text-primary">
                            {{ number_format((float) $verification->payment_amount, 2, ',', ' ') }} €
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary btn-lg" onclick="startVerificationStripePayment({{ $verification->id }}, this)">
                        <i class="fas fa-lock me-2"></i>Continuer par carte
                    </button>
                    <form action="{{ route('verification.cancel') }}" method="POST" class="mt-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-times me-2"></i>Annuler cette demande
                        </button>
                    </form>
                </div>
            </div>

            {{-- ===============================
                 NEW FORM - First submission  
                 =============================== --}}
            @elseif(!$user->identity_verified && (!$verification || !$verification->isPending()))
            <div class="verification-card">
                <h4 class="mb-4" style="font-weight: 700;">
                    <i class="fas fa-upload me-2" style="color: var(--primary);"></i>
                    Soumettre vos documents
                </h4>
                
                <div class="info-box mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Pourquoi vérifier votre identité ?</strong>
                    <ul class="mb-0 mt-2">
                        <li>Obtenez le badge "Vérifié" sur votre profil</li>
                        <li>Augmentez la confiance des autres utilisateurs</li>
                        <li>Accédez à des fonctionnalités exclusives</li>
                        <li>Vos documents sont traités de manière confidentielle</li>
                    </ul>
                </div>

                <div class="verification-price-note">
                    <span><i class="fas fa-lock me-2"></i>Vos documents seront transmis à l’administration uniquement après le paiement sécurisé de <strong>5,00 €</strong>.</span>
                </div>

                <form id="pageVerificationForm" action="{{ route('verification.store') }}" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf

                    @if($errors->any())
                    <div class="alert alert-danger" style="border-radius: 12px;">
                        <strong><i class="fas fa-exclamation-triangle me-2"></i>Erreurs de validation :</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    
                    <!-- Step 1: Document Type -->
                    <div class="mb-4">
                        <label class="form-label fw-bold mb-3">1. Type de document d'identité *</label>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="document-type-card" data-type="id_card">
                                    <input type="radio" name="document_type" value="id_card" class="d-none" {{ old('document_type') == 'id_card' ? 'checked' : '' }} required>
                                    <i class="fas fa-id-card d-block"></i>
                                    <h5>Carte d'identité</h5>
                                    <p>CNI française ou européenne</p>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="document-type-card" data-type="passport">
                                    <input type="radio" name="document_type" value="passport" class="d-none" {{ old('document_type') == 'passport' ? 'checked' : '' }}>
                                    <i class="fas fa-passport d-block"></i>
                                    <h5>Passeport</h5>
                                    <p>Passeport valide</p>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="document-type-card" data-type="driver_license">
                                    <input type="radio" name="document_type" value="driver_license" class="d-none" {{ old('document_type') == 'driver_license' ? 'checked' : '' }}>
                                    <i class="fas fa-car d-block"></i>
                                    <h5>Permis de conduire</h5>
                                    <p>Permis français</p>
                                </label>
                            </div>
                        </div>
                        @error('document_type')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Step 2: Document Photos -->
                    <div class="mb-4">
                        <label class="form-label fw-bold mb-3">2. Photos du document *</label>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="upload-zone w-100" id="pageFrontZone">
                                    <input type="file" name="document_front" accept="image/*,.pdf,application/pdf" class="d-none" id="page_document_front">
                                    <input type="file" name="document_front_camera" accept="image/*" capture="environment" class="d-none" id="page_document_front_camera">
                                    <i class="fas fa-camera d-block"></i>
                                    <h5 class="mb-1" id="pageFrontTitle">Page d’identité ou recto</h5>
                                    <p class="text-muted small mb-0" id="pageFrontHelp">Prenez une photo nette ou choisissez un fichier</p>
                                    <div class="verification-upload-actions">
                                        <button type="button" class="verification-file-action" data-file-target="page_document_front">
                                            <i class="fas fa-folder-open"></i>Fichiers
                                        </button>
                                        <button type="button" class="verification-file-action is-camera" data-camera-target="page_document_front" data-camera-fallback="page_document_front_camera" data-camera-facing="environment">
                                            <i class="fas fa-camera"></i>Prendre une photo
                                        </button>
                                    </div>
                                    <img id="pageFrontPreview" class="upload-preview">
                                </div>
                                @error('document_front')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6" id="pageBackColumn">
                                <div class="upload-zone w-100" id="pageBackZone">
                                    <input type="file" name="document_back" accept="image/*,.pdf,application/pdf" class="d-none" id="page_document_back">
                                    <input type="file" name="document_back_camera" accept="image/*" capture="environment" class="d-none" id="page_document_back_camera">
                                    <i class="fas fa-camera d-block"></i>
                                    <h5 class="mb-1">Verso du document</h5>
                                    <p class="text-muted small mb-0">Carte d’identité, permis ou titre de séjour</p>
                                    <div class="verification-upload-actions">
                                        <button type="button" class="verification-file-action" data-file-target="page_document_back">
                                            <i class="fas fa-folder-open"></i>Fichiers
                                        </button>
                                        <button type="button" class="verification-file-action is-camera" data-camera-target="page_document_back" data-camera-fallback="page_document_back_camera" data-camera-facing="environment">
                                            <i class="fas fa-camera"></i>Prendre une photo
                                        </button>
                                    </div>
                                    <img id="pageBackPreview" class="upload-preview">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Selfie -->
                    <div class="mb-4">
                        <label class="form-label fw-bold mb-3">3. Photo selfie avec le document *</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="upload-zone w-100" id="pageSelfieZone">
                                    <input type="file" name="selfie" accept="image/*" class="d-none" id="page_selfie">
                                    <input type="file" name="selfie_camera" accept="image/*" capture="user" class="d-none" id="page_selfie_camera">
                                    <i class="fas fa-user-circle d-block"></i>
                                    <h5 class="mb-1">Selfie avec document</h5>
                                    <p class="text-muted small mb-0">Tenez le document à côté de votre visage</p>
                                    <div class="verification-upload-actions">
                                        <button type="button" class="verification-file-action" data-file-target="page_selfie">
                                            <i class="fas fa-folder-open"></i>Fichiers
                                        </button>
                                        <button type="button" class="verification-file-action is-camera" data-camera-target="page_selfie" data-camera-fallback="page_selfie_camera" data-camera-facing="user">
                                            <i class="fas fa-camera"></i>Prendre une photo
                                        </button>
                                    </div>
                                    <img id="pageSelfiePreview" class="upload-preview">
                                </div>
                                @error('selfie')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 d-flex align-items-center">
                                <div class="p-3">
                                    <h6 class="fw-bold mb-2"><i class="fas fa-lightbulb text-warning me-2"></i>Conseils</h6>
                                    <ul class="small text-muted mb-0">
                                        <li>Assurez-vous que le document est lisible</li>
                                        <li>Bonne luminosité sans reflets</li>
                                        <li>Votre visage doit être visible</li>
                                        <li>Format accepté : JPEG, PNG, PDF (max 15 Mo)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Professional Document (conditional) -->
                    @if(isset($requiresProDoc) && $requiresProDoc)
                    <div class="mb-4">
                        <label class="form-label fw-bold mb-3">
                            4. Document professionnel *
                            <span class="text-muted fw-normal ms-2" style="font-size: 0.85rem;">
                                @if(isset($proDocType) && $proDocType === 'kbis')
                                    (Extrait Kbis - requis pour les entreprises)
                                @elseif(isset($proDocType) && $proDocType === 'sirene')
                                    (Avis de situation SIRENE - requis pour les auto-entrepreneurs)
                                @endif
                            </span>
                        </label>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="upload-zone w-100" id="pageProDocZone">
                                    <input type="file" name="professional_document" accept="image/*,.pdf" class="d-none" id="page_professional_document" required>
                                    <input type="hidden" name="professional_document_type" value="{{ $proDocType ?? '' }}">
                                    <i class="fas fa-building d-block"></i>
                                    <h5 class="mb-1">
                                        @if(isset($proDocType) && $proDocType === 'kbis')
                                            Extrait Kbis
                                        @elseif(isset($proDocType) && $proDocType === 'sirene')
                                            Avis SIRENE
                                        @else
                                            Document professionnel
                                        @endif
                                    </h5>
                                    <p class="text-muted small mb-0">PDF ou image (max 5MB)</p>
                                    <img id="pageProDocPreview" class="upload-preview">
                                </label>
                                @error('professional_document')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 d-flex align-items-center">
                                <div class="p-3">
                                    <h6 class="fw-bold mb-2"><i class="fas fa-info-circle text-primary me-2"></i>Information</h6>
                                    <ul class="small text-muted mb-0">
                                        @if(isset($proDocType) && $proDocType === 'kbis')
                                            <li>Extrait Kbis de moins de 3 mois</li>
                                            <li>Téléchargeable sur infogreffe.fr</li>
                                            <li>Doit mentionner votre entreprise</li>
                                        @elseif(isset($proDocType) && $proDocType === 'sirene')
                                            <li>Avis de situation SIRENE</li>
                                            <li>Téléchargeable sur sirene.fr</li>
                                            <li>Doit mentionner votre activité</li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="verification-form-actions">
                        <a href="{{ route('home') }}" class="btn btn-outline-secondary verification-back-btn">
                            <i class="fas fa-arrow-left"></i><span>Retour</span>
                        </a>
                        <div class="verification-submit-wrap">
                            @if(isset($missingFields) && count($missingFields) > 0)
                                <div class="text-danger small mb-2 text-end">
                                    <i class="fas fa-exclamation-circle me-1"></i>Complétez votre profil pour soumettre
                                </div>
                            @endif
                            <button type="submit" class="btn-verify verification-submit-btn" id="submitVerificationBtn" {{ (isset($missingFields) && count($missingFields) > 0) ? 'disabled' : '' }}>
                                <i class="fas fa-lock"></i>
                                <span>Continuer vers le paiement · 5 €</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- ===============================
                 PENDING - Waiting for review  
                 =============================== --}}
            @elseif($verification && $verification->isPending())
            <div class="verification-card">
                <div class="text-center mb-4">
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: rgba(245, 158, 11, 0.15);">
                        <i class="fas fa-hourglass-half fa-2x" style="color: #f59e0b;"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Demande en cours de traitement</h4>
                    <p class="text-muted">
                        Soumise le <strong>{{ $verification->submitted_at?->format('d/m/Y à H:i') ?? $verification->created_at->format('d/m/Y à H:i') }}</strong>
                    </p>
                </div>

                {{-- Étapes du processus --}}
                <div class="mb-4" style="background: #f8fafc; border-radius: 12px; padding: 24px;">
                    <h6 class="fw-bold mb-3"><i class="fas fa-list-ol me-2 text-primary"></i>Processus de vérification</h6>
                    <div class="d-flex align-items-start mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-3" style="width: 32px; height: 32px; background: #10b981; color: white; font-size: 0.85rem; font-weight: 700;">1</div>
                        <div>
                            <div class="fw-semibold" style="color: #10b981;"><i class="fas fa-check me-1"></i>Documents envoyés</div>
                            <small class="text-muted">Vos documents ont bien été transmis à notre équipe</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-start mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-3" style="width: 32px; height: 32px; background: #f59e0b; color: white; font-size: 0.85rem; font-weight: 700;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 0.75rem;"></i>
                        </div>
                        <div>
                            <div class="fw-semibold" style="color: #f59e0b;">Examen en cours</div>
                            <small class="text-muted">Un administrateur vérifie la conformité de vos documents</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-start">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 me-3" style="width: 32px; height: 32px; background: #e2e8f0; color: #94a3b8; font-size: 0.85rem; font-weight: 700;">3</div>
                        <div>
                            <div class="fw-semibold text-muted">Résultat</div>
                            <small class="text-muted">Vous recevrez une notification avec le résultat de la vérification</small>
                        </div>
                    </div>
                </div>

                {{-- Documents soumis récapitulatif --}}
                <div class="mb-4" style="background: #f8fafc; border-radius: 12px; padding: 24px;">
                    <h6 class="fw-bold mb-3"><i class="fas fa-file-alt me-2 text-info"></i>Documents soumis</h6>
                    <div class="row g-2">
                        <div class="col-6 col-md-3">
                            <div class="text-center p-2" style="background: white; border-radius: 8px; border: 1px solid #e2e8f0;">
                                <i class="fas fa-id-card d-block mb-1" style="color: #6366f1;"></i>
                                <small class="text-muted d-block">Recto</small>
                                <i class="fas fa-check-circle text-success" style="font-size: 0.75rem;"></i>
                            </div>
                        </div>
                        @if($verification->document_back)
                        <div class="col-6 col-md-3">
                            <div class="text-center p-2" style="background: white; border-radius: 8px; border: 1px solid #e2e8f0;">
                                <i class="fas fa-id-card-alt d-block mb-1" style="color: #6366f1;"></i>
                                <small class="text-muted d-block">Verso</small>
                                <i class="fas fa-check-circle text-success" style="font-size: 0.75rem;"></i>
                            </div>
                        </div>
                        @endif
                        <div class="col-6 col-md-3">
                            <div class="text-center p-2" style="background: white; border-radius: 8px; border: 1px solid #e2e8f0;">
                                <i class="fas fa-camera d-block mb-1" style="color: #6366f1;"></i>
                                <small class="text-muted d-block">Selfie</small>
                                <i class="fas fa-check-circle text-success" style="font-size: 0.75rem;"></i>
                            </div>
                        </div>
                        @if($verification->professional_document)
                        <div class="col-6 col-md-3">
                            <div class="text-center p-2" style="background: white; border-radius: 8px; border: 1px solid #e2e8f0;">
                                <i class="fas fa-building d-block mb-1" style="color: #6366f1;"></i>
                                <small class="text-muted d-block">Doc pro</small>
                                <i class="fas fa-check-circle text-success" style="font-size: 0.75rem;"></i>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="text-center">
                    <form action="{{ route('verification.cancel') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir annuler votre demande ? Vos documents seront supprimés.')">
                            <i class="fas fa-times me-2"></i>Annuler ma demande
                        </button>
                    </form>
                </div>
            </div>

            {{-- ===============================
                 VERIFIED - Success  
                 =============================== --}}
            @elseif($user->identity_verified)
            <div class="verification-card text-center">
                <i class="fas fa-check-circle fa-4x text-success mb-4"></i>
                <h4 class="fw-bold mb-3">Identité vérifiée !</h4>
                <p class="text-muted mb-4">
                    Félicitations ! Votre identité a été vérifiée avec succès.<br>
                    Le badge "Vérifié" apparaît désormais sur votre profil.
                </p>
                <a href="{{ route('profile.show') }}" class="btn btn-primary">
                    <i class="fas fa-user me-2"></i>Voir mon profil
                </a>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="verification-camera-overlay" id="verificationCameraOverlay" hidden aria-hidden="true">
    <div class="verification-camera-panel" role="dialog" aria-modal="true" aria-labelledby="verificationCameraTitle">
        <div class="verification-camera-header">
            <strong id="verificationCameraTitle">Prendre une photo</strong>
            <button type="button" class="verification-camera-close" id="verificationCameraClose" aria-label="Fermer l’appareil photo">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <video class="verification-camera-video" id="verificationCameraVideo" autoplay playsinline muted></video>
        <canvas id="verificationCameraCanvas" hidden></canvas>
        <div class="verification-camera-actions">
            <button type="button" class="verification-camera-switch" id="verificationCameraSwitch" title="Changer de caméra" aria-label="Changer de caméra">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="verification-camera-capture" id="verificationCameraCapture">
                <i class="fas fa-camera"></i>
                <span>Utiliser cette photo</span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const verificationImageTasks = new Set();
    const verificationCameraState = {
        stream: null,
        targetId: null,
        fallbackId: null,
        facingMode: 'environment'
    };

    function resetVerificationUpload(inputIds, previewId, zoneId) {
        inputIds.forEach(id => {
            const input = document.getElementById(id);
            if (input) input.value = '';
        });

        const preview = document.getElementById(previewId);
        if (preview) {
            if (preview.dataset.objectUrl) URL.revokeObjectURL(preview.dataset.objectUrl);
            preview.removeAttribute('src');
            preview.removeAttribute('data-object-url');
            preview.style.display = 'none';
        }

        const zone = document.getElementById(zoneId);
        zone?.classList.remove('has-file', 'validation-error', 'is-processing');
        zone?.querySelector('.verification-file-selected')?.remove();
    }

    function updateDocumentForm(options = {}) {
        const selectedType = document.querySelector('#pageVerificationForm input[name="document_type"]:checked')?.value;
        const backColumn = document.getElementById('pageBackColumn');
        const frontTitle = document.getElementById('pageFrontTitle');
        const frontHelp = document.getElementById('pageFrontHelp');
        const isPassport = selectedType === 'passport';

        if (!backColumn) return;

        if (options.resetFront) {
            resetVerificationUpload(
                ['page_document_front', 'page_document_front_camera'],
                'pageFrontPreview',
                'pageFrontZone'
            );
        }

        if (!selectedType) {
            if (frontTitle) frontTitle.textContent = 'Page d’identité ou recto';
            if (frontHelp) frontHelp.textContent = 'Prenez une photo nette ou choisissez un fichier';
        } else if (isPassport) {
            if (frontTitle) frontTitle.textContent = 'Page d’identité du passeport';
            if (frontHelp) frontHelp.textContent = 'Page avec votre photo et vos informations';
        } else if (selectedType === 'driver_license') {
            if (frontTitle) frontTitle.textContent = 'Recto du permis de conduire';
            if (frontHelp) frontHelp.textContent = 'Face avec votre photo et vos informations';
        } else {
            if (frontTitle) frontTitle.textContent = 'Recto de la carte d’identité';
            if (frontHelp) frontHelp.textContent = 'Face avec votre photo et vos informations';
        }

        backColumn.hidden = isPassport;
        backColumn.setAttribute('aria-hidden', isPassport ? 'true' : 'false');

        if (isPassport) {
            resetVerificationUpload(
                ['page_document_back', 'page_document_back_camera'],
                'pageBackPreview',
                'pageBackZone'
            );
        }
    }

    // Document type selection
    let previousDocumentType = document.querySelector('#pageVerificationForm input[name="document_type"]:checked')?.value || null;
    document.querySelectorAll('input[name="document_type"]').forEach(input => {
        // Set initial state for old values
        if (input.checked) {
            const card = input.closest('.document-type-card');
            if (card) card.classList.add('selected');
        }
        input.addEventListener('change', () => {
            document.querySelectorAll('.document-type-card').forEach(card => card.classList.remove('selected'));
            const card = input.closest('.document-type-card');
            if (card) card.classList.add('selected');
            const hasFrontFile = ['page_document_front', 'page_document_front_camera']
                .some(id => document.getElementById(id)?.files?.length > 0);
            const documentChanged = previousDocumentType !== input.value
                && (previousDocumentType !== null || hasFrontFile);
            previousDocumentType = input.value;
            updateDocumentForm({ resetFront: documentChanged });
        });
    });
    updateDocumentForm();

    document.querySelectorAll('[data-file-target]').forEach(button => {
        button.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();
            document.getElementById(button.dataset.fileTarget)?.click();
        });
    });

    function replaceVerificationInputFile(input, file) {
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
    }

    async function optimizeVerificationImage(file) {
        if (!file.type.startsWith('image/') || file.type === 'image/svg+xml' || file.type === 'image/gif') {
            return file;
        }

        let bitmap;
        try {
            bitmap = await createImageBitmap(file);
        } catch (error) {
            return file;
        }

        const maxDimension = 1800;
        const ratio = Math.min(1, maxDimension / Math.max(bitmap.width, bitmap.height));
        const width = Math.max(1, Math.round(bitmap.width * ratio));
        const height = Math.max(1, Math.round(bitmap.height * ratio));

        if (ratio === 1 && file.size <= 2500000) {
            bitmap.close?.();
            return file;
        }

        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        canvas.getContext('2d', { alpha: false }).drawImage(bitmap, 0, 0, width, height);
        bitmap.close?.();

        const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.84));
        if (!blob || blob.size >= file.size) return file;

        const baseName = file.name.replace(/\.[^.]+$/, '') || 'document';
        return new File([blob], `${baseName}.jpg`, {
            type: 'image/jpeg',
            lastModified: Date.now()
        });
    }

    function renderVerificationFile(file, preview, zone) {
        zone.querySelector('.verification-file-selected')?.remove();
        const selected = document.createElement('div');
        selected.className = 'verification-file-selected';
        const selectedIcon = document.createElement('i');
        selectedIcon.className = 'fas fa-check-circle me-1';
        selected.append(selectedIcon, document.createTextNode(file.name));
        zone.appendChild(selected);

        if (preview.dataset.objectUrl) URL.revokeObjectURL(preview.dataset.objectUrl);
        if (file.type.startsWith('image/')) {
            const objectUrl = URL.createObjectURL(file);
            preview.src = objectUrl;
            preview.dataset.objectUrl = objectUrl;
            preview.style.display = 'block';
        } else {
            preview.removeAttribute('src');
            preview.removeAttribute('data-object-url');
            preview.style.display = 'none';
        }

        zone.classList.add('has-file');
        zone.classList.remove('validation-error', 'is-processing');
    }

    function processVerificationFile(input, file, preview, zone) {
        zone.classList.add('is-processing');
        const task = optimizeVerificationImage(file)
            .then(optimizedFile => {
                if (!input.files?.length || input.files[0] !== file) return;
                if (optimizedFile !== file) replaceVerificationInputFile(input, optimizedFile);
                renderVerificationFile(optimizedFile, preview, zone);
            })
            .catch(() => {
                if (input.files?.length && input.files[0] === file) {
                    renderVerificationFile(file, preview, zone);
                }
            })
            .finally(() => verificationImageTasks.delete(task));

        verificationImageTasks.add(task);
        return task;
    }

    // File upload preview and mobile image optimization
    function setupFilePreview(inputId, previewId, zoneId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        const zone = document.getElementById(zoneId);
        
        if (input && preview && zone) {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    processVerificationFile(input, file, preview, zone);
                }
            });
        }
    }

    function stopVerificationCamera() {
        verificationCameraState.stream?.getTracks().forEach(track => track.stop());
        verificationCameraState.stream = null;
        const video = document.getElementById('verificationCameraVideo');
        if (video) video.srcObject = null;
    }

    function closeVerificationCamera() {
        stopVerificationCamera();
        const overlay = document.getElementById('verificationCameraOverlay');
        overlay.hidden = true;
        overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('verification-camera-open');
    }

    async function startVerificationCamera() {
        const overlay = document.getElementById('verificationCameraOverlay');
        const video = document.getElementById('verificationCameraVideo');

        stopVerificationCamera();
        verificationCameraState.stream = await navigator.mediaDevices.getUserMedia({
            audio: false,
            video: {
                facingMode: { ideal: verificationCameraState.facingMode },
                width: { ideal: 1920 },
                height: { ideal: 1440 }
            }
        });
        video.srcObject = verificationCameraState.stream;
        overlay.hidden = false;
        overlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('verification-camera-open');
        await video.play();
    }

    document.querySelectorAll('[data-camera-target]').forEach(button => {
        button.addEventListener('click', async event => {
            event.preventDefault();
            event.stopPropagation();
            verificationCameraState.targetId = button.dataset.cameraTarget;
            verificationCameraState.fallbackId = button.dataset.cameraFallback;
            verificationCameraState.facingMode = button.dataset.cameraFacing || 'environment';

            if (!navigator.mediaDevices?.getUserMedia) {
                document.getElementById(verificationCameraState.fallbackId)?.click();
                return;
            }

            try {
                await startVerificationCamera();
            } catch (error) {
                closeVerificationCamera();
                alert('L’appareil photo n’est pas accessible. Autorisez la caméra dans votre navigateur ou utilisez le bouton Fichiers.');
            }
        });
    });

    document.getElementById('verificationCameraClose')?.addEventListener('click', closeVerificationCamera);
    document.getElementById('verificationCameraSwitch')?.addEventListener('click', async () => {
        verificationCameraState.facingMode = verificationCameraState.facingMode === 'user' ? 'environment' : 'user';
        try {
            await startVerificationCamera();
        } catch (error) {
            closeVerificationCamera();
            alert('Impossible de changer de caméra.');
        }
    });
    document.getElementById('verificationCameraCapture')?.addEventListener('click', () => {
        const video = document.getElementById('verificationCameraVideo');
        const canvas = document.getElementById('verificationCameraCanvas');
        const input = document.getElementById(verificationCameraState.targetId);
        if (!video || !canvas || !input || !video.videoWidth || !video.videoHeight) return;

        const maxDimension = 1800;
        const ratio = Math.min(1, maxDimension / Math.max(video.videoWidth, video.videoHeight));
        canvas.width = Math.max(1, Math.round(video.videoWidth * ratio));
        canvas.height = Math.max(1, Math.round(video.videoHeight * ratio));
        canvas.getContext('2d', { alpha: false }).drawImage(video, 0, 0, canvas.width, canvas.height);
        canvas.toBlob(blob => {
            if (!blob) return;
            const file = new File([blob], `verification-${Date.now()}.jpg`, {
                type: 'image/jpeg',
                lastModified: Date.now()
            });
            replaceVerificationInputFile(input, file);
            input.dispatchEvent(new Event('change', { bubbles: true }));
            closeVerificationCamera();
        }, 'image/jpeg', 0.84);
    });
    window.addEventListener('pagehide', stopVerificationCamera);

    // New submission form
    setupFilePreview('page_document_front', 'pageFrontPreview', 'pageFrontZone');
    setupFilePreview('page_document_front_camera', 'pageFrontPreview', 'pageFrontZone');
    setupFilePreview('page_document_back', 'pageBackPreview', 'pageBackZone');
    setupFilePreview('page_document_back_camera', 'pageBackPreview', 'pageBackZone');
    setupFilePreview('page_selfie', 'pageSelfiePreview', 'pageSelfieZone');
    setupFilePreview('page_selfie_camera', 'pageSelfiePreview', 'pageSelfieZone');
    setupFilePreview('page_professional_document', 'pageProDocPreview', 'pageProDocZone');

    // Resubmission form
    setupFilePreview('resub_document_front', 'resubFrontPreview', 'resubFrontZone');
    setupFilePreview('resub_document_back', 'resubBackPreview', 'resubBackZone');
    setupFilePreview('resub_selfie', 'resubSelfiePreview', 'resubSelfieZone');
    setupFilePreview('resub_professional_document', 'resubProDocPreview', 'resubProDocZone');

    // Fallback full resubmission form
    setupFilePreview('resub_all_document_front', 'resubAllFrontPreview', 'resubFrontZoneAll');
    setupFilePreview('resub_all_selfie', 'resubAllSelfiePreview', 'resubSelfieZoneAll');

    function setVerificationPaymentButtonLoading(button, loadingText) {
        if (!button) return;
        button.disabled = true;
        button.dataset.originalHtml = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>' + loadingText;
    }

    function restoreVerificationPaymentButton(button) {
        if (!button) return;
        button.disabled = false;
        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
        }
    }

    function showVerificationPaymentError(message) {
        alert(message || 'Impossible de lancer le paiement. Veuillez réessayer.');
    }

    function startVerificationStripePayment(verificationId, button) {
        setVerificationPaymentButtonLoading(button, 'Redirection sécurisée...');

        fetch('{{ route("verification.create.payment") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ verification_id: verificationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.checkout_url) {
                window.location.href = data.checkout_url;
                return;
            }

            restoreVerificationPaymentButton(button);
            showVerificationPaymentError(data.message);
        })
        .catch(() => {
            restoreVerificationPaymentButton(button);
            showVerificationPaymentError('Erreur de connexion au service de paiement.');
        });
    }

    function payVerificationWithPoints(verificationId, button) {
        setVerificationPaymentButtonLoading(button, 'Validation...');

        fetch('{{ route("verification.pay.points") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ verification_id: verificationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
                return;
            }

            restoreVerificationPaymentButton(button);
            showVerificationPaymentError(data.message);
        })
        .catch(() => {
            restoreVerificationPaymentButton(button);
            showVerificationPaymentError('Erreur de connexion pendant le paiement par points.');
        });
    }

    // Custom form validation — browser native validation fails silently on hidden inputs
    document.querySelectorAll('form[action*="verification"]').forEach(function(form) {
        form.setAttribute('novalidate', 'novalidate');
        form.addEventListener('submit', function(e) {
            if (verificationImageTasks.size > 0) {
                e.preventDefault();
                Promise.allSettled(Array.from(verificationImageTasks)).then(() => form.requestSubmit());
                return;
            }

            // Remove previous error messages
            form.querySelectorAll('.js-validation-error').forEach(el => el.remove());
            form.querySelectorAll('.upload-zone.validation-error').forEach(el => el.classList.remove('validation-error'));
            form.querySelectorAll('.document-type-card.validation-error').forEach(el => el.classList.remove('validation-error'));

            let errors = [];
            let firstErrorEl = null;

            // Check document type radio
            const docTypeInput = form.querySelector('input[name="document_type"]');
            if (docTypeInput) {
                const checked = form.querySelector('input[name="document_type"]:checked');
                if (!checked) {
                    errors.push('Veuillez sélectionner un type de document');
                    form.querySelectorAll('.document-type-card').forEach(c => c.classList.add('validation-error'));
                    if (!firstErrorEl) firstErrorEl = form.querySelector('.document-type-card');
                }
            }

            // Check required file inputs within THIS form
            let errors_found = [];
            form.querySelectorAll('input[type="file"][required]').forEach(function(input) {
                if (!input.files || input.files.length === 0) {
                    const zone = input.closest('.upload-zone');
                    const label = input.name === 'document_front' ? 'le recto du document' 
                                : input.name === 'selfie' ? 'votre selfie avec le document'
                                : input.name === 'professional_document' ? 'le document professionnel'
                                : input.name === 'document_back' ? 'le verso du document'
                                : input.name;
                    errors_found.push({ label: label, zone: zone });
                }
            });

            if (form.id === 'pageVerificationForm') {
                const selectedDocumentType = form.querySelector('input[name="document_type"]:checked')?.value;
                const frontLabel = selectedDocumentType === 'passport'
                    ? 'la page d’identité du passeport'
                    : selectedDocumentType === 'driver_license'
                        ? 'le recto du permis de conduire'
                        : 'le recto de la carte d’identité';
                const requiredGroups = [
                    {
                        inputs: ['page_document_front', 'page_document_front_camera'],
                        label: frontLabel,
                        zone: document.getElementById('pageFrontZone')
                    },
                    {
                        inputs: ['page_selfie', 'page_selfie_camera'],
                        label: 'votre selfie avec le document',
                        zone: document.getElementById('pageSelfieZone')
                    }
                ];

                requiredGroups.forEach(group => {
                    const hasFile = group.inputs.some(id => {
                        const input = document.getElementById(id);
                        return input?.files?.length > 0;
                    });

                    if (!hasFile) {
                        errors_found.push({ label: group.label, zone: group.zone });
                    }
                });
            }

            const fileChecks = errors_found;

            fileChecks.forEach(function(check) {
                errors.push('Veuillez télécharger ' + check.label);
                if (check.zone) {
                    check.zone.classList.add('validation-error');
                    if (!firstErrorEl) firstErrorEl = check.zone;
                }
            });

            if (errors.length > 0) {
                e.preventDefault();
                // Show error alert at top of form
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger js-validation-error';
                alertDiv.style.cssText = 'border-radius: 12px; margin-bottom: 20px;';
                alertDiv.innerHTML = '<strong><i class="fas fa-exclamation-triangle me-2"></i>Veuillez corriger les erreurs suivantes :</strong><ul class="mb-0 mt-2">' +
                    errors.map(err => '<li>' + err + '</li>').join('') + '</ul>';
                form.insertBefore(alertDiv, form.firstChild);

                // Scroll to first error
                if (firstErrorEl) {
                    firstErrorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }

            // Disable button to prevent double submit
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Envoi en cours...';
            }
        });
    });

    // Handle click on disabled submit button — scroll to missing fields warning
    const submitBtn = document.getElementById('submitVerificationBtn');
    if (submitBtn && submitBtn.disabled) {
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const missingBox = document.querySelector('.missing-fields-box');
            if (missingBox) {
                missingBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                missingBox.style.animation = 'pulse 0.5s ease 2';
            }
        });
        // Disabled buttons don't fire click events — use a wrapper
        submitBtn.parentElement.addEventListener('click', function(e) {
            if (submitBtn.disabled) {
                const missingBox = document.querySelector('.missing-fields-box');
                if (missingBox) {
                    missingBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    missingBox.style.animation = 'none';
                    setTimeout(() => missingBox.style.animation = 'pulse 0.5s ease 2', 10);
                }
            }
        });
    }
</script>
@endpush
@endsection

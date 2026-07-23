@extends('layouts.app')

@section('title', ($type === 'quote' ? 'Devis' : 'Facture') . ' créé(e) - Lunamars')

@section('content')
<style>
    .dl-card {
        border: none; border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 8px 24px rgba(0,0,0,0.06);
        overflow: hidden;
    }
    .dl-header {
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: white; padding: 2rem 2rem 1.5rem;
    }
    .dl-header .icon { width: 56px; height: 56px; background: rgba(255,255,255,0.15); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem; }
    .dl-body { padding: 2rem; }
    .dl-info-row { display: flex; justify-content: space-between; padding: 0.6rem 0; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
    .dl-info-row:last-child { border-bottom: none; }
    .dl-info-label { color: #64748b; font-weight: 500; }
    .dl-info-value { color: #1e293b; font-weight: 600; }
    .btn-download {
        background: linear-gradient(135deg, #6366f1, #4f46e5); border: none;
        border-radius: 12px; color: #fff; font-weight: 600; font-size: 1.05rem;
        padding: 1rem 2rem; width: 100%; transition: opacity 0.2s, transform 0.15s;
        box-shadow: 0 4px 14px rgba(99,102,241,0.35); text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
    }
    .btn-download:hover { opacity: 0.92; color: #fff; transform: translateY(-1px); }
    .action-card {
        border: none; border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06); padding: 1.25rem;
        display: flex; align-items: center; gap: 1rem; text-decoration: none; color: inherit;
        transition: box-shadow 0.2s, transform 0.15s;
    }
    .action-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.1); transform: translateY(-2px); color: inherit; text-decoration: none; }
    .action-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
</style>

<div class="container py-4" style="max-width: 720px;">

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center" style="border-radius: 12px; border: 1px solid #a7f3d0; background: #ecfdf5; color: #065f46;">
            <i class="fas fa-check-circle me-2" style="font-size: 1.2rem;"></i>
            {{ session('success') }}
        </div>
    @endif

    <div class="dl-card mb-4">
        <div class="dl-header">
            <div class="icon">
                <i class="fas {{ $type === 'quote' ? 'fa-file-alt' : 'fa-file-invoice' }}"></i>
            </div>
            <h1 style="font-size: 1.4rem; font-weight: 700; margin: 0 0 0.25rem;">
                {{ $type === 'quote' ? 'Devis' : 'Facture' }} {{ $type === 'quote' ? $document->quote_number : $document->invoice_number }}
            </h1>
            <p style="opacity: 0.8; font-size: 0.9rem; margin: 0;">
                Votre document est pret a etre telecharge.
            </p>
        </div>
        <div class="dl-body">
            <div class="dl-info-row">
                <span class="dl-info-label">Client</span>
                <span class="dl-info-value">{{ $document->client_name }}</span>
            </div>
            @if($type === 'quote' && $document->subject)
                <div class="dl-info-row">
                    <span class="dl-info-label">Objet</span>
                    <span class="dl-info-value">{{ $document->subject }}</span>
                </div>
            @endif
            <div class="dl-info-row">
                <span class="dl-info-label">Total TTC</span>
                <span class="dl-info-value" style="font-size: 1.1rem; color: #4f46e5;">{{ number_format($document->total, 2, ',', ' ') }} &euro;</span>
            </div>
            <div class="dl-info-row">
                <span class="dl-info-label">Date de creation</span>
                <span class="dl-info-value">{{ $document->created_at->format('d/m/Y') }}</span>
            </div>
            @if($type === 'quote' && $document->valid_until)
                <div class="dl-info-row">
                    <span class="dl-info-label">Valide jusqu'au</span>
                    <span class="dl-info-value">{{ \Carbon\Carbon::parse($document->valid_until)->format('d/m/Y') }}</span>
                </div>
            @endif
            @if($type === 'invoice' && $document->due_date)
                <div class="dl-info-row">
                    <span class="dl-info-label">Echeance</span>
                    <span class="dl-info-value">{{ \Carbon\Carbon::parse($document->due_date)->format('d/m/Y') }}</span>
                </div>
            @endif
            @if($type === 'invoice')
                <div class="dl-info-row">
                    <span class="dl-info-label">Statut de paiement</span>
                    <span class="dl-info-value">{{ $document->status === 'paid' ? 'Payée' : 'Non payée' }}</span>
                </div>
            @endif

            <div class="mt-4">
                <a href="{{ route('quote-tool.download', ['token' => $token]) }}?pdf=1" class="btn-download">
                    <i class="fas fa-download"></i> Telecharger le PDF
                </a>
            </div>
        </div>
    </div>

    <h6 class="fw-bold text-muted mb-3" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">Que souhaitez-vous faire ensuite ?</h6>

    <div class="row g-3">
        @if($creditsRemaining > 0 || $creditsRemaining === -1)
            <div class="col-md-6">
                <a href="{{ route('quote-tool.quote.create') }}" class="action-card bg-white">
                    <div class="action-icon" style="background: #eef2ff; color: #6366f1;">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size: 0.92rem;">Creer un devis</div>
                        <div class="text-muted" style="font-size: 0.8rem;">
                            @if($creditsRemaining === -1)
                                Inclus dans votre abonnement
                            @else
                                {{ $creditsRemaining }} credit(s) restant(s)
                            @endif
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6">
                <a href="{{ route('quote-tool.invoice.create') }}" class="action-card bg-white">
                    <div class="action-icon" style="background: #f0fdf4; color: #16a34a;">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size: 0.92rem;">Creer une facture</div>
                        <div class="text-muted" style="font-size: 0.8rem;">
                            @if($creditsRemaining === -1)
                                Inclus dans votre abonnement
                            @else
                                {{ $creditsRemaining }} credit(s) restant(s)
                            @endif
                        </div>
                    </div>
                </a>
            </div>
        @else
            <div class="col-12">
                <a href="{{ route('quote-tool.credits') }}" class="action-card bg-white">
                    <div class="action-icon" style="background: #fef3c7; color: #d97706;">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size: 0.92rem;">Acheter des credits</div>
                        <div class="text-muted" style="font-size: 0.8rem;">A partir de 4,99 EUR pour 5 documents</div>
                    </div>
                </a>
            </div>
        @endif
        <div class="col-md-6">
            <a href="{{ route('quote-tool.landing') }}" class="action-card bg-white">
                <div class="action-icon" style="background: #f8f8fc; color: #64748b;">
                    <i class="fas fa-arrow-left"></i>
                </div>
                <div>
                    <div class="fw-bold" style="font-size: 0.92rem;">Retour a l'accueil</div>
                    <div class="text-muted" style="font-size: 0.8rem;">Page de l'outil devis/facture</div>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection

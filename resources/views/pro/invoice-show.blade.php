@extends('pro.layout')
@section('title', 'Facture ' . $invoice->invoice_number . ' - Espace Pro')

@section('content')
<style>
    .invoice-logo-block {
        display: inline-block;
        font-size: 1.25rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: 0.3px;
        padding-bottom: 8px;
        border-bottom: 3px solid #6366f1;
        margin-bottom: 10px;
    }
    .invoice-doc-title {
        font-size: 1.8rem;
        font-weight: 800;
        color: #6366f1;
        letter-spacing: 2px;
    }
    .invoice-info-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 20px;
    }
    .invoice-info-label {
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        color: #94a3b8;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .invoice-info-name {
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 4px;
    }
    .invoice-info-detail {
        font-size: 0.82rem;
        color: #64748b;
        line-height: 1.6;
    }
    .invoice-totals {
        min-width: 280px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 0;
        overflow: hidden;
    }
    .invoice-totals .total-line {
        display: flex;
        justify-content: space-between;
        padding: 10px 18px;
        font-size: 0.88rem;
        color: #475569;
    }
    .invoice-totals .total-line-grand {
        display: flex;
        justify-content: space-between;
        padding: 14px 18px;
        font-size: 1.15rem;
        font-weight: 800;
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: #ffffff;
    }
    .invoice-actions-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 20px;
    }
    .invoice-actions-bar .btn {
        border-radius: 10px;
        font-size: 0.85rem;
        padding: 8px 18px;
    }
    .btn-download-pdf {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #fff;
        border: none;
    }
    .btn-download-pdf:hover {
        background: linear-gradient(135deg, #059669, #047857);
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
    }
</style>

<div class="pro-content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 0.8rem;">
                <li class="breadcrumb-item"><a href="{{ route('pro.dashboard') }}" style="color: var(--pro-primary);">Espace Pro</a></li>
                <li class="breadcrumb-item"><a href="{{ route('pro.invoices') }}" style="color: var(--pro-primary);">Factures</a></li>
                <li class="breadcrumb-item active">{{ $invoice->invoice_number }}</li>
            </ol>
        </nav>
        <h1>Facture {{ $invoice->invoice_number }}</h1>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <span class="pro-status pro-status-{{ $invoice->getStatusColor() }}" style="font-size: 0.88rem; padding: 8px 16px;">{{ $invoice->getStatusLabel() }}</span>
        @if(in_array($invoice->status, ['draft', 'sent', 'overdue']))
            <form method="POST" action="{{ route('pro.invoices.status', $invoice->id) }}" class="d-inline">
                @csrf @method('PUT')
                <input type="hidden" name="status" value="paid">
                <input type="hidden" name="payment_method" value="other">
                <button class="btn btn-pro-primary btn-sm"><i class="fas fa-check me-1"></i> Marquer payée</button>
            </form>
        @endif
    </div>
</div>

@if($invoice->status === 'draft' && ! $user->canIssueCommercialDocuments())
<div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-radius: 12px;">
    <span><i class="fas fa-lock me-2"></i>Cette facture est un brouillon. Complétez la conformité PRO pour lui attribuer un numéro définitif et l’envoyer.</span>
    <a href="{{ route('pro.compliance') }}" class="btn btn-sm btn-warning">Checklist PRO</a>
</div>
@endif

<div class="pro-card">
    {{-- Header: Logo + Infos + Invoice Title --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4 pb-4" style="border-bottom: 3px solid #6366f1;">
        <div>
            <div class="invoice-logo-block">{{ $user->company_name ?? $user->name }}</div>
            <div style="font-size: 0.82rem; color: var(--pro-text-secondary); line-height: 1.7; margin-top: 4px;">
                @if($user->address)<div>{{ $user->address }}</div>@endif
                @if($user->city)<div>{{ $user->city }}@if($user->country), {{ $user->country }}@endif</div>@endif
                @if($user->phone)<div>Tél : {{ $user->phone }}</div>@endif
                <div>{{ $user->email }}</div>
                @if($user->siret)<div style="color: #94a3b8; font-size: 0.78rem;">SIRET : {{ $user->siret }}</div>@endif
                @if($user->tva_number)<div style="color: #94a3b8; font-size: 0.78rem;">TVA : {{ $user->tva_number }}</div>@endif
            </div>
        </div>
        <div class="text-end">
            <div class="invoice-doc-title">FACTURE</div>
            <div style="font-size: 0.88rem; color: #475569; line-height: 1.8; margin-top: 4px;">
                <div><strong>N° :</strong> {{ $invoice->invoice_number }}</div>
                <div><strong>Date d’émission :</strong> {{ ($invoice->finalized_at ?? $invoice->created_at)->format('d/m/Y') }}</div>
                @if($invoice->service_date)<div><strong>Date de l’opération :</strong> {{ $invoice->service_date->format('d/m/Y') }}</div>@endif
                @if($invoice->due_date)<div><strong>Échéance :</strong> {{ $invoice->due_date->format('d/m/Y') }}</div>@endif
                @if($invoice->quote)<div><strong>Réf. devis :</strong> {{ $invoice->quote->quote_number }}</div>@endif
            </div>
        </div>
    </div>

    {{-- Client (right-aligned, below header) --}}
    <div class="d-flex justify-content-end mb-4">
        <div class="invoice-info-card" style="min-width: 280px;">
            <div class="invoice-info-label">Client</div>
            <div class="invoice-info-name">{{ $invoice->client_name }}</div>
            <div class="invoice-info-detail">
                @if($invoice->client_company)<div><strong>{{ $invoice->client_company }}</strong></div>@endif
                @if($invoice->client_address)<div>{{ $invoice->client_address }}</div>@endif
                @if($invoice->client_registration_number)<div>Immatriculation : {{ $invoice->client_registration_number }}</div>@endif
                @if($invoice->client_vat_number)<div>TVA : {{ $invoice->client_vat_number }}</div>@endif
                @if($invoice->client_email)<div>{{ $invoice->client_email }}</div>@endif
                @if($invoice->client_phone)<div>{{ $invoice->client_phone }}</div>@endif
            </div>
        </div>
    </div>

    {{-- Items Table --}}
    <div class="table-responsive mb-4" style="overflow: visible;">
        <table class="pro-table">
            <thead>
                <tr>
                    <th style="width: 48%;">Désignation</th>
                    <th class="text-center" style="width: 12%;">Qté</th>
                    <th class="text-end" style="width: 20%;">Prix unitaire HT</th>
                    <th class="text-end" style="width: 20%;">Total HT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item['description'] }}</td>
                    <td class="text-center">{{ $item['quantity'] }}</td>
                    <td class="text-end">{{ number_format($item['unit_price'], 2, ',', ' ') }} €</td>
                    <td class="text-end fw-semibold">{{ number_format($item['total'] ?? ($item['quantity'] * $item['unit_price']), 2, ',', ' ') }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Totals --}}
    <div class="d-flex justify-content-end mb-4">
        <div class="invoice-totals">
            <div class="total-line">
                <span>Sous-total HT</span>
                <strong>{{ number_format($invoice->subtotal, 2, ',', ' ') }} €</strong>
            </div>
            <div class="total-line" style="border-top: 1px solid #e2e8f0;">
                <span>TVA ({{ number_format($invoice->tax_rate, 2, ',', ' ') }}%)</span>
                <strong>{{ number_format($invoice->tax_amount, 2, ',', ' ') }} €</strong>
            </div>
            <div class="total-line-grand">
                <span>Total TTC</span>
                <span>{{ number_format($invoice->total, 2, ',', ' ') }} €</span>
            </div>
        </div>
    </div>

    {{-- Payment status --}}
    @if($invoice->paid_at)
    <div class="p-3 mb-3" style="background: rgba(16,185,129,0.06); border-radius: 12px; border: 1px solid rgba(16,185,129,0.2);">
        <i class="fas fa-check-circle text-success me-2"></i>
        <strong>Payée le {{ $invoice->paid_at->format('d/m/Y') }}</strong>
        @if($invoice->payment_method) — Mode : {{ ucfirst($invoice->payment_method) }}@endif
    </div>
    @endif

    <div class="p-3 mb-3" style="background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; font-size: .83rem; color: #475569;">
        @if((float) $invoice->tax_rate === 0.0 && $invoice->vat_exemption_reason)<div class="fw-bold mb-1">{{ $invoice->vat_exemption_reason }}</div>@endif
        <div><strong>Conditions de paiement :</strong> {{ $invoice->payment_terms ?: 'À convenir' }}</div>
        <div><strong>Escompte :</strong> {{ $invoice->early_payment_discount ?: 'Néant' }}</div>
        @if($invoice->client_type === 'business')<div><strong>Retard :</strong> {{ number_format($invoice->late_penalty_rate ?? 0, 2, ',', ' ') }} % par an + indemnité forfaitaire de recouvrement de 40 €.</div>@endif
    </div>

    {{-- Notes --}}
    @if($invoice->notes)
    <div class="pt-3" style="border-top: 1px solid var(--pro-border);">
        <strong style="font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;">Notes & conditions</strong>
        <p class="text-muted mt-2" style="font-size: 0.85rem; white-space: pre-line;">{{ $invoice->notes }}</p>
    </div>
    @endif
</div>

{{-- Action Buttons --}}
<div class="invoice-actions-bar">
    <a href="{{ route('pro.invoices.download', $invoice->id) }}" class="btn btn-download-pdf">
        <i class="fas fa-file-pdf me-1"></i> Télécharger PDF
    </a>
    @if($invoice->isEditable())
    <a href="{{ route('pro.invoices.edit', $invoice->id) }}" class="btn btn-pro-primary">
        <i class="fas fa-edit me-1"></i> Modifier
    </a>
    @endif
    <button onclick="window.print()" class="btn btn-pro-outline">
        <i class="fas fa-print me-1"></i> Imprimer
    </button>
    <a href="{{ route('pro.invoices') }}" class="btn btn-light" style="border-radius: 10px;">
        <i class="fas fa-arrow-left me-1"></i> Retour
    </a>
</div>

@if(in_array($invoice->status, ['draft', 'sent']))
<div class="pro-card mt-3" style="max-width: 720px;">
    <div class="pro-card-title"><i class="fas fa-envelope text-primary"></i> Envoyer la facture par email</div>
    <form method="POST" action="{{ route('pro.invoices.sendEmail', $invoice->id) }}" class="row g-3">
        @csrf
        <div class="col-md-5">
            <label class="form-label fw-semibold">Destinataire</label>
            <input type="email" name="email" value="{{ $invoice->client_email }}" class="form-control" required>
        </div>
        <div class="col-md-7">
            <label class="form-label fw-semibold">Message (facultatif)</label>
            <input type="text" name="message" class="form-control" placeholder="Veuillez trouver votre facture en pièce jointe.">
        </div>
        <div class="col-12"><button class="btn btn-pro-primary"><i class="fas fa-paper-plane me-1"></i> Envoyer le PDF</button></div>
    </form>
</div>
@endif
@endsection

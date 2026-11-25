@extends('pro.layout')
@section('title', $invoice->documentLabel() . ' ' . $invoice->invoice_number . ' - Espace Pro')

@section('content')
@push('head')
<link rel="stylesheet" href="{{ asset('css/pro-document-sheet.css') }}?v={{ @filemtime(public_path('css/pro-document-sheet.css')) }}">
@endpush
<style>
    .invoice-actions-bar { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 20px; }
    .invoice-actions-bar .btn { border-radius: 10px; font-size: 0.85rem; padding: 8px 18px; }
    .btn-download-pdf { background: linear-gradient(135deg, #10b981, #059669); color: #fff; border: none; }
    .btn-download-pdf:hover { background: linear-gradient(135deg, #059669, #047857); color: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35); }
    @media (max-width: 576px) {
        .invoice-actions-bar .btn,
        .invoice-actions-bar form,
        .invoice-actions-bar form .btn { width: 100%; }
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
        <h1>{{ $invoice->documentLabel() }} {{ $invoice->invoice_number }}</h1>
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

@if($invoice->status === 'draft' && ! app(\App\Services\CommercialDocumentAllowance::class)->canIssue($user, $invoice))
<div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-radius: 12px;" data-invoice-over-allowance>
    @if($proSubscriptionsEnabled ?? false)
        <span><i class="fas fa-crown me-2" aria-hidden="true"></i>Cette facture dépasse vos {{ \App\Services\CommercialDocumentAllowance::FREE['invoice'] }} factures gratuites : elle reste en brouillon tant que vous n’êtes pas abonné à Premium.</span>
        <a href="{{ route('pro.subscription') }}" class="btn btn-sm btn-warning">Passer à Premium</a>
    @else
        <span><i class="fas fa-info-circle me-2" aria-hidden="true"></i>Cette facture dépasse vos {{ \App\Services\CommercialDocumentAllowance::FREE['invoice'] }} factures gratuites : elle reste en brouillon. {{ \App\Services\CommercialDocumentAllowance::SUBSCRIPTIONS_CLOSED }}</span>
    @endif
</div>
@endif

<div class="pk-sheet-frame">
    <article class="pk-sheet" aria-label="Aperçu du document">
        @include('pro.partials.invoice-sheet')
    </article>
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

@include('pro.partials.free-watermark-notice', ['documentLabel' => $invoice->isCreditNote() ? 'le PDF de cet avoir' : 'le PDF de cette facture', 'pkDocument' => $invoice, 'pkLine' => true])

@include('pro.partials.invoice-reminder')
@include('pro.partials.invoice-adjustments')

@if(in_array($invoice->status, ['draft', 'sent', 'overdue', \App\Models\ProInvoice::STATUS_ISSUED]))
<div class="pro-card mt-3" style="max-width: 720px;">
    <div class="pro-card-title"><i class="fas fa-envelope text-primary"></i> Envoyer {{ $invoice->isCreditNote() ? 'l’avoir' : 'la facture' }} par email</div>
    <form method="POST" action="{{ route('pro.invoices.sendEmail', $invoice->id) }}" class="row g-3">
        @csrf
        <div class="col-md-5">
            <label class="form-label fw-semibold">Destinataire</label>
            <input type="email" name="email" value="{{ $invoice->client_email }}" class="form-control" required>
        </div>
        <div class="col-md-7">
            <label class="form-label fw-semibold">Message (facultatif)</label>
            <input type="text" name="message" class="form-control" placeholder="{{ $invoice->isCreditNote() ? 'Veuillez trouver votre avoir en pièce jointe.' : 'Veuillez trouver votre facture en pièce jointe.' }}">
        </div>
        @if(! $invoice->isCreditNote() && $user->hasActiveProSubscription() && $invoice->due_date && ! $invoice->reminder_sent_at)
        <div class="col-12">
            <div class="form-check">
                <input type="hidden" name="auto_reminder" value="0">
                <input class="form-check-input" type="checkbox" name="auto_reminder" value="1" id="pkInvoiceAutoReminder" checked>
                <label class="form-check-label" for="pkInvoiceAutoReminder">Rappeler automatiquement ce client une seule fois, {{ \App\Services\InvoiceFollowUpService::DELAY_DAYS }} jours après l’échéance si la facture reste impayée <span class="text-muted">(Premium)</span></label>
            </div>
        </div>
        @endif
        <div class="col-12"><button class="btn btn-pro-primary"><i class="fas fa-paper-plane me-1"></i> Envoyer le PDF</button></div>
    </form>
</div>
@endif
@endsection

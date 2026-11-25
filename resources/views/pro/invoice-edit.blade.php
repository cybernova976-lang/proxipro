@extends('pro.layout')
@section('title', 'Modifier Facture ' . $invoice->invoice_number . ' - Espace Pro')

@section('styles')
<style>
.invoice-item-row {
    background: #f8fafc;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    position: relative;
}
.invoice-item-row .remove-item {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: none;
    border: none;
    color: var(--pro-danger);
    cursor: pointer;
    font-size: 1rem;
}
</style>
@endsection

@section('content')
<div class="pro-content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 0.8rem;">
                <li class="breadcrumb-item"><a href="{{ route('pro.dashboard') }}" style="color: var(--pro-primary);">Espace Pro</a></li>
                <li class="breadcrumb-item"><a href="{{ route('pro.invoices') }}" style="color: var(--pro-primary);">Factures</a></li>
                <li class="breadcrumb-item"><a href="{{ route('pro.invoices.show', $invoice->id) }}" style="color: var(--pro-primary);">{{ $invoice->invoice_number }}</a></li>
                <li class="breadcrumb-item active">Modifier</li>
            </ol>
        </nav>
        <h1>Modifier la facture {{ $invoice->invoice_number }}</h1>
    </div>
    <span class="pro-status pro-status-{{ $invoice->getStatusColor() }}">{{ $invoice->getStatusLabel() }}</span>
</div>

@if($errors->any())
<div class="alert alert-danger" style="border-radius: 12px; border: none;">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<link rel="stylesheet" href="{{ asset('css/pro-premium.css') }}?v={{ @filemtime(public_path('css/pro-premium.css')) }}">
<form method="POST" action="{{ route('pro.invoices.update', $invoice->id) }}">
    @csrf
    @method('PUT')
    
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="pro-card">
                <div class="pro-card-title"><i class="fas fa-user text-primary"></i> Client</div>
                @if($clients->isNotEmpty())
                <div class="mb-3">
                    <select name="client_id" id="clientSelect" class="form-select" style="border-radius: 10px;" onchange="fillClientInfo(this)">
                        <option value="">-- Client personnalisé --</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" data-name="{{ $client->name }}" data-email="{{ $client->email }}" data-phone="{{ $client->phone }}" data-address="{{ $client->address }}" {{ (string) old('client_id', $invoice->pro_client_id) === (string) $client->id ? 'selected' : '' }}>{{ $client->name }}{{ $client->company ? ' (' . $client->company . ')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nom *</label>
                        <input type="text" name="client_name" id="clientName" class="form-control" required style="border-radius: 10px;" value="{{ old('client_name', $invoice->client_name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="client_email" id="clientEmail" class="form-control" style="border-radius: 10px;" value="{{ old('client_email', $invoice->client_email) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Téléphone</label>
                        <input type="text" name="client_phone" id="clientPhone" class="form-control" style="border-radius: 10px;" value="{{ old('client_phone', $invoice->client_phone) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Adresse</label>
                        <input type="text" name="client_address" id="clientAddress" class="form-control" style="border-radius: 10px;" value="{{ old('client_address', $invoice->client_address) }}">
                    </div>
                    @include('pro.partials.invoice-edit-fields', ['part' => 'client'])
                </div>
            </div>

            <div class="pro-card">
                <div class="pro-card-title"><i class="fas fa-tag text-info"></i> Objet de la facture</div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">Objet / Sujet *</label>
                    <input type="text" name="subject" class="form-control" required style="border-radius: 10px;" value="{{ old('subject', $invoice->subject) }}" placeholder="Ex: Prestation de service, Vente de matériel...">
                </div>
                @include('pro.partials.invoice-edit-fields', ['part' => 'details'])
            </div>

            <div class="pro-card">
                <div class="pro-card-title"><i class="fas fa-file-invoice-dollar text-danger"></i> Lignes de facture</div>
                @include('pro.partials.document-lines', ['pkLines' => $invoice->items, 'pkRowClass' => 'invoice-item-row', 'pkContainerId' => 'invoiceItems'])
            </div>

            <div class="pro-card">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" style="border-radius: 10px; font-size: 0.88rem;">{{ old('notes', $invoice->notes) }}</textarea>
                </div>
                <div>
                    <label class="form-label fw-semibold">Mode de paiement</label>
                    <select name="payment_method" class="form-select" style="border-radius: 10px;">
                        <option value="virement" {{ ($invoice->payment_method ?? '') === 'virement' ? 'selected' : '' }}>Virement bancaire</option>
                        <option value="cheque" {{ ($invoice->payment_method ?? '') === 'cheque' ? 'selected' : '' }}>Chèque</option>
                        <option value="especes" {{ ($invoice->payment_method ?? '') === 'especes' ? 'selected' : '' }}>Espèces</option>
                        <option value="carte" {{ ($invoice->payment_method ?? '') === 'carte' ? 'selected' : '' }}>Carte bancaire</option>
                        <option value="other" {{ ($invoice->payment_method ?? '') === 'other' ? 'selected' : '' }}>Autre</option>
                    </select>
                </div>
                @include('pro.partials.invoice-edit-fields', ['part' => 'payment'])
            </div>
        </div>

        <div class="col-lg-4">
            <div class="pro-card" style="position: sticky; top: calc(var(--header-height) + 1.5rem);">
                <div class="pro-card-title"><i class="fas fa-cog text-secondary"></i> Paramètres</div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">TVA (%)</label>
                    <input type="number" name="tax_rate" id="taxRate" class="form-control" value="{{ old('tax_rate', $invoice->tax_rate ?? 20) }}" min="0" max="100" step="0.1" style="border-radius: 10px;" oninput="recalculate()">
                </div>
                <div class="mb-3">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="show_due_date" {{ old('due_date', $invoice->due_date) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="show_due_date">Indiquer une &eacute;ch&eacute;ance</label>
                    </div>
                    <div id="due_date_wrapper" style="display: {{ old('due_date', $invoice->due_date) ? 'block' : 'none' }};">
                        <input type="date" name="due_date" id="due_date" class="form-control" value="{{ old('due_date', $invoice->due_date ? $invoice->due_date->format('Y-m-d') : now()->addDays(30)->format('Y-m-d')) }}" style="border-radius: 10px;">
                    </div>
                </div>

                <div style="background: #f8fafc; border-radius: 12px; padding: 1.25rem;" class="mt-4">
                    <div class="d-flex justify-content-between py-1" style="font-size: 0.9rem;"><span>Sous-total HT</span><span id="subtotal" class="fw-semibold">{{ number_format($invoice->subtotal, 2, ',', '') }}€</span></div>
                    <div class="d-flex justify-content-between py-1" style="font-size: 0.9rem;"><span>TVA</span><span id="taxAmount" class="fw-semibold">{{ number_format($invoice->tax ?? $invoice->tax_amount ?? 0, 2, ',', '') }}€</span></div>
                    <div class="d-flex justify-content-between py-2 mt-1" style="font-size: 1.15rem; font-weight: 800; color: var(--pro-primary); border-top: 2px solid var(--pro-border);"><span>Total TTC</span><span id="totalTTC">{{ number_format($invoice->total, 2, ',', '') }}€</span></div>
                </div>

                @include('pro.partials.free-watermark-notice', ['documentLabel' => 'cette facture', 'pkDocument' => $invoice])

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-pro-primary"><i class="fas fa-save me-1"></i> Enregistrer les modifications</button>
                    <a href="{{ route('pro.invoices.show', $invoice->id) }}" class="btn btn-light" style="border-radius: 10px;">Annuler</a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script>
// Lignes et totaux : js/pro-document-lines.js (partials/document-lines).
function fillClientInfo(select) {
    const o = select.selectedOptions[0];
    document.getElementById('clientName').value = o.dataset.name || '';
    document.getElementById('clientEmail').value = o.dataset.email || '';
    document.getElementById('clientPhone').value = o.dataset.phone || '';
    document.getElementById('clientAddress').value = o.dataset.address || '';
}

document.addEventListener('DOMContentLoaded', function() {
    var cb = document.getElementById('show_due_date');
    var wrap = document.getElementById('due_date_wrapper');
    var inp = document.getElementById('due_date');
    if (cb && wrap) {
        cb.addEventListener('change', function() {
            wrap.style.display = this.checked ? 'block' : 'none';
            if (!this.checked && inp) inp.value = '';
        });
    }
});
</script>
@endsection

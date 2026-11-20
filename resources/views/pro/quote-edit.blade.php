@extends('pro.layout')
@section('title', 'Modifier Devis ' . $quote->quote_number . ' - Espace Pro')

@section('styles')
<style>
.quote-item-row {
    background: #f8fafc;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    position: relative;
}
.quote-item-row .remove-item {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: none;
    border: none;
    color: var(--pro-danger);
    cursor: pointer;
    font-size: 1rem;
}
.quote-totals {
    background: #f8fafc;
    border-radius: 12px;
    padding: 1.25rem;
}
.quote-totals .total-line {
    display: flex;
    justify-content: space-between;
    padding: 0.4rem 0;
    font-size: 0.9rem;
}
.quote-totals .total-final {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--pro-primary);
    border-top: 2px solid var(--pro-border);
    padding-top: 0.75rem;
    margin-top: 0.5rem;
}
</style>
@endsection

@section('content')
<div class="pro-content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 0.8rem;">
                <li class="breadcrumb-item"><a href="{{ route('pro.dashboard') }}" style="color: var(--pro-primary);">Espace Pro</a></li>
                <li class="breadcrumb-item"><a href="{{ route('pro.quotes') }}" style="color: var(--pro-primary);">Devis</a></li>
                <li class="breadcrumb-item"><a href="{{ route('pro.quotes.show', $quote->id) }}" style="color: var(--pro-primary);">{{ $quote->quote_number }}</a></li>
                <li class="breadcrumb-item active">Modifier</li>
            </ol>
        </nav>
        <h1>Modifier le devis {{ $quote->quote_number }}</h1>
    </div>
    <span class="pro-status pro-status-{{ $quote->getStatusColor() }}">{{ $quote->getStatusLabel() }}</span>
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
<form method="POST" action="{{ route('pro.quotes.update', $quote->id) }}">
    @csrf
    @method('PUT')
    
    <div class="row g-4">
        <div class="col-lg-8">
            {{-- Client info --}}
            <div class="pro-card">
                <div class="pro-card-title"><i class="fas fa-user text-primary"></i> Informations client</div>
                
                <input type="hidden" name="client_id" id="clientIdHidden" value="{{ old('client_id', $quote->pro_client_id) }}">
                @if($clients->isNotEmpty())
                <div class="mb-3">
                    <label class="form-label fw-semibold">Sélectionner un client existant</label>
                    <select id="clientSelect" class="form-select" style="border-radius: 10px;" onchange="fillClientInfo(this)">
                        <option value="">-- Client personnalisé --</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" 
                                data-name="{{ $client->name }}" 
                                data-email="{{ $client->email }}" 
                                data-phone="{{ $client->phone }}" 
                                data-address="{{ $client->address }}"
                                {{ (string) old('client_id', $quote->pro_client_id) === (string) $client->id ? 'selected' : '' }}>
                                {{ $client->name }}{{ $client->company ? ' (' . $client->company . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nom du client *</label>
                        <input type="text" name="client_name" id="clientName" class="form-control" required style="border-radius: 10px;" value="{{ old('client_name', $quote->client_name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="client_email" id="clientEmail" class="form-control" style="border-radius: 10px;" value="{{ old('client_email', $quote->client_email) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Téléphone</label>
                        <input type="text" name="client_phone" id="clientPhone" class="form-control" style="border-radius: 10px;" value="{{ old('client_phone', $quote->client_phone) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Adresse</label>
                        <input type="text" name="client_address" id="clientAddress" class="form-control" style="border-radius: 10px;" value="{{ old('client_address', $quote->client_address) }}">
                    </div>
                    @include('pro.partials.quote-edit-fields', ['part' => 'client'])
                </div>
            </div>

            {{-- Quote details --}}
            <div class="pro-card">
                <div class="pro-card-title"><i class="fas fa-file-alt text-warning"></i> Détails du devis</div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Objet du devis *</label>
                    <input type="text" name="subject" class="form-control" required style="border-radius: 10px;" placeholder="Ex: Rénovation salle de bain" value="{{ old('subject', $quote->subject) }}">
                </div>
                @include('pro.partials.quote-edit-fields', ['part' => 'details'])

                <label class="form-label fw-semibold">Lignes du devis</label>
                @include('pro.partials.document-lines', ['pkLines' => $quote->items, 'pkRowClass' => 'quote-item-row', 'pkContainerId' => 'quoteItems'])
            </div>

            {{-- Notes --}}
            <div class="pro-card">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" style="border-radius: 10px; font-size: 0.88rem;" placeholder="Notes visibles par le client">{{ old('notes', $quote->notes) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Conditions</label>
                        <textarea name="conditions" class="form-control" rows="3" style="border-radius: 10px; font-size: 0.88rem;" placeholder="Conditions de réalisation">{{ old('conditions', $quote->conditions) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Settings --}}
            <div class="pro-card" style="position: sticky; top: calc(var(--header-height) + 1.5rem);">
                <div class="pro-card-title"><i class="fas fa-cog text-secondary"></i> Paramètres</div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Taux TVA (%)</label>
                    <input type="number" name="tax_rate" id="taxRate" class="form-control" value="{{ old('tax_rate', $quote->tax_rate ?? 20) }}" min="0" max="100" step="0.1" style="border-radius: 10px;" onchange="recalculate()">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Validité jusqu'au</label>
                    <input type="date" name="valid_until" class="form-control" value="{{ old('valid_until', $quote->valid_until ? $quote->valid_until->format('Y-m-d') : '') }}" style="border-radius: 10px;">
                </div>
                @include('pro.partials.quote-edit-fields', ['part' => 'settings'])

                <div class="quote-totals mt-4">
                    <div class="total-line">
                        <span>Sous-total HT</span>
                        <span id="subtotal" class="fw-semibold">{{ number_format($quote->subtotal, 2, ',', '') }}€</span>
                    </div>
                    <div class="total-line">
                        <span>TVA (<span id="taxLabel">{{ $quote->tax_rate ?? 20 }}</span>%)</span>
                        <span id="taxAmount" class="fw-semibold">{{ number_format($quote->tax ?? $quote->tax_amount ?? 0, 2, ',', '') }}€</span>
                    </div>
                    <div class="total-line total-final">
                        <span>Total TTC</span>
                        <span id="totalTTC">{{ number_format($quote->total, 2, ',', '') }}€</span>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-pro-primary">
                        <i class="fas fa-save me-1"></i> Enregistrer les modifications
                    </button>
                    <a href="{{ route('pro.quotes.show', $quote->id) }}" class="btn btn-light" style="border-radius: 10px;">Annuler</a>
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
    const option = select.selectedOptions[0];
    document.getElementById('clientIdHidden').value = option.value || '';
    document.getElementById('clientName').value = option.dataset.name || '';
    document.getElementById('clientEmail').value = option.dataset.email || '';
    document.getElementById('clientPhone').value = option.dataset.phone || '';
    document.getElementById('clientAddress').value = option.dataset.address || '';
}
</script>
@endsection

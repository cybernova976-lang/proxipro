@extends('pro.layout')
@section('title', 'Nouvelle Facture - Espace Pro')

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
                <li class="breadcrumb-item active">Nouvelle facture</li>
            </ol>
        </nav>
        <h1>
            @if($quote)
                Facture depuis devis {{ $quote->quote_number }}
            @else
                Créer une facture
            @endif
        </h1>
    </div>
</div>

@unless($user->canIssueCommercialDocuments())
<div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-radius: 12px;">
    <span><i class="fas fa-lock me-2"></i>La facture sera enregistrée comme brouillon sans numéro définitif.</span>
    <a href="{{ route('pro.compliance') }}" class="btn btn-sm btn-warning">Voir la checklist</a>
</div>
@endunless

<form method="POST" action="{{ route('pro.invoices.store') }}">
    @csrf
    @if($quote)
        <input type="hidden" name="quote_id" value="{{ $quote->id }}">
        <input type="hidden" name="client_id" value="{{ $quote->pro_client_id }}">
    @endif
    
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="pro-card">
                <div class="pro-card-title"><i class="fas fa-user text-primary"></i> Client</div>
                @if($clients->isNotEmpty() && !$quote)
                <div class="mb-3">
                    <select name="client_id" id="clientSelect" class="form-select" style="border-radius: 10px;" onchange="fillClientInfo(this)">
                        <option value="">-- Nouveau client --</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" data-name="{{ $client->name }}" data-email="{{ $client->email }}" data-phone="{{ $client->phone }}" data-address="{{ $client->address }}" data-company="{{ $client->company }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nom *</label>
                        <input type="text" name="client_name" id="clientName" class="form-control" required style="border-radius: 10px;" value="{{ $quote ? $quote->client_name : old('client_name') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Entreprise du client</label>
                        <input type="text" name="client_company" id="clientCompany" class="form-control" style="border-radius: 10px;" value="{{ $quote ? $quote->client_company : old('client_company') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Type de client *</label>
                        <select name="client_type" class="form-select" required style="border-radius: 10px;">
                            <option value="individual" @selected(old('client_type') === 'individual')>Particulier</option>
                            <option value="business" @selected(old('client_type', $quote?->client_company ? 'business' : '') === 'business')>Professionnel / entreprise</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Immatriculation client</label>
                        <input type="text" name="client_registration_number" class="form-control" style="border-radius: 10px;" value="{{ $quote ? $quote->client_registration_number : old('client_registration_number') }}" placeholder="SIREN / registre local">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">N° TVA client</label>
                        <input type="text" name="client_vat_number" class="form-control" style="border-radius: 10px;" value="{{ $quote ? $quote->client_vat_number : old('client_vat_number') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="client_email" id="clientEmail" class="form-control" style="border-radius: 10px;" value="{{ $quote ? $quote->client_email : old('client_email') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Téléphone</label>
                        <input type="text" name="client_phone" id="clientPhone" class="form-control" style="border-radius: 10px;" value="{{ $quote ? $quote->client_phone : old('client_phone') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Adresse</label>
                        <input type="text" name="client_address" id="clientAddress" class="form-control" style="border-radius: 10px;" value="{{ $quote ? $quote->client_address : old('client_address') }}">
                    </div>
                </div>
            </div>

            <div class="pro-card">
                <div class="pro-card-title"><i class="fas fa-tag text-info"></i> Objet de la facture</div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Objet / Sujet *</label>
                    <input type="text" name="subject" class="form-control" required style="border-radius: 10px;" value="{{ $quote ? $quote->subject ?? '' : old('subject') }}" placeholder="Ex: Prestation de service, Vente de matériel...">
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nature de l’opération *</label>
                        <select name="operation_type" class="form-select" required style="border-radius: 10px;">
                            <option value="services" @selected(old('operation_type', $quote?->operation_type ?? 'services') === 'services')>Prestation de services</option>
                            <option value="goods" @selected(old('operation_type', $quote?->operation_type) === 'goods')>Vente de biens</option>
                            <option value="mixed" @selected(old('operation_type', $quote?->operation_type) === 'mixed')>Biens et services</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Date de vente / prestation *</label>
                        <input type="date" name="service_date" class="form-control" required value="{{ old('service_date', now()->format('Y-m-d')) }}" style="border-radius: 10px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Bon de commande</label>
                        <input type="text" name="purchase_order_number" class="form-control" value="{{ old('purchase_order_number') }}" style="border-radius: 10px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Adresse de livraison</label>
                        <input type="text" name="delivery_address" class="form-control" value="{{ old('delivery_address') }}" style="border-radius: 10px;">
                    </div>
                </div>
            </div>

            <div class="pro-card">
                <div class="pro-card-title"><i class="fas fa-file-invoice-dollar text-danger"></i> Lignes de facture</div>
                <div class="row g-2 mb-2 px-3" style="font-size: 0.8rem; font-weight: 600; color: var(--pro-text-secondary);">
                    <div class="col-md-6">Description</div>
                    <div class="col-md-2">Quantité</div>
                    <div class="col-md-2">Prix unitaire €</div>
                    <div class="col-md-2 text-end">Total</div>
                </div>
                <div id="invoiceItems">
                    @if($quote && is_array($quote->items))
                        @foreach($quote->items as $i => $item)
                        <div class="invoice-item-row" data-index="{{ $i }}">
                            @if($i > 0)<button type="button" class="remove-item" onclick="removeItem(this)"><i class="fas fa-times-circle"></i></button>@endif
                            <div class="row g-2">
                                <div class="col-md-6"><input type="text" name="items[{{ $i }}][description]" class="form-control" value="{{ $item['description'] }}" required style="border-radius: 10px; font-size: 0.9rem;"></div>
                                <div class="col-md-2"><input type="number" name="items[{{ $i }}][quantity]" class="form-control item-qty" value="{{ $item['quantity'] }}" min="0.01" step="0.01" required style="border-radius: 10px; font-size: 0.9rem;" oninput="recalculate()"></div>
                                <div class="col-md-2"><input type="number" name="items[{{ $i }}][unit_price]" class="form-control item-price" value="{{ $item['unit_price'] }}" min="0" step="0.01" required style="border-radius: 10px; font-size: 0.9rem;" oninput="recalculate()"></div>
                                <div class="col-md-2"><div class="form-control-plaintext text-end fw-bold item-total" style="font-size: 0.9rem;">{{ number_format($item['total'] ?? 0, 2, ',', '') }}€</div></div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="invoice-item-row" data-index="0">
                            <div class="row g-2">
                                <div class="col-md-6"><input type="text" name="items[0][description]" class="form-control" placeholder="Description" required style="border-radius: 10px; font-size: 0.9rem;"></div>
                                <div class="col-md-2"><input type="number" name="items[0][quantity]" class="form-control item-qty" value="1" min="0.01" step="0.01" required style="border-radius: 10px; font-size: 0.9rem;" placeholder="Qté" oninput="recalculate()"></div>
                                <div class="col-md-2"><input type="number" name="items[0][unit_price]" class="form-control item-price" value="0" min="0" step="0.01" required style="border-radius: 10px; font-size: 0.9rem;" placeholder="Prix €" oninput="recalculate()"></div>
                                <div class="col-md-2"><div class="form-control-plaintext text-end fw-bold item-total" style="font-size: 0.9rem;">0,00€</div></div>
                            </div>
                        </div>
                    @endif
                </div>
                <button type="button" class="btn btn-light btn-sm mt-2" onclick="addItem()" style="border-radius: 10px;">
                    <i class="fas fa-plus me-1"></i> Ajouter une ligne
                </button>
            </div>

            <div class="pro-card">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" style="border-radius: 10px; font-size: 0.88rem;">{{ old('notes') }}</textarea>
                </div>
                <div>
                    <label class="form-label fw-semibold">Mode de paiement</label>
                    <select name="payment_method" class="form-select" style="border-radius: 10px;">
                        <option value="virement">Virement bancaire</option>
                        <option value="cheque">Chèque</option>
                        <option value="especes">Espèces</option>
                        <option value="carte">Carte bancaire</option>
                        <option value="other">Autre</option>
                    </select>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Conditions de paiement *</label>
                        <textarea name="payment_terms" class="form-control" rows="2" required style="border-radius: 10px;">{{ old('payment_terms', 'Paiement à 30 jours à compter de la date d’émission.') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Escompte *</label>
                        <input type="text" name="early_payment_discount" class="form-control" required value="{{ old('early_payment_discount', 'Néant') }}" style="border-radius: 10px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Pénalités de retard (%)</label>
                        <input type="number" name="late_penalty_rate" class="form-control" min="0" max="100" step="0.01" value="{{ old('late_penalty_rate', 12) }}" style="border-radius: 10px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Mention d’exonération de TVA</label>
                        <input type="text" name="vat_exemption_reason" class="form-control" value="{{ old('vat_exemption_reason', 'TVA non applicable, art. 293 B du CGI') }}" style="border-radius: 10px;">
                        <small class="text-muted">Requise uniquement si le taux de TVA est 0 %.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="pro-card" style="position: sticky; top: calc(var(--header-height) + 1.5rem);">
                <div class="pro-card-title"><i class="fas fa-cog text-secondary"></i> Paramètres</div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">TVA (%)</label>
                    <input type="number" name="tax_rate" id="taxRate" class="form-control" value="20" min="0" max="100" step="0.1" style="border-radius: 10px;" oninput="recalculate()">
                </div>
                <div class="mb-3">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="show_due_date" {{ old('due_date') ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="show_due_date">Indiquer une &eacute;ch&eacute;ance</label>
                    </div>
                    <div id="due_date_wrapper" style="display: {{ old('due_date') ? 'block' : 'none' }};">
                        <input type="date" name="due_date" id="due_date" class="form-control" value="{{ old('due_date', now()->addDays(30)->format('Y-m-d')) }}" style="border-radius: 10px;">
                    </div>
                </div>

                <div style="background: #f8fafc; border-radius: 12px; padding: 1.25rem;" class="mt-4">
                    <div class="d-flex justify-content-between py-1" style="font-size: 0.9rem;"><span>Sous-total HT</span><span id="subtotal" class="fw-semibold">0,00€</span></div>
                    <div class="d-flex justify-content-between py-1" style="font-size: 0.9rem;"><span>TVA</span><span id="taxAmount" class="fw-semibold">0,00€</span></div>
                    <div class="d-flex justify-content-between py-2 mt-1" style="font-size: 1.15rem; font-weight: 800; color: var(--pro-primary); border-top: 2px solid var(--pro-border);"><span>Total TTC</span><span id="totalTTC">0,00€</span></div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-pro-primary"><i class="fas fa-file-invoice me-1"></i> Créer la facture</button>
                    <a href="{{ route('pro.invoices') }}" class="btn btn-light" style="border-radius: 10px;">Annuler</a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script>
let itemIndex = {{ $quote && is_array($quote->items) ? count($quote->items) : 1 }};

function addItem() {
    const container = document.getElementById('invoiceItems');
    const row = document.createElement('div');
    row.className = 'invoice-item-row';
    row.innerHTML = `<button type="button" class="remove-item" onclick="removeItem(this)"><i class="fas fa-times-circle"></i></button><div class="row g-2"><div class="col-md-6"><input type="text" name="items[${itemIndex}][description]" class="form-control" placeholder="Description" required style="border-radius: 10px; font-size: 0.9rem;"></div><div class="col-md-2"><input type="number" name="items[${itemIndex}][quantity]" class="form-control item-qty" value="1" min="0.01" step="0.01" required style="border-radius: 10px; font-size: 0.9rem;" placeholder="Qté" oninput="recalculate()"></div><div class="col-md-2"><input type="number" name="items[${itemIndex}][unit_price]" class="form-control item-price" value="0" min="0" step="0.01" required style="border-radius: 10px; font-size: 0.9rem;" placeholder="Prix €" oninput="recalculate()"></div><div class="col-md-2"><div class="form-control-plaintext text-end fw-bold item-total" style="font-size: 0.9rem;">0,00€</div></div></div>`;
    container.appendChild(row);
    itemIndex++;
}

function removeItem(btn) {
    if (document.querySelectorAll('.invoice-item-row').length <= 1) return;
    btn.closest('.invoice-item-row').remove();
    recalculate();
}

function recalculate() {
    let subtotal = 0;
    document.querySelectorAll('.invoice-item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty')?.value || 0);
        const price = parseFloat(row.querySelector('.item-price')?.value || 0);
        const total = qty * price;
        subtotal += total;
        row.querySelector('.item-total').textContent = total.toFixed(2).replace('.', ',') + '€';
    });
    const taxRate = parseFloat(document.getElementById('taxRate').value || 0);
    const tax = subtotal * taxRate / 100;
    document.getElementById('subtotal').textContent = subtotal.toFixed(2).replace('.', ',') + '€';
    document.getElementById('taxAmount').textContent = tax.toFixed(2).replace('.', ',') + '€';
    document.getElementById('totalTTC').textContent = (subtotal + tax).toFixed(2).replace('.', ',') + '€';
}

function fillClientInfo(select) {
    const o = select.selectedOptions[0];
    document.getElementById('clientName').value = o.dataset.name || '';
    document.getElementById('clientEmail').value = o.dataset.email || '';
    document.getElementById('clientPhone').value = o.dataset.phone || '';
    document.getElementById('clientAddress').value = o.dataset.address || '';
    document.getElementById('clientCompany').value = o.dataset.company || '';
}

document.addEventListener('DOMContentLoaded', function() {
    recalculate();
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

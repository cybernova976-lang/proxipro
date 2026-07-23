@extends('layouts.app')

@section('title', 'Créer une facture - Lunamars')

@section('content')
<style>
    .quote-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.04);
        margin-bottom: 1.5rem;
    }
    .quote-card .card-header {
        background: transparent;
        border-bottom: 1px solid #f1f1f4;
        padding: 1.15rem 1.5rem;
        font-weight: 600;
        font-size: 1.05rem;
        color: #1e1e2d;
        border-radius: 12px 12px 0 0;
    }
    .quote-card .card-body { padding: 1.5rem; }
    .quote-card .card-header i { color: #6366f1; margin-right: 0.5rem; }
    .credit-banner {
        border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.5rem;
        font-weight: 500; display: flex; align-items: center; gap: 0.65rem;
    }
    .credit-banner.free { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .credit-banner.info { background: #eef2ff; color: #3730a3; border: 1px solid #c7d2fe; }
    .credit-banner.subscription { background: #ecfdf5; color: #047857; border: 1px solid #6ee7b7; }
    .form-label { font-weight: 500; font-size: 0.875rem; color: #4a4a68; margin-bottom: 0.35rem; }
    .form-control, .form-select {
        border-radius: 8px; border: 1px solid #e2e2ea;
        padding: 0.55rem 0.85rem; font-size: 0.9rem;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-control:focus, .form-select:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.12); }
    .items-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .items-table thead th {
        background: #f8f8fc; padding: 0.7rem 0.75rem; font-size: 0.8rem;
        font-weight: 600; color: #6b6b80; text-transform: uppercase;
        letter-spacing: 0.03em; border-bottom: 2px solid #ededf3;
    }
    .items-table thead th:first-child { border-radius: 8px 0 0 0; }
    .items-table thead th:last-child { border-radius: 0 8px 0 0; }
    .items-table tbody td { padding: 0.6rem 0.5rem; vertical-align: middle; border-bottom: 1px solid #f3f3f8; }
    .items-table .form-control { border-radius: 6px; padding: 0.45rem 0.65rem; font-size: 0.875rem; }
    .items-table .row-total {
        font-weight: 600; color: #1e1e2d; font-size: 0.9rem;
        white-space: nowrap; min-width: 90px; text-align: right; padding-right: 0.75rem;
    }
    .btn-add-item {
        color: #6366f1; font-weight: 500; font-size: 0.875rem;
        border: 1px dashed #c7d2fe; border-radius: 8px;
        padding: 0.5rem 1rem; background: #f8f8ff;
        transition: background 0.2s, border-color 0.2s;
    }
    .btn-add-item:hover { background: #eef2ff; border-color: #6366f1; color: #4f46e5; }
    .btn-remove-item {
        width: 32px; height: 32px; border-radius: 6px; border: none;
        background: #fef2f2; color: #dc2626; display: inline-flex;
        align-items: center; justify-content: center; font-size: 1rem;
        transition: background 0.2s; padding: 0; line-height: 1;
    }
    .btn-remove-item:hover { background: #fecaca; }
    .totals-card .totals-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 0.6rem 0; font-size: 0.925rem; color: #4a4a68;
    }
    .totals-card .totals-row.total-ttc {
        border-top: 2px solid #e2e2ea; margin-top: 0.35rem; padding-top: 0.85rem;
        font-size: 1.15rem; font-weight: 700; color: #1e1e2d;
    }
    .totals-card .totals-row .amount { font-weight: 600; font-variant-numeric: tabular-nums; }
    .btn-generate {
        background: linear-gradient(135deg, #6366f1, #4f46e5); border: none;
        border-radius: 10px; color: #fff; font-weight: 600; font-size: 1rem;
        padding: 0.85rem 1.5rem; width: 100%;
        transition: opacity 0.2s, transform 0.15s;
        box-shadow: 0 4px 14px rgba(99,102,241,0.35);
    }
    .btn-generate:hover { opacity: 0.92; color: #fff; transform: translateY(-1px); }
    .btn-generate:active { transform: translateY(0); }
    .sticky-sidebar { position: sticky; top: 1.5rem; }
    @media (max-width: 991.98px) { .sticky-sidebar { position: static; } }
</style>

<div class="container py-4">
    <div class="mb-4">
        <h1 class="fw-bold mb-1" style="font-size: 1.65rem; color: #1e1e2d;">Nouvelle facture</h1>
        <p class="text-muted mb-0" style="font-size: 0.95rem;">Remplissez les informations ci-dessous pour g&eacute;n&eacute;rer votre facture en PDF.</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger" style="border-radius: 10px;">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('quote-tool.invoice.store') }}" method="POST" id="invoiceForm">
        @csrf
        <div class="row">
            <div class="col-lg-8">

                @if($hasSubscription ?? false)
                    <div class="credit-banner subscription">
                        <i class="fas fa-crown" style="font-size: 1.2rem; color: #059669;"></i>
                        Inclus dans votre abonnement &mdash; G&eacute;n&eacute;ration illimit&eacute;e de factures
                    </div>
                @elseif($isFree)
                    <div class="credit-banner free">
                        <i class="fas fa-gift" style="font-size: 1.2rem;"></i>
                        Essai gratuit &mdash; Cr&eacute;ez votre premier document gratuitement !
                    </div>
                @else
                    <div class="credit-banner info">
                        <i class="fas fa-coins" style="font-size: 1.2rem;"></i>
                        {{ $creditsRemaining }} cr&eacute;dit(s) restant(s)
                    </div>
                @endif

                <div class="card quote-card">
                    <div class="card-header"><i class="fas fa-user-circle"></i> Vos informations</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="your_name" class="form-label">Nom complet <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="your_name" name="your_name" value="{{ old('your_name', $user->name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="your_company" class="form-label">Entreprise</label>
                                <input type="text" class="form-control" id="your_company" name="your_company" value="{{ old('your_company', $user->company_name) }}">
                            </div>
                            <div class="col-md-6">
                                <label for="your_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="your_email" name="your_email" value="{{ old('your_email', $user->email) }}">
                            </div>
                            <div class="col-md-6">
                                <label for="your_phone" class="form-label">T&eacute;l&eacute;phone</label>
                                <input type="text" class="form-control" id="your_phone" name="your_phone" value="{{ old('your_phone', $user->phone) }}">
                            </div>
                            <div class="col-md-6">
                                <label for="your_address" class="form-label">Adresse</label>
                                <input type="text" class="form-control" id="your_address" name="your_address" value="{{ old('your_address', $user->address) }}">
                            </div>
                            <div class="col-md-6">
                                <label for="your_siret" class="form-label">SIRET</label>
                                <input type="text" class="form-control" id="your_siret" name="your_siret" value="{{ old('your_siret', $user->siret) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card quote-card">
                    <div class="card-header"><i class="fas fa-building"></i> Informations du client</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="client_name" class="form-label">Nom du client <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="client_name" name="client_name" value="{{ old('client_name') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="client_email" class="form-label">Email du client</label>
                                <input type="email" class="form-control" id="client_email" name="client_email" value="{{ old('client_email') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="client_phone" class="form-label">T&eacute;l&eacute;phone du client</label>
                                <input type="text" class="form-control" id="client_phone" name="client_phone" value="{{ old('client_phone') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="client_address" class="form-label">Adresse du client</label>
                                <input type="text" class="form-control" id="client_address" name="client_address" value="{{ old('client_address') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card quote-card">
                    <div class="card-header"><i class="fas fa-tag"></i> Objet de la facture</div>
                    <div class="card-body">
                        <label for="subject" class="form-label">Objet / Sujet</label>
                        <input type="text" class="form-control" id="subject" name="subject" value="{{ old('subject') }}" placeholder="Ex: Prestation de service, Vente de matériel...">
                    </div>
                </div>

                <div class="card quote-card">
                    <div class="card-header"><i class="fas fa-list"></i> Lignes de la facture</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="items-table" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th style="width: 45%;">Description</th>
                                        <th style="width: 14%;">Quantit&eacute;</th>
                                        <th style="width: 20%;">Prix unitaire HT</th>
                                        <th style="width: 15%; text-align: right;">Total HT</th>
                                        <th style="width: 6%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <tr class="item-row">
                                        <td><input type="text" class="form-control" name="items[0][description]" placeholder="Description du produit ou service" required></td>
                                        <td><input type="number" class="form-control item-qty" name="items[0][quantity]" value="1" min="0" step="any" oninput="recalculate()"></td>
                                        <td><input type="number" class="form-control item-price" name="items[0][unit_price]" value="0" min="0" step="0.01" oninput="recalculate()"></td>
                                        <td class="row-total">0,00 &euro;</td>
                                        <td class="text-center"><button type="button" class="btn-remove-item" onclick="removeItem(this)" title="Supprimer">&times;</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-add-item" onclick="addItem()">
                                <i class="fas fa-plus me-1"></i> Ajouter une ligne
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card quote-card">
                    <div class="card-header"><i class="fas fa-credit-card"></i> Notes et paiement</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="payment_status" class="form-label">Statut de paiement</label>
                            <select class="form-select" id="payment_status" name="payment_status" required>
                                <option value="unpaid" {{ old('payment_status', 'unpaid') == 'unpaid' ? 'selected' : '' }}>Non payée</option>
                                <option value="paid" {{ old('payment_status') == 'paid' ? 'selected' : '' }}>Payée</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Mode de paiement</label>
                            <select class="form-select" id="payment_method" name="payment_method">
                                <option value="">-- S&eacute;lectionnez --</option>
                                <option value="Virement bancaire" {{ old('payment_method') == 'Virement bancaire' ? 'selected' : '' }}>Virement bancaire</option>
                                <option value="Chèque" {{ old('payment_method') == 'Chèque' ? 'selected' : '' }}>Ch&egrave;que</option>
                                <option value="Espèces" {{ old('payment_method') == 'Espèces' ? 'selected' : '' }}>Esp&egrave;ces</option>
                                <option value="Carte bancaire" {{ old('payment_method') == 'Carte bancaire' ? 'selected' : '' }}>Carte bancaire</option>
                                <option value="Autre" {{ old('payment_method') == 'Autre' ? 'selected' : '' }}>Autre</option>
                            </select>
                        </div>
                        <div>
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Informations compl&eacute;mentaires...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="sticky-sidebar">
                    <div class="card quote-card">
                        <div class="card-header"><i class="fas fa-cog"></i> Param&egrave;tres</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="tax_rate" class="form-label">Taux TVA (%)</label>
                                <input type="number" class="form-control" id="tax_rate" name="tax_rate" value="{{ old('tax_rate', 20) }}" min="0" max="100" step="0.1" oninput="recalculate()">
                            </div>
                            <div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="show_due_date" {{ old('due_date') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="show_due_date" style="font-size: 0.875rem; font-weight: 500; color: #4a4a68;">Indiquer une date d'&eacute;ch&eacute;ance</label>
                                </div>
                                <div id="due_date_wrapper" style="display: {{ old('due_date') ? 'block' : 'none' }};">
                                    <input type="date" class="form-control" id="due_date" name="due_date" value="{{ old('due_date', \Carbon\Carbon::now()->addDays(30)->format('Y-m-d')) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card quote-card totals-card">
                        <div class="card-header"><i class="fas fa-calculator"></i> R&eacute;capitulatif</div>
                        <div class="card-body">
                            <div class="totals-row">
                                <span>Sous-total HT</span>
                                <span class="amount" id="subtotalDisplay">0,00 &euro;</span>
                            </div>
                            <div class="totals-row">
                                <span>TVA (<span id="taxRateDisplay">20</span>%)</span>
                                <span class="amount" id="taxDisplay">0,00 &euro;</span>
                            </div>
                            <div class="totals-row total-ttc">
                                <span>Total TTC</span>
                                <span class="amount" id="totalDisplay">0,00 &euro;</span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-generate">
                        <i class="fas fa-file-invoice me-2"></i> G&eacute;n&eacute;rer ma facture PDF
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    let itemIndex = 1;

    function addItem() {
        const tbody = document.getElementById('itemsBody');
        const tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.innerHTML = `
            <td><input type="text" class="form-control" name="items[${itemIndex}][description]" placeholder="Description du produit ou service" required></td>
            <td><input type="number" class="form-control item-qty" name="items[${itemIndex}][quantity]" value="1" min="0" step="any" oninput="recalculate()"></td>
            <td><input type="number" class="form-control item-price" name="items[${itemIndex}][unit_price]" value="0" min="0" step="0.01" oninput="recalculate()"></td>
            <td class="row-total">0,00 \u20AC</td>
            <td class="text-center"><button type="button" class="btn-remove-item" onclick="removeItem(this)" title="Supprimer">&times;</button></td>
        `;
        tbody.appendChild(tr);
        itemIndex++;
        recalculate();
    }

    function removeItem(btn) {
        const rows = document.querySelectorAll('#itemsBody .item-row');
        if (rows.length <= 1) return;
        btn.closest('.item-row').remove();
        recalculate();
    }

    function recalculate() {
        const rows = document.querySelectorAll('#itemsBody .item-row');
        let subtotal = 0;
        rows.forEach(function(row) {
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const lineTotal = qty * price;
            subtotal += lineTotal;
            row.querySelector('.row-total').textContent = lineTotal.toFixed(2).replace('.', ',') + ' \u20AC';
        });
        const taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
        const taxAmount = subtotal * (taxRate / 100);
        const totalTTC = subtotal + taxAmount;
        document.getElementById('subtotalDisplay').textContent = subtotal.toFixed(2).replace('.', ',') + ' \u20AC';
        document.getElementById('taxRateDisplay').textContent = taxRate % 1 === 0 ? taxRate.toFixed(0) : taxRate.toFixed(1);
        document.getElementById('taxDisplay').textContent = taxAmount.toFixed(2).replace('.', ',') + ' \u20AC';
        document.getElementById('totalDisplay').textContent = totalTTC.toFixed(2).replace('.', ',') + ' \u20AC';
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

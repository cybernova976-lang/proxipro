@extends('pro.layout')
@section('title', 'Nouveau Devis - Espace Pro')

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
                <li class="breadcrumb-item active">Nouveau devis</li>
            </ol>
        </nav>
        <h1>Créer un devis</h1>
    </div>
</div>

@include('pro.partials.seller-identity-note', ['documentLabel' => 'ce devis'])

@if($template ?? null)
<div class="alert alert-info" role="status" style="border-radius: 12px;">
    <i class="fas fa-copy me-2"></i>Formulaire prérempli avec le modèle « {{ $template->name }} ». Ajoutez le client, ajustez si besoin, puis enregistrez.
</div>
@else
    @include('pro.partials.quote-templates')
@endif

<form method="POST" action="{{ route('pro.quotes.store') }}">
    @csrf
    
    <div class="row g-4">
        <div class="col-lg-8">
            {{-- Client info --}}
            <div class="pro-card">
                <div class="pro-card-title"><i class="fas fa-user text-primary"></i> Informations client</div>
                
                <input type="hidden" name="client_id" id="clientIdHidden" value="">
                @if($clients->isNotEmpty())
                <div class="mb-3">
                    <label class="form-label fw-semibold">Sélectionner un client existant</label>
                    <select id="clientSelect" class="form-select" style="border-radius: 10px;" onchange="fillClientInfo(this)">
                        <option value="">-- Nouveau client --</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" 
                                data-name="{{ $client->name }}" 
                                data-email="{{ $client->email }}" 
                                data-phone="{{ $client->phone }}" 
                                data-address="{{ $client->address }}"
                                data-company="{{ $client->company }}">
                                {{ $client->name }}{{ $client->company ? ' (' . $client->company . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nom du client *</label>
                        <input type="text" name="client_name" id="clientName" class="form-control" required style="border-radius: 10px;" value="{{ old('client_name') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="client_email" id="clientEmail" class="form-control" style="border-radius: 10px;" value="{{ old('client_email') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Téléphone</label>
                        <input type="text" name="client_phone" id="clientPhone" class="form-control" style="border-radius: 10px;" value="{{ old('client_phone') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Adresse</label>
                        <input type="text" name="client_address" id="clientAddress" class="form-control" style="border-radius: 10px;" value="{{ old('client_address') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Entreprise du client</label>
                        <input type="text" name="client_company" id="clientCompany" class="form-control" style="border-radius: 10px;" value="{{ old('client_company') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Immatriculation</label>
                        <input type="text" name="client_registration_number" class="form-control" style="border-radius: 10px;" value="{{ old('client_registration_number') }}" placeholder="SIREN / registre local">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">N° TVA</label>
                        <input type="text" name="client_vat_number" class="form-control" style="border-radius: 10px;" value="{{ old('client_vat_number') }}">
                    </div>
                </div>
            </div>

            {{-- Quote details --}}
            <div class="pro-card">
                <div class="pro-card-title"><i class="fas fa-file-alt text-warning"></i> Détails du devis</div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Objet du devis *</label>
                    <input type="text" name="subject" class="form-control" required style="border-radius: 10px;" placeholder="Ex: Rénovation salle de bain" value="{{ old('subject') }}">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nature de l’opération *</label>
                        <select name="operation_type" class="form-select" required style="border-radius: 10px;">
                            <option value="services" @selected(old('operation_type') === 'services')>Prestation de services</option>
                            <option value="goods" @selected(old('operation_type') === 'goods')>Vente de biens</option>
                            <option value="mixed" @selected(old('operation_type') === 'mixed')>Biens et services</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Lieu d’exécution</label>
                        <input type="text" name="execution_location" class="form-control" value="{{ old('execution_location') }}" style="border-radius: 10px;" placeholder="Adresse du chantier ou à distance">
                    </div>
                </div>

                <label class="form-label fw-semibold">Lignes du devis</label>
                @include('pro.partials.document-lines', ['pkLines' => [], 'pkRowClass' => 'quote-item-row', 'pkContainerId' => 'quoteItems'])
            </div>

            {{-- Notes --}}
            <div class="pro-card">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" style="border-radius: 10px; font-size: 0.88rem;" placeholder="Notes visibles par le client">{{ old('notes') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Conditions</label>
                        <textarea name="conditions" class="form-control" rows="3" style="border-radius: 10px; font-size: 0.88rem;" placeholder="Conditions de réalisation">{{ old('conditions', "Devis valable 30 jours.\nAcompte de 30% à la commande.") }}</textarea>
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
                    <input type="number" name="tax_rate" id="taxRate" class="form-control" value="{{ old('tax_rate', $user->defaultVatRate()) }}" min="0" max="100" step="0.1" style="border-radius: 10px;" onchange="recalculate()">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Validité jusqu'au</label>
                    <input type="date" name="valid_until" class="form-control" value="{{ now()->addDays(30)->format('Y-m-d') }}" style="border-radius: 10px;">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Établissement du devis</label>
                    <select name="is_free" class="form-select" style="border-radius: 10px;" required>
                        <option value="1" @selected(old('is_free', '1') === '1')>Gratuit</option>
                        <option value="0" @selected(old('is_free') === '0')>Payant (préciser dans les conditions)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Acompte demandé (%)</label>
                    <input type="number" name="deposit_percentage" class="form-control" value="{{ old('deposit_percentage', 30) }}" min="0" max="100" step="0.01" style="border-radius: 10px;">
                </div>

                <div class="quote-totals mt-4">
                    <div class="total-line">
                        <span>Sous-total HT</span>
                        <span id="subtotal" class="fw-semibold">0,00€</span>
                    </div>
                    <div class="total-line">
                        <span>TVA (<span id="taxLabel">{{ old('tax_rate', $user->defaultVatRate()) }}</span>%)</span>
                        <span id="taxAmount" class="fw-semibold">0,00€</span>
                    </div>
                    <div class="total-line total-final">
                        <span>Total TTC</span>
                        <span id="totalTTC">0,00€</span>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-pro-primary">
                        <i class="fas fa-save me-1"></i> Enregistrer le devis
                    </button>
                    <a href="{{ route('pro.quotes') }}" class="btn btn-light" style="border-radius: 10px;">Annuler</a>
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
    document.getElementById('clientCompany').value = option.dataset.company || '';
}
</script>
@if($template ?? null)
<script type="application/json" id="pkQuoteTemplate">{!! json_encode($template->formPayload(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
<script src="{{ asset('js/pro-quote-template.js') }}?v={{ @filemtime(public_path('js/pro-quote-template.js')) ?: 1 }}"></script>
@endif
@endsection

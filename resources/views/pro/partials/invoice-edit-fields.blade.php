@php
    // Champs de la création d'une facture que l'écran de modification n'affichait pas.
    // $part : « client », « details » ou « payment », selon la carte où ils s'insèrent.
    $pkClientType = old('client_type', $invoice->client_type ?: ($invoice->client_company ? 'business' : 'individual'));
    $pkOperation = old('operation_type', $invoice->operation_type ?: 'services');
@endphp
@if($part === 'client')
    <div class="col-md-6">
        <label class="form-label fw-semibold" for="clientCompany">Entreprise du client</label>
        <input type="text" name="client_company" id="clientCompany" class="form-control pk-doc-input" maxlength="255" value="{{ old('client_company', $invoice->client_company) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold" for="clientType">Type de client *</label>
        <select name="client_type" id="clientType" class="form-select pk-doc-input" required>
            <option value="individual" @selected($pkClientType === 'individual')>Particulier</option>
            <option value="business" @selected($pkClientType === 'business')>Professionnel / entreprise</option>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold" for="clientRegistration">Immatriculation client</label>
        <input type="text" name="client_registration_number" id="clientRegistration" class="form-control pk-doc-input" maxlength="64" value="{{ old('client_registration_number', $invoice->client_registration_number) }}" placeholder="SIREN / registre local">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold" for="clientVat">N° TVA client</label>
        <input type="text" name="client_vat_number" id="clientVat" class="form-control pk-doc-input" maxlength="64" value="{{ old('client_vat_number', $invoice->client_vat_number) }}">
    </div>
@elseif($part === 'details')
    <div class="row g-3 mt-1">
        <div class="col-md-6">
            <label class="form-label fw-semibold" for="operationType">Nature de l’opération *</label>
            <select name="operation_type" id="operationType" class="form-select pk-doc-input" required>
                <option value="services" @selected($pkOperation === 'services')>Prestation de services</option>
                <option value="goods" @selected($pkOperation === 'goods')>Vente de biens</option>
                <option value="mixed" @selected($pkOperation === 'mixed')>Biens et services</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold" for="serviceDate">Date de vente / prestation *</label>
            <input type="date" name="service_date" id="serviceDate" class="form-control pk-doc-input" required value="{{ old('service_date', optional($invoice->service_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold" for="purchaseOrder">Bon de commande</label>
            <input type="text" name="purchase_order_number" id="purchaseOrder" class="form-control pk-doc-input" maxlength="100" value="{{ old('purchase_order_number', $invoice->purchase_order_number) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold" for="deliveryAddress">Adresse de livraison</label>
            <input type="text" name="delivery_address" id="deliveryAddress" class="form-control pk-doc-input" maxlength="500" value="{{ old('delivery_address', $invoice->delivery_address) }}">
        </div>
    </div>
@elseif($part === 'payment')
    <div class="row g-3 mt-1">
        <div class="col-md-6">
            <label class="form-label fw-semibold" for="paymentTerms">Conditions de paiement *</label>
            <textarea name="payment_terms" id="paymentTerms" class="form-control pk-doc-input" rows="2" required maxlength="1000">{{ old('payment_terms', $invoice->payment_terms ?: 'Paiement à 30 jours à compter de la date d’émission.') }}</textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold" for="earlyDiscount">Escompte *</label>
            <input type="text" name="early_payment_discount" id="earlyDiscount" class="form-control pk-doc-input" required maxlength="255" value="{{ old('early_payment_discount', $invoice->early_payment_discount ?: 'Néant') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold" for="latePenalty">Pénalités de retard (%)</label>
            <input type="number" name="late_penalty_rate" id="latePenalty" class="form-control pk-doc-input" min="0" max="100" step="0.01" value="{{ old('late_penalty_rate', $invoice->late_penalty_rate) }}">
            <small class="text-muted">Obligatoire pour un client professionnel.</small>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold" for="vatExemption">Mention d’exonération de TVA</label>
            <input type="text" name="vat_exemption_reason" id="vatExemption" class="form-control pk-doc-input" maxlength="255" value="{{ old('vat_exemption_reason', $invoice->vat_exemption_reason) }}">
            <small class="text-muted">Requise uniquement si le taux de TVA est 0 %.</small>
        </div>
    </div>
@endif

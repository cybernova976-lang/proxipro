@php
    // Champs de la création d'un devis que l'écran de modification n'affichait pas.
    // $part : « client », « details » ou « settings », selon la carte où ils s'insèrent.
    $pkOperation = old('operation_type', $quote->operation_type ?: 'services');
    $pkFree = (string) old('is_free', $quote->is_free === false ? '0' : '1');
@endphp
@if($part === 'client')
    <div class="col-md-6">
        <label class="form-label fw-semibold" for="clientCompany">Entreprise du client</label>
        <input type="text" name="client_company" id="clientCompany" class="form-control pk-doc-input" maxlength="255" value="{{ old('client_company', $quote->client_company) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold" for="clientRegistration">Immatriculation</label>
        <input type="text" name="client_registration_number" id="clientRegistration" class="form-control pk-doc-input" maxlength="64" value="{{ old('client_registration_number', $quote->client_registration_number) }}" placeholder="SIREN / registre local">
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold" for="clientVat">N° TVA</label>
        <input type="text" name="client_vat_number" id="clientVat" class="form-control pk-doc-input" maxlength="64" value="{{ old('client_vat_number', $quote->client_vat_number) }}">
    </div>
@elseif($part === 'details')
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold" for="operationType">Nature de l’opération *</label>
            <select name="operation_type" id="operationType" class="form-select pk-doc-input" required>
                <option value="services" @selected($pkOperation === 'services')>Prestation de services</option>
                <option value="goods" @selected($pkOperation === 'goods')>Vente de biens</option>
                <option value="mixed" @selected($pkOperation === 'mixed')>Biens et services</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold" for="executionLocation">Lieu d’exécution</label>
            <input type="text" name="execution_location" id="executionLocation" class="form-control pk-doc-input" maxlength="255" value="{{ old('execution_location', $quote->execution_location) }}" placeholder="Adresse du chantier ou à distance">
        </div>
    </div>
@elseif($part === 'settings')
    <div class="mb-3">
        <label class="form-label fw-semibold" for="isFree">Établissement du devis</label>
        <select name="is_free" id="isFree" class="form-select pk-doc-input" required>
            <option value="1" @selected($pkFree === '1')>Gratuit</option>
            <option value="0" @selected($pkFree === '0')>Payant (préciser dans les conditions)</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label fw-semibold" for="depositPercentage">Acompte demandé (%)</label>
        <input type="number" name="deposit_percentage" id="depositPercentage" class="form-control pk-doc-input" value="{{ old('deposit_percentage', $quote->deposit_percentage) }}" min="0" max="100" step="0.01">
    </div>
@endif

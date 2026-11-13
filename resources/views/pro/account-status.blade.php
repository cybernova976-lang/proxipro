@extends('pro.layout')
@section('title', 'Statut du compte - Espace Pro')
@section('topbar_title', 'Statut et conformité')

@section('content')
@php
    $currentStatus = $user->isEntreprise() ? 'entreprise' : ($user->isAutoEntrepreneur() ? 'auto-entrepreneur' : 'particulier');
    $selectedStatus = old('pro_status', $currentStatus);
    $statusOptions = [
        'particulier' => ['icon' => 'fa-user', 'title' => 'Particulier prestataire',
            'text' => 'Vous rendez des services ponctuels, sans activité déclarée. Pas de devis ni de facture à votre nom.'],
        'auto-entrepreneur' => ['icon' => 'fa-user-tie', 'title' => 'Auto-entrepreneur',
            'text' => 'Micro-entreprise immatriculée. Devis et factures à votre nom, avec votre SIRET.'],
        'entreprise' => ['icon' => 'fa-building', 'title' => 'Société',
            'text' => 'SASU, SAS, SARL, EURL, SA… Devis et factures au nom de votre société.'],
    ];
@endphp
<div class="pro-content-header">
    <div>
        <h1>Votre statut professionnel</h1>
        <p class="text-muted mb-0">Statut actuel : <strong>{{ $user->getAccountTypeLabel() }}</strong>. Vous pouvez en changer à tout moment, vos clients, devis et factures sont conservés.</p>
    </div>
</div>

@include('pro.partials.status-tabs')

<form method="POST" action="{{ route('pro.account-status.update') }}" class="pk-status-form">
    @csrf @method('PUT')

    <fieldset class="pro-card">
        <legend class="pro-card-title">Quelle est votre situation ?</legend>
        <div class="pk-status-options">
            @foreach($statusOptions as $value => $option)
                <label class="pk-status-option">
                    <input type="radio" name="pro_status" value="{{ $value }}" {{ $selectedStatus === $value ? 'checked' : '' }} class="visually-hidden status-radio">
                    <span class="pk-status-option__card">
                        <i class="fas {{ $option['icon'] }}" aria-hidden="true"></i>
                        <span class="pk-status-option__title">{{ $option['title'] }}</span>
                        <span class="pk-status-option__text">{{ $option['text'] }}</span>
                    </span>
                </label>
            @endforeach
        </div>
    </fieldset>

    {{-- Visible seulement pour un statut professionnel (règle CSS dans pro-space.css). --}}
    <fieldset class="pro-card pk-status-company">
        <legend class="pro-card-title">Votre entreprise</legend>
        <p class="small text-muted">Ces informations figureront sur vos devis et factures. Elles concernent votre activité, pas l’entreprise qui exploite Prokejem. Ne renseignez un numéro de TVA que s’il vous a été attribué.</p>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold" for="companyName">Nom de l’entreprise ou raison sociale</label>
                <input type="text" id="companyName" name="company_name" class="form-control" value="{{ old('company_name', $user->company_name) }}" autocomplete="organization">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" for="siret">Numéro SIRET</label>
                <input type="text" id="siret" name="siret" class="form-control" value="{{ old('siret', $user->siret) }}" placeholder="14 chiffres (ou registre local hors de France)" maxlength="64" inputmode="numeric">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" for="tvaNumber">Numéro de TVA intracommunautaire <span class="text-muted fw-normal">(facultatif)</span></label>
                <input type="text" id="tvaNumber" name="tva_number" class="form-control" value="{{ old('tva_number', $user->tva_number) }}" placeholder="FR12345678901">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" for="insuranceNumber">Assurance RC Pro <span class="text-muted fw-normal">(facultatif)</span></label>
                <input type="text" id="insuranceNumber" name="insurance_number" class="form-control" value="{{ old('insurance_number', $user->insurance_number) }}">
            </div>
        </div>
    </fieldset>

    <div class="pk-status-submit">
        <p class="small text-muted mb-0">Après l’enregistrement, il vous reste à cocher les conditions d’émission (profil vérifié, adresse, acceptation des conditions PRO).</p>
        <button type="submit" class="btn btn-pro-primary">Enregistrer mon statut</button>
    </div>
</form>
@endsection

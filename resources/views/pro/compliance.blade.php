@extends('pro.layout')
@section('title', 'Conformité PRO - Espace Pro')

@section('content')
<div class="pro-content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: .8rem;">
                <li class="breadcrumb-item"><a href="{{ route('pro.dashboard') }}">Espace PRO</a></li>
                <li class="breadcrumb-item active">Conformité</li>
            </ol>
        </nav>
        <h1>Checklist de conformité PRO</h1>
        <p class="text-muted mb-0">Préparez vos brouillons dès maintenant et débloquez leur émission quand votre activité est prête.</p>
    </div>
    <span class="pro-status pro-status-{{ $user->canIssueCommercialDocuments() ? 'success' : 'warning' }}" style="padding: 9px 14px;">
        <i class="fas fa-{{ $user->canIssueCommercialDocuments() ? 'check-circle' : 'clock' }} me-1"></i>
        {{ $user->canIssueCommercialDocuments() ? 'Émission autorisée' : 'Brouillons uniquement' }}
    </span>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="pro-card">
            <div class="pro-card-title"><i class="fas fa-tasks text-primary"></i> Conditions d’émission</div>
            @php
                $checks = [
                    ['ok' => $user->isProfessionnel(), 'label' => 'Statut professionnel déclaré', 'route' => route('pro.account-status')],
                    ['ok' => $user->hasCompleteVerificationProfile(), 'label' => 'Profil entièrement complété', 'route' => route('profile.edit')],
                    ['ok' => $user->hasVerifiedProfileBadge(), 'label' => 'Badge « Profil vérifié » obtenu', 'route' => route('verification.index')],
                    ['ok' => filled($user->company_name), 'label' => 'Raison sociale renseignée', 'route' => route('pro.account-status')],
                    ['ok' => $user->hasValidBusinessRegistrationNumber(), 'label' => 'Immatriculation valide (SIRET à 14 chiffres en France)', 'route' => route('pro.account-status')],
                    ['ok' => filled($user->address) && filled($user->city) && filled($user->country), 'label' => 'Adresse professionnelle complète', 'route' => route('pro.profile.edit')],
                    ['ok' => $user->hasAcceptedCurrentProTerms(), 'label' => 'Conditions PRO version '.config('legal.pro_terms_version').' acceptées', 'route' => '#pro-terms'],
                ];
            @endphp
            <div class="d-grid gap-2">
                @foreach($checks as $check)
                    <a href="{{ $check['route'] }}" class="d-flex align-items-center justify-content-between p-3 text-decoration-none" style="border: 1px solid {{ $check['ok'] ? '#bbf7d0' : '#fde68a' }}; border-radius: 12px; background: {{ $check['ok'] ? '#f0fdf4' : '#fffbeb' }}; color: #1e293b;">
                        <span><i class="fas fa-{{ $check['ok'] ? 'check-circle text-success' : 'exclamation-circle text-warning' }} me-2"></i>{{ $check['label'] }}</span>
                        @unless($check['ok'])<i class="fas fa-arrow-right text-muted"></i>@endunless
                    </a>
                @endforeach
            </div>
        </div>

        <div class="pro-card" id="pro-terms">
            <div class="pro-card-title"><i class="fas fa-file-signature text-success"></i> Engagement de l’émetteur</div>
            <p class="text-muted" style="font-size: .88rem;">ProxiPro fournit un outil d’aide à la création. Le professionnel reste responsable de l’exactitude des mentions, de la fiscalité applicable et des obligations du pays où il exerce.</p>
            @if($user->hasAcceptedCurrentProTerms())
                <div class="alert alert-success mb-0" style="border-radius: 12px;">
                    <i class="fas fa-check-circle me-2"></i>Version {{ $user->pro_terms_version }} acceptée le {{ $user->pro_terms_accepted_at->format('d/m/Y à H:i') }}.
                </div>
            @else
                <form method="POST" action="{{ route('pro.compliance.accept') }}">
                    @csrf
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="certify_information" value="1" id="certifyInformation" required>
                        <label class="form-check-label" for="certifyInformation">Je certifie que mes informations professionnelles sont exactes et à jour.</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="accept_pro_terms" value="1" id="acceptProTerms" required>
                        <label class="form-check-label" for="acceptProTerms">J’accepte les <a href="{{ route('legal.pro-terms') }}" target="_blank">conditions de l’Espace PRO</a> et les <a href="{{ route('legal.platform-rules') }}" target="_blank">règles de la marketplace</a>.</label>
                    </div>
                    <button class="btn btn-pro-primary"><i class="fas fa-signature me-1"></i> Accepter la version {{ config('legal.pro_terms_version') }}</button>
                </form>
            @endif
        </div>
    </div>

    <div class="col-lg-5">
        <div class="pro-card" style="border-top: 4px solid #6366f1;">
            <div class="pro-card-title"><i class="fas fa-file-invoice text-primary"></i> Ce que protège ce parcours</div>
            <ul class="text-muted ps-3 mb-0" style="font-size: .86rem; line-height: 1.8;">
                <li>Numérotation définitive continue au moment de l’émission.</li>
                <li>Brouillons clairement identifiés et modifiables.</li>
                <li>Documents émis verrouillés pour préserver leur historique.</li>
                <li>Coordonnées du vendeur figées lors de l’émission.</li>
                <li>Clients isolés entre les différents prestataires.</li>
            </ul>
        </div>
        <div class="pro-card" style="background: #eff6ff; border-color: #bfdbfe;">
            <h6 class="fw-bold"><i class="fas fa-info-circle text-primary me-2"></i>Facturation électronique</h6>
            <p class="mb-0 text-muted" style="font-size: .84rem;">Le PDF est utile pour le suivi et l’archivage, mais il ne remplace pas les futurs flux structurés obligatoires de facturation électronique. Une connexion à une plateforme agréée devra être ajoutée selon le calendrier applicable.</p>
        </div>
    </div>
</div>
@endsection

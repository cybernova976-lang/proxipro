@extends('pro.layout')
@section('title', 'Conformité PRO - Espace Pro')

@section('content')
@php
    $checks = [
        ['ok' => $user->isProfessionnel(), 'label' => 'Statut professionnel déclaré', 'route' => route('pro.account-status')],
        ['ok' => $user->hasCompleteVerificationProfile(), 'label' => 'Profil entièrement complété', 'route' => route('profile.edit')],
        ['ok' => $user->hasVerifiedProfileBadge(), 'label' => 'Badge « Profil vérifié » obtenu', 'route' => route('verification.index')],
        ['ok' => filled($user->company_name), 'label' => 'Raison sociale renseignée', 'route' => route('pro.account-status')],
        ['ok' => $user->hasValidBusinessRegistrationNumber(), 'label' => 'Numéro d’immatriculation renseigné au format attendu', 'route' => route('pro.account-status')],
        ['ok' => filled($user->address) && filled($user->city) && filled($user->country), 'label' => 'Adresse professionnelle complète', 'route' => route('pro.profile.edit')],
        ['ok' => $user->hasAcceptedCurrentProTerms(), 'label' => 'Conditions PRO version '.config('legal.pro_terms_version').' acceptées', 'route' => '#pro-terms'],
    ];
    $completedChecks = collect($checks)->where('ok', true)->count();
    $nextCheck = collect($checks)->firstWhere('ok', false);
    $canIssue = $user->canIssueCommercialDocuments();
@endphp
<div class="pro-content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: .8rem;">
                <li class="breadcrumb-item"><a href="{{ route('pro.dashboard') }}">Espace PRO</a></li>
                <li class="breadcrumb-item active">Conformité</li>
            </ol>
        </nav>
        <h1>Checklist de conformité PRO</h1>
        <p class="text-muted mb-0">Configurez votre identité professionnelle, puis préparez et émettez vos documents.</p>
    </div>
    <span class="pro-status pro-status-{{ $canIssue ? 'success' : 'warning' }}" style="padding: 9px 14px;">
        <i class="fas fa-{{ $canIssue ? 'check-circle' : 'clock' }} me-1"></i>
        {{ $canIssue ? 'Émission autorisée' : ($user->isProfessionnel() ? 'Brouillons uniquement' : 'Statut professionnel à déclarer') }}
    </span>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="pro-card">
            <div class="pro-card-title"><i class="fas fa-tasks text-primary"></i> Conditions d’émission</div>
            <p class="small text-muted">{{ $completedChecks }} / {{ count($checks) }} étapes complétées</p>
            <div class="progress mb-3" role="progressbar" aria-label="Configuration de l’espace PRO" aria-valuenow="{{ $completedChecks }}" aria-valuemin="0" aria-valuemax="{{ count($checks) }}" style="height: 6px;">
                <div class="progress-bar {{ $canIssue ? 'bg-success' : '' }}" style="width: {{ $completedChecks / count($checks) * 100 }}%; background-color: #6366f1;"></div>
            </div>
            @if($nextCheck)
                <div class="rounded-3 p-3 mb-3" style="background: #eef2ff; border: 1px solid #c7d2fe;">
                    <strong class="d-block mb-2">Prochaine étape : {{ $nextCheck['label'] }}</strong>
                    <a class="btn btn-pro-primary" href="{{ $nextCheck['route'] }}">Compléter cette étape <i class="fas fa-arrow-right ms-1" aria-hidden="true"></i></a>
                </div>
            @else
                <div class="rounded-3 p-3 mb-3" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
                    <strong class="d-block mb-2">Votre espace est prêt pour l’émission.</strong>
                    <a class="btn btn-pro-primary" href="{{ route('pro.quotes.create') }}">Créer un devis <i class="fas fa-arrow-right ms-1" aria-hidden="true"></i></a>
                </div>
            @endif
            <div class="d-grid gap-2">
                @foreach($checks as $check)
                    <a href="{{ $check['route'] }}" class="d-flex align-items-center justify-content-between p-3 text-decoration-none" style="border: 1px solid {{ $check['ok'] ? '#bbf7d0' : '#fde68a' }}; border-radius: 12px; background: {{ $check['ok'] ? '#f0fdf4' : '#fffbeb' }}; color: #1e293b;">
                        <span><i class="fas fa-{{ $check['ok'] ? 'check-circle text-success' : 'exclamation-circle text-warning' }} me-2" aria-hidden="true"></i><span class="visually-hidden">{{ $check['ok'] ? 'Complété : ' : 'À compléter : ' }}</span>{{ $check['label'] }}</span>
                        @unless($check['ok'])<i class="fas fa-arrow-right text-muted"></i>@endunless
                    </a>
                @endforeach
            </div>
        </div>

        <div class="pro-card" id="pro-terms">
            <div class="pro-card-title"><i class="fas fa-file-signature text-success"></i> Engagement de l’émetteur</div>
            <p class="text-muted" style="font-size: .88rem;">Prokejem fournit un outil d’aide à la création. Le professionnel reste responsable de l’exactitude des mentions, de la fiscalité applicable et des obligations du pays où il exerce.</p>
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
        <div class="pro-card" style="border-top: 4px solid #6366f1; overflow-wrap: anywhere;">
            <div class="pro-card-title"><i class="fas fa-building text-primary" aria-hidden="true"></i> Votre identité d’émetteur</div>
            <dl class="mb-3">
                <dt>Statut</dt><dd>{{ $user->getAccountTypeLabel() }}</dd>
                <dt>Raison sociale</dt><dd>{{ $user->company_name ?: 'À renseigner' }}</dd>
                <dt>Immatriculation</dt><dd>{{ $user->siret ?: 'À renseigner' }}</dd>
                <dt>Adresse professionnelle</dt>
                <dd>{{ $user->address ?: 'Adresse à renseigner' }}<br>{{ $user->postal_code }} {{ $user->city }} {{ $user->country }}</dd>
            </dl>
            <a href="{{ route('pro.account-status') }}" class="btn btn-outline-primary btn-sm">Modifier mon identité</a>
            <a href="{{ route('pro.profile.edit') }}" class="btn btn-link btn-sm">Modifier mon adresse</a>
            <p class="small text-muted mt-3 mb-0">Ces informations concernent votre activité, pas l’entreprise qui exploite la plateforme. Le contrôle du format d’un numéro ne vaut pas vérification de son attribution par un registre officiel.</p>
        </div>
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
            <p class="mb-0 text-muted" style="font-size: .84rem;">Un PDF ne constitue pas, à lui seul, un flux de facturation électronique réglementaire. Vérifiez avec votre comptable les obligations et le calendrier applicables à votre activité et à vos clients. Cet outil ne constitue pas une plateforme agréée de facturation électronique.</p>
            <a class="small d-inline-block mt-2" href="https://www.impots.gouv.fr/professionnel/je-decouvre-la-facturation-electronique" target="_blank" rel="noopener noreferrer">Consulter les informations officielles <i class="fas fa-external-link-alt ms-1" aria-hidden="true"></i></a>
        </div>
    </div>
</div>
@endsection

@php
    // Logo des devis et factures : réglage dans le profil Pro (avantage Premium).
    $pkLogoPremium = $user->hasActiveProSubscription();
@endphp
<div class="pro-card" data-document-logo>
    <h6 class="fw-bold mb-2"><i class="fas fa-stamp me-2 text-primary" aria-hidden="true"></i>Logo sur vos devis et factures</h6>
    @if($pkLogoPremium)
        @if($user->company_logo)
            <img src="{{ storage_url($user->company_logo) }}" alt="Logo actuel" class="img-fluid border rounded mb-2 p-2 bg-white">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="remove_company_logo" value="1" id="pkRemoveCompanyLogo">
                <label class="form-check-label small" for="pkRemoveCompanyLogo">Retirer le logo</label>
            </div>
        @endif
        <label for="pkCompanyLogo" class="visually-hidden">Fichier du logo</label>
        <input type="file" name="company_logo" id="pkCompanyLogo" class="form-control" accept="image/png,image/jpeg">
        <small class="text-muted d-block mt-1">PNG ou JPG, {{ (int) (\App\Support\DocumentLogo::MAX_KILOBYTES / 1024) }} Mo au plus. Il apparaît en tête de vos devis et factures en PDF.</small>
        @error('company_logo')<small class="text-danger d-block mt-1" role="alert">{{ $message }}</small>@enderror
    @else
        <p class="small text-muted mb-2">Avec Premium, votre logo apparaît en tête de vos devis et factures en PDF.@if($user->company_logo) Votre logo est conservé : il réapparaîtra dès la reprise de l’abonnement.@endif</p>
        <a href="{{ route('pro.subscription') }}" class="small fw-semibold">Voir Premium</a>
    @endif
</div>

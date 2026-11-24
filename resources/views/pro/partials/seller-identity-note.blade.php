{{--
    Ce que le document portera comme identité d'émetteur : exactement ce que le
    membre a renseigné (User::commercialIdentitySnapshot). Sans SIRET renseigné,
    aucun numéro n'est ajouté ; l'absence de SIRET ne bloque pas les documents offerts.
    Paramètre : $documentLabel (« ce devis », « cette facture »).
--}}
@php
    $pkSellerName = $user->company_name ?: $user->name;
    $pkEditUrl = $user->isProfessionnel() || $user->isServiceProvider() ? route('pro.account-status') : route('profile.edit');
@endphp
<div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap gap-2" role="note" data-seller-identity-note>
    <span>
        <i class="far fa-id-card me-2" aria-hidden="true"></i>
        {{ ucfirst($documentLabel) }} portera le nom <strong>{{ $pkSellerName }}</strong> et les coordonnées de votre compte, telles que vous les avez renseignées.
        @if(filled($user->siret))
            SIRET indiqué : {{ $user->siret }}.
        @else
            Aucun SIRET n’est renseigné : aucun numéro d’immatriculation n’y figurera.
        @endif
    </span>
    <a href="{{ $pkEditUrl }}" class="btn btn-sm btn-outline-secondary">Modifier mes informations</a>
</div>

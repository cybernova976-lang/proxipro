{{-- Deux étapes liées : déclarer son statut, puis compléter l'identité qui figurera sur les documents. --}}
@php
    $pkMissing = count(Auth::user()->proCommercialMissingRequirements());
@endphp
<nav class="pk-status-tabs" aria-label="Statut et conformité">
    <a href="{{ route('pro.account-status') }}" @class(['active' => request()->routeIs('pro.account-status*')]) @if(request()->routeIs('pro.account-status*')) aria-current="page" @endif>
        <span class="pk-status-tabs__step">1</span> Votre statut
    </a>
    <a href="{{ route('pro.compliance') }}" @class(['active' => request()->routeIs('pro.compliance*')]) @if(request()->routeIs('pro.compliance*')) aria-current="page" @endif>
        <span class="pk-status-tabs__step">2</span> Identité sur vos documents
        <span class="pk-status-tabs__count">{{ $pkMissing === 0 ? 'Prêt' : $pkMissing.' à faire' }}</span>
    </a>
</nav>

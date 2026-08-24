{{-- Barre d'onglets mobile — « Publier » au centre, surelevee --}}
@php
    $pkPublishUrl = ($pkRole ?? 'client') === 'provider'
        ? route('ads.create', ['type' => 'service'])
        : route('demand.create');
@endphp

<nav class="pk-tabbar" aria-label="Navigation principale">
    <a href="{{ route('feed') }}" class="is-active" aria-current="page">
        <i class="fas fa-home"></i><span>Accueil</span>
    </a>
    <a href="{{ route('ads.index') }}">
        <i class="fas fa-clipboard-list"></i><span>Annonces</span>
    </a>
    <a href="{{ $pkPublishUrl }}" class="pk-tabbar__pub">
        <i class="fas fa-plus"></i><span>Publier</span>
    </a>
    <a href="{{ route('messages.index') }}">
        <i class="far fa-comments"></i><span>Messages</span>
    </a>
    <a href="{{ route('profile.show') }}">
        <i class="far fa-user"></i><span>Profil</span>
    </a>
</nav>

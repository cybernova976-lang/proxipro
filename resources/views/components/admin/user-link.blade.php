@props(['user' => null, 'fallback' => 'Compte supprimé', 'fiche' => false])
@php
    // Nom d'un membre dans l'administration : il ouvre son profil public, dans un nouvel onglet.
    // Avec « fiche », une petite icône à côté mène à sa fiche dans l'administration.
    $linkable = $user instanceof \App\Models\User && ! $user->trashed();
@endphp
@if($linkable)
    <a href="{{ route('profile.public', $user->id) }}" target="_blank" rel="noopener"
       title="Voir le profil public de {{ $user->name }} (nouvel onglet)"
       {{ $attributes->merge(['class' => 'text-decoration-none']) }}>{{ $user->name }}<i class="fas fa-arrow-up-right-from-square ms-1 small opacity-50" aria-hidden="true"></i></a>
    @if($fiche)
        <a href="{{ route('admin.users.show', $user->id) }}" class="ms-1 text-secondary small"
           title="Fiche admin de {{ $user->name }}" aria-label="Fiche admin de {{ $user->name }}"><i class="fas fa-id-card" aria-hidden="true"></i></a>
    @endif
@else
    <span {{ $attributes }}>{{ $user?->name ?? $fallback }}</span>
@endif

{{-- Ligne de liste : utilisee quand le flux client compte de 1 a 3 offres, a la place de la grille. --}}
@php
    $pkSaved = collect($pkSaved ?? []);

    $pkPhotos = $ad->photos ?? [];
    if (is_string($pkPhotos)) {
        $pkDecoded = json_decode($pkPhotos, true);
        $pkPhotos = (json_last_error() === JSON_ERROR_NONE && is_array($pkDecoded)) ? $pkDecoded : [$pkPhotos];
    }
    $pkFirstPhoto = ltrim(trim((string) (array_values(array_filter((array) $pkPhotos))[0] ?? '')), '/');
    $pkThumb = match (true) {
        $pkFirstPhoto === '' => null,
        str_starts_with($pkFirstPhoto, 'http://') || str_starts_with($pkFirstPhoto, 'https://') => $pkFirstPhoto,
        str_starts_with($pkFirstPhoto, 'storage/') => asset($pkFirstPhoto),
        default => storage_url(preg_replace('#^public/#', '', $pkFirstPhoto)),
    };

    $pkIsUrgent = $ad->is_urgent && (! $ad->urgent_until || $ad->urgent_until->isFuture());
    // Seule l'option payante renseigne urgent_until ; l'urgence choisie au dépôt est gratuite.
    $pkUrgentLabel = \App\Support\AdDisclosure::urgentLabel($ad);
    $pkIsSponsored = \App\Support\AdDisclosure::isSponsored($ad);

    $pkAuthor = $ad->user;
    $pkName = $pkAuthor?->name ?? 'Utilisateur';
    $pkStatus = \App\Support\AdDisclosure::advertiserStatus($pkAuthor);
    $pkPlace = $ad->location ?: ($ad->city ?: null);
    $pkUrl = route('ads.show', $ad);
    $pkCategoryLabel = $ad->category ?: ($ad->main_category ?: 'Service');
    $pkCategoryIcon = collect($pkSearchIndex ?? [])->firstWhere('label', $pkCategoryLabel)['icon'] ?? 'fas fa-briefcase';
    $pkIsSaved = $pkSaved->contains((int) $ad->id);
@endphp
<article class="pk-ad-row{{ $pkIsUrgent ? ' is-urgent' : '' }}">
    <a class="pk-ad-row__thumb" href="{{ $pkUrl }}" tabindex="-1" aria-hidden="true">
        <i class="{{ $pkCategoryIcon }}"></i>
        @if($pkThumb)
            <img src="{{ $pkThumb }}" alt="" width="72" height="72" loading="lazy" decoding="async" data-pk-row-photo>
        @endif
    </a>
    <div class="pk-ad-row__body">
        <div class="pk-ad-row__top">
            <span class="pk-ad-row__cat">{{ $pkCategoryLabel }}</span>
            @if($pkUrgentLabel)
                <span class="pk-tag pk-tag--urgent"><i class="fas fa-bolt" aria-hidden="true"></i> {{ $pkUrgentLabel }}</span>
            @endif
            @if($pkIsSponsored)
                <span class="pk-tag pk-tag--sponsored">{{ \App\Support\AdDisclosure::SPONSORED_LABEL }}<span class="pk-sr"> : {{ \App\Support\AdDisclosure::SPONSORED_SCREEN_READER }}</span></span>
                <a class="pk-ad-row__why" href="{{ route('legal.platform-rules') }}#classement" aria-label="Pourquoi ? Règles de classement des offres sponsorisées">Pourquoi ?</a>
            @endif
        </div>
        <h3><a href="{{ $pkUrl }}">{{ $ad->title }}</a></h3>
        <p class="pk-ad-row__facts">
            <span><i class="fas fa-map-marker-alt" aria-hidden="true"></i> {{ $pkPlace ?: 'Lieu non précisé' }}</span>
            @if($ad->isRemoteIntervention())
                <span class="pk-tag pk-tag--remote"><i class="fas fa-laptop-house" aria-hidden="true"></i> À distance</span>
            @endif
            <strong>{{ $ad->formatted_price }}</strong>
        </p>
        <p class="pk-ad-row__meta">
            <span>{{ $pkName }}</span>
            @if($pkStatus)
                · <span class="pk-ad-row__status">{{ $pkStatus }}</span>
            @endif
            @if($ad->created_at)
                · <time datetime="{{ $ad->created_at->toIso8601String() }}">{{ $ad->created_at->diffForHumans() }}</time>
            @endif
        </p>
    </div>
    <div class="pk-ad-row__actions">
        <a href="{{ $pkUrl }}" class="pk-btn-sm">Voir l'offre <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        @auth
            <button type="button" class="pk-save" data-pk-save="{{ $ad->id }}"
                    aria-pressed="{{ $pkIsSaved ? 'true' : 'false' }}"
                    aria-label="{{ $pkIsSaved ? 'Retirer des favoris' : 'Enregistrer dans les favoris' }}">
                <i class="{{ $pkIsSaved ? 'fas' : 'far' }} fa-bookmark" aria-hidden="true"></i>
            </button>
        @endauth
    </div>
</article>

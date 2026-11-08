@php
    $pkRole  = $pkRole  ?? 'client';
    $pkSaved = collect($pkSaved ?? []);

    // Une photo principale ; le détail conserve la galerie complète.
    $pkPhotos = $ad->photos ?? [];
    if (is_string($pkPhotos)) {
        $pkDecoded = json_decode($pkPhotos, true);
        $pkPhotos = (json_last_error() === JSON_ERROR_NONE && is_array($pkDecoded))
            ? $pkDecoded
            : (trim($pkPhotos) !== '' ? [$pkPhotos] : []);
    } elseif (! is_array($pkPhotos)) {
        $pkPhotos = (array) $pkPhotos;
    }
    $pkPhotos = array_values(array_filter($pkPhotos));
    $pkPhotoCount = count($pkPhotos);

    $pkPhotoUrl = static function ($photo) {
        $path = ltrim(trim((string) $photo), '/');
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }
        if (str_starts_with($path, 'public/')) {
            return storage_url(str_replace('public/', '', $path));
        } else {
            return storage_url($path);
        }
    };

    $pkVisiblePhotos = array_values(array_filter(array_map($pkPhotoUrl, array_slice($pkPhotos, 0, 1))));
    $pkThumb = $pkVisiblePhotos[0] ?? null;

    // --- statuts ---
    $pkIsUrgent  = $ad->is_urgent && (! $ad->urgent_until || $ad->urgent_until->isFuture());
    $pkUrgentLabel = \App\Support\AdDisclosure::urgentLabel($ad);
    $pkIsBoosted = $ad->is_boosted && $ad->boost_end && $ad->boost_end->isFuture();
    // Côté client, un libellé unique pour toute mise en avant payante (boost ou Premium).
    $pkIsSponsored = $pkRole === 'client' && \App\Support\AdDisclosure::isSponsored($ad);
    $pkIsFresh   = $ad->created_at && $ad->created_at->greaterThan(now()->subHours(24)) && ! $pkIsUrgent;
    $pkIsDemande = $ad->service_type === 'demande';

    // --- auteur ---
    $pkAuthor   = $ad->user;
    $pkName     = $pkAuthor?->name ?? 'Utilisateur';
    $pkVerified = (bool) ($pkAuthor?->hasVerifiedProfileBadge() ?? false);
    $pkStatus   = \App\Support\AdDisclosure::advertiserStatus($pkAuthor);

    // --- moment souhaite, dit honnetement (« Cette semaine » plutot qu'une date technique) ---
    $pkWhen = \App\Models\Ad::whenLabelFromDetails(is_array($ad->ad_details ?? null) ? $ad->ad_details : []);

    // --- distance, presente quand le flux est geolocalise ---
    $pkDistance = isset($ad->distance) && $ad->distance !== null
        ? number_format((float) $ad->distance, (float) $ad->distance < 10 ? 1 : 0, ',', ' ')
        : null;

    $pkPlace = $ad->location ?: ($ad->city ?: null);

    // --- nombre de reponses (demandes uniquement) ---
    $pkReplies = $pkIsDemande ? (int) ($ad->service_proposals_count ?? 0) : null;

    $pkIsSaved = $pkSaved->contains((int) $ad->id);
    $pkUrl = route('ads.show', $ad);
    $pkCategoryLabel = $ad->category ?: ($ad->main_category ?: 'Service');
    $pkCategoryIcon = collect($pkSearchIndex ?? [])->firstWhere('label', $pkCategoryLabel)['icon']
        ?? ($pkIsDemande ? 'fas fa-hand-holding-heart' : 'fas fa-briefcase');
@endphp
<article class="pk-ad{{ $pkThumb ? '' : ' pk-ad--nothumb' }}{{ $pkIsUrgent ? ' is-urgent' : '' }}">
    <div class="pk-ad__media">
        <div class="pk-ad__placeholder" aria-hidden="true">
            <i class="{{ $pkCategoryIcon }}"></i>
            <span>Photo non fournie</span>
        </div>
        @if($pkThumb)
            <a class="pk-ad__photo" href="{{ $pkUrl }}" tabindex="-1" aria-hidden="true" data-pk-feed-photo>
                <img src="{{ $pkThumb }}" alt="" loading="lazy" decoding="async">
            </a>
            @if($pkPhotoCount > 1)
                <span class="pk-ad__photo-count"><i class="far fa-images" aria-hidden="true"></i> {{ $pkPhotoCount }} photos</span>
            @endif
        @endif
        <span class="pk-ad__type{{ $pkIsDemande ? ' pk-ad__type--request' : '' }}">{{ $pkIsDemande ? 'Demande de service' : 'Service proposé' }}</span>
    </div>
    <div class="pk-ad__body">
        <div class="pk-ad__top">
            <span class="pk-ad__cat">{{ $pkCategoryLabel }}</span>
            @if($pkUrgentLabel)
                <span class="pk-tag pk-tag--urgent"><i class="fas fa-bolt" aria-hidden="true"></i> {{ $pkUrgentLabel }}</span>
            @elseif($pkIsFresh)
                <span class="pk-tag pk-tag--new">Nouveau</span>
            @endif
            @if($pkIsSponsored)
                <span class="pk-tag pk-tag--sponsored">{{ \App\Support\AdDisclosure::SPONSORED_LABEL }}<span class="pk-sr"> : {{ \App\Support\AdDisclosure::SPONSORED_SCREEN_READER }}</span></span>
                <a class="pk-ad__why" href="{{ route('legal.platform-rules') }}#classement" aria-label="Pourquoi ? Règles de classement des offres sponsorisées">Pourquoi ?</a>
            @elseif($pkRole !== 'client' && $pkIsBoosted)
                <span class="pk-tag pk-tag--boost">Boosté</span>
            @endif
        </div>
        <h3><a href="{{ $pkUrl }}">{{ $ad->title }}</a></h3>
        <div class="pk-ad__facts">
            <span class="pk-ad__place">
                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                {{ $pkPlace ?: 'Lieu non précisé' }}
                @if($pkDistance)
                    · {{ $pkDistance }} km
                @endif
                @if($ad->isRemoteIntervention())
                    <span class="pk-tag pk-tag--remote"><i class="fas fa-laptop-house" aria-hidden="true"></i> À distance</span>
                @endif
            </span>
            <strong class="pk-ad__price">{{ $ad->formatted_price }}</strong>
        </div>
        <p class="pk-ad__desc">{{ Str::limit(strip_tags($ad->description ?? ''), 160) }}</p>
        @if($pkIsDemande)
            <div class="pk-ad__request-info">
                @if($pkWhen)<span><i class="far fa-calendar" aria-hidden="true"></i> {{ $pkWhen }}</span>@endif
                <span class="pk-replies">
                    <i class="far fa-comment" aria-hidden="true"></i>
                    @if($pkReplies > 0)
                        {{ $pkReplies }} réponse{{ $pkReplies > 1 ? 's' : '' }}
                    @else
                        {{ $pkRole === 'provider' ? 'Soyez le premier à répondre' : 'En attente de réponses' }}
                    @endif
                </span>
            </div>
        @endif
        <div class="pk-ad__authorbar">
            @if($pkAuthor)
                <a class="pk-ad__author" href="{{ route('profile.public', $pkAuthor->id) }}" aria-label="Voir le profil de {{ $pkName }}" title="{{ $pkName }}">
            @else
                <span class="pk-ad__author">
            @endif
                <span class="av">
                    @if($pkAuthor?->avatar)
                        <img src="{{ storage_url($pkAuthor->avatar) }}" alt="" loading="lazy" decoding="async">
                    @else
                        {{ Str::upper(Str::substr($pkName, 0, 1)) }}
                    @endif
                </span>
                <span class="nm">{{ $pkName }}</span>
                @if($pkVerified)
                    <span class="pk-verified" title="Identité vérifiée" aria-label="Identité vérifiée"><i class="fas fa-check" aria-hidden="true"></i></span>
                @endif
            @if($pkAuthor)</a>@else</span>@endif
            @if($pkStatus)
                <span class="pk-ad__status">{{ $pkStatus }}</span>
            @endif
            @if($ad->created_at)
                <time class="pk-ad__age" datetime="{{ $ad->created_at->toIso8601String() }}">{{ $ad->created_at->diffForHumans() }}</time>
            @endif
        </div>
        <div class="pk-ad__foot">
            <a href="{{ $pkUrl }}" class="pk-btn-sm">
                {{ $pkIsDemande ? ($pkRole === 'provider' ? 'Proposer mes services' : 'Voir la demande') : "Voir l'offre" }}
                <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
            @auth
                <button type="button" class="pk-save" data-pk-save="{{ $ad->id }}"
                        aria-pressed="{{ $pkIsSaved ? 'true' : 'false' }}"
                        aria-label="{{ $pkIsSaved ? 'Retirer des favoris' : 'Enregistrer dans les favoris' }}">
                    <i class="{{ $pkIsSaved ? 'fas' : 'far' }} fa-bookmark" aria-hidden="true"></i>
                </button>
            @endauth
        </div>
    </div>
</article>

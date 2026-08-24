{{--
    Carte d'annonce — modele unique du feed.

    Variables attendues :
      $ad       Ad
      $pkRole   'client' | 'provider'
      $pkSaved  Collection d'identifiants d'annonces deja enregistrees

    Regles d'affichage :
      · la vignette n'apparait que si l'annonce a une photo (pas de placeholder)
      · la fraicheur est toujours relative (« il y a 2 h »), jamais une date
      · le nombre de reponses n'est affiche que pour les demandes
--}}
@php
    $pkRole  = $pkRole  ?? 'client';
    $pkSaved = collect($pkSaved ?? []);

    // --- photo : la premiere du tableau, quelle que soit la forme stockee ---
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

    $pkThumb = null;
    if ($pkPhotoCount > 0) {
        $pkFirst = ltrim(trim((string) $pkPhotos[0]), '/');
        if (str_starts_with($pkFirst, 'http://') || str_starts_with($pkFirst, 'https://')) {
            $pkThumb = $pkFirst;
        } elseif (str_starts_with($pkFirst, 'storage/')) {
            $pkThumb = asset($pkFirst);
        } elseif (str_starts_with($pkFirst, 'public/')) {
            $pkThumb = storage_url(str_replace('public/', '', $pkFirst));
        } else {
            $pkThumb = storage_url($pkFirst);
        }
    }

    // --- statuts ---
    $pkIsUrgent  = $ad->is_urgent && (! $ad->urgent_until || $ad->urgent_until->isFuture());
    $pkIsBoosted = $ad->is_boosted && $ad->boost_end && $ad->boost_end->isFuture();
    $pkIsFresh   = $ad->created_at && $ad->created_at->greaterThan(now()->subHours(24)) && ! $pkIsUrgent;
    $pkIsDemande = $ad->service_type === 'demande';

    // --- auteur ---
    $pkAuthor   = $ad->user;
    $pkName     = $pkAuthor?->name ?? 'Utilisateur';
    $pkVerified = (bool) ($pkAuthor?->hasVerifiedProfileBadge() ?? false);

    // --- creneau souhaite, uniquement s'il a ete renseigne ---
    $pkDetails = is_array($ad->ad_details ?? null) ? $ad->ad_details : [];
    $pkWhen = null;
    if (! empty($pkDetails['desired_date'])) {
        try {
            $pkWhen = \Carbon\Carbon::parse($pkDetails['desired_date'])->translatedFormat('D j M');
        } catch (\Throwable $e) {
            $pkWhen = null;
        }
    }

    // --- distance, presente quand le flux est geolocalise ---
    $pkDistance = isset($ad->distance) && $ad->distance !== null
        ? number_format((float) $ad->distance, (float) $ad->distance < 10 ? 1 : 0, ',', ' ')
        : null;

    $pkPlace = $ad->location ?: ($ad->city ?: null);

    // --- nombre de reponses (demandes uniquement) ---
    $pkReplies = $pkIsDemande ? (int) ($ad->service_proposals_count ?? 0) : null;

    $pkIsSaved = $pkSaved->contains((int) $ad->id);
    $pkUrl = route('ads.show', $ad);
@endphp

<article class="pk-ad{{ $pkThumb ? '' : ' pk-ad--nothumb' }}{{ $pkIsUrgent ? ' is-urgent' : '' }}">

    @if($pkThumb)
        <a class="pk-ad__thumb" href="{{ $pkUrl }}" tabindex="-1" aria-hidden="true">
            <img src="{{ $pkThumb }}" alt="" loading="lazy" decoding="async">
            @if($pkPhotoCount > 1)
                <span class="pk-ad__thumb-count"><i class="fas fa-images"></i> {{ $pkPhotoCount }}</span>
            @endif
        </a>
    @endif

    <div class="pk-ad__body">
        <div class="pk-ad__top">
            <span class="pk-ad__cat">{{ Str::limit($ad->category ?: ($ad->main_category ?: 'Service'), 28) }}</span>
            @if($ad->created_at)
                <span class="pk-ad__sep">·</span>
                <span>{{ $ad->created_at->diffForHumans() }}</span>
            @endif
            @if($pkIsUrgent)
                <span class="pk-tag pk-tag--urgent"><i class="fas fa-bolt"></i> Urgent</span>
            @elseif($pkIsFresh)
                <span class="pk-tag pk-tag--new">Nouveau</span>
            @endif
            @if($pkIsBoosted)
                <span class="pk-tag pk-tag--boost"><i class="fas fa-rocket"></i> Boosté</span>
            @endif
        </div>

        <h3><a href="{{ $pkUrl }}">{{ Str::limit($ad->title, 80) }}</a></h3>

        <div class="pk-ad__facts">
            @if($pkPlace)
                <span>
                    <i class="fas fa-map-marker-alt"></i>
                    {{ Str::limit($pkPlace, 24) }}@if($pkDistance) · <b>{{ $pkDistance }} km</b>@endif
                </span>
            @endif
            @if($pkWhen)
                <span><i class="far fa-calendar"></i> <b>{{ $pkWhen }}</b></span>
            @endif
            <span><i class="fas fa-euro-sign"></i> <b>{{ $ad->formatted_price }}</b></span>
        </div>
    </div>

    <div class="pk-ad__foot">
        <span class="pk-ad__author">
            <span class="av">
                @if($pkAuthor?->avatar)
                    <img src="{{ storage_url($pkAuthor->avatar) }}" alt="" loading="lazy">
                @else
                    {{ Str::upper(Str::substr($pkName, 0, 1)) }}
                @endif
            </span>
            <span class="nm">{{ $pkName }}</span>
            @if($pkVerified)
                <span class="pk-verified" title="Identité vérifiée"><i class="fas fa-check"></i> Vérifié</span>
            @endif
        </span>

        @if($pkIsDemande)
            @if($pkReplies > 0)
                <span class="pk-replies">
                    <i class="far fa-comment"></i> <b>{{ $pkReplies }}</b> réponse{{ $pkReplies > 1 ? 's' : '' }}
                </span>
            @elseif($pkRole === 'provider')
                <span class="pk-replies pk-replies--first">
                    <i class="fas fa-bolt"></i> Aucune réponse — soyez le premier
                </span>
            @else
                <span class="pk-replies">En attente de réponses</span>
            @endif
        @endif

        <span class="pk-ad__cta">
            @auth
                <button type="button"
                        class="pk-save"
                        data-pk-save="{{ $ad->id }}"
                        aria-pressed="{{ $pkIsSaved ? 'true' : 'false' }}"
                        aria-label="{{ $pkIsSaved ? 'Retirer des favoris' : 'Enregistrer dans les favoris' }}">
                    <i class="{{ $pkIsSaved ? 'fas' : 'far' }} fa-bookmark"></i>
                </button>
            @endauth
            <a href="{{ $pkUrl }}" class="pk-btn-sm">
                @if($pkRole === 'provider' && $pkIsDemande)
                    Proposer mes services
                @elseif($pkIsDemande)
                    Voir la demande
                @else
                    Voir l'offre
                @endif
            </a>
        </span>
    </div>
</article>

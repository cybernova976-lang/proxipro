{{--
    Zone 5 · prestataires recommandes.

    Carte d'identite compacte : photo, metier, tarif, avis, localisation et
    competences. Aucun libelle de remplissage n'est invente quand une donnee
    manque. La verification reste visible sous la forme d'un signe discret.
--}}
@php
    $pkProviders = collect($homeProfessionalProfiles ?? [])->take(4);
@endphp

@php
    $pkProviderCategory = $pkProviderCategory ?? null;
    $pkProviderCity = $pkProviderCity ?? null;
    $pkProviderCountry = $pkProviderCountry ?? null;
    $pkDiscovery = $pkProviderDiscovery ?? ['expanded' => false, 'requested_location' => ''];
    $pkDirectoryUrl = route('feed.professionals', array_filter([
        'subcategory' => $pkProviderCategory, 'city' => $pkProviderCity, 'country' => $pkProviderCountry,
    ]));
@endphp
<section id="pkProviderList" class="pk-provider-discovery" aria-labelledby="pkProsTitle">
    @if($pkProviders->isNotEmpty())
    <div class="pk-sechead">
        <div>
            <span class="pk-provider-eyebrow">Des personnes, des savoir-faire</span>
            <h2 id="pkProsTitle">{{ $pkProviderCity ? 'Prestataires à '.$pkProviderCity : 'Prestataires à découvrir' }}</h2>
            <p class="pk-sechead__sub">
                {{ $pkProviderCountry ? $pkProviderCountry.' · ' : '' }}Consultez les profils et échangez avant de choisir.
            </p>
        </div>
        <a href="{{ $pkDirectoryUrl }}" class="pk-sechead__more">
            Voir tous les prestataires <i class="fas fa-arrow-right" aria-hidden="true"></i>
        </a>
    </div>

    @if($pkDiscovery['expanded'])
        <p class="pk-provider-location-note"><i class="fas fa-map-marker-alt" aria-hidden="true"></i>
            <span>Pas encore de profil public à {{ $pkDiscovery['requested_location'] }}.
            Voici des prestataires basés ailleurs : vérifiez leur zone d’intervention avant de les contacter.</span>
        </p>
    @endif

    <div class="pk-pros">
        @foreach($pkProviders as $pkPro)
            @php
                $pkRatingRaw = $pkPro->verified_reviews_avg ?? $pkPro->reviews_avg_rating ?? null;
                $pkReviews = (int) ($pkPro->verified_reviews_count ?? $pkPro->reviews_count ?? 0);
                $pkService = $pkPro->relationLoaded('services') ? $pkPro->services->first() : null;
                // ?: et non ?? : une chaine vide doit basculer sur la suite.
                $pkJob = $pkPro->profession
                    ?: ($pkPro->service_category
                    ?: ($pkService?->subcategory
                    ?: ($pkService?->main_category
                    ?: 'Prestataire de services')));
                $pkCity = $pkPro->city ?: null;
                $pkHourlyRate = $pkPro->hourly_rate && ($pkPro->show_hourly_rate ?? true)
                    ? number_format((float) $pkPro->hourly_rate, 0, ',', ' ')
                    : null;
                $pkRawSpecialties = $pkPro->specialties;
                $pkSpecialties = collect(is_array($pkRawSpecialties)
                    ? $pkRawSpecialties
                    : (is_string($pkRawSpecialties) ? preg_split('/[,;]+/', $pkRawSpecialties) : []))
                    ->concat($pkPro->relationLoaded('services')
                        ? $pkPro->services->flatMap(fn ($service) => [$service->subcategory, $service->main_category])
                        : [])
                    ->filter(fn ($specialty) => is_string($specialty) && trim($specialty) !== '')
                    ->map(fn ($specialty) => trim($specialty))
                    ->reject(fn ($specialty) => mb_strtolower($specialty) === mb_strtolower($pkJob))
                    ->unique()
                    ->take(2);
                $pkIsVerified = $pkPro->hasVerifiedProfileBadge();
            @endphp
            <article class="pk-pro" data-provider-id="{{ $pkPro->id }}">
                <a href="{{ route('profile.public', $pkPro->id) }}"
                   class="pk-pro__identity"
                   aria-label="Voir le profil de {{ $pkPro->name }}">
                    <span class="pk-pro__visual">
                        @if($pkPro->avatar)
                            <img src="{{ storage_url($pkPro->avatar) }}" alt="Photo de {{ $pkPro->name }}" width="80" height="80" loading="lazy">
                        @else
                            <span class="pk-pro__fallback" aria-hidden="true">{{ Str::upper(Str::substr($pkPro->name, 0, 1)) }}</span>
                        @endif
                        @if($pkIsVerified)
                            <span class="pk-pro__verified" title="Identité vérifiée" aria-label="Identité vérifiée">
                                <i class="fas fa-check" aria-hidden="true"></i>
                            </span>
                        @endif
                    </span>
                    <span class="pk-pro__body">
                        <span class="pk-pro__headline">
                            <b>{{ $pkPro->name }}</b>
                        </span>
                        <span class="pk-pro__jobline">
                            <span class="pk-pro__job">{{ $pkJob }}</span>
                            @if($pkHourlyRate)<strong class="pk-pro__price">{{ $pkHourlyRate }} €/h</strong>@endif
                        </span>
                        @if($pkReviews > 0 && $pkRatingRaw)
                            <span class="pk-pro__rate">
                                <i class="fas fa-star" aria-hidden="true"></i>
                                {{ number_format((float) $pkRatingRaw, 1, ',', '') }}
                                <span>({{ $pkReviews }} avis)</span>
                            </span>
                        @endif
                        <span class="pk-pro__meta"><i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                            {{ implode(' · ', array_filter([$pkCity, $pkPro->country])) ?: 'Localisation non renseignée' }}
                        </span>
                        @if($pkSpecialties->isNotEmpty())
                            <span class="pk-pro__tags">
                                @foreach($pkSpecialties as $pkSpecialty)
                                    <span class="pk-pro__tag">{{ Str::limit($pkSpecialty, 26) }}</span>
                                @endforeach
                            </span>
                        @endif
                    </span>
                </a>
                <div class="pk-pro__actions">
                    <a href="{{ route('profile.public', $pkPro->id) }}" class="pk-pro__action pk-pro__action--profile">
                        Voir le profil
                    </a>
                </div>
            </article>
        @endforeach
    </div>
    <div class="pk-provider-bottom">
        <p class="pk-selection-note">Métiers variés · sélection renouvelée chaque jour, sans priorité liée à l’abonnement.</p>
        <a href="{{ route('feed.professionals') }}">Changer de zone ou de métier <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </div>
    @else
        <div class="pk-provider-empty">
            <span class="pk-provider-empty__icon" aria-hidden="true"><i class="far fa-compass"></i></span>
            <div class="pk-provider-empty__copy">
                <h2 id="pkProsTitle">Un prestataire pour votre projet</h2>
                <p>Aucun profil public ne correspond encore à ces critères.</p>
                @if($pkProviderCategory || $pkProviderCity)
                    <span class="pk-provider-empty__criteria">{{ implode(' · ', array_filter([$pkProviderCategory, $pkProviderCity, $pkProviderCountry])) }}</span>
                @endif
            </div>
            <a href="{{ route('feed.professionals') }}">Élargir ma recherche <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </div>
    @endif
</section>

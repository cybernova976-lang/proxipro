{{--
    Zone 5 · prestataires recommandes.

    Carte d'identite compacte : photo, metier, tarif, avis, localisation et
    competences. Aucun libelle de remplissage n'est invente quand une donnee
    manque. La verification reste visible sous la forme d'un signe discret.
--}}
@php
    $pkProviders = collect($homeProfessionalProfiles ?? [])->take(4);
@endphp

@if($pkProviders->isNotEmpty())
<section aria-labelledby="pkProsTitle">
    <div class="pk-sechead">
        <div>
            <h2 id="pkProsTitle">Prestataires recommandés</h2>
            <p class="pk-sechead__sub">Compétences, tarifs et avis utiles pour faire votre choix</p>
        </div>
        <a href="{{ route('ads.index', ['type' => 'offres']) }}" class="pk-sechead__more">
            Voir les profils <i class="fas fa-arrow-right"></i>
        </a>
    </div>

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
            <article class="pk-pro">
                <a href="{{ route('profile.public', $pkPro->id) }}"
                   class="pk-pro__identity"
                   aria-label="Voir le profil de {{ $pkPro->name }}">
                    <span class="pk-pro__visual">
                        @if($pkPro->avatar)
                            <img src="{{ storage_url($pkPro->avatar) }}" alt="Photo de {{ $pkPro->name }}" loading="lazy">
                        @else
                            <span class="pk-pro__fallback" aria-hidden="true">{{ Str::upper(Str::substr($pkPro->name, 0, 1)) }}</span>
                        @endif
                    </span>
                    <span class="pk-pro__body">
                        @if($pkIsVerified)
                            <span class="pk-pro__verified" title="Identité vérifiée" aria-label="Identité vérifiée">
                                <i class="fas fa-check" aria-hidden="true"></i>
                            </span>
                        @endif
                        <span class="pk-pro__headline">
                            <b>{{ Str::limit($pkPro->name, 26) }}</b>
                        </span>
                        <span class="pk-pro__jobline">
                            <span class="pk-pro__job">{{ Str::limit($pkJob, 38) }}</span>
                            @if($pkHourlyRate)<strong class="pk-pro__price">{{ $pkHourlyRate }} €/h</strong>@endif
                        </span>
                        @if($pkReviews > 0 && $pkRatingRaw)
                            <span class="pk-pro__rate">
                                <i class="fas fa-star" aria-hidden="true"></i>
                                {{ number_format((float) $pkRatingRaw, 1, ',', '') }}
                                <span>({{ $pkReviews }} avis)</span>
                            </span>
                        @endif
                        @if($pkCity)
                            <span class="pk-pro__meta"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> {{ Str::limit($pkCity, 26) }}</span>
                        @endif
                        @if($pkSpecialties->isNotEmpty())
                            <span class="pk-pro__tags">
                                @foreach($pkSpecialties as $pkSpecialty)
                                    <span class="pk-pro__tag">{{ Str::limit($pkSpecialty, 26) }}</span>
                                @endforeach
                            </span>
                        @endif
                    </span>
                </a>
                <a href="{{ route('profile.public', $pkPro->id) }}" class="pk-pro__go">
                    Voir le profil <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </article>
        @endforeach
    </div>
</section>
@endif

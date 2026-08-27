{{-- Zone 5 · prestataires recommandes — notes issues d'avis verifies uniquement --}}
@php
    $pkProviders = collect($homeProfessionalProfiles ?? [])->take(4);
@endphp

@if($pkProviders->isNotEmpty())
<section aria-labelledby="pkProsTitle">
    <div class="pk-sechead">
        <div>
            <h2 id="pkProsTitle">Prestataires recommandés</h2>
            <p class="pk-sechead__sub">Notes calculées sur les avis vérifiés uniquement</p>
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
                $pkJob = $pkPro->profession
                    ?? $pkPro->service_category
                    ?? $pkService?->subcategory
                    ?? $pkService?->main_category
                    ?? 'Prestataire de services';
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
                $pkIsTopProvider = (bool) ($pkPro->is_top_provider ?? false);
                $pkIsVerified = $pkPro->hasVerifiedProfileBadge();
            @endphp
            <a href="{{ route('profile.public', $pkPro->id) }}" class="pk-pro">
                <span class="pk-pro__visual">
                    @if($pkPro->avatar)
                        <img src="{{ storage_url($pkPro->avatar) }}" alt="Photo de {{ $pkPro->name }}" loading="lazy">
                    @else
                        <span class="pk-pro__fallback" aria-hidden="true">{{ Str::upper(Str::substr($pkPro->name, 0, 1)) }}</span>
                    @endif
                    @if($pkIsTopProvider)
                        <span class="pk-pro__badge"><i class="fas fa-award"></i> Recommandé</span>
                    @elseif($pkIsVerified)
                        <span class="pk-pro__badge is-verified"><i class="fas fa-shield-alt"></i> Profil vérifié</span>
                    @endif
                </span>
                <span class="pk-pro__body">
                    <span class="pk-pro__headline">
                        <b>{{ Str::limit($pkPro->name, 24) }}</b>
                        @if($pkHourlyRate)<strong class="pk-pro__price">{{ $pkHourlyRate }} €/h</strong>@endif
                    </span>
                    <span class="pk-pro__job">{{ Str::limit($pkJob, 34) }}</span>
                    <span class="pk-pro__rate">
                        <i class="fas fa-star"></i>
                        @if($pkReviews > 0 && $pkRatingRaw)
                            {{ number_format((float) $pkRatingRaw, 1, ',', '') }}
                            <span>({{ $pkReviews }} avis vérifiés)</span>
                        @else
                            <span>Nouveau prestataire</span>
                        @endif
                    </span>
                    @if($pkCity)
                        <span class="pk-pro__meta"><i class="fas fa-map-marker-alt"></i> {{ Str::limit($pkCity, 24) }}</span>
                    @endif
                    @if($pkSpecialties->isNotEmpty())
                        <span class="pk-pro__tags">
                            @foreach($pkSpecialties as $pkSpecialty)
                                <span class="pk-pro__tag">{{ Str::limit($pkSpecialty, 24) }}</span>
                            @endforeach
                        </span>
                    @endif
                    <span class="pk-pro__go">Découvrir ce profil <i class="fas fa-arrow-right"></i></span>
                </span>
            </a>
        @endforeach
    </div>
</section>
@endif

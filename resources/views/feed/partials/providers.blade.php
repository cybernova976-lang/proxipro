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
            @endphp
            <a href="{{ route('profile.public', $pkPro->id) }}" class="pk-pro">
                <span class="pk-pro__av">
                    @if($pkPro->avatar)
                        <img src="{{ storage_url($pkPro->avatar) }}" alt="" loading="lazy">
                    @else
                        {{ Str::upper(Str::substr($pkPro->name, 0, 1)) }}
                    @endif
                    @if($pkPro->hasVerifiedProfileBadge())
                        <span class="chk" title="Identité vérifiée"><i class="fas fa-check"></i></span>
                    @endif
                </span>
                <b>{{ Str::limit($pkPro->name, 20) }}</b>
                <span class="pk-pro__job">{{ Str::limit($pkJob, 30) }}</span>
                <span class="pk-pro__rate">
                    <i class="fas fa-star"></i>
                    @if($pkReviews > 0 && $pkRatingRaw)
                        {{ number_format((float) $pkRatingRaw, 1, ',', '') }}
                        <span>· {{ $pkReviews }} avis</span>
                    @else
                        <span>Nouveau profil</span>
                    @endif
                </span>
                @if($pkCity)
                    <span class="pk-pro__meta"><i class="fas fa-map-marker-alt"></i> {{ Str::limit($pkCity, 20) }}</span>
                @endif
                <span class="pk-pro__go">Voir le profil</span>
            </a>
        @endforeach
    </div>
</section>
@endif

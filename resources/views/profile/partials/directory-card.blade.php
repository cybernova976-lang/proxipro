@php
    $trade = $pro->profession ?: ($pro->services->first()?->subcategory ?: $pro->service_category);
    $skills = $pro->services->pluck('subcategory')->filter()->unique()->reject(fn ($skill) => $skill === $trade)->take(2);
    $place = collect([$pro->city, $pro->country])->filter()->implode(', ');
@endphp
<article class="directory-card">
    <div class="directory-card-body">
        <a class="directory-portrait" href="{{ route('profile.public', $pro) }}" aria-label="Voir le profil de {{ $pro->name }}">
            @if($pro->avatar)
                <img src="{{ storage_url($pro->avatar) }}" alt="Photo de {{ $pro->name }}" loading="lazy" width="136" height="160">
            @else
                <span aria-hidden="true">{{ mb_strtoupper(mb_substr($pro->name, 0, 1)) }}</span>
            @endif
        </a>
        <div class="directory-identity">
            <h3><a href="{{ route('profile.public', $pro) }}">{{ $pro->name }}</a></h3>
            @if($pro->hasVerifiedProfileBadge())
                <span class="directory-verified" role="img" aria-label="Identité vérifiée" title="Identité vérifiée : ce badge ne certifie pas les qualifications professionnelles.">✓</span>
            @endif
            @if($trade)<p class="directory-trade">{{ $trade }}</p>@endif
            @if($place)<p class="directory-place">{{ $place }}</p>@endif
            @if($pro->hourly_rate > 0 && ($pro->show_hourly_rate ?? true))
                <p class="directory-rate">{{ number_format((float) $pro->hourly_rate, 0, ',', ' ') }} <span>€/h · tarif déclaré</span></p>
            @endif
            <p class="directory-rating">
                @if($pro->reviews_count > 0)
                    <span aria-hidden="true">★</span> {{ number_format($pro->reviews_avg_rating, 1, ',', ' ') }}/5 · {{ $pro->reviews_count }} avis vérifié{{ $pro->reviews_count > 1 ? 's' : '' }}
                @else
                    Pas encore d’avis vérifié
                @endif
            </p>
            @if($skills->isNotEmpty())<ul class="directory-skills">@foreach($skills as $skill)<li>{{ $skill }}</li>@endforeach</ul>@endif
        </div>
    </div>
    <footer class="directory-card-actions">
        <a class="directory-button" href="{{ route('profile.public', $pro) }}">Voir le profil</a>
        @if(auth()->id() !== $pro->id)
            <a class="directory-button directory-button-primary" href="{{ route('profile.public', ['id' => $pro->id, 'contact' => 1]) }}#profile-contact">Décrire mon besoin</a>
        @else
            <a class="directory-button directory-button-primary" href="{{ route('profile.edit') }}">Modifier mon profil</a>
        @endif
    </footer>
</article>

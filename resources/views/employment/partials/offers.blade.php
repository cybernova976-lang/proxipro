{{-- Offres d'emploi publiées par les membres. Variables : $pkOffers, $pkCanPublishOffer, $pkOffersPlace. --}}
@php
    $pkJobFields = (array) config('ad_publication.domains.employment.fields', []);
    $pkJobContracts = (array) config('categories.marketplace.Emploi.subcategories', []);
    $pkOffersTotal = $pkOffers->total();
@endphp
<section class="pk-employment-offers" aria-labelledby="pkEmploymentOffersTitle">
    @if($pkOffersTotal > 0)
        <h2 id="pkEmploymentOffersTitle" class="pk-employment-offers__title">{{ $pkOffersTotal }} {{ $pkOffersTotal > 1 ? 'offres d’emploi' : 'offre d’emploi' }}{{ $pkOffersPlace }}</h2>
        <ul class="pk-employment-offers__list">
            @foreach($pkOffers as $pkOffer)
                @php
                    $pkJobDetails = (array) $pkOffer->ad_details;
                    $pkJobFacts = array_filter([
                        in_array($pkOffer->category, $pkJobContracts, true) ? $pkOffer->category : null,
                        $pkOffer->city ?: $pkOffer->location,
                        $pkJobFields['work_time']['options'][$pkJobDetails['work_time'] ?? ''] ?? null,
                        $pkJobFields['work_mode']['options'][$pkJobDetails['work_mode'] ?? ''] ?? null,
                    ]);
                @endphp
                <li>
                    <a class="pk-job" href="{{ route('ads.show', $pkOffer) }}">
                        <span class="pk-job__title">{{ $pkOffer->title }}</span>
                        @if($pkJobFacts !== [])
                            <span class="pk-job__facts">{{ implode(' · ', $pkJobFacts) }}</span>
                        @endif
                        <span class="pk-job__foot">@if($pkOffer->user)Publiée par {{ $pkOffer->user->name }} · @endif{{ $pkOffer->created_at->format('d/m/Y') }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
        @if($pkOffers->hasPages())
            <nav class="pk-employment-offers__pages" aria-label="Pages des offres d’emploi">
                @if($pkOffers->onFirstPage())<span>Précédent</span>@else<a href="{{ $pkOffers->previousPageUrl() }}" rel="prev">Précédent</a>@endif
                <span>Page {{ $pkOffers->currentPage() }} sur {{ $pkOffers->lastPage() }}</span>
                @if($pkOffers->hasMorePages())<a href="{{ $pkOffers->nextPageUrl() }}" rel="next">Suivant</a>@else<span>Suivant</span>@endif
            </nav>
        @endif
        @if($pkCanPublishOffer)
            <a class="pk-employment-offers__publish" href="{{ route('ads.create') }}">Publier une offre d’emploi <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        @endif
    @else
        <div class="pk-employment-card">
            <h2 id="pkEmploymentOffersTitle">Aucune offre d’emploi{{ $pkOffersPlace }} pour le moment</h2>
            <p>Les offres d’emploi publiées par les membres de Prokejem apparaîtront ici.</p>
            @if($pkCanPublishOffer)
                <a class="pk-employment-card__link" href="{{ route('ads.create') }}">Publier une offre d’emploi</a>
            @endif
        </div>
    @endif
</section>

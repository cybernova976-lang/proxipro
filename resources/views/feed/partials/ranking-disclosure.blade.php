{{-- Critere de classement du flux client (article D111-6 II du code de la consommation). --}}
@if(! empty($pkRankingDisclosure))
    <p class="pk-feed-selection" id="pkRankingDisclosure">
        <i class="fas fa-info-circle" aria-hidden="true"></i>
        <span>
            {{ $pkRankingDisclosure['text'] }}
            {{ $pkRankingDisclosure['details'] }}
            <a href="{{ $pkRankingDisclosure['url'] }}">{{ $pkRankingDisclosure['link_label'] }}</a>
        </span>
    </p>
@endif

{{--
    Zone 6 · emploi du territoire (rail du mode client).
    Des liens seulement : aucune offre reprise, aucun compteur, jamais sponsorisé.
    Territoire inconnu : recherche France Travail générale et page /emploi.
--}}
@php
    $pkEmploymentTerritory = $pkTerritory ?? null;
    $pkEmploymentPageUrl = $pkEmploymentTerritory
        ? route('employment.territory', ['territoire' => $pkEmploymentTerritory->slug])
        : route('employment.index');
@endphp
<section class="pk-rcard pk-employment-rail" aria-labelledby="pkRailEmploymentTitle">
    <h2 id="pkRailEmploymentTitle">Emploi{{ $pkEmploymentTerritory ? ' '.$pkEmploymentTerritory->withPreposition() : '' }}</h2>
    <nav class="pk-shortcuts" aria-label="Liens emploi">
        <a href="{{ \App\Support\FranceTravailLinks::searchUrl($pkEmploymentTerritory) }}" target="_blank" rel="noopener noreferrer">
            <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i>
            <span>{{ \App\Support\FranceTravailLinks::searchLabel($pkEmploymentTerritory) }} (site externe)<small>La recherche et la candidature se poursuivent sur le site France Travail</small></span>
            <span class="visually-hidden">(nouvel onglet)</span>
        </a>
        <a href="{{ \App\Support\ProviderOfferLink::url(auth()->user()) }}">
            <i class="fas fa-screwdriver-wrench" aria-hidden="true"></i>
            <span>Proposer mes services<small>Vous savez faire un métier ?</small></span>
            <i class="fas fa-chevron-right pk-shortcuts__arrow" aria-hidden="true"></i>
        </a>
        <a href="{{ $pkEmploymentPageUrl }}">
            <i class="fas fa-briefcase" aria-hidden="true"></i>
            <span>Toutes les infos emploi</span>
            <i class="fas fa-chevron-right pk-shortcuts__arrow" aria-hidden="true"></i>
        </a>
    </nav>
</section>

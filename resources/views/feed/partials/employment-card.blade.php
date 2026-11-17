{{--
    Zone 6 · emploi du territoire (rail du mode client).
    Des liens seulement : aucun compteur, jamais sponsorisé. Les offres sont
    celles publiées par les membres de Prokejem, sur la page emploi.
    Territoire inconnu : page /emploi, toutes offres confondues.
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
        <a href="{{ $pkEmploymentPageUrl }}">
            <i class="fas fa-briefcase" aria-hidden="true"></i>
            <span>Voir les offres d’emploi<small>Publiées par les membres de Prokejem</small></span>
            <i class="fas fa-chevron-right pk-shortcuts__arrow" aria-hidden="true"></i>
        </a>
        <a href="{{ \App\Support\ProviderOfferLink::url(auth()->user()) }}">
            <i class="fas fa-screwdriver-wrench" aria-hidden="true"></i>
            <span>Proposer mes services<small>Vous savez faire un métier ?</small></span>
            <i class="fas fa-chevron-right pk-shortcuts__arrow" aria-hidden="true"></i>
        </a>
    </nav>
</section>

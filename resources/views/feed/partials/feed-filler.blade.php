{{--
    Carte de complément du flux client : quand la page compte moins de 6 offres,
    la place libre propose de décrire son besoin au lieu de rester vide.
    Avec 4 offres, la carte n'apparaît qu'en 3 colonnes (1 024 px et plus), sur 2 cases.
    Ce n'est jamais une fausse offre : aucun prestataire, aucun prix, aucun chiffre.
--}}
<article class="pk-feed-filler{{ ($pkFillerWide ?? false) ? ' pk-feed-filler--wide' : '' }}" aria-labelledby="pkFeedFillerTitle">
    <img src="{{ asset('img/pk-situations/autre-besoin.svg') }}" alt="" width="44" height="44">
    <h3 id="pkFeedFillerTitle">Vous ne trouvez pas votre service ?</h3>
    <p>Décrivez votre besoin en quelques questions : les prestataires {{ ($pkTerritory ?? null) ? $pkTerritory->withDe() : 'inscrits' }} pourront vous faire une proposition.</p>
    <a class="pk-btn-outline" href="{{ route('demand.create') }}">Décrire mon besoin</a>
</article>

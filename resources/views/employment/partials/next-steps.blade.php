{{-- Deux suites possibles sur Prokejem : proposer ses services ou décrire un besoin. --}}
<section class="pk-employment-next" aria-labelledby="pkEmploymentNextTitle">
    <h2 id="pkEmploymentNextTitle" class="pk-employment-next__title">Et sur Prokejem</h2>
    <ul class="pk-employment-next__list">
        <li>
            <span>Vous savez faire un métier ?</span>
            <a href="{{ \App\Support\ProviderOfferLink::url(auth()->user()) }}">Proposer mes services <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </li>
        <li>
            <span>Besoin d’un service ?</span>
            <a href="{{ route('demand.create') }}">Décrire mon besoin <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </li>
    </ul>
</section>

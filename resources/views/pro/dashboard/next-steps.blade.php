{{-- À faire maintenant : la première étape porte le seul bouton plein de la page. --}}
<section class="pk-dash-next" aria-labelledby="pkNextTitle">
    <h2 id="pkNextTitle" class="pk-dash__title">À faire maintenant</h2>

    @if($nextSteps === [])
        <div class="pk-dash-next__lead pk-dash-next__lead--calm" data-next-step="none">
            <div>
                <p class="pk-dash-next__heading">Rien d’urgent : tout est à jour.</p>
                <p class="pk-dash-next__detail">Aucune commande, aucun message ni aucun document n’attend de réponse de votre part.</p>
            </div>
            <a href="{{ route('pro.opportunities') }}" class="btn btn-pro-primary">Voir les demandes</a>
        </div>
    @else
        @php($pkLead = $nextSteps[0])
        <div class="pk-dash-next__lead" data-next-step="{{ $pkLead['key'] }}">
            <div>
                <p class="pk-dash-next__heading">{{ $pkLead['title'] }}</p>
                <p class="pk-dash-next__detail">{{ $pkLead['detail'] }}</p>
            </div>
            <a href="{{ $pkLead['url'] }}" class="btn btn-pro-primary">{{ $pkLead['cta'] }}</a>
        </div>

        @if(count($nextSteps) > 1)
            <ul class="pk-dash-next__list">
                @foreach(array_slice($nextSteps, 1) as $step)
                    <li data-next-step="{{ $step['key'] }}">
                        <a href="{{ $step['url'] }}">
                            <span>
                                <span class="pk-dash-next__item">{{ $step['title'] }}</span>
                                <span class="pk-dash-next__detail">{{ $step['detail'] }}</span>
                            </span>
                            <span class="pk-dash-next__cta">{{ $step['cta'] }} <i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    @endif
</section>

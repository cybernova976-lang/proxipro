{{--
    Zone 7 · repères du rail client.
    « À faire » n'apparaît que si une ligne est réellement due (données du
    compte préparées par FeedController dans $pkTodo). Les raccourcis donnent
    accès au profil du membre et à la gestion de ses annonces.
--}}
@php
    $pkIdentityLine = match (true) {
        $pkVerificationStatus === 'returned' => ['tone' => 'warn', 'label' => 'Corriger ma vérification d’identité'],
        $pkVerificationStatus === 'rejected' => ['tone' => 'warn', 'label' => 'Refaire ma vérification d’identité'],
        $pkVerificationStatus === 'pending' => ['tone' => 'info', 'label' => 'Vérification d’identité en cours d’examen'],
        ! $pkIsVerified => ['tone' => 'info', 'label' => 'Vérifier mon identité'],
        default => null,
    };
    $pkHasTodo = ! empty($pkTodo) || $pkIdentityLine !== null;
    // Entrée vers l'Espace Pro pour les membres qui ne sont pas encore prestataires.
    // Le fondateur a demandé le 25/11/2026 de n'y laisser que l'invitation « Découvrir
    // l'Espace Pro » : l'offre (devis et factures offerts) est expliquée sur la page d'arrivée.
    $pkShowProPromo = ! \App\Support\ClientSpaceNavigation::proEntry(auth()->user())['is_provider'];
@endphp
<details class="pk-rcard pk-landmarks" open>
    <summary>
        <span><i class="far fa-compass" aria-hidden="true"></i> Repères</span>
        <i class="fas fa-chevron-down pk-landmarks__chevron" aria-hidden="true"></i>
    </summary>

    @if($pkHasTodo)
        <h3 class="pk-landmarks__title">À faire</h3>
        <ul class="pk-landmarks__list">
            @if(! empty($pkTodo['push']))
                <li data-pk-todo-push>
                    <button type="button" class="pk-landmarks__item" data-push-enable>
                        <i class="far fa-bell" aria-hidden="true"></i><span>Activer les notifications</span>
                    </button>
                </li>
            @endif
            @if(! empty($pkTodo['alert']))
                <li data-pk-alert>
                    <template data-pk-alert-done>
                        <span class="pk-landmarks__item pk-landmarks__done"><i class="fas fa-check-circle" aria-hidden="true"></i><span>Alerte activée {{ $pkTerritory->withPreposition() }}</span></span>
                    </template>
                    <form method="POST" action="{{ route('saved-searches.store') }}" class="pk-landmarks__form">
                        @csrf
                        @foreach($pkTerritoryAlert as $pkAlertField => $pkAlertValue)
                            <input type="hidden" name="{{ $pkAlertField }}" value="{{ $pkAlertValue }}">
                        @endforeach
                        <button type="submit" class="pk-landmarks__item">
                            <i class="far fa-bookmark" aria-hidden="true"></i><span>Me prévenir des nouvelles offres {{ $pkTerritory->withPreposition() }}</span>
                        </button>
                    </form>
                </li>
            @endif
            @if(! empty($pkTodo['city']))
                <li><a class="pk-landmarks__item" href="{{ route('profile.edit') }}#city"><i class="fas fa-map-marker-alt" aria-hidden="true"></i><span>Indiquer ma commune</span></a></li>
            @endif
            @if($pkIdentityLine)
                <li><a class="pk-landmarks__item pk-landmarks__tone--{{ $pkIdentityLine['tone'] }}" href="{{ route('verification.index') }}"><i class="fas fa-shield-alt" aria-hidden="true"></i><span>{{ $pkIdentityLine['label'] }}</span></a></li>
            @endif
        </ul>
    @endif

    <ul class="pk-landmarks__list pk-landmarks__list--links">
        <li><a class="pk-landmarks__item" href="{{ route('profile.show') }}"><i class="far fa-user" aria-hidden="true"></i><span>Profil</span></a></li>
        <li><a class="pk-landmarks__item" href="{{ route('ads.myads') }}"><i class="fas fa-bullhorn" aria-hidden="true"></i><span>Mes annonces</span></a></li>
    </ul>

    @if($pkShowProPromo)
        <a class="pk-landmarks__pro" href="{{ route('quote-tool.landing') }}" data-pk-pro-promo>
            <span class="pk-landmarks__pro-icon" aria-hidden="true"><i class="fas fa-file-invoice"></i></span>
            <span class="pk-landmarks__pro-cta">Découvrir l’Espace Pro</span>
            <i class="fas fa-arrow-right pk-landmarks__pro-arrow" aria-hidden="true"></i>
        </a>
    @endif

    <details class="pk-landmarks__how">
        <summary>Comment ça marche ?</summary>
        @include('feed.partials.trust')
        @include('feed.partials.guides')
    </details>
</details>

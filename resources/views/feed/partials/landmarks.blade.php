{{--
    Zone 7 · repères du rail client.
    « À faire » n'apparaît que si une ligne est réellement due (données du
    compte préparées par FeedController dans $pkTodo). Aucun compteur.
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
                <li><a class="pk-landmarks__item" href="{{ route('saved-searches.index') }}"><i class="far fa-bookmark" aria-hidden="true"></i><span>Créer une alerte pour les nouvelles offres</span></a></li>
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
        <li><a class="pk-landmarks__item" href="{{ route('saved-ads.index') }}"><i class="far fa-heart" aria-hidden="true"></i><span>Mes favoris</span></a></li>
        <li><a class="pk-landmarks__item" href="{{ route('legal.platform-rules') }}#classement"><i class="fas fa-sort-amount-down" aria-hidden="true"></i><span>Comment les offres sont classées</span></a></li>
    </ul>

    <details class="pk-landmarks__how">
        <summary>Comment ça marche ?</summary>
        @include('feed.partials.trust')
        @include('feed.partials.guides')
    </details>
</details>

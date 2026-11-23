{{-- « À faire » : seulement ce qui est réellement dû sur le compte. --}}
@if(! empty($todo))
    <section class="pk-board-card pk-board-card--todo" aria-labelledby="pkBoardTodoTitle">
        <h2 id="pkBoardTodoTitle"><i class="fas fa-list-check" aria-hidden="true"></i> À faire</h2>
        <ul class="pk-board-rows">
            @if(! empty($todo['identity']))
                <li>
                    <a class="pk-board-row" href="{{ route('verification.index') }}">
                        <span class="pk-board-row__icon" aria-hidden="true"><i class="fas fa-shield-halved"></i></span>
                        <span class="pk-board-row__text"><strong>{{ $todo['identity'] }}</strong><small>Un profil vérifié rassure les prestataires qui vous répondent.</small></span>
                        <i class="fas fa-chevron-right pk-board-link__arrow" aria-hidden="true"></i>
                    </a>
                </li>
            @endif
            @if(! empty($todo['city']))
                <li>
                    <a class="pk-board-row" href="{{ route('profile.edit') }}#city">
                        <span class="pk-board-row__icon" aria-hidden="true"><i class="fas fa-location-dot"></i></span>
                        <span class="pk-board-row__text"><strong>Indiquer ma commune</strong><small>Pour voir les services proposés près de chez vous.</small></span>
                        <i class="fas fa-chevron-right pk-board-link__arrow" aria-hidden="true"></i>
                    </a>
                </li>
            @endif
        </ul>
    </section>
@endif

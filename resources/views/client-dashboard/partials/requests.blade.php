{{-- Les demandes et missions en cours (trois au plus), avec leur état réel. --}}
<section class="pk-board-card" aria-labelledby="pkBoardRequestsTitle">
    <div class="pk-board-card__head">
        <h2 id="pkBoardRequestsTitle">Demandes en cours <span class="pk-board-count">{{ $requestsTotal }}</span></h2>
        <a class="pk-board-card__more pk-board-in-tabbar" href="{{ route('home') }}">Toutes mes demandes <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </div>

    @if($requests->isNotEmpty())
        <ul class="pk-board-rows">
            @foreach($requests as $item)
                <li>
                    <a class="pk-board-row" href="{{ $item['action_url'] }}">
                        <span class="pk-board-row__icon" aria-hidden="true"><i class="fas {{ $item['kind'] === 'order' ? 'fa-briefcase' : 'fa-clipboard-list' }}"></i></span>
                        <span class="pk-board-row__text">
                            <strong>{{ $item['title'] }}</strong>
                            <small class="pk-board-status pk-board-status--{{ $item['tone'] }}">{{ $item['status'] }}</small>
                        </span>
                        <span class="pk-board-row__action">{{ $item['short_action'] }} <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <p class="pk-board-empty">Aucune demande en cours.</p>
    @endif
</section>

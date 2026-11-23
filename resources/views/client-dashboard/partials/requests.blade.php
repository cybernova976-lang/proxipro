{{-- Les demandes et missions en cours (trois au plus), avec leur état réel. --}}
<section class="pk-board-card" aria-labelledby="pkBoardRequestsTitle">
    <div class="pk-board-card__head">
        <h2 id="pkBoardRequestsTitle"><i class="far fa-clipboard" aria-hidden="true"></i> Mes demandes en cours</h2>
        @if($requestsTotal > 0)
            <a class="pk-board-card__more" href="{{ route('home') }}">Tout voir <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        @endif
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
        <a class="pk-board-btn" href="{{ route('demand.create') }}"><i class="fas fa-plus" aria-hidden="true"></i> Publier une demande</a>
    @endif
</section>

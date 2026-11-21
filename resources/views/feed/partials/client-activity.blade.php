@php($primary = $pkClientActivity['primary'])
@if($primary && $primary['priority'] <= 2)
    <section class="pk-resume" aria-label="Votre prochaine action" data-activity-key="{{ $primary['key'] }}">
        <span class="pk-resume__icon"><i class="fas {{ $primary['kind'] === 'order' ? 'fa-briefcase' : 'fa-comment-dots' }}" aria-hidden="true"></i></span>
        <div class="pk-resume__copy">
            <span class="pk-resume__label">{{ $primary['status'] }}</span>
            <h2>{{ $primary['title'] }}</h2>
        </div>
        <a class="{{ ($pkPrimaryAction['state'] ?? 'action') === 'action' ? 'pk-btn' : 'pk-btn-outline' }}" href="{{ $primary['action_url'] }}">{{ $primary['short_action'] }} <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        <a class="pk-resume__all" href="{{ route('home') }}">Mes demandes <span>({{ $pkClientActivity['total'] }})</span></a>
    </section>
@elseif($pkClientActivity['total'] > 0)
    {{-- Rien à traiter : le membre retrouve quand même ses demandes publiées, nommées une à une (3 au plus). --}}
    <section class="pk-mine" aria-labelledby="pkMineTitle">
        <div class="pk-mine__head">
            <h2 id="pkMineTitle">Mes demandes en cours <span class="pk-activity__count">{{ $pkClientActivity['total'] }}</span></h2>
            <a href="{{ route('home') }}">Voir mes demandes <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        <ul class="pk-activity__list">
            @foreach(collect([$primary])->concat($pkClientActivity['others'])->filter()->take(3) as $item)
                @include('feed.partials.client-activity-row', ['item' => $item])
            @endforeach
        </ul>
    </section>
@endif

<li class="pk-activity__row" data-activity-key="{{ $item['key'] }}">
    <span class="pk-activity__icon" aria-hidden="true"><i class="fas {{ $item['kind'] === 'order' ? 'fa-briefcase' : 'fa-clipboard-list' }}"></i></span>
    <div class="pk-activity__body">
        <h3>{{ $item['title'] }}</h3>
        <span class="pk-activity__status pk-activity__status--{{ $item['tone'] }}">{{ $item['status'] }}</span>
    </div>
    <a class="pk-activity__action" href="{{ $item['action_url'] }}" aria-label="{{ $item['short_action'] }} : {{ $item['title'] }}">
        {{ $item['short_action'] }} <i class="fas fa-arrow-right" aria-hidden="true"></i>
    </a>
</li>

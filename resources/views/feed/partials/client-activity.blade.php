@php($primary = $pkClientActivity['primary'])
@if($primary && $primary['priority'] <= 2)
    <section class="pk-resume" aria-label="Votre prochaine action" data-activity-key="{{ $primary['key'] }}">
        <span class="pk-resume__icon"><i class="fas {{ $primary['kind'] === 'order' ? 'fa-briefcase' : 'fa-comment-dots' }}" aria-hidden="true"></i></span>
        <div class="pk-resume__copy">
            <span class="pk-resume__label">{{ $primary['status'] }}</span>
            <h2>{{ $primary['title'] }}</h2>
        </div>
        <a class="pk-btn" href="{{ $primary['action_url'] }}">{{ $primary['short_action'] }} <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        <a class="pk-resume__all" href="{{ route('home') }}">Mon suivi <span>({{ $pkClientActivity['total'] }})</span></a>
    </section>
@elseif($pkClientActivity['total'] > 0)
    <a class="pk-waiting-summary" href="{{ route('home') }}"><i class="far fa-clock" aria-hidden="true"></i><span>{{ $pkClientActivity['total'] }} demande{{ $pkClientActivity['total'] > 1 ? 's' : '' }} ou mission{{ $pkClientActivity['total'] > 1 ? 's' : '' }} en cours</span><strong>Voir mon suivi <i class="fas fa-arrow-right" aria-hidden="true"></i></strong></a>
@endif

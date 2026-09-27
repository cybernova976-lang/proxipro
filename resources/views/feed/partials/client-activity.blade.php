@php($primary = $pkClientActivity['primary'])
@if($primary)
    <section class="pk-activity" aria-labelledby="pkClientActivityTitle">
        <div class="pk-activity__heading">
            <h2 id="pkClientActivityTitle">Vos demandes et missions en cours <span class="pk-activity__count">{{ $pkClientActivity['total'] }}</span></h2>
        </div>
        <section class="pk-state pk-state--active-request" aria-labelledby="pkStateTitle" data-activity-key="{{ $primary['key'] }}">
            <div class="pk-state__request-head">
                <span class="pk-state__eyebrow"><i class="fas {{ $primary['kind'] === 'order' ? 'fa-briefcase' : 'fa-clipboard-list' }}" aria-hidden="true"></i>
                    {{ $primary['kind'] === 'order' ? 'Votre mission' : 'Votre demande en cours' }} · Priorité
                </span>
                <span class="pk-state__status{{ $primary['kind'] === 'request' && $primary['tone'] === 'action' ? ' pk-state__status--answered' : ($primary['tone'] === 'attention' ? ' pk-state__status--attention' : '') }}">{{ $primary['status'] }}</span>
            </div>
            <h1 id="pkStateTitle">{{ $primary['title'] }}</h1>
            <p>{{ $primary['description'] }}</p>
            <div class="pk-state__actions">
                <a href="{{ $primary['action_url'] }}" class="pk-btn-white">{{ $primary['action_label'] }} <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                <a href="{{ route('demands.tracking') }}" class="pk-state__secondary">
                    {{ $pkClientActivity['request_count'] > 1 ? 'Mes '.$pkClientActivity['request_count'].' demandes en cours' : 'Toutes les étapes' }}
                </a>
            </div>
        </section>
        @if($pkClientActivity['others']->isNotEmpty())
            <div class="pk-activity__other">
                <p class="pk-activity__label">Également en cours</p>
                <ul class="pk-activity__list">
                    @foreach($pkClientActivity['others'] as $item)
                        @include('feed.partials.client-activity-row', ['item' => $item])
                    @endforeach
                </ul>
            </div>
        @endif
        <a class="pk-activity__all" href="{{ route('client-activity.index') }}">Voir toutes mes demandes et missions <span>({{ $pkClientActivity['total'] }})</span> <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </section>
@else
    <section class="pk-state" aria-labelledby="pkStateTitle">
        <span class="pk-state__eyebrow"><i class="fas fa-check-circle" aria-hidden="true"></i> Suivi à jour</span>
        <h1 id="pkStateTitle">Aucune demande ni mission en cours</h1>
        <p>Retrouvez vos demandes précédentes dans le suivi ou publiez un nouveau besoin.</p>
        <div class="pk-state__actions"><a class="pk-btn-white" href="{{ route('demand.create') }}">Publier un besoin</a><a class="pk-state__secondary" href="{{ route('demands.tracking') }}">Voir mon historique</a></div>
    </section>
@endif

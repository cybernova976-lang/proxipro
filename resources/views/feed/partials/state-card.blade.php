@php($pkFirstName = trim(Str::before(trim(Auth::user()->name ?? 'Utilisateur'), ' ')) ?: 'Utilisateur')
@if(($pkRole ?? 'client') === 'provider')
    <section class="pk-discover pk-discover--provider" aria-labelledby="pkStateTitle">
        <span class="pk-discover__eyebrow">Votre activité prestataire</span>
        <h1 id="pkStateTitle">Trouvez votre prochaine mission</h1>
        <p>{{ $pkMatchingCount }} demande{{ $pkMatchingCount > 1 ? 's' : '' }} compatible{{ $pkMatchingCount > 1 ? 's' : '' }} avec vos métiers · {{ $geoCity ?: 'votre zone' }}</p>
        @include('feed.partials.intent-bar')
        <a class="pk-discover__link" href="{{ route('pro.opportunities') }}">Suivre mes propositions et missions <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </section>
@else
    <section class="pk-discover" aria-labelledby="pkStateTitle">
        <span class="pk-discover__eyebrow"><span class="pk-discover__hello" aria-hidden="true"><i class="far fa-sun"></i></span> Bonjour {{ $pkFirstName }}</span>
        <h1 id="pkStateTitle">De quoi <span class="pk-discover__keep">avez-vous</span> besoin&nbsp;?</h1>
        <p>Trouvez un prestataire près de chez vous ou décrivez votre besoin gratuitement.</p>
        @include('feed.partials.intent-bar')
        @if(!empty($pkQuickCategories))
            <div class="pk-quickcats">
                @foreach($pkQuickCategories as $pkCatName => $pkCatData)
                    <button type="button" class="pk-quickcat" data-pk-category="{{ $pkCatName }}">
                        <span class="pk-quickcat__icon" aria-hidden="true"><i class="{{ $pkCatData['icon'] ?? 'fas fa-tools' }}"></i></span>
                        <b>{{ $pkCatName }}</b>
                    </button>
                @endforeach
            </div>
        @endif
        <div class="pk-discover__alternative">
            <span>Vous préférez recevoir des propositions ?</span>
            <a href="{{ route('demand.create') }}"><i class="fas fa-plus" aria-hidden="true"></i> Publier une demande</a>
        </div>
    </section>
    <div id="pkClientActivity" data-refresh-url="{{ route('client-activity.refresh') }}" data-revision="{{ $pkClientActivity['revision'] }}">
        @include('feed.partials.client-activity')
    </div>
@endif

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
        <span class="pk-discover__eyebrow">Bonjour {{ $pkFirstName }}</span>
        <h1 id="pkStateTitle">De quoi avez-vous besoin&nbsp;?</h1>
        <p>Trouvez un prestataire près de chez vous ou décrivez votre besoin gratuitement.</p>
        @include('feed.partials.intent-bar')
        @if(!empty($pkQuickCategories))
            <div class="pk-quickcats">
                @foreach($pkQuickCategories as $pkCatName => $pkCatData)
                    <button type="button" class="pk-quickcat" data-pk-category="{{ $pkCatName }}"><i class="{{ $pkCatData['icon'] ?? 'fas fa-tools' }}" aria-hidden="true"></i><b>{{ Str::limit($pkCatName, 26) }}</b></button>
                @endforeach
            </div>
        @endif
    </section>
    <div id="pkClientActivity" data-refresh-url="{{ route('client-activity.refresh') }}" data-revision="{{ $pkClientActivity['revision'] }}">
        @include('feed.partials.client-activity')
    </div>
@endif

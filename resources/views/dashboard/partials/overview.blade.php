<div class="pk-dashboard">
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    <header class="pk-dash-head">
        <div><span class="pk-dash-kicker">Votre espace de suivi</span><h1>Mon suivi</h1><p>Vos demandes et vos réservations, de la première réponse à la prestation terminée.</p></div>
        <a class="pk-dash-primary" href="{{ route('demand.create') }}"><i class="fas fa-plus" aria-hidden="true"></i> Publier un besoin</a>
    </header>
    <nav class="pk-dash-tools" aria-label="Vos raccourcis">
        <a href="{{ route('messages.index') }}"><i class="far fa-comments" aria-hidden="true"></i> Mes messages @if($unread = Auth::user()->unreadMessagesCount())<b>{{ $unread }} non lu{{ $unread > 1 ? 's' : '' }}</b>@endif</a>
        @if($canProvide)<a href="{{ route('pro.opportunities') }}"><i class="fas fa-briefcase" aria-hidden="true"></i> Mon activité prestataire</a>@endif
        <a href="{{ route('home') }}#account" onclick="dashboardNav('account'); return false;"><i class="far fa-user" aria-hidden="true"></i> Mon compte</a>
    </nav>
    <nav class="pk-dash-tabs" aria-label="Filtrer mon suivi">
        @foreach(['active' => 'Tout en cours', 'action' => 'À traiter', 'waiting' => 'En attente', 'ongoing' => 'En cours', 'closed' => 'Historique'] as $key => $label)
            <a href="{{ route('home', ['etat' => $key]) }}" @if($activityFilter === $key) aria-current="page" @endif>{{ $label }}<b>{{ $key === 'active' ? $activityCounts['action'] + $activityCounts['waiting'] + $activityCounts['ongoing'] : $activityCounts[$key] }}</b></a>
        @endforeach
    </nav>
    <div class="pk-dash-list">
        @forelse($activityItems as $item)
            <article class="pk-follow-card pk-follow-card--{{ $item['stage'] }}" id="{{ $item['key'] }}">
                <div class="pk-follow-card__top"><span class="pk-follow-kind">{{ $item['kind'] === 'order' ? 'Prestation réservée' : 'Votre demande' }}</span><span class="pk-follow-status">{{ $item['status'] }}</span></div>
                <div class="pk-follow-card__body">
                    <div class="pk-follow-copy"><h2>{{ $item['title'] }}</h2><p>{{ $item['description'] }}</p></div>
                    <div class="pk-follow-card__actions">
                        @if($item['editable'])<a class="pk-follow-edit" href="{{ $item['editable'] }}" aria-label="Modifier {{ $item['title'] }}" title="Modifier la demande"><i class="fas fa-pen" aria-hidden="true"></i></a>@endif
                        <a href="{{ $item['action_url'] }}" class="{{ $item['stage'] === 'action' ? 'pk-dash-primary' : 'pk-dash-link' }}">{{ $item['action_label'] }} <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                    </div>
                </div>
            </article>
        @empty
            <section class="pk-dash-empty">
                <i class="far {{ $activityFilter === 'action' ? 'fa-check-circle' : 'fa-clipboard' }}" aria-hidden="true"></i>
                <h2>{{ $activityFilter === 'action' ? 'Vous êtes à jour' : ($activityFilter === 'closed' ? 'Votre historique apparaîtra ici' : 'Aucun élément dans cette rubrique') }}</h2>
                <p>{{ $activityFilter === 'action' ? 'Aucune proposition ou commande ne demande votre attention pour le moment.' : 'Retrouvez ici vos demandes et vos réservations. Un nouveau besoin ? Découvrez les services près de chez vous.' }}</p>
                <a class="pk-dash-primary" href="{{ route('feed', ['mode' => 'client']) }}">Trouver un service <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
            </section>
        @endforelse
    </div>
    @if($activityItems->hasPages())<div class="mt-4">{{ $activityItems->links('pagination::bootstrap-5') }}</div>@endif
    <footer class="pk-dash-bottom"><span>Une question sur une prestation ?</span><a href="{{ route('contact.index') }}">Contacter l’assistance</a><a href="{{ route('home') }}#my-ads" onclick="dashboardNav('my-ads'); return false;">Gérer mes annonces</a></footer>
</div>

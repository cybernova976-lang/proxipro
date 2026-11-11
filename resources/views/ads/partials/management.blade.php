<link rel="stylesheet" href="{{ asset('css/account-center.css') }}?v={{ filemtime(public_path('css/account-center.css')) }}">
<div class="account-center publication-manager">
    <header class="account-heading"><div><span class="account-eyebrow">Votre activité</span><h1>Mes annonces et brouillons</h1><p>Reprenez une demande, suivez sa publication ou archivez ce qui n’est plus d’actualité.</p></div><a href="{{ route('demand.create') }}" class="btn btn-primary">Publier une demande</a></header>
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
    <div class="account-note"><strong>{{ $publicationCounts['active'] }} / {{ $publicationLimit }} annonces actives</strong> pour votre statut de compte. Archivez une annonce devenue inutile pour libérer une place. <a href="{{ route('ads.create') }}">Publier une offre</a>.</div>
    <nav class="publication-filters" aria-label="État des publications">
        @foreach(['all' => 'Toutes', 'drafts' => 'Brouillon', 'active' => 'Actives', 'pending' => 'En validation', 'closed' => 'Hors ligne'] as $state => $label)
            <a href="{{ route('ads.myads', ['state' => $state]) }}" @if($publicationState === $state) aria-current="page" @endif>{{ $label }} <span>{{ $publicationCounts[$state] }}</span></a>
        @endforeach
    </nav>
    @if($draft && ($publicationState === 'drafts' || ($publicationState === 'all' && $ads->onFirstPage())))
        <article class="publication-row publication-draft">
            <div><span class="badge bg-warning text-dark">Brouillon · non publié</span><h2>{{ is_string($draft->payload['title'] ?? null) && filled($draft->payload['title']) ? $draft->payload['title'] : 'Votre demande en cours de rédaction' }}</h2><p>Étape {{ $draft->current_step }} sur 5 · Dernière modification le {{ $draft->last_activity_at?->format('d/m/Y à H:i') }}.</p><p class="small">Le texte est conservé sur votre compte. Les photos doivent être ajoutées sur l’appareil utilisé pour publier.</p></div>
            <div class="publication-actions"><a class="btn btn-primary" href="{{ route('demand.create', ['resume' => 1]) }}">Reprendre ma demande</a><form action="{{ route('demand.draft.destroy') }}" method="POST" onsubmit="return confirm('Abandonner ce brouillon ? Il ne sera pas publié.');">@csrf @method('DELETE')<button class="btn btn-outline-secondary">Abandonner</button></form></div>
        </article>
    @endif
    @foreach($ads as $ad)
        @php
            $expired = $ad->expires_at && $ad->expires_at->lte(now());
            $categoryVisible = \App\Support\MarketplaceCategoryRegistry::isVisible($ad->main_category, $ad->category);
            $active = $ad->status === 'active' && ! $expired && $categoryVisible;
            $label = match (true) {
                $active => 'Active',
                $ad->status === 'pending' => 'En validation',
                $expired => 'Expirée',
                $ad->status === 'active' && ! $categoryVisible => 'Catégorie suspendue',
                default => 'Archivée / terminée',
            };
        @endphp
        <article class="publication-row">
            <div><span class="badge {{ $active ? 'bg-success' : ($ad->status === 'pending' ? 'bg-warning text-dark' : 'bg-secondary') }}">{{ $label }}</span><h2><a href="{{ route('ads.show', $ad) }}">{{ $ad->title }}</a></h2><p>{{ $ad->service_type === 'demande' ? 'Demande' : 'Offre' }} · {{ $ad->category }} · {{ $ad->views ?? 0 }} vues cumulées</p>@if($active && $ad->expires_at)<p class="small">Publication jusqu’au {{ $ad->expires_at->format('d/m/Y') }}.</p>@endif</div>
            <div class="publication-actions"><a class="btn btn-outline-primary" href="{{ route('ads.edit', $ad) }}">Modifier</a>
                @if($ad->status === 'active' && ! $expired)<form action="{{ route('ads.archive', $ad) }}" method="POST">@csrf @method('PATCH')<button class="btn btn-outline-secondary">Archiver</button></form>
                @elseif($categoryVisible && in_array($ad->status, ['active', 'inactive', 'archived', 'expired', 'completed'], true))<form action="{{ route('ads.republish', $ad) }}" method="POST">@csrf<button class="btn btn-outline-primary">Republier</button></form>@endif
            </div>
        </article>
    @endforeach
    @if($ads->isEmpty() && ! ($draft && in_array($publicationState, ['all', 'drafts'], true)))<div class="account-section"><h2>Aucune publication dans cette rubrique</h2><p>{{ $publicationState === 'drafts' ? 'Votre prochaine demande inachevée apparaîtra ici après sa sauvegarde.' : 'Changez de rubrique ou publiez votre prochain besoin.' }}</p></div>@endif
    <div class="mt-3">{{ $ads->links() }}</div>
    <p class="account-note">Un brouillon reste privé jusqu’à votre confirmation de publication. <a href="{{ route('settings.index') }}#notifications">Régler mes rappels et notifications</a>.</p>
</div>

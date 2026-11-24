<link rel="stylesheet" href="{{ asset('css/account-center.css') }}?v={{ filemtime(public_path('css/account-center.css')) }}">
@php
    $pageTitle = $publicationType === 'offre' ? 'Mes offres' : 'Mes annonces';
    $pageIntro = match ($publicationType) {
        'offre' => 'Vos offres présentent votre activité aux clients. Modifiez-les, mettez-les hors ligne ou supprimez-les ici.',
        'demande' => 'Vos demandes cherchent un prestataire. Modifiez-les, mettez-les hors ligne ou supprimez-les ici.',
        default => $publicationCanOffer
            ? 'Vos offres (votre activité) et vos demandes (ce que vous cherchez). Modifiez, mettez hors ligne ou supprimez chacune ici.'
            : 'Vos demandes publiées et votre brouillon. Modifiez, mettez hors ligne ou supprimez chacune ici.',
    };
    $stateFilters = ['all' => 'Toutes', 'drafts' => 'Brouillon', 'active' => 'En ligne', 'pending' => 'En validation', 'closed' => 'Hors ligne'];
    if ($publicationType === 'offre') {
        unset($stateFilters['drafts']);
    }
    $typeQuery = $publicationType === 'all' ? [] : ['type' => $publicationType];
@endphp
<div class="account-center publication-manager">
    <header class="account-heading"><div><span class="account-eyebrow">Votre activité</span><h1>{{ $pageTitle }}</h1><p>{{ $pageIntro }}</p></div><a href="{{ $publicationPublish['url'] }}" class="btn btn-outline-primary">{{ $publicationPublish['label'] }}</a></header>
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if(session('info'))<div class="alert alert-info" role="status">{{ session('info') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
    @if($publicationCanOffer)
        <nav class="publication-filters publication-types" aria-label="Type d’annonce">
            @foreach(['all' => 'Toutes', 'offre' => 'Offres', 'demande' => 'Demandes'] as $type => $label)
                <a href="{{ route('ads.myads', $type === 'all' ? [] : ['type' => $type]) }}" @if($publicationType === $type) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
    @endif
    <nav class="publication-filters" aria-label="État des annonces">
        @foreach($stateFilters as $state => $label)
            <a href="{{ route('ads.myads', $typeQuery + ['state' => $state]) }}" @if($publicationState === $state) aria-current="page" @endif>{{ $label }} <span>{{ $publicationCounts[$state] }}</span></a>
        @endforeach
    </nav>
    @if($draft && ($publicationState === 'drafts' || ($publicationState === 'all' && $ads->onFirstPage())))
        <article class="publication-row publication-draft">
            <div><span class="badge bg-warning text-dark">Brouillon · non publié</span><h2>{{ is_string($draft->payload['title'] ?? null) && filled($draft->payload['title']) ? $draft->payload['title'] : 'Votre demande en cours de rédaction' }}</h2><p>Demande · étape {{ $draft->current_step }} sur 5 · Dernière modification le {{ $draft->last_activity_at?->format('d/m/Y à H:i') }}.</p><p class="small">Le texte est conservé sur votre compte. Les photos doivent être ajoutées sur l’appareil utilisé pour publier.</p></div>
            <div class="publication-actions"><a class="btn btn-outline-primary" href="{{ route('demand.create', ['resume' => 1]) }}">Reprendre ma demande</a><form action="{{ route('demand.draft.destroy') }}" method="POST" onsubmit="return confirm('Abandonner ce brouillon ? Il ne sera pas publié.');">@csrf @method('DELETE')<button class="btn btn-outline-secondary">Abandonner</button></form></div>
        </article>
    @endif
    @foreach($ads as $ad)
        @php
            $expired = $ad->expires_at && $ad->expires_at->lte(now());
            $categoryVisible = \App\Support\MarketplaceCategoryRegistry::isVisible($ad->main_category, $ad->category);
            $active = $ad->status === 'active' && ! $expired && $categoryVisible;
            $label = match (true) {
                $active => 'En ligne',
                $ad->status === 'pending' => 'En validation',
                $expired => 'Expirée',
                $ad->status === 'active' && ! $categoryVisible => 'Catégorie suspendue',
                default => 'Hors ligne',
            };
            $isDemand = $ad->service_type === 'demande';
        @endphp
        <article class="publication-row">
            <div><span class="badge {{ $active ? 'bg-success' : ($ad->status === 'pending' ? 'bg-warning text-dark' : 'bg-secondary') }}">{{ $label }}</span><h2><a href="{{ route('ads.show', $ad) }}">{{ $ad->title }}</a></h2><p><strong>{{ $isDemand ? 'Demande' : 'Offre' }}</strong> · {{ $ad->category }}@if($adViewsSince = app(\App\Services\AdViewCounter::class)->countedSince($ad)) · {{ (int) $ad->views }} vue{{ (int) $ad->views > 1 ? 's' : '' }} depuis le {{ $adViewsSince->format('d/m/Y') }}@endif</p>@if($active && $ad->expires_at)<p class="small">En ligne jusqu’au {{ $ad->expires_at->format('d/m/Y') }}.</p>@endif</div>
            <div class="publication-actions"><a class="btn btn-outline-primary" href="{{ route('ads.edit', $ad) }}">Modifier</a>
                @if($ad->status === 'active' && ! $expired)<form action="{{ route('ads.archive', $ad) }}" method="POST">@csrf @method('PATCH')<button class="btn btn-outline-secondary">Mettre hors ligne</button></form>
                @elseif($categoryVisible && in_array($ad->status, ['active', 'inactive', 'archived', 'expired', 'completed'], true))<form action="{{ route('ads.republish', $ad) }}" method="POST">@csrf<button class="btn btn-outline-primary">Remettre en ligne</button></form>@endif
                <form action="{{ route('ads.destroy', $ad) }}" method="POST" onsubmit="return confirm('Supprimer définitivement cette annonce ? Cette action est irréversible.');">@csrf @method('DELETE')<button class="btn btn-outline-danger">Supprimer</button></form>
            </div>
        </article>
    @endforeach
    @if($ads->isEmpty() && ! ($draft && in_array($publicationState, ['all', 'drafts'], true)))<div class="account-section"><h2>Aucune annonce dans cette rubrique</h2><p>{{ $publicationState === 'drafts' ? 'Votre prochaine demande inachevée apparaîtra ici après sa sauvegarde.' : 'Changez de rubrique ou publiez votre prochaine annonce.' }}</p></div>@endif
    <div class="mt-3">{{ $ads->links() }}</div>
    <p class="account-note"><strong>{{ $publicationOnline }} / {{ $publicationLimit }} annonces en ligne</strong> pour votre statut de compte. Mettez hors ligne une annonce devenue inutile pour libérer une place.</p>
</div>

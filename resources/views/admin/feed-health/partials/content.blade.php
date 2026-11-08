{{-- Offres probablement mal typées et auteurs d'offres actives. --}}
<section class="card shadow-sm mb-4" aria-labelledby="pkHealthMisfiledTitle">
    <div class="card-body">
        <h2 id="pkHealthMisfiledTitle" class="h5 fw-bold">Offres dont le titre commence par « Cherche » ({{ $pkHealth['misfiled_requests']->count() }})</h2>
        <p class="text-muted small">Probables demandes publiées comme offres : elles apparaissent dans le flux des clients.</p>
        @if($pkHealth['misfiled_requests']->isEmpty())
            <p class="mb-0">Aucune offre active dans ce cas.</p>
        @else
            <ul class="list-unstyled mb-0">
                @foreach($pkHealth['misfiled_requests'] as $pkMisfiled)
                    <li class="py-1">
                        <a href="{{ route('ads.show', $pkMisfiled->id) }}">#{{ $pkMisfiled->id }} {{ \Illuminate\Support\Str::limit($pkMisfiled->title, 80) }}</a>
                        <span class="text-muted small">· {{ $pkMisfiled->user?->name ?? 'auteur inconnu' }} #{{ $pkMisfiled->user_id }} · {{ $pkMisfiled->created_at?->format('d/m/Y') }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>

<section class="card shadow-sm mb-4" aria-labelledby="pkHealthAuthorsTitle">
    <div class="card-body">
        <h2 id="pkHealthAuthorsTitle" class="h5 fw-bold">Auteurs d’offres actives ({{ $pkHealth['offer_authors']->count() }})</h2>
        <p class="text-muted small">
            Pour retirer des pages publiques les comptes internes ou de test, indiquez leurs identifiants dans la variable FEED_HIDDEN_AUTHOR_IDS, séparés par des virgules.
            Comptes masqués actuellement : {{ $pkHealth['hidden_author_ids'] === [] ? 'aucun' : implode(', ', $pkHealth['hidden_author_ids']) }}.
        </p>
        @if($pkHealth['offer_authors']->isEmpty())
            <p class="mb-0">Aucune offre active.</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr><th scope="col">Identifiant</th><th scope="col">Nom</th><th scope="col" class="text-end">Offres actives</th><th scope="col">Masqué</th></tr>
                    </thead>
                    <tbody>
                        @foreach($pkHealth['offer_authors'] as $pkAuthor)
                            <tr>
                                <td>{{ $pkAuthor['id'] }}</td>
                                <td><a href="{{ route('admin.users.show', $pkAuthor['id']) }}">{{ $pkAuthor['name'] ?? 'Compte introuvable' }}</a></td>
                                <td class="text-end">{{ $pkAuthor['offers_count'] }}</td>
                                <td>{{ in_array($pkAuthor['id'], $pkHealth['hidden_author_ids'], true) ? 'oui' : 'non' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>

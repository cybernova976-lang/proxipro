{{-- Boosts en cours et abonnements présents seulement dans users.plan. --}}
<section class="card shadow-sm mb-4" aria-labelledby="pkHealthBoostsTitle">
    <div class="card-body">
        <h2 id="pkHealthBoostsTitle" class="h5 fw-bold">Boosts en cours ({{ $pkHealth['boosts']->count() }})</h2>
        <p class="text-muted small">Le type permet de repérer les boosts vendus avec une ancienne promesse (par exemple « Première position garantie »).</p>
        @if($pkHealth['boosts']->isEmpty())
            <p class="mb-0">Aucun boost en cours.</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Annonce</th>
                            <th scope="col">Auteur</th>
                            <th scope="col">Territoire</th>
                            <th scope="col">Type</th>
                            <th scope="col">Fin du boost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pkHealth['boosts'] as $pkBoost)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.ads.show', $pkBoost->id) }}">#{{ $pkBoost->id }} {{ \Illuminate\Support\Str::limit($pkBoost->title, 60) }}</a>
                                    <span class="text-muted small">· {{ $pkBoost->service_type === 'demande' ? 'demande' : 'offre' }} · {{ $pkBoost->status }}</span>
                                </td>
                                <td>{{ $pkBoost->user?->name ?? '—' }} <span class="text-muted small">#{{ $pkBoost->user_id }}</span></td>
                                <td>{{ $pkBoost->pk_territory?->label ?? 'Inconnu' }}</td>
                                <td>{{ $pkBoost->boost_type ?: '—' }}</td>
                                <td>{{ $pkBoost->boost_end?->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>

<section class="card shadow-sm mb-4" aria-labelledby="pkHealthPlanTitle">
    <div class="card-body">
        <h2 id="pkHealthPlanTitle" class="h5 fw-bold">Formule indiquée seulement dans users.plan ({{ $pkHealth['plan_only_subscribers']->count() }})</h2>
        <p class="text-muted small">Comptes dont la colonne plan indique une formule payante, sans abonnement actif dans pro_subscriptions : le flux ne les traite pas comme Premium.</p>
        @if($pkHealth['plan_only_subscribers']->isEmpty())
            <p class="mb-0">Aucun compte dans ce cas.</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr><th scope="col">Compte</th><th scope="col">Plan</th><th scope="col">Fin indiquée</th></tr>
                    </thead>
                    <tbody>
                        @foreach($pkHealth['plan_only_subscribers'] as $pkPlanUser)
                            <tr>
                                <td><a href="{{ route('admin.users.show', $pkPlanUser->id) }}">#{{ $pkPlanUser->id }} {{ $pkPlanUser->name }}</a></td>
                                <td>{{ $pkPlanUser->plan }}</td>
                                <td>{{ $pkPlanUser->subscription_end ? \Illuminate\Support\Carbon::parse($pkPlanUser->subscription_end)->format('d/m/Y') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>

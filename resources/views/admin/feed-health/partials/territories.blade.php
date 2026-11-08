{{-- Une ligne par territoire rencontré dans les données. --}}
<section class="card shadow-sm mb-4" aria-labelledby="pkHealthTerritoriesTitle">
    <div class="card-body">
        <h2 id="pkHealthTerritoriesTitle" class="h5 fw-bold">Par territoire</h2>
        <p class="text-muted small">
            Offres actives : offres de services visibles dans le flux client. Promues : boost en cours ou abonnement Premium actif de l’auteur.
            Demandes : demandes créées sur la période, quel que soit leur statut actuel.
        </p>
        @if($pkHealth['territories'] === [])
            <p class="mb-0">Aucune offre, aucun prestataire et aucune demande récente.</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Territoire</th>
                            <th scope="col" class="text-end">Offres actives</th>
                            <th scope="col" class="text-end">Dont promues</th>
                            <th scope="col" class="text-end">Prestataires</th>
                            <th scope="col" class="text-end">Demandes 30 j</th>
                            <th scope="col" class="text-end">Demandes 90 j</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pkHealth['territories'] as $pkRow)
                            <tr>
                                <th scope="row">
                                    {{ $pkRow['label'] }}
                                    @if($pkRow['code'] !== null && $pkRow['overseas'])<span class="text-muted small">({{ $pkRow['code'] }})</span>@endif
                                </th>
                                <td class="text-end">{{ $pkRow['offers'] }}</td>
                                <td class="text-end">{{ $pkRow['promoted'] }}</td>
                                <td class="text-end">{{ $pkRow['providers'] }}</td>
                                <td class="text-end">{{ $pkRow['requests_30'] }}</td>
                                <td class="text-end">{{ $pkRow['requests_90'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <p class="small text-muted mt-3 mb-0">
            Comptes sans lieu déclaré qui portent la position par défaut (Mamoudzou) : {{ $pkHealth['default_location_users'] }}.
            Ils sont traités comme territoire inconnu.
        </p>
    </div>
</section>

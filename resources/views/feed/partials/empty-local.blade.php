{{-- Etat vide du flux client limite au territoire : aucune promesse non tenue, aucun compteur. --}}
<div class="pk-empty pk-empty-local" id="pkEmptyLocal">
    <i class="far fa-compass" aria-hidden="true"></i>
    <h3>Aucun prestataire n'a encore publié d'offre {{ $pkTerritory->withPreposition() }}.</h3>
    <p>
        <a href="{{ route('demand.create') }}">Décrivez votre besoin</a> : les prestataires {{ $pkTerritory->withDe() }} qui s'inscrivent le verront.
    </p>
    <div class="pk-empty-local__actions">
        @if($pkEmptyLocal['alert_active'])
            <a class="pk-empty-local__active" href="{{ route('saved-searches.index') }}">
                <i class="fas fa-bell" aria-hidden="true"></i> Alerte active : vous serez prévenu de la première offre
            </a>
        @else
            <form method="POST" action="{{ route('saved-searches.store') }}" class="pk-empty-local__alert">
                @csrf
                @foreach($pkEmptyLocal['alert'] as $pkAlertField => $pkAlertValue)
                    <input type="hidden" name="{{ $pkAlertField }}" value="{{ $pkAlertValue }}">
                @endforeach
                <button type="submit" class="pk-btn-outline">
                    <i class="far fa-bell" aria-hidden="true"></i> Me prévenir
                </button>
            </form>
        @endif
        <a class="pk-empty-local__invite" href="{{ $pkEmptyLocal['invite_url'] }}" target="_blank" rel="noopener">
            <i class="fab fa-whatsapp" aria-hidden="true"></i> Vous connaissez un artisan ? Invitez-le
        </a>
    </div>
    @if($pkEmptyLocal['elsewhere']->isEmpty())
        <a class="pk-empty-local__elsewhere" href="{{ $pkTerritoryLinks['all'] }}">
            Voir les autres territoires <i class="fas fa-arrow-right" aria-hidden="true"></i>
        </a>
    @endif
</div>

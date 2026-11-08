{{-- Etat vide du flux client limite au territoire : aucune promesse non tenue, aucun compteur. --}}
<div class="pk-empty pk-empty-local" id="pkEmptyLocal">
    <i class="far fa-compass" aria-hidden="true"></i>
    <h3>Aucun prestataire n'a encore publié d'offre {{ $pkTerritory->withPreposition() }}.</h3>
    <p>
        <a href="{{ route('demand.create') }}">Décrivez votre besoin</a> : les prestataires {{ $pkTerritory->withDe() }} qui s'inscrivent le verront.
    </p>
    <div class="pk-empty-local__actions">
        <form method="POST" action="{{ route('saved-searches.store') }}" class="pk-empty-local__alert">
            @csrf
            @foreach($pkEmptyLocal['alert'] as $pkAlertField => $pkAlertValue)
                <input type="hidden" name="{{ $pkAlertField }}" value="{{ $pkAlertValue }}">
            @endforeach
            <button type="submit" class="pk-btn-outline">
                <i class="far fa-bell" aria-hidden="true"></i> Me prévenir
            </button>
        </form>
        <a class="pk-empty-local__invite" href="{{ $pkEmptyLocal['invite_url'] }}" target="_blank" rel="noopener">
            <i class="fab fa-whatsapp" aria-hidden="true"></i> Vous connaissez un artisan ? Invitez-le
        </a>
    </div>
    <a class="pk-empty-local__elsewhere" href="{{ $pkTerritoryLinks['all'] }}">
        Voir les autres territoires <i class="fas fa-arrow-right" aria-hidden="true"></i>
    </a>
</div>

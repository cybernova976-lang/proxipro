{{--
    Alertes et notifications, au même endroit : les alertes du compte (chacune
    s'arrête d'un clic), les notifications de l'appareil, puis les réglages.
    La liste des notifications reçues n'est pas ici : elle s'ouvre par la cloche de l'en-tête.
--}}
<section class="pk-board-card" id="alertes" aria-labelledby="pkBoardAlertsTitle">
    <h2 id="pkBoardAlertsTitle"><i class="far fa-bell" aria-hidden="true"></i> Alertes et notifications</h2>

    <h3 class="pk-board-sub">Mes alertes <span class="pk-board-meta">({{ $activeAlertCount }} {{ $activeAlertCount > 1 ? 'actives' : 'active' }})</span></h3>
    @if($savedSearches->isNotEmpty())
        <ul class="pk-board-rows" data-board-alerts>
            @foreach($savedSearches as $alert)
                <li class="pk-board-row pk-board-row--static">
                    <span class="pk-board-row__icon" aria-hidden="true"><i class="far fa-bell{{ $alert->is_active ? '' : '-slash' }}"></i></span>
                    <span class="pk-board-row__text">
                        <strong>{{ $alert->name }}</strong>
                        <small>{{ $alert->is_active ? 'Active' : 'En pause' }} · créée le {{ $alert->created_at->format('d/m/Y') }}</small>
                    </span>
                    <form method="POST" action="{{ route('saved-searches.destroy', $alert) }}"
                          data-pk-confirm="Arrêter l’alerte « {{ $alert->name }} » ? Vous ne serez plus prévenu.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="pk-board-stop" aria-label="Arrêter l’alerte {{ $alert->name }}">
                            <i class="far fa-bell-slash" aria-hidden="true"></i> Arrêter
                        </button>
                    </form>
                </li>
            @endforeach
        </ul>
    @else
        <p class="pk-board-empty">Aucune alerte pour le moment.</p>
    @endif

    <div class="pk-board-actions">
        @if($territoryAlert && $territory)
            <form method="POST" action="{{ route('saved-searches.store') }}">
                @csrf
                @foreach($territoryAlert as $alertField => $alertValue)
                    <input type="hidden" name="{{ $alertField }}" value="{{ $alertValue }}">
                @endforeach
                <button type="submit" class="pk-board-btn"><i class="far fa-bell" aria-hidden="true"></i> Me prévenir des nouvelles offres {{ $territory->withPreposition() }}</button>
            </form>
        @endif
        <a class="pk-board-textlink" href="{{ route('saved-searches.index') }}">Gérer mes alertes</a>
    </div>

    <h3 class="pk-board-sub">Notifications sur cet appareil</h3>
    <div class="pk-board-push" data-push-notification-controls>
        <div>
            <p class="pk-board-push__lead">Nouveaux messages, propositions et réponses, même quand Prokejem est fermé.</p>
            <p class="mb-0 small" data-push-status aria-live="polite">Vérification en cours…</p>
        </div>
        <div class="pk-board-push__buttons">
            <button type="button" class="pk-board-btn" data-push-enable hidden><i class="far fa-bell" aria-hidden="true"></i> Activer</button>
            <button type="button" class="pk-board-stop" data-push-disable hidden><i class="far fa-bell-slash" aria-hidden="true"></i> Désactiver</button>
        </div>
    </div>

    <h3 class="pk-board-sub">E-mails</h3>
    <a class="pk-board-row" href="{{ route('settings.index') }}#notifications">
        <span class="pk-board-row__icon" aria-hidden="true"><i class="far fa-envelope"></i></span>
        <span class="pk-board-row__text"><strong>Choisir les e-mails que je reçois</strong><small>Notifications, rappels de brouillon, lettre d’information.</small></span>
        <i class="fas fa-chevron-right pk-board-link__arrow" aria-hidden="true"></i>
    </a>
</section>

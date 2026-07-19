<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 py-3 d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h5 class="mb-1">
                <i class="fas fa-repeat me-2 text-primary"></i>
                Commercialisation des abonnements Pro
            </h5>
            <p class="text-muted small mb-0">Le bouton ouvre ou ferme uniquement les nouvelles souscriptions. Les abonnements déjà payés continuent d’être synchronisés.</p>
        </div>
        @if($proSubscriptionReadiness['enabled'])
            <span class="badge bg-success px-3 py-2"><i class="fas fa-check-circle me-1"></i>Ouvert</span>
        @elseif($proSubscriptionReadiness['requested'])
            <span class="badge bg-danger px-3 py-2"><i class="fas fa-triangle-exclamation me-1"></i>Bloqué par la checklist</span>
        @else
            <span class="badge bg-secondary px-3 py-2"><i class="fas fa-lock me-1"></i>Désactivé</span>
        @endif
    </div>
    <div class="card-body pt-1">
        <div class="row g-2 mb-3">
            @foreach($proSubscriptionReadiness['checks'] as $check)
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded-3 p-2 h-100 d-flex gap-2 align-items-start">
                        <i class="fas {{ $check['ready'] ? 'fa-circle-check text-success' : 'fa-circle-xmark text-danger' }} mt-1"></i>
                        <span>
                            <span class="d-block fw-semibold small">{{ $check['label'] }}</span>
                            <span class="d-block text-muted" style="font-size: .72rem; line-height: 1.3;">{{ $check['help'] }}</span>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 border-top pt-3">
            <div class="small">
                Environnement Stripe :
                <span class="badge {{ $proSubscriptionReadiness['mode'] === 'live' ? 'bg-danger' : 'bg-info text-dark' }}">
                    {{ $proSubscriptionReadiness['mode'] === 'live' ? 'PRODUCTION' : 'TEST' }}
                </span>
                <span class="text-muted ms-2">Webhook : {{ url('/stripe/webhook') }}</span>
            </div>
            <form action="{{ route('admin.settings.pro-subscriptions') }}" method="POST" onsubmit="return confirm('{{ $proSubscriptionReadiness['requested'] ? 'Fermer les nouvelles souscriptions ?' : 'Ouvrir les abonnements Pro avec renouvellement automatique ?' }}');">
                @csrf
                <input type="hidden" name="enabled" value="{{ $proSubscriptionReadiness['requested'] ? 0 : 1 }}">
                <button type="submit" class="btn {{ $proSubscriptionReadiness['requested'] ? 'btn-outline-danger' : 'btn-success' }}" {{ ! $proSubscriptionReadiness['ready'] && ! $proSubscriptionReadiness['requested'] ? 'disabled' : '' }}>
                    <i class="fas {{ $proSubscriptionReadiness['requested'] ? 'fa-toggle-off' : 'fa-toggle-on' }} me-2"></i>
                    {{ $proSubscriptionReadiness['requested'] ? 'Désactiver les souscriptions' : 'Activer les souscriptions' }}
                </button>
            </form>
        </div>
    </div>
</div>

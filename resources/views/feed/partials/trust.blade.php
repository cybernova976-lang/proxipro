{{-- Zone 6 · reassurance et mode d'emploi selon le role. --}}
<section class="pk-card pk-trust" aria-labelledby="pkTrustTitle">
    <div class="pk-trust__head">
        <span class="ico"><i class="fas {{ $pkRole === 'provider' ? 'fa-briefcase' : 'fa-shield-alt' }}"></i></span>
        <div>
            <b id="pkTrustTitle">{{ $pkRole === 'provider' ? 'Décrochez vos missions' : 'Paiement protégé' }}</b>
            <p>{{ $pkRole === 'provider' ? 'Un parcours clair, de la demande au paiement.' : 'Vous gardez le contrôle du début à la fin.' }}</p>
        </div>
    </div>
    <div class="pk-steps">
        <div class="pk-step">
            <span class="n">1</span>
            <div>
                <b>{{ $pkRole === 'provider' ? 'Ciblez les bonnes demandes' : 'Vous choisissez' }}</b>
                <span>{{ $pkRole === 'provider'
                    ? 'Répondez aux besoins compatibles avec votre métier, votre zone et vos disponibilités.'
                    : 'Comparez les propositions reçues et acceptez celle qui vous convient.' }}</span>
            </div>
        </div>
        <div class="pk-step">
            <span class="n">2</span>
            <div>
                <b>{{ $pkRole === 'provider' ? 'Envoyez une proposition claire' : 'Les fonds sont bloqués' }}</b>
                <span>{{ $pkRole === 'provider'
                    ? 'Précisez le prix, le délai, ce qui est inclus et votre prochain créneau disponible.'
                    : 'L’argent est mis de côté : le prestataire ne le reçoit pas encore.' }}</span>
            </div>
        </div>
        <div class="pk-step">
            <span class="n">3</span>
            <div>
                <b>{{ $pkRole === 'provider' ? 'Réalisez et recevez le paiement' : 'Vous validez' }}</b>
                <span>{{ $pkRole === 'provider'
                    ? 'Après la prestation, le client valide la réalisation et les fonds sont libérés.'
                    : 'Le paiement n’est libéré qu’une fois la prestation terminée.' }}</span>
            </div>
        </div>
    </div>
</section>

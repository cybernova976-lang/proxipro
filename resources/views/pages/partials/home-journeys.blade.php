<div class="home-journey-tabs" role="tablist" aria-label="Choisissez votre parcours" hidden>
    <button type="button" role="tab" id="home-client-tab" aria-controls="home-client-panel" aria-selected="true" tabindex="0">Je cherche un service</button>
    <button type="button" role="tab" id="home-provider-tab" aria-controls="home-provider-panel" aria-selected="false" tabindex="-1">Je propose mes services</button>
</div>
<div id="home-client-panel" class="home-journey-panel" data-home-panel>
    <h3 class="home-journey-heading">Je cherche un service</h3>
    <div class="steps-grid">
        <div class="step-card"><div class="step-num">1</div><h4>Décrivez votre besoin</h4><p>Précisez le service, le lieu et les détails utiles. Vous pouvez préparer votre demande avant de vous inscrire.</p></div>
        <div class="step-card"><div class="step-num">2</div><h4>Comparez et échangez</h4><p>Consultez les profils et les propositions reçues. Posez vos questions dans la messagerie.</p></div>
        <div class="step-card"><div class="step-num">3</div><h4>Choisissez librement</h4><p>Convenez du prix, du périmètre et du rendez-vous avec le prestataire qui vous convient.</p></div>
    </div>
    <a class="home-inline-action" href="{{ route('demand.create') }}">Décrire mon besoin <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
</div>
<div id="home-provider-panel" class="home-journey-panel" data-home-panel>
    <h3 class="home-journey-heading">Je propose mes services</h3>
    <div class="steps-grid">
        <div class="step-card"><div class="step-num">1</div><h4>Présentez votre activité</h4><p>Créez votre profil, indiquez votre métier et votre ville, puis ajoutez vos réalisations.</p></div>
        <div class="step-card"><div class="step-num">2</div><h4>Découvrez les demandes</h4><p>Repérez les besoins qui correspondent à vos compétences et échangez avec les clients.</p></div>
        <div class="step-card"><div class="step-num">3</div><h4>Organisez vos prestations</h4><p>Suivez vos échanges. Si vous êtes professionnel, configurez votre espace PRO pour vos devis et factures.</p></div>
    </div>
    <a class="home-inline-action" href="{{ route('register') }}">Créer mon profil prestataire <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
</div>

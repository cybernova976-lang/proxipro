@php
    // Avertissement de la version gratuite (décision du fondateur du 25/11/2026) :
    // sans Premium, le PDF d'un devis, d'une facture ou d'un avoir émis porte le
    // filigrane « Prokejem · version gratuite ». Le membre en est prévenu avant de
    // créer le document, et rappelé près du téléchargement ou de l'envoi.
    // La règle n'est pas ici : App\Services\CommercialDocumentAllowance::carriesFreeWatermark().
    // Tant que l'abonnement n'est pas ouvert, la phrase ne promet pas Premium.
    // Paramètres : $documentLabel (« ce devis », « cette facture », « cet avoir »),
    // $pkDocument (document déjà enregistré ; absent à la création), $pkLine (vrai
    // pour le rappel discret d'une ligne). La vue fournit $user.
    $pkDocument = $pkDocument ?? null;
    $pkLine = $pkLine ?? false;
    // Un brouillon ne porte encore que « BROUILLON » : il portera le filigrane une fois émis.
    $pkNotIssued = $pkDocument === null || $pkDocument->status === 'draft';
    $pkWatermarked = app(\App\Services\CommercialDocumentAllowance::class)
        ->carriesFreeWatermark($user, $pkNotIssued ? null : $pkDocument);
    $pkPremiumOpen = $proSubscriptionsEnabled ?? \App\Support\PlatformFeatures::proSubscriptionsEnabled();
@endphp
@if($pkWatermarked)
<p class="{{ $pkLine ? 'pk-free-watermark pk-free-watermark--line small text-muted mt-2 mb-0' : 'pk-free-watermark' }}" role="note" data-free-watermark-notice>
    @if(! $pkLine) <i class="fas fa-stamp" aria-hidden="true"></i> @endif
    <span>
        Version gratuite : {{ $documentLabel }} {{ $pkNotIssued ? 'portera' : 'porte' }} le filigrane « Prokejem · version gratuite ».
        @if($pkPremiumOpen) Premium le retire. <a href="{{ route('pro.subscription') }}" data-free-watermark-premium>Voir Premium</a> @endif
    </span>
</p>
@endif

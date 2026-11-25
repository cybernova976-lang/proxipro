@php
    // « Bon pour accord » d'un devis en PDF : la réponse donnée en ligne par le
    // client si elle existe, sinon un cadre de signature tant que le devis attend
    // sa réponse. Paramètre : $quote.
    $pkOnlineResponse = $quote->onlineResponseLabel();
    $pkAwaitsSignature = in_array($quote->status, ['draft', 'sent'], true);
@endphp
@if($pkOnlineResponse)
<div class="pk-accord pk-paid">
    <div class="pk-label">{{ $quote->status === 'accepted' ? 'Bon pour accord' : 'Réponse du client' }}</div>
    <div class="pk-accord-text">{{ $pkOnlineResponse }}, depuis le lien personnel envoyé avec ce devis.</div>
</div>
@elseif($pkAwaitsSignature)
<div class="pk-accord pk-sign-box">
    <div class="pk-label">Bon pour accord</div>
    <div class="pk-sign-hint">Date et signature du client, précédées de la mention «&nbsp;Bon pour accord&nbsp;».</div>
</div>
@endif

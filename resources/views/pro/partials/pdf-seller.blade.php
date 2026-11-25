@php
    // Identité de l'émetteur dans l'en-tête d'un PDF : logo (Premium), nom, puis
    // les coordonnées telles qu'elles ont été figées à l'émission. Rien n'est
    // ajouté : une information absente n'est pas affichée.
    // Paramètres : $pkSeller (identité), $pkLogoOwner (membre dont on lit le logo).
    $pkSellerName = $pkSeller['company_name'] ?? $pkSeller['name'] ?? '';
    $pkSellerLines = [];
    if (! empty($pkSeller['name']) && $pkSeller['name'] !== $pkSellerName) {
        $pkSellerLines[] = $pkSeller['name'];
    }
    if (! empty($pkSeller['address'])) {
        $pkSellerLines[] = $pkSeller['address'];
    }
    if (! empty($pkSeller['postal_code']) || ! empty($pkSeller['city'])) {
        $pkSellerLines[] = trim(($pkSeller['postal_code'] ?? '').' '.($pkSeller['city'] ?? ''))
            .(! empty($pkSeller['country']) ? ', '.$pkSeller['country'] : '');
    }
    // Téléphone et e-mail sur une ligne, immatriculation et TVA sur une autre.
    $pkContact = array_filter([
        ! empty($pkSeller['phone']) ? 'Tél : '.$pkSeller['phone'] : null,
        ! empty($pkSeller['email']) ? $pkSeller['email'] : null,
    ]);
    if ($pkContact !== []) {
        $pkSellerLines[] = implode(' · ', $pkContact);
    }
    $pkRegistration = array_filter([
        ! empty($pkSeller['siret']) ? 'SIRET : '.$pkSeller['siret'] : null,
        ! empty($pkSeller['tva_number']) ? 'TVA : '.$pkSeller['tva_number'] : null,
    ]);
    if ($pkRegistration !== []) {
        $pkSellerLines[] = implode(' · ', $pkRegistration);
    }
    $pkDocumentLogo = \App\Support\DocumentLogo::dataUri($pkLogoOwner);
@endphp
@if($pkDocumentLogo)
<img class="pk-logo" src="{{ $pkDocumentLogo }}" alt=""><br>
@endif
<div class="pk-seller-name {{ $pkDocumentLogo ? 'pk-seller-name-with-logo' : '' }}">{{ $pkSellerName }}</div>
<div class="pk-seller-info">
    @foreach($pkSellerLines as $pkSellerLine)
    {{ $pkSellerLine }} @if(! $loop->last)<br>@endif
    @endforeach
</div>

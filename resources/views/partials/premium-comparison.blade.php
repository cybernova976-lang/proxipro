@php
    // Même source que le bloc « comparatif » des e-mails : App\Support\OfferComparison.
    $pkSections = \App\Support\OfferComparison::sections();
    $pkPremiumPrice = \App\Support\OfferComparison::premiumPrice();
    $pkArgument = \App\Support\OfferComparison::argument();
    $pkOff = \App\Support\OfferComparison::NOT_INCLUDED;
@endphp
<link rel="stylesheet" href="{{ asset('css/offer-comparison.css') }}?v={{ filemtime(public_path('css/offer-comparison.css')) }}">
<section class="pk-compare" aria-labelledby="pk-compare-title">
    <h2 class="pk-compare__title" id="pk-compare-title">Gratuit, à la carte ou Premium : ce que vous obtenez</h2>
    <p class="pk-compare__lead">Publier et échanger restent gratuits. Vous payez seulement pour être vu avant les autres, ou pour gagner du temps sur vos devis et vos factures.</p>
    @if($pkArgument)
        <p class="pk-compare__argument"><strong>Le calcul.</strong> {{ $pkArgument }}</p>
    @endif
    <table class="pk-compare__table">
        <thead>
            <tr>
                <th scope="col">Fonctionnalité</th>
                <th scope="col">Gratuit</th>
                <th scope="col">À la carte</th>
                <th scope="col" class="pk-compare__premium">Premium @if($pkPremiumPrice)<span class="pk-compare__price">{{ $pkPremiumPrice }}</span>@endif</th>
            </tr>
        </thead>
        @foreach($pkSections as $pkSection)
            <tbody>
                <tr class="pk-compare__section"><th colspan="4" scope="colgroup">{{ $pkSection['title'] }}</th></tr>
                @foreach($pkSection['rows'] as $pkRow)
                    <tr>
                        <th scope="row">{{ $pkRow['label'] }}</th>
                        <td data-plan="Gratuit" @class(['is-off' => $pkRow['free'] === $pkOff])>{{ $pkRow['free'] }}</td>
                        <td data-plan="À la carte" @class(['is-off' => $pkRow['unit'] === $pkOff])>{{ $pkRow['unit'] }}</td>
                        <td data-plan="Premium" @class(['pk-compare__premium', 'is-off' => $pkRow['premium'] === $pkOff])>{{ $pkRow['premium'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        @endforeach
    </table>
    <ul class="pk-compare__notes">
        @foreach(\App\Support\OfferComparison::notes() as $pkNote)
            <li>{{ $pkNote }}</li>
        @endforeach
    </ul>
</section>

@php
    // Tableau des lignes d'un PDF de devis ou de facture. Sous une désignation :
    // sa remise, et son taux de TVA quand le document en mêle plusieurs
    // (pro.partials.document-line-note). Les quantités s'écrivent à la française.
    // Paramètres : $pkItems, $pkDefaultRate, $pkSeveralRates, $pkFirstColumn.
    $pkQuantity = fn ($value) => is_numeric($value)
        ? rtrim(rtrim(number_format((float) $value, 2, ',', ' '), '0'), ',')
        : (string) $value;
@endphp
<table class="pk-lines">
    <thead>
        <tr>
            <th>{{ $pkFirstColumn }}</th>
            <th class="pk-num" style="width: 9%;">Qté</th>
            <th class="pk-num" style="width: 17%;">Prix unit. HT</th>
            <th class="pk-num" style="width: 16%;">Total HT</th>
        </tr>
    </thead>
    <tbody>
        @foreach((array) $pkItems as $item)
        <tr>
            <td>{{ $item['description'] ?? '' }}@include('pro.partials.document-line-note')</td>
            <td class="pk-num">{{ $pkQuantity($item['quantity'] ?? '') }}</td>
            <td class="pk-num">{{ \App\Support\Money::eur($item['unit_price'] ?? 0) }}</td>
            <td class="pk-num pk-line-total">{{ \App\Support\Money::eur($item['total'] ?? ((float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0))) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

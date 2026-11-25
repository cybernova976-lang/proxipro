@php
    // Bloc des totaux d'un PDF : total HT, TVA par taux, total TTC, puis, sur une
    // facture, les acomptes et avoirs déduits et le net. Le montant à retenir
    // (total TTC, ou net s'il y a des déductions) est dans la bande indigo.
    // Paramètres : $pkSubtotal, $pkVatLines, $pkSeveralRates, $pkTotalLabel,
    // $pkTotal, $pkDeductions (null s'il n'y a rien à déduire).
    $pkDeductions = $pkDeductions ?? null;
@endphp
<table class="pk-totals">
    <tr>
        <td class="l">Total HT</td>
        <td class="a">{{ \App\Support\Money::eur($pkSubtotal) }}</td>
    </tr>
    @foreach($pkVatLines as $pkVat)
    <tr>
        <td class="l"><span>TVA ({{ number_format($pkVat['rate'], 2, ',', ' ') }}%)@if($pkSeveralRates) <span style="white-space: nowrap;">sur {{ \App\Support\Money::eur($pkVat['base']) }}</span> @endif</span></td>
        <td class="a">{{ \App\Support\Money::eur($pkVat['tax']) }}</td>
    </tr>
    @endforeach
    <tr class="{{ $pkDeductions ? 'pk-total-strong' : 'pk-total-grand' }}">
        <td>{{ $pkTotalLabel }}</td>
        <td class="pk-num">{{ \App\Support\Money::eur($pkTotal) }}</td>
    </tr>
    @if($pkDeductions)
    @foreach($pkDeductions['lines'] as $pkLine)
    @php
        // « Acompte déjà facturé (FAC-… du …) » : la référence passe sur sa propre ligne.
        $pkLabel = array_pad(explode(' (', (string) $pkLine['label'], 2), 2, null);
    @endphp
    <tr>
        <td class="l">{{ $pkLabel[0] }} @if($pkLabel[1] !== null)<br><span class="pk-total-detail">({{ $pkLabel[1] }}</span> @endif</td>
        <td class="a">{{ \App\Support\Money::eur($pkLine['amount']) }}</td>
    </tr>
    @endforeach
    <tr class="pk-total-grand">
        <td>{{ $pkDeductions['net_label'] }}</td>
        <td class="pk-num">{{ \App\Support\Money::eur($pkDeductions['net']) }}</td>
    </tr>
    @endif
</table>

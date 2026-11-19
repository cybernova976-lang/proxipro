@php
    // Même source que la page des offres : App\Support\OfferComparison.
    // Les styles sont en ligne parce que les messageries ignorent les feuilles de style.
    $pkSections = \App\Support\OfferComparison::sections();
    $pkPremiumPrice = \App\Support\OfferComparison::premiumPrice();
    $pkArgument = \App\Support\OfferComparison::argument();
    $pkOff = \App\Support\OfferComparison::NOT_INCLUDED;
    $pkCell = 'padding:8px 10px;font-size:13px;line-height:1.45;vertical-align:top;border-top:1px solid #e0e7ff;';
@endphp
@if($pkArgument)
<div style="margin:0 0 18px;padding:14px 16px;border-left:4px solid #7c3aed;background:#eef2ff;color:#312e81;font-size:15px;line-height:1.55;"><strong>Le calcul.</strong> {{ $pkArgument }}</div>
@endif
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 12px;border:1px solid #e0e7ff;border-collapse:separate;border-spacing:0;border-radius:12px;overflow:hidden;">
    <tr>
        <td width="33%" style="padding:10px;font-size:12px;font-weight:700;color:#0f172a;background:#f8fafc;">Gratuit</td>
        <td width="33%" style="padding:10px;font-size:12px;font-weight:700;color:#0f172a;background:#f8fafc;">À la carte</td>
        <td width="34%" style="padding:10px;font-size:12px;font-weight:700;color:#ffffff;background:#4338ca;">Premium @if($pkPremiumPrice)<br><span style="font-weight:400;">{{ $pkPremiumPrice }}</span>@endif</td>
    </tr>
    @foreach($pkSections as $pkSection)
        <tr><td colspan="3" style="padding:10px;font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#6d28d9;background:#f8fafc;border-top:1px solid #e0e7ff;">{{ $pkSection['title'] }}</td></tr>
        @foreach($pkSection['rows'] as $pkRow)
            <tr><td colspan="3" style="padding:10px 10px 2px;font-size:14px;font-weight:700;line-height:1.4;color:#1e293b;border-top:1px solid #e0e7ff;">{{ $pkRow['label'] }}</td></tr>
            <tr>
                <td style="{{ $pkCell }}border-top:0;color:{{ $pkRow['free'] === $pkOff ? '#64748b' : '#334155' }};">{{ $pkRow['free'] }}</td>
                <td style="{{ $pkCell }}border-top:0;color:{{ $pkRow['unit'] === $pkOff ? '#64748b' : '#334155' }};">{{ $pkRow['unit'] }}</td>
                <td style="{{ $pkCell }}border-top:0;background:#eef2ff;color:{{ $pkRow['premium'] === $pkOff ? '#64748b' : '#312e81' }};font-weight:{{ $pkRow['premium'] === $pkOff ? '400' : '700' }};">{{ $pkRow['premium'] }}</td>
            </tr>
        @endforeach
    @endforeach
</table>
<div style="margin:0 0 20px;color:#64748b;font-size:12px;line-height:1.6;">
    @foreach(\App\Support\OfferComparison::notes() as $pkNote)
        {{ $pkNote }}@if(! $loop->last)<br>@endif
    @endforeach
</div>

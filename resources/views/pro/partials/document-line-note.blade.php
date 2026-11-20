@php
    // Sous la description d'une ligne : sa remise, et son taux de TVA quand le
    // document en mêle plusieurs. Style en ligne : ce partial sert aussi aux PDF.
    $pkNotes = [];
    if ((float) ($item['discount_percent'] ?? 0) > 0) {
        $pkNotes[] = 'Remise de '.\App\Support\DocumentTotals::formatRate((float) $item['discount_percent']).' %';
    }
    if ($pkSeveralRates ?? false) {
        $pkNotes[] = 'TVA '.\App\Support\DocumentTotals::formatRate((float) ($item['tax_rate'] ?? $pkDefaultRate)).' %';
    }
@endphp
@if($pkNotes !== [])<br><span style="font-size: .85em; font-weight: 400; color: #64748b;">{{ implode(' · ', $pkNotes) }}</span>@endif

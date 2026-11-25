@php
    // Notes et conditions d'un devis en PDF. Paramètres : $pkNotes, $pkConditions,
    // $pkFirst (vrai quand le bloc ouvre une colonne : pas de marge au-dessus).
    $pkFirst = $pkFirst ?? false;
@endphp
@if($pkNotes)
<div class="pk-section {{ $pkFirst ? 'pk-section-first' : '' }}">
    <div class="pk-section-title">Notes</div>
    <div class="pk-section-text pk-pre">{{ $pkNotes }}</div>
</div>
@endif
@if($pkConditions)
<div class="pk-section {{ $pkFirst && ! $pkNotes ? 'pk-section-first' : '' }}">
    <div class="pk-section-title">Conditions</div>
    <div class="pk-section-text pk-pre">{{ $pkConditions }}</div>
</div>
@endif

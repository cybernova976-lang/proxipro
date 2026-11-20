@php
    // Éditeur de lignes commun aux devis et aux factures (création et modification).
    // $pkLines : lignes à afficher ; $pkRowClass et $pkContainerId reprennent les
    // noms attendus par les styles de chaque écran.
    $pkLines = array_values((array) old('items', $pkLines ?? []));
    $pkAdvanced = collect($pkLines)->contains(fn ($line) => filled($line['discount_percent'] ?? null) || filled($line['tax_rate'] ?? null));
    $pkCount = max(1, count($pkLines));
@endphp
<link rel="stylesheet" href="{{ asset('css/pro-premium.css') }}?v={{ @filemtime(public_path('css/pro-premium.css')) }}">
<div class="pk-lines" data-pk-lines data-next-index="{{ $pkCount }}" data-row-class="{{ $pkRowClass }}">
    <div class="row g-2 mb-2 d-none d-md-flex pk-lines__head">
        <div class="col-md-6">Description</div>
        <div class="col-md-2">Quantité</div>
        <div class="col-md-2">Prix unitaire HT (€)</div>
        <div class="col-md-2 text-end">Total HT</div>
    </div>
    <div id="{{ $pkContainerId }}" data-pk-lines-rows>
        @for($pkIndex = 0; $pkIndex < $pkCount; $pkIndex++)
            @include('pro.partials.document-line', ['pkLine' => $pkLines[$pkIndex] ?? null])
        @endfor
    </div>
    <template data-pk-line-template>
        @include('pro.partials.document-line', ['pkLine' => null, 'pkIndex' => '__INDEX__'])
    </template>
    <div class="pk-lines__actions">
        <button type="button" class="btn btn-light btn-sm pk-lines__add" data-pk-add-line><i class="fas fa-plus me-1" aria-hidden="true"></i> Ajouter une ligne</button>
        <label class="pk-lines__toggle">
            <input type="checkbox" class="form-check-input" data-pk-lines-advanced @checked($pkAdvanced)>
            Remise ou TVA différente sur certaines lignes
        </label>
    </div>
</div>
<script src="{{ asset('js/pro-document-lines.js') }}?v={{ @filemtime(public_path('js/pro-document-lines.js')) }}"></script>

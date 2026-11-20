@php
    // Une ligne de devis ou de facture. $pkLine est null pour une ligne vide.
    $pkDiscount = $pkLine['discount_percent'] ?? '';
    $pkLineRate = $pkLine['tax_rate'] ?? '';
@endphp
<div class="{{ $pkRowClass }} pk-line" data-pk-line data-index="{{ $pkIndex }}">
    <button type="button" class="remove-item" data-pk-remove-line aria-label="Retirer cette ligne"><i class="fas fa-times-circle" aria-hidden="true"></i></button>
    <div class="row g-2">
        <div class="col-md-6">
            <input type="text" name="items[{{ $pkIndex }}][description]" class="form-control pk-line__input" value="{{ $pkLine['description'] ?? '' }}" placeholder="Description de la prestation" required maxlength="500" aria-label="Description">
        </div>
        <div class="col-md-2">
            <input type="number" name="items[{{ $pkIndex }}][quantity]" class="form-control pk-line__input item-qty" value="{{ $pkLine['quantity'] ?? 1 }}" min="0.01" step="0.01" required placeholder="Qté" aria-label="Quantité">
        </div>
        <div class="col-md-2">
            <input type="number" name="items[{{ $pkIndex }}][unit_price]" class="form-control pk-line__input item-price" value="{{ $pkLine['unit_price'] ?? 0 }}" min="0" step="0.01" required placeholder="Prix €" aria-label="Prix unitaire hors taxes">
        </div>
        <div class="col-md-2">
            <div class="form-control-plaintext text-end fw-bold item-total pk-line__total">{{ number_format(\App\Support\DocumentTotals::lineTotal((array) $pkLine), 2, ',', '') }}€</div>
        </div>
    </div>
    <div class="row g-2 pk-line__advanced" data-pk-line-advanced hidden>
        <div class="col-6 col-md-3">
            <label class="pk-line__label">Remise (%)
                <input type="number" name="items[{{ $pkIndex }}][discount_percent]" class="form-control pk-line__input item-discount" value="{{ $pkDiscount }}" min="0" max="100" step="0.01" placeholder="0">
            </label>
        </div>
        <div class="col-6 col-md-3">
            <label class="pk-line__label">TVA (%)
                <input type="number" name="items[{{ $pkIndex }}][tax_rate]" class="form-control pk-line__input item-tax" value="{{ $pkLineRate }}" min="0" max="100" step="0.1" title="Laissez vide pour appliquer le taux du document">
            </label>
        </div>
    </div>
</div>

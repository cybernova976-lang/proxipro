/* Éditeur de lignes des devis et factures de l'espace Pro
   (resources/views/pro/partials/document-lines.blade.php).

   Une seule implémentation pour les quatre écrans : ajout et retrait de lignes,
   remise et TVA par ligne, totaux. Le calcul reprend celui du serveur
   (App\Support\DocumentTotals) : total de ligne remise déduite, puis TVA par
   taux. Le serveur recalcule tout à l'enregistrement ; ce script n'est qu'un
   aperçu. Les fonctions addItem(), removeItem() et recalculate() restent
   disponibles pour les scripts des pages. */
(function () {
  'use strict';

  var root = document.querySelector('[data-pk-lines]');
  if (!root) return;

  var rowsBox = root.querySelector('[data-pk-lines-rows]');
  var template = root.querySelector('[data-pk-line-template]');
  var advanced = root.querySelector('[data-pk-lines-advanced]');
  var nextIndex = parseInt(root.getAttribute('data-next-index'), 10) || 1;

  function round2(value) { return Math.round((value + Number.EPSILON) * 100) / 100; }
  function number(input) { return input ? parseFloat(input.value || 0) || 0 : 0; }
  function euros(value) { return value.toFixed(2).replace('.', ',') + '€'; }
  function setText(id, text) { var el = document.getElementById(id); if (el) el.textContent = text; }
  function rows() { return Array.prototype.slice.call(rowsBox.querySelectorAll('[data-pk-line]')); }

  function showAdvanced() {
    var on = !!(advanced && advanced.checked);
    rows().forEach(function (row) {
      var box = row.querySelector('[data-pk-line-advanced]');
      if (!box) return;
      box.hidden = !on;
      if (!on) {
        // Champs masqués : ils ne doivent plus peser sur le calcul.
        box.querySelectorAll('input').forEach(function (input) { input.value = ''; });
      }
    });
  }

  function refreshRemoveButtons() {
    var all = rows();
    all.forEach(function (row) {
      var button = row.querySelector('[data-pk-remove-line]');
      if (button) button.style.display = all.length > 1 ? 'block' : 'none';
    });
  }

  function recalculate() {
    var rateField = document.getElementById('taxRate');
    var defaultRate = number(rateField);
    var bases = {};
    var subtotal = 0;

    rows().forEach(function (row) {
      var discount = Math.min(100, Math.max(0, number(row.querySelector('.item-discount'))));
      var total = round2(number(row.querySelector('.item-qty')) * number(row.querySelector('.item-price')) * (1 - discount / 100));
      var own = row.querySelector('.item-tax');
      // Le champ vide rappelle le taux qui s'appliquera : celui du document.
      if (own) own.placeholder = String(defaultRate).replace('.', ',');
      var rate = own && own.value !== '' ? number(own) : defaultRate;
      bases[rate] = (bases[rate] || 0) + total;
      subtotal += total;
      var cell = row.querySelector('.item-total');
      if (cell) cell.textContent = euros(total);
    });

    var tax = 0;
    var rates = Object.keys(bases);
    rates.forEach(function (rate) { tax += round2(round2(bases[rate]) * parseFloat(rate) / 100); });
    subtotal = round2(subtotal);
    tax = round2(tax);

    setText('subtotal', euros(subtotal));
    setText('taxAmount', euros(tax));
    setText('totalTTC', euros(round2(subtotal + tax)));
    // Le gabarit ajoute « % » après ce libellé : « TVA (5,5 % + 20%) ».
    var labels = rates.map(parseFloat).sort(function (a, b) { return a - b; })
      .map(function (rate) { return String(rate).replace('.', ','); });
    setText('taxLabel', labels.length ? labels.join(' % + ') : String(defaultRate).replace('.', ','));
  }

  function addItem() {
    var holder = document.createElement('div');
    holder.innerHTML = template.innerHTML.replace(/__INDEX__/g, String(nextIndex));
    var row = holder.querySelector('[data-pk-line]');
    if (!row) return null;
    nextIndex += 1;
    rowsBox.appendChild(row);
    showAdvanced();
    refreshRemoveButtons();
    recalculate();
    return row;
  }

  function removeItem(button) {
    if (rows().length <= 1) return;
    var row = button.closest('[data-pk-line]');
    if (row) row.remove();
    refreshRemoveButtons();
    recalculate();
  }

  root.addEventListener('click', function (event) {
    var remove = event.target.closest('[data-pk-remove-line]');
    if (remove) { removeItem(remove); return; }
    if (event.target.closest('[data-pk-add-line]')) addItem();
  });
  root.addEventListener('input', function (event) {
    if (event.target.closest('[data-pk-line]')) recalculate();
  });
  if (advanced) {
    advanced.addEventListener('change', function () { showAdvanced(); recalculate(); });
  }

  window.addItem = addItem;
  window.removeItem = removeItem;
  window.recalculate = recalculate;
  // Appelé par le script des modèles de devis quand un modèle porte une remise ou un taux.
  window.pkShowLineOptions = function () {
    if (advanced && !advanced.checked) { advanced.checked = true; showAdvanced(); }
  };

  function start() {
    var rateField = document.getElementById('taxRate');
    if (rateField) rateField.addEventListener('input', recalculate);
    showAdvanced();
    refreshRemoveButtons();
    recalculate();
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();

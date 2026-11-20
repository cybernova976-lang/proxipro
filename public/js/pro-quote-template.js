/* Préremplit « Créer un devis » à partir d'un modèle (Premium).
   Les données viennent du bloc JSON #pkQuoteTemplate ; les fonctions addItem()
   et recalculate() sont celles de la page. Rien n'est enregistré avant l'envoi
   du formulaire par le prestataire. */
(function () {
  'use strict';

  var source = document.getElementById('pkQuoteTemplate');
  if (!source) return;

  var template;
  try {
    template = JSON.parse(source.textContent || '{}');
  } catch (error) {
    return;
  }

  function setField(name, value) {
    var field = document.querySelector('[name="' + name + '"]');
    if (field && value !== null && value !== undefined) field.value = value;
  }

  setField('subject', template.subject);
  setField('operation_type', template.operation_type);
  setField('tax_rate', template.tax_rate);
  setField('deposit_percentage', template.deposit_percentage);
  setField('is_free', template.is_free);
  setField('notes', template.notes);
  setField('conditions', template.conditions);

  var items = Array.isArray(template.items) ? template.items : [];
  items.forEach(function (item, index) {
    if (index > 0 && typeof window.addItem === 'function') window.addItem();
    var rows = document.querySelectorAll('#quoteItems .quote-item-row');
    var row = rows[rows.length - 1];
    if (!row) return;
    var description = row.querySelector('input[name$="[description]"]');
    var quantity = row.querySelector('.item-qty');
    var price = row.querySelector('.item-price');
    if (description) description.value = item.description || '';
    if (quantity) quantity.value = item.quantity;
    if (price) price.value = item.unit_price;
    // Remise et TVA propres à la ligne, si le modèle en porte.
    var discount = row.querySelector('.item-discount');
    var lineRate = row.querySelector('.item-tax');
    var hasOptions = (item.discount_percent !== null && item.discount_percent !== undefined)
      || (item.tax_rate !== null && item.tax_rate !== undefined);
    if (hasOptions && typeof window.pkShowLineOptions === 'function') window.pkShowLineOptions();
    if (discount && item.discount_percent !== null && item.discount_percent !== undefined) discount.value = item.discount_percent;
    if (lineRate && item.tax_rate !== null && item.tax_rate !== undefined) lineRate.value = item.tax_rate;
  });

  if (typeof window.recalculate === 'function') window.recalculate();
})();

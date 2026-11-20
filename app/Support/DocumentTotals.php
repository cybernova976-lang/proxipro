<?php

namespace App\Support;

/**
 * Totaux d'un devis ou d'une facture de l'espace Pro.
 *
 * Chaque ligne peut porter une remise (en %) et son propre taux de TVA ; sans
 * taux propre, elle suit celui du document. Le total HT d'une ligne est calculé
 * remise déduite, et la TVA est calculée par taux, sur la somme des lignes de
 * ce taux : c'est ce que la facture doit détailler.
 *
 * Un document sans remise ni taux par ligne donne exactement les mêmes totaux
 * qu'avant : sous-total, puis TVA au taux du document.
 */
class DocumentTotals
{
    /**
     * Normalise les lignes saisies et calcule les totaux.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{items: list<array<string, mixed>>, subtotal: float, tax_amount: float, total: float, breakdown: list<array{rate: float, base: float, tax: float}>}
     */
    public static function compute(array $items, float $defaultRate): array
    {
        $lines = [];
        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $discount = min(100.0, max(0.0, (float) ($item['discount_percent'] ?? 0)));

            $line = [
                'description' => (string) ($item['description'] ?? ''),
                'quantity' => $item['quantity'] ?? 0,
                'unit_price' => $item['unit_price'] ?? 0,
            ];
            if ($discount > 0) {
                $line['discount_percent'] = round($discount, 2);
            }
            // Un taux identique à celui du document n'est pas un taux « propre ».
            $rate = $item['tax_rate'] ?? null;
            if ($rate !== null && $rate !== '' && is_numeric($rate) && round((float) $rate, 2) !== round($defaultRate, 2)) {
                $line['tax_rate'] = round((float) $rate, 2);
            }
            $line['total'] = round($quantity * $unitPrice * (1 - $discount / 100), 2);
            $lines[] = $line;
        }

        $breakdown = self::breakdown($lines, $defaultRate);
        $subtotal = round(array_sum(array_column($lines, 'total')), 2);
        $tax = round(array_sum(array_column($breakdown, 'tax')), 2);

        return [
            'items' => $lines,
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'total' => round($subtotal + $tax, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Base HT et TVA par taux, du plus petit taux au plus grand.
     *
     * @param  array<int, array<string, mixed>>|null  $items
     * @return list<array{rate: float, base: float, tax: float}>
     */
    public static function breakdown(?array $items, float $defaultRate): array
    {
        $bases = [];
        foreach ((array) $items as $item) {
            $rate = isset($item['tax_rate']) && is_numeric($item['tax_rate']) ? (float) $item['tax_rate'] : $defaultRate;
            $key = number_format($rate, 2, '.', '');
            $bases[$key] = ($bases[$key] ?? 0) + self::lineTotal($item);
        }
        ksort($bases, SORT_NUMERIC);

        $groups = [];
        foreach ($bases as $rate => $base) {
            $base = round($base, 2);
            $groups[] = ['rate' => (float) $rate, 'base' => $base, 'tax' => round($base * (float) $rate / 100, 2)];
        }

        return $groups;
    }

    /**
     * Lignes « TVA » à afficher sur un document enregistré. Leur somme est
     * toujours le montant de TVA enregistré : un écart d'arrondi éventuel est
     * porté par la dernière ligne.
     *
     * @return list<array{rate: float, base: float, tax: float}>
     */
    public static function vatLines(?array $items, float $defaultRate, float $storedTax): array
    {
        $groups = self::breakdown($items, $defaultRate);
        if (count($groups) <= 1) {
            return [['rate' => $groups[0]['rate'] ?? $defaultRate, 'base' => $groups[0]['base'] ?? 0.0, 'tax' => round($storedTax, 2)]];
        }

        $last = count($groups) - 1;
        $others = array_sum(array_column(array_slice($groups, 0, $last), 'tax'));
        $groups[$last]['tax'] = round($storedTax - $others, 2);

        return $groups;
    }

    /** Le document mêle-t-il plusieurs taux de TVA ? */
    public static function hasSeveralRates(?array $items, float $defaultRate): bool
    {
        return count(self::breakdown($items, $defaultRate)) > 1;
    }

    /** Une partie du document est-elle sans TVA ? (mention d'exonération exigée sur une facture) */
    public static function hasZeroRate(?array $items, float $defaultRate): bool
    {
        foreach (self::breakdown($items, $defaultRate) as $group) {
            if ($group['rate'] === 0.0 && $group['base'] > 0) {
                return true;
            }
        }

        return (array) $items === [] && $defaultRate === 0.0;
    }

    /** Total HT d'une ligne enregistrée, remise déduite. */
    public static function lineTotal(array $item): float
    {
        if (isset($item['total']) && is_numeric($item['total'])) {
            return (float) $item['total'];
        }

        return round((float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0)
            * (1 - min(100.0, max(0.0, (float) ($item['discount_percent'] ?? 0))) / 100), 2);
    }

    /** « 20 » pour 20.00, « 8,5 » pour 8.50. */
    public static function formatRate(float $rate): string
    {
        return rtrim(rtrim(number_format($rate, 2, ',', ''), '0'), ',');
    }
}

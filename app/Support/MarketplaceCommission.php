<?php

namespace App\Support;

/**
 * Commission de la plateforme sur une commande réglée via Prokejem.
 *
 * Un seul endroit calcule la commission : le taux vient de
 * config/marketplace.php (variable MARKETPLACE_COMMISSION_PERCENT). Le client
 * paie le prix convenu ; la commission est déduite du montant versé au
 * prestataire. Les montants sont figés sur la commande à sa création : un
 * changement de taux ne modifie jamais une commande existante.
 */
final class MarketplaceCommission
{
    /** Taux en pourcentage, borné entre 0 et 100. */
    public static function percent(): float
    {
        return max(0.0, min(100.0, (float) config('marketplace.commission_percent', 10)));
    }

    /** Taux prêt à afficher : « 10 % », « 12,5 % ». */
    public static function label(): string
    {
        return rtrim(rtrim(number_format(self::percent(), 2, ',', ' '), '0'), ',').' %';
    }

    /**
     * Répartition d'un prix : commission de la plateforme et montant versé au prestataire.
     *
     * @return array{amount: float, commission_amount: float, seller_amount: float}
     */
    public static function split(float $amount): array
    {
        $amount = round($amount, 2);
        $commission = round($amount * self::percent() / 100, 2);

        return [
            'amount' => $amount,
            'commission_amount' => $commission,
            'seller_amount' => round($amount - $commission, 2),
        ];
    }

    /** Montant versé au prestataire pour un prix donné, formaté en euros. */
    public static function sellerAmountLabel(float $amount): string
    {
        return number_format(self::split($amount)['seller_amount'], 2, ',', ' ').' €';
    }
}

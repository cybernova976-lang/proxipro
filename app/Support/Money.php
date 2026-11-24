<?php

namespace App\Support;

/**
 * Un seul format de montant dans l'Espace Pro : « 1 234,50 € », une espace
 * avant le symbole, le signe moins typographique pour un montant négatif
 * (un avoir). Les cellules qui l'affichent restent sur une ligne (CSS).
 */
class Money
{
    public static function eur(float|int|string|null $amount, int $decimals = 2): string
    {
        $value = round((float) $amount, $decimals);
        $sign = $value < 0 ? '−' : '';

        return $sign.number_format(abs($value), $decimals, ',', ' ').' €';
    }
}

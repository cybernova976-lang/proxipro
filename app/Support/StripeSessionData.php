<?php

namespace App\Support;

use Illuminate\Support\Arr;

class StripeSessionData
{
    public static function value(object|array $session, string $key, mixed $default = null): mixed
    {
        if (is_array($session)) {
            return Arr::get($session, $key, $default);
        }

        $value = data_get($session, $key);

        return $value === null ? $default : $value;
    }

    /** @return array<string, mixed> */
    public static function metadata(object|array $session): array
    {
        $metadata = self::value($session, 'metadata', []);

        if (is_array($metadata)) {
            return $metadata;
        }

        if (is_object($metadata) && method_exists($metadata, 'toArray')) {
            return $metadata->toArray();
        }

        return is_object($metadata) ? get_object_vars($metadata) : [];
    }

    public static function id(object|array $session): string
    {
        return (string) self::value($session, 'id', '');
    }

    public static function isPaid(object|array $session): bool
    {
        return self::value($session, 'payment_status') === 'paid';
    }

    public static function amountTotal(object|array $session): int
    {
        return (int) self::value($session, 'amount_total', 0);
    }

    public static function currency(object|array $session): string
    {
        return strtolower((string) self::value($session, 'currency', ''));
    }
}

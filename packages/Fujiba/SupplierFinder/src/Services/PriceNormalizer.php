<?php

namespace Fujiba\SupplierFinder\Services;

class PriceNormalizer
{
    /** ¥ per metre from a listed price + unit; null when the unit is unknown. */
    public static function perMetre(?int $price, ?string $unit, ?int $tanMetres = null): ?int
    {
        if (! $price) {
            return null;
        }

        return match (true) {
            $unit === '1m'                                  => $price,
            $unit === 'tan'                                 => $tanMetres ? (int) round($price / $tanMetres) : null,
            (bool) preg_match('/^(\d+)cm$/', (string) $unit, $m) => (int) round($price * 100 / (int) $m[1]),
            default                                         => null,
        };
    }

    /** Estimated wholesale ¥/m for a retail price — always labelled as an estimate in the UI. */
    public static function wholesaleEstimate(int $retailPerMetre, float $factor): int
    {
        return (int) round($retailPerMetre * $factor);
    }
}

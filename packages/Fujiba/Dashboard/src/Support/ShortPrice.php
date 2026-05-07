<?php

namespace Fujiba\Dashboard\Support;

use Illuminate\Contracts\Foundation\Application;

class ShortPrice
{
    public function __construct(private readonly Application $app) {}

    /**
     * Format a JPY amount with locale-aware abbreviation.
     *
     * EN locale (and any non-ja):
     *   |amount| <  10,000     → ¥590        (full via core()->formatPrice)
     *   |amount| <  1,000,000  → ¥X.YK       (one decimal, trailing .0 stripped)
     *   |amount| >= 1,000,000  → ¥X.YM
     *
     * JA locale:
     *   |amount| <  10,000          → ¥590
     *   |amount| <  100,000,000     → ¥X.Y万   (commas inserted >= 1,000)
     *   |amount| >= 100,000,000     → ¥X.Y億   (commas inserted >= 1,000)
     *
     * Boundary promotion: when rounding pushes the displayed value to >= 1000
     * within its bucket (e.g. 999,999 / 1,000 = 999.999 ≈ 1000.0), promote to
     * the next-larger unit so callers never see "¥1000K" or "¥10000万".
     *
     * Negatives: format absolute value, prefix '-'.
     * Zero: falls through to formatPrice(0).
     */
    public function format(float|int $amount): string
    {
        $abs    = abs((float) $amount);
        $sign   = $amount < 0 ? '-' : '';
        $locale = $this->app->getLocale();

        if ($abs < 10000) {
            return $sign === '' ? core()->formatPrice($amount) : $sign.core()->formatPrice($abs);
        }

        if ($locale === 'ja') {
            // Promote to 億 when the rounded 万 figure would reach 10,000 or more,
            // OR when the raw value is already in the 億 range.
            if ($abs >= 100_000_000 || round($abs / 10_000, 1) >= 10_000) {
                return $sign.'¥'.$this->oneDecimalWithCommas($abs / 100_000_000).'億';
            }

            return $sign.'¥'.$this->oneDecimalWithCommas($abs / 10_000).'万';
        }

        // Promote K → M when the rounded K figure would reach 1,000 or more,
        // OR when the raw value is already in the M range.
        if ($abs >= 1_000_000 || round($abs / 1_000, 1) >= 1_000) {
            return $sign.'¥'.$this->oneDecimalWithCommas($abs / 1_000_000).'M';
        }

        return $sign.'¥'.$this->oneDecimalWithCommas($abs / 1_000).'K';
    }

    /**
     * Format a number to one decimal place with thousands separator,
     * stripping a trailing .0 so 1.0 → "1" and 1,000.0 → "1,000".
     */
    private function oneDecimalWithCommas(float $value): string
    {
        $rounded = round($value, 1);

        if ($rounded == (int) $rounded) {
            return number_format((int) $rounded, 0, '.', ',');
        }

        return number_format($rounded, 1, '.', ',');
    }
}

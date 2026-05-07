<?php

namespace Fujiba\Accounting\Services\Tax;

use Illuminate\Support\Facades\Log;

/**
 * 内税方式 (tax-inclusive) consumption-tax calculator.
 *
 * Conventions:
 *   - All amounts are JPY whole yen integers (no fractional 円).
 *   - tax = floor(gross × rate / (100 + rate))    ← Japanese standard rounding (端数切り捨て)
 *   - net = gross − tax
 *
 * mmfood split: orders sometimes mix 軽減8% (food/takeout) and 標準10% (drink/dine-in).
 * splitFoodVsDrink walks the line items and segregates them by the
 * `tax_reduced_eligible` product attribute. If the attribute is missing on
 * Bagisto's catalog (the spec acknowledges this may happen) we fall back to 10%
 * and emit a warning to the log so payroll/tax accountants can audit.
 */
class ConsumptionTaxCalculator
{
    /**
     * @return array{net:int,tax:int,gross:int}
     */
    public function calculate(int $grossIncludingTax, float $rate): array
    {
        if ($grossIncludingTax < 0) {
            throw new \InvalidArgumentException('Gross amount must be non-negative.');
        }
        if ($rate < 0 || $rate > 100) {
            throw new \InvalidArgumentException("Tax rate {$rate} is out of range.");
        }

        if ($grossIncludingTax === 0 || $rate == 0.0) {
            return ['net' => $grossIncludingTax, 'tax' => 0, 'gross' => $grossIncludingTax];
        }

        $tax = (int) floor($grossIncludingTax * $rate / (100 + $rate));
        $net = $grossIncludingTax - $tax;

        return ['net' => $net, 'tax' => $tax, 'gross' => $grossIncludingTax];
    }

    /**
     * Walk an order's items and group totals by reduced-rate eligibility.
     *
     * @param  array<int,array{
     *   product_id?: int|null,
     *   sku?: string|null,
     *   gross?: int|float,
     *   total_incl_tax?: int|float,
     *   is_takeout_food?: bool,
     * }>  $orderItems
     *
     * @return array<int,array{rate:float,gross:int,net:int,tax:int}>  one element per non-zero bucket
     */
    public function splitFoodVsDrink(array $orderItems): array
    {
        $buckets = [
            '8'  => 0,   // 軽減税率 (takeout food)
            '10' => 0,   // 標準税率
        ];

        foreach ($orderItems as $item) {
            $gross = (int) round((float) ($item['gross'] ?? $item['total_incl_tax'] ?? 0));
            if ($gross <= 0) {
                continue;
            }

            $reduced = $this->isReducedRate($item);
            if ($reduced === null) {
                Log::warning('Fujiba\\Accounting: tax_reduced_eligible attribute missing — defaulting to 10%', [
                    'product_id' => $item['product_id'] ?? null,
                    'sku'        => $item['sku'] ?? null,
                ]);
                $reduced = false;
            }

            $bucket = $reduced ? '8' : '10';
            $buckets[$bucket] += $gross;
        }

        $out = [];
        foreach ($buckets as $rateStr => $gross) {
            if ($gross <= 0) {
                continue;
            }
            $rate = (float) $rateStr;
            $calc = $this->calculate($gross, $rate);
            $out[] = ['rate' => $rate] + $calc;
        }
        return $out;
    }

    /**
     * @param  array<string,mixed>  $item
     * @return bool|null  null when the attribute is absent on the product
     */
    protected function isReducedRate(array $item): ?bool
    {
        if (array_key_exists('is_takeout_food', $item)) {
            return (bool) $item['is_takeout_food'];
        }
        if (array_key_exists('tax_reduced_eligible', $item)) {
            return (bool) $item['tax_reduced_eligible'];
        }
        return null;
    }
}

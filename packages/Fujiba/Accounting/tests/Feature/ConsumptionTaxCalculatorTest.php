<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Services\Tax\ConsumptionTaxCalculator;
use Fujiba\Accounting\Tests\TestCase;

class ConsumptionTaxCalculatorTest extends TestCase
{
    public function test_yen_1000_inclusive_10_percent_yields_910_90(): void
    {
        // floor(1000 * 10 / 110) = floor(90.909…) = 90 → net 910 / tax 90
        $c = (new ConsumptionTaxCalculator())->calculate(1000, 10.0);
        $this->assertSame(['net' => 910, 'tax' => 90, 'gross' => 1000], $c);
    }

    public function test_yen_1100_inclusive_10_percent_yields_1000_100(): void
    {
        // The clean spec example: ¥1100 inc 10% should split exactly 1000/100.
        $c = (new ConsumptionTaxCalculator())->calculate(1100, 10.0);
        $this->assertSame(['net' => 1000, 'tax' => 100, 'gross' => 1100], $c);
    }

    public function test_yen_1080_inclusive_8_percent_yields_1000_80(): void
    {
        $c = (new ConsumptionTaxCalculator())->calculate(1080, 8.0);
        $this->assertSame(['net' => 1000, 'tax' => 80, 'gross' => 1080], $c);
    }

    public function test_zero_gross_returns_zero_tax(): void
    {
        $c = (new ConsumptionTaxCalculator())->calculate(0, 10.0);
        $this->assertSame(['net' => 0, 'tax' => 0, 'gross' => 0], $c);
    }

    public function test_one_yen_gross_yields_zero_tax_under_floor(): void
    {
        $c = (new ConsumptionTaxCalculator())->calculate(1, 10.0);
        // floor(1 * 10 / 110) = 0 → all of ¥1 is net
        $this->assertSame(['net' => 1, 'tax' => 0, 'gross' => 1], $c);
    }

    public function test_zero_rate_yields_zero_tax(): void
    {
        $c = (new ConsumptionTaxCalculator())->calculate(1000, 0.0);
        $this->assertSame(['net' => 1000, 'tax' => 0, 'gross' => 1000], $c);
    }

    public function test_negative_gross_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new ConsumptionTaxCalculator())->calculate(-1, 10.0);
    }

    public function test_split_food_vs_drink_segregates_buckets(): void
    {
        $items = [
            ['product_id' => 1, 'gross' => 1080, 'is_takeout_food' => true],   // → 8% bucket
            ['product_id' => 2, 'gross' => 220,  'is_takeout_food' => false],  // → 10% bucket
            ['product_id' => 3, 'gross' => 540,  'is_takeout_food' => true],   // → 8% bucket
        ];
        $out = (new ConsumptionTaxCalculator())->splitFoodVsDrink($items);

        $this->assertCount(2, $out);
        $by = collect($out)->keyBy('rate');
        $this->assertSame(1620, $by[8.0]['gross']);
        $this->assertSame(220,  $by[10.0]['gross']);
    }

    public function test_split_food_vs_drink_defaults_to_10_when_attribute_missing(): void
    {
        $items = [
            ['product_id' => 99, 'gross' => 1100],   // no is_takeout_food / tax_reduced_eligible
        ];
        $out = (new ConsumptionTaxCalculator())->splitFoodVsDrink($items);

        $this->assertCount(1, $out);
        $this->assertSame(10.0, $out[0]['rate']);
        $this->assertSame(1100, $out[0]['gross']);
    }
}

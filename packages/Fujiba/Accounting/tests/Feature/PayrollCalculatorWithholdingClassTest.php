<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Models\Timecard;
use Fujiba\Accounting\Services\Payroll\PayrollCalculator;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * Phase 1A regression: B and C 区分 staff must produce non-zero (and
 * different) withholding tax. Previous Sprint-1 behaviour returned ¥0 for
 * non-A staff which was a tax-compliance bug.
 */
class PayrollCalculatorWithholdingClassTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeStaff(string $class, int $hourly = 1500): Staff
    {
        return Staff::create([
            'staff_code' => 'WC-' . $class . '-' . uniqid(),
            'qr_token'   => 'wc-' . $class . '-' . uniqid(),
            'name_ja'    => 'WH ' . $class . ' テスト',
            'employment_type' => 'part_time',
            'hourly_rate' => $hourly,
            'withholding_class' => $class,
            'enrolled_employment_insurance' => true,
            'is_active'  => true,
        ]);
    }

    protected function seedFullMonth(Staff $s, int $minutesPerDay = 480, int $days = 22): void
    {
        // 22 approved 8h days = ~176 hours/month, all in 2026-04
        for ($i = 1; $i <= $days; $i++) {
            Timecard::create([
                'staff_id' => $s->id,
                'work_date' => sprintf('2026-04-%02d', min($i, 30)),
                'work_minutes' => $minutesPerDay,
                'status' => 'approved',
            ]);
        }
    }

    protected function calc(): PayrollCalculator
    {
        return app(PayrollCalculator::class);
    }

    public function test_class_b_secondary_income_is_taxed_at_flat_rate(): void
    {
        $s = $this->makeStaff('B', 2000);
        $this->seedFullMonth($s);

        $slip = $this->calc()->calculate($s, '2026-04');

        $this->assertGreaterThan(0, (int) $slip->income_tax,
            'Class B staff must have non-zero income tax (was ¥0 before Sprint 6)');
        // Flat 10.21% × taxable
        $expectedAtTaxable = (int) floor(((int) $slip->gross_pay - (int) $slip->employment_insurance) * 0.1021);
        // tolerance: ±¥1 for floor() ordering
        $this->assertEqualsWithDelta($expectedAtTaxable, (int) $slip->income_tax, 2);
    }

    public function test_class_c_daily_uses_per_day_bracket(): void
    {
        $s = $this->makeStaff('C', 1500);
        // Small month — 5 days only, 8h each → 40 hours = ¥60,000 gross, daily = ¥12,000
        for ($i = 1; $i <= 5; $i++) {
            Timecard::create([
                'staff_id' => $s->id,
                'work_date' => sprintf('2026-04-%02d', $i),
                'work_minutes' => 480,
                'status' => 'approved',
            ]);
        }

        $slip = $this->calc()->calculate($s, '2026-04');

        $this->assertGreaterThan(0, (int) $slip->income_tax,
            'Class C with daily ¥12,000 must produce non-zero tax (above ¥9,300 threshold)');
    }

    public function test_class_c_under_threshold_is_zero(): void
    {
        $s = $this->makeStaff('C', 800);   // 800 × 8h = ¥6,400/day < ¥9,300 daily threshold
        for ($i = 1; $i <= 5; $i++) {
            Timecard::create([
                'staff_id' => $s->id,
                'work_date' => sprintf('2026-04-%02d', $i),
                'work_minutes' => 480,
                'status' => 'approved',
            ]);
        }

        $slip = $this->calc()->calculate($s, '2026-04');
        $this->assertSame(0, (int) $slip->income_tax,
            'Class C below ¥9,300/day threshold must be ¥0');
    }

    public function test_class_a_and_b_at_same_taxable_diverge(): void
    {
        $sa = $this->makeStaff('A', 2000);
        $sb = $this->makeStaff('B', 2000);
        $this->seedFullMonth($sa);
        $this->seedFullMonth($sb);

        $slipA = $this->calc()->calculate($sa, '2026-04');
        $slipB = $this->calc()->calculate($sb, '2026-04');

        $this->assertNotSame((int) $slipA->income_tax, (int) $slipB->income_tax,
            'A and B should not produce the same tax for the same gross');
        // B (副業 standard) typically owes more than A (primary income with ¥88k threshold)
        $this->assertGreaterThan((int) $slipA->income_tax, (int) $slipB->income_tax,
            'Class B (副業) should owe at least as much as Class A on the same amount');
    }

    public function test_calc_meta_records_class_used(): void
    {
        $s = $this->makeStaff('C');
        $this->seedFullMonth($s);
        $slip = $this->calc()->calculate($s, '2026-04');
        $meta = $slip->calc_meta;

        $this->assertSame('C', $meta['withholding_class']);
        $this->assertContains('withholding_c', $meta['rate_codes']);
    }
}

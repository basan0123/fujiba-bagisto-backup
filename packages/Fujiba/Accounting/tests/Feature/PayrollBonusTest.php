<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\PayrollSlip;
use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Models\Timecard;
use Fujiba\Accounting\Services\Payroll\PayrollCalculator;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PayrollBonusTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeStaff(): Staff
    {
        return Staff::create([
            'staff_code' => 'BNS-' . uniqid(),
            'qr_token'   => 'bns-' . uniqid(),
            'name_ja'    => '賞与テスト',
            'employment_type' => 'full_time',
            'hourly_rate' => 2500,
            'enrolled_health_insurance' => true,
            'enrolled_pension'          => true,
            'enrolled_employment_insurance' => true,
            'is_active' => true,
        ]);
    }

    protected function calc(): PayrollCalculator
    {
        return app(PayrollCalculator::class);
    }

    public function test_bonus_calc_with_explicit_prev_taxable(): void
    {
        $s = $this->makeStaff();
        $slip = $this->calc()->calculateBonus($s, '2026-12', 500000, prevTaxable: 250000);

        $this->assertTrue((bool) $slip->is_bonus);
        $this->assertSame(500000, (int) $slip->gross_pay);
        $this->assertGreaterThan(0, (int) $slip->income_tax,
            'Bonus with non-zero prev_taxable must have income tax');
        $this->assertGreaterThan(0, (int) $slip->health_insurance);
        $this->assertSame((int) $slip->gross_pay - (int) $slip->total_deduction, (int) $slip->net_pay);
        $this->assertSame(250000, (int) $slip->prev_taxable);
    }

    public function test_bonus_auto_resolves_prev_taxable_from_prior_month(): void
    {
        $s = $this->makeStaff();
        // First create a prior monthly slip (2026-11)
        for ($i = 1; $i <= 22; $i++) {
            Timecard::create([
                'staff_id' => $s->id,
                'work_date' => sprintf('2026-11-%02d', min($i, 30)),
                'work_minutes' => 480,
                'status' => 'approved',
            ]);
        }
        $monthly = $this->calc()->calculate($s, '2026-11');
        $this->assertGreaterThan(0, (int) $monthly->gross_pay);

        // Now bonus in 2026-12 — prev_taxable auto-discovered from 11月 slip
        $bonus = $this->calc()->calculateBonus($s, '2026-12', 300000);
        $this->assertTrue((bool) $bonus->is_bonus);
        $this->assertGreaterThan(0, (int) $bonus->prev_taxable);
    }

    public function test_bonus_and_monthly_coexist_for_same_year_month(): void
    {
        $s = $this->makeStaff();
        Timecard::create([
            'staff_id' => $s->id, 'work_date' => '2026-12-15',
            'work_minutes' => 480, 'status' => 'approved',
        ]);

        $monthly = $this->calc()->calculate($s, '2026-12');
        $bonus   = $this->calc()->calculateBonus($s, '2026-12', 200000, prevTaxable: 100000);

        $this->assertNotSame($monthly->id, $bonus->id);
        $this->assertFalse((bool) $monthly->is_bonus);
        $this->assertTrue((bool) $bonus->is_bonus);

        $count = PayrollSlip::where('staff_id', $s->id)->where('year_month', '2026-12')->count();
        $this->assertSame(2, $count, 'Same year_month must allow both monthly + bonus slip');
    }

    public function test_bonus_zero_or_negative_amount_throws(): void
    {
        $s = $this->makeStaff();
        $this->expectException(\InvalidArgumentException::class);
        $this->calc()->calculateBonus($s, '2026-12', 0);
    }

    public function test_low_prev_taxable_yields_zero_or_low_rate(): void
    {
        $s = $this->makeStaff();
        // prev_taxable < ¥68,000 → rate = 0%
        $slip = $this->calc()->calculateBonus($s, '2026-12', 100000, prevTaxable: 50000);
        // Bonus at 0% withholding rate: only social insurance is deducted
        $this->assertSame(0, (int) $slip->income_tax);
    }

    public function test_finalized_bonus_slip_is_not_recomputed(): void
    {
        $s = $this->makeStaff();
        $first = $this->calc()->calculateBonus($s, '2026-12', 100000, prevTaxable: 200000);
        $first->update(['status' => 'finalized', 'gross_pay' => 999999]);

        $second = $this->calc()->calculateBonus($s, '2026-12', 100000, prevTaxable: 200000);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(999999, (int) $second->gross_pay);
    }
}

<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\PayrollSlip;
use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Services\Payroll\YearEndAdjustmentService;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class YearEndAdjustmentTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeStaff(): Staff
    {
        return Staff::create([
            'staff_code' => 'YE-' . uniqid(),
            'qr_token'   => 'ye-' . uniqid(),
            'name_ja'    => '年末調整テスト',
            'employment_type' => 'full_time',
            'dependents_count' => 1,
            'enrolled_health_insurance' => true,
            'enrolled_pension'          => true,
            'enrolled_employment_insurance' => true,
            'is_active' => true,
        ]);
    }

    protected function svc(): YearEndAdjustmentService
    {
        return new YearEndAdjustmentService();
    }

    /** Seed 12 monthly slips of equal amount for the year. */
    protected function seedMonthlySlips(Staff $s, int $year, int $monthlyGross, int $monthlyTax, int $monthlySi): void
    {
        for ($m = 1; $m <= 12; $m++) {
            PayrollSlip::create([
                'staff_id' => $s->id,
                'year_month' => sprintf('%04d-%02d', $year, $m),
                'is_bonus' => false,
                'channel_id' => 1,
                'gross_pay' => $monthlyGross,
                'income_tax' => $monthlyTax,
                'health_insurance' => (int) ($monthlySi * 0.4),
                'pension'          => (int) ($monthlySi * 0.5),
                'employment_insurance' => (int) ($monthlySi * 0.1),
                'total_deduction' => $monthlyTax + $monthlySi,
                'net_pay'   => $monthlyGross - $monthlyTax - $monthlySi,
                'status'    => 'finalized',
            ]);
        }
    }

    public function test_proposal_returns_full_breakdown(): void
    {
        $s = $this->makeStaff();
        $this->seedMonthlySlips($s, 2026, 300000, 6000, 45000);

        $p = $this->svc()->propose($s, 2026);

        $this->assertSame(2026, $p['year']);
        $this->assertSame(3600000, $p['annual_gross']);
        $this->assertSame(72000, $p['annual_withheld']);
        $this->assertGreaterThan(0, $p['annual_employment_deduction']);
        $this->assertGreaterThan(0, $p['annual_taxable_income']);
        // 1 dependent → ¥380k
        $this->assertSame(380000, $p['annual_dependent_deduction']);
        // No insurance entered → 0
        $this->assertSame(0, $p['annual_insurance_deduction']);
    }

    public function test_insurance_metadata_round_trip(): void
    {
        $s = $this->makeStaff();
        $this->svc()->setInsuranceMeta($s, 2026, [
            'life_insurance'      => 80000,
            'earthquake_insurance'=> 30000,
        ]);
        $s->refresh();

        $meta = $this->svc()->insuranceMeta($s, 2026);
        $this->assertSame(80000, $meta['life_insurance']);
        $this->assertSame(30000, $meta['earthquake_insurance']);

        // Insurance deduction: life ¥80k → bracket ¥80k/4 + ¥25k = ¥45k; eq ¥30k = ¥30k
        $this->assertSame(45000 + 30000, $this->svc()->insuranceDeduction($meta));
    }

    public function test_employment_income_deduction_brackets(): void
    {
        $svc = $this->svc();
        $this->assertSame(550000,  $svc->employmentIncomeDeduction(1500000));
        $this->assertSame((int) floor(1700000 * 0.40 - 100000), $svc->employmentIncomeDeduction(1700000));
        $this->assertSame((int) floor(5000000 * 0.20 + 440000), $svc->employmentIncomeDeduction(5000000));
        $this->assertSame(1950000, $svc->employmentIncomeDeduction(15000000));   // 上限
    }

    public function test_apply_to_december_adjusts_slip_and_logs_meta(): void
    {
        $s = $this->makeStaff();
        $this->seedMonthlySlips($s, 2026, 300000, 8000, 45000);
        // Bump withholding so we have a refund scenario
        // (year withheld 96000 vs true ~ smaller → positive adjustment)

        $proposal = $this->svc()->propose($s, 2026);
        $december = PayrollSlip::where('staff_id', $s->id)->where('year_month', '2026-12')->first();

        // Mark December as draft so apply works
        $december->update(['status' => 'draft']);
        $december->refresh();
        $beforeNet = (int) $december->net_pay;

        $applied = $this->svc()->applyToDecember($proposal);
        $this->assertNotNull($applied);
        $this->assertNotSame($beforeNet, (int) $applied->net_pay,
            'December net_pay must change when an adjustment is applied');
        $this->assertArrayHasKey('year_end_adjustment', (array) $applied->calc_meta);
    }

    public function test_apply_to_december_no_op_for_finalized(): void
    {
        $s = $this->makeStaff();
        $this->seedMonthlySlips($s, 2026, 300000, 8000, 45000);

        $proposal = $this->svc()->propose($s, 2026);
        // December is already 'finalized' from seed
        $applied = $this->svc()->applyToDecember($proposal);
        $this->assertNull($applied);
    }
}

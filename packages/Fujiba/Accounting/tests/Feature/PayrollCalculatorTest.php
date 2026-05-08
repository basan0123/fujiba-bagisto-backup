<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\PayrollSlip;
use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Models\Timecard;
use Fujiba\Accounting\Services\Payroll\PayrollCalculator;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;

class PayrollCalculatorTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeStaff(array $overrides = []): Staff
    {
        return Staff::create(array_merge([
            'staff_code' => 'PAY-' . uniqid(),
            'qr_token'   => 'pay-' . uniqid(),
            'name_ja'    => '給与テスト',
            'employment_type' => 'part_time',
            'hourly_rate' => 1500,
            'enrolled_health_insurance' => false,
            'enrolled_pension'          => false,
            'enrolled_employment_insurance' => true,
            'is_active'  => true,
        ], $overrides));
    }

    protected function calc(): PayrollCalculator
    {
        return app(PayrollCalculator::class);
    }

    public function test_zero_timecards_yields_zero_gross(): void
    {
        $s = $this->makeStaff();
        $slip = $this->calc()->calculate($s, '2026-04');
        $this->assertSame(0, (int) $slip->gross_pay);
        $this->assertSame(0, (int) $slip->net_pay);
        $this->assertSame('draft', $slip->status);
    }

    public function test_part_time_with_approved_timecards_computes_base_pay(): void
    {
        $s = $this->makeStaff(['hourly_rate' => 1500]);

        // Two approved 8-hour days, no overtime
        $month = Carbon::parse('2026-04-15');
        Timecard::create([
            'staff_id'   => $s->id,
            'work_date'  => $month->copy()->day(10)->toDateString(),
            'work_minutes' => 480,
            'status'     => 'approved',
        ]);
        Timecard::create([
            'staff_id'   => $s->id,
            'work_date'  => $month->copy()->day(11)->toDateString(),
            'work_minutes' => 480,
            'status'     => 'approved',
        ]);

        $slip = $this->calc()->calculate($s, '2026-04');

        // 1500 * (960/60) = 24000
        $this->assertSame(24000, (int) $slip->base_pay);
        $this->assertSame(960, (int) $slip->regular_minutes);
        $this->assertGreaterThan(0, (int) $slip->employment_insurance);
        $this->assertSame((int) $slip->base_pay - (int) $slip->total_deduction, (int) $slip->net_pay);
    }

    public function test_overtime_pays_125_percent(): void
    {
        $s = $this->makeStaff(['hourly_rate' => 1000]);

        Timecard::create([
            'staff_id' => $s->id,
            'work_date' => '2026-04-12',
            'work_minutes' => 480,
            'overtime_minutes' => 60,
            'status' => 'approved',
        ]);

        $slip = $this->calc()->calculate($s, '2026-04');
        $this->assertSame(8000, (int) $slip->base_pay);
        $this->assertSame(1250, (int) $slip->overtime_pay);   // floor(1000 * 1.25 * 60/60) = 1250
    }

    public function test_unapproved_timecards_are_excluded(): void
    {
        $s = $this->makeStaff(['hourly_rate' => 1000]);

        Timecard::create(['staff_id' => $s->id, 'work_date' => '2026-04-10', 'work_minutes' => 480, 'status' => 'approved']);
        Timecard::create(['staff_id' => $s->id, 'work_date' => '2026-04-11', 'work_minutes' => 480, 'status' => 'closed']);
        Timecard::create(['staff_id' => $s->id, 'work_date' => '2026-04-12', 'work_minutes' => 480, 'status' => 'open']);

        $slip = $this->calc()->calculate($s, '2026-04');
        $this->assertSame(8000, (int) $slip->base_pay, 'Only the approved timecard should count');
    }

    public function test_calc_does_not_clobber_finalized_slip(): void
    {
        $s = $this->makeStaff();
        $existing = PayrollSlip::create([
            'staff_id' => $s->id,
            'year_month' => '2026-04',
            'gross_pay' => 999999,
            'status' => 'finalized',
        ]);

        $slip = $this->calc()->calculate($s, '2026-04');
        $this->assertSame($existing->id, $slip->id);
        $this->assertSame(999999, (int) $slip->gross_pay, 'Finalized slips must not be recomputed');
    }

    public function test_invalid_year_month_throws(): void
    {
        $s = $this->makeStaff();
        $this->expectException(\InvalidArgumentException::class);
        $this->calc()->calculate($s, 'not-a-month');
    }
}

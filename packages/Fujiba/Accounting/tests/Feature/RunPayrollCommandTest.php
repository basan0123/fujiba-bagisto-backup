<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\PayrollSlip;
use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Models\Timecard;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RunPayrollCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected function seedStaffWithApprovedDay(int $hourly = 1500): Staff
    {
        $s = Staff::create([
            'staff_code' => 'RPC-' . uniqid(),
            'qr_token'   => 'rpc-' . uniqid(),
            'name_ja'    => 'コマンドテスト',
            'employment_type' => 'part_time',
            'hourly_rate' => $hourly,
            'enrolled_employment_insurance' => true,
            'is_active'  => true,
        ]);
        Timecard::create([
            'staff_id'   => $s->id,
            'work_date'  => '2026-04-10',
            'work_minutes' => 480,
            'status'     => 'approved',
        ]);
        return $s;
    }

    public function test_dry_run_does_not_persist(): void
    {
        $s = $this->seedStaffWithApprovedDay();

        $this->artisan('accounting:run-payroll', [
            '--year-month' => '2026-04',
            '--staff'      => (string) $s->id,
            '--dry-run'    => true,
        ])->assertExitCode(0);

        $this->assertNull(PayrollSlip::where('staff_id', $s->id)->first());
    }

    public function test_calculate_only_creates_draft(): void
    {
        $s = $this->seedStaffWithApprovedDay();

        $this->artisan('accounting:run-payroll', [
            '--year-month' => '2026-04',
            '--staff'      => (string) $s->id,
        ])->assertExitCode(0);

        $slip = PayrollSlip::where('staff_id', $s->id)->where('year_month', '2026-04')->first();
        $this->assertNotNull($slip);
        $this->assertSame('draft', $slip->status);
        $this->assertNull($slip->journal_entry_id);
    }

    public function test_finalize_flag_posts_journal_entry(): void
    {
        $s = $this->seedStaffWithApprovedDay();

        $this->artisan('accounting:run-payroll', [
            '--year-month' => '2026-04',
            '--staff'      => (string) $s->id,
            '--finalize'   => true,
        ])->assertExitCode(0);

        $slip = PayrollSlip::where('staff_id', $s->id)->where('year_month', '2026-04')->first();
        $this->assertNotNull($slip);
        $this->assertSame('finalized', $slip->status);
        $this->assertNotNull($slip->journal_entry_id);
        $this->assertNotNull($slip->finalized_at);
    }

    public function test_invalid_year_month_returns_failure(): void
    {
        $this->artisan('accounting:run-payroll', ['--year-month' => 'bogus'])
            ->assertExitCode(1);
    }
}

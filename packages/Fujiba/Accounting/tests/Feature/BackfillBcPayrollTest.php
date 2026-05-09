<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\AuditLog;
use Fujiba\Accounting\Models\JournalEntry;
use Fujiba\Accounting\Models\PayrollSlip;
use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Services\Payroll\BackfillBcPayrollService;
use Fujiba\Accounting\Services\Payroll\PayrollCalculator;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class BackfillBcPayrollTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeStaff(string $class, array $overrides = []): Staff
    {
        return Staff::create(array_merge([
            'staff_code' => 'BF-' . $class . '-' . uniqid(),
            'qr_token'   => 'bf-' . $class . '-' . uniqid(),
            'name_ja'    => "Backfill {$class}",
            'employment_type'   => 'part_time',
            'hourly_rate'       => 1500,
            'withholding_class' => $class,
            'enrolled_employment_insurance' => true,
            'is_active' => true,
        ], $overrides));
    }

    protected function makeBuggySlip(Staff $s, string $ym, int $gross): PayrollSlip
    {
        return PayrollSlip::create([
            'staff_id'    => $s->id,
            'year_month'  => $ym,
            'is_bonus'    => false,
            'channel_id'  => 1,
            'gross_pay'   => $gross,
            'base_pay'    => $gross,
            'income_tax'  => 0,                        // ← the bug
            'employment_insurance' => (int) floor($gross * 0.006),
            'total_deduction'      => (int) floor($gross * 0.006),
            'net_pay'     => $gross - (int) floor($gross * 0.006),
            'status'      => 'finalized',
            'finalized_at'=> now()->subDays(7),
        ]);
    }

    protected function svc(): BackfillBcPayrollService
    {
        return app(BackfillBcPayrollService::class);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $s = $this->makeStaff('B');
        $this->makeBuggySlip($s, '2026-05', 250000);

        $before = ['slips' => PayrollSlip::count(), 'entries' => JournalEntry::count(), 'audit' => AuditLog::count()];

        $stats = $this->svc()->backfill([
            'mode' => 'staff-deduction', 'from' => '2026-04', 'to' => '2026-06', 'dry_run' => true,
        ]);

        $after = ['slips' => PayrollSlip::count(), 'entries' => JournalEntry::count(), 'audit' => AuditLog::count()];
        $this->assertSame($before, $after, 'dry-run must not write anything');

        $this->assertSame(1, $stats['created_corrections']);
        $this->assertGreaterThan(0, $stats['total_shortfall']);
    }

    public function test_confirm_staff_deduction_creates_correction_no_journal(): void
    {
        $s = $this->makeStaff('B');
        $orig = $this->makeBuggySlip($s, '2026-05', 250000);

        $entriesBefore = JournalEntry::count();
        $stats = $this->svc()->backfill([
            'mode' => 'staff-deduction', 'from' => '2026-04', 'to' => '2026-06', 'dry_run' => false,
        ]);

        $this->assertSame(1, $stats['created_corrections']);
        $this->assertSame($entriesBefore, JournalEntry::count(),
            'staff-deduction mode must NOT post a journal entry');

        $correction = PayrollSlip::where('corrected_from_slip_id', $orig->id)
            ->where('status', 'correction')->first();
        $this->assertNotNull($correction);
        $this->assertGreaterThan(0, (int) $correction->income_tax);
        $this->assertSame('staff-deduction', $correction->calc_meta['mode']);
        $this->assertNull($correction->journal_entry_id);
    }

    public function test_confirm_company_bears_creates_correction_and_balanced_journal(): void
    {
        $s = $this->makeStaff('B');
        $orig = $this->makeBuggySlip($s, '2026-05', 250000);

        $stats = $this->svc()->backfill([
            'mode' => 'company-bears', 'from' => '2026-04', 'to' => '2026-06', 'dry_run' => false,
        ]);

        $this->assertSame(1, $stats['created_corrections']);
        $correction = PayrollSlip::where('corrected_from_slip_id', $orig->id)->where('status', 'correction')->first();
        $this->assertNotNull($correction);
        $this->assertNotNull($correction->journal_entry_id);

        $entry = JournalEntry::with('lines')->find($correction->journal_entry_id);
        $this->assertSame('payroll_correction', $entry->source_type);

        $dr = (int) $entry->lines->sum('debit');
        $cr = (int) $entry->lines->sum('credit');
        $this->assertSame($dr, $cr, 'correction journal must balance');
        $this->assertSame((int) $correction->income_tax, $dr);

        // DR side is 7230, CR side is 2220
        $this->assertSame('7230', $entry->lines->firstWhere('debit', '>', 0)->account?->code);
        $this->assertSame('2220', $entry->lines->firstWhere('credit', '>', 0)->account?->code);
    }

    public function test_idempotent_second_run_creates_no_new_corrections(): void
    {
        $s = $this->makeStaff('B');
        $this->makeBuggySlip($s, '2026-05', 250000);

        $first = $this->svc()->backfill(['mode' => 'company-bears', 'from' => '2026-04', 'to' => '2026-06', 'dry_run' => false]);
        $second = $this->svc()->backfill(['mode' => 'company-bears', 'from' => '2026-04', 'to' => '2026-06', 'dry_run' => false]);

        $this->assertSame(1, $first['created_corrections']);
        $this->assertSame(0, $second['created_corrections']);
        $this->assertSame(1, $second['skipped_already_corrected']);

        $this->assertSame(1, PayrollSlip::where('staff_id', $s->id)->where('status', 'correction')->count());
    }

    public function test_class_a_slip_is_not_in_scope(): void
    {
        $a = $this->makeStaff('A');
        $this->makeBuggySlip($a, '2026-05', 200000);

        $stats = $this->svc()->backfill(['mode' => 'staff-deduction', 'from' => '2026-04', 'to' => '2026-06', 'dry_run' => false]);
        $this->assertSame(0, $stats['created_corrections']);
        $this->assertSame(0, PayrollSlip::where('status', 'correction')->count());
    }

    public function test_inactive_staff_uses_inactive_mode_override(): void
    {
        $s = $this->makeStaff('B', ['is_active' => false]);
        $this->makeBuggySlip($s, '2026-05', 200000);

        $stats = $this->svc()->backfill([
            'mode'           => 'staff-deduction',           // for active
            'inactive_mode'  => 'company-bears',             // for terminated
            'from' => '2026-04', 'to' => '2026-06', 'dry_run' => false,
            'include_inactive' => true,
        ]);

        $this->assertSame(1, $stats['created_corrections']);
        $correction = PayrollSlip::where('staff_id', $s->id)->where('status', 'correction')->first();
        $this->assertSame('company-bears', $correction->calc_meta['mode'],
            'inactive staff must use --inactive-mode (company-bears), not the default mode');
        $this->assertNotNull($correction->journal_entry_id);
    }

    public function test_invalid_mode_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->svc()->backfill(['mode' => 'wat', 'from' => '2026-04', 'to' => '2026-06', 'dry_run' => true]);
    }

    public function test_carry_forward_uplifts_next_monthly_slip_income_tax(): void
    {
        $s = $this->makeStaff('B', ['hourly_rate' => 2000]);
        $orig = $this->makeBuggySlip($s, '2026-05', 250000);

        // Run staff-deduction backfill
        $this->svc()->backfill([
            'mode' => 'staff-deduction', 'from' => '2026-04', 'to' => '2026-06', 'dry_run' => false,
        ]);

        $correction = PayrollSlip::where('corrected_from_slip_id', $orig->id)->first();
        $shortfall = (int) $correction->income_tax;
        $this->assertGreaterThan(0, $shortfall);

        // Now run a NEW month's payroll for the same staff — calculator should
        // bump income_tax by the shortfall and mark the correction applied.
        \Fujiba\Accounting\Models\Timecard::create([
            'staff_id' => $s->id, 'work_date' => '2026-06-15',
            'work_minutes' => 480, 'status' => 'approved',
        ]);
        $next = app(PayrollCalculator::class)->calculate($s, '2026-06');

        $this->assertSame($shortfall, (int) ($next->calc_meta['prior_period_correction'] ?? 0),
            'next slip calc_meta must record the carried-forward amount');

        $correction->refresh();
        $this->assertSame($next->id, $correction->calc_meta['applied_in_slip_id'] ?? null,
            'correction must be tagged as absorbed by the new slip');

        // Re-running the calculator on the same month must NOT double-uplift.
        $next2 = app(PayrollCalculator::class)->calculate($s, '2026-06');
        $this->assertSame(0, (int) ($next2->calc_meta['prior_period_correction'] ?? 0),
            'second run must see zero pending uplift (correction already absorbed)');
    }

    public function test_pending_uplift_is_zero_when_no_corrections(): void
    {
        $s = $this->makeStaff('A');
        $this->assertSame(0, $this->svc()->pendingStaffDeductionUplift($s->id, '2026-06'));
    }

    public function test_audit_log_records_each_correction(): void
    {
        $s = $this->makeStaff('B');
        $this->makeBuggySlip($s, '2026-05', 250000);

        $auditBefore = AuditLog::where('user_name', 'cron:accounting:backfill-bc-payroll')->count();
        $this->svc()->backfill(['mode' => 'company-bears', 'from' => '2026-04', 'to' => '2026-06', 'dry_run' => false]);
        $auditAfter = AuditLog::where('user_name', 'cron:accounting:backfill-bc-payroll')->count();

        $this->assertSame($auditBefore + 1, $auditAfter);
    }

    public function test_command_requires_explicit_mode(): void
    {
        $this->artisan('accounting:backfill-bc-payroll')
            ->expectsOutputToContain('--mode is required')
            ->assertExitCode(1);
    }

    public function test_command_dry_run_default_no_writes(): void
    {
        $s = $this->makeStaff('B');
        $this->makeBuggySlip($s, '2026-05', 250000);

        $countBefore = PayrollSlip::count();
        $this->artisan('accounting:backfill-bc-payroll', [
            '--mode' => 'staff-deduction',
            '--from' => '2026-04', '--to' => '2026-06',
        ])->assertExitCode(0);

        $this->assertSame($countBefore, PayrollSlip::count(), 'default (dry-run) must not write');
    }
}

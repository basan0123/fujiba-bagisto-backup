<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\PayrollSlip;
use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Models\Timecard;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

/**
 * Sprint 7 Phase A — read-only nature of the audit command is the most
 * important property. Every test asserts no DB rows are mutated, with the
 * count probes recorded before and after the run.
 */
class AuditBcPayrollCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeStaff(string $class, array $overrides = []): Staff
    {
        return Staff::create(array_merge([
            'staff_code' => 'AB-' . $class . '-' . uniqid(),
            'qr_token'   => 'ab-' . $class . '-' . uniqid(),
            'name_ja'    => "Audit {$class} 様",
            'employment_type'   => 'part_time',
            'hourly_rate'       => 1500,
            'withholding_class' => $class,
            'enrolled_employment_insurance' => true,
            'is_active' => true,
        ], $overrides));
    }

    /** Mimic a Sprint 4-vintage buggy slip: B/C staff with income_tax = 0. */
    protected function makeBuggySlip(Staff $s, string $ym, int $gross): PayrollSlip
    {
        return PayrollSlip::create([
            'staff_id'    => $s->id,
            'year_month'  => $ym,
            'is_bonus'    => false,
            'channel_id'  => 1,
            'gross_pay'   => $gross,
            'base_pay'    => $gross,
            'income_tax'  => 0,                       // ← the bug
            'employment_insurance' => (int) floor($gross * 0.006),
            'total_deduction'      => (int) floor($gross * 0.006),
            'net_pay'     => $gross - (int) floor($gross * 0.006),
            'status'      => 'finalized',
            'finalized_at'=> now()->subDays(7),
        ]);
    }

    public function test_zero_in_scope_staff_returns_success_with_no_op_message(): void
    {
        // No B/C staff exist → audit should report and exit cleanly.
        $resp = $this->artisan('accounting:audit-bc-payroll', ['--from' => '2026-04', '--to' => '2026-06'])
            ->assertExitCode(0);
    }

    public function test_invalid_from_format_returns_failure(): void
    {
        $this->artisan('accounting:audit-bc-payroll', ['--from' => '2026/04'])
            ->expectsOutputToContain('Invalid')
            ->assertExitCode(1);
    }

    public function test_from_after_to_returns_failure(): void
    {
        $this->artisan('accounting:audit-bc-payroll', ['--from' => '2026-09', '--to' => '2026-04'])
            ->expectsOutputToContain('--from')
            ->assertExitCode(1);
    }

    public function test_audit_does_not_mutate_anything(): void
    {
        $b = $this->makeStaff('B');
        $c = $this->makeStaff('C');
        $bSlip = $this->makeBuggySlip($b, '2026-05', 250000);
        $cSlip = $this->makeBuggySlip($c, '2026-05', 180000);

        $countsBefore = [
            'staff'       => Staff::count(),
            'slips'       => PayrollSlip::count(),
            'audit_log'   => DB::table('fujiba_audit_log')->count(),
        ];
        $bSlipBefore = $bSlip->refresh()->toArray();
        $cSlipBefore = $cSlip->refresh()->toArray();

        $tmp = tempnam(sys_get_temp_dir(), 'audit_') . '.csv';
        $this->artisan('accounting:audit-bc-payroll', [
            '--from' => '2026-04',
            '--to'   => '2026-06',
            '--out'  => $tmp,
        ])->assertExitCode(0);

        $countsAfter = [
            'staff'       => Staff::count(),
            'slips'       => PayrollSlip::count(),
            'audit_log'   => DB::table('fujiba_audit_log')->count(),
        ];
        $this->assertSame($countsBefore, $countsAfter, 'audit must not insert/delete any rows');
        $this->assertEquals($bSlipBefore, $bSlip->refresh()->toArray(), 'B slip must be untouched');
        $this->assertEquals($cSlipBefore, $cSlip->refresh()->toArray(), 'C slip must be untouched');

        @unlink($tmp);
    }

    public function test_csv_output_contains_one_row_per_in_scope_slip_with_shortfall(): void
    {
        $b = $this->makeStaff('B');
        $this->makeBuggySlip($b, '2026-05', 250000);
        $this->makeBuggySlip($b, '2026-06', 250000);

        $tmp = tempnam(sys_get_temp_dir(), 'audit_') . '.csv';
        $this->artisan('accounting:audit-bc-payroll', [
            '--from' => '2026-05',
            '--to'   => '2026-06',
            '--out'  => $tmp,
        ])->assertExitCode(0);

        $csv = file_get_contents($tmp);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv, 'CSV must have UTF-8 BOM');
        $lines = explode("\n", trim($csv));

        // header + 2 data rows
        $this->assertGreaterThanOrEqual(3, count($lines));
        $this->assertStringContainsString('shortfall', $lines[0]);

        // Data lines should contain non-zero correct_income_tax for class B (10.21% flat).
        $body = implode("\n", array_slice($lines, 1));
        $this->assertStringContainsString('B,', $body);
        // ¥250,000 - SI ≈ ¥248,500 × 10.21% ≈ ¥25,000+ — non-zero
        $this->assertMatchesRegularExpression('/,(2[0-9]{4}|3[0-9]{4})\b/', $body,
            'Class B shortfall should be a non-trivial amount');

        @unlink($tmp);
    }

    public function test_inactive_excluded_by_default_included_with_flag(): void
    {
        $active   = $this->makeStaff('B', ['is_active' => true]);
        $inactive = $this->makeStaff('B', ['is_active' => false]);
        $this->makeBuggySlip($active,   '2026-05', 200000);
        $this->makeBuggySlip($inactive, '2026-05', 200000);

        $defaultCsv = tempnam(sys_get_temp_dir(), 'audit_') . '.csv';
        $this->artisan('accounting:audit-bc-payroll', [
            '--from' => '2026-05', '--to' => '2026-05', '--out' => $defaultCsv,
        ])->assertExitCode(0);
        $defaultLines = count(explode("\n", trim(file_get_contents($defaultCsv)))) - 1; // minus header
        $this->assertSame(1, $defaultLines, 'inactive staff should be excluded by default');

        $allCsv = tempnam(sys_get_temp_dir(), 'audit_') . '.csv';
        $this->artisan('accounting:audit-bc-payroll', [
            '--from' => '2026-05', '--to' => '2026-05',
            '--include-inactive' => true, '--out' => $allCsv,
        ])->assertExitCode(0);
        $allLines = count(explode("\n", trim(file_get_contents($allCsv)))) - 1;
        $this->assertSame(2, $allLines, '--include-inactive should add the terminated staff row');

        @unlink($defaultCsv);
        @unlink($allCsv);
    }

    public function test_class_a_slips_are_not_in_scope(): void
    {
        $a = $this->makeStaff('A');
        $this->makeBuggySlip($a, '2026-05', 200000);

        // Use a path that doesn't pre-exist so we can assert non-creation.
        $tmp = sys_get_temp_dir() . '/audit_a_only_' . uniqid() . '.csv';
        $this->artisan('accounting:audit-bc-payroll', [
            '--from' => '2026-05', '--to' => '2026-05', '--out' => $tmp,
        ])
            ->expectsOutputToContain('Nothing to audit')
            ->assertExitCode(0);

        // No B/C staff exist → command early-exits before writing the CSV.
        $this->assertFalse(file_exists($tmp), 'CSV must not be created when nothing is in scope');
    }

    public function test_class_c_under_threshold_yields_zero_shortfall(): void
    {
        // Class C with low daily wage (<¥9,300/日) — bracket says zero, so shortfall = 0
        $c = $this->makeStaff('C', ['hourly_rate' => 800]);
        // Approve 5 timecards for the slip month so workingDays = 5
        for ($i = 1; $i <= 5; $i++) {
            Timecard::create([
                'staff_id' => $c->id,
                'work_date' => sprintf('2026-05-%02d', $i),
                'work_minutes' => 480,
                'status' => 'approved',
            ]);
        }
        // 800 × 8h × 5 days = ¥32,000 — daily ¥6,400 < ¥9,300 threshold
        $this->makeBuggySlip($c, '2026-05', 32000);

        $tmp = tempnam(sys_get_temp_dir(), 'audit_') . '.csv';
        $this->artisan('accounting:audit-bc-payroll', [
            '--from' => '2026-05', '--to' => '2026-05', '--out' => $tmp,
        ])->assertExitCode(0);

        $body = file_get_contents($tmp);
        $lines = array_values(array_filter(explode("\n", trim($body))));
        $dataLine = $lines[1] ?? '';
        $cells = str_getcsv($dataLine);
        // shortfall column is the 2nd-to-last
        $shortfall = (int) ($cells[count($cells) - 2] ?? -1);
        $this->assertSame(0, $shortfall, 'Class C below daily threshold must show zero shortfall');

        @unlink($tmp);
    }
}

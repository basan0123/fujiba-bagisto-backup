<?php

namespace Fujiba\Accounting\Services\Payroll;

use Fujiba\Accounting\Models\AuditLog;
use Fujiba\Accounting\Models\JournalEntry;
use Fujiba\Accounting\Models\PayrollSlip;
use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Models\Timecard;
use Fujiba\Accounting\Services\JournalPoster;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sprint 7 Phase B — apply correction slips for B/C 区分 staff whose
 * original slips had the Sprint-4-vintage `income_tax = 0` bug.
 *
 * Modes:
 *   • staff-deduction — correction slip records the shortfall but DOES NOT
 *     post a journal. Instead, the next monthly slip's `income_tax` is
 *     bumped by the prior shortfall (calculator picks it up via
 *     `calc_meta.prior_period_correction`). Effect: staff catches up the
 *     shortfall in next paycheck. Legally correct path.
 *
 *   • company-bears — correction slip posts a real journal entry
 *     `DR 7230 雑損失 / CR 2220 預り金(源泉)`, source_type='payroll_correction'.
 *     Effect: company absorbs the missed withholding; staff is unaffected.
 *
 * Each call is idempotent at the (original-slip-id) level: re-running over
 * already-corrected slips is a no-op.
 *
 * Per-slip processing is wrapped in its own transaction. A failure on one
 * staff doesn't block the others — failures are collected and surfaced in
 * the run summary.
 */
class BackfillBcPayrollService
{
    public const MODE_STAFF_DEDUCTION = 'staff-deduction';
    public const MODE_COMPANY_BEARS   = 'company-bears';

    public function __construct(
        protected PayrollCalculator $calculator,
        protected JournalPoster $poster,
    ) {}

    /**
     * @return array{
     *   mode: string,
     *   inactive_mode: string,
     *   from: string, to: string,
     *   dry_run: bool,
     *   processed: int,
     *   created_corrections: int,
     *   skipped_no_shortfall: int,
     *   skipped_already_corrected: int,
     *   skipped_class_a: int,
     *   errors: array<int,string>,
     *   total_shortfall: int,
     *   per_staff: array<int,array{staff_id:int, slips:int, shortfall:int}>,
     * }
     */
    public function backfill(array $opts): array
    {
        $mode         = $opts['mode'];
        $inactiveMode = $opts['inactive_mode'] ?? self::MODE_COMPANY_BEARS;
        $from         = $opts['from'] ?? '2026-04';
        $to           = $opts['to']   ?? Carbon::now()->format('Y-m');
        $dryRun       = (bool) ($opts['dry_run'] ?? true);
        $staffFilter  = $opts['staff_ids'] ?? null;
        $includeInactive = (bool) ($opts['include_inactive'] ?? true);

        $this->guardMode($mode, 'mode');
        $this->guardMode($inactiveMode, 'inactive_mode');

        $stats = [
            'mode'                       => $mode,
            'inactive_mode'              => $inactiveMode,
            'from'                       => $from,
            'to'                         => $to,
            'dry_run'                    => $dryRun,
            'processed'                  => 0,
            'created_corrections'        => 0,
            'skipped_no_shortfall'       => 0,
            'skipped_already_corrected'  => 0,
            'skipped_class_a'            => 0,
            'errors'                     => [],
            'total_shortfall'            => 0,
            'per_staff'                  => [],
        ];

        // Pull in-scope original slips (B/C, finalized/paid, in window).
        $staffQ = Staff::whereIn('withholding_class', ['B', 'C']);
        if (! $includeInactive) {
            $staffQ->where('is_active', true);
        }
        if ($staffFilter !== null) {
            $staffQ->whereIn('id', $staffFilter);
        }
        $staffIds = $staffQ->pluck('id');
        if ($staffIds->isEmpty()) {
            return $stats;
        }

        $slips = PayrollSlip::with('staff')
            ->whereIn('staff_id', $staffIds)
            ->whereIn('status', ['finalized', 'paid'])
            ->where('is_bonus', false)
            ->where('year_month', '>=', $from)
            ->where('year_month', '<=', $to)
            ->orderBy('year_month')
            ->orderBy('staff_id')
            ->get();

        foreach ($slips as $original) {
            $stats['processed']++;
            $staff = $original->staff;
            if (! $staff || ! in_array($staff->withholding_class, ['B', 'C'], true)) {
                $stats['skipped_class_a']++;
                continue;
            }

            // Idempotency: skip if a correction already exists for this slip.
            $existing = PayrollSlip::where('corrected_from_slip_id', $original->id)
                ->where('status', 'correction')
                ->first();
            if ($existing) {
                $stats['skipped_already_corrected']++;
                continue;
            }

            $shortfall = $this->shortfallFor($original, $staff);
            if ($shortfall <= 0) {
                $stats['skipped_no_shortfall']++;
                continue;
            }

            $effectiveMode = $staff->is_active ? $mode : $inactiveMode;

            try {
                if ($dryRun) {
                    $stats['created_corrections']++;
                    $stats['total_shortfall'] += $shortfall;
                    $stats['per_staff'][$staff->id] ??= ['staff_id' => $staff->id, 'slips' => 0, 'shortfall' => 0];
                    $stats['per_staff'][$staff->id]['slips']++;
                    $stats['per_staff'][$staff->id]['shortfall'] += $shortfall;
                    continue;
                }

                DB::transaction(function () use ($original, $staff, $shortfall, $effectiveMode) {
                    $this->createCorrection($original, $staff, $shortfall, $effectiveMode);
                });

                $stats['created_corrections']++;
                $stats['total_shortfall']    += $shortfall;
                $stats['per_staff'][$staff->id] ??= ['staff_id' => $staff->id, 'slips' => 0, 'shortfall' => 0];
                $stats['per_staff'][$staff->id]['slips']++;
                $stats['per_staff'][$staff->id]['shortfall'] += $shortfall;
            } catch (Throwable $e) {
                $stats['errors'][$original->id] = "slip {$original->id}: " . $e->getMessage();
                Log::error('Fujiba\\Accounting: backfill failed', [
                    'slip_id' => $original->id,
                    'staff_id' => $staff->id,
                    'mode' => $effectiveMode,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Re-key per_staff to a clean numeric list for stable JSON encoding.
        $stats['per_staff'] = array_values($stats['per_staff']);
        return $stats;
    }

    /**
     * Compute the income-tax shortfall on an original slip via the
     * Sprint-6-fixed calculator's `withholding()` (reached through Closure::bind).
     */
    public function shortfallFor(PayrollSlip $original, Staff $staff): int
    {
        $gross   = (int) $original->gross_pay;
        $commute = (int) $original->commute_allowance;
        $hi      = (int) $original->health_insurance;
        $pen     = (int) $original->pension;
        $ei      = (int) $original->employment_insurance;
        $taxable = max(0, $gross - $commute - $hi - $pen - $ei);

        $workingDays = 0;
        if ($staff->withholding_class === 'C') {
            $start = Carbon::parse($original->year_month . '-01')->startOfMonth();
            $end   = $start->copy()->endOfMonth();
            $workingDays = Timecard::where('staff_id', $staff->id)
                ->where('status', 'approved')
                ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
                ->count();
        }

        $closure = \Closure::bind(function (
            PayrollCalculator $self, int $taxable, string $class, int $dependents, int $workingDays
        ) {
            return $self->withholding($taxable, $class, $dependents, $workingDays);
        }, null, PayrollCalculator::class);

        $correct = (int) $closure(
            $this->calculator, $taxable,
            $staff->withholding_class ?? 'A',
            $staff->dependents_count ?? 0,
            $workingDays,
        );

        return max(0, $correct - (int) $original->income_tax);
    }

    /**
     * Create a correction slip + (mode-dependent) journal posting.
     */
    protected function createCorrection(PayrollSlip $original, Staff $staff, int $shortfall, string $mode): PayrollSlip
    {
        $correction = PayrollSlip::create([
            'staff_id'              => $staff->id,
            'year_month'            => $original->year_month,
            'is_bonus'              => false,
            'channel_id'            => $original->channel_id,
            'pay_date'              => now()->toDateString(),
            'corrected_from_slip_id'=> $original->id,
            'gross_pay'             => 0,
            'income_tax'            => $shortfall,
            'total_deduction'       => $shortfall,
            'net_pay'               => -$shortfall,   // negative = staff owes / company carries
            'status'                => 'correction',
            'finalized_at'          => now(),
            'calc_meta'             => [
                'sprint7_backfill'      => true,
                'mode'                  => $mode,
                'original_slip_id'      => $original->id,
                'original_income_tax'   => (int) $original->income_tax,
                'corrected_income_tax'  => (int) $original->income_tax + $shortfall,
                'shortfall'             => $shortfall,
                'computed_at'           => now()->toIso8601String(),
            ],
        ]);

        if ($mode === self::MODE_COMPANY_BEARS) {
            $entry = $this->postCompanyBearsJournal($staff, $shortfall, $correction);
            $correction->update(['journal_entry_id' => $entry->id]);
        }
        // staff-deduction has no journal — the next monthly slip absorbs it
        // via PayrollCalculator's `pendingCorrectionUplift()` lookup.

        AuditLog::create([
            'table_name' => 'fujiba_payroll_slips',
            'record_id'  => $correction->id,
            'action'     => 'created',
            'after_json' => [
                'source'      => 'sprint7-backfill',
                'mode'        => $mode,
                'original_id' => $original->id,
                'shortfall'   => $shortfall,
            ],
            'user_name'  => 'cron:accounting:backfill-bc-payroll',
        ]);

        return $correction->fresh();
    }

    /**
     * company-bears journal:
     *   DR 7230 雑損失       ¥shortfall
     *      CR 2220 預り金(源泉)               ¥shortfall
     */
    protected function postCompanyBearsJournal(Staff $staff, int $shortfall, PayrollSlip $correction): JournalEntry
    {
        return $this->poster->post([
            'entry_date'      => now(),
            'channel_id'      => $staff->channel_id,
            'memo'            => "B/C源泉訂正 {$correction->year_month} {$staff->name_ja} (会社負担)",
            'reference_no'    => "PAY-COR-{$correction->id}",
            'source_type'     => 'payroll_correction',
            'source_id'       => $correction->id,
            'idempotency_key' => "payroll-correction-{$correction->id}",
            'lines' => [
                ['account_code' => '7230', 'debit' => $shortfall, 'credit' => 0,
                 'memo' => "B/C源泉訂正 {$staff->staff_code}"],
                ['account_code' => '2220', 'debit' => 0,          'credit' => $shortfall,
                 'memo' => "預り金(源泉) 追計上"],
            ],
        ]);
    }

    protected function guardMode(string $mode, string $field): void
    {
        if (! in_array($mode, [self::MODE_STAFF_DEDUCTION, self::MODE_COMPANY_BEARS], true)) {
            throw new \InvalidArgumentException("Invalid {$field}: {$mode} (expected 'staff-deduction' or 'company-bears')");
        }
    }

    /**
     * Sum of un-applied staff-deduction corrections for a staff up to a given
     * year-month. Used by PayrollCalculator to bump next-slip income_tax.
     *
     * "Applied" is detected via correction's calc_meta.applied_in_slip_id —
     * once a regular slip absorbs the carry-forward, the correction is marked
     * so we don't double-count.
     */
    public function pendingStaffDeductionUplift(int $staffId, string $upcomingYm): int
    {
        $pending = PayrollSlip::where('staff_id', $staffId)
            ->where('status', 'correction')
            ->where('year_month', '<', $upcomingYm)
            ->get()
            ->filter(fn ($s) => ($s->calc_meta['mode'] ?? null) === self::MODE_STAFF_DEDUCTION)
            ->filter(fn ($s) => ! ($s->calc_meta['applied_in_slip_id'] ?? null))
            ->sum(fn ($s) => (int) $s->income_tax);

        return (int) $pending;
    }

    /** Mark all pending staff-deduction corrections as absorbed by $upcomingSlipId. */
    public function markStaffDeductionApplied(int $staffId, string $upcomingYm, int $upcomingSlipId): void
    {
        PayrollSlip::where('staff_id', $staffId)
            ->where('status', 'correction')
            ->where('year_month', '<', $upcomingYm)
            ->get()
            ->filter(fn ($s) => ($s->calc_meta['mode'] ?? null) === self::MODE_STAFF_DEDUCTION)
            ->filter(fn ($s) => ! ($s->calc_meta['applied_in_slip_id'] ?? null))
            ->each(function (PayrollSlip $s) use ($upcomingSlipId) {
                $meta = is_array($s->calc_meta) ? $s->calc_meta : [];
                $meta['applied_in_slip_id'] = $upcomingSlipId;
                $meta['applied_at']         = now()->toIso8601String();
                $s->calc_meta = $meta;
                $s->save();
            });
    }
}

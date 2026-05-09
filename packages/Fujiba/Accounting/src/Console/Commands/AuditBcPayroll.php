<?php

namespace Fujiba\Accounting\Console\Commands;

use Fujiba\Accounting\Models\AuditLog;
use Fujiba\Accounting\Models\PayrollSlip;
use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Models\Timecard;
use Fujiba\Accounting\Services\Payroll\PayrollCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Sprint 7 Phase A — read-only audit of B/C 区分 payroll exposure.
 *
 * The bug: between Sprint 4 (2026-04) and Sprint 6 Phase 1A, the calculator
 * returned `null` for non-甲 withholding classes, recording income_tax = 0
 * on every B/C slip. This command reads the books — it never writes.
 *
 * Output:
 *   - Console summary (totals, counts, estimated shortfall)
 *   - Detailed CSV per (staff × month)
 *
 * No DB writes. No journal posting. No slip mutation. Operator runs this,
 * shares the CSV with the tax accountant, then decides whether to run the
 * Phase B backfill command (separate file, separate commit).
 */
class AuditBcPayroll extends Command
{
    protected $signature = 'accounting:audit-bc-payroll
                            {--from= : Year-month YYYY-MM (default: 2026-04 — Sprint 4 release)}
                            {--to= : Year-month YYYY-MM (default: current month)}
                            {--include-inactive : Include slips for terminated staff}
                            {--out= : CSV output path (default: /tmp/bc-audit-{ts}.csv)}';

    protected $description = 'Read-only audit of withholding class B/C payroll exposure (Sprint 4 → Sprint 6 P1A bug).';

    public function handle(PayrollCalculator $calc): int
    {
        $from = $this->option('from') ?: '2026-04';
        $to   = $this->option('to')   ?: Carbon::now()->format('Y-m');

        if (! preg_match('/^\d{4}-\d{2}$/', $from) || ! preg_match('/^\d{4}-\d{2}$/', $to)) {
            $this->error('Invalid --from/--to. Expected YYYY-MM.');
            return self::FAILURE;
        }
        if ($from > $to) {
            $this->error("--from ({$from}) > --to ({$to})");
            return self::FAILURE;
        }

        $includeInactive = (bool) $this->option('include-inactive');
        $outPath = $this->option('out') ?: '/tmp/bc-audit-' . now()->format('Y-m-d-His') . '.csv';

        $this->line('================================================================');
        $this->line('甲欄 B/C 源泉徴収バックフィル 影響調査  (READ-ONLY)');
        $this->line('================================================================');
        $this->line(" 期間 : {$from} → {$to}");
        $this->line(' include_inactive : ' . ($includeInactive ? 'yes' : 'no (active only)'));
        $this->line('');

        // ── Headcount ────────────────────────────────────────────────────────
        $staffQ = Staff::whereIn('withholding_class', ['B', 'C']);
        $activeBC   = (clone $staffQ)->where('is_active', true)->count();
        $activeB    = (clone $staffQ)->where('is_active', true)->where('withholding_class', 'B')->count();
        $activeC    = (clone $staffQ)->where('is_active', true)->where('withholding_class', 'C')->count();
        $inactiveBC = (clone $staffQ)->where('is_active', false)->count();

        $this->line(" active B 区分 staff       : {$activeB}");
        $this->line(" active C 区分 staff       : {$activeC}");
        $this->line(" inactive B/C staff        : {$inactiveBC}");

        // ── In-scope staff ───────────────────────────────────────────────────
        $staffIds = (clone $staffQ)
            ->when(! $includeInactive, fn ($q) => $q->where('is_active', true))
            ->pluck('id');

        if ($staffIds->isEmpty()) {
            $this->line('');
            $this->info('No B/C staff in scope. Nothing to audit.');
            return self::SUCCESS;
        }

        // ── Buggy slips: B/C, finalized/paid, in window ─────────────────────
        $slips = PayrollSlip::with('staff')
            ->whereIn('staff_id', $staffIds)
            ->whereIn('status', ['finalized', 'paid'])
            ->where('is_bonus', false)
            ->where('year_month', '>=', $from)
            ->where('year_month', '<=', $to)
            ->orderBy('year_month')
            ->orderBy('staff_id')
            ->get();

        $this->line(" buggy 期間中 finalized slips : {$slips->count()}    (income_tax=0 で記帳済み)");

        // ── Class-change events from audit log ──────────────────────────────
        $classChanges = AuditLog::where('table_name', 'fujiba_staff')
            ->where('action', 'updated')
            ->whereIn('record_id', $staffIds)
            ->get()
            ->filter(function ($row) {
                $before = is_array($row->before_json) ? $row->before_json : [];
                $after  = is_array($row->after_json)  ? $row->after_json  : [];
                return array_key_exists('withholding_class', $before)
                    || array_key_exists('withholding_class', $after);
            });
        $this->line(' buggy 期間中の class 変更ログ : ' . $classChanges->count());

        // ── Per-slip recalc and CSV emit ────────────────────────────────────
        $rows = [];
        $totalShortfall = 0;
        $journalIds = [];

        foreach ($slips as $slip) {
            $staff = $slip->staff;
            if (! $staff) {
                continue;
            }
            $recordedTax = (int) $slip->income_tax;

            $correctTax = $this->recomputeIncomeTax($slip, $staff, $calc);
            $shortfall = max(0, $correctTax - $recordedTax);

            $changed = $classChanges
                ->filter(fn ($r) => $r->record_id === $staff->id)
                ->filter(fn ($r) => Carbon::parse($r->created_at)->format('Y-m') >= $slip->year_month)
                ->count() > 0;

            $rows[] = [
                'staff_id'                 => $staff->id,
                'staff_code'               => $staff->staff_code,
                'staff_name'               => $staff->name_ja,
                'withholding_class'        => $staff->withholding_class,
                'is_active'                => (int) $staff->is_active,
                'year_month'               => $slip->year_month,
                'slip_id'                  => $slip->id,
                'slip_status'              => $slip->status,
                'journal_entry_id'         => $slip->journal_entry_id,
                'gross_pay'                => (int) $slip->gross_pay,
                'prev_taxable'             => (int) $slip->prev_taxable,
                'recorded_income_tax'      => $recordedTax,
                'correct_income_tax'       => $correctTax,
                'shortfall'                => $shortfall,
                'class_change_after_slip'  => $changed ? 1 : 0,
            ];

            $totalShortfall += $shortfall;
            if ($slip->journal_entry_id) {
                $journalIds[$slip->journal_entry_id] = true;
            }
        }

        $this->line('================================================================');
        $this->line(' 推定不足源泉総額            : ¥' . number_format($totalShortfall));
        $this->line(' 推定不足預り金 (2210 社保)   : 0   ← 社保は別計算なので影響なし');
        $this->line(' 仕訳訂正対象 journal entries : ' . count($journalIds));
        $this->line('================================================================');

        $this->writeCsv($outPath, $rows);
        $this->line(' 詳細CSV : ' . $outPath);

        // Console table summary by staff (top 20 by shortfall)
        if (! empty($rows)) {
            $byStaff = [];
            foreach ($rows as $r) {
                $k = $r['staff_id'];
                $byStaff[$k] ??= [
                    'code' => $r['staff_code'],
                    'name' => $r['staff_name'],
                    'class'=> $r['withholding_class'],
                    'months'=> 0,
                    'shortfall' => 0,
                ];
                $byStaff[$k]['months']    += 1;
                $byStaff[$k]['shortfall'] += $r['shortfall'];
            }
            usort($byStaff, fn ($a, $b) => $b['shortfall'] <=> $a['shortfall']);

            $this->newLine();
            $this->line('影響上位 staff (推定不足源泉降順):');
            $this->table(
                ['code', 'name', 'class', 'months', 'shortfall (¥)'],
                array_map(
                    fn ($s) => [$s['code'], $s['name'], $s['class'], $s['months'], number_format($s['shortfall'])],
                    array_slice($byStaff, 0, 20),
                ),
            );
        }

        $this->newLine();
        $this->warn('READ-ONLY audit 完了。仕訳・slip は一切変更していません。');
        $this->line('GO/NO-GO の判断材料を CSV と一緒に税理士へ共有してください。');

        return self::SUCCESS;
    }

    /**
     * Recompute income_tax via the (Sprint 6-fixed) calculator without
     * touching the slip. We mimic the flow inside calculate() but without
     * persisting: gross & SI come from the slip itself (already correct
     * for B/C — only the withholding step was buggy), so we only need to
     * re-derive the taxable income and pass it through `withholding()`.
     *
     * The calculator's `withholding()` is protected; reach it through a
     * Closure to avoid duplicating the bracket logic here.
     */
    protected function recomputeIncomeTax(PayrollSlip $slip, Staff $staff, PayrollCalculator $calc): int
    {
        $gross = (int) $slip->gross_pay;
        $commute = (int) $slip->commute_allowance;
        $hi  = (int) $slip->health_insurance;
        $pen = (int) $slip->pension;
        $ei  = (int) $slip->employment_insurance;
        $taxable = max(0, $gross - $commute - $hi - $pen - $ei);

        // Working days for class C bracket — best effort from approved timecards
        // in the slip's month.
        $workingDays = 0;
        if ($staff->withholding_class === 'C') {
            $start = Carbon::parse($slip->year_month . '-01')->startOfMonth();
            $end   = $start->copy()->endOfMonth();
            $workingDays = Timecard::where('staff_id', $staff->id)
                ->where('status', 'approved')
                ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
                ->count();
        }

        $closure = \Closure::bind(function (
            PayrollCalculator $self,
            int $taxable, string $class, int $dependents, int $workingDays
        ) {
            return $self->withholding($taxable, $class, $dependents, $workingDays);
        }, null, PayrollCalculator::class);

        return (int) $closure($calc, $taxable, $staff->withholding_class ?? 'A', $staff->dependents_count ?? 0, $workingDays);
    }

    protected function writeCsv(string $path, array $rows): void
    {
        $h = fopen($path, 'w');
        // UTF-8 BOM for Excel
        fwrite($h, "\xEF\xBB\xBF");
        $headers = [
            'staff_id', 'staff_code', 'staff_name', 'withholding_class', 'is_active',
            'year_month', 'slip_id', 'slip_status', 'journal_entry_id',
            'gross_pay', 'prev_taxable', 'recorded_income_tax', 'correct_income_tax',
            'shortfall', 'class_change_after_slip',
        ];
        fputcsv($h, $headers);
        foreach ($rows as $r) {
            fputcsv($h, array_map(fn ($k) => $r[$k] ?? '', $headers));
        }
        fclose($h);
    }
}

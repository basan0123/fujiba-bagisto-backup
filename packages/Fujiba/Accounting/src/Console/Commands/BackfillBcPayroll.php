<?php

namespace Fujiba\Accounting\Console\Commands;

use Fujiba\Accounting\Services\Payroll\BackfillBcPayrollService;
use Illuminate\Console\Command;

/**
 * Sprint 7 Phase B — apply correction slips for B/C payroll bug.
 *
 * Deliberately separate file from `accounting:audit-bc-payroll` so a
 * tired operator can't typo their way into running the wrong command.
 *
 * Defaults:
 *   --mode is REQUIRED (no default — operator must pick consciously)
 *   --inactive-mode defaults to company-bears (terminated staff can't be
 *     payroll-deducted)
 *   --dry-run is the default execution; --confirm flips it to writes.
 */
class BackfillBcPayroll extends Command
{
    protected $signature = 'accounting:backfill-bc-payroll
                            {--mode= : staff-deduction | company-bears  (REQUIRED)}
                            {--inactive-mode=company-bears : mode for terminated staff}
                            {--from= : Year-month YYYY-MM (default 2026-04)}
                            {--to= : Year-month YYYY-MM (default current month)}
                            {--staff= : Comma-separated staff IDs to limit scope}
                            {--include-inactive=1 : Include terminated staff (1=yes,0=no)}
                            {--dry-run : Default. Compute corrections without writing.}
                            {--confirm : Actually write the corrections + journals.}';

    protected $description = 'Apply correction slips for B/C 区分 payroll income_tax shortfall (Sprint 4 → Sprint 6 P1A).';

    public function handle(BackfillBcPayrollService $svc): int
    {
        $mode = (string) $this->option('mode');
        if (! $mode) {
            $this->error('--mode is required. Pass either --mode=staff-deduction or --mode=company-bears.');
            return self::FAILURE;
        }

        $dryRun = ! (bool) $this->option('confirm');
        if ($this->option('dry-run') && $this->option('confirm')) {
            $this->error('Pass either --dry-run or --confirm, not both.');
            return self::FAILURE;
        }

        $opts = [
            'mode'              => $mode,
            'inactive_mode'     => (string) $this->option('inactive-mode'),
            'from'              => $this->option('from') ?: '2026-04',
            'to'                => $this->option('to')   ?: now()->format('Y-m'),
            'dry_run'           => $dryRun,
            'include_inactive'  => (bool) $this->option('include-inactive'),
            'staff_ids'         => $this->parseStaffIds(),
        ];

        if (! preg_match('/^\d{4}-\d{2}$/', $opts['from']) || ! preg_match('/^\d{4}-\d{2}$/', $opts['to'])) {
            $this->error('Invalid --from/--to. Expected YYYY-MM.');
            return self::FAILURE;
        }

        $this->line('================================================================');
        $this->line('B/C 源泉徴収バックフィル' . ($dryRun ? ' (DRY-RUN)' : ' (LIVE — WRITES)'));
        $this->line('================================================================');
        $this->line(' mode               : ' . $opts['mode']);
        $this->line(' inactive_mode      : ' . $opts['inactive_mode']);
        $this->line(' window             : ' . $opts['from'] . ' → ' . $opts['to']);
        $this->line(' include_inactive   : ' . ($opts['include_inactive'] ? 'yes' : 'no'));
        if ($opts['staff_ids']) {
            $this->line(' staff filter       : ' . implode(',', $opts['staff_ids']));
        }

        if (! $dryRun) {
            if (! $this->confirm('LIVE mode — correction slips and (for company-bears) journals will be written. Continue?', false)) {
                $this->warn('Aborted by operator.');
                return self::FAILURE;
            }
        }

        try {
            $stats = $svc->backfill($opts);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('================================================================');
        $this->line(' processed slips                : ' . $stats['processed']);
        $this->line(' created correction slips       : ' . $stats['created_corrections']);
        $this->line(' skipped (already corrected)    : ' . $stats['skipped_already_corrected']);
        $this->line(' skipped (no shortfall)         : ' . $stats['skipped_no_shortfall']);
        $this->line(' skipped (class A or other)     : ' . $stats['skipped_class_a']);
        $this->line(' total shortfall to correct (¥) : ¥' . number_format($stats['total_shortfall']));
        $this->line(' errors                         : ' . count($stats['errors']));
        $this->line('================================================================');

        if (! empty($stats['per_staff'])) {
            $rows = [];
            foreach ($stats['per_staff'] as $row) {
                $rows[] = [$row['staff_id'], $row['slips'], '¥' . number_format($row['shortfall'])];
            }
            $this->table(['staff_id', 'slips', 'shortfall'], $rows);
        }

        foreach ($stats['errors'] as $msg) {
            $this->error('  ✗ ' . $msg);
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY-RUN — no slips were written. Re-run with --confirm to apply.');
        } else {
            $this->info('Backfill applied. Audit log captured every correction.');
        }

        return empty($stats['errors']) ? self::SUCCESS : self::FAILURE;
    }

    /** @return array<int,int>|null */
    protected function parseStaffIds(): ?array
    {
        $raw = $this->option('staff');
        if (! $raw) {
            return null;
        }
        $ids = array_values(array_filter(array_map('intval', explode(',', $raw))));
        return $ids ?: null;
    }
}

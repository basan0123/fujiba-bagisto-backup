<?php

namespace Fujiba\Accounting\Console\Commands;

use Fujiba\Accounting\Models\PayrollSlip;
use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Services\JournalPoster;
use Fujiba\Accounting\Services\Payroll\PayrollCalculator;
use Fujiba\Accounting\Services\Payroll\PayrollJournalBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

class RunPayroll extends Command
{
    protected $signature = 'accounting:run-payroll
                            {--year-month= : YYYY-MM (default: previous month)}
                            {--staff= : Comma-separated staff IDs (default: all active)}
                            {--pay-date= : Pay date (default: 25th of the year_month)}
                            {--finalize : Also post the journal entry (default: leaves slips as drafts)}
                            {--dry-run : Show what would happen without writing}';

    protected $description = 'Calculate (and optionally finalize) payroll slips for a month.';

    public function handle(
        PayrollCalculator $calc,
        PayrollJournalBuilder $builder,
        JournalPoster $poster,
    ): int {
        $ym = $this->option('year-month') ?: now()->subMonth()->format('Y-m');
        if (! preg_match('/^\d{4}-\d{2}$/', $ym)) {
            $this->error("Invalid --year-month: {$ym}");
            return self::FAILURE;
        }

        $payDate = $this->option('pay-date')
            ? Carbon::parse($this->option('pay-date'))
            : Carbon::parse("{$ym}-25");

        $staffQuery = Staff::where('is_active', true);
        if ($this->option('staff')) {
            $ids = array_filter(array_map('intval', explode(',', (string) $this->option('staff'))));
            $staffQuery->whereIn('id', $ids);
        }
        $staff = $staffQuery->get();
        $this->line("Computing payroll for {$staff->count()} staff member(s) — {$ym}");

        $calculated = 0;
        $finalized = 0;
        $errors = [];

        foreach ($staff as $s) {
            try {
                if ($this->option('dry-run')) {
                    // Don't persist; show what would compute.
                    $this->line(" - {$s->staff_code} {$s->name_ja}: would calculate slip for {$ym}");
                    $calculated++;
                    continue;
                }

                $slip = $calc->calculate($s, $ym, $payDate);
                $calculated++;
                $this->line(" ✓ {$s->staff_code} {$s->name_ja}: gross ¥" . number_format((int) $slip->gross_pay) . " net ¥" . number_format((int) $slip->net_pay));

                if ($this->option('finalize') && $slip->status === 'draft' && (int) $slip->gross_pay > 0) {
                    $entry = $poster->post($builder->build($slip));
                    $slip->update([
                        'journal_entry_id' => $entry->id,
                        'status'           => 'finalized',
                        'finalized_at'     => now(),
                    ]);
                    $finalized++;
                    $this->info("   → finalized as {$entry->entry_no}");
                }
            } catch (Throwable $e) {
                $errors[$s->id] = $e->getMessage();
                $this->error(" ✗ {$s->staff_code} {$s->name_ja}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->table(
            ['calculated', 'finalized', 'errors'],
            [[$calculated, $finalized, count($errors)]],
        );

        return empty($errors) ? self::SUCCESS : self::FAILURE;
    }
}

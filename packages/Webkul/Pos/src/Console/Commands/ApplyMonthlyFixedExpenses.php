<?php

namespace Webkul\Pos\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Webkul\Pos\Repositories\FixedExpenseApplicationRepository;
use Webkul\Pos\Repositories\FixedExpenseRepository;

class ApplyMonthlyFixedExpenses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:apply-fixed-expenses {--month= : Month in YYYY-MM format (default: current month)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-apply active fixed expenses for the given month (default: current month)';

    /**
     * Create a new command instance.
     */
    public function __construct(
        protected FixedExpenseRepository $fixedExpenseRepository,
        protected FixedExpenseApplicationRepository $fixedExpenseApplicationRepository
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $month = $this->option('month') ?? Carbon::now()->format('Y-m');

        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $this->error("Invalid month format: {$month}. Use YYYY-MM.");

            return self::FAILURE;
        }

        $expenses = $this->fixedExpenseRepository->findWhere(['is_active' => 1]);
        $applied  = 0;
        $skipped  = 0;

        foreach ($expenses as $expense) {
            $exists = $this->fixedExpenseApplicationRepository->findWhere([
                'fixed_expense_id' => $expense->id,
                'month'            => $month,
            ])->isNotEmpty();

            if ($exists) {
                $skipped++;

                continue;
            }

            $this->fixedExpenseApplicationRepository->create([
                'fixed_expense_id' => $expense->id,
                'outlet_id'        => $expense->outlet_id,
                'month'            => $month,
                'applied_amount'   => $expense->amount,
                'status'           => 'auto',
                'applied_at'       => now(),
            ]);

            $applied++;
        }

        $this->info("Fixed expenses for {$month}: {$applied} applied, {$skipped} skipped (already existed).");

        return self::SUCCESS;
    }
}

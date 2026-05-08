<?php

namespace Fujiba\Accounting\Services\Reports;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 貸借対照表 (B/S) as of a given date.
 *
 * Asset / Liability / Equity buckets use the Sprint 1 COA `sub_type`:
 *   current_asset / fixed_asset / intangible_asset / investment_asset
 *   current_liability / fixed_liability
 *   capital / reserve
 *
 * Retained earnings: we compute the running net income from inception →
 * as_of by summing all revenue / expense balances and adding the result
 * to the explicit `3220 繰越利益剰余金` account. This means the B/S
 * stays self-consistent even before a 決算 closing entry runs.
 */
class BsCalculator
{
    public function __construct(
        protected TrialBalanceCalculator $tb,
    ) {}

    public function calculate(CarbonInterface $asOf, ?int $channelId = null): array
    {
        $tb = $this->tb->calculate(Carbon::create(1970, 1, 1)->startOfDay(), $asOf, $channelId);
        $rows = $tb['rows'];

        $bucket = fn (string $subType) => array_values(array_filter($rows, fn ($r) => $r['sub_type'] === $subType));
        $sumBalance = fn (array $rs) => (int) array_sum(array_column($rs, 'balance'));

        $currentAssets    = $bucket('current_asset');
        $fixedAssets      = array_merge($bucket('fixed_asset'), $bucket('intangible_asset'), $bucket('investment_asset'));

        $currentLiab      = $bucket('current_liability');
        $fixedLiab        = $bucket('fixed_liability');

        $capital          = $bucket('capital');
        $reserve          = $bucket('reserve');

        // Running net income (revenue − expense) for everything ever posted, to
        // capture not-yet-closed earnings into retained earnings on the B/S.
        $runningNetIncome = 0;
        foreach ($rows as $r) {
            if (in_array($r['type'], ['revenue', 'expense'], true)) {
                $sign = $r['type'] === 'revenue' ? 1 : -1;
                $runningNetIncome += $sign * $r['balance'];
            }
        }

        $totalCurrentAssets = $sumBalance($currentAssets);
        $totalFixedAssets   = $sumBalance($fixedAssets);
        $totalAssets        = $totalCurrentAssets + $totalFixedAssets;

        $totalCurrentLiab   = $sumBalance($currentLiab);
        $totalFixedLiab     = $sumBalance($fixedLiab);
        $totalLiab          = $totalCurrentLiab + $totalFixedLiab;

        $totalCapital       = $sumBalance($capital);
        $totalReserve       = $sumBalance($reserve);
        $totalEquity        = $totalCapital + $totalReserve + $runningNetIncome;

        $totalLiabEquity    = $totalLiab + $totalEquity;

        return [
            'as_of' => $asOf->toDateString(),
            'assets' => [
                'current' => ['rows' => $currentAssets, 'total' => $totalCurrentAssets],
                'fixed'   => ['rows' => $fixedAssets,   'total' => $totalFixedAssets],
                'total'   => $totalAssets,
            ],
            'liabilities' => [
                'current' => ['rows' => $currentLiab, 'total' => $totalCurrentLiab],
                'fixed'   => ['rows' => $fixedLiab,   'total' => $totalFixedLiab],
                'total'   => $totalLiab,
            ],
            'equity' => [
                'capital'  => ['rows' => $capital, 'total' => $totalCapital],
                'reserve'  => ['rows' => $reserve, 'total' => $totalReserve],
                'retained_running' => $runningNetIncome,
                'total'    => $totalEquity,
            ],
            'total_liab_and_equity' => $totalLiabEquity,
            'balanced'              => $totalAssets === $totalLiabEquity,
        ];
    }
}

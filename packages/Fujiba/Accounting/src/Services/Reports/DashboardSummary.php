<?php

namespace Fujiba\Accounting\Services\Reports;

use Fujiba\Accounting\Models\Timecard;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Numbers powering the dashboard's "当月サマリー" widget.
 * Cached for `accounting.dashboard.cache_ttl_seconds` (default 1 h).
 */
class DashboardSummary
{
    public function get(?int $channelId = null, bool $bypassCache = false): array
    {
        $key = config('accounting.dashboard.cache_prefix', 'fujiba:accounting:report:')
             . 'summary:' . ($channelId ?? 'all');
        $ttl = (int) config('accounting.dashboard.cache_ttl_seconds', 3600);

        $compute = fn () => $this->compute($channelId);

        if ($bypassCache || $ttl <= 0) {
            return $compute();
        }
        return Cache::remember($key, $ttl, $compute);
    }

    protected function compute(?int $channelId): array
    {
        $now = Carbon::now();
        $thisMonth = ['from' => $now->copy()->startOfMonth(), 'to' => $now->copy()->endOfMonth()];
        $lastMonth = ['from' => $now->copy()->subMonth()->startOfMonth(), 'to' => $now->copy()->subMonth()->endOfMonth()];

        $sales      = $this->revenueOrCogs($thisMonth, 'sales',     $channelId);
        $cogs       = $this->revenueOrCogs($thisMonth, 'cogs',      $channelId);
        $salesPrev  = $this->revenueOrCogs($lastMonth, 'sales',     $channelId);
        $cogsPrev   = $this->revenueOrCogs($lastMonth, 'cogs',      $channelId);

        $unapprovedTimecards = Timecard::whereIn('status', ['open', 'closed', 'edited'])
            ->whereDate('work_date', '<', $now->toDateString())
            ->count();

        return [
            'computed_at' => $now->toIso8601String(),
            'period'      => $now->format('Y-m'),
            'sales'       => $sales,
            'cogs'        => $cogs,
            'gross'       => $sales - $cogs,
            'sales_prev'  => $salesPrev,
            'cogs_prev'   => $cogsPrev,
            'gross_prev'  => $salesPrev - $cogsPrev,
            'sales_pct'   => $this->pctChange($salesPrev, $sales),
            'cogs_pct'    => $this->pctChange($cogsPrev, $cogs),
            'gross_pct'   => $this->pctChange($salesPrev - $cogsPrev, $sales - $cogs),
            'unapproved_timecards' => $unapprovedTimecards,
        ];
    }

    protected function revenueOrCogs(array $window, string $subType, ?int $channelId): int
    {
        $q = DB::table('fujiba_journal_lines as jl')
            ->join('fujiba_journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('fujiba_accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.status', 'posted')
            ->whereBetween('je.entry_date', [$window['from']->toDateString(), $window['to']->toDateString()])
            ->where('a.sub_type', $subType);

        if ($channelId !== null) {
            $q->where('je.channel_id', $channelId);
        }

        // Revenue (sales) sits CR-positive; expense (cogs) sits DR-positive.
        if ($subType === 'sales') {
            return (int) round((float) ($q->sum(DB::raw('jl.credit - jl.debit'))));
        }
        return (int) round((float) ($q->sum(DB::raw('jl.debit - jl.credit'))));
    }

    protected function pctChange(int $prev, int $current): ?float
    {
        if ($prev === 0) {
            return $current === 0 ? 0.0 : null;   // null = "n/a" (no baseline)
        }
        return round((($current - $prev) / abs($prev)) * 100.0, 1);
    }
}

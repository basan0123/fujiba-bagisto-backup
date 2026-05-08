<?php

namespace Fujiba\Accounting\Services\Reports;

use Fujiba\Accounting\Models\Account;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * 総勘定元帳 — for one account, list every line within the period plus
 * the opening balance (everything posted before $from) and a running balance.
 */
class LedgerCalculator
{
    public function calculate(string $accountCode, CarbonInterface $from, CarbonInterface $to, ?int $channelId = null): array
    {
        $account = Account::where('code', $accountCode)->first();
        if (! $account) {
            throw new \InvalidArgumentException("Unknown account code: {$accountCode}");
        }

        $sign = in_array($account->type, ['asset', 'expense'], true) ? 1 : -1;

        // Opening balance: everything posted strictly before $from
        $openingQ = DB::table('fujiba_journal_lines as jl')
            ->join('fujiba_journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->where('jl.account_id', $account->id)
            ->where('je.status', 'posted')
            ->whereDate('je.entry_date', '<', $from->toDateString());
        if ($channelId !== null) {
            $openingQ->where('je.channel_id', $channelId);
        }
        $opening = (int) round((float) ($openingQ->sum(DB::raw('jl.debit - jl.credit'))));
        $opening = $sign * $opening;

        // Period lines
        $linesQ = DB::table('fujiba_journal_lines as jl')
            ->join('fujiba_journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->where('jl.account_id', $account->id)
            ->where('je.status', 'posted')
            ->whereBetween('je.entry_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('je.entry_date')
            ->orderBy('je.id')
            ->orderBy('jl.line_no')
            ->select([
                'je.entry_no', 'je.entry_date', 'je.memo as entry_memo', 'je.source_type',
                'jl.debit', 'jl.credit', 'jl.memo as line_memo',
            ]);
        if ($channelId !== null) {
            $linesQ->where('je.channel_id', $channelId);
        }

        $running = $opening;
        $totalDr = 0;
        $totalCr = 0;
        $rows = [];

        foreach ($linesQ->get() as $r) {
            $dr = (int) round((float) $r->debit);
            $cr = (int) round((float) $r->credit);
            $running += $sign * ($dr - $cr);
            $totalDr += $dr;
            $totalCr += $cr;
            $rows[] = [
                'entry_no'    => $r->entry_no,
                'entry_date'  => $r->entry_date,
                'source_type' => $r->source_type,
                'memo'        => $r->line_memo ?: $r->entry_memo,
                'debit'       => $dr,
                'credit'      => $cr,
                'running'     => $running,
            ];
        }

        return [
            'account' => [
                'code'    => $account->code,
                'name_ja' => $account->name_ja,
                'type'    => $account->type,
            ],
            'from'           => $from->toDateString(),
            'to'             => $to->toDateString(),
            'opening'        => $opening,
            'rows'           => $rows,
            'period_debit'   => $totalDr,
            'period_credit'  => $totalCr,
            'closing'        => $running,
        ];
    }
}

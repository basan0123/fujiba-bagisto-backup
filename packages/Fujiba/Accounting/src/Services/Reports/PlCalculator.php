<?php

namespace Fujiba\Accounting\Services\Reports;

use Carbon\CarbonInterface;

/**
 * 損益計算書 — Japanese standard format:
 *
 *   売上高 (sales)
 *   − 売上原価 (cogs)
 *   = 売上総利益 (gross_profit)
 *   − 販売費及び一般管理費 (sga)
 *   = 営業利益 (operating)
 *   + 営業外収益 (non_op_revenue)
 *   − 営業外費用 (non_op_expense)
 *   = 経常利益 (ordinary)
 *   + 特別利益 (special_revenue)
 *   − 特別損失 (special_expense)
 *   = 税引前当期純利益 (pretax)
 *
 * Group lookup uses Account.sub_type from Sprint 1's COA seeder:
 *   sales / cogs / sga / non_op_revenue / non_op_expense / special_revenue / special_expense
 */
class PlCalculator
{
    public function __construct(
        protected TrialBalanceCalculator $tb,
    ) {}

    public function calculate(CarbonInterface $from, CarbonInterface $to, ?int $channelId = null): array
    {
        $tb = $this->tb->calculate($from, $to, $channelId);
        $rows = $tb['rows'];

        $bucket = fn (string $subType) => array_values(array_filter($rows, fn ($r) => $r['sub_type'] === $subType));
        $sumBalance = fn (array $rs) => (int) array_sum(array_column($rs, 'balance'));

        $sales         = $bucket('sales');
        $cogs          = $bucket('cogs');
        $sga           = $bucket('sga');
        $nonOpRev      = $bucket('non_op_revenue');
        $nonOpExp      = $bucket('non_op_expense');
        $specialRev    = $bucket('special_revenue');
        $specialExp    = $bucket('special_expense');

        $salesTotal     = $sumBalance($sales);
        $cogsTotal      = $sumBalance($cogs);
        $grossProfit    = $salesTotal - $cogsTotal;
        $sgaTotal       = $sumBalance($sga);
        $operating      = $grossProfit - $sgaTotal;
        $nonOpRevTotal  = $sumBalance($nonOpRev);
        $nonOpExpTotal  = $sumBalance($nonOpExp);
        $ordinary       = $operating + $nonOpRevTotal - $nonOpExpTotal;
        $specialRevTotal= $sumBalance($specialRev);
        $specialExpTotal= $sumBalance($specialExp);
        $pretax         = $ordinary + $specialRevTotal - $specialExpTotal;

        return [
            'from' => $from->toDateString(),
            'to'   => $to->toDateString(),
            'sections' => [
                'sales'           => ['rows' => $sales,        'total' => $salesTotal],
                'cogs'            => ['rows' => $cogs,         'total' => $cogsTotal],
                'gross_profit'    => ['total' => $grossProfit],
                'sga'             => ['rows' => $sga,          'total' => $sgaTotal],
                'operating'       => ['total' => $operating],
                'non_op_revenue'  => ['rows' => $nonOpRev,     'total' => $nonOpRevTotal],
                'non_op_expense'  => ['rows' => $nonOpExp,     'total' => $nonOpExpTotal],
                'ordinary'        => ['total' => $ordinary],
                'special_revenue' => ['rows' => $specialRev,   'total' => $specialRevTotal],
                'special_expense' => ['rows' => $specialExp,   'total' => $specialExpTotal],
                'pretax'          => ['total' => $pretax],
            ],
        ];
    }
}

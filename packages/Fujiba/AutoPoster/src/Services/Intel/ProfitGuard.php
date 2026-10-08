<?php

namespace Fujiba\AutoPoster\Services\Intel;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * N — Profit Guard. Decides whether a generation is worth its cost.
 *
 *   expected_value = reach × ctr × cvr × aov × margin − generation_cost
 *
 * The system optimises profit, not post count, so the default answer to an
 * expensive generation for a product nobody buys is NO.
 *
 * Every decision — allow or block, with its inputs — is written to
 * fi_profit_guard_log. There is no silent generation path.
 */
class ProfitGuard
{
    public const BLOCK_OUT_OF_STOCK = 'product is out of stock';
    public const BLOCK_NEGATIVE_EV = 'expected value is negative';
    public const BLOCK_LOW_ROI = 'ROI below configured minimum';
    public const BLOCK_BUDGET = 'monthly budget would be exceeded';
    public const BLOCK_DUPLICATE = 'an approved asset for this concept already exists';

    /**
     * @param  array  $ctx  product_id, estimated_cost_jpy, operation, concept,
     *                      and optionally measured reach/ctr/cvr/aov/margin
     * @return array{allowed:bool,reason:?string,expected_value:float,roi:?float,low_data:bool,inputs:array}
     */
    public function evaluate(array $ctx): array
    {
        $cfg    = config('fujiba_intel.profit_guard');
        $priors = $cfg['priors'];

        $productId = $ctx['product_id'] ?? null;
        $cost      = (float) ($ctx['estimated_cost_jpy'] ?? 0.0);

        // Anything not measured falls back to a conservative prior, and the
        // whole decision is then flagged low_data so nobody mistakes a guess
        // for history.
        $lowData = false;
        $pick = function (string $key, $prior) use ($ctx, &$lowData) {
            if (array_key_exists($key, $ctx) && $ctx[$key] !== null) {
                return $ctx[$key];
            }
            $lowData = true;

            return $prior;
        };

        $reach  = (float) $pick('reach', $priors['reach']);
        $ctr    = (float) $pick('click_rate', $priors['click_rate']);
        $cvr    = (float) $pick('conversion', $priors['conversion']);
        $aov    = (float) $pick('aov_jpy', $priors['aov_jpy']);
        $margin = (float) $pick('margin', $priors['margin']);

        $expectedRevenue = $reach * $ctr * $cvr * $aov;
        $expectedProfit  = $expectedRevenue * $margin;
        $expectedValue   = $expectedProfit - $cost;
        $roi             = $cost > 0 ? $expectedProfit / $cost : null;

        $inputs = compact('reach', 'ctr', 'cvr', 'aov', 'margin', 'cost')
            + ['expected_revenue' => $expectedRevenue, 'expected_profit' => $expectedProfit];

        $reason = null;

        // Checks run cheapest-and-most-decisive first.
        if ($productId !== null && $this->isOutOfStock((int) $productId)) {
            $reason = self::BLOCK_OUT_OF_STOCK;
        } elseif (! empty($ctx['concept']) && $this->hasApprovedConcept((string) $ctx['concept'], $productId)) {
            $reason = self::BLOCK_DUPLICATE;
        } elseif ($cost > 0 && $this->wouldExceedBudget($cost)) {
            $reason = self::BLOCK_BUDGET;
        } elseif ($expectedValue < 0) {
            $reason = self::BLOCK_NEGATIVE_EV;
        } elseif ($roi !== null && $roi < (float) $cfg['min_roi']) {
            $reason = self::BLOCK_LOW_ROI;
        }

        $allowed = $reason === null;

        $this->log([
            'product_id'         => $productId,
            'creative_id'        => $ctx['creative_id'] ?? null,
            'operation'          => $ctx['operation'] ?? null,
            'allowed'            => $allowed,
            'reason'             => $reason,
            'inputs'             => $inputs,
            'expected_value_jpy' => $expectedValue,
            'estimated_cost_jpy' => $cost,
            'roi'                => $roi,
            'low_data'           => $lowData,
        ]);

        return [
            'allowed'        => $allowed,
            'reason'         => $reason,
            'expected_value' => $expectedValue,
            'roi'            => $roi,
            'low_data'       => $lowData,
            'inputs'         => $inputs,
        ];
    }

    protected function isOutOfStock(int $productId): bool
    {
        $qty = (int) DB::table('product_inventories')->where('product_id', $productId)->sum('qty');
        $reserved = (int) DB::table('product_ordered_inventories')->where('product_id', $productId)->sum('qty');

        return ($qty - $reserved) <= 0;
    }

    protected function hasApprovedConcept(string $concept, ?int $productId): bool
    {
        return DB::table('fi_creatives')
            ->where('approval_state', 'approved')
            ->where('concept', $concept)
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->exists();
    }

    /**
     * Budget is a hard stop, never raised automatically. Default is 0, so any
     * priced generation is refused until the owner sets a budget.
     */
    protected function wouldExceedBudget(float $cost): bool
    {
        $budget = (float) config('fujiba_intel.monthly_budget_jpy', 0);

        $spent = (float) DB::table('fi_ai_usage')
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->sum('cost_jpy');

        return ($spent + $cost) > $budget;
    }

    protected function log(array $row): void
    {
        DB::table('fi_profit_guard_log')->insert([
            'product_id'         => $row['product_id'],
            'creative_id'        => $row['creative_id'],
            'operation'          => $row['operation'],
            'allowed'            => $row['allowed'],
            'reason'             => $row['reason'],
            'inputs'             => json_encode($row['inputs'], JSON_UNESCAPED_UNICODE),
            'expected_value_jpy' => $row['expected_value_jpy'],
            'estimated_cost_jpy' => $row['estimated_cost_jpy'],
            'roi'                => $row['roi'],
            'low_data'           => $row['low_data'],
            'created_at'         => Carbon::now(),
            'updated_at'         => Carbon::now(),
        ]);
    }
}

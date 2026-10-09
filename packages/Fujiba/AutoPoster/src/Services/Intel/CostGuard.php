<?php

namespace Fujiba\AutoPoster\Services\Intel;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Every provider call passes through here: budget is checked BEFORE the call,
 * and the spend is logged after. The default budget is 0, so in Phase 1 any
 * priced operation is refused.
 *
 * The budget is never raised programmatically. There is no method to do it —
 * that is deliberate, not an omission.
 */
class CostGuard
{
    public function monthlyBudget(): float
    {
        return (float) config('fujiba_intel.monthly_budget_jpy', 0);
    }

    public function spentThisMonth(): float
    {
        return (float) DB::table('fi_ai_usage')
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->sum('cost_jpy');
    }

    public function remaining(): float
    {
        return max(0.0, $this->monthlyBudget() - $this->spentThisMonth());
    }

    public function wouldExceed(float $cost): bool
    {
        return ($this->spentThisMonth() + $cost) > $this->monthlyBudget();
    }

    /**
     * Run an operation under the budget. Throws rather than silently skipping,
     * so a caller cannot accidentally treat "refused" as "returned nothing".
     *
     * @throws RuntimeException when the budget would be exceeded
     */
    public function guard(string $operation, float $estimatedCost, callable $run, array $meta = []): array
    {
        if ($estimatedCost > 0 && $this->wouldExceed($estimatedCost)) {
            $this->log($operation, 0.0, true, $meta + ['refused' => 'budget']);

            throw new RuntimeException(sprintf(
                '[CostGuard] %s refused: ¥%.2f would exceed the ¥%.2f monthly budget (¥%.2f spent).',
                $operation, $estimatedCost, $this->monthlyBudget(), $this->spentThisMonth()
            ));
        }

        $result = $run();

        $this->log($operation, (float) ($result['cost_jpy'] ?? $estimatedCost),
            (bool) ($result['cache_hit'] ?? false), $meta);

        return $result;
    }

    public function log(string $operation, float $cost, bool $cacheHit = false, array $meta = []): void
    {
        DB::table('fi_ai_usage')->insert([
            'operation'    => $operation,
            'provider'     => $meta['provider'] ?? null,
            'model'        => $meta['model'] ?? null,
            'input_units'  => (int) ($meta['input_units'] ?? 0),
            'output_units' => (int) ($meta['output_units'] ?? 0),
            'cost_jpy'     => $cost,
            'cache_hit'    => $cacheHit,
            'creative_id'  => $meta['creative_id'] ?? null,
            'product_id'   => $meta['product_id'] ?? null,
            'created_at'   => Carbon::now(),
            'updated_at'   => Carbon::now(),
        ]);
    }
}

<?php

namespace Fujiba\CustomerRanks\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Auto-promotes customers into the "Kimono +3" loyalty group.
 *
 * Design (see Config/ranks.php):
 *   - Promotion only; customers are never demoted.
 *   - Customers in a protected group (wholesale, guest) are never touched.
 *   - Only customers currently in a promotable group (general) are eligible.
 *   - "Kimono order" = a completed order containing >=1 product whose category
 *     is anywhere in the configured kimono category subtree(s).
 *   - "3 times" = 3 distinct qualifying orders.
 *
 * The discount is NOT applied here — admin attaches a Catalog Rule to the group.
 */
class PromoteCustomerRankService
{
    /** @var array<string,mixed> */
    protected array $config;

    public function __construct()
    {
        $this->config = config('ranks.kimono_plus3');
    }

    /**
     * Evaluate a single customer and promote if eligible.
     * Safe to call repeatedly — it never demotes and is a no-op once promoted.
     *
     * @return bool true if the customer was promoted on this call.
     */
    public function evaluateCustomer(int $customerId): bool
    {
        $targetGroupId = $this->targetGroupId();

        if (! $targetGroupId) {
            Log::warning('[CustomerRanks] target group "'.$this->config['target_group_code'].'" not found; skipping.');

            return false;
        }

        $customer = DB::table('customers')
            ->where('id', $customerId)
            ->first(['id', 'customer_group_id']);

        if (! $customer) {
            return false;
        }

        // Already in the target group — nothing to do.
        if ((int) $customer->customer_group_id === $targetGroupId) {
            return false;
        }

        // Only promote customers currently in a promotable group.
        // This is what makes "Wholesale wins": wholesale is not promotable.
        if (! in_array((int) $customer->customer_group_id, $this->promotableGroupIds(), true)) {
            return false;
        }

        if ($this->qualifyingOrderCount($customerId) < (int) $this->config['threshold']) {
            return false;
        }

        DB::table('customers')
            ->where('id', $customerId)
            ->update([
                'customer_group_id' => $targetGroupId,
                'updated_at'        => now(),
            ]);

        Log::info('[CustomerRanks] promoted customer '.$customerId.' to '.$this->config['target_group_code']);

        return true;
    }

    /**
     * Backfill: evaluate every promotable customer that already qualifies.
     * Returns the number of customers promoted.
     */
    public function backfill(?callable $progress = null): int
    {
        $targetGroupId = $this->targetGroupId();

        if (! $targetGroupId) {
            return 0;
        }

        $kimonoCategoryIds = $this->kimonoCategoryIds();

        if (empty($kimonoCategoryIds)) {
            return 0;
        }

        // One set-based query finds every promotable customer who already has
        // >= threshold distinct qualifying orders. Far cheaper than per-customer.
        $eligible = DB::table('orders as o')
            ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->join('product_categories as pc', 'pc.product_id', '=', 'oi.product_id')
            ->join('customers as c', 'c.id', '=', 'o.customer_id')
            ->whereIn('pc.category_id', $kimonoCategoryIds)
            ->whereIn('o.status', $this->config['qualifying_order_statuses'])
            ->whereNotNull('o.customer_id')
            ->whereIn('c.customer_group_id', $this->promotableGroupIds())
            ->groupBy('o.customer_id')
            ->havingRaw('COUNT(DISTINCT o.id) >= ?', [(int) $this->config['threshold']])
            ->pluck('o.customer_id');

        $promoted = 0;

        foreach ($eligible as $customerId) {
            DB::table('customers')
                ->where('id', $customerId)
                ->whereIn('customer_group_id', $this->promotableGroupIds())
                ->update([
                    'customer_group_id' => $targetGroupId,
                    'updated_at'        => now(),
                ]);

            $promoted++;

            if ($progress) {
                $progress($customerId);
            }
        }

        return $promoted;
    }

    /**
     * Count distinct qualifying (completed, kimono-containing) orders.
     */
    public function qualifyingOrderCount(int $customerId): int
    {
        $kimonoCategoryIds = $this->kimonoCategoryIds();

        if (empty($kimonoCategoryIds)) {
            return 0;
        }

        return (int) DB::table('orders as o')
            ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->join('product_categories as pc', 'pc.product_id', '=', 'oi.product_id')
            ->where('o.customer_id', $customerId)
            ->whereIn('o.status', $this->config['qualifying_order_statuses'])
            ->whereIn('pc.category_id', $kimonoCategoryIds)
            ->distinct()
            ->count('o.id');
    }

    /**
     * Expand the configured root kimono categories to their full nested-set
     * subtrees, so kimono sub-categories added in admin are picked up
     * automatically. Cached for the lifetime of the request/job.
     *
     * @return array<int,int>
     */
    public function kimonoCategoryIds(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $roots = DB::table('categories')
            ->whereIn('id', $this->config['kimono_root_category_ids'])
            ->get(['_lft', '_rgt']);

        if ($roots->isEmpty()) {
            return $cache = [];
        }

        $query = DB::table('categories');

        foreach ($roots as $root) {
            $query->orWhereBetween('_lft', [$root->_lft, $root->_rgt]);
        }

        return $cache = $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    protected function targetGroupId(): ?int
    {
        $id = DB::table('customer_groups')
            ->where('code', $this->config['target_group_code'])
            ->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * @return array<int,int>
     */
    protected function promotableGroupIds(): array
    {
        return DB::table('customer_groups')
            ->whereIn('code', $this->config['promotable_from_group_codes'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}

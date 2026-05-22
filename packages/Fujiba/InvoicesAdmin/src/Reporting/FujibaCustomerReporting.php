<?php

namespace Fujiba\InvoicesAdmin\Reporting;

use Illuminate\Support\Facades\DB;
use Webkul\Admin\Helpers\Reporting\Customer as CoreCustomerReporting;
use Illuminate\Database\Eloquent\Collection;

/**
 * Top-customers reporting overrides.
 *
 * The core Webkul helper groups customer sales/orders by
 * `CONCAT(customer_email, "-", customer_id)`. On Fujiba a large share of
 * orders are ChatPion / Messenger / POS guest orders that carry a NULL
 * `customer_email`. In MySQL `CONCAT(NULL, ...)` evaluates to NULL, so
 * *every* NULL-email order collapses into a single GROUP BY bucket — dozens
 * of distinct buyers merged into one phantom "customer". Because the app
 * connection runs with `sql_mode` strict=false (ONLY_FULL_GROUP_BY off),
 * MySQL then picks the name/email/id from an arbitrary row in that bucket,
 * so the Dashboard's "売上最多の顧客" widget showed e.g. a buyer with one
 * ¥5,400 order credited with 90+ orders and the merged total of everyone
 * who happened to lack an email.
 *
 * We re-key the grouping on a NULL-safe per-buyer identity:
 *   - prefer customer_id,
 *   - else the email,
 *   - else the order id (so a truly anonymous one-off never merges with
 *     another anonymous one-off).
 * The displayed name/email are taken via MIN() so the row is at least
 * deterministic instead of arbitrary.
 *
 * Mirrors the FujibaProductReporting override pattern; bound over
 * {@see CoreCustomerReporting} in InvoicesAdminServiceProvider.
 */
class FujibaCustomerReporting extends CoreCustomerReporting
{
    public function getCustomersWithMostSales($limit = null): Collection
    {
        $tablePrefix = DB::getTablePrefix();

        return $this->orderRepository
            ->resetModel()
            ->addSelect(
                DB::raw('MAX('.$tablePrefix.'orders.customer_id) as id'),
                DB::raw('MIN('.$tablePrefix.'orders.customer_email) as email'),
                DB::raw('MIN(CONCAT('.$tablePrefix.'orders.customer_first_name, " ", '.$tablePrefix.'orders.customer_last_name)) as full_name'),
                DB::raw('SUM(base_grand_total_invoiced - base_grand_total_refunded) as total'),
                DB::raw('COUNT(*) as orders')
            )
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween($this->getDateColumn(), [$this->startDate, $this->endDate])
            ->groupBy(DB::raw($this->buyerIdentityKey($tablePrefix)))
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    public function getCustomersWithMostOrders($limit = null): Collection
    {
        $tablePrefix = DB::getTablePrefix();

        return $this->orderRepository
            ->resetModel()
            ->addSelect(
                DB::raw('MAX('.$tablePrefix.'orders.customer_id) as id'),
                DB::raw('MIN('.$tablePrefix.'orders.customer_email) as email'),
                DB::raw('MIN(CONCAT('.$tablePrefix.'orders.customer_first_name, " ", '.$tablePrefix.'orders.customer_last_name)) as full_name'),
                DB::raw('COUNT(*) as orders')
            )
            ->whereIn('channel_id', $this->channelIds)
            ->whereBetween($this->getDateColumn(), [$this->startDate, $this->endDate])
            ->groupBy(DB::raw($this->buyerIdentityKey($tablePrefix)))
            ->orderByDesc('orders')
            ->limit($limit)
            ->get();
    }

    /**
     * NULL-safe per-buyer grouping key. COALESCE picks the first non-null of
     * customer_id → email → the order id, each namespaced so a numeric
     * customer_id can't collide with an order id. The result is never NULL,
     * so guest orders no longer collapse into one bucket.
     */
    protected function buyerIdentityKey(string $tablePrefix): string
    {
        return 'COALESCE('
            ."CONCAT('c:', {$tablePrefix}orders.customer_id), "
            ."CONCAT('e:', {$tablePrefix}orders.customer_email), "
            ."CONCAT('o:', {$tablePrefix}orders.id)"
            .')';
    }
}

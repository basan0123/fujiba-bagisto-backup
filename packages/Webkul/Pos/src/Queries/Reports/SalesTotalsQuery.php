<?php

namespace Webkul\Pos\Queries\Reports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Pos\Helpers\RoleGate;

/**
 * getSalesTotals — headline totals for the Reports「集計」tab and the Today
 * dashboard summary. Returns date-ranged sales/profit/orderCount split by
 * SOURCE, plus a point-in-time stock-value snapshot (ignores the date range).
 *
 * Source semantics (decided with the merchant):
 *   - pos      → orders with a pos_order row + a POS payment method
 *                (pos_cash / pos_card / pos_split) — rung up at the register.
 *   - website  → orders on THIS outlet's channel that have NO pos_order row —
 *                i.e. placed on the storefront, not the register.
 *   - all      → both of the above (union by order id).
 *
 * Profit & stockValue are management figures: null for cashiers (RoleGate),
 * same gate as the product-editor cost and getInvoiceStats. Cost basis is the
 * product 'cost' attribute (attribute_id 12), consistent with the rest of POS.
 */
class SalesTotalsQuery
{
    /** product_attribute_values.attribute_id for the 'cost' attribute. */
    private const COST_ATTRIBUTE_ID = 12;

    private const DEFAULT_DAYS = 30;

    private const COMPLETED_STATUSES = ['complete', 'completed', 'processing'];

    private const POS_PAYMENT_METHODS = ['pos_cash', 'pos_card', 'pos_split'];

    public function __invoke($root, array $args, GraphQLContext $context): array
    {
        $user     = $context->user();
        $outletId = (int) $user->outlet_id;
        $channelId = $user->outlet?->channel_id;

        $source = in_array($args['source'] ?? 'all', ['pos', 'website', 'all'], true)
            ? $args['source']
            : 'all';

        [$from, $to] = $this->range($args);

        $canSeeProfit = RoleGate::atLeast($user, RoleGate::MANAGER);

        // Order ids in scope for the chosen source + date range. We resolve to a
        // concrete id set first, then aggregate revenue and (manager-only) cost
        // off the same set so the two never drift.
        $orderIds = $this->scopedOrderIds($source, $outletId, $channelId, $from, $to);

        $orderCount = count($orderIds);
        $itemCount = 0;
        $revenue = 0.0;
        $profit = null;
        $cost = null;

        if ($orderCount > 0) {
            $revenue = (float) DB::table('orders')
                ->whereIn('id', $orderIds)
                ->sum(DB::raw('grand_total - COALESCE(grand_total_refunded, 0)'));
            $revenue = round($revenue, 2);

            // Total units sold (net of refunds) across the in-scope orders.
            // parent_id NULL avoids double-counting configurable/bundle child
            // rows, mirroring the cost query below.
            $itemCount = (int) DB::table('order_items')
                ->whereIn('order_id', $orderIds)
                ->whereNull('parent_id')
                ->sum(DB::raw('qty_ordered - COALESCE(qty_refunded, 0)'));

            if ($canSeeProfit) {
                $costRow = DB::table('order_items')
                    ->leftJoin('product_attribute_values as pav', function ($j) {
                        $j->on('pav.product_id', '=', 'order_items.product_id')
                          ->where('pav.attribute_id', '=', self::COST_ATTRIBUTE_ID);
                    })
                    ->whereIn('order_items.order_id', $orderIds)
                    ->whereNull('order_items.parent_id')
                    ->selectRaw('COALESCE(SUM(order_items.qty_ordered * COALESCE(pav.float_value,0)),0) as cost')
                    ->first();

                $cost   = round((float) ($costRow->cost ?? 0), 2);
                $profit = round($revenue - $cost, 2);
            }
        } elseif ($canSeeProfit) {
            $cost = 0.0;
            $profit = 0.0;
        }

        return [
            'source'        => $source,
            'orderCount'    => $orderCount,
            'itemCount'     => $itemCount,
            'revenue'       => $revenue,
            'cost'          => $cost,
            'profit'        => $profit,
            'stockValue'    => $canSeeProfit ? $this->stockValue($user) : null,
            'canSeeProfit'  => $canSeeProfit,
        ];
    }

    /**
     * Resolve the in-scope order ids for the source + date range. Returns a
     * plain array of ints (deduped).
     */
    private function scopedOrderIds(string $source, int $outletId, $channelId, Carbon $from, Carbon $to): array
    {
        // POS leg: orders rung up at this outlet's register.
        $posQuery = DB::table('pos_order as po')
            ->join('orders as o', 'o.id', '=', 'po.order_id')
            ->where('po.outlet_id', $outletId)
            ->whereIn('o.status', self::COMPLETED_STATUSES)
            ->whereBetween('o.created_at', [$from, $to])
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('order_payment')
                  ->whereColumn('order_payment.order_id', 'po.order_id')
                  ->whereIn('order_payment.method', self::POS_PAYMENT_METHODS);
            })
            ->select('o.id');

        // Website leg: orders on this outlet's channel that were NOT rung up at
        // a register (no pos_order row). Requires a known channel.
        $webQuery = null;
        if ($channelId !== null) {
            $webQuery = DB::table('orders as o')
                ->where('o.channel_id', $channelId)
                ->whereIn('o.status', self::COMPLETED_STATUSES)
                ->whereBetween('o.created_at', [$from, $to])
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                      ->from('pos_order')
                      ->whereColumn('pos_order.order_id', 'o.id');
                })
                ->select('o.id');
        }

        $ids = match ($source) {
            'pos'     => $posQuery->pluck('id'),
            'website' => $webQuery ? $webQuery->pluck('id') : collect(),
            default   => $webQuery
                ? $posQuery->pluck('id')->merge($webQuery->pluck('id'))
                : $posQuery->pluck('id'),
        };

        return $ids->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /**
     * Current stock value for the outlet = Σ (on-hand qty at the outlet's
     * inventory source) × (product cost attribute), over products assigned to
     * this outlet. Point-in-time; not affected by the report date range.
     */
    private function stockValue($user): float
    {
        $sourceId = $user->outlet?->inventory_source_id;
        if (! $sourceId) {
            return 0.0;
        }

        $row = DB::table('pos_outlet_product as op')
            ->join('product_inventories as pi', function ($j) use ($sourceId) {
                $j->on('pi.product_id', '=', 'op.product_id')
                  ->where('pi.inventory_source_id', '=', $sourceId);
            })
            ->leftJoin('product_attribute_values as pav', function ($j) {
                $j->on('pav.product_id', '=', 'op.product_id')
                  ->where('pav.attribute_id', '=', self::COST_ATTRIBUTE_ID);
            })
            ->where('op.outlet_id', $user->outlet_id)
            ->selectRaw('COALESCE(SUM(pi.qty * COALESCE(pav.float_value,0)),0) as stock_value')
            ->first();

        return round((float) ($row->stock_value ?? 0), 2);
    }

    /**
     * Inclusive date range; defaults to the last 30 days when not supplied.
     */
    private function range(array $args): array
    {
        $to = ! empty($args['dateTo'])
            ? Carbon::parse($args['dateTo'])->endOfDay()
            : Carbon::now()->endOfDay();

        $from = ! empty($args['dateFrom'])
            ? Carbon::parse($args['dateFrom'])->startOfDay()
            : (clone $to)->subDays(self::DEFAULT_DAYS - 1)->startOfDay();

        return [$from, $to];
    }
}

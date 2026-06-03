<?php

namespace Webkul\Pos\Queries\Order;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Pos\Helpers\RoleGate;
use Webkul\Pos\Repositories\OrderRepository;
use Webkul\Sales\Repositories\OrderRepository as SalesOrderRepository;

class OrderQuery extends Controller
{
    /** product_attribute_values.attribute_id for the 'cost' attribute. */
    protected const COST_ATTRIBUTE_ID = 12;

    private const POS_PAYMENT_METHODS = ['pos_cash', 'pos_card', 'pos_split'];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected SalesOrderRepository $salesOrderRepository,
    ) {}

    /**
     * Get orders for the OrderList. The `source` arg selects which sales feed:
     *
     *   - pos      (default) → register sales: a pos_order row + a POS payment
     *                          method. Returns pos_order rows, exactly as before
     *                          — the historical behaviour is byte-for-byte intact.
     *   - website            → storefront sales on this outlet's channel that
     *                          have NO pos_order row. Returns core `orders` rows.
     *   - all                → both feeds, returned as core `orders` rows.
     *
     * The two feeds use different base tables, so the field resolvers below
     * (resolveOrder / resolveOutlet / resolveBarcodeUrl / resolveCustomerCredit)
     * branch on the root type. A pos_order root has `order_id`; a core order
     * root does not.
     */
    public function getOrders($query, $input, GraphQLContext $context)
    {
        $user   = $context->user();
        $source = in_array($input['source'] ?? 'pos', ['pos', 'website', 'all'], true)
            ? ($input['source'] ?? 'pos')
            : 'pos';

        // Optional date range — the OrderList "今日 / 今週" tabs pass these so
        // we don't fetch-loop through years of old orders on the client.
        $from = $input['from'] ?? null;
        $to   = $input['to']   ?? null;

        // --- POS feed (unchanged shape): pos_order rows with a POS payment method ---
        if ($source === 'pos') {
            $q = $this->orderRepository->query()
                ->where('pos_order.outlet_id', $user->outlet_id)
                ->whereExists(function ($q) {
                    $q->select(DB::raw(1))
                      ->from('order_payment')
                      ->whereColumn('order_payment.order_id', 'pos_order.order_id')
                      ->whereIn('order_payment.method', self::POS_PAYMENT_METHODS);
                });

            if ($from) { $q->where('pos_order.created_at', '>=', $from); }
            if ($to)   { $q->where('pos_order.created_at', '<=', $to); }

            return $q->orderByDesc('pos_order.created_at');
        }

        // --- Website / all feeds: core orders on this outlet's channel ---
        $channelId = $user->outlet?->channel_id;

        $orders = $this->salesOrderRepository->query()->select('orders.*');

        // Without a channel we can't identify the storefront feed; fall back to
        // an empty result for website, and to POS-linked orders for 'all'.
        if ($channelId !== null) {
            $orders->where('orders.channel_id', $channelId);
        } else {
            $orders->whereRaw('1 = 0');
        }

        $hasPosOrder = function ($q) {
            $q->select(DB::raw(1))
              ->from('pos_order')
              ->whereColumn('pos_order.order_id', 'orders.id');
        };

        if ($source === 'website') {
            // Storefront orders only: no register row.
            $orders->whereNotExists($hasPosOrder);
        }
        // 'all' → no pos_order filter: every channel order, register or web.

        if ($from) { $orders->where('orders.created_at', '>=', $from); }
        if ($to)   { $orders->where('orders.created_at', '<=', $to); }

        return $orders->orderByDesc('orders.created_at');
    }

    /**
     * Single order by its sales order id, scoped to the cashier's outlet OR the
     * outlet's channel. Resolves BOTH register sales (pos_order row) and
     * storefront sales (channel order, no pos_order row) so the OrderDetail
     * screen can open either. Returns a pos_order row when one exists (so the
     * POS-specific fields/relations keep working), else the core order.
     */
    public function getOrder($root, array $args, GraphQLContext $context)
    {
        $orderId = (int) ($args['id'] ?? 0);
        if (! $orderId) {
            return null;
        }

        $user = $context->user();

        // Prefer the pos_order row (register sale) — unchanged path.
        $posOrder = $this->orderRepository->query()
            ->where('pos_order.outlet_id', $user->outlet_id)
            ->where('pos_order.order_id', $orderId)
            ->first();

        if ($posOrder) {
            return $posOrder;
        }

        // Storefront fallback: a core order on this outlet's channel that has
        // no pos_order row. Scoped to the channel so an agent can only open
        // orders belonging to their store.
        $channelId = $user->outlet?->channel_id;
        if ($channelId === null) {
            return null;
        }

        return $this->salesOrderRepository->query()
            ->select('orders.*')
            ->where('orders.id', $orderId)
            ->where('orders.channel_id', $channelId)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('pos_order')
                  ->whereColumn('pos_order.order_id', 'orders.id');
            })
            ->first();
    }

    /**
     * Open storefront-order statuses we fetch the breakdown for. The badge
     * `total` counts only 'pending' (see pendingWebOrderCount), but we still
     * query pending_payment + processing so the resolver can return the split.
     */
    private const PENDING_WEB_STATUSES = ['pending', 'pending_payment', 'processing'];

    /**
     * Count of unfulfilled WEBSITE orders for the top-bar badge, scoped to the
     * agent's OWN store (outlet channel).
     *
     * Scope (2026-05-30): the badge MUST match what the agent can actually open
     * and edit. getOrders(website) and getOrder() are both channel-scoped, so a
     * tenant-wide count showed orders the agent could see in the badge but NOT
     * open — tapping a different store's web order ran getOrder() → null →
     * OrderDetail/OrderEdit rendered with no data ("no edit/status control"
     * bug). Scoping the badge to the outlet's channel keeps badge → list →
     * detail → edit consistent: every counted order is one the agent can act on.
     *
     * Website = a core `orders` row with NO pos_order row (register sales
     * excluded). Badge `total` = status 'pending' only; pending_payment +
     * processing are returned as a breakdown but don't drive the badge. Falls
     * back to an empty count when the outlet has no channel (can't identify the
     * storefront feed), mirroring getOrders().
     */
    public function pendingWebOrderCount($root, array $args, GraphQLContext $context): array
    {
        $channelId = $context->user()->outlet?->channel_id;

        $base = $this->salesOrderRepository->query()
            ->whereIn('orders.status', self::PENDING_WEB_STATUSES)
            ->when(
                $channelId !== null,
                fn ($q) => $q->where('orders.channel_id', $channelId),
                fn ($q) => $q->whereRaw('1 = 0'),
            )
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('pos_order')
                  ->whereColumn('pos_order.order_id', 'orders.id');
            });

        $byStatus = (clone $base)
            ->select('orders.status', DB::raw('COUNT(*) as c'))
            ->groupBy('orders.status')
            ->pluck('c', 'orders.status');

        $pending        = (int) ($byStatus['pending'] ?? 0);
        $pendingPayment = (int) ($byStatus['pending_payment'] ?? 0);
        $processing     = (int) ($byStatus['processing'] ?? 0);

        return [
            // Badge `total` counts ONLY status='pending' (cashier request
            // 2026-06-02): pending_payment orders are unpaid (awaiting bank
            // transfer etc.) and processing orders are already in hand, so the
            // badge should surface just the genuinely new, unacknowledged web
            // orders. The pendingPayment/processing counts are still returned
            // below so a future UI can show the unpaid/in-progress split — they
            // simply no longer inflate the headline badge.
            'total'           => $pending,
            'pending'         => $pending,
            'pendingPayment'  => $pendingPayment,
            'processing'      => $processing,
        ];
    }

    /* ------------------------------------------------------------------ */
    /* OutletOrder field resolvers — branch on pos_order vs core order root */
    /* ------------------------------------------------------------------ */

    /** True when the root is a pos_order row (has order_id), else a core order. */
    private function isPosOrder($root): bool
    {
        // The pos Order model maps `pos_order` and always carries order_id; the
        // core Order model has none.
        return isset($root->order_id);
    }

    /**
     * The core sales order. pos_order root → its `order` relation; core order
     * root → itself.
     */
    public function resolveOrder($root, array $args, GraphQLContext $context)
    {
        return $this->isPosOrder($root) ? $root->order : $root;
    }

    /**
     * The selling outlet. pos_order root → its `outlet` relation; a storefront
     * order belongs to no register, so we surface the agent's current outlet so
     * the UI still shows a store name.
     */
    public function resolveOutlet($root, array $args, GraphQLContext $context)
    {
        return $this->isPosOrder($root) ? $root->outlet : $context->user()->outlet;
    }

    /** Order barcode — only register sales have one. */
    public function resolveBarcodeUrl($root, array $args, GraphQLContext $context): ?string
    {
        return $this->isPosOrder($root) ? $root->barcode_url : null;
    }

    /** Customer credit (change/tendered) — register sales only. */
    public function resolveCustomerCredit($root, array $args, GraphQLContext $context)
    {
        return $this->isPosOrder($root) ? $root->customerCredit : null;
    }

    /**
     * Tracking number of the order's latest shipment (the one the
     * tracking-update mutation writes to), or null when nothing is shipped yet.
     * Resolves on the core Order type — $root is an Order model here.
     */
    public function resolveTrackingNumber($root, array $args, GraphQLContext $context): ?string
    {
        return $root->shipments()->latest('id')->value('track_number');
    }

    /**
     * Per-order profit summary for the OrderDetail screen. Manager/owner only —
     * a cashier gets null and the UI hides the block (same gate as cost on the
     * product editor and getInvoiceStats). Cost basis is Σ qty_ordered × the
     * product's 'cost' attribute (attribute_id 12); revenue is Σ base_total of
     * the top-level items (parent_id null) so configurable parents aren't
     * double-counted with their simple children.
     *
     * Works for both root types: a pos_order has order_id; a core order's own id
     * IS the order id.
     */
    public function profitSummary($root, array $args, GraphQLContext $context): ?array
    {
        if (! RoleGate::atLeast($context->user(), RoleGate::MANAGER)) {
            return null;
        }

        $orderId = $this->isPosOrder($root) ? (int) $root->order_id : (int) ($root->id ?? 0);
        if (! $orderId) {
            return null;
        }

        $row = DB::table('order_items')
            ->leftJoin('product_attribute_values as pav', function ($j) {
                $j->on('pav.product_id', '=', 'order_items.product_id')
                  ->where('pav.attribute_id', '=', self::COST_ATTRIBUTE_ID);
            })
            ->where('order_items.order_id', $orderId)
            ->whereNull('order_items.parent_id')
            ->selectRaw('COALESCE(SUM(order_items.qty_ordered * COALESCE(pav.float_value,0)),0) as cost,
                         COALESCE(SUM(order_items.base_total),0) as revenue')
            ->first();

        $revenue = round((float) ($row->revenue ?? 0), 2);
        $cost    = round((float) ($row->cost ?? 0), 2);
        $profit  = round($revenue - $cost, 2);
        $margin  = $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0.0;

        return [
            'revenue'   => $revenue,
            'cost'      => $cost,
            'profit'    => $profit,
            'marginPct' => $margin,
        ];
    }
}

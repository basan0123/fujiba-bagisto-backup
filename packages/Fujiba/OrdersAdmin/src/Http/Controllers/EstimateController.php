<?php

namespace Fujiba\OrdersAdmin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Abandoned-cart estimate page.
 *
 * Lists carts that have items but never converted to an order, and shows
 * the projected profit if they HAD checked out:
 *   profit = sub_total − discount_amount − Σ(item.qty × cost)
 * Cost is read from the EAV `cost` attribute (attribute_id resolved at
 * runtime). Items whose product has no configured cost are surfaced so
 * the admin can set 仕入価格 before the customer returns.
 *
 * Mounted at /admin/sales/estimate (page) + /admin/api/estimate (JSON).
 */
class EstimateController extends Controller
{
    public function index()
    {
        if (! bouncer()->hasPermission('sales.orders')) {
            abort(403);
        }

        $channels = core()->getAllChannels()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'code' => $c->code])
            ->values();

        return view('orders-admin::admin.estimate', [
            'channels' => $channels,
        ]);
    }

    /**
     * GET /admin/api/estimate/abandoned
     *
     * Filters:
     *   - channel_id   (optional)
     *   - status       active | inactive | all  (default: all)
     *   - days         created within N days (default: 30; 0 = no limit)
     *   - min_items    skip carts with fewer items (default: 1)
     *   - search       customer email / name LIKE
     *   - page, per_page
     *
     * Sort: newest first by cart.id.
     *
     * Definition of "abandoned": cart has items AND either:
     *   (a) no order was ever placed from this cart, OR
     *   (b) cart.updated_at is later than the last order placed from it
     *       (the customer kept shopping after their last checkout — those
     *       new items are unconverted).
     * Bagisto's PWA/messenger flow reuses the same cart row across multiple
     * orders, so condition (b) is essential — without it, repeat customers
     * never appear here.
     */
    public function abandoned(Request $request): JsonResponse
    {
        if (! bouncer()->hasPermission('sales.orders')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $perPage   = max(1, min(100, (int) $request->input('per_page', 10)));
        $page      = max(1, (int) $request->input('page', 1));
        $channelId = (int) $request->input('channel_id') ?: null;
        $status    = $request->input('status', 'all');
        $days      = $request->input('days', 30);
        $days      = is_numeric($days) ? (int) $days : 30;
        $minItems  = max(1, (int) $request->input('min_items', 1));
        $search    = trim((string) $request->input('search', ''));

        // Subquery: most recent order timestamp per cart. NULL when the
        // cart never converted. Joined as left so condition (a) and (b)
        // can be expressed in one WHERE.
        $lastOrderSub = DB::table('orders')
            ->select('cart_id', DB::raw('MAX(created_at) as last_order_at'))
            ->whereNotNull('cart_id')
            ->groupBy('cart_id');

        $q = DB::table('cart as c')
            ->leftJoinSub($lastOrderSub, 'lo', 'lo.cart_id', '=', 'c.id')
            ->where('c.items_count', '>=', $minItems)
            ->where(function ($w) {
                // Either never ordered, or activity after the last order.
                $w->whereNull('lo.last_order_at')
                  ->orWhereColumn('c.updated_at', '>', 'lo.last_order_at');
            });

        if ($channelId) {
            $q->where('c.channel_id', $channelId);
        }

        if ($status === 'active') {
            $q->where('c.is_active', 1);
        } elseif ($status === 'inactive') {
            $q->where('c.is_active', 0);
        }

        if ($days > 0) {
            // Use updated_at: a year-old cart that the customer just touched
            // is still "fresh activity", and we want it surfaced.
            $q->where('c.updated_at', '>=', now()->subDays($days));
        }

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('c.customer_email', 'like', "%{$search}%")
                  ->orWhereRaw('CONCAT(IFNULL(c.customer_first_name,""), " ", IFNULL(c.customer_last_name,"")) LIKE ?', ["%{$search}%"]);
            });
        }

        $total = (clone $q)->count();

        $carts = $q->orderByDesc('c.updated_at')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get([
                'c.id', 'c.channel_id', 'c.customer_id', 'c.customer_email',
                'c.customer_first_name', 'c.customer_last_name',
                'c.items_count', 'c.items_qty', 'c.sub_total', 'c.grand_total',
                'c.shipping_amount', 'c.discount_amount',
                'c.is_active', 'c.created_at', 'c.updated_at', 'c.coupon_code',
                'lo.last_order_at',
            ]);

        if ($carts->isEmpty()) {
            return response()->json([
                'data' => [],
                'meta' => ['current_page' => $page, 'per_page' => $perPage, 'total' => 0, 'last_page' => 1],
                'summary' => $this->emptySummary(),
            ]);
        }

        $cartIds = $carts->pluck('id')->all();

        $items = DB::table('cart_items')
            ->whereIn('cart_id', $cartIds)
            ->whereNull('parent_id') // top-level rows only; children of configurable already aggregated
            ->get(['cart_id', 'product_id', 'sku', 'name', 'quantity', 'price', 'total']);

        $productIds = $items->pluck('product_id')->filter()->unique()->values()->all();
        $costMap    = $this->loadCosts($productIds);
        $imageMap   = $this->loadImages($productIds);

        // Channel name lookup. The `channels` table has no `name` column —
        // localized names live in channel_translations. Prefer the current
        // app locale, fall back to any translation, then to the channel code.
        $locale       = app()->getLocale();
        $codeMap      = DB::table('channels')->pluck('code', 'id')->toArray();
        $translations = DB::table('channel_translations')->get(['channel_id', 'locale', 'name']);
        $channelNames = [];
        foreach ($codeMap as $id => $code) {
            $localized = $translations->first(fn ($t) => $t->channel_id == $id && $t->locale === $locale)
                      ?? $translations->first(fn ($t) => $t->channel_id == $id);
            $channelNames[$id] = $localized->name ?? $code;
        }

        $itemsByCart = $items->groupBy('cart_id');

        $rows = $carts->map(function ($c) use ($itemsByCart, $costMap, $imageMap, $channelNames) {
            $cartItems = $itemsByCart->get($c->id, collect());

            $costTotal       = 0.0;
            $missing         = [];
            $itemRows        = [];
            foreach ($cartItems as $i) {
                $pid  = (int) $i->product_id;
                $cost = $costMap[$pid] ?? null;
                $qty  = (int) $i->quantity;
                if ($cost === null) {
                    $missing[] = ['product_id' => $pid, 'sku' => $i->sku, 'name' => $i->name];
                } else {
                    $costTotal += $cost * $qty;
                }
                $itemRows[] = [
                    'product_id' => $pid,
                    'sku'        => $i->sku,
                    'name'       => $i->name,
                    'qty'        => $qty,
                    'price'      => (float) $i->price,
                    'total'      => (float) $i->total,
                    'cost'       => $cost,
                    'image'      => $imageMap[$pid] ?? null,
                ];
            }

            $hasMissing = ! empty($missing);
            $subTotal   = (float) $c->sub_total;
            $shipping   = (float) $c->shipping_amount;
            $discount   = (float) $c->discount_amount;

            // Shipping is pass-through at Kimono (collected from customer,
            // paid to carrier), so it doesn't affect margin. Discount does
            // reduce what the customer pays and must be subtracted.
            $profit = $hasMissing ? null : ($subTotal - $discount - $costTotal);

            return [
                'cart_id'      => $c->id,
                'channel_id'   => $c->channel_id,
                'channel_name' => $channelNames[$c->channel_id] ?? '—',
                'customer'     => [
                    'id'    => $c->customer_id,
                    'email' => $c->customer_email,
                    'name'  => trim(($c->customer_first_name ?? '') . ' ' . ($c->customer_last_name ?? '')) ?: null,
                ],
                'items_count'    => (int) $c->items_count,
                'items_qty'      => (float) $c->items_qty,
                'sub_total'      => $subTotal,
                'grand_total'    => (float) $c->grand_total,
                'shipping_amount'=> $shipping,
                'discount_amount'=> $discount,
                'cost_total'     => $hasMissing ? null : $costTotal,
                'profit'         => $profit,
                'missing_cost_products' => $missing,
                'is_active'      => (bool) $c->is_active,
                'coupon_code'    => $c->coupon_code,
                'created_at'     => $c->created_at,
                'updated_at'     => $c->updated_at,
                'last_order_at'  => $c->last_order_at ?? null,
                'has_prior_order'=> ! empty($c->last_order_at),
                'items'          => $itemRows,
            ];
        })->values();

        // Page-scoped summary — sums what's visible on this page so the
        // admin can see "if all these abandoned carts converted, how much
        // profit". Carts with missing cost are excluded from profit total
        // but counted separately so the number isn't overstated.
        $summary = [
            'cart_count'             => $carts->count(),
            'total_items'            => $rows->sum('items_count'),
            'sub_total_sum'          => $rows->sum('sub_total'),
            'profit_sum'             => $rows->whereNotNull('profit')->sum('profit'),
            'carts_missing_cost'     => $rows->where('profit', null)->count(),
            'unique_missing_products'=> $rows
                ->flatMap(fn ($r) => $r['missing_cost_products'])
                ->pluck('product_id')->unique()->values()->count(),
        ];

        return response()->json([
            'data' => $rows,
            'meta' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => max(1, (int) ceil($total / $perPage)),
            ],
            'summary' => $summary,
        ]);
    }

    protected function emptySummary(): array
    {
        return [
            'cart_count' => 0, 'total_items' => 0, 'sub_total_sum' => 0,
            'profit_sum' => 0, 'carts_missing_cost' => 0, 'unique_missing_products' => 0,
        ];
    }

    /**
     * [product_id => float|null]. NULL means cost row missing OR float_value NULL.
     */
    protected function loadCosts(array $productIds): array
    {
        if (empty($productIds)) return [];

        $rows = DB::table('product_attribute_values')
            ->whereIn('product_id', $productIds)
            ->whereIn('attribute_id', function ($q) {
                $q->select('id')->from('attributes')->where('code', 'cost');
            })
            ->get(['product_id', 'float_value']);

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->product_id] = $r->float_value !== null ? (float) $r->float_value : null;
        }
        foreach ($productIds as $pid) {
            $map[(int) $pid] = $map[(int) $pid] ?? null;
        }
        return $map;
    }

    /**
     * [product_id => first_image_url|null]
     */
    protected function loadImages(array $productIds): array
    {
        if (empty($productIds)) return [];

        $rows = DB::table('product_images')
            ->whereIn('product_id', $productIds)
            ->orderBy('id')
            ->get(['product_id', 'path']);

        $out = [];
        foreach ($rows as $r) {
            if (! isset($out[$r->product_id])) {
                $out[$r->product_id] = Storage::url($r->path);
            }
        }
        return $out;
    }
}

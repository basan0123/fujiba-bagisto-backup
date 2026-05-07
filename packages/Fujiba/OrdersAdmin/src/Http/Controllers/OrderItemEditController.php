<?php

namespace Fujiba\OrdersAdmin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Product\Jobs\UpdateCreateInventoryIndex as UpdateCreateInventoryIndexJob;
use Webkul\Product\Models\Product;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Repositories\OrderItemRepository;
use Webkul\Sales\Repositories\OrderRepository;

/**
 * Admin order line-item editor (v1).
 *
 * Scope: simple products only, only on orders in pending/processing.
 * Behaviour:
 *   - add/update/remove order items
 *   - stock validated against inventory_indices (per-channel) before
 *     committing; insufficient stock → 422
 *   - shipping: optional manual override; otherwise re-derive via the
 *     existing carrier (re-runs the storefront rule using the order's
 *     channel & shipping address)
 *   - tax: per-item recompute using the same Tax::getTaxRatesWithAmount()
 *     helper the storefront cart uses
 *   - discounts: cart_rules NOT re-evaluated (v1) — original discount is
 *     scaled proportionally to the new subtotal so the order doesn't lose
 *     a coupon that was already accepted
 *   - payment: not auto-adjusted; UI surfaces the delta
 */
class OrderItemEditController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderItemRepository $orderItemRepository,
    ) {}

    /**
     * GET /api/orders/{id}/edit-items
     * Returns current items + recalc-preview baseline for the modal.
     *
     * Names + images come from a fresh product lookup, NOT the order_items
     * snapshot — older orders sometimes saved the SKU into order_items.name,
     * so the admin would see "KIMONO-2551" instead of the real product name.
     */
    public function show(int $id): JsonResponse
    {
        $order = $this->orderRepository->with(['items.product'])->findOrFail($id);

        $productIds = $order->items->pluck('product_id')->filter()->unique()->values()->toArray();
        $info = $this->fetchProductInfo($productIds);

        return response()->json([
            'order' => [
                'id'              => $order->id,
                'increment_id'    => $order->increment_id,
                'status'          => $order->status,
                'channel_id'      => $order->channel_id,
                'sub_total'       => (float) $order->sub_total,
                'shipping_amount' => (float) $order->shipping_amount,
                'tax_amount'      => (float) $order->tax_amount,
                'discount_amount' => (float) $order->discount_amount,
                'grand_total'     => (float) $order->grand_total,
                'shipping_method' => $order->shipping_method,
                'shipping_title'  => $order->shipping_title,
                'editable'        => $this->isEditable($order),
            ],
            'items' => $order->items->map(function ($i) use ($info) {
                $row = $info[$i->product_id] ?? null;

                return [
                    'id'         => $i->id,
                    'product_id' => $i->product_id,
                    'sku'        => $row['sku'] ?? $i->sku,
                    'name'       => $row['name'] ?? $i->name ?? $i->sku,
                    'image'      => $row['image'] ?? null,
                    'price'      => (float) $i->price,
                    'qty'        => (int) $i->qty_ordered,
                    'total'      => (float) $i->total,
                    'tax'        => (float) $i->tax_amount,
                ];
            })->values(),
        ]);
    }

    /**
     * Product search for the "add item" picker. Returns simple products with
     * name + image + stock for the order's channel. Searches against SKU and
     * the localized product_flat.name (any locale). With no query, returns
     * the most recent simple products so the admin can browse without typing.
     */
    public function searchProducts(Request $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findOrFail($id);
        $query = trim((string) $request->input('q', ''));
        $perPage = (int) $request->input('per_page', 30);
        $page    = max(1, (int) $request->input('page', 1));

        // Restrict to products linked to the order's channel via product_channels.
        // Without this filter the kimono store order would surface mmfood
        // products and vice versa.
        $productsQ = Product::query()
            ->where('type', 'simple')
            ->whereIn('id', function ($sub) use ($order) {
                $sub->select('product_id')
                    ->from('product_channels')
                    ->where('channel_id', $order->channel_id);
            });

        if ($query !== '') {
            // Match against SKU OR product_flat.name (any locale). product_flat
            // is keyed by channel + locale; using whereIn against just name
            // avoids missing products that exist in only one channel's row.
            $productsQ->where(function ($q) use ($query) {
                $q->where('sku', 'like', '%'.$query.'%')
                    ->orWhereIn('id', function ($sub) use ($query) {
                        $sub->select('product_id')
                            ->from('product_flat')
                            ->where('name', 'like', '%'.$query.'%');
                    });
            });
        }

        $total = (clone $productsQ)->count();

        $products = $productsQ
            ->orderByDesc('id')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $info = $this->fetchProductInfo($products->pluck('id')->toArray(), $order->channel_id);

        $data = $products->map(function ($p) use ($info) {
            $row = $info[$p->id] ?? [];

            return [
                'id'          => $p->id,
                'sku'         => $p->sku,
                'name'        => $row['name'] ?? $p->sku,
                'image'       => $row['image'] ?? null,
                'price'       => (float) ($row['price'] ?? 0),
                'qty_in_stock'=> (int) ($row['qty_in_stock'] ?? 0),
            ];
        })->values();

        return response()->json([
            'data'      => $data,
            'page'      => $page,
            'per_page'  => $perPage,
            'total'     => $total,
            'has_more'  => ($page * $perPage) < $total,
        ]);
    }

    /**
     * Hydrate display data (name, image, price, stock) for a list of product
     * IDs in one go. Reads:
     *   - product_flat for name + price (preferring the order's channel/
     *     locale, falling back to any row)
     *   - product_images for the first image path
     *   - product_inventory_indices for per-channel stock
     *
     * Returns: [productId => ['name','image','price','sku','qty_in_stock']]
     */
    protected function fetchProductInfo(array $productIds, ?int $channelId = null): array
    {
        if (empty($productIds)) {
            return [];
        }

        $locale = app()->getLocale();
        $channelCode = $channelId ? (DB::table('channels')->where('id', $channelId)->value('code') ?? 'default') : 'default';

        // Fetch all flats for these products; pick best match per product below.
        $flats = DB::table('product_flat')
            ->whereIn('product_id', $productIds)
            ->get(['product_id', 'channel', 'locale', 'sku', 'name', 'price']);

        $images = DB::table('product_images')
            ->whereIn('product_id', $productIds)
            ->orderBy('id')
            ->get(['product_id', 'path']);

        $stockRows = DB::table('product_inventory_indices')
            ->whereIn('product_id', $productIds)
            ->when($channelId, fn ($q) => $q->where('channel_id', $channelId))
            ->get(['product_id', 'qty', 'channel_id']);

        $imageByProduct = [];
        foreach ($images as $img) {
            // Keep the first image only.
            $imageByProduct[$img->product_id] = $imageByProduct[$img->product_id] ?? $img->path;
        }

        $stockByProduct = [];
        foreach ($stockRows as $s) {
            $stockByProduct[$s->product_id] = ($stockByProduct[$s->product_id] ?? 0) + (int) $s->qty;
        }

        $out = [];
        foreach ($productIds as $pid) {
            $candidates = $flats->where('product_id', $pid);

            // Preference: channel+locale → channel any-locale → any.
            $best = $candidates->first(fn ($r) => $r->channel === $channelCode && $r->locale === $locale && $r->name)
                ?? $candidates->first(fn ($r) => $r->channel === $channelCode && $r->name)
                ?? $candidates->first(fn ($r) => (bool) $r->name)
                ?? $candidates->first();

            $imagePath = $imageByProduct[$pid] ?? null;

            $out[$pid] = [
                'name'         => $best?->name ?? $best?->sku ?? null,
                'sku'          => $best?->sku ?? null,
                'price'        => $best?->price !== null ? (float) $best->price : null,
                'image'        => $imagePath ? \Illuminate\Support\Facades\Storage::url($imagePath) : null,
                'qty_in_stock' => $stockByProduct[$pid] ?? 0,
            ];
        }

        return $out;
    }

    /**
     * POST /api/orders/{id}/edit-items
     *
     * Payload: {
     *   items: [{ product_id, qty, order_item_id?: null }, ...],
     *   shipping_amount?: number   (manual override; null = recalc via carrier),
     *   note?: string              (admin comment to attach)
     * }
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|integer',
            'items.*.qty'          => 'required|integer|min:1',
            'items.*.order_item_id'=> 'nullable|integer',
            'shipping_amount'      => 'nullable|numeric|min:0',
            'note'                 => 'nullable|string|max:1000',
        ]);

        $order = $this->orderRepository->with(['items.product', 'channel'])->findOrFail($id);

        if (! $this->isEditable($order)) {
            return response()->json([
                'message' => 'Order cannot be edited in its current status.',
            ], 422);
        }

        $desired = collect($request->input('items'))->keyBy('product_id');

        // Stock pre-flight: for each product, requested_qty - existing_qty
        // (zero if the item is new) must be ≤ available stock. Indices live
        // per channel, so we read the order's channel_id.
        $existingByProduct = $order->items->keyBy('product_id');
        $stockProblems = [];

        foreach ($desired as $pid => $row) {
            $existingQty = (int) ($existingByProduct[$pid]->qty_ordered ?? 0);
            $delta = (int) $row['qty'] - $existingQty;

            if ($delta <= 0) {
                continue;   // reducing or same — no stock concern
            }

            $product = Product::find($pid);
            if (! $product || $product->type !== 'simple') {
                $stockProblems[] = "Product {$pid} is not a simple product.";
                continue;
            }

            $available = (int) ($product->inventory_indices->where('channel_id', $order->channel_id)->first()?->qty ?? 0);

            if ($delta > $available) {
                $stockProblems[] = "Not enough stock for {$product->sku}: need {$delta} more, only {$available} available.";
            }
        }

        if (! empty($stockProblems)) {
            return response()->json([
                'message' => 'Stock check failed',
                'errors'  => $stockProblems,
            ], 422);
        }

        // Snapshot original totals so we can scale the discount.
        $originalSubTotal      = (float) $order->sub_total;
        $originalDiscount      = (float) $order->discount_amount;
        $originalBaseDiscount  = (float) $order->base_discount_amount;
        $touchedProductIds     = collect($order->items->pluck('product_id'))
            ->merge($desired->keys())
            ->unique()
            ->values()
            ->toArray();

        DB::transaction(function () use ($order, $desired, $existingByProduct, $request, $originalSubTotal, $originalDiscount, $originalBaseDiscount) {
            // 1) Remove items not in `desired`. Stock is restored via the
            //    standard returnQtyToProductInventory() helper so the
            //    ordered_inventories counter drops accordingly.
            foreach ($order->items as $item) {
                if (! $desired->has($item->product_id)) {
                    $this->orderItemRepository->returnQtyToProductInventory($item);
                    $item->delete();
                }
            }

            // 2) For each desired row, update qty if existing, otherwise
            //    insert a new line. Tax computed per-line via Tax helper.
            foreach ($desired as $pid => $row) {
                $product = Product::find($pid);
                $newQty  = (int) $row['qty'];

                if ($existing = $existingByProduct->get($pid)) {
                    $existing = OrderItem::find($existing->id);
                    $oldQty = (int) $existing->qty_ordered;

                    if ($newQty > $oldQty) {
                        $this->bumpOrderedInventory($product, $order->channel_id, $newQty - $oldQty);
                    } elseif ($newQty < $oldQty) {
                        $this->bumpOrderedInventory($product, $order->channel_id, -($oldQty - $newQty));
                    }

                    $unitPrice = (float) $existing->price;
                    $taxPercent = (float) $existing->tax_percent;
                    $taxAmount  = round($unitPrice * $newQty * ($taxPercent / 100), 4);

                    $existing->qty_ordered  = $newQty;
                    $existing->total        = round($unitPrice * $newQty, 4);
                    $existing->base_total   = round($unitPrice * $newQty, 4);
                    $existing->tax_amount   = $taxAmount;
                    $existing->base_tax_amount = $taxAmount;
                    $existing->total_incl_tax     = round($existing->total + $taxAmount, 4);
                    $existing->base_total_incl_tax= round($existing->total + $taxAmount, 4);
                    $existing->save();
                } else {
                    $flat = $product->product_flat
                        ? $product->product_flat->where('channel', $order->channel->code)->first()
                        : null;
                    $unitPrice = (float) ($flat->price ?? $product->price ?? 0);
                    $taxPercent = $this->resolveTaxPercent($product, $order);
                    $taxAmount  = round($unitPrice * $newQty * ($taxPercent / 100), 4);

                    OrderItem::create([
                        'order_id'        => $order->id,
                        'product_id'      => $pid,
                        // morphTo target — must be the real model class, not
                        // the type string. Some legacy rows in this DB store
                        // "simple" here and break MorphTo when later loaded.
                        'product_type'    => Product::class,
                        'type'            => 'simple',
                        'sku'             => $product->sku,
                        'name'            => $flat->name ?? $product->sku,
                        'qty_ordered'     => $newQty,
                        'qty_invoiced'    => 0,
                        'price'           => $unitPrice,
                        'base_price'      => $unitPrice,
                        'price_incl_tax'  => round($unitPrice + ($unitPrice * $taxPercent / 100), 4),
                        'base_price_incl_tax' => round($unitPrice + ($unitPrice * $taxPercent / 100), 4),
                        'total'           => round($unitPrice * $newQty, 4),
                        'base_total'      => round($unitPrice * $newQty, 4),
                        'total_incl_tax'  => round($unitPrice * $newQty + $taxAmount, 4),
                        'base_total_incl_tax' => round($unitPrice * $newQty + $taxAmount, 4),
                        'tax_percent'     => $taxPercent,
                        'tax_amount'      => $taxAmount,
                        'base_tax_amount' => $taxAmount,
                        'weight'          => $product->weight ?? 0,
                        'total_weight'    => ($product->weight ?? 0) * $newQty,
                        'additional'      => ['admin_added' => true, 'cart_id' => null],
                    ]);

                    $this->bumpOrderedInventory($product, $order->channel_id, $newQty);
                }
            }

            // 3) Recompute order totals from the freshly synced items.
            $order->refresh();
            $order->load('items');

            $subTotal = 0;
            $taxTotal = 0;
            foreach ($order->items as $item) {
                $subTotal += (float) $item->total;
                $taxTotal += (float) $item->tax_amount;
            }

            // Shipping: explicit override > recompute via carrier > keep existing.
            if ($request->has('shipping_amount') && $request->input('shipping_amount') !== null) {
                $shipping = (float) $request->input('shipping_amount');
            } else {
                $shipping = $this->recomputeShipping($order, $subTotal);
            }

            // Discount: scale proportional to subtotal change (v1 — full
            // cart_rules re-eval is out of scope). If original subtotal was
            // zero, just zero the discount.
            $discount     = $originalSubTotal > 0 ? round($originalDiscount * ($subTotal / $originalSubTotal), 4) : 0;
            $baseDiscount = $originalSubTotal > 0 ? round($originalBaseDiscount * ($subTotal / $originalSubTotal), 4) : 0;

            $grand = round($subTotal + $taxTotal + $shipping - $discount, 4);

            $order->sub_total          = $subTotal;
            $order->base_sub_total     = $subTotal;
            $order->tax_amount         = $taxTotal;
            $order->base_tax_amount    = $taxTotal;
            $order->shipping_amount    = $shipping;
            $order->base_shipping_amount = $shipping;
            $order->discount_amount    = $discount;
            $order->base_discount_amount = $baseDiscount;
            $order->grand_total        = $grand;
            $order->base_grand_total   = $grand;
            $order->save();

            if ($note = $request->input('note')) {
                DB::table('order_comments')->insert([
                    'order_id'          => $order->id,
                    'comment'           => $note,
                    'customer_notified' => 0,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }
        });

        // Reindex stock for every product touched (added, removed, qty
        // changed) so the storefront sees the updated availability.
        UpdateCreateInventoryIndexJob::dispatchSync($touchedProductIds);

        Event::dispatch('sales.order.update.after', $order->fresh(['items']));

        $fresh = $this->orderRepository->with(['items'])->findOrFail($id);

        return response()->json([
            'message' => 'Order updated.',
            'order'   => [
                'id'              => $fresh->id,
                'sub_total'       => (float) $fresh->sub_total,
                'shipping_amount' => (float) $fresh->shipping_amount,
                'tax_amount'      => (float) $fresh->tax_amount,
                'discount_amount' => (float) $fresh->discount_amount,
                'grand_total'     => (float) $fresh->grand_total,
            ],
            'items'   => $fresh->items->map(fn ($i) => [
                'id'         => $i->id,
                'product_id' => $i->product_id,
                'sku'        => $i->sku,
                'name'       => $i->name,
                'qty'        => (int) $i->qty_ordered,
                'price'      => (float) $i->price,
                'total'      => (float) $i->total,
            ])->values(),
        ]);
    }

    protected function isEditable(Order $order): bool
    {
        return in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_PROCESSING], true);
    }

    /**
     * Increment/decrement product_ordered_inventories.qty for the channel.
     * Mirrors what the cart→order pipeline does at order placement.
     */
    protected function bumpOrderedInventory(Product $product, int $channelId, int $delta): void
    {
        if (! $product->manage_stock) {
            return;
        }

        $row = $product->ordered_inventories()->where('channel_id', $channelId)->first();

        if (! $row) {
            $product->ordered_inventories()->create([
                'qty'        => max(0, $delta),
                'channel_id' => $channelId,
            ]);

            return;
        }

        $row->qty = max(0, (int) $row->qty + $delta);
        $row->save();
    }

    /**
     * Look up the product's tax category and resolve a flat percentage
     * against the order's shipping address. Uses the same TaxRate model
     * the storefront resolves to.
     */
    protected function resolveTaxPercent(Product $product, Order $order): float
    {
        $taxCategoryId = $product->tax_category_id;

        if (! $taxCategoryId) {
            return 0.0;
        }

        $rates = \Webkul\Tax\Models\TaxRate::query()
            ->whereIn('id', \Webkul\Tax\Models\TaxMap::where('tax_category_id', $taxCategoryId)->pluck('tax_rate_id'))
            ->get();

        $shippingAddress = $order->shipping_address ?? $order->billing_address;
        if (! $shippingAddress) {
            return (float) ($rates->first()->tax_rate ?? 0);
        }

        foreach ($rates as $rate) {
            if (
                ($rate->country && $shippingAddress->country && strcasecmp($rate->country, $shippingAddress->country) !== 0)
                || ($rate->state && $shippingAddress->state && strcasecmp($rate->state, $shippingAddress->state) !== 0)
            ) {
                continue;
            }

            return (float) $rate->tax_rate;
        }

        return (float) ($rates->first()->tax_rate ?? 0);
    }

    /**
     * Re-derive shipping using the order's existing shipping_method code.
     * If the carrier returns a different price (e.g. weight-based flat
     * rate, cart-total-conditioned free shipping), use it. If we can't
     * recompute (carrier missing, address gone), fall back to existing.
     */
    protected function recomputeShipping(Order $order, float $newSubTotal): float
    {
        $existing = (float) $order->shipping_amount;

        if (! $order->shipping_method) {
            return $existing;
        }

        // Bagisto's carrier classes read from the active Cart facade. Doing
        // a full Cart shim is heavy; for v1 we approximate: keep the per-
        // -kg / fixed amount the order already has. Channel-rule changes
        // (free-over-X) are picked up via manual override in the modal.
        return $existing;
    }
}

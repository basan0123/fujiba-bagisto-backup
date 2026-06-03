<?php

namespace Fujiba\OrdersAdmin\Services;

use Fujiba\OrdersAdmin\Exceptions\OrderEditException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Product\Jobs\UpdateCreateInventoryIndex as UpdateCreateInventoryIndexJob;
use Webkul\Product\Models\Product;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Repositories\OrderItemRepository;

/**
 * Order line-item editor (v1). Add / update / remove simple-product lines on a
 * pending/processing order, with the full recompute the admin advanced screen
 * does: stock pre-flight, per-item tax, ordered-inventory accounting,
 * subtotal/COD/cool/discount/grand recalc, and a stock reindex.
 *
 * Extracted verbatim from OrderItemEditController so the admin REST screen and
 * the POS GraphQL mutation share ONE implementation — the rules can't drift.
 * Throws OrderEditException for business-rule failures (not-editable, stock).
 */
class OrderItemEditor
{
    public function __construct(
        protected OrderItemRepository $orderItemRepository,
        protected FulfillmentRecalculator $fulfillmentRecalculator,
    ) {}

    public function isEditable(Order $order): bool
    {
        return in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_PROCESSING], true);
    }

    /**
     * Apply a new desired set of items to the order.
     *
     * @param  array<int,array{product_id:int,qty:int,order_item_id?:?int}>  $items
     * @param  float|null  $shippingOverride  explicit shipping; null = recompute via carrier
     * @return Order  the fresh order (items reloaded)
     *
     * @throws OrderEditException not-editable, or stock check failed (with ->errors)
     */
    public function apply(Order $order, array $items, ?float $shippingOverride = null, ?string $note = null): Order
    {
        if (! $this->isEditable($order)) {
            throw new OrderEditException(trans('admin::app.sales.orders.not-editable') ?: 'Order cannot be edited in its current status.');
        }

        $desired = collect($items)->keyBy('product_id');

        // Stock pre-flight: for each product, requested_qty - existing_qty
        // (zero if new) must be ≤ available stock on the order's channel.
        $existingByProduct = $order->items->keyBy('product_id');
        $stockProblems = [];

        foreach ($desired as $pid => $row) {
            $existingQty = (int) ($existingByProduct[$pid]->qty_ordered ?? 0);
            $delta = (int) $row['qty'] - $existingQty;

            if ($delta <= 0) {
                continue;
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
            throw new OrderEditException('Stock check failed', $stockProblems);
        }

        $originalSubTotal     = (float) $order->sub_total;
        $originalDiscount     = (float) $order->discount_amount;
        $originalBaseDiscount = (float) $order->base_discount_amount;
        $touchedProductIds    = collect($order->items->pluck('product_id'))
            ->merge($desired->keys())
            ->unique()
            ->values()
            ->toArray();

        DB::transaction(function () use ($order, $desired, $existingByProduct, $shippingOverride, $note, $originalSubTotal, $originalDiscount, $originalBaseDiscount) {
            // 1) Remove items not in $desired; restore their stock.
            foreach ($order->items as $item) {
                if (! $desired->has($item->product_id)) {
                    $this->orderItemRepository->returnQtyToProductInventory($item);
                    $item->delete();
                }
            }

            // 2) Update qty on existing lines / insert new lines.
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

                    $unitPrice  = (float) $existing->price;
                    $taxPercent = (float) $existing->tax_percent;
                    $taxAmount  = round($unitPrice * $newQty * ($taxPercent / 100), 4);

                    $existing->qty_ordered         = $newQty;
                    $existing->total               = round($unitPrice * $newQty, 4);
                    $existing->base_total          = round($unitPrice * $newQty, 4);
                    $existing->tax_amount          = $taxAmount;
                    $existing->base_tax_amount     = $taxAmount;
                    $existing->total_incl_tax      = round($existing->total + $taxAmount, 4);
                    $existing->base_total_incl_tax = round($existing->total + $taxAmount, 4);
                    $existing->save();
                } else {
                    $flat = $product->product_flat
                        ? $product->product_flat->where('channel', $order->channel->code)->first()
                        : null;
                    $unitPrice  = (float) ($flat->price ?? $product->price ?? 0);
                    $taxPercent = $this->resolveTaxPercent($product, $order);
                    $taxAmount  = round($unitPrice * $newQty * ($taxPercent / 100), 4);

                    OrderItem::create([
                        'order_id'             => $order->id,
                        'product_id'           => $pid,
                        // morphTo target must be the real model class, not the
                        // type string (legacy "simple" rows break MorphTo).
                        'product_type'         => Product::class,
                        'type'                 => 'simple',
                        'sku'                  => $product->sku,
                        'name'                 => $flat->name ?? $product->sku,
                        'qty_ordered'          => $newQty,
                        'qty_invoiced'         => 0,
                        'price'                => $unitPrice,
                        'base_price'           => $unitPrice,
                        'price_incl_tax'       => round($unitPrice + ($unitPrice * $taxPercent / 100), 4),
                        'base_price_incl_tax'  => round($unitPrice + ($unitPrice * $taxPercent / 100), 4),
                        'total'                => round($unitPrice * $newQty, 4),
                        'base_total'           => round($unitPrice * $newQty, 4),
                        'total_incl_tax'       => round($unitPrice * $newQty + $taxAmount, 4),
                        'base_total_incl_tax'  => round($unitPrice * $newQty + $taxAmount, 4),
                        'tax_percent'          => $taxPercent,
                        'tax_amount'           => $taxAmount,
                        'base_tax_amount'      => $taxAmount,
                        'weight'               => $product->weight ?? 0,
                        'total_weight'         => ($product->weight ?? 0) * $newQty,
                        'additional'           => ['admin_added' => true, 'cart_id' => null],
                    ]);

                    $this->bumpOrderedInventory($product, $order->channel_id, $newQty);
                }
            }

            // 3) Recompute totals from the freshly synced items.
            $order->refresh();
            $order->load('items');

            $subTotal = 0;
            $taxTotal = 0;
            foreach ($order->items as $item) {
                $subTotal += (float) $item->total;
                $taxTotal += (float) $item->tax_amount;
            }

            $shipping = $shippingOverride !== null
                ? (float) $shippingOverride
                : $this->recomputeShipping($order, $subTotal);

            // Discount scaled proportional to subtotal change (v1 — cart_rules
            // not re-evaluated). Zero subtotal → zero discount.
            $discount     = $originalSubTotal > 0 ? round($originalDiscount * ($subTotal / $originalSubTotal), 4) : 0;
            $baseDiscount = $originalSubTotal > 0 ? round($originalBaseDiscount * ($subTotal / $originalSubTotal), 4) : 0;

            // COD fee + cool surcharge re-resolved for the new subtotal.
            $paymentMethod    = $order->payment?->method;
            $order->sub_total = $subTotal;
            $cod  = 0.0;
            $cool = 0.0;
            if ($paymentMethod === 'cashondelivery') {
                $cod = $this->fulfillmentRecalculator->resolveCodFee($subTotal);
            }
            if ($this->fulfillmentRecalculator->orderNeedsCoolShipping($order)) {
                $cool = (float) config('fujiba.fees.cool', 220);
            }

            $grand = round($subTotal + $taxTotal + $shipping + $cod + $cool - $discount, 4);

            $order->base_sub_total       = $subTotal;
            $order->tax_amount           = $taxTotal;
            $order->base_tax_amount      = $taxTotal;
            $order->shipping_amount      = $shipping;
            $order->base_shipping_amount = $shipping;
            $order->discount_amount      = $discount;
            $order->base_discount_amount = $baseDiscount;
            $order->cod_fee              = $cod;
            $order->base_cod_fee         = $cod;
            $order->cool_surcharge       = $cool;
            $order->base_cool_surcharge  = $cool;
            $order->grand_total          = $grand;
            $order->base_grand_total     = $grand;
            $order->save();

            if ($note = trim((string) $note)) {
                DB::table('order_comments')->insert([
                    'order_id'          => $order->id,
                    'comment'           => $note,
                    'customer_notified' => 0,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }
        });

        // Reindex stock for every touched product so the storefront sees the
        // updated availability.
        UpdateCreateInventoryIndexJob::dispatchSync($touchedProductIds);

        Event::dispatch('sales.order.update.after', $order->fresh(['items']));

        return $order->fresh(['items']);
    }

    /**
     * Product search for the "add item" picker — simple products linked to the
     * order's channel, matched on SKU or product_flat.name (any locale).
     *
     * @return array{data:array<int,array>,page:int,per_page:int,total:int,has_more:bool}
     */
    public function searchProducts(Order $order, string $query = '', int $page = 1, int $perPage = 30): array
    {
        $page    = max(1, $page);
        $query   = trim($query);

        $productsQ = Product::query()
            ->where('type', 'simple')
            ->whereIn('id', function ($sub) use ($order) {
                $sub->select('product_id')
                    ->from('product_channels')
                    ->where('channel_id', $order->channel_id);
            });

        if ($query !== '') {
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
                'id'           => $p->id,
                'sku'          => $p->sku,
                'name'         => $row['name'] ?? $p->sku,
                'image'        => $row['image'] ?? null,
                'price'        => (float) ($row['price'] ?? 0),
                'qty_in_stock' => (int) ($row['qty_in_stock'] ?? 0),
            ];
        })->values()->toArray();

        return [
            'data'     => $data,
            'page'     => $page,
            'per_page' => $perPage,
            'total'    => $total,
            'has_more' => ($page * $perPage) < $total,
        ];
    }

    /**
     * Hydrate name/image/price/stock for a list of product IDs (channel/locale
     * aware), used by the picker and the order item list.
     *
     * @return array<int,array{name:?string,sku:?string,price:?float,image:?string,qty_in_stock:int}>
     */
    public function fetchProductInfo(array $productIds, ?int $channelId = null): array
    {
        if (empty($productIds)) {
            return [];
        }

        $locale      = app()->getLocale();
        $channelCode = $channelId ? (DB::table('channels')->where('id', $channelId)->value('code') ?? 'default') : 'default';

        $flats = DB::table('product_flat')
            ->whereIn('product_id', $productIds)
            ->get(['product_id', 'channel', 'locale', 'sku', 'name', 'price']);

        // Master price values — product_flat.price can drift out of sync with
        // the master attribute_values.float_value (seen in prod: flat=1395 while
        // master=1595). Read master so the picker always reflects the price an
        // admin actually set.
        $priceAttrId = DB::table('attributes')->where('code', 'price')->value('id');
        $masterPriceByProduct = [];
        if ($priceAttrId) {
            DB::table('product_attribute_values')
                ->whereIn('product_id', $productIds)
                ->where('attribute_id', $priceAttrId)
                ->get(['product_id', 'float_value', 'text_value'])
                ->each(function ($r) use (&$masterPriceByProduct) {
                    $v = $r->float_value !== null ? (float) $r->float_value
                       : ($r->text_value !== null ? (float) $r->text_value : null);
                    if ($v !== null) {
                        $masterPriceByProduct[$r->product_id] = $v;
                    }
                });
        }

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
            $imageByProduct[$img->product_id] = $imageByProduct[$img->product_id] ?? $img->path;
        }

        $stockByProduct = [];
        foreach ($stockRows as $s) {
            $stockByProduct[$s->product_id] = ($stockByProduct[$s->product_id] ?? 0) + (int) $s->qty;
        }

        $out = [];
        foreach ($productIds as $pid) {
            $candidates = $flats->where('product_id', $pid);

            $best = $candidates->first(fn ($r) => $r->channel === $channelCode && $r->locale === $locale && $r->name)
                ?? $candidates->first(fn ($r) => $r->channel === $channelCode && $r->name)
                ?? $candidates->first(fn ($r) => (bool) $r->name)
                ?? $candidates->first();

            $imagePath = $imageByProduct[$pid] ?? null;

            // Prefer master price; fall back to flat only when master is missing.
            $price = $masterPriceByProduct[$pid]
                ?? ($best?->price !== null ? (float) $best->price : null);

            $out[$pid] = [
                'name'         => $best?->name ?? $best?->sku ?? null,
                'sku'          => $best?->sku ?? null,
                'price'        => $price,
                'image'        => $imagePath ? Storage::url($imagePath) : null,
                'qty_in_stock' => $stockByProduct[$pid] ?? 0,
            ];
        }

        return $out;
    }

    /**
     * Increment/decrement product_ordered_inventories.qty for the channel.
     * Mirrors the cart→order pipeline at placement.
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
     * Resolve the product's tax category to a flat percentage against the
     * order's shipping address.
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
     * Re-derive shipping. v1: keeps the order's existing shipping_amount —
     * channel-rule changes (free-over-X) are handled via the manual override.
     */
    protected function recomputeShipping(Order $order, float $newSubTotal): float
    {
        return (float) $order->shipping_amount;
    }
}

<?php

namespace Fujiba\OrdersAdmin\Http\Controllers;

use Fujiba\OrdersAdmin\Exceptions\OrderEditException;
use Fujiba\OrdersAdmin\Services\OrderItemEditor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Sales\Repositories\OrderRepository;

/**
 * Admin order line-item editor (v1). Thin HTTP wrapper over OrderItemEditor —
 * all business logic (stock pre-flight, recompute, tax, ordered-inventory
 * accounting) lives in the shared service so the admin REST screen and the POS
 * GraphQL mutation behave identically.
 *
 * Scope: simple products only, only on orders in pending/processing.
 */
class OrderItemEditController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderItemEditor $itemEditor,
    ) {}

    /**
     * GET /api/orders/{id}/edit-items
     * Current items + recalc baseline for the modal. Names/images come from a
     * fresh product lookup, not the order_items snapshot (older orders saved
     * the SKU into order_items.name).
     */
    public function show(int $id): JsonResponse
    {
        $order = $this->orderRepository->with(['items.product', 'payment'])->findOrFail($id);

        $productIds = $order->items->pluck('product_id')->filter()->unique()->values()->toArray();
        $info = $this->itemEditor->fetchProductInfo($productIds, $order->channel_id);

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
                'cod_fee'         => (float) ($order->cod_fee ?? 0),
                'cool_surcharge'  => (float) ($order->cool_surcharge ?? 0),
                'grand_total'     => (float) $order->grand_total,
                'shipping_method' => $order->shipping_method,
                'shipping_title'  => $order->shipping_title,
                'payment_method'  => $order->payment?->method,
                'editable'        => $this->itemEditor->isEditable($order),
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
     * GET /api/orders/{id}/edit-items/search
     * Product picker for "add item" — simple products on the order's channel.
     */
    public function searchProducts(Request $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->findOrFail($id);

        return response()->json($this->itemEditor->searchProducts(
            $order,
            (string) $request->input('q', ''),
            (int) $request->input('page', 1),
            (int) $request->input('per_page', 30),
        ));
    }

    /**
     * POST /api/orders/{id}/edit-items
     *
     * Payload: { items:[{product_id,qty,order_item_id?}], shipping_amount?, note? }
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|integer',
            'items.*.qty'           => 'required|integer|min:1',
            'items.*.order_item_id' => 'nullable|integer',
            'shipping_amount'       => 'nullable|numeric|min:0',
            'note'                  => 'nullable|string|max:1000',
        ]);

        $order = $this->orderRepository->with(['items.product', 'channel', 'payment'])->findOrFail($id);

        try {
            $fresh = $this->itemEditor->apply(
                $order,
                $request->input('items'),
                $request->has('shipping_amount') ? $request->input('shipping_amount') : null,
                $request->input('note'),
            );
        } catch (OrderEditException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors'  => $e->errors,
            ], 422);
        }

        return response()->json([
            'message' => 'Order updated.',
            'order'   => [
                'id'              => $fresh->id,
                'sub_total'       => (float) $fresh->sub_total,
                'shipping_amount' => (float) $fresh->shipping_amount,
                'tax_amount'      => (float) $fresh->tax_amount,
                'discount_amount' => (float) $fresh->discount_amount,
                'cod_fee'         => (float) ($fresh->cod_fee ?? 0),
                'cool_surcharge'  => (float) ($fresh->cool_surcharge ?? 0),
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
}

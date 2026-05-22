<?php

namespace Webkul\Pos\Mutations\Outlet;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Checkout\Models\CartAddress;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\GraphQLAPI\Validators\CustomException;
use Webkul\Pos\Helpers\Order as OrderHelper;
use Webkul\Pos\Helpers\RoleGate;
use Webkul\Pos\Services\QualifiedInvoiceMapper;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\ShipmentRepository;

/**
 * Standalone "invoice builder" — create an invoice from scratch (pick a
 * customer, add products), independent of a prior cash sale.
 *
 * DESIGN (locked with the merchant 2026-05-21):
 *   • Each invoice is backed by a REAL Bagisto order so profit / stock /
 *     reporting reuse the core machinery (decision: "Real Order + Invoice").
 *   • Two independent status axes:
 *       document  : draft → created → shipped
 *       payment   : unpaid → partial → paid   (derived from amount_paid)
 *   • Stock: draft = NO stock impact (a quote). generateInvoice reserves;
 *     marking shipped deducts. So this builder must NOT route through the
 *     POS OrderRepository (that one auto-invoices + auto-ships every line).
 *
 * We therefore create the order directly via the core OrderRepository, then
 * write a pos_order row ourselves (outlet scoping) WITHOUT invoice/shipment.
 * The order is parked at status='draft'. generateInvoice (existing
 * InvoiceMutation) advances it to 'created'; setOrderShippingStatus advances
 * it to shipped and creates the shipment that deducts inventory.
 *
 * Profit visibility (cost) stays owner/manager-only — see InvoiceQuery.
 */
class InvoiceBuilderMutation
{
    public function __construct(
        protected OrderHelper $orderHelper,
        protected ProductRepository $productRepository,
        protected CustomerRepository $customerRepository,
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository,
        protected ShipmentRepository $shipmentRepository,
        protected QualifiedInvoiceMapper $mapper,
        protected OrderMutation $orderMutation,
    ) {}

    /**
     * Create a draft invoice = a draft order with line items but no invoice
     * and no shipment yet. Cashier+.
     */
    public function createDraft($root, array $args, GraphQLContext $context): array
    {
        RoleGate::requireCashier($context->user());

        $validator = Validator::make($args, [
            'idempotency_key'       => 'nullable|string|min:16|max:64|regex:/^[A-Za-z0-9_-]+$/',
            'customer_id'           => 'nullable|integer',
            'customer_first_name'   => 'required_without:customer_id|string',
            'customer_last_name'    => 'nullable|string',
            'customer_email'        => 'required_without:customer_id|string',
            'customer_phone'        => 'nullable|string',
            'order_discount_amount' => 'nullable|numeric|min:0|max:99999999',
            'order_discount_reason' => 'nullable|string|max:250',
            'note'                  => 'nullable|string|max:500',
            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|integer',
            'items.*.quantity'      => 'required|integer|min:1',
            'items.*.custom_price'  => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        // Idempotency: a re-submitted draft (flaky network) returns the
        // existing order rather than minting a duplicate.
        if (! empty($args['idempotency_key'])) {
            $existing = DB::table('pos_order')
                ->where('user_id', $context->user()->id)
                ->where('idempotency_key', $args['idempotency_key'])
                ->value('order_id');

            if ($existing) {
                return $this->result((int) $existing, trans('pos::app.outlet.order.sync-success'));
            }
        }

        $customer = $this->resolveCustomer($args);
        $outlet   = $context->user()->outlet;
        $channel  = core()->getDefaultChannel();

        $order = $this->buildOrderArray($args, $customer, $outlet, $channel);

        // Suppress the customer-facing "order placed" notification: a draft is
        // not a confirmed sale. Core fires checkout.order.save.after with
        // status=pending (it overwrites any status we pass), so we flag the
        // request and OrderEventListener skips order.placed while it's set.
        // Bind an explicit instance — this is the legitimate use of bound()
        // (a deliberate sentinel), NOT the autowire footgun from
        // feedback_app_bound_silent_skip.
        app()->instance('fujiba.suppress_order_placed', true);

        try {
            $order = DB::transaction(function () use ($order) {
                return $this->orderRepository->create($order);
            });
        } catch (\Throwable $e) {
            throw new CustomException($e->getMessage());
        } finally {
            app()->forgetInstance('fujiba.suppress_order_placed');
        }

        // Park as a draft. orders.status normally lands at 'pending'/'processing'
        // from core; force 'draft' so the list/stats query can bucket it.
        DB::table('orders')->where('id', $order->id)->update([
            'status'     => 'draft',
            'updated_at' => now(),
        ]);

        // pos_order row for outlet scoping (reuses the same columns the cash
        // sale writes, minus the cash/card totals — nothing is paid yet).
        DB::table('pos_order')->insert([
            'idempotency_key'       => $args['idempotency_key'] ?? null,
            'order_id'              => $order->id,
            'order_ref_id'          => 'DRAFT',
            'outlet_id'             => $context->user()->outlet_id,
            'user_id'               => $context->user()->id,
            'order_note'            => $args['note'] ?? '',
            'is_training'           => 0,
            'order_discount_amount' => ! empty($args['order_discount_amount'])
                ? round((float) $args['order_discount_amount'], 2) : null,
            'order_discount_reason' => ! empty($args['order_discount_amount'])
                ? ($args['order_discount_reason'] ?? 'unspecified') : null,
            'discount_amount'       => round($order->discount_amount, 2),
            'base_discount_amount'  => round($order->base_discount_amount, 2),
            'order_currency'        => $order->order_currency_code,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        return $this->result((int) $order->id, trans('pos::app.outlet.invoice.draft-created'));
    }

    /**
     * Resolve (or create) the customer for the draft. Prefers an explicit
     * customer_id (picked from search); otherwise updateOrCreate by email,
     * mirroring OrderMutation::syncOrder.
     */
    protected function resolveCustomer(array $args)
    {
        if (! empty($args['customer_id'])) {
            $found = $this->customerRepository->find((int) $args['customer_id']);
            if ($found) {
                return $found;
            }
        }

        return $this->customerRepository->updateOrCreate([
            'email' => $args['customer_email'],
        ], [
            'first_name' => $args['customer_first_name'] ?? 'Walk-in',
            'last_name'  => $args['customer_last_name'] ?? 'Customer',
            'phone'      => $args['customer_phone'] ?? null,
            'password'   => Hash::make(random_int(100000, 10000000)),
        ]);
    }

    /**
     * Build the Bagisto order payload from the builder's line items. Mirrors
     * OrderMutation::syncOrder's item loop but trimmed to the product types
     * the builder supports (simple / configurable / bundle); tax + line
     * discount reuse OrderMutation's resolver so behaviour stays identical.
     */
    protected function buildOrderArray(array $args, $customer, $outlet, $channel): array
    {
        $order = [
            'cart_id'                  => null,
            'is_guest'                 => 0,
            'customer_id'              => $customer->id,
            'customer_type'            => get_class($customer),
            'customer_email'           => $customer->email,
            'customer_first_name'      => $customer->first_name,
            'customer_last_name'       => $customer->last_name,
            'channel_id'               => $channel->id,
            'channel_name'             => $channel->name,
            'channel_type'             => get_class($channel),
            'total_item_count'         => 0,
            'total_qty_ordered'        => 0,
            'base_currency_code'       => core()->getBaseCurrencyCode(),
            'channel_currency_code'    => $channel->base_currency->code,
            'order_currency_code'      => core()->getCurrentCurrencyCode(),
            'grand_total'              => 0,
            'base_grand_total'         => 0,
            'sub_total'                => 0,
            'sub_total_incl_tax'       => 0,
            'base_sub_total'           => 0,
            'base_sub_total_incl_tax'  => 0,
            'tax_amount'               => 0,
            'base_tax_amount'          => 0,
            'shipping_tax_amount'      => 0,
            'base_shipping_tax_amount' => 0,
            'coupon_code'              => null,
            'applied_cart_rule_ids'    => null,
            'discount_amount'          => 0,
            'base_discount_amount'     => 0,
        ];

        $shippableItemsCount = 0;

        foreach ($args['items'] as $key => $line) {
            $qty     = max(1, (int) $line['quantity']);
            $product = $this->productRepository->findOrFail($line['product_id']);

            if ($product->isStockable()) {
                $shippableItemsCount += 1;
            }

            $children = [];
            $weight   = 0;

            if ($product->type === 'configurable' && ! empty($line['selected_configurable_option'])) {
                $child    = $this->productRepository->find((int) $line['selected_configurable_option']);
                $price    = $child->getTypeInstance()->getFinalPrice($qty);
                $weight   = (float) $child->weight;
                $children[] = $child;
            } elseif ($product->type === 'bundle' && ! empty($line['additional'])) {
                $additional    = $this->orderHelper->formatRequestedBundleOptions(json_decode($line['additional'], true));
                $childProducts = $product->getTypeInstance()->getCartChildProducts($additional);
                foreach ($childProducts as $cp) {
                    $childProduct = $this->productRepository->find($cp['product_id']);
                    $weight      += (float) $childProduct->weight;
                    $children[]   = $childProduct;
                }
                $price = $this->orderHelper->getBundleProductPrice($additional);
            } else {
                $price  = $product->getTypeInstance()->getFinalPrice($qty);
                $weight = (float) $product->weight;
            }

            // Per-line override price (markdown). Same maths as syncOrder.
            $discountAmount     = 0;
            $baseDiscountAmount = 0;
            $discountPercentage = 0;
            if (! empty($line['custom_price']) && $line['custom_price'] > 0 && $price > 0) {
                $baseDiscountAmount = ($price - (float) $line['custom_price']);
                $discountAmount     = core()->convertPrice($baseDiscountAmount);
                $discountPercentage = ($baseDiscountAmount / $price) / 100;
                $price              = (float) $line['custom_price'];
            }

            $taxPercent = $this->orderMutation->getTaxPercentage($product, $outlet) ?: 0;

            $productTaxAmount     = $taxPercent ? (core()->convertPrice($price * $qty) * $taxPercent) / 100 : 0;
            $productBaseTaxAmount = $taxPercent ? ($price * $qty * $taxPercent) / 100 : 0;

            $order['total_item_count']  += 1;
            $order['total_qty_ordered'] += $qty;

            $order['items'][$key] = [
                'product_id'           => $product->id,
                'product_type'         => get_class($product),
                'sku'                  => $product->sku,
                'type'                 => $product->type,
                'name'                 => $product->name,
                'weight'               => $weight,
                'total_weight'         => $weight * $qty,
                'qty_ordered'          => $qty,
                'price'                => core()->convertPrice($price),
                'price_incl_tax'       => core()->convertPrice($price + ($taxPercent ? $price * $taxPercent / 100 : 0)),
                'base_price'           => $price,
                'base_price_incl_tax'  => $price + ($taxPercent ? $price * $taxPercent / 100 : 0),
                'total'                => core()->convertPrice($price * $qty),
                'total_incl_tax'       => core()->convertPrice(($price * $qty) + $productTaxAmount),
                'base_total_incl_tax'  => ($price * $qty) + $productBaseTaxAmount,
                'base_total'           => $price * $qty,
                'tax_percent'          => $taxPercent,
                'tax_amount'           => $productTaxAmount,
                'base_tax_amount'      => $productBaseTaxAmount,
                'tax_category_id'      => null,
                'discount_percent'     => $discountPercentage,
                'discount_amount'      => $discountAmount,
                'base_discount_amount' => $baseDiscountAmount,
                'additional'           => ['locale' => core()->getCurrentLocale()->code],
            ];

            foreach ($children as $child) {
                $order['items'][$key]['children'][] = [
                    'product_id'   => $child->id,
                    'product_type' => get_class($child),
                    'sku'          => $child->sku,
                    'type'         => $child->type,
                    'name'         => $child->name,
                    'weight'       => $child->weight,
                    'total_weight' => 0,
                    'qty_ordered'  => 0,
                    'price'        => 1.0,
                    'price_incl_tax' => 1.0,
                    'base_price'   => 0,
                    'base_price_incl_tax' => 0,
                    'total'        => 0,
                    'total_incl_tax' => 0,
                    'base_total'   => 0,
                    'tax_percent'  => 0,
                    'tax_amount'   => 0,
                    'base_tax_amount' => 0,
                    'tax_category_id' => null,
                    'discount_percent' => 0,
                    'discount_amount'  => 0,
                    'base_discount_amount' => 0,
                    'additional'   => array_merge($child->additional ?? [], ['locale' => core()->getCurrentLocale()->code]),
                ];
            }

            $order['sub_total']               += core()->convertPrice($price * $qty);
            $order['sub_total_incl_tax']      += core()->convertPrice($price * $qty) + $productTaxAmount;
            $order['base_sub_total']          += $price * $qty;
            $order['base_sub_total_incl_tax'] += ($price * $qty) + $productBaseTaxAmount;
            $order['tax_amount']              += $productTaxAmount;
            $order['base_tax_amount']         += $productBaseTaxAmount;
            $order['discount_amount']         += $discountAmount;
            $order['base_discount_amount']    += $baseDiscountAmount;
            $order['grand_total']             += core()->convertPrice($price * $qty) + $productTaxAmount - $discountAmount;
            $order['base_grand_total']        += ($price * $qty) + $productBaseTaxAmount - $baseDiscountAmount;
        }

        foreach (['discount_amount', 'base_discount_amount', 'sub_total', 'base_sub_total',
            'sub_total_incl_tax', 'base_sub_total_incl_tax', 'grand_total', 'base_grand_total'] as $f) {
            $order[$f] = round($order[$f], 2);
        }

        $orderDiscount = (float) ($args['order_discount_amount'] ?? 0);
        if ($orderDiscount > 0) {
            $order['grand_total']      = round(max(0, $order['grand_total'] - core()->convertPrice($orderDiscount)), 2);
            $order['base_grand_total'] = round(max(0, $order['base_grand_total'] - $orderDiscount), 2);
        }

        $order['billing_address'] = $this->customerAddress($customer, CartAddress::ADDRESS_TYPE_BILLING, $outlet);

        if ($shippableItemsCount > 0) {
            $order = [...$order, ...$this->shippingDetails()];
            $order['shipping_address'] = $this->customerAddress($customer, CartAddress::ADDRESS_TYPE_SHIPPING, $outlet);
        }

        // No payment is taken at draft time; record a placeholder method so
        // core OrderRepository::create doesn't choke on a null payment block.
        $order['payment'] = ['method' => 'pos_invoice', 'method_title' => trans('pos::app.outlet.invoice.title')];

        return $order;
    }

    protected function customerAddress($customer, string $type, $outlet): array
    {
        return [
            'address_type' => $type,
            'first_name'   => $customer->first_name ?: 'Walk-in',
            'last_name'    => $customer->last_name ?: 'Customer',
            'gender'       => $customer->gender,
            'company_name' => $customer->company_name,
            'email'        => $customer->email,
            'phone'        => $customer->phone ?: ($outlet->phone ?: ''),
            'address'      => $outlet->address ?: ($outlet->name ?: '-'),
            'city'         => $outlet->city     ?: '-',
            'state'        => $outlet->state    ?: '-',
            'country'      => $outlet->country  ?: 'JP',
            'postcode'     => $outlet->postcode ?: '0000000',
        ];
    }

    protected function shippingDetails(): array
    {
        return [
            'shipping_method'               => 'pos_free_shipping',
            'shipping_title'                => trans('pos::app.outlet.shipping.title'),
            'shipping_description'          => trans('pos::app.outlet.shipping.description'),
            'shipping_amount'               => 0,
            'base_shipping_amount'          => 0,
            'shipping_amount_incl_tax'      => 0,
            'base_shipping_amount_incl_tax' => 0,
            'shipping_discount_amount'      => 0,
            'base_shipping_discount_amount' => 0,
        ];
    }

    protected function result(int $orderId, string $message): array
    {
        $invoiceId = (int) (DB::table('invoices')->where('order_id', $orderId)->value('id') ?? 0);

        return [
            'success'   => true,
            'message'   => $message,
            'orderId'   => $orderId,
            'invoiceId' => $invoiceId ?: null,
        ];
    }
}

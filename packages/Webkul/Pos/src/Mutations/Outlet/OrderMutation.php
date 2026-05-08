<?php

namespace Webkul\Pos\Mutations\Outlet;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Webkul\Checkout\Models\CartAddress;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\GraphQLAPI\Validators\CustomException;
use Webkul\Pos\Helpers\Order as OrderHelper;
use Webkul\Pos\Repositories\OrderRepository as PosOrderRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Tax\Repositories\TaxCategoryRepository;

class OrderMutation
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected OrderHelper $orderHelper,
        protected ProductRepository $productRepository,
        protected CustomerRepository $customerRepository,
        protected OrderRepository $orderRepository,
        protected PosOrderRepository $posOrderRepository,
        protected TaxCategoryRepository $taxCategoryRepository,
    ) {}

    /**
     * Confirm Payment
     */
    public function syncOrder($root, array $args, GraphQLContext $context)
    {
        $validator = Validator::make($args, [
            'idempotency_key'     => 'nullable|string|min:16|max:64|regex:/^[A-Za-z0-9_-]+$/',
            'customer_first_name' => 'required|string',
            'customer_last_name'  => 'required|string',
            'customer_email'      => 'required|string',
            'payment_mode'        => 'required|string|in:pos_cash,pos_card,pos_split',
            'cash_total'          => 'required_if:payment_mode,pos_cash,pos_split|numeric|min:0',
            'card_details'        => 'required_if:payment_mode,pos_card,pos_split|string',
            'bank_name'           => 'required_if:payment_mode,pos_card,pos_split|string',
            'training_mode'       => 'nullable|boolean',
            'order_items'         => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            throw new CustomException($validator->errors()->first());
        }

        if (! empty($args['idempotency_key'])) {
            $existing = $this->posOrderRepository
                ->where('user_id', $context->user()->id)
                ->where('idempotency_key', $args['idempotency_key'])
                ->first();

            if ($existing) {
                return [
                    'success'      => true,
                    'message'      => trans('pos::app.outlet.order.sync-success'),
                    'outlet_order' => $existing,
                ];
            }
        }

        $customer = $this->customerRepository->updateOrCreate([
            'email' => $args['customer_email'],
        ], [
            'first_name' => $args['customer_first_name'],
            'last_name'  => $args['customer_last_name'],
            'password'   => Hash::make(random_int(100000, 10000000)),
        ]);

        $outlet = $context->user()->outlet;

        $shippableItemsCount = 0;

        $channel = core()->getDefaultChannel();

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

        foreach ($args['order_items'] as $key => $orderItem) {
            $additional = json_decode($orderItem['additional'], true);

            $children = [];

            $weight = 0;

            $order['total_item_count'] += 1;

            $order['total_qty_ordered'] += $additional['quantity'];

            $product = $this->productRepository->findOrFail($orderItem['product_id']);

            if ($product->isStockable()) {
                $shippableItemsCount += 1;
            }

            if ($product->type == 'configurable') {
                if (! empty($additional['super_attribute'])) {
                    $additional['super_attribute'] = array_filter($additional['super_attribute']);
                }

                $child = $this->productRepository->find($additional['selected_configurable_option']);

                $price = $child->getTypeInstance()->getFinalPrice($additional['quantity']);

                $weight = (float) $child->weight;

                $children[] = $child;
            } elseif ($product->type == 'bundle') {
                $additional = $this->orderHelper->formatRequestedBundleOptions($additional);

                $childProducts = $product->getTypeInstance()->getCartChildProducts($additional);

                foreach ($childProducts as $child) {
                    $childProduct = $this->productRepository->find($child['product_id']);

                    $weight += (float) $childProduct->weight;

                    $children[] = $childProduct;
                }

                $price = $this->orderHelper->getBundleProductPrice($additional);
            } elseif ($product->type == 'downloadable') {
                $price = $product->getTypeInstance()->getFinalPrice($additional['quantity']);

                $price += $this->orderHelper->getDownloadLinkPrice($product, $additional);

                $weight = (float) $product->weight;
            } elseif ($product->type == 'booking') {
                $additional = $this->orderHelper->formatRequestedBookingOptions($product, $additional);

                $price = $this->orderHelper->getBookingProductPrice($additional);
            } else {
                $price = $product->getTypeInstance()->getFinalPrice($additional['quantity']);

                $weight = (float) $product->weight;

                if (! empty($additional['customizable_options'])) {
                    $additional = $this->orderHelper->formatRequestedCustomizableOptions(
                        $product,
                        $additional
                    );

                    $price += collect($additional['formatted_customizable_options'])->sum('total_price');
                }
            }

            $discountAmount = 0;
            $baseDiscountAmount = 0;
            $discountPercentage = 0;

            if (
                ! empty($orderItem['custom_price'])
                && $orderItem['custom_price'] > 0
            ) {
                $baseDiscountAmount = ($price - $orderItem['custom_price']);

                $discountAmount = core()->convertPrice($baseDiscountAmount);

                $discountPercentage = ($baseDiscountAmount / $price) / 100;
            }

            $taxPercent = $this->getTaxPercentage($product, $outlet);

            $productTaxAmount = $taxPercent
                ? (core()->convertPrice($price * $additional['quantity']) * $taxPercent) / 100
                : 0;

            $productBaseTaxAmount = $taxPercent
                ? ($price * $additional['quantity'] * $taxPercent) / 100
                : 0;

            $order['items'][$key] = [
                'product_id'           => $product->id,
                'product_type'         => get_class($product),
                'sku'                  => $product->sku,
                'type'                 => $product->type,
                'name'                 => $product->name,
                'weight'               => $weight,
                'total_weight'         => $weight * $additional['quantity'],
                'qty_ordered'          => $additional['quantity'],
                'price'                => core()->convertPrice($price),
                'price_incl_tax'       => core()->convertPrice($price + $productTaxAmount),
                'base_price'           => $price,
                'base_price_incl_tax'  => $price + $productBaseTaxAmount,
                'total'                => core()->convertPrice($price * $additional['quantity']),
                'total_incl_tax'       => core()->convertPrice(($price * $additional['quantity']) + $productTaxAmount),
                'base_total_incl_tax'  => ($price * $additional['quantity']) + $productBaseTaxAmount,
                'base_total'           => $price * $additional['quantity'],
                'tax_percent'          => $taxPercent,
                'tax_amount'           => $productTaxAmount,
                'base_tax_amount'      => $productBaseTaxAmount,
                'tax_category_id'      => null,
                'discount_percent'     => $discountPercentage,
                'discount_amount'      => $discountAmount,
                'base_discount_amount' => $baseDiscountAmount,
                'additional'           => array_merge($additional, [
                    'locale' => core()->getCurrentLocale()->code,
                ]),
            ];

            foreach ($children as $child) {
                $order['items'][$key]['children'][] = [
                    'product_id'           => $child->id,
                    'product_type'         => get_class($child),
                    'sku'                  => $child->sku,
                    'type'                 => $child->type,
                    'name'                 => $child->name,
                    'weight'               => $child->weight,
                    'total_weight'         => 0,
                    'qty_ordered'          => 0,
                    'price'                => 1.0,
                    'price_incl_tax'       => 1.0,
                    'base_price'           => 0,
                    'base_price_incl_tax'  => 0,
                    'total'                => 0,
                    'total_incl_tax'       => 0,
                    'base_total'           => 0,
                    'tax_percent'          => 0,
                    'tax_amount'           => 0,
                    'base_tax_amount'      => 0,
                    'tax_category_id'      => null,
                    'discount_percent'     => 0,
                    'discount_amount'      => 0,
                    'base_discount_amount' => 0,
                    'additional'           => array_merge($child->additional ?? [], [
                        'locale' => core()->getCurrentLocale()->code,
                    ]),
                ];
            }

            $order['sub_total'] += core()->convertPrice($price * $additional['quantity']);

            $order['sub_total_incl_tax'] += core()->convertPrice($price * $additional['quantity']) + $productTaxAmount;

            $order['base_sub_total'] += $price * $additional['quantity'];

            $order['base_sub_total_incl_tax'] += ($price * $additional['quantity']) + $productBaseTaxAmount;

            $order['tax_amount'] += $productTaxAmount;

            $order['base_tax_amount'] += $productBaseTaxAmount;

            $order['discount_amount'] += $discountAmount;

            $order['base_discount_amount'] += $baseDiscountAmount;

            $order['grand_total'] += core()->convertPrice($price * $additional['quantity']) + $productTaxAmount - $discountAmount;

            $order['base_grand_total'] += ($price * $additional['quantity']) + $productBaseTaxAmount - $baseDiscountAmount;
        }

        $order['discount_amount'] = round($order['discount_amount'], 2);
        $order['base_discount_amount'] = round($order['base_discount_amount'], 2);

        $order['sub_total'] = round($order['sub_total'], 2);
        $order['base_sub_total'] = round($order['base_sub_total'], 2);

        $order['sub_total_incl_tax'] = round($order['sub_total_incl_tax'], 2);
        $order['base_sub_total_incl_tax'] = round($order['base_sub_total_incl_tax'], 2);

        $order['grand_total'] = round($order['grand_total'], 2);
        $order['base_grand_total'] = round($order['base_grand_total'], 2);

        $order['billing_address'] = $this->getCustomerAddress(
            $customer,
            CartAddress::ADDRESS_TYPE_BILLING,
            $outlet
        );

        if ($shippableItemsCount > 0) {
            $order = [
                ...$order,
                ...$this->getShippingDetails(),
            ];

            $order['shipping_address'] = $this->getCustomerAddress(
                $customer,
                CartAddress::ADDRESS_TYPE_SHIPPING,
                $outlet
            );
        }

        $order['payment'] = $this->getPayment($args['payment_mode']);

        $order = $this->orderRepository->create($order);

        [$posOrder, $customerCredit] = $this->posOrderRepository->create([
            'order' => $order,
            'args'  => $args,
            'user'  => $context->user(),
        ]);

        return [
            'success'         => true,
            'message'         => trans('pos::app.outlet.order.sync-success'),
            'outlet_order'    => $posOrder,
            'customer_credit' => $customerCredit,
        ];
    }

    /**
     * Get Tax Percentage
     *
     * @return float
     */
    private function getTaxPercentage($product, $outlet)
    {
        $taxCategoryId = $product->tax_category_id;

        if (empty($taxCategoryId)) {
            $taxCategoryId = core()->getConfigData('sales.taxes.categories.product');
        }

        if (empty($taxCategoryId)) {
            return;
        }

        $taxPercent = 0;

        $taxCategory = $this->taxCategoryRepository->find($taxCategoryId);

        if (! $taxCategory) {
            return $taxPercent;
        }

        $taxRates = $taxCategory->tax_rates()->where([
            ['country', '=', $outlet->country],
            ['state', '=', $outlet->state],
        ])->orWhere([
            ['country', '=', $outlet->country],
            ['state', '=', ''],
        ])->orderBy('tax_rate', 'desc')->get();

        foreach ($taxRates as $rate) {
            $haveTaxRate = false;

            if ($rate->is_zip == 0) {
                if (
                    $rate->zip_code == ''
                    || $rate->zip_code == '*'
                    || $rate->zip_code == $outlet->postcode
                ) {
                    $haveTaxRate = true;
                }
            } else {
                if (
                    $outlet->postcode >= $rate->zip_from
                    && $outlet->postcode <= $rate->zip_to
                ) {
                    $haveTaxRate = true;
                }
            }

            if ($haveTaxRate) {
                $taxPercent = $rate->tax_rate;

                break;
            }
        }

        return $taxPercent;
    }

    /**
     * Get customer address
     */
    private function getCustomerAddress(object $customer, string $type, object $outlet): array
    {
        return [
            'address_type' => $type,
            'first_name'   => $customer->first_name,
            'last_name'    => $customer->last_name,
            'gender'       => $customer->gender,
            'company_name' => $customer->company_name,
            'email'        => $customer->email,
            'phone'        => $customer->phone ?? $outlet->phone,
            'address'      => $outlet->address,
            'city'         => $outlet->city,
            'state'        => $outlet->state,
            'country'      => $outlet->country,
            'postcode'     => $outlet->postcode,
        ];
    }

    /**
     * Get shipping details
     */
    private function getShippingDetails(): array
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

    /**
     * Get payment data
     */
    private function getPayment(string $paymentMode): array
    {
        switch ($paymentMode) {
            case 'pos_cash':
                return [
                    'method'       => 'pos_cash',
                    'method_title' => trans('pos::app.outlet.payment.options.cash.title'),
                ];
            case 'pos_card':
                return [
                    'method'       => 'pos_card',
                    'method_title' => trans('pos::app.outlet.payment.options.card.title'),
                ];
            case 'pos_split':
                return [
                    'method'       => 'pos_split',
                    'method_title' => trans('pos::app.outlet.payment.options.split.title'),
                ];
        }

        return [];
    }
}

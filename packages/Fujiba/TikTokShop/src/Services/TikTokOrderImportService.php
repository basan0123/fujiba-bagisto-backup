<?php

namespace Fujiba\TikTokShop\Services;

use Fujiba\AutoLogin\Services\TikTokCustomerResolver;
use Fujiba\TikTokShop\Models\TikTokCredential;
use Fujiba\TikTokShop\Models\TikTokOrderMap;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;
use Webkul\Customer\Models\Customer;
use Webkul\Sales\Repositories\OrderRepository;

/**
 * Imports a TikTok Shop webhook payload as a Bagisto order.
 *
 * Calls the canonical Webkul\Sales\Repositories\OrderRepository::create()
 * (which fires checkout.order.save.before / .after, creates OrderItems,
 * OrderPayment, OrderAddress, etc.). Modeled on
 * Webkul\Pos\Mutations\Outlet\OrderMutation::syncOrder.
 *
 * Channel resolution: comes from the active TikTokCredential (looked up
 * via the channel_id in the credentials row, NOT from the webhook payload).
 * Webhooks must not trust client-supplied channel hints.
 *
 * Customer resolution: delegated to Fujiba\AutoLogin\Services\TikTokCustomerResolver.
 *   - With buyer_open_id  → resolveOrCreate (link customer to open_id)
 *   - Without buyer_open_id → guest order (customer_id null, is_guest=1)
 *
 * Dedup: tiktok_order_map.unique('tiktok_order_id') is the deduplication
 * guard. A duplicate webhook for the same tiktok_order_id throws a
 * QueryException on insert; we catch it, look up the existing map row,
 * and return that — caller treats this as success.
 *
 * Defaults for TikTok-payload-missing fields (documented in
 * SPRINT_2_DISCOVERY.md "Defaults for TikTok-missing order fields"):
 *   - tax_amount         = 0       (TikTok orders are tax-inclusive at line level)
 *   - shipping_amount    = $payload['shipping_fee'] ?? 0
 *   - billing_address    = synthesized from buyer profile + shipping address
 *   - shipping_address   = $payload['recipient_address']
 *   - payment.method     = 'tiktok_shop'  (sentinel)
 *   - currency           = base currency  (TikTok JP returns JPY; multi-currency
 *                          TikTok shops will need expansion in a later step)
 */
class TikTokOrderImportService
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected TikTokCustomerResolver $customerResolver,
    ) {}

    /**
     * Import a TikTok webhook payload. Returns the created TikTokOrderMap row.
     *
     * The map row's import_status is 'imported' on success or 'failed' with
     * import_error populated on failure.
     */
    public function import(int $channelId, array $payload): TikTokOrderMap
    {
        $tiktokOrderId = (string) ($payload['order_id'] ?? '');
        if ($tiktokOrderId === '') {
            throw new RuntimeException('TikTok webhook payload missing order_id');
        }

        // Pre-flight log per master prompt §0.8
        Log::info('[TikTokShop] order import context', [
            'db'              => DB::connection()->getDatabaseName(),
            'tenant'          => function_exists('tenant') && tenant() ? tenant('id') : 'central',
            'tiktok_order_id' => $tiktokOrderId,
            'channel_id'      => $channelId,
        ]);

        // Channel resolution from credentials, not webhook payload.
        $creds = TikTokCredential::where('channel_id', $channelId)->where('is_active', true)->first();
        if (! $creds) {
            return $this->markFailed($tiktokOrderId, $channelId, null, 'No active TikTok credentials for channel ' . $channelId);
        }

        // Dedup pre-check (cheaper than catching the unique-violation exception).
        $existing = TikTokOrderMap::where('tiktok_order_id', $tiktokOrderId)->first();
        if ($existing) {
            Log::info('[TikTokShop] order already imported, skipping', [
                'tiktok_order_id'  => $tiktokOrderId,
                'bagisto_order_id' => $existing->bagisto_order_id,
            ]);
            return $existing;
        }

        try {
            $customer = $this->resolveCustomer($payload, $channelId);
            $orderArr = $this->buildOrderArray($payload, $channelId, $customer);

            // OrderRepository::create handles items, addresses, payment, events
            $order = $this->orderRepository->create($orderArr);

            return TikTokOrderMap::create([
                'tiktok_order_id'  => $tiktokOrderId,
                'bagisto_order_id' => $order->id,
                'channel_id'       => $channelId,
                'import_status'    => 'imported',
            ]);
        } catch (QueryException $e) {
            // SQLSTATE 23000 = unique-constraint violation. If our pre-check raced
            // with another inflight import (queue worker contention), the unique
            // index is the safety net.
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'tiktok_order_id')) {
                $existing = TikTokOrderMap::where('tiktok_order_id', $tiktokOrderId)->first();
                if ($existing) {
                    return $existing;
                }
            }
            return $this->markFailed($tiktokOrderId, $channelId, null, 'DB error: ' . $e->getMessage());
        } catch (Throwable $e) {
            return $this->markFailed($tiktokOrderId, $channelId, null, 'Exception: ' . $e->getMessage());
        }
    }

    private function resolveCustomer(array $payload, int $channelId): ?Customer
    {
        $openId = $payload['buyer_open_id'] ?? null;
        if (! $openId) {
            return null;  // guest path
        }

        // TikTok JP API returns buyer_name as a single field; profile handed
        // to the resolver is best-effort. Email is intentionally omitted —
        // TikTok does not return buyer email by default (privacy policy),
        // so the resolver will use the synthetic tt_{openId}@tiktok.local.
        $name = (string) ($payload['buyer_name'] ?? 'TikTok User');
        $parts = explode(' ', $name, 2);
        $profile = [
            'first_name' => $parts[0] ?: 'TikTok',
            'last_name'  => $parts[1] ?? 'User',
        ];

        return $this->customerResolver->resolveOrCreate($openId, $channelId, $profile);
    }

    /**
     * Build the full Bagisto order array from a TikTok webhook payload.
     * Modeled on Webkul\Pos\Mutations\Outlet\OrderMutation::syncOrder.
     */
    private function buildOrderArray(array $payload, int $channelId, ?Customer $customer): array
    {
        $channel  = core()->getDefaultChannel();
        $items    = $this->buildItems($payload['items'] ?? []);
        $shipping = (float) ($payload['shipping_fee'] ?? 0);

        $subTotal = 0.0;
        $totalQty = 0;
        $totalCount = 0;
        foreach ($items as $i) {
            $subTotal   += (float) $i['total'];
            $totalQty   += (int) $i['qty_ordered'];
            $totalCount += 1;
        }

        $grandTotal = round($subTotal + $shipping, 2);

        $openId = $payload['buyer_open_id'] ?? null;

        $order = [
            'cart_id'                       => null,
            'is_guest'                      => $customer ? 0 : 1,
            'customer_id'                   => $customer?->id,
            'customer_type'                 => $customer ? get_class($customer) : null,
            'customer_email'                => $customer?->email
                ?? ($openId ? $this->customerResolver->syntheticEmail($openId) : 'guest@tiktok.local'),
            'customer_first_name'           => $customer?->first_name ?? 'TikTok',
            'customer_last_name'            => $customer?->last_name ?? 'Guest',
            'channel_id'                    => $channel->id,
            'channel_name'                  => $channel->name,
            'channel_type'                  => get_class($channel),  // FQCN per existing OrderMutation precedent
            'total_item_count'              => $totalCount,
            'total_qty_ordered'             => $totalQty,
            'base_currency_code'            => core()->getBaseCurrencyCode(),
            'channel_currency_code'         => $channel->base_currency->code,
            'order_currency_code'           => core()->getCurrentCurrencyCode(),
            'grand_total'                   => $grandTotal,
            'base_grand_total'              => $grandTotal,
            'sub_total'                     => round($subTotal, 2),
            'sub_total_incl_tax'            => round($subTotal, 2),
            'base_sub_total'                => round($subTotal, 2),
            'base_sub_total_incl_tax'       => round($subTotal, 2),
            'tax_amount'                    => 0,
            'base_tax_amount'               => 0,
            'shipping_tax_amount'           => 0,
            'base_shipping_tax_amount'      => 0,
            'coupon_code'                   => null,
            'applied_cart_rule_ids'         => null,
            'discount_amount'               => 0,
            'base_discount_amount'          => 0,
            'items'                         => $items,
            'billing_address'               => $this->buildAddress($payload, $customer, 'billing'),
            'shipping_address'              => $this->buildAddress($payload, $customer, 'shipping'),
            'shipping_method'               => 'tiktok_shop_shipping',
            'shipping_title'                => 'TikTok Shop',
            'shipping_description'          => 'TikTok Shop fulfillment',
            'shipping_amount'               => $shipping,
            'base_shipping_amount'          => $shipping,
            'shipping_amount_incl_tax'      => $shipping,
            'base_shipping_amount_incl_tax' => $shipping,
            'shipping_discount_amount'      => 0,
            'base_shipping_discount_amount' => 0,
            'payment'                       => [
                'method'       => 'tiktok_shop',
                'method_title' => 'TikTok Shop',
            ],
        ];

        return $order;
    }

    /**
     * Translate TikTok line items into Bagisto order items. Each TikTok item
     * is matched to a Bagisto product via tiktok_product_map (synced in
     * Step 4); items with no map row are skipped with a logged warning
     * (better partial-import than total-fail for known SKUs).
     */
    private function buildItems(array $tiktokItems): array
    {
        $out = [];

        foreach ($tiktokItems as $key => $line) {
            $tiktokProductId = (string) ($line['product_id'] ?? '');
            if ($tiktokProductId === '') {
                Log::warning('[TikTokShop] order item missing product_id, skipping', ['line' => $line]);
                continue;
            }

            $map = \Fujiba\TikTokShop\Models\TikTokProductMap::where('tiktok_product_id', $tiktokProductId)->first();
            if (! $map) {
                Log::warning('[TikTokShop] order item references unknown TikTok product, skipping', [
                    'tiktok_product_id' => $tiktokProductId,
                ]);
                continue;
            }

            $product = \Webkul\Product\Models\Product::find($map->bagisto_product_id);
            if (! $product) {
                Log::warning('[TikTokShop] order item product no longer exists, skipping', [
                    'bagisto_product_id' => $map->bagisto_product_id,
                ]);
                continue;
            }

            $qty   = (int) ($line['quantity'] ?? 1);
            $price = (float) ($line['unit_price'] ?? $product->price ?? 0);
            $total = $qty * $price;

            $out[$key] = [
                'product_id'           => $product->id,
                'product_type'         => get_class($product),
                'sku'                  => $product->sku,
                'type'                 => $product->type,
                'name'                 => $product->name,
                'weight'               => 0,
                'total_weight'         => 0,
                'qty_ordered'          => $qty,
                'price'                => $price,
                'price_incl_tax'       => $price,
                'base_price'           => $price,
                'base_price_incl_tax'  => $price,
                'total'                => $total,
                'total_incl_tax'       => $total,
                'base_total'           => $total,
                'base_total_incl_tax'  => $total,
                'tax_percent'          => 0,
                'tax_amount'           => 0,
                'base_tax_amount'      => 0,
                'tax_category_id'      => null,
                'discount_percent'     => 0,
                'discount_amount'      => 0,
                'base_discount_amount' => 0,
                'additional'           => [
                    'quantity' => $qty,
                    'locale'   => core()->getCurrentLocale()->code,
                ],
            ];
        }

        return $out;
    }

    /**
     * Bagisto requires both billing and shipping address shapes on the order
     * array. TikTok webhook returns recipient_address (a single shipping
     * address) plus buyer info. We synthesize billing from the same data
     * since TikTok doesn't expose a separate billing address.
     */
    private function buildAddress(array $payload, ?Customer $customer, string $type): array
    {
        $recipient = $payload['recipient_address'] ?? [];

        return [
            'address_type' => $type === 'billing'
                ? \Webkul\Checkout\Models\CartAddress::ADDRESS_TYPE_BILLING
                : \Webkul\Checkout\Models\CartAddress::ADDRESS_TYPE_SHIPPING,
            'first_name'   => $customer?->first_name ?? ($payload['buyer_name'] ?? 'TikTok'),
            'last_name'    => $customer?->last_name ?? 'Guest',
            'email'        => $customer?->email ?? 'guest@tiktok.local',
            'phone'        => $recipient['phone'] ?? '0000000000',
            'address'      => is_array($recipient['address_line'] ?? null)
                ? implode("\n", $recipient['address_line'])
                : ((string) ($recipient['address_line'] ?? 'Unknown')),
            'city'         => (string) ($recipient['city'] ?? 'Unknown'),
            'state'        => (string) ($recipient['state'] ?? ''),
            'country'      => (string) ($recipient['region'] ?? 'JP'),
            'postcode'     => (string) ($recipient['zipcode'] ?? '0000000'),
        ];
    }

    private function markFailed(string $tiktokOrderId, int $channelId, ?int $bagistoOrderId, string $error): TikTokOrderMap
    {
        Log::warning('[TikTokShop] order import failed', [
            'tiktok_order_id' => $tiktokOrderId,
            'channel_id'      => $channelId,
            'error'           => $error,
        ]);

        return TikTokOrderMap::updateOrCreate(
            ['tiktok_order_id' => $tiktokOrderId],
            [
                'bagisto_order_id' => $bagistoOrderId,
                'channel_id'       => $channelId,
                'import_status'    => 'failed',
                'import_error'     => $error,
            ]
        );
    }
}

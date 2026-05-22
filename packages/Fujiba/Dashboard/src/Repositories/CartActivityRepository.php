<?php

namespace Fujiba\Dashboard\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cart Activity = latest 50 carts (active + abandoned + checked-out)
 * surfaced on the admin dashboard with customer mini-profile and a
 * detail modal payload (line items).
 *
 * Channel filter: code-string at the boundary, matches W1 envelope.
 *   '' or null  → all channels
 *   'default'   → kimono
 *   'mmfood'    → mmfood
 *
 * Conversion state derives from `orders` join — if any order exists for
 * the cart's customer + grand_total + same day window, mark as converted.
 * Otherwise → abandoned (still in cart) or active (last touched < 1h).
 */
class CartActivityRepository
{
    private const LIMIT = 50;
    private const ACTIVE_WINDOW_MINUTES = 60;

    /**
     * @param  string|null  $channelCode
     * @return array<int, array<string, mixed>>
     */
    public function latest(?string $channelCode): array
    {
        $channelId = $this->resolveChannelId($channelCode);

        $rows = DB::table('cart as c')
            ->leftJoin('customers as cu', 'cu.id', '=', 'c.customer_id')
            ->leftJoin('channels as ch', 'ch.id', '=', 'c.channel_id')
            ->select([
                'c.id',
                'c.customer_id',
                'c.customer_email',
                'c.customer_first_name',
                'c.customer_last_name',
                'c.is_guest',
                'c.is_active',
                'c.items_count',
                'c.items_qty',
                'c.sub_total',
                'c.grand_total',
                'c.cart_currency_code',
                'c.coupon_code',
                'c.created_at',
                'c.updated_at',
                'c.channel_id',
                'ch.code as channel_code',
                'cu.first_name as customer_first',
                'cu.last_name as customer_last',
                'cu.email as customer_real_email',
                'cu.image as customer_image',
                'cu.phone as customer_phone',
            ])
            ->whereNotNull('c.items_count')
            ->where('c.items_count', '>', 0)
            ->when($channelId, fn ($q) => $q->where('c.channel_id', $channelId))
            ->orderByDesc('c.updated_at')
            ->limit(self::LIMIT)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        // Bulk-fetch lifetime order count per customer (registered users) and
        // per customer_email (guests reusing same email). Two queries, then
        // hashmap lookup per row — avoids N+1 against `orders`.
        $customerIds = $rows->pluck('customer_id')->filter()->unique()->values()->all();
        $emails      = $rows->pluck('customer_email')->filter()->unique()->values()->all();

        $countsById = [];
        if (! empty($customerIds)) {
            $countsById = DB::table('orders')
                ->select('customer_id', DB::raw('COUNT(*) as c'))
                ->whereIn('customer_id', $customerIds)
                ->groupBy('customer_id')
                ->pluck('c', 'customer_id')
                ->all();
        }
        $countsByEmail = [];
        if (! empty($emails)) {
            $countsByEmail = DB::table('orders')
                ->select('customer_email', DB::raw('COUNT(*) as c'))
                ->whereIn('customer_email', $emails)
                ->groupBy('customer_email')
                ->pluck('c', 'customer_email')
                ->all();
        }

        $now = Carbon::now();

        return $rows->map(function ($row) use ($now, $countsById, $countsByEmail) {
            $updatedAt = $row->updated_at ? Carbon::parse($row->updated_at) : null;
            $isActive  = $updatedAt
                ? $updatedAt->diffInMinutes($now) <= self::ACTIVE_WINDOW_MINUTES
                : false;

            $converted = $this->cartConverted($row);

            $state = $converted
                ? 'converted'
                : ($isActive ? 'active' : 'abandoned');

            $purchaseCount = 0;
            if ($row->customer_id && isset($countsById[$row->customer_id])) {
                $purchaseCount = (int) $countsById[$row->customer_id];
            } elseif ($row->customer_email && isset($countsByEmail[$row->customer_email])) {
                $purchaseCount = (int) $countsByEmail[$row->customer_email];
            }

            return [
                'cart_id'          => (int) $row->id,
                'customer_id'      => $row->customer_id ? (int) $row->customer_id : null,
                'is_guest'         => (bool) $row->is_guest,
                'display_name'     => $this->displayName($row),
                'email'            => $row->customer_real_email ?: $row->customer_email,
                'purchase_count'   => $purchaseCount,
                'phone'            => $row->customer_phone,
                'avatar'           => $this->resolveAvatar($row),
                'avatar_initial'   => $this->initial($row),
                'channel_code'     => $row->channel_code,
                'items_count'      => (int) ($row->items_count ?? 0),
                'items_qty'        => (float) ($row->items_qty ?? 0),
                'sub_total'        => (float) ($row->sub_total ?? 0),
                'grand_total'      => (float) ($row->grand_total ?? 0),
                'currency'         => $row->cart_currency_code ?: 'JPY',
                'coupon_code'      => $row->coupon_code,
                'state'            => $state,
                'updated_at'       => $updatedAt ? $updatedAt->toIso8601String() : null,
                'updated_human'    => $updatedAt ? $updatedAt->diffForHumans() : null,
                'created_at'       => $row->created_at ? Carbon::parse($row->created_at)->toIso8601String() : null,
            ];
        })->all();
    }

    /**
     * Cart detail payload for modal — items + addresses + payment method.
     */
    public function detail(int $cartId): ?array
    {
        $cart = DB::table('cart as c')
            ->leftJoin('customers as cu', 'cu.id', '=', 'c.customer_id')
            ->leftJoin('channels as ch', 'ch.id', '=', 'c.channel_id')
            ->select([
                'c.*',
                'ch.code as channel_code',
                'cu.first_name as customer_first',
                'cu.last_name as customer_last',
                'cu.email as customer_real_email',
                'cu.image as customer_image',
                'cu.phone as customer_phone',
            ])
            ->where('c.id', $cartId)
            ->first();

        if (! $cart) {
            return null;
        }

        $itemRows = DB::table('cart_items as ci')
            ->leftJoin('products as p', 'p.id', '=', 'ci.product_id')
            ->leftJoin('product_attribute_values as pav', function ($j) {
                // attr 12 = cost (per BusinessOverviewRepository::ATTR_COST memory)
                $j->on('pav.product_id', '=', 'p.id')->where('pav.attribute_id', 12);
            })
            ->select([
                'ci.id', 'ci.sku', 'ci.name', 'ci.quantity', 'ci.price', 'ci.total',
                'ci.tax_amount', 'ci.discount_amount', 'ci.type',
                'pav.float_value as cost_price',
            ])
            ->where('ci.cart_id', $cartId)
            ->whereNull('ci.parent_id')
            ->orderBy('ci.id')
            ->get();

        $totalCost = 0.0;
        $items = $itemRows->map(function ($i) use (&$totalCost) {
            $cost = (float) ($i->cost_price ?? 0);
            $lineCost = $cost * (int) $i->quantity;
            $totalCost += $lineCost;
            return [
                'sku'             => $i->sku,
                'name'            => $i->name,
                'quantity'        => (int) $i->quantity,
                'price'           => (float) $i->price,
                'total'           => (float) $i->total,
                'tax_amount'      => (float) $i->tax_amount,
                'discount_amount' => (float) $i->discount_amount,
                'cost'            => $cost,
                'line_cost'       => $lineCost,
                'type'            => $i->type,
            ];
        })->all();

        $payment = DB::table('cart_payment')
            ->where('cart_id', $cartId)
            ->value('method');

        // Profit per kimono formula: sub_total − discount − cost (carts don't have cool_surcharge).
        // Memory: project_kimono_profit_formula — shipping/COD are pass-through, NOT subtracted.
        $subTotal       = (float) ($cart->sub_total ?? 0);
        $discountAmount = (float) ($cart->discount_amount ?? 0);
        $estimatedProfit = $subTotal - $discountAmount - $totalCost;
        $profitMargin = $subTotal > 0 ? round(($estimatedProfit / $subTotal) * 100, 1) : 0.0;

        return [
            'cart_id'        => (int) $cart->id,
            'customer_id'    => $cart->customer_id ? (int) $cart->customer_id : null,
            'display_name'   => $this->displayName($cart),
            'email'          => $cart->customer_real_email ?: $cart->customer_email,
            'phone'          => $cart->customer_phone,
            'avatar'         => $this->resolveAvatar($cart),
            'avatar_initial' => $this->initial($cart),
            'channel_code'   => $cart->channel_code,
            'is_guest'       => (bool) $cart->is_guest,
            'items_count'    => (int) ($cart->items_count ?? 0),
            'sub_total'      => (float) ($cart->sub_total ?? 0),
            'tax_total'      => (float) ($cart->tax_total ?? 0),
            'discount_amount'=> (float) ($cart->discount_amount ?? 0),
            'shipping_amount'=> (float) ($cart->shipping_amount ?? 0),
            'grand_total'    => (float) ($cart->grand_total ?? 0),
            'currency'       => $cart->cart_currency_code ?: 'JPY',
            'coupon_code'    => $cart->coupon_code,
            'shipping_method'=> $cart->shipping_method,
            'payment_method' => $payment,
            'total_cost'     => $totalCost,
            'estimated_profit' => $estimatedProfit,
            'profit_margin'  => $profitMargin,
            'created_at'     => $cart->created_at ? Carbon::parse($cart->created_at)->toIso8601String() : null,
            'updated_at'     => $cart->updated_at ? Carbon::parse($cart->updated_at)->toIso8601String() : null,
            'items'          => $items,
        ];
    }

    private function cartConverted(object $row): bool
    {
        if (! $row->customer_id || ! $row->updated_at) {
            return false;
        }

        $window = Carbon::parse($row->updated_at);

        return DB::table('orders')
            ->where('customer_id', $row->customer_id)
            ->whereBetween('created_at', [
                $window->copy()->subMinutes(30),
                $window->copy()->addMinutes(30),
            ])
            ->where('grand_total', $row->grand_total)
            ->exists();
    }

    private function displayName(object $row): string
    {
        $first = trim((string) ($row->customer_first ?? $row->customer_first_name ?? ''));
        $last  = trim((string) ($row->customer_last ?? $row->customer_last_name ?? ''));
        $name  = trim($first.' '.$last);

        if ($name !== '') {
            return $name;
        }

        if (! empty($row->customer_real_email)) {
            return $row->customer_real_email;
        }

        if (! empty($row->customer_email)) {
            return $row->customer_email;
        }

        return $row->is_guest
            ? trans('fujiba::app.dashboard.cart_activity.guest')
            : '#'.$row->id;
    }

    private function initial(object $row): string
    {
        $first = trim((string) ($row->customer_first ?? $row->customer_first_name ?? ''));
        if ($first !== '') {
            return mb_strtoupper(mb_substr($first, 0, 1));
        }

        $email = (string) ($row->customer_real_email ?? $row->customer_email ?? '');
        if ($email !== '') {
            return mb_strtoupper(mb_substr($email, 0, 1));
        }

        return '?';
    }

    private function resolveAvatar(object $row): ?string
    {
        $img = $row->customer_image ?? null;
        if (! $img) {
            return null;
        }

        if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
            return $img;
        }

        return '/storage/'.ltrim($img, '/');
    }

    private function resolveChannelId(?string $code): ?int
    {
        if ($code === null || $code === '') {
            return null;
        }

        $cacheKey = 'fujiba.dashboard.channel_id.'.$code;
        $id = cache()->remember($cacheKey, 300, fn () => DB::table('channels')->where('code', $code)->value('id'));

        return $id ? (int) $id : null;
    }
}

<?php

namespace Fujiba\OrdersAdmin\Http\Controllers;

use Fujiba\InventoryControl\Support\BarcodeNormalizer;
use Fujiba\LiveChat\Models\LiveChatSetting;
use Fujiba\LiveChat\Services\ChatpionFailoverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;

class OrderListController extends Controller
{
    /**
     * Per-request lookup of Chatpion profile_pic by subscriber_id.
     * Built once per page in index(), consumed inside transform().
     */
    protected array $chatpionProfilePics = [];

    /**
     * Per-request product cost map: [product_id => cost_float|null].
     * Built once per page so the profit column doesn't N+1 on EAV.
     */
    protected array $productCosts = [];

    public function index(Request $request): JsonResponse
    {
        if (! bouncer()->hasPermission('sales.orders')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 10)));

        // Whitelist sortable columns; anything else falls back to id desc.
        $sortBy  = $request->input('sort_by', 'id');
        $sortDir = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = [
            'id'           => 'orders.id',
            'increment_id' => 'orders.id',           // increment_id is monotonic with id
            'created_at'   => 'orders.created_at',
            'grand_total'  => 'orders.grand_total',
        ];
        $sortColumn = $allowedSorts[$sortBy] ?? 'orders.id';

        $query = Order::query()
            ->with([
                'items',
                'payment',
                'customer',
                'shipments',
                'comments',
                'addresses' => fn ($q) => $q->where('address_type', OrderAddress::ADDRESS_TYPE_SHIPPING),
            ])
            ->orderBy($sortColumn, $sortDir);

        // Status filter — accepts a single string or an array. "shipped" is a
        // virtual status (label_printed=1) so it's handled separately and may
        // be combined with real statuses via OR. When "shipped" is NOT
        // selected, real-status matches must exclude already-shipped rows or
        // they bleed back in (a row with status='pending' + label_printed=1
        // displays as "Shipped" in the UI).
        $statuses = (array) $request->input('status', []);
        $statuses = array_values(array_filter(array_map('strval', $statuses), fn ($v) => $v !== ''));
        if (! empty($statuses)) {
            $hasShipped = in_array('shipped', $statuses, true);
            $real       = array_values(array_diff($statuses, ['shipped']));
            $query->where(function ($q) use ($real, $hasShipped) {
                if (! empty($real)) {
                    $q->where(function ($qq) use ($real, $hasShipped) {
                        $qq->whereIn('status', $real);
                        if (! $hasShipped) {
                            $qq->where(function ($qqq) {
                                $qqq->whereNull('fujiba_label_printed')
                                    ->orWhere('fujiba_label_printed', 0);
                            });
                        }
                    });
                }
                if ($hasShipped) {
                    $q->orWhere('fujiba_label_printed', 1);
                }
            });
        }

        // Channel filter — accepts a single id or an array.
        $channelIds = (array) $request->input('channel_id', []);
        $channelIds = array_values(array_filter(array_map('intval', $channelIds), fn ($v) => $v > 0));
        if (! empty($channelIds)) {
            $query->whereIn('channel_id', $channelIds);
        }

        if ($search = trim((string) $request->input('search', ''))) {
            // Smart search: a single string entered by support must match across
            // many fields. Free-text matches identifiers/names/email/address;
            // a normalized digits-only version matches phone/postcode/tracking
            // even when the user typed Burmese (၀-၉) or fullwidth (０-９) digits.
            $like        = "%{$search}%";
            $digitsOnly  = preg_replace('/\D+/u', '', BarcodeNormalizer::normalize($search));
            $digitsLike  = $digitsOnly !== '' ? "%{$digitsOnly}%" : null;
            $hasSagawa   = Schema::hasTable('sagawa_shipments');

            $query->where(function ($q) use ($search, $like, $digitsLike, $hasSagawa) {
                // Identifiers
                $q->where('orders.increment_id', 'LIKE', $like)
                  ->orWhere('orders.id', '=', ctype_digit($search) ? (int) $search : 0)
                  ->orWhere('orders.customer_id', '=', ctype_digit($search) ? (int) $search : 0);

                // Customer fields on the order row + customers table
                $q->orWhere('orders.customer_email', 'LIKE', $like)
                  ->orWhereRaw('CONCAT(orders.customer_first_name, " ", orders.customer_last_name) LIKE ?', [$like])
                  ->orWhereExists(function ($sub) use ($like, $digitsLike) {
                      $sub->select(DB::raw(1))
                          ->from('customers')
                          ->whereColumn('customers.id', 'orders.customer_id')
                          ->where(function ($w) use ($like, $digitsLike) {
                              $w->where('customers.email', 'LIKE', $like)
                                ->orWhereRaw('CONCAT(customers.first_name, " ", customers.last_name) LIKE ?', [$like]);
                              if ($digitsLike) {
                                  $w->orWhere('customers.phone', 'LIKE', $digitsLike);
                              }
                          });
                  });

                // Address text — matches kanji address, postcode, city, state, phone
                $q->orWhereExists(function ($sub) use ($like, $digitsLike) {
                    $sub->select(DB::raw(1))
                        ->from('addresses')
                        ->whereColumn('addresses.order_id', 'orders.id')
                        ->where(function ($w) use ($like, $digitsLike) {
                            $w->where('addresses.address',  'LIKE', $like)
                              ->orWhere('addresses.city',   'LIKE', $like)
                              ->orWhere('addresses.state',  'LIKE', $like)
                              ->orWhereRaw('CONCAT(addresses.first_name, " ", addresses.last_name) LIKE ?', [$like]);
                            if ($digitsLike) {
                                $w->orWhere('addresses.postcode', 'LIKE', $digitsLike)
                                  ->orWhere('addresses.phone',    'LIKE', $digitsLike);
                            }
                        });
                });

                // Tracking number — Webkul's shipments.track_number
                $q->orWhereExists(function ($sub) use ($like) {
                    $sub->select(DB::raw(1))
                        ->from('shipments')
                        ->whereColumn('shipments.order_id', 'orders.id')
                        ->where('shipments.track_number', 'LIKE', $like);
                });

                // Tracking number — Sagawa side. Guarded because the table only
                // exists on tenants where the SagawaDelivery package is active.
                if ($hasSagawa) {
                    $q->orWhereExists(function ($sub) use ($like) {
                        $sub->select(DB::raw(1))
                            ->from('sagawa_shipments')
                            ->whereColumn('sagawa_shipments.order_id', 'orders.id')
                            ->where('sagawa_shipments.tracking_number', 'LIKE', $like);
                    });
                }

                // Payment method (cashondelivery / moneytransfer / paypay / ...)
                $q->orWhereExists(function ($sub) use ($like) {
                    $sub->select(DB::raw(1))
                        ->from('order_payment')
                        ->whereColumn('order_payment.order_id', 'orders.id')
                        ->where('order_payment.method', 'LIKE', $like);
                });
            });
        }

        // Date range filter — both bounds optional.
        if ($from = $request->input('created_from')) {
            $query->whereDate('orders.created_at', '>=', $from);
        }
        if ($to = $request->input('created_to')) {
            $query->whereDate('orders.created_at', '<=', $to);
        }

        // Amount range filter — matches grand_total in shop currency.
        if (($amountMin = $request->input('amount_min')) !== null && $amountMin !== '') {
            $query->where('orders.grand_total', '>=', (float) $amountMin);
        }
        if (($amountMax = $request->input('amount_max')) !== null && $amountMax !== '') {
            $query->where('orders.grand_total', '<=', (float) $amountMax);
        }

        $page = $query->paginate($perPage);

        // Avatars from Chatpion are loaded by a separate endpoint
        // (GET .../api/orders/avatars) so the table renders before the
        // external Chatpion call returns. transform() falls back to the
        // local customer.image (or null) on this synchronous response.
        $this->chatpionProfilePics = [];

        // Bulk-load cost (EAV attribute_id=12) for every product on this page
        // so transform() can compute the profit column without N+1.
        $this->productCosts = $this->loadProductCosts($page->getCollection());

        return response()->json([
            'data' => $page->getCollection()->map(fn (Order $order) => $this->transform($order))->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page'    => $page->lastPage(),
                'per_page'     => $page->perPage(),
                'total'        => $page->total(),
            ],
        ]);
    }

    /**
     * Per-channel pending+processing counts for the dashboard pill row above
     * the orders table. Returned shape:
     *   data: [
     *     { channel_id: 1, channel_code: 'default', name: 'Kimono Store',
     *       pending: 3, processing: 7, total: 10 },
     *     ...
     *   ]
     * Only channels that actually have open orders show up. The front-end
     * decides which channels to surface as pills.
     */
    public function summary(): JsonResponse
    {
        if (! bouncer()->hasPermission('sales.orders')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        // Exclude label-printed rows: in this admin a printed label means the
        // order is treated as "shipped" (virtual status) and is no longer an
        // open order. Without this guard a processing+label_printed order is
        // counted in the pill but unreachable by the pending/processing filter
        // (which excludes label-printed rows), so the count never matches what
        // the filter can surface. See index()'s status-filter logic.
        $rows = \Illuminate\Support\Facades\DB::table('orders')
            ->select('channel_id', 'status', \Illuminate\Support\Facades\DB::raw('COUNT(*) as cnt'))
            ->whereIn('status', ['pending', 'processing'])
            ->where(function ($q) {
                $q->whereNull('fujiba_label_printed')
                    ->orWhere('fujiba_label_printed', 0);
            })
            ->groupBy('channel_id', 'status')
            ->get();

        $byChannel = [];
        foreach ($rows as $r) {
            $cid = (int) $r->channel_id;
            if (! isset($byChannel[$cid])) {
                $byChannel[$cid] = ['channel_id' => $cid, 'pending' => 0, 'processing' => 0];
            }
            $byChannel[$cid][$r->status] = (int) $r->cnt;
        }

        // Attach channel code/name for the front-end. Falls back to default
        // english translation when the active locale row is missing.
        $channels = \Webkul\Core\Models\Channel::query()
            ->whereIn('id', array_keys($byChannel) ?: [0])
            ->get();

        $out = [];
        foreach ($channels as $ch) {
            $cid = $ch->id;
            $name = $ch->translate(app()->getLocale())->name ?? $ch->translate('en')->name ?? $ch->code;
            $row = $byChannel[$cid];
            $row['channel_code'] = $ch->code;
            $row['name']         = $name;
            $row['total']        = $row['pending'] + $row['processing'];
            $out[] = $row;
        }

        return response()->json(['data' => $out]);
    }

    /**
     * Return `{ <order_id>: <avatar_url> }` for the requested ids. Called
     * asynchronously by the front-end after the order table renders so the
     * Chatpion HTTP fetch never blocks the initial page load.
     */
    public function avatars(Request $request): JsonResponse
    {
        if (! bouncer()->hasPermission('sales.orders')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $ids = collect($request->input('ids', []))
            ->map(fn ($v) => (int) $v)
            ->filter()
            ->take(100) // upper bound — matches max per_page
            ->values();

        if ($ids->isEmpty()) {
            return response()->json(['data' => (object) []]);
        }

        $orders = Order::query()
            ->with(['customer'])
            ->whereIn('id', $ids)
            ->get();

        $this->chatpionProfilePics = $this->loadChatpionProfilePicsForPage($orders);

        $out = [];
        foreach ($orders as $order) {
            $customer = $order->customer;
            $url = $this->avatarUrl(
                $customer?->image
                    ?: ($this->chatpionProfilePics[$customer?->chatpion_subscribe_id] ?? null)
            );
            if ($url) {
                $out[$order->id] = $url;
            }
        }

        return response()->json(['data' => $out]);
    }

    /**
     * Build a [subscriber_id => profile_pic_url] map for every channel that
     * appears on this page. Cached per channel for 5 min so paging /
     * filter-tweaking doesn't repeatedly hit Chatpion.
     *
     * Walks Chatpion's livechat_conversations endpoint (paginated). Stops
     * after MAX_PAGES per channel — enough to cover any reasonably-recent
     * customer; older subscribers without local image still fall back to
     * the colored-initial avatar in the UI.
     */
    protected function loadChatpionProfilePicsForPage($orders): array
    {
        if ($orders->isEmpty()) return [];

        $channelIds = $orders->pluck('channel_id')->unique()->filter()->values();

        // Subscriber ids we actually need to resolve (limit lookups).
        $needed = $orders
            ->map(fn ($o) => $o->customer?->chatpion_subscribe_id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($needed)) return [];

        $needed = array_flip($needed);
        $map    = [];

        foreach ($channelIds as $channelId) {
            $channelMap = Cache::remember(
                "fujiba.orders_admin.profile_pics.channel.{$channelId}",
                300, // 5 min
                function () use ($channelId) {
                    $setting = LiveChatSetting::where('channel_id', $channelId)->first();
                    if (! $setting) return [];

                    $svc    = new ChatpionFailoverService($setting);
                    $byId   = [];
                    $offset = 0;
                    $limit  = 100;
                    $maxPages = 20; // up to 2,000 most-recent subscribers per channel

                    for ($i = 0; $i < $maxPages; $i++) {
                        $resp = $svc->apiPost('livechat_conversations', [
                            'whatsapp_bot_id' => $setting->fb_bot_id,
                            'limit'           => $limit,
                            'offset'          => $offset,
                        ]);
                        if (($resp['status'] ?? '') !== 'success') break;

                        $items = $resp['data'] ?? [];
                        if (empty($items)) break;

                        foreach ($items as $item) {
                            $sid = (string) ($item['from_user_id'] ?? '');
                            $pic = (string) ($item['profile_pic']  ?? '');
                            if ($sid !== '' && $pic !== '') {
                                $byId[$sid] = $pic;
                            }
                        }
                        $offset += $limit;
                    }

                    return $byId;
                }
            );

            foreach ($channelMap as $sid => $pic) {
                if (isset($needed[$sid])) {
                    $map[$sid] = $pic;
                }
            }
        }

        return $map;
    }

    /**
     * Read product cost (EAV attribute_id = 12, code 'cost') in one query for
     * every product appearing on this page of orders. Missing rows or NULL
     * float_value mean "cost not configured" — surfaced to the UI as null so
     * the front-end can flag those orders distinctly from a true 0 cost.
     */
    protected function loadProductCosts($orders): array
    {
        $productIds = $orders
            ->flatMap(fn ($o) => $o->items->pluck('product_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($productIds)) {
            return [];
        }

        $rows = \Illuminate\Support\Facades\DB::table('product_attribute_values')
            ->whereIn('product_id', $productIds)
            ->whereIn('attribute_id', function ($q) {
                $q->select('id')->from('attributes')->where('code', 'cost');
            })
            ->get(['product_id', 'float_value']);

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->product_id] = $r->float_value !== null ? (float) $r->float_value : null;
        }
        // Ensure products with no row at all show up as null (not absent).
        foreach ($productIds as $pid) {
            $map[(int) $pid] = $map[(int) $pid] ?? null;
        }
        return $map;
    }

    protected function transform(Order $order): array
    {
        $shipping = $order->addresses->first();
        $payment  = $order->payment;
        $customer = $order->customer;

        // Profit = sub_total - sum(item.qty * cost) - shipping - cod_fee
        //         - cool_surcharge.  Cost lookup uses the per-page bulk map
        // populated in index(); keys missing from the map mean cost was
        // not configured for that product and we tag the row so the UI
        // can warn the admin.
        $costTotal           = 0.0;
        $missingCostProducts = [];
        foreach ($order->items as $item) {
            $pid  = (int) $item->product_id;
            $cost = $this->productCosts[$pid] ?? null;
            if ($cost === null) {
                $missingCostProducts[] = [
                    'product_id' => $pid,
                    'sku'        => $item->sku,
                    'name'       => $item->name,
                ];
                continue;
            }
            $costTotal += $cost * (int) $item->qty_ordered;
        }

        $shippingAmt   = (float) $order->shipping_amount;
        $codFee        = (float) $order->cod_fee;
        $coolSurcharge = (float) ($order->cool_surcharge ?? 0);
        $subTotal      = (float) $order->sub_total;
        $discount      = (float) ($order->discount_amount ?? 0);

        $hasMissingCost = ! empty($missingCostProducts);
        // Shipping and COD fees are pass-through at Kimono: collected from
        // the customer at the same rate paid to the carrier, so they do not
        // affect margin. Discount reduces what the customer actually pays
        // and therefore must be subtracted.
        $profit = $hasMissingCost
            ? null
            : $subTotal - $discount - $costTotal - $coolSurcharge;

        $adminNote = $order->comments
            ->filter(fn ($c) => is_array($c->additional ?? null) && ($c->additional['is_admin_note'] ?? false))
            ->sortByDesc('created_at')
            ->first();

        $tracking = $order->shipments
            ->pluck('track_number')
            ->filter()
            ->first();

        return [
            'id'             => $order->id,
            'increment_id'   => $order->increment_id,
            'status'         => $order->status,
            'channel_id'     => $order->channel_id,
            'created_at'     => optional($order->created_at)->toIso8601String(),
            'grand_total'    => (float) $order->grand_total,
            'items_count'    => (int) ($order->items->sum('qty_ordered') ?: $order->total_qty_ordered),
            'payment_method' => $payment?->method,

            'customer' => [
                'id'                      => $customer?->id,
                'name'                    => trim(($order->customer_first_name ?? '') . ' ' . ($order->customer_last_name ?? '')) ?: '—',
                'email'                   => $order->customer_email,
                'phone'                   => $customer?->phone,
                // Tenant DBs store this as chatpion_subscribe_id; the older
                // central / dev migration named it messenger_subscriber_id.
                // Read both so the API works on either schema.
                'messenger_subscriber_id' => $customer?->chatpion_subscribe_id
                    ?? $customer?->messenger_subscriber_id,
                'avatar_url'              => $this->avatarUrl(
                    $customer?->image
                        ?: ($this->chatpionProfilePics[$customer?->chatpion_subscribe_id] ?? null)
                ),
            ],

            'shipping_address' => $shipping ? $this->splitAddress($shipping) : null,

            'admin_note'           => $adminNote?->comment,
            'tracking_number'      => $tracking,
            'label_printed'        => (bool) ($order->fujiba_label_printed ?? false),
            'fulfillment_type'     => $order->fulfillment_type ?? 'delivery',
            'shipping_amount'      => (float) $order->shipping_amount,
            'cod_fee'              => (float) $order->cod_fee,
            'cool_surcharge'       => (float) ($order->cool_surcharge ?? 0),
            'payment_confirmed_at' => $order->payment_confirmed_at
                ? \Illuminate\Support\Carbon::parse($order->payment_confirmed_at)->toIso8601String()
                : null,
            'payment_note'         => $order->payment_note,

            'sub_total'              => $subTotal,
            'discount_amount'        => $discount,
            'cost_total'             => $hasMissingCost ? null : $costTotal,
            'profit'                 => $profit,
            'missing_cost_products'  => $missingCostProducts,
        ];
    }

    /**
     * The addresses table stores a single `address` text column with newline
     * between lines. Split so the front end gets address1/address2.
     */
    protected function splitAddress($address): array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $address->address) ?: [];

        return [
            'name'     => trim(($address->first_name ?? '') . ' ' . ($address->last_name ?? '')),
            'address1' => $lines[0] ?? '',
            'address2' => $lines[1] ?? '',
            'city'     => $address->city ?? '',
            'state'    => $address->state ?? '',
            'postcode' => $address->postcode ?? '',
            'country'  => $address->country ?? '',
            'phone'    => $address->phone ?? '',
        ];
    }

    /**
     * Customer photos in this codebase come from two sources:
     *   - Messenger / Chatpion: full https URL pointing at Facebook's CDN
     *   - PWA upload:           a relative storage path (e.g. customer/1/p.jpg)
     * Storage::url() must NOT be called on the former — it'd turn the URL
     * into "/storage/.../https://..." which 404s.
     */
    protected function avatarUrl(?string $image): ?string
    {
        if (! $image) {
            return null;
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        return Storage::url($image);
    }
}

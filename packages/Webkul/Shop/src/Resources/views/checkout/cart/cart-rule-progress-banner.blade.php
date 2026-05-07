{{--
    Cart-rule progress banner. Inlined into the cart page between the item
    list and the controls/summary. Server-rendered; no Vue, no JS.

    Hidden when:
      - No cart / empty cart
      - Hostname is not in the allow-list (only kimono.* by default)
      - No active no-coupon cart_rule for this channel + customer group
      - Cart qty is past the last tier (nothing left to nudge toward)

    Visual matches Bagisto's confirm-modal card style (rounded-xl, gray
    border icon badge, title + message, two action buttons), but inline
    rather than fixed-position.
--}}
@php
    use Illuminate\Support\Facades\DB;
    use Webkul\Checkout\Facades\Cart;

    $crpAllowedHosts = array_filter(array_map('trim', explode(
        ',',
        (string) env('CRP_ALLOWED_HOSTS', 'kimono.fujiba.co.jp,kimono.snssale.com')
    )));

    $crpRequestHost = request()->getHost();
    $crpAllowed     = empty($crpAllowedHosts) || in_array($crpRequestHost, $crpAllowedHosts, true);

    $crpCart       = $crpAllowed ? Cart::getCart() : null;
    $crpItemsQty   = $crpCart ? (int) $crpCart->items_qty : 0;

    $crpProgress = null;

    if ($crpAllowed && $crpCart && $crpItemsQty > 0) {
        $crpChannel       = core()->getCurrentChannel();
        $crpChannelId     = $crpChannel?->id;
        $crpCustomerGroup = auth()->guard('customer')->check()
            ? (int) (auth()->guard('customer')->user()->customer_group_id
                ?? DB::table('customer_groups')->where('code', 'guest')->value('id') ?? 1)
            : (int) (DB::table('customer_groups')->where('code', 'guest')->value('id') ?? 1);
        $crpNow = now();

        $crpRule = DB::table('cart_rules')
            ->where('status', 1)
            ->where(fn ($q) => $q->whereNull('starts_from')->orWhere('starts_from', '<=', $crpNow))
            ->where(fn ($q) => $q->whereNull('ends_till')->orWhere('ends_till', '>=', $crpNow))
            ->where('coupon_type', 1)
            ->whereExists(function ($q) use ($crpChannelId) {
                $q->select(DB::raw(1))
                    ->from('cart_rule_channels')
                    ->whereColumn('cart_rule_channels.cart_rule_id', 'cart_rules.id')
                    ->where('cart_rule_channels.channel_id', $crpChannelId);
            })
            ->whereExists(function ($q) use ($crpCustomerGroup) {
                $q->select(DB::raw(1))
                    ->from('cart_rule_customer_groups')
                    ->whereColumn('cart_rule_customer_groups.cart_rule_id', 'cart_rules.id')
                    ->where('cart_rule_customer_groups.customer_group_id', $crpCustomerGroup);
            })
            ->orderBy('sort_order')
            ->first(['id', 'name', 'discount_amount', 'discount_step', 'conditions']);

        if ($crpRule) {
            $crpConditions = json_decode($crpRule->conditions ?: '[]', true) ?: [];
            $crpFlat = [];
            foreach ($crpConditions as $c) {
                if (isset($c['conditions']) && is_array($c['conditions'])) {
                    foreach ($c['conditions'] as $inner) { $crpFlat[] = $inner; }
                } else { $crpFlat[] = $c; }
            }

            $crpBase = null;
            foreach ($crpFlat as $c) {
                $attr = $c['attribute'] ?? null;
                if (($c['attribute_type'] ?? '') !== 'integer') continue;
                if (! in_array($attr, ['cart|items_qty', 'cart_item|quantity'], true)) continue;

                $val = (int) ($c['value'] ?? 0);
                $op  = $c['operator'] ?? '==';
                if ($val <= 0) continue;

                $minSat = match ($op) {
                    '>=', '==' => $val,
                    '>'        => $val + 1,
                    default    => null,
                };
                if ($minSat === null) continue;
                if ($crpBase === null || $minSat < $crpBase) {
                    $crpBase = $minSat;
                }
            }

            if ($crpBase !== null) {
                $crpStep = max(1, (int) ($crpRule->discount_step ?? 0));
                if ($crpItemsQty < $crpBase) {
                    $crpTier   = 0;
                    $crpTarget = $crpBase;
                } else {
                    $crpTier   = (int) floor(($crpItemsQty - $crpBase) / $crpStep) + 1;
                    $crpTarget = $crpBase + $crpTier * $crpStep;
                }
                $crpProgress = [
                    'name'      => $crpRule->name,
                    'amount'    => (float) $crpRule->discount_amount,
                    'current'   => $crpItemsQty,
                    'target'    => $crpTarget,
                    'remaining' => $crpTarget - $crpItemsQty,
                    'tier'      => $crpTier,
                    'pct'       => max(2, min(100, (int) round(($crpItemsQty / $crpTarget) * 100))),
                ];
            }
        }
    }

    $crpCurrency = core()->getCurrentCurrency();
    $crpSymbol   = $crpCurrency?->symbol ?? '¥';
    $crpAmount   = $crpProgress
        ? $crpSymbol . number_format((int) round($crpProgress['amount']))
        : null;
@endphp

{{-- The banner always renders (display:none when no progress), so JS can
     toggle/update it without needing to inject elements after the fact. --}}
<div
    id="crp-banner"
    class="my-5 overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm max-sm:p-4"
    style="{{ $crpProgress ? '' : 'display:none;' }}"
    data-crp-allowed="{{ $crpAllowed ? '1' : '0' }}"
>
    <div class="flex gap-3 max-sm:gap-2.5">
        <div class="flex-shrink-0">
            <span
                id="crp-banner-icon"
                class="flex items-center justify-center rounded-full border border-gray-300 p-2.5 text-3xl leading-none max-sm:text-xl"
            >🎁</span>
        </div>

        <div class="flex-1 min-w-0">
            <div
                id="crp-banner-headline"
                class="flex items-center justify-between gap-5 text-xl max-sm:text-lg"
            >@lang('shop::cart-rule-progress.progress.almost_there')</div>

            <div
                id="crp-banner-message"
                class="pb-3 pt-1.5 text-left text-sm text-gray-500"
            >@if ($crpProgress){!!
                str_replace(
                    [':n', ':amount'],
                    [
                        '<strong class="text-gray-900">' . $crpProgress['remaining'] . '</strong>',
                        '<span class="crp-amount" style="color: var(--brand-color, #B8860B); font-weight: 700;">' . e($crpAmount) . '</span>',
                    ],
                    e(trans('shop::cart-rule-progress.progress.add_to_unlock'))
                )
            !!}@endif</div>

            <div class="mb-2 h-2 overflow-hidden rounded-full bg-gray-100">
                <div
                    id="crp-banner-fill"
                    class="h-full rounded-full transition-all"
                    style="width: {{ $crpProgress ? $crpProgress['pct'] : 0 }}%; background: var(--brand-color, #B8860B);"
                ></div>
            </div>

            <div class="flex items-center justify-between text-xs font-medium text-gray-500">
                <span id="crp-banner-counts">{{ $crpProgress ? $crpProgress['current'].' / '.$crpProgress['target'] : '' }}</span>
                <span id="crp-banner-pct">{{ $crpProgress ? $crpProgress['pct'].'%' : '' }}</span>
            </div>
        </div>
    </div>
</div>

@pushOnce('scripts')
<script>
(function () {
    var banner = document.getElementById('crp-banner');
    if (! banner || banner.dataset.crpAllowed !== '1') return;

    var endpoint     = @json(route('shop.api.checkout.cart.rule_progress'));
    var iconEl       = document.getElementById('crp-banner-icon');
    var headlineEl   = document.getElementById('crp-banner-headline');
    var messageEl    = document.getElementById('crp-banner-message');
    var fillEl       = document.getElementById('crp-banner-fill');
    var countsEl     = document.getElementById('crp-banner-counts');
    var pctEl        = document.getElementById('crp-banner-pct');

    var strings = {
        almostThere: @json(trans('shop::cart-rule-progress.progress.almost_there')),
        addToUnlock: @json(trans('shop::cart-rule-progress.progress.add_to_unlock')),
        unlocked:    @json(trans('shop::cart-rule-progress.progress.unlocked')),
    };
    var symbol = @json(core()->getCurrentCurrency()?->symbol ?? '¥');

    function fmtAmount(n) { return symbol + Math.round(Number(n) || 0).toLocaleString(); }

    function escape(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function renderUnlocked(amount) {
        iconEl.textContent = '🎉';
        headlineEl.textContent = strings.unlocked.replace(':amount', '');
        messageEl.innerHTML = '<span class="crp-amount" style="color: var(--brand-color, #B8860B); font-weight: 700;">' + escape(fmtAmount(amount)) + '</span>';
        fillEl.style.width = '100%';
        countsEl.textContent = '';
        pctEl.textContent = '100%';
        banner.style.display = '';
    }

    function renderProgress(p) {
        if (! p) { banner.style.display = 'none'; return; }
        var pct = Math.max(2, Math.min(100, Math.round((p.current / p.target) * 100)));
        iconEl.textContent = '🎁';
        headlineEl.textContent = strings.almostThere;
        messageEl.innerHTML = strings.addToUnlock
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(':n', '<strong class="text-gray-900">' + escape(p.remaining) + '</strong>')
            .replace(':amount', '<span class="crp-amount" style="color: var(--brand-color, #B8860B); font-weight: 700;">' + escape(fmtAmount(p.discount_amount)) + '</span>');
        fillEl.style.width = pct + '%';
        countsEl.textContent = p.current + ' / ' + p.target;
        pctEl.textContent = pct + '%';
        banner.style.display = '';
    }

    var inFlight = false;
    function refresh() {
        if (inFlight) return;
        inFlight = true;
        fetch(endpoint, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (j) {
                if (! j) return;
                var rule = (j.rules || [])[0] || null;
                if (! rule) {
                    // No active rule — hide banner.
                    banner.style.display = 'none';
                    return;
                }
                if (rule.remaining === 0) {
                    renderUnlocked(rule.discount_amount);
                } else {
                    renderProgress(rule);
                }
            })
            .catch(function () { /* best-effort */ })
            .finally(function () { inFlight = false; });
    }

    // Re-fetch whenever the cart changes:
    //   1. Bagisto's emitter — Vue components emit `update-mini-cart` after qty
    //      change, remove, move-to-wishlist.
    //   2. Axios interceptor — catches every /api/checkout/cart hit even from
    //      pages whose Vue components don't emit anything.
    function attachEmitter() {
        var app = window.app;
        var cfg = app && (app.config || (app._context && app._context.config));
        var emitter = cfg && cfg.globalProperties && cfg.globalProperties.$emitter;
        if (emitter) {
            emitter.on('update-mini-cart', refresh);
            emitter.on('add-flash', refresh);
            return true;
        }
        return false;
    }
    if (! attachEmitter()) {
        var tries = 0;
        var t = setInterval(function () {
            if (attachEmitter() || ++tries > 60) clearInterval(t);
        }, 50);
    }

    if (window.axios && ! window.__crpBannerAxiosHooked) {
        window.__crpBannerAxiosHooked = true;
        window.axios.interceptors.response.use(function (resp) {
            try {
                var url = resp && resp.config && resp.config.url || '';
                if (/\/api\/checkout\/cart(\b|\?|\/)/.test(url)) {
                    setTimeout(refresh, 50);
                }
            } catch (e) {}
            return resp;
        });
    }
})();
</script>
@endPushOnce

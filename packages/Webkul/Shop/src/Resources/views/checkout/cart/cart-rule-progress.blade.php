{{-- Cart-rule progress one-shot popup.
     Fires a bloom-in toast only when the customer crosses a near-threshold
     boundary (remaining drops to 1 or to 0). Auto-dismisses after 5s.
     Reads thresholds from active cart_rules (no hardcoded numbers).
     Multi-locale via shop::cart-rule-progress. --}}
<v-cart-rule-progress
    :endpoint="'{{ route('shop.api.checkout.cart.rule_progress') }}'"
></v-cart-rule-progress>

{{-- Styles inlined (NOT @pushOnce) because this component is injected via
     `bagisto.shop.layout.content.before` AFTER @stack('styles') has already
     been flushed to <head>. Pushing would silently drop the CSS.

     Layout uses Bagisto's existing Tailwind classes (matches v-modal-confirm
     1:1). Only minimal overrides are needed below. --}}
<style>
    /* Backdrop — opacity-50 gray dim */
    .crp-modal-overlay {
        position: fixed; inset: 0; z-index: 20;
        background: rgb(107 114 128 / .5);
    }
    /* Default-hidden so a Vue mount failure can't leak the static fragment */
    .crp-modal-root { display: none; }
    .crp-modal-root[data-crp-shown] { display: block; }

    /* The amount placeholder inside the message uses brand color */
    .crp-amount {
        font-weight: 700;
        color: var(--brand-color, #B8860B);
    }
    .crp-toast-met .crp-amount { color: #047857; }

    /* Animations */
    @keyframes crp-fade-in  { from { opacity: 0; } to { opacity: 1; } }
    @keyframes crp-fade-out { from { opacity: 1; } to { opacity: 0; } }
    @keyframes crp-pop-in {
        from { opacity: 0; transform: translate(-50%, calc(-50% + 16px)) scale(.95); }
        to   { opacity: 1; transform: translate(-50%, -50%) scale(1); }
    }
    @keyframes crp-pop-out {
        from { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        to   { opacity: 0; transform: translate(-50%, calc(-50% + 16px)) scale(.95); }
    }
    .crp-modal-card.crp-enter-active    { animation: crp-pop-in  .30s ease-out; }
    .crp-modal-card.crp-leave-active    { animation: crp-pop-out .20s ease-in forwards; }
    .crp-modal-overlay.crp-enter-active { animation: crp-fade-in  .30s ease-out; }
    .crp-modal-overlay.crp-leave-active { animation: crp-fade-out .20s ease-in forwards; }

    @media (prefers-reduced-motion: reduce) {
        .crp-modal-card.crp-enter-active, .crp-modal-card.crp-leave-active,
        .crp-modal-overlay.crp-enter-active, .crp-modal-overlay.crp-leave-active {
            animation: none !important;
        }
    }
</style>

{{-- Scripts also inlined for the same reason as styles above. --}}
<script type="text/x-template" id="v-cart-rule-progress-template">
    <div class="crp-modal-root" :class="{ 'crp-toast-met': active && active.met }" data-crp-shown>
        <transition name="crp">
            <div
                v-if="visible"
                class="crp-modal-overlay transition-opacity"
                @click="dismiss"
            ></div>
        </transition>

        <transition name="crp">
            <div
                v-if="visible && active"
                class="fixed inset-0 z-20 transform overflow-y-auto transition"
            >
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div
                        class="crp-modal-card absolute left-1/2 top-1/2 z-[999] w-full max-w-[475px] -translate-x-1/2 -translate-y-1/2 overflow-hidden rounded-xl bg-white p-5 max-md:w-[90%] max-sm:p-4"
                        role="alertdialog"
                        aria-live="assertive"
                        aria-modal="true"
                    >
                        <div class="flex gap-2.5">
                            <div>
                                <span class="flex rounded-full border border-gray-300 p-2.5 text-3xl max-sm:text-xl">
                                    @{{ active.met ? '🎉' : '🎁' }}
                                </span>
                            </div>

                            <div>
                                <div class="flex items-center justify-between gap-5 text-xl max-sm:text-lg">
                                    @{{ headlineText }}
                                </div>

                                <div class="pb-5 pt-1.5 text-left text-sm text-gray-500" v-html="messageHtml"></div>

                                <div class="flex justify-end gap-2.5">
                                    <button
                                        type="button"
                                        class="secondary-button max-md:py-3 max-sm:px-6 max-sm:py-2.5"
                                        @click="dismiss"
                                    >
                                        @{{ dismissLabel }}
                                    </button>
                                    <button
                                        type="button"
                                        class="primary-button max-md:py-3 max-sm:px-6 max-sm:py-2.5"
                                        @click="ctaClick"
                                    >
                                        @{{ ctaLabel }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</script>

    <script type="module">
        app.component('v-cart-rule-progress', {
            template: '#v-cart-rule-progress-template',

            props: {
                endpoint: { type: String, required: true },
            },

            data() {
                // Restore tier-bump memory across navigations so we don't
                // double-celebrate the same crossing on the next page.
                // `lastFired` is intentionally NOT restored — each page-load
                // gets one shot at the nudge popup so the customer sees it
                // on whichever page they're on (cart, home, category…) when
                // the cart is in the nudge zone.
                let savedSeen = {};
                try {
                    const raw = sessionStorage.getItem('crp:state');
                    if (raw) {
                        const s = JSON.parse(raw);
                        savedSeen = s.seenTierByRule || {};
                    }
                } catch (e) {}

                return {
                    active: null,
                    visible: false,
                    loading: false,
                    dismissTimer: null,
                    /** Last popup key we fired for in this page-lifetime. */
                    lastFired: null,
                    /** Wall-clock ms when lastFired was set — debounces fast re-triggers. */
                    lastFiredAt: 0,
                    /** Min ms between two pops for ANY key on this page. */
                    refireGuardMs: 4000,
                    /** Per-rule tier_index seen on the previous refresh. */
                    seenTierByRule: savedSeen,
                    /** Remaining-threshold to start nudging at (1 = "add 1 more"). */
                    nudgeAt: 1,
                    autoDismissMs: 5500,
                    confetti: [],
                    strings: {
                        almost_there:        @json(trans('shop::cart-rule-progress.progress.almost_there')),
                        add_to_unlock:       @json(trans('shop::cart-rule-progress.progress.add_to_unlock')),
                        add_to_unlock_line:  @json(trans('shop::cart-rule-progress.progress.add_to_unlock_line')),
                        unlocked:            @json(trans('shop::cart-rule-progress.progress.unlocked')),
                    },
                    currency: {!! json_encode([
                        'symbol' => core()->getCurrentCurrency()->symbol ?? '¥',
                        'code'   => core()->getCurrentCurrencyCode(),
                    ]) !!},
                    routes: {!! json_encode([
                        'cart' => route('shop.checkout.cart.index'),
                        'home' => url('/'),
                    ]) !!},
                    ctaLabels: {!! json_encode([
                        'continue' => trans('shop::cart-rule-progress.cta.continue_shopping'),
                        'view'     => trans('shop::cart-rule-progress.cta.view_cart'),
                        'close'    => trans('shop::cart-rule-progress.cta.close'),
                    ]) !!},
                };
            },

            computed: {
                headlineText() {
                    if (! this.active) return '';
                    return this.active.met
                        ? this.unlockedHeadline()
                        : this.strings.almost_there;
                },

                messageHtml() {
                    if (! this.active) return '';
                    if (this.active.met) {
                        return '<span class="crp-amount">' + this.formatAmount(this.active.discount_amount) + '</span>';
                    }
                    const tpl = this.active.attribute === 'cart_item|quantity'
                        ? this.strings.add_to_unlock_line
                        : this.strings.add_to_unlock;
                    return tpl
                        .replace(':n', '<strong>' + this.active.remaining + '</strong>')
                        .replace(':amount', '<span class="crp-amount">' + this.formatAmount(this.active.discount_amount) + '</span>');
                },

                progressPct() {
                    if (! this.active || ! this.active.target) return 0;
                    const pct = Math.round((this.active.current / this.active.target) * 100);
                    return Math.max(2, Math.min(100, pct));
                },

                ctaHref() {
                    if (! this.active) return this.routes.home;
                    // On cart page: send them shopping. Anywhere else: send them to cart.
                    const onCart = window.location.pathname.indexOf('/checkout/cart') !== -1;
                    if (this.active.met) return this.routes.cart;
                    return onCart ? this.routes.home : this.routes.cart;
                },

                ctaLabel() {
                    if (! this.active) return this.ctaLabels.continue;
                    const onCart = window.location.pathname.indexOf('/checkout/cart') !== -1;
                    if (this.active.met) return this.ctaLabels.view;
                    return onCart ? this.ctaLabels.continue : this.ctaLabels.view;
                },

                dismissLabel() {
                    return this.ctaLabels.close;
                },
            },

            mounted() {
                // Initial probe: snapshot current cart state silently so we
                // don't fire the modal just because the page loaded with the
                // cart already in the nudge zone. The modal only pops on a
                // subsequent cart change (add/remove/qty update).
                this.refresh({ silent: true });

                // Bagisto's cart page only emits `update-mini-cart` on remove
                // and move-to-wishlist — NOT on qty change. We listen on the
                // events it DOES emit broadly (add-flash fires on every cart
                // success) and tap axios responses to any /api/checkout/cart
                // hit so we catch all qty changes without polling.
                this.$emitter.on('update-mini-cart', this.refresh);
                this.$emitter.on('add-flash', this.onFlash);

                if (window.axios && ! window.__crpAxiosHooked) {
                    window.__crpAxiosHooked = true;
                    window.axios.interceptors.response.use(resp => {
                        try {
                            const url = resp?.config?.url || '';
                            if (/\/api\/checkout\/cart(\b|\?|\/)/.test(url)) {
                                window.dispatchEvent(new CustomEvent('crp:cart-touched'));
                            }
                        } catch (e) {}
                        return resp;
                    });
                }
                window.addEventListener('crp:cart-touched', this.refresh);

                // ESC dismisses while the modal is open.
                this._onKey = (e) => {
                    if (e.key === 'Escape' && this.visible) this.dismiss();
                };
                window.addEventListener('keydown', this._onKey);
            },

            beforeUnmount() {
                this.$emitter.off('update-mini-cart', this.refresh);
                this.$emitter.off('add-flash', this.onFlash);
                window.removeEventListener('crp:cart-touched', this.refresh);
                if (this._onKey) window.removeEventListener('keydown', this._onKey);
                if (this.dismissTimer) clearTimeout(this.dismissTimer);
            },

            methods: {
                onFlash() {
                    // Any flash message implies a cart action just succeeded.
                    this.refresh();
                },

                async refresh(opts) {
                    const silent = !!(opts && opts.silent === true);
                    if (this.loading) return;
                    this.loading = true;
                    try {
                        const r = await this.$axios.get(this.endpoint);
                        const closest = (r.data.rules || [])[0] || null;
                        console.log('[CRP]', {
                            silent,
                            cart_qty: r.data.cart_items_qty,
                            target: closest?.target,
                            remaining: closest?.remaining,
                            tier: closest?.tier_index,
                            lastFired: this.lastFired,
                            seen: JSON.stringify(this.seenTierByRule),
                            visible: this.visible,
                        });
                        this.maybeFire(closest, silent);
                    } catch (e) {
                        console.warn('[CRP] err', e);
                    } finally {
                        this.loading = false;
                    }
                },

                /**
                 * Fire on EVERY tier approach AND on every tier crossing.
                 *
                 * Controller returns the *next unmet* tier per rule. So:
                 *   qty 10 → target 11, remaining 1   (nudge)
                 *   qty 11 → target 22, remaining 11, tier_index 1 (just crossed!)
                 *   qty 21 → target 22, remaining 1   (nudge)
                 *   qty 22 → target 33, remaining 11, tier_index 2 (just crossed!)
                 *
                 * We detect a tier crossing client-side by comparing tier_index
                 * with what we saw last refresh — if it bumped up, fire 🎉.
                 */
                maybeFire(rule, silent) {
                    if (! rule) {
                        if (this.lastFired !== null) {
                            this.lastFired = null;
                        }
                        return;
                    }

                    const seen   = this.seenTierByRule[rule.rule_id];
                    const bumped = seen !== undefined && rule.tier_index > seen;
                    this.seenTierByRule[rule.rule_id] = rule.tier_index;
                    this.persistState();

                    const inNudgeZone = rule.remaining > 0 && rule.remaining <= this.nudgeAt;

                    // Silent mode: snapshot tier_index/lastFired so the next
                    // refresh has a baseline, but never show the modal. Used on
                    // page-mount so reloading with a 10-item cart doesn't pop
                    // a stale nudge — the customer only sees it when their
                    // cart actually changes.
                    //
                    // Important: only set `lastFired` (the dedup key), NOT
                    // `lastFiredAt` — otherwise the 4-second refire guard
                    // would block the very next user-triggered fire.
                    if (silent) {
                        if (bumped) {
                            this.lastFired = rule.rule_id + ':crossed:' + rule.tier_index;
                        } else if (inNudgeZone) {
                            this.lastFired = rule.rule_id + ':nudge:' + rule.target;
                        }
                        return;
                    }

                    if (bumped) {
                        const key = rule.rule_id + ':crossed:' + rule.tier_index;
                        if (this.lastFired === key) return;
                        if (this.withinRefireGuard()) return;
                        this.markFired(key);
                        this.show({ ...rule, met: true });
                        return;
                    }

                    if (! inNudgeZone) {
                        console.log('[CRP] BAIL: not nudge zone (remaining=' + rule.remaining + ')');
                        if (this.lastFired !== null) {
                            this.lastFired = null;
                        }
                        return;
                    }

                    const key = rule.rule_id + ':nudge:' + rule.target;
                    if (this.lastFired === key) {
                        console.log('[CRP] BAIL: dup nudge key', key);
                        return;
                    }
                    if (this.withinRefireGuard()) {
                        console.log('[CRP] BAIL: refire guard');
                        return;
                    }
                    console.log('[CRP] FIRING NUDGE', key);
                    this.markFired(key);
                    this.show({ ...rule, met: false });
                },

                withinRefireGuard() {
                    return this.lastFiredAt > 0
                        && (Date.now() - this.lastFiredAt) < this.refireGuardMs;
                },

                markFired(key) {
                    this.lastFired   = key;
                    this.lastFiredAt = Date.now();
                },

                persistState() {
                    try {
                        sessionStorage.setItem('crp:state', JSON.stringify({
                            seenTierByRule: this.seenTierByRule,
                        }));
                    } catch (e) {}
                },

                show(rule) {
                    console.log('[CRP] show() called — visible was', this.visible);
                    this.active = rule;
                    this.confetti = this.makeConfetti();
                    this.visible = true;
                    this.$nextTick(() => {
                        const el = document.querySelector('.crp-modal-card');
                        console.log('[CRP] DOM check — modal el:', el,
                            'computed display:', el ? getComputedStyle(el).display : 'NO ELEMENT');
                    });

                    if (this.dismissTimer) clearTimeout(this.dismissTimer);
                    this.dismissTimer = setTimeout(() => this.dismiss(), this.autoDismissMs);
                },

                dismiss() {
                    this.visible = false;
                    if (this.dismissTimer) {
                        clearTimeout(this.dismissTimer);
                        this.dismissTimer = null;
                    }
                },

                ctaClick() {
                    const href = this.ctaHref;
                    this.dismiss();
                    if (href) window.location.href = href;
                },

                formatAmount(amount) {
                    const n = Math.round(Number(amount) || 0);
                    return this.currency.symbol + n.toLocaleString();
                },

                unlockedHeadline() {
                    return this.strings.unlocked.replace(':amount', '');
                },

                makeConfetti() {
                    const colors = ['#fbbf24','#10b981','#3b82f6','#ef4444','#a78bfa'];
                    const out = [];
                    for (let i = 0; i < 18; i++) {
                        out.push({
                            left:    Math.random() * 100 + '%',
                            top:     '60%',
                            background: colors[i % colors.length],
                            'animation-delay': (Math.random() * 0.6) + 's',
                        });
                    }
                    return out;
                },
            },
        });
    </script>

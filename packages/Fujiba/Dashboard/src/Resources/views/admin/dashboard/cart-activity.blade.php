@php
    $initialJson = json_encode($cartActivityInitial, JSON_UNESCAPED_UNICODE);
    $endpointUrl = route('admin.fujiba.dashboard.cart_activity');
    $detailUrl   = route('admin.fujiba.dashboard.cart_activity_detail', ['cart' => '__ID__']);
    $customerUrlPattern = route('admin.customers.customers.view', ['id' => '__ID__']);
@endphp

<div class="flex flex-col gap-2" id="fujiba-cart-activity-card">
    <v-fujiba-cart-activity
        endpoint="{{ $endpointUrl }}"
        detail-url="{{ $detailUrl }}"
        customer-url-pattern="{{ $customerUrlPattern }}"
        :initial-data="{{ $initialJson }}"
    ></v-fujiba-cart-activity>
</div>

<script type="text/x-template" id="v-fujiba-cart-activity-template">
    <div class="fujiba-ca-shell">
        <!-- Title row with cart icon -->
        <div class="fujiba-ca-titlebar">
            <span class="fujiba-ca-title-icon">🛒</span>
            <span class="fujiba-ca-title-text">@lang('fujiba::app.dashboard.cart_activity.title')</span>
        </div>

        <!-- Loading state -->
        <template v-if="isLoading">
            <div class="fujiba-ca-list">
                <div class="fujiba-ca-row-shimmer" v-for="n in 6" :key="'sh'+n">
                    <div class="shimmer fujiba-ca-shimmer-avatar"></div>
                    <div class="fujiba-ca-shimmer-body">
                        <div class="shimmer fujiba-ca-shimmer-line"></div>
                        <div class="shimmer fujiba-ca-shimmer-line short"></div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Empty state -->
        <template v-else-if="filtered.length === 0">
            <div class="fujiba-ca-empty">
                <p>@lang('fujiba::app.dashboard.cart_activity.empty_state')</p>
            </div>
        </template>

        <!-- Cart rows -->
        <template v-else>
            <div class="fujiba-ca-list">
                <div
                    v-for="(c, idx) in filtered"
                    :key="c.cart_id"
                    class="fujiba-ca-row"
                    :style="{ animationDelay: (Math.min(idx, 12) * 25) + 'ms' }"
                    @click="openDetail(c)"
                >
                    <!-- Avatar -->
                    <a class="fujiba-ca-avatar-link"
                       :href="customerHref(c)"
                       :title="c.display_name"
                       @click.stop>
                        <img v-if="c.avatar"
                             :src="c.avatar"
                             :alt="c.display_name"
                             class="fujiba-ca-avatar"
                             v-on:error="onAvatarError($event)">
                        <span v-else class="fujiba-ca-avatar fujiba-ca-avatar-fallback"
                              :style="{ background: avatarGradient(c) }">
                            @{{ c.avatar_initial }}
                        </span>
                    </a>

                    <!-- Body -->
                    <div class="fujiba-ca-body">
                        <div class="fujiba-ca-line1">
                            <span class="fujiba-ca-cart-icon" :class="'is-' + c.state" :title="stateLabel(c.state)">
                                <span v-if="c.state === 'converted'">✓</span>
                                <span v-else-if="c.state === 'abandoned'">✕</span>
                                <span v-else>+</span>
                            </span>
                            <span class="fujiba-ca-name">@{{ c.display_name }}</span>
                            <span
                                class="fujiba-ca-purchase-badge"
                                :class="purchaseTier(c.purchase_count)"
                                :title="`Purchased ${c.purchase_count || 0} time(s)`"
                            >
                                <i class="icon-cart"></i>
                                @{{ c.purchase_count || 0 }}x
                            </span>
                        </div>
                        <div class="fujiba-ca-line2" :class="'is-' + c.state">@{{ actionLabel(c) }}</div>
                    </div>

                    <!-- Time ago -->
                    <span class="fujiba-ca-time">@{{ c.updated_human }}</span>
                </div>
            </div>
        </template>

        <!-- Modal -->
        <transition name="fujiba-ca-modal">
            <div v-if="modal.open" class="fujiba-ca-modal-backdrop" @click.self="closeDetail">
                <div class="fujiba-ca-modal">
                    <button class="fujiba-ca-modal-close" @click="closeDetail" aria-label="Close">×</button>

                    <div class="fujiba-ca-modal-header" :class="'is-' + (modal.cart?.state || 'active')">
                        <a class="fujiba-ca-modal-avatar-link"
                           :href="modal.cart ? customerHref(modal.cart) : '#'"
                           v-if="modal.cart">
                            <img v-if="modal.cart.avatar"
                                 :src="modal.cart.avatar"
                                 class="fujiba-ca-modal-avatar"
                                 v-on:error="onAvatarError($event)">
                            <span v-else class="fujiba-ca-modal-avatar fujiba-ca-avatar-fallback"
                                  :style="{ background: avatarGradient(modal.cart) }">
                                @{{ modal.cart.avatar_initial }}
                            </span>
                        </a>
                        <div class="fujiba-ca-modal-id">
                            <p class="fujiba-ca-modal-name">@{{ modal.cart?.display_name }}</p>
                            <p class="fujiba-ca-modal-meta" v-if="modal.cart">
                                <span v-if="modal.cart.email">@{{ modal.cart.email }}</span>
                                <span v-if="modal.cart.phone"> · @{{ modal.cart.phone }}</span>
                            </p>
                            <p class="fujiba-ca-modal-meta" v-if="modal.detail">
                                @lang('fujiba::app.dashboard.cart_activity.cart_id'): #@{{ modal.detail.cart_id }}
                                · @{{ modal.detail.channel_code || '—' }}
                                <span v-if="modal.detail.payment_method"> · @{{ modal.detail.payment_method }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="fujiba-ca-modal-body">
                        <template v-if="modal.loading">
                            <div class="shimmer h-3 w-32 mb-2"></div>
                            <div class="shimmer h-12 w-full mb-2" v-for="n in 3" :key="'mli'+n"></div>
                        </template>

                        <template v-else-if="modal.detail">
                            <table class="fujiba-ca-items-table">
                                <thead>
                                    <tr>
                                        <th>@lang('fujiba::app.dashboard.cart_activity.col_item')</th>
                                        <th class="text-right">@lang('fujiba::app.dashboard.cart_activity.col_qty')</th>
                                        <th class="text-right">@lang('fujiba::app.dashboard.cart_activity.col_total')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="it in modal.detail.items" :key="it.sku">
                                        <td>
                                            <div class="fujiba-ca-item-name">@{{ it.name }}</div>
                                            <div class="fujiba-ca-item-sku">@{{ it.sku }}</div>
                                        </td>
                                        <td class="text-right tabular-nums">@{{ it.quantity }}</td>
                                        <td class="text-right tabular-nums">@{{ formatPrice(it.total) }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="fujiba-ca-modal-totals">
                                <div><span>@lang('fujiba::app.dashboard.cart_activity.sub_total')</span><b>@{{ formatPrice(modal.detail.sub_total) }}</b></div>
                                <div v-if="modal.detail.discount_amount > 0" class="text-emerald-600">
                                    <span>@lang('fujiba::app.dashboard.cart_activity.discount')</span>
                                    <b>− @{{ formatPrice(modal.detail.discount_amount) }}</b>
                                </div>
                                <div v-if="modal.detail.tax_total > 0">
                                    <span>@lang('fujiba::app.dashboard.cart_activity.tax')</span>
                                    <b>@{{ formatPrice(modal.detail.tax_total) }}</b>
                                </div>
                                <div v-if="modal.detail.shipping_amount > 0">
                                    <span>@lang('fujiba::app.dashboard.cart_activity.shipping')</span>
                                    <b>@{{ formatPrice(modal.detail.shipping_amount) }}</b>
                                </div>
                                <div class="fujiba-ca-grand">
                                    <span>@lang('fujiba::app.dashboard.cart_activity.grand_total')</span>
                                    <b>@{{ formatPrice(modal.detail.grand_total) }}</b>
                                </div>

                                <!-- Profit summary -->
                                <div v-if="modal.detail.total_cost > 0" class="fujiba-ca-cost">
                                    <span>@lang('fujiba::app.dashboard.cart_activity.cost')</span>
                                    <b>@{{ formatPrice(modal.detail.total_cost) }}</b>
                                </div>
                                <div v-if="modal.detail.total_cost > 0"
                                     class="fujiba-ca-profit"
                                     :class="modal.detail.estimated_profit >= 0 ? 'is-gain' : 'is-loss'">
                                    <span>@lang('fujiba::app.dashboard.cart_activity.estimated_profit')
                                        <small>(@{{ modal.detail.profit_margin }}%)</small>
                                    </span>
                                    <b>@{{ formatPrice(modal.detail.estimated_profit) }}</b>
                                </div>
                                <div v-else class="fujiba-ca-cost-missing">
                                    @lang('fujiba::app.dashboard.cart_activity.cost_missing')
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <p class="text-sm text-gray-500">@lang('fujiba::app.dashboard.cart_activity.detail_failed')</p>
                        </template>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</script>

<style>
    .fujiba-ca-shell {
        background: #fff;
        border-radius: 12px;
        padding: 0;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04), 0 4px 12px -8px rgba(15,23,42,0.06);
        border: 1px solid rgba(15,23,42,0.06);
        overflow: hidden;
    }
    .dark .fujiba-ca-shell { background: rgb(17,24,39); border-color: rgba(255,255,255,0.06); }

    .fujiba-ca-titlebar {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 14px 18px;
        border-bottom: 1px solid rgba(15,23,42,0.06);
    }
    .dark .fujiba-ca-titlebar { border-bottom-color: rgba(255,255,255,0.06); }
    .fujiba-ca-title-icon { font-size: 16px; color: #3b82f6; }
    .fujiba-ca-title-text {
        font-size: 14px;
        font-weight: 600;
        color: #1e3a8a;
    }
    .dark .fujiba-ca-title-text { color: #93c5fd; }

    .fujiba-ca-list {
        max-height: 540px;
        overflow-y: auto;
    }

    .fujiba-ca-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 18px;
        cursor: pointer;
        border-bottom: 1px solid rgba(15,23,42,0.04);
        transition: background 150ms ease;
        opacity: 0;
        animation: fujiba-ca-row-in 280ms cubic-bezier(0.215,0.61,0.355,1) forwards;
    }
    .dark .fujiba-ca-row { border-bottom-color: rgba(255,255,255,0.04); }
    .fujiba-ca-row:last-child { border-bottom: 0; }
    .fujiba-ca-row:hover { background: rgba(59,130,246,0.04); }
    @keyframes fujiba-ca-row-in {
        from { opacity: 0; transform: translateY(2px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Avatar */
    .fujiba-ca-avatar-link {
        flex-shrink: 0 !important;
        text-decoration: none;
        width: 28px !important;
        height: 28px !important;
        display: inline-block !important;
        overflow: hidden !important;
        border-radius: 50%;
    }
    .fujiba-ca-avatar {
        width: 28px !important; height: 28px !important;
        border-radius: 50%;
        object-fit: cover;
        display: flex !important; align-items: center; justify-content: center;
        color: #fff; font-weight: 700; font-size: 11px;
        flex-shrink: 0;
    }
    .fujiba-ca-avatar-link img.fujiba-ca-avatar { width: 28px !important; height: 28px !important; max-width: 28px !important; max-height: 28px !important; }
    .fujiba-ca-avatar-fallback {
        background: linear-gradient(135deg,#94a3b8,#64748b);
    }

    /* Body */
    .fujiba-ca-body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .fujiba-ca-line1 {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        color: #1f2937;
    }
    .dark .fujiba-ca-line1 { color: #f3f4f6; }
    .fujiba-ca-name {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .fujiba-ca-cart-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        font-size: 12px;
        line-height: 1;
        flex-shrink: 0;
        color: #fff;
    }
    .fujiba-ca-cart-icon.is-converted { background: #10b981; box-shadow: 0 0 0 2px rgba(16,185,129,0.18); }
    .fujiba-ca-cart-icon.is-abandoned { background: #ef4444; box-shadow: 0 0 0 2px rgba(239,68,68,0.18); }
    .fujiba-ca-cart-icon.is-active    { background: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.18); }

    /* Purchase-count badge — mirrors /admin/customers tier palette */
    .fujiba-ca-purchase-badge {
        display: inline-flex; align-items: center; gap: 3px;
        padding: 1px 7px;
        border-radius: 9999px;
        font-size: 10px;
        font-weight: 700;
        line-height: 1.5;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .fujiba-ca-purchase-badge i { font-size: 10px; }
    .fujiba-ca-purchase-badge.is-tier-zero   { background: #f3f4f6; color: #4b5563; }
    .fujiba-ca-purchase-badge.is-tier-new    { background: #dbeafe; color: #1d4ed8; }
    .fujiba-ca-purchase-badge.is-tier-repeat { background: #d1fae5; color: #047857; }
    .fujiba-ca-purchase-badge.is-tier-loyal  { background: #fef3c7; color: #92400e; }
    .dark .fujiba-ca-purchase-badge.is-tier-zero   { background: rgba(255,255,255,0.06); color: #d1d5db; }
    .dark .fujiba-ca-purchase-badge.is-tier-new    { background: rgba(59,130,246,0.22); color: #93c5fd; }
    .dark .fujiba-ca-purchase-badge.is-tier-repeat { background: rgba(16,185,129,0.22); color: #6ee7b7; }
    .dark .fujiba-ca-purchase-badge.is-tier-loyal  { background: rgba(245,158,11,0.22); color: #fcd34d; }

    /* Tinted action labels for quick visual scan */
    .fujiba-ca-line2.is-converted { color: #059669; font-weight: 600; }
    .fujiba-ca-line2.is-abandoned { color: #dc2626; font-weight: 600; }
    .fujiba-ca-line2.is-active    { color: #2563eb; font-weight: 600; }
    .dark .fujiba-ca-line2.is-converted { color: #34d399; }
    .dark .fujiba-ca-line2.is-abandoned { color: #f87171; }
    .dark .fujiba-ca-line2.is-active    { color: #60a5fa; }

    .fujiba-ca-line2 {
        font-size: 12px;
        color: #6b7280;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .dark .fujiba-ca-line2 { color: #9ca3af; }

    .fujiba-ca-time {
        flex-shrink: 0;
        font-size: 12px;
        color: #3b82f6;
        font-weight: 500;
    }

    /* Empty */
    .fujiba-ca-empty {
        text-align: center;
        padding: 32px 18px;
        color: #6b7280;
        font-size: 13px;
    }

    /* Shimmer rows */
    .fujiba-ca-row-shimmer {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 18px;
        border-bottom: 1px solid rgba(15,23,42,0.04);
    }
    .fujiba-ca-shimmer-avatar {
        width: 40px; height: 40px; border-radius: 50%;
        flex-shrink: 0;
    }
    .fujiba-ca-shimmer-body { flex: 1; display: flex; flex-direction: column; gap: 6px; }
    .fujiba-ca-shimmer-line { height: 12px; border-radius: 4px; }
    .fujiba-ca-shimmer-line.short { width: 60%; }

    /* Modal — keep clean white */
    .fujiba-ca-modal-backdrop {
        position: fixed; inset: 0;
        background: rgba(15,23,42,0.55);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: flex; align-items: center; justify-content: center;
        padding: 12px;
    }
    .fujiba-ca-modal {
        background: #fff;
        border-radius: 14px;
        max-width: 560px;
        width: 100%;
        max-height: 88vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        position: relative;
        box-shadow: 0 24px 48px rgba(0,0,0,0.30);
    }
    .dark .fujiba-ca-modal { background: rgb(17,24,39); }
    .fujiba-ca-modal-close {
        position: absolute; top: 10px; right: 14px;
        width: 28px; height: 28px;
        border: 0; background: rgba(15,23,42,0.08);
        color: #1f2937; border-radius: 50%;
        font-size: 18px; line-height: 1; cursor: pointer; z-index: 2;
    }
    .fujiba-ca-modal-close:hover { background: rgba(15,23,42,0.18); }
    .fujiba-ca-modal-header {
        display: flex; align-items: center; gap: 12px;
        padding: 18px 18px 14px;
        border-bottom: 1px solid rgba(15,23,42,0.06);
    }
    .fujiba-ca-modal-avatar {
        width: 48px; height: 48px;
        border-radius: 50%;
        object-fit: cover;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 700;
        flex-shrink: 0;
    }
    .fujiba-ca-modal-id { flex: 1; min-width: 0; }
    .fujiba-ca-modal-name { font-size: 15px; font-weight: 700; color: #1f2937; margin: 0; }
    .dark .fujiba-ca-modal-name { color: #f3f4f6; }
    .fujiba-ca-modal-meta { font-size: 11px; color: #6b7280; margin-top: 2px; }
    .fujiba-ca-modal-body { padding: 14px 18px 18px; overflow-y: auto; }

    .fujiba-ca-items-table { width: 100%; font-size: 13px; }
    .fujiba-ca-items-table th {
        font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em;
        color: #6b7280; padding: 6px 4px;
        text-align: left;
        border-bottom: 1px solid rgba(15,23,42,0.06);
    }
    .fujiba-ca-items-table td {
        padding: 8px 4px;
        border-bottom: 1px solid rgba(15,23,42,0.04);
    }
    .fujiba-ca-item-name { font-weight: 500; color: #1f2937; }
    .dark .fujiba-ca-item-name { color: #f3f4f6; }
    .fujiba-ca-item-sku { font-size: 10px; color: #9ca3af; margin-top: 2px; }
    .fujiba-ca-modal-totals { margin-top: 12px; font-size: 13px; }
    .fujiba-ca-modal-totals > div {
        display: flex; justify-content: space-between; padding: 4px 0;
    }
    .fujiba-ca-modal-totals .fujiba-ca-grand {
        margin-top: 6px; padding-top: 8px;
        border-top: 1px solid rgba(15,23,42,0.06);
        font-weight: 700; font-size: 15px;
    }
    .fujiba-ca-modal-totals .fujiba-ca-cost {
        color: #6b7280;
        font-size: 12px;
    }
    .fujiba-ca-modal-totals .fujiba-ca-profit {
        margin-top: 4px;
        padding-top: 8px;
        border-top: 1px dashed rgba(15,23,42,0.1);
        font-size: 14px;
        font-weight: 700;
    }
    .fujiba-ca-modal-totals .fujiba-ca-profit.is-gain { color: #059669; }
    .fujiba-ca-modal-totals .fujiba-ca-profit.is-loss { color: #dc2626; }
    .fujiba-ca-modal-totals .fujiba-ca-profit small {
        font-weight: 500;
        font-size: 11px;
        margin-left: 4px;
        opacity: 0.8;
    }
    .fujiba-ca-cost-missing {
        font-size: 11px;
        color: #9ca3af;
        font-style: italic;
        margin-top: 6px;
        padding-top: 6px;
        border-top: 1px dashed rgba(15,23,42,0.08);
    }
    .fujiba-ca-modal-enter-active,
    .fujiba-ca-modal-leave-active { transition: opacity 200ms ease; }
    .fujiba-ca-modal-enter-from,
    .fujiba-ca-modal-leave-to { opacity: 0; }
    .text-right { text-align: right; }
    .tabular-nums { font-variant-numeric: tabular-nums; }
    .text-emerald-600 { color: #059669; }

    @media (max-width: 640px) {
        .fujiba-ca-shell { max-width: 100% !important; overflow: hidden !important; }
        .fujiba-ca-titlebar { padding: 12px 14px; }
        .fujiba-ca-row { padding: 10px 12px; gap: 10px; min-width: 0; }
        .fujiba-ca-avatar { width: 28px; height: 28px; font-size: 11px; }
        .fujiba-ca-body { min-width: 0; }
        .fujiba-ca-line1 { font-size: 12px; }
        .fujiba-ca-line2 { font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .fujiba-ca-time { font-size: 10px; flex-shrink: 0; }
        .fujiba-ca-modal { border-radius: 12px 12px 0 0; align-self: flex-end; max-height: 92vh; }
        .fujiba-ca-modal-backdrop { align-items: flex-end; padding: 0; }
        .fujiba-ca-items-table th, .fujiba-ca-items-table td { font-size: 11px; padding: 4px 2px; }
    }
</style>

<script type="module">
    (function () {
        console.log('[Fujiba CartActivity] script loaded, app=', typeof window.app, 'has component:', !!(window.app && window.app.component));
        if (window.__fujibaCartActivityRegistered) return;
        window.__fujibaCartActivityRegistered = true;

        // Move our <style> block out of #app (where Vue may strip/skip it)
        // and into <head> so the browser parses it as a real stylesheet.
        try {
            const inApp = document.querySelectorAll('#app style');
            inApp.forEach(s => {
                if (s.textContent && s.textContent.includes('fujiba-')) {
                    document.head.appendChild(s);
                }
            });
        } catch (e) { console.warn('Fujiba style relocate failed', e); }

        let cartActivityTries = 0;
        function registerCartActivity() {
            cartActivityTries++;
            if (typeof window.app === 'undefined' || !window.app || !window.app.component) {
                if (cartActivityTries === 1 || cartActivityTries % 60 === 0) {
                    console.log('[Fujiba CartActivity] waiting for app, try=', cartActivityTries);
                }
                if (cartActivityTries > 600) {
                    console.error('[Fujiba CartActivity] gave up waiting for app after 600 frames');
                    return;
                }
                return requestAnimationFrame(registerCartActivity);
            }
            console.log('[Fujiba CartActivity] registering component after', cartActivityTries, 'tries');

            // Debug: check post-mount state
            window.addEventListener('load', () => {
                setTimeout(() => {
                    const tag = document.querySelector('v-fujiba-cart-activity');
                    const card = document.getElementById('fujiba-cart-activity-card');
                    const shell = document.querySelector('.fujiba-ca-shell');
                    console.log('[Fujiba CartActivity post-load] tag exists:', !!tag,
                        '| card children:', card ? card.children.length : '?',
                        '| shell rendered:', !!shell,
                        '| #app data-v-app:', document.getElementById('app')?.hasAttribute('data-v-app'),
                        '| component registered:', !!(window.app._context && window.app._context.components['v-fujiba-cart-activity']));
                }, 500);
            });

            const tplEl = document.getElementById('v-fujiba-cart-activity-template');
            const tplStr = tplEl ? tplEl.innerHTML : '';
            console.log('[Fujiba CartActivity] template string length:', tplStr.length);
            window.app.component('v-fujiba-cart-activity', {
            template: tplStr,

            props: {
                endpoint:           { type: String, required: true },
                detailUrl:          { type: String, required: true },
                customerUrlPattern: { type: String, required: true },
                initialData:        { type: Object, required: true },
            },

            data() {
                return {
                    data: this.initialData,
                    isLoading: false,
                    active: 'all',
                    modal: { open: false, loading: false, cart: null, detail: null },
                };
            },

            computed: {
                rows() { return this.data?.cart_activity ?? []; },

                counters() {
                    const c = { active: 0, abandoned: 0, converted: 0 };
                    for (const r of this.rows) c[r.state] = (c[r.state] || 0) + 1;
                    return c;
                },

                filters() {
                    return [
                        { key: 'all',        label: this.t('all'),        count: this.rows.length },
                        { key: 'active',     label: this.t('state_active'),    count: this.counters.active },
                        { key: 'abandoned',  label: this.t('state_abandoned'), count: this.counters.abandoned },
                        { key: 'converted',  label: this.t('state_converted'), count: this.counters.converted },
                    ];
                },

                filtered() {
                    if (this.active === 'all') return this.rows;
                    return this.rows.filter(r => r.state === this.active);
                },
            },

            mounted() {
                this.$emitter.on('reporting-filter-updated', this.onFilterChange);
                document.addEventListener('keydown', this.onKey);
            },

            beforeUnmount() {
                document.removeEventListener('keydown', this.onKey);
            },

            methods: {
                t(key) {
                    const dict = {
                        all:                "@lang('fujiba::app.dashboard.cart_activity.state_all')",
                        state_active:       "@lang('fujiba::app.dashboard.cart_activity.state_active')",
                        state_abandoned:    "@lang('fujiba::app.dashboard.cart_activity.state_abandoned')",
                        state_converted:    "@lang('fujiba::app.dashboard.cart_activity.state_converted')",
                    };
                    return dict[key] || key;
                },

                purchaseTier(n) {
                    const x = Number(n) || 0;
                    if (x === 0) return 'is-tier-zero';
                    if (x <= 2) return 'is-tier-new';
                    if (x <= 9) return 'is-tier-repeat';
                    return 'is-tier-loyal';
                },

                stateLabel(state) {
                    const map = {
                        active:    "@lang('fujiba::app.dashboard.cart_activity.state_active')",
                        abandoned: "@lang('fujiba::app.dashboard.cart_activity.state_abandoned')",
                        converted: "@lang('fujiba::app.dashboard.cart_activity.state_converted')",
                    };
                    return map[state] || state;
                },

                actionLabel(c) {
                    const price = this.formatPrice(c.grand_total);
                    if (c.state === 'converted') {
                        return "@lang('fujiba::app.dashboard.cart_activity.action_converted')" + ' (' + price + ')';
                    }
                    if (c.state === 'abandoned') {
                        return "@lang('fujiba::app.dashboard.cart_activity.action_abandoned')";
                    }
                    return "@lang('fujiba::app.dashboard.cart_activity.action_active')";
                },

                customerHref(c) {
                    if (!c || !c.customer_id) return 'javascript:void(0)';
                    return this.customerUrlPattern.replace('__ID__', c.customer_id);
                },

                avatarGradient(c) {
                    const palettes = [
                        'linear-gradient(135deg, #6366f1, #8b5cf6)',
                        'linear-gradient(135deg, #ec4899, #f43f5e)',
                        'linear-gradient(135deg, #10b981, #059669)',
                        'linear-gradient(135deg, #f59e0b, #d97706)',
                        'linear-gradient(135deg, #06b6d4, #0891b2)',
                        'linear-gradient(135deg, #8b5cf6, #6366f1)',
                    ];
                    const seed = (c?.cart_id ?? 0) + (c?.customer_id ?? 0);
                    return palettes[seed % palettes.length];
                },

                onAvatarError(e) {
                    e.target.style.display = 'none';
                },

                onKey(e) {
                    if (e.key === 'Escape' && this.modal.open) this.closeDetail();
                },

                async openDetail(c) {
                    console.log('[Fujiba CartActivity] openDetail clicked', c?.cart_id);
                    this.modal = { open: true, loading: true, cart: c, detail: null };
                    try {
                        const url = this.detailUrl.replace('__ID__', c.cart_id);
                        const resp = await this.$axios.get(url);
                        this.modal.detail = resp.data?.cart ?? null;
                    } catch (err) {
                        console.error('Fujiba cart activity: detail fetch failed', err);
                        this.modal.detail = null;
                    } finally {
                        this.modal.loading = false;
                    }
                },

                closeDetail() {
                    this.modal.open = false;
                },

                async onFilterChange(filters) {
                    this.isLoading = true;
                    try {
                        const params = { channel: filters.channel ?? '' };
                        const resp = await this.$axios.get(this.endpoint, { params });
                        this.data = resp.data;
                    } catch (e) {
                        console.error('Fujiba cart activity fetch failed', e);
                    } finally {
                        this.isLoading = false;
                    }
                },

                formatPrice(amount) {
                    const abs    = Math.abs(Number(amount) || 0);
                    const sign   = amount < 0 ? '-' : '';
                    const locale = "{{ app()->getLocale() }}";
                    if (abs < 10000) return sign + '¥' + Math.round(abs).toLocaleString();
                    const oneDec = (n) => {
                        const r = Math.round(n * 10) / 10;
                        if (r === Math.trunc(r)) return Math.trunc(r).toLocaleString();
                        return r.toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 });
                    };
                    if (locale === 'ja') {
                        if (abs >= 100000000) return sign + '¥' + oneDec(abs / 100000000) + '億';
                        return sign + '¥' + oneDec(abs / 10000) + '万';
                    }
                    if (abs >= 1000000) return sign + '¥' + oneDec(abs / 1000000) + 'M';
                    return sign + '¥' + oneDec(abs / 1000) + 'K';
                },
            },
        });
        }

        registerCartActivity();
    })();
</script>

<template>
    <div class="flex flex-col bg-[#F2F2F7]" style="height: calc(100vh - 44px);">

        <!-- ═══ COLLAPSIBLE HEADER (auto-hides on scroll on mobile) ═══ -->
        <div
            class="flex-shrink-0 overflow-hidden transition-[max-height,opacity] duration-200 ease-out"
            :class="headerCollapsed ? 'max-h-0 opacity-0' : 'max-h-[480px] opacity-100'"
        >
            <!-- Search + action chips -->
            <div class="bg-white px-3 py-2 border-b border-[#E5E5EA]">
                <input
                    v-model="searchQuery"
                    type="search"
                    class="w-full rounded-xl border border-[#E5E5EA] bg-[#F2F2F7] px-4 py-2.5 text-[15px] text-[#1C1C1E] placeholder-[#8E8E93] outline-none focus:border-[#00B8D9] focus:ring-1 focus:ring-[#00B8D9]"
                    :placeholder="t('pos.products.inception.search_placeholder')"
                />

                <div class="mt-2 flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="rounded-full border border-[#E5E5EA] bg-[#F2F2F7] px-3 py-1 text-[12px] text-[#1C1C1E] active:opacity-70"
                        @click="fillFromSystem"
                    >{{ t('pos.products.inception.fill_system') }}</button>

                    <button
                        type="button"
                        class="rounded-full border border-[#FF3B30] bg-white px-3 py-1 text-[12px] text-[#FF3B30] active:opacity-70"
                        @click="clearCounts"
                    >{{ t('pos.products.inception.clear_counts') }}</button>
                </div>
            </div>

            <!-- Stock-level filter pills + custom threshold -->
            <div class="bg-white px-3 py-2 border-b border-[#E5E5EA]">
                <div class="flex items-center gap-1.5 overflow-x-auto" style="scrollbar-width:none;">
                    <button
                        v-for="f in ['all','zero','low','in_stock','custom']"
                        :key="f"
                        type="button"
                        class="flex-shrink-0 rounded-full border px-3 py-1 text-[12px] font-medium whitespace-nowrap active:opacity-70"
                        :class="stockFilter === f
                            ? 'border-[#00B8D9] bg-[#E6FAFD] text-[#00B8D9]'
                            : 'border-[#E5E5EA] bg-white text-[#1C1C1E]'"
                        @click="stockFilter = f"
                    >
                        {{ filterLabel(f) }}
                    </button>

                    <input
                        v-if="stockFilter === 'custom'"
                        v-model.number="customThreshold"
                        type="number"
                        min="0"
                        class="ml-1 h-7 w-16 flex-shrink-0 rounded-full border border-[#00B8D9] bg-white px-2 text-center text-[12px] font-semibold text-[#1C1C1E] outline-none"
                        placeholder="≤"
                    />
                </div>
            </div>

            <!-- Live totals strip -->
            <div class="grid grid-cols-4 gap-px bg-[#E5E5EA] border-b border-[#E5E5EA]">
                <div class="bg-white p-2 text-center">
                    <p class="text-[10px] text-[#8E8E93] truncate">{{ t('pos.products.inception.total_products') }}</p>
                    <p class="text-[15px] font-semibold text-[#1C1C1E]">{{ totals.products.toLocaleString() }}</p>
                    <p class="text-[10px] text-[#00B8D9]">
                        {{ t('pos.products.inception.counted_count') }}: {{ totals.countedRows }}
                    </p>
                </div>
                <div class="bg-white p-2 text-center">
                    <p class="text-[10px] text-[#8E8E93] truncate">{{ t('pos.products.inception.total_system') }}</p>
                    <p class="text-[15px] font-semibold text-[#1C1C1E]">{{ totals.systemQty.toLocaleString() }}</p>
                </div>
                <div class="bg-white p-2 text-center">
                    <p class="text-[10px] text-[#8E8E93] truncate">{{ t('pos.products.inception.total_counted') }}</p>
                    <p class="text-[15px] font-semibold text-[#00B8D9]">{{ totals.countedQty.toLocaleString() }}</p>
                </div>
                <div class="bg-white p-2 text-center">
                    <p class="text-[10px] text-[#8E8E93] truncate">{{ t('pos.products.inception.total_diff') }}</p>
                    <p
                        class="text-[15px] font-semibold"
                        :class="totals.diff > 0 ? 'text-green-600' : (totals.diff < 0 ? 'text-[#FF3B30]' : 'text-[#1C1C1E]')"
                    >{{ totals.diff > 0 ? '+' : '' }}{{ totals.diff.toLocaleString() }}</p>
                </div>
            </div>
        </div>

        <!-- ── Sort + view toggle + collapse toggle (always visible) ── -->
        <div class="flex-shrink-0 flex items-center justify-between gap-2 h-10 border-b border-[#E5E5EA] bg-[#F7F7F8] px-3">
            <button
                type="button"
                class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-md border border-[#E5E5EA] bg-white text-[14px] text-[#1C1C1E] active:opacity-70"
                :title="headerCollapsed ? 'Show filters' : 'Hide filters'"
                @click="headerCollapsed = !headerCollapsed"
            >{{ headerCollapsed ? '▾' : '▴' }}</button>

            <div class="flex items-center gap-2 overflow-x-auto" style="scrollbar-width:none;">
                <button
                    v-for="key in ['name','system','counted','diff']"
                    :key="key"
                    type="button"
                    class="flex items-center gap-1 rounded-md border border-[#E5E5EA] bg-white px-2.5 py-1 text-[12px] font-medium whitespace-nowrap active:opacity-70"
                    :class="sortKey === key ? 'border-[#00B8D9] text-[#00B8D9] bg-[#E6FAFD]' : 'text-[#1C1C1E]'"
                    @click="toggleSort(key)"
                >
                    {{ sortLabel(key) }} {{ sortIcon(key) }}
                </button>
            </div>

            <div class="flex flex-shrink-0 items-center rounded-md border border-[#E5E5EA] bg-white">
                <button
                    type="button"
                    class="px-2 py-1 text-[12px] font-semibold"
                    :class="viewMode === 'grid' ? 'bg-[#00B8D9] text-white rounded-l-md' : 'text-[#8E8E93]'"
                    @click="setViewMode('grid')"
                >▦</button>
                <button
                    type="button"
                    class="px-2 py-1 text-[12px] font-semibold"
                    :class="viewMode === 'list' ? 'bg-[#00B8D9] text-white rounded-r-md' : 'text-[#8E8E93]'"
                    @click="setViewMode('list')"
                >☰</button>
            </div>
        </div>

        <!-- ── Scrollable area ──────────────────────────────────────── -->
        <div
            ref="listEl"
            class="flex-1 overflow-y-auto bg-[#F2F2F7]"
            style="padding-bottom: 120px;"
        >
            <template v-if="loading">
                <div class="flex items-center justify-center py-16">
                    <span class="icon-spinner animate-spin text-3xl text-[#00B8D9]"></span>
                </div>
            </template>

            <template v-else>
                <div
                    v-if="!sortedProducts.length"
                    class="flex flex-col items-center justify-center py-16 text-[#8E8E93]"
                >
                    <p class="text-[15px]">{{ t('pos.products.inception.no_products') }}</p>
                </div>

                <!-- ═══ GRID VIEW (big image, big font — Air Regi style) ═══ -->
                <div
                    v-else-if="viewMode === 'grid'"
                    class="grid gap-2 p-2"
                    style="grid-template-columns: repeat(2, minmax(0, 1fr));"
                >
                    <div
                        v-for="product in sortedProducts"
                        :key="product.id"
                        class="flex flex-col overflow-hidden rounded-xl border border-[#E5E5EA] bg-white shadow-sm"
                        :class="product._counted !== null ? 'ring-2 ring-[#00B8D9]' : ''"
                    >
                        <!-- Big image (180px) -->
                        <div class="relative w-full bg-[#F2F2F7]" style="height:180px;">
                            <img
                                v-if="product.images && product.images.length"
                                :src="product.images[0].url"
                                class="absolute inset-0 h-full w-full object-cover"
                                :alt="product.name"
                                loading="lazy"
                            />
                            <div
                                v-else
                                class="absolute inset-0 flex items-center justify-center text-[28px] text-[#C7C7CC]"
                            >
                                <i class="icon-image"></i>
                            </div>

                            <!-- BIG color-coded stock badge (top-left) -->
                            <span
                                class="absolute left-1.5 top-1.5 inline-flex min-w-[44px] items-center justify-center rounded-lg px-2 py-1 text-[18px] font-extrabold leading-none text-white shadow"
                                :class="stockBadgeClass(product)"
                            >
                                {{ (product._systemQty ?? 0).toLocaleString() }}
                            </span>

                            <!-- Diff badge (top-right) -->
                            <span
                                v-if="product._counted !== null"
                                class="absolute right-1.5 top-1.5 rounded-md px-2 py-1 text-[14px] font-bold leading-none text-white shadow"
                                :class="rowDiff(product) > 0 ? 'bg-green-600' : (rowDiff(product) < 0 ? 'bg-red-500' : 'bg-[#8E8E93]')"
                            >
                                {{ rowDiff(product) > 0 ? '+' : '' }}{{ rowDiff(product).toLocaleString() }}
                            </span>
                        </div>

                        <!-- Body: name + SKU + counted input -->
                        <div class="flex flex-col gap-1 p-2.5">
                            <p class="line-clamp-2 text-[14px] font-semibold leading-tight text-[#1C1C1E]" style="min-height:34px;">
                                {{ product.name }}
                            </p>
                            <p
                                v-if="product.sku"
                                class="truncate text-[11px] text-[#8E8E93]"
                            >{{ product.sku }}</p>

                            <div class="mt-1.5 flex items-center gap-1">
                                <button
                                    type="button"
                                    class="h-9 w-9 flex-shrink-0 rounded-lg bg-red-50 text-[20px] font-bold text-red-500 active:opacity-70"
                                    @click="adjustCount(product, -1)"
                                >−</button>
                                <input
                                    type="number"
                                    inputmode="numeric"
                                    min="0"
                                    :value="product._counted === null ? '' : product._counted"
                                    class="h-9 w-full min-w-0 rounded-lg border border-[#E5E5EA] bg-white px-2 text-center text-[18px] font-bold text-[#1C1C1E] outline-none focus:border-[#00B8D9] focus:ring-1 focus:ring-[#00B8D9]"
                                    placeholder="—"
                                    @input="onCountInput(product, $event.target.value)"
                                    @focus="$event.target.select()"
                                />
                                <button
                                    type="button"
                                    class="h-9 w-9 flex-shrink-0 rounded-lg bg-green-50 text-[20px] font-bold text-green-600 active:opacity-70"
                                    @click="adjustCount(product, 1)"
                                >＋</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══ LIST VIEW (compact rows — original layout) ═══ -->
                <div v-else class="bg-white">
                    <div
                        v-for="product in sortedProducts"
                        :key="product.id"
                        data-row
                        class="flex items-center border-b border-[#E5E5EA] bg-white px-3"
                        style="height:56px;"
                        :class="product._counted ? 'bg-[#F7FEFF]' : ''"
                    >
                        <div class="flex items-center gap-2 overflow-hidden" style="width:50%;">
                            <div class="relative h-9 w-9 flex-shrink-0 overflow-hidden rounded border border-[#E5E5EA] bg-[#F2F2F7]">
                                <img
                                    v-if="product.images && product.images.length"
                                    :src="product.images[0].url"
                                    class="absolute inset-0 h-full w-full object-cover"
                                    :alt="product.name"
                                    loading="lazy"
                                />
                                <div
                                    v-else
                                    class="absolute inset-0 flex items-center justify-center text-[14px] text-[#C7C7CC]"
                                >
                                    <i class="icon-image"></i>
                                </div>
                            </div>
                            <div class="min-w-0 flex flex-col">
                                <span class="truncate text-[14px] text-[#1C1C1E]">{{ product.name }}</span>
                                <span
                                    v-if="product.sku"
                                    class="truncate text-[11px] text-[#8E8E93]"
                                >{{ product.sku }}</span>
                            </div>
                        </div>

                        <div class="text-right text-[14px] text-[#8E8E93]" style="width:16%;">
                            {{ (product._systemQty ?? 0).toLocaleString() }}
                        </div>

                        <div class="px-1" style="width:18%;">
                            <input
                                type="number"
                                inputmode="numeric"
                                min="0"
                                :value="product._counted === null ? '' : product._counted"
                                class="w-full rounded-lg border border-[#E5E5EA] bg-white px-2 py-1.5 text-right text-[15px] text-[#1C1C1E] outline-none focus:border-[#00B8D9] focus:ring-1 focus:ring-[#00B8D9]"
                                placeholder="—"
                                @input="onCountInput(product, $event.target.value)"
                                @focus="$event.target.select()"
                            />
                        </div>

                        <div
                            class="text-right text-[14px] font-medium"
                            style="width:16%;"
                            :class="diffClass(product)"
                        >
                            <template v-if="product._counted === null">
                                <span class="text-[#C7C7CC]">—</span>
                            </template>
                            <template v-else>
                                {{ rowDiff(product) > 0 ? '+' : '' }}{{ rowDiff(product).toLocaleString() }}
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- ── Bottom action bar ────────────────────────────────────── -->
        <div
            class="fixed inset-x-0 bottom-0 z-40 flex items-center gap-3 border-t border-[#E5E5EA] bg-white px-4"
            style="height:56px; padding-bottom: env(safe-area-inset-bottom, 0px);"
        >
            <button
                type="button"
                class="flex h-10 items-center rounded-lg border border-[#C7C7CC] px-3 text-[14px] text-[#1C1C1E] active:opacity-70"
                @click="router.push('/products/list')"
            >
                ← {{ t('pos.products.list.title') }}
            </button>

            <span class="ml-auto text-[12px] text-[#8E8E93]">
                {{ totals.countedRows }} / {{ totals.products }}
            </span>

            <button
                type="button"
                class="flex h-10 items-center rounded-lg px-5 text-[15px] font-semibold text-white transition-colors duration-200"
                :class="totals.countedRows > 0 ? 'bg-[#00B8D9] active:opacity-70' : 'bg-[#C7C7CC] pointer-events-none'"
                :disabled="totals.countedRows === 0 || saving"
                @click="confirmApply = true"
            >
                <span v-if="saving" class="icon-spinner mr-1.5 animate-spin text-sm"></span>
                {{ t('pos.products.inception.apply_all') }}
            </button>
        </div>

        <!-- ── Apply confirm overlay ────────────────────────────────── -->
        <Teleport to="body">
            <div
                v-if="confirmApply"
                class="fixed inset-0 z-50 flex items-end justify-center md:items-center"
            >
                <div class="absolute inset-0 bg-black/40" @click="confirmApply = false"></div>

                <div class="relative z-10 w-full max-w-sm rounded-t-2xl bg-white p-6 md:rounded-2xl">
                    <p class="mb-2 text-center text-[17px] font-semibold text-[#1C1C1E]">
                        {{ t('pos.products.inception.apply_confirm_title') }}
                    </p>
                    <p class="mb-5 text-center text-[14px] text-[#8E8E93]">
                        {{ t('pos.products.inception.apply_confirm_body', { count: totals.countedRows }) }}
                    </p>

                    <!-- Progress bar (visible while saving) -->
                    <div v-if="saving" class="mb-4">
                        <div class="mb-1 flex items-center justify-between text-[12px] text-[#8E8E93]">
                            <span>{{ saveProgress.done }} / {{ saveProgress.total }}</span>
                            <span v-if="saveProgress.failed > 0" class="text-[#FF3B30]">{{ saveProgress.failed }} failed</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-[#E5E5EA]">
                            <div
                                class="h-full bg-[#00B8D9] transition-[width] duration-200"
                                :style="{ width: saveProgress.total > 0 ? ((saveProgress.done + saveProgress.failed) / saveProgress.total * 100) + '%' : '0%' }"
                            ></div>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button
                            type="button"
                            class="flex-1 rounded-xl border border-[#E5E5EA] bg-[#F2F2F7] py-3 text-[15px] font-medium text-[#1C1C1E] active:opacity-70 disabled:opacity-50"
                            :disabled="saving"
                            @click="confirmApply = false"
                        >{{ t('pos.products.inception.cancel_btn') }}</button>

                        <button
                            type="button"
                            class="flex-1 rounded-xl bg-[#00B8D9] py-3 text-[15px] font-semibold text-white active:opacity-70 disabled:opacity-50"
                            :disabled="saving"
                            @click="applyAll"
                        >
                            <span v-if="saving" class="icon-spinner mr-1 animate-spin text-sm"></span>
                            {{ saving ? t('pos.products.inception.applying', { done: saveProgress.done, total: saveProgress.total }) : t('pos.products.inception.apply_btn') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
    import { ref, reactive, computed, inject, onMounted, onBeforeUnmount, watch } from 'vue';
    import { useRouter } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import { useIndexedDB } from '@src/composable/indexed-db';
    import { useMutation } from '@vue/apollo-composable';
    import { BULK_UPDATE_PRODUCTS } from '@src/graphql/products';

    const router  = useRouter();
    const { t }   = useI18n();
    const emitter = inject('emitter');
    const DB      = useIndexedDB();

    const loading       = ref(true);
    const saving        = ref(false);
    const confirmApply  = ref(false);
    const localProducts = ref([]);
    const listEl        = ref(null);
    const searchQuery   = ref('');

    /**
     * Stock-level filter: 'all' | 'zero' | 'low' | 'in_stock' | 'custom'
     * 'low' uses the LOW_STOCK_THRESHOLD constant; 'custom' uses customThreshold.
     */
    const LOW_STOCK_THRESHOLD = 10;
    const stockFilter     = ref('all');
    const customThreshold = ref(5);

    try {
        const sf = localStorage.getItem('pos-inception-stock-filter');
        if (sf) stockFilter.value = sf;
        const ct = parseInt(localStorage.getItem('pos-inception-custom-threshold') || '', 10);
        if (!isNaN(ct) && ct >= 0) customThreshold.value = ct;
    } catch (_) { /* ignore */ }

    watch(stockFilter,     (v) => { try { localStorage.setItem('pos-inception-stock-filter', v); } catch (_) {} });
    watch(customThreshold, (v) => { try { localStorage.setItem('pos-inception-custom-threshold', String(v ?? '')); } catch (_) {} });

    function filterLabel(key) {
        if (key === 'all')       return t('pos.products.inception.filter_all');
        if (key === 'zero')      return t('pos.products.inception.filter_zero');
        if (key === 'low')       return `${t('pos.products.inception.filter_low')} (<${LOW_STOCK_THRESHOLD})`;
        if (key === 'in_stock')  return t('pos.products.inception.filter_in_stock');
        if (key === 'custom')    return t('pos.products.inception.filter_custom');
        return key;
    }

    function passesStockFilter(p) {
        const sys = parseInt(p._systemQty ?? 0, 10) || 0;
        switch (stockFilter.value) {
            case 'zero':     return sys === 0;
            case 'low':      return sys > 0 && sys < LOW_STOCK_THRESHOLD;
            case 'in_stock': return sys > 0;
            case 'custom':   return sys <= (parseInt(customThreshold.value, 10) || 0);
            default:         return true;
        }
    }

    const filteredProducts = computed(() => {
        const q = searchQuery.value.trim().toLowerCase();
        return localProducts.value.filter(p => {
            if (!passesStockFilter(p)) return false;
            if (!q) return true;
            return (p.name ?? '').toLowerCase().includes(q)
                || (p.sku ?? '').toLowerCase().includes(q)
                || (p.barcode ?? '').toLowerCase().includes(q)
                || (p.gtin ?? '').toLowerCase().includes(q)
                || (p.scanValue ?? '').toLowerCase().includes(q);
        });
    });

    // ── Auto-hide header on scroll (mobile UX) ───────────────────────
    const headerCollapsed = ref(false);
    let lastScrollTop = 0;
    let scrollEl = null;

    function onScroll() {
        if (!scrollEl) return;
        const cur = scrollEl.scrollTop;
        if (cur > lastScrollTop + 12 && cur > 80) {
            headerCollapsed.value = true;
        } else if (cur < lastScrollTop - 12) {
            headerCollapsed.value = false;
        }
        lastScrollTop = cur;
    }

    // ── Sort state ────────────────────────────────────────────────
    // sortKey: 'name' | 'system' | 'counted' | 'diff' | null
    // sortDir: 'asc' | 'desc'
    const sortKey = ref(null);
    const sortDir = ref('asc');

    function toggleSort(key) {
        if (sortKey.value === key) {
            sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortKey.value = key;
            sortDir.value = 'asc';
        }
    }

    function sortIcon(key) {
        if (sortKey.value !== key) return '↕';
        return sortDir.value === 'asc' ? '↑' : '↓';
    }

    function sortLabel(key) {
        if (key === 'name')    return t('pos.products.list.title');
        if (key === 'system')  return t('pos.products.inception.system_qty');
        if (key === 'counted') return t('pos.products.inception.counted_qty');
        if (key === 'diff')    return t('pos.products.inception.diff');
        return key;
    }

    // ── View mode (grid = big-image cards, list = compact rows) ──
    const viewMode = ref('grid');
    try {
        const saved = localStorage.getItem('pos-inception-view-mode');
        if (saved === 'list' || saved === 'grid') viewMode.value = saved;
    } catch (_) { /* ignore */ }

    function setViewMode(mode) {
        viewMode.value = mode;
        try { localStorage.setItem('pos-inception-view-mode', mode); } catch (_) { /* ignore */ }
    }

    function adjustCount(product, delta) {
        const cur = product._counted === null ? (product._systemQty ?? 0) : (parseInt(product._counted, 10) || 0);
        const next = Math.max(0, cur + delta);
        product._counted = next;
    }

    /**
     * Color band for the big stock badge:
     *   0          → red    (out of stock)
     *   1..LOW-1   → amber  (low stock)
     *   LOW..      → green  (healthy)
     */
    function stockBadgeClass(product) {
        const sys = parseInt(product._systemQty ?? 0, 10) || 0;
        if (sys === 0) return 'bg-red-500';
        if (sys < LOW_STOCK_THRESHOLD) return 'bg-amber-500';
        return 'bg-green-600';
    }

    function sortValue(p, key) {
        if (key === 'name')    return (p.name ?? '').toLowerCase();
        if (key === 'system')  return parseInt(p._systemQty ?? 0, 10) || 0;
        if (key === 'counted') return p._counted === null ? -Infinity : (parseInt(p._counted, 10) || 0);
        if (key === 'diff')    return p._counted === null ? -Infinity : ((parseInt(p._counted, 10) || 0) - (parseInt(p._systemQty, 10) || 0));
        return 0;
    }

    const sortedProducts = computed(() => {
        const list = filteredProducts.value;
        if (!sortKey.value) return list;

        const dir = sortDir.value === 'asc' ? 1 : -1;
        return [...list].sort((a, b) => {
            const av = sortValue(a, sortKey.value);
            const bv = sortValue(b, sortKey.value);
            if (av < bv) return -1 * dir;
            if (av > bv) return  1 * dir;
            return 0;
        });
    });

    const totals = computed(() => {
        let products    = 0;
        let systemQty   = 0;
        let countedQty  = 0;
        let countedRows = 0;
        let diff        = 0;

        for (const p of localProducts.value) {
            products++;
            const sys = parseInt(p._systemQty ?? 0, 10) || 0;
            systemQty += sys;

            if (p._counted !== null && p._counted !== '' && !isNaN(parseInt(p._counted, 10))) {
                const cnt = parseInt(p._counted, 10);
                countedRows++;
                countedQty += cnt;
                diff       += (cnt - sys);
            }
        }

        return { products, systemQty, countedQty, countedRows, diff };
    });

    onMounted(async () => {
        const raw = await DB.getAllItems('products');

        localProducts.value = raw.map(p => ({
            ...p,
            _systemQty: parseInt(p.quantity ?? 0, 10) || 0,
            _counted:   null,
        }));

        loading.value = false;

        // Bind scroll listener after DOM mounts
        await new Promise(r => requestAnimationFrame(r));
        scrollEl = listEl.value;
        if (scrollEl) scrollEl.addEventListener('scroll', onScroll, { passive: true });
    });

    onBeforeUnmount(() => {
        if (scrollEl) scrollEl.removeEventListener('scroll', onScroll);
        scrollEl = null;
    });

    function onCountInput(product, raw) {
        if (raw === '' || raw === null || raw === undefined) {
            product._counted = null;
            return;
        }
        const n = parseInt(raw, 10);
        if (isNaN(n) || n < 0) {
            product._counted = null;
            return;
        }
        product._counted = n;
    }

    function rowDiff(product) {
        if (product._counted === null) return 0;
        return (parseInt(product._counted, 10) || 0) - (parseInt(product._systemQty, 10) || 0);
    }

    function diffClass(product) {
        if (product._counted === null) return 'text-[#C7C7CC]';
        const d = rowDiff(product);
        if (d > 0) return 'text-green-600';
        if (d < 0) return 'text-[#FF3B30]';
        return 'text-[#1C1C1E]';
    }

    function fillFromSystem() {
        for (const p of localProducts.value) {
            p._counted = p._systemQty;
        }
    }

    function clearCounts() {
        for (const p of localProducts.value) {
            p._counted = null;
        }
    }

    const { mutate: bulkUpdate } = useMutation(BULK_UPDATE_PRODUCTS);

    /** Save progress UI state */
    const saveProgress = ref({ done: 0, total: 0, failed: 0 });
    const CHUNK_SIZE = 25;

    async function applyAll() {
        if (saving.value) return;

        const dirty = localProducts.value.filter(p =>
            p._counted !== null && p._counted !== '' && parseInt(p._counted, 10) !== p._systemQty
        );

        if (!dirty.length) {
            confirmApply.value = false;
            return;
        }

        saving.value = true;
        saveProgress.value = { done: 0, total: dirty.length, failed: 0 };

        // Send only id + quantity — name/price/weight aren't being changed
        // and including them triggers heavy ProductObserver pipelines per row,
        // which times out for large stocktakes.
        const succeeded = [];

        for (let i = 0; i < dirty.length; i += CHUNK_SIZE) {
            const chunk = dirty.slice(i, i + CHUNK_SIZE);
            const payload = chunk.map(p => ({
                id:       p.id,
                quantity: parseInt(p._counted, 10) || 0,
            }));

            try {
                const response = await bulkUpdate({ input: { products: payload } });
                const result   = response?.data?.bulkUpdateOutletProducts;
                if (! result?.success) {
                    throw new Error(result?.message ?? 'Bulk update failed');
                }
                succeeded.push(...chunk);
                saveProgress.value.done += chunk.length;
            } catch (err) {
                saveProgress.value.failed += chunk.length;
                console.error('[StockInception] chunk failed', err);
            }
        }

        // Record session history (best-effort) — only for products that succeeded
        if (succeeded.length > 0) {
            try {
                const token = localStorage.getItem('accessToken');
                await fetch('/api/v1/pos/stocktake-sessions', {
                    method:  'POST',
                    headers: {
                        'Accept'        : 'application/json',
                        'Content-Type'  : 'application/json',
                        'Authorization' : `Bearer ${token}`,
                    },
                    body: JSON.stringify({
                        status: 'completed',
                        items:  succeeded.map(p => ({
                            product_id:  p.id,
                            sku:         p.sku ?? null,
                            system_qty:  parseInt(p._systemQty ?? 0, 10) || 0,
                            counted_qty: parseInt(p._counted, 10) || 0,
                        })),
                    }),
                });
            } catch (_) { /* history is non-fatal */ }
        }

        // Update local state for succeeded rows
        for (const p of succeeded) {
            p.quantity   = parseInt(p._counted, 10) || 0;
            p._systemQty = p.quantity;
            p._counted   = null;

            const dbRecord = { ...p };
            delete dbRecord._systemQty;
            delete dbRecord._counted;

            try { await DB.updateItem('products', dbRecord); } catch (_) { /* non-fatal */ }
        }

        if (saveProgress.value.failed > 0) {
            emitter.emit('add_flash', {
                type:    'warning',
                message: `${succeeded.length} updated, ${saveProgress.value.failed} failed. Retry the failed items.`,
            });
        } else {
            emitter.emit('add_flash', {
                type:    'success',
                message: t('pos.products.inception.apply_success', { count: succeeded.length }),
            });
        }

        confirmApply.value = false;
        saving.value = false;
    }
</script>

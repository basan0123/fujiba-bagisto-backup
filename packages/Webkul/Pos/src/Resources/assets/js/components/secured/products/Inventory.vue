<template>
    <div class="min-h-screen bg-[#F2F2F7]">

        <!-- Segmented control tab switcher -->
        <div class="sticky top-0 z-10 bg-[#F2F2F7] px-4 pt-4 pb-2">
            <div class="flex rounded-xl bg-[#E5E5EA] p-1">
                <button
                    type="button"
                    class="flex-1 rounded-lg py-1.5 text-[13px] font-semibold transition-colors"
                    :class="activeTab === 'stock'
                        ? 'bg-white text-[#1C1C1E] shadow-sm'
                        : 'text-[#8E8E93]'"
                    @click="activeTab = 'stock'"
                >
                    {{ t('pos.products.inventory.tab_stock') }}
                </button>

                <button
                    type="button"
                    class="flex-1 rounded-lg py-1.5 text-[13px] font-semibold transition-colors"
                    :class="activeTab === 'stocktake'
                        ? 'bg-white text-[#1C1C1E] shadow-sm'
                        : 'text-[#8E8E93]'"
                    @click="activeTab = 'stocktake'"
                >
                    {{ t('pos.products.inventory.tab_stocktake') }}
                </button>
            </div>
        </div>

        <!-- ========== TAB 1: 在庫 (Stock) ========== -->
        <div v-if="activeTab === 'stock'" class="px-4 pb-6">

            <!-- Search bar -->
            <div class="mt-3 flex flex-col gap-2">
                <input
                    v-model="filterQuery"
                    type="search"
                    class="w-full rounded-xl border border-[#E5E5EA] bg-[#F2F2F7] px-4 py-3 text-[15px] text-[#1C1C1E] placeholder-[#8E8E93] outline-none focus:border-[#00B8D9] focus:ring-1 focus:ring-[#00B8D9]"
                    :placeholder="t('pos.products.inventory.search_placeholder')"
                    @keyup.enter="submitSearch"
                />

                <button
                    type="button"
                    class="w-full rounded-xl border border-[#E5E5EA] bg-[#F2F2F7] py-3 text-[15px] font-medium text-[#1C1C1E] active:opacity-70"
                    @click="submitSearch"
                >
                    {{ t('pos.products.inventory.search_btn') }}
                </button>
            </div>

            <!-- Stock value summary -->
            <div class="mt-3 flex justify-end">
                <span class="text-[13px] text-[#8E8E93]">
                    {{ t('pos.products.inventory.stock_value_label') }}
                    <span class="font-semibold text-[#1C1C1E]">
                        ¥{{ stockValue.toLocaleString('ja-JP') }}
                    </span>
                </span>
            </div>

            <!-- Empty state -->
            <div
                v-if="!filteredProducts.length"
                class="mt-8 flex flex-col items-center justify-center py-12 text-[#8E8E93]"
            >
                <span class="icon-product text-5xl opacity-40"></span>
                <p class="mt-3 text-[15px]">
                    {{ t('pos.products.inventory.no_products') }}
                </p>
            </div>

            <!-- Product cards: big-image grid (Air Regi style) -->
            <div
                v-else
                class="mt-3 grid gap-3"
                style="grid-template-columns: repeat(2, minmax(0, 1fr));"
            >
                <div
                    v-for="product in filteredProducts"
                    :key="product.id"
                    class="flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm"
                    :class="product._dirty ? 'ring-2 ring-[#00B8D9]' : ''"
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

                        <!-- Managed toggle, top-right -->
                        <button
                            type="button"
                            class="absolute top-2 right-2 inline-flex h-7 w-12 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                            :class="product._managed ? 'bg-[#00B8D9]' : 'bg-black/35'"
                            role="switch"
                            :aria-checked="product._managed"
                            @click="toggleManaged(product)"
                            title="管理対象"
                        >
                            <span
                                class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow transition duration-200 ease-in-out"
                                :class="product._managed ? 'translate-x-5' : 'translate-x-0'"
                            ></span>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="flex flex-col gap-1 p-3">
                        <p class="line-clamp-2 text-[15px] font-semibold leading-tight text-[#1C1C1E]" style="min-height:36px;">
                            {{ product.name }}
                        </p>
                        <p
                            v-if="product.sku"
                            class="truncate text-[11px] text-[#8E8E93]"
                        >{{ product.sku }}</p>

                        <!-- Qty stepper -->
                        <div class="mt-2 flex items-center gap-1">
                            <button
                                type="button"
                                class="h-10 w-10 flex-shrink-0 rounded-lg bg-red-50 text-[22px] font-bold text-red-500 active:opacity-70"
                                @click="adjustQty(product, -1)"
                            >−</button>
                            <input
                                v-model.number="product._qtyDraft"
                                type="number"
                                inputmode="numeric"
                                min="0"
                                class="h-10 w-full min-w-0 rounded-lg border border-[#E5E5EA] bg-white px-2 text-center text-[20px] font-bold text-[#1C1C1E] outline-none focus:border-[#00B8D9] focus:ring-1 focus:ring-[#00B8D9]"
                                @input="onQtyInput(product)"
                                @focus="$event.target.select()"
                            />
                            <button
                                type="button"
                                class="h-10 w-10 flex-shrink-0 rounded-lg bg-green-50 text-[22px] font-bold text-green-600 active:opacity-70"
                                @click="adjustQty(product, 1)"
                            >＋</button>
                        </div>

                        <!-- Save button -->
                        <button
                            v-if="product._dirty"
                            type="button"
                            class="mt-2 h-9 w-full rounded-lg bg-[#00B8D9] text-[14px] font-semibold text-white active:opacity-70 disabled:opacity-50"
                            :disabled="product._saving"
                            @click="saveProduct(product)"
                        >
                            <span
                                v-if="product._saving"
                                class="icon-spinner mr-1 animate-spin text-sm"
                            ></span>
                            保存する
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== TAB 2: 棚卸 (Stocktake) ========== -->
        <div v-if="activeTab === 'stocktake'" class="px-4 pb-6">

            <!-- New stocktake button -->
            <div class="mt-3 flex justify-end">
                <button
                    type="button"
                    class="rounded-xl bg-[#00B8D9] px-5 py-2.5 text-[15px] font-semibold text-white active:opacity-70"
                    @click="showNewStocktakeDialog = true"
                >
                    {{ t('pos.products.inventory.new_stocktake_btn') }}
                </button>
            </div>

            <!-- Filter card -->
            <div class="mt-3 rounded-2xl bg-white px-4 py-4">
                <p class="mb-3 text-[13px] font-medium text-[#8E8E93]">
                    {{ t('pos.products.inventory.filter_label') }}
                </p>

                <!-- Date range row -->
                <div class="flex items-center gap-2">
                    <input
                        v-model="stocktakeFilter.dateFrom"
                        type="date"
                        class="flex-1 rounded-lg border border-[#E5E5EA] bg-[#F2F2F7] px-3 py-2 text-[14px] text-[#1C1C1E] outline-none focus:border-[#00B8D9] focus:ring-1 focus:ring-[#00B8D9]"
                    />

                    <span class="text-[14px] text-[#8E8E93]">～</span>

                    <input
                        v-model="stocktakeFilter.dateTo"
                        type="date"
                        class="flex-1 rounded-lg border border-[#E5E5EA] bg-[#F2F2F7] px-3 py-2 text-[14px] text-[#1C1C1E] outline-none focus:border-[#00B8D9] focus:ring-1 focus:ring-[#00B8D9]"
                    />
                </div>

                <button
                    type="button"
                    class="mt-3 w-full rounded-xl border border-[#E5E5EA] bg-[#F2F2F7] py-2.5 text-[14px] font-medium text-[#1C1C1E] active:opacity-70"
                    @click="applyStocktakeFilter"
                >
                    {{ t('pos.products.inventory.filter_apply_btn') }}
                </button>
            </div>

            <!-- Session list empty state -->
            <div
                v-if="!filteredSessions.length"
                class="mt-10 flex flex-col items-center justify-center py-12 text-[#8E8E93]"
            >
                <span class="icon-list text-5xl opacity-40"></span>
                <p class="mt-3 text-[15px]">
                    {{ t('pos.products.inventory.no_sessions') }}
                </p>
            </div>

            <!-- Session rows -->
            <div
                v-else
                class="mt-3 rounded-2xl bg-white"
            >
                <div
                    v-for="(session, index) in filteredSessions"
                    :key="session.id"
                    class="flex cursor-pointer items-center gap-3 px-4 py-4 active:bg-[#F2F2F7]"
                    :class="index < filteredSessions.length - 1 ? 'border-b border-[#E5E5EA]' : ''"
                    @click="openSession(session)"
                >
                    <!-- Date -->
                    <span class="w-24 text-[14px] text-[#1C1C1E]">
                        {{ formatDate(session.start_date ?? session.date) }}
                    </span>

                    <!-- Status badge -->
                    <span
                        class="rounded-full px-2.5 py-0.5 text-[12px] font-semibold"
                        :class="session.status === 'completed'
                            ? 'bg-green-100 text-green-700'
                            : 'bg-yellow-100 text-yellow-700'"
                    >
                        {{ session.status === 'completed'
                            ? t('pos.products.inventory.status_completed')
                            : t('pos.products.inventory.status_in_progress') }}
                    </span>

                    <!-- Item count -->
                    <span class="flex-1 text-right text-[14px] text-[#00B8D9]">
                        {{ session.item_count ?? 0 }} {{ t('pos.products.inception.total_products') }}
                    </span>

                    <!-- Chevron -->
                    <span class="icon-arrow-right text-lg text-[#8E8E93]"></span>
                </div>
            </div>

        </div>

    </div>

    <!-- ========== New Stocktake Dialog ========== -->
    <div
        v-if="showNewStocktakeDialog"
        class="fixed inset-0 z-50 flex items-end justify-center sm:items-center"
    >
        <!-- Backdrop -->
        <div
            class="absolute inset-0 bg-black/40"
            @click="showNewStocktakeDialog = false"
        ></div>

        <!-- Card -->
        <div class="relative z-10 w-full max-w-lg rounded-t-2xl bg-white px-5 py-6 sm:rounded-2xl">

            <!-- Dialog title -->
            <h2 class="mb-4 text-center text-[17px] font-semibold text-[#1C1C1E]">
                {{ t('pos.products.inventory.new_stocktake_title') }}
            </h2>

            <!-- Date field -->
            <div class="mb-4">
                <label
                    for="stocktake-date"
                    class="mb-1 block text-[13px] font-medium text-[#8E8E93]"
                >
                    {{ t('pos.products.inventory.stocktake_date_label') }}
                </label>

                <input
                    id="stocktake-date"
                    v-model="newStocktakeForm.date"
                    type="date"
                    class="w-full rounded-xl border border-[#E5E5EA] bg-[#F2F2F7] px-4 py-3 text-[15px] text-[#1C1C1E] outline-none focus:border-[#00B8D9] focus:ring-1 focus:ring-[#00B8D9]"
                />
            </div>

            <!-- Notes field -->
            <div class="mb-6">
                <label
                    for="stocktake-notes"
                    class="mb-1 block text-[13px] font-medium text-[#8E8E93]"
                >
                    {{ t('pos.products.inventory.stocktake_notes_label') }}
                </label>

                <textarea
                    id="stocktake-notes"
                    v-model="newStocktakeForm.notes"
                    rows="3"
                    class="w-full rounded-xl border border-[#E5E5EA] bg-[#F2F2F7] px-4 py-3 text-[15px] text-[#1C1C1E] placeholder-[#8E8E93] outline-none focus:border-[#00B8D9] focus:ring-1 focus:ring-[#00B8D9]"
                    :placeholder="t('pos.products.inventory.stocktake_notes_placeholder')"
                ></textarea>
            </div>

            <!-- Action buttons -->
            <div class="flex gap-3">
                <!-- Cancel -->
                <button
                    type="button"
                    class="flex-1 rounded-xl border border-[#E5E5EA] bg-[#F2F2F7] py-3 text-[15px] font-medium text-[#1C1C1E] active:opacity-70"
                    @click="showNewStocktakeDialog = false"
                >
                    {{ t('pos.products.inventory.cancel_btn') }}
                </button>

                <!-- Start -->
                <button
                    type="button"
                    class="flex-1 rounded-xl bg-[#00B8D9] py-3 text-[15px] font-semibold text-white active:opacity-70"
                    @click="submitNewStocktake"
                >
                    {{ t('pos.products.inventory.start_btn') }}
                </button>
            </div>

        </div>
    </div>
</template>

<script setup>
    import { ref, computed, inject, onMounted } from 'vue';
    import { useRouter } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import { useMutation } from '@vue/apollo-composable';
    import { useIndexedDB } from '@src/composable/indexed-db';
    import { BULK_UPDATE_PRODUCTS } from '@src/graphql/products';

    /**
     * General setup
     */
    const { t } = useI18n();
    const router  = useRouter();
    const DB = useIndexedDB();
    const emitter = inject('emitter');
    const { mutate: bulkUpdate } = useMutation(BULK_UPDATE_PRODUCTS);

    /**
     * Active tab state
     */
    const activeTab = ref('stock');

    // =====================================================
    // TAB 1 – Stock
    // =====================================================

    /**
     * All products loaded from IndexedDB, augmented with UI state fields
     */
    const products = ref([]);

    /**
     * Search query (applied on search submit)
     */
    const filterQuery = ref('');
    const activeQuery = ref('');

    /**
     * Computed list: filter by activeQuery against product name
     */
    const filteredProducts = computed(() => {
        const q = activeQuery.value.trim().toLowerCase();

        if (!q) {
            return products.value;
        }

        return products.value.filter((p) =>
            (p.name ?? '').toLowerCase().includes(q)
        );
    });

    /**
     * Computed stock value – sum of (price * qty) for managed products
     * Falls back to 0 when price is not available in IndexedDB
     */
    const stockValue = computed(() => {
        return products.value.reduce((total, p) => {
            if (!p._managed) {
                return total;
            }

            const price = parseFloat(p.price ?? p.formattedPrice ?? 0) || 0;
            const qty = parseInt(p.quantity ?? 0, 10) || 0;

            return total + price * qty;
        }, 0);
    });

    /**
     * Load all products from IndexedDB + stocktake sessions from API on mount
     */
    onMounted(async () => {
        const raw = await DB.getAllItems('products');

        products.value = raw.map((p) => ({
            ...p,
            _managed: p.manage_stock ?? true,
            _qtyDraft: p.quantity ?? 0,
            _dirty: false,
            _saving: false,
        }));

        await loadSessions();
    });

    /**
     * Submit search – copy filterQuery into activeQuery to trigger filter
     */
    const submitSearch = () => {
        activeQuery.value = filterQuery.value;
    };

    /**
     * Toggle manage_stock flag for a product and mark dirty
     */
    const toggleManaged = (product) => {
        product._managed = !product._managed;
        product._dirty = true;
    };

    /**
     * Stepper: adjust quantity by ±delta (clamped at 0)
     */
    const adjustQty = (product, delta) => {
        const cur  = parseInt(product._qtyDraft ?? 0, 10) || 0;
        const next = Math.max(0, cur + delta);
        product._qtyDraft = next;
        product.quantity  = next;
        product._dirty    = true;
    };

    /**
     * Bind: typed input
     */
    const onQtyInput = (product) => {
        const n = parseInt(product._qtyDraft, 10);
        if (!isNaN(n) && n >= 0) {
            product.quantity = n;
            product._dirty   = true;
        }
    };

    /**
     * Save a single product card: bulkUpdate (qty → backend) + IndexedDB
     */
    const saveProduct = async (product) => {
        product._saving = true;

        try {
            const qty = parseInt(product._qtyDraft ?? product.quantity ?? 0, 10) || 0;

            const response = await bulkUpdate({
                input: {
                    products: [{
                        id:       product.id,
                        name:     product.name,
                        price:    parseFloat(product.price) || 0,
                        quantity: qty,
                        weight:   parseFloat(product.weight ?? 0) || 0,
                    }],
                },
            });

            const result = response?.data?.bulkUpdateOutletProducts;

            if (! result?.success) {
                throw new Error(result?.message ?? '');
            }

            product.quantity = qty;

            await DB.updateItem('products', {
                ...product,
                manage_stock: product._managed,
                quantity:     qty,
                _managed:     undefined,
                _qtyDraft:    undefined,
                _dirty:       undefined,
                _saving:      undefined,
            });

            product._dirty = false;

            emitter.emit('add_flash', {
                type:    'success',
                message: t('pos.products.inventory.save_success'),
            });
        } catch (err) {
            emitter.emit('add_flash', {
                type:    'error',
                message: t('pos.products.inventory.save_error'),
            });
        } finally {
            product._saving = false;
        }
    };

    // =====================================================
    // TAB 2 – Stocktake
    // =====================================================

    /**
     * Stocktake sessions loaded from API
     */
    const sessions = ref([]);

    const stocktakeFilter = ref({ dateFrom: '', dateTo: '' });
    const activeSessionFilter = ref({ dateFrom: '', dateTo: '' });

    const filteredSessions = computed(() => {
        const { dateFrom, dateTo } = activeSessionFilter.value;

        return sessions.value.filter((s) => {
            const d = s.start_date ?? s.date ?? '';
            if (dateFrom && d < dateFrom) return false;
            if (dateTo   && d > dateTo)   return false;
            return true;
        });
    });

    const applyStocktakeFilter = () => {
        activeSessionFilter.value = { ...stocktakeFilter.value };
    };

    const loadSessions = async () => {
        try {
            const token = localStorage.getItem('accessToken');
            const res = await fetch('/api/v1/pos/stocktake-sessions?limit=100', {
                headers: {
                    'Accept'        : 'application/json',
                    'Authorization' : `Bearer ${token}`,
                },
            });
            if (!res.ok) return;
            const json = await res.json();
            sessions.value = json.data ?? [];
        } catch (_) { /* non-fatal */ }
    };

    /**
     * Open a session — for now we just navigate to the inception screen
     * (full-detail view can be wired later)
     */
    const openSession = (_session) => {
        router.push('/products/inception');
    };

    /**
     * Format ISO date string as Japanese locale date
     */
    const formatDate = (isoDate) => {
        if (!isoDate) return '';
        const d = new Date(isoDate);
        return d.toLocaleDateString('ja-JP', {
            year:  'numeric',
            month: '2-digit',
            day:   '2-digit',
        });
    };

    /**
     * New stocktake dialog state
     */
    const showNewStocktakeDialog = ref(false);

    /**
     * Today's date in YYYY-MM-DD format
     */
    const todayIso = () => {
        const d = new Date();
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');

        return `${yyyy}-${mm}-${dd}`;
    };

    /**
     * New stocktake form state
     */
    const newStocktakeForm = ref({
        date: todayIso(),
        notes: '',
    });

    /**
     * Submit new stocktake – navigate to inception screen
     * (the actual session is created by inception's "Apply All")
     */
    const submitNewStocktake = () => {
        showNewStocktakeDialog.value = false;
        newStocktakeForm.value = { date: todayIso(), notes: '' };
        router.push('/products/inception');
    };
</script>

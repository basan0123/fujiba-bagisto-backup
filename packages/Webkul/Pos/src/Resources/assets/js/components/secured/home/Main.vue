<template>
    <!-- ═══════════════════════════════════════════════════════
         MOBILE (phones only) — Air Regi 4×4 horizontal paged tile grid
         md:hidden so it disappears on every iPad (≥768px, incl.
         iPad mini & all iPads in portrait). Only ≤767px phones see it.
         ═══════════════════════════════════════════════════════ -->
    <div
        class="md:hidden flex flex-col"
        style="height: calc(100vh - 44px - 49px - env(safe-area-inset-bottom, 0px));"
    >
        <!-- Category filter bar -->
        <div class="flex-shrink-0">
            <category
                v-model="selectedCategoryId"
                :categories="categories"
                @category-selected="handleCategorySelection"
            />
        </div>

        <!-- Mobile search bar -->
        <div class="flex-shrink-0 px-2 py-1 bg-white border-b border-[#E5E5EA]">
            <div class="relative flex items-center">
                <i class="icon-search absolute left-2.5 text-sm text-[#8E8E93] pointer-events-none"></i>
                <input
                    type="text"
                    class="h-8 w-full rounded-lg bg-[#F2F2F7] pl-7 pr-3 text-[13px] text-[#1C1C1E] outline-none placeholder-[#8E8E93]"
                    :placeholder="$t('pos.layout.header.search_products')"
                    @input="searchProduct($event.target.value)"
                />
            </div>
        </div>

        <!-- Paged tile grid -->
        <div class="relative flex-1 overflow-hidden">
            <div
                ref="scrollEl"
                class="flex h-full overflow-x-scroll"
                style="scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch; scrollbar-width: none; touch-action: pan-x;"
                @scroll="onGridScroll"
            >
                <div
                    v-for="(page, pageIdx) in pagedProducts"
                    :key="pageIdx"
                    class="grid flex-shrink-0 w-full"
                    style="grid-template-columns: repeat(4, 1fr); grid-template-rows: repeat(4, 1fr); scroll-snap-align: start; touch-action: pan-x;"
                >
                    <template
                        v-for="slotIdx in 16"
                        :key="slotIdx - 1"
                    >
                        <div
                            v-if="page[slotIdx - 1]"
                            class="relative flex cursor-pointer flex-col overflow-hidden border border-[#E5E5EA] bg-white transition-transform active:scale-95"
                            @click="addToCart(page[slotIdx - 1])"
                        >
                            <!-- Image area (top ~60% of tile) -->
                            <div class="relative w-full" style="flex: 0 0 60%;">
                                <img
                                    v-if="page[slotIdx - 1].images?.length"
                                    :src="page[slotIdx - 1].images[0].url"
                                    class="absolute inset-0 h-full w-full object-cover"
                                    :alt="page[slotIdx - 1].name"
                                />

                                <img
                                    v-else
                                    src="@images/product-placeholder.webp"
                                    class="absolute inset-0 h-full w-full object-cover opacity-30"
                                    alt=""
                                />

                                <!-- Cart qty badge -->
                                <div
                                    v-if="cartQty[page[slotIdx - 1].id]"
                                    class="absolute right-0.5 top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-[#00B8D9] text-[10px] font-bold text-white"
                                >
                                    {{ cartQty[page[slotIdx - 1].id] }}
                                </div>
                            </div>

                            <!-- Info area (name + stock below image) -->
                            <div class="flex flex-1 flex-col justify-between gap-0.5 px-1 py-1">
                                <p class="truncate-text-2 text-[11px] leading-tight text-[#1C1C1E]">
                                    {{ page[slotIdx - 1].name }}
                                </p>
                                <p class="truncate text-[10px] leading-tight text-[#8E8E93]">
                                    {{ $t('pos.home.products.view.qty', { qty: page[slotIdx - 1].quantity ?? 0 }) }}
                                </p>
                            </div>
                        </div>

                        <!-- Empty slot -->
                        <div
                            v-else
                            class="border border-dashed border-[#D1D5DB] bg-white"
                        ></div>
                    </template>
                </div>
            </div>

            <!-- Page dots -->
            <div
                v-if="totalPages > 1"
                class="pointer-events-none absolute bottom-12 left-0 right-0 flex justify-center gap-1.5"
            >
                <span
                    v-for="p in totalPages"
                    :key="p"
                    class="h-1.5 rounded-full transition-all duration-200"
                    :class="p - 1 === currentPage ? 'w-4 bg-[#00B8D9]' : 'w-1.5 bg-[#C7C7CC]'"
                ></span>
            </div>

            <!-- Horizontal scroll indicator (thick, touch-friendly) -->
            <div
                v-if="totalPages > 1"
                class="absolute bottom-2 left-4 right-4 h-5 overflow-hidden rounded-full bg-[#E5E5EA]"
            >
                <div
                    class="h-full rounded-full bg-[#00B8D9] transition-all duration-150"
                    :style="{ width: scrollBarWidth + '%', marginLeft: scrollBarLeft + '%' }"
                ></div>
            </div>
        </div>

        <!-- Cart bottom bar -->
        <div
            class="flex flex-shrink-0 items-center gap-2 border-t border-[#E5E5EA] bg-white px-3"
            style="padding-top:8px; padding-bottom:8px;"
        >
            <button
                type="button"
                class="rounded-xl border border-[#C7C7CC] px-4 py-2.5 text-[14px] font-medium text-[#1C1C1E] active:opacity-70"
                @click="emitter.emit('hold_order')"
            >
                {{ $t('pos.home.cart_bar.hold') }}
            </button>

            <button
                type="button"
                class="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-[#00B8D9] py-2.5 text-[15px] font-semibold text-white active:opacity-70"
                :class="totalCartCount === 0 ? 'opacity-60' : ''"
                @click="openCart"
            >
                <template v-if="totalCartCount > 0">
                    <span>{{ totalCartCount }}{{ $t('pos.home.cart_bar.items') }}</span>
                    <span>·</span>
                </template>
                <span>{{ $t('pos.home.cart_bar.checkout') }}</span>
            </button>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         PC / iPad — Original Bagisto product grid + cart
         hidden on phones, md:flex on every iPad (≥768px). The cart
         is a drawer below 1024px and a fixed sidebar at ≥1024px
         (see isMobileOrTab ≤1024), so narrow iPad portrait gets the
         full-width grid + slide-up cart.
         ═══════════════════════════════════════════════════════ -->
    <div class="hidden md:flex h-full gap-4 overflow-hidden">
        <div class="padding-right flex-1 overflow-hidden py-4">
            <div class="flex flex-col gap-4">
                <category
                    v-model="selectedCategoryId"
                    :categories="categories"
                    @category-selected="handleCategorySelection"
                />

                <CustomLayout
                    :all-products="allProducts"
                    :active-category-id="selectedCategoryId"
                    :active-category-name="activeCategoryName"
                    :search-term="desktopSearchTerm"
                    @add-to-cart="addToCart"
                />
            </div>
        </div>

        <Cart v-if="! isMobileOrTab" />
    </div>

    <!-- ═══════ Shared teleported modals ═══════ -->
    <Teleport to="body">
        <!-- Mobile cart drawer (opened via openCart()) -->
        <Cart
            v-if="isMobileOrTab"
            :layout="'drawer'"
        />

        <Modal ref="productModal">
            <template v-slot:header="{ toggle }">
                <div class="flex justify-between gap-2.5">
                    <span class="text-gray-900 dark:text-gray-100">{{ modalProduct?.name }}</span>

                    <span
                        class="icon-cross cursor-pointer text-2xl text-gray-900 dark:text-gray-100"
                        @click="toggle"
                    ></span>
                </div>
            </template>

            <template v-slot:content>
                <View
                    :product="modalProduct"
                    :productModal="productModal"
                />
            </template>
        </Modal>

        <Modal ref="receiptPrintModal">
            <template v-slot:header="{ toggle }">
                <div class="flex justify-between gap-2.5">
                    <div class="flex items-center gap-2.5">
                        <span class="icon-warning cursor-pointer text-2xl text-yellow-500"></span>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ $t('pos.home.print_confirmation_title') }}
                        </p>
                    </div>

                    <span
                        class="icon-cross cursor-pointer text-2xl text-gray-900 dark:text-gray-100"
                        @click="toggle"
                    ></span>
                </div>
            </template>

            <template v-slot:content>
                <p class="pb-2.5 text-base text-gray-900 dark:text-gray-100">
                    {{ $t('pos.home.print_confirm_message') }}
                </p>

                <Print :outletOrder="outletOrder" />
            </template>
        </Modal>
    </Teleport>
</template>

<script setup>
    import { ref, computed, onMounted, onBeforeUnmount, inject } from 'vue';
    import { useApolloClient } from '@vue/apollo-composable';
    import { useIndexedDB } from '@src/composable/indexed-db';
    import { useWindowWidth } from '@src/composable/window';
    import { PRODUCTS } from '@src/graphql/home';
    import Category     from '@components/secured/home/Category.vue';
    import CustomLayout from '@components/secured/home/CustomLayout.vue';
    import View         from '@components/secured/home/products/View.vue';
    import Print        from '@components/secured/common/Print.vue';

    const TILES_PER_PAGE = 16; // 4 cols × 4 rows

    const emitter        = inject('emitter');
    const isOnline       = inject('isOnline');
    const DB             = useIndexedDB();
    const apolloClient   = useApolloClient();
    const { isMobileOrTab } = useWindowWidth();

    // ── Data ──────────────────────────────────────────────
    const products           = ref([]); // mobile-paged subset (filtered by category + search)
    const allProducts        = ref([]); // full unfiltered list (desktop CustomLayout filters internally)
    const categories         = ref([]);
    /**
     * Default landing tab is the No-Barcode quick-pick view — the cashier
     * working a counter without a scanner needs hand-tappable items first.
     * Users can switch to "All" or any real category from the chip row.
     */
    const selectedCategoryId = ref('no-barcode');
    const desktopSearchTerm  = ref('');

    /**
     * Flatten the category tree and find the active category's display name.
     */
    const flattenCategories = (nodes) => {
        const out = [];
        const walk = (list) => {
            for (const node of list || []) {
                out.push(node);
                if (node.children?.length) walk(node.children);
            }
        };
        walk(nodes);
        return out;
    };

    const activeCategoryName = computed(() => {
        if (! selectedCategoryId.value) return 'すべて';
        if (selectedCategoryId.value === 'no-barcode') return 'バーコードなし';
        const flat = flattenCategories(categories.value);
        return flat.find(c => String(c.id) === String(selectedCategoryId.value))?.name ?? 'カテゴリ';
    });

    /**
     * A product counts as "no-barcode" when it has no real, scannable GTIN —
     * pos_product_barcode.gtin is empty AND scan_type != 'gtin'.
     *
     * Why not check `barcode` / `scan_value`: AutoRegisterProductBarcode fills
     * those with the SKU on every product save (so the resolver can find any
     * product). That makes them non-empty for *every* product, not just truly
     * scannable ones. Only `gtin` (and scan_type='gtin') reflect a real
     * EAN-13/UPC-A entered by admin via /admin/catalog/barcode-assign.
     *
     * Cashier-facing meaning: items in this tab need to be *tapped*, since the
     * scanner has nothing to read.
     */
    const hasNoBarcode = (product) => {
        const gtin = (product?.gtin ?? '').toString().trim();
        const scanType = (product?.scanType ?? product?.scan_type ?? '').toString().trim();
        return ! gtin && scanType !== 'gtin';
    };

    /**
     * One-shot product sync. Fires when the cashier hits /home but the local
     * IndexedDB has no products — e.g. first login on a new device, or after
     * the browser cleared site data. Streams pages into IDB so the grid fills
     * progressively without forcing the user through Settings → Sync.
     *
     * isSyncing prevents two parallel runs (e.g. mounted twice in fast-refresh).
     */
    const isSyncing = ref(false);

    const autoSyncProducts = async () => {
        if (isSyncing.value) return;
        if (! isOnline?.value) return;

        isSyncing.value = true;

        try {
            let page = 1;
            let lastPage = 1;
            const batchSize = 50;

            do {
                const { data } = await apolloClient.client.query({
                    query: PRODUCTS,
                    variables: { page, first: batchSize },
                    fetchPolicy: 'no-cache',
                });

                const payload = data?.getOutletProducts;
                if (! payload) break;

                const items = payload.data || [];
                lastPage    = payload.paginatorInfo?.lastPage || 1;

                for (const item of items) {
                    try {
                        await DB.updateItem('products', JSON.parse(JSON.stringify(item)));
                    } catch (e) {
                        // Single-row failure shouldn't block the rest.
                    }
                }

                // Refresh in-memory copy after each batch so the grid populates live.
                allProducts.value = await DB.getAllItems('products');
                products.value    = allProducts.value;

                page++;
            } while (page <= lastPage);
        } catch (e) {
            // Silent: cashier still has whatever was in IDB; Settings → Sync remains.
            console.warn('Auto product sync failed:', e);
        } finally {
            isSyncing.value = false;
        }
    };

    onMounted(async () => {
        categories.value  = await DB.getItem('categories', 1).then(d => d?.categories) || [];
        allProducts.value = await DB.getAllItems('products');

        // Apply the default tab's filter so the mobile paged grid lands on
        // No-Barcode items immediately, matching the active chip.
        if (selectedCategoryId.value === 'no-barcode') {
            products.value = allProducts.value.filter(hasNoBarcode);
        } else {
            products.value = allProducts.value;
        }

        if (allProducts.value.length === 0) {
            autoSyncProducts();
        }
    });

    const handleCategorySelection = async (categoryId) => {
        selectedCategoryId.value = categoryId;

        const all = await DB.getAllItems('products');
        allProducts.value = all;

        if (categoryId === 'no-barcode') {
            products.value = all.filter(hasNoBarcode);
        } else if (categoryId) {
            products.value = all.filter(p => p.categories.some(c => c.id == categoryId));
        } else {
            products.value = all;
        }

        if (scrollEl.value) scrollEl.value.scrollLeft = 0;
    };

    const searchProduct = async (term) => {
        desktopSearchTerm.value = term ?? '';

        const all = await DB.getAllItems('products');
        allProducts.value = all;

        products.value = term
            ? all.filter(p =>
                p.name.toLowerCase().includes(term.toLowerCase()) ||
                p.sku.toLowerCase().includes(term.toLowerCase())
            )
            : all;

        if (scrollEl.value) scrollEl.value.scrollLeft = 0;
    };

    // ── Mobile paged grid ─────────────────────────────────
    const pagedProducts = computed(() => {
        const pages = [];

        for (let i = 0; i < products.value.length; i += TILES_PER_PAGE) {
            pages.push(products.value.slice(i, i + TILES_PER_PAGE));
        }

        return pages.length ? pages : [[]];
    });

    const totalPages = computed(() => pagedProducts.value.length);

    const scrollEl    = ref(null);
    const currentPage = ref(0);

    const scrollBarWidth = computed(() => totalPages.value > 0 ? 100 / totalPages.value : 100);
    const scrollBarLeft  = computed(() => currentPage.value * scrollBarWidth.value);

    const onGridScroll = () => {
        if (! scrollEl.value) return;

        const { scrollLeft, clientWidth } = scrollEl.value;

        currentPage.value = Math.round(scrollLeft / clientWidth);
    };

    // ── Cart tracking (mobile badge counts) ───────────────
    const cartQty        = ref({});
    const totalCartCount = computed(() => Object.values(cartQty.value).reduce((s, q) => s + q, 0));

    const trackCartAdd = ({ productId, quantity }) => {
        cartQty.value = { ...cartQty.value, [productId]: (cartQty.value[productId] ?? 0) + (quantity ?? 1) };
    };

    const trackCartClear = () => { cartQty.value = {}; };

    // ── Add to cart ───────────────────────────────────────
    const productModal = ref(null);
    const modalProduct = ref(null);

    const addToCart = (product) => {
        if (['simple', 'virtual'].includes(product.type)) {
            if (product.customizableOptions?.length) {
                modalProduct.value = product;
                productModal.value.toggle();
            } else {
                emitter.emit('add_to_cart', { productId: product.id, quantity: 1 });
            }
        } else {
            modalProduct.value = product;
            productModal.value.toggle();
        }
    };

    const openCart = () => { emitter.emit('toggle_cart'); };

    // ── Receipt print ─────────────────────────────────────
    const outletOrder       = ref(null);
    const receiptPrintModal = ref(null);

    const checkForReceiptPrint = () => {
        DB.getAgent().then(agent => {
            const data = localStorage.getItem('outlet_order');

            if (agent && Boolean(agent.outlet?.receipt?.showPrintConfirmation) && data) {
                outletOrder.value = JSON.parse(data);
                receiptPrintModal.value.toggle();
            }

            localStorage.removeItem('outlet_order');
        });
    };

    // ── Lifecycle ─────────────────────────────────────────
    onMounted(() => {
        emitter.on('search_product', searchProduct);
        emitter.on('new_product', async () => {
            const all = await DB.getAllItems('products');
            allProducts.value = all;
            products.value = all;
        });
        emitter.on('add_to_cart', trackCartAdd);
        emitter.on('cart_cleared', trackCartClear);
        checkForReceiptPrint();
    });

    onBeforeUnmount(() => {
        emitter.off('search_product');
        emitter.off('new_product');
        emitter.off('add_to_cart', trackCartAdd);
        emitter.off('cart_cleared', trackCartClear);
    });
</script>

<style scoped>
div[style*="scrollbar-width: none"]::-webkit-scrollbar {
    display: none;
}
</style>

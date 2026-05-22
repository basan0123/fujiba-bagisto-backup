<template>
    <!--
        Air Regi 商品設定 — Mobile product bulk-edit list.
        Shown only on mobile (md:hidden in parent).
        PC uses the original Bagisto component.
    -->
    <div class="flex flex-col bg-[#F2F2F7]" style="height: calc(100vh - 44px);">

        <!-- ── Search bar ─────────────────────────────────────────── -->
        <div class="flex-shrink-0 bg-white px-3 py-2 border-b border-[#E5E5EA]">
            <input
                v-model="searchQuery"
                type="search"
                class="w-full rounded-xl border border-[#E5E5EA] bg-[#F2F2F7] px-4 py-2.5 text-[15px] text-[#1C1C1E] placeholder-[#8E8E93] outline-none focus:border-[#00B8D9] focus:ring-1 focus:ring-[#00B8D9]"
                placeholder="商品名・バーコードで検索"
            />
        </div>

        <!-- ── Sticky column header ───────────────────────────────── -->
        <div class="flex-shrink-0 flex items-center h-11 border-b border-[#E5E5EA] bg-[#F2F2F7] px-0 relative">
            <!-- Name column 60% -->
            <div class="flex items-center gap-1.5 px-3" style="width:60%;">
                <span class="text-[13px] font-medium text-[#1C1C1E]">商品名</span>
                <span class="rounded-full bg-[#FF3B30] px-1.5 py-0.5 text-[11px] font-semibold leading-none text-white">必須</span>
            </div>

            <!-- Configurable second column 24% -->
            <div class="flex items-center justify-end gap-1 px-2" style="width:24%;">
                <span class="text-[13px] font-medium text-[#1C1C1E]">{{ secondColLabel }}</span>
            </div>

            <!-- ... menu trigger 16% -->
            <div class="flex items-center justify-end px-2" style="width:16%;">
                <button
                    type="button"
                    class="flex h-8 w-8 items-center justify-center rounded-full hover:bg-[#E5E5EA] active:opacity-70"
                    @click.stop="columnMenuOpen = !columnMenuOpen"
                    :aria-label="$t('pos.products.list.column.menu_aria')"
                >
                    <span class="text-[20px] font-bold text-[#00B8D9] leading-none">⋯</span>
                </button>
            </div>

            <!-- Column menu dropdown -->
            <Teleport to="body">
                <div
                    v-if="columnMenuOpen"
                    class="fixed inset-0 z-[10070]"
                    @click="closeColumnMenu"
                >
                    <div
                        class="absolute right-3 top-12 w-44 rounded-lg border border-[#E5E5EA] bg-white shadow-lg"
                        @click.stop
                    >
                        <p class="px-3 py-2 text-[12px] text-[#8E8E93] border-b border-[#E5E5EA]">{{ $t('pos.products.list.column.menu_title') }}</p>
                        <button
                            type="button"
                            v-for="opt in columnOptions"
                            :key="opt.value"
                            class="flex w-full items-center justify-between px-3 py-2.5 text-left text-[14px] text-[#1C1C1E] hover:bg-[#F2F2F7]"
                            :class="secondColumnPref === opt.value ? 'font-semibold text-[#00B8D9]' : ''"
                            @click="selectColumn(opt.value)"
                        >
                            <span>{{ opt.label }}</span>
                            <span v-if="secondColumnPref === opt.value" class="text-[#00B8D9]">✓</span>
                        </button>
                    </div>
                </div>
            </Teleport>
        </div>

        <!-- ── Scrollable product list ────────────────────────────── -->
        <div
            ref="listEl"
            class="flex-1 overflow-y-auto bg-white"
            style="padding-bottom: 120px;"
        >
            <template v-if="loading">
                <div class="flex items-center justify-center py-16">
                    <span class="icon-spinner animate-spin text-3xl text-[#00B8D9]"></span>
                </div>
            </template>

            <template v-else>
                <!-- Empty state (no search match) -->
                <div
                    v-if="searchQuery.trim() && !filteredProducts.length"
                    class="flex flex-col items-center justify-center py-16 text-[#8E8E93]"
                >
                    <p class="text-[15px]">該当する商品が見つかりません</p>
                </div>

                <!-- Product rows -->
                <template v-for="product in filteredProducts" :key="product.id">
                <div
                    :data-idx="indexOf(product)"
                    data-row
                    class="relative flex items-center border-b border-[#E5E5EA] bg-white"
                    style="height:64px; min-height:64px;"
                    :style="product._shipping ? 'border-left: 3px solid #FF3B30;' : ''"
                >
                    <!-- ─ Name zone (60%) ─ -->
                    <div
                        class="flex h-full items-center gap-2 px-3 cursor-pointer overflow-hidden transition-colors"
                        style="width:60%;"
                        :class="isActive(indexOf(product), 'name') ? 'bg-[#E5F7FB]' : ''"
                        @click="activate(indexOf(product), 'name')"
                    >
                        <!-- Thumbnail -->
                        <div class="relative h-11 w-11 flex-shrink-0 overflow-hidden rounded border border-[#E5E5EA] bg-[#F2F2F7]">
                            <img
                                v-if="product.images && product.images.length"
                                :src="product.images[0].url"
                                class="absolute inset-0 h-full w-full object-cover"
                                :alt="product.name"
                                loading="lazy"
                            />
                            <div
                                v-else
                                class="absolute inset-0 flex items-center justify-center text-[16px] text-[#C7C7CC]"
                            >
                                <i class="icon-image"></i>
                            </div>
                        </div>

                        <!-- Name + category (SKU intentionally hidden for cleaner mobile view) -->
                        <div class="flex min-w-0 flex-1 flex-col justify-center overflow-hidden">
                            <span
                                class="flex-1 truncate text-[17px] border-b"
                                :class="[
                                    isActive(indexOf(product), 'name') ? 'text-[#00B8D9] border-[#00B8D9] font-medium' : 'text-[#1C1C1E] border-[#C7C7CC]',
                                    dirtyRows.has(product.id) ? 'font-semibold' : ''
                                ]"
                            >{{ product.name }}</span>
                            <span
                                v-if="product.categories && product.categories.length"
                                class="truncate text-[11px] text-[#8E8E93] leading-none mt-0.5"
                            >{{ product.categories[0].name }}</span>
                        </div>
                    </div>

                    <!-- ─ Configurable second cell (24%) ─ -->
                    <div
                        class="flex h-full items-center justify-end px-2 overflow-hidden transition-colors"
                        :class="[
                            isInlineEditable ? 'cursor-pointer' : '',
                            isActive(indexOf(product), secondColumn) ? 'bg-[#E5F7FB]' : ''
                        ]"
                        style="width:24%;"
                        @click="isInlineEditable && ! product._shipping && activate(indexOf(product), secondColumn)"
                    >
                        <template v-if="product._shipping && secondColumn === 'price'">
                            <span class="text-[13px] text-[#8E8E93]">{{ $t('pos.products.list.set_at_order') }}</span>
                        </template>

                        <!-- Status: two pill toggles (POS / WEB) -->
                        <template v-else-if="secondColumn === 'status'">
                            <div class="flex items-center gap-1.5">
                                <button
                                    type="button"
                                    class="flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold transition-colors active:opacity-70"
                                    :class="(product.posStatus ?? 1) ? 'bg-[#00B8D9] text-white' : 'bg-[#E5E5EA] text-[#8E8E93]'"
                                    :title="$t('pos.products.list.status.pos')"
                                    @click.stop="toggleStatus(product, 'posStatus')"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="(product.posStatus ?? 1) ? 'bg-white' : 'bg-[#8E8E93]'"></span>
                                    {{ $t('pos.products.list.status.pos_short') }}
                                </button>

                                <button
                                    type="button"
                                    class="flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold transition-colors active:opacity-70"
                                    :class="(product.status ?? 1) ? 'bg-[#34C759] text-white' : 'bg-[#E5E5EA] text-[#8E8E93]'"
                                    :title="$t('pos.products.list.status.web')"
                                    @click.stop="toggleStatus(product, 'status')"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="(product.status ?? 1) ? 'bg-white' : 'bg-[#8E8E93]'"></span>
                                    {{ $t('pos.products.list.status.web_short') }}
                                </button>
                            </div>
                        </template>

                        <!-- Category: read-only display, no inline edit -->
                        <template v-else-if="secondColumn === 'category'">
                            <span class="flex-1 truncate text-right text-[14px] text-[#1C1C1E]">
                                {{ secondColDisplay(product) }}
                            </span>
                        </template>

                        <!-- Numeric columns: inline-edit popup -->
                        <template v-else>
                            <span
                                class="flex-1 truncate text-right text-[17px] border-b"
                                :class="isActive(indexOf(product), secondColumn) ? 'text-[#00B8D9] border-[#00B8D9] font-medium' : 'text-[#1C1C1E] border-[#C7C7CC]'"
                            >{{ secondColDisplay(product) }}</span>
                            <i
                                class="ml-1 flex-shrink-0 text-[13px]"
                                :class="isActive(indexOf(product), secondColumn) ? 'icon-edit text-[#00B8D9]' : 'icon-edit text-[#C7C7CC]'"
                            ></i>
                        </template>
                    </div>

                    <!-- ─ Delete zone (8%) ─ -->
                    <button
                        type="button"
                        class="flex h-full items-center justify-center active:opacity-50"
                        style="width:8%;"
                        @click.stop="confirmDelete(product, indexOf(product))"
                    >
                        <i class="icon-delete text-[20px] text-[#00B8D9]"></i>
                    </button>

                    <!-- ─ Detail zone (8%) ─ -->
                    <button
                        type="button"
                        class="flex h-full items-center justify-center active:opacity-50"
                        style="width:8%;"
                        @click.stop="router.push('/products/edit/' + product.id)"
                    >
                        <i class="icon-chevron-right text-[20px] text-[#00B8D9]"></i>
                    </button>
                </div>
                </template>

                <!-- Add product row -->
                <button
                    type="button"
                    class="flex w-full items-center justify-center border border-dashed border-[#C7C7CC] bg-white"
                    style="height:44px; margin-top:8px; border-radius:8px;"
                    @click="router.push('/products/create')"
                >
                    <span class="text-[17px] text-[#00B8D9]">+ 商品を追加する</span>
                </button>
            </template>
        </div>

        <!-- ── Fixed bottom action bar ────────────────────────────── -->
        <div
            class="fixed inset-x-0 bottom-0 z-40 flex items-center gap-3 border-t border-[#E5E5EA] bg-white px-4"
            style="height:50px; padding-bottom: env(safe-area-inset-bottom, 0px);"
        >
            <!-- PC redirect button -->
            <button
                type="button"
                class="flex h-10 items-center rounded-lg border border-[#C7C7CC] px-3 text-[14px] text-[#1C1C1E] active:opacity-70"
                @click="emitter.emit('add_flash', { type: 'info', message: 'PCブラウザからご利用ください。' })"
            >
                パソコンで登録する
            </button>

            <!-- Stock Inception entry point -->
            <button
                type="button"
                class="flex h-10 items-center rounded-lg border border-[#00B8D9] px-3 text-[14px] font-medium text-[#00B8D9] active:opacity-70"
                @click="router.push('/products/inception')"
            >
                {{ $t('pos.products.inception.title') }}
            </button>

            <!-- Save button: gray until dirty, teal when dirty -->
            <button
                type="button"
                class="ml-auto flex h-10 items-center rounded-lg px-5 text-[15px] font-semibold text-white transition-colors duration-200"
                :class="dirtyRows.size > 0 ? 'bg-[#00B8D9] active:opacity-70' : 'bg-[#C7C7CC] pointer-events-none'"
                :disabled="dirtyRows.size === 0 || saving"
                @click="saveChanges"
            >
                <span v-if="saving" class="icon-spinner mr-1.5 animate-spin text-sm"></span>
                保存する
            </button>
        </div>

        <!-- ── Floating edit popup (above keyboard) ─────────────── -->
        <Teleport to="body">
            <div
                v-if="activeField !== null && activeField.idx < localProducts.length"
                class="fixed inset-x-0 bottom-0 z-[10060]"
            >
                <!-- backdrop (tap outside to dismiss) -->
                <div
                    class="fixed inset-0 bg-transparent"
                    @mousedown.prevent="dismissKeyboard"
                    @touchstart.prevent="dismissKeyboard"
                ></div>

                <!-- Edit card sits above keyboard -->
                <div class="relative bg-white shadow-[0_-4px_20px_rgba(0,0,0,0.12)] border-t border-[#E5E5EA] px-4 pt-3"
                     style="padding-bottom: max(12px, env(safe-area-inset-bottom, 12px));">
                    <!-- Context label -->
                    <p class="text-[12px] text-[#8E8E93] mb-1.5 truncate">
                        {{ localProducts[activeField.idx]?.name || '' }}
                        <span class="ml-1 text-[#00B8D9]">{{ activeFieldLabel }}</span>
                    </p>

                    <!-- Big input -->
                    <input
                        v-if="activeField.field === 'name'"
                        :ref="el => popupInputRef = el"
                        v-model="localProducts[activeField.idx].name"
                        type="text"
                        class="w-full rounded-xl border-2 border-[#00B8D9] px-4 py-2.5 text-[20px] text-[#1C1C1E] outline-none bg-[#F7FEFF]"
                        placeholder="商品名"
                        @input="markDirty(activeField.idx)"
                    />
                    <input
                        v-else
                        :ref="el => popupInputRef = el"
                        v-model="localProducts[activeField.idx][activeField.field]"
                        :type="numericInputType(activeField.field)"
                        :inputmode="numericInputMode(activeField.field)"
                        min="0"
                        :step="numericStep(activeField.field)"
                        class="w-full rounded-xl border-2 border-[#00B8D9] px-4 py-2.5 text-[20px] text-right text-[#1C1C1E] outline-none bg-[#F7FEFF]"
                        placeholder="0"
                        @input="markDirty(activeField.idx)"
                    />

                    <!-- Navigation toolbar -->
                    <div class="flex items-center justify-between mt-2 pb-0.5">
                        <div class="flex gap-5">
                            <button
                                type="button"
                                class="text-[22px] font-bold text-[#4A4A4A] active:opacity-50 px-2"
                                @mousedown.prevent="focusPrev"
                                @touchstart.prevent="focusPrev"
                            >∧</button>
                            <button
                                type="button"
                                class="text-[22px] font-bold text-[#4A4A4A] active:opacity-50 px-2"
                                @mousedown.prevent="focusNext"
                                @touchstart.prevent="focusNext"
                            >∨</button>
                        </div>
                        <button
                            type="button"
                            class="text-[17px] font-semibold text-[#00B8D9] active:opacity-50 px-2"
                            @mousedown.prevent="dismissKeyboard"
                            @touchstart.prevent="dismissKeyboard"
                        >完了</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ── Delete confirm overlay ─────────────────────────────── -->
        <Teleport to="body">
            <div
                v-if="deleteTarget !== null"
                class="fixed inset-0 z-50 flex items-end justify-center md:items-center"
            >
                <div class="absolute inset-0 bg-black/40" @click="deleteTarget = null"></div>

                <div class="relative z-10 w-full max-w-sm rounded-t-2xl bg-white p-6 md:rounded-2xl">
                    <p class="mb-4 text-center text-[17px] font-semibold text-[#1C1C1E]">
                        「{{ deleteTarget?.product?.name }}」を削除しますか？
                    </p>

                    <div class="flex gap-3">
                        <button
                            type="button"
                            class="flex-1 rounded-xl border border-[#E5E5EA] bg-[#F2F2F7] py-3 text-[15px] font-medium text-[#1C1C1E] active:opacity-70"
                            @click="deleteTarget = null"
                        >キャンセル</button>

                        <button
                            type="button"
                            class="flex-1 rounded-xl bg-[#FF3B30] py-3 text-[15px] font-semibold text-white active:opacity-70"
                            @click="executeDelete"
                        >削除</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ── Price help tooltip ─────────────────────────────────── -->
        <Teleport to="body">
            <div
                v-if="showPriceHelp"
                class="fixed inset-0 z-50 flex items-center justify-center"
                @click="showPriceHelp = false"
            >
                <div class="absolute inset-0 bg-black/40"></div>

                <div class="relative z-10 mx-6 rounded-2xl bg-white p-6 text-center">
                    <p class="text-[17px] font-semibold text-[#1C1C1E] mb-2">価格</p>
                    <p class="text-[15px] text-[#8E8E93]">税込価格を入力してください。</p>
                    <button
                        type="button"
                        class="mt-5 rounded-xl bg-[#00B8D9] px-8 py-2.5 text-[15px] font-semibold text-white"
                        @click="showPriceHelp = false"
                    >OK</button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
    import { ref, reactive, inject, onMounted, nextTick, computed } from 'vue';
    import { useRouter } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import { useIndexedDB } from '@src/composable/indexed-db';
    import { useApolloClient, useMutation } from '@vue/apollo-composable';
    import { BULK_UPDATE_PRODUCTS } from '@src/graphql/products';
    import { PRODUCTS } from '@src/graphql/home';

    const router       = useRouter();
    const { t }        = useI18n();
    const emitter      = inject('emitter');
    const isOnline     = inject('isOnline', ref(true));
    const DB           = useIndexedDB();
    const apolloClient = useApolloClient();

    // ── Product data ─────────────────────────────────────────────
    const loading       = ref(true);
    const localProducts = ref([]);
    const listEl        = ref(null);

    // ── Search ───────────────────────────────────────────────────
    const searchQuery = ref('');

    const filteredProducts = computed(() => {
        const q = searchQuery.value.trim().toLowerCase();
        if (!q) return localProducts.value;
        return localProducts.value.filter(p =>
            (p.name ?? '').toLowerCase().includes(q)
            || (p.sku ?? '').toLowerCase().includes(q)
            || (p.barcode ?? '').toLowerCase().includes(q)
            || (p.gtin ?? '').toLowerCase().includes(q)
            || (p.scanValue ?? '').toLowerCase().includes(q)
        );
    });

    function indexOf(product) {
        return localProducts.value.findIndex(p => p.id === product.id);
    }

    // ── Configurable second column ───────────────────────────────
    const COL_STORAGE_KEY = 'pos_product_list_second_column';
    const columnOptions = computed(() => [
        { value: 'stock',    label: t('pos.products.list.column.stock') },
        { value: 'price',    label: t('pos.products.list.column.price') },
        { value: 'weight',   label: t('pos.products.list.column.weight') },
        { value: 'category', label: t('pos.products.list.column.category') },
        { value: 'status',   label: t('pos.products.list.column.status') },
    ]);
    const secondColumnPref = ref(localStorage.getItem(COL_STORAGE_KEY) || 'stock');
    const columnMenuOpen   = ref(false);

    /**
     * Normalised second-column key. UI uses "stock" but the underlying
     * product field is "quantity"; map between them so the popup editor
     * can v-model the right property.
     */
    const secondColumn = computed(() => {
        if (secondColumnPref.value === 'stock') return 'quantity';
        return secondColumnPref.value;
    });

    const secondColLabel = computed(() => {
        const opt = columnOptions.value.find(o => o.value === secondColumnPref.value);
        return opt?.label ?? '';
    });

    /**
     * Category and status are non-numeric columns. The first is read-only
     * (cashier edits it through the full ProductEdit screen), the second
     * is rendered as two pill toggles (POS / Website) inline.
     */
    const isInlineEditable = computed(() => {
        return ['quantity', 'price', 'weight'].includes(secondColumn.value);
    });

    function selectColumn(value) {
        secondColumnPref.value = value;
        localStorage.setItem(COL_STORAGE_KEY, value);
        columnMenuOpen.value = false;
    }

    function closeColumnMenu() {
        columnMenuOpen.value = false;
    }

    function secondColDisplay(product) {
        const field = secondColumn.value;
        if (field === 'price')    return '¥' + formatPrice(product.price);
        if (field === 'weight')   return formatNum(product.weight) + ' kg';
        if (field === 'category') return product.categories?.[0]?.name ?? '—';
        return String(product.quantity ?? 0);
    }

    function numericInputType()  { return 'number'; }
    function numericInputMode(field) {
        return field === 'quantity' ? 'numeric' : 'decimal';
    }
    function numericStep(field) {
        if (field === 'quantity') return '1';
        if (field === 'weight')   return '0.001';
        return '0.01';
    }

    // One-shot top-up: if IndexedDB is short, fetch the full outlet
    // catalog from GraphQL in one call and upsert before rendering.
    // The Settings/Sync page paginates at batchSize=20 and stops on any
    // interruption, leaving partial caches (eg. 60 of 366 products).
    // This guarantees the list renders the full catalog without depending
    // on a completed sync session.
    async function topUpFromServer() {
        if (! isOnline.value) return;
        try {
            const { data } = await apolloClient.client.query({
                query: PRODUCTS,
                variables: { page: 1, first: 5000 },
                fetchPolicy: 'network-only',
            });
            const items = data?.getOutletProducts?.data ?? [];
            for (const item of items) {
                try {
                    await DB.updateItem('products', JSON.parse(JSON.stringify(item)));
                } catch (e) {
                    console.warn('topUp upsert failed for product', item?.id, e);
                }
            }
        } catch (e) {
            // Stay quiet on failure — IndexedDB still has whatever the
            // last successful sync wrote, so the page degrades to the
            // previous behaviour rather than breaking.
            console.warn('Product top-up failed; using cached set:', e);
        }
    }

    onMounted(async () => {
        await topUpFromServer();
        const raw = await DB.getAllItems('products');

        localProducts.value = raw.map(p => ({
            ...p,
            // Default missing status flags to "on" so a freshly synced cache
            // (pre-status-column rollout) doesn't show every product as off.
            status:    p.status    ?? 1,
            posStatus: p.posStatus ?? 1,
            // Keep originals for dirty detection
            _origName:      p.name,
            _origPrice:     p.price,
            _origQuantity:  p.quantity  ?? 0,
            _origWeight:    p.weight    ?? 0,
            _origStatus:    p.status    ?? 1,
            _origPosStatus: p.posStatus ?? 1,
            _shipping:      false,   // flag shipping row (you can mark by type/sku)
        }));

        loading.value = false;
    });

    // ── Dirty tracking ────────────────────────────────────────────
    const dirtyRows = reactive(new Set());
    const saving    = ref(false);

    const { mutate: bulkUpdate } = useMutation(BULK_UPDATE_PRODUCTS);

    function markDirty(idx) {
        const p = localProducts.value[idx];

        const nameChanged      = p.name !== p._origName;
        const priceChanged     = String(p.price) !== String(p._origPrice);
        const qtyChanged       = String(p.quantity ?? 0) !== String(p._origQuantity ?? 0);
        const weightChanged    = String(p.weight ?? 0) !== String(p._origWeight ?? 0);
        const statusChanged    = Number(p.status ?? 1) !== Number(p._origStatus ?? 1);
        const posStatusChanged = Number(p.posStatus ?? 1) !== Number(p._origPosStatus ?? 1);

        if (nameChanged || priceChanged || qtyChanged || weightChanged || statusChanged || posStatusChanged) {
            dirtyRows.add(p.id);
        } else {
            dirtyRows.delete(p.id);
        }
    }

    /**
     * Flip a status flag in-place. Two independent toggles per row:
     * `status`    → products.status (storefront/website visibility)
     * `posStatus` → pos_outlet_product.status (POS grid visibility for *this* outlet)
     */
    function toggleStatus(product, field) {
        if (product._shipping) return;
        const idx = indexOf(product);
        if (idx === -1) return;
        const current = Number(localProducts.value[idx][field] ?? 1);
        localProducts.value[idx][field] = current ? 0 : 1;
        markDirty(idx);
    }

    // ── Active field tracking ─────────────────────────────────────
    // activeField: null | { idx, field: 'name'|'price' }
    const activeField  = ref(null);
    const popupInputRef = ref(null);
    const activeIdx    = computed(() => activeField.value?.idx ?? -1);

    function isActive(idx, field) {
        return activeField.value?.idx === idx && activeField.value?.field === field;
    }

    const activeFieldLabel = computed(() => {
        const f = activeField.value?.field;
        if (f === 'name')     return '商品名を編集';
        if (f === 'price')    return '価格を編集';
        if (f === 'quantity') return '在庫を編集';
        if (f === 'weight')   return '重量を編集';
        return '';
    });

    // Legacy inputMap kept for keyboard nav (no longer used for inline input)
    const inputMap = {};

    function setInputRef(el, idx, field) {
        if (el) inputMap[`${idx}-${field}`] = el;
    }

    function activate(idx, field) {
        activeField.value = { idx, field };

        // Auto-focus the popup input after Vue renders it
        nextTick(() => {
            if (popupInputRef.value) {
                popupInputRef.value.focus();
                popupInputRef.value.select?.();
            }
        });

        // Scroll the selected row into view
        nextTick(() => {
            const rows = listEl.value?.querySelectorAll('[data-row]');
            if (rows?.[idx]) {
                rows[idx].scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        });
    }

    function onFocus(idx, field) {
        activeField.value = { idx, field };
    }

    function onBlur(idx, field) {
        setTimeout(() => {
            if (activeField.value?.idx === idx && activeField.value?.field === field) {
                markDirty(idx);
                activeField.value = null;
            }
        }, 100);
    }

    // ── Keyboard toolbar navigation ───────────────────────────────
    // Flat order: [0-name, 0-price, 1-name, 1-price, ...]
    function allFieldKeys() {
        const keys = [];
        const second = secondColumn.value;

        for (let i = 0; i < localProducts.value.length; i++) {
            if (! localProducts.value[i]._shipping) {
                keys.push({ idx: i, field: 'name' });
                // Only cycle through inline-editable columns; status/category
                // are handled by direct taps, not by the up/down keyboard nav.
                if (isInlineEditable.value) {
                    keys.push({ idx: i, field: second });
                }
            }
        }

        return keys;
    }

    function currentFieldPos() {
        if (activeField.value === null) return -1;

        const keys = allFieldKeys();

        return keys.findIndex(k => k.idx === activeField.value.idx && k.field === activeField.value.field);
    }

    function focusPrev() {
        const keys = allFieldKeys();
        const pos  = currentFieldPos();

        if (pos <= 0) return;

        markDirty(activeField.value.idx);

        const prev = keys[pos - 1];

        activate(prev.idx, prev.field);
    }

    function focusNext() {
        const keys = allFieldKeys();
        const pos  = currentFieldPos();

        if (pos === -1 || pos >= keys.length - 1) return;

        markDirty(activeField.value?.idx ?? -1);

        const next = keys[pos + 1];

        activate(next.idx, next.field);
    }

    function dismissKeyboard() {
        if (activeField.value !== null) {
            markDirty(activeField.value.idx);
        }

        popupInputRef.value?.blur();
        activeField.value = null;
    }

    // ── Delete ────────────────────────────────────────────────────
    const deleteTarget = ref(null);

    function confirmDelete(product, idx) {
        deleteTarget.value = { product, idx };
    }

    async function executeDelete() {
        const { product, idx } = deleteTarget.value;

        deleteTarget.value = null;

        // Remove from local list and dirty tracking
        localProducts.value.splice(idx, 1);
        dirtyRows.delete(product.id);

        // Remove from IndexedDB so it doesn't reappear after next sync
        try {
            await DB.removeItem('products', product.id);
        } catch (_) { /* non-fatal */ }

        emitter.emit('add_flash', { type: 'success', message: t('pos.products.list.delete_success') });

        // Note: actual catalog deletion requires admin access (not available from POS).
        // The product remains in Bagisto catalog but is removed from the offline cache.
        // A full sync will re-add it unless de-assigned in the admin panel.
    }

    // ── Save (GraphQL bulk mutation) ──────────────────────────────
    async function saveChanges() {
        if (dirtyRows.size === 0 || saving.value) return;

        saving.value = true;

        const payload = localProducts.value
            .filter(p => dirtyRows.has(p.id))
            .map(p => {
                const item = {
                    id:       p.id,
                    name:     p.name,
                    price:    parseFloat(p.price) || 0,
                    quantity: parseInt(p.quantity ?? 0, 10) || 0,
                    weight:   parseFloat(p.weight ?? 0) || 0,
                };

                // Only send status fields when actually toggled, so rows that
                // changed name/price don't accidentally re-stamp a status the
                // server already has.
                if (Number(p.status ?? 1) !== Number(p._origStatus ?? 1)) {
                    item.status = Number(p.status) ? 1 : 0;
                }
                if (Number(p.posStatus ?? 1) !== Number(p._origPosStatus ?? 1)) {
                    item.posStatus = Number(p.posStatus) ? 1 : 0;
                }

                return item;
            });

        try {
            const response = await bulkUpdate({ input: { products: payload } });

            const result = response?.data?.bulkUpdateOutletProducts;

            if (result?.success) {
                // Persist originals + update IndexedDB cache
                for (const p of localProducts.value.filter(p => dirtyRows.has(p.id))) {
                    p._origName      = p.name;
                    p._origPrice     = p.price;
                    p._origQuantity  = p.quantity  ?? 0;
                    p._origWeight    = p.weight    ?? 0;
                    p._origStatus    = p.status    ?? 1;
                    p._origPosStatus = p.posStatus ?? 1;

                    // Strip internal UI fields before writing to DB
                    const { _origName, _origPrice, _origQuantity, _origWeight, _origStatus, _origPosStatus, _shipping, ...dbRecord } = p;
                    await DB.updateItem('products', dbRecord);
                }

                dirtyRows.clear();

                emitter.emit('add_flash', { type: 'success', message: t('pos.products.list.save_success') });
            } else {
                throw new Error(result?.message ?? '');
            }
        } catch (err) {
            emitter.emit('add_flash', { type: 'error', message: t('pos.products.list.save_error') });
        } finally {
            saving.value = false;
        }
    }

    // ── Helpers ───────────────────────────────────────────────────
    function formatPrice(val) {
        const n = parseFloat(val) || 0;

        return n.toLocaleString('ja-JP');
    }

    function formatNum(val) {
        const n = parseFloat(val);
        if (isNaN(n)) return '0';
        // Trim insignificant trailing zeros for weight-style display.
        return Number(n.toFixed(3)).toString();
    }

    const showPriceHelp = ref(false);
</script>

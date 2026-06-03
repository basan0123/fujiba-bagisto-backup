<template>
    <div class="flex flex-col gap-3">
        <!-- Toolbar -->
        <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-white dark:bg-gray-900 px-3 py-2">
            <div class="flex items-center gap-2 text-sm">
                <span class="font-semibold text-gray-900 dark:text-gray-100">
                    {{ editing ? '配置の編集中' : 'POS ホーム' }}
                </span>
                <span class="text-gray-500 dark:text-gray-400">
                    {{ activeCategoryName }}
                </span>
            </div>

            <div class="flex items-center gap-2">
                <button
                    v-if="editing"
                    type="button"
                    class="rounded-md bg-[#00B8D9] px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90"
                    @click="openPicker"
                >
                    + 商品を追加
                </button>

                <button
                    v-if="editing"
                    type="button"
                    class="rounded-md border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800"
                    @click="resetLayout"
                >
                    リセット
                </button>

                <button
                    type="button"
                    class="rounded-md border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800"
                    :class="{ '!bg-[#00B8D9] !text-white !border-[#00B8D9]': editing }"
                    @click="toggleEditing"
                >
                    {{ editing ? '完了' : '配置を編集' }}
                </button>
            </div>
        </div>

        <!-- Product grid -->
        <div
            ref="gridEl"
            class="grid gap-3 [grid-template-columns:repeat(auto-fill,_minmax(160px,_1fr))]"
            @dragover.prevent
            @drop.prevent="onDropToEnd"
        >
            <div
                v-for="(product, idx) in arrangedProducts"
                :key="`tile-${product.id}`"
                :data-tile-id="product.id"
                class="group relative box-shadow flex cursor-pointer flex-col gap-1.5 rounded-lg bg-white dark:bg-gray-900 p-2 transition-transform"
                :class="{
                    'cursor-grab touch-none select-none': editing,
                    'ring-2 ring-[#00B8D9]': editing && draggingId === product.id,
                    'opacity-40': editing && draggingId === product.id,
                    'ring-1 ring-dashed ring-[#00B8D9]/60': editing && dragOverId === product.id && draggingId !== product.id,
                }"
                :draggable="editing"
                @click="onTileClick(product)"
                @dragstart="onDragStart($event, product)"
                @dragend="onDragEnd"
                @dragover.prevent="onDragOver($event, product)"
                @dragleave="onDragLeave(product)"
                @drop.stop.prevent="onDrop($event, product, idx)"
                @pointerdown="onPointerDown($event, product)"
            >
                <!-- Remove button (edit mode only) -->
                <button
                    v-if="editing"
                    type="button"
                    class="absolute -right-1 -top-1 z-10 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white shadow hover:bg-red-600"
                    title="このタイルを削除"
                    @click.stop="removeTile(product)"
                >
                    ×
                </button>

                <img
                    v-if="product.images?.length"
                    :src="product.images[0].url"
                    class="aspect-square rounded object-cover"
                    alt="product image"
                />
                <img
                    v-else
                    src="@images/product-placeholder.webp"
                    class="aspect-square rounded object-cover opacity-30 dark:invert"
                    alt=""
                />

                <p class="truncate-text-2 text-center text-[13px] font-medium leading-4 text-gray-900 dark:text-gray-100">
                    {{ product.name || product.sku }}
                </p>

                <p
                    class="flex justify-center gap-1 text-[13px] font-normal leading-4 text-gray-900 dark:text-gray-100"
                    v-html="product.priceHtml"
                ></p>

                <p
                    v-if="['simple', 'virtual'].includes(product.type)"
                    class="text-center text-[11px] font-medium text-gray-500 dark:text-gray-400"
                >
                    在庫 {{ product.quantity ?? 0 }}
                </p>
            </div>

            <!-- Add tile (edit mode) -->
            <button
                v-if="editing"
                type="button"
                class="flex aspect-[4/5] flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:border-[#00B8D9] hover:text-[#00B8D9]"
                @click="openPicker"
            >
                <span class="text-3xl leading-none">+</span>
                <span class="text-xs">商品を追加</span>
            </button>

            <!-- Empty hint -->
            <div
                v-if="!arrangedProducts.length && !editing"
                class="col-span-full rounded-lg border border-dashed border-gray-300 dark:border-gray-700 px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400"
            >
                <template v-if="activeCategoryId === 'no-barcode'">
                    バーコードのない商品はありません。<br />
                    すべての商品にバーコードが登録されています。
                </template>
                <template v-else>
                    このカテゴリには商品が登録されていません。<br />
                    「配置を編集」から追加してください。
                </template>
            </div>
        </div>

        <!-- Touch drag ghost (follows finger) -->
        <Teleport to="body">
            <div
                v-if="ghost.visible"
                class="pointer-events-none fixed z-[70] w-[140px] rounded-lg bg-white dark:bg-gray-900 p-2 opacity-90 shadow-2xl ring-2 ring-[#00B8D9]"
                :style="{ left: ghost.x + 'px', top: ghost.y + 'px', transform: 'translate(-50%, -50%)' }"
            >
                <img
                    v-if="ghost.product?.images?.length"
                    :src="ghost.product.images[0].url"
                    class="aspect-square rounded object-cover"
                    alt=""
                />
                <img
                    v-else
                    src="@images/product-placeholder.webp"
                    class="aspect-square rounded object-cover opacity-30"
                    alt=""
                />
                <p class="truncate-text-2 text-center text-[12px] font-medium leading-4 text-gray-900 dark:text-gray-100">
                    {{ ghost.product?.name || ghost.product?.sku }}
                </p>
            </div>
        </Teleport>

        <!-- Product picker overlay -->
        <Teleport to="body">
            <div
                v-if="pickerOpen"
                class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 px-4"
                @click.self="closePicker"
            >
                <div class="flex w-full max-w-3xl flex-col rounded-xl bg-white dark:bg-gray-900 shadow-xl" style="max-height: 80vh;">
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 p-4">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                            商品を追加 — {{ activeCategoryName }}
                        </h3>
                        <button
                            type="button"
                            class="text-gray-500 hover:text-gray-900 dark:text-gray-300"
                            @click="closePicker"
                        >
                            <span class="icon-cross text-2xl"></span>
                        </button>
                    </div>

                    <div class="border-b border-gray-200 dark:border-gray-700 p-3">
                        <input
                            v-model="pickerSearch"
                            type="text"
                            class="h-9 w-full rounded-md bg-[#F2F2F7] dark:bg-gray-800 px-3 text-sm text-gray-900 dark:text-gray-100 outline-none"
                            placeholder="商品名 / SKU で検索"
                        />
                    </div>

                    <div class="flex-1 overflow-y-auto p-3">
                        <div class="grid gap-2 [grid-template-columns:repeat(auto-fill,_minmax(140px,_1fr))]">
                            <button
                                v-for="product in pickerResults"
                                :key="`pick-${product.id}`"
                                type="button"
                                class="flex flex-col gap-1 rounded-md border border-gray-200 dark:border-gray-700 p-2 text-left hover:border-[#00B8D9] hover:bg-[#00B8D9]/5"
                                @click="addProductToLayout(product)"
                            >
                                <img
                                    v-if="product.images?.length"
                                    :src="product.images[0].url"
                                    class="aspect-square rounded object-cover"
                                    alt=""
                                />
                                <img
                                    v-else
                                    src="@images/product-placeholder.webp"
                                    class="aspect-square rounded object-cover opacity-30"
                                    alt=""
                                />
                                <p class="truncate text-[12px] font-medium text-gray-900 dark:text-gray-100">
                                    {{ product.name || product.sku }}
                                </p>
                                <p class="truncate text-[10px] text-gray-500 dark:text-gray-400">
                                    {{ product.sku }}
                                </p>
                            </button>

                            <p
                                v-if="!pickerResults.length"
                                class="col-span-full py-6 text-center text-sm text-gray-500 dark:text-gray-400"
                            >
                                該当する商品がありません。
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
    import { ref, computed, watch, onBeforeUnmount } from 'vue';
    import { useIndexedDB } from '@src/composable/indexed-db';

    const props = defineProps({
        allProducts: {
            type: Array,
            required: true,
        },
        activeCategoryId: {
            type: [Number, String],
            default: 0,
        },
        activeCategoryName: {
            type: String,
            default: 'すべて',
        },
        searchTerm: {
            type: String,
            default: '',
        },
    });

    const emit = defineEmits(['add-to-cart']);

    const DB = useIndexedDB();

    /**
     * State
     */
    const editing      = ref(false);
    const layoutKey    = ref(null);
    const orderedIds   = ref([]); // explicit ordering for current category
    const removedIds   = ref([]); // products hidden by cashier
    const pickerOpen   = ref(false);
    const pickerSearch = ref('');
    const draggingId   = ref(null);
    const dragOverId   = ref(null);

    /**
     * Touch drag (iPad / phone). HTML5 drag events don't fire on touch, so we
     * drive reordering with Pointer events: a long-press starts the drag, a
     * floating ghost follows the finger, and the tile under the finger becomes
     * the drop target.
     */
    const gridEl = ref(null);
    const ghost  = ref({ visible: false, x: 0, y: 0, product: null });

    let touchState = null; // { pointerId, product, longPressTimer, started }
    let lastDragEndedAt = 0;
    const LONG_PRESS_MS = 220;
    const MOVE_CANCEL_PX = 12; // finger movement before long-press fires = scroll, not drag

    /**
     * Build a stable per-cashier-per-outlet-per-category storage key.
     */
    const buildKey = async (categoryId) => {
        const agent = await DB.getAgent();
        const outletId = agent?.outletId ?? agent?.outlet_id ?? 'unknown';
        const email = (agent?.email ?? 'anon').toLowerCase();
        return `o${outletId}:u${email}:c${categoryId ?? 0}`;
    };

    /**
     * Load layout from IndexedDB for the current category.
     */
    const loadLayout = async () => {
        layoutKey.value = await buildKey(props.activeCategoryId);
        const row = await DB.getItem('pos_layouts', layoutKey.value);
        orderedIds.value = Array.isArray(row?.orderedIds) ? [...row.orderedIds] : [];
        removedIds.value = Array.isArray(row?.removedIds) ? [...row.removedIds] : [];
    };

    const saveLayout = async () => {
        if (! layoutKey.value) return;
        await DB.updateItem('pos_layouts', {
            id: layoutKey.value,
            orderedIds: orderedIds.value,
            removedIds: removedIds.value,
            updatedAt: Date.now(),
        });
    };

    /**
     * Default products for the current category, before customization.
     * `'no-barcode'` is a virtual category surfacing products without a
     * scannable barcode/scan_value — these inherit the same per-cashier
     * drag-reorder system as real categories.
     */
    const defaultCategoryProducts = computed(() => {
        const cid = props.activeCategoryId;
        if (! cid) return props.allProducts;

        if (cid === 'no-barcode') {
            // "No barcode" = no real GTIN. The auto-register listener stamps
            // SKU into pos_product_barcode.barcode/scan_value for every
            // product, so checking those is meaningless — only `gtin` (and
            // scan_type='gtin') reflects an actual scannable barcode the
            // admin entered manually.
            return props.allProducts.filter(p => {
                const gtin = (p?.gtin ?? '').toString().trim();
                const scanType = (p?.scanType ?? p?.scan_type ?? '').toString().trim();
                return ! gtin && scanType !== 'gtin';
            });
        }

        return props.allProducts.filter(p =>
            Array.isArray(p.categories) && p.categories.some(c => String(c.id) === String(cid))
        );
    });

    /**
     * Final ordering: cashier-arranged ids first (in their order), then any
     * default-category products that haven't been arranged yet, minus removed.
     */
    const arrangedProducts = computed(() => {
        const byId = new Map(props.allProducts.map(p => [p.id, p]));
        const removed = new Set(removedIds.value);
        const result = [];
        const seen = new Set();

        for (const id of orderedIds.value) {
            if (removed.has(id)) continue;
            const p = byId.get(id);
            if (p) {
                result.push(p);
                seen.add(id);
            }
        }

        for (const p of defaultCategoryProducts.value) {
            if (seen.has(p.id) || removed.has(p.id)) continue;
            result.push(p);
        }

        // Apply optional search term
        if (props.searchTerm) {
            const t = props.searchTerm.toLowerCase();
            return result.filter(p =>
                (p.name || '').toLowerCase().includes(t) ||
                (p.sku || '').toLowerCase().includes(t)
            );
        }

        return result;
    });

    /**
     * Picker — list of products that can be added to the current category tile set.
     * Excludes products already on the grid (and respects removed-list — adding
     * a removed product un-removes it).
     */
    const pickerResults = computed(() => {
        const present = new Set(arrangedProducts.value.map(p => p.id));
        const t = pickerSearch.value.trim().toLowerCase();

        return props.allProducts
            .filter(p => ! present.has(p.id))
            .filter(p => {
                if (! t) return true;
                return (p.name || '').toLowerCase().includes(t)
                    || (p.sku || '').toLowerCase().includes(t);
            })
            .slice(0, 200);
    });

    /**
     * Editing actions
     */
    const toggleEditing = () => {
        editing.value = ! editing.value;
        if (! editing.value) {
            saveLayout();
        }
    };

    const onTileClick = (product) => {
        if (editing.value) return; // Don't add-to-cart in edit mode
        if (Date.now() - lastDragEndedAt < 350) return; // swallow click synthesized after a touch drag
        emit('add-to-cart', product);
    };

    const removeTile = async (product) => {
        if (! removedIds.value.includes(product.id)) {
            removedIds.value = [...removedIds.value, product.id];
        }
        orderedIds.value = orderedIds.value.filter(id => id !== product.id);
        await saveLayout();
    };

    const resetLayout = async () => {
        orderedIds.value = [];
        removedIds.value = [];
        await saveLayout();
    };

    /**
     * Picker
     */
    const openPicker = () => {
        pickerSearch.value = '';
        pickerOpen.value = true;
    };

    const closePicker = () => {
        pickerOpen.value = false;
    };

    const addProductToLayout = async (product) => {
        // un-remove if previously removed
        removedIds.value = removedIds.value.filter(id => id !== product.id);

        // append to ordered list
        if (! orderedIds.value.includes(product.id)) {
            orderedIds.value = [...orderedIds.value, product.id];
        }

        await saveLayout();
        closePicker();
    };

    /**
     * Drag and drop
     */
    const ensureExplicitOrder = () => {
        // Materialize current arrangement into explicit ids so we can reorder.
        if (orderedIds.value.length === arrangedProducts.value.length) return;
        orderedIds.value = arrangedProducts.value.map(p => p.id);
    };

    const onDragStart = (event, product) => {
        if (! editing.value) return;
        draggingId.value = product.id;
        ensureExplicitOrder();
        try {
            event.dataTransfer.setData('text/plain', String(product.id));
            event.dataTransfer.effectAllowed = 'move';
        } catch (e) { /* some browsers */ }
    };

    const onDragOver = (event, product) => {
        if (! editing.value || draggingId.value === null) return;
        dragOverId.value = product.id;
        event.dataTransfer.dropEffect = 'move';
    };

    const onDragLeave = (product) => {
        if (dragOverId.value === product.id) dragOverId.value = null;
    };

    const onDrop = async (event, target, targetIdx) => {
        if (! editing.value || draggingId.value === null) return;
        const fromId = draggingId.value;
        const toId = target.id;
        if (fromId === toId) {
            draggingId.value = null;
            dragOverId.value = null;
            return;
        }

        await moveTile(fromId, toId);

        draggingId.value = null;
        dragOverId.value = null;
    };

    const onDropToEnd = async (event) => {
        if (! editing.value || draggingId.value === null) return;
        ensureExplicitOrder();
        const next = orderedIds.value.filter(id => id !== draggingId.value);
        next.push(draggingId.value);
        orderedIds.value = next;
        draggingId.value = null;
        dragOverId.value = null;
        await saveLayout();
    };

    const onDragEnd = () => {
        draggingId.value = null;
        dragOverId.value = null;
    };

    /**
     * Touch / pointer drag (iPad & phone)
     */
    const tileIdFromPoint = (x, y) => {
        const el = document.elementFromPoint(x, y);
        const tile = el?.closest?.('[data-tile-id]');
        if (! tile) return null;
        const id = tile.getAttribute('data-tile-id');
        // ids may be number or string — match against the live list
        const match = arrangedProducts.value.find(p => String(p.id) === String(id));
        return match ? match.id : null;
    };

    const onPointerDown = (event, product) => {
        // Mouse keeps using native HTML5 drag; only hijack touch/pen.
        if (! editing.value || event.pointerType === 'mouse') return;

        const startX = event.clientX;
        const startY = event.clientY;

        touchState = {
            pointerId: event.pointerId,
            product,
            startX,
            startY,
            started: false,
            longPressTimer: setTimeout(() => {
                // Begin drag: lock the tile, show ghost, capture the pointer.
                if (! touchState) return;
                touchState.started = true;
                draggingId.value = product.id;
                ensureExplicitOrder();
                ghost.value = { visible: true, x: startX, y: startY, product };
                try { event.target.setPointerCapture?.(event.pointerId); } catch (e) { /* noop */ }
                if (navigator.vibrate) navigator.vibrate(10);
            }, LONG_PRESS_MS),
        };

        window.addEventListener('pointermove', onPointerMove, { passive: false });
        window.addEventListener('pointerup', onPointerUp);
        window.addEventListener('pointercancel', onPointerUp);
    };

    const onPointerMove = (event) => {
        if (! touchState || event.pointerId !== touchState.pointerId) return;

        const dx = Math.abs(event.clientX - touchState.startX);
        const dy = Math.abs(event.clientY - touchState.startY);

        if (! touchState.started) {
            // Moved before the long-press fired → treat as a scroll, abort drag.
            if (dx > MOVE_CANCEL_PX || dy > MOVE_CANCEL_PX) {
                clearTimeout(touchState.longPressTimer);
                teardownPointer();
            }
            return;
        }

        // Dragging: prevent the page from scrolling and move the ghost.
        event.preventDefault();
        ghost.value.x = event.clientX;
        ghost.value.y = event.clientY;

        const overId = tileIdFromPoint(event.clientX, event.clientY);
        dragOverId.value = (overId !== null && overId !== draggingId.value) ? overId : null;
    };

    const onPointerUp = async (event) => {
        if (! touchState || event.pointerId !== touchState.pointerId) return;

        const started = touchState.started;
        clearTimeout(touchState.longPressTimer);

        if (started) {
            const overId = tileIdFromPoint(event.clientX, event.clientY);
            if (overId !== null && overId !== draggingId.value) {
                await moveTile(draggingId.value, overId);
            }
            ghost.value = { visible: false, x: 0, y: 0, product: null };
            draggingId.value = null;
            dragOverId.value = null;
            lastDragEndedAt = Date.now();
        }
        // If not started, it was a tap — let the native @click add-to-cart fire.

        teardownPointer();
    };

    const teardownPointer = () => {
        window.removeEventListener('pointermove', onPointerMove);
        window.removeEventListener('pointerup', onPointerUp);
        window.removeEventListener('pointercancel', onPointerUp);
        touchState = null;
    };

    /**
     * Move `fromId` to sit just before `toId` in the explicit order, and persist.
     * Shared by HTML5 drop and touch drop.
     */
    const moveTile = async (fromId, toId) => {
        ensureExplicitOrder();
        const next = [...orderedIds.value];
        const fromIdx = next.indexOf(fromId);
        const toIdx = next.indexOf(toId);
        if (fromIdx === -1 || toIdx === -1) return;

        next.splice(fromIdx, 1);
        next.splice(next.indexOf(toId), 0, fromId);
        orderedIds.value = next;
        await saveLayout();
    };

    /**
     * Reload layout when category changes.
     */
    watch(() => props.activeCategoryId, async () => {
        await loadLayout();
    }, { immediate: true });

    onBeforeUnmount(() => {
        if (touchState) clearTimeout(touchState.longPressTimer);
        teardownPointer();
    });
</script>

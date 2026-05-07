<template>
    <div class="grid gap-4 p-4">
        <!-- Form card -->
        <div class="rounded-xl bg-white dark:bg-gray-900 p-4 grid gap-3 shadow-sm">
            <h2 class="font-semibold text-[17px] text-gray-800 dark:text-white">
                {{ $t('pos.cashier.waste.add_entry') }}
            </h2>

            <!-- Product search / barcode -->
            <div class="relative">
                <label class="block text-[12px] text-[#8E8E93] mb-1">
                    {{ $t('pos.cashier.waste.product') }}
                </label>
                <div class="relative flex items-center gap-2">
                    <div class="relative flex-1">
                        <i class="icon-search absolute left-3 top-1/2 -translate-y-1/2 text-[#8E8E93] text-sm pointer-events-none"></i>
                        <input
                            v-model="productQuery"
                            type="text"
                            class="w-full rounded-xl border border-[#E5E5EA] bg-[#F2F2F7] pl-9 pr-3 py-2.5 text-[15px] text-[#1C1C1E] outline-none focus:border-[#00B8D9] focus:bg-white"
                            :placeholder="$t('pos.cashier.waste.product_placeholder')"
                            autocomplete="off"
                            @input="onQueryInput"
                            @focus="showSuggestions = suggestions.length > 0"
                        />
                    </div>
                    <!-- Barcode scan button -->
                    <button
                        type="button"
                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-[#F2F2F7] border border-[#E5E5EA] active:opacity-70"
                        @click="scanBarcode"
                    >
                        <i class="icon-barcode text-[18px] text-[#00B8D9]"></i>
                    </button>
                </div>

                <!-- Suggestions dropdown -->
                <div
                    v-if="showSuggestions && suggestions.length"
                    class="absolute left-0 right-10 z-50 mt-1 max-h-48 overflow-y-auto rounded-xl border border-[#E5E5EA] bg-white shadow-lg"
                >
                    <button
                        v-for="p in suggestions"
                        :key="p.id"
                        type="button"
                        class="flex w-full items-center justify-between px-4 py-2.5 text-left border-b border-[#F2F2F7] last:border-0 active:bg-[#F2F2F7]"
                        @mousedown.prevent="selectProduct(p)"
                        @touchstart.prevent="selectProduct(p)"
                    >
                        <div>
                            <p class="text-[15px] text-[#1C1C1E] font-medium">{{ p.name }}</p>
                            <p class="text-[12px] text-[#8E8E93]">{{ p.sku }}</p>
                        </div>
                        <span class="text-[14px] font-semibold text-[#00B8D9]">¥{{ p.price }}</span>
                    </button>
                </div>
            </div>

            <!-- Category chips (from selected product's categories) -->
            <div v-if="selectedProduct?.categories?.length" class="flex flex-wrap gap-1.5">
                <span
                    v-for="cat in selectedProduct.categories"
                    :key="cat.id"
                    class="rounded-full bg-[#E5F7FB] px-2.5 py-0.5 text-[12px] text-[#00B8D9]"
                >{{ cat.name }}</span>
            </div>

            <!-- Qty + Unit cost -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[12px] text-[#8E8E93] mb-1">{{ $t('pos.cashier.waste.qty') }}</label>
                    <input
                        v-model.number="form.quantity"
                        type="number"
                        min="1"
                        class="w-full rounded-xl border border-[#E5E5EA] bg-[#F2F2F7] px-3 py-2.5 text-[15px] text-[#1C1C1E] outline-none focus:border-[#00B8D9] focus:bg-white text-center"
                    />
                </div>
                <div>
                    <label class="block text-[12px] text-[#8E8E93] mb-1">{{ $t('pos.cashier.waste.unit_cost') }}</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#8E8E93] text-[14px]">¥</span>
                        <input
                            v-model.number="form.unitCost"
                            type="number"
                            min="0"
                            step="0.01"
                            class="w-full rounded-xl border border-[#E5E5EA] bg-[#F2F2F7] pl-7 pr-3 py-2.5 text-[15px] text-[#1C1C1E] outline-none focus:border-[#00B8D9] focus:bg-white text-right"
                        />
                    </div>
                </div>
            </div>

            <!-- Total cost display -->
            <div v-if="form.quantity > 0 && form.unitCost > 0" class="flex justify-between items-center rounded-xl bg-[#F2F2F7] px-4 py-2">
                <span class="text-[13px] text-[#8E8E93]">合計廃棄コスト</span>
                <span class="text-[16px] font-semibold text-[#FF3B30]">¥{{ (form.quantity * form.unitCost).toFixed(0) }}</span>
            </div>

            <!-- Reason chips -->
            <div>
                <label class="block text-[12px] text-[#8E8E93] mb-1.5">{{ $t('pos.cashier.waste.reason') }}</label>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="r in reasons"
                        :key="r"
                        type="button"
                        class="px-3 py-1.5 rounded-full text-[13px] font-medium border transition-colors"
                        :class="form.reason === r
                            ? 'bg-[#00B8D9] text-white border-[#00B8D9]'
                            : 'border-[#E5E5EA] text-[#3C3C43] bg-white'"
                        @click="form.reason = r"
                    >{{ $t('pos.cashier.waste.reasons.' + r) }}</button>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-[12px] text-[#8E8E93] mb-1">{{ $t('pos.cashier.waste.notes') }}</label>
                <textarea
                    v-model="form.notes"
                    rows="2"
                    class="w-full rounded-xl border border-[#E5E5EA] bg-[#F2F2F7] px-3 py-2 text-[15px] text-[#1C1C1E] outline-none focus:border-[#00B8D9] focus:bg-white resize-none"
                />
            </div>

            <p v-if="formError" class="text-[#FF3B30] text-[13px]">{{ formError }}</p>

            <button
                type="button"
                class="w-full rounded-xl bg-[#00B8D9] text-white py-3 text-[15px] font-semibold disabled:opacity-50 active:opacity-80"
                :disabled="submitting || !form.productName"
                @click="submit"
            >
                <span v-if="submitting" class="icon-spinner animate-spin mr-1"></span>
                {{ submitting ? $t('pos.cashier.waste.submitting') : $t('pos.cashier.waste.submit') }}
            </button>
        </div>

        <!-- Recent entries -->
        <div class="rounded-xl bg-white dark:bg-gray-900 p-4 shadow-sm">
            <h2 class="font-semibold text-[15px] text-gray-800 dark:text-white mb-3">
                {{ $t('pos.cashier.waste.recent') }}
            </h2>
            <div v-if="loadingEntries" class="text-center text-[#8E8E93] py-4">
                <span class="icon-spinner animate-spin text-2xl text-[#00B8D9]"></span>
            </div>
            <div v-else-if="!entries.length" class="text-center text-[#8E8E93] py-4 text-[14px]">
                {{ $t('pos.cashier.waste.no_entries') }}
            </div>
            <ul v-else class="divide-y divide-[#F2F2F7]">
                <li v-for="e in entries" :key="e.id" class="py-3 flex justify-between items-start gap-2">
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-[15px] text-[#1C1C1E] truncate">{{ e.productName }}</p>
                        <p class="text-[12px] text-[#8E8E93] mt-0.5">
                            {{ $t('pos.cashier.waste.reasons.' + e.reason) }} · ×{{ e.quantity }}
                            <template v-if="e.totalCost"> · ¥{{ e.totalCost }}</template>
                        </p>
                        <p class="text-[11px] text-[#C7C7CC] mt-0.5">{{ e.wastedAt }}</p>
                    </div>
                    <span
                        class="flex-shrink-0 text-[11px] px-2 py-0.5 rounded-full font-medium"
                        :class="{
                            'bg-yellow-100 text-yellow-700': e.status === 'pending',
                            'bg-green-100 text-green-700':  e.status === 'verified',
                            'bg-red-100 text-red-700':      e.status === 'rejected',
                        }"
                    >{{ e.status }}</span>
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useMutation, useQuery } from '@vue/apollo-composable';
import { useIndexedDB } from '@src/composable/indexed-db';
import { RECORD_WASTE, GET_WASTE_ENTRIES } from '@graphql/waste.js';

const DB = useIndexedDB();

const reasons = ['damaged', 'expired', 'theft', 'spoilage', 'other'];

// ── Product search ────────────────────────────────────────────────────────
const allProducts    = ref([]);
const productQuery   = ref('');
const showSuggestions = ref(false);
const selectedProduct = ref(null);

onMounted(async () => {
    allProducts.value = await DB.getAllItems('products');
});

const suggestions = computed(() => {
    const q = productQuery.value.trim().toLowerCase();
    if (!q) return [];
    return allProducts.value.filter(p =>
        p.name?.toLowerCase().includes(q) ||
        p.sku?.toLowerCase().includes(q) ||
        p.barcode?.toLowerCase().includes(q) ||
        p.gtin?.toLowerCase().includes(q)
    ).slice(0, 8);
});

function onQueryInput() {
    showSuggestions.value = suggestions.value.length > 0;
    if (!productQuery.value) {
        selectedProduct.value = null;
        form.value.productName = '';
        form.value.unitCost = 0;
    } else {
        form.value.productName = productQuery.value;
    }
}

function selectProduct(p) {
    selectedProduct.value = p;
    productQuery.value     = p.name;
    form.value.productName = p.name;
    form.value.unitCost    = parseFloat(p.price ?? 0);
    showSuggestions.value  = false;
}

// Barcode scan: scan first char sequence → match product
function scanBarcode() {
    const raw = window.prompt('バーコードを入力してください');
    if (!raw) return;
    const hit = allProducts.value.find(p =>
        p.scanValue === raw || p.gtin === raw || p.barcode === raw || p.sku === raw
    );
    if (hit) {
        selectProduct(hit);
    } else {
        productQuery.value = raw;
        form.value.productName = raw;
    }
}

// ── Form ──────────────────────────────────────────────────────────────────
const form = ref({
    productName: '',
    quantity: 1,
    unitCost: 0,
    reason: 'damaged',
    notes: '',
});
const submitting  = ref(false);
const formError   = ref('');

// ── Recent entries ────────────────────────────────────────────────────────
const entries        = ref([]);
const loadingEntries = ref(true);

const { onResult, refetch } = useQuery(GET_WASTE_ENTRIES);
onResult(({ data }) => {
    entries.value        = data?.getWasteEntries ?? [];
    loadingEntries.value = false;
});

// ── Submit ────────────────────────────────────────────────────────────────
const { mutate: recordWaste } = useMutation(RECORD_WASTE);

async function submit() {
    formError.value = '';
    if (!form.value.productName || !form.value.reason) {
        formError.value = '商品名と理由は必須です。';
        return;
    }
    submitting.value = true;
    try {
        const { data } = await recordWaste({
            input: {
                productName: form.value.productName,
                quantity:    form.value.quantity,
                unitCost:    form.value.unitCost,
                reason:      form.value.reason,
                notes:       form.value.notes || null,
            },
        });
        if (data?.recordWaste?.success) {
            form.value = { productName: '', quantity: 1, unitCost: 0, reason: 'damaged', notes: '' };
            productQuery.value   = '';
            selectedProduct.value = null;
            await refetch();
        } else {
            formError.value = data?.recordWaste?.errors ?? 'エラーが発生しました';
        }
    } catch (e) {
        formError.value = e.message;
    } finally {
        submitting.value = false;
    }
}

onMounted(() => refetch());
</script>

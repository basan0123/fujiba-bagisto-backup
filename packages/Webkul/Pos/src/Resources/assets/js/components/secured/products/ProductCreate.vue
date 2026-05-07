<template>
    <div class="flex flex-col bg-[#F2F2F7]" style="min-height: calc(100vh - 44px);">

        <!-- ── Content ── -->
        <div class="flex-1 overflow-y-auto px-4 py-4 pb-28">

            <!-- Name -->
            <div class="pos-section-header mb-1 text-[13px] font-medium text-[#6C6C70] uppercase tracking-wide">
                {{ $t('pos.layout.header.product_create_form.name') }}
            </div>

            <div class="mb-4 rounded-xl bg-white px-4 py-3">
                <input
                    v-model="form.name"
                    type="text"
                    class="w-full bg-transparent text-[17px] text-[#1C1C1E] outline-none placeholder-[#C7C7CC]"
                    :placeholder="$t('pos.layout.header.product_create_form.name')"
                    autofocus
                />
                <p v-if="errors.name" class="mt-1 text-[12px] text-[#FF3B30]">{{ errors.name }}</p>
            </div>

            <!-- SKU (optional) -->
            <div class="pos-section-header mb-1 text-[13px] font-medium text-[#6C6C70] uppercase tracking-wide">
                {{ $t('pos.layout.header.product_create_form.sku') }}
            </div>

            <div class="mb-4 rounded-xl bg-white px-4 py-3">
                <input
                    v-model="form.sku"
                    type="text"
                    class="w-full bg-transparent text-[17px] text-[#1C1C1E] outline-none placeholder-[#C7C7CC]"
                    :placeholder="$t('pos.products.create.sku_placeholder')"
                />
                <p class="mt-1 text-[12px] text-[#8E8E93]">{{ $t('pos.products.create.sku_helper') }}</p>
            </div>

            <!-- Price -->
            <div class="pos-section-header mb-1 text-[13px] font-medium text-[#6C6C70] uppercase tracking-wide">
                {{ $t('pos.layout.header.product_create_form.price') }}
            </div>

            <div class="mb-4 rounded-xl bg-white px-4 py-3">
                <input
                    v-model="form.price"
                    type="number"
                    inputmode="decimal"
                    min="0"
                    step="0.01"
                    class="w-full bg-transparent text-[17px] text-[#1C1C1E] outline-none placeholder-[#C7C7CC]"
                    placeholder="0.00"
                />
                <p v-if="errors.price" class="mt-1 text-[12px] text-[#FF3B30]">{{ errors.price }}</p>
            </div>

            <!-- Quantity -->
            <div class="pos-section-header mb-1 text-[13px] font-medium text-[#6C6C70] uppercase tracking-wide">
                {{ $t('pos.layout.header.product_create_form.quantity') }}
            </div>

            <div class="mb-4 rounded-xl bg-white px-4 py-3">
                <input
                    v-model="form.quantity"
                    type="number"
                    inputmode="numeric"
                    min="0"
                    step="1"
                    class="w-full bg-transparent text-[17px] text-[#1C1C1E] outline-none placeholder-[#C7C7CC]"
                    placeholder="0"
                />
                <p v-if="errors.quantity" class="mt-1 text-[12px] text-[#FF3B30]">{{ errors.quantity }}</p>
            </div>

            <!-- Weight -->
            <div class="pos-section-header mb-1 text-[13px] font-medium text-[#6C6C70] uppercase tracking-wide">
                {{ $t('pos.layout.header.product_create_form.weight') }}
            </div>

            <div class="mb-4 rounded-xl bg-white px-4 py-3">
                <input
                    v-model="form.weight"
                    type="number"
                    inputmode="decimal"
                    min="0"
                    step="0.01"
                    class="w-full bg-transparent text-[17px] text-[#1C1C1E] outline-none placeholder-[#C7C7CC]"
                    placeholder="0.00"
                />
                <p v-if="errors.weight" class="mt-1 text-[12px] text-[#FF3B30]">{{ errors.weight }}</p>
            </div>
        </div>

        <!-- ── Fixed bottom bar ── -->
        <div
            class="fixed inset-x-0 bottom-0 z-40 flex items-center gap-3 border-t border-[#E5E5EA] bg-white px-4"
            style="height:60px; padding-bottom: env(safe-area-inset-bottom, 0px);"
        >
            <button
                type="button"
                class="flex-1 rounded-xl border border-[#E5E5EA] bg-[#F2F2F7] py-3 text-[15px] font-medium text-[#1C1C1E] active:opacity-70"
                @click="$router.back()"
            >
                {{ $t('pos.layout.header.product_create_form.cancel_btn_title') }}
            </button>

            <button
                type="button"
                class="flex-1 rounded-xl bg-[#00B8D9] py-3 text-[15px] font-semibold text-white active:opacity-70 disabled:opacity-50"
                :disabled="isCreating"
                @click="submitForm"
            >
                <span v-if="isCreating" class="icon-spinner mr-1.5 animate-spin text-sm"></span>
                {{ $t('pos.layout.header.product_create_form.proceed_btn_title') }}
            </button>
        </div>
    </div>
</template>

<script setup>
    import { ref, inject } from 'vue';
    import { useRouter } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import { useMutation } from '@vue/apollo-composable';
    import { useIndexedDB } from '@src/composable/indexed-db';
    import { CREATE_PRODUCT } from '@src/graphql/products';

    const router  = useRouter();
    const { t }   = useI18n();
    const emitter = inject('emitter');
    const isOnline = inject('isOnline');
    const DB      = useIndexedDB();

    const form = ref({
        name: '',
        sku: '',
        price: '',
        quantity: '',
        weight: '',
    });

    const errors = ref({});

    const { mutate: createProduct, loading: isCreating } = useMutation(CREATE_PRODUCT);

    const submitForm = async () => {
        errors.value = {};

        if (! form.value.name) {
            errors.value.name = t('pos.products.create.error_name_required');
            return;
        }

        if (form.value.price === '' || isNaN(parseFloat(form.value.price))) {
            errors.value.price = t('pos.products.create.error_price_required');
            return;
        }

        if (form.value.quantity === '' || isNaN(parseInt(form.value.quantity))) {
            errors.value.quantity = t('pos.products.create.error_quantity_required');
            return;
        }

        if (form.value.weight === '' || isNaN(parseFloat(form.value.weight))) {
            errors.value.weight = t('pos.products.create.error_weight_required');
            return;
        }

        if (! isOnline.value) {
            emitter.emit('add_flash', {
                type: 'error',
                message: t('pos.common.flash_messages.offline_error'),
            });
            return;
        }

        try {
            const response = await createProduct({ input: {
                name:     form.value.name,
                sku:      form.value.sku || '',
                price:    parseFloat(form.value.price),
                quantity: parseInt(form.value.quantity),
                weight:   parseFloat(form.value.weight),
            }});

            const result = response?.data?.createOutletProduct;

            if (result?.success) {
                emitter.emit('add_flash', {
                    type: 'success',
                    message: result.message,
                });

                if (result.product) {
                    await DB.addItem('products', result.product);
                    emitter.emit('new_product', result.product);
                }

                router.push('/products/list');
            } else {
                if (result?.errors) {
                    try {
                        errors.value = JSON.parse(result.errors);
                    } catch {
                        emitter.emit('add_flash', { type: 'error', message: result.errors });
                    }
                }
            }
        } catch (err) {
            emitter.emit('add_flash', {
                type: 'error',
                message: err.message ?? t('pos.products.create.error_generic'),
            });
        }
    };
</script>

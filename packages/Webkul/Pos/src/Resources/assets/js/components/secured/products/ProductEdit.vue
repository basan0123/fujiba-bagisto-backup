<template>
    <div class="flex flex-col bg-[#F2F2F7]" style="min-height: calc(100vh - 44px);">

        <!-- Loading state -->
        <div v-if="loading" class="flex flex-1 items-center justify-center py-16">
            <span class="icon-spinner animate-spin text-3xl text-[#00B8D9]"></span>
        </div>

        <!-- Product not found -->
        <div v-else-if="!product" class="flex flex-1 items-center justify-center py-16">
            <p class="text-[17px] text-[#8E8E93]">{{ $t('pos.products.edit.not_found') }}</p>
        </div>

        <!-- Edit form -->
        <template v-else>
            <div class="flex-1 overflow-y-auto px-4 py-4 pb-28">

                <!-- Image -->
                <div class="pos-section-header mb-1 text-[13px] font-medium text-[#6C6C70] uppercase tracking-wide">
                    {{ $t('pos.products.edit.image') }}
                </div>

                <div class="mb-4 rounded-xl bg-white px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="relative h-20 w-20 flex-shrink-0 overflow-hidden rounded-lg border border-[#E5E5EA] bg-[#F2F2F7]">
                            <img
                                v-if="primaryImageSrc"
                                :src="primaryImageSrc"
                                class="absolute inset-0 h-full w-full object-cover"
                                :alt="product.name"
                            />
                            <div
                                v-else
                                class="absolute inset-0 flex items-center justify-center text-[28px] text-[#C7C7CC]"
                            >
                                <i class="icon-image"></i>
                            </div>
                        </div>

                        <div class="flex flex-1 flex-col gap-2">
                            <div class="flex gap-2">
                                <label
                                    for="product-image-camera"
                                    class="flex-1 cursor-pointer rounded-lg border border-[#00B8D9] bg-white px-3 py-2 text-center text-[14px] font-medium text-[#00B8D9] active:opacity-70"
                                    :class="isImageBusy ? 'pointer-events-none opacity-50' : ''"
                                >
                                    {{ $t('pos.products.edit.image_camera') }}
                                </label>

                                <label
                                    for="product-image-input"
                                    class="flex-1 cursor-pointer rounded-lg border border-[#00B8D9] bg-white px-3 py-2 text-center text-[14px] font-medium text-[#00B8D9] active:opacity-70"
                                    :class="isImageBusy ? 'pointer-events-none opacity-50' : ''"
                                >
                                    <i class="icon-image mr-1"></i>
                                    {{ primaryImageSrc
                                        ? $t('pos.products.edit.image_change')
                                        : $t('pos.products.edit.image_add') }}
                                </label>
                            </div>

                            <input
                                id="product-image-input"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="onImageSelected"
                            />

                            <input
                                id="product-image-camera"
                                type="file"
                                accept="image/*"
                                capture="environment"
                                class="hidden"
                                @change="onImageSelected"
                            />

                            <button
                                v-if="primaryImageSrc"
                                type="button"
                                class="rounded-lg border border-[#FF3B30] bg-white px-3 py-2 text-[14px] font-medium text-[#FF3B30] active:opacity-70 disabled:opacity-50"
                                :disabled="isImageBusy"
                                @click="removeImage"
                            >
                                <i class="icon-delete mr-1"></i>
                                {{ $t('pos.products.edit.image_remove') }}
                            </button>
                        </div>
                    </div>

                    <p
                        v-if="isImageBusy"
                        class="mt-2 flex items-center gap-1 text-[12px] text-[#8E8E93]"
                    >
                        <span class="icon-spinner animate-spin"></span>
                        ...
                    </p>
                </div>

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

                <!-- SKU (read-only info) -->
                <div class="pos-section-header mb-1 text-[13px] font-medium text-[#6C6C70] uppercase tracking-wide">
                    {{ $t('pos.layout.header.product_create_form.sku') }}
                </div>

                <div class="mb-4 rounded-xl bg-white px-4 py-3">
                    <p class="text-[17px] text-[#8E8E93]">{{ product.sku || '—' }}</p>
                </div>

                <!-- Barcode (editable; persists via linkBarcodeToProduct) -->
                <div class="pos-section-header mb-1 text-[13px] font-medium text-[#6C6C70] uppercase tracking-wide">
                    {{ $t('pos.layout.header.product_create_form.barcode_label') }}
                </div>

                <div class="mb-4 rounded-xl bg-white px-4 py-3">
                    <input
                        v-model="form.barcode"
                        type="text"
                        inputmode="text"
                        autocomplete="off"
                        class="w-full bg-transparent text-[17px] text-[#1C1C1E] outline-none placeholder-[#C7C7CC]"
                        :placeholder="$t('pos.products.edit.barcode_placeholder')"
                    />
                    <p v-if="errors.barcode" class="mt-1 text-[12px] text-[#FF3B30]">{{ errors.barcode }}</p>
                </div>

                <!-- Categories (read-only info) -->
                <div
                    v-if="product.categories && product.categories.length"
                    class="mb-4"
                >
                    <div class="pos-section-header mb-1 text-[13px] font-medium text-[#6C6C70] uppercase tracking-wide">
                        {{ $t('pos.products.edit.categories') }}
                    </div>

                    <div class="rounded-xl bg-white px-4 py-3">
                        <p class="text-[17px] text-[#1C1C1E]">
                            {{ product.categories.map(c => c.name).join(', ') }}
                        </p>
                    </div>
                </div>

                <!-- Stock (read-only info) -->
                <div class="pos-section-header mb-1 text-[13px] font-medium text-[#6C6C70] uppercase tracking-wide">
                    {{ $t('pos.products.edit.stock') }}
                </div>

                <div class="mb-4 rounded-xl bg-white px-4 py-3">
                    <p class="text-[17px] text-[#1C1C1E]">{{ product.quantity ?? '—' }}</p>
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
                    :class="!isDirty || isSaving ? 'opacity-50 pointer-events-none' : ''"
                    :disabled="!isDirty || isSaving"
                    @click="saveChanges"
                >
                    <span v-if="isSaving" class="icon-spinner mr-1.5 animate-spin text-sm"></span>
                    {{ $t('pos.products.list.save_success_btn') }}
                </button>
            </div>
        </template>
    </div>
</template>

<script setup>
    import { ref, computed, onMounted, inject } from 'vue';
    import { useRouter } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import { useMutation } from '@vue/apollo-composable';
    import { useIndexedDB } from '@src/composable/indexed-db';
    import { BULK_UPDATE_PRODUCTS, SET_PRODUCT_IMAGE, DELETE_PRODUCT_IMAGE, LINK_BARCODE_TO_PRODUCT } from '@src/graphql/products';

    const props = defineProps({
        id: {
            type: String,
            required: true,
        },
    });

    const router   = useRouter();
    const { t }    = useI18n();
    const emitter  = inject('emitter');
    const isOnline = inject('isOnline');
    const DB       = useIndexedDB();

    const loading = ref(true);
    const product = ref(null);
    const isSaving = ref(false);
    const isImageBusy = ref(false);
    const errors = ref({});

    const form = ref({ name: '', price: '', barcode: '' });

    // Track the original barcode so we only fire linkBarcodeToProduct when changed.
    const originalBarcode = ref('');

    /**
     * Resolve the URL for the first image — handles both the IndexedDB shape
     * (images: [{ url }]) and the freshly mutated GraphQL response.
     */
    const primaryImageSrc = computed(() => {
        const imgs = product.value?.images;
        if (! imgs || ! imgs.length) return '';
        return imgs[0].url || '';
    });

    const isDirty = computed(() => {
        if (!product.value) return false;
        return form.value.name !== product.value.name ||
               String(form.value.price) !== String(product.value.price) ||
               (form.value.barcode || '').trim() !== (originalBarcode.value || '').trim();
    });

    onMounted(async () => {
        try {
            const productId = parseInt(props.id);
            const p = await DB.getItem('products', productId);
            if (p) {
                product.value = p;
                form.value.name  = p.name;
                form.value.price = p.price;
                // gtin is the canonical barcode; scanValue may equal SKU when no
                // explicit barcode is set, so prefer gtin and fall back to a
                // scanValue that differs from the SKU.
                const initialBarcode = p.gtin
                    || ((p.scanValue && p.scanValue !== p.sku) ? p.scanValue : '');
                form.value.barcode  = initialBarcode || '';
                originalBarcode.value = initialBarcode || '';
            }
        } catch (err) {
            // not found
        } finally {
            loading.value = false;
        }
    });

    const { mutate: bulkUpdate } = useMutation(BULK_UPDATE_PRODUCTS);
    const { mutate: setImageMutation } = useMutation(SET_PRODUCT_IMAGE);
    const { mutate: deleteImageMutation } = useMutation(DELETE_PRODUCT_IMAGE);
    const { mutate: linkBarcodeMutation } = useMutation(LINK_BARCODE_TO_PRODUCT);

    // Canvas-downscale any browser-decodable image to JPEG ≤ 1600px longest edge.
    // Phone cameras often shoot 8–12MB HEIC/JPEG; this keeps uploads under ~400KB
    // and sidesteps server-side memory limits while still looking sharp on POS.
    const downscaleImage = (file) => new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const img = new Image();

        img.onload = () => {
            try {
                const MAX = 1600;
                let { width, height } = img;

                if (width > MAX || height > MAX) {
                    if (width >= height) {
                        height = Math.round(height * (MAX / width));
                        width  = MAX;
                    } else {
                        width  = Math.round(width * (MAX / height));
                        height = MAX;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width  = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                const dataUrl = canvas.toDataURL('image/jpeg', 0.85);

                URL.revokeObjectURL(url);
                resolve(dataUrl);
            } catch (e) {
                URL.revokeObjectURL(url);
                reject(e);
            }
        };

        img.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('decode_failed'));
        };

        img.src = url;
    });

    const onImageSelected = async (event) => {
        const file = event.target.files?.[0];

        // Reset the input so picking the same file again still triggers change.
        event.target.value = '';

        if (! file) return;

        if (! file.type.startsWith('image/')) {
            emitter.emit('add_flash', {
                type: 'error',
                message: t('pos.products.edit.image_invalid'),
            });
            return;
        }

        isImageBusy.value = true;

        try {
            const dataUrl  = await downscaleImage(file);
            const safeName = (file.name || 'photo.jpg').replace(/\.(heic|heif|png|webp|gif|bmp)$/i, '.jpg');

            await uploadImage(dataUrl, safeName);
        } catch (err) {
            isImageBusy.value = false;
            emitter.emit('add_flash', {
                type: 'error',
                message: t('pos.products.edit.image_invalid'),
            });
        }
    };

    // IDB structured-clone rejects Apollo cache references / Vue proxies wrapping
    // arrays. Round-tripping through JSON gives us a plain, cloneable copy.
    const toPlain = (obj) => JSON.parse(JSON.stringify(obj));

    const uploadImage = async (dataUrl, name) => {
        if (! isOnline?.value) {
            isImageBusy.value = false;
            emitter.emit('add_flash', {
                type: 'error',
                message: t('pos.common.flash_messages.offline_error'),
            });
            return;
        }

        isImageBusy.value = true;

        try {
            const response = await setImageMutation({
                input: {
                    productId:   product.value.id,
                    imageBase64: dataUrl,
                    imageName:   name,
                },
            });

            const result = response?.data?.setOutletProductImage;

            if (result?.success) {
                // Patch local copy + IDB so list view shows the new thumb immediately.
                const updated = toPlain({ ...product.value, images: result.product?.images || [] });
                product.value = updated;
                await DB.updateItem('products', updated);

                emitter.emit('add_flash', {
                    type: 'success',
                    message: t('pos.products.edit.image_updated'),
                });
            } else {
                emitter.emit('add_flash', {
                    type: 'error',
                    message: result?.message || t('pos.products.edit.image_error'),
                });
            }
        } catch (err) {
            emitter.emit('add_flash', {
                type: 'error',
                message: err?.message || t('pos.products.edit.image_error'),
            });
        } finally {
            isImageBusy.value = false;
        }
    };

    const removeImage = async () => {
        if (! isOnline?.value) {
            emitter.emit('add_flash', {
                type: 'error',
                message: t('pos.common.flash_messages.offline_error'),
            });
            return;
        }

        if (! window.confirm(t('pos.products.edit.image_remove_confirm'))) {
            return;
        }

        isImageBusy.value = true;

        try {
            const response = await deleteImageMutation({
                input: { productId: product.value.id },
            });

            const result = response?.data?.deleteOutletProductImage;

            if (result?.success) {
                const updated = toPlain({ ...product.value, images: [] });
                product.value = updated;
                await DB.updateItem('products', updated);

                emitter.emit('add_flash', {
                    type: 'success',
                    message: t('pos.products.edit.image_removed'),
                });
            } else {
                emitter.emit('add_flash', {
                    type: 'error',
                    message: result?.message || t('pos.products.edit.image_error'),
                });
            }
        } catch (err) {
            emitter.emit('add_flash', {
                type: 'error',
                message: err?.message || t('pos.products.edit.image_error'),
            });
        } finally {
            isImageBusy.value = false;
        }
    };

    const saveChanges = async () => {
        errors.value = {};

        if (!form.value.name) {
            errors.value.name = t('pos.products.create.error_name_required');
            return;
        }

        if (isNaN(parseFloat(form.value.price))) {
            errors.value.price = t('pos.products.create.error_price_required');
            return;
        }

        const newBarcode      = (form.value.barcode || '').trim();
        const barcodeChanged  = newBarcode !== (originalBarcode.value || '').trim();
        const nameOrPriceChanged = form.value.name !== product.value.name
            || String(form.value.price) !== String(product.value.price);

        isSaving.value = true;

        try {
            let updated = { ...product.value };

            if (nameOrPriceChanged) {
                const response = await bulkUpdate({
                    input: {
                        products: [{
                            id:    product.value.id,
                            name:  form.value.name,
                            price: parseFloat(form.value.price),
                        }],
                    },
                });

                const result = response?.data?.bulkUpdateOutletProducts;

                if (! result?.success) {
                    emitter.emit('add_flash', {
                        type: 'error',
                        message: result?.message ?? t('pos.products.list.save_error'),
                    });
                    isSaving.value = false;
                    return;
                }

                updated = { ...updated, name: form.value.name, price: parseFloat(form.value.price) };
            }

            if (barcodeChanged) {
                if (! isOnline?.value) {
                    emitter.emit('add_flash', {
                        type: 'error',
                        message: t('pos.common.flash_messages.offline_error'),
                    });
                    isSaving.value = false;
                    return;
                }

                if (! newBarcode) {
                    // Clearing a barcode isn't supported by linkBarcodeToProduct;
                    // surface that to the cashier rather than silently no-op.
                    errors.value.barcode = t('pos.products.edit.barcode_required');
                    isSaving.value = false;
                    return;
                }

                const linkResp = await linkBarcodeMutation({
                    input: {
                        productId: parseInt(product.value.id, 10),
                        barcode:   newBarcode,
                    },
                });

                const linkResult = linkResp?.data?.linkBarcodeToProduct;

                if (! linkResult?.success) {
                    emitter.emit('add_flash', {
                        type: 'error',
                        message: linkResult?.message ?? t('pos.products.edit.barcode_save_error'),
                    });
                    isSaving.value = false;
                    return;
                }

                updated.gtin      = linkResult.gtin      ?? newBarcode;
                updated.scanValue = linkResult.scanValue ?? newBarcode;
                originalBarcode.value = updated.gtin || newBarcode;
            }

            const plainUpdated = toPlain(updated);
            await DB.updateItem('products', plainUpdated);
            product.value = plainUpdated;

            emitter.emit('add_flash', {
                type: 'success',
                message: t('pos.products.list.save_success'),
            });

            router.back();
        } catch (err) {
            emitter.emit('add_flash', {
                type: 'error',
                message: t('pos.products.list.save_error'),
            });
        } finally {
            isSaving.value = false;
        }
    };
</script>

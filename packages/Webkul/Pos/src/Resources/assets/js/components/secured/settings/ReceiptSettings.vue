<template>
    <!-- Scrollable body with bottom padding for fixed bar -->
    <div class="min-h-screen bg-[#F2F2F7] pb-24">

        <!-- Logo section -->
        <div class="pos-section-header mt-4 px-4">
            {{ t('pos.settings.receipt.section_logo') }}
        </div>

        <div class="bg-white">
            <div class="pos-list-row px-4 py-4">
                <p class="mb-1 text-[13px] font-medium text-[#1C1C1E]">
                    {{ t('pos.settings.receipt.logo_label') }}
                </p>

                <div class="flex items-center gap-3">
                    <!-- Hidden file input -->
                    <input
                        ref="fileInputRef"
                        type="file"
                        accept="image/*"
                        class="hidden"
                        @change="onLogoFileChange"
                    />

                    <!-- Trigger button -->
                    <button
                        type="button"
                        class="rounded-lg border border-[#E5E5EA] bg-[#F2F2F7] px-4 py-2 text-[15px] font-medium text-[#1C1C1E] active:opacity-70"
                        @click="fileInputRef.click()"
                    >
                        {{ t('pos.settings.receipt.logo_choose_file') }}
                    </button>

                    <!-- Filename preview -->
                    <span class="truncate text-[14px] text-[#8E8E93]">
                        {{ logoFileName || t('pos.settings.receipt.logo_no_file') }}
                    </span>
                </div>

                <!-- Logo preview thumbnail -->
                <div
                    v-if="logoPreviewUrl"
                    class="mt-3"
                >
                    <img
                        :src="logoPreviewUrl"
                        alt="Logo preview"
                        class="h-16 w-auto rounded-lg border border-[#E5E5EA] object-contain"
                    />
                </div>

                <p class="mt-1 text-[12px] text-[#8E8E93]">
                    {{ t('pos.settings.receipt.logo_helper') }}
                </p>
            </div>
        </div>

        <!-- Store info section -->
        <div class="pos-section-header mt-6 px-4">
            {{ t('pos.settings.receipt.section_store_info') }}
        </div>

        <div class="bg-white">
            <!-- Store name -->
            <div class="pos-list-row px-4 py-4">
                <label
                    for="receipt-store-name"
                    class="mb-1 block text-[13px] font-medium text-[#1C1C1E]"
                >
                    {{ t('pos.settings.receipt.store_name_label') }}
                </label>

                <textarea
                    id="receipt-store-name"
                    v-model="form.storeName"
                    rows="3"
                    class="w-full rounded-lg border border-[#E5E5EA] bg-white px-3 py-2 text-[15px] text-[#1C1C1E] placeholder-[#8E8E93] outline-none focus:border-[#00B8D9] focus:ring-1 focus:ring-[#00B8D9]"
                    :placeholder="t('pos.settings.receipt.store_name_placeholder')"
                ></textarea>

                <p class="mt-1 text-[12px] text-[#8E8E93]">
                    {{ t('pos.settings.receipt.store_name_helper') }}
                </p>
            </div>

            <!-- Address line 1 -->
            <div class="pos-list-row px-4 py-4">
                <label
                    for="receipt-address1"
                    class="mb-1 block text-[13px] font-medium text-[#1C1C1E]"
                >
                    {{ t('pos.settings.receipt.address1_label') }}
                </label>

                <input
                    id="receipt-address1"
                    v-model="form.address1"
                    type="text"
                    class="w-full rounded-lg border border-[#E5E5EA] bg-white px-3 py-2 text-[15px] text-[#1C1C1E] placeholder-[#8E8E93] outline-none focus:border-[#00B8D9] focus:ring-1 focus:ring-[#00B8D9]"
                    :placeholder="t('pos.settings.receipt.address1_placeholder')"
                />

                <p class="mt-1 text-[12px] text-[#8E8E93]">
                    {{ t('pos.settings.receipt.address1_helper') }}
                </p>
            </div>

            <!-- Address line 2 -->
            <div class="pos-list-row px-4 py-4">
                <label
                    for="receipt-address2"
                    class="mb-1 block text-[13px] font-medium text-[#1C1C1E]"
                >
                    {{ t('pos.settings.receipt.address2_label') }}
                </label>

                <input
                    id="receipt-address2"
                    v-model="form.address2"
                    type="text"
                    class="w-full rounded-lg border border-[#E5E5EA] bg-white px-3 py-2 text-[15px] text-[#1C1C1E] placeholder-[#8E8E93] outline-none focus:border-[#00B8D9] focus:ring-1 focus:ring-[#00B8D9]"
                    :placeholder="t('pos.settings.receipt.address2_placeholder')"
                />

                <p class="mt-1 text-[12px] text-[#8E8E93]">
                    {{ t('pos.settings.receipt.address2_helper') }}
                </p>
            </div>

            <!-- Phone -->
            <div class="pos-list-row px-4 py-4">
                <label
                    for="receipt-phone"
                    class="mb-1 block text-[13px] font-medium text-[#1C1C1E]"
                >
                    {{ t('pos.settings.receipt.phone_label') }}
                </label>

                <input
                    id="receipt-phone"
                    v-model="form.phone"
                    type="text"
                    inputmode="tel"
                    class="w-full rounded-lg border border-[#E5E5EA] bg-white px-3 py-2 text-[15px] text-[#1C1C1E] placeholder-[#8E8E93] outline-none focus:border-[#00B8D9] focus:ring-1 focus:ring-[#00B8D9]"
                    :placeholder="t('pos.settings.receipt.phone_placeholder')"
                />

                <p class="mt-1 text-[12px] text-[#8E8E93]">
                    {{ t('pos.settings.receipt.phone_helper') }}
                </p>
            </div>

            <!-- URL -->
            <div class="pos-list-row px-4 py-4">
                <label
                    for="receipt-url"
                    class="mb-1 block text-[13px] font-medium text-[#1C1C1E]"
                >
                    {{ t('pos.settings.receipt.url_label') }}
                </label>

                <input
                    id="receipt-url"
                    v-model="form.url"
                    type="url"
                    inputmode="url"
                    class="w-full rounded-lg border border-[#E5E5EA] bg-white px-3 py-2 text-[15px] text-[#1C1C1E] placeholder-[#8E8E93] outline-none focus:border-[#00B8D9] focus:ring-1 focus:ring-[#00B8D9]"
                    :placeholder="t('pos.settings.receipt.url_placeholder')"
                />

                <p class="mt-1 text-[12px] text-[#8E8E93]">
                    {{ t('pos.settings.receipt.url_helper') }}
                </p>
            </div>
        </div>

        <!-- Footer section -->
        <div class="pos-section-header mt-6 px-4">
            {{ t('pos.settings.receipt.section_footer') }}
        </div>

        <div class="bg-white">
            <div class="pos-list-row px-4 py-4">
                <label
                    for="receipt-footer"
                    class="mb-1 block text-[13px] font-medium text-[#1C1C1E]"
                >
                    {{ t('pos.settings.receipt.footer_label') }}
                </label>

                <textarea
                    id="receipt-footer"
                    v-model="form.footerMessage"
                    rows="4"
                    class="w-full rounded-lg border border-[#E5E5EA] bg-white px-3 py-2 text-[15px] text-[#1C1C1E] placeholder-[#8E8E93] outline-none focus:border-[#00B8D9] focus:ring-1 focus:ring-[#00B8D9]"
                    :placeholder="t('pos.settings.receipt.footer_placeholder')"
                ></textarea>

                <p class="mt-1 text-[12px] text-[#8E8E93]">
                    {{ t('pos.settings.receipt.footer_helper') }}
                </p>
            </div>
        </div>

        <!-- Preview section -->
        <div class="pos-section-header mt-6 px-4">
            {{ t('pos.settings.receipt.section_preview') }}
        </div>

        <div class="bg-white px-4 py-4">
            <p class="mb-3 text-[12px] text-[#8E8E93]">
                {{ t('pos.settings.receipt.preview_helper') }}
            </p>

            <!-- Tab switcher -->
            <div class="mb-4 inline-flex rounded-lg bg-[#F2F2F7] p-1">
                <button
                    type="button"
                    class="rounded-md px-4 py-1.5 text-[13px] font-medium transition"
                    :class="previewMode === 'standard' ? 'bg-white text-[#1C1C1E] shadow-sm' : 'text-[#8E8E93]'"
                    @click="previewMode = 'standard'"
                >
                    {{ t('pos.settings.receipt.preview_tab_standard') }}
                </button>
                <button
                    type="button"
                    class="rounded-md px-4 py-1.5 text-[13px] font-medium transition"
                    :class="previewMode === 'ryoshuho' ? 'bg-white text-[#1C1C1E] shadow-sm' : 'text-[#8E8E93]'"
                    @click="previewMode = 'ryoshuho'"
                >
                    {{ t('pos.settings.receipt.preview_tab_ryoshuho') }}
                </button>
            </div>

            <div class="flex justify-center">
                <div class="receipt-preview">
                    <!-- Ryoshuho heading replaces logo + store name -->
                    <template v-if="previewMode === 'ryoshuho'">
                        <div class="receipt-preview__ryoshuho-title">領収証</div>
                        <div class="receipt-preview__ryoshuho-subtitle">（正式領収書）</div>
                    </template>

                    <!-- Standard receipt: logo + store name -->
                    <template v-else>
                        <div
                            v-if="logoPreviewUrl"
                            class="receipt-preview__logo"
                        >
                            <img
                                :src="logoPreviewUrl"
                                alt="Logo"
                            />
                        </div>

                        <div
                            v-if="form.storeName"
                            class="receipt-preview__store-name"
                        >{{ form.storeName }}</div>
                    </template>

                    <div
                        v-if="form.address1 || form.address2 || form.phone || form.url"
                        class="receipt-preview__store-info"
                    >
                        <div v-if="form.address1">{{ form.address1 }}</div>
                        <div v-if="form.address2">{{ form.address2 }}</div>
                        <div v-if="form.phone">TEL: {{ form.phone }}</div>
                        <div v-if="form.url">{{ form.url }}</div>
                    </div>

                    <!-- Ryoshuho: addressee line -->
                    <div
                        v-if="previewMode === 'ryoshuho'"
                        class="receipt-preview__addressee"
                    >
                        <div class="receipt-preview__addressee-name">_______________ {{ t('pos.settings.receipt.preview_addressee_suffix') }}</div>
                        <div class="receipt-preview__addressee-amount">¥1,485 -</div>
                        <div class="receipt-preview__addressee-tax">{{ t('pos.settings.receipt.preview_tax_included') }}</div>
                        <div class="receipt-preview__addressee-note">{{ t('pos.settings.receipt.preview_addressee_note') }}</div>
                    </div>

                    <div class="receipt-preview__date">{{ previewDate }}</div>
                    <div class="receipt-preview__order">{{ t('pos.settings.receipt.preview_order_no') }} #000123</div>

                    <hr class="receipt-preview__hr" />

                    <table class="receipt-preview__table">
                        <thead>
                            <tr>
                                <th>{{ t('pos.settings.receipt.preview_col_item') }}</th>
                                <th class="r">{{ t('pos.settings.receipt.preview_col_qty') }}</th>
                                <th class="r">{{ t('pos.settings.receipt.preview_col_price') }}</th>
                                <th class="r">{{ t('pos.settings.receipt.preview_col_total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ t('pos.settings.receipt.preview_sample_item_a') }}</td>
                                <td class="r">2</td>
                                <td class="r">500</td>
                                <td class="r">1,000</td>
                            </tr>
                            <tr>
                                <td>{{ t('pos.settings.receipt.preview_sample_item_b') }}</td>
                                <td class="r">1</td>
                                <td class="r">350</td>
                                <td class="r">350</td>
                            </tr>
                        </tbody>
                    </table>

                    <hr class="receipt-preview__hr" />

                    <table class="receipt-preview__totals">
                        <tr>
                            <td>{{ t('pos.settings.receipt.preview_subtotal') }}</td>
                            <td class="r">1,350</td>
                        </tr>
                        <tr>
                            <td>{{ t('pos.settings.receipt.preview_tax') }}</td>
                            <td class="r">135</td>
                        </tr>
                        <tr class="grand">
                            <td>{{ t('pos.settings.receipt.preview_total') }}</td>
                            <td class="r">1,485</td>
                        </tr>
                    </table>

                    <hr class="receipt-preview__hr" />

                    <div
                        v-if="form.footerMessage"
                        class="receipt-preview__footer"
                    >{{ form.footerMessage }}</div>
                </div>
            </div>
        </div>

    </div>

    <!-- Fixed bottom action bar -->
    <div class="fixed inset-x-0 bottom-0 z-50 flex items-center gap-3 border-t border-[#E5E5EA] bg-white px-4 py-3 safe-area-bottom">
        <!-- Test print button -->
        <button
            type="button"
            class="flex flex-1 items-center justify-center rounded-xl border border-[#E5E5EA] bg-[#F2F2F7] px-4 py-3 text-[15px] font-medium text-[#1C1C1E] active:opacity-70 disabled:opacity-50"
            :disabled="isTestPrinting"
            @click="handleTestPrint"
        >
            <span
                v-if="isTestPrinting"
                class="icon-spinner mr-2 animate-spin text-lg"
            ></span>
            {{ t('pos.settings.receipt.test_print_btn') }}
        </button>

        <!-- Save button -->
        <button
            type="button"
            class="flex flex-1 items-center justify-center rounded-xl bg-[#00B8D9] px-4 py-3 text-[15px] font-semibold text-white active:opacity-70 disabled:opacity-50"
            :disabled="isSaving"
            @click="handleSave"
        >
            <span
                v-if="isSaving"
                class="icon-spinner mr-2 animate-spin text-lg"
            ></span>
            {{ t('pos.settings.receipt.save_btn') }}
        </button>
    </div>
</template>

<script setup>
    import { ref, computed, onMounted, inject } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useIndexedDB } from '@src/composable/indexed-db';

    /**
     * General setup
     */
    const { t } = useI18n();
    const DB = useIndexedDB();
    const emitter = inject('emitter');

    /**
     * File input ref for logo upload
     */
    const fileInputRef = ref(null);
    const logoFileName = ref('');
    const logoPreviewUrl = ref('');
    const logoFile = ref(null);

    /**
     * Form state
     */
    const form = ref({
        storeName: '',
        address1: '',
        address2: '',
        phone: '',
        url: '',
        footerMessage: '',
    });

    /**
     * Loading states
     */
    const isSaving = ref(false);
    const isTestPrinting = ref(false);

    /**
     * Preview date — fixed at component mount so the preview doesn't tick.
     */
    const previewDate = computed(() => new Date().toLocaleString());

    /**
     * Preview tab — 'standard' (regular thermal receipt) or 'ryoshuho' (領収証).
     */
    const previewMode = ref('standard');

    /**
     * Handle logo file selection
     */
    const onLogoFileChange = (event) => {
        const file = event.target.files[0];

        if (!file) {
            return;
        }

        logoFile.value = file;
        logoFileName.value = file.name;

        const reader = new FileReader();

        reader.onload = (e) => {
            logoPreviewUrl.value = e.target.result;
        };

        reader.readAsDataURL(file);
    };

    /**
     * Fetch current settings on mount
     */
    onMounted(async () => {
        try {
            const token    = localStorage.getItem('accessToken');
            const response = await fetch('/api/v1/pos/receipt-settings', {
                headers: {
                    'Accept'        : 'application/json',
                    'Authorization' : `Bearer ${token}`,
                },
            });

            if (response.ok) {
                const data = await response.json();

                form.value.storeName = data.store_name ?? '';
                form.value.address1 = data.address1 ?? '';
                form.value.address2 = data.address2 ?? '';
                form.value.phone = data.phone ?? '';
                form.value.url = data.url ?? '';
                form.value.footerMessage = data.footer_message ?? '';

                if (data.logo_url) {
                    logoPreviewUrl.value = data.logo_url;
                    logoFileName.value = data.logo_url.split('/').pop();
                }
            }
        } catch (err) {
            // Silent fail on fetch – user can still edit and save
            console.warn('[ReceiptSettings] Failed to load settings:', err);
        }
    });

    /**
     * Save handler
     */
    const handleSave = async () => {
        isSaving.value = true;

        try {
            const payload = new FormData();

            payload.append('store_name', form.value.storeName);
            payload.append('address1', form.value.address1);
            payload.append('address2', form.value.address2);
            payload.append('phone', form.value.phone);
            payload.append('url', form.value.url);
            payload.append('footer_message', form.value.footerMessage);

            if (logoFile.value) {
                payload.append('logo', logoFile.value);
            }

            const token    = localStorage.getItem('accessToken');
            const response = await fetch('/api/v1/pos/receipt-settings', {
                method: 'POST',
                headers: {
                    'Accept'        : 'application/json',
                    'Authorization' : `Bearer ${token}`,
                },
                body: payload,
            });

            const data = await response.json();

            if (response.ok) {
                emitter.emit('add_flash', {
                    type: 'success',
                    message: data.message ?? t('pos.settings.receipt.save_success'),
                });
            } else {
                emitter.emit('add_flash', {
                    type: 'error',
                    message: data.message ?? t('pos.settings.receipt.save_error'),
                });
            }
        } catch (err) {
            emitter.emit('add_flash', {
                type: 'error',
                message: t('pos.settings.receipt.save_error'),
            });
        } finally {
            isSaving.value = false;
        }
    };

    /**
     * Test print handler
     */
    const handleTestPrint = async () => {
        isTestPrinting.value = true;

        try {
            const token    = localStorage.getItem('accessToken');
            const response = await fetch('/api/v1/pos/print-test-receipt', {
                method: 'POST',
                headers: {
                    'Content-Type'  : 'application/json',
                    'Accept'        : 'application/json',
                    'Authorization' : `Bearer ${token}`,
                },
            });

            const data = await response.json();

            if (response.ok) {
                emitter.emit('add_flash', {
                    type: 'success',
                    message: data.message ?? t('pos.settings.receipt.test_print_success'),
                });
            } else {
                emitter.emit('add_flash', {
                    type: 'error',
                    message: data.message ?? t('pos.settings.receipt.test_print_error'),
                });
            }
        } catch (err) {
            emitter.emit('add_flash', {
                type: 'error',
                message: t('pos.settings.receipt.test_print_error'),
            });
        } finally {
            isTestPrinting.value = false;
        }
    };
</script>

<style scoped>
    .receipt-preview {
        width: 280px;
        max-width: 100%;
        padding: 12px 14px;
        background: #fff;
        color: #000;
        font-family: 'IPAGothic', 'Pyidaungsu', 'Hind', 'Courier New', Courier, monospace;
        font-size: 11px;
        line-height: 1.4;
        border: 1px dashed #c7c7cc;
        border-radius: 4px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .receipt-preview__logo {
        text-align: center;
        margin-bottom: 6px;
    }

    .receipt-preview__logo img {
        max-height: 48px;
        max-width: 100%;
        object-fit: contain;
        display: inline-block;
    }

    .receipt-preview__store-name {
        text-align: center;
        font-size: 14px;
        font-weight: bold;
        white-space: pre-line;
        margin-bottom: 4px;
    }

    .receipt-preview__ryoshuho-title {
        text-align: center;
        font-size: 20px;
        font-weight: bold;
        letter-spacing: 4px;
        margin-bottom: 2px;
    }

    .receipt-preview__ryoshuho-subtitle {
        text-align: center;
        font-size: 10px;
        margin-bottom: 6px;
    }

    .receipt-preview__addressee {
        text-align: center;
        margin: 6px 0;
        padding: 4px 0;
        border-top: 1px solid #000;
        border-bottom: 1px solid #000;
    }

    .receipt-preview__addressee-name {
        font-size: 11px;
        margin-bottom: 4px;
    }

    .receipt-preview__addressee-amount {
        font-size: 16px;
        font-weight: bold;
        margin-bottom: 2px;
    }

    .receipt-preview__addressee-tax,
    .receipt-preview__addressee-note {
        font-size: 9px;
        color: #333;
    }

    .receipt-preview__store-info {
        text-align: center;
        font-size: 10px;
        margin-bottom: 4px;
    }

    .receipt-preview__date,
    .receipt-preview__order {
        text-align: center;
        font-size: 10px;
    }

    .receipt-preview__hr {
        border: none;
        border-top: 1px dashed #000;
        margin: 4px 0;
    }

    .receipt-preview__table,
    .receipt-preview__totals {
        width: 100%;
        border-collapse: collapse;
    }

    .receipt-preview__table th,
    .receipt-preview__table td,
    .receipt-preview__totals td {
        padding: 1px 2px;
        font-size: 11px;
    }

    .receipt-preview__table th {
        text-align: left;
        border-bottom: 1px dashed #000;
        font-weight: normal;
    }

    .receipt-preview__table th.r,
    .receipt-preview__table td.r,
    .receipt-preview__totals td.r {
        text-align: right;
    }

    .receipt-preview__totals tr.grand td {
        font-size: 13px;
        font-weight: bold;
        border-top: 1px dashed #000;
        padding-top: 3px;
    }

    .receipt-preview__footer {
        text-align: center;
        white-space: pre-line;
        margin-top: 6px;
        font-size: 11px;
    }
</style>

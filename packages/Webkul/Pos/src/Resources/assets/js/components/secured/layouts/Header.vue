<template>
    <header
        class="fixed top-0 left-0 right-0 z-[10001] shadow-[0px_-1px_0px_0px_rgba(0,0,0,0.1)_inset] bg-white dark:bg-gray-900 dark:shadow-[0px_-1px_0px_0px_rgba(255,255,255,0.1)_inset] transition-transform duration-300"
        :class="{ '-translate-y-full': isScrollingDown }"
    >
        <!-- Desktop View -->
        <section class="hidden items-center justify-between p-2.5 lg:flex">
            <div class="flex items-center gap-1.5">
                <!-- Heading -->
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ $t('pos.layout.header.title') }}
                </h1>

                <div
                    v-if="$route.path.split('/')[1] == 'home'"
                    class="ml-10 flex items-center gap-x-2"
                >
                    <!-- Search Bar -->
                    <div class="relative flex w-[486px] max-w-[486px] items-center max-lg:w-[400px] ltr:ml-2.5 rtl:mr-2.5 text-gray-900 dark:text-gray-100">
                        <input
                            type="text"
                            class="h-12 w-full rounded-md px-3.5 py-3 text-base font-normal leading-5 bg-gray-100 text-gray-900 placeholder-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-400"
                            :placeholder="$t('pos.layout.header.search_products')"
                            @input="searchProduct"
                        >

                        <i
                            class="icon-search absolute top-3 flex items-center text-2xl ltr:right-2 rtl:left-2 text-gray-900 dark:text-gray-100"
                        ></i>
                    </div>

                    <!-- Barcode Scanner (manual fallback) -->
                    <div
                        v-if="showBarcodeScanner"
                        class="flex h-12 w-12 cursor-pointer items-center justify-center rounded-md bg-gray-100 hover:bg-gray-200 dark:bg-gray-900 dark:hover:bg-gray-700"
                        @click="$refs.barcodeModal.open()"
                    >
                        <i class="icon-barcode text-2xl text-gray-900 dark:text-gray-100"></i>
                    </div>

                    <!-- Scanner status indicator -->
                    <div class="flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs bg-gray-100 dark:bg-gray-800 select-none">
                        <span
                            class="h-2 w-2 rounded-full transition-colors duration-300"
                            :class="scannerActive ? 'bg-green-500 animate-pulse' : 'bg-gray-400 dark:bg-gray-500'"
                        ></span>
                        <span class="text-gray-600 dark:text-gray-400 whitespace-nowrap">
                            {{ scannerActive ? $t('pos.layout.header.scanner_scanning') : $t('pos.layout.header.scanner_ready') }}
                        </span>
                    </div>

                    <!-- Product List -->
                    <div
                        class="flex h-12 w-12 cursor-pointer items-center justify-center rounded-md bg-gray-100 hover:bg-gray-200 dark:bg-gray-900 dark:hover:bg-gray-700"
                        @click="$refs.productCreateModal.open()"
                    >
                        <i class="icon-box text-2xl text-gray-900 dark:text-gray-100"></i>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-x-2">
                <!-- New Sale shortcut — visible on all pages -->
                <router-link
                    to="/home"
                    class="flex h-12 cursor-pointer items-center gap-2 rounded-md bg-[#00B8D9] px-4 hover:bg-[#00A5C4] text-white"
                >
                    <i class="icon-checkout text-2xl"></i>
                    <span class="text-base font-semibold">{{ $t('pos.layout.header.new_sale') }}</span>
                </router-link>

                <!-- Setting Page -> Sync Tab -->
                <router-link
                    to="/settings/sync"
                    class="flex h-12 w-12 cursor-pointer items-center justify-center rounded-md bg-gray-100 hover:bg-gray-200 dark:bg-gray-900 dark:hover:bg-gray-700"
                >
                    <i class="icon-sync text-2xl text-gray-900 dark:text-gray-100"></i>
                </router-link>

                <!-- Toggle full screen -->
                <div
                    class="flex h-12 w-12 cursor-pointer items-center justify-center rounded-md bg-gray-100 hover:bg-gray-200 dark:bg-gray-900 dark:hover:bg-gray-700"
                    @click="toggleFullScreen()"
                >
                    <i class="icon-fullscreen text-2xl text-gray-900 dark:text-gray-100"></i>
                </div>

                <!-- Toggle dark mode -->
                <div
                    class="flex h-12 w-12 cursor-pointer items-center justify-center rounded-md bg-gray-100 hover:bg-gray-200 dark:bg-gray-900 dark:hover:bg-gray-700"
                    @click="toggleDarkMode()"
                >
                    <span class="text-2xl icon-dark-mode text-gray-900 dark:icon-light-mode dark:text-gray-100">
                    </span>
                </div>

                <!-- Network -->
                <div class="flex h-12 w-12 cursor-pointer items-center justify-center rounded-md bg-gray-100 hover:bg-gray-200 dark:bg-gray-900 dark:hover:bg-gray-700">
                    <i
                        :class="[isOnline ? 'text-green-600' : 'text-red-500']"
                        class="icon-network text-2xl"
                    >
                    </i>
                </div>

                <router-link
                    to="/orders/hold"
                    class="transparent-button"
                >
                    <i class="icon-hold-cart text-2xl text-gray-900 dark:text-gray-100"></i>

                    <span class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                        {{ $t('pos.layout.header.hold_orders') }}
                    </span>
                </router-link>
            </div>
        </section>

        <!-- ── Mobile View — Air Regi top bar (xl: hidden, replaced by desktop Header above) ── -->
        <section
            v-if="$route.path.split('/')[1] != 'payment'"
            class="pos-topbar lg:hidden"
            :class="isLightTopBar ? 'bg-white' : 'bg-[#00B8D9]'"
        >
            <!-- Left: back button (not shown on dashboard) -->
            <div class="w-10 flex justify-start flex-shrink-0">
                <button
                    v-if="$route.path !== '/dashboard'"
                    type="button"
                    class="flex items-center justify-center w-8 h-8"
                    :class="isLightTopBar ? 'text-[#00B8D9]' : 'text-white'"
                    @click="$router.back()"
                >
                    <i class="icon-chevron-left text-2xl leading-none"></i>
                </button>
            </div>

            <!-- Center: route title -->
            <div class="flex-1 text-center px-2 truncate">
                <span
                    class="font-semibold text-[17px] leading-tight"
                    :class="isLightTopBar ? 'text-[#1C1C1E]' : 'text-white'"
                >
                    {{ routeTitle }}
                </span>
            </div>

            <!-- Right: context actions -->
            <div class="flex justify-end items-center gap-1 flex-shrink-0">
                <!-- Product-create button on /home -->
                <button
                    v-if="$route.path === '/home'"
                    type="button"
                    class="flex items-center justify-center w-8 h-8"
                    :class="isLightTopBar ? 'text-[#00B8D9]' : 'text-white'"
                    @click="$refs.productCreateModal.open()"
                >
                    <i class="icon-add-product text-xl"></i>
                </button>

                <!-- Barcode button on Order Entry (/home) -->
                <button
                    v-if="$route.path === '/home' && showBarcodeScanner"
                    type="button"
                    class="flex items-center justify-center w-8 h-8"
                    :class="isLightTopBar ? 'text-[#00B8D9]' : 'text-white'"
                    @click="$refs.barcodeModal.open()"
                >
                    <i
                        class="icon-barcode text-xl"
                        :class="scannerActive ? 'text-green-400 animate-pulse' : ''"
                    ></i>
                </button>

                <!-- Sync button on dashboard -->
                <router-link
                    v-if="$route.path === '/dashboard'"
                    to="/settings/sync"
                    class="flex items-center justify-center w-8 h-8"
                    :class="isLightTopBar ? 'text-[#00B8D9]' : 'text-white'"
                >
                    <i class="icon-sync text-xl"></i>
                </router-link>

                <!-- New Sale shortcut (hidden on /home since we're already there) -->
                <router-link
                    v-if="$route.path !== '/home'"
                    to="/home"
                    class="flex items-center justify-center w-8 h-8"
                    :class="isLightTopBar ? 'text-[#00B8D9]' : 'text-white'"
                >
                    <i class="icon-checkout text-xl"></i>
                </router-link>

                <!-- Products "..." context menu -->
                <div
                    v-if="$route.path.startsWith('/products')"
                    class="relative"
                >
                    <button
                        type="button"
                        class="flex items-center justify-center w-8 h-8"
                        :class="isLightTopBar ? 'text-[#1C1C1E]' : 'text-white'"
                        @click.stop="productMenuOpen = !productMenuOpen"
                    >
                        <i class="icon-more text-xl"></i>
                    </button>

                    <!-- Dropdown -->
                    <Teleport to="body">
                        <div
                            v-if="productMenuOpen"
                            class="fixed inset-0 z-[10100]"
                            @click="productMenuOpen = false"
                        >
                            <div
                                class="absolute right-2 top-12 w-48 rounded-xl bg-white shadow-xl border border-[#E5E5EA] overflow-hidden"
                                @click.stop
                            >
                                <router-link
                                    v-for="item in productMenuItems"
                                    :key="item.path"
                                    :to="item.path"
                                    class="flex items-center px-4 py-3 text-[16px] text-[#00B8D9] border-b border-[#F2F2F7] last:border-0 active:bg-[#F2F2F7]"
                                    @click="productMenuOpen = false"
                                >
                                    {{ item.label }}
                                </router-link>
                            </div>
                        </div>
                    </Teleport>
                </div>

                <!-- Logout button -->
                <button
                    type="button"
                    class="flex items-center justify-center w-8 h-8"
                    :class="isLightTopBar ? 'text-[#8E8E93]' : 'text-white/80'"
                    @click="logout"
                >
                    <i class="icon-logout text-xl"></i>
                </button>
            </div>
        </section>

        <Teleport to="body">

            <Modal ref="barcodeModal">
                <template v-slot:header>
                    {{ $t('pos.layout.header.barcode_form.manual_title') }}
                </template>
                
                <template v-slot:content="{ toggle }">
                    <v-form
                        class="grid gap-4"
                        @submit="submitBarcodeForm"
                    >
                        <ControlGroup>
                            <Field
                                :type="'text'"
                                :name="'barcode'"
                                :id="'barcode'"
                                :rules="'required'"
                                :placeholder="$t('pos.layout.header.barcode_form.barcode_placeholder')"
                            />

                            <Error :name="'barcode'" />
                        </ControlGroup>

                        <div class="flex justify-end gap-6">
                            <button
                                type="button"
                                class="transparent-button w-36"
                                @click="toggle"
                            >
                                {{ $t('pos.layout.header.barcode_form.cancel_btn_title') }}
                            </button>

                            <button
                                type="submit"
                                class="primary-button w-36"
                            >
                                {{ $t('pos.layout.header.barcode_form.proceed_btn_title') }}
                            </button>
                        </div>
                    </v-form>
                </template>
            </Modal>

            <Modal ref="productCreateModal">
                <template v-slot:header>
                    {{ $t('pos.layout.header.product_create_form.title') }}
                </template>

                <template v-slot:content="{ toggle }">
                    <v-form v-slot="{ handleSubmit }">
                        <form
                            class="flex flex-col justify-start gap-4"
                            @submit="handleSubmit($event, submitProductForm)"
                        >
                            <!-- Pre-filled barcode badge: only when launched from picker -->
                            <div
                                v-if="productPrefillBarcode"
                                class="rounded-md bg-cyan-50 border border-cyan-200 px-3 py-2 text-sm text-cyan-900 dark:bg-cyan-900/30 dark:border-cyan-700 dark:text-cyan-100"
                            >
                                <span class="font-semibold">{{ $t('pos.layout.header.product_create_form.barcode_label') }}:</span>
                                <span class="ml-1 break-all">{{ productPrefillBarcode }}</span>
                            </div>

                            <ControlGroup>
                                <Label for="name">
                                    {{ $t('pos.layout.header.product_create_form.name') }}
                                </Label>

                                <Field
                                    :type="'text'"
                                    :name="'name'"
                                    :id="'name'"
                                    :rules="'required'"
                                    :placeholder="$t('pos.layout.header.product_create_form.name')"
                                />

                                <Error :name="'name'" />
                            </ControlGroup>

                            <!-- Optional product image -->
                            <ControlGroup>
                                <Label for="product_image">
                                    {{ $t('pos.layout.header.product_create_form.image') }}
                                </Label>

                                <div class="flex items-center gap-3">
                                    <label
                                        for="product_image"
                                        class="cursor-pointer rounded-md border border-neutral-400 bg-white px-3 py-2 text-sm text-gray-800 hover:bg-gray-50 dark:bg-slate-900 dark:text-white dark:hover:bg-slate-800"
                                    >
                                        <i class="icon-image mr-1"></i>
                                        {{ productImageDataUrl
                                            ? $t('pos.layout.header.product_create_form.image_change')
                                            : $t('pos.layout.header.product_create_form.image_pick') }}
                                    </label>

                                    <input
                                        id="product_image"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="onProductImageSelected"
                                    />

                                    <img
                                        v-if="productImageDataUrl"
                                        :src="productImageDataUrl"
                                        class="h-12 w-12 rounded border border-gray-200 object-cover"
                                        alt="preview"
                                    />

                                    <button
                                        v-if="productImageDataUrl"
                                        type="button"
                                        class="text-xs text-red-500 hover:underline"
                                        @click="clearProductImage"
                                    >
                                        {{ $t('pos.layout.header.product_create_form.image_remove') }}
                                    </button>
                                </div>
                            </ControlGroup>

                            <div :class="[showProductSkuField ? 'flex max-sm:flex-col max-sm:gap-4 gap-2.5' : '']">
                                <ControlGroup
                                    v-if="showProductSkuField"
                                    className="flex-1"
                                >
                                    <Label for="sku">
                                        {{ $t('pos.layout.header.product_create_form.sku') }}
                                    </Label>

                                    <Field
                                        :type="'text'"
                                        :name="'sku'"
                                        :id="'sku'"
                                        :rules="'required'"
                                        :placeholder="$t('pos.layout.header.product_create_form.sku')"
                                    />

                                    <Error :name="'sku'" />
                                </ControlGroup>

                                <ControlGroup className="flex-1">
                                    <Label for="price">
                                        {{ $t('pos.layout.header.product_create_form.price') }}
                                    </Label>

                                    <Field
                                        :type="'text'"
                                        :name="'price'"
                                        :id="'price'"
                                        :rules="'required|decimal:2'"
                                        :placeholder="$t('pos.layout.header.product_create_form.price')"
                                    />

                                    <Error :name="'price'" />
                                </ControlGroup>
                            </div>

                            <div class="flex gap-2.5 max-sm:flex-col max-sm:gap-4">
                                <ControlGroup className="flex-1">
                                    <Label for="quantity">
                                        {{ $t('pos.layout.header.product_create_form.quantity') }}
                                    </Label>

                                    <Field
                                        :type="'text'"
                                        :name="'quantity'"
                                        :id="'quantity'"
                                        :rules="'required|integer'"
                                        :placeholder="$t('pos.layout.header.product_create_form.quantity')"
                                    />

                                    <Error :name="'quantity'" />
                                </ControlGroup>

                                <ControlGroup className="flex-1">
                                    <Label for="weight">
                                        {{ $t('pos.layout.header.product_create_form.weight') }}
                                    </Label>

                                    <Field
                                        :type="'text'"
                                        :name="'weight'"
                                        :id="'weight'"
                                        :rules="'decimal:2'"
                                        :placeholder="$t('pos.layout.header.product_create_form.weight')"
                                        value="0"
                                    />

                                    <Error :name="'weight'" />
                                </ControlGroup>
                            </div>

                            <div class="flex justify-end gap-6 max-sm:justify-between">
                                <button
                                    type="button"
                                    class="transparent-button w-36 max-sm:w-full"
                                    @click="toggle"
                                >
                                    {{ $t('pos.layout.header.product_create_form.cancel_btn_title') }}
                                </button>

                                <Button
                                    type="submit"
                                    class="primary-button w-36 max-sm:w-full"
                                    :label="$t('pos.layout.header.product_create_form.proceed_btn_title')"
                                    :isLoading="isCreatingProduct"
                                />
                            </div>
                        </form>
                    </v-form>
                </template>
            </Modal>

            <Modal ref="barcodePickerModal">
                <template v-slot:header>
                    {{ $t('pos.layout.header.barcode_picker.title') }}
                </template>

                <template v-slot:content="{ toggle }">
                    <div class="flex flex-col gap-3">
                        <p class="text-sm leading-5 text-gray-700 dark:text-gray-300">
                            {{ $t('pos.layout.header.barcode_picker.subtitle', { barcode: unknownBarcode }) }}
                        </p>

                        <input
                            type="text"
                            v-model="productPickerSearch"
                            :placeholder="$t('pos.layout.header.barcode_picker.search_placeholder')"
                            class="w-full rounded-md border border-neutral-400 bg-white dark:bg-slate-900 text-gray-800 dark:text-white px-3 py-2.5 transition-all hover:border-gray-500 focus:border-gray-500 focus:outline-none"
                        />

                        <div class="max-h-[360px] overflow-y-auto rounded-md border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
                            <div
                                v-if="filteredPickerProducts.length === 0"
                                class="p-4 text-center text-sm text-gray-500 dark:text-gray-400"
                            >
                                {{ $t('pos.layout.header.barcode_picker.no_results') }}
                            </div>

                            <button
                                v-for="p in filteredPickerProducts"
                                :key="p.id"
                                type="button"
                                :disabled="isLinkingBarcode"
                                class="flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left hover:bg-gray-50 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"
                                @click="confirmLinkBarcode(p.id)"
                            >
                                <div class="relative h-12 w-12 shrink-0 overflow-hidden rounded border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                    <img
                                        v-if="p.images?.length"
                                        :src="p.images[0].url"
                                        class="absolute inset-0 h-full w-full object-cover"
                                        :alt="p.name"
                                        loading="lazy"
                                    />

                                    <img
                                        v-else
                                        src="@images/product-placeholder.webp"
                                        class="absolute inset-0 h-full w-full object-cover opacity-30"
                                        alt=""
                                        loading="lazy"
                                    />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                        {{ p.name }}
                                    </p>
                                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                        SKU: {{ p.sku }}
                                    </p>
                                </div>
                                <span class="primary-button shrink-0 text-xs">
                                    {{ $t('pos.layout.header.barcode_picker.link_btn') }}
                                </span>
                            </button>
                        </div>

                        <div class="flex flex-wrap justify-between items-center gap-2 sm:gap-3">
                            <button
                                type="button"
                                class="transparent-button shrink-0 px-4"
                                :disabled="isLinkingBarcode"
                                @click="toggle"
                            >
                                {{ $t('pos.layout.header.barcode_picker.cancel_btn') }}
                            </button>

                            <button
                                type="button"
                                class="primary-button shrink-0 px-4 inline-flex items-center"
                                :disabled="isLinkingBarcode"
                                @click="openCreateFromPicker"
                            >
                                <i class="icon-add-product mr-1 text-base align-middle"></i>
                                <span class="truncate">{{ $t('pos.layout.header.barcode_picker.create_new_btn') }}</span>
                            </button>
                        </div>
                    </div>
                </template>
            </Modal>
        </Teleport>
    </header>
</template>

<script setup>
    import { inject, ref, computed, onMounted, onBeforeUnmount, watchEffect, watch } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useRouter, useRoute } from 'vue-router';
    import { useOutlet } from '@src/composable/outlet';
    import { useIndexedDB } from '@src/composable/indexed-db';
    import { useMutation } from '@vue/apollo-composable';
    import { CREATE_PRODUCT, LINK_BARCODE_TO_PRODUCT } from '@src/graphql/products';
    import { LOGOUT } from '@src/graphql/session';
    import { useCookies } from '@src/composable/cookies';
    import { useWindowWidth } from '@src/composable/window';
    import { useBarcodeScanner } from '@src/composable/useBarcodeScanner';
    import { useScrollDirection } from '@src/composable/useScrollDirection';

    /**
     * General Imports
     */
    const { t } = useI18n();
    const { isScrollingDown } = useScrollDirection(10);
    const DB = useIndexedDB();
    const emitter = inject('emitter');

    // Products "..." context menu
    const productMenuOpen = ref(false);
    const productMenuItems = [
        { label: 'カテゴリー設定', path: '/products/list' },
        { label: '在庫管理',       path: '/inventory' },
        { label: '棚卸 (Take Stock)', path: '/products/inception' },
        { label: 'タイルの配置',   path: '/products/settings' },
        { label: '販売単位設定',   path: '/products/requested' },
    ];
    const isOnline = inject('isOnline');
    const cookies = useCookies();
    const { getMenuItems } = useOutlet();
    const { isMobileOrTab } = useWindowWidth();
    const route = useRoute();
    const routeTitle = computed(() => route.meta?.title_key ? t(route.meta.title_key) : '');

    // Light (white) top bar on dashboard and settings root; teal everywhere else
    const LIGHT_TOPBAR_PATHS = ['/dashboard', '/settings'];
    const isLightTopBar = computed(() =>
        LIGHT_TOPBAR_PATHS.includes(route.path)
    );

    /**
     * Mobile Menu
     */
    const menuItems = getMenuItems();
    const isMobileMenuOpen = ref(false);

    /**
     * Search Products
     */
    const searchProduct = (e) => {
        let searchTerm = e.target.value;

        emitter.emit('search_product', searchTerm);
    };

    /**
     * Product Form
     */
    const productCreateModal = ref(null);

    // Holds the optional image (data URL) + barcode that the cashier attaches
    // to a quick-create product. Cleared after successful submit.
    const productImageDataUrl = ref('');
    const productImageName    = ref('');
    const productPrefillBarcode = ref('');

    const onProductImageSelected = (event) => {
        const file = event.target.files?.[0];

        if (! file) {
            productImageDataUrl.value = '';
            productImageName.value    = '';
            return;
        }

        if (! file.type.startsWith('image/')) {
            emitter.emit('add_flash', {
                type: 'error',
                message: t('pos.layout.header.product_create_form.image_invalid'),
            });
            return;
        }

        // ~3MB cap — base64 inflates by ~33% and we send via GraphQL JSON.
        if (file.size > 3 * 1024 * 1024) {
            emitter.emit('add_flash', {
                type: 'error',
                message: t('pos.layout.header.product_create_form.image_too_large'),
            });
            return;
        }

        const reader = new FileReader();

        reader.onload = (e) => {
            productImageDataUrl.value = e.target.result;
            productImageName.value    = file.name;
        };

        reader.readAsDataURL(file);
    };

    const clearProductImage = () => {
        productImageDataUrl.value = '';
        productImageName.value    = '';
    };

    const { mutate: createProduct, loading: isCreatingProduct } = useMutation(CREATE_PRODUCT);

    const submitProductForm = (params, { setErrors, resetForm }) => {
        if (! isOnline.value) {
            emitter.emit('add_flash', {
                type: 'error',
                message: t('pos.common.flash_messages.offline_error'),
            });

            productCreateModal.value.toggle();

            return;
        }

        const input = {
            name: params.name,
            sku: params.sku || '',
            price: parseFloat(params.price),
            quantity: parseInt(params.quantity),
            weight: parseFloat(params.weight || 0),
        };

        // If we got here from the unknown-barcode picker, pre-fill the barcode
        // so the new product is immediately scannable.
        if (productPrefillBarcode.value) {
            input.barcode = productPrefillBarcode.value;
        }

        if (productImageDataUrl.value) {
            input.imageBase64 = productImageDataUrl.value;
            input.imageName   = productImageName.value;
        }

        createProduct({ input }).then(async (response) => {
            const { createOutletProduct } = response?.data;

            if (createOutletProduct?.success) {
                emitter.emit('add_flash', {
                    type: 'success',
                    message: createOutletProduct?.message,
                });

                resetForm();
                clearProductImage();
                productPrefillBarcode.value = '';

                productCreateModal.value.toggle();

                // Also close the picker if it was the entry point.
                if (barcodePickerModal.value?.isOpen?.value) {
                    barcodePickerModal.value.close();
                }

                await DB.addItem('products', createOutletProduct.product);

                emitter.emit('new_product', createOutletProduct.product);

                emitter.emit('add_to_cart', {
                    productId: createOutletProduct.product.id,
                    quantity: 1,
                });
            } else {
                if (createOutletProduct?.errors) {
                    setErrors(JSON.parse(createOutletProduct.errors));
                }
            }
        });
    };

    /**
     * "Create New" entry point from the unknown-barcode picker — opens the
     * product create modal pre-filled with the scanned barcode so it gets
     * linked on submit.
     */
    const openCreateFromPicker = () => {
        productPrefillBarcode.value = unknownBarcode.value;
        clearProductImage();
        // Hide the picker so only the create form is visible — otherwise the
        // two modals stack and the picker shows through behind the form.
        barcodePickerModal.value?.close();
        productCreateModal.value?.open();
    };

    /**
     * Show Barcode Scanner
     */
    const showBarcodeScanner = ref(false);
    const showProductSkuField = ref(false);

    onMounted(() => {
        DB.getItem('configurations', 1).then(data => {
            showBarcodeScanner.value = data?.configurations?.find(config => config.code == 'pos.settings.barcode.hide')?.value == '1' ? false : true;

            showProductSkuField.value = data?.configurations?.find(config => config.code == 'pos.settings.product.allow_sku')?.value == '1' ? true : false;
        });
    });

    /**
     * Barcode prefix (resolved once from IndexedDB)
     */
    const barcodePrefix = ref('');

    onMounted(() => {
        DB.getItem('configurations', 1).then(data => {
            barcodePrefix.value = data?.configurations?.find(config => config.code == 'pos.settings.barcode.prefix')?.value ?? '';
        });
    });

    /**
     * Shared barcode scan handler — used by both the hardware scanner
     * listener and the manual barcode form.
     *
     * Looks up the product in all IndexedDB products, matching on:
     *   1. product.gtin  — real EAN-13 / UPC-A / GTIN
     *   2. product.barcode — legacy generated value
     * Then emits add_to_cart directly.
     */
    const handleBarcodeScan = async (rawBarcode) => {
        let barcode = rawBarcode.trim();

        // Apply configured prefix if the value doesn't already start with it
        if (barcodePrefix.value && !barcode.startsWith(barcodePrefix.value)) {
            barcode = barcodePrefix.value + barcode;
        }

        const allProducts = await DB.getAllItems('products');

        /**
         * Multi-type lookup — resolves any of the three barcode strategies:
         *   1. scan_value  — pre-computed per-product value (gtin | sku | product_id string)
         *   2. gtin        — legacy explicit GTIN field
         *   3. barcode     — legacy generated image filename value
         *   4. sku         — direct SKU match (works for sku-type barcodes)
         *   5. product id  — numeric scan matched against product.id
         *
         * Order matters: scan_value is authoritative; SKU/ID fallbacks ensure
         * products without any barcode record are still reachable.
         */
        const localMatch = allProducts.find(p => {
            if (p.scanValue && p.scanValue === barcode) return true;
            if (p.gtin      && p.gtin      === barcode) return true;
            if (p.barcode   && p.barcode   === barcode) return true;
            if (p.sku       && p.sku       === barcode) return true;
            if (/^\d+$/.test(barcode) && p.id == parseInt(barcode, 10)) return true;
            return false;
        });

        if (localMatch) {
            emitter.emit('add_to_cart', {
                productId: localMatch.id,
                quantity: 1,
            });
            return;
        }

        // ── Server fallback ───────────────────────────────────────────
        // Local IndexedDB may be stale (admin bulk-filled barcodes,
        // cashier hasn't synced). Hit the live tenant DB before giving up.
        if (isOnline.value) {
            try {
                const token = localStorage.getItem('accessToken');
                const res = await fetch(
                    `/api/v1/pos/resolve-barcode?code=${encodeURIComponent(barcode)}`,
                    {
                        headers: {
                            'Accept'        : 'application/json',
                            'Authorization' : `Bearer ${token}`,
                        },
                    },
                );

                if (res.ok) {
                    const json = await res.json();
                    if (json?.found && json.product?.id) {
                        // Patch IndexedDB so the next scan resolves locally.
                        try {
                            const existing = await DB.getItem('products', json.product.id);
                            if (existing) {
                                existing.scanValue = json.product.scanValue ?? existing.scanValue;
                                existing.gtin      = json.product.gtin      ?? existing.gtin;
                                existing.barcode   = json.product.barcode   ?? existing.barcode;
                                await DB.addItem('products', existing);
                            }
                        } catch (_) { /* non-fatal */ }

                        emitter.emit('add_to_cart', {
                            productId: json.product.id,
                            quantity:  1,
                        });
                        return;
                    }
                }
            } catch (_) { /* fall through to picker */ }
        }

        // Unknown barcode → open picker so cashier can link it to a product.
        unknownBarcode.value      = barcode;
        productPickerSearch.value = '';

        const all = await DB.getAllItems('products');
        allProductsForPicker.value = all || [];
        barcodePickerModal.value?.open();
    };

    /**
     * Global passive barcode scanner — listens on all secured screens.
     * Disabled while any modal is open to prevent accidental cart adds.
     */
    const barcodeModal = ref(null);

    /**
     * Unknown-barcode picker: opens when a scanned code matches no
     * product. Cashier picks one from a searchable list; on confirm we
     * persist gtin/scan_value via linkBarcodeToProduct mutation, update
     * IndexedDB so subsequent scans resolve, then add_to_cart.
     */
    const barcodePickerModal   = ref(null);
    const unknownBarcode       = ref('');
    const productPickerSearch  = ref('');
    const allProductsForPicker = ref([]);
    const isLinkingBarcode     = ref(false);

    const filteredPickerProducts = computed(() => {
        const q = productPickerSearch.value.trim().toLowerCase();

        const list = allProductsForPicker.value || [];

        if (! q) {
            return list;
        }

        return list.filter(p => {
            const name = (p.name || '').toLowerCase();
            const sku  = (p.sku  || '').toLowerCase();

            return name.includes(q) || sku.includes(q);
        });
    });

    const { mutate: linkBarcodeMutation } = useMutation(LINK_BARCODE_TO_PRODUCT);

    const confirmLinkBarcode = async (productId) => {
        if (! isOnline.value) {
            emitter.emit('add_flash', {
                type: 'error',
                message: t('pos.common.flash_messages.offline_error'),
            });

            return;
        }

        isLinkingBarcode.value = true;

        try {
            const response = await linkBarcodeMutation({
                input: {
                    productId: parseInt(productId, 10),
                    barcode:   unknownBarcode.value,
                },
            });

            const result = response?.data?.linkBarcodeToProduct;

            if (result?.success) {
                // Patch the IndexedDB record so the next scan of the same
                // code resolves locally without needing a full sync.
                try {
                    const existing = await DB.getItem('products', productId);

                    if (existing) {
                        existing.gtin      = result.gtin      ?? unknownBarcode.value;
                        existing.scanValue = result.scanValue ?? unknownBarcode.value;
                        await DB.addItem('products', existing);
                    }
                } catch (e) {}

                emitter.emit('add_flash', {
                    type: 'success',
                    message: result.message || t('pos.layout.header.barcode_picker.link_success'),
                });

                barcodePickerModal.value?.toggle();

                emitter.emit('add_to_cart', {
                    productId: productId,
                    quantity:  1,
                });
            } else {
                emitter.emit('add_flash', {
                    type: 'error',
                    message: result?.message || t('pos.layout.header.barcode_picker.link_error'),
                });
            }
        } catch (err) {
            emitter.emit('add_flash', {
                type: 'error',
                message: err?.message || t('pos.layout.header.barcode_picker.link_error'),
            });
        } finally {
            isLinkingBarcode.value = false;
        }
    };

    const isAnyModalOpen = computed(() =>
        barcodeModal.value?.isOpen.value ||
        productCreateModal.value?.isOpen.value ||
        barcodePickerModal.value?.isOpen.value
    );

    const { scannerActive } = useBarcodeScanner(handleBarcodeScan, {
        disabled: isAnyModalOpen,
    });

    /**
     * Manual barcode form (fallback when no hardware scanner)
     */
    const submitBarcodeForm = async (e) => {
        await handleBarcodeScan(e.barcode);

        barcodeModal.value.toggle();
    };

    /**
     * Get Agent Profile
     */
    const agent = ref({});

    watchEffect(async () => {
        agent.value = await DB.getAgent();
    });

    /**
     * Logout
     */
    const { mutate:agentLogout } = useMutation(LOGOUT);

    const router = useRouter();

    const logout = async () => {
        try {
            const data = await agentLogout();

            if (data?.data?.agentLogout?.success) {
                localStorage.removeItem('accessToken');

                router.push({path: '/'});
            }
        } catch (err) {}
    };

    /**
     * Full Screen
     */
    const toggleFullScreen = () => {
        if (! document.fullscreenElement) {
            document.documentElement.requestFullscreen();
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }
    };

    /**
     * Dark Mode
     */
    const isDarkMode = ref(cookies.get('dark-mode'));

    const toggleDarkMode = () => {
        isDarkMode.value = !isDarkMode.value;

        if (isDarkMode.value) {
            document.documentElement.classList.add('dark');

            cookies.set('dark-mode', true);
        } else {
            document.documentElement.classList.remove('dark');
            
            cookies.set('dark-mode', false);
        }
    };

    /**
     * Keyboard Shortcuts
     */
    onMounted(() => {
        emitter.on('toggle_dark_mode', toggleDarkMode);

        emitter.on('open_barcode_model', () => {
            barcodeModal.value.toggle();
        });

        emitter.on('open_product_create_model', () => {
            productCreateModal.value.toggle();
        });
    });

    onBeforeUnmount(() => {
        emitter.off('toggle_dark_mode');

        emitter.off('open_barcode_model');

        emitter.off('open_product_create_model');
    });
</script>

<template>
    <div class="flex h-full gap-4 overflow-hidden">
        <div class="padding-right flex-1 overflow-hidden py-4">
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $t('pos.customers.title') }}
                    </p>

                    <router-link
                        to="/customers/create"
                        class="max-sm:transparent-button primary-button"
                    >
                        <span class="icon-plus text-2xl dark:text-gray-100"></span>

                        <span class="max-sm:hidden dark:text-gray-100">
                            {{ $t('pos.customers.add_btn') }}
                        </span>
                    </router-link>
                </div>

                <template v-if="isCurrentCustomerLoading">
                    <CurrentCustomerSkeleton />
                </template>

                <template v-else>
                    <!-- Desktop View -->
                    <div class="box-shadow flex gap-6 rounded-lg bg-white p-4 max-sm:hidden dark:bg-gray-900">
                        <template v-if="currentCustomer?.id">
                            <template v-if="currentCustomer.imageUrl">
                                <img
                                    :src="currentCustomer.imageUrl"
                                    class="h-40 w-40 rounded-lg"
                                    alt="profile image"
                                >
                            </template>

                            <template v-else>
                                <img
                                    src="/src/Resources/assets/images/user-placeholder.png"
                                    class="h-40 w-40 rounded-lg dark:invert"
                                    alt="profile image"
                                >
                            </template>

                            <div class="grid w-full">
                                <div class="grid content-start gap-1">
                                    <p class="text-4xl font-normal text-gray-900 dark:text-gray-100">
                                        {{ currentCustomer.firstName }} {{ currentCustomer.lastName }}

                                        <span class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-200">
                                            #{{ currentCustomer.id }}
                                        </span>
                                    </p>

                                    <div class="grid gap-0.5">
                                        <p class="text-base font-normal leading-5 text-neutral-400 dark:text-neutral-400">
                                            {{ currentCustomer.email }}
                                        </p>

                                        <p class="text-base font-normal leading-5 text-neutral-400 dark:text-neutral-400">
                                            {{ currentCustomer.phone ?? 'N/A' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-end justify-between">
                                    <div class="flex gap-6 text-gray-900 dark:text-gray-100">
                                        <router-link
                                            :to="getCustomerEditPath()"
                                            class="flex cursor-pointer items-center gap-2.5"
                                        >
                                            <span class="icon-edit text-2xl dark:text-gray-100"></span>

                                            {{ $t('pos.customers.edit_btn') }}
                                        </router-link>

                                        <div
                                            class="flex cursor-pointer items-center gap-2.5"
                                            :class="{
                                                'pointer-events-none': isCustomerDeleting,
                                                'opacity-50': isSameCustomer,
                                            }"
                                            @click="
                                                ! isCustomerDeleting
                                                && ! isSameCustomer
                                                && deleteCurrentCustomer()
                                            "
                                        >
                                            <template v-if="isCustomerDeleting">
                                                <div class="h-5 w-5 animate-spin rounded-full border-4 border-slate-600 border-t-transparent dark:border-slate-400 dark:border-t-transparent">
                                                </div>
                                            </template>

                                            <template v-else>
                                                <span class="icon-delete text-2xl dark:text-gray-100"></span>
                                            </template>

                                            {{ $t('pos.customers.delete_btn') }}
                                        </div>
                                    </div>

                                    <button
                                        class="primary-button"
                                        :class="[isSameCustomer ? 'opacity-50 hover:opacity-50' : '']"
                                        :disabled="isSameCustomer"
                                        @click="changeCurrentCustomer()"
                                    >
                                        <template v-if="cartCustomer?.id">
                                            {{ $t('pos.customers.change_btn') }}
                                        </template>

                                        <template v-else>
                                            {{ $t('pos.customers.select_btn') }}
                                        </template>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <div class="flex h-40 w-full items-center justify-center">
                                <p class="text-lg font-normal text-neutral-400 dark:text-neutral-400">
                                    {{ $t('pos.customers.no_current_customer') }}
                                </p>
                            </div>
                        </template>
                    </div>

                    <!-- Mobile View -->
                    <div class="box-shadow flex flex-col gap-5 rounded-lg bg-white p-4 md:hidden dark:bg-gray-900">
                        <template v-if="currentCustomer?.id">
                            <div class="flex gap-2.5">
                                <template v-if="currentCustomer.imageUrl">
                                    <img
                                        :src="currentCustomer.imageUrl"
                                        class="h-20 w-20 rounded-lg"
                                        alt="profile image"
                                    >
                                </template>

                                <template v-else>
                                    <img
                                        src="/src/Resources/assets/images/user-placeholder.png"
                                        class="h-20 w-20 rounded-lg dark:invert"
                                        alt="profile image"
                                    >
                                </template>

                                <div class="grid content-start gap-1">
                                    <p class="text-xl font-normal text-gray-900 dark:text-gray-100">
                                        {{ currentCustomer.firstName }} {{ currentCustomer.lastName }}

                                        <span class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-200">
                                            #{{ currentCustomer.id }}
                                        </span>
                                    </p>

                                    <div class="grid gap-0.5">
                                        <p class="text-base font-normal leading-5 text-neutral-400 dark:text-neutral-400">
                                            {{ currentCustomer.email }}
                                        </p>

                                        <p class="text-base font-normal leading-5 text-neutral-400 dark:text-neutral-400">
                                            {{ currentCustomer.phone ?? 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-end justify-between">
                                <router-link
                                    :to="getCustomerEditPath()"
                                    class="flex cursor-pointer items-center gap-2.5 dark:text-gray-100"
                                >
                                    <span class="icon-edit text-2xl"></span>

                                    {{ $t('pos.customers.edit_btn') }}
                                </router-link>

                                <div
                                    class="flex cursor-pointer items-center gap-2.5 dark:text-gray-100"
                                    :class="{
                                        'pointer-events-none': isCustomerDeleting,
                                        'opacity-50': isSameCustomer,
                                    }"
                                    @click="
                                        ! isCustomerDeleting
                                        && ! isSameCustomer
                                        && deleteCurrentCustomer()
                                    "
                                >
                                    <template v-if="isCustomerDeleting">
                                        <div class="h-5 w-5 animate-spin rounded-full border-4 border-slate-600 border-t-transparent dark:border-slate-400 dark:border-t-transparent">
                                        </div>
                                    </template>

                                    <template v-else>
                                        <span class="icon-delete text-2xl"></span>
                                    </template>

                                    {{ $t('pos.customers.delete_btn') }}
                                </div>

                                <div
                                    class="flex cursor-pointer items-center gap-2.5 dark:text-gray-100"
                                    :class="{
                                        'pointer-events-none': isCustomerDeleting,
                                        'opacity-50': isSameCustomer,
                                    }"
                                    @click="
                                        ! isCustomerDeleting
                                        && ! isSameCustomer
                                        && changeCurrentCustomer()
                                    "
                                >
                                    <span class="icon-checkout text-2xl"></span>

                                    {{ $t('pos.customers.change_btn') }}
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <div class="flex h-40 w-full items-center justify-center">
                                <p class="text-lg font-normal text-neutral-400 dark:text-neutral-400">
                                    {{ $t('pos.customers.no_current_customer') }}
                                </p>
                            </div>
                        </template>
                    </div>
                </template>

                <div class="box-shadow grid rounded-lg bg-white px-4 py-1 dark:bg-gray-900">
                    <div class="flex h-12 gap-2.5 rounded-lg bg-white pt-2 dark:bg-gray-900">
                        <div
                            v-on:click="activeTab = type"
                            :class="[activeTab === type ? 'bg-gray-100 text-slate-600 !border-slate-600 dark:bg-gray-800 dark:text-slate-300 dark:!border-slate-400' : 'text-gray-900 dark:text-gray-100']"
                            class="flex cursor-pointer items-center rounded-lg border-2 border-transparent px-3 py-2 text-base font-medium"
                            v-for="(type, index) in ['customers', 'offline_customers']"
                            :key="index"
                        >
                            {{ $t(`pos.customers.${type}`) }}
                        </div>
                    </div>

                    <div class="relative flex items-center text-gray-900 dark:text-gray-100 my-4">
                        <input
                            type="text"
                            class="h-12 w-full px-8 text-base font-normal leading-5 bg-transparent border-b"
                            :placeholder="$t('pos.customers.search_customers')"
                            v-model="searchTerm"
                        >

                        <i class="icon-search left absolute top-3.5 flex items-center text-xl leading-5 dark:text-gray-100"></i>
                    </div>

                    <template v-if="isCustomerListLoading">
                        <CustomerListSkeleton />
                    </template>

                    <template v-else>
                        <div class="flex w-full flex-col content-start gap-1 overflow-y-auto overflow-x-hidden">
                            <template v-if="customers.length">
                                <div
                                    class="mr-4 flex cursor-pointer justify-between rounded-lg p-2.5 hover:bg-gray-100 dark:hover:bg-gray-700"
                                    v-for="(customer, index) in customers"
                                    :key="index"
                                >
                                    <div
                                        class="flex flex-1 gap-2.5" 
                                        @click="currentCustomer = customer"
                                    >
                                        <template v-if="customer.imageUrl">
                                            <img
                                                :src="customer.imageUrl"
                                                class="h-12 w-12 rounded"
                                                alt="profile image"
                                            >
                                        </template>

                                        <template v-else>
                                            <img
                                                src="/src/Resources/assets/images/user-placeholder.png"
                                                class="h-12 w-12 rounded dark:invert"
                                                alt="profile image"
                                            >
                                        </template>

                                        <div class="grid gap-1">
                                            <p class="text-base font-normal leading-5 text-gray-900 dark:text-gray-100">
                                                {{ customer.firstName }} {{ customer.lastName }}
                                            </p>

                                            <p class="text-base font-normal leading-5 text-neutral-400 dark:text-neutral-400">
                                                {{ customer.email }}
                                            </p>
                                        </div>
                                    </div>

                                    <template v-if="activeTab === 'customers'">
                                        <div class="flex flex-col items-end gap-1">
                                            <p class="text-base font-normal leading-5 text-gray-900 dark:text-gray-100">
                                                {{ customer.createdAt?.split(" ")?.[0]?.split("-")?.reverse()?.join("/") }}
                                            </p>
                                        </div>
                                    </template>

                                    <template v-else-if="isOnline">
                                        <div
                                            class="flex h-12 w-12 cursor-pointer items-center justify-center rounded-md hover:bg-gray-200 dark:hover:bg-gray-900"
                                            @click="syncCustomer(customer)"
                                        >
                                            <span
                                                v-if="syncingCustomerIds.includes(customer.id)"
                                                class="h-5 w-5 animate-spin rounded-full border-4 border-slate-600 border-t-transparent"
                                            >
                                            </span>

                                            <span
                                                v-else
                                                class="icon-sync text-2xl text-gray-900 dark:text-gray-100"
                                            >
                                            </span>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <template v-else>
                                <div class="py-8 text-center text-gray-700 dark:text-gray-300">
                                    <div class="text-gray-400 dark:text-gray-500 text-4xl mb-3">📋</div>

                                    <div class="font-medium mb-1">
                                        {{ $t('pos.customers.no_customers_found') }}
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Pagination Controls -->
                    <Pagination
                        :loading="isCustomerListLoading"
                        :pageInfo="pageInfo"
                        @page-change="goToPage"
                    />
                </div>
            </div>
        </div>

        <Cart v-if="! isMobileOrTab" />
    </div>
</template>

<script setup>
    import { useI18n } from 'vue-i18n';
    import { useRouter } from 'vue-router';
    import { ref, toRaw, computed, onMounted, onBeforeUnmount, inject, watch } from 'vue';
    import { useMutation } from '@vue/apollo-composable';
    import { DELETE } from '@src/graphql/customers';
    import { useIndexedDB } from '@src/composable/indexed-db';
    import { useWindowWidth } from '@src/composable/window';
    import CurrentCustomerSkeleton from '@skeletons/customers/Current.vue';
    import CustomerListSkeleton from '@skeletons/customers/List.vue';
    import { CREATE } from '@src/graphql/customers';

    /**
     * General variables
     */
    const DB = useIndexedDB();
    const { t } = useI18n();
    const router = useRouter();
    const emitter = inject('emitter');
    const isOnline = inject('isOnline');
    const activeTab = ref('customers');
    const { isMobileOrTab } = useWindowWidth();

    /**
     * Setup for current customer
     */
    const customers = ref([]);
    const currentCustomer = ref({});
    const pageInfo = ref({
        currentPage: 1,
        lastPage: 1,
        total: 0,
        count: 0
    });
    const perPage = 5;
    const cartCustomer = ref({});
    const isCustomerListLoading = ref(true);
    const isCurrentCustomerLoading = ref(true);
    const syncingCustomerIds = ref([]);

    onMounted(async () => {
        await getCustomersList();

        cartCustomer.value = await DB.getCartCustomer();

        currentCustomer.value = cartCustomer.value;

        isCurrentCustomerLoading.value = false;
    });

    /**
     * Watch for current customer
     */
    const isSameCustomer = computed(() => {
        return currentCustomer.value?.email == cartCustomer.value?.email;
    });

    const isOfflineCustomer = computed(() => {
        return currentCustomer.value?.isOffline === true;
    });

    /**
     * Get customers list
     */
    const getCustomersList = async (page = 1) => {
        let result = [];

        if (activeTab.value === 'customers') {
            result = await DB.paginateItems('customers', page, perPage);
        } else {
            result = await DB.paginateItems('offline_customers', page, perPage);
        }

        customers.value = result.data;
        pageInfo.value = result.paginatorInfo;

        isCustomerListLoading.value = false;
    };

    /**
     * Search term for customers
     */
    const searchTerm = ref('');

    watch(searchTerm, (newValue) => {
        searchCustomers(newValue);
    });

    const searchCustomers = async (searchTerm = '', page = 1, limit = 5) => {
        isCustomerListLoading.value = true;

        let tableName = activeTab.value === 'customers' ? 'customers' : 'offline_customers';

        const db = await DB.getDb();
        const tx = db.transaction(tableName, 'readonly');
        const store = tx.objectStore(tableName);

        const term = searchTerm.toLowerCase();
        const filteredData = [];

        let cursor = await store.openCursor(null, 'prev');

        while (cursor) {
            const value = cursor.value;

            if (
                ! term
                || (
                    value.firstName.toLowerCase().includes(term)
                    || value.lastName.toLowerCase().includes(term)
                    || value.email.toLowerCase().includes(term)
                )
            ) {
                filteredData.push(value);
            }

            cursor = await cursor.continue();
        }

        const total = filteredData.length;
        const lastPage = Math.ceil(total / limit);
        const offset = (page - 1) * limit;

        const paginatedData = filteredData.slice(offset, offset + limit);

        customers.value = paginatedData;
        pageInfo.value = {
            currentPage: page,
            lastPage: lastPage,
            total: total,
            count: paginatedData.length
        };

        isCustomerListLoading.value = false;
    };

    /**
     * Go to page
     */
    const goToPage = async (page) => {
        isCustomerListLoading.value = true;

        await getCustomersList(page);

        isCustomerListLoading.value = false;
    };

    /**
     * Watch for active tab
     */
    watch(activeTab, async () => {
        isCustomerListLoading.value = true;

        await getCustomersList();

        isCustomerListLoading.value = false;
    });

    /**
     * Sync customer
     */
    const { mutate } = useMutation(CREATE);

    const syncCustomer = async (customer) => {
        if (syncingCustomerIds.value.includes(customer.id)) {
            return;
        }

        emitter.emit('open_confirm_modal', {
            agree: async () => {
                if (! isOnline.value) {
                    emitter.emit('add_flash', {
                        type: 'warning',
                        message: t('pos.common.flash_messages.offline_error'),
                    });

                    return;
                }

                syncingCustomerIds.value.push(customer.id);                

                const { id:customerId, isOffline, ...customerData } = customer;

                const data = await mutate({
                    input: customerData,
                });                

                const { createPosCustomer } = data?.data;

                if (createPosCustomer?.success === true) {
                    emitter.emit('add_flash', {
                        type: 'success',
                        message: createPosCustomer.message,
                    });

                    syncingCustomerIds.value = syncingCustomerIds.value.filter(id => id !== customerId);

                    await DB.updateItem('customers', createPosCustomer.customer);

                    await DB.deleteItem('offline_customers', customerId);

                    await getCustomersList();
                } else if (createPosCustomer?.success === false) {
                    const errors = JSON.parse(createPosCustomer.errors);
                    
                    if (errors?.email) {
                        emitter.emit('add_flash', {
                            type: 'error',
                            message: errors.email[0],
                        });
                    } else if (errors?.phone) {
                        emitter.emit('add_flash', {
                            type: 'error',
                            message: errors.phone[0],
                        });
                    } else {
                        emitter.emit('add_flash', {
                            type: 'error',
                            message: t('pos.common.flash_messages.error_message'),
                        });
                    }
                }
            },
        });
    };    

    /**
     * Methods to change the cart customer
     */
    const changeCurrentCustomer = () => {
        if (isSameCustomer.value) {
            return;
        }

        emitter.emit('open_confirm_modal', {
            agree: async () => {
                const customer = toRaw(currentCustomer.value);

                await DB.deleteAllItems('cart_customer');

                const success = await DB.addItem('cart_customer', customer);
                
                if (success) {
                    cartCustomer.value = customer;

                    emitter.emit('add_flash', {
                        type: 'success',
                        message: t('pos.customers.change_success'),
                    });

                    emitter.emit('customer_changed', customer);
                } else {
                    emitter.emit('add_flash', {
                        type: 'error',
                        message: t('pos.common.flash_messages.error_message'),
                    });
                }
            },
        });
    };

    /**
     * Delete customer
     */
    const { mutate: deleteCustomer, loading: isCustomerDeleting } = useMutation(DELETE);

    const deleteCurrentCustomer = async () => {
        if (isSameCustomer.value) {
            return;
        }
        
        emitter.emit('open_confirm_modal', {
            agree: async () => {
                let customerId = currentCustomer.value.id;

                if (isOfflineCustomer.value) {
                    await DB.deleteItem('offline_customers', customerId);

                    currentCustomer.value = {};

                    emitter.emit('add_flash', {
                        type: 'success',
                        message: t('pos.customers.delete_success'),
                    });
                } else {
                    const { data } = await deleteCustomer({ id: customerId });                

                    if (data?.deletePosCustomer?.success) {
                        await DB.deleteItem('customers', customerId);

                        currentCustomer.value = {};

                        emitter.emit('add_flash', {
                            type: 'success',
                            message: data.deletePosCustomer.message,
                        });
                    } else {
                        emitter.emit('add_flash', {
                            type: 'error',
                            message: data.deletePosCustomer.message,
                        });
                    }
                }

                if (searchTerm.value) {
                    await searchCustomers(searchTerm.value);
                } else {
                    await getCustomersList();
                }
            },
        });
    };
    
    /**
     * Get edit customer page URL
     */
    const getCustomerEditPath = () => {
        if (currentCustomer.value?.id) {
            return `/customers/edit/${currentCustomer.value.id}/${currentCustomer.value.isOffline ? 'offline' : 'online'}`;
        }

        return '';
    };

    /**
     * Keyboard Shortcuts
     */
    onMounted(() => {
        emitter.on('edit_customer', () => {
            if (currentCustomer.value?.id) {
                router.push({ path: getCustomerEditPath() });
            }
        });

        emitter.on('change_customer', () => {
            if (currentCustomer.value?.id) {
                changeCurrentCustomer();
            }
        });

        emitter.on('delete_customer', () => {
            if (currentCustomer.value?.id) {
                deleteCurrentCustomer();
            }
        });
    });

    onBeforeUnmount(() => {
        emitter.off('edit_customer');

        emitter.off('change_customer');

        emitter.off('delete_customer');
    });
</script>

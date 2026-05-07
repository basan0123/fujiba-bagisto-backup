<template>
    <div class="box-shadow grid gap-6 rounded-lg bg-white dark:bg-gray-900 p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Sync entities section -->
            <div class="flex flex-col gap-6">
                <div class="flex flex-col">
                    <span class="text-base font-medium text-gray-700 dark:text-gray-200">
                        {{ $t('pos.settings.sync.entities') }}
                    </span>

                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $t('pos.settings.sync.entities_desc') }}
                    </span>
                </div>

                <div class="flex space-x-2.5">
                    <div
                        v-for="(entity, entityName) in syncEntities"
                        :key="entityName"
                        @click="toggleEntitySelection(entityName)"
                        class="grid h-20 w-20 cursor-pointer place-items-center content-center rounded-lg hover:text-slate-600 dark:hover:text-white border-2"
                        :class="[
                            entitySelections[entityName]
                                ? 'text-slate-600 border-slate-600 bg-gray-100 dark:text-white dark:border-white dark:bg-gray-900'
                                : 'text-neutral-400 dark:text-neutral-500 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900'
                        ]"
                    >
                        <i
                            class="icon text-2xl"
                            :class="entity.icon"
                        >
                        </i>

                        <span class="text-sm font-medium">
                            {{ $t(`pos.settings.sync.${entityName}`) }}
                        </span>

                        <input
                            type="checkbox"
                            v-model="entitySelections[entityName]"
                            class="hidden"
                        />
                    </div>
                </div>

                <div class="flex space-x-2.5">
                    <button
                        class="flex-1 px-3 py-1.5 text-sm font-medium text-center rounded-md border bg-gray-100 text-gray-800 hover:bg-gray-200 border-gray-300 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-600 dark:border-gray-600"
                        @click="selectAllEntities"
                    >
                        {{ $t('pos.settings.sync.select_all') }}
                    </button>

                    <button
                        class="flex-1 px-3 py-1.5 text-sm font-medium text-center rounded-md border bg-gray-100 text-gray-800 hover:bg-gray-200 border-gray-300 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-600 dark:border-gray-600"
                        @click="deselectAllEntities"
                    >
                        {{ $t('pos.settings.sync.deselect_all') }}
                    </button>
                </div>

                <Button
                    class="secondary-button w-full"
                    :label="$t('pos.settings.sync.start_syncing')"
                    :icon="'icon-sync'"
                    :isLoading="isAnySyncRunning"
                    :disabled="!hasAnyEntitySelected"
                    @click="initiateSyncProcess"
                />
            </div>

            <!-- Progress section -->
            <div class="grid gap-4">
                <div
                    v-for="(entity, entityName) in syncEntities"
                    :key="entityName"
                    class="p-3 border rounded-lg transition"
                    :class="[
                        entitySelections[entityName]
                            ? 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-400'
                            : 'bg-white border-gray-200 dark:bg-gray-900 dark:border-gray-600'
                    ]"
                >
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">
                            {{ $t(`pos.settings.sync.${entityName}`) }}
                        </span>

                        <span class="text-sm font-bold text-gray-700 dark:text-gray-100">
                            {{ entity.syncProgress }}%
                        </span>
                    </div>

                    <div class="w-full bg-gray-200 dark:bg-gray-800 rounded h-1.5">
                        <div
                            class="bg-slate-600 dark:bg-gray-300 h-1.5 rounded"
                            :style="{ width: `${entity.syncProgress}%` }"
                        >
                        </div>
                    </div>

                    <div class="text-xs mt-1.5 flex justify-between text-gray-600 dark:text-gray-400">
                        <span>
                            {{ entity.syncedCount }} / {{ entity.totalRecords }}
                            {{ $t('pos.settings.sync.records') }}
                        </span>

                        <span
                            v-if="entity.isActive"
                            class="text-blue-600"
                        >
                            {{ $t('pos.settings.sync.fetching') }}
                        </span>

                        <span
                            v-else-if="entity.syncProgress === 100"
                            class="text-green-600 dark:text-green-400"
                        >
                            {{ $t('pos.settings.sync.completed') }}
                        </span>

                        <span
                            v-else
                            class="text-gray-500 dark:text-gray-500"
                        >
                            {{ $t('pos.settings.sync.ready') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
    import { computed, reactive, inject, nextTick } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useApolloClient } from '@vue/apollo-composable';
    import { PRODUCTS } from '@src/graphql/home';
    import { CUSTOMERS } from '@src/graphql/customers';
    import { ORDERS } from '@src/graphql/orders';
    import { useIndexedDB } from '@src/composable/indexed-db';

    /**
     * General use variables
     */
    const { t } = useI18n();
    const apolloClient = useApolloClient();
    const DB = useIndexedDB();
    const emitter = inject('emitter');
    const isOnline = inject('isOnline');

    const entitySelections = reactive({
        products: true,
        customers: true,
        orders: true
    });

    const syncEntities = reactive({
        products: {
            gqlQuery: PRODUCTS,
            syncedData: [],
            syncProgress: 0,
            dataKey: 'getOutletProducts',
            storageKey: 'products',
            icon: 'icon-products',
            isActive: false,
            totalRecords: 0,
            syncedCount: 0,
            batchSize: 20,
        },
        customers: {
            gqlQuery: CUSTOMERS,
            syncedData: [],
            syncProgress: 0,
            dataKey: 'getCustomers',
            storageKey: 'customers',
            icon: 'icon-customers',
            isActive: false,
            totalRecords: 0,
            syncedCount: 0,
            batchSize: 20,
        },
        orders: {
            gqlQuery: ORDERS,
            syncedData: [],
            syncProgress: 0,
            dataKey: 'getOrders',
            storageKey: 'orders',
            icon: 'icon-orders',
            isActive: false,
            totalRecords: 0,
            syncedCount: 0,
            batchSize: 20,
        }
    });

    const isAnySyncRunning = computed(() => {
        return Object.values(syncEntities).some(entity => entity.isActive);
    });

    const hasAnyEntitySelected = computed(() => {
        return Object.values(entitySelections).some(selected => selected === true);
    });

    const toggleEntitySelection = (entityName) => {
        entitySelections[entityName] = !entitySelections[entityName];
    };

    const selectAllEntities = () => {
        Object.keys(entitySelections).forEach(entityName => {
            entitySelections[entityName] = true;
        });
    };

    const deselectAllEntities = () => {
        Object.keys(entitySelections).forEach(entityName => {
            entitySelections[entityName] = false;
        });
    };

    const fetchEntityData = async (entityName) => {
        const entity = syncEntities[entityName];
        let currentPage = 1;
        let hasMorePages = true;
        
        entity.isActive = true;
        entity.syncProgress = 0;
        entity.syncedData = [];
        entity.syncedCount = 0;
        entity.totalRecords = 0;

        try {
            while (hasMorePages) {
                const { data } = await apolloClient.client.query({
                    query: entity.gqlQuery,
                    variables: {
                        page: currentPage,
                        first: entity.batchSize
                    },
                });

                const entityData = data?.[entity.dataKey];
                
                if (! entityData) {
                    break;
                }

                const items = entityData.data || [];
                const paginatorInfo = entityData.paginatorInfo || {};
                const total = paginatorInfo.total || 0;
                const lastPage = paginatorInfo.lastPage || 1;

                if (currentPage === 1) {
                    entity.totalRecords = total;
                }

                const processingPromises = items.map(async (item) => {
                    const existingIndex = entity.syncedData.findIndex(existing => existing.id === item.id);
                    
                    if (existingIndex === -1) {
                        entity.syncedData.push(item);
                        entity.syncedCount++;
                        
                        try {
                            await DB.updateItem(entity.storageKey, JSON.parse(JSON.stringify(item)));
                        } catch (dbError) {
                            console.warn(`Failed to store ${entityName} item ${item.id}:`, dbError);
                        }
                    }
                });

                await Promise.allSettled(processingPromises);

                if (entity.totalRecords > 0) {
                    entity.syncProgress = Math.min(
                        Math.round((entity.syncedCount / entity.totalRecords) * 100),
                        100
                    );
                } else {
                    entity.syncProgress = 100;
                }

                hasMorePages = currentPage < lastPage;
                currentPage++;

                if (hasMorePages) {
                    await new Promise(resolve => setTimeout(resolve, 100));
                }
            }

            entity.syncProgress = 100;
        } catch (error) {
            console.error(`Error syncing ${entityName}:`, error);
        } finally {
            entity.isActive = false;
        }
    };

    const initiateSyncProcess = async () => {
        if (! isOnline.value) {
            emitter.emit('add_flash', {
                type: 'warning',
                message: t('pos.common.flash_messages.offline_error'),
            });

            return;
        }

        const selectedEntityNames = Object.keys(entitySelections)
            .filter(entityName => entitySelections[entityName]);

        if (selectedEntityNames.length === 0) {
            return;
        }

        await Promise.all(selectedEntityNames.map(async (entityName) => {
            const entity = syncEntities[entityName];

            entity.syncProgress = 0;
            entity.syncedData = [];
            entity.syncedCount = 0;
            entity.totalRecords = 0;

            await DB.deleteAllItems(entity.storageKey);
        }));

        await nextTick();

        const concurrencyLimit = 2;
        const executingPromises = [];

        for (let i = 0; i < selectedEntityNames.length; i += concurrencyLimit) {
            const batch = selectedEntityNames.slice(i, i + concurrencyLimit);
            const batchPromises = batch.map(entityName => fetchEntityData(entityName));
            
            executingPromises.push(...batchPromises);
            
            await Promise.allSettled(batchPromises);
        }

        await Promise.allSettled(executingPromises);
    };
</script>
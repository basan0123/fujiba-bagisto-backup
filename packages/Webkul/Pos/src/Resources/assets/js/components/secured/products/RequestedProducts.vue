<template>
    <div class="box-shadow grid gap-4 rounded-lg bg-white dark:bg-gray-900 py-1 px-4">
        <div class="relative flex items-center text-gray-900 dark:text-gray-100">
            <input
                type="text"
                class="h-12 w-full px-8 text-base font-normal leading-5 text-gray-900 dark:text-white bg-transparent placeholder-gray-400 dark:placeholder-gray-300 border-b"
                :placeholder="$t('pos.products.requested_products.search_products')"
                v-model="query"
            >
            
            <i class="icon-search absolute left-0 top-3.5 text-xl text-gray-500 dark:text-gray-300"></i>
        </div>

        <template v-if="loading">
            <RequestedProductSkeleton />
        </template>

        <template v-else>
            <template v-if="products.length">
                <div class="grid w-full content-start gap-2">
                    <div
                        class="grid gap-4 rounded-lg p-2"
                        :class="[index % 2 === 0 ? 'bg-gray-100 dark:bg-gray-800' : 'bg-white dark:bg-gray-900']"
                        v-for="(product, index) in products"
                        :key="index"
                    >
                        <div class="flex justify-between gap-y-2.5 max-sm:flex-col md:items-center">
                            <p class="text-base font-medium leading-5 text-gray-900 dark:text-gray-100">
                                {{ product.name }}
                            </p>

                            <div class="flex flex-wrap justify-between gap-6">
                                <p class="text-base leading-5 text-gray-900 dark:text-gray-200">
                                    {{ $t('pos.products.requested_products.qty', { qty: product.requestedQuantity }) }}
                                </p>

                                <template v-if="product.requestStatus === '1'">
                                    <span class="label-success">
                                        {{ $t('pos.products.requested_products.received') }}
                                    </span>
                                </template>

                                <template v-else-if="product.requestStatus === '2'">
                                    <span class="label-closed">
                                        {{ $t('pos.products.requested_products.declined') }}
                                    </span>
                                </template>

                                <template v-else>
                                    <span class="label-pending">
                                        {{ $t('pos.products.requested_products.pending') }}
                                    </span>
                                </template>

                                <p class="text-base leading-5 text-gray-900 dark:text-gray-300">
                                    {{ product.createdAt }}
                                </p>
                            </div>
                        </div>

                        <p class="text-justify text-base leading-5 text-gray-900 dark:text-gray-200">
                            {{ product.comment }}
                        </p>
                    </div>
                </div>
            </template>

            <template v-else>
                <div class="mb-4 flex items-center justify-center">
                    <p class="text-base font-normal leading-5 text-gray-900 dark:text-gray-100">
                        {{ $t('pos.products.requested_products.no_products') }}
                    </p>
                </div>
            </template>
        </template>

        <!-- Pagination Controls -->
        <Pagination
            :loading="loading"
            :pageInfo="pageInfo"
            @page-change="goToPage"
        />
    </div>
</template>

<script setup>
    import { ref, computed, watch, inject } from 'vue';
    import { useQuery } from '@vue/apollo-composable';
    import { GET_REQUESTED_PRODUCTS } from '@src/graphql/products';
    import RequestedProductSkeleton from '@skeletons/products/Requested.vue';

    /**
     * General Variables
     */
    const isOnline = inject('isOnline');

    /**
     * GraphQL query to get customers
     */
    const page = ref(1);
    const first = ref(4);
    const query = ref('');

    const variables = computed(() => ({
        page: page.value,
        first: first.value,
        query: query.value,
    }));

    const { result, loading, refetch } = useQuery(GET_REQUESTED_PRODUCTS, variables, {
        skip: computed(() => !isOnline.value)
    });

    const products = computed(() => result.value?.getRequestedProducts.data ?? []);
    const pageInfo = computed(() => result.value?.getRequestedProducts.paginatorInfo ?? {});

    watch(query, () => {
        refetch(variables.value);
    });

    /**
     * Go to a specific page
     */
    const goToPage = (newPage) => {
        page.value = newPage;
        
        refetch(variables.value);
    };
</script>
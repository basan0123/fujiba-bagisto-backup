<template>
    <div class="my-4 grid gap-4">
        <div class="grid gap-4 select-none">
            <div class="flex gap-x-4 gap-y-2 max-sm:flex-col">
                <div class="flex gap-2.5 rounded-lg bg-white dark:bg-gray-900 py-2 md:h-12 px-3">
                    <div
                        v-on:click="activeTab = tab"
                        :class="[
                            activeTab === tab
                                ? 'bg-gray-100 text-slate-600 !border-slate-600 dark:bg-gray-800 dark:text-slate-300 dark:!border-slate-300'
                                : 'text-gray-900 dark:text-gray-200'
                        ]"
                        class="flex cursor-pointer items-center rounded-lg border-2 border-transparent px-3 py-2 text-base font-medium"
                        v-for="(tab, index) in tabs"
                        :key="index"
                    >
                        {{ $t(`pos.reports.${tab}`) }}
                    </div>
                </div>

                <div class="flex gap-2.5 rounded-lg bg-white dark:bg-gray-900 py-2 md:h-12 px-3">
                    <Field
                        :type="'date'"
                        :name="'start_date'"
                        :id="'start_date'"
                        className="!py-1 border-2 border-slate-600 ltr:pl-2 rtl:pr-2 rounded-lg"
                        v-model="startDate"
                    />

                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                        -
                    </div>

                    <Field
                        :type="'date'"
                        :name="'end_date'"
                        :id="'end_date'"
                        className="!py-1 border-2 border-slate-600 ltr:pl-2 rtl:pr-2 rounded-lg"
                        v-model="endDate"
                    />
                </div>
            </div>
        </div>

        <template v-if="loading">
            <GraphSkeleton />
        </template>

        <template v-else>
            <div class="grid gap-4 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3">
                <div
                    class="box-shadow rounded-lg bg-white dark:bg-gray-900 p-4"
                    v-for="(chart, index) in charts"
                    :key="index"
                >
                    <div class="flex justify-between">
                        <div class="grid gap-1.5">
                            <div class="text-lg font-medium leading-6 text-neutral-400 dark:text-neutral-300">
                                {{ chart?.name }}
                            </div>

                            <div class="text-3xl font-semibold text-gray-900 dark:text-white">
                                <template v-if="['ordersCount', 'avgItemsPerOrder'].includes(index)">
                                    {{ chart.total }}
                                </template>

                                <template v-else>
                                    {{ formatPrice(chart.total) }}
                                </template>
                            </div>
                        </div>

                        <template v-if="chart.goingUp">
                            <div class="flex h-10 items-center self-end rounded-lg bg-blue-100 dark:bg-blue-900 px-2.5">
                                <span class="text-lg font-medium leading-6 text-blue-600 dark:text-blue-300">
                                    {{ chart.progress }}
                                </span>

                                <span class="icon-arrow-up text-2xl text-blue-600 dark:text-blue-300">
                                </span>
                            </div>
                        </template>

                        <template v-else>
                            <div class="flex h-10 items-center self-end rounded-lg bg-red-100 dark:bg-red-900 px-2.5">
                                <span class="text-lg font-medium leading-6 text-red-600 dark:text-red-300">
                                    {{ chart.progress }}
                                </span>

                                <span class="icon-arrow-down text-2xl text-red-600 dark:text-red-300">
                                </span>
                            </div>
                        </template>
                    </div>

                    <Chart
                        :series="chart?.series"
                        :labels="chart?.labels"
                    />
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
    import { ref, computed, watch, inject } from 'vue';
    import Chart from '@components/secured/common/Chart.vue';
    import { useQuery } from '@vue/apollo-composable';
    import { GET_SALE_REPORT } from '@src/graphql/reports';
    import GraphSkeleton from '@skeletons/reports/Graph.vue';
    import { useOutlet } from '@src/composable/outlet';

    /**
     * General variables
     */
    const { formatPrice } = useOutlet();
    const tabs = ['day', 'week', 'month'];
    const activeTab = ref('day');
    const startDate = ref(new Date().toISOString().split('T')[0]);
    const endDate = ref(new Date().toISOString().split('T')[0]);
    const isOnline = inject('isOnline');

    /**
     * Get the chart data
     */
    const { result, loading, refetch } = useQuery(GET_SALE_REPORT, {
        startDate: startDate.value,
        endDate: endDate.value,
    }, {
        skip: computed(() => !isOnline.value)
    });

    const charts = computed(() => result.value?.getSaleReport.reports ?? []);

    /**
     * Watchers to refetch the data
     */
    watch([startDate, endDate], () => {
        refetch({
            startDate: startDate.value,
            endDate: endDate.value,
        });
    });

    watch(activeTab, (value) => {
        if (value == 'day') {
            startDate.value = new Date().toISOString().split('T')[0];
        } else if (value == 'week') {
            startDate.value = new Date(new Date().setDate(new Date().getDate() - 7)).toISOString().split('T')[0];
        } else if (value == 'month') {
            startDate.value = new Date(new Date().setMonth(new Date().getMonth() - 1)).toISOString().split('T')[0];
        }

        endDate.value = new Date().toISOString().split('T')[0];

        refetch({
            startDate: startDate.value,
            endDate: endDate.value,
        });
    });
</script>
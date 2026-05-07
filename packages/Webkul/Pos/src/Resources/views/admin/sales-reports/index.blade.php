<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('pos::app.admin.sales-reports.index.title')
    </x-slot:title>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-white font-bold">
            @lang('pos::app.admin.sales-reports.index.title')
        </p>

        <div class="flex gap-x-2.5 items-center">
            <!-- Export Modal -->
            <x-admin::datagrid.export src="{{ route('admin.pos.sales_report.index') }}" />
        </div>
    </div>

    <x-admin::datagrid :src="route('admin.pos.sales_report.index')" :isMultiRow="true">
        <template #header="{
            isLoading,
            available,
            applied,
            selectAll,
            sort,
            performAction
        }">
            <template v-if="isLoading">
                <x-admin::shimmer.datagrid.table.head :isMultiRow="true" />
            </template>

            <template v-else>
                <div class="row grid grid-cols-3 grid-rows-1 items-center border-b px-4 py-2.5 dark:border-gray-800">
                    <div
                        class="flex select-none items-center gap-2.5"
                        v-for="(columnGroup, index) in [['order_increment_id', 'created_at', 'order_status'], ['base_grand_total', 'method_title', 'order_note'], ['outlet_name', 'bank_name']]"
                    >
                        <p class="text-gray-600 dark:text-gray-300">
                            <span class="[&>*]:after:content-['_/_']">
                                <template v-for="column in columnGroup">
                                    <span
                                        class="after:content-['/'] last:after:content-['']"
                                        :class="{
                                            'font-medium text-gray-800 dark:text-white': applied.sort.column == column,
                                            'cursor-pointer hover:text-gray-800 dark:hover:text-white': available.columns.find(columnTemp => columnTemp.index === column)?.sortable,
                                        }"
                                        @click="
                                            available.columns.find(columnTemp => columnTemp.index === column)?.sortable ? sort(available.columns.find(columnTemp => columnTemp.index === column)): {}
                                        "
                                    >
                                        @{{ available.columns.find(columnTemp => columnTemp.index === column)?.label }}
                                    </span>
                                </template>
                            </span>

                            <i
                                class="align-text-bottom text-base text-gray-800 dark:text-white ltr:ml-1.5 rtl:mr-1.5"
                                :class="[applied.sort.order === 'asc' ? 'icon-down-stat': 'icon-up-stat']"
                                v-if="columnGroup.includes(applied.sort.column)"
                            >
                            </i>
                        </p>
                    </div>
                </div>
            </template>
        </template>

        <template #body="{
            isLoading,
            available,
            applied,
            selectAll,
            sort,
            performAction
        }">
            <template v-if="isLoading">
                <x-admin::shimmer.datagrid.table.body :isMultiRow="true" />
            </template>

            <template v-else>
                <div
                    class="row grid grid-cols-3 border-b px-4 py-2.5 transition-all hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-950"
                    v-for="record in available.records"
                >
                    <!-- Order Id, Order Date, Status -->
                    <div class="">
                        <div class="flex gap-2.5">
                            <div class="flex flex-col gap-1.5">
                                <p
                                    class="text-base font-semibold text-gray-800 dark:text-white"
                                >
                                    @{{ "@lang('pos::app.admin.sales-reports.index.datagrid.order-id-value')".replace(':id', record.order_increment_id) }}
                                </p>

                                <p class="text-gray-600 dark:text-gray-300">
                                    @{{ record.created_at }}
                                </p>

                                <p v-html="record.order_status"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Total, Payment Method, Order Note -->
                    <div class="">
                        <div class="flex flex-col gap-1.5">
                            <p class="text-base font-semibold text-gray-800 dark:text-white">
                                @{{ $admin.formatPrice(record.base_grand_total) }}
                            </p>

                            <p class="text-gray-600 dark:text-gray-300">
                                @{{ record.method_title ?? '-' }}
                            </p>

                            <p class="text-gray-600 dark:text-gray-300">
                                @{{ record.order_note ?? '-' }}
                            </p>
                        </div>
                    </div>

                    <!-- Outlet Name, Sales Type, Bank Name -->
                    <div class="flex justify-between gap-1.5">
                        <div class="flex flex-col gap-1.5">
                            <p class="text-base text-gray-800 dark:text-white">
                                @{{ record.outlet_name ?? '-' }}
                            </p>

                            <p class="text-gray-600 dark:text-gray-300">
                                @{{ record.bank_name ?? '-' }}
                            </p>
                        </div>

                        <a
                            v-for="action in record.actions"
                            :href="action.url"
                        >
                            <span
                                class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 dark:hover:bg-gray-800 ltr:ml-1 rtl:mr-1"
                                :class="action.icon"
                            ></span>
                        </a>
                    </div>
                </div>
            </template>
        </template>
    </x-admin::datagrid>
</x-admin::layouts>
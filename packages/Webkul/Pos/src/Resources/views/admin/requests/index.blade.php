<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('pos::app.admin.requests.index.title')
    </x-slot:title>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-white font-bold py-3">
            @lang('pos::app.admin.requests.index.title')
        </p>
    </div>

    <!-- Datagrid -->
    <x-admin::datagrid
        :src="route('admin.pos.requests.index')"
        :isMultiRow="true"
    >
        <!-- Datagrid Header -->
        @php
            $hasPermission = bouncer()->hasPermission('pos.requests.edit');
        @endphp

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
                <div class="row grid grid-cols-[2fr_1fr_1fr] grid-rows-1 items-center border-b px-4 py-2.5 dark:border-gray-800">
                    <div
                        class="flex select-none items-center gap-2.5"
                        v-for="(columnGroup, index) in [['product_name', 'quantity', 'request_date'], ['user_name', 'outlet_name', 'request_status'], ['product_image']]"
                    >
                        @if ($hasPermission)
                            <label
                                class="flex w-max cursor-pointer select-none items-center gap-1"
                                for="mass_action_select_all_records"
                                v-if="! index"
                            >
                                <input
                                    type="checkbox"
                                    name="mass_action_select_all_records"
                                    id="mass_action_select_all_records"
                                    class="peer hidden"
                                    :checked="['all', 'partial'].includes(applied.massActions.meta.mode)"
                                    @change="selectAll"
                                >

                                <span
                                    class="icon-uncheckbox cursor-pointer rounded-md text-2xl"
                                    :class="[
                                        applied.massActions.meta.mode === 'all' ? 'peer-checked:icon-checked peer-checked:text-blue-600' : (
                                            applied.massActions.meta.mode === 'partial' ? 'peer-checked:icon-checkbox-partial peer-checked:text-blue-600' : ''
                                        ),
                                    ]"
                                >
                                </span>
                            </label>
                        @endif

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
                            ></i>
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
                    class="row grid grid-cols-[2fr_1fr_1fr] grid-rows-1 border-b px-4 py-2.5 transition-all hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-950"
                    v-for="record in available.records"
                >
                    <!-- Product Name, QTY, Request Date -->
                    <div class="flex gap-2.5">
                        @if ($hasPermission)
                            <input
                                type="checkbox"
                                :name="`mass_action_select_record_${record.request_id}`"
                                :id="`mass_action_select_record_${record.request_id}`"
                                :value="record.request_id"
                                class="peer hidden"
                                v-model="applied.massActions.indices"
                            >

                            <label
                                class="icon-uncheckbox peer-checked:icon-checked cursor-pointer rounded-md text-2xl peer-checked:text-blue-600"
                                :for="`mass_action_select_record_${record.request_id}`"
                            ></label>
                        @endif

                        <div class="flex flex-col gap-1.5">
                            <p class="text-base font-semibold text-gray-800 dark:text-white">
                                @{{ record.product_name }}
                            </p>

                            <p class="text-gray-600 dark:text-gray-300">
                                @{{ "@lang('pos::app.admin.requests.index.datagrid.qty-value')".replace(':qty', record.quantity) }}
                            </p>

                            <p class="text-gray-600 dark:text-gray-300">
                                @{{ record.request_date }}
                            </p>
                        </div>
                    </div>

                    <!-- User Name, Outlet Name, Request Status -->
                    <div class="flex flex-col gap-1.5">
                        <p class="text-base font-semibold text-gray-800 dark:text-white">
                            @{{ record.user_name }}
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            @{{ record.outlet_name }}
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            <template v-if="record.request_status === 1">
                                <span class="label-completed">
                                    @lang('pos::app.admin.requests.index.datagrid.status.options.complete')
                                </span>
                            </template>

                            <template v-else-if="record.request_status === 2">
                                <span class="label-canceled">
                                    @lang('pos::app.admin.requests.index.datagrid.status.options.decline')
                                </span>
                            </template>

                            <template v-else>
                                <span class="label-pending">
                                    @lang('pos::app.admin.requests.index.datagrid.status.options.pending')
                                </span>
                            </template>
                        </p>
                    </div>

                    <!-- Image, Category, Type Columns -->
                    <div class="flex items-center justify-between gap-x-4">
                        <div class="relative">
                            <template v-if="record.product_image">
                                <img
                                    class="max-h-[65px] min-h-[65px] min-w-[65px] max-w-[65px] rounded"
                                    :src="`{{ Storage::url('') }}${record.product_image}`"
                                />
                            </template>

                            <template v-else>
                                <div class="relative h-[60px] max-h-[60px] w-full max-w-[60px] rounded border border-dashed border-gray-300 dark:border-gray-800 dark:mix-blend-exclusion dark:invert">
                                    <img src="{{ bagisto_asset('images/product-placeholders/front.svg')}}">

                                    <p class="absolute bottom-1.5 w-full text-center text-[6px] font-semibold text-gray-400">
                                        @lang('pos::app.admin.barcode-products.index.datagrid.product-image')
                                    </p>
                                </div>
                            </template>
                        </div>

                        <div class="flex items-center gap-1.5">
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
                </div>
            </template>
        </template>
    </x-admin::datagrid>
</x-admin::layouts>
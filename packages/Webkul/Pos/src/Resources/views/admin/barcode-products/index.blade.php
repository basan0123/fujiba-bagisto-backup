<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('pos::app.admin.barcode-products.index.title')
    </x-slot:title>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-white font-bold py-3">
            @lang('pos::app.admin.barcode-products.index.title')
        </p>
    </div>

    <!-- Datagrid -->
    <x-admin::datagrid
        :src="route('admin.pos.barcode_products.index')"
        :isMultiRow="true"
    >
        <!-- Datagrid Header -->
        @php
            $hasPermission = bouncer()->hasPermission('pos.barcode_products.generate_barcode') || bouncer()->hasPermission('pos.barcode_products.print_barcode');
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
                @if ($hasPermission)
                    <div class="md:hidden flex items-center gap-2.5 border-b px-4 py-2.5 dark:border-gray-800">
                        <label class="flex w-max cursor-pointer select-none items-center gap-1" for="mass_action_select_all_records_mobile">
                            <input
                                type="checkbox"
                                name="mass_action_select_all_records_mobile"
                                id="mass_action_select_all_records_mobile"
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
                            ></span>
                        </label>
                        <span class="text-sm text-gray-600 dark:text-gray-300">@{{ available.records.length }} @lang('pos::app.admin.barcode-products.index.title')</span>
                    </div>
                @endif

                <div class="row grid grid-cols-[2fr_1fr_1fr] grid-rows-1 items-center border-b px-4 py-2.5 dark:border-gray-800 max-md:hidden">
                    <div
                        class="flex select-none items-center gap-2.5"
                        v-for="(columnGroup, index) in [['product_name', 'product_sku', 'status'], ['product_image', 'product_price', 'product_quantity', 'product_id'], ['barcode', 'gtin']]"
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
                    class="row grid grid-cols-[2fr_1fr_1fr] grid-rows-1 border-b px-4 py-2.5 transition-all hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-950 max-md:grid-cols-1 max-md:gap-3"
                    v-for="record in available.records"
                >
                    <!-- Name, SKU, Attribute Family Columns -->
                    <div class="flex gap-2.5">
                        @if ($hasPermission)
                            <input
                                type="checkbox"
                                :name="`mass_action_select_record_${record.product_id}`"
                                :id="`mass_action_select_record_${record.product_id}`"
                                :value="record.product_id"
                                class="peer hidden"
                                v-model="applied.massActions.indices"
                            >

                            <label
                                class="icon-uncheckbox peer-checked:icon-checked cursor-pointer rounded-md text-2xl peer-checked:text-blue-600"
                                :for="`mass_action_select_record_${record.product_id}`"
                            ></label>
                        @endif

                        <div class="flex flex-col gap-1.5">
                            <p class="text-base font-semibold text-gray-800 dark:text-white">
                                @{{ record.product_name }}
                            </p>

                            <p class="text-gray-600 dark:text-gray-300">
                                @{{ "@lang('pos::app.admin.barcode-products.index.datagrid.sku-value')".replace(':sku', record.product_sku) }}
                            </p>

                            <p class="text-gray-600 dark:text-gray-300">
                                @{{ "@lang('pos::app.admin.barcode-products.index.datagrid.id-value')".replace(':id', record.product_id) }}
                            </p>
                        </div>
                    </div>

                    <!-- Image, Price, Id, Stock Columns -->
                    <div class="flex gap-1.5">
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

                        <div class="flex flex-col gap-1.5">
                            <p class="text-base font-semibold text-gray-800 dark:text-white">
                                @{{ $admin.formatPrice(record.product_price) }}
                            </p>

                            <!-- Parent Product Quantity -->
                            <div v-if="['configurable', 'bundle', 'grouped'].includes(record.type)">
                                <p class="text-gray-600 dark:text-gray-300">
                                    <span class="text-red-600">N/A</span>
                                </p>
                            </div>

                            <div v-else>
                                <p
                                    class="text-gray-600 dark:text-gray-300"
                                    v-if="record.product_quantity > 0"
                                >
                                    <span class="text-green-600">
                                        @{{ "@lang('pos::app.admin.barcode-products.index.datagrid.qty-value')".replace(':qty', record.product_quantity) }}
                                    </span>
                                </p>

                                <p
                                    class="text-gray-600 dark:text-gray-300"
                                    v-else
                                >
                                    <span class="text-red-600">
                                        @lang('pos::app.admin.barcode-products.index.datagrid.out-of-stock')
                                    </span>
                                </p>
                            </div>
                            
                            <p :class="[record.status ? 'label-active': 'label-info']">
                                @{{ record.status ? "@lang('pos::app.admin.barcode-products.index.datagrid.status.options.active')" : "@lang('pos::app.admin.barcode-products.index.datagrid.status.options.disable')" }}
                            </p>
                        </div>
                    </div>

                    <!-- Barcode Image + GTIN + Type Column -->
                    <div class="flex items-center justify-between gap-x-4 max-md:flex-wrap max-md:gap-y-2">
                        <div class="flex flex-col gap-2 max-md:w-full">
                            <template v-if="record.barcode_url">
                                <img
                                    class="max-h-[65px] max-w-[130px]"
                                    :src="record.barcode_url"
                                />
                            </template>

                            <template v-else>
                                <p class="text-gray-600 dark:text-gray-300">N/A</p>
                            </template>

                            @if (bouncer()->hasPermission('pos.barcode_products.generate_barcode'))
                                {{-- GTIN / EAN-13 / UPC-A input — the real scanner value --}}
                                <div class="flex gap-1 max-md:w-full">
                                    <input
                                        type="text"
                                        class="w-32 max-md:w-full max-md:flex-1 rounded border border-gray-300 bg-white px-2 py-1 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                        :value="record.gtin ?? ''"
                                        :placeholder="'@lang('pos::app.admin.barcode-products.index.datagrid.gtin-placeholder')'"
                                        :id="`gtin_${record.product_id}`"
                                    />

                                    <button
                                        type="button"
                                        class="rounded bg-blue-600 px-2 py-1 text-xs text-white hover:bg-blue-700"
                                        @click="
                                            const val = document.getElementById(`gtin_${record.product_id}`).value.trim();
                                            if (!val) return;
                                            fetch(`{{ rtrim(route('admin.pos.barcode_products.update_gtin', ''), '/') }}/${record.product_id}`, {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                                body: JSON.stringify({ gtin: val, barcode_type: record.barcode_type ?? '' })
                                            }).then(r => r.json()).then(() => performAction({ type: 'reload' }))
                                        "
                                    >@lang('pos::app.admin.barcode-products.index.datagrid.gtin-save')</button>
                                </div>

                                {{-- Barcode symbology selector —  regenerates on change --}}
                                <select
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 max-md:w-full"
                                    :value="record.barcode_type ?? ''"
                                    @change="
                                        fetch(`{{ rtrim(route('admin.pos.barcode_products.update_barcode_type', ''), '/') }}/${record.product_id}`, {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': `{{ csrf_token() }}`
                                            },
                                            body: JSON.stringify({ barcode_type: $event.target.value })
                                        }).then(r => r.json()).then(() => performAction({ type: 'reload' }))
                                    "
                                >
                                    <option value="">@lang('pos::app.admin.barcode-products.index.datagrid.type-default')</option>
                                    <option value="code128">Code 128</option>
                                    <option value="code128a">Code 128A</option>
                                    <option value="code128b">Code 128B</option>
                                    <option value="code39">Code 39</option>
                                    <option value="code25">Code 25</option>
                                    <option value="codabar">Codabar</option>
                                </select>
                            @endif
                        </div>

                        <div class="flex items-center gap-1.5 max-md:w-full max-md:justify-end max-md:border-t max-md:border-gray-100 max-md:dark:border-gray-800 max-md:pt-2">
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
<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('pos::app.admin.users.outlets.assign.title')
    </x-slot:title>

    @php
        $channels         = core()->getAllChannels();
        $inventorySources = app(\Webkul\Inventory\Repositories\InventorySourceRepository::class)->findWhere(['status' => 1]);

        // The datagrid auto-scopes to the channel whose code matches the outlet
        // name (e.g. outlet "MMFOOD" → channel "mmfood"). Mirror that default
        // here so the dropdown reflects what the grid is actually showing.
        $defaultChannel = \Illuminate\Support\Facades\DB::table('channels')
            ->whereRaw('LOWER(code) = ?', [mb_strtolower($posOutlet->name)])
            ->value('code') ?: '';
        $selectedChannel = request()->has('product_channel')
            ? (string) request('product_channel')
            : $defaultChannel;
        $selectedInvSrc  = request('inventory_source_id', '');
        $queryParams     = array_filter([
            'product_channel'     => $selectedChannel,
            'inventory_source_id' => $selectedInvSrc,
        ]);
        $datagridSrc = route('admin.pos.outlets.assign_products', $posOutlet->id)
            . ($queryParams ? '?' . http_build_query($queryParams) : '');
    @endphp

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-white font-bold">
            @lang('pos::app.admin.users.outlets.assign.title')
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400 ml-2">— {{ $posOutlet->name }}</span>
        </p>

        <!-- Back Button -->
        <a
            href="{{ route('admin.pos.outlets.index') }}"
            class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
        >
            @lang('pos::app.admin.users.outlets.assign.back-btn')
        </a>
    </div>

    {{-- Filter bar --}}
    <div class="flex flex-wrap items-end gap-3 rounded-lg bg-white dark:bg-gray-900 p-4 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-300">
            <span class="icon-filter text-lg"></span>
            フィルター
        </div>

        {{-- Channel filter --}}
        <div class="flex flex-col gap-1 min-w-[180px]">
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400">
                @lang('pos::app.admin.users.outlets.assign.datagrid.channel')
            </label>
            <select
                id="filter-channel"
                class="rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-white px-3 py-1.5 text-sm"
                onchange="applyOutletFilters()"
            >
                <option value="">— 全チャンネル —</option>
                @foreach($channels as $ch)
                    <option value="{{ $ch->code }}" {{ $selectedChannel === $ch->code ? 'selected' : '' }}>
                        {{ $ch->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Inventory source filter --}}
        <div class="flex flex-col gap-1 min-w-[200px]">
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400">
                @lang('pos::app.admin.users.outlets.assign.datagrid.inventory-source')
            </label>
            <select
                id="filter-inventory-source"
                class="rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-white px-3 py-1.5 text-sm"
                onchange="applyOutletFilters()"
            >
                <option value="">— 全在庫ソース —</option>
                @foreach($inventorySources as $src)
                    <option value="{{ $src->id }}" {{ $selectedInvSrc == $src->id ? 'selected' : '' }}>
                        {{ $src->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Clear filters --}}
        @if($selectedChannel || $selectedInvSrc)
        <a
            href="{{ route('admin.pos.outlets.assign_products', $posOutlet->id) }}"
            class="px-3 py-1.5 text-sm rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 self-end"
        >
            ✕ クリア
        </a>
        @endif
    </div>

    <v-assign-product>
        <x-admin::shimmer.datagrid />
    </v-assign-product>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-assign-product-template"
        >
            <!-- Datagrid -->
            <x-admin::datagrid
                :src="$datagridSrc"
                :isMultiRow="true"
                ref="datagrid"
            >
                <!-- Datagrid Header -->
                @php
                    $hasPermission = bouncer()->hasPermission('pos.users.outlets.mass-assign') || bouncer()->hasPermission('pos.users.outlets.mass-unassign');
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
                                v-for="(columnGroup, index) in [['product_name', 'product_sku', 'type'], ['product_image', 'product_price', 'product_quantity', 'product_id'], ['product_status', 'pos_status']]"
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
                                        @{{ "@lang('pos::app.admin.users.outlets.assign.datagrid.sku-value')".replace(':sku', record.product_sku) }}
                                    </p>

                                    <p class="text-gray-600 dark:text-gray-300">
                                        @{{ record.type }}
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
                                                @lang('pos::app.admin.users.outlets.assign.datagrid.product-image')
                                            </p>
                                        </div>
                                    </template>
                                </div>

                                <div class="flex flex-col gap-1.5">
                                    <p class="text-base font-semibold text-gray-800 dark:text-white">
                                        @{{ $admin.formatPrice(record.product_price) }}
                                    </p>

                                    <!-- Sum of all related product qty -->
                                    <p
                                        class="text-gray-600 dark:text-gray-300"
                                        v-if="record.product_quantity > 0"
                                    >
                                        <span class="text-green-600">
                                            @{{ "@lang('pos::app.admin.users.outlets.assign.datagrid.qty-value')".replace(':qty', record.product_quantity) }}
                                        </span>
                                    </p>

                                    <p
                                        class="text-gray-600 dark:text-gray-300"
                                        v-else-if="record.product_quantity == 'N/A'"
                                    >
                                        <span class="text-red-600">
                                            @{{ record.product_quantity }}
                                        </span>
                                    </p>

                                    <p
                                        class="text-gray-600 dark:text-gray-300"
                                        v-else
                                    >
                                        <span class="text-red-600">
                                            @lang('pos::app.admin.users.outlets.assign.datagrid.out-of-stock')
                                        </span>
                                    </p>

                                    <p class="text-gray-600 dark:text-gray-300">
                                        @{{ "@lang('pos::app.admin.users.outlets.assign.datagrid.id-value')".replace(':id', record.product_id) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Status, POS Status, Channel Columns -->
                            <div class="flex items-center justify-between gap-x-4">
                                <div class="flex flex-col gap-1.5">
                                    <p :class="[record.product_status ? 'label-active': 'label-info']">
                                        @{{ record.product_status ? "@lang('pos::app.admin.users.outlets.assign.datagrid.active')" : "@lang('pos::app.admin.users.outlets.assign.datagrid.disable')" }}
                                    </p>

                                    <x-admin::form.control-group class="!mb-0">
                                        <x-admin::form.control-group.control
                                            type="select"
                                            class="min-w-[200px]"
                                            v-model="record.pos_status"
                                            ::disabled="record.pos_status === 'N/A'"
                                            @change="changePosStatus(record.product_id, $event.target.value)"
                                        >
                                            <option value="">
                                                @lang('pos::app.admin.users.outlets.assign.datagrid.disable')
                                            </option>

                                            <option value="1">
                                                @lang('pos::app.admin.users.outlets.assign.datagrid.active')
                                            </option>
                                        </x-admin::form.control-group.control>
                                    </x-admin::form.control-group>

                                    <!-- Channel badge -->
                                    <p v-if="record.product_channel" class="text-xs text-gray-500 dark:text-gray-400">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-blue-400 inline-block"></span>
                                            @{{ record.product_channel }}
                                        </span>
                                    </p>

                                    <!-- Inventory source badge -->
                                    <p v-if="record.inventory_source_names" class="text-xs text-gray-500 dark:text-gray-400">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-green-400 inline-block"></span>
                                            @{{ record.inventory_source_names }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </template>
                </template>
            </x-admin::datagrid>
        </script>
    
        <script type="module">
            app.component('v-assign-product', {
                template: '#v-assign-product-template',

                methods: {
                    changePosStatus(productId, status) {
                        const indices = [productId];

                        this.$axios.post("{{ route('admin.pos.outlets.assign_products.mass_assign', $posOutlet->id) }}", {
                            indices: indices,
                            value: status ? 1 : 0,
                        })
                            .then(response => {
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message, 
                                });
                            })
                            .catch(error => {
                                this.$refs.datagrid.get();
                            });
                    },
                },
            });
        </script>

        <script>
            function applyOutletFilters() {
                const channel = document.getElementById('filter-channel').value;
                const invSrc  = document.getElementById('filter-inventory-source').value;
                const url     = new URL(window.location.href);
                // Empty value means "all channels" — pass it explicitly so the
                // backend doesn't fall back to the outlet's default channel.
                url.searchParams.set('product_channel', channel);
                if (invSrc)   url.searchParams.set('inventory_source_id', invSrc);
                else          url.searchParams.delete('inventory_source_id');
                window.location.href = url.toString();
            }
        </script>
    @endPushOnce
</x-admin::layouts>
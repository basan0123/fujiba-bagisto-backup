<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.sales.orders.index.title')
    </x-slot>

    @push('styles')
        <style>
            /* View toggle */
            .ov-vt-btn { color: #9ca3af; padding: 6px 10px; }
            .ov-vt-btn.is-active { background: #3b82f6; color: #fff; }
            .dark .ov-vt-btn.is-active { background: #2563eb; }

            /* Filter selects — match reorder page style */
            .ov-filter-select { padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; background: #fff; }
            .dark .ov-filter-select { background: #1f2937; border-color: #374151; color: #e5e7eb; }
            .ov-filter-select:focus { outline: none; border-color: #3b82f6; }

            /* List view (View B) channel tinting */
            .olv-row { transition: background .12s; }
            .olv-row:hover { background: rgba(0,0,0,.025); }
            .dark .olv-row:hover { background: rgba(255,255,255,.025); }
            .olv-row.ch-kimono { background: rgba(255,228,230,.5); border-left: 3px solid #fb7185 !important; }
            .dark .olv-row.ch-kimono { background: rgba(244,63,100,.07); }
            .olv-row.ch-food { background: rgba(254,243,199,.6); border-left: 3px solid #fbbf24 !important; }
            .dark .olv-row.ch-food { background: rgba(251,191,36,.07); }

            .chip { font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 999px; }
            .chip-kimono { background:#ffe4e6; color:#be123c; }
            .dark .chip-kimono { background:rgba(244,63,100,.2); color:#fb7185; }
            .chip-food { background:#fef3c7; color:#92400e; }
            .dark .chip-food { background:rgba(251,191,36,.2); color:#fbbf24; }
            .chip-default { background:#e0e7ff; color:#3730a3; }
            .dark .chip-default { background:rgba(99,102,241,.2); color:#818cf8; }

            .av { width: 36px; height: 36px; border-radius: 9999px; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size: 14px; flex-shrink: 0; }
            .av-rose   { background: #f43f5e; }
            .av-violet { background: #8b5cf6; }
            .av-blue   { background: #3b82f6; }
            .av-teal   { background: #14b8a6; }
            .av-amber  { background: #f59e0b; }
            .av-green  { background: #22c55e; }

            .olv-header { background:#f9fafb; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#9ca3af; }
            .dark .olv-header { background:rgba(255,255,255,.02); }

            .olv-page-btn { padding:4px 10px; border-radius:6px; border:1px solid #e5e7eb; font-size:13px; cursor:pointer; background:transparent; }
            .olv-page-btn:hover { background:#f3f4f6; }
            .olv-page-btn.is-active { background:#3b82f6; color:#fff; border-color:#3b82f6; }
            .dark .olv-page-btn { border-color:#374151; color:#d1d5db; }
            .dark .olv-page-btn:hover { background:#1f2937; }
        </style>
    @endpush

    {{-- ══════════════════════════════════════════════════════════
         TOP BAR
    ══════════════════════════════════════════════════════════ --}}
    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="py-3 text-xl font-bold text-gray-800 dark:text-white">
            @lang('admin::app.sales.orders.index.title')
        </p>

        <div class="flex flex-wrap items-center gap-2">

            {{-- Filters — plain form GET. Both view toggle buttons are submit
                 buttons so the form posts on click. No JS required. --}}
            @php
                /* Read clean params first; fall back to legacy bracket form so
                   bookmarked old URLs still work. */
                $qsChannel = request()->query('channel_id',
                                request()->query('filters.channel_id.0',
                                  request()->query('filters.channel_id', '')));
                $qsStatus  = request()->query('status',
                                request()->query('filters.status.0',
                                  request()->query('filters.status', '')));
                $qsView    = request()->query('view', 'advanced');
            @endphp

            {{-- Plain JS-driven nav that builds a clean URL: only emits the
                 query params that actually have values (no empty channel_id /
                 status keys), and omits view= entirely on the default datagrid.
                 Hidden when the advanced Vue view is active — that view has
                 its own internal filter bar so two would conflict. --}}
            @if ($qsView !== 'advanced')
            <label class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-300">
                Channel:
                <select id="ov-channel-filter" class="ov-filter-select" onchange="window.ovNav({channel_id: this.value})">
                    <option value="">All</option>
                    @foreach (core()->getAllChannels() as $channel)
                        <option value="{{ $channel->id }}" @selected((string)$qsChannel === (string)$channel->id)>
                            {{ $channel->name }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-300">
                Status:
                <select id="ov-status-filter" class="ov-filter-select" onchange="window.ovNav({status: this.value})">
                    <option value="">All</option>
                    @foreach ([
                        'pending'         => 'admin::app.sales.orders.index.datagrid.pending',
                        'pending_payment' => 'admin::app.sales.orders.index.datagrid.pending-payment',
                        'processing'      => 'admin::app.sales.orders.index.datagrid.processing',
                        'completed'       => 'admin::app.sales.orders.index.datagrid.completed',
                        'canceled'        => 'admin::app.sales.orders.index.datagrid.canceled',
                        'closed'          => 'admin::app.sales.orders.index.datagrid.closed',
                        'fraud'           => 'admin::app.sales.orders.index.datagrid.fraud',
                    ] as $val => $langKey)
                        <option value="{{ $val }}" @selected($qsStatus === $val)>@lang($langKey)</option>
                    @endforeach
                </select>
            </label>
            @endif

            <div class="flex items-center rounded-lg border border-gray-300 dark:border-gray-700 overflow-hidden">
                <button type="button"
                    class="ov-vt-btn {{ $qsView === 'datagrid' ? 'is-active' : '' }}"
                    title="シンプル"
                    onclick="window.ovNav({view: 'datagrid'})">
                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <button type="button"
                    class="ov-vt-btn border-l border-gray-300 dark:border-gray-700 {{ $qsView === 'listview' ? 'is-active' : '' }}"
                    title="詳細"
                    onclick="window.ovNav({view: 'listview'})">
                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </button>
                <button type="button"
                    class="ov-vt-btn border-l border-gray-300 dark:border-gray-700 {{ $qsView === 'advanced' ? 'is-active' : '' }}"
                    title="アドバンス"
                    onclick="window.ovNav({view: 'advanced'})">
                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/></svg>
                </button>
            </div>

            <script>
                /* Build a clean URL: only push keys that have a non-empty value.
                   Lets us replace the bracket-encoded mess with simple ?view=…
                   or ?status=processing&channel_id=1, etc. */
                window.ovNav = window.ovNav || function (patch) {
                    var u = new URL(window.location.href);

                    /* Wipe all known keys so leftovers don't accumulate. */
                    ['view','channel_id','status','filters[channel_id][0]','filters[status][0]','filters[channel_id]','filters[status]'].forEach(function (k) {
                        u.searchParams.delete(k);
                    });

                    /* Carry over the existing values that aren't being patched. */
                    var keep = {
                        view:       (patch.view       !== undefined ? patch.view       : '{{ $qsView }}'),
                        channel_id: (patch.channel_id !== undefined ? patch.channel_id : '{{ $qsChannel }}'),
                        status:     (patch.status     !== undefined ? patch.status     : '{{ $qsStatus }}'),
                    };

                    /* view=datagrid is the default — no need to put it in the URL */
                    /* advanced is the default — only emit view= for the others */
                    if (keep.view && keep.view !== 'advanced') u.searchParams.set('view', keep.view);
                    if (keep.channel_id) u.searchParams.set('channel_id', keep.channel_id);
                    if (keep.status)     u.searchParams.set('status',     keep.status);

                    window.location.href = u.toString();
                };

                /* Translate clean URL params (channel_id, status) into the
                   Bagisto datagrid's localStorage filter shape, so the
                   datagrid view sees the filter on load. Runs synchronously
                   before the datagrid component mounts. */
                (function () {
                    var SRC = '{{ route('admin.sales.orders.index') }}';
                    var ch  = '{{ $qsChannel }}';
                    var st  = '{{ $qsStatus }}';

                    try {
                        var arr = JSON.parse(localStorage.getItem('datagrids') || '[]') || [];
                        var dg  = arr.find(function (d) { return d && d.src === SRC; });
                        if (!dg) {
                            dg = {
                                src: SRC, requestCount: 0,
                                applied: {
                                    pagination: { page: 1, perPage: 10 },
                                    sort:       { column: null, order: null },
                                    filters:    { columns: [{ index: 'all', value: [] }] },
                                    savedFilterId: null,
                                },
                            };
                            arr.push(dg);
                        }
                        dg.applied = dg.applied || {};
                        dg.applied.filters = dg.applied.filters || { columns: [] };
                        var cols = dg.applied.filters.columns
                            .filter(function (c) { return c.index !== 'channel_id' && c.index !== 'status'; });
                        if (ch) cols.push({ index: 'channel_id', value: [String(ch)] });
                        if (st) cols.push({ index: 'status',     value: [String(st)] });
                        dg.applied.filters.columns = cols;
                        localStorage.setItem('datagrids', JSON.stringify(arr));
                    } catch (_) {}
                })();
            </script>

            <x-admin::datagrid.export src="{{ route('admin.sales.orders.index') }}" />

            {!! view_render_event('bagisto.admin.sales.orders.create.before') !!}

            @if (bouncer()->hasPermission('sales.orders.create'))
                <button class="primary-button" @click="$refs.selectCustomerComponent.openDrawer()">
                    @lang('admin::app.sales.orders.index.create-btn')
                </button>
            @endif

            {!! view_render_event('bagisto.admin.sales.orders.create.after') !!}
        </div>
    </div>

    <v-customer-search ref="selectCustomerComponent"></v-customer-search>

    {{-- ══════════════════════════════════════════════════════════
         VIEW A — Bagisto default datagrid (UNTOUCHED)
    ══════════════════════════════════════════════════════════ --}}
    <div id="view-datagrid" @if($qsView !== 'datagrid') style="display:none;" @endif>
        <x-admin::datagrid :src="route('admin.sales.orders.index')" :isMultiRow="true">
            <template #header="{ isLoading, available, applied, selectAll, sort, performAction }">
                <template v-if="isLoading">
                    <x-admin::shimmer.datagrid.table.head :isMultiRow="true" />
                </template>
                <template v-else>
                    <div class="row grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 items-center border-b px-2 sm:px-4 py-2.5 dark:border-gray-800">
                        <div
                            class="flex select-none items-center gap-2.5"
                            v-for="(columnGroup, index) in [['increment_id', 'created_at', 'status'], ['base_grand_total', 'method', 'channel_id'], ['full_name', 'customer_email', 'location'], ['items']]"
                        >
                            <p class="text-gray-600 dark:text-gray-300 text-sm sm:text-base">
                                <span class="[&>*]:after:content-['_/_']">
                                    <template v-for="column in columnGroup">
                                        <span
                                            class="after:content-['/'] last:after:content-['']"
                                            :class="{
                                                'font-medium text-gray-800 dark:text-white': applied.sort.column == column,
                                                'cursor-pointer hover:text-gray-800 dark:hover:text-white': available.columns.find(columnTemp => columnTemp.index === column)?.sortable,
                                            }"
                                            @click="available.columns.find(columnTemp => columnTemp.index === column)?.sortable ? sort(available.columns.find(columnTemp => columnTemp.index === column)) : {}"
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

            <template #body="{ isLoading, available, applied, selectAll, sort, performAction }">
                <template v-if="isLoading">
                    <x-admin::shimmer.datagrid.table.body :isMultiRow="true" />
                </template>
                <template v-else>
                    <div
                        class="row grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-y-4 border-b px-2 sm:px-4 py-2.5 transition-all hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-950"
                        v-for="record in available.records"
                    >
                        <div class="flex flex-col gap-1.5">
                            <p class="text-sm sm:text-base font-semibold text-gray-800 dark:text-white">
                                @{{ "@lang('admin::app.sales.orders.index.datagrid.id')".replace(':id', record.increment_id) }}
                            </p>
                            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300">@{{ record.created_at }}</p>
                            <p v-html="record.status"></p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <p class="text-sm sm:text-base font-semibold text-gray-800 dark:text-white">
                                @{{ $admin.formatPrice(record.base_grand_total) }}
                            </p>
                            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                                @lang('admin::app.sales.orders.index.datagrid.pay-by', ['method' => ''])@{{ record.method }}
                            </p>
                            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300">@{{ record.channel_name }}</p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-sm sm:text-base text-gray-800 dark:text-white">@{{ record.full_name }}</p>

                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold whitespace-nowrap"
                                    :class="{
                                        'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300': Number(record.customer_order_count) === 0,
                                        'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300': Number(record.customer_order_count) >= 1 && Number(record.customer_order_count) <= 2,
                                        'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300': Number(record.customer_order_count) >= 3 && Number(record.customer_order_count) <= 9,
                                        'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300': Number(record.customer_order_count) >= 10,
                                    }"
                                    :title="`Purchased ${record.customer_order_count} time(s)`"
                                >
                                    <i class="icon-cart text-sm"></i>
                                    @{{ record.customer_order_count || 0 }}x
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300">@{{ record.customer_email }}</p>
                            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300">@{{ record.location }}</p>
                        </div>
                        <div class="flex items-center justify-between gap-x-2">
                            <div class="flex flex-col gap-1.5 text-xs sm:text-sm" v-html="record.items"></div>
                            <a :href="`{{ route('admin.sales.orders.view', '') }}/${record.id}`">
                                <span class="icon-sort-right rtl:icon-sort-left cursor-pointer p-1.5 text-xl sm:text-2xl hover:rounded-md hover:bg-gray-200 dark:hover:bg-gray-800 ltr:ml-1 rtl:mr-1"></span>
                            </a>
                        </div>
                    </div>
                </template>
            </template>
        </x-admin::datagrid>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         VIEW B — Custom List View (rendered by Vue)
    ══════════════════════════════════════════════════════════ --}}
    <div id="view-listview" @if($qsView !== 'listview') style="display:none;" @endif>
        <v-order-list-view></v-order-list-view>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         VIEW C — Advanced Orders View (Fujiba\OrdersAdmin)
    ══════════════════════════════════════════════════════════ --}}
    <div id="orders-view-app" @if($qsView !== 'advanced') style="display:none;" @endif>
        @php
            $statusLabels = [
                'pending'         => __('admin::app.sales.orders.index.datagrid.pending'),
                'pending_payment' => __('admin::app.sales.orders.index.datagrid.pending-payment'),
                'processing'      => __('admin::app.sales.orders.index.datagrid.processing'),
                'shipped'         => __('admin::app.sales.orders.index.datagrid.shipped'),
                'completed'       => __('admin::app.sales.orders.index.datagrid.completed'),
                'canceled'        => __('admin::app.sales.orders.index.datagrid.canceled'),
                'closed'          => __('admin::app.sales.orders.index.datagrid.closed'),
                'fraud'           => __('admin::app.sales.orders.index.datagrid.fraud'),
            ];
        @endphp
        <v-orders-view
            :channels="{{ json_encode(core()->getAllChannels()->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'code' => $c->code])->values()) }}"
            :status-labels="{{ json_encode($statusLabels) }}"
            chatpion-base-url="{{ config('fujiba.chatpion.base_url', '') }}"
            :admin-id="{{ (int) auth('admin')->id() }}"
        ></v-orders-view>
    </div>

    @include('orders-admin::admin.orders-view')

    @include('admin::customers.customers.index.create')

    @pushOnce('scripts')

    {{-- ══ x-template: customer search (unchanged) ══ --}}
    <script type="text/x-template" id="tpl-customer-search">
        <div>
            <x-admin::drawer ref="searchCustomerDrawer" @close="searchTerm=''; searchedCustomers=[];">
                <x-slot:header>
                    <div class="grid gap-3">
                        <p class="py-2 text-xl font-medium dark:text-white">
                            @lang('admin::app.sales.orders.index.search-customer.title')
                        </p>
                        <div class="relative w-full">
                            <input type="text"
                                class="block w-full rounded-lg border bg-white py-1.5 leading-6 text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 ltr:pl-3 ltr:pr-10 rtl:pl-10 rtl:pr-3"
                                placeholder="@lang('admin::app.sales.orders.index.search-customer.search-by')"
                                v-model.lazy="searchTerm" v-debounce="500" />
                            <template v-if="isSearching">
                                <img class="absolute top-2.5 h-5 w-5 animate-spin ltr:right-3 rtl:left-3" src="{{ bagisto_asset('images/spinner.svg') }}" />
                            </template>
                            <template v-else>
                                <span class="icon-search pointer-events-none absolute top-1.5 flex items-center text-2xl ltr:right-3 rtl:left-3"></span>
                            </template>
                        </div>
                    </div>
                </x-slot>
                <x-slot:content class="!p-0">
                    <div class="grid max-h-[400px] overflow-y-auto" v-if="searchedCustomers.length">
                        <div class="grid cursor-pointer place-content-start gap-1.5 border-b border-slate-300 p-4 last:border-b-0 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-950"
                             v-for="customer in searchedCustomers" @click="createCart(customer)">
                            <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
                                @{{ customer.first_name + ' ' + customer.last_name }}
                            </p>
                            <p class="text-gray-500">@{{ customer.email }}</p>
                        </div>
                    </div>
                    <div class="grid justify-center justify-items-center gap-3.5 px-2.5 py-10" v-else>
                        <img src="{{ bagisto_asset('images/empty-placeholders/customers.svg') }}" class="h-20 w-20 dark:mix-blend-exclusion dark:invert" />
                        <div class="flex flex-col items-center gap-1.5">
                            <p class="text-base font-semibold text-gray-400">
                                @lang('admin::app.sales.orders.index.search-customer.empty-title')
                            </p>
                            <p class="text-gray-400">
                                @lang('admin::app.sales.orders.index.search-customer.empty-info')
                            </p>
                            <button class="secondary-button"
                                @click="$refs.searchCustomerDrawer.close(); $refs.createCustomerComponent.openModal()">
                                @lang('admin::app.sales.orders.index.search-customer.create-btn')
                            </button>
                        </div>
                    </div>
                </x-slot>
            </x-admin::drawer>
            <v-create-customer-form ref="createCustomerComponent" @customer-created="createCart"></v-create-customer-form>
        </div>
    </script>

    {{-- ══ x-template: list view ══ --}}
    <script type="text/x-template" id="tpl-order-list-view">
        <div>
            <div class="olv-search-bar flex flex-wrap items-center gap-2 px-4 py-2.5 bg-white dark:bg-gray-900 border border-b-0 dark:border-gray-800 rounded-t-lg">
                <input type="text" v-model="filters.search"
                       v-on:input="onSearchInput"
                       v-on:keyup.enter="load(1)"
                       placeholder="注文ID / 名前 / 電話 / 住所 / 郵便番号 / 追跡番号 / 支払方法..."
                       class="ov-input flex-grow" style="min-width:240px; max-width:420px" />
                <span class="text-xs text-gray-500 dark:text-gray-300">金額:</span>
                <input type="number" min="0" step="1" v-model.number="filters.amount_min"
                       v-on:change="load(1)" v-on:keyup.enter="load(1)"
                       placeholder="最小" class="ov-input" style="max-width:110px" />
                <span class="text-xs text-gray-500 dark:text-gray-300">〜</span>
                <input type="number" min="0" step="1" v-model.number="filters.amount_max"
                       v-on:change="load(1)" v-on:keyup.enter="load(1)"
                       placeholder="最大" class="ov-input" style="max-width:110px" />
                <span class="text-xs text-gray-400 ml-auto">@{{ meta.total }} 件</span>
            </div>
            <div class="olv-header grid items-center border-b border-t px-4 py-2.5 dark:border-gray-800"
                 style="grid-template-columns: 2.4fr 1fr 1.1fr 1fr 1fr 36px;">
                <div>顧客名 / 注文ID</div>
                <div>ステータス</div>
                <div>チャンネル</div>
                <div class="text-right">金額</div>
                <div>日時</div>
                <div></div>
            </div>

            <div v-if="isLoading" class="flex justify-center py-16 bg-white dark:bg-gray-900 rounded-b-lg">
                <img src="{{ bagisto_asset('images/spinner.svg') }}" class="h-8 w-8 animate-spin" />
            </div>

            <div v-else class="bg-white dark:bg-gray-900 rounded-b-lg overflow-hidden box-shadow">
                <div
                    v-for="record in records"
                    :key="record.id"
                    class="olv-row grid items-center border-b px-4 py-3 dark:border-gray-800 last:border-b-0"
                    :class="chRowClass(record.channel_name)"
                    style="grid-template-columns: 2.4fr 1fr 1.1fr 1fr 1fr 36px;"
                >
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="av" :class="avColor(record.full_name)">
                            @{{ (record.full_name || '?').charAt(0).toUpperCase() }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-[15px] font-bold text-gray-900 dark:text-white truncate leading-tight">
                                @{{ record.full_name || '—' }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 truncate mt-0.5">
                                <span class="font-mono">#@{{ record.increment_id }}</span>
                                <span v-if="record.customer_email"> · @{{ record.customer_email }}</span>
                            </p>
                        </div>
                    </div>
                    <div><span v-html="record.status"></span></div>
                    <div>
                        <span class="chip" :class="chChipClass(record.channel_name)">
                            @{{ record.channel_name }}
                        </span>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-gray-800 dark:text-white">
                            @{{ $admin.formatPrice(record.base_grand_total) }}
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">@{{ record.method }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">@{{ record.created_at }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">@{{ record.location }}</p>
                    </div>
                    <a :href="`{{ route('admin.sales.orders.view', '') }}/${record.id}`"
                       class="flex items-center justify-center p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>

                <div v-if="!records.length && !isLoading" class="py-14 text-center text-gray-400 text-sm">
                    注文が見つかりません
                </div>
            </div>

            <div v-if="meta.last_page > 1" class="flex justify-center gap-1.5 mt-4">
                <button v-for="p in meta.last_page" :key="p"
                        class="olv-page-btn"
                        :class="{ 'is-active': p === meta.current_page }"
                        @click="load(p)">
                    @{{ p }}
                </button>
            </div>
        </div>
    </script>

    {{-- ══ Vue components (type=module — required by Bagisto's `app`) ══ --}}
    <script type="module">
        app.component('v-customer-search', {
            template: '#tpl-customer-search',
            data() { return { searchTerm: '', searchedCustomers: [], isSearching: false }; },
            watch: { searchTerm() { this.search(); } },
            methods: {
                openDrawer() { this.$refs.searchCustomerDrawer.open(); },
                search() {
                    if (this.searchTerm.length <= 1) { this.searchedCustomers = []; return; }
                    this.isSearching = true;
                    this.$axios.get('{{ route("admin.customers.customers.search") }}', { params: { query: this.searchTerm } })
                        .then(r => { this.isSearching = false; this.searchedCustomers = r.data.data; })
                        .catch(() => {});
                },
                createCart(customer) {
                    this.$axios.post('{{ route("admin.sales.cart.store") }}', { customer_id: customer.id })
                        .then(r => { window.location.href = r.data.redirect_url; })
                        .catch(e => { this.$emitter.emit('add-flash', { type: 'error', message: e.response.data.message }); });
                },
            }
        });

        app.component('v-order-list-view', {
            template: '#tpl-order-list-view',
            data() {
                return {
                    records: [],
                    isLoading: false,
                    meta: { current_page: 1, last_page: 1, per_page: 25, total: 0 },
                    filters: { search: '', amount_min: null, amount_max: null },
                    searchTimer: null,
                    channelNames: {!! json_encode(core()->getAllChannels()->mapWithKeys(fn ($c) => [$c->id => $c->name])->toArray()) !!},
                    paymentTitles: {!! json_encode([
                        'cashondelivery' => core()->getConfigData('sales.payment_methods.cashondelivery.title') ?: '代引',
                        'moneytransfer'  => core()->getConfigData('sales.payment_methods.moneytransfer.title')  ?: '振込',
                        'paypay'         => core()->getConfigData('sales.payment_methods.paypay.title')         ?: 'PayPay',
                    ]) !!},
                };
            },
            mounted() {
                this.load(1);
            },
            methods: {
                getStoredFilters() {
                    // Read URL-level filters set by the view-toggle bar so listview
                    // honors channel/status the same way the datagrid view does.
                    const out = {};
                    const url = new URL(window.location.href);
                    const ch = url.searchParams.get('channel_id')
                            || url.searchParams.get('filters[channel_id][0]')
                            || url.searchParams.get('filters[channel_id]');
                    const st = url.searchParams.get('status')
                            || url.searchParams.get('filters[status][0]')
                            || url.searchParams.get('filters[status]');
                    if (ch) out.channel_id = [ch];
                    if (st) out.status = [st];
                    return out;
                },
                onSearchInput() {
                    clearTimeout(this.searchTimer);
                    this.searchTimer = setTimeout(() => this.load(1), 350);
                },
                load(page = 1) {
                    this.isLoading = true;
                    const stored = this.getStoredFilters();
                    const params = {
                        page,
                        per_page: this.meta.per_page,
                        sort_by:  'id',
                        sort_dir: 'desc',
                    };
                    if (stored.channel_id) params.channel_id = stored.channel_id;
                    if (stored.status)     params.status     = stored.status;
                    if (this.filters.search) params.search   = this.filters.search;
                    if (this.filters.amount_min !== null && this.filters.amount_min !== '')
                        params.amount_min = this.filters.amount_min;
                    if (this.filters.amount_max !== null && this.filters.amount_max !== '')
                        params.amount_max = this.filters.amount_max;

                    this.$axios.get('{{ url(config('app.admin_url')) }}/api/orders', { params })
                        .then(r => {
                            this.records = (r.data.data || []).map(o => this.adapt(o));
                            this.meta    = r.data.meta || this.meta;
                        })
                        .catch(() => { this.records = []; })
                        .finally(() => { this.isLoading = false; });
                },
                adapt(o) {
                    // Map /api/orders response into the legacy listview record shape
                    // so the existing template (full_name, status html, channel_name,
                    // base_grand_total, method, location) keeps working.
                    const status   = o.status || '';
                    const statusHtml = `<span class="ov-st ov-st-${status}">${status}</span>`;
                    const addr = o.shipping_address || {};
                    const location = [addr.city, addr.state, addr.country].filter(Boolean).join(', ');
                    const created  = o.created_at ? new Date(o.created_at).toLocaleString('ja-JP') : '';
                    return {
                        id:               o.id,
                        increment_id:     o.increment_id,
                        full_name:        (o.customer && o.customer.name) || '—',
                        customer_email:   (o.customer && o.customer.email) || '',
                        status:           statusHtml,
                        channel_id:       o.channel_id,
                        channel_name:     this.channelNames[o.channel_id] || '',
                        base_grand_total: o.grand_total,
                        method:           this.paymentTitles[o.payment_method] || o.payment_method || '',
                        created_at:       created,
                        location:         location,
                    };
                },
                chRowClass(name) {
                    if (!name) return '';
                    const n = name.toLowerCase();
                    if (n.includes('kimono') || n.includes('fujiba')) return 'ch-kimono';
                    if (n.includes('food') || n.includes('mm')) return 'ch-food';
                    return '';
                },
                chChipClass(name) {
                    if (!name) return 'chip-default';
                    const n = name.toLowerCase();
                    if (n.includes('kimono') || n.includes('fujiba')) return 'chip-kimono';
                    if (n.includes('food') || n.includes('mm')) return 'chip-food';
                    return 'chip-default';
                },
                avColor(name) {
                    const p = ['av-rose','av-violet','av-blue','av-teal','av-amber','av-green'];
                    return name ? p[name.charCodeAt(0) % p.length] : p[0];
                },
            }
        });
    </script>

    {{-- ══════════════════════════════════════════════════════════
         Filter + view-toggle wiring — same pattern as the working
         /admin/inventory/reorder page: plain <select> + addEventListener,
         IIFE with DOMContentLoaded guard, no Vue, no custom dropdowns.
    ══════════════════════════════════════════════════════════ --}}
    {{-- Filters + view toggle now use a plain GET form — no JS needed. --}}

    @endPushOnce
</x-admin::layouts>

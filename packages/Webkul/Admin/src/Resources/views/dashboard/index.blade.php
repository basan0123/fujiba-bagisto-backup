<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.dashboard.index.title')
    </x-slot>

    <!-- User Details Section -->
    <div class="flex items-center justify-between gap-4 mb-5 max-sm:flex-wrap">
        <div class="grid gap-1.5">
            <p class="text-xl font-bold !leading-normal text-gray-800 dark:text-white" v-pre>
                @lang('admin::app.dashboard.index.user-name', ['user_name' => auth()->guard('admin')->user()->name])
            </p>

            <p class="!leading-normal text-gray-600 dark:text-gray-300">
                @lang('admin::app.dashboard.index.user-info')
            </p>
        </div>

        <!-- Actions -->
        <v-dashboard-filters>
            <!-- Shimmer -->
            <div class="flex gap-1.5">
                <div class="shimmer h-[39px] w-[132px] rounded-md"></div>
                <div class="shimmer h-[39px] w-[140px] rounded-md"></div>
                <div class="shimmer h-[39px] w-[140px] rounded-md"></div>
            </div>
        </v-dashboard-filters>
    </div>

    <!-- Body Component -->
    <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
        <!-- Left Section -->
        <div class="flex flex-col flex-1 gap-8 max-xl:flex-auto">
            {!! view_render_event('bagisto.admin.dashboard.overall_details.before') !!}

            <!-- Overall Details -->
            <div class="flex flex-col gap-2">
                <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
                    @lang('admin::app.dashboard.index.overall-details')
                </p>

                <!-- Over All Details Section -->
                @include('admin::dashboard.over-all-details')
            </div>

            {!! view_render_event('bagisto.admin.dashboard.overall_details.after') !!}

            {!! view_render_event('bagisto.admin.dashboard.todays_details.before') !!}

            <!-- Todays Details -->
            <div class="flex flex-col gap-2">
                <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
                    @lang('admin::app.dashboard.index.today-details')
                </p>

                <!-- Todays Details Section -->
                @include('admin::dashboard.todays-details')
            </div>

            {!! view_render_event('bagisto.admin.dashboard.todays_details.after') !!}

            {!! view_render_event('bagisto.admin.dashboard.stock_threshold.before') !!}

            <!-- Stock Threshold -->
            <div class="flex flex-col gap-2">
                <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
                    @lang('admin::app.dashboard.index.stock-threshold')
                </p>

                <!-- Products List -->  
                @include('admin::dashboard.stock-threshold-products')
            </div>
            
            {!! view_render_event('bagisto.admin.dashboard.stock_threshold.after') !!}
        </div>

        <!-- Right Section -->
        <div class="flex w-[360px] max-w-full flex-col gap-2 max-sm:w-full">
            <!-- First Component -->
            <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
                @lang('admin::app.dashboard.index.store-stats')
            </p>

            {!! view_render_event('bagisto.admin.dashboard.store_stats.before') !!}

            <!-- Store Stats -->
            <div class="bg-white rounded box-shadow dark:bg-gray-900">
                <!-- Total Sales Details -->
                @include('admin::dashboard.total-sales')

                <!-- Total Visitors Details -->
                @include('admin::dashboard.total-visitors')

                <!-- Top Selling Products -->
                @include('admin::dashboard.top-selling-products')

                <!-- Top Customers -->
                @include('admin::dashboard.top-customers')
            </div>

            {!! view_render_event('bagisto.admin.dashboard.store_stats.after') !!}
        </div>
    </div>
    
    @pushOnce('scripts')
        <script
            type="module"
            src="{{ bagisto_asset('js/chart.js') }}"
        >
        </script>

        <script
            type="text/x-template"
            id="v-dashboard-filters-template"
        >
            <div class="flex gap-1.5">
                <template v-if="channels.length > 2">
                    <x-admin::dropdown position="bottom-right">
                        <x-slot:toggle>
                            <button
                                type="button"
                                class="inline-flex w-full cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border bg-white px-2.5 py-1.5 text-center text-sm leading-6 text-gray-600 transition-all marker:shadow hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                            >
                                @{{ channels.find(channel => channel.code == filters.channel).name }}
                                
                                <span class="text-2xl icon-sort-down"></span>
                            </button>
                        </x-slot>

                        <x-slot:menu class="!p-0 shadow-[0_5px_20px_rgba(0,0,0,0.15)] dark:border-gray-800">
                            <x-admin::dropdown.menu.item
                                v-for="channel in channels"
                                ::class="{'bg-gray-100 dark:bg-gray-950': channel.code == filters.channel}"
                                @click="filters.channel = channel.code"
                            >
                                @{{ channel.name }}
                            </x-admin::dropdown.menu.item>
                        </x-slot>
                    </x-admin::dropdown>
                </template>

                <x-admin::flat-picker.date class="!w-[140px]" ::allow-input="false">
                    <input
                        class="flex min-h-[39px] w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400"
                        v-model="filters.start"
                        placeholder="@lang('admin::app.dashboard.index.start-date')"
                    />
                </x-admin::flat-picker.date>

                <x-admin::flat-picker.date class="!w-[140px]" ::allow-input="false">
                    <input
                        class="flex min-h-[39px] w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400"
                        v-model="filters.end"
                        placeholder="@lang('admin::app.dashboard.index.end-date')"
                    />
                </x-admin::flat-picker.date>

                <div class="inline-flex rounded-md border bg-white dark:border-gray-800 dark:bg-gray-900" role="group">
                    <button
                        type="button"
                        @click="filters.mode = 'orders'"
                        ::class="filters.mode === 'orders' ? 'bg-blue-600 text-white' : 'text-gray-600 dark:text-gray-300'"
                        class="px-3 py-1.5 text-sm rounded-l-md transition-all hover:bg-gray-100 dark:hover:bg-gray-800"
                    >
                        Orders
                    </button>
                    <button
                        type="button"
                        @click="filters.mode = 'payments'"
                        ::class="filters.mode === 'payments' ? 'bg-blue-600 text-white' : 'text-gray-600 dark:text-gray-300'"
                        class="px-3 py-1.5 text-sm rounded-r-md transition-all hover:bg-gray-100 dark:hover:bg-gray-800"
                    >
                        Payments
                    </button>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-dashboard-filters', {
                template: '#v-dashboard-filters-template',

                data() {
                    const KEY = 'fujiba_global_filters:' + window.location.host;
                    const ISO = /^\d{4}-\d{2}-\d{2}$/;
                    const defaults = {
                        channel: '',
                        start: "{{ $startDate->format('Y-m-d') }}",
                        end: "{{ $endDate->format('Y-m-d') }}",
                        mode: 'orders',
                    };

                    let saved = {};
                    try {
                        const raw = localStorage.getItem(KEY);
                        if (raw) saved = JSON.parse(raw) || {};
                    } catch (e) {}

                    if (saved.start && (!ISO.test(saved.start) || (saved.end && saved.start > saved.end))) {
                        delete saved.start;
                        delete saved.end;
                    }
                    if (saved.end && !ISO.test(saved.end)) delete saved.end;
                    if (saved.mode && saved.mode !== 'orders' && saved.mode !== 'payments') delete saved.mode;

                    return {
                        storageKey: KEY,

                        channels: [
                            {
                                name: "@lang('admin::app.dashboard.index.all-channels')",
                                code: ''
                            },
                            ...@json(core()->getAllChannels()),
                        ],

                        filters: { ...defaults, ...saved },
                    }
                },

                watch: {
                    filters: {
                        handler() {
                            try {
                                let prev = {};
                                try { prev = JSON.parse(localStorage.getItem(this.storageKey) || '{}') || {}; } catch (e) {}
                                localStorage.setItem(this.storageKey, JSON.stringify({ ...prev, ...this.filters }));
                            } catch (e) {}
                            this.$emitter.emit('reporting-filter-updated', this.filters);
                        },

                        deep: true
                    }
                },

                mounted() {
                    this.$emitter.emit('reporting-filter-updated', this.filters);
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>

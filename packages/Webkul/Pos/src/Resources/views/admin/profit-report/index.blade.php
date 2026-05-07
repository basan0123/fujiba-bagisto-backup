<x-admin::layouts>
    <x-slot:title>@lang('pos::app.admin.profit-report.index.title')</x-slot:title>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap mb-6">
        <p class="text-xl text-gray-800 dark:text-white font-bold">
            @lang('pos::app.admin.profit-report.index.title')
        </p>
    </div>

    <div id="profit-app">
        {{-- Filter bar --}}
        <div class="box-shadow rounded-lg bg-white dark:bg-gray-900 p-4 mb-6 flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs text-gray-500 mb-1">@lang('pos::app.admin.profit-report.index.month')</label>
                <input type="month" id="filter-month" class="custom-input" :value="month" @change="month = $event.target.value" />
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">@lang('pos::app.admin.profit-report.index.outlet')</label>
                <select id="filter-outlet" class="custom-select" @change="outletId = $event.target.value">
                    <option value="">@lang('pos::app.admin.profit-report.index.all-outlets')</option>
                    @foreach ($outlets as $outlet)
                        <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="primary-button" @click="load">
                @lang('pos::app.admin.profit-report.index.search')
            </button>
        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6" v-if="data">
            <div class="box-shadow rounded-lg bg-white dark:bg-gray-900 p-4 text-center">
                <p class="text-xs text-gray-500 mb-1">@lang('pos::app.admin.profit-report.index.gross-sales')</p>
                <p class="text-xl font-bold text-gray-800 dark:text-white">@{{ fmt(data.gross_sales) }}</p>
            </div>
            <div class="box-shadow rounded-lg bg-white dark:bg-gray-900 p-4 text-center">
                <p class="text-xs text-gray-500 mb-1">@lang('pos::app.admin.profit-report.index.total-expense')</p>
                <p class="text-xl font-bold text-red-600">@{{ fmt(data.total_expense) }}</p>
            </div>
            <div class="box-shadow rounded-lg bg-white dark:bg-gray-900 p-4 text-center">
                <p class="text-xs text-gray-500 mb-1">@lang('pos::app.admin.profit-report.index.net-profit')</p>
                <p class="text-xl font-bold" :class="data.net_profit >= 0 ? 'text-green-600' : 'text-red-600'">
                    @{{ fmt(data.net_profit) }}
                </p>
            </div>
            <div class="box-shadow rounded-lg bg-white dark:bg-gray-900 p-4 text-center">
                <p class="text-xs text-gray-500 mb-1">@lang('pos::app.admin.profit-report.index.cash-income')</p>
                <p class="text-xl font-bold text-green-600">@{{ fmt(data.cash_income) }}</p>
            </div>
        </div>

        {{-- Expense Breakdown --}}
        <div class="box-shadow rounded-lg bg-white dark:bg-gray-900 p-4 mb-6" v-if="data">
            <p class="font-semibold text-gray-800 dark:text-white mb-3">
                @lang('pos::app.admin.profit-report.index.breakdown')
            </p>
            <table class="w-full text-sm">
                <tbody>
                    <tr class="border-b dark:border-gray-700">
                        <td class="py-1 text-gray-600 dark:text-gray-400">@lang('pos::app.admin.profit-report.index.gross-sales')</td>
                        <td class="py-1 text-right font-medium text-gray-800 dark:text-white">@{{ fmt(data.gross_sales) }}</td>
                    </tr>
                    <tr class="border-b dark:border-gray-700">
                        <td class="py-1 text-gray-600 dark:text-gray-400 pl-4">– @lang('pos::app.admin.profit-report.index.waste-cost')</td>
                        <td class="py-1 text-right text-red-500">@{{ fmt(data.waste_cost) }}</td>
                    </tr>
                    <tr class="border-b dark:border-gray-700">
                        <td class="py-1 text-gray-600 dark:text-gray-400 pl-4">– @lang('pos::app.admin.profit-report.index.cash-expenses')</td>
                        <td class="py-1 text-right text-red-500">@{{ fmt(data.cash_expenses) }}</td>
                    </tr>
                    <tr class="border-b dark:border-gray-700">
                        <td class="py-1 text-gray-600 dark:text-gray-400 pl-4">– @lang('pos::app.admin.profit-report.index.fixed-cost')</td>
                        <td class="py-1 text-right text-red-500">@{{ fmt(data.fixed_cost) }}</td>
                    </tr>
                    <tr class="border-b dark:border-gray-700">
                        <td class="py-1 text-gray-600 dark:text-gray-400 pl-4">+ @lang('pos::app.admin.profit-report.index.cash-income')</td>
                        <td class="py-1 text-right text-green-500">@{{ fmt(data.cash_income) }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 font-bold text-gray-800 dark:text-white">@lang('pos::app.admin.profit-report.index.net-profit')</td>
                        <td class="py-2 text-right font-bold" :class="data.net_profit >= 0 ? 'text-green-600' : 'text-red-600'">
                            @{{ fmt(data.net_profit) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Fixed expense breakdown --}}
        <div class="box-shadow rounded-lg bg-white dark:bg-gray-900 p-4" v-if="data && data.fixed_breakdown && data.fixed_breakdown.length">
            <p class="font-semibold text-gray-800 dark:text-white mb-3">
                @lang('pos::app.admin.profit-report.index.fixed-breakdown')
            </p>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b dark:border-gray-700">
                        <th class="pb-2 text-left text-gray-500">@lang('pos::app.admin.profit-report.index.name')</th>
                        <th class="pb-2 text-left text-gray-500">@lang('pos::app.admin.profit-report.index.category')</th>
                        <th class="pb-2 text-right text-gray-500">@lang('pos::app.admin.profit-report.index.amount')</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in data.fixed_breakdown" :key="row.id" class="border-b dark:border-gray-700">
                        <td class="py-1 text-gray-800 dark:text-white">@{{ row.name }}</td>
                        <td class="py-1 text-gray-500">@{{ row.category }}</td>
                        <td class="py-1 text-right text-red-500">@{{ fmt(row.applied_amount) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p v-if="loading" class="text-center text-gray-400 mt-8">Loading…</p>
    </div>

    @push('scripts')
    <script type="module">
        import { createApp, ref, onMounted } from 'https://unpkg.com/vue@3/dist/vue.esm-browser.js';
        createApp({
            setup() {
                const today   = new Date();
                const month   = ref(`${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`);
                const outletId = ref('');
                const data    = ref(null);
                const loading = ref(false);

                const fmt = (v) => new Intl.NumberFormat('ja-JP', { style: 'currency', currency: '{{ core()->getBaseCurrencyCode() }}' }).format(v ?? 0);

                async function load() {
                    loading.value = true;
                    const params  = new URLSearchParams({ month: month.value });
                    if (outletId.value) params.append('outlet_id', outletId.value);
                    const resp = await fetch(`{{ route('admin.pos.profit-report.data') }}?` + params.toString());
                    data.value = await resp.json();
                    loading.value = false;
                }

                onMounted(load);

                return { month, outletId, data, loading, fmt, load };
            }
        }).mount('#profit-app');
    </script>
    @endpush
</x-admin::layouts>

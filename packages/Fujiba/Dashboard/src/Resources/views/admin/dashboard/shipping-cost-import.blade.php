<x-admin::layouts>
    <x-slot:title>
        @lang('fujiba::app.dashboard.shipping_performance.import.page_title')
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <p class="text-xl font-semibold text-gray-800 dark:text-white">
                @lang('fujiba::app.dashboard.shipping_performance.import.page_title')
            </p>
            <a
                href="{{ route('admin.dashboard.index') }}"
                class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
            >
                <span class="icon-arrow-left align-middle"></span>
                @lang('fujiba::app.dashboard.shipping_performance.import.back_to_dashboard')
            </a>
        </div>

        @if (session('success'))
            <div class="rounded bg-emerald-50 border border-emerald-200 text-emerald-700 px-3 py-2 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded bg-rose-50 border border-rose-200 text-rose-700 px-3 py-2 text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php $unmatched = session('import_unmatched', []); @endphp
        @if (! empty($unmatched))
            <div class="rounded bg-amber-50 border border-amber-200 text-amber-800 px-3 py-2 text-sm">
                <p class="font-semibold mb-1">
                    @lang('fujiba::app.dashboard.shipping_performance.import.unmatched_header', ['count' => count($unmatched)])
                </p>
                <ul class="list-disc list-inside max-h-40 overflow-auto">
                    @foreach ($unmatched as $u)
                        <li>row {{ $u['row'] }} — {{ $u['tracking'] ?? '(no tracking)' }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded bg-white dark:bg-gray-900 box-shadow p-4">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                @lang('fujiba::app.dashboard.shipping_performance.import.upload_section')
            </p>

            <form method="POST"
                  action="{{ route('admin.fujiba.dashboard.shipping_costs.import') }}"
                  enctype="multipart/form-data"
                  class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                @csrf

                <div class="md:col-span-2 flex flex-col gap-1">
                    <label class="text-xs text-gray-600 dark:text-gray-400">
                        @lang('fujiba::app.dashboard.shipping_performance.import.csv_file')
                    </label>
                    <input type="file" name="csv" accept=".csv,text/csv" required
                           class="border border-gray-300 dark:border-gray-700 rounded px-2 py-1 text-sm bg-white dark:bg-gray-800" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-gray-600 dark:text-gray-400">
                        @lang('fujiba::app.dashboard.shipping_performance.import.carrier_code')
                    </label>
                    <input type="text" name="carrier_code" placeholder="sagawa" maxlength="32"
                           class="border border-gray-300 dark:border-gray-700 rounded px-2 py-1 text-sm bg-white dark:bg-gray-800" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-gray-600 dark:text-gray-400">
                        @lang('fujiba::app.dashboard.shipping_performance.import.billing_month')
                    </label>
                    <input type="month" name="billing_month"
                           class="border border-gray-300 dark:border-gray-700 rounded px-2 py-1 text-sm bg-white dark:bg-gray-800" />
                </div>

                <div class="md:col-span-4">
                    <button type="submit"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded px-4 py-2">
                        @lang('fujiba::app.dashboard.shipping_performance.import.submit')
                    </button>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        @lang('fujiba::app.dashboard.shipping_performance.import.format_hint')
                    </p>
                </div>
            </form>
        </div>

        <div class="rounded bg-white dark:bg-gray-900 box-shadow p-4">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    @lang('fujiba::app.dashboard.shipping_performance.import.recent_section')
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ (int) ($totals->rows_total ?? 0) }} rows ·
                    ¥{{ number_format((float) ($totals->sum_cost ?? 0)) }}
                    @if ($totals->last_month ?? null)
                        · @lang('fujiba::app.dashboard.shipping_performance.import.last_month_label', ['m' => $totals->last_month])
                    @endif
                </p>
            </div>

            @if ($recent->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    @lang('fujiba::app.dashboard.shipping_performance.import.empty_state')
                </p>
            @else
                <div class="overflow-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="text-left py-2 pr-3">Order</th>
                                <th class="text-left py-2 pr-3">Tracking</th>
                                <th class="text-right py-2 pr-3">¥</th>
                                <th class="text-left py-2 pr-3">Carrier</th>
                                <th class="text-left py-2 pr-3">Month</th>
                                <th class="text-left py-2 pr-3">Source</th>
                                <th class="text-left py-2 pr-3">Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recent as $row)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="py-2 pr-3">
                                        <a href="{{ route('admin.sales.orders.view', $row->order_id) }}"
                                           class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                            #{{ $row->order_id }}
                                        </a>
                                    </td>
                                    <td class="py-2 pr-3 font-mono text-xs">{{ $row->tracking_number }}</td>
                                    <td class="py-2 pr-3 text-right">¥{{ number_format((float) $row->carrier_cost) }}</td>
                                    <td class="py-2 pr-3">{{ $row->carrier_code }}</td>
                                    <td class="py-2 pr-3">{{ $row->billing_month }}</td>
                                    <td class="py-2 pr-3 text-xs">{{ $row->source }}</td>
                                    <td class="py-2 pr-3 text-xs">{{ $row->updated_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-admin::layouts>

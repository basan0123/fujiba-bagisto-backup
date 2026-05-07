<x-admin::layouts>
    <x-slot:title>{{ __('sagawa::app.menu.usage') }}</x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            {{ __('sagawa::app.menu.usage') }}
        </p>
    </div>

    <!-- Filter -->
    <form
        method="GET"
        action="{{ route('admin.sagawa.usage') }}"
        class="mt-3.5 flex flex-wrap items-end gap-3 box-shadow rounded bg-white p-4 dark:bg-gray-900"
    >
        <div class="flex flex-col gap-1">
            <label for="from" class="text-xs font-medium text-gray-600 dark:text-gray-300">
                {{ __('sagawa::app.usage.from') }}
            </label>

            <input
                type="date"
                id="from"
                name="from"
                value="{{ $from }}"
                class="h-9 rounded-md border px-3 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 focus:outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
            />
        </div>

        <div class="flex flex-col gap-1">
            <label for="to" class="text-xs font-medium text-gray-600 dark:text-gray-300">
                {{ __('sagawa::app.usage.to') }}
            </label>

            <input
                type="date"
                id="to"
                name="to"
                value="{{ $to }}"
                class="h-9 rounded-md border px-3 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 focus:outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
            />
        </div>

        <button type="submit" class="primary-button h-9">
            {{ __('sagawa::app.usage.filter') }}
        </button>
    </form>

    <!-- Stats Table -->
    <div class="mt-3.5 box-shadow overflow-hidden rounded bg-white dark:bg-gray-900">
        <div class="overflow-x-auto">
            <x-admin::table>
                <x-admin::table.thead>
                    <x-admin::table.thead.tr>
                        <x-admin::table.th>{{ __('sagawa::app.usage.endpoint') }}</x-admin::table.th>
                        <x-admin::table.th class="text-right">{{ __('sagawa::app.usage.total') }}</x-admin::table.th>
                        <x-admin::table.th class="text-right">{{ __('sagawa::app.usage.success') }}</x-admin::table.th>
                        <x-admin::table.th class="text-right">{{ __('sagawa::app.usage.failures') }}</x-admin::table.th>
                    </x-admin::table.thead.tr>
                </x-admin::table.thead>

                <x-admin::table.tbody>
                    @forelse ($stats as $row)
                        <x-admin::table.tbody.tr class="border-b border-gray-200 last:border-0 dark:border-gray-800">
                            <x-admin::table.td class="font-medium text-gray-800 dark:text-white">
                                {{ $row->endpoint }}
                            </x-admin::table.td>

                            <x-admin::table.td class="text-right font-mono">
                                {{ number_format($row->total) }}
                            </x-admin::table.td>

                            <x-admin::table.td class="text-right font-mono text-green-600 dark:text-green-400">
                                {{ number_format($row->success) }}
                            </x-admin::table.td>

                            <x-admin::table.td class="text-right font-mono text-red-600 dark:text-red-400">
                                {{ number_format($row->failures) }}
                            </x-admin::table.td>
                        </x-admin::table.tbody.tr>
                    @empty
                        <x-admin::table.tbody.tr>
                            <x-admin::table.td colspan="4" class="py-10 text-center text-sm text-gray-400">
                                {{ __('sagawa::app.usage.empty') }}
                            </x-admin::table.td>
                        </x-admin::table.tbody.tr>
                    @endforelse
                </x-admin::table.tbody>
            </x-admin::table>
        </div>
    </div>

    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
        {{ __('sagawa::app.usage.note') }}
    </p>
</x-admin::layouts>

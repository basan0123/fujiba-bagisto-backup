<x-admin::layouts>
    <x-slot:title>{{ __('accounting::app.reports.trial_balance') }}</x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            {{ __('accounting::app.reports.trial_balance') }}
            <span class="ml-2 text-sm text-gray-500 font-normal">{{ $period['label'] }}</span>
        </p>
        @include('accounting::admin.reports._export_buttons', [
            'reportName' => 'trial_balance',
            'params' => $params,
        ])
    </div>

    <form method="GET" class="mt-3.5 flex flex-wrap items-end gap-3 box-shadow rounded bg-white p-4 dark:bg-gray-900">
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.reports.year_month') }}</label>
            <input type="month" name="year_month" value="{{ $params['year_month'] ?? '' }}"
                   class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.reports.fiscal_year') }}</label>
            <input type="number" name="fiscal_year" value="{{ $params['fiscal_year'] ?? '' }}" placeholder="2026"
                   min="2000" max="2100"
                   class="h-9 w-28 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
        </div>
        <button type="submit" class="h-9 rounded-md bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">
            {{ __('accounting::app.reports.apply') }}
        </button>
    </form>

    @unless ($data['balanced'])
        <div class="mt-3 rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">
            {{ __('accounting::app.reports.unbalanced_warn') }} (¥{{ number_format($data['total_debit']) }} / ¥{{ number_format($data['total_credit']) }})
        </div>
    @endunless

    <div class="mt-4 box-shadow rounded bg-white dark:bg-gray-900 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-950">
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <th class="px-4 py-3">{{ __('accounting::app.reports.account') }}</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3 text-right">{{ __('accounting::app.reports.debit') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('accounting::app.reports.credit') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('accounting::app.reports.balance') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data['rows'] as $r)
                    <tr class="border-b last:border-b-0 dark:border-gray-800">
                        <td class="px-4 py-2"><span class="font-mono text-xs text-gray-500">{{ $r['code'] }}</span> {{ $r['name_ja'] }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500">{{ $r['type'] }}</td>
                        <td class="px-4 py-2 text-right font-mono">¥{{ number_format($r['debit']) }}</td>
                        <td class="px-4 py-2 text-right font-mono">¥{{ number_format($r['credit']) }}</td>
                        <td class="px-4 py-2 text-right font-mono font-semibold">¥{{ number_format($r['balance']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-gray-500">{{ __('accounting::app.reports.no_data') }}</td></tr>
                @endforelse
            </tbody>
            <tfoot class="border-t-2 bg-gray-50 font-semibold dark:border-gray-800 dark:bg-gray-950">
                <tr>
                    <td class="px-4 py-3" colspan="2">{{ __('accounting::app.reports.totals') }}</td>
                    <td class="px-4 py-3 text-right font-mono">¥{{ number_format($data['total_debit']) }}</td>
                    <td class="px-4 py-3 text-right font-mono">¥{{ number_format($data['total_credit']) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-admin::layouts>

<x-admin::layouts>
    <x-slot:title>{{ __('accounting::app.reports.pl') }}</x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            {{ __('accounting::app.reports.pl') }}
            <span class="ml-2 text-sm text-gray-500 font-normal">{{ $period['label'] }}</span>
        </p>
        @include('accounting::admin.reports._export_buttons', ['reportName' => 'pl', 'params' => $params])
    </div>

    <form method="GET" class="mt-3.5 flex flex-wrap items-end gap-3 box-shadow rounded bg-white p-4 dark:bg-gray-900">
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.reports.from') }}</label>
            <input type="date" name="from" value="{{ $params['from'] ?? '' }}" class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.reports.to') }}</label>
            <input type="date" name="to" value="{{ $params['to'] ?? '' }}" class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.reports.year_month') }}</label>
            <input type="month" name="year_month" value="{{ $params['year_month'] ?? '' }}" class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
        </div>
        <button type="submit" class="h-9 rounded-md bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">
            {{ __('accounting::app.reports.apply') }}
        </button>
    </form>

    @php $s = $data['sections']; @endphp

    <div class="mt-4 box-shadow rounded bg-white p-6 dark:bg-gray-900">
        <table class="w-full text-sm">
            @foreach ([
                'sales' => 'pl_sales',
                'cogs'  => 'pl_cogs',
            ] as $key => $label)
                <tr>
                    <td class="border-t pt-3 pb-1 font-semibold text-gray-700 dark:text-gray-200" colspan="3">
                        {{ __('accounting::app.reports.' . $label) }}
                    </td>
                </tr>
                @foreach ($s[$key]['rows'] as $r)
                    <tr>
                        <td class="pl-6 py-1 text-gray-500"><span class="font-mono text-xs">{{ $r['code'] }}</span> {{ $r['name_ja'] }}</td>
                        <td></td>
                        <td class="py-1 text-right font-mono">¥{{ number_format($r['balance']) }}</td>
                    </tr>
                @endforeach
                <tr class="border-b">
                    <td class="pl-6 py-1 italic text-gray-600">subtotal</td>
                    <td></td>
                    <td class="py-1 text-right font-mono font-semibold">¥{{ number_format($s[$key]['total']) }}</td>
                </tr>
            @endforeach

            <tr class="border-t-2 border-double">
                <td class="py-2 font-bold text-gray-800 dark:text-white">{{ __('accounting::app.reports.pl_gross_profit') }}</td>
                <td></td>
                <td class="py-2 text-right font-mono font-bold">¥{{ number_format($s['gross_profit']['total']) }}</td>
            </tr>

            <tr><td class="border-t pt-3 pb-1 font-semibold" colspan="3">{{ __('accounting::app.reports.pl_sga') }}</td></tr>
            @foreach ($s['sga']['rows'] as $r)
                <tr>
                    <td class="pl-6 py-1 text-gray-500"><span class="font-mono text-xs">{{ $r['code'] }}</span> {{ $r['name_ja'] }}</td>
                    <td></td>
                    <td class="py-1 text-right font-mono">¥{{ number_format($r['balance']) }}</td>
                </tr>
            @endforeach
            <tr class="border-b">
                <td class="pl-6 py-1 italic text-gray-600">subtotal</td>
                <td></td>
                <td class="py-1 text-right font-mono font-semibold">¥{{ number_format($s['sga']['total']) }}</td>
            </tr>

            <tr class="border-t-2 border-double">
                <td class="py-2 font-bold">{{ __('accounting::app.reports.pl_operating') }}</td><td></td>
                <td class="py-2 text-right font-mono font-bold">¥{{ number_format($s['operating']['total']) }}</td>
            </tr>

            @foreach ([
                'non_op_revenue' => 'pl_non_op_revenue',
                'non_op_expense' => 'pl_non_op_expense',
            ] as $key => $label)
                <tr><td class="border-t pt-3 pb-1 font-semibold" colspan="3">{{ __('accounting::app.reports.' . $label) }}</td></tr>
                @forelse ($s[$key]['rows'] as $r)
                    <tr>
                        <td class="pl-6 py-1 text-gray-500"><span class="font-mono text-xs">{{ $r['code'] }}</span> {{ $r['name_ja'] }}</td>
                        <td></td>
                        <td class="py-1 text-right font-mono">¥{{ number_format($r['balance']) }}</td>
                    </tr>
                @empty
                    <tr><td class="pl-6 py-1 text-xs text-gray-400" colspan="3">—</td></tr>
                @endforelse
                <tr class="border-b">
                    <td class="pl-6 py-1 italic text-gray-600">subtotal</td><td></td>
                    <td class="py-1 text-right font-mono font-semibold">¥{{ number_format($s[$key]['total']) }}</td>
                </tr>
            @endforeach

            <tr class="border-t-2 border-double">
                <td class="py-2 font-bold">{{ __('accounting::app.reports.pl_ordinary') }}</td><td></td>
                <td class="py-2 text-right font-mono font-bold">¥{{ number_format($s['ordinary']['total']) }}</td>
            </tr>

            @foreach ([
                'special_revenue' => 'pl_special_revenue',
                'special_expense' => 'pl_special_expense',
            ] as $key => $label)
                <tr><td class="border-t pt-3 pb-1 font-semibold" colspan="3">{{ __('accounting::app.reports.' . $label) }}</td></tr>
                @forelse ($s[$key]['rows'] as $r)
                    <tr>
                        <td class="pl-6 py-1 text-gray-500"><span class="font-mono text-xs">{{ $r['code'] }}</span> {{ $r['name_ja'] }}</td>
                        <td></td>
                        <td class="py-1 text-right font-mono">¥{{ number_format($r['balance']) }}</td>
                    </tr>
                @empty
                    <tr><td class="pl-6 py-1 text-xs text-gray-400" colspan="3">—</td></tr>
                @endforelse
                <tr class="border-b">
                    <td class="pl-6 py-1 italic text-gray-600">subtotal</td><td></td>
                    <td class="py-1 text-right font-mono font-semibold">¥{{ number_format($s[$key]['total']) }}</td>
                </tr>
            @endforeach

            <tr class="border-t-4 border-double">
                <td class="py-3 text-lg font-bold text-gray-800 dark:text-white">{{ __('accounting::app.reports.pl_pretax') }}</td>
                <td></td>
                <td class="py-3 text-right font-mono text-lg font-bold">¥{{ number_format($s['pretax']['total']) }}</td>
            </tr>
        </table>
    </div>
</x-admin::layouts>

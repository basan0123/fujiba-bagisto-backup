<x-admin::layouts>
    <x-slot:title>{{ __('accounting::app.reports.bs') }}</x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            {{ __('accounting::app.reports.bs') }}
            <span class="ml-2 text-sm text-gray-500 font-normal">{{ __('accounting::app.reports.as_of') }} {{ $data['as_of'] }}</span>
        </p>
        @include('accounting::admin.reports._export_buttons', ['reportName' => 'bs', 'params' => $params])
    </div>

    <form method="GET" class="mt-3.5 flex flex-wrap items-end gap-3 box-shadow rounded bg-white p-4 dark:bg-gray-900">
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.reports.as_of') }}</label>
            <input type="date" name="as_of" value="{{ $params['as_of'] ?? '' }}" class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
        </div>
        <button type="submit" class="h-9 rounded-md bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">
            {{ __('accounting::app.reports.apply') }}
        </button>
    </form>

    @unless ($data['balanced'])
        <div class="mt-3 rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">
            {{ __('accounting::app.reports.unbalanced_warn') }}
            (資産 ¥{{ number_format($data['assets']['total']) }} ≠ 負債純資産 ¥{{ number_format($data['total_liab_and_equity']) }})
        </div>
    @endunless

    <div class="mt-4 grid gap-4 md:grid-cols-2">
        {{-- Assets --}}
        <div class="box-shadow rounded bg-white p-5 dark:bg-gray-900">
            <p class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('accounting::app.reports.bs_assets') }}</p>
            <table class="w-full text-sm">
                @foreach (['current' => 'bs_current_assets', 'fixed' => 'bs_fixed_assets'] as $g => $label)
                    <tr><td class="border-t pt-2 pb-1 font-semibold" colspan="2">{{ __('accounting::app.reports.' . $label) }}</td></tr>
                    @foreach ($data['assets'][$g]['rows'] as $r)
                        <tr>
                            <td class="pl-6 py-1 text-gray-500"><span class="font-mono text-xs">{{ $r['code'] }}</span> {{ $r['name_ja'] }}</td>
                            <td class="py-1 text-right font-mono">¥{{ number_format($r['balance']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="border-b">
                        <td class="pl-6 py-1 italic text-gray-600">subtotal</td>
                        <td class="py-1 text-right font-mono font-semibold">¥{{ number_format($data['assets'][$g]['total']) }}</td>
                    </tr>
                @endforeach
                <tr class="border-t-2 border-double">
                    <td class="py-2 font-bold">{{ __('accounting::app.reports.bs_total_assets') }}</td>
                    <td class="py-2 text-right font-mono font-bold">¥{{ number_format($data['assets']['total']) }}</td>
                </tr>
            </table>
        </div>

        {{-- Liabilities + Equity --}}
        <div class="box-shadow rounded bg-white p-5 dark:bg-gray-900">
            <p class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('accounting::app.reports.bs_liabilities') }}</p>
            <table class="w-full text-sm">
                @foreach (['current' => 'bs_current_liab', 'fixed' => 'bs_fixed_liab'] as $g => $label)
                    <tr><td class="border-t pt-2 pb-1 font-semibold" colspan="2">{{ __('accounting::app.reports.' . $label) }}</td></tr>
                    @foreach ($data['liabilities'][$g]['rows'] as $r)
                        <tr>
                            <td class="pl-6 py-1 text-gray-500"><span class="font-mono text-xs">{{ $r['code'] }}</span> {{ $r['name_ja'] }}</td>
                            <td class="py-1 text-right font-mono">¥{{ number_format($r['balance']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="border-b">
                        <td class="pl-6 py-1 italic text-gray-600">subtotal</td>
                        <td class="py-1 text-right font-mono font-semibold">¥{{ number_format($data['liabilities'][$g]['total']) }}</td>
                    </tr>
                @endforeach
                <tr class="border-t-2 border-double">
                    <td class="py-2 font-bold">{{ __('accounting::app.reports.bs_total_liab') }}</td>
                    <td class="py-2 text-right font-mono font-bold">¥{{ number_format($data['liabilities']['total']) }}</td>
                </tr>
            </table>

            <p class="mt-5 mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('accounting::app.reports.bs_equity') }}</p>
            <table class="w-full text-sm">
                @foreach (['capital' => 'bs_capital', 'reserve' => 'bs_retained'] as $g => $label)
                    <tr><td class="border-t pt-2 pb-1 font-semibold" colspan="2">{{ __('accounting::app.reports.' . $label) }}</td></tr>
                    @foreach ($data['equity'][$g]['rows'] as $r)
                        <tr>
                            <td class="pl-6 py-1 text-gray-500"><span class="font-mono text-xs">{{ $r['code'] }}</span> {{ $r['name_ja'] }}</td>
                            <td class="py-1 text-right font-mono">¥{{ number_format($r['balance']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="border-b">
                        <td class="pl-6 py-1 italic text-gray-600">subtotal</td>
                        <td class="py-1 text-right font-mono font-semibold">¥{{ number_format($data['equity'][$g]['total']) }}</td>
                    </tr>
                @endforeach
                <tr class="border-b">
                    <td class="pl-6 py-1 text-gray-500">当期純利益 (running)</td>
                    <td class="py-1 text-right font-mono">¥{{ number_format($data['equity']['retained_running']) }}</td>
                </tr>
                <tr class="border-t-2 border-double">
                    <td class="py-2 font-bold">{{ __('accounting::app.reports.bs_total_equity') }}</td>
                    <td class="py-2 text-right font-mono font-bold">¥{{ number_format($data['equity']['total']) }}</td>
                </tr>
                <tr class="border-t-4 border-double">
                    <td class="py-3 text-lg font-bold">{{ __('accounting::app.reports.bs_total_liab_equity') }}</td>
                    <td class="py-3 text-right font-mono text-lg font-bold">¥{{ number_format($data['total_liab_and_equity']) }}</td>
                </tr>
            </table>
        </div>
    </div>
</x-admin::layouts>

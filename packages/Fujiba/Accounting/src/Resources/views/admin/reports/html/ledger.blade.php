<x-admin::layouts>
    <x-slot:title>{{ __('accounting::app.reports.ledger') }}</x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            {{ __('accounting::app.reports.ledger') }}
            <span class="ml-2 text-sm text-gray-500 font-normal">
                {{ $data['account']['code'] }} {{ $data['account']['name_ja'] }} — {{ $period['label'] }}
            </span>
        </p>
        @include('accounting::admin.reports._export_buttons', ['reportName' => 'ledger', 'params' => $params])
    </div>

    <form method="GET" class="mt-3.5 flex flex-wrap items-end gap-3 box-shadow rounded bg-white p-4 dark:bg-gray-900">
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.reports.account') }}</label>
            <select name="account_code" class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                @foreach ($accounts as $a)
                    <option value="{{ $a->code }}" @selected($params['account_code'] === $a->code)>{{ $a->code }} {{ $a->name_ja }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.reports.from') }}</label>
            <input type="date" name="from" value="{{ $params['from'] ?? '' }}" class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.reports.to') }}</label>
            <input type="date" name="to" value="{{ $params['to'] ?? '' }}" class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
        </div>
        <button type="submit" class="h-9 rounded-md bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">
            {{ __('accounting::app.reports.apply') }}
        </button>
    </form>

    <div class="mt-4 box-shadow rounded bg-white dark:bg-gray-900 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-950">
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <th class="px-3 py-2">{{ __('accounting::app.journal.date') }}</th>
                    <th class="px-3 py-2">仕訳No</th>
                    <th class="px-3 py-2">{{ __('accounting::app.journal.memo') }}</th>
                    <th class="px-3 py-2 text-right">{{ __('accounting::app.reports.debit') }}</th>
                    <th class="px-3 py-2 text-right">{{ __('accounting::app.reports.credit') }}</th>
                    <th class="px-3 py-2 text-right">{{ __('accounting::app.reports.balance') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr class="bg-gray-50 dark:bg-gray-950 font-semibold">
                    <td colspan="5" class="px-3 py-2">期首残高</td>
                    <td class="px-3 py-2 text-right font-mono">¥{{ number_format($data['opening']) }}</td>
                </tr>
                @forelse ($data['rows'] as $r)
                    <tr class="border-t dark:border-gray-800">
                        <td class="px-3 py-1.5">{{ $r['entry_date'] }}</td>
                        <td class="px-3 py-1.5 font-mono text-xs">{{ $r['entry_no'] }}</td>
                        <td class="px-3 py-1.5 text-gray-500">{{ $r['memo'] }}</td>
                        <td class="px-3 py-1.5 text-right font-mono">@if ($r['debit']) ¥{{ number_format($r['debit']) }} @endif</td>
                        <td class="px-3 py-1.5 text-right font-mono">@if ($r['credit']) ¥{{ number_format($r['credit']) }} @endif</td>
                        <td class="px-3 py-1.5 text-right font-mono">¥{{ number_format($r['running']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-gray-500">{{ __('accounting::app.reports.no_data') }}</td></tr>
                @endforelse
                <tr class="border-t-2 bg-gray-50 font-semibold dark:border-gray-800 dark:bg-gray-950">
                    <td colspan="3" class="px-3 py-2">期間合計</td>
                    <td class="px-3 py-2 text-right font-mono">¥{{ number_format($data['period_debit']) }}</td>
                    <td class="px-3 py-2 text-right font-mono">¥{{ number_format($data['period_credit']) }}</td>
                    <td></td>
                </tr>
                <tr class="bg-gray-50 font-semibold dark:bg-gray-950">
                    <td colspan="5" class="px-3 py-2">期末残高</td>
                    <td class="px-3 py-2 text-right font-mono">¥{{ number_format($data['closing']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</x-admin::layouts>

<x-admin::layouts>
    <x-slot:title>{{ __('accounting::app.reports.journal_book') }}</x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            {{ __('accounting::app.reports.journal_book') }}
            <span class="ml-2 text-sm text-gray-500 font-normal">{{ $period['label'] }}</span>
        </p>
        @include('accounting::admin.reports._export_buttons', [
            'reportName' => 'journal',
            'params' => $params,
            'allowPdf' => false,
        ])
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
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.reports.account') }}</label>
            <select name="account_code" class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <option value="">— {{ __('accounting::app.reports.all_accounts') }} —</option>
                @foreach ($accounts as $a)
                    <option value="{{ $a->code }}" @selected(($account_code ?? '') === $a->code)>{{ $a->code }} {{ $a->name_ja }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="h-9 rounded-md bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">
            {{ __('accounting::app.reports.apply') }}
        </button>
    </form>

    <div class="mt-4 box-shadow rounded bg-white dark:bg-gray-900 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-950">
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <th class="px-3 py-2">仕訳No</th>
                    <th class="px-3 py-2">{{ __('accounting::app.journal.date') }}</th>
                    <th class="px-3 py-2">Source</th>
                    <th class="px-3 py-2">{{ __('accounting::app.reports.account') }}</th>
                    <th class="px-3 py-2">{{ __('accounting::app.journal.memo') }}</th>
                    <th class="px-3 py-2 text-right">{{ __('accounting::app.reports.debit') }}</th>
                    <th class="px-3 py-2 text-right">{{ __('accounting::app.reports.credit') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data['rows'] as $r)
                    <tr class="border-b last:border-b-0 dark:border-gray-800">
                        <td class="px-3 py-1.5 font-mono text-xs">{{ $r['entry_no'] }}</td>
                        <td class="px-3 py-1.5">{{ $r['entry_date'] }}</td>
                        <td class="px-3 py-1.5 text-xs text-gray-500">{{ $r['source_type'] }}</td>
                        <td class="px-3 py-1.5"><span class="font-mono text-xs">{{ $r['account_code'] }}</span> {{ $r['account_name'] }}</td>
                        <td class="px-3 py-1.5 text-gray-500">{{ $r['memo'] }}</td>
                        <td class="px-3 py-1.5 text-right font-mono">@if ($r['debit']) ¥{{ number_format($r['debit']) }} @endif</td>
                        <td class="px-3 py-1.5 text-right font-mono">@if ($r['credit']) ¥{{ number_format($r['credit']) }} @endif</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-gray-500">{{ __('accounting::app.reports.no_data') }}</td></tr>
                @endforelse
            </tbody>
            <tfoot class="border-t-2 bg-gray-50 font-semibold dark:border-gray-800 dark:bg-gray-950">
                <tr>
                    <td class="px-3 py-2" colspan="5">{{ __('accounting::app.reports.totals') }}</td>
                    <td class="px-3 py-2 text-right font-mono">¥{{ number_format($data['total_debit']) }}</td>
                    <td class="px-3 py-2 text-right font-mono">¥{{ number_format($data['total_credit']) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-admin::layouts>

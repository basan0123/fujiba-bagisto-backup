<x-admin::layouts>
    <x-slot:title>{{ $entry->entry_no ?? '#' . $entry->id }}</x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            {{ $entry->entry_no ?? '#' . $entry->id }}
        </p>
        <a href="{{ route('admin.accounting.journal.index') }}"
           class="rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50 dark:border-gray-700">
            ← {{ __('accounting::app.common.back') }}
        </a>
    </div>

    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div class="box-shadow rounded bg-white p-5 dark:bg-gray-900">
            <dl class="grid grid-cols-2 gap-y-2 text-sm">
                <dt class="text-gray-500">{{ __('accounting::app.journal.date') }}</dt>
                <dd class="text-gray-800 dark:text-gray-200">{{ $entry->entry_date?->toDateString() }}</dd>

                <dt class="text-gray-500">{{ __('accounting::app.journal.fiscal_period') }}</dt>
                <dd class="text-gray-800 dark:text-gray-200">{{ $entry->fiscalPeriod?->name }}</dd>

                <dt class="text-gray-500">{{ __('accounting::app.journal.channel') }}</dt>
                <dd class="text-gray-800 dark:text-gray-200">{{ $entry->channel_id ?? '—' }}</dd>

                <dt class="text-gray-500">{{ __('accounting::app.journal.source') }}</dt>
                <dd class="text-gray-800 dark:text-gray-200">
                    {{ $entry->source_type }} @if ($entry->source_id) #{{ $entry->source_id }} @endif
                </dd>

                <dt class="text-gray-500">{{ __('accounting::app.journal.status') }}</dt>
                <dd class="text-gray-800 dark:text-gray-200">{{ $entry->status }}</dd>

                <dt class="text-gray-500">{{ __('accounting::app.journal.memo') }}</dt>
                <dd class="text-gray-800 dark:text-gray-200">{{ $entry->memo }}</dd>
            </dl>
        </div>
    </div>

    <div class="mt-4 box-shadow rounded bg-white dark:bg-gray-900 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-950">
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">{{ __('accounting::app.journal.account') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('accounting::app.journal.debit') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('accounting::app.journal.credit') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('accounting::app.journal.tax') }}</th>
                    <th class="px-4 py-3">{{ __('accounting::app.journal.memo') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($entry->lines as $line)
                    <tr class="border-b last:border-b-0 dark:border-gray-800">
                        <td class="px-4 py-2 text-gray-500">{{ $line->line_no }}</td>
                        <td class="px-4 py-2 font-mono text-xs">
                            {{ $line->account?->code }} — {{ $line->account?->name_ja }}
                        </td>
                        <td class="px-4 py-2 text-right font-mono">
                            @if ((int) $line->debit > 0) ¥{{ number_format((int) $line->debit) }} @endif
                        </td>
                        <td class="px-4 py-2 text-right font-mono">
                            @if ((int) $line->credit > 0) ¥{{ number_format((int) $line->credit) }} @endif
                        </td>
                        <td class="px-4 py-2 text-right font-mono text-gray-500">
                            @if ((int) $line->tax_amount > 0) ¥{{ number_format((int) $line->tax_amount) }} @endif
                        </td>
                        <td class="px-4 py-2 text-gray-600">{{ $line->memo }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="border-t-2 bg-gray-50 dark:border-gray-800 dark:bg-gray-950">
                <tr class="text-sm font-semibold">
                    <td class="px-4 py-3" colspan="2">{{ __('accounting::app.journal.totals') }}</td>
                    <td class="px-4 py-3 text-right font-mono">¥{{ number_format((int) $entry->lines->sum('debit')) }}</td>
                    <td class="px-4 py-3 text-right font-mono">¥{{ number_format((int) $entry->lines->sum('credit')) }}</td>
                    <td class="px-4 py-3 text-right font-mono">¥{{ number_format((int) $entry->lines->sum('tax_amount')) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-admin::layouts>

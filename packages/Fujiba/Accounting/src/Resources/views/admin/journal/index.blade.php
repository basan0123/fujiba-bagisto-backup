<x-admin::layouts>
    <x-slot:title>{{ __('accounting::app.menu.journal') }}</x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            {{ __('accounting::app.menu.journal') }}
        </p>
    </div>

    {{-- Filters --}}
    <form
        method="GET"
        action="{{ route('admin.accounting.journal.index') }}"
        class="mt-3.5 flex flex-wrap items-end gap-3 box-shadow rounded bg-white p-4 dark:bg-gray-900"
    >
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.journal.from') }}</label>
            <input type="date" name="from" value="{{ $filters['from'] }}"
                class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.journal.to') }}</label>
            <input type="date" name="to" value="{{ $filters['to'] }}"
                class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.journal.source_type') }}</label>
            <select name="source_type"
                class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <option value="">— {{ __('accounting::app.common.all') }} —</option>
                @foreach ($sourceTypes as $st)
                    <option value="{{ $st }}" @selected($filters['source_type'] === $st)>{{ $st }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.journal.channel') }}</label>
            <select name="channel_id"
                class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <option value="">—</option>
                @foreach (config('accounting.channels', []) as $cid => $code)
                    <option value="{{ $cid }}" @selected((int) $filters['channel_id'] === (int) $cid)>{{ $cid }} ({{ $code }})</option>
                @endforeach
            </select>
        </div>
        <button type="submit"
            class="h-9 rounded-md bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">
            {{ __('accounting::app.common.search') }}
        </button>
    </form>

    {{-- Entries table --}}
    <div class="mt-4 box-shadow rounded bg-white dark:bg-gray-900 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-950">
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <th class="px-4 py-3">{{ __('accounting::app.journal.entry_no') }}</th>
                    <th class="px-4 py-3">{{ __('accounting::app.journal.date') }}</th>
                    <th class="px-4 py-3">{{ __('accounting::app.journal.memo') }}</th>
                    <th class="px-4 py-3">{{ __('accounting::app.journal.channel') }}</th>
                    <th class="px-4 py-3">{{ __('accounting::app.journal.source') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('accounting::app.journal.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $e)
                    @php
                        $debit = $e->lines->sum('debit');
                        $cls = match ($e->source_type) {
                            'order' => 'bg-emerald-100 text-emerald-700',
                            'refund' => 'bg-rose-100 text-rose-700',
                            'manual' => 'bg-blue-100 text-blue-700',
                            'payroll' => 'bg-amber-100 text-amber-700',
                            'receipt' => 'bg-violet-100 text-violet-700',
                            default => 'bg-gray-100 text-gray-700',
                        };
                    @endphp
                    <tr class="border-b last:border-b-0 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-950">
                        <td class="px-4 py-3 font-mono text-xs">
                            <a href="{{ route('admin.accounting.journal.show', $e->id) }}"
                               class="text-blue-600 hover:underline">
                                {{ $e->entry_no ?? '—' }}
                            </a>
                        </td>
                        <td class="px-4 py-3">{{ $e->entry_date?->toDateString() }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $e->memo }}</td>
                        <td class="px-4 py-3">{{ $e->channel_id ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $cls }}">
                                {{ $e->source_type }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-mono">¥{{ number_format($debit) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                            {{ __('accounting::app.journal.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($entries->hasPages())
            <div class="border-t px-4 py-3 dark:border-gray-800">
                {{ $entries->links() }}
            </div>
        @endif
    </div>
</x-admin::layouts>

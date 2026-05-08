<x-admin::layouts>
    <x-slot:title>{{ __('accounting::app.payroll.title') }}</x-slot>

    <p class="text-xl font-bold text-gray-800 dark:text-white">{{ __('accounting::app.payroll.title') }}</p>

    @if (session('success'))
        <div class="mt-3 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mt-3 rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">{{ session('error') }}</div>
    @endif

    <form method="GET" class="mt-3.5 flex flex-wrap items-end gap-3 box-shadow rounded bg-white p-4 dark:bg-gray-900">
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.payroll.year_month') }}</label>
            <input type="month" name="year_month" value="{{ $yearMonth }}" class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
        </div>
        <button type="submit" class="h-9 rounded-md bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">
            {{ __('accounting::app.common.search') }}
        </button>
    </form>

    <form method="POST" action="{{ route('admin.accounting.payroll.run') }}" class="mt-3 flex flex-wrap items-end gap-3 box-shadow rounded bg-white p-4 dark:bg-gray-900">
        @csrf
        <input type="hidden" name="year_month" value="{{ $yearMonth }}"/>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.payroll.pay_date') }}</label>
            <input type="date" name="pay_date" class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
        </div>
        <button type="submit" class="h-9 rounded-md bg-violet-600 px-4 text-sm font-medium text-white hover:bg-violet-700">
            {{ __('accounting::app.payroll.run') }} ({{ $yearMonth }})
        </button>
    </form>

    <div class="mt-4 box-shadow rounded bg-white dark:bg-gray-900 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-950">
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <th class="px-4 py-3">{{ __('accounting::app.staff.staff_code') }}</th>
                    <th class="px-4 py-3">{{ __('accounting::app.staff.name_ja') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('accounting::app.payroll.gross') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('accounting::app.payroll.deductions') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('accounting::app.payroll.net') }}</th>
                    <th class="px-4 py-3">{{ __('accounting::app.payroll.status') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($slips as $slip)
                    @php
                        $cls = match ($slip->status) {
                            'draft'     => 'bg-amber-100 text-amber-700',
                            'finalized' => 'bg-emerald-100 text-emerald-700',
                            'paid'      => 'bg-blue-100 text-blue-700',
                            default     => 'bg-gray-100 text-gray-700',
                        };
                    @endphp
                    <tr class="border-b last:border-b-0 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-950">
                        <td class="px-4 py-2 font-mono text-xs">{{ $slip->staff?->staff_code }}</td>
                        <td class="px-4 py-2"><a href="{{ route('admin.accounting.payroll.show', $slip->id) }}" class="text-blue-600 hover:underline">{{ $slip->staff?->name_ja }}</a></td>
                        <td class="px-4 py-2 text-right font-mono">¥{{ number_format((int) $slip->gross_pay) }}</td>
                        <td class="px-4 py-2 text-right font-mono text-rose-600">¥{{ number_format((int) $slip->total_deduction) }}</td>
                        <td class="px-4 py-2 text-right font-mono font-semibold">¥{{ number_format((int) $slip->net_pay) }}</td>
                        <td class="px-4 py-2"><span class="rounded-full px-2 py-0.5 text-xs {{ $cls }}">{{ __('accounting::app.payroll.' . $slip->status) }}</span></td>
                        <td class="px-4 py-2 text-right">
                            @if ($slip->status === 'draft')
                                <form method="POST" action="{{ route('admin.accounting.payroll.finalize', $slip->id) }}" class="inline">
                                    @csrf
                                    <button class="text-xs text-emerald-700 hover:underline">{{ __('accounting::app.payroll.finalize') }}</button>
                                </form>
                            @elseif ($slip->journal_entry_id)
                                <a href="{{ route('admin.accounting.journal.show', $slip->journal_entry_id) }}" class="text-xs text-blue-600 hover:underline">仕訳</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-gray-500">{{ __('accounting::app.payroll.no_results') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin::layouts>

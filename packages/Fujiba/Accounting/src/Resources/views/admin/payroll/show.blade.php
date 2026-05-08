<x-admin::layouts>
    <x-slot:title>{{ $slip->staff?->name_ja }} — {{ $slip->year_month }}</x-slot>

    <div class="flex items-center justify-between gap-4">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            {{ $slip->staff?->name_ja }} — {{ $slip->year_month }}
        </p>
        <a href="{{ route('admin.accounting.payroll.index', ['year_month' => $slip->year_month]) }}"
           class="rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50 dark:border-gray-700">← {{ __('accounting::app.common.back') }}</a>
    </div>

    @if (session('success'))
        <div class="mt-3 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mt-3 rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div class="box-shadow rounded bg-white p-5 dark:bg-gray-900">
            <p class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">支給</p>
            <dl class="grid grid-cols-2 gap-y-2 text-sm">
                <dt class="text-gray-500">{{ __('accounting::app.payroll.base_pay') }}</dt>           <dd class="text-right font-mono">¥{{ number_format((int) $slip->base_pay) }}</dd>
                <dt class="text-gray-500">{{ __('accounting::app.payroll.overtime_pay') }}</dt>       <dd class="text-right font-mono">¥{{ number_format((int) $slip->overtime_pay) }}</dd>
                <dt class="text-gray-500">{{ __('accounting::app.payroll.night_pay') }}</dt>          <dd class="text-right font-mono">¥{{ number_format((int) $slip->night_pay) }}</dd>
                <dt class="text-gray-500">{{ __('accounting::app.payroll.holiday_pay') }}</dt>        <dd class="text-right font-mono">¥{{ number_format((int) $slip->holiday_pay) }}</dd>
                <dt class="text-gray-500">{{ __('accounting::app.payroll.commute') }}</dt>            <dd class="text-right font-mono">¥{{ number_format((int) $slip->commute_allowance) }}</dd>
                <dt class="text-gray-500">{{ __('accounting::app.payroll.other_allowance') }}</dt>    <dd class="text-right font-mono">¥{{ number_format((int) $slip->other_allowance) }}</dd>
                <dt class="border-t pt-2 font-semibold">{{ __('accounting::app.payroll.gross') }}</dt>
                <dd class="border-t pt-2 text-right font-mono font-semibold">¥{{ number_format((int) $slip->gross_pay) }}</dd>
            </dl>
        </div>

        <div class="box-shadow rounded bg-white p-5 dark:bg-gray-900">
            <p class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">控除</p>
            <dl class="grid grid-cols-2 gap-y-2 text-sm">
                <dt class="text-gray-500">{{ __('accounting::app.payroll.health_insurance') }}</dt>      <dd class="text-right font-mono text-rose-600">¥{{ number_format((int) $slip->health_insurance) }}</dd>
                <dt class="text-gray-500">{{ __('accounting::app.payroll.pension') }}</dt>               <dd class="text-right font-mono text-rose-600">¥{{ number_format((int) $slip->pension) }}</dd>
                <dt class="text-gray-500">{{ __('accounting::app.payroll.employment_insurance') }}</dt>  <dd class="text-right font-mono text-rose-600">¥{{ number_format((int) $slip->employment_insurance) }}</dd>
                <dt class="text-gray-500">{{ __('accounting::app.payroll.income_tax') }}</dt>            <dd class="text-right font-mono text-rose-600">¥{{ number_format((int) $slip->income_tax) }}</dd>
                <dt class="text-gray-500">{{ __('accounting::app.payroll.resident_tax') }}</dt>          <dd class="text-right font-mono text-rose-600">¥{{ number_format((int) $slip->resident_tax) }}</dd>
                <dt class="text-gray-500">{{ __('accounting::app.payroll.other_deduction') }}</dt>       <dd class="text-right font-mono text-rose-600">¥{{ number_format((int) $slip->other_deduction) }}</dd>
                <dt class="border-t pt-2 font-semibold">{{ __('accounting::app.payroll.deductions') }}</dt>
                <dd class="border-t pt-2 text-right font-mono font-semibold text-rose-600">¥{{ number_format((int) $slip->total_deduction) }}</dd>
            </dl>
        </div>

        <div class="md:col-span-2 box-shadow rounded bg-white p-5 dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <p class="text-2xl">
                    <span class="text-sm text-gray-500">{{ __('accounting::app.payroll.net') }}</span>
                    <span class="font-mono font-bold">¥{{ number_format((int) $slip->net_pay) }}</span>
                </p>
                <div class="flex gap-2">
                    @if ($slip->status === 'draft')
                        <form method="POST" action="{{ route('admin.accounting.payroll.finalize', $slip->id) }}">
                            @csrf
                            <button class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                                {{ __('accounting::app.payroll.finalize') }}
                            </button>
                        </form>
                    @elseif ($slip->journal_entry_id)
                        <a href="{{ route('admin.accounting.journal.show', $slip->journal_entry_id) }}"
                           class="rounded-md border px-4 py-2 text-sm hover:bg-gray-50 dark:border-gray-700">仕訳 {{ $slip->journalEntry?->entry_no }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin::layouts>

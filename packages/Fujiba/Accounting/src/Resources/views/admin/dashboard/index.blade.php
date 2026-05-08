<x-admin::layouts>
    <x-slot:title>{{ __('accounting::app.dashboard.title') }}</x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            {{ __('accounting::app.dashboard.title') }}
        </p>
    </div>

    <div class="mt-6 grid gap-4 md:grid-cols-3">

        {{-- Welcome / placeholder card --}}
        <div class="md:col-span-3 box-shadow rounded bg-white p-6 dark:bg-gray-900">
            <p class="text-2xl font-bold text-gray-800 dark:text-white">
                {{ __('accounting::app.dashboard.welcome') }}
            </p>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                {{ __('accounting::app.dashboard.placeholder') }}
            </p>
        </div>

        {{-- Current fiscal period --}}
        <div class="box-shadow rounded bg-white p-5 dark:bg-gray-900">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ __('accounting::app.dashboard.fiscal_period') }}
            </p>
            <p class="mt-2 text-lg font-semibold text-gray-800 dark:text-white">
                @if ($currentPeriod)
                    {{ $currentPeriod->name }}
                @else
                    <span class="text-gray-400">{{ __('accounting::app.dashboard.no_period') }}</span>
                @endif
            </p>
            @if ($currentPeriod)
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ $currentPeriod->start_date->toDateString() }}
                    →
                    {{ $currentPeriod->end_date->toDateString() }}
                </p>
            @endif
        </div>

        {{-- Accounts seeded --}}
        <div class="box-shadow rounded bg-white p-5 dark:bg-gray-900">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ __('accounting::app.dashboard.accounts_seeded') }}
            </p>
            <p class="mt-2 text-3xl font-bold text-gray-800 dark:text-white">
                {{ number_format($accountCount) }}
            </p>
        </div>

        {{-- Audit log rows --}}
        <div class="box-shadow rounded bg-white p-5 dark:bg-gray-900">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ __('accounting::app.dashboard.audit_log_rows') }}
            </p>
            <p class="mt-2 text-3xl font-bold text-gray-800 dark:text-white">
                {{ number_format($auditLogRows) }}
            </p>
        </div>
    </div>

    {{-- Sprint 5: 当月サマリー widget --}}
    @php
        $w = $widget ?? null;
        $fmtPct = function ($v) {
            if ($v === null) return '<span class="text-gray-400">—</span>';
            $sign = $v > 0 ? '+' : '';
            $cls = $v > 0 ? 'text-emerald-600' : ($v < 0 ? 'text-rose-600' : 'text-gray-500');
            return '<span class="' . $cls . '">' . $sign . number_format($v, 1) . '%</span>';
        };
    @endphp
    @if ($w)
        <div class="mt-6 grid gap-4 md:grid-cols-4">
            <div class="md:col-span-4 flex items-center justify-between">
                <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                    {{ __('accounting::app.reports.dash_this_month') }} ({{ $w['period'] }})
                </p>
                <p class="text-xs text-gray-400">
                    {{ __('accounting::app.reports.output_at') }}:
                    {{ \Illuminate\Support\Carbon::parse($w['computed_at'])->toDateTimeString() }}
                </p>
            </div>

            <div class="box-shadow rounded bg-white p-5 dark:bg-gray-900">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ __('accounting::app.reports.dash_sales') }}
                </p>
                <p class="mt-2 text-2xl font-bold font-mono text-gray-800 dark:text-white">¥{{ number_format($w['sales']) }}</p>
                <p class="mt-1 text-xs">
                    {{ __('accounting::app.reports.dash_vs_last_month') }}: {!! $fmtPct($w['sales_pct']) !!}
                </p>
            </div>

            <div class="box-shadow rounded bg-white p-5 dark:bg-gray-900">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ __('accounting::app.reports.dash_purchases') }}
                </p>
                <p class="mt-2 text-2xl font-bold font-mono text-gray-800 dark:text-white">¥{{ number_format($w['cogs']) }}</p>
                <p class="mt-1 text-xs">
                    {{ __('accounting::app.reports.dash_vs_last_month') }}: {!! $fmtPct($w['cogs_pct']) !!}
                </p>
            </div>

            <div class="box-shadow rounded bg-white p-5 dark:bg-gray-900">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ __('accounting::app.reports.dash_gross') }}
                </p>
                <p class="mt-2 text-2xl font-bold font-mono text-gray-800 dark:text-white">¥{{ number_format($w['gross']) }}</p>
                <p class="mt-1 text-xs">
                    {{ __('accounting::app.reports.dash_vs_last_month') }}: {!! $fmtPct($w['gross_pct']) !!}
                </p>
            </div>

            <div class="box-shadow rounded bg-white p-5 dark:bg-gray-900">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ __('accounting::app.reports.dash_unapproved_tc') }}
                </p>
                <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($w['unapproved_timecards']) }}</p>
                @if ($w['unapproved_timecards'] > 0)
                    <a href="{{ route('admin.accounting.timecards.index', ['status' => 'closed']) }}"
                       class="mt-1 inline-block text-xs text-blue-600 hover:underline">→ 確認</a>
                @endif
            </div>
        </div>
    @endif
</x-admin::layouts>

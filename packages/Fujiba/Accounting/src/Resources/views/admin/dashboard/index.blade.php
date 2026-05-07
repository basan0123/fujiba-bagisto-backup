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
</x-admin::layouts>

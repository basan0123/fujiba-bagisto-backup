<x-admin::layouts>
    <x-slot:title>{{ __('accounting::app.staff.title') }}</x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">{{ __('accounting::app.staff.title') }}</p>
        <a href="{{ route('admin.accounting.staff.create') }}"
           class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            + {{ __('accounting::app.staff.create') }}
        </a>
    </div>

    @if (session('success'))
        <div class="mt-3 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    <form method="GET" class="mt-3.5 flex flex-wrap items-end gap-3 box-shadow rounded bg-white p-4 dark:bg-gray-900">
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.common.search') }}</label>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="名前/コード"
                   class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.staff.channel') }}</label>
            <select name="channel_id" class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <option value="">—</option>
                @foreach (config('accounting.channels', []) as $cid => $code)
                    <option value="{{ $cid }}" @selected((int) ($filters['channel_id'] ?? 0) === (int) $cid)>{{ $cid }} ({{ $code }})</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.staff.is_active') }}</label>
            <select name="active" class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <option value="">—</option>
                <option value="1" @selected(($filters['active'] ?? '') === '1')>有</option>
                <option value="0" @selected(($filters['active'] ?? '') === '0')>無</option>
            </select>
        </div>
        <button type="submit" class="h-9 rounded-md bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">
            {{ __('accounting::app.common.search') }}
        </button>
    </form>

    <div class="mt-4 box-shadow rounded bg-white dark:bg-gray-900 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-950">
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <th class="px-4 py-3">{{ __('accounting::app.staff.staff_code') }}</th>
                    <th class="px-4 py-3">{{ __('accounting::app.staff.name_ja') }}</th>
                    <th class="px-4 py-3">{{ __('accounting::app.staff.employment_type') }}</th>
                    <th class="px-4 py-3">{{ __('accounting::app.staff.channel') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('accounting::app.staff.hourly_rate') }}</th>
                    <th class="px-4 py-3">{{ __('accounting::app.staff.is_active') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($staff as $s)
                    <tr class="border-b last:border-b-0 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-950">
                        <td class="px-4 py-3 font-mono text-xs">{{ $s->staff_code }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.accounting.staff.edit', $s->id) }}" class="text-blue-600 hover:underline">
                                {{ $s->name_ja }}
                            </a>
                            @if ($s->name_kana)<div class="text-xs text-gray-500">{{ $s->name_kana }}</div>@endif
                        </td>
                        <td class="px-4 py-3">{{ __('accounting::app.staff.employment_' . $s->employment_type) }}</td>
                        <td class="px-4 py-3">{{ $s->channel_id ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-mono">
                            @if ($s->hourly_rate) ¥{{ number_format((int) $s->hourly_rate) }} @endif
                            @if ($s->monthly_salary) <div class="text-xs text-gray-500">月給 ¥{{ number_format((int) $s->monthly_salary) }}</div>@endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($s->is_active)
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">有</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700">無</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.accounting.staff.qr', $s->id) }}"
                               class="text-xs text-blue-600 hover:underline">{{ __('accounting::app.staff.print_qr') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-gray-500">{{ __('accounting::app.staff.no_results') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($staff->hasPages())
            <div class="border-t px-4 py-3 dark:border-gray-800">{{ $staff->links() }}</div>
        @endif
    </div>
</x-admin::layouts>

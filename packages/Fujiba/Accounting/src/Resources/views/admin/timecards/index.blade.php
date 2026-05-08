<x-admin::layouts>
    <x-slot:title>{{ __('accounting::app.timecards.title') }}</x-slot>

    <p class="text-xl font-bold text-gray-800 dark:text-white">{{ __('accounting::app.timecards.title') }}</p>

    @if (session('success'))
        <div class="mt-3 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    <form method="GET" class="mt-3.5 flex flex-wrap items-end gap-3 box-shadow rounded bg-white p-4 dark:bg-gray-900">
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.journal.from') }}</label>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.journal.to') }}</label>
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.timecards.staff') }}</label>
            <select name="staff_id" class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <option value="">—</option>
                @foreach ($staff as $s)
                    <option value="{{ $s->id }}" @selected((int) ($filters['staff_id'] ?? 0) === $s->id)>{{ $s->staff_code }} {{ $s->name_ja }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.timecards.status') }}</label>
            <select name="status" class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <option value="">—</option>
                @foreach (['open','closed','edited','approved'] as $st)
                    <option value="{{ $st }}" @selected(($filters['status'] ?? '') === $st)>{{ __('accounting::app.timecards.' . $st) }}</option>
                @endforeach
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
                    <th class="px-4 py-3">{{ __('accounting::app.timecards.work_date') }}</th>
                    <th class="px-4 py-3">{{ __('accounting::app.timecards.staff') }}</th>
                    <th class="px-4 py-3">{{ __('accounting::app.timecards.clock_in') }}</th>
                    <th class="px-4 py-3">{{ __('accounting::app.timecards.clock_out') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('accounting::app.timecards.work_min') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('accounting::app.timecards.overtime_min') }}</th>
                    <th class="px-4 py-3">{{ __('accounting::app.timecards.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cards as $c)
                    @php
                        $cls = match ($c->status) {
                            'open'     => 'bg-amber-100 text-amber-700',
                            'closed'   => 'bg-blue-100 text-blue-700',
                            'edited'   => 'bg-violet-100 text-violet-700',
                            'approved' => 'bg-emerald-100 text-emerald-700',
                            default    => 'bg-gray-100 text-gray-700',
                        };
                    @endphp
                    <tr class="border-b last:border-b-0 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-950">
                        <td class="px-4 py-2">
                            <a href="{{ route('admin.accounting.timecards.edit', $c->id) }}" class="text-blue-600 hover:underline">
                                {{ $c->work_date->toDateString() }}
                            </a>
                        </td>
                        <td class="px-4 py-2">{{ $c->staff?->name_ja }} <span class="text-xs text-gray-500">({{ $c->staff?->staff_code }})</span></td>
                        <td class="px-4 py-2 font-mono">{{ optional($c->clock_in)->format('H:i') }}</td>
                        <td class="px-4 py-2 font-mono">{{ optional($c->clock_out)->format('H:i') }}</td>
                        <td class="px-4 py-2 text-right font-mono">{{ $c->work_minutes }}</td>
                        <td class="px-4 py-2 text-right font-mono">{{ $c->overtime_minutes }}</td>
                        <td class="px-4 py-2">
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $cls }}">{{ __('accounting::app.timecards.' . $c->status) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-gray-500">{{ __('accounting::app.timecards.no_results') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($cards->hasPages())<div class="border-t px-4 py-3 dark:border-gray-800">{{ $cards->links() }}</div>@endif
    </div>
</x-admin::layouts>

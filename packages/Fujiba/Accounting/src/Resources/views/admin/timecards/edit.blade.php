<x-admin::layouts>
    <x-slot:title>{{ $card->work_date->toDateString() }} — {{ $card->staff?->name_ja }}</x-slot>

    <div class="flex items-center justify-between gap-4">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            {{ $card->staff?->name_ja }} — {{ $card->work_date->toDateString() }}
        </p>
        <a href="{{ route('admin.accounting.timecards.index') }}" class="rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50 dark:border-gray-700">
            ← {{ __('accounting::app.common.back') }}
        </a>
    </div>

    @if (session('success'))
        <div class="mt-3 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mt-3 rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.accounting.timecards.update', $card->id) }}" class="mt-4 box-shadow rounded bg-white p-5 dark:bg-gray-900">
        @csrf @method('PUT')

        <div class="grid gap-3 md:grid-cols-2">
            <div>
                <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.timecards.clock_in') }}</label>
                <input type="datetime-local" name="clock_in"
                       value="{{ optional($card->clock_in)->format('Y-m-d\\TH:i') }}"
                       class="mt-1 h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.timecards.clock_out') }}</label>
                <input type="datetime-local" name="clock_out"
                       value="{{ optional($card->clock_out)->format('Y-m-d\\TH:i') }}"
                       class="mt-1 h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.timecards.break_min') }}</label>
                <input type="number" name="break_minutes" value="{{ $card->break_minutes }}" min="0" max="1440"
                       class="mt-1 h-9 w-full rounded-md border px-3 text-right font-mono text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.timecards.work_min') }}</label>
                <input type="number" name="work_minutes" value="{{ $card->work_minutes }}" min="0"
                       class="mt-1 h-9 w-full rounded-md border px-3 text-right font-mono text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.timecards.overtime_min') }}</label>
                <input type="number" name="overtime_minutes" value="{{ $card->overtime_minutes }}" min="0"
                       class="mt-1 h-9 w-full rounded-md border px-3 text-right font-mono text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600">深夜(分)</label>
                <input type="number" name="night_minutes" value="{{ $card->night_minutes }}" min="0"
                       class="mt-1 h-9 w-full rounded-md border px-3 text-right font-mono text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
            </div>
            <div class="md:col-span-2">
                <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.timecards.status') }}</label>
                <select name="status" class="mt-1 h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                    @foreach (['open','closed','edited','approved'] as $st)
                        <option value="{{ $st }}" @selected($card->status === $st)>{{ __('accounting::app.timecards.' . $st) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="text-xs font-medium text-gray-600">備考</label>
                <textarea name="notes" rows="2" class="mt-1 w-full rounded-md border px-3 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">{{ $card->notes }}</textarea>
            </div>
        </div>

        <div class="mt-4 flex justify-between">
            @if ($card->status !== 'approved')
                <button type="button"
                        onclick="document.getElementById('approve-form').submit()"
                        class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    {{ __('accounting::app.timecards.approve') }}
                </button>
            @else
                <span></span>
            @endif
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                {{ __('accounting::app.common.save') }}
            </button>
        </div>
    </form>

    @if ($card->status !== 'approved')
        <form id="approve-form" method="POST" action="{{ route('admin.accounting.timecards.approve', $card->id) }}" class="hidden">@csrf</form>
    @endif
</x-admin::layouts>

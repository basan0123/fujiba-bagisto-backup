<x-admin::layouts>
    <x-slot:title>{{ __('sagawa::app.schedule.title') }}</x-slot>

    @php
        $inputClass = 'w-full rounded-md border px-2.5 py-1.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 focus:outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300';
        $labelClass = 'block text-[11px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1';

        // 0=Sun ... 6=Sat (matches Carbon::dayOfWeek and JS Date.getDay())
        $weekdays = [
            1 => __('sagawa::app.schedule.weekday.mon'),
            2 => __('sagawa::app.schedule.weekday.tue'),
            3 => __('sagawa::app.schedule.weekday.wed'),
            4 => __('sagawa::app.schedule.weekday.thu'),
            5 => __('sagawa::app.schedule.weekday.fri'),
            6 => __('sagawa::app.schedule.weekday.sat'),
            0 => __('sagawa::app.schedule.weekday.sun'),
        ];
    @endphp

    <div class="mx-auto max-w-5xl">
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <div>
                <p class="text-xl font-bold text-gray-800 dark:text-white">
                    {{ __('sagawa::app.schedule.title') }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                    {{ __('sagawa::app.schedule.info') }}
                    <span class="ml-1 text-gray-400 dark:text-gray-500">
                        Tenant:
                        <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[11px] text-gray-700 dark:bg-gray-800 dark:text-gray-300">{{ $tenantId }}</code>
                    </span>
                </p>
            </div>
        </div>

        @if (session('success'))
            <div class="mt-3 rounded-md border border-green-200 bg-green-50 p-2.5 text-sm text-green-800 dark:border-green-900 dark:bg-green-950/40 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mt-3 rounded-md border border-red-200 bg-red-50 p-2.5 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                {{ session('error') }}
            </div>
        @endif

        <div class="mt-3 flex flex-col gap-4">
            @foreach ($channels as $channel)
                @php
                    $row    = $settings[$channel->id] ?? null;
                    $weekly = is_array($row?->weekly_default) && $row->weekly_default !== []
                        ? array_map('intval', $row->weekly_default)
                        : [1, 2, 3, 4, 5];
                    $blackoutText = is_array($row?->blackout_dates) ? implode("\n", $row->blackout_dates) : '';
                    $extraText    = is_array($row?->extra_prep_dates) ? implode("\n", $row->extra_prep_dates) : '';
                @endphp

                <div class="box-shadow rounded bg-white dark:bg-gray-900">
                    <form method="POST" action="{{ route('admin.sagawa.schedule.save') }}">
                        @csrf
                        <input type="hidden" name="channel_id" value="{{ $channel->id }}">

                        <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-4 py-2.5 dark:border-gray-800">
                            <div class="flex items-baseline gap-2">
                                <span class="text-sm font-semibold text-gray-800 dark:text-white">
                                    {{ $channel->code }}
                                </span>
                                <span class="text-[11px] text-gray-400">ID {{ $channel->id }}</span>
                            </div>
                            <button type="submit" class="primary-button !h-7 !px-3 !py-0 !text-xs">
                                {{ __('sagawa::app.schedule.save') }}
                            </button>
                        </div>

                        <div class="grid grid-cols-1 gap-x-4 gap-y-3 p-4 md:grid-cols-3">
                            <div>
                                <label class="{{ $labelClass }}">{{ __('sagawa::app.schedule.processing-days') }}</label>
                                <input type="number" min="0" max="7" name="processing_days"
                                    value="{{ $row?->processing_days ?? 1 }}" class="{{ $inputClass }}">
                                <p class="mt-1 text-[11px] text-gray-400">{{ __('sagawa::app.schedule.processing-days-info') }}</p>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('sagawa::app.schedule.cutoff-hour') }}</label>
                                <input type="number" min="0" max="23" name="cutoff_hour"
                                    value="{{ $row?->cutoff_hour ?? 22 }}" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('sagawa::app.schedule.cutoff-minute') }}</label>
                                <input type="number" min="0" max="59" name="cutoff_minute"
                                    value="{{ $row?->cutoff_minute ?? 30 }}" class="{{ $inputClass }}">
                                <p class="mt-1 text-[11px] text-gray-400">{{ __('sagawa::app.schedule.cutoff-info') }}</p>
                            </div>

                            <div class="md:col-span-3">
                                <label class="{{ $labelClass }}">{{ __('sagawa::app.schedule.weekly-default') }}</label>
                                <div class="flex flex-wrap gap-3">
                                    <input type="hidden" name="weekly_default[]" value="">
                                    @foreach ($weekdays as $idx => $label)
                                        <label class="flex cursor-pointer items-center gap-1.5 rounded border border-gray-200 px-2.5 py-1 text-xs text-gray-700 hover:border-gray-400 dark:border-gray-700 dark:text-gray-300">
                                            <input type="checkbox" name="weekly_default[]" value="{{ $idx }}"
                                                @checked(in_array($idx, $weekly, true))
                                                class="h-3.5 w-3.5 rounded border-gray-300 dark:border-gray-700">
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                                <p class="mt-1 text-[11px] text-gray-400">{{ __('sagawa::app.schedule.weekly-default-info') }}</p>
                            </div>

                            <div class="md:col-span-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="{{ $labelClass }}">{{ __('sagawa::app.schedule.blackout-dates') }}</label>
                                    <textarea name="blackout_dates" rows="4"
                                        placeholder="2026-05-15&#10;2026-05-16"
                                        class="{{ $inputClass }} font-mono text-[12px]">{{ $blackoutText }}</textarea>
                                    <p class="mt-1 text-[11px] text-gray-400">{{ __('sagawa::app.schedule.blackout-dates-info') }}</p>
                                </div>
                                <div>
                                    <label class="{{ $labelClass }}">{{ __('sagawa::app.schedule.extra-prep-dates') }}</label>
                                    <textarea name="extra_prep_dates" rows="4"
                                        placeholder="2026-05-04&#10;2026-05-05"
                                        class="{{ $inputClass }} font-mono text-[12px]">{{ $extraText }}</textarea>
                                    <p class="mt-1 text-[11px] text-gray-400">{{ __('sagawa::app.schedule.extra-prep-dates-info') }}</p>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
</x-admin::layouts>

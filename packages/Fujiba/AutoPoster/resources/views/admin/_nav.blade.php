{{-- Shared sub-navigation + channel switcher. --}}
<div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap gap-1">
        @php($tabs = [
            'admin.autoposter.index'        => 'Dashboard',
            'admin.autoposter.queue'        => 'Queue',
            'admin.autoposter.history'      => 'History',
            'admin.autoposter.posts.create' => 'Manual Post',
            'admin.autoposter.bulk'         => 'Bulk Schedule',
            'admin.autoposter.templates'    => 'Templates',
            'admin.autoposter.settings'     => 'Settings',
        ])

        @foreach ($tabs as $route => $label)
            <a href="{{ route($route, ['channel_id' => $channelId]) }}"
               class="px-3 py-1.5 rounded text-sm transition
                      {{ request()->routeIs($route)
                         ? 'bg-blue-600 text-white'
                         : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    @if (($channels ?? collect())->count() > 1)
        <form method="GET" class="flex items-center gap-2">
            <label class="text-sm text-gray-600 dark:text-gray-300">Channel</label>
            <select name="channel_id" onchange="this.form.submit()"
                    class="text-sm rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                @foreach ($channels as $ch)
                    <option value="{{ $ch->id }}" @selected($ch->id == $channelId)>{{ $ch->code }}</option>
                @endforeach
            </select>
        </form>
    @endif
</div>

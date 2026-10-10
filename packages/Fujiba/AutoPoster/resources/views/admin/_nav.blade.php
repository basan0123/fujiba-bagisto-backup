{{-- Shared sub-navigation + channel switcher. --}}
<div class="ap-navbar">
    <nav class="ap-tabs">
        @php($tabs = [
            'admin.autoposter.index'        => ['Dashboard', 'home'],
            'admin.autoposter.queue'        => ['Queue', 'queue'],
            'admin.autoposter.history'      => ['History', 'history'],
            'admin.autoposter.posts.create' => ['New Post', 'plus'],
            'admin.autoposter.bulk'         => ['Bulk Schedule', 'layers'],
            'admin.autoposter.templates'    => ['Templates', 'template'],
            'admin.autoposter.tiktok'       => ['TikTok', 'send'],
            'admin.autoposter.settings'     => ['Settings', 'settings'],
        ])

        @foreach ($tabs as $route => [$label, $icon])
            <a href="{{ route($route, ['channel_id' => $channelId]) }}"
               class="ap-tab {{ request()->routeIs($route) ? 'is-active' : '' }}">
                @include('autoposter::admin._icon', ['i' => $icon]) {{ $label }}
            </a>
        @endforeach
    </nav>

    @if (($channels ?? collect())->count() > 1)
        <form method="GET" class="ap-field" style="flex-direction: row; align-items: center; gap: 8px;">
            <span class="ap-label ap-muted">Channel</span>
            <select name="channel_id" onchange="this.form.submit()" style="width: auto; min-width: 140px;">
                @foreach ($channels as $ch)
                    <option value="{{ $ch->id }}" @selected($ch->id == $channelId)>{{ $ch->code }}</option>
                @endforeach
            </select>
        </form>
    @endif
</div>

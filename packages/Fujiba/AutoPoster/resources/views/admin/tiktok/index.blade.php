<x-admin::layouts>
    <x-slot:title>Auto Poster — TikTok</x-slot:title>

    <div class="ap">
        @include('autoposter::admin._header', ['title' => 'TikTok', 'subtitle' => 'Connect your TikTok account and share AutoPoster posts to it.'])

        @include('autoposter::admin._nav')
        @include('autoposter::admin._flash')

        <div class="ap-card ap-rise" style="margin-bottom: 16px;">
            <h2 class="ap-card-title" style="margin-bottom: 12px;">TikTok account</h2>

            @if ($account)
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    @if ($account->avatar_url)
                        <img src="{{ $account->avatar_url }}" alt="" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">
                    @endif
                    <div style="flex: 1; min-width: 160px;">
                        <div style="font-weight: 600;">{{ $account->display_name ?: $account->open_id }}</div>
                        <div class="ap-muted ap-small">Connected · token refreshes automatically</div>
                    </div>
                    <form method="POST" action="{{ route('admin.autoposter.tiktok.disconnect') }}" onsubmit="return confirm('Disconnect TikTok?')">
                        @csrf
                        <input type="hidden" name="channel_id" value="{{ $channelId }}">
                        <button class="ap-btn ap-btn-secondary ap-btn-sm">Disconnect</button>
                    </form>
                </div>
            @else
                <p class="ap-card-desc" style="margin-top: 0;">Log in with TikTok to let Fujiba post product videos and photos to your account. You confirm every post before it is sent.</p>
                <a href="{{ route('admin.autoposter.tiktok.connect', ['channel_id' => $channelId]) }}" class="ap-btn ap-btn-primary">Connect TikTok</a>
            @endif
        </div>

        @if ($account)
            <div class="ap-card ap-rise" style="margin-bottom: 16px;">
                <h2 class="ap-card-title" style="margin-bottom: 12px;">Posts ready for TikTok</h2>

                <div style="display: flex; gap: 12px; align-items: center; padding: 10px 0;">
                    <video src="{{ \Fujiba\AutoPoster\Http\Controllers\Admin\TikTokController::TEST_VIDEO }}" muted preload="metadata" style="width: 56px; height: 84px; object-fit: cover; border-radius: 6px; background: #000;"></video>
                    <div style="flex: 1; min-width: 0;">
                        <div class="ap-small" style="font-weight: 600;">Content Posting API test video</div>
                        <div class="ap-muted ap-small">10s test clip · use this to test the connection</div>
                    </div>
                    <a href="{{ route('admin.autoposter.tiktok.compose', ['id' => 0, 'channel_id' => $channelId]) }}" class="ap-btn ap-btn-primary ap-btn-sm">Share to TikTok</a>
                </div>

                @forelse ($posts as $post)
                    @php($p = $post->instagram_payload)
                    <div style="display: flex; gap: 12px; align-items: center; padding: 10px 0; border-top: 1px solid rgba(127,127,127,.15);">
                        @if (! empty($p['video']))
                            <video src="{{ $p['video'] }}" muted preload="metadata" style="width: 56px; height: 84px; object-fit: cover; border-radius: 6px; background: #000;"></video>
                        @elseif (! empty($p['images'][0]))
                            <img src="{{ $p['images'][0] }}" alt="" style="width: 56px; height: 56px; object-fit: cover; border-radius: 6px;">
                        @endif
                        <div style="flex: 1; min-width: 0;">
                            <div class="ap-clamp ap-small">{{ \Illuminate\Support\Str::limit($p['message'] ?? $post->caption, 120) }}</div>
                            <div class="ap-muted ap-small">#{{ $post->id }} · {{ ! empty($p['video']) ? 'Video' : count($p['images'] ?? []).' photos' }} · {{ $post->published_at?->format('Y-m-d H:i') }}</div>
                        </div>
                        <a href="{{ route('admin.autoposter.tiktok.compose', $post->id) }}" class="ap-btn ap-btn-primary ap-btn-sm">Share to TikTok</a>
                    </div>
                @empty
                    <div class="ap-empty">No published posts with media yet.</div>
                @endforelse
            </div>

            <div class="ap-card ap-rise">
                <h2 class="ap-card-title" style="margin-bottom: 12px;">Sent to TikTok</h2>
                @forelse ($sent as $s)
                    <div style="display: flex; gap: 10px; padding: 8px 0; border-top: 1px solid rgba(127,127,127,.15); flex-wrap: wrap;" data-tt-status="{{ route('admin.autoposter.tiktok.status', $s->id) }}" data-final="{{ in_array($s->status, ['PUBLISH_COMPLETE', 'SEND_TO_USER_INBOX', 'FAILED']) ? 1 : 0 }}">
                        <span class="ap-small">{{ $s->queue_id ? '#'.$s->queue_id : 'Test video' }} · {{ $s->mode === 'direct' ? 'Direct post' : 'Draft' }} · {{ $s->media_type }}{{ $s->privacy_level ? ' · '.$s->privacy_level : '' }}</span>
                        <span class="ap-badge" data-status-text>{{ $s->status }}</span>
                        <span class="ap-muted ap-small" data-error>{{ $s->error }}</span>
                        <span class="ap-muted ap-small" style="margin-left: auto;">{{ $s->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                @empty
                    <div class="ap-empty">Nothing sent yet.</div>
                @endforelse
            </div>
        @endif
    </div>

    @pushOnce('scripts')
    <script>
        window.addEventListener('load', function () { setTimeout(function () {
        document.querySelectorAll('[data-tt-status][data-final="0"]').forEach(function (row) {
            var tries = 0;
            (function poll() {
                fetch(row.dataset.ttStatus, { headers: { Accept: 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        row.querySelector('[data-status-text]').textContent = d.status;
                        row.querySelector('[data-error]').textContent = d.error || '';
                        if (['PUBLISH_COMPLETE', 'SEND_TO_USER_INBOX', 'FAILED'].indexOf(d.status) === -1 && ++tries < 40) setTimeout(poll, 5000);
                    });
            })();
        });
        }, 300); });
    </script>
    @endPushOnce
</x-admin::layouts>

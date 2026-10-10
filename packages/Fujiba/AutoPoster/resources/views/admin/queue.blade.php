<x-admin::layouts>
    <x-slot:title>Auto Poster — Queue</x-slot:title>

    <div class="ap">
        @include('autoposter::admin._header', [
            'title'    => 'Queue',
            'subtitle' => 'Everything waiting to go out, plus drafts and failures.',
        ])

        @include('autoposter::admin._nav')
        @include('autoposter::admin._flash')

        <div class="ap-pills">
            @php($all = array_sum($counts))
            <a href="{{ route('admin.autoposter.queue', ['channel_id' => $channelId, 'status' => 'all']) }}"
               class="ap-pill {{ $status === 'all' ? 'is-active' : '' }}">
                All <span class="ap-pill-count">{{ $all }}</span>
            </a>
            @foreach (['draft','scheduled','processing','published','failed','cancelled'] as $st)
                <a href="{{ route('admin.autoposter.queue', ['channel_id' => $channelId, 'status' => $st]) }}"
                   class="ap-pill {{ $status === $st ? 'is-active' : '' }}">
                    {{ $st }} <span class="ap-pill-count">{{ $counts[$st] ?? 0 }}</span>
                </a>
            @endforeach
        </div>

        @if ($posts->isEmpty())
            <div class="ap-card ap-empty ap-rise">
                <div class="ap-empty-icon ap-tone-blue">@include('autoposter::admin._icon', ['i' => 'queue'])</div>
                <h3>No posts{{ $status !== 'all' ? ' with status “' . $status . '”' : '' }} yet</h3>
                <p style="margin-bottom: 16px;">Create a post by hand or let the scheduler fill today's slots.</p>
                <a href="{{ route('admin.autoposter.posts.create', ['channel_id' => $channelId]) }}" class="ap-btn ap-btn-primary">
                    @include('autoposter::admin._icon', ['i' => 'plus']) New post
                </a>
            </div>
        @else
            <div class="ap-card ap-card-flush ap-rise">
                <div class="ap-table-wrap">
                    <table class="ap-table">
                        <thead>
                            <tr>
                                <th>Preview</th>
                                <th>Post</th>
                                <th>Caption</th>
                                <th>Status</th>
                                <th>Scheduled</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($posts as $post)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.autoposter.posts.edit', $post->id) }}" class="ap-thumbs">
                                            @foreach ($post->products->take(3) as $item)
                                                @if ($item->effectiveImageUrl())
                                                    <img src="{{ $item->effectiveImageUrl() }}" alt="" loading="lazy">
                                                @endif
                                            @endforeach
                                            @if ($post->products->count() > 3)
                                                <span>+{{ $post->products->count() - 3 }}</span>
                                            @endif
                                        </a>
                                    </td>
                                    <td style="white-space: nowrap;">
                                        <b>#{{ $post->id }}</b>
                                        <div class="ap-muted ap-small">{{ $post->origin }} · {{ str_replace('_',' ',$post->format) }}</div>
                                    </td>
                                    <td><div class="ap-clamp">{{ Str::limit($post->caption ?: '— no caption —', 140) }}</div></td>
                                    <td>
                                        @include('autoposter::admin._status', ['status' => $post->status])
                                        @if ($post->status === 'failed' && $post->attempts)
                                            <div class="ap-small" style="color: var(--ap-danger); margin-top: 4px;">{{ $post->attempts }} attempt(s)</div>
                                        @endif
                                    </td>
                                    <td style="white-space: nowrap;">
                                        @if ($post->scheduled_at)
                                            {{ $post->scheduled_at->format('M j, H:i') }}
                                            <div class="ap-muted ap-small">{{ $post->scheduled_at->diffForHumans() }}</div>
                                        @else
                                            <span class="ap-muted">—</span>
                                        @endif
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <div class="ap-actions" style="justify-content: flex-end; gap: 4px;">
                                            @if ($post->status === 'failed')
                                                <form method="POST" action="{{ route('admin.autoposter.posts.retry', $post->id) }}">
                                                    @csrf
                                                    <button class="ap-btn ap-btn-ghost ap-btn-sm" title="Retry">@include('autoposter::admin._icon', ['i' => 'refresh'])</button>
                                                </form>
                                            @endif

                                            @if ($post->isCancellable())
                                                <form method="POST" action="{{ route('admin.autoposter.posts.cancel', $post->id) }}"
                                                      onsubmit="return confirm('Cancel this post?');">
                                                    @csrf
                                                    <button class="ap-btn ap-btn-ghost ap-btn-sm" title="Cancel">@include('autoposter::admin._icon', ['i' => 'x'])</button>
                                                </form>
                                            @endif

                                            <a href="{{ route('admin.autoposter.posts.edit', $post->id) }}" class="ap-btn ap-btn-secondary ap-btn-sm">
                                                @include('autoposter::admin._icon', ['i' => $post->isPublished() ? 'eye' : 'edit'])
                                                {{ $post->isPublished() ? 'View' : 'Edit' }}
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $posts->links() }}</div>
        @endif
    </div>
</x-admin::layouts>

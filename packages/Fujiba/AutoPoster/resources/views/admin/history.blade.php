<x-admin::layouts>
    <x-slot:title>Auto Poster — History</x-slot:title>

    <div class="ap">
        @include('autoposter::admin._header', [
            'title'    => 'Post history',
            'subtitle' => 'Everything that has gone out to Facebook.',
        ])

        @include('autoposter::admin._nav')
        @include('autoposter::admin._flash')

        @if ($posts->isEmpty())
            <div class="ap-card ap-empty ap-rise">
                <div class="ap-empty-icon ap-tone-violet">@include('autoposter::admin._icon', ['i' => 'history'])</div>
                <h3>Nothing published yet</h3>
                <p>Published posts will show up here with links to Facebook.</p>
            </div>
        @else
            <div class="ap-card ap-card-flush ap-rise">
                <div class="ap-table-wrap">
                    <table class="ap-table">
                        <thead>
                            <tr>
                                <th>Post</th>
                                <th>Caption</th>
                                <th>Published</th>
                                <th>Status</th>
                                <th>Messenger</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($posts as $post)
                                @php($first = $post->products->first())
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            @if ($first?->effectiveImageUrl())
                                                <img src="{{ $first->effectiveImageUrl() }}" alt="" loading="lazy"
                                                     style="width: 48px; height: 48px; border-radius: 10px; object-fit: cover; flex-shrink: 0;">
                                            @endif
                                            <div style="white-space: nowrap;">
                                                <b>#{{ $post->id }}</b>
                                                <div class="ap-muted ap-small">{{ $post->products->count() }} products · {{ str_replace('_',' ',$post->format) }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><div class="ap-clamp">{{ Str::limit($post->caption, 140) }}</div></td>
                                    <td style="white-space: nowrap;">
                                        {{ $post->published_at?->format('M j, H:i') ?? '—' }}
                                        @if ($post->facebook_post_id)
                                            <div class="ap-muted ap-mono" title="{{ $post->facebook_post_id }}">{{ Str::limit($post->facebook_post_id, 18) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @include('autoposter::admin._status', ['status' => $post->status])
                                        @if ($post->last_error)
                                            <div class="ap-small" style="color: var(--ap-danger); margin-top: 4px;" title="{{ $post->last_error }}">
                                                {{ Str::limit($post->last_error, 40) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($post->messenger_destination)
                                            <a href="{{ $post->messenger_destination }}" target="_blank" rel="noopener" class="ap-badge ap-tone-blue no-dot" title="{{ $post->messenger_destination }}">
                                                Messenger ↗
                                            </a>
                                        @else
                                            <span class="ap-muted">—</span>
                                        @endif
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <div class="ap-actions" style="justify-content: flex-end; gap: 4px;">
                                            @if ($post->facebook_permalink)
                                                <a href="{{ $post->facebook_permalink }}" target="_blank" rel="noopener" class="ap-btn ap-btn-ghost ap-btn-sm" title="Open on Facebook">
                                                    @include('autoposter::admin._icon', ['i' => 'external'])
                                                </a>
                                            @endif
                                            <form method="POST" action="{{ route('admin.autoposter.posts.duplicate', $post->id) }}">
                                                @csrf
                                                <button class="ap-btn ap-btn-ghost ap-btn-sm" title="Post again">@include('autoposter::admin._icon', ['i' => 'copy'])</button>
                                            </form>
                                            <a href="{{ route('admin.autoposter.posts.edit', $post->id) }}" class="ap-btn ap-btn-secondary ap-btn-sm">
                                                @include('autoposter::admin._icon', ['i' => 'eye']) View
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

<x-admin::layouts>
    <x-slot:title>Auto Poster</x-slot:title>

    <div class="ap">
        @include('autoposter::admin._header', [
            'title'    => 'Facebook Auto Poster',
            'subtitle' => 'Schedule product carousels to your Page and route buyers to Messenger.',
        ])

        @include('autoposter::admin._nav')
        @include('autoposter::admin._flash')

        @if (! $channelId)
            <div class="ap-card ap-empty">
                <div class="ap-empty-icon ap-tone-gray">@include('autoposter::admin._icon', ['i' => 'box'])</div>
                <h3>No channels configured</h3>
                <p>Create a sales channel first, then come back to set up auto posting.</p>
            </div>
        @else

        @php($ready = $preflight['ready'] ?? null)
        @php($on = (bool) $settings?->auto_posting_enabled)

        {{-- ── Readiness + master switch ──────────────────────────────── --}}
        <div class="ap-card ap-hero ap-rise {{ $ready === true ? 'is-ready' : ($ready === false ? 'is-blocked' : '') }}">
            <div class="ap-hero-row">
                <div class="ap-hero-state">
                    <div class="ap-hero-icon {{ $ready === true ? 'ap-tone-green' : ($ready === false ? 'ap-tone-red' : 'ap-tone-gray') }}">
                        @include('autoposter::admin._icon', ['i' => $ready === true ? 'shield' : ($ready === false ? 'alert' : 'info')])
                    </div>
                    <div>
                        <div class="ap-hero-title">
                            @if ($ready === true)
                                Ready to launch
                            @elseif ($ready === false)
                                Not ready — {{ $preflight['blocking_failures'] }} issue(s) to fix
                            @else
                                Pre-flight not run yet
                            @endif
                        </div>
                        <div class="ap-muted ap-small" style="margin-top: 2px;">
                            @if ($preflightAt)
                                Last checked {{ $preflightAt->diffForHumans() }}
                                @if (($preflight['warnings'] ?? 0) > 0)
                                    · {{ $preflight['warnings'] }} warning(s)
                                @endif
                            @else
                                Run the checks to confirm this channel can post.
                            @endif
                        </div>
                    </div>
                </div>

                <div class="ap-actions">
                    <form method="POST" action="{{ route('admin.autoposter.preflight') }}">
                        @csrf
                        <input type="hidden" name="channel_id" value="{{ $channelId }}">
                        <button class="ap-btn ap-btn-secondary">
                            @include('autoposter::admin._icon', ['i' => 'refresh']) Run checks
                        </button>
                    </form>

                    {{-- Master toggle. Turning ON re-runs pre-flight server-side;
                         turning OFF mid-queue asks for confirmation. --}}
                    <form method="POST" action="{{ route('admin.autoposter.toggle') }}"
                          @if ($on)
                              onsubmit="return confirm('Turn OFF auto posting?\n\nScheduled posts will stay in the queue but will NOT be published until you turn it back on.');"
                          @endif>
                        @csrf
                        <input type="hidden" name="channel_id" value="{{ $channelId }}">
                        <input type="hidden" name="enable" value="{{ $on ? 0 : 1 }}">
                        <button class="ap-switch {{ $on ? 'is-on' : '' }}" title="{{ $on ? 'Click to turn off' : 'Click to turn on' }}">
                            <span class="ap-switch-track"></span>
                            Auto posting {{ $on ? 'ON' : 'OFF' }}
                        </button>
                    </form>
                </div>
            </div>

            @if (! empty($preflight['checks']))
                <div class="ap-checks">
                    @foreach ($preflight['checks'] as $check)
                        @php($tone = $check['status'] === 'pass' ? 'ap-tone-green' : ($check['status'] === 'warn' ? 'ap-tone-amber' : 'ap-tone-red'))
                        <div class="ap-check-item">
                            <span class="ap-check-dot {{ $tone }}">
                                @include('autoposter::admin._icon', ['i' => $check['status'] === 'pass' ? 'check' : ($check['status'] === 'warn' ? 'alert' : 'x')])
                            </span>
                            <div>
                                <b>{{ $check['label'] }}</b>
                                <p>{{ $check['detail'] }}</p>
                                @if (! empty($check['fix']))
                                    <p class="ap-fix">→ {{ $check['fix'] }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Operator-level kill switch notice. --}}
        @if (! $scheduleOn)
            <div class="ap-alert ap-alert-warn">
                @include('autoposter::admin._icon', ['i' => 'alert'])
                <div class="ap-alert-body">
                    <strong>Global scheduler is OFF.</strong>
                    Posts can be created and scheduled, but nothing will publish automatically until an
                    operator sets <code>AUTOPOSTER_SCHEDULE_ENABLED=true</code> in <code>.env</code>.
                    Manual <em>Post Now</em> still works.
                </div>
            </div>
        @endif

        {{-- ── Stat tiles ─────────────────────────────────────────────── --}}
        <div class="ap-grid ap-grid-4">
            @php($tiles = [
                ['Auto posting', $on ? 'ON' : 'OFF', 'power', $on ? 'ap-tone-green' : 'ap-tone-gray'],
                ['Posts per day', $settings->posts_per_day ?? 5, 'calendar', 'ap-tone-blue'],
                ['Published today', $stats['published_today'], 'send', 'ap-tone-violet'],
                ['Failed', $stats['failed'], 'alert', $stats['failed'] > 0 ? 'ap-tone-red' : 'ap-tone-gray'],
            ])

            @foreach ($tiles as [$label, $value, $icon, $tone])
                <div class="ap-card ap-stat ap-rise">
                    <div class="ap-stat-icon {{ $tone }}">@include('autoposter::admin._icon', ['i' => $icon])</div>
                    <div>
                        <div class="ap-stat-label">{{ $label }}</div>
                        <div class="ap-stat-value">{{ $value }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="ap-grid ap-grid-2">
            {{-- Connection summary --}}
            <div class="ap-card ap-rise">
                <div class="ap-card-head">
                    <h2 class="ap-card-title">@include('autoposter::admin._icon', ['i' => 'link']) Connection</h2>
                </div>
                <dl class="ap-dl">
                    <dt>Page</dt>
                    <dd class="ap-mono">{{ $catalog?->page_id ?: '—' }}</dd>

                    <dt>Catalog</dt>
                    <dd class="ap-mono">{{ $catalog?->catalog_id ?: '—' }}</dd>

                    <dt>Product set</dt>
                    <dd>
                        @php($cfg = $settings?->selection_config ?? [])
                        @if (($settings?->selection_mode) === 'product_set' && ! empty($cfg['retailer_ids']))
                            {{ count($cfg['retailer_ids']) }} SKU(s)
                        @else
                            <span class="ap-badge ap-tone-gray no-dot">{{ str_replace('_', ' ', $settings->selection_mode ?? 'random') }}</span>
                        @endif
                    </dd>

                    <dt>Messenger</dt>
                    <dd>
                        @php($mlink = $settings?->messenger_destination ?: ($catalog?->page_id ? 'https://m.me/' . $catalog->page_id : null))
                        @if ($mlink)
                            <a href="{{ $mlink }}" target="_blank" rel="noopener" class="ap-link" style="word-break: break-all;">{{ $mlink }}</a>
                        @else
                            —
                        @endif
                    </dd>

                    <dt>Eligible</dt>
                    <dd><span class="ap-badge ap-tone-green">{{ $eligibleCount }} products ready</span></dd>
                </dl>
            </div>

            {{-- Timing --}}
            <div class="ap-card ap-rise">
                <div class="ap-card-head">
                    <h2 class="ap-card-title">@include('autoposter::admin._icon', ['i' => 'clock']) Timing</h2>
                    <form method="POST" action="{{ route('admin.autoposter.plan') }}">
                        @csrf
                        <input type="hidden" name="channel_id" value="{{ $channelId }}">
                        <button class="ap-btn ap-btn-secondary ap-btn-sm">
                            @include('autoposter::admin._icon', ['i' => 'sparkles']) Fill today's slots
                        </button>
                    </form>
                </div>
                <dl class="ap-dl">
                    <dt>Next post</dt>
                    <dd>
                        @if ($nextPost)
                            <a href="{{ route('admin.autoposter.posts.edit', $nextPost->id) }}" class="ap-link">
                                {{ $nextPost->scheduled_at->format('Y-m-d H:i') }}
                            </a>
                            <span class="ap-muted ap-small">· {{ $nextPost->scheduled_at->diffForHumans() }}</span>
                        @else
                            <span class="ap-muted">Nothing scheduled</span>
                        @endif
                    </dd>

                    <dt>Last success</dt>
                    <dd>
                        @if ($lastPublished)
                            {{ $lastPublished->published_at?->format('Y-m-d H:i') }}
                            @if ($lastPublished->facebook_permalink)
                                <a href="{{ $lastPublished->facebook_permalink }}" target="_blank" rel="noopener" class="ap-link">· view post</a>
                            @endif
                        @else
                            <span class="ap-muted">None yet</span>
                        @endif
                    </dd>

                    <dt>Window</dt>
                    <dd>
                        @if (! empty($settings?->posting_times))
                            {{ implode(', ', $settings->posting_times) }}
                        @else
                            {{ substr($settings->posting_window_start ?? '09:00:00', 0, 5) }}–{{ substr($settings->posting_window_end ?? '21:30:00', 0, 5) }}
                            <span class="ap-muted">(auto)</span>
                        @endif
                        <span class="ap-muted ap-small">· {{ $settings->timezone ?? 'Asia/Tokyo' }}</span>
                    </dd>
                </dl>
            </div>
        </div>

        {{-- ── Queue by status ───────────────────────────────────────── --}}
        <div class="ap-card ap-rise">
            <div class="ap-card-head">
                <h2 class="ap-card-title">@include('autoposter::admin._icon', ['i' => 'queue']) Queue</h2>
                <div class="ap-actions">
                    <a href="{{ route('admin.autoposter.bulk', ['channel_id' => $channelId]) }}" class="ap-btn ap-btn-secondary">
                        @include('autoposter::admin._icon', ['i' => 'layers']) Bulk schedule
                    </a>
                    <a href="{{ route('admin.autoposter.posts.create', ['channel_id' => $channelId]) }}" class="ap-btn ap-btn-primary">
                        @include('autoposter::admin._icon', ['i' => 'plus']) New post
                    </a>
                </div>
            </div>

            <div class="ap-status-strip">
                @foreach (['draft','scheduled','processing','published','failed','cancelled'] as $st)
                    <a href="{{ route('admin.autoposter.queue', ['channel_id' => $channelId, 'status' => $st]) }}" class="ap-status-cell">
                        @include('autoposter::admin._status', ['status' => $st])
                        <b>{{ $stats[$st] }}</b>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ── Recent posts ──────────────────────────────────────────── --}}
        @if ($recent->count())
            <div class="ap-card ap-card-flush ap-rise">
                <div class="ap-card-head" style="padding: 18px 20px 0;">
                    <h2 class="ap-card-title">@include('autoposter::admin._icon', ['i' => 'chart']) Recent posts</h2>
                    <a href="{{ route('admin.autoposter.queue', ['channel_id' => $channelId]) }}" class="ap-link ap-small">View all →</a>
                </div>
                <div class="ap-table-wrap">
                    <table class="ap-table">
                        <thead>
                            <tr>
                                <th>Post</th>
                                <th>Products</th>
                                <th>Status</th>
                                <th>When</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recent as $post)
                                <tr>
                                    <td>
                                        <b>#{{ $post->id }}</b>
                                        <span class="ap-muted ap-small">· {{ $post->origin }}</span>
                                    </td>
                                    <td>{{ $post->products->count() }}</td>
                                    <td>@include('autoposter::admin._status', ['status' => $post->status])</td>
                                    <td class="ap-muted" style="white-space: nowrap;">
                                        {{ optional($post->published_at ?? $post->scheduled_at)->format('m-d H:i') ?? '—' }}
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="{{ route('admin.autoposter.posts.edit', $post->id) }}" class="ap-btn ap-btn-ghost ap-btn-sm">
                                            @include('autoposter::admin._icon', ['i' => 'eye']) Open
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @endif
    </div>
</x-admin::layouts>

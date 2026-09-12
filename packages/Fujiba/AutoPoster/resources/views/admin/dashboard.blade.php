<x-admin::layouts>
    <x-slot:title>Auto Poster</x-slot:title>

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <p class="text-xl text-gray-800 dark:text-white font-bold">Facebook Auto Poster</p>
        </div>

        @include('autoposter::admin._nav')
        @include('autoposter::admin._flash')

        @if (! $channelId)
            <div class="p-4 border rounded text-sm">No channels configured.</div>
        @else

        @php($ready = $preflight['ready'] ?? null)

        {{-- ── READY TO LAUNCH banner ─────────────────────────────────── --}}
        <div class="rounded border p-4 flex flex-col gap-3
                    {{ $ready === true ? 'border-green-500 bg-green-50 dark:bg-green-900/20'
                       : ($ready === false ? 'border-red-500 bg-red-50 dark:bg-red-900/20'
                       : 'border-gray-300 bg-gray-50 dark:bg-gray-800 dark:border-gray-700') }}">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <div class="text-lg font-bold
                        {{ $ready === true ? 'text-green-700 dark:text-green-300'
                           : ($ready === false ? 'text-red-700 dark:text-red-300' : 'text-gray-700 dark:text-gray-200') }}">
                        @if ($ready === true)
                            ✅ READY TO LAUNCH
                        @elseif ($ready === false)
                            ⚠️ NOT READY — {{ $preflight['blocking_failures'] }} issue(s) to fix
                        @else
                            Pre-flight not run yet
                        @endif
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
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

                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('admin.autoposter.preflight') }}">
                        @csrf
                        <input type="hidden" name="channel_id" value="{{ $channelId }}">
                        <button class="px-3 py-2 border rounded text-sm bg-white dark:bg-gray-900 dark:border-gray-700">
                            Run pre-flight checks
                        </button>
                    </form>

                    {{-- Master toggle. Turning ON re-runs pre-flight server-side;
                         turning OFF mid-queue asks for confirmation. --}}
                    <form method="POST" action="{{ route('admin.autoposter.toggle') }}"
                          @if ($settings?->auto_posting_enabled)
                              onsubmit="return confirm('Turn OFF auto posting?\n\nScheduled posts will stay in the queue but will NOT be published until you turn it back on.');"
                          @endif>
                        @csrf
                        <input type="hidden" name="channel_id" value="{{ $channelId }}">
                        <input type="hidden" name="enable" value="{{ $settings?->auto_posting_enabled ? 0 : 1 }}">
                        <button class="px-4 py-2 rounded text-sm text-white
                                       {{ $settings?->auto_posting_enabled ? 'bg-red-600' : 'bg-green-600' }}">
                            {{ $settings?->auto_posting_enabled ? 'Turn OFF auto posting' : 'Turn ON auto posting' }}
                        </button>
                    </form>
                </div>
            </div>

            @if (! empty($preflight['checks']))
                <div class="grid gap-1.5 md:grid-cols-2">
                    @foreach ($preflight['checks'] as $check)
                        <div class="flex items-start gap-2 text-sm">
                            <span class="mt-0.5">
                                @if ($check['status'] === 'pass') ✅
                                @elseif ($check['status'] === 'warn') ⚠️
                                @else ❌
                                @endif
                            </span>
                            <div>
                                <span class="font-medium">{{ $check['label'] }}</span>
                                <div class="text-xs text-gray-600 dark:text-gray-400">{{ $check['detail'] }}</div>
                                @if (! empty($check['fix']))
                                    <div class="text-xs mt-0.5 text-blue-700 dark:text-blue-300">
                                        → {{ $check['fix'] }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Operator-level kill switch notice. --}}
        @if (! $scheduleOn)
            <div class="p-3 border border-yellow-500 rounded text-sm bg-yellow-50 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200">
                <strong>Global scheduler is OFF.</strong>
                Posts can be created and scheduled, but nothing will publish automatically until an
                operator sets <code>AUTOPOSTER_SCHEDULE_ENABLED=true</code> in <code>.env</code>.
                Manual <em>Post Now</em> still works.
            </div>
        @endif

        {{-- ── Status tiles ───────────────────────────────────────────── --}}
        <div class="grid gap-3 grid-cols-2 md:grid-cols-4">
            @php($tiles = [
                ['Auto posting', $settings?->auto_posting_enabled ? 'ON' : 'OFF',
                    $settings?->auto_posting_enabled ? 'text-green-600' : 'text-gray-500'],
                ['Posts per day', $settings->posts_per_day ?? 5, ''],
                ['Published today', $stats['published_today'], ''],
                ['Failed', $stats['failed'], $stats['failed'] > 0 ? 'text-red-600' : ''],
            ])

            @foreach ($tiles as [$label, $value, $colour])
                <div class="border rounded p-3 dark:border-gray-700">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</div>
                    <div class="text-2xl font-bold {{ $colour }}">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            {{-- Connection summary --}}
            <div class="border rounded p-4 dark:border-gray-700 flex flex-col gap-2">
                <h2 class="font-semibold">Connection</h2>
                <dl class="text-sm grid grid-cols-3 gap-y-1.5">
                    <dt class="text-gray-500 dark:text-gray-400">Page</dt>
                    <dd class="col-span-2">{{ $catalog?->page_id ?: '—' }}</dd>

                    <dt class="text-gray-500 dark:text-gray-400">Catalog</dt>
                    <dd class="col-span-2">{{ $catalog?->catalog_id ?: '—' }}</dd>

                    <dt class="text-gray-500 dark:text-gray-400">Product set</dt>
                    <dd class="col-span-2">
                        @php($cfg = $settings?->selection_config ?? [])
                        @if (($settings?->selection_mode) === 'product_set' && ! empty($cfg['retailer_ids']))
                            {{ count($cfg['retailer_ids']) }} SKU(s)
                        @else
                            <span class="text-gray-500">Mode: {{ str_replace('_', ' ', $settings->selection_mode ?? 'random') }}</span>
                        @endif
                    </dd>

                    <dt class="text-gray-500 dark:text-gray-400">Messenger</dt>
                    <dd class="col-span-2 break-all">
                        {{ $settings?->messenger_destination
                           ?: ($catalog?->page_id ? 'https://m.me/' . $catalog->page_id : '—') }}
                    </dd>

                    <dt class="text-gray-500 dark:text-gray-400">Eligible</dt>
                    <dd class="col-span-2">{{ $eligibleCount }} product(s) ready to post</dd>
                </dl>
            </div>

            {{-- Timing --}}
            <div class="border rounded p-4 dark:border-gray-700 flex flex-col gap-2">
                <h2 class="font-semibold">Timing</h2>
                <dl class="text-sm grid grid-cols-3 gap-y-1.5">
                    <dt class="text-gray-500 dark:text-gray-400">Next post</dt>
                    <dd class="col-span-2">
                        @if ($nextPost)
                            <a href="{{ route('admin.autoposter.posts.edit', $nextPost->id) }}" class="text-blue-600">
                                {{ $nextPost->scheduled_at->format('Y-m-d H:i') }}
                                ({{ $nextPost->scheduled_at->diffForHumans() }})
                            </a>
                        @else
                            <span class="text-gray-500">Nothing scheduled</span>
                        @endif
                    </dd>

                    <dt class="text-gray-500 dark:text-gray-400">Last success</dt>
                    <dd class="col-span-2">
                        @if ($lastPublished)
                            {{ $lastPublished->published_at?->format('Y-m-d H:i') }}
                            @if ($lastPublished->facebook_permalink)
                                <a href="{{ $lastPublished->facebook_permalink }}" target="_blank" rel="noopener"
                                   class="text-blue-600">view →</a>
                            @endif
                        @else
                            <span class="text-gray-500">None yet</span>
                        @endif
                    </dd>

                    <dt class="text-gray-500 dark:text-gray-400">Window</dt>
                    <dd class="col-span-2">
                        @if (! empty($settings?->posting_times))
                            {{ implode(', ', $settings->posting_times) }}
                        @else
                            {{ substr($settings->posting_window_start ?? '09:00:00', 0, 5) }}–{{ substr($settings->posting_window_end ?? '21:30:00', 0, 5) }}
                            (auto)
                        @endif
                        <span class="text-gray-500">{{ $settings->timezone ?? 'Asia/Tokyo' }}</span>
                    </dd>
                </dl>

                <form method="POST" action="{{ route('admin.autoposter.plan') }}" class="mt-1">
                    @csrf
                    <input type="hidden" name="channel_id" value="{{ $channelId }}">
                    <button class="px-3 py-1.5 border rounded text-sm dark:border-gray-700">
                        Fill today's remaining slots
                    </button>
                </form>
            </div>
        </div>

        {{-- ── Queue by status ───────────────────────────────────────── --}}
        <div class="border rounded p-4 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                <h2 class="font-semibold">Queue</h2>
                <div class="flex gap-2">
                    <a href="{{ route('admin.autoposter.posts.create', ['channel_id' => $channelId]) }}"
                       class="px-4 py-2 bg-blue-600 text-white rounded text-sm">Manual Post</a>
                    <a href="{{ route('admin.autoposter.bulk', ['channel_id' => $channelId]) }}"
                       class="px-4 py-2 bg-indigo-600 text-white rounded text-sm">Bulk Post</a>
                </div>
            </div>

            <div class="grid gap-2 grid-cols-3 md:grid-cols-6">
                @foreach (['draft','scheduled','processing','published','failed','cancelled'] as $st)
                    <a href="{{ route('admin.autoposter.queue', ['channel_id' => $channelId, 'status' => $st]) }}"
                       class="border rounded p-2 text-center hover:bg-gray-50 dark:hover:bg-gray-800 dark:border-gray-700">
                        <div class="text-lg font-bold">{{ $stats[$st] }}</div>
                        <div class="text-xs text-gray-500 capitalize">{{ $st }}</div>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ── Recent posts ──────────────────────────────────────────── --}}
        @if ($recent->count())
            <div class="border rounded dark:border-gray-700 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr class="text-left">
                            <th class="p-2">Post</th>
                            <th class="p-2">Products</th>
                            <th class="p-2">Status</th>
                            <th class="p-2">When</th>
                            <th class="p-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recent as $post)
                            <tr class="border-t dark:border-gray-700">
                                <td class="p-2">#{{ $post->id }}
                                    <span class="text-xs text-gray-500">{{ $post->origin }}</span>
                                </td>
                                <td class="p-2">{{ $post->products->count() }}</td>
                                <td class="p-2">
                                    @include('autoposter::admin._status', ['status' => $post->status])
                                </td>
                                <td class="p-2 whitespace-nowrap">
                                    {{ optional($post->published_at ?? $post->scheduled_at)->format('m-d H:i') ?? '—' }}
                                </td>
                                <td class="p-2 text-right">
                                    <a href="{{ route('admin.autoposter.posts.edit', $post->id) }}"
                                       class="text-blue-600">view</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @endif
    </div>
</x-admin::layouts>

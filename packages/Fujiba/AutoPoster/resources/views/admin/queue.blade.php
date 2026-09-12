<x-admin::layouts>
    <x-slot:title>Auto Poster — Queue</x-slot:title>

    <div class="flex flex-col gap-4">
        <p class="text-xl text-gray-800 dark:text-white font-bold">Queue</p>

        @include('autoposter::admin._nav')
        @include('autoposter::admin._flash')

        <div class="flex flex-wrap gap-1">
            @php($all = array_sum($counts))
            <a href="{{ route('admin.autoposter.queue', ['channel_id' => $channelId, 'status' => 'all']) }}"
               class="px-3 py-1.5 rounded text-sm {{ $status === 'all' ? 'bg-gray-800 text-white dark:bg-gray-200 dark:text-gray-900' : 'border dark:border-gray-700' }}">
                All ({{ $all }})
            </a>
            @foreach (['draft','scheduled','processing','published','failed','cancelled'] as $st)
                <a href="{{ route('admin.autoposter.queue', ['channel_id' => $channelId, 'status' => $st]) }}"
                   class="px-3 py-1.5 rounded text-sm capitalize {{ $status === $st ? 'bg-gray-800 text-white dark:bg-gray-200 dark:text-gray-900' : 'border dark:border-gray-700' }}">
                    {{ $st }} ({{ $counts[$st] ?? 0 }})
                </a>
            @endforeach
        </div>

        @if ($posts->isEmpty())
            <div class="p-6 border rounded text-sm text-center text-gray-500 dark:border-gray-700">
                No posts{{ $status !== 'all' ? ' with status "' . $status . '"' : '' }} yet.
                <a href="{{ route('admin.autoposter.posts.create', ['channel_id' => $channelId]) }}"
                   class="text-blue-600">Create one →</a>
            </div>
        @else
            <div class="border rounded dark:border-gray-700 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr class="text-left">
                            <th class="p-2">Preview</th>
                            <th class="p-2">Post</th>
                            <th class="p-2">Caption</th>
                            <th class="p-2">Status</th>
                            <th class="p-2">Scheduled</th>
                            <th class="p-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($posts as $post)
                            <tr class="border-t dark:border-gray-700 align-top">
                                <td class="p-2">
                                    <div class="flex -space-x-2">
                                        @foreach ($post->products->take(3) as $item)
                                            @if ($item->effectiveImageUrl())
                                                <img src="{{ $item->effectiveImageUrl() }}" alt="" loading="lazy"
                                                     class="w-10 h-10 rounded object-cover border-2 border-white dark:border-gray-900">
                                            @endif
                                        @endforeach
                                        @if ($post->products->count() > 3)
                                            <span class="w-10 h-10 rounded bg-gray-100 dark:bg-gray-800 text-xs
                                                         flex items-center justify-center border-2 border-white dark:border-gray-900">
                                                +{{ $post->products->count() - 3 }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="p-2 whitespace-nowrap">
                                    #{{ $post->id }}
                                    <div class="text-xs text-gray-500">{{ $post->origin }} · {{ str_replace('_',' ',$post->format) }}</div>
                                </td>
                                <td class="p-2 max-w-xs">
                                    <div class="text-xs text-gray-600 dark:text-gray-300 line-clamp-2">
                                        {{ Str::limit($post->caption ?: '— no caption —', 110) }}
                                    </div>
                                </td>
                                <td class="p-2">
                                    @include('autoposter::admin._status', ['status' => $post->status])
                                    @if ($post->status === 'failed' && $post->attempts)
                                        <div class="text-xs text-red-600 mt-0.5">{{ $post->attempts }} attempt(s)</div>
                                    @endif
                                </td>
                                <td class="p-2 whitespace-nowrap text-xs">
                                    {{ $post->scheduled_at?->format('Y-m-d H:i') ?? '—' }}
                                </td>
                                <td class="p-2 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.autoposter.posts.edit', $post->id) }}"
                                       class="text-blue-600">{{ $post->isPublished() ? 'view' : 'edit' }}</a>

                                    @if ($post->status === 'failed')
                                        <form method="POST" action="{{ route('admin.autoposter.posts.retry', $post->id) }}" class="inline">
                                            @csrf
                                            <button class="text-orange-600 ml-2">retry</button>
                                        </form>
                                    @endif

                                    @if ($post->isCancellable())
                                        <form method="POST" action="{{ route('admin.autoposter.posts.cancel', $post->id) }}" class="inline"
                                              onsubmit="return confirm('Cancel this post?');">
                                            @csrf
                                            <button class="text-gray-600 dark:text-gray-400 ml-2">cancel</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div>{{ $posts->links() }}</div>
        @endif
    </div>
</x-admin::layouts>

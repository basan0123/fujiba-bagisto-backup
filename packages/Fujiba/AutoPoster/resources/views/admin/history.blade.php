<x-admin::layouts>
    <x-slot:title>Auto Poster — History</x-slot:title>

    <div class="flex flex-col gap-4">
        <p class="text-xl text-gray-800 dark:text-white font-bold">Post history</p>

        @include('autoposter::admin._nav')
        @include('autoposter::admin._flash')

        @if ($posts->isEmpty())
            <div class="p-6 border rounded text-sm text-center text-gray-500 dark:border-gray-700">
                Nothing published yet.
            </div>
        @else
            <div class="border rounded dark:border-gray-700 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr class="text-left">
                            <th class="p-2">Thumb</th>
                            <th class="p-2">Products</th>
                            <th class="p-2">Caption</th>
                            <th class="p-2">Facebook</th>
                            <th class="p-2">Published</th>
                            <th class="p-2">Format</th>
                            <th class="p-2">Status</th>
                            <th class="p-2">Messenger</th>
                            <th class="p-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($posts as $post)
                            <tr class="border-t dark:border-gray-700 align-top">
                                <td class="p-2">
                                    @php($first = $post->products->first())
                                    @if ($first?->effectiveImageUrl())
                                        <img src="{{ $first->effectiveImageUrl() }}" alt="" loading="lazy"
                                             class="w-12 h-12 rounded object-cover">
                                    @endif
                                </td>
                                <td class="p-2 text-xs">
                                    {{ $post->products->count() }}
                                    <div class="text-gray-500">#{{ $post->id }}</div>
                                </td>
                                <td class="p-2 max-w-xs">
                                    <div class="text-xs text-gray-600 dark:text-gray-300 line-clamp-2">
                                        {{ Str::limit($post->caption, 90) }}
                                    </div>
                                </td>
                                <td class="p-2 text-xs">
                                    @if ($post->facebook_post_id)
                                        <code class="text-xs">{{ Str::limit($post->facebook_post_id, 18) }}</code>
                                        @if ($post->facebook_permalink)
                                            <a href="{{ $post->facebook_permalink }}" target="_blank" rel="noopener"
                                               class="text-blue-600 block">open →</a>
                                        @endif
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="p-2 text-xs whitespace-nowrap">
                                    {{ $post->published_at?->format('Y-m-d H:i') ?? '—' }}
                                </td>
                                <td class="p-2 text-xs">{{ str_replace('_',' ',$post->format) }}</td>
                                <td class="p-2">
                                    @include('autoposter::admin._status', ['status' => $post->status])
                                    @if ($post->last_error)
                                        <div class="text-xs text-red-600 mt-0.5" title="{{ $post->last_error }}">
                                            {{ Str::limit($post->last_error, 40) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="p-2 text-xs break-all max-w-[10rem]">
                                    {{ $post->messenger_destination ?: '—' }}
                                </td>
                                <td class="p-2 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.autoposter.posts.edit', $post->id) }}" class="text-blue-600">view</a>
                                    <form method="POST" action="{{ route('admin.autoposter.posts.duplicate', $post->id) }}" class="inline">
                                        @csrf
                                        <button class="text-indigo-600 ml-2">post again</button>
                                    </form>
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

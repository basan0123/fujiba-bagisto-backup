<x-admin::layouts>
    <x-slot:title>Auto Poster — Post #{{ $post->id }}</x-slot:title>

    @php($channelId = $post->channel_id)
    @php($channels = collect())

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <p class="text-xl text-gray-800 dark:text-white font-bold">
                Post #{{ $post->id }}
                @include('autoposter::admin._status', ['status' => $post->status])
            </p>
            <a href="{{ route('admin.autoposter.queue', ['channel_id' => $post->channel_id]) }}"
               class="text-sm text-blue-600">← back to queue</a>
        </div>

        @include('autoposter::admin._flash')

        @if ($post->status === 'failed' && $post->last_error)
            <div class="p-3 border border-red-500 rounded text-sm bg-red-50 dark:bg-red-900/20">
                <div class="font-semibold text-red-800 dark:text-red-200">
                    Failed{{ $post->attempts ? ' after ' . $post->attempts . ' attempt(s)' : '' }}
                    @if ($post->last_error_at) · {{ $post->last_error_at->diffForHumans() }} @endif
                </div>
                <div class="mt-1 text-red-800 dark:text-red-200">{{ $post->last_error }}</div>
                @if ($post->last_api_response)
                    <details class="mt-2">
                        <summary class="cursor-pointer text-xs text-red-700 dark:text-red-300">
                            Raw Facebook response
                        </summary>
                        <pre class="mt-1 p-2 bg-white dark:bg-gray-900 rounded text-xs overflow-x-auto">{{ json_encode($post->last_api_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                @endif
                @if ($post->next_retry_at)
                    <div class="text-xs mt-1 text-red-700 dark:text-red-300">
                        Automatic retry {{ $post->next_retry_at->diffForHumans() }}.
                    </div>
                @endif
            </div>
        @endif

        @if ($post->isPublished())
            <div class="p-3 border border-green-500 rounded text-sm bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200">
                Published {{ $post->published_at?->format('Y-m-d H:i') }} ·
                Facebook post <code>{{ $post->facebook_post_id }}</code>
                @if ($post->facebook_permalink)
                    · <a href="{{ $post->facebook_permalink }}" target="_blank" rel="noopener" class="underline">open on Facebook →</a>
                @endif
                <div class="text-xs mt-1">This post is locked — published posts cannot be edited or re-sent.</div>
            </div>
        @endif

        <div class="grid gap-4 lg:grid-cols-2">
            {{-- ── Carousel preview + drag-to-reorder ─────────────────── --}}
            <div class="border rounded p-4 dark:border-gray-700 flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold">Carousel preview</h2>
                    <span class="text-xs text-gray-500">{{ count($products) }} product(s)</span>
                </div>

                @if (! $post->isPublished())
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Drag the cards to change the order customers see. Saved automatically.
                    </p>
                @endif

                <v-carousel-reorder
                    :items='@json($products)'
                    reorder-url="{{ route('admin.autoposter.posts.reorder', $post->id) }}"
                    :locked="{{ $post->isPublished() ? 'true' : 'false' }}"
                    csrf="{{ csrf_token() }}"
                ></v-carousel-reorder>

                @if (! $post->isPublished() && count($products) > 1)
                    <details class="text-xs">
                        <summary class="cursor-pointer text-gray-500">Remove a product</summary>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach ($products as $card)
                                <form method="POST" action="{{ route('admin.autoposter.posts.remove_product', $post->id) }}"
                                      onsubmit="return confirm('Remove {{ addslashes($card['name']) }} from this post?');">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $card['product_id'] }}">
                                    <button class="px-2 py-1 border rounded dark:border-gray-700">
                                        ✕ {{ Str::limit($card['name'], 20) }}
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </details>
                @endif
            </div>

            {{-- ── Caption + settings ─────────────────────────────────── --}}
            <div class="border rounded p-4 dark:border-gray-700 flex flex-col gap-3">
                <form method="POST" action="{{ route('admin.autoposter.posts.update', $post->id) }}" class="flex flex-col gap-3">
                    @csrf
                    @method('PUT')

                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold">Caption</h2>
                        @if (! $post->isPublished())
                            <button type="submit" form="regen-{{ $post->id }}"
                                    class="px-2 py-1 border rounded text-xs dark:border-gray-700">
                                ↻ Regenerate{{ $aiAvailable ? '' : ' from template' }}
                            </button>
                        @endif
                    </div>

                    <textarea name="caption" rows="12" @disabled($post->isPublished())
                              class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm font-mono
                                     disabled:opacity-60">{{ old('caption', $post->caption) }}</textarea>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        The Messenger link is appended automatically when the post is published, if it
                        is not already in the text.
                    </p>

                    {{-- Provenance: an admin reviewing a caption should be able to see whether
                         Claude wrote it or the template did — and, when AI was configured but
                         failed, why it silently fell back. --}}
                    @php($cmeta = $post->caption_meta ?? [])
                    @if (! empty($cmeta['source']))
                        <div class="text-xs flex flex-wrap items-center gap-2">
                            @if ($cmeta['source'] === 'ai')
                                <span class="px-2 py-0.5 rounded bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-200">
                                    AI-written
                                </span>
                                <span class="text-gray-500 dark:text-gray-400">{{ $cmeta['model'] ?? '' }}</span>
                            @else
                                <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    From template
                                </span>
                            @endif

                            @if (! empty($cmeta['ai_error']))
                                <span class="text-yellow-700 dark:text-yellow-300"
                                      title="{{ $cmeta['ai_error'] }}">
                                    ⚠️ AI generation failed — fell back to the template
                                </span>
                            @endif
                        </div>
                    @endif

                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="text-sm">
                            <span class="block mb-1">Format</span>
                            <select name="format" @disabled($post->isPublished())
                                    class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                                <option value="multi_photo" @selected($post->format === 'multi_photo')>Multi-photo carousel</option>
                                <option value="single_image" @selected($post->format === 'single_image')>Single image + link</option>
                            </select>
                        </label>

                        <label class="text-sm">
                            <span class="block mb-1">Template</span>
                            <select name="template_id" @disabled($post->isPublished())
                                    class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                                <option value="">— default —</option>
                                @foreach ($templates as $tpl)
                                    <option value="{{ $tpl->id }}" @selected($tpl->id == $post->template_id)>{{ $tpl->name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="text-sm md:col-span-2">
                            <span class="block mb-1">Messenger link</span>
                            <input type="url" name="messenger_destination" @disabled($post->isPublished())
                                   value="{{ old('messenger_destination', $post->messenger_destination) }}"
                                   class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                        </label>

                        <label class="text-sm md:col-span-2">
                            <span class="block mb-1">Scheduled for</span>
                            <input type="datetime-local" name="scheduled_at" @disabled($post->isPublished())
                                   value="{{ old('scheduled_at', $post->scheduled_at?->format('Y-m-d\TH:i')) }}"
                                   class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                        </label>
                    </div>

                    @unless ($post->isPublished())
                        <button class="px-4 py-2 bg-gray-700 text-white rounded text-sm">Save changes</button>
                    @endunless
                </form>

                <form method="POST" id="regen-{{ $post->id }}"
                      action="{{ route('admin.autoposter.posts.regenerate', $post->id) }}">@csrf</form>
            </div>
        </div>

        {{-- ── Actions ────────────────────────────────────────────────── --}}
        <div class="border rounded p-4 dark:border-gray-700 flex flex-wrap items-center gap-2">
            @unless ($post->isPublished())
                <form method="POST" action="{{ route('admin.autoposter.posts.publish', $post->id) }}"
                      onsubmit="return confirm('Publish this post to Facebook now?\n\nThis is immediate and public.');">
                    @csrf
                    <button class="px-5 py-2 bg-green-600 text-white rounded text-sm font-medium">Post Now</button>
                </form>

                @if ($post->status === 'failed')
                    <form method="POST" action="{{ route('admin.autoposter.posts.retry', $post->id) }}">
                        @csrf
                        <button class="px-4 py-2 bg-orange-500 text-white rounded text-sm">Retry</button>
                    </form>
                @endif

                @if ($post->isCancellable())
                    <form method="POST" action="{{ route('admin.autoposter.posts.cancel', $post->id) }}"
                          onsubmit="return confirm('Cancel this scheduled post?');">
                        @csrf
                        <button class="px-4 py-2 border rounded text-sm dark:border-gray-700">Cancel post</button>
                    </form>
                @endif
            @endunless

            <form method="POST" action="{{ route('admin.autoposter.posts.duplicate', $post->id) }}">
                @csrf
                <button class="px-4 py-2 border rounded text-sm dark:border-gray-700">
                    {{ $post->isPublished() ? 'Post again (new draft)' : 'Duplicate' }}
                </button>
            </form>

            @unless ($post->isPublished())
                <form method="POST" action="{{ route('admin.autoposter.posts.destroy', $post->id) }}" class="ml-auto"
                      onsubmit="return confirm('Delete this post permanently? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button class="px-4 py-2 text-red-600 border border-red-300 rounded text-sm">Delete</button>
                </form>
            @endunless
        </div>
    </div>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-carousel-reorder-template">
            <div>
                <div class="flex gap-3 overflow-x-auto pb-2">
                    <div v-for="(item, index) in list" :key="item.product_id"
                         :draggable="! locked"
                         @dragstart="onDragStart(index)"
                         @dragover.prevent="onDragOver(index)"
                         @dragend="onDragEnd"
                         class="shrink-0 w-36 border rounded overflow-hidden dark:border-gray-700 bg-white dark:bg-gray-900"
                         :class="[
                             locked ? '' : 'cursor-move',
                             dragIndex === index ? 'opacity-40' : ''
                         ]">
                        <div class="relative">
                            <img v-if="item.image" :src="item.image" alt="" loading="lazy"
                                 class="w-full aspect-square object-cover bg-gray-100 dark:bg-gray-800">
                            <div v-else
                                 class="w-full aspect-square bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-xs text-gray-400">
                                no image
                            </div>
                            <div class="absolute top-1 left-1 w-6 h-6 rounded-full bg-black/70 text-white
                                        text-xs flex items-center justify-center font-bold">
                                @{{ index + 1 }}
                            </div>
                        </div>
                        <div class="p-1.5">
                            <div class="text-xs truncate" :title="item.name">@{{ item.name }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="saving" class="text-xs text-gray-500 mt-1">Saving order…</div>
                <div v-if="savedAt" class="text-xs text-green-600 mt-1">Order saved.</div>
                <div v-if="error" class="text-xs text-red-600 mt-1">@{{ error }}</div>
            </div>
        </script>

        <script type="module">
            app.component('v-carousel-reorder', {
                template: '#v-carousel-reorder-template',

                props: {
                    items:      { type: Array,   default: () => [] },
                    reorderUrl: { type: String,  required: true },
                    locked:     { type: Boolean, default: false },
                    csrf:       { type: String,  required: true },
                },

                data() {
                    return {
                        list: [...this.items],
                        dragIndex: null,
                        saving: false,
                        savedAt: false,
                        error: null,
                    };
                },

                methods: {
                    onDragStart(index) {
                        if (this.locked) {
                            return;
                        }

                        this.dragIndex = index;
                    },

                    onDragOver(index) {
                        if (this.locked || this.dragIndex === null || this.dragIndex === index) {
                            return;
                        }

                        // Move the dragged card into the hovered slot so the
                        // preview reflects the final order while dragging.
                        const moved = this.list.splice(this.dragIndex, 1)[0];

                        this.list.splice(index, 0, moved);

                        this.dragIndex = index;
                    },

                    onDragEnd() {
                        if (this.locked || this.dragIndex === null) {
                            return;
                        }

                        this.dragIndex = null;

                        this.persist();
                    },

                    persist() {
                        this.saving  = true;
                        this.savedAt = false;
                        this.error   = null;

                        fetch(this.reorderUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrf,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                order: this.list.map(i => i.product_id),
                            }),
                        })
                        .then(res => {
                            if (! res.ok) {
                                throw new Error('Could not save the new order (HTTP ' + res.status + ').');
                            }

                            this.savedAt = true;

                            setTimeout(() => { this.savedAt = false; }, 2000);
                        })
                        .catch(err => { this.error = err.message; })
                        .finally(() => { this.saving = false; });
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>

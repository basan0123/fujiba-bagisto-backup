<x-admin::layouts>
    <x-slot:title>Auto Poster — Post #{{ $post->id }}</x-slot:title>

    @php($channelId = $post->channel_id)
    @php($channels = collect())
    @php($locked = $post->isPublished())
    @php($channel = \Webkul\Core\Models\Channel::find($post->channel_id))

    <div class="ap">
        @include('autoposter::admin._header', [
            'title'     => 'Post #' . $post->id . ' ' . view('autoposter::admin._status', ['status' => $post->status])->render(),
            'subtitle'  => ucfirst($post->origin) . ' · ' . str_replace('_', ' ', $post->format)
                           . ($post->scheduled_at ? ' · scheduled ' . $post->scheduled_at->format('Y-m-d H:i') : ''),
            'back'      => route('admin.autoposter.queue', ['channel_id' => $post->channel_id]),
            'backLabel' => 'Queue',
        ])

        @include('autoposter::admin._flash')

        @if ($post->status === 'failed' && $post->last_error)
            <div class="ap-alert ap-alert-danger">
                @include('autoposter::admin._icon', ['i' => 'alert'])
                <div class="ap-alert-body">
                    <strong>
                        Failed{{ $post->attempts ? ' after ' . $post->attempts . ' attempt(s)' : '' }}
                        @if ($post->last_error_at) · {{ $post->last_error_at->diffForHumans() }} @endif
                    </strong>
                    <div style="margin-top: 2px;">{{ $post->last_error }}</div>
                    @if ($post->next_retry_at)
                        <div class="ap-small" style="margin-top: 4px;">Automatic retry {{ $post->next_retry_at->diffForHumans() }}.</div>
                    @endif
                    @if ($post->last_api_response)
                        <details style="margin-top: 8px;">
                            <summary style="cursor: pointer;" class="ap-small">Raw Facebook response</summary>
                            <pre class="ap-mono" style="margin-top: 6px; padding: 10px; border-radius: 8px; background: var(--ap-surface); color: var(--ap-text); overflow-x: auto;">{{ json_encode($post->last_api_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </details>
                    @endif
                </div>
            </div>
        @endif

        @if ($locked)
            <div class="ap-alert ap-alert-success">
                @include('autoposter::admin._icon', ['i' => 'check'])
                <div class="ap-alert-body">
                    <strong>Published {{ $post->published_at?->format('Y-m-d H:i') }}</strong>
                    · Facebook post <code>{{ $post->facebook_post_id }}</code>
                    @if ($post->facebook_permalink)
                        · <a href="{{ $post->facebook_permalink }}" target="_blank" rel="noopener" style="text-decoration: underline;">Open on Facebook</a>
                    @endif
                    <div class="ap-small" style="margin-top: 2px;">This post is locked — published posts cannot be edited or re-sent.</div>
                </div>
            </div>
        @endif

        <div class="ap-grid ap-split-edit">
            {{-- ── Left: live preview + carousel order ─────────────────── --}}
            <div class="ap-grid">
                <div class="ap-card ap-rise">
                    <div class="ap-card-head">
                        <h2 class="ap-card-title">@include('autoposter::admin._icon', ['i' => 'eye']) Post preview</h2>
                    </div>

                    <v-fb-preview
                        :items='@json($products)'
                        page-name="{{ $channel?->name ?: config('app.name') }}"
                        page-avatar="{{ $channel?->logo_url }}"
                        caption-source="#ap-caption"
                        format-source="#ap-format"
                        format="{{ $post->format }}"
                        messenger="{{ $post->messenger_destination }}"
                        when="{{ $locked ? $post->published_at?->diffForHumans() : ($post->scheduled_at ? $post->scheduled_at->format('M j \\a\\t H:i') : 'Just now') }}"
                    ></v-fb-preview>
                </div>

                <div class="ap-card ap-rise">
                    <div class="ap-card-head">
                        <h2 class="ap-card-title">@include('autoposter::admin._icon', ['i' => 'image']) Carousel order</h2>
                        <span class="ap-badge ap-tone-blue no-dot">{{ count($products) }} products</span>
                    </div>
                    @unless ($locked)
                        <p class="ap-card-desc">Drag cards to reorder — saved automatically. Hover a card to remove it.</p>
                    @endunless

                    <v-carousel-reorder
                        :items='@json($products)'
                        reorder-url="{{ route('admin.autoposter.posts.reorder', $post->id) }}"
                        remove-url="{{ route('admin.autoposter.posts.remove_product', $post->id) }}"
                        :locked="{{ $locked ? 'true' : 'false' }}"
                        csrf="{{ csrf_token() }}"
                    ></v-carousel-reorder>
                </div>
            </div>

            {{-- ── Right: caption + settings ─────────────────────────── --}}
            <form method="POST" action="{{ route('admin.autoposter.posts.update', $post->id) }}" id="ap-edit-form" class="ap-card ap-rise">
                @csrf
                @method('PUT')

                <div class="ap-card-head">
                    <h2 class="ap-card-title">@include('autoposter::admin._icon', ['i' => 'edit']) Caption</h2>
                    @unless ($locked)
                        <button type="submit" form="regen-{{ $post->id }}" class="ap-btn ap-btn-secondary ap-btn-sm">
                            @include('autoposter::admin._icon', ['i' => 'sparkles'])
                            {{ $aiAvailable ? 'Regenerate with AI' : 'Regenerate from template' }}
                        </button>
                    @endunless
                </div>

                <div class="ap-field">
                    <textarea name="caption" id="ap-caption" rows="13" @disabled($locked)
                              oninput="var c=document.getElementById('ap-caption-count'); c.textContent=this.value.length.toLocaleString()+' / 63,206'; c.classList.toggle('is-over', this.value.length>63206);">{{ old('caption', $post->caption) }}</textarea>
                    <div style="display: flex; justify-content: space-between; gap: 10px; align-items: flex-start;">
                        <span class="ap-hint">The Messenger link is appended automatically on publish if it isn't already in the text.</span>
                        <span id="ap-caption-count" class="ap-counter">{{ number_format(mb_strlen((string) $post->caption)) }} / 63,206</span>
                    </div>
                </div>

                {{-- Provenance: whether Claude or the template wrote the caption,
                     and why AI silently fell back when it failed. --}}
                @php($cmeta = $post->caption_meta ?? [])
                @if (! empty($cmeta['source']))
                    <div class="ap-actions" style="margin-top: 12px; gap: 8px;">
                        @if ($cmeta['source'] === 'ai')
                            <span class="ap-badge ap-tone-violet">AI-written</span>
                            <span class="ap-muted ap-small">{{ $cmeta['model'] ?? '' }}</span>
                        @else
                            <span class="ap-badge ap-tone-gray">From template</span>
                        @endif

                        @if (! empty($cmeta['ai_error']))
                            <span class="ap-badge ap-tone-amber no-dot" title="{{ $cmeta['ai_error'] }}">AI failed — used template</span>
                        @endif
                    </div>
                @endif

                <div class="ap-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-top: 18px;">
                    <label class="ap-field">
                        <span class="ap-label">Format</span>
                        <select name="format" id="ap-format" @disabled($locked)>
                            <option value="multi_photo" @selected($post->format === 'multi_photo')>Multi-photo carousel</option>
                            <option value="single_image" @selected($post->format === 'single_image')>Single image + link</option>
                        </select>
                    </label>

                    <label class="ap-field">
                        <span class="ap-label">Template</span>
                        <select name="template_id" @disabled($locked)>
                            <option value="">— default —</option>
                            @foreach ($templates as $tpl)
                                <option value="{{ $tpl->id }}" @selected($tpl->id == $post->template_id)>{{ $tpl->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <label class="ap-field" style="margin-top: 14px;">
                    <span class="ap-label">Messenger link</span>
                    <input type="url" name="messenger_destination" @disabled($locked) placeholder="https://m.me/…"
                           value="{{ old('messenger_destination', $post->messenger_destination) }}">
                </label>

                <label class="ap-field" style="margin-top: 14px;">
                    <span class="ap-label">Scheduled for</span>
                    <input type="datetime-local" name="scheduled_at" @disabled($locked)
                           value="{{ old('scheduled_at', $post->scheduled_at?->format('Y-m-d\TH:i')) }}">
                </label>
            </form>

            <form method="POST" id="regen-{{ $post->id }}" action="{{ route('admin.autoposter.posts.regenerate', $post->id) }}">@csrf</form>
        </div>

        {{-- ── Sticky actions ────────────────────────────────────────── --}}
        <div class="ap-actionbar">
            @unless ($locked)
                <button type="submit" form="ap-edit-form" class="ap-btn ap-btn-secondary">
                    @include('autoposter::admin._icon', ['i' => 'check']) Save changes
                </button>
            @endunless

            <form method="POST" action="{{ route('admin.autoposter.posts.duplicate', $post->id) }}">
                @csrf
                <button class="ap-btn ap-btn-ghost">
                    @include('autoposter::admin._icon', ['i' => 'copy']) {{ $locked ? 'Post again' : 'Duplicate' }}
                </button>
            </form>

            @unless ($locked)
                @if ($post->isCancellable())
                    <form method="POST" action="{{ route('admin.autoposter.posts.cancel', $post->id) }}"
                          onsubmit="return confirm('Cancel this scheduled post?');">
                        @csrf
                        <button class="ap-btn ap-btn-ghost">@include('autoposter::admin._icon', ['i' => 'x']) Cancel post</button>
                    </form>
                @endif

                <form method="POST" action="{{ route('admin.autoposter.posts.destroy', $post->id) }}"
                      onsubmit="return confirm('Delete this post permanently? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button class="ap-btn ap-btn-danger">@include('autoposter::admin._icon', ['i' => 'trash']) Delete</button>
                </form>

                <span class="ap-spacer"></span>

                @if ($post->status === 'failed')
                    <form method="POST" action="{{ route('admin.autoposter.posts.retry', $post->id) }}">
                        @csrf
                        <button class="ap-btn ap-btn-warn">@include('autoposter::admin._icon', ['i' => 'refresh']) Retry</button>
                    </form>
                @endif

                <form method="POST" action="{{ route('admin.autoposter.posts.publish', $post->id) }}"
                      onsubmit="return confirm('Publish this post to Facebook now?\n\nThis is immediate and public.');">
                    @csrf
                    <button class="ap-btn ap-btn-primary ap-btn-lg">@include('autoposter::admin._icon', ['i' => 'send']) Post now</button>
                </form>
            @else
                <span class="ap-spacer"></span>
                @if ($post->facebook_permalink)
                    <a href="{{ $post->facebook_permalink }}" target="_blank" rel="noopener" class="ap-btn ap-btn-primary">
                        @include('autoposter::admin._icon', ['i' => 'external']) View on Facebook
                    </a>
                @endif
            @endunless
        </div>
    </div>

    @include('autoposter::admin._fb_preview')

    @pushOnce('scripts')
        <script type="text/x-template" id="v-carousel-reorder-template">
            <div>
                <div class="ap-reorder">
                    <div v-for="(item, index) in list" :key="item.product_id"
                         :draggable="! locked"
                         @dragstart="onDragStart(index)"
                         @dragover.prevent="onDragOver(index)"
                         @dragend="onDragEnd"
                         class="ap-reorder-item"
                         :class="{ 'is-draggable': ! locked, 'is-dragging': dragIndex === index }">
                        <span class="ap-pcard-num">@{{ index + 1 }}</span>
                        <button v-if="! locked && list.length > 1" type="button" class="ap-reorder-x"
                                :title="'Remove ' + item.name" @click.stop="remove(item)">&times;</button>
                        <img v-if="item.image" :src="item.image" alt="" loading="lazy" draggable="false">
                        <div v-else class="ap-pcard-noimg">No image</div>
                        <div class="ap-reorder-name" :title="item.name">@{{ item.name }}</div>
                    </div>
                </div>

                <div style="min-height: 18px; margin-top: 6px;">
                    <span v-if="saving" class="ap-save-state ap-muted">Saving order…</span>
                    <span v-else-if="savedAt" class="ap-save-state" style="color: var(--ap-success);">✓ Order saved</span>
                    <span v-else-if="error" class="ap-save-state" style="color: var(--ap-danger);">@{{ error }}</span>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-carousel-reorder', {
                template: '#v-carousel-reorder-template',

                props: {
                    items:      { type: Array,   default: () => [] },
                    reorderUrl: { type: String,  required: true },
                    removeUrl:  { type: String,  default: '' },
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
                    // Keep the live Facebook preview in step with the order.
                    broadcast() {
                        window.dispatchEvent(new CustomEvent('ap:items', { detail: this.list }));
                    },

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

                        this.broadcast();
                    },

                    onDragEnd() {
                        if (this.locked || this.dragIndex === null) {
                            return;
                        }

                        this.dragIndex = null;

                        this.persist();
                    },

                    // Removal goes through the existing form endpoint (it
                    // redirects back with a flash), so post a real form.
                    remove(item) {
                        if (! this.removeUrl || ! confirm('Remove ' + item.name + ' from this post?')) {
                            return;
                        }

                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = this.removeUrl;
                        form.innerHTML = '<input type="hidden" name="_token" value="' + this.csrf + '">'
                            + '<input type="hidden" name="product_id" value="' + item.product_id + '">';
                        document.body.appendChild(form);
                        form.submit();
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

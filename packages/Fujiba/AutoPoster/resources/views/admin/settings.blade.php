<x-admin::layouts>
    <x-slot:title>Auto Poster — Settings</x-slot:title>

    <div class="ap">
        @include('autoposter::admin._header', ['title' => 'Settings', 'subtitle' => 'Schedule, product selection, captions and Messenger routing.'])

        @include('autoposter::admin._nav')
        @include('autoposter::admin._flash')

        @if (! $channelId)
            <div class="ap-card ap-empty">No channels configured.</div>
        @else

        <form method="POST" action="{{ route('admin.autoposter.settings.save') }}" class="ap-grid">
            @csrf
            <input type="hidden" name="channel_id" value="{{ $channelId }}">

            {{-- ── Facebook connection (read-only mirror) ──────────────── --}}
            <div class="ap-card ap-rise">
                <h2 class="ap-card-title" style="margin-bottom: 12px;">Facebook connection</h2>
                <p class="ap-card-desc" style="margin-top: 0;">
                    Page, Catalog and access token are owned by the
                    <a href="{{ route('admin.facebook_catalog.index') }}" class="ap-link">Facebook Catalog</a>
                    module and shown here read-only. Auto Poster reuses that one connection so a token
                    rotation never has to be repeated in two places.
                </p>
                <dl class="ap-dl">
                    <dt>Page ID</dt>
                    <dd>{{ $catalog?->page_id ?: '— not set —' }}</dd>
                    <dt>Catalog ID</dt>
                    <dd>{{ $catalog?->catalog_id ?: '— not set —' }}</dd>
                    <dt>Business ID</dt>
                    <dd>{{ $catalog?->business_id ?: '—' }}</dd>
                    <dt>ChatPion</dt>
                    <dd>
                        @if ($livechat['auto_flow'])
                            <span class="ap-badge ap-tone-green">Auto-flow greeting active</span>
                        @elseif ($livechat['configured'])
                            <span class="ap-badge ap-tone-amber">LiveChat configured, auto-flow greeting off</span>
                        @else
                            <span class="ap-muted">Not configured</span>
                        @endif
                    </dd>
                </dl>
            </div>

            {{-- ── Schedule ────────────────────────────────────────────── --}}
            <div class="ap-card ap-rise ap-grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
                <h2 class="ap-card-title" style="grid-column: 1 / -1;">Schedule</h2>

                <label class="ap-field">
                    <span class="ap-label">Posts per day</span>
                    <input type="number" name="posts_per_day" min="1" max="20"
                           value="{{ old('posts_per_day', $settings->posts_per_day) }}"
                           >
                </label>

                <label class="ap-field">
                    <span class="ap-label">Timezone</span>
                    <select name="timezone">
                        @foreach (['Asia/Tokyo','Asia/Yangon','UTC','Asia/Bangkok','Asia/Singapore'] as $tz)
                            <option value="{{ $tz }}" @selected(old('timezone', $settings->timezone) === $tz)>{{ $tz }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="ap-field">
                    <span class="ap-label">Window start</span>
                    <input type="time" name="posting_window_start"
                           value="{{ old('posting_window_start', substr($settings->posting_window_start ?? '09:00:00', 0, 5)) }}"
                           >
                </label>

                <label class="ap-field">
                    <span class="ap-label">Window end</span>
                    <input type="time" name="posting_window_end"
                           value="{{ old('posting_window_end', substr($settings->posting_window_end ?? '21:30:00', 0, 5)) }}"
                           >
                </label>

                <label class="ap-field" style="grid-column: 1 / -1;">
                    <span class="ap-label">Exact posting times <span class="ap-muted">(optional)</span></span>
                    <input type="text" name="posting_times" placeholder="09:00, 12:30, 16:00, 19:00, 21:30"
                           value="{{ old('posting_times', implode(', ', $settings->posting_times ?? [])) }}"
                           >
                    <span class="ap-hint">
                        Leave empty to auto-distribute evenly across the window (avoids clustering).
                        When set, these times win over posts-per-day.
                    </span>
                </label>
            </div>

            {{-- ── Product selection ───────────────────────────────────── --}}
            <div class="ap-card ap-rise ap-grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
                <h2 class="ap-card-title" style="grid-column: 1 / -1;">Product selection</h2>

                <label class="ap-field">
                    <span class="ap-label">Mode</span>
                    <select name="selection_mode">
                        @foreach ([
                            'random' => 'Random',
                            'new_arrivals' => 'New arrivals',
                            'category' => 'Selected category',
                            'product_set' => 'Selected product set',
                            'never_posted' => 'Never posted',
                            'least_recently_posted' => 'Least recently posted',
                            'manual' => 'Manual select only',
                        ] as $val => $label)
                            <option value="{{ $val }}" @selected(old('selection_mode', $settings->selection_mode) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="ap-field">
                    <span class="ap-label">Products per post</span>
                    <input type="number" name="products_per_post" min="1" max="10"
                           value="{{ old('products_per_post', $settings->products_per_post) }}"
                           >
                </label>

                <label class="ap-field">
                    <span class="ap-label">Repeat cooldown (days)</span>
                    <input type="number" name="repeat_cooldown_days" min="0" max="365"
                           value="{{ old('repeat_cooldown_days', $settings->repeat_cooldown_days) }}"
                           >
                    <span class="ap-hint">
                        A product will not be posted again for this many days. 0 disables the cooldown.
                    </span>
                </label>

                <label class="ap-field">
                    <span class="ap-label">New-arrival window (days)</span>
                    <input type="number" name="new_arrival_days" min="1" max="365"
                           value="{{ old('new_arrival_days', $settings->selection_config['new_arrival_days'] ?? 30) }}"
                           >
                    <span class="ap-hint">Used by the "New arrivals" mode.</span>
                </label>

                <label class="ap-field" style="grid-column: 1 / -1;">
                    <span class="ap-label">Categories <span class="ap-muted">(for "Selected category")</span></span>
                    <select name="category_ids[]" multiple size="5">
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}"
                                @selected(in_array($cat->id, $settings->selection_config['category_ids'] ?? []))>
                                {{ $cat->name ?? ('Category ' . $cat->id) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="ap-field" style="grid-column: 1 / -1;">
                    <span class="ap-label">Product set SKUs <span class="ap-muted">(for "Selected product set")</span></span>
                    <textarea name="retailer_ids" rows="3" placeholder="SKU-001, SKU-002">{{ old('retailer_ids', implode(', ', $settings->selection_config['retailer_ids'] ?? [])) }}</textarea>
                    <span class="ap-hint">
                        Comma or newline separated. These are Facebook retailer IDs, which for this catalog are Bagisto SKUs.
                    </span>
                </label>
            </div>

            {{-- ── Post format & caption ───────────────────────────────── --}}
            <div class="ap-card ap-rise ap-grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
                <h2 class="ap-card-title" style="grid-column: 1 / -1;">Post format &amp; caption</h2>

                <label class="ap-field">
                    <span class="ap-label">Default format</span>
                    <select name="default_format">
                        <option value="multi_photo" @selected(old('default_format', $settings->default_format) === 'multi_photo')>
                            Multi-photo carousel (recommended)
                        </option>
                        <option value="single_image" @selected(old('default_format', $settings->default_format) === 'single_image')>
                            Single image + link
                        </option>
                    </select>
                    <span class="ap-hint">
                        Organic Page posts cannot carry a true per-card catalog carousel — that is a paid ad
                        format. Multi-photo plus the Messenger link is the organic equivalent.
                    </span>
                </label>

                <label class="ap-field">
                    <span class="ap-label">Default language</span>
                    <select name="default_language">
                        @foreach ([
                            'en' => 'English', 'ja' => '日本語', 'my' => 'မြန်မာ',
                            'en_ja' => 'EN + JA', 'en_my' => 'EN + MY', 'en_ja_my' => 'EN + JA + MY',
                        ] as $val => $label)
                            <option value="{{ $val }}" @selected(old('default_language', $settings->default_language) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="ap-field" style="grid-column: 1 / -1;">
                    <span class="ap-label">Default caption template</span>
                    <select name="default_template_id">
                        <option value="">— first active template —</option>
                        @foreach ($templates as $tpl)
                            <option value="{{ $tpl->id }}" @selected(old('default_template_id', $settings->default_template_id) == $tpl->id)>
                                {{ $tpl->name }} ({{ $tpl->language_mode }}){{ $tpl->is_default ? ' — default' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @if ($templates->isEmpty())
                        <span class="text-xs text-red-600">
                            No templates yet — create one on the
                            <a href="{{ route('admin.autoposter.templates', ['channel_id' => $channelId]) }}" class="underline">Templates</a> page.
                        </span>
                    @endif
                </label>
            </div>

            {{-- ── Messenger destination ───────────────────────────────── --}}
            <div class="ap-card ap-rise ap-grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
                <h2 class="ap-card-title" style="grid-column: 1 / -1;">Messenger destination</h2>
                <p class="text-xs ap-muted md:col-span-2">
                    Every post drives customers into Messenger, where the existing ChatPion flow greets them.
                    Leave the link empty to derive <code>https://m.me/&lt;page-id&gt;</code> from the Facebook Catalog page ID.
                </p>

                <label class="ap-field">
                    <span class="ap-label">Messenger link (override)</span>
                    <input type="url" name="messenger_destination" placeholder="https://m.me/100083296150785"
                           value="{{ old('messenger_destination', $settings->messenger_destination) }}"
                           >
                </label>

                <label class="ap-field">
                    <span class="ap-label">Referral tag <span class="ap-muted">(optional)</span></span>
                    <input type="text" name="messenger_ref" maxlength="100" placeholder="autopost"
                           value="{{ old('messenger_ref', $settings->messenger_ref) }}"
                           >
                    <span class="ap-hint">
                        Appended as <code>?ref=</code> for attribution. Note: the current ChatPion greeting
                        does not branch on this value — it is recorded for reporting only.
                    </span>
                </label>
            </div>

            {{-- ── Retries ─────────────────────────────────────────────── --}}
            <div class="ap-card ap-rise ap-grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
                <h2 class="ap-card-title" style="grid-column: 1 / -1;">Failure handling</h2>

                <label class="ap-check" style="grid-column: 1 / -1; justify-self: start;">
                    <input type="checkbox" name="auto_retry_enabled" value="1"
                           @checked(old('auto_retry_enabled', $settings->auto_retry_enabled))>
                    Automatically retry failed posts (exponential backoff)
                </label>

                <label class="ap-field">
                    <span class="ap-label">Max retries</span>
                    <input type="number" name="max_retries" min="0" max="10"
                           value="{{ old('max_retries', $settings->max_retries) }}"
                           >
                </label>
            </div>

            {{-- ── Switches (read-only here) ───────────────────────────── --}}
            <div class="ap-card ap-rise">
                <h2 class="ap-card-title" style="margin-bottom: 12px;">Auto posting switches</h2>
                <div class="text-sm flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <span class="ap-badge {{ $settings->auto_posting_enabled ? 'ap-tone-green' : 'ap-tone-gray' }}">
                            {{ $settings->auto_posting_enabled ? 'ON' : 'OFF' }}
                        </span>
                        <span>Per-channel auto posting —
                            <a href="{{ route('admin.autoposter.index', ['channel_id' => $channelId]) }}"
                               class="ap-link">toggle on the dashboard</a>
                            (turning it on requires pre-flight to pass).
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="ap-badge {{ $scheduleOn ? 'ap-tone-green' : 'ap-tone-gray' }}">
                            {{ $scheduleOn ? 'ON' : 'OFF' }}
                        </span>
                        <span>Global scheduler — set <code>AUTOPOSTER_SCHEDULE_ENABLED</code> in <code>.env</code>
                            (operator only, applies to every tenant).</span>
                    </div>
                </div>
            </div>

            <div class="ap-actionbar">
                <span class="ap-muted ap-small">Changes apply to this channel only.</span>
                <span class="ap-spacer"></span>
                <button class="ap-btn ap-btn-primary ap-btn-lg">@include('autoposter::admin._icon', ['i' => 'check']) Save settings</button>
            </div>
        </form>

        @endif
    </div>
</x-admin::layouts>

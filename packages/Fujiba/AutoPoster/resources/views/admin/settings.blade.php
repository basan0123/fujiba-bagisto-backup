<x-admin::layouts>
    <x-slot:title>Auto Poster — Settings</x-slot:title>

    <div class="flex flex-col gap-4">
        <p class="text-xl text-gray-800 dark:text-white font-bold">Auto Poster — Settings</p>

        @include('autoposter::admin._nav')
        @include('autoposter::admin._flash')

        @if (! $channelId)
            <div class="p-4 border rounded text-sm">No channels configured.</div>
        @else

        <form method="POST" action="{{ route('admin.autoposter.settings.save') }}" class="flex flex-col gap-4">
            @csrf
            <input type="hidden" name="channel_id" value="{{ $channelId }}">

            {{-- ── Facebook connection (read-only mirror) ──────────────── --}}
            <div class="border rounded p-4 dark:border-gray-700">
                <h2 class="font-semibold mb-1">Facebook connection</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                    Page, Catalog and access token are owned by the
                    <a href="{{ route('admin.facebook_catalog.index') }}" class="text-blue-600">Facebook Catalog</a>
                    module and shown here read-only. Auto Poster reuses that one connection so a token
                    rotation never has to be repeated in two places.
                </p>
                <dl class="text-sm grid grid-cols-4 gap-y-2">
                    <dt class="text-gray-500 dark:text-gray-400">Page ID</dt>
                    <dd class="col-span-3">{{ $catalog?->page_id ?: '— not set —' }}</dd>
                    <dt class="text-gray-500 dark:text-gray-400">Catalog ID</dt>
                    <dd class="col-span-3">{{ $catalog?->catalog_id ?: '— not set —' }}</dd>
                    <dt class="text-gray-500 dark:text-gray-400">Business ID</dt>
                    <dd class="col-span-3">{{ $catalog?->business_id ?: '—' }}</dd>
                    <dt class="text-gray-500 dark:text-gray-400">ChatPion</dt>
                    <dd class="col-span-3">
                        @if ($livechat['auto_flow'])
                            <span class="text-green-600">Auto-flow greeting active</span>
                        @elseif ($livechat['configured'])
                            <span class="text-yellow-600">LiveChat configured, auto-flow greeting off</span>
                        @else
                            <span class="text-gray-500">Not configured</span>
                        @endif
                    </dd>
                </dl>
            </div>

            {{-- ── Schedule ────────────────────────────────────────────── --}}
            <div class="border rounded p-4 dark:border-gray-700 grid gap-4 md:grid-cols-2">
                <h2 class="font-semibold md:col-span-2">Schedule</h2>

                <label class="text-sm">
                    <span class="block mb-1">Posts per day</span>
                    <input type="number" name="posts_per_day" min="1" max="20"
                           value="{{ old('posts_per_day', $settings->posts_per_day) }}"
                           class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                </label>

                <label class="text-sm">
                    <span class="block mb-1">Timezone</span>
                    <select name="timezone" class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                        @foreach (['Asia/Tokyo','Asia/Yangon','UTC','Asia/Bangkok','Asia/Singapore'] as $tz)
                            <option value="{{ $tz }}" @selected(old('timezone', $settings->timezone) === $tz)>{{ $tz }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="text-sm">
                    <span class="block mb-1">Window start</span>
                    <input type="time" name="posting_window_start"
                           value="{{ old('posting_window_start', substr($settings->posting_window_start ?? '09:00:00', 0, 5)) }}"
                           class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                </label>

                <label class="text-sm">
                    <span class="block mb-1">Window end</span>
                    <input type="time" name="posting_window_end"
                           value="{{ old('posting_window_end', substr($settings->posting_window_end ?? '21:30:00', 0, 5)) }}"
                           class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                </label>

                <label class="text-sm md:col-span-2">
                    <span class="block mb-1">Exact posting times <span class="text-gray-500">(optional)</span></span>
                    <input type="text" name="posting_times" placeholder="09:00, 12:30, 16:00, 19:00, 21:30"
                           value="{{ old('posting_times', implode(', ', $settings->posting_times ?? [])) }}"
                           class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Leave empty to auto-distribute evenly across the window (avoids clustering).
                        When set, these times win over posts-per-day.
                    </span>
                </label>
            </div>

            {{-- ── Product selection ───────────────────────────────────── --}}
            <div class="border rounded p-4 dark:border-gray-700 grid gap-4 md:grid-cols-2">
                <h2 class="font-semibold md:col-span-2">Product selection</h2>

                <label class="text-sm">
                    <span class="block mb-1">Mode</span>
                    <select name="selection_mode" class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
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

                <label class="text-sm">
                    <span class="block mb-1">Products per post</span>
                    <input type="number" name="products_per_post" min="1" max="10"
                           value="{{ old('products_per_post', $settings->products_per_post) }}"
                           class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                </label>

                <label class="text-sm">
                    <span class="block mb-1">Repeat cooldown (days)</span>
                    <input type="number" name="repeat_cooldown_days" min="0" max="365"
                           value="{{ old('repeat_cooldown_days', $settings->repeat_cooldown_days) }}"
                           class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        A product will not be posted again for this many days. 0 disables the cooldown.
                    </span>
                </label>

                <label class="text-sm">
                    <span class="block mb-1">New-arrival window (days)</span>
                    <input type="number" name="new_arrival_days" min="1" max="365"
                           value="{{ old('new_arrival_days', $settings->selection_config['new_arrival_days'] ?? 30) }}"
                           class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Used by the "New arrivals" mode.</span>
                </label>

                <label class="text-sm md:col-span-2">
                    <span class="block mb-1">Categories <span class="text-gray-500">(for "Selected category")</span></span>
                    <select name="category_ids[]" multiple size="5"
                            class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}"
                                @selected(in_array($cat->id, $settings->selection_config['category_ids'] ?? []))>
                                {{ $cat->name ?? ('Category ' . $cat->id) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="text-sm md:col-span-2">
                    <span class="block mb-1">Product set SKUs <span class="text-gray-500">(for "Selected product set")</span></span>
                    <textarea name="retailer_ids" rows="3" placeholder="SKU-001, SKU-002"
                              class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">{{ old('retailer_ids', implode(', ', $settings->selection_config['retailer_ids'] ?? [])) }}</textarea>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Comma or newline separated. These are Facebook retailer IDs, which for this catalog are Bagisto SKUs.
                    </span>
                </label>
            </div>

            {{-- ── Post format & caption ───────────────────────────────── --}}
            <div class="border rounded p-4 dark:border-gray-700 grid gap-4 md:grid-cols-2">
                <h2 class="font-semibold md:col-span-2">Post format &amp; caption</h2>

                <label class="text-sm">
                    <span class="block mb-1">Default format</span>
                    <select name="default_format" class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                        <option value="multi_photo" @selected(old('default_format', $settings->default_format) === 'multi_photo')>
                            Multi-photo carousel (recommended)
                        </option>
                        <option value="single_image" @selected(old('default_format', $settings->default_format) === 'single_image')>
                            Single image + link
                        </option>
                    </select>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Organic Page posts cannot carry a true per-card catalog carousel — that is a paid ad
                        format. Multi-photo plus the Messenger link is the organic equivalent.
                    </span>
                </label>

                <label class="text-sm">
                    <span class="block mb-1">Default language</span>
                    <select name="default_language" class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                        @foreach ([
                            'en' => 'English', 'ja' => '日本語', 'my' => 'မြန်မာ',
                            'en_ja' => 'EN + JA', 'en_my' => 'EN + MY', 'en_ja_my' => 'EN + JA + MY',
                        ] as $val => $label)
                            <option value="{{ $val }}" @selected(old('default_language', $settings->default_language) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="text-sm md:col-span-2">
                    <span class="block mb-1">Default caption template</span>
                    <select name="default_template_id" class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
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
            <div class="border rounded p-4 dark:border-gray-700 grid gap-4 md:grid-cols-2">
                <h2 class="font-semibold md:col-span-2">Messenger destination</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 md:col-span-2">
                    Every post drives customers into Messenger, where the existing ChatPion flow greets them.
                    Leave the link empty to derive <code>https://m.me/&lt;page-id&gt;</code> from the Facebook Catalog page ID.
                </p>

                <label class="text-sm">
                    <span class="block mb-1">Messenger link (override)</span>
                    <input type="url" name="messenger_destination" placeholder="https://m.me/100083296150785"
                           value="{{ old('messenger_destination', $settings->messenger_destination) }}"
                           class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                </label>

                <label class="text-sm">
                    <span class="block mb-1">Referral tag <span class="text-gray-500">(optional)</span></span>
                    <input type="text" name="messenger_ref" maxlength="100" placeholder="autopost"
                           value="{{ old('messenger_ref', $settings->messenger_ref) }}"
                           class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Appended as <code>?ref=</code> for attribution. Note: the current ChatPion greeting
                        does not branch on this value — it is recorded for reporting only.
                    </span>
                </label>
            </div>

            {{-- ── Retries ─────────────────────────────────────────────── --}}
            <div class="border rounded p-4 dark:border-gray-700 grid gap-4 md:grid-cols-2">
                <h2 class="font-semibold md:col-span-2">Failure handling</h2>

                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="auto_retry_enabled" value="1"
                           @checked(old('auto_retry_enabled', $settings->auto_retry_enabled))>
                    Automatically retry failed posts (exponential backoff)
                </label>

                <label class="text-sm">
                    <span class="block mb-1">Max retries</span>
                    <input type="number" name="max_retries" min="0" max="10"
                           value="{{ old('max_retries', $settings->max_retries) }}"
                           class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                </label>
            </div>

            {{-- ── Switches (read-only here) ───────────────────────────── --}}
            <div class="border rounded p-4 dark:border-gray-700">
                <h2 class="font-semibold mb-2">Auto posting switches</h2>
                <div class="text-sm flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-xs
                            {{ $settings->auto_posting_enabled
                               ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-200'
                               : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $settings->auto_posting_enabled ? 'ON' : 'OFF' }}
                        </span>
                        <span>Per-channel auto posting —
                            <a href="{{ route('admin.autoposter.index', ['channel_id' => $channelId]) }}"
                               class="text-blue-600">toggle on the dashboard</a>
                            (turning it on requires pre-flight to pass).
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-xs
                            {{ $scheduleOn
                               ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-200'
                               : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $scheduleOn ? 'ON' : 'OFF' }}
                        </span>
                        <span>Global scheduler — set <code>AUTOPOSTER_SCHEDULE_ENABLED</code> in <code>.env</code>
                            (operator only, applies to every tenant).</span>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button class="px-5 py-2 bg-blue-600 text-white rounded text-sm">Save settings</button>
            </div>
        </form>

        @endif
    </div>
</x-admin::layouts>

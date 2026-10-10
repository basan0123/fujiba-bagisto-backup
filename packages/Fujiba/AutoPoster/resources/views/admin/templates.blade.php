<x-admin::layouts>
    <x-slot:title>Auto Poster — Templates</x-slot:title>

    <div class="ap">
        @include('autoposter::admin._header', ['title' => 'Caption templates', 'subtitle' => 'The brief every caption is written from.'])

        @include('autoposter::admin._nav')
        @include('autoposter::admin._flash')

        @if ($aiAvailable)
            <div class="ap-alert ap-alert-success">@include('autoposter::admin._icon', ['i' => 'sparkles'])<div class="ap-alert-body">
                AI caption generation is <strong>active</strong>. These templates are the brief:
                Claude follows their tone and structure, and the emoji / hashtag / campaign /
                new-arrival toggles below are applied to its output exactly as they are to
                template-only captions.
            </div></div>
        @else
            <div class="ap-alert ap-alert-info">@include('autoposter::admin._icon', ['i' => 'info'])<div class="ap-alert-body">
                AI caption generation is not configured (<code>ANTHROPIC_API_KEY</code> not set).
                Captions are rendered from these templates only. Once a key is added, these same
                templates guide the AI's tone and structure.
            </div></div>
        @endif

        <div class="ap-card ap-small" style="padding: 14px 18px;" v-pre>
            <strong>Placeholders:</strong>
            @foreach ($placeholders as $ph)
                <code class="mx-1">{{ $ph }}</code>
            @endforeach
        </div>

        @if ($templates->isNotEmpty())
            <div class="ap-card ap-card-flush ap-rise"><div class="ap-table-wrap">
                <table class="ap-table">
                    <thead>
                        <tr>
                            <th class="">Name</th>
                            <th class="">Languages</th>
                            <th class="">Options</th>
                            <th class="">State</th>
                            <th class=" text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($templates as $tpl)
                            <tr>
                                <td>{{ $tpl->name }}</td>
                                <td class="text-xs">{{ strtoupper(str_replace('_', ' + ', $tpl->language_mode)) }}</td>
                                <td class="ap-small ap-muted">
                                    {{ $tpl->include_emojis ? 'emoji ' : '' }}
                                    {{ $tpl->include_hashtags ? 'hashtags ' : '' }}
                                    {{ $tpl->include_campaign_text ? 'campaign ' : '' }}
                                    {{ $tpl->include_new_arrival_text ? 'new-arrival' : '' }}
                                </td>
                                <td class="text-xs">
                                    @if ($tpl->is_default)
                                        <span class="ap-badge ap-tone-blue">default</span>
                                    @endif
                                    @if (! $tpl->is_active)
                                        <span class="ap-badge ap-tone-gray">inactive</span>
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    <form method="POST" action="{{ route('admin.autoposter.templates.destroy', $tpl->id) }}" class="inline"
                                          onsubmit="return confirm('Delete template &quot;{{ addslashes($tpl->name) }}&quot;?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="ap-btn ap-btn-danger ap-btn-sm">@include('autoposter::admin._icon', ['i' => 'trash']) Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div></div>
        @endif

        <form method="POST" action="{{ route('admin.autoposter.templates.save') }}"
              class="ap-card ap-rise ap-grid">
            @csrf
            <input type="hidden" name="channel_id" value="{{ $channelId }}">

            <h2 class="ap-card-title">New template</h2>

            @php
                // Built in PHP rather than written inline: a literal {{ ... }}
                // inside a Blade attribute is parsed as an echo and breaks
                // compilation, even when quoted.
                $open  = '{' . '{';
                $close = '}' . '}';
                $samplePlaceholderEn = "New today: {$open}product_names{$close} — from {$open}price_from{$close}";
                $samplePlaceholderJa = "本日の新着：{$open}product_names{$close}";
            @endphp

            <div class="grid gap-4 md:grid-cols-2">
                <label class="ap-field">
                    <span class="ap-label">Name</span>
                    <input type="text" name="name" required maxlength="120" placeholder="Daily kimono promo">
                </label>

                <label class="ap-field">
                    <span class="ap-label">Languages</span>
                    <select name="language_mode">
                        @foreach ([
                            'en_ja_my' => 'EN + JA + MY', 'en_ja' => 'EN + JA', 'en_my' => 'EN + MY',
                            'en' => 'English only', 'ja' => '日本語のみ', 'my' => 'မြန်မာ only',
                        ] as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <label class="ap-field">
                    <span class="ap-label">English</span>
                    <textarea name="body_en" rows="6"
                              placeholder="{{ $samplePlaceholderEn }}"></textarea>
                </label>
                <label class="ap-field">
                    <span class="ap-label">日本語</span>
                    <textarea name="body_ja" rows="6"
                              placeholder="{{ $samplePlaceholderJa }}"></textarea>
                </label>
                <label class="ap-field">
                    <span class="ap-label">မြန်မာ</span>
                    <textarea name="body_my" rows="6"></textarea>
                </label>
            </div>

            <label class="ap-field">
                <span class="ap-label">Campaign text</span>
                <input type="text" name="campaign_text" maxlength="2000" placeholder="Free shipping over ¥10,000">
            </label>

            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                <label class="ap-check"><input type="checkbox" name="include_emojis" value="1" checked> Emojis</label>
                <label class="ap-check"><input type="checkbox" name="include_hashtags" value="1" checked> Hashtags</label>
                <label class="ap-check"><input type="checkbox" name="include_campaign_text" value="1"> Campaign text</label>
                <label class="ap-check"><input type="checkbox" name="include_new_arrival_text" value="1"> "New Arrival" text</label>
                <label class="ap-check"><input type="checkbox" name="is_default" value="1"> Set as default</label>
                <label class="ap-check"><input type="checkbox" name="is_active" value="1" checked> Active</label>
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button class="ap-btn ap-btn-primary ap-btn-lg">@include('autoposter::admin._icon', ['i' => 'plus']) Save template</button>
            </div>
        </form>
    </div>
</x-admin::layouts>

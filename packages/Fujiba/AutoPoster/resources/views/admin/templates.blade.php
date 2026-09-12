<x-admin::layouts>
    <x-slot:title>Auto Poster — Templates</x-slot:title>

    <div class="flex flex-col gap-4">
        <p class="text-xl text-gray-800 dark:text-white font-bold">Caption templates</p>

        @include('autoposter::admin._nav')
        @include('autoposter::admin._flash')

        @if ($aiAvailable)
            <div class="p-3 border border-green-500 rounded text-sm bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200">
                AI caption generation is <strong>active</strong>. These templates are the brief:
                Claude follows their tone and structure, and the emoji / hashtag / campaign /
                new-arrival toggles below are applied to its output exactly as they are to
                template-only captions.
            </div>
        @else
            <div class="p-3 border border-blue-500 rounded text-sm bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200">
                AI caption generation is not configured (<code>ANTHROPIC_API_KEY</code> not set).
                Captions are rendered from these templates only. Once a key is added, these same
                templates guide the AI's tone and structure.
            </div>
        @endif

        <div class="p-3 border rounded text-xs dark:border-gray-700">
            <strong>Placeholders:</strong>
            @foreach ($placeholders as $ph)
                <code class="mx-1">{{ $ph }}</code>
            @endforeach
        </div>

        @if ($templates->isNotEmpty())
            <div class="border rounded dark:border-gray-700 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr class="text-left">
                            <th class="p-2">Name</th>
                            <th class="p-2">Languages</th>
                            <th class="p-2">Options</th>
                            <th class="p-2">State</th>
                            <th class="p-2 text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($templates as $tpl)
                            <tr class="border-t dark:border-gray-700">
                                <td class="p-2">{{ $tpl->name }}</td>
                                <td class="p-2 text-xs">{{ strtoupper(str_replace('_', ' + ', $tpl->language_mode)) }}</td>
                                <td class="p-2 text-xs text-gray-500">
                                    {{ $tpl->include_emojis ? 'emoji ' : '' }}
                                    {{ $tpl->include_hashtags ? 'hashtags ' : '' }}
                                    {{ $tpl->include_campaign_text ? 'campaign ' : '' }}
                                    {{ $tpl->include_new_arrival_text ? 'new-arrival' : '' }}
                                </td>
                                <td class="p-2 text-xs">
                                    @if ($tpl->is_default)
                                        <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200">default</span>
                                    @endif
                                    @if (! $tpl->is_active)
                                        <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-500 dark:bg-gray-800">inactive</span>
                                    @endif
                                </td>
                                <td class="p-2 text-right">
                                    <form method="POST" action="{{ route('admin.autoposter.templates.destroy', $tpl->id) }}" class="inline"
                                          onsubmit="return confirm('Delete template &quot;{{ addslashes($tpl->name) }}&quot;?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600">delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.autoposter.templates.save') }}"
              class="border rounded p-4 dark:border-gray-700 flex flex-col gap-4">
            @csrf
            <input type="hidden" name="channel_id" value="{{ $channelId }}">

            <h2 class="font-semibold">New template</h2>

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
                <label class="text-sm">
                    <span class="block mb-1">Name</span>
                    <input type="text" name="name" required maxlength="120" placeholder="Daily kimono promo"
                           class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                </label>

                <label class="text-sm">
                    <span class="block mb-1">Languages</span>
                    <select name="language_mode" class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
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
                <label class="text-sm">
                    <span class="block mb-1">English</span>
                    <textarea name="body_en" rows="6"
                              placeholder="{{ $samplePlaceholderEn }}"
                              class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm"></textarea>
                </label>
                <label class="text-sm">
                    <span class="block mb-1">日本語</span>
                    <textarea name="body_ja" rows="6"
                              placeholder="{{ $samplePlaceholderJa }}"
                              class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm"></textarea>
                </label>
                <label class="text-sm">
                    <span class="block mb-1">မြန်မာ</span>
                    <textarea name="body_my" rows="6"
                              class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm"></textarea>
                </label>
            </div>

            <label class="text-sm">
                <span class="block mb-1">Campaign text</span>
                <input type="text" name="campaign_text" maxlength="2000" placeholder="Free shipping over ¥10,000"
                       class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
            </label>

            <div class="flex flex-wrap gap-4 text-sm">
                <label class="flex items-center gap-2"><input type="checkbox" name="include_emojis" value="1" checked> Emojis</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="include_hashtags" value="1" checked> Hashtags</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="include_campaign_text" value="1"> Campaign text</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="include_new_arrival_text" value="1"> "New Arrival" text</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="is_default" value="1"> Set as default</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" checked> Active</label>
            </div>

            <div class="flex justify-end">
                <button class="px-5 py-2 bg-blue-600 text-white rounded text-sm">Save template</button>
            </div>
        </form>
    </div>
</x-admin::layouts>

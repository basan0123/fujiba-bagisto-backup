<x-admin::layouts>
    <x-slot:title>Auto Poster — New Post</x-slot:title>

    @php($channel = \Webkul\Core\Models\Channel::find($channelId))

    <div class="ap">
        @include('autoposter::admin._header', [
            'title'    => 'New post',
            'subtitle' => 'Pick products in the order they should appear, then create a draft to review.',
        ])

        @include('autoposter::admin._nav')
        @include('autoposter::admin._flash')

        @if ($aiAvailable)
            <div class="ap-alert ap-alert-info">
                @include('autoposter::admin._icon', ['i' => 'sparkles'])
                <div class="ap-alert-body">
                    Captions are written by <strong>Claude</strong> in EN / JA / MY, guided by your template's tone
                    and toggles. You can edit the result on the next screen.
                </div>
            </div>
        @else
            <div class="ap-alert ap-alert-info">
                @include('autoposter::admin._icon', ['i' => 'info'])
                <div class="ap-alert-body">
                    Captions are rendered from your saved <strong>templates</strong> (AI isn't configured —
                    no <code>ANTHROPIC_API_KEY</code>). You can edit the text on the next screen.
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.autoposter.posts.store') }}" class="ap-grid ap-split">
            @csrf
            <input type="hidden" name="channel_id" value="{{ $channelId }}">

            <div class="ap-card ap-rise" style="min-width: 0;">
                <div class="ap-card-head">
                    <h2 class="ap-card-title">@include('autoposter::admin._icon', ['i' => 'box']) Select products</h2>
                </div>
                @include('autoposter::admin._picker')
            </div>

            <div class="ap-grid ap-sticky">
                <div class="ap-card ap-rise">
                    <div class="ap-card-head">
                        <h2 class="ap-card-title">@include('autoposter::admin._icon', ['i' => 'settings']) Post setup</h2>
                    </div>

                    <div class="ap-grid" style="gap: 14px;">
                        <label class="ap-field">
                            <span class="ap-label">Format</span>
                            <select name="format" id="ap-format">
                                <option value="multi_photo" @selected($settings->default_format === 'multi_photo')>Multi-photo carousel</option>
                                <option value="single_image" @selected($settings->default_format === 'single_image')>Single image + link</option>
                            </select>
                        </label>

                        <label class="ap-field">
                            <span class="ap-label">Caption template</span>
                            <select name="template_id">
                                <option value="">— default —</option>
                                @foreach ($templates as $tpl)
                                    <option value="{{ $tpl->id }}" @selected($tpl->id == $settings->default_template_id)>
                                        {{ $tpl->name }} ({{ $tpl->language_mode }})
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <button class="ap-btn ap-btn-primary ap-btn-lg ap-btn-block">
                            Create draft &amp; preview @include('autoposter::admin._icon', ['i' => 'arrow-right'])
                        </button>
                    </div>
                </div>

                <div class="ap-card ap-rise">
                    <v-fb-preview
                        :items="[]"
                        page-name="{{ $channel?->name ?: config('app.name') }}"
                        page-avatar="{{ $channel?->logo_url }}"
                        caption="Your caption is generated after you create the draft — you can edit it before posting."
                        format-source="#ap-format"
                        format="{{ $settings->default_format }}"
                    ></v-fb-preview>
                </div>
            </div>
        </form>
    </div>

    @include('autoposter::admin._fb_preview')
</x-admin::layouts>

@php
    $privacyLabels = [
        'PUBLIC_TO_EVERYONE'    => 'Everyone',
        'MUTUAL_FOLLOW_FRIENDS' => 'Friends',
        'FOLLOWER_OF_CREATOR'   => 'Followers',
        'SELF_ONLY'             => 'Only me',
    ];
    $maxSec = (int) ($creator['max_video_post_duration_sec'] ?? 0);
@endphp
<x-admin::layouts>
    <x-slot:title>Auto Poster — Share to TikTok</x-slot:title>

    <div class="ap">
        @include('autoposter::admin._header', ['title' => 'Share to TikTok', 'subtitle' => 'Review and confirm before posting.', 'back' => route('admin.autoposter.tiktok', ['channel_id' => $channelId])])
        @include('autoposter::admin._flash')

        @if ($errors->any())
            <div class="ap-alert ap-alert-danger"><div class="ap-alert-body">{{ $errors->first() }}</div></div>
        @endif

        <form method="POST" action="{{ route('admin.autoposter.tiktok.publish', ['id' => $post->id, 'channel_id' => $channelId]) }}" id="tt-form" class="ap-grid" style="grid-template-columns: minmax(0, 320px) minmax(0, 1fr); gap: 16px; align-items: start;">
            @csrf

            <div class="ap-card">
                <div class="ap-label" style="margin-bottom: 8px;">Preview</div>
                @if ($video)
                    <video id="tt-video" src="{{ $video }}" controls playsinline style="width: 100%; aspect-ratio: 9/16; background: #000; border-radius: 8px;"></video>
                    <div class="ap-muted ap-small" id="tt-duration" style="margin-top: 6px;"></div>
                @else
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px;">
                        @foreach ($images as $img)
                            <img src="{{ $img }}" alt="" style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 4px;">
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="ap-card">
                {{-- Posting account (creator_info) --}}
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
                    @if (! empty($creator['creator_avatar_url']))
                        <img src="{{ $creator['creator_avatar_url'] }}" alt="" style="width: 40px; height: 40px; border-radius: 50%;">
                    @endif
                    <div>
                        <div class="ap-muted ap-small">Posting to TikTok as</div>
                        <div style="font-weight: 600;">{{ $creator['creator_nickname'] ?? $account->display_name }} @if (! empty($creator['creator_username']))<span class="ap-muted">{{ '@'.$creator['creator_username'] }}</span>@endif</div>
                    </div>
                </div>

                <div class="ap-field" style="margin-bottom: 14px;">
                    <span class="ap-label">How to send</span>
                    <label><input type="radio" name="mode" value="direct" @checked(old('mode', 'direct') === 'direct')> Post directly to my profile</label>
                    <label><input type="radio" name="mode" value="draft" @checked(old('mode') === 'draft')> Send to TikTok inbox as a draft (finish in the TikTok app)</label>
                </div>

                <div class="ap-field" style="margin-bottom: 14px;">
                    <span class="ap-label">Title / caption</span>
                    <textarea name="caption" rows="6" maxlength="2200">{{ old('caption', $caption) }}</textarea>
                </div>

                <div data-direct>
                    <div class="ap-field" style="margin-bottom: 14px;">
                        <span class="ap-label">Who can view this post *</span>
                        <select name="privacy_level" id="tt-privacy">
                            <option value="" disabled @selected(! old('privacy_level'))>Select privacy</option>
                            @foreach ($creator['privacy_level_options'] ?? [] as $opt)
                                <option value="{{ $opt }}" @selected(old('privacy_level') === $opt)>{{ $privacyLabels[$opt] ?? $opt }}</option>
                            @endforeach
                        </select>
                        <span class="ap-hint" id="tt-private-hint" style="display: none;">Branded content can't be set to "Only me".</span>
                    </div>

                    <div class="ap-field" style="margin-bottom: 14px;">
                        <span class="ap-label">Allow users to</span>
                        <label><input type="checkbox" name="allow_comment" value="1" @checked(old('allow_comment')) @disabled(! empty($creator['comment_disabled']))> Comment</label>
                        @if ($video)
                            <label><input type="checkbox" name="allow_duet" value="1" @checked(old('allow_duet')) @disabled(! empty($creator['duet_disabled']))> Duet</label>
                            <label><input type="checkbox" name="allow_stitch" value="1" @checked(old('allow_stitch')) @disabled(! empty($creator['stitch_disabled']))> Stitch</label>
                        @endif
                    </div>

                    <div class="ap-field" style="margin-bottom: 14px;">
                        <label style="font-weight: 600;"><input type="checkbox" name="disclose" value="1" id="tt-disclose" @checked(old('disclose'))> Disclose post content</label>
                        <span class="ap-hint">Turn on to disclose that this post promotes goods or services in exchange for something of value.</span>
                        <div id="tt-disclose-opts" style="display: none; padding-left: 22px; margin-top: 6px;">
                            <label><input type="checkbox" name="your_brand" value="1" id="tt-yours" @checked(old('your_brand'))> Your brand — you are promoting yourself or your own business.</label>
                            <label><input type="checkbox" name="branded" value="1" id="tt-branded" @checked(old('branded'))> Branded content — you are promoting another brand or a third party.</label>
                            <div class="ap-hint" id="tt-label"></div>
                        </div>
                    </div>

                    <label class="ap-small" style="display: block; margin-bottom: 14px;">
                        <input type="checkbox" name="consent" value="1" id="tt-consent" @checked(old('consent'))>
                        <span id="tt-consent-text">By posting, you agree to TikTok's <a class="ap-link" href="https://www.tiktok.com/legal/page/global/music-usage-confirmation/en" target="_blank">Music Usage Confirmation</a>.</span>
                    </label>
                </div>

                <button type="submit" class="ap-btn ap-btn-primary ap-btn-lg" id="tt-submit">Post to TikTok</button>
                <div class="ap-muted ap-small" style="margin-top: 8px;">After you post, TikTok may take a few minutes to process the content before it appears on your profile.</div>
            </div>
        </form>
    </div>

    @pushOnce('scripts')
    <script>
        // The admin layout re-renders this markup with Vue after page load, which
        // drops element references and listeners — so look elements up on every
        // event and listen on document.
        (function () {
            var maxSec = {{ $maxSec }}, tooLong = false,
                musicLink = '<a class="ap-link" href="https://www.tiktok.com/legal/page/global/music-usage-confirmation/en" target="_blank">Music Usage Confirmation</a>',
                brandLink = '<a class="ap-link" href="https://www.tiktok.com/legal/page/global/bc-policy/en" target="_blank">Branded Content Policy</a>';
            function $(id) { return document.getElementById(id); }

            function update() {
                var form = $('tt-form');
                if (! form) return;
                var privacy = $('tt-privacy'), disclose = $('tt-disclose'), yours = $('tt-yours'),
                    branded = $('tt-branded'), consent = $('tt-consent'), submit = $('tt-submit'),
                    mode = form.querySelector('[name=mode]:checked'),
                    direct = ! mode || mode.value === 'direct';

                form.querySelector('[data-direct]').style.display = direct ? '' : 'none';
                $('tt-disclose-opts').style.display = disclose.checked ? '' : 'none';

                // Branded content cannot be private.
                var selfOpt = privacy.querySelector('option[value=SELF_ONLY]');
                if (selfOpt) selfOpt.disabled = disclose.checked && branded.checked;
                if (selfOpt && selfOpt.disabled && privacy.value === 'SELF_ONLY') privacy.value = '';
                $('tt-private-hint').style.display = disclose.checked && branded.checked ? '' : 'none';

                var label = '';
                if (disclose.checked && branded.checked) label = 'Your video will be labeled "Paid partnership".';
                else if (disclose.checked && yours.checked) label = 'Your video will be labeled "Promotional content".';
                $('tt-label').textContent = label;

                $('tt-consent-text').innerHTML = 'By posting, you agree to TikTok\'s '
                    + (disclose.checked && branded.checked ? brandLink + ' and ' : '') + musicLink + '.';

                var ok = ! tooLong && (! direct || (privacy.value && consent.checked && (! disclose.checked || yours.checked || branded.checked)));
                submit.disabled = ! ok;
                submit.title = disclose.checked && ! yours.checked && ! branded.checked ? 'You need to indicate if your content promotes yourself, a third party, or both.' : '';
            }

            document.addEventListener('change', function (e) { if (e.target.closest('#tt-form')) update(); });
            document.addEventListener('input', function (e) { if (e.target.closest('#tt-form')) update(); });
            document.addEventListener('submit', function (e) {
                if (e.target.id !== 'tt-form') return;
                var b = $('tt-submit'); b.disabled = true; b.textContent = 'Sending…';
            });
            document.addEventListener('loadedmetadata', function (e) {
                if (e.target.id !== 'tt-video') return;
                var d = Math.round(e.target.duration);
                tooLong = maxSec > 0 && d > maxSec;
                $('tt-duration').textContent = d + 's' + (tooLong ? ' — longer than this account allows (' + maxSec + 's)' : '');
                update();
            }, true);

            window.addEventListener('load', function () { setTimeout(update, 300); });
            setTimeout(update, 1500);
        })();
    </script>
    @endPushOnce
</x-admin::layouts>

<?php

return [
    /*
     * Master kill switch for the scheduled commands, independent of the
     * per-channel autoposter_settings.auto_posting_enabled flag. Set
     * AUTOPOSTER_SCHEDULE_ENABLED=false to stop ALL tenants planning and
     * dispatching without touching any tenant's admin settings — the lever to
     * pull if something goes wrong in production.
     */
    'schedule_enabled' => env('AUTOPOSTER_SCHEDULE_ENABLED', false),

    /*
     * Max posts published per dispatch run, per tenant.
     */
    'dispatch_limit' => (int) env('AUTOPOSTER_DISPATCH_LIMIT', 10),

    /*
     * Caption generation. When no API key is present the generator falls back
     * to template-only output — see CaptionGenerator (step 4).
     */
    'anthropic_api_key' => env('ANTHROPIC_API_KEY'),
    'anthropic_model'   => env('AUTOPOSTER_CAPTION_MODEL', 'claude-opus-5'),

    /*
     * Caption requests are short (three ~500-char bodies), so these are sized
     * for that rather than for long-form generation. Raising max_tokens does
     * not make captions better; it just raises the ceiling on a runaway reply.
     */
    'caption_max_tokens' => (int) env('AUTOPOSTER_CAPTION_MAX_TOKENS', 2000),
    'caption_timeout'    => (int) env('AUTOPOSTER_CAPTION_TIMEOUT', 60),

    /*
     * TikTok Content Posting API (developers.tiktok.com app "Fujiba AutoPoster").
     * Until the app passes TikTok's audit only SELF_ONLY (private) posts work and
     * only sandbox target users can connect. Staff confirm every post by hand —
     * TikTok's UX rules require it.
     */
    'tiktok' => [
        'client_key'    => env('AUTOPOSTER_TIKTOK_CLIENT_KEY'),
        'client_secret' => env('AUTOPOSTER_TIKTOK_CLIENT_SECRET'),
        'redirect_uri'  => env('AUTOPOSTER_TIKTOK_REDIRECT_URI', 'https://kimono.fujiba.co.jp/admin/tiktok/callback'),
        'scopes'        => 'user.info.basic,video.publish,video.upload',
    ],
];

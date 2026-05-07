<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta http-equiv="content-language" content="{{ app()->getLocale() }}">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="{{ core()->getConfigData('pwa.settings.general.theme_color') ?? '#0041ff'  }}">
        <meta name="description" content="{{ core()->getConfigData('pwa.settings.seo.seo_description') ?? 'This is a PWA app'  }}" > <!-- this line is to meet the requirment of lighthouse extension. -->

        @if (core()->getConfigData('pwa.settings.seo.seo_author'))
        <meta name="author" content="{{ core()->getConfigData('pwa.settings.seo.seo_author')  }}" >
        @endif

        @if (core()->getConfigData('pwa.settings.seo.seo_keywords'))
        <meta name="keywords" content="{{ core()->getConfigData('pwa.settings.seo.seo_keywords')  }}" >
        @endif

        <link rel="stylesheet" href="{{ asset('themes/pwa/default/build/assets/css/pwa.css?v=' . strtotime("now")) }}">
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">

        <link rel="icon" sizes="48x48" href="{{ core()->getConfigData('pwa.settings.media.small') ? Storage::url(core()->getConfigData('pwa.settings.media.small')) : asset('themes/pwa/default/build/assets/images/48x48.png') }}" />
        <link rel="icon" sizes="96x96" href="{{ core()->getConfigData('pwa.settings.media.small') ? Storage::url(core()->getConfigData('pwa.settings.media.medium')) : asset('themes/pwa/default/build/assets/images/96x96.png')  }}">
        <link rel="icon" sizes="144x144" href="{{ core()->getConfigData('pwa.settings.media.small') ? Storage::url(core()->getConfigData('pwa.settings.media.large')) : asset('themes/pwa/default/build/assets/images/144x144.png')  }}">
        <link rel="icon" sizes="196x196" href="{{ core()->getConfigData('pwa.settings.media.small') ? Storage::url(core()->getConfigData('pwa.settings.media.extra_large')) : asset('themes/pwa/default/build/assets/images/196x196.png')  }}">

        {{-- icons for IOS devices --}}
        <link rel="apple-touch-icon" sizes="48x48" href="{{ core()->getConfigData('pwa.settings.media.small') ? Storage::url(core()->getConfigData('pwa.settings.media.small')) : asset('themes/pwa/default/build/assets/images/48x48.png')  }}">
        <link rel="apple-touch-icon" sizes="96x96" href="{{ core()->getConfigData('pwa.settings.media.small') ? Storage::url(core()->getConfigData('pwa.settings.media.medium')) : asset('themes/pwa/default/build/assets/images/96x96.png')  }}">
        <link rel="apple-touch-icon" sizes="144x144" href="{{ core()->getConfigData('pwa.settings.media.small') ? Storage::url(core()->getConfigData('pwa.settings.media.large')) : asset('themes/pwa/default/build/assets/images/144x144.png')  }}">
        <link rel="apple-touch-icon" sizes="196x196" href="{{ core()->getConfigData('pwa.settings.media.small') ? Storage::url(core()->getConfigData('pwa.settings.media.extra_large')) : asset('themes/pwa/default/build/assets/images/196x196.png')  }}">
        <meta name="apple-mobile-web-app-capable" content="yes">

        {{-- Removed stray <span class="phpdebugbar-text-muted"><samp class="sf-dump-compact">
             that was injecting Symfony VarDumper's ~50KB client-side script
             into <head> on every page. That blob accounted for the bulk of
             the LCP delay (browser couldn't paint anything until the script
             parsed). --}}

        {!! view_render_event('bagisto.pwa.layout.head') !!}
        <title>
            {{ core()->getConfigData('pwa.settings.seo.seo_title') ?? 'PWA'  }}
        </title>

        {{-- Preload the first hero image so the browser fetches it in
             parallel with the JS bundle. Falls back silently when admin
             hasn't uploaded any hero slot. --}}
        @php
            $pwaHeroChannel = core()->getCurrentChannel();
            $pwaHeroChC     = $pwaHeroChannel?->code;
            $pwaHeroLoc     = app()->getLocale();
            $pwaFirstHero   = core()->getConfigData('general.design.hero.slot_1_image', $pwaHeroChC, $pwaHeroLoc);
            $pwaHeroUrl     = null;
            if ($pwaFirstHero) {
                try { $pwaHeroUrl = \Illuminate\Support\Facades\Storage::url($pwaFirstHero); } catch (\Throwable) {}
            }
        @endphp
        @if ($pwaHeroUrl)
            <link rel="preload" as="image" href="{{ $pwaHeroUrl }}" fetchpriority="high">
        @endif
    </head>

    <body @if (app()->getLocale() == 'ar') class="rtl" dir="rtl" @endif style="scroll-behavior: smooth;">

        {!! view_render_event('bagisto.pwa.layout.body.before') !!}

        {{-- First-paint shell. Painted by the browser before Vue/JS even
             boots so the user sees something instead of a white page. The
             shell sits ABOVE the SPA mount point with `position: fixed`,
             then fades out when Vue mounts. The fade is opacity-only — no
             reflow — so it doesn't contribute to CLS. --}}
        <div id="app">
            <div class="pwa-prepaint" aria-hidden="true">
                <div class="pwa-prepaint-topbar"></div>
                <div class="pwa-prepaint-nav"></div>
                <div class="pwa-prepaint-hero"></div>
            </div>
            <app></app>
        </div>
        <style>
            /* Shell is absolutely positioned over the SPA so its
               appearance/disappearance never moves any other element. */
            .pwa-prepaint {
                position: fixed;
                top: 0; left: 0; right: 0;
                z-index: 200;
                pointer-events: none;
                background: #faf8f5;
                height: 100vh;
                transition: opacity 0.18s ease;
                opacity: 1;
            }
            .pwa-prepaint-topbar {
                position: absolute; top: 0; left: 0; right: 0; height: 32px;
                background: var(--dark, #1a0800);
            }
            .pwa-prepaint-nav {
                position: absolute; top: 32px; left: 0; right: 0; height: 52px;
                background: #fff; border-bottom: 1px solid rgba(0,0,0,0.08);
            }
            .pwa-prepaint-hero {
                position: absolute;
                top: 96px; left: 12px; right: 12px;
                aspect-ratio: 16 / 9;
                max-height: 220px;
                border-radius: 18px;
                background: linear-gradient(135deg, var(--p, #c8420a), var(--acc, #f5a623));
            }
            /* Vue adds <body class="pwa-mounted"> after the SPA paints. */
            body.pwa-mounted .pwa-prepaint {
                opacity: 0;
                pointer-events: none;
            }
        </style>
        {{-- ── Per-channel design tokens for PWA ─────────────────────────
             Server-rendered for first paint. Channel-aware (Fujiba\PwaFrontend
             admin config). Runtime sync below also refreshes from
             /api/v1/pwa-frontend/config on every PWA boot. --}}
        @php
            $pwaCh   = core()->getCurrentChannel();
            $pwaChC  = $pwaCh?->code;
            $pwaLoc  = app()->getLocale();
            $pwaCfg  = fn ($k) => core()->getConfigData($k, $pwaChC, $pwaLoc);

            $pwaBrand   = $pwaCfg('general.design.brand_color.value')        ?: '#c8420a';
            $pwaAccent  = $pwaCfg('general.design.brand_color.accent_color') ?: '#f5a623';
            $pwaDark    = $pwaCfg('general.design.brand_color.dark_color')   ?: '#1a0800';
            $pwaBtnR    = $pwaCfg('general.design.brand_color.btn_radius')   ?: '12px';
            $pwaCardR   = $pwaCfg('general.design.brand_color.card_radius')  ?: '18px';

            $pwaHeaderBg   = $pwaCfg('general.design.brand_color.header_bg')   ?: '';
            $pwaHeaderText = $pwaCfg('general.design.brand_color.header_text') ?: '';
            $pwaFooterBg   = $pwaCfg('general.design.brand_color.footer_bg')   ?: '';
            $pwaFooterText = $pwaCfg('general.design.brand_color.footer_text') ?: '';
            $pwaBtnText    = $pwaCfg('general.design.brand_color.button_text') ?: '#ffffff';

            $pwaLogo    = $pwaCfg('general.design.brand_color.logo_secondary');
            $pwaLogoUrl = $pwaLogo ? \Illuminate\Support\Facades\Storage::url($pwaLogo) : ($pwaCh?->logo_url ?? null);

            $rgb = sscanf($pwaBrand, '#%02x%02x%02x') ?: [200, 66, 10];
        @endphp
        <style>
            :root {
                --p:    {{ $pwaBrand }};
                --p2:   {{ $pwaBrand }};
                --acc:  {{ $pwaAccent }};
                --dark: {{ $pwaDark }};
                --br:   {{ $pwaBtnR }};
                --cr:   {{ $pwaCardR }};
                --p-rgb: {{ implode(',', $rgb) }};
                --brand-color: {{ $pwaBrand }};
                --brand-color-rgb: {{ implode(',', $rgb) }};

                @if ($pwaHeaderBg)   --header-bg:   {{ $pwaHeaderBg }};   @endif
                @if ($pwaHeaderText) --header-text: {{ $pwaHeaderText }}; @endif
                @if ($pwaFooterBg)   --footer-bg:   {{ $pwaFooterBg }};   @endif
                @if ($pwaFooterText) --footer-text: {{ $pwaFooterText }}; @endif
                --btn-text: {{ $pwaBtnText }};
            }

            /* Apply brand color to common Bagisto-PWA CTAs/buttons. */
            .pwa-button-primary,
            button.pwa-primary,
            .add-to-cart-button,
            .place-order-button,
            .btn-primary {
                background-color: var(--brand-color) !important;
                border-color: var(--brand-color) !important;
                color: var(--btn-text) !important;
            }
            a.brand-link, .text-primary { color: var(--brand-color) !important; }

            /* Header / footer per-channel theming. The selectors cover both
               the Bagisto Blade storefront and the PWA SPA chrome. Empty
               admin values leave the rule with `var(--header-bg)` undefined
               which means the original CSS keeps its default (no override). */
            @if ($pwaHeaderBg)
                header.navbar.navbar-top, /* PWA SPA header */
                .lc-nav,                  /* Bagisto Blade desktop nav */
                .header-content,          /* Bagisto Blade mobile nav */
                #pwa-header { background: var(--header-bg) !important; }
            @endif
            @if ($pwaHeaderText)
                header.navbar.navbar-top,
                header.navbar.navbar-top a,
                header.navbar.navbar-top button,
                .lc-nav,
                .lc-nav a,
                .header-content,
                .header-content a { color: var(--header-text) !important; }
            @endif
            @if ($pwaFooterBg)
                #pwa-footer,
                footer.lc-footer,
                .footer-content { background: var(--footer-bg) !important; }
            @endif
            @if ($pwaFooterText)
                #pwa-footer,
                #pwa-footer a,
                footer.lc-footer,
                footer.lc-footer a,
                .footer-content,
                .footer-content a { color: var(--footer-text) !important; }
            @endif
        </style>

        <script type="text/javascript">
            // Multi-tenant fix: use the current request origin so the PWA
            // hits the same host that served the page (avoids CORS, broken
            // session cookies, and "token null" on tenants that aren't the
            // central APP_URL).
            var appBaseUrl = window.location.origin + '/';

            // Brand colour is server-rendered above. This runtime sync lets
            // an admin change it in /admin/configuration/general/design/brand_color
            // and have clients pick it up on next reload (no deploy).
            (function(){
                try {
                    fetch('/api/v1/pwa-frontend/config', { credentials:'same-origin' })
                        .then(function(r){ return r.ok ? r.json() : null; })
                        .then(function(j){
                            if (!j || !j.data || !j.data.brand) return;
                            var b = j.data.brand;
                            var r = document.documentElement.style;
                            if (b.color)      { r.setProperty('--brand-color', b.color); r.setProperty('--p', b.color); }
                            if (b.accent)     { r.setProperty('--acc', b.accent); }
                            if (b.dark)       { r.setProperty('--dark', b.dark); }
                            if (b.btn_radius) { r.setProperty('--br', b.btn_radius); }
                            if (b.card_radius){ r.setProperty('--cr', b.card_radius); }
                            // Expose to Vue via window.config for downstream components
                            window.PWA_FRONTEND = j.data;
                        })
                        .catch(function(){});
                } catch (_) {}
            })();
        </script>
        @php
            $pwaAuthCustomer       = auth()->guard('customer')->user();
            $pwaIsMessengerLogin   = $pwaAuthCustomer && ! empty($pwaAuthCustomer->chatpion_subscribe_id);
            $pwaChannelCodeForOpt  = optional(core()->getCurrentChannel())->code;
            $pwaMessengerOptional  = $pwaIsMessengerLogin
                && in_array($pwaChannelCodeForOpt, ['mmfood', 'kimono'], true);
        @endphp
        <script type="text/javascript">
            // Messenger-logged-in customers on mmfood/kimono can leave email
            // and postcode blank during checkout. The Vue checkout-address
            // component reads this flag to skip the v-validate `required`.
            window.MESSENGER_OPTIONAL_FIELDS = {!! $pwaMessengerOptional ? 'true' : 'false' !!};

            window.channel = @json(new \Webkul\PWA\Http\Resources\Core\Channel(core()->getCurrentChannel()));
            window.config = {
                app_short_name: "{{ core()->getConfigData('pwa.settings.general.short_name') }}",
                app_base_url: appBaseUrl,
                url_path: "{{ $urlPath }}",
                prefix: "{{ $urlPath }}" + '/' + "{{ request()->route()->getName() == 'pwa.home' ? 'pwa' : 'mobile' }}",
                currencies: @json(core()->getCurrentChannel()->currencies),
                currentCurrency: @json(core()->getCurrentCurrency()),
                locales: @json(core()->getCurrentChannel()->locales),
                currentLocale: @json(core()->getCurrentLocale()),
                device: @json($device),
            };
        </script>

        @php
            $clientId = core()->getConfigData('sales.paymentmethods.paypal_smart_button.client_id');
            $acceptedCurrency = core()->getConfigData('sales.paymentmethods.paypal_smart_button.accepted_currencies');
        @endphp

        @if ($clientId && $acceptedCurrency)
            <script src="https://www.paypal.com/sdk/js?client-id={{ $clientId }}&currency={{ $acceptedCurrency }}" data-partner-attribution-id="Bagisto_Cart"></script>
        @endif

        <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/mobilenet" defer></script>
        <script type="text/javascript" src="{{ asset('themes/pwa/default/build/assets/js/app.js?v=' . strtotime("now")) }}"></script>

        @stack('scripts')

        {{-- ── Auto-load on scroll (infinite pagination) ──────────────── --}}
        <script>
        (function () {
            // Auto-clicks the PWA's Load More button when it enters the viewport.
            // Works because the Vue category list already paginates server-side
            // via emit('onPaginate', current_page + 1); we just save the user
            // a tap. Ignored on home-page rows (different markup, fixed counts).
            if (typeof IntersectionObserver === 'undefined') return;
            var seen = new WeakSet();
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    var btn = entry.target;
                    if (seen.has(btn)) return;
                    seen.add(btn);
                    // Give Vue one tick to settle, then click.
                    setTimeout(function () { try { btn.click(); } catch (_) {} }, 50);
                    io.unobserve(btn);
                });
            }, { rootMargin: '300px 0px' });

            function attach() {
                document.querySelectorAll('.pagination button').forEach(function (btn) {
                    if (seen.has(btn)) return;
                    io.observe(btn);
                });
            }
            // First pass + observe DOM changes for SPA navigations.
            window.addEventListener('load', attach);
            new MutationObserver(attach).observe(document.documentElement, {
                childList: true, subtree: true,
            });
        })();
        </script>

        {!! view_render_event('bagisto.pwa.layout.body.after') !!}

        <script>
            if ('serviceWorker' in navigator ) {

                window.addEventListener('load', function() {
                    navigator.serviceWorker.register("{{ asset('service-worker.js') }}")
                        .then(function(registration) {
                            console.log('ServiceWorker registration successful with scope: ', registration.scope);

                            let deferredPrompt;

                            window.addEventListener('beforeinstallprompt', (e) => {
                                // Stash the event so it can be triggered later.
                                deferredPrompt = e;
                                // Update UI to notify the user they can add to home screen
                            });
                        }, function(err) {
                            console.log('ServiceWorker registration failed: ', err);
                        });
                });
            }
        </script>

        {{-- ── PWA Footer (channel-aware) — must be the last element so it
             stays below all SPA content. The fixed bottom-nav floats above. --}}
        @php
            $pwaChannel     = core()->getCurrentChannel();
            $pwaChannelCode = $pwaChannel?->code;
            $pwaLocale      = app()->getLocale();
            $pwaCopyright   = core()->getConfigData('general.content.footer.copyright_content', $pwaChannelCode, $pwaLocale) ?? '';

            try {
                $pwaCmsPages = \Webkul\CMS\Models\Page::query()
                    ->whereHas('channels', fn ($q) => $q->where('channels.id', $pwaChannel?->id))
                    ->with(['translations' => fn ($q) => $q->where('locale', $pwaLocale)])
                    ->get()
                    ->map(function ($page) use ($pwaLocale) {
                        $t = $page->translations->where('locale', $pwaLocale)->first()
                          ?? $page->translations->first();
                        return [
                            'title'   => $t->page_title ?? '',
                            'url_key' => $t->url_key    ?? '',
                        ];
                    })
                    ->filter(fn ($p) => $p['title'] && $p['url_key'])
                    ->values();
            } catch (\Throwable $e) {
                $pwaCmsPages = collect();
            }
        @endphp
        <footer id="pwa-footer" data-channel="{{ $pwaChannelCode }}">
            <div class="pwa-footer-inner">
                @if ($pwaCmsPages->count())
                    <ul class="pwa-footer-links">
                        @foreach ($pwaCmsPages as $page)
                            <li>
                                <a href="{{ url('/page/' . $page['url_key']) }}">
                                    {{ $page['title'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
                @if ($pwaCopyright)
                    <p class="pwa-footer-copy">{!! str_replace(':current_year', now()->year, $pwaCopyright) !!}</p>
                @else
                    <p class="pwa-footer-copy">© {{ now()->year }} {{ $pwaChannel?->name ?? config('app.name') }}</p>
                @endif
            </div>
        </footer>
        <style>
            #pwa-footer {
                background: #f8f8f8;
                color: #444;
                padding: 24px 16px calc(72px + env(safe-area-inset-bottom, 0px)) 16px;
                font-size: 13px;
                border-top: 1px solid #e5e5e5;
            }
            #pwa-footer .pwa-footer-inner { max-width: 720px; margin: 0 auto; }
            #pwa-footer .pwa-footer-links {
                display: flex; flex-wrap: wrap; gap: 12px 18px;
                list-style: none; padding: 0; margin: 0 0 16px;
                justify-content: center;
            }
            #pwa-footer .pwa-footer-links a {
                color: #1f2937; text-decoration: none; font-weight: 500;
            }
            #pwa-footer .pwa-footer-links a:hover { text-decoration: underline; }
            #pwa-footer .pwa-footer-copy {
                text-align: center; color: #6b7280; line-height: 1.5; margin: 0;
            }
            #pwa-footer .pwa-footer-copy p { margin: 0; }
            /* Hidden by default — shown only when Vue marks <body> as home. */
            #pwa-footer { display: none; }
            body.pwa-show-static-footer #pwa-footer { display: block; }
        </style>
    </body>

</html>

@props([
    'hasHeader'  => true,
    'hasFeature' => true,
    'hasFooter'  => true,
])

<!DOCTYPE html>

<html
    lang="{{ app()->getLocale() }}"
    dir="{{ core()->getCurrentLocale()->direction }}"
>
    <head>

        {!! view_render_event('bagisto.shop.layout.head.before') !!}

        <title>{{ $title ?? '' }}</title>

        <meta charset="UTF-8">

        <meta
            http-equiv="X-UA-Compatible"
            content="IE=edge"
        >
        <meta
            http-equiv="content-language"
            content="{{ app()->getLocale() }}"
        >

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover"
        >
        <meta
            name="base-url"
            content="{{ url()->to('/') }}"
        >
        <meta
            name="currency"
            content="{{ core()->getCurrentCurrency()->toJson() }}"
        >
        <meta 
            name="generator" 
            content="Bagisto"
        >

        @stack('meta')

        <link
            rel="icon"
            sizes="16x16"
            href="{{ core()->getCurrentChannel()->favicon_url ?? bagisto_asset('images/favicon.ico') }}"
        />

        @bagistoVite(['src/Resources/assets/css/app.css', 'src/Resources/assets/js/app.js'])

        <link
            rel="preconnect"
            href="https://fonts.googleapis.com"
            crossorigin
        />

        <link
            rel="preconnect"
            href="https://fonts.gstatic.com"
            crossorigin
        />

        <link
            rel="preload" as="style"
            href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=DM+Serif+Display&display=swap"
        />

        <link
            rel="stylesheet"
            href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=DM+Serif+Display&display=swap"
        />

        <link rel="manifest" href="/manifest.webmanifest">
        <meta name="theme-color" content="#3B82F6">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="mobile-web-app-capable" content="yes">

        @stack('styles')

        <style>
            {!! core()->getConfigData('general.content.custom_scripts.custom_css') !!}
        </style>

        {{-- ── Per-channel design tokens (Admin → Configuration → General → Design) ── --}}
        @php
            $lcBrandColor    = core()->getConfigData('general.design.brand_color.value')    ?: '#1e1b4b';
            $lcHeaderBg      = core()->getConfigData('general.design.header.bg_color')      ?: '#ffffff';
            $lcHeaderText    = core()->getConfigData('general.design.header.text_color')    ?: '#111827';
            $lcHeaderBtnBg   = core()->getConfigData('general.design.header.button_bg_color')   ?: '#1e1b4b';
            $lcHeaderBtnText = core()->getConfigData('general.design.header.button_text_color') ?: '#ffffff';

            $lcNavBg     = core()->getConfigData('general.design.bottom_nav.bg_color')     ?: 'linear-gradient(135deg,#1e1b4b 0%,#312e81 40%,#1e3a5f 100%)';
            $lcNavText   = core()->getConfigData('general.design.bottom_nav.text_color')   ?: 'rgba(255,255,255,.45)';
            $lcNavActive = core()->getConfigData('general.design.bottom_nav.active_color') ?: '#ffffff';

            $lcFooterBg      = core()->getConfigData('general.design.footer.bg_color')      ?: '#0f172a';
            $lcFooterText    = core()->getConfigData('general.design.footer.text_color')    ?: '#e5e7eb';
            $lcFooterBtnBg   = core()->getConfigData('general.design.footer.button_bg_color')   ?: '#1e1b4b';
            $lcFooterBtnText = core()->getConfigData('general.design.footer.button_text_color') ?: '#ffffff';
        @endphp
        <style>
        :root{
            --brand-color: {{ $lcBrandColor }};
            --brand-color-rgb: {{ implode(',', sscanf($lcBrandColor, '#%02x%02x%02x') ?: [30, 27, 75]) }};
            --brand-color-soft: rgba(var(--brand-color-rgb), .08);
            --brand-color-hover: rgba(var(--brand-color-rgb), .92);
            --lc-header-bg: {{ $lcHeaderBg }};
            --lc-header-text: {{ $lcHeaderText }};
            --lc-header-btn-bg: {{ $lcHeaderBtnBg }};
            --lc-header-btn-text: {{ $lcHeaderBtnText }};
            --lc-nav-bg: {{ $lcNavBg }};
            --lc-nav-text: {{ $lcNavText }};
            --lc-nav-active: {{ $lcNavActive }};
            --lc-footer-bg: {{ $lcFooterBg }};
            --lc-footer-text: {{ $lcFooterText }};
            --lc-footer-btn-bg: {{ $lcFooterBtnBg }};
            --lc-footer-btn-text: {{ $lcFooterBtnText }};
        }

        /* ── Brand-colour utility classes ─────────────────────────────
           Opt-in helpers any blade can use to honour the per-channel
           brand color set in admin (general.design.brand_color.value). */
        .lc-brand-bg       { background-color: var(--brand-color) !important; }
        .lc-brand-text     { color: var(--brand-color) !important; }
        .lc-brand-border   { border-color: var(--brand-color) !important; }
        .lc-brand-soft-bg  { background-color: var(--brand-color-soft) !important; }
        .lc-brand-btn {
            background-color: var(--brand-color) !important;
            color: #fff !important;
            border: none;
            transition: filter .15s, transform .12s;
        }
        .lc-brand-btn:hover   { filter: brightness(1.08); }
        .lc-brand-btn:active  { transform: scale(.97); }
        .lc-brand-btn:disabled{ opacity: .55; cursor: not-allowed; filter: none; }
        </style>

        {{-- ── Mobile nav styles in <head> so they are parsed before first paint,
             preventing the 1-2s FOUC where the nav briefly shows unstyled ── --}}
        <style>
        /* ── Mobile bottom nav ─────────────────────────────────────── */
        #lc-mobile-nav{
            background: var(--lc-nav-bg) !important;
            box-shadow:0 -2px 24px rgba(99,102,241,.30),0 -1px 0 rgba(255,255,255,.08) !important;
            backdrop-filter:blur(12px);
            -webkit-backdrop-filter:blur(12px);
        }
        .lc-nav-item{
            display:flex;flex-direction:column;align-items:center;justify-content:center;
            gap:3px;padding:6px 0 5px;border-radius:14px;
            color:var(--lc-nav-text, rgba(255,255,255,.45));text-decoration:none;background:none !important;border:none;
            cursor:pointer;font-family:inherit;flex:1;min-width:0;
            transition:color .18s,transform .12s;
            -webkit-tap-highlight-color:transparent;
            position:relative;
        }
        .lc-nav-item:active{transform:scale(.88);}
        .lc-nav-label{
            font-size:9px;font-weight:700;letter-spacing:.04em;line-height:1;
            color:inherit;margin-top:1px;
        }
        .lc-nav-icon-wrap{
            width:36px;height:36px;border-radius:12px;
            display:flex;align-items:center;justify-content:center;
            background:rgba(255,255,255,.06);
            transition:background .18s,box-shadow .18s;
            position:relative;
        }
        .lc-nav-item.lc-active .lc-nav-icon-wrap{
            background:rgba(255,255,255,.18);
            box-shadow:0 2px 12px rgba(255,255,255,.15);
        }
        .lc-nav-item.lc-active{color:var(--lc-nav-active, #fff);}
        .lc-nav-item.lc-active[data-nav="home"]     .lc-nav-icon-wrap{background:rgba(167,139,250,.30);box-shadow:0 3px 14px rgba(139,92,246,.45);}
        .lc-nav-item.lc-active[data-nav="category"] .lc-nav-icon-wrap{background:rgba(56,189,248,.28);box-shadow:0 3px 14px rgba(14,165,233,.4);}
        .lc-nav-item.lc-active[data-nav="cart"]     .lc-nav-icon-wrap{background:rgba(251,146,60,.28);box-shadow:0 3px 14px rgba(234,88,12,.4);}
        .lc-nav-item.lc-active[data-nav="acct"]     .lc-nav-icon-wrap{background:rgba(232,121,249,.28);box-shadow:0 3px 14px rgba(192,38,211,.4);}
        .lc-cart-badge{
            display:none;
            position:absolute;
            top:-5px;right:-5px;
            min-width:16px;height:16px;
            background:#ef4444;
            color:#fff;
            font-size:9px;font-weight:800;line-height:16px;
            text-align:center;
            border-radius:99px;
            padding:0 3px;
            border:2px solid transparent;
            box-shadow:0 1px 4px rgba(0,0,0,.35);
            pointer-events:none;
            z-index:1;
        }
        .lc-cart-badge.lc-badge-show{display:block;}
        /* ── Scroll hide/show for header & bottom nav ── */
        .lc-scroll-header{
            position:fixed !important;
            top:0;left:0;right:0;
            z-index:50;
            /* PWA-matched motion: same duration + cubic-bezier as the SPA
               header so the two storefronts feel like one app.
               NOTE: do NOT add `will-change: transform` here — it creates a
               new containing block for `position: fixed` descendants, which
               causes the drawer (rendered inside this header) to be sized
               against the header's box instead of the viewport. The drawer
               then can't reach full page height. */
            transition:transform .3s ease;
        }
        /* -110% (vs -100%) clears any subpixel border/shadow that lingers
           on some renders. Same trick PWA's bottom-nav uses. */
        .lc-scroll-header.lc-header-hidden{
            transform:translateY(-110%);
        }
        /* Home page on mobile: topnav scrolls with the page (not fixed).
           Footer/bottom-nav keep their show/hide behavior. Desktop unaffected. */
        @media (max-width:1023px){
            body.lc-home-page .lc-scroll-header{
                position: static !important;
            }
            body.lc-home-page .lc-scroll-header.lc-header-hidden{
                transform: none !important;
            }
        }
        .lc-header-spacer{
            display:none;
        }
        #lc-mobile-nav{
            transition:transform .3s cubic-bezier(.4,0,.2,1);
        }
        #lc-mobile-nav.lc-nav-hidden{
            transform:translateY(100%) !important;
        }
        /* Prevent body content shift when mobile nav is fixed */
        @media (max-width:1023px){
            body { padding-bottom: calc(72px + env(safe-area-inset-bottom, 0px)); }
        }
        /* Desktop footer wrapper */
        @media (min-width:1024px){
            .lc-footer-desktop-wrap,
            .lc-services-desktop-wrap { display:block !important; }
            /* Hide mobile nav on desktop */
            #lc-mobile-nav { display:none !important; }
            /* Remove bottom padding on desktop since no mobile nav */
            [data-lc-main] { padding-bottom:0 !important; }
        }
        </style>

        @if(core()->getConfigData('general.content.speculation_rules.enabled'))
            <script type="speculationrules">
                @json(core()->getSpeculationRules(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            </script>
        @endif

        {!! view_render_event('bagisto.shop.layout.head.after') !!}

        {{-- Mobile-app feel comes from the existing speculationrules prerender
             above (head). Turbo Drive was removed because it conflicted with
             Bagisto's per-page Vue mounts (product detail, checkout) and
             produced blank pages on PWA cold-start. --}}
        <script>
            // Hide the PWA splash on every navigation source: initial paint,
            // bfcache restore, and pageshow.
            window.addEventListener('pageshow', function () {
                var splash = document.getElementById('pwa-splash');
                if (splash) { splash.style.display = 'none'; }
            });

            // Brand color: server-rendered above for first-paint, plus a
            // runtime sync from /api/v1/pwa-frontend/config so a tenant
            // admin can change the colour without a full deploy. Cached on
            // the client for an hour to avoid a request per page view.
            (function () {
                try {
                    var KEY = 'lc-brand-cfg';
                    var cached = JSON.parse(sessionStorage.getItem(KEY) || 'null');
                    var now = Date.now();
                    function applyBrand(color) {
                        if (!color || typeof color !== 'string') return;
                        document.documentElement.style.setProperty('--brand-color', color);
                        var rgb = color.replace('#','').match(/.{2}/g);
                        if (rgb && rgb.length === 3) {
                            var r = parseInt(rgb[0],16), g = parseInt(rgb[1],16), b = parseInt(rgb[2],16);
                            document.documentElement.style.setProperty('--brand-color-rgb', r+','+g+','+b);
                        }
                    }
                    if (cached && cached.exp > now) {
                        applyBrand(cached.color);
                        return;
                    }
                    fetch('/api/v1/pwa-frontend/config', { credentials: 'same-origin' })
                        .then(function (r) { return r.ok ? r.json() : null; })
                        .then(function (j) {
                            var color = j && j.data && j.data.brand && j.data.brand.color;
                            if (!color) return;
                            applyBrand(color);
                            sessionStorage.setItem(KEY, JSON.stringify({ color: color, exp: now + 3600000 }));
                        })
                        .catch(function () {});
                } catch (_) {}
            })();
        </script>

    </head>

    <body @class(['lc-home-page' => \Route::currentRouteName() === 'shop.home.index'])>
        {!! view_render_event('bagisto.shop.layout.body.before') !!}

        <!-- ── PWA Splash Screen (mobile/PWA only) ── -->
        <div id="pwa-splash" style="display:none;position:fixed;inset:0;z-index:9999;background:#fff;flex-direction:column;align-items:center;justify-content:center;gap:20px;">
            <img id="pwa-splash-logo" src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}" style="max-width:180px;max-height:80px;object-fit:contain;animation:splashPop 0.5s ease-out both" alt="{{ config('app.name') }}">
            <div style="width:40px;height:4px;border-radius:99px;background:#e2e8f0;overflow:hidden;margin-top:12px">
                <div id="pwa-splash-bar" style="height:100%;width:0;background:linear-gradient(90deg,#3d94fd,#6cbdfc);border-radius:99px;transition:width 1.5s ease"></div>
            </div>
            <p style="font-size:13px;color:#94a3b8;margin-top:4px">{{ core()->getCurrentChannel()->name }}</p>
        </div>
        <style>
        @keyframes splashPop{from{transform:scale(0.7);opacity:0}to{transform:scale(1);opacity:1}}
        /* Mobile sidebar: full screen only on small devices */
        @media (min-width:769px){
            .v-drawer-container { max-width: 380px !important; }
        }
        /* Admin sidebar mobile fullscreen */
        @media (min-width:769px){
            .admin-sidebar, [class*="sidebar"][class*="drawer"] { max-width: 280px !important; }
        }
        </style>
        <script>
        (function(){
            // Only show splash on first load or PWA mode
            var isPWA = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
            var isMobile = window.innerWidth <= 768;
            if ((isPWA || isMobile) && !sessionStorage.getItem('splashShown')) {
                var el = document.getElementById('pwa-splash');
                if (el) {
                    el.style.display = 'flex';
                    var bar = document.getElementById('pwa-splash-bar');
                    setTimeout(function(){ if(bar) bar.style.width = '100%'; }, 50);
                    setTimeout(function(){
                        el.style.transition = 'opacity 0.4s';
                        el.style.opacity = '0';
                        setTimeout(function(){ el.style.display = 'none'; }, 400);
                        sessionStorage.setItem('splashShown', '1');
                    }, 1800);
                }
            }
        })();
        </script>



        <a
            href="#main"
            class="skip-to-main-content-link"
        >
            Skip to main content
        </a>

        <!-- Built With Bagisto -->
        <div id="app">
            <!-- Flash Message Blade Component -->
            <x-shop::flash-group />

            <!-- Add-to-cart UX (toast, fly-to-cart, bounce, badge) -->
            @include('shop::components.layouts.cart-ux')

            <!-- Confirm Modal Blade Component -->
            <x-shop::modal.confirm />

            <!-- Page Header Blade Component -->
            @if ($hasHeader)
                <x-shop::layouts.header />
            @endif

            <!-- ── Global Search Modal ── -->
            <div
                id="global-search-modal"
                style="display:none;position:fixed;inset:0;z-index:9990;background:rgba(15,15,15,0.72);backdrop-filter:blur(6px);"
                onclick="if(event.target===this)closeSearchModal()"
            >
                <div style="position:absolute;top:15%;left:50%;transform:translateX(-50%);width:94%;max-width:660px;">
                    <form
                        action="{{ route('shop.search.index') }}"
                        method="GET"
                        style="position:relative;display:flex;align-items:center;"
                    >
                        <span
                            class="icon-search"
                            style="position:absolute;left:18px;font-size:22px;color:#888;pointer-events:none;"
                        ></span>
                        <input
                            type="text"
                            id="global-search-input"
                            name="query"
                            minlength="{{ core()->getConfigData('catalog.products.search.min_query_length') }}"
                            maxlength="{{ core()->getConfigData('catalog.products.search.max_query_length') }}"
                            placeholder="@lang('shop::app.components.layouts.header.desktop.bottom.search-text')"
                            aria-label="Search"
                            pattern="[^\\]+"
                            required
                            style="width:100%;padding:18px 56px;font-size:17px;border-radius:16px;border:none;outline:none;background:#fff;box-shadow:0 8px 40px rgba(0,0,0,0.18);"
                        >
                        <button
                            type="button"
                            onclick="closeSearchModal()"
                            style="position:absolute;right:16px;font-size:22px;background:none;border:none;cursor:pointer;color:#aaa;line-height:1;"
                            aria-label="Close search"
                        >&#x2715;</button>
                    </form>
                    <p style="margin-top:10px;text-align:center;font-size:13px;color:rgba(255,255,255,0.6);">
                        @lang('shop::app.categories.filters.filters') — Press Enter to search
                    </p>
                </div>
            </div>
            <script>
                function openSearchModal() {
                    var m = document.getElementById('global-search-modal');
                    if (!m) return;
                    m.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                    setTimeout(function() {
                        var i = document.getElementById('global-search-input');
                        if (i) { i.value = ''; i.focus(); }
                    }, 60);
                }
                function closeSearchModal() {
                    var m = document.getElementById('global-search-modal');
                    if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
                }
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') closeSearchModal();
                });
            </script>

            @if(
                core()->getConfigData('general.gdpr.settings.enabled')
                && core()->getConfigData('general.gdpr.cookie.enabled')
            )
                <x-shop::layouts.cookie />
            @endif

            {!! view_render_event('bagisto.shop.layout.content.before') !!}

            <!-- Page Content Blade Component -->
            <main id="main" class="bg-white" style="padding-bottom:72px" data-lc-main>
                {{ $slot }}
            </main>

            {!! view_render_event('bagisto.shop.layout.content.after') !!}


            <!-- Page Services Blade Component (hidden on mobile; bottom nav owns that space) -->
            @if ($hasFeature)
                <div class="lc-services-desktop-wrap" style="display:none">
                    <x-shop::layouts.services />
                </div>
            @endif

            <!-- Page Footer Blade Component (desktop only, hidden on mobile) -->
            @if ($hasFooter)
                {{-- Use inline style + our head-defined .lc-footer-desktop-wrap media rule --}}
                <div class="lc-footer-desktop-wrap" style="display:none">
                    <x-shop::layouts.footer />
                </div>
            @endif

            <!-- ── Language / Currency Bottom Sheet ── -->
            @php
                $lcLocales    = core()->getCurrentChannel()->locales()->orderBy('name')->get();
                $lcCurrencies = core()->getCurrentChannel()->currencies;
                $lcLocale     = app()->getLocale();
                $lcCurrency   = core()->getCurrentCurrencyCode();
            @endphp
            <div id="lc-lang-backdrop" onclick="lcCloseLangSheet()"
                 style="display:none;position:fixed;inset:0;z-index:9500;background:rgba(0,0,0,.5);"></div>
            <div id="lc-lang-sheet"
                 style="display:none;position:fixed;left:0;right:0;bottom:0;z-index:9501;
                        background:#fff;border-radius:20px 20px 0 0;
                        box-shadow:0 -8px 40px rgba(0,0,0,.2);
                        padding-bottom:max(env(safe-area-inset-bottom,0px),12px);
                        max-height:72vh;overflow:hidden;flex-direction:column;transform:translateY(100%);
                        transition:transform .32s cubic-bezier(.32,0,.15,1);">
                {{-- drag handle --}}
                <div style="display:flex;justify-content:center;padding:12px 0 4px;flex-shrink:0;">
                    <div style="width:40px;height:4px;border-radius:99px;background:#e5e7eb;"></div>
                </div>
                {{-- title + close --}}
                <div style="display:flex;align-items:center;justify-content:space-between;padding:4px 16px 10px;flex-shrink:0;">
                    <p style="font-size:15px;font-weight:700;color:#111827;">言語 &amp; 通貨</p>
                    <button onclick="lcCloseLangSheet()" style="background:none;border:none;cursor:pointer;font-size:20px;color:#9ca3af;line-height:1;padding:4px;">&#x2715;</button>
                </div>
                {{-- tabs --}}
                <div style="display:flex;gap:8px;padding:0 16px 10px;flex-shrink:0;">
                    <button id="lc-tab-btn-lang" onclick="lcShowTab('lang')"
                            style="flex:1;padding:9px 0;border-radius:10px;border:none;font-size:13px;font-weight:700;cursor:pointer;background:#1e1b4b;color:#fff;transition:all .15s;">
                        🌐 言語
                    </button>
                    <button id="lc-tab-btn-curr" onclick="lcShowTab('curr')"
                            style="flex:1;padding:9px 0;border-radius:10px;border:none;font-size:13px;font-weight:700;cursor:pointer;background:#f3f4f6;color:#374151;transition:all .15s;">
                        💱 通貨
                    </button>
                </div>
                {{-- language list --}}
                <div id="lc-tab-lang-content" style="overflow-y:auto;padding:0 12px 16px;">
                    @foreach($lcLocales as $loc)
                    <button onclick="lcSwitchLocale('{{ $loc->code }}')"
                            style="width:100%;display:flex;align-items:center;gap:12px;padding:13px 10px;background:{{ $lcLocale===$loc->code?'#ede9fe':'none' }};border:none;border-radius:12px;cursor:pointer;text-align:left;transition:background .12s;margin-bottom:2px;">
                        @if($loc->logo_url)
                            <img src="{{ $loc->logo_url }}" width="28" height="18" style="border-radius:3px;flex-shrink:0;object-fit:cover;">
                        @else
                            <span style="width:28px;height:18px;border-radius:3px;background:#e5e7eb;display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0;">🌐</span>
                        @endif
                        <span style="font-size:14px;font-weight:{{ $lcLocale===$loc->code?'700':'500' }};color:{{ $lcLocale===$loc->code?'#4f46e5':'#1f2937' }};flex:1;">{{ $loc->name }}</span>
                        @if($lcLocale===$loc->code)
                            <svg style="flex-shrink:0;" width="18" height="18" fill="none" viewBox="0 0 24 24"><path d="M5 12l5 5L19 7" stroke="#4f46e5" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @endif
                    </button>
                    @endforeach
                </div>
                {{-- currency list --}}
                <div id="lc-tab-curr-content" style="display:none;overflow-y:auto;padding:0 12px 16px;">
                    @foreach($lcCurrencies as $cur)
                    <button onclick="lcSwitchCurrency('{{ $cur->code }}')"
                            style="width:100%;display:flex;align-items:center;gap:12px;padding:13px 10px;background:{{ $lcCurrency===$cur->code?'#ede9fe':'none' }};border:none;border-radius:12px;cursor:pointer;text-align:left;transition:background .12s;margin-bottom:2px;">
                        <span style="width:38px;height:30px;border-radius:8px;background:{{ $lcCurrency===$cur->code?'#ede9fe':'#f3f4f6' }};display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:{{ $lcCurrency===$cur->code?'#4f46e5':'#6b7280' }};flex-shrink:0;">{{ $cur->symbol }}</span>
                        <div style="flex:1;">
                            <div style="font-size:14px;font-weight:{{ $lcCurrency===$cur->code?'700':'500' }};color:{{ $lcCurrency===$cur->code?'#4f46e5':'#1f2937' }};">{{ $cur->name }}</div>
                            <div style="font-size:11px;color:#9ca3af;">{{ $cur->code }}</div>
                        </div>
                        @if($lcCurrency===$cur->code)
                            <svg style="flex-shrink:0;" width="18" height="18" fill="none" viewBox="0 0 24 24"><path d="M5 12l5 5L19 7" stroke="#4f46e5" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @endif
                    </button>
                    @endforeach
                </div>
            </div>
            <script>
                function lcOpenLangSheet(){
                    document.getElementById('lc-lang-backdrop').style.display='block';
                    var s=document.getElementById('lc-lang-sheet');
                    s.style.display='flex';
                    requestAnimationFrame(function(){
                        requestAnimationFrame(function(){ s.style.transform='translateY(0)'; });
                    });
                }
                function lcCloseLangSheet(){
                    var s=document.getElementById('lc-lang-sheet');
                    s.style.transform='translateY(100%)';
                    setTimeout(function(){
                        s.style.display='none';
                        document.getElementById('lc-lang-backdrop').style.display='none';
                    },320);
                }
                function lcShowTab(tab){
                    var lc=document.getElementById('lc-tab-lang-content');
                    var cc=document.getElementById('lc-tab-curr-content');
                    var lb=document.getElementById('lc-tab-btn-lang');
                    var cb=document.getElementById('lc-tab-btn-curr');
                    var active='background:#1e1b4b;color:#fff;';
                    var inactive='background:#f3f4f6;color:#374151;';
                    if(tab==='lang'){
                        lc.style.display='block'; cc.style.display='none';
                        lb.style.cssText+=active;  cb.style.cssText+=inactive;
                    } else {
                        lc.style.display='none';  cc.style.display='block';
                        cb.style.cssText+=active; lb.style.cssText+=inactive;
                    }
                }
                function lcSwitchLocale(code){
                    var url=new URL(window.location.href);
                    url.searchParams.set('locale',code);
                    window.location.href=url.href;
                }
                function lcSwitchCurrency(code){
                    var url=new URL(window.location.href);
                    url.searchParams.set('currency',code);
                    window.location.href=url.href;
                }
                document.addEventListener('keydown',function(e){if(e.key==='Escape')lcCloseLangSheet();});
            </script>

            <!-- ── Mobile Bottom Navigation Bar ── -->
            <nav id="lc-mobile-nav"
                 class="lc-nav-flex"
                 style="position:fixed;bottom:0;left:0;right:0;z-index:1000;display:flex;align-items:flex-end;padding:8px 4px max(env(safe-area-inset-bottom,0px),8px);">

                <!-- Back -->
                <button type="button" class="lc-nav-item" data-nav="back" onclick="if(history.length>1){history.back()}else{location.href='{{ route('shop.home.index') }}'}" aria-label="@lang('shop::app.components.layouts.header.mobile.nav-back')">
                    <span class="lc-nav-icon-wrap">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><defs><linearGradient id="lc-g-back" x1="2" y1="4" x2="20" y2="20" gradientUnits="userSpaceOnUse"><stop stop-color="#d1d5db"/><stop offset="1" stop-color="#6b7280"/></linearGradient></defs><path d="M15 19l-7-7 7-7" stroke="url(#lc-g-back)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
                    </span>
                    <span class="lc-nav-label">@lang('shop::app.components.layouts.header.mobile.nav-back')</span>
                </button>

                <!-- Home (purple) -->
                <a href="{{ route('shop.home.index') }}" class="lc-nav-item" data-nav="home" aria-label="Home">
                    <span class="lc-nav-icon-wrap">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><defs><linearGradient id="lc-g-home" x1="2" y1="3" x2="22" y2="21" gradientUnits="userSpaceOnUse"><stop stop-color="#ddd6fe"/><stop offset="1" stop-color="#7c3aed"/></linearGradient></defs><path d="M3 12L12 3l9 9v9a1 1 0 01-1 1h-5v-5H9v5H4a1 1 0 01-1-1v-9z" fill="url(#lc-g-home)"/></svg>
                    </span>
                    <span class="lc-nav-label">@lang('shop::app.components.layouts.header.mobile.nav-home')</span>
                </a>

                <!-- Orders (teal/blue) -->
                @auth('customer')
                <a href="{{ route('shop.customers.account.orders.index') }}" class="lc-nav-item" data-nav="orders" aria-label="Orders">
                    <span class="lc-nav-icon-wrap">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><defs><linearGradient id="lc-g-ord" x1="2" y1="2" x2="22" y2="22" gradientUnits="userSpaceOnUse"><stop stop-color="#bae6fd"/><stop offset="1" stop-color="#0369a1"/></linearGradient></defs><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z" fill="url(#lc-g-ord)"/><path d="M14 2v6h6" stroke="url(#lc-g-ord)" stroke-width="1.5" fill="none"/><line x1="8" y1="13" x2="16" y2="13" stroke="#fff" stroke-width="1.5"/><line x1="8" y1="17" x2="13" y2="17" stroke="#fff" stroke-width="1.5"/></svg>
                    </span>
                    <span class="lc-nav-label">@lang('shop::app.components.layouts.header.mobile.nav-orders')</span>
                </a>
                @endauth

                <!-- Cart (orange) -->
                @if(core()->getConfigData('sales.checkout.shopping_cart.cart_page'))
                <a href="{{ route('shop.checkout.cart.index') }}" class="lc-nav-item" data-nav="cart" aria-label="Cart">
                    <span class="lc-nav-icon-wrap">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><defs><linearGradient id="lc-g-cart" x1="2" y1="2" x2="22" y2="22" gradientUnits="userSpaceOnUse"><stop stop-color="#fed7aa"/><stop offset="1" stop-color="#ea580c"/></linearGradient></defs><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4H6z" stroke="url(#lc-g-cart)" stroke-width="2" fill="none" stroke-linejoin="round"/><line x1="3" y1="6" x2="21" y2="6" stroke="url(#lc-g-cart)" stroke-width="2"/><path d="M16 10a4 4 0 01-8 0" stroke="url(#lc-g-cart)" stroke-width="2" fill="none"/></svg>
                        <span id="lc-cart-badge" class="lc-cart-badge"></span>
                    </span>
                    <span class="lc-nav-label">@lang('shop::app.components.layouts.header.mobile.nav-cart')</span>
                </a>
                @endif

                <!-- Account (pink-purple) -->
                @guest('customer')
                <a href="{{ route('shop.customer.session.create') }}" class="lc-nav-item" data-nav="acct" aria-label="Login">
                    <span class="lc-nav-icon-wrap">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><defs><linearGradient id="lc-g-acct" x1="4" y1="3" x2="20" y2="21" gradientUnits="userSpaceOnUse"><stop stop-color="#f5d0fe"/><stop offset="1" stop-color="#9333ea"/></linearGradient></defs><circle cx="12" cy="8" r="4" fill="url(#lc-g-acct)"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="url(#lc-g-acct)" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                    </span>
                    <span class="lc-nav-label">@lang('shop::app.components.layouts.header.mobile.nav-login')</span>
                </a>
                @endguest
                @auth('customer')
                <a href="{{ route('shop.customers.account.index') }}" class="lc-nav-item" data-nav="acct" aria-label="Account">
                    <span class="lc-nav-icon-wrap">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><defs><linearGradient id="lc-g-acct" x1="4" y1="3" x2="20" y2="21" gradientUnits="userSpaceOnUse"><stop stop-color="#f5d0fe"/><stop offset="1" stop-color="#9333ea"/></linearGradient></defs><circle cx="12" cy="8" r="4" fill="url(#lc-g-acct)"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="url(#lc-g-acct)" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                    </span>
                    <span class="lc-nav-label">@lang('shop::app.components.layouts.header.mobile.account')</span>
                </a>
                @endauth
            </nav>
            <script>
                (function(){
                    /* ── Active nav item highlight ── */
                    var path = window.location.pathname;
                    document.querySelectorAll('#lc-mobile-nav .lc-nav-item[href]').forEach(function(el){
                        try {
                            var elPath = new URL(el.href).pathname.replace(/\/$/,'') || '/';
                            var curPath = path.replace(/\/$/,'') || '/';
                            if(elPath==='/'?curPath==='/':curPath===elPath||curPath.startsWith(elPath+'/')){
                                el.classList.add('lc-active');
                            }
                        } catch(e){}
                    });

                    /* ── Cart badge ── */
                    function lcUpdateCartBadge(n){
                        var badge = document.getElementById('lc-cart-badge');
                        if(!badge) return;
                        if(n > 0){
                            badge.textContent = n > 99 ? '99+' : n;
                            badge.classList.add('lc-badge-show');
                        } else {
                            badge.classList.remove('lc-badge-show');
                        }
                    }

                    function lcFetchCartCount(){
                        fetch('{{ route('shop.api.checkout.cart.index') }}', {
                            headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
                        })
                        .then(function(r){ return r.ok ? r.json() : null; })
                        .then(function(d){
                            if(d && d.data) lcUpdateCartBadge(d.data.items_qty || d.data.items_count || 0);
                        })
                        .catch(function(){});
                    }

                    /* Initial load */
                    lcFetchCartCount();

                    /* Listen for Vue cart updates (mini-cart emits 'update-mini-cart') */
                    window.addEventListener('lc-cart-updated', function(e){
                        lcUpdateCartBadge(e.detail || 0);
                    });

                    /* Expose for other scripts to call */
                    window.lcUpdateCartBadge = lcUpdateCartBadge;
                    window.lcFetchCartCount  = lcFetchCartCount;
                })();
            </script>

            <!-- scroll-hide script moved after Vue mount -->
        </div>

        {!! view_render_event('bagisto.shop.layout.body.after') !!}

        @stack('scripts')

        {!! view_render_event('bagisto.shop.layout.vue-app-mount.before') !!}
        <script>
            /**
             * Load event, the purpose of using the event is to mount the application
             * after all of our `Vue` components which is present in blade file have
             * been registered in the app. No matter what `app.mount()` should be
             * called in the last.
             */
            window.addEventListener("load", function (event) {
                app.mount("#app");

                // Scroll-direction hide/show — must run AFTER Vue mount so DOM refs are live
                setTimeout(function(){
                    var header  = document.querySelector('.lc-scroll-header');
                    var mobileNav = document.getElementById('lc-mobile-nav');
                    if (!header && !mobileNav) return;

                    // Home page on mobile: header is position:static, so don't
                    // reserve space for it. Resize toggles between modes when the
                    // viewport crosses the lg breakpoint.
                    function isHomeMobile(){
                        return document.body.classList.contains('lc-home-page')
                            && window.innerWidth < 1024;
                    }

                    if (header) {
                        // Reserve the fixed-header's height via body padding-top
                        // instead of injecting a freestanding `<div class="lc-header-spacer">`
                        // mid-document. The injected div was painting as a 57px
                        // ghost gap on every page; using body padding keeps the
                        // same offset without an extra DOM node.
                        function syncSpacer(){
                            var h = header.offsetHeight || 0;
                            document.body.style.paddingTop = isHomeMobile()
                                ? ''
                                : (h + 'px');
                            // Publish the live header height so any sticky
                            // sub-bar (e.g. .pgf-header) can pin flush below
                            // it instead of relying on a hardcoded 60px.
                            document.documentElement.style.setProperty('--lc-header-h', h + 'px');
                        }
                        syncSpacer();
                        window.addEventListener('resize', syncSpacer);
                    }

                    var lastY = window.scrollY;
                    var ticking = false;
                    var THRESHOLD = 8;
                    var lockUntil = 0; // timestamp; scroll listener is muted while locked

                    // Filters call window.lcLockScroll() before triggering
                    // window.scrollTo({behavior:'smooth'}) so the auto-scroll
                    // doesn't re-hide the header mid-flight.
                    window.lcLockScroll = function (ms) {
                        lockUntil = Date.now() + (ms || 700);
                        if (header)    header.classList.remove('lc-header-hidden');
                        if (mobileNav) mobileNav.classList.remove('lc-nav-hidden');
                        document.body.classList.remove('lc-scrolled-down');
                    };

                    window.addEventListener('scroll', function(){
                        if (ticking) return;
                        ticking = true;
                        requestAnimationFrame(function(){
                            var y = window.scrollY;
                            // While locked (during a programmatic smooth-scroll),
                            // just track lastY without toggling visibility.
                            if (Date.now() < lockUntil) {
                                lastY = y;
                                ticking = false;
                                return;
                            }
                            var delta = y - lastY;
                            if (Math.abs(delta) >= THRESHOLD) {
                                var down = delta > 0 && y > 50;
                                // Home page on mobile: skip header toggle (it's static, scrolls with page).
                                // Bottom nav still toggles regardless.
                                if (header && !isHomeMobile()) header.classList.toggle('lc-header-hidden', down);
                                if (mobileNav) mobileNav.classList.toggle('lc-nav-hidden', down);
                                document.body.classList.toggle('lc-scrolled-down', down);
                                lastY = y;
                            }
                            ticking = false;
                        });
                    }, {passive: true});
                }, 300);
            });
        </script>

        {!! view_render_event('bagisto.shop.layout.vue-app-mount.after') !!}

        <script type="text/javascript">
            {!! core()->getConfigData('general.content.custom_scripts.custom_javascript') !!}
        </script>
    </body>
</html>

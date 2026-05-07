@php
    /** @var \Webkul\Theme\Models\ThemeCustomization $customization */
    $opts = $customization->options ?? [];

    $uid             = 'pgf-' . $customization->id;
    $componentName   = 'v-' . $uid;
    $templateId      = 'v-' . $uid . '-tpl';

    $title           = $opts['title']                  ?? '';
    $chipSources = array_values((array) ($opts['chip_sources'] ?? ['category']));
    $filterAttribute = $opts['filter_attribute']       ?? '';
    $viewOptions         = array_values(array_filter(array_map('intval', (array) ($opts['view_options']         ?? [1, 2, 3]))));
    $viewOptionsMobile   = array_values(array_filter(array_map('intval', (array) ($opts['view_options_mobile']  ?? []))));
    $viewOptionsDesktop  = array_values(array_filter(array_map('intval', (array) ($opts['view_options_desktop'] ?? []))));
    if (empty($viewOptions)) { $viewOptions = [1, 2, 3]; }

    $defaultViewMobile  = (int) ($opts['default_view_mobile']  ?? $opts['default_view'] ?? 2);
    $defaultViewDesktop = (int) ($opts['default_view_desktop'] ?? $opts['default_view'] ?? 3);

    if (! in_array($defaultViewMobile,  $viewOptions,        true)) { $viewOptions[]        = $defaultViewMobile; }
    if (! in_array($defaultViewDesktop, $viewOptions,        true)) { $viewOptions[]        = $defaultViewDesktop; }
    if ($viewOptionsMobile  && ! in_array($defaultViewMobile,  $viewOptionsMobile,  true)) { $viewOptionsMobile[]  = $defaultViewMobile; }
    if ($viewOptionsDesktop && ! in_array($defaultViewDesktop, $viewOptionsDesktop, true)) { $viewOptionsDesktop[] = $defaultViewDesktop; }

    sort($viewOptions);
    if ($viewOptionsMobile)  sort($viewOptionsMobile);
    if ($viewOptionsDesktop) sort($viewOptionsDesktop);
    $perPage         = (int) ($opts['per_page'] ?? 24);
    $sticky          = (bool) ($opts['sticky_filter_nav'] ?? true);
    $currentLocaleCode = app()->getLocale();

    $chipSourceLabels = [
        'category' => __('shop::app.home.product-grid-filter.category'),
        'brand'    => __('shop::app.home.product-grid-filter.brand'),
        'color'    => __('shop::app.home.product-grid-filter.color'),
        'writer'   => __('shop::app.home.product-grid-filter.writer'),
    ];

    $currentLocale = core()->getCurrentLocale();
    $isCustomerLoggedIn = auth()->guard('customer')->check();
    $customerFirstName = $isCustomerLoggedIn ? auth()->guard('customer')->user()->first_name : '';
    $dropdownPos = 'bottom-' . ($currentLocale->direction === 'ltr' ? 'right' : 'left');
    /**
     * Pick the flag image for the active locale. Priority:
     *  1) Admin-uploaded logo on the locale row.
     *  2) Static fallback at public/flags/<iso>.svg keyed by Bagisto's
     *     locale code (en/ja/my/my_MM → en/jp/mm). Lets us ship flags
     *     without writing to per-tenant locale tables.
     *  3) Bagisto's neutral default-language.svg.
     */
    $flagFallback = [
        'en'    => '/flags/en.svg',
        'en_US' => '/flags/en.svg',
        'en_GB' => '/flags/en.svg',
        'ja'    => '/flags/jp.svg',
        'ja_JP' => '/flags/jp.svg',
        'my'    => '/flags/mm.svg',
        'my_MM' => '/flags/mm.svg',
    ];
    $localeFlag = ! empty($currentLocale->logo_url)
        ? $currentLocale->logo_url
        : ($flagFallback[$currentLocale->code] ?? bagisto_asset('images/default-language.svg'));
    $compareEnabled = (bool) core()->getConfigData('catalog.products.settings.compare_option');
    $cartEnabled = (bool) core()->getConfigData('sales.checkout.shopping_cart.cart_page');
    $wishlistEnabled = (bool) core()->getConfigData('customer.settings.wishlist.wishlist_option');

    $config = [
        'uid'                 => $uid,
        'title'               => $title,
        'chipSources'         => $chipSources,
        'chipSourceLabels'    => array_intersect_key($chipSourceLabels, array_flip($chipSources)),
        'filterAttribute'     => $filterAttribute,
        'viewOptions'         => $viewOptions,
        'viewOptionsMobile'   => $viewOptionsMobile,
        'viewOptionsDesktop'  => $viewOptionsDesktop,
        'defaultViewMobile'   => $defaultViewMobile,
        'defaultViewDesktop'  => $defaultViewDesktop,
        'perPage'             => $perPage,
        'sticky'              => $sticky,
        'locale'              => $currentLocaleCode,
        'addToCartLabel'      => __('shop::app.components.products.card.add-to-cart'),
        'api' => [
            'products'        => route('shop.api.products.index'),
            'categoriesTree'  => route('shop.api.categories.tree'),
            'attributes'      => route('shop.api.categories.attributes'),
        ],
        'icons' => [
            'dropdownPos'     => $dropdownPos,
            'localeCode'      => strtoupper($currentLocaleCode),
            'localeFlag'      => $localeFlag,
            'localeLabel'     => __('shop::app.components.layouts.header.desktop.top.default-locale'),
            'compareEnabled'  => $compareEnabled,
            'compareUrl'      => route('shop.compare.index'),
            'compareLabel'    => __('shop::app.components.layouts.header.desktop.bottom.compare'),
            'cartEnabled'     => $cartEnabled,
            'profileLabel'    => __('shop::app.components.layouts.header.desktop.bottom.profile'),
            'isLoggedIn'      => $isCustomerLoggedIn,
            'customerName'    => $customerFirstName,
            'welcomeGuest'    => __('shop::app.components.layouts.header.desktop.bottom.welcome-guest'),
            'welcome'         => __('shop::app.components.layouts.header.desktop.bottom.welcome'),
            'dropdownText'    => __('shop::app.components.layouts.header.desktop.bottom.dropdown-text'),
            'signInLabel'     => __('shop::app.components.layouts.header.desktop.bottom.sign-in'),
            'signUpLabel'     => __('shop::app.components.layouts.header.desktop.bottom.sign-up'),
            'profileMenuLabel'=> __('shop::app.components.layouts.header.desktop.bottom.profile'),
            'ordersLabel'     => __('shop::app.components.layouts.header.desktop.bottom.orders'),
            'wishlistLabel'   => __('shop::app.components.layouts.header.desktop.bottom.wishlist'),
            'logoutLabel'     => __('shop::app.components.layouts.header.desktop.bottom.logout'),
            'wishlistEnabled' => $wishlistEnabled,
            'signInUrl'       => route('shop.customer.session.create'),
            'signUpUrl'       => route('shop.customers.register.index'),
            'profileUrl'      => route('shop.customers.account.profile.index'),
            'ordersUrl'       => route('shop.customers.account.orders.index'),
            'wishlistUrl'     => route('shop.customers.account.wishlist.index'),
            'logoutUrl'       => route('shop.customer.session.destroy'),
            'csrfToken'       => csrf_token(),
        ],
    ];
@endphp

@pushOnce('styles')
<style>
.pgf-wrap{background:#fff;min-height:100vh;overflow:visible}

/* ── Sticky header ─────────────────────────────────────────── */
.pgf-header{background:#fff;border-bottom:1px solid #e5e7eb;z-index:40;transition:box-shadow .22s ease,top .25s cubic-bezier(.4,0,.2,1)}
.pgf-header.pgf-sticky{position:sticky;top:var(--lc-header-h,60px);box-shadow:0 1px 6px rgba(0,0,0,.06)}
.lc-scrolled-down .pgf-header.pgf-sticky{top:0}
@media(max-width:1023px){body.lc-home-page .pgf-header.pgf-sticky{top:0}}
.pgf-header.is-stuck{box-shadow:0 4px 12px rgba(0,0,0,.08)}

/* Center strips to same max-width as grid */
.pgf-header>.pgf-bar,.pgf-header>.filter-scroll{max-width:1600px;margin-left:auto;margin-right:auto}
@media(min-width:1024px){.pgf-header>.pgf-bar{padding-left:32px;padding-right:32px}.pgf-header>.filter-scroll{padding-left:32px;padding-right:32px}}
@media(min-width:1536px){.pgf-header>.pgf-bar{padding-left:64px;padding-right:64px}.pgf-header>.filter-scroll{padding-left:64px;padding-right:64px}}
@media(min-width:1920px){.pgf-header>.pgf-bar{padding-left:0;padding-right:0}.pgf-header>.filter-scroll{padding-left:0;padding-right:0}}

.lc-header-right{display:none !important}

/* ── Row 1 — bar ─────────────────────────────────────────────
   Mobile: 56px min-height, 10px padding, 34px controls.
   View buttons hidden on mobile — user swipes the grid instead.
   Desktop: keeps the view toggle buttons visible. */
.pgf-bar{
    display:flex;align-items:center;gap:8px;
    padding:6px 12px;background:#fff;
    position:relative;min-height:46px;
    box-sizing:border-box;
}

/* View toggle — HIDDEN on mobile, shown on desktop */
.pgf-views{
    display:none; /* hidden mobile */
    align-items:center;gap:2px;flex:0 0 auto;
    background:#f3f4f6;border-radius:6px;padding:2px;
}
.pgf-view-btn{
    width:26px;height:26px;border:0;border-radius:5px;
    display:flex;align-items:center;justify-content:center;
    font-size:11px;font-weight:700;color:#6b7280;cursor:pointer;
    background:transparent;transition:background-color .15s,color .15s;
    -webkit-tap-highlight-color:transparent;line-height:1;padding:0;
}
.pgf-view-btn:hover{color:#111827}
.pgf-view-btn.pgf-active{background:#fff;color:#111827;box-shadow:0 1px 2px rgba(0,0,0,.08)}

/* Mobile swipe view indicator — small dots shown on mobile only */
.pgf-swipe-indicator{
    display:inline-flex;align-items:center;gap:4px;flex:0 0 auto;
}
.pgf-swipe-dot{
    width:6px;height:6px;border-radius:50%;
    background:#d1d5db;transition:background .2s,transform .2s;
}
.pgf-swipe-dot.active{background:#111827;transform:scale(1.3)}

/* ── Search toggle ──────────────────────────────────────────── */
.pgf-search-toggle{
    width:34px;height:34px;border:0;border-radius:8px;
    background:transparent;color:#6b7280;cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    -webkit-tap-highlight-color:transparent;
    transition:background-color .15s,color .15s;
    padding:0;flex:0 0 auto;
}
.pgf-search-toggle:hover{background:#f3f4f6;color:#111827}
.pgf-search-toggle svg{width:18px;height:18px}

/* ── Search overlay — covers entire bar ────────────────────── */
.pgf-search-overlay{
    position:absolute;
    top:10px;left:12px;right:12px;bottom:10px;
    z-index:5;
    display:flex;align-items:center;
    background:#fff;
    animation:pgfOverlayIn .18s ease-out;
}
@keyframes pgfOverlayIn{from{opacity:0;transform:translateY(-2px)}to{opacity:1;transform:translateY(0)}}
.pgf-search-overlay input{
    width:100%;height:36px;
    padding:0 36px 0 34px;
    border-radius:99px;border:1.5px solid #060C3B;
    font-size:15px;outline:none;background:#fff;
    box-sizing:border-box;
}
.pgf-search-overlay .pgf-search-icon{
    position:absolute;left:12px;color:#6b7280;
    pointer-events:none;display:flex;align-items:center;
}
.pgf-search-overlay .pgf-search-icon svg{width:15px;height:15px}
.pgf-search-overlay .pgf-search-clear{
    position:absolute;right:6px;
    width:26px;height:26px;border:0;border-radius:99px;
    background:#e5e7eb;color:#374151;
    font-size:16px;line-height:1;cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    -webkit-tap-highlight-color:transparent;padding:0;
}
.pgf-search-overlay .pgf-search-clear:hover{background:#d1d5db}

/* ── Filter pill + arrows ───────────────────────────────────── */
.pgf-filter-pill-wrap{
    flex:0 0 auto;display:inline-flex;align-items:center;gap:0;
    perspective:400px;height:34px;
}
.pgf-filter-pill{
    position:relative;
    height:34px;min-width:90px;max-width:120px;
    padding:0 12px;
    border-radius:99px;
    background:#EEEDFE;color:#26215C;
    border:1.5px solid #CECBF6;
    font-size:13px;font-weight:600;
    cursor:pointer;
    transform-style:preserve-3d;
    transition:background-color .25s,color .25s,border-color .25s;
    display:flex;align-items:center;justify-content:center;
    overflow:hidden;
    -webkit-tap-highlight-color:transparent;
    line-height:1;box-sizing:border-box;
}
.pgf-filter-pill-label{
    display:inline-block;
    transition:transform .45s cubic-bezier(.6,.02,.3,1),opacity .25s;
    line-height:1;white-space:nowrap;
}
.pgf-filter-pill.is-rotating .pgf-filter-pill-label{
    transform:translateY(-34px) rotateX(-90deg);opacity:0;
}
.pgf-filter-pill[data-src="category"]{background:#E1F5EE;color:#04342C;border-color:#9FE1CB}
.pgf-filter-pill[data-src="brand"]   {background:#EEEDFE;color:#26215C;border-color:#CECBF6}
.pgf-filter-pill[data-src="color"]   {background:#FAECE7;color:#4A1B0C;border-color:#F5C4B3}
.pgf-filter-pill[data-src="writer"]  {background:#FBEAF0;color:#4B1528;border-color:#F4C0D1}

.pgf-filter-arrow{display:none !important}

/* ── Right icons — 34px on mobile ──────────────────────────── */
.pgf-bar-icons{
    display:inline-flex;align-items:center;gap:2px;
    margin-left:auto;flex:0 0 auto;
}
.pgf-bar-icon{
    display:inline-flex;align-items:center;justify-content:center;
    width:38px;height:38px;border-radius:8px;
    color:#374151;cursor:pointer;text-decoration:none;
    transition:background-color .2s,color .2s,transform .2s;
    position:relative;box-sizing:border-box;padding:0;
    vertical-align:middle;
}
.pgf-bar-icon:hover{background:rgba(0,0,0,.05);color:#111827;transform:scale(1.05)}
.pgf-bar-icon>span[class^="icon-"],
.pgf-bar-icon>span[class*=" icon-"]{font-size:22px;line-height:1}
.pgf-bar-icons .icon-cart{font-size:22px !important;line-height:1}
.pgf-bar-icons .pgf-bar-icon>a{
    display:inline-flex;align-items:center;justify-content:center;
    width:100%;height:100%;color:inherit;text-decoration:none;
}
/* Cart badge */
.pgf-bar-icons .icon-cart+span{
    top:-4px !important;left:auto !important;right:-6px;
    padding:0 4px !important;font-size:9px !important;line-height:1.4 !important;
    min-width:15px;height:15px;text-align:center;
    background:#dc2626 !important;color:#fff !important;
    border-radius:99px !important;font-weight:600 !important;
    display:inline-flex !important;align-items:center;justify-content:center;
    box-shadow:0 0 0 1.5px #fff;
}
/* Language icon — same 34px box as siblings */
.pgf-bar-lang{
    width:auto !important;min-width:38px;height:38px !important;
    padding:0 8px !important;gap:5px !important;
    display:inline-flex !important;align-items:center !important;
    justify-content:center !important;line-height:1 !important;
    box-sizing:border-box !important;vertical-align:middle !important;
}
.pgf-bar-lang img{width:19px;height:13px;display:block;border-radius:1px;flex-shrink:0;object-fit:cover;margin:0 !important}
.pgf-bar-lang span.lc-lang-code{font-size:11px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#3f3f46;line-height:1;display:inline-block;margin:0 !important}

@media(max-width:380px){
    .pgf-bar-icons a[aria-label*="ompare"]{display:none}
}

/* ── Row 2 — chip strip, full size ─────────────────────────── */
.filter-scroll{
    display:flex;flex-wrap:nowrap;gap:7px;
    padding:8px 12px 10px;
    overflow-x:auto;-webkit-overflow-scrolling:touch;scroll-behavior:smooth;
    scrollbar-width:none;border-top:1px solid #f3f4f6;
    /* 3D context for the source-swap flip animation. The chip row rotates
       on the X axis (top edge tilts away) when the user changes source. */
    perspective:600px;transform-style:preserve-3d;
    transition:background-color .35s ease;
    will-change:transform;
}
.filter-scroll::-webkit-scrollbar{display:none}

/* Source-swap animation. JS toggles `.is-flipping-out` for ~180ms, swaps
   data-src + chipItems, then `.is-flipping-in` plays the entry. The
   single-axis rotateX gives the same "card flip" feel as the source pill. */
.filter-scroll.is-flipping-out{
    animation:pgfChipFlipOut .22s cubic-bezier(.6,.02,.3,1) forwards;
}
.filter-scroll.is-flipping-in{
    animation:pgfChipFlipIn .32s cubic-bezier(.22,.61,.36,1) both;
}
@keyframes pgfChipFlipOut{
    0%  {opacity:1;transform:rotateX(0)         translateX(0);}
    100%{opacity:0;transform:rotateX(-70deg)    translateX(-30px);}
}
@keyframes pgfChipFlipIn{
    0%  {opacity:0;transform:rotateX(70deg)     translateX(30px);}
    60% {opacity:1;}
    100%{opacity:1;transform:rotateX(0)         translateX(0);}
}

.filter-pill{
    flex:0 0 auto;
    padding:7px 15px;
    border-radius:99px;border:1px solid transparent;
    background:#f3f4f6;color:#374151;
    font-size:13px;font-weight:600;line-height:1.2;
    white-space:nowrap;cursor:pointer;
    transition:background-color .25s,color .25s,border-color .25s,transform .1s;
    -webkit-tap-highlight-color:transparent;
}
.filter-pill:hover{background:#e5e7eb}
.filter-pill:active{transform:scale(.96)}
.filter-pill.active{background:#111827;color:#fff;border-color:#111827}

/* Source-themed chip palette — mirrors the source-pill colour scheme so
   the whole row visibly changes hue when the user flips Category↔Brand
   etc. The active chip stays solid dark for contrast. */
.filter-scroll[data-src="category"] .filter-pill{background:#E1F5EE;color:#04342C}
.filter-scroll[data-src="category"] .filter-pill.active{background:#04342C;color:#E1F5EE;border-color:#04342C}
.filter-scroll[data-src="brand"]    .filter-pill{background:#EEEDFE;color:#26215C}
.filter-scroll[data-src="brand"]    .filter-pill.active{background:#26215C;color:#EEEDFE;border-color:#26215C}
.filter-scroll[data-src="color"]    .filter-pill{background:#FAECE7;color:#4A1B0C}
.filter-scroll[data-src="color"]    .filter-pill.active{background:#4A1B0C;color:#FAECE7;border-color:#4A1B0C}
.filter-scroll[data-src="writer"]   .filter-pill{background:#FBEAF0;color:#4B1528}
.filter-scroll[data-src="writer"]   .filter-pill.active{background:#4B1528;color:#FBEAF0;border-color:#4B1528}

/* ── Desktop ────────────────────────────────────────────────── */
@media(min-width:768px){
    .pgf-bar{padding:6px 16px;gap:12px;min-height:46px}
    .filter-scroll{padding:8px 16px 10px;gap:8px}
    .filter-pill{padding:7px 16px;font-size:13px}
    /* Show view buttons on desktop, hide swipe indicator */
    .pgf-views{display:inline-flex}
    .pgf-swipe-indicator{display:none}
    .pgf-bar-icon{width:38px;height:38px}
    .pgf-bar-lang{height:38px !important;padding:0 9px !important;gap:6px !important}
    .pgf-bar-lang img{width:22px;height:15px}
    .pgf-bar-lang span.lc-lang-code{font-size:12px}
    .pgf-filter-pill{height:32px;min-width:100px;font-size:13px}
    .pgf-filter-pill.is-rotating .pgf-filter-pill-label{transform:translateY(-32px) rotateX(-90deg)}
    .pgf-filter-pill-wrap{height:32px}
    .pgf-filter-arrow{height:32px;width:22px}
    .pgf-search-toggle{width:34px;height:34px}
    .pgf-search-overlay{top:10px;bottom:10px;left:16px;right:16px}
    .pgf-search-overlay input{height:36px;font-size:14px}
}

/* ── Product grid ───────────────────────────────────────────── */
.pgf-grid{display:grid;gap:10px;padding:10px 10px 40px;max-width:1600px;margin-left:auto;margin-right:auto;scroll-margin-top:calc(var(--lc-header-h,60px) + var(--pgf-bar-h,96px) + 8px);transition:opacity .22s ease;will-change:opacity}
.pgf-grid.pgf-cols-1{grid-template-columns:1fr}
.pgf-grid.pgf-cols-2{grid-template-columns:repeat(2,1fr)}
.pgf-grid.pgf-cols-3{grid-template-columns:repeat(3,1fr)}
.pgf-grid.pgf-cols-4{grid-template-columns:repeat(4,1fr)}
.pgf-grid.pgf-cols-5{grid-template-columns:repeat(5,1fr)}
.pgf-grid.pgf-cols-6{grid-template-columns:repeat(6,1fr)}
@media(min-width:1024px){.pgf-grid{padding:12px 32px 40px;gap:16px}}
@media(min-width:1536px){.pgf-grid{padding:16px 64px 60px;gap:20px}}
@media(min-width:1920px){.pgf-grid{padding:16px 0 60px}}
.pgf-grid.pgf-fading{opacity:.45;pointer-events:none}
@keyframes pgfSlideInRight{from{opacity:0;transform:translateX(40px)}to{opacity:1;transform:translateX(0)}}
@keyframes pgfSlideInLeft{from{opacity:0;transform:translateX(-40px)}to{opacity:1;transform:translateX(0)}}
.pgf-grid.pgf-slide-right{animation:pgfSlideInRight .35s cubic-bezier(.22,.61,.36,1) both}
.pgf-grid.pgf-slide-left{animation:pgfSlideInLeft .35s cubic-bezier(.22,.61,.36,1) both}

/* ── List mode ─────────────────────────────────────────────── */
.pgf-list{max-width:1400px;margin-left:auto;margin-right:auto;padding:10px 12px 40px;display:flex;flex-direction:column;gap:12px}
@media(min-width:1024px){.pgf-list{padding:12px 24px 40px;gap:16px}}
.pgf-list-card{display:flex;gap:14px;background:#fff;border-radius:12px;padding:10px;box-shadow:0 1px 4px rgba(0,0,0,.07);transition:box-shadow .18s;text-decoration:none;color:inherit}
.pgf-list-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.12)}
.pgf-list-img{flex-shrink:0;width:110px;height:110px;border-radius:10px;overflow:hidden;background:#f9fafb;position:relative}
@media(min-width:768px){.pgf-list-img{width:180px;height:180px}}
.pgf-list-img img{width:100%;height:100%;object-fit:cover}
.pgf-list-body{flex:1;min-width:0;display:flex;flex-direction:column;justify-content:space-between;gap:8px}
.pgf-list-name{font-size:14px;font-weight:600;color:#111827;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
@media(min-width:768px){.pgf-list-name{font-size:16px}}
.pgf-list-price{font-size:16px;font-weight:700;color:#1e1b4b}
.pgf-list-orig{font-size:12px;color:#9ca3af;text-decoration:line-through;margin-left:6px;font-weight:400}
.pgf-list-sale{color:#dc2626}

/* ── Cards ─────────────────────────────────────────────────── */
.pgf-card{background:#fff;border-radius:6px;overflow:hidden;display:flex;flex-direction:column;transition:box-shadow .3s}
.pgf-card:hover{box-shadow:0 5px 10px rgba(0,0,0,.1)}
.pgf-card-img{position:relative;background:#f4f4f5;overflow:hidden;aspect-ratio:1/1;border-radius:6px}
.pgf-card-img img{width:100%;height:100%;object-fit:cover;transition:transform .3s}
.pgf-card:hover .pgf-card-img img{transform:scale(1.05)}
.pgf-card-body{padding:10px 4px 6px;flex:1;display:flex;flex-direction:column;position:relative;gap:6px}
.pgf-card-name{font-size:14px;font-weight:600;color:#111827;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;flex:1;text-decoration:none}
@media(max-width:640px){.pgf-card-name{font-size:13px;line-height:1.35}}
.pgf-card-foot{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:auto}
.pgf-card-price{font-size:15px;font-weight:700;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1 1 auto;min-width:0}
@media(max-width:640px){.pgf-card-price{font-size:13.5px}}
.pgf-card-orig{font-size:12px;color:#9ca3af;text-decoration:line-through;margin-left:4px;font-weight:400}
.pgf-card-sale{color:#dc2626}
.pgf-badges{position:absolute;top:6px;left:6px;display:flex;flex-direction:column;gap:4px;z-index:2;pointer-events:none;align-items:flex-start}
.pgf-badge{font-size:13px;font-weight:500;padding:1px 10px;border-radius:44px;color:#fff;line-height:1.5}
.pgf-badge-new{background:#060C3B}
.pgf-badge-sale{background:#dc2626}
.pgf-badge-oos{background:#6b7280}
@media(max-width:640px){.pgf-badges{top:6px;left:0}.pgf-badge{font-size:11px;padding:2px 8px;border-radius:0 12px 12px 0}}

.pgf-list-cart-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:12px;border:1px solid #060C3B;background:#fff;color:#060C3B;font-size:13px;font-weight:600;line-height:1.2;cursor:pointer;align-self:flex-start;transition:background-color .15s,transform .1s;z-index:1;position:relative}
.pgf-list-cart-btn:hover{background:rgba(6,12,59,.05)}
.pgf-list-cart-btn:active{transform:scale(.97)}
.pgf-list-cart-btn:disabled{border-color:#d4d4d8;color:#9ca3af;background:#fafafa;cursor:not-allowed;transform:none}

.pgf-shimmer{background:linear-gradient(90deg,#f0f0f0 25%,#e8e8e8 50%,#f0f0f0 75%);background-size:200% 100%;animation:pgfShim 1.4s infinite;border-radius:8px}
@keyframes pgfShim{to{background-position:-200% 0}}
.pgf-spin{display:inline-block;width:22px;height:22px;border:2px solid rgba(30,27,75,.18);border-top-color:#1e1b4b;border-radius:50%;animation:pgfRot .6s linear infinite}
@keyframes pgfRot{to{transform:rotate(360deg)}}
.pgf-empty{text-align:center;color:#9ca3af;padding:40px 0;font-size:14px}
</style>
@endPushOnce

<div id="{{ $uid }}" class="pgf-wrap">
    <component :is="'{{ $componentName }}'"></component>
</div>

@pushOnce('scripts')
<script type="text/x-template" id="{{ $templateId }}">
    <div ref="root">
        <div class="pgf-header" ref="bar" :class="cfg.sticky ? 'pgf-sticky' : ''">

            {{-- ── Row 1 ── --}}
            <div class="pgf-bar">

                {{-- Desktop: view toggle buttons --}}
                <div v-if="viewOptions.length > 1 && !searchOpen" class="pgf-views" role="group" aria-label="View">
                    <button
                        v-for="n in viewOptions" :key="'v'+n"
                        type="button" class="pgf-view-btn"
                        :class="{'pgf-active': currentCols === n}"
                        :aria-pressed="currentCols === n"
                        @click="setCols(n)"
                    >@{{ n }}</button>
                </div>

                {{-- Mobile: swipe dots (shows current col visually) --}}
                <div v-if="viewOptions.length > 1 && !searchOpen && !isDesktop" class="pgf-swipe-indicator" aria-hidden="true">
                    <span
                        v-for="n in viewOptions" :key="'d'+n"
                        class="pgf-swipe-dot"
                        :class="{ active: currentCols === n }"
                    ></span>
                </div>

                {{-- Search toggle --}}
                <button
                    v-if="!searchOpen"
                    type="button" class="pgf-search-toggle"
                    @click="openSearch"
                    :aria-label="searchPlaceholder"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>

                {{-- Filter pill --}}
                <div v-if="cfg.chipSources.length > 1 && !searchOpen" class="pgf-filter-pill-wrap">
                    <button type="button" class="pgf-filter-arrow" @click="cycleSource(-1)" aria-label="Previous filter">
                        <svg viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M3 9 L7 5 L11 9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <button type="button" class="pgf-filter-pill" :class="{'is-rotating':pillRotating}" :data-src="activeSource" @click="cycleSource(1)" aria-label="Filter by">
                        <span class="pgf-filter-pill-label">@{{ cfg.chipSourceLabels[activeSource] || activeSource }}</span>
                    </button>
                    <button type="button" class="pgf-filter-arrow" @click="cycleSource(1)" aria-label="Next filter">
                        <svg viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M3 5 L7 9 L11 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>

                {{-- Right icons --}}
                <div v-if="!searchOpen" class="pgf-bar-icons">
                    <v-dropdown :position="cfg.icons.dropdownPos">
                        <template v-slot:toggle>
                            <div class="pgf-bar-icon pgf-bar-lang" role="button" tabindex="0" :aria-label="cfg.icons.localeLabel">
                                <img :src="cfg.icons.localeFlag" :alt="cfg.icons.localeLabel" width="22" height="15">
                                <span class="lc-lang-code">@{{ cfg.icons.localeCode }}</span>
                            </div>
                        </template>
                        <template v-slot:content>
                            <div class="journal-scroll max-h-[500px] !p-0"><v-locale-switcher></v-locale-switcher></div>
                        </template>
                    </v-dropdown>

                    <a v-if="cfg.icons.compareEnabled" :href="cfg.icons.compareUrl" class="pgf-bar-icon" :aria-label="cfg.icons.compareLabel">
                        <span class="icon-compare" role="presentation"></span>
                    </a>

                    <span v-if="cfg.icons.cartEnabled" class="pgf-bar-icon">
                        <v-mini-cart>
                            <span class="icon-cart cursor-pointer" role="button" :aria-label="cfg.icons.profileLabel"></span>
                        </v-mini-cart>
                    </span>

                    <v-dropdown :position="cfg.icons.dropdownPos">
                        <template v-slot:toggle>
                            <span class="pgf-bar-icon" role="button" tabindex="0" :aria-label="cfg.icons.profileLabel">
                                <span class="icon-users"></span>
                            </span>
                        </template>
                        <template v-slot:content>
                            <div class="!p-0" style="min-width:240px">
                                <div v-if="!cfg.icons.isLoggedIn">
                                    <div class="grid gap-2.5 p-5 pb-0">
                                        <p class="text-xl font-dmserif">@{{ cfg.icons.welcomeGuest }}</p>
                                        <p class="text-sm">@{{ cfg.icons.dropdownText }}</p>
                                    </div>
                                    <p class="w-full mt-3 border border-zinc-200"></p>
                                    <div class="flex gap-4 m-5">
                                        <a :href="cfg.icons.signInUrl" class="block m-0 mx-auto text-base text-center primary-button w-max rounded-2xl px-7 max-md:rounded-lg ltr:ml-0 rtl:mr-0">@{{ cfg.icons.signInLabel }}</a>
                                        <a :href="cfg.icons.signUpUrl" class="block m-0 mx-auto text-base text-center border-2 secondary-button w-max rounded-2xl px-7 max-md:rounded-lg max-md:py-3 ltr:ml-0 rtl:mr-0">@{{ cfg.icons.signUpLabel }}</a>
                                    </div>
                                </div>
                                <div v-else>
                                    <div class="grid gap-2.5 p-5 pb-0">
                                        <p class="text-xl font-dmserif">@{{ cfg.icons.welcome }} @{{ cfg.icons.customerName }}</p>
                                        <p class="text-sm">@{{ cfg.icons.dropdownText }}</p>
                                    </div>
                                    <p class="w-full mt-3 border border-zinc-200"></p>
                                    <div class="mt-2.5 grid gap-1 pb-2.5">
                                        <a class="px-5 py-2 text-base cursor-pointer hover:bg-gray-100" :href="cfg.icons.profileUrl">@{{ cfg.icons.profileMenuLabel }}</a>
                                        <a class="px-5 py-2 text-base cursor-pointer hover:bg-gray-100" :href="cfg.icons.ordersUrl">@{{ cfg.icons.ordersLabel }}</a>
                                        <a v-if="cfg.icons.wishlistEnabled" class="px-5 py-2 text-base cursor-pointer hover:bg-gray-100" :href="cfg.icons.wishlistUrl">@{{ cfg.icons.wishlistLabel }}</a>
                                        <a class="px-5 py-2 text-base cursor-pointer hover:bg-gray-100" href="#" @click.prevent="logoutCustomer">@{{ cfg.icons.logoutLabel }}</a>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </v-dropdown>

                    <form ref="logoutForm" :action="cfg.icons.logoutUrl" method="POST" style="display:none">
                        <input type="hidden" name="_token" :value="cfg.icons.csrfToken">
                        <input type="hidden" name="_method" value="DELETE">
                    </form>
                </div>

                {{-- Full-width search overlay --}}
                <div v-if="searchOpen" class="pgf-search-overlay">
                    <span class="pgf-search-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </span>
                    <input type="search" ref="searchInput" v-model="searchTerm"
                        :placeholder="searchPlaceholder"
                        @input="onSearchInput"
                        @keydown.escape="clearSearch"
                        :aria-label="searchPlaceholder"
                    />
                    <button type="button" class="pgf-search-clear" @click="clearSearch" aria-label="Close search">×</button>
                </div>
            </div>

            {{-- ── Row 2 — chip strip ──
                 `data-src` drives the source-themed colour palette. The
                 `chipFlip*` classes are toggled by cycleSource() to play
                 the 3D rotate-out / rotate-in transition. --}}
            <div v-if="chipItems.length"
                 class="filter-scroll"
                 ref="chipNav"
                 :data-src="activeSource"
                 :class="{'is-flipping-out': chipFlipOut, 'is-flipping-in': chipFlipIn}">
                <button type="button" class="filter-pill" :class="{'active': chipValue === null}" @click="pickChip(null, $event)">@{{ allLabel }}</button>
                <button
                    v-for="it in chipItems" :key="it.id"
                    type="button" class="filter-pill"
                    :class="{'active': chipValue === it.id}"
                    @click="pickChip(it.id, $event)"
                >@{{ it.name }}</button>
            </div>
        </div>

        {{-- Grid — swipe left/right on mobile to change column count --}}
        <div
            v-if="currentCols > 1"
            ref="gridEl"
            class="pgf-grid"
            :class="['pgf-cols-'+currentCols, {'pgf-fading':replacing}, slideClass]"
            @touchstart.passive="onTouchStart"
            @touchend.passive="onTouchEnd"
        >
            <template v-if="loading && products.length === 0">
                <div v-for="i in 8" :key="'s'+i" class="pgf-card">
                    <div class="pgf-shimmer" style="aspect-ratio:1/1"></div>
                    <div style="padding:8px 10px 12px">
                        <div class="pgf-shimmer" style="height:11px;margin-bottom:6px"></div>
                        <div class="pgf-shimmer" style="height:11px;width:55%"></div>
                    </div>
                </div>
            </template>

            <a v-for="p in products" :key="p.id" :href="'/' + p.url_key" class="pgf-card">
                <div class="pgf-card-img">
                    <div class="pgf-badges">
                        <span v-if="p.on_sale" class="pgf-badge pgf-badge-sale">@{{ saleLabel }}</span>
                        <span v-else-if="p.is_new" class="pgf-badge pgf-badge-new">@{{ newLabel }}</span>
                        <span v-if="!p.is_saleable" class="pgf-badge pgf-badge-oos">@{{ oosLabel }}</span>
                    </div>
                    <img :src="p.base_image && p.base_image.medium_image_url" :alt="p.name" loading="lazy"/>
                </div>
                <div class="pgf-card-body">
                    <span class="pgf-card-name">@{{ p.name }}</span>
                    <div class="pgf-card-foot">
                        <span class="pgf-card-price" :class="p.on_sale ? 'pgf-card-sale' : ''">
                            @{{ priceOf(p) }}
                            <span v-if="p.on_sale && p.prices && p.prices.regular" class="pgf-card-orig">@{{ p.prices.regular.formatted_price }}</span>
                        </span>
                        <button type="button" class="pgf-cart-btn"
                            :disabled="!p.is_saleable || atCap(p)"
                            :aria-label="atCap(p) ? cfg.maxLabel : cfg.addToCartLabel"
                            @click.prevent.stop="addToCart(p)"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </a>
        </div>

        {{-- List mode --}}
        <div v-else class="pgf-list"
            @touchstart.passive="onTouchStart"
            @touchend.passive="onTouchEnd"
        >
            <template v-if="loading && products.length === 0">
                <div v-for="i in 4" :key="'ls'+i" class="pgf-list-card">
                    <div class="pgf-list-img pgf-shimmer"></div>
                    <div class="pgf-list-body">
                        <div class="pgf-shimmer" style="height:14px;width:80%"></div>
                        <div class="pgf-shimmer" style="height:14px;width:40%"></div>
                        <div class="pgf-shimmer" style="height:28px;width:120px;border-radius:8px"></div>
                    </div>
                </div>
            </template>

            <a v-for="p in products" :key="'l'+p.id" :href="'/' + p.url_key" class="pgf-list-card">
                <div class="pgf-list-img">
                    <div class="pgf-badges">
                        <span v-if="p.on_sale" class="pgf-badge pgf-badge-sale">@{{ saleLabel }}</span>
                        <span v-else-if="p.is_new" class="pgf-badge pgf-badge-new">@{{ newLabel }}</span>
                        <span v-if="!p.is_saleable" class="pgf-badge pgf-badge-oos">@{{ oosLabel }}</span>
                    </div>
                    <img :src="p.base_image && p.base_image.medium_image_url" :alt="p.name" loading="lazy"/>
                </div>
                <div class="pgf-list-body">
                    <p class="pgf-list-name">@{{ p.name }}</p>
                    <div class="pgf-list-price" :class="p.on_sale ? 'pgf-list-sale' : ''">
                        @{{ priceOf(p) }}
                        <span v-if="p.on_sale && p.prices && p.prices.regular" class="pgf-list-orig">@{{ p.prices.regular.formatted_price }}</span>
                    </div>
                    <button type="button" class="pgf-list-cart-btn" :disabled="!p.is_saleable || atCap(p)" @click.prevent.stop="addToCart(p)">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        @{{ atCap(p) ? cfg.maxLabel : cfg.addToCartLabel }}
                    </button>
                </div>
            </a>
        </div>

        <p v-if="!loading && products.length === 0" class="pgf-empty">@{{ emptyLabel }}</p>
        <div ref="sentinel" style="height:1px"></div>
        <div v-if="loadingMore" style="display:flex;justify-content:center;padding:20px 0"><span class="pgf-spin"></span></div>
    </div>
</script>

<script type="module">
(function () {
    const cfg = @json($config);

    app.component('{{ $componentName }}', {
        template: '#{{ $templateId }}',
        data() {
            return {
                cfg,
                activeSource: cfg.chipSources[0] || null,
                pillRotating: false,
                // Toggle the 3D flip animation on the chip-row when source
                // changes (Category ↔ Brand). chipFlipOut plays first to
                // rotate the old chips away, then chipItems are swapped and
                // chipFlipIn plays to rotate the new ones in.
                chipFlipOut: false, chipFlipIn: false,
                chipItems: [], chipValue: null,
                filterAttrId: null, filterItems: [], filterValue: null,
                categoryTree: [], attributeMap: {},
                products: [], nextUrl: null,
                loading: false, loadingMore: false, replacing: false,
                _filterTimer: null, _userInteracted: false,
                slideClass: '', _lastChipIndex: -1,
                colsMobile: cfg.defaultViewMobile,
                colsDesktop: cfg.defaultViewDesktop,
                viewOptions: [],
                isDesktop: typeof window !== 'undefined' && window.matchMedia && window.matchMedia('(min-width:1024px)').matches,
                _obs: null, _mq: null, _resizeHandler: null,
                _ticking: false,
                searchTerm: '', _searchTimer: null, searchOpen: false,
                // touch tracking for swipe-to-change-view
                _touchStartX: 0, _touchStartY: 0,
                allLabel:   @json(__('shop::app.home.product-grid-filter.all')),
                viewLabel:  @json(__('shop::app.home.product-grid-filter.view')),
                emptyLabel: @json(__('shop::app.home.product-grid-filter.empty')),
                saleLabel:  @json(__('shop::app.components.products.card.sale')),
                newLabel:   @json(__('shop::app.components.products.card.new')),
                oosLabel: 'Out of Stock', addedLabel: 'Added to cart',
                maxLabel: @json(__('shop::app.components.products.card.max-in-cart') ?: 'Max'),
                searchPlaceholder: @json(__('shop::app.components.layouts.header.mobile.search-text')),
            };
        },
        computed: {
            currentCols() { return this.isDesktop ? this.colsDesktop : this.colsMobile; },
        },
        async mounted() {
            this.updateViewOptions();
            this._publishBarHeight();

            if (window.matchMedia) {
                this._mq = window.matchMedia('(min-width:1024px)');
                const h = e => { this.isDesktop = e.matches; this.updateViewOptions(); };
                this._mq.addEventListener ? this._mq.addEventListener('change', h) : this._mq.addListener(h);
                this._mqHandler = h;
            }
            this._resizeHandler = () => { this.updateViewOptions(); this._publishBarHeight(); };
            window.addEventListener('resize', this._resizeHandler);
            window.addEventListener('scroll', this._onScroll, { passive: true });

            await this.loadSources();
            this.fetchProducts(true);
            this.$nextTick(this.initScroll);
        },
        beforeUnmount() {
            this._obs && this._obs.disconnect();
            if (this._mq && this._mqHandler) {
                this._mq.removeEventListener ? this._mq.removeEventListener('change', this._mqHandler) : this._mq.removeListener(this._mqHandler);
            }
            window.removeEventListener('resize', this._resizeHandler);
            window.removeEventListener('scroll', this._onScroll);
        },
        methods: {
            _publishBarHeight() {
                const el = this.$refs.bar;
                if (!el) return;
                document.documentElement.style.setProperty('--pgf-bar-h', (el.offsetHeight || 0) + 'px');
            },
            _onScroll() {
                if (!this._ticking) {
                    requestAnimationFrame(() => {
                        const h = this.$el.querySelector('.pgf-header');
                        if (h) { h.classList.toggle('is-stuck', h.getBoundingClientRect().top <= 1); }
                        this._ticking = false;
                    });
                    this._ticking = true;
                }
            },
            updateViewOptions() {
                const isMobile = window.innerWidth <= 768;
                let opts = isMobile ? cfg.viewOptionsMobile : cfg.viewOptionsDesktop;
                if (!opts || !opts.length) opts = cfg.viewOptions;
                this.viewOptions = opts;
                if (!this.viewOptions.includes(this.currentCols)) {
                    if (isMobile) this.colsMobile = this.viewOptions[0] || 1;
                    else          this.colsDesktop = this.viewOptions[0] || 1;
                }
            },
            setCols(n) {
                if (this.isDesktop) this.colsDesktop = n;
                else                this.colsMobile  = n;
                this._userInteracted = true;
                this.$nextTick(() => this._scrollToSection());
            },
            /* ── Swipe on the grid to cycle view columns ── */
            onTouchStart(e) {
                const t = e.touches[0];
                this._touchStartX = t.clientX;
                this._touchStartY = t.clientY;
            },
            onTouchEnd(e) {
                if (this.isDesktop) return; // desktop uses buttons
                const t = e.changedTouches[0];
                const dx = t.clientX - this._touchStartX;
                const dy = t.clientY - this._touchStartY;
                // only count horizontal swipes (more horizontal than vertical)
                if (Math.abs(dx) < 40 || Math.abs(dx) < Math.abs(dy) * 1.5) return;

                const opts = this.viewOptions;
                if (!opts || opts.length < 2) return;
                const cur = opts.indexOf(this.colsMobile);
                if (dx < 0) {
                    // swipe left → more columns (bigger number)
                    const next = opts[Math.min(cur + 1, opts.length - 1)];
                    if (next !== this.colsMobile) this.setCols(next);
                } else {
                    // swipe right → fewer columns (smaller number)
                    const prev = opts[Math.max(cur - 1, 0)];
                    if (prev !== this.colsMobile) this.setCols(prev);
                }
            },
            priceOf(p) {
                return (p.prices && p.prices.final && p.prices.final.formatted_price) || p.min_price || '';
            },
            atCap(p) {
                if (!p || p.available_qty == null) return false;
                return (parseInt(p.cart_qty || 0, 10) + 1) > parseInt(p.available_qty, 10);
            },
            withLocale(url) {
                return url + (url.includes('?') ? '&' : '?') + 'locale=' + encodeURIComponent(cfg.locale);
            },
            async loadSources() {
                try {
                    const [treeRes, attrRes] = await Promise.all([
                        this.$axios.get(this.withLocale(cfg.api.categoriesTree)),
                        this.$axios.get(this.withLocale(cfg.api.attributes)),
                    ]);
                    this.categoryTree = treeRes.data?.data || [];
                    const attrs = attrRes.data?.data || [];
                    this.attributeMap = Object.fromEntries(attrs.map(a => [a.code, a]));
                } catch (e) { console.warn('pgf sources', e); }
                await this.refreshChipItems();
                await this.refreshFilterItems();
            },
            async refreshChipItems() {
                if (!this.activeSource) { this.chipItems = []; return; }
                if (this.activeSource === 'category') {
                    const out = [];
                    const walk = nodes => (nodes||[]).forEach(n => { out.push({id:n.id,name:n.name}); if(n.children) walk(n.children); });
                    walk(this.categoryTree);
                    this.chipItems = out; return;
                }
                const attr = this.attributeMap[this.activeSource];
                if (!attr) { this.chipItems = []; return; }
                try {
                    const r = await this.$axios.get(this.withLocale(cfg.api.attributes + '/' + attr.id + '/options'));
                    this.chipItems = (r.data?.data||[]).map(o => ({id:o.id, name:o.name||o.label||o.admin_name||String(o.id)}));
                } catch(e) { this.chipItems = []; }
            },
            async refreshFilterItems() {
                this.filterItems = []; this.filterValue = null;
                if (!cfg.filterAttribute) return;
                const attr = this.attributeMap[cfg.filterAttribute];
                if (!attr) return;
                this.filterAttrId = attr.id;
                try {
                    const r = await this.$axios.get(this.withLocale(cfg.api.attributes + '/' + attr.id + '/options'));
                    this.filterItems = (r.data?.data||[]).map(o => ({id:o.id, name:o.name||o.label||o.admin_name||String(o.id)}));
                } catch(e) { this.filterItems = []; }
            },
            async pickSource(src) {
                this.activeSource = src; this.chipValue = null; this._lastChipIndex = -1;
                this._userInteracted = true;
                await this.refreshChipItems();
                this.scheduleFilterFetch();
            },
            cycleSource(dir) {
                const list = this.cfg.chipSources;
                if (!list || list.length < 2) return;
                const next = ((list.indexOf(this.activeSource) + dir) % list.length + list.length) % list.length;
                if (list[next] === this.activeSource) return;

                // Run the source-pill rotation AND the chip-row flip in
                // parallel so the whole bar reads as one synchronized
                // transition. Out-phase plays 220ms; we swap chipItems at
                // the apex (when chips are invisible) and play in-phase.
                this.pillRotating = true;
                this.chipFlipOut = true;
                this.chipFlipIn  = false;

                setTimeout(() => {
                    // Swap source — refreshChipItems() updates this.chipItems
                    // synchronously for `category`, asynchronously for attr
                    // sources. Either way, kick off the in-flip immediately;
                    // if the new chips arrive a frame late, they slot in
                    // mid-animation which still looks good.
                    this.pickSource(list[next]);
                    this.pillRotating = false;
                    this.chipFlipOut  = false;
                    this.chipFlipIn   = true;
                    setTimeout(() => { this.chipFlipIn = false; }, 340);
                }, 220);
            },
            pickChip(id, ev) {
                const newIndex = id === null ? -1 : this.chipItems.findIndex(c => c.id === id);
                this.slideClass = newIndex >= this._lastChipIndex ? 'pgf-slide-right' : 'pgf-slide-left';
                this._lastChipIndex = newIndex;
                this.chipValue = id; this._userInteracted = true;
                if (ev?.target && this.$refs.chipNav) {
                    const s = this.$refs.chipNav;
                    const btn = ev.target.closest('button') || ev.target;
                    s.scrollTo({ left: btn.offsetLeft - s.clientWidth/2 + btn.offsetWidth/2, behavior:'smooth' });
                }
                this.scheduleFilterFetch();
            },
            pickFilter(id) { this.filterValue = id; this._userInteracted = true; this.scheduleFilterFetch(); },
            _sectionTarget() {
                const wrap = this.$refs.root || this.$el;
                const naturalOffset = window.scrollY + wrap.getBoundingClientRect().top;
                let stickyTop = 0;
                const bar = this.$refs.bar;
                if (bar) {
                    const cs = window.getComputedStyle(bar);
                    if (cs.position === 'sticky') { const t = parseFloat(cs.top); if (!isNaN(t)) stickyTop = t; }
                }
                return Math.max(0, naturalOffset - stickyTop);
            },
            _scrollToSection() {
                const bar = this.$refs.bar;
                if (bar) document.documentElement.style.setProperty('--pgf-bar-h', (bar.offsetHeight||0) + 'px');
                if (typeof window.lcLockScroll === 'function') window.lcLockScroll(900);
                const target = this._sectionTarget();
                try { window.scrollTo({ top: target, behavior:'smooth' }); } catch(_) { window.scrollTo(0, target); }
            },
            scrollBarToTop() { this._userInteracted = true; this._scrollToSection(); },
            scheduleFilterFetch() {
                if (this._filterTimer) clearTimeout(this._filterTimer);
                this._filterTimer = setTimeout(() => this.fetchProducts(true), 150);
            },
            buildUrl() {
                const p = new URLSearchParams({ limit: cfg.perPage, status: 1, visible_individually: 1 });
                if (this.activeSource === 'category' && this.chipValue !== null) p.set('category_id', this.chipValue);
                else if (this.activeSource && this.activeSource !== 'category' && this.chipValue !== null) p.set(this.activeSource, this.chipValue);
                if (cfg.filterAttribute && this.filterValue !== null) p.set(cfg.filterAttribute, this.filterValue);
                const term = (this.searchTerm||'').trim();
                if (term) p.set('name', term);
                return this.withLocale(cfg.api.products + '?' + p.toString());
            },
            openSearch() {
                this.searchOpen = true;
                this.$nextTick(() => { if (this.$refs.searchInput) this.$refs.searchInput.focus(); });
            },
            closeSearch() { this.searchOpen = false; },
            onSearchInput() {
                if (this._searchTimer) clearTimeout(this._searchTimer);
                this._userInteracted = true;
                if ((this.searchTerm || '').trim()) this.chipValue = null;
                this._searchTimer = setTimeout(() => this.fetchProducts(true), 350);
            },
            clearSearch() {
                this.searchTerm = ''; this._userInteracted = true;
                this.searchOpen = false; this.fetchProducts(true);
            },
            async addToCart(p) {
                if (!p || !p.is_saleable) return;
                try {
                    const fd = new FormData();
                    fd.append('product_id', p.id); fd.append('quantity', 1); fd.append('is_buy_now', 0);
                    const r = await this.$axios.post(this.withLocale('{{ route("shop.api.checkout.cart.store") }}'), fd);
                    if (this.$emitter) {
                        this.$emitter.emit('add-flash', { type:'success', message: r?.data?.message || this.addedLabel, meta:{ silent:true }});
                        this.$emitter.emit('update-mini-cart', r?.data?.data);
                    }
                } catch(e) {
                    if (this.$emitter) this.$emitter.emit('add-flash', { type:'warning', message: e?.response?.data?.message||'Error' });
                }
            },
            async fetchProducts(reset) {
                if (reset) { this.replacing = true; this.nextUrl = null; }
                else { if (this.loadingMore || !this.nextUrl) return; this.loadingMore = true; }
                const url = reset ? this.buildUrl() : this.nextUrl;
                try {
                    const r = await this.$axios.get(url);
                    const d = r.data?.data || [], l = r.data?.links || {};
                    this.products = reset ? d : [...this.products, ...d];
                    this.nextUrl = l.next || null;
                } catch(e) { console.warn('pgf products', e); }
                finally { this.replacing = false; this.loading = false; this.loadingMore = false; }
                if (reset && this._userInteracted) {
                    this.$nextTick(() => requestAnimationFrame(() => this._scrollToSection()));
                    if (this.slideClass) setTimeout(() => { this.slideClass = ''; }, 400);
                }
            },
            initScroll() {
                if (!this.$refs.sentinel) return;
                this._obs = new IntersectionObserver(entries => {
                    if (entries[0].isIntersecting && this.nextUrl && !this.loadingMore) this.fetchProducts(false);
                }, { rootMargin:'300px' });
                this._obs.observe(this.$refs.sentinel);
            },
            logoutCustomer() { if (this.$refs.logoutForm) this.$refs.logoutForm.submit(); },
        },
    });
})();
</script>
@endPushOnce
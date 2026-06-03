<!--
    This code needs to be refactored to reduce the amount of PHP in the Blade
    template as much as possible.
-->
@php
    $showCompare = (bool) core()->getConfigData('catalog.products.settings.compare_option');

    $showWishlist = (bool) core()->getConfigData('customer.settings.wishlist.wishlist_option');

    $hideMobileMenuCfg = core()->getConfigData('general.design.header.hide_mobile_menu');
    $showMobileMenu = ! ($hideMobileMenuCfg === '1' || $hideMobileMenuCfg === 1 || $hideMobileMenuCfg === true);
@endphp

<div class="flex flex-wrap px-4 pt-4 pb-3 shadow-sm lg:hidden">

    <div class="flex items-center justify-between w-full lc-mobile-nav-row">
        <!-- Left: Back + Drawer (hamburger) -->
        <div class="flex items-center gap-x-1 flex-shrink-0">
            <button
                type="button"
                class="lc-back-btn"
                aria-label="Back"
                onclick="if(window.history.length > 1) { window.history.back(); } else { window.location.href = '{{ route('shop.home.index') }}'; }"
                style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border:0;background:transparent;cursor:pointer;-webkit-tap-highlight-color:transparent;"
            >
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
            </button>

            {!! view_render_event('bagisto.shop.components.layouts.header.mobile.drawer.before') !!}

            @if ($showMobileMenu)
                <!-- Drawer -->
                <v-mobile-drawer></v-mobile-drawer>
            @endif

            {!! view_render_event('bagisto.shop.components.layouts.header.mobile.drawer.after') !!}
        </div>

        <!-- Center: Logo -->
        <div class="flex-1 flex items-center justify-center min-w-0 px-2">
            {!! view_render_event('bagisto.shop.components.layouts.header.mobile.logo.before') !!}

            <a
                href="{{ route('shop.home.index') }}"
                class="max-h-[30px] inline-flex items-center"
                aria-label="@lang('shop::app.components.layouts.header.mobile.bagisto')"
            >
                <img
                    src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                    alt="{{ config('app.name') }}"
                    width="131"
                    height="29"
                    style="max-height:30px;width:auto;object-fit:contain;"
                >
            </a>

            {!! view_render_event('bagisto.shop.components.layouts.header.mobile.logo.after') !!}
        </div>

        <!-- Right Navigation -->
        <div>
            <div class="flex items-center gap-x-3">
                {!! view_render_event('bagisto.shop.components.layouts.header.mobile.compare.before') !!}

                @if($showCompare)
                    <a
                        href="{{ route('shop.compare.index') }}"
                        aria-label="@lang('shop::app.components.layouts.header.mobile.compare')"
                    >
                        <span class="text-2xl cursor-pointer icon-compare"></span>
                    </a>
                @endif

                {!! view_render_event('bagisto.shop.components.layouts.header.mobile.compare.after') !!}

                {{-- ── Search button (all screens) ── --}}
              <!--   <button
                    type="button"
                    onclick="openSearchModal()"
                    aria-label="Search"
                    style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:rgba(0,0,0,.06);border:none;cursor:pointer;-webkit-tap-highlight-color:transparent;"
                >
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                        <circle cx="10.5" cy="10.5" r="6.5"/>
                        <line x1="15.5" y1="15.5" x2="20.5" y2="20.5"/>
                    </svg>
                </button> -->

                {{-- ── Lang / Currency pill (all screens) ── --}}
                @php
                    $mhLocale   = app()->getLocale();
                    $mhCurrency = core()->getCurrentCurrencyCode();
                    $mhChannel  = core()->getCurrentChannel();
                    $mhLoc      = $mhChannel->locales()->where('code', $mhLocale)->first();
                @endphp
                <button
                    type="button"
                    onclick="if(typeof lcOpenLangSheet==='function')lcOpenLangSheet()"
                    aria-label="Language / Currency"
                    style="display:inline-flex;align-items:center;gap:5px;padding:5px 10px;border-radius:99px;background:#4f46e5;color:#fff;font-size:11px;font-weight:700;border:none;cursor:pointer;white-space:nowrap;-webkit-tap-highlight-color:transparent;letter-spacing:.03em;line-height:1;"
                >
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10A15.3 15.3 0 0 1 12 2z"/></svg>
                    {{ strtoupper($mhLocale) }} | {{ strtoupper($mhCurrency) }}
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
                </button>

                <!-- Account dropdown (tablet only — phones use bottom nav) -->
                <div class="max-md:hidden">
                    <x-shop::dropdown position="bottom-{{ core()->getCurrentLocale()->direction === 'ltr' ? 'right' : 'left' }}">
                        <x-slot:toggle>
                            <span class="text-2xl cursor-pointer icon-users"></span>
                        </x-slot>

                        <!-- Guest Dropdown -->
                        @guest('customer')
                            <x-slot:content>
                                <div class="grid gap-2.5">
                                    <p class="text-xl font-dmserif">
                                        @lang('shop::app.components.layouts.header.mobile.welcome-guest')
                                    </p>

                                    <p class="text-sm">
                                        @lang('shop::app.components.layouts.header.mobile.dropdown-text')
                                    </p>
                                </div>

                                <p class="w-full mt-3 border border-zinc-200"></p>

                                {!! view_render_event('bagisto.shop.components.layouts.header.mobile.index.customers_action.before') !!}

                                <div class="flex gap-4 mt-6">
                                    {!! view_render_event('bagisto.shop.components.layouts.header.mobile.index.sign_in_button.before') !!}

                                    <a
                                        href="{{ route('shop.customer.session.create') }}"
                                        class="block py-4 m-0 mx-auto text-base font-medium text-center text-white cursor-pointer w-max rounded-2xl bg-navyBlue px-7 ltr:ml-0 rtl:mr-0"
                                    >
                                        @lang('shop::app.components.layouts.header.mobile.sign-in')
                                    </a>

                                    <a
                                        href="{{ route('shop.customers.register.index') }}"
                                        class="m-0 mx-auto block w-max cursor-pointer rounded-2xl border-2 border-navyBlue bg-white px-7 py-3.5 text-center text-base font-medium text-navyBlue ltr:ml-0 rtl:mr-0"
                                    >
                                        @lang('shop::app.components.layouts.header.mobile.sign-up')
                                    </a>

                                    {!! view_render_event('bagisto.shop.components.layouts.header.mobile.index.sign_in_button.after') !!}
                                </div>

                                {!! view_render_event('bagisto.shop.components.layouts.header.mobile.index.customers_action.after') !!}
                            </x-slot>
                        @endguest

                        <!-- Customers Dropdown -->
                        @auth('customer')
                            <x-slot:content class="!p-0">
                                <div class="grid gap-2.5 p-5 pb-0">
                                    <p class="text-xl font-dmserif" v-pre>
                                        @lang('shop::app.components.layouts.header.mobile.welcome')’
                                        {{ auth()->guard('customer')->user()->first_name }}
                                    </p>

                                    <p class="text-sm">
                                        @lang('shop::app.components.layouts.header.mobile.dropdown-text')
                                    </p>
                                </div>

                                <p class="w-full mt-3 border border-zinc-200"></p>

                                <div class="mt-2.5 grid gap-1 pb-2.5">
                                    {!! view_render_event('bagisto.shop.components.layouts.header.mobile.index.profile_dropdown.links.before') !!}

                                    <a
                                        class="px-5 py-2 text-base cursor-pointer"
                                        href="{{ route('shop.customers.account.profile.index') }}"
                                    >
                                        @lang('shop::app.components.layouts.header.mobile.profile')
                                    </a>

                                    <a
                                        class="px-5 py-2 text-base cursor-pointer"
                                        href="{{ route('shop.customers.account.orders.index') }}"
                                    >
                                        @lang('shop::app.components.layouts.header.mobile.orders')
                                    </a>

                                    @if ($showWishlist)
                                        <a
                                            class="px-5 py-2 text-base cursor-pointer"
                                            href="{{ route('shop.customers.account.wishlist.index') }}"
                                        >
                                            @lang('shop::app.components.layouts.header.mobile.wishlist')
                                        </a>
                                    @endif

                                    <!--Customers logout-->
                                    @auth('customer')
                                        <x-shop::form
                                            method="DELETE"
                                            action="{{ route('shop.customer.session.destroy') }}"
                                            id="customerLogout"
                                        />

                                        <a
                                            class="px-5 py-2 text-base cursor-pointer"
                                            href="{{ route('shop.customer.session.destroy') }}"
                                            onclick="event.preventDefault(); document.getElementById('customerLogout').submit();"
                                        >
                                            @lang('shop::app.components.layouts.header.mobile.logout')
                                        </a>
                                    @endauth

                                    {!! view_render_event('bagisto.shop.components.layouts.header.mobile.index.profile_dropdown.links.after') !!}
                                </div>
                            </x-slot>
                        @endauth
                    </x-shop::dropdown>
                </div>


            </div>
        </div>
    </div>
</div>

@pushOnce('scripts')

    <script type="text/x-template" id="v-mobile-drawer-template">
        <x-shop::drawer
            position="left"
            width="100%"
            @close="onDrawerClose"
        >
            <x-slot:toggle>
                <span class="text-2xl cursor-pointer icon-hamburger"></span>
            </x-slot>

            <x-slot:header>
                <div class="flex items-center justify-between">
                    <a href="{{ route('shop.home.index') }}">
                        <img
                            src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                            alt="{{ config('app.name') }}"
                            width="131"
                            height="29"
                        >
                    </a>
                </div>
            </x-slot>

            <x-slot:content class="!p-0">
                <!-- Account Profile Hero Section -->
                <div class="p-4 border-b border-zinc-200">
                    <div class="grid grid-cols-[auto_1fr] items-center gap-4 rounded-xl border border-zinc-200 p-2.5">
                        <div>
                            <img
                                src="{{ auth()->user()?->image_url ??  bagisto_asset('images/user-placeholder.png') }}"
                                class="h-[60px] w-[60px] rounded-full max-md:rounded-full"
                            >
                        </div>

                        @guest('customer')
                            <a
                                href="{{ route('shop.customer.session.create') }}"
                                class="flex text-base font-medium"
                            >
                                @lang('shop::app.components.layouts.header.mobile.login')

                                <i class="icon-double-arrow text-2xl ltr:ml-2.5 rtl:mr-2.5"></i>
                            </a>
                        @endguest

                        @auth('customer')
                            <div
                                class="flex flex-col justify-between gap-2.5 max-md:gap-0"
                                v-pre
                            >
                                <p class="text-2xl break-all font-mediums max-md:text-xl">Hello! {{ auth()->user()?->first_name }}</p>

                                <p class="no-underline text-zinc-500 max-md:text-sm">{{ auth()->user()?->email }}</p>
                            </div>
                        @endauth
                    </div>
                </div>

                {!! view_render_event('bagisto.shop.components.layouts.header.mobile.drawer.categories.before') !!}

                <!-- Mobile category view -->
                <v-mobile-category ref="mobileCategory"></v-mobile-category>

                {!! view_render_event('bagisto.shop.components.layouts.header.mobile.drawer.categories.after') !!}
            </x-slot>

            <x-slot:footer>
                <!-- Localization & Currency Section -->
                @if(core()->getCurrentChannel()->locales()->count() > 1 || core()->getCurrentChannel()->currencies()->count() > 1 )
                    <div class="fixed bottom-0 z-10 grid w-full max-w-full grid-cols-[1fr_auto_1fr] items-center justify-items-center border-t border-zinc-200 bg-white px-5 ltr:left-0 rtl:right-0">
                        <!-- Filter Drawer -->
                        <x-shop::drawer
                            position="bottom"
                            width="100%"
                        >
                            <!-- Drawer Toggler -->
                            <x-slot:toggle>
                                <div
                                    class="flex cursor-pointer items-center gap-x-2.5 px-2.5 py-3.5 text-lg font-medium uppercase max-md:py-3 max-sm:text-base"
                                    role="button"
                                    v-pre
                                >
                                    {{ core()->getCurrentCurrency()->symbol . ' ' . core()->getCurrentCurrencyCode() }}
                                </div>
                            </x-slot>

                            <!-- Drawer Header -->
                            <x-slot:header>
                                <div class="flex items-center justify-between">
                                    <p class="text-lg font-semibold">
                                        @lang('shop::app.components.layouts.header.mobile.currencies')
                                    </p>
                                </div>
                            </x-slot>

                            <!-- Drawer Content -->
                            <x-slot:content class="!px-0">
                                <div
                                    class="overflow-auto"
                                    :style="{ height: getCurrentScreenHeight }"
                                >
                                    <v-currency-switcher></v-currency-switcher>
                                </div>
                            </x-slot>
                        </x-shop::drawer>

                        <!-- Seperator -->
                        <span class="h-5 w-0.5 bg-zinc-200"></span>

                        <!-- Sort Drawer -->
                        <x-shop::drawer
                            position="bottom"
                            width="100%"
                        >
                            <!-- Drawer Toggler -->
                            <x-slot:toggle>
                                <div
                                    class="flex cursor-pointer items-center gap-x-2.5 px-2.5 py-3.5 text-lg font-medium uppercase max-md:py-3 max-sm:text-base"
                                    role="button"
                                    v-pre
                                >
                                    <img
                                        src="{{ ! empty(core()->getCurrentLocale()->logo_url)
                                                ? core()->getCurrentLocale()->logo_url
                                                : bagisto_asset('images/default-language.svg')
                                            }}"
                                        class="h-full"
                                        alt="Default locale"
                                        width="24"
                                        height="16"
                                    />

                                    {{ core()->getCurrentChannel()->locales()->orderBy('name')->where('code', app()->getLocale())->value('name') }}
                                </div>
                            </x-slot>

                            <!-- Drawer Header -->
                            <x-slot:header>
                                <div class="flex items-center justify-between">
                                    <p class="text-lg font-semibold">
                                        @lang('shop::app.components.layouts.header.mobile.locales')
                                    </p>
                                </div>
                            </x-slot>

                            <!-- Drawer Content -->
                            <x-slot:content class="!px-0">
                                <div
                                    class="overflow-auto"
                                    :style="{ height: getCurrentScreenHeight }"
                                >
                                    <v-locale-switcher></v-locale-switcher>
                                </div>
                            </x-slot>
                        </x-shop::drawer>
                    </div>
                @endif
            </x-slot>
        </x-shop::drawer>
    </script>

    <script
        type="text/x-template"
        id="v-mobile-category-template"
    >
        <!-- Wrapper with transition effects -->
        <div class="relative h-full overflow-hidden">
            <!-- Sliding container -->
            <div
                class="flex h-full transition-transform duration-300"
                :class="{
                    'ltr:translate-x-0 rtl:translate-x-0': currentViewLevel !== 'third',
                    'ltr:-translate-x-full rtl:translate-x-full': currentViewLevel === 'third'
                }"
            >
                <!-- First level view -->
                <div class="flex-shrink-0 w-full h-full px-6 overflow-auto">
                    <div class="py-4">
                        <div
                            v-for="category in categories"
                            :key="category.id"
                            :class="{'mb-2': category.children && category.children.length}"
                        >
                            <div class="flex items-center justify-between py-2 transition-colors duration-200 cursor-pointer">
                                <a :href="category.url" class="text-base font-medium text-black">
                                    @{{ category.name }}
                                </a>
                            </div>

                            <!-- Second Level Categories -->
                            <div v-if="category.children && category.children.length" >
                                <div
                                    v-for="secondLevelCategory in category.children"
                                    :key="secondLevelCategory.id"
                                >
                                    <div
                                        class="flex items-center justify-between py-2 transition-colors duration-200 cursor-pointer"
                                        @click="showThirdLevel(secondLevelCategory, category, $event)"
                                    >
                                        <a :href="secondLevelCategory.url" class="text-sm font-normal">
                                            @{{ secondLevelCategory.name }}
                                        </a>

                                        <span
                                            v-if="secondLevelCategory.children && secondLevelCategory.children.length"
                                            class="icon-arrow-right rtl:icon-arrow-left"
                                        ></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Third level view -->
                <div
                    class="flex-shrink-0 w-full h-full"
                    v-if="currentViewLevel === 'third'"
                >
                    <div class="px-6 py-4 border-b border-gray-200">
                        <button
                            @click="goBackToMainView"
                            class="flex items-center justify-center gap-2 focus:outline-none"
                            aria-label="Go back"
                        >
                            <span class="text-lg icon-arrow-left rtl:icon-arrow-right"></span>
                            <div class="text-base font-medium text-black">
                                @lang('shop::app.components.layouts.header.mobile.back-button')
                            </div>
                        </button>
                    </div>

                    <!-- Third Level Content -->
                    <div class="px-6 py-4">
                        <div
                            v-for="thirdLevelCategory in currentSecondLevelCategory?.children"
                            :key="thirdLevelCategory.id"
                            class="mb-2"
                        >
                            <a
                                :href="thirdLevelCategory.url"
                                class="block py-2 text-sm transition-colors duration-200"
                            >
                                @{{ thirdLevelCategory.name }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-mobile-category', {
            template: '#v-mobile-category-template',

            data() {
                return  {
                    categories: [],
                    currentViewLevel: 'main',
                    currentSecondLevelCategory: null,
                    currentParentCategory: null
                }
            },

            mounted() {
                this.initCategories();
            },

            computed: {
                getCurrentScreenHeight() {
                    return window.innerHeight - (window.innerWidth < 920 ? 61 : 0) + 'px';
                },
            },

            methods: {
                initCategories() {
                    // Versioned + TTL'd cache. Must stay in sync with the desktop header
                    // (components/layouts/header/desktop/bottom.blade.php) — both share the
                    // 'categories_v' localStorage key and CATEGORY_CACHE_VERSION constant.
                    const CATEGORY_CACHE_VERSION = 2; // bumped 2026-05-24 for ￥ price rename
                    const CATEGORY_CACHE_TTL = 60 * 60 * 1000; // 1 hour

                    try {
                        const raw = localStorage.getItem('categories_v');

                        if (raw) {
                            const cache = JSON.parse(raw);

                            if (
                                cache
                                && cache.v === CATEGORY_CACHE_VERSION
                                && Array.isArray(cache.data)
                                && (Date.now() - (cache.t || 0)) < CATEGORY_CACHE_TTL
                            ) {
                                this.categories = cache.data;
                                this.isLoading = false;
                                return;
                            }
                        }

                        localStorage.removeItem('categories');
                    } catch (e) {}

                    this.getCategories(CATEGORY_CACHE_VERSION);
                },
                getCategories(version) {
                    this.$axios.get("{{ route('shop.api.categories.tree') }}")
                        .then(response => {
                            this.categories = response.data.data;
                            try {
                                localStorage.setItem('categories_v', JSON.stringify({
                                    v: version,
                                    t: Date.now(),
                                    data: this.categories,
                                }));
                            } catch (e) {}
                        })
                        .catch(error => {
                            console.log(error);
                        });
                },

                showThirdLevel(secondLevelCategory, parentCategory, event) {
                    if (secondLevelCategory.children && secondLevelCategory.children.length) {
                        this.currentSecondLevelCategory = secondLevelCategory;
                        this.currentParentCategory = parentCategory;
                        this.currentViewLevel = 'third';

                        if (event) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                    }
                },

                goBackToMainView() {
                    this.currentViewLevel = 'main';
                }
            },
        });

        app.component('v-mobile-drawer', {
            template: '#v-mobile-drawer-template',

            methods: {
                onDrawerClose() {
                    this.$refs.mobileCategory.currentViewLevel = 'main';
                }
            },
        });
    </script>
@endPushOnce

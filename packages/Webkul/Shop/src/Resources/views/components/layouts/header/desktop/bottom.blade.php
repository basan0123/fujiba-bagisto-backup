{!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.before') !!}

@php
    $lcShowMenuCat    = (bool) (core()->getConfigData('general.design.header.show_menu_category') ?? true);
    $lcSearchPlaceholder = core()->getConfigData('general.design.header.search_placeholder') ?: trans('shop::app.components.layouts.header.search');
@endphp

<div class="flex min-h-[44px] w-full items-center gap-6 border border-b border-l-0 border-r-0 border-t-0 px-[60px] max-1180:px-8">
    <!--
        This section will provide categories for the first, second, and third levels. If
        additional levels are required, users can customize them according to their needs.
    -->
    <!-- Left Nagivation Section -->
    <div class="flex items-center gap-x-10 max-[1180px]:gap-x-5 flex-shrink-0">
        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.logo.before') !!}

        <a
            href="{{ route('shop.home.index') }}"
            aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.bagisto')"
        >
            <img
                src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                width="131"
                height="29"
                alt="{{ config('app.name') }}"
            >
        </a>

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.logo.after') !!}

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.category.before') !!}

        @if ($lcShowMenuCat)
        <v-desktop-category>
            <div class="flex items-center gap-5">
                <span
                    class="w-20 h-6 rounded shimmer"
                    role="presentation"
                ></span>

                <span
                    class="w-20 h-6 rounded shimmer"
                    role="presentation"
                ></span>

                <span
                    class="w-20 h-6 rounded shimmer"
                    role="presentation"
                ></span>
            </div>
        </v-desktop-category>
        @endif

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.category.after') !!}
    </div>

    <!-- Spacer so search/lang/etc are right-aligned -->
    <div class="flex-1"></div>

    {{-- Collapsible top-nav search. Plain JS only (no Vue/Alpine
         directives) so it can't interfere with Bagisto's root Vue
         in-DOM compilation that walks the whole #app subtree. Default
         state hides the form; clicking the button reveals it. Empty
         blur or Escape collapses it again. --}}
    <div id="lc-header-search" class="relative flex items-center">
        <button
            type="button"
            id="lc-header-search-btn"
            class="flex h-9 w-9 items-center justify-center rounded-md text-zinc-700 hover:bg-zinc-100 transition-colors"
            aria-label="{{ $lcSearchPlaceholder }}"
        >
            <span class="icon-search text-xl"></span>
        </button>

        <form
            action="{{ route('shop.search.index') }}"
            method="GET"
            role="search"
            id="lc-header-search-form"
            class="overflow-hidden transition-[width] duration-200 ease-out"
            style="width:0;display:none"
        >
            <div class="relative w-full">
                <input
                    type="search"
                    name="query"
                    id="lc-header-search-input"
                    placeholder="{{ $lcSearchPlaceholder }}"
                    aria-label="{{ $lcSearchPlaceholder }}"
                    class="w-full h-9 rounded-md border border-gray-200 pl-10 pr-3 text-base focus:outline-none focus:ring-2 focus:ring-navyBlue/30"
                    style="background:rgba(0,0,0,.03);"
                />
                <span class="icon-search text-xl absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-500"></span>
            </div>
        </form>
    </div>
    @pushOnce('scripts')
    <script>
        (function () {
            function wire() {
                var btn  = document.getElementById('lc-header-search-btn');
                var form = document.getElementById('lc-header-search-form');
                var inp  = document.getElementById('lc-header-search-input');
                if (!btn || !form || !inp) return;
                btn.addEventListener('click', function () {
                    form.style.display = 'block';
                    form.style.width   = '320px';
                    btn.style.display  = 'none';
                    setTimeout(function () { inp.focus(); }, 0);
                });
                inp.addEventListener('blur', function () {
                    if (!inp.value) {
                        form.style.display = 'none';
                        form.style.width   = '0';
                        btn.style.display  = 'inline-flex';
                    }
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && document.activeElement === inp) {
                        inp.value = '';
                        form.style.display = 'none';
                        form.style.width   = '0';
                        btn.style.display  = 'inline-flex';
                    }
                });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', wire);
            } else {
                wire();
            }
        })();
    </script>
    @endPushOnce

    <!-- Right Nagivation Section -->
    <div class="lc-header-right flex items-center gap-x-3 flex-shrink-0">

        <!-- Right Navigation Links -->
        <div class="flex items-center gap-x-2">

            <!-- Locale Switcher (icon only) — uses the same flag image and
                 v-locale-switcher dropdown that the top header uses. -->
            <x-shop::dropdown position="bottom-{{ core()->getCurrentLocale()->direction === 'ltr' ? 'right' : 'left' }}">
                <x-slot:toggle>
                    <div
                        class="flex cursor-pointer items-center gap-1.5 h-9 px-2 rounded-md hover:bg-zinc-100 transition-colors"
                        role="button"
                        tabindex="0"
                        aria-label="@lang('shop::app.components.layouts.header.desktop.top.default-locale')"
                    >
                        <img
                            src="{{ ! empty(core()->getCurrentLocale()->logo_url)
                                    ? core()->getCurrentLocale()->logo_url
                                    : bagisto_asset('images/default-language.svg')
                                }}"
                            alt="@lang('shop::app.components.layouts.header.desktop.top.default-locale')"
                            width="20"
                            height="14"
                        />
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-zinc-700">
                            {{ strtoupper(app()->getLocale()) }}
                        </span>
                    </div>
                </x-slot>
                <x-slot:content class="journal-scroll max-h-[500px] !p-0">
                    <v-locale-switcher></v-locale-switcher>
                </x-slot>
            </x-shop::dropdown>

            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.compare.before') !!}

            <!-- Compare -->
            @if(core()->getConfigData('catalog.products.settings.compare_option'))
                <a
                    href="{{ route('shop.compare.index') }}"
                    aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.compare')"
                >
                    <span
                        class="inline-block text-lg cursor-pointer icon-compare"
                        role="presentation"
                    ></span>
                </a>
            @endif

            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.compare.after') !!}

            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.mini_cart.before') !!}

            <!-- Mini cart -->
            @if(core()->getConfigData('sales.checkout.shopping_cart.cart_page'))
                @include('shop::checkout.cart.mini-cart')
            @endif

            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.mini_cart.after') !!}

            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile.before') !!}

            <!-- user profile -->
            <x-shop::dropdown position="bottom-{{ core()->getCurrentLocale()->direction === 'ltr' ? 'right' : 'left' }}">
                <x-slot:toggle>
                    <span
                        class="inline-block text-lg cursor-pointer icon-users"
                        role="button"
                        aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.profile')"
                        tabindex="0"
                    ></span>
                </x-slot>

                <!-- Guest Dropdown -->
                @guest('customer')
                    <x-slot:content>
                        <div class="grid gap-2.5">
                            <p class="text-xl font-dmserif">
                                @lang('shop::app.components.layouts.header.desktop.bottom.welcome-guest')
                            </p>

                            <p class="text-sm">
                                @lang('shop::app.components.layouts.header.desktop.bottom.dropdown-text')
                            </p>
                        </div>

                        <p class="w-full mt-3 border border-zinc-200"></p>

                        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.customers_action.before') !!}

                        <div class="flex gap-4 mt-6">
                            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.sign_in_button.before') !!}

                            <a
                                href="{{ route('shop.customer.session.create') }}"
                                class="block m-0 mx-auto text-base text-center primary-button w-max rounded-2xl px-7 max-md:rounded-lg ltr:ml-0 rtl:mr-0"
                            >
                                @lang('shop::app.components.layouts.header.desktop.bottom.sign-in')
                            </a>

                            <a
                                href="{{ route('shop.customers.register.index') }}"
                                class="block m-0 mx-auto text-base text-center border-2 secondary-button w-max rounded-2xl px-7 max-md:rounded-lg max-md:py-3 ltr:ml-0 rtl:mr-0"
                            >
                                @lang('shop::app.components.layouts.header.desktop.bottom.sign-up')
                            </a>

                            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.sign_up_button.after') !!}
                        </div>

                        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.customers_action.after') !!}
                    </x-slot>
                @endguest

                <!-- Customers Dropdown -->
                @auth('customer')
                    <x-slot:content class="!p-0">
                        <div class="grid gap-2.5 p-5 pb-0">
                            <p class="text-xl font-dmserif" v-pre>
                                @lang('shop::app.components.layouts.header.desktop.bottom.welcome')’
                                {{ auth()->guard('customer')->user()->first_name }}
                            </p>

                            <p class="text-sm">
                                @lang('shop::app.components.layouts.header.desktop.bottom.dropdown-text')
                            </p>
                        </div>

                        <p class="w-full mt-3 border border-zinc-200"></p>

                        <div class="mt-2.5 grid gap-1 pb-2.5">
                            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile_dropdown.links.before') !!}

                            <a
                                class="px-5 py-2 text-base cursor-pointer hover:bg-gray-100"
                                href="{{ route('shop.customers.account.profile.index') }}"
                            >
                                @lang('shop::app.components.layouts.header.desktop.bottom.profile')
                            </a>

                            <a
                                class="px-5 py-2 text-base cursor-pointer hover:bg-gray-100"
                                href="{{ route('shop.customers.account.orders.index') }}"
                            >
                                @lang('shop::app.components.layouts.header.desktop.bottom.orders')
                            </a>

                            @if (core()->getConfigData('customer.settings.wishlist.wishlist_option'))
                                <a
                                    class="px-5 py-2 text-base cursor-pointer hover:bg-gray-100"
                                    href="{{ route('shop.customers.account.wishlist.index') }}"
                                >
                                    @lang('shop::app.components.layouts.header.desktop.bottom.wishlist')
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
                                    class="px-5 py-2 text-base cursor-pointer hover:bg-gray-100"
                                    href="{{ route('shop.customer.session.destroy') }}"
                                    onclick="event.preventDefault(); document.getElementById('customerLogout').submit();"
                                >
                                    @lang('shop::app.components.layouts.header.desktop.bottom.logout')
                                </a>
                            @endauth

                            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile_dropdown.links.after') !!}
                        </div>
                    </x-slot>
                @endauth
            </x-shop::dropdown>

            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile.after') !!}
        </div>
    </div>
</div>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-desktop-category-template"
    >
        <!-- Loading State -->
        <div
            class="flex items-center gap-5"
            v-if="isLoading"
        >
            <span
                class="w-20 h-6 rounded shimmer"
                role="presentation"
            ></span>

            <span
                class="w-20 h-6 rounded shimmer"
                role="presentation"
            ></span>

            <span
                class="w-20 h-6 rounded shimmer"
                role="presentation"
            ></span>
        </div>

        <!-- Default category layout -->
        <div
            class="flex items-center"
            v-else-if="'{{ core()->getConfigData('general.design.categories.category_view') }}' !== 'sidebar'"
        >
            <div
                class="group relative flex h-[52px] items-center border-b-2 border-transparent hover:border-navyBlue"
                :class="{ 'border-navyBlue': isCategoryActive(category) }"
                v-for="category in categories"
            >
                <span>
                    <a
                        :href="category.url"
                        class="inline-block px-3 uppercase tracking-wide transition-colors hover:text-navyBlue"
                        :class="isCategoryActive(category)
                            ? 'text-[14px] font-semibold text-navyBlue'
                            : 'text-[12px] font-medium'"
                        :style="isCategoryActive(category) ? '' : 'color: var(--lc-cat-color, #71717a)'"
                    >
                        @{{ category.name }}
                    </a>
                </span>

                <div
                    class="pointer-events-none absolute top-[52px] z-[1] max-h-[580px] w-max max-w-[1260px] overflow-auto overflow-x-auto border border-b-0 border-l-0 border-r-0 border-t border-[#F3F3F3] bg-white p-9 opacity-0 shadow-[0_6px_6px_1px_rgba(0,0,0,.3)] transition-opacity duration-200 ease-out group-hover:pointer-events-auto group-hover:opacity-100 ltr:-left-9 rtl:-right-9"
                    v-if="category.children && category.children.length"
                >
                    <div class="flex justify-between gap-x-[70px]">
                        <div
                            class="grid w-full min-w-max max-w-[150px] flex-auto grid-cols-[1fr] content-start gap-5"
                            v-for="pairCategoryChildren in pairCategoryChildren(category)"
                        >
                            <template v-for="secondLevelCategory in pairCategoryChildren">
                                <p class="font-medium text-navyBlue">
                                    <a :href="secondLevelCategory.url">
                                        @{{ secondLevelCategory.name }}
                                    </a>
                                </p>

                                <ul
                                    class="grid grid-cols-[1fr] gap-3"
                                    v-if="secondLevelCategory.children && secondLevelCategory.children.length"
                                >
                                    <li
                                        class="text-sm font-medium text-zinc-500"
                                        v-for="thirdLevelCategory in secondLevelCategory.children"
                                    >
                                        <a :href="thirdLevelCategory.url">
                                            @{{ thirdLevelCategory.name }}
                                        </a>
                                    </li>
                                </ul>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar category layout -->
        <div v-else>
            <!-- Categories Navigation -->
            <div class="flex items-center">
                <!-- "All" button for opening the category drawer -->
                <div
                    class="flex h-[77px] cursor-pointer items-center border-b-4 border-transparent hover:border-b-4 hover:border-navyBlue"
                    @click="toggleCategoryDrawer"
                >
                    <span class="flex items-center gap-1 px-5 uppercase">
                        <span class="text-xl icon-hamburger"></span>

                        @lang('shop::app.components.layouts.header.desktop.bottom.all')
                    </span>
                </div>

                <!-- Show only first 4 categories in main navigation -->
                <div
                    class="group relative flex h-[77px] items-center border-b-4 border-transparent hover:border-b-4 hover:border-navyBlue"
                    v-for="category in categories.slice(0, 4)"
                >
                    <span>
                        <a
                            :href="category.url"
                            class="inline-block px-5 uppercase"
                        >
                            @{{ category.name }}
                        </a>
                    </span>

                    <!-- Dropdown for each category -->
                    <div
                        class="pointer-events-none absolute top-[78px] z-[1] max-h-[580px] w-max max-w-[1260px] translate-y-1 overflow-auto overflow-x-auto border border-b-0 border-l-0 border-r-0 border-t border-[#F3F3F3] bg-white p-9 opacity-0 shadow-[0_6px_6px_1px_rgba(0,0,0,.3)] transition duration-300 ease-out group-hover:pointer-events-auto group-hover:translate-y-0 group-hover:opacity-100 group-hover:duration-200 group-hover:ease-in ltr:-left-9 rtl:-right-9"
                        v-if="category.children && category.children.length"
                    >
                        <div class="flex justify-between gap-x-[70px]">
                            <div
                                class="grid w-full min-w-max max-w-[150px] flex-auto grid-cols-[1fr] content-start gap-5"
                                v-for="pairCategoryChildren in pairCategoryChildren(category)"
                            >
                                <template v-for="secondLevelCategory in pairCategoryChildren">
                                    <p class="font-medium text-navyBlue">
                                        <a :href="secondLevelCategory.url">
                                            @{{ secondLevelCategory.name }}
                                        </a>
                                    </p>

                                    <ul
                                        class="grid grid-cols-[1fr] gap-3"
                                        v-if="secondLevelCategory.children && secondLevelCategory.children.length"
                                    >
                                        <li
                                            class="text-sm font-medium text-zinc-500"
                                            v-for="thirdLevelCategory in secondLevelCategory.children"
                                        >
                                            <a :href="thirdLevelCategory.url">
                                                @{{ thirdLevelCategory.name }}
                                            </a>
                                        </li>
                                    </ul>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bagisto Drawer Integration -->
            <x-shop::drawer
                position="left"
                width="400px"
                ::is-active="isDrawerActive"
                @toggle="onDrawerToggle"
                @close="onDrawerClose"
            >
                <x-slot:toggle></x-slot>

                <x-slot:header class="border-b border-gray-200">
                    <div class="flex items-center justify-between w-full">
                        <p class="text-xl font-medium">
                            @lang('shop::app.components.layouts.header.desktop.bottom.categories')
                        </p>
                    </div>
                </x-slot>

                <x-slot:content class="!px-0">
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
                            <div class="h-[calc(100vh-74px)] w-full flex-shrink-0 overflow-auto">
                                <div class="py-4">
                                    <div
                                        v-for="category in categories"
                                        :key="category.id"
                                        :class="{'mb-2': category.children && category.children.length}"
                                    >
                                        <div class="flex items-center justify-between px-6 py-2 transition-colors duration-200 cursor-pointer hover:bg-gray-100">
                                            <a
                                                :href="category.url"
                                                class="text-base font-medium text-black"
                                            >
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
                                                    class="flex items-center justify-between px-6 py-2 transition-colors duration-200 cursor-pointer hover:bg-gray-100"
                                                    @click="showThirdLevel(secondLevelCategory, category, $event)"
                                                >
                                                    <a
                                                        :href="secondLevelCategory.url"
                                                        class="text-sm font-normal"
                                                    >
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

                                        <p class="text-base font-medium text-black">
                                            @lang('shop::app.components.layouts.header.desktop.bottom.back-button')
                                        </p>
                                    </button>
                                </div>

                                <!-- Third Level Content -->
                                <div class="py-4">
                                    <div
                                        v-for="thirdLevelCategory in currentSecondLevelCategory?.children"
                                        :key="thirdLevelCategory.id"
                                        class="mb-2"
                                    >
                                        <a
                                            :href="thirdLevelCategory.url"
                                            class="block px-6 py-2 text-sm transition-colors duration-200 hover:bg-gray-100"
                                        >
                                            @{{ thirdLevelCategory.name }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-slot>
            </x-shop::drawer>
        </div>
    </script>

    <script type="module">
        app.component('v-desktop-category', {
            template: '#v-desktop-category-template',

            data() {
                return {
                    isLoading: true,
                    categories: [],
                    isDrawerActive: false,
                    currentViewLevel: 'main',
                    currentSecondLevelCategory: null,
                    currentParentCategory: null
                }
            },

            mounted() {
                this.initCategories();
            },

            methods: {
                initCategories() {
                    // Cached category tree in localStorage with a version stamp + TTL.
                    // Bump CATEGORY_CACHE_VERSION whenever category names/structure change
                    // to force every returning visitor to re-fetch once. The TTL is a
                    // backstop so caches refresh even without a version bump.
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

                        // Drop the legacy un-versioned cache if present.
                        localStorage.removeItem('categories');
                    } catch (e) {}

                    this.getCategories(CATEGORY_CACHE_VERSION);
                },

                getCategories(version) {
                    this.$axios.get("{{ route('shop.api.categories.tree') }}")
                        .then(response => {
                            this.isLoading = false;
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

                pairCategoryChildren(category) {
                    if (! category.children) return [];

                    return category.children.reduce((result, value, index, array) => {
                        if (index % 2 === 0) {
                            result.push(array.slice(index, index + 2));
                        }
                        return result;
                    }, []);
                },

                // Active when the current page URL matches the category URL
                // OR when any descendant category matches (so a parent stays
                // highlighted while the user browses inside its tree).
                isCategoryActive(category) {
                    if (! category) return false;
                    const here = window.location.pathname.replace(/\/+$/, '');
                    const match = (cat) => {
                        try {
                            const u = new URL(cat.url, window.location.origin);
                            if (u.pathname.replace(/\/+$/, '') === here) return true;
                        } catch (e) {}
                        return (cat.children || []).some(match);
                    };
                    return match(category);
                },

                toggleCategoryDrawer() {
                    this.isDrawerActive = !this.isDrawerActive;
                    if (this.isDrawerActive) {
                        this.currentViewLevel = 'main';
                    }
                },

                onDrawerToggle(event) {
                    this.isDrawerActive = event.isActive;
                },

                onDrawerClose(event) {
                    this.isDrawerActive = false;
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
    </script>
@endPushOnce
{!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.after') !!}

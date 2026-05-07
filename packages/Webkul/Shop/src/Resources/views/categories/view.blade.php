<!-- SEO Meta Content -->
@push('meta')
    <meta
        name="description"
        content="{{ trim($category->meta_description) != "" ? $category->meta_description : \Illuminate\Support\Str::limit(strip_tags($category->description), 120, '') }}"
    />
    <meta name="keywords" content="{{ $category->meta_keywords }}" />
    @if (core()->getConfigData('catalog.rich_snippets.categories.enable'))
        <script type="application/ld+json">
            {!! app('Webkul\Product\Helpers\SEO')->getCategoryJsonLd($category) !!}
        </script>
    @endif
@endPush

@push('styles')
<style>
/* Filter modal slide-in animation */
@keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to   { transform: translateX(0);   opacity: 1; }
}
.animate-slide-in-right { animation: slideInRight 0.25s ease-out forwards; }
.lc-fade-enter-active, .lc-fade-leave-active { transition: opacity 0.2s; }
.lc-fade-enter-from, .lc-fade-leave-to { opacity: 0; }

/* Sticky bar scroll-aware */
#lc-sticky-bar { transition: box-shadow 0.2s; }
#lc-sticky-bar.scrolled { box-shadow: 0 2px 12px rgba(0,0,0,0.10); }

/* Hide carousel when scrolled */
#lc-carousel-wrap { transition: max-height 0.35s ease, opacity 0.35s ease; overflow: hidden; }
#lc-carousel-wrap.hidden-scroll { max-height: 0 !important; opacity: 0; }

/* 4-col product grid */
.product-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; }
@media (max-width: 1280px) { .product-grid-4 { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px)  { .product-grid-4 { grid-template-columns: repeat(2, 1fr); gap: 0.75rem; } }
</style>
@endpush

<x-shop::layouts>
    <!-- Page Title -->
    <x-slot:title>
        {{ trim($category->meta_title) != "" ? $category->meta_title : $category->name }}
    </x-slot>

    {!! view_render_event('bagisto.shop.categories.view.banner_path.before') !!}

    <!-- Hero Image (hides on scroll) -->
    @if ($category->banner_path)
        <div id="lc-carousel-wrap" class="container mt-8 px-[60px] max-lg:px-8 max-md:mt-4 max-md:px-4">
            <x-shop::media.images.lazy
                class="aspect-[4/1] max-h-full max-w-full rounded-xl"
                src="{{ $category->banner_url }}"
                alt="{{ $category->name }}"
                width="1320"
                height="300"
            />
        </div>
    @endif

    {!! view_render_event('bagisto.shop.categories.view.banner_path.after') !!}

    {!! view_render_event('bagisto.shop.categories.view.description.before') !!}

    @if (in_array($category->display_mode, [null, 'description_only', 'products_and_description']))
        @if ($category->description)
            <div class="container mt-[34px] px-[60px] max-lg:px-8 max-md:mt-4 max-md:px-4 max-md:text-sm max-sm:text-xs">
                {!! $category->description !!}
            </div>
        @endif
    @endif

    {!! view_render_event('bagisto.shop.categories.view.description.after') !!}

    @if (in_array($category->display_mode, [null, 'products_only', 'products_and_description']))
        <!-- Category Vue Component -->
        <v-category>
            <!-- Category Shimmer Effect -->
            <x-shop::shimmer.categories.view />
        </v-category>
    @endif

    @pushOnce('scripts')
        <script type="text/x-template" id="v-category-template">
            <div>
                <!-- ── Sticky Toolbar Bar ── -->
                <div id="lc-sticky-bar" class="sticky top-0 z-30 bg-white border-b border-zinc-200">
                    <div class="container px-[60px] max-lg:px-8 max-md:px-4 flex items-center justify-between py-2 gap-3">

                        <!-- Filter Button -->
                        <button
                            class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-medium hover:border-navyBlue hover:text-navyBlue transition-colors"
                            @click="filterModalOpen = true"
                        >
                            <span class="icon-filter-1 text-lg"></span>
                            @lang('shop::app.categories.filters.filters')
                            <span v-if="activeFilterCount > 0" class="ml-1 flex h-5 w-5 items-center justify-center rounded-full bg-navyBlue text-[10px] text-white font-bold">@{{ activeFilterCount }}</span>
                        </button>

                        <!-- Desktop Toolbar -->
                        <div class="flex items-center gap-3 max-md:hidden flex-1 justify-end">
                            @include('shop::categories.toolbar')
                        </div>

                        <!-- Mobile Sort Drawer -->
                        <div class="md:hidden">
                            <x-shop::drawer position="bottom" width="100%" ::is-active="isDrawerActive.toolbar">
                                <x-slot:toggle>
                                    <div class="flex cursor-pointer items-center gap-x-2 px-3 py-2 text-sm font-medium uppercase" @click="isDrawerActive.toolbar = true">
                                        <span class="icon-sort-1 text-lg"></span>
                                        @lang('shop::app.categories.filters.sort')
                                    </div>
                                </x-slot>
                                <x-slot:header>
                                    <p class="text-lg font-semibold">@lang('shop::app.categories.filters.sort')</p>
                                </x-slot>
                                <x-slot:content class="!px-0">
                                    @include('shop::categories.toolbar')
                                </x-slot>
                            </x-shop::drawer>
                        </div>
                    </div>
                </div>

                <!-- ── Filter Popup Modal ── -->
                <teleport to="body">
                    <transition name="lc-fade">
                        <div v-if="filterModalOpen" class="fixed inset-0 z-50 flex" @click.self="filterModalOpen = false">
                            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="filterModalOpen = false"></div>
                            <div class="relative z-10 ml-auto h-full w-full max-w-[380px] max-md:max-w-full bg-white shadow-2xl flex flex-col animate-slide-in-right">
                                <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4">
                                    <p class="text-lg font-semibold">@lang('shop::app.categories.filters.filters')</p>
                                    <div class="flex items-center gap-4">
                                        <button v-if="activeFilterCount > 0" class="text-sm text-navyBlue font-medium" @click="clearAllFilters()">@lang('shop::app.categories.filters.clear-all')</button>
                                        <button class="text-2xl leading-none text-zinc-400 hover:text-zinc-700" @click="filterModalOpen = false">✕</button>
                                    </div>
                                </div>
                                <div class="flex-1 overflow-y-auto px-6 py-4">
                                    @include('shop::categories.filters')
                                </div>
                                <div class="border-t border-zinc-200 px-6 py-4">
                                    <button class="w-full rounded-xl bg-navyBlue py-3 text-sm font-semibold text-white" @click="filterModalOpen = false">
                                        @lang('shop::app.categories.view.apply-filter', [], null) <span v-if="activeFilterCount > 0">(@{{ activeFilterCount }})</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </transition>
                </teleport>

                <!-- ── Product Area ── -->
                <div class="container px-[60px] max-lg:px-8 max-md:px-4 mt-6 pb-10">

                    <!-- List mode -->
                    <div
                        class="grid grid-cols-1 gap-6"
                        v-if="(filters.toolbar.applied.mode ?? filters.toolbar.default.mode) === 'list'"
                    >
                        <template v-if="isLoading">
                            <x-shop::shimmer.products.cards.list count="12" />
                        </template>

                        {!! view_render_event('bagisto.shop.categories.view.list.product_card.before') !!}

                        <template v-else>
                            <template v-if="products.length">
                                <x-shop::products.card ::mode="'list'" v-for="product in products" />
                            </template>
                            <template v-else>
                                <div class="m-auto grid w-full place-content-center items-center justify-items-center py-32 text-center">
                                    <img class="max-md:h-[100px] max-md:w-[100px]" src="{{ bagisto_asset('images/thank-you.png') }}" alt="@lang('shop::app.categories.view.empty')" loading="lazy" decoding="async" />
                                    <p class="text-xl max-md:text-sm" role="heading">@lang('shop::app.categories.view.empty')</p>
                                </div>
                            </template>
                        </template>

                        {!! view_render_event('bagisto.shop.categories.view.list.product_card.after') !!}
                    </div>

                    <!-- Grid mode — 4 cols desktop, 2 mobile -->
                    <div v-else>
                        <template v-if="isLoading">
                            <div class="product-grid-4">
                                <x-shop::shimmer.products.cards.grid count="12" />
                            </div>
                        </template>

                        {!! view_render_event('bagisto.shop.categories.view.grid.product_card.before') !!}

                        <template v-else>
                            <template v-if="products.length">
                                <div class="product-grid-4">
                                    <x-shop::products.card ::mode="'grid'" v-for="product in products" />
                                </div>
                            </template>
                            <template v-else>
                                <div class="m-auto grid w-full place-content-center items-center justify-items-center py-32 text-center">
                                    <img class="max-md:h-[100px] max-md:w-[100px]" src="{{ bagisto_asset('images/thank-you.png') }}" alt="@lang('shop::app.categories.view.empty')" loading="lazy" decoding="async" />
                                    <p class="text-xl max-md:text-sm" role="heading">@lang('shop::app.categories.view.empty')</p>
                                </div>
                            </template>
                        </template>

                        {!! view_render_event('bagisto.shop.categories.view.grid.product_card.after') !!}
                    </div>

                    {!! view_render_event('bagisto.shop.categories.view.load_more_button.before') !!}

                    <!-- Load More -->
                    <button
                        class="secondary-button mx-auto mt-10 block w-max rounded-2xl px-11 py-3 text-center text-base max-md:rounded-lg max-sm:mt-6 max-sm:px-6 max-sm:py-1.5 max-sm:text-sm"
                        @click="loadMoreProducts"
                        v-if="links.next && !loader"
                    >
                        @lang('shop::app.categories.view.load-more')
                    </button>
                    <button v-else-if="links.next" class="secondary-button mx-auto mt-10 block w-max rounded-2xl px-[74.5px] py-3.5 text-center text-base max-md:rounded-lg max-md:py-3 max-sm:mt-6">
                        <img class="h-5 w-5 animate-spin text-navyBlue" src="{{ bagisto_asset('images/spinner.svg') }}" alt="Loading" />
                    </button>

                    {!! view_render_event('bagisto.shop.categories.view.grid.load_more_button.after') !!}
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-category', {
                template: '#v-category-template',

                data() {
                    return {
                        isMobile: window.innerWidth <= 767,
                        isLoading: true,
                        filterModalOpen: false,
                        isDrawerActive: { toolbar: false, filter: false },
                        filters: {
                            toolbar: { default: {}, applied: {} },
                            filter: {},
                        },
                        products: [],
                        links: {},
                        loader: false,
                    }
                },

                computed: {
                    queryParams() {
                        let queryParams = Object.assign({}, this.filters.filter, this.filters.toolbar.applied);
                        return this.removeJsonEmptyValues(queryParams);
                    },
                    queryString() {
                        return this.jsonToQueryString(this.queryParams);
                    },
                    activeFilterCount() {
                        return Object.keys(this.filters.filter || {}).filter(k => {
                            const v = this.filters.filter[k];
                            return v && (Array.isArray(v) ? v.length > 0 : v !== '');
                        }).length;
                    },
                },

                watch: {
                    queryParams() { this.getProducts(); },
                    queryString() { window.history.pushState({}, '', '?' + this.queryString); },
                    filterModalOpen(val) {
                        document.body.style.overflow = val ? 'hidden' : '';
                    },
                },

                mounted() {
                    this.initScrollBehavior();
                },

                methods: {
                    setFilters(type, filters) { this.filters[type] = filters; },
                    clearFilters(type, filters) { this.filters[type] = {}; },
                    clearAllFilters() {
                        this.filters.filter = {};
                        this.$emit('filter-clear', {});
                    },

                    initScrollBehavior() {
                        let lastY = 0;
                        const bar = document.getElementById('lc-sticky-bar');
                        const carousel = document.getElementById('lc-carousel-wrap');
                        window.addEventListener('scroll', () => {
                            const y = window.scrollY;
                            if (bar) bar.classList.toggle('scrolled', y > 10);
                            if (carousel) carousel.classList.toggle('hidden-scroll', y > 80);
                            lastY = y;
                        }, { passive: true });
                    },

                    getProducts() {
                        this.isDrawerActive = { toolbar: false, filter: false };
                        document.body.style.overflow = 'scroll';
                        this.isLoading = true;
                        this.$axios.get("{{ route('shop.api.products.index', ['category_id' => $category->id]) }}", {
                            params: this.queryParams
                        }).then(response => {
                            this.isLoading = false;
                            this.products = response.data.data;
                            this.links = response.data.links;
                        }).catch(error => { console.log(error); });
                    },

                    loadMoreProducts() {
                        if (!this.links.next) return;
                        this.loader = true;
                        this.$axios.get(this.links.next).then(response => {
                            this.loader = false;
                            this.products = [...this.products, ...response.data.data];
                            this.links = response.data.links;
                        }).catch(error => { console.log(error); });
                    },

                    removeJsonEmptyValues(params) {
                        Object.keys(params).forEach(function (key) {
                            if (!params[key] && params[key] !== undefined) delete params[key];
                            if (Array.isArray(params[key])) params[key] = params[key].join(',');
                        });
                        return params;
                    },

                    jsonToQueryString(params) {
                        let parameters = new URLSearchParams();
                        for (const key in params) parameters.append(key, params[key]);
                        return parameters.toString();
                    },
                },
            });
        </script>
    @endPushOnce
</x-shop::layouts>

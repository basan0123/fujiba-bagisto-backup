<?php
    $searchTitle = $suggestion ?? $query;
    $title = $searchTitle ? trans('shop::app.search.title', ['query' => $searchTitle]) : trans('shop::app.search.results');
    $searchInstead = $suggestion ? $query : null;
?>
@push('meta')
    <meta name="description" content="{{ $title }}" />
    <meta name="keywords" content="{{ $title }}" />
@endPush

@push('styles')
<style>
@keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to   { transform: translateX(0);   opacity: 1; }
}
.animate-slide-in-right { animation: slideInRight 0.25s ease-out forwards; }
.lc-fade-enter-active, .lc-fade-leave-active { transition: opacity 0.2s; }
.lc-fade-enter-from, .lc-fade-leave-to { opacity: 0; }
#lc-search-sticky-bar { transition: box-shadow 0.2s; }
#lc-search-sticky-bar.scrolled { box-shadow: 0 2px 12px rgba(0,0,0,0.10); }
.product-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; }
@media (max-width: 1280px) { .product-grid-4 { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px)  { .product-grid-4 { grid-template-columns: repeat(2, 1fr); gap: 0.75rem; } }

/* Gallery Mode */
.lc-gallery-wrap { position:relative; }
.lc-gallery-track {
    display:flex;
    gap:12px;
    overflow-x:auto;
    scroll-snap-type:x mandatory;
    scrollbar-width:thin;
    scrollbar-color:#cbd5e1 transparent;
    padding-bottom:12px;
    cursor:grab;
}
.lc-gallery-track:active { cursor:grabbing; }
.lc-gallery-track::-webkit-scrollbar { height:6px; }
.lc-gallery-track::-webkit-scrollbar-track { background:transparent; }
.lc-gallery-track::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:99px; }
.lc-gallery-item {
    flex:0 0 220px;
    scroll-snap-align:start;
    position:relative;
    border-radius:12px;
    overflow:hidden;
    aspect-ratio:3/4;
    background:#f4f4f5;
}
@media (max-width:768px) { .lc-gallery-item { flex:0 0 160px; } }
.lc-gallery-item img {
    width:100%; height:100%; object-fit:cover;
    transition:transform 0.3s ease;
}
.lc-gallery-item:hover img { transform:scale(1.05); }
.lc-gallery-item .lc-price-overlay {
    position:absolute; bottom:0; left:0; right:0;
    background:linear-gradient(transparent,rgba(0,0,0,0.72));
    padding:28px 10px 10px;
    opacity:0;
    transition:opacity 0.22s;
    display:flex;
    flex-direction:column;
    align-items:flex-start;
}
.lc-gallery-item:hover .lc-price-overlay,
.lc-gallery-item.lc-tapped .lc-price-overlay { opacity:1; }
.lc-gallery-item .lc-price-overlay .price { color:#fff; font-size:16px; font-weight:700; line-height:1.2; }
.lc-gallery-item .lc-price-overlay .old-price { color:rgba(255,255,255,0.65); font-size:12px; text-decoration:line-through; }
.lc-gallery-arrow {
    position:absolute; top:50%; transform:translateY(-50%);
    z-index:10;
    width:44px; height:44px;
    background:white;
    border:none; border-radius:50%;
    box-shadow:0 2px 12px rgba(0,0,0,0.18);
    display:flex; align-items:center; justify-content:center;
    font-size:20px; cursor:pointer;
    transition:background 0.15s;
}
.lc-gallery-arrow:hover { background:#f1f5f9; }
.lc-gallery-arrow.left { left:-18px; }
.lc-gallery-arrow.right { right:-18px; }
@media (max-width:768px) {
    .lc-gallery-arrow.left { left:4px; }
    .lc-gallery-arrow.right { right:4px; }
}
</style>
@endpush

<x-shop::layouts :has-feature="false">
    <x-slot:title>{{ $title }}</x-slot>

    <div class="container px-[60px] max-lg:px-8 max-sm:px-4 mt-8 max-md:mt-5">
        @if (request()->has('image-search'))
            @include('shop::search.images.results')
        @endif

        <h2 class="break-all text-2xl font-medium max-sm:text-base">
            <span v-text="'{{ preg_replace('/[,\\"\\\']+/', '', $title) }}'"></span>
        </h2>

        @if ($searchInstead)
            <form action="{{ route('shop.search.index', ['suggest' => false]) }}" class="flex max-w-[445px] items-center mt-2" role="search">
                <input type="text" name="query" class="hidden" value="{{ $searchInstead }}">
                <input type="text" name="suggest" class="hidden" value="0">
                <p class="mt-1 text-sm text-gray-600" v-pre>
                    {{ trans('shop::app.search.suggest') }}
                    <button type="submit" class="text-blue-600 hover:text-blue-800 hover:underline">{{ $searchInstead }}</button>
                </p>
            </form>
        @endif
    </div>

    <v-search>
        <x-shop::shimmer.categories.view />
    </v-search>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-search-template">
            <div>
                <!-- Sticky Toolbar -->
                <div id="lc-search-sticky-bar" class="sticky top-0 z-30 bg-white border-b border-zinc-200">
                    <div class="container px-[60px] max-lg:px-8 max-md:px-4 flex items-center justify-between py-2 gap-3">
                        <!-- Filter button -->
                        <button class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-medium hover:border-navyBlue hover:text-navyBlue transition-colors" @click="filterModalOpen = true">
                            <span class="icon-filter-1 text-lg"></span>
                            @lang('shop::app.categories.filters.filters')
                            <span v-if="activeFilterCount > 0" class="ml-1 flex h-5 w-5 items-center justify-center rounded-full bg-navyBlue text-[10px] text-white font-bold">@{{ activeFilterCount }}</span>
                        </button>

                        <!-- Gallery mode toggle button (desktop) -->
                        <div class="flex items-center gap-2 max-md:hidden">
                            <button
                                class="flex items-center gap-1 rounded-lg border px-3 py-2 text-sm font-medium transition-colors"
                                :class="viewMode === 'gallery' ? 'border-navyBlue bg-navyBlue text-white' : 'border-zinc-200 bg-white text-zinc-600 hover:border-navyBlue'"
                                @click="viewMode = 'gallery'"
                                title="Gallery view"
                            >
                                <span style="font-size:16px">&#9639;</span>
                                Gallery
                            </button>
                        </div>

                        <!-- Sort toolbar (desktop) -->
                        <div class="flex items-center gap-3 max-md:hidden flex-1 justify-end">
                            @include('shop::categories.toolbar')
                        </div>

                        <!-- Mobile gallery + sort buttons -->
                        <div class="md:hidden flex items-center gap-2">
                            <button
                                class="flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-colors"
                                :class="viewMode === 'gallery' ? 'border-navyBlue bg-navyBlue text-white' : 'border-zinc-200 bg-white text-zinc-600'"
                                @click="viewMode = (viewMode === 'gallery' ? 'grid' : 'gallery')"
                            >
                                <span style="font-size:13px">&#9639;</span> Gallery
                            </button>
                            <x-shop::drawer position="bottom" width="100%" ::is-active="isDrawerActive.toolbar">
                                <x-slot:toggle>
                                    <div class="flex cursor-pointer items-center gap-x-2 px-3 py-2 text-sm font-medium uppercase" @click="isDrawerActive.toolbar = true">
                                        <span class="icon-sort-1 text-lg"></span>
                                        @lang('shop::app.categories.filters.sort')
                                    </div>
                                </x-slot>
                                <x-slot:header><p class="text-lg font-semibold">@lang('shop::app.categories.filters.sort')</p></x-slot>
                                <x-slot:content class="!px-0">@include('shop::categories.toolbar')</x-slot>
                            </x-shop::drawer>
                        </div>
                    </div>
                </div>

                <!-- Matching Categories (e.g. searching "yen" / "YEN" / "Yenn" surfaces the Yen price categories) -->
                <div v-if="categories.length" class="container px-[60px] max-lg:px-8 max-md:px-4 mt-6">
                    <h3 class="text-lg font-medium text-zinc-800 mb-3">@lang('shop::app.components.layouts.header.desktop.bottom.categories')</h3>
                    <div class="flex flex-wrap gap-2">
                        <a
                            v-for="category in categories"
                            :key="category.id"
                            :href="`{{ route('shop.product_or_category.index', '') }}/${category.slug}`"
                            class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 transition-colors hover:border-navyBlue hover:text-navyBlue"
                        >
                            @{{ category.name }}
                        </a>
                    </div>
                </div>

                <!-- Filter Popup Modal -->
                <teleport to="body">
                    <transition name="lc-fade">
                        <div v-if="filterModalOpen" class="fixed inset-0 z-50 flex" @click.self="filterModalOpen = false">
                            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="filterModalOpen = false"></div>
                            <div class="relative z-10 ml-auto h-full w-full max-w-[380px] max-md:max-w-full bg-white shadow-2xl flex flex-col animate-slide-in-right">
                                <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4">
                                    <p class="text-lg font-semibold">@lang('shop::app.categories.filters.filters')</p>
                                    <div class="flex items-center gap-4">
                                        <button v-if="activeFilterCount > 0" class="text-sm text-navyBlue font-medium" @click="clearAllFilters()">@lang('shop::app.categories.filters.clear-all')</button>
                                        <button class="text-2xl leading-none text-zinc-400 hover:text-zinc-700" @click="filterModalOpen = false">&#x2715;</button>
                                    </div>
                                </div>
                                <div class="flex-1 overflow-y-auto px-6 py-4">
                                    @include('shop::categories.filters')
                                </div>
                                <div class="border-t border-zinc-200 px-6 py-4">
                                    <button class="w-full rounded-xl bg-navyBlue py-3 text-sm font-semibold text-white" @click="filterModalOpen = false">
                                        @lang('shop::app.categories.view.apply-filter', [], null)
                                        <span v-if="activeFilterCount > 0">(@{{ activeFilterCount }})</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </transition>
                </teleport>

                <!-- Product Area -->
                <div class="container px-[60px] max-lg:px-8 max-md:px-4 mt-6 pb-10">

                    <!-- ══ GALLERY MODE ══ -->
                    <div v-if="viewMode === 'gallery'" class="lc-gallery-wrap">
                        <!-- Left arrow -->
                        <button class="lc-gallery-arrow left" @click="galleryScroll(-1)" aria-label="Scroll left">
                            <span class="icon-arrow-left text-2xl"></span>
                        </button>

                        <!-- Gallery track (draggable) -->
                        <div
                            class="lc-gallery-track"
                            ref="galleryTrack"
                            @mousedown="galleryDragStart"
                            @mousemove="galleryDragMove"
                            @mouseup="galleryDragEnd"
                            @mouseleave="galleryDragEnd"
                            @touchstart.passive="galleryDragStart"
                            @touchmove.passive="galleryDragMove"
                            @touchend="galleryDragEnd"
                        >
                            <!-- Shimmer -->
                            <template v-if="isLoading">
                                <div v-for="n in 12" :key="n" class="lc-gallery-item shimmer"></div>
                            </template>

                            <!-- Gallery items — no name, price on hover -->
                            <template v-else>
                                <a
                                    v-for="product in products"
                                    :key="product.id"
                                    :href="`{{ route('shop.product_or_category.index', '') }}/${product.url_key}`"
                                    class="lc-gallery-item"
                                    @touchstart.stop="galleryTap($event, product)"
                                >
                                    <img
                                        :src="product.base_image.medium_image_url"
                                        :alt="product.name"
                                        loading="lazy"
                                    />
                                    <!-- Price overlay (visible on hover / tap) -->
                                    <div class="lc-price-overlay" :id="'gp-' + product.id">
                                        <span class="price" v-html="product.price_html"></span>
                                    </div>
                                    <!-- Sale badge -->
                                    <span v-if="product.on_sale" class="absolute top-2 left-2 rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white">SALE</span>
                                </a>
                            </template>

                            <!-- Infinite scroll sentinel inside track -->
                            <div ref="gallerySentinel" class="flex-shrink-0 w-4"></div>
                        </div>

                        <!-- Right arrow -->
                        <button class="lc-gallery-arrow right" @click="galleryScroll(1)" aria-label="Scroll right">
                            <span class="icon-arrow-right text-xl"></span>
                        </button>

                        <!-- Loading more -->
                        <div v-if="loader" class="flex justify-center mt-4">
                            <img class="h-6 w-6 animate-spin" src="{{ bagisto_asset('images/spinner.svg') }}" alt="Loading" />
                        </div>

                        <!-- Empty state -->
                        <div v-if="!isLoading && !products.length" class="m-auto grid w-full place-content-center items-center justify-items-center py-32 text-center">
                            <img class="max-md:h-[100px] max-md:w-[100px]" src="{{ bagisto_asset('images/thank-you.png') }}" alt="Empty" loading="lazy" decoding="async" />
                            <p class="text-xl max-md:text-sm" role="heading">@lang('shop::app.categories.view.empty')</p>
                        </div>
                    </div>

                    <!-- ══ LIST MODE ══ -->
                    <div class="grid grid-cols-1 gap-6" v-else-if="(filters.toolbar.applied.mode ?? filters.toolbar.default.mode) === 'list'">
                        <template v-if="isLoading"><x-shop::shimmer.products.cards.list count="12" /></template>
                        <template v-else>
                            <template v-if="products.length">
                                <x-shop::products.card ::mode="'list'" v-for="product in products" />
                            </template>
                            <template v-else>
                                <div class="m-auto grid w-full place-content-center items-center justify-items-center py-32 text-center">
                                    <img class="max-md:h-[100px] max-md:w-[100px]" src="{{ bagisto_asset('images/thank-you.png') }}" alt="Empty" loading="lazy" decoding="async" />
                                    <p class="text-xl max-md:text-sm" role="heading">@lang('shop::app.categories.view.empty')</p>
                                </div>
                            </template>
                        </template>
                    </div>

                    <!-- ══ GRID MODE (default) ══ -->
                    <div v-else>
                        <template v-if="isLoading">
                            <div class="product-grid-4"><x-shop::shimmer.products.cards.grid count="12" /></div>
                        </template>
                        <template v-else>
                            <template v-if="products.length">
                                <div class="product-grid-4">
                                    <x-shop::products.card ::mode="'grid'" v-for="product in products" :navigation-link="route('shop.search.index')" />
                                </div>
                            </template>
                            <template v-else>
                                <div class="m-auto grid w-full place-content-center items-center justify-items-center py-32 text-center">
                                    <img class="max-md:h-[100px] max-md:w-[100px]" src="{{ bagisto_asset('images/thank-you.png') }}" alt="Empty" loading="lazy" decoding="async" />
                                    <p class="text-xl max-md:text-sm" role="heading">@lang('shop::app.categories.view.empty')</p>
                                </div>
                            </template>
                        </template>
                    </div>

                    <!-- Infinite Scroll Sentinel (grid/list) -->
                    <div v-if="viewMode !== 'gallery'" ref="infiniteScrollSentinel" class="h-4 mt-4"></div>
                    <div v-if="loader && viewMode !== 'gallery'" class="flex justify-center mt-6 pb-4">
                        <img class="h-8 w-8 animate-spin" src="{{ bagisto_asset('images/spinner.svg') }}" alt="Loading" />
                    </div>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-search', {
                template: '#v-search-template',

                data() {
                    return {
                        isMobile: window.innerWidth <= 767,
                        isLoading: true,
                        filterModalOpen: false,
                        viewMode: 'grid', // 'grid' | 'list' | 'gallery'
                        isDrawerActive: { toolbar: false, filter: false },
                        filters: {
                            toolbar: { default: {}, applied: {} },
                            filter: {},
                        },
                        products: [],
                        categories: [],
                        links: {},
                        loader: false,
                        // drag state
                        drag: { active: false, startX: 0, scrollLeft: 0 },
                    }
                },

                computed: {
                    queryParams() {
                        let p = Object.assign({}, this.filters.filter, this.filters.toolbar.applied);
                        return this.removeJsonEmptyValues(p);
                    },
                    queryString() {
                        return this.jsonToQueryString(this.queryParams);
                    },
                    activeFilterCount() {
                        return Object.keys(this.filters.filter || {}).filter(k => {
                            if (k === 'query') return false;
                            const v = this.filters.filter[k];
                            return v && (Array.isArray(v) ? v.length > 0 : v !== '');
                        }).length;
                    },
                },

                watch: {
                    queryParams() { this.getProducts(); },
                    queryString() { window.history.pushState({}, '', '?' + this.queryString); },
                    filterModalOpen(val) { document.body.style.overflow = val ? 'hidden' : ''; },
                    viewMode() {
                        this.$nextTick(() => {
                            if (this.viewMode === 'gallery') {
                                this.initGalleryInfiniteScroll();
                            } else {
                                this.initInfiniteScroll();
                            }
                        });
                    },
                },

                mounted() {
                    this.initScrollBehavior();
                    this.initInfiniteScroll();
                    this.getCategories();
                },

                methods: {
                    setFilters(type, filters) { this.filters[type] = filters; },
                    clearFilters(type, filters) { this.filters[type] = {}; },
                    clearAllFilters() { this.filters.filter = {}; },

                    initScrollBehavior() {
                        window.addEventListener('scroll', () => {
                            const bar = document.getElementById('lc-search-sticky-bar');
                            if (bar) bar.classList.toggle('scrolled', window.scrollY > 10);
                        }, { passive: true });
                    },

                    initInfiniteScroll() {
                        this.$nextTick(() => {
                            const sentinel = this.$refs.infiniteScrollSentinel;
                            if (!sentinel) return;
                            const obs = new IntersectionObserver((entries) => {
                                if (entries[0].isIntersecting && this.links.next && !this.loader && !this.isLoading) {
                                    this.loadMoreProducts();
                                }
                            }, { threshold: 0.1 });
                            obs.observe(sentinel);
                        });
                    },

                    initGalleryInfiniteScroll() {
                        const sentinel = this.$refs.gallerySentinel;
                        const track = this.$refs.galleryTrack;
                        if (!sentinel || !track) return;
                        const obs = new IntersectionObserver((entries) => {
                            if (entries[0].isIntersecting && this.links.next && !this.loader && !this.isLoading) {
                                this.loadMoreProducts();
                            }
                        }, { root: track, threshold: 0.1 });
                        obs.observe(sentinel);
                    },

                    galleryScroll(dir) {
                        const track = this.$refs.galleryTrack;
                        if (track) track.scrollBy({ left: dir * 640, behavior: 'smooth' });
                    },

                    galleryDragStart(e) {
                        const track = this.$refs.galleryTrack;
                        if (!track) return;
                        this.drag.active = true;
                        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                        this.drag.startX = clientX - track.offsetLeft;
                        this.drag.scrollLeft = track.scrollLeft;
                    },

                    galleryDragMove(e) {
                        if (!this.drag.active) return;
                        const track = this.$refs.galleryTrack;
                        if (!track) return;
                        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                        const walk = (clientX - track.offsetLeft - this.drag.startX) * 1.4;
                        track.scrollLeft = this.drag.scrollLeft - walk;
                    },

                    galleryDragEnd() {
                        this.drag.active = false;
                    },

                    galleryTap(e, product) {
                        // Toggle price overlay on mobile tap
                        const el = e.currentTarget;
                        const overlay = el.querySelector('.lc-price-overlay');
                        if (!overlay) return;
                        const isShowing = overlay.style.opacity === '1';
                        // hide all
                        document.querySelectorAll('.lc-price-overlay').forEach(o => o.style.opacity = '0');
                        if (!isShowing) {
                            overlay.style.opacity = '1';
                            e.preventDefault(); // prevent navigation on first tap
                        }
                    },

                    getProducts() {
                        this.isDrawerActive = { toolbar: false, filter: false };
                        document.body.style.overflow = '';
                        this.isLoading = true;
                        this.$axios.get("{{ route('shop.api.products.index') }}", {
                            params: this.queryParams
                        }).then(response => {
                            this.isLoading = false;
                            this.products = response.data.data;
                            this.links = response.data.links;
                            this.$nextTick(() => {
                                if (this.viewMode === 'gallery') {
                                    this.initGalleryInfiniteScroll();
                                } else {
                                    this.initInfiniteScroll();
                                }
                            });
                        }).catch(error => { console.log(error); });
                    },

                    getCategories() {
                        // Surface categories whose NAME or SLUG matches the search term.
                        //
                        // Name search is via the category API (case-insensitive on the
                        // server, utf8mb4_unicode_ci) so digits like "1595" or the symbol
                        // "￥" match the price categories (named ￥1595, ￥1795, ...).
                        //
                        // The price categories no longer contain the word "yen" in their
                        // NAME, but their SLUG still does (1595yen, 1795yen, ...). So we
                        // also fetch the visible category tree and match the query against
                        // name OR slug client-side, then merge + dedupe by id. The tree
                        // endpoint only returns enabled categories, so the disabled
                        // 1295Yen category is automatically excluded.
                        const params = new URLSearchParams(window.location.search);
                        const query = (params.get('query') || '').trim();

                        if (!query) return;

                        const q = query.toLowerCase();

                        const byName = this.$axios.get("{{ route('shop.api.categories.index') }}", {
                            params: { name: query, limit: 24 }
                        }).then(r => r.data.data || []).catch(() => []);

                        const flatten = (nodes, acc = []) => {
                            (nodes || []).forEach(n => {
                                acc.push(n);
                                if (n.children && n.children.length) flatten(n.children, acc);
                            });
                            return acc;
                        };

                        const bySlug = this.$axios.get("{{ route('shop.api.categories.tree') }}")
                            .then(r => flatten(r.data.data).filter(c =>
                                (c.name && c.name.toLowerCase().includes(q)) ||
                                (c.slug && c.slug.toLowerCase().includes(q))
                            ))
                            .catch(() => []);

                        Promise.all([byName, bySlug]).then(([a, b]) => {
                            const merged = {};
                            [...a, ...b].forEach(c => { if (c && c.id != null) merged[c.id] = c; });
                            this.categories = Object.values(merged);
                        });
                    },

                    loadMoreProducts() {
                        if (!this.links.next || this.loader) return;
                        this.loader = true;
                        this.$axios.get(this.links.next).then(response => {
                            this.loader = false;
                            this.products = [...this.products, ...response.data.data];
                            this.links = response.data.links;
                        }).catch(error => { this.loader = false; console.log(error); });
                    },

                    removeJsonEmptyValues(params) {
                        Object.keys(params).forEach(key => {
                            if (!params[key] && params[key] !== undefined) delete params[key];
                            if (Array.isArray(params[key])) params[key] = params[key].join(',');
                        });
                        return params;
                    },

                    jsonToQueryString(params) {
                        let p = new URLSearchParams();
                        for (const k in params) p.append(k, params[k]);
                        return p.toString();
                    },
                },
            });
        </script>
    @endPushOnce
</x-shop::layouts>

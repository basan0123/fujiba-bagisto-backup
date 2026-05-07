<template>
    <div class="content home-v3" :class="{ 'is-filtering': isFiltering }">
        <!-- Upper sections: hero + rails. All collapse when the user is
             actively filtering / searching so the grid takes the whole screen. -->
        <transition name="upper-fade">
            <div class="home-upper" v-show="!isFiltering">
                <!-- Hero carousel (admin-configured slots) -->
                <carousel
                    v-if="heroSlides.length"
                    :per-page="1"
                    :loop="heroSlides.length > 1"
                    :autoplay="heroSlides.length > 1"
                    :autoplayTimeout="5500"
                    pagination-color="rgba(255,255,255,0.45)"
                    pagination-active-color="#ffffff"
                    class="hero-carousel">
                    <slide :key="'h'+i" v-for="(slide, i) in heroSlides">
                        <component :is="slide.cta_url ? 'a' : 'div'" :href="slide.cta_url || null" class="hero-card">
                            <img class="hero-img"
                                :src="slide.image"
                                :alt="slide.title || ''"
                                width="1280" height="720"
                                :loading="i === 0 ? 'eager' : 'lazy'"
                                :fetchpriority="i === 0 ? 'high' : 'auto'"
                                decoding="async"/>
                            <span v-if="slide.badge" class="hero-badge">{{ slide.badge }}</span>
                            <div class="hero-text" v-if="slide.title || slide.subtitle">
                                <h2 v-if="slide.title">{{ slide.title }}</h2>
                                <p v-if="slide.subtitle">{{ slide.subtitle }}</p>
                                <span v-if="slide.cta_text && slide.cta_url" class="hero-cta">{{ slide.cta_text }} →</span>
                            </div>
                        </component>
                    </slide>
                </carousel>

                <!-- Hero gradient fallback shown until carousel mounts (or
                     when admin hasn't uploaded any hero slots). Reserves
                     the hero's exact aspect-ratio so the page doesn't shift
                     when slides arrive. -->
                <div class="hero-fallback" v-else>
                    <div class="hero-fb-text">
                        <h2>{{ channelName }}</h2>
                        <p>{{ $t('Welcome — fresh picks for you') }}</p>
                    </div>
                </div>

                <!-- New Arrivals horizontal scroll -->
                <section v-if="product.new.length" class="rail">
                    <header class="rail-head">
                        <h2>✨ {{ $t('New Arrivals') }}</h2>
                    </header>
                    <div class="rail-scroll">
                        <div class="rail-card" v-for="p in product.new" :key="'n'+p.id">
                            <product-card :product="p" :is-customer="!!customer" :customer="customer" :in-cart="cartIds.has(p.id)" :cart-qty="cartQtyMap[p.id] || 0"></product-card>
                        </div>
                    </div>
                </section>

                <!-- Featured horizontal scroll -->
                <section v-if="product.featured.length" class="rail">
                    <header class="rail-head">
                        <h2>🔥 {{ $t('Featured Products') }}</h2>
                    </header>
                    <div class="rail-scroll">
                        <div class="rail-card" v-for="p in product.featured" :key="'f'+p.id">
                            <product-card :product="p" :is-customer="!!customer" :customer="customer" :in-cart="cartIds.has(p.id)" :cart-qty="cartQtyMap[p.id] || 0"></product-card>
                        </div>
                    </div>
                </section>
            </div>
        </transition>

        <!-- ALL PRODUCTS — embedded filter + grid -->
        <section class="all-products" ref="allProducts">
            <header class="rail-head" v-show="!isFiltering">
                <h2>🛍 {{ $t('All Products') }}</h2>
            </header>

            <div class="ap-sticky-sentinel" ref="apStickySentinel"></div>
            <div class="ap-sticky" ref="apSticky" :class="{ 'is-stuck': stickyActive }">
                <!-- Filter tabs (with a "Back to home" pill on the left when filtering) -->
                <div class="ftab-strip" v-if="filterTabs.length">
                    <button v-if="isFiltering"
                            class="ftab back-pill"
                            :title="$t('Clear filters')"
                            @click="clearFilters">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"/>
                        </svg>
                    </button>
                    <button
                        v-for="tab in filterTabs"
                        :key="tab.code"
                        class="ftab"
                        :class="{ on: activeTab === tab.code }"
                        @click="setTab(tab.code)">
                        {{ tab.name }}
                    </button>
                </div>

                <!-- Filter chips for the active tab -->
                <div class="fchip-strip" v-if="activeChipOptions.length">
                    <button class="fchip" :class="{ on: !activeChipId }" @click="setChip(null)">
                        {{ $t('All') }}
                    </button>
                    <button
                        v-for="opt in activeChipOptions"
                        :key="opt.id"
                        class="fchip"
                        :class="{ on: activeChipId === opt.id }"
                        @click="setChip(opt.id)">
                        {{ opt.name || opt.label }}
                    </button>
                </div>

                <!-- Controls row: view toggle + search + product count -->
                <div class="ap-controls">
                    <div class="view-btns">
                        <button class="vbtn" :class="{ act: gridCols === 2 }" @click="setCols(2)" :title="$t('2 per row')">
                            <svg width="14" height="14" viewBox="0 0 14 14"><rect x="0" y="0" width="6" height="6" rx="1" fill="currentColor"/><rect x="8" y="0" width="6" height="6" rx="1" fill="currentColor"/><rect x="0" y="8" width="6" height="6" rx="1" fill="currentColor"/><rect x="8" y="8" width="6" height="6" rx="1" fill="currentColor"/></svg>
                        </button>
                        <button class="vbtn" :class="{ act: gridCols === 3 }" @click="setCols(3)" :title="$t('3 per row')">
                            <svg width="14" height="14" viewBox="0 0 14 14"><rect x="0" y="0" width="3.5" height="6" rx="1" fill="currentColor"/><rect x="5.25" y="0" width="3.5" height="6" rx="1" fill="currentColor"/><rect x="10.5" y="0" width="3.5" height="6" rx="1" fill="currentColor"/><rect x="0" y="8" width="3.5" height="6" rx="1" fill="currentColor"/><rect x="5.25" y="8" width="3.5" height="6" rx="1" fill="currentColor"/><rect x="10.5" y="8" width="3.5" height="6" rx="1" fill="currentColor"/></svg>
                        </button>
                    </div>

                    <div class="ap-search">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <input v-model="searchQuery"
                               :placeholder="$t('Search products')"
                               @input="onSearchInput"
                               class="ap-search-input"/>
                        <button v-if="searchQuery" class="ap-search-clear" @click="clearSearch" aria-label="Clear">×</button>
                    </div>

                    <span class="ap-count">
                        <transition name="ap-count" mode="out-in">
                            <span :key="apTotal">{{ apTotal }}</span>
                        </transition>
                        {{ $t('items') }}
                    </span>
                </div>
            </div>

            <!-- Product grid: virtualized. Only the cards visible in the
                 viewport (± buffer rows) are mounted in the DOM. Empty
                 rows above/below are represented by sized spacer divs so
                 the scrollbar tracks the full collection length. -->
            <div class="ap-grid-wrap" :style="{ minHeight: apMinHeight + 'px' }">
                <transition :name="'ap-slide-' + slideDir" mode="out-in">
                    <div v-if="apProducts.length"
                         :key="'g:' + activeTab + ':' + (activeChipId || 'all') + ':' + (searchQuery || '') + ':' + sortKey"
                         ref="apGrid"
                         class="ap-grid" :class="['product-grid-' + gridCols, { dim: apReplacing }]"
                         :style="{ paddingTop: vPadTop + 'px', paddingBottom: vPadBottom + 'px' }">
                        <product-card
                            v-for="p in visibleProducts"
                            :key="'ap'+p.id"
                            :product="p"
                            :is-customer="!!customer"
                            :customer="customer"
                            :in-cart="cartIds.has(p.id)"
                            :cart-qty="cartQtyMap[p.id] || 0"
                        ></product-card>
                    </div>

                    <div v-else-if="apReplacing || apLoading"
                         key="ap-skeleton"
                         class="ap-grid skeleton" :class="'product-grid-' + gridCols">
                        <div v-for="i in skeletonCount" :key="'sk'+i" class="skel-card">
                            <div class="skel-img"></div>
                            <div class="skel-line w70"></div>
                            <div class="skel-line w50"></div>
                        </div>
                    </div>

                    <div v-else key="ap-empty" class="ap-empty">
                        <div class="ap-empty-ic">📦</div>
                        <p>{{ $t('No products found') }}</p>
                        <p class="ap-empty-sub">{{ $t('Try a different category or search term') }}</p>
                    </div>
                </transition>

                <!-- Inline top-strip indicator while the grid is being replaced. -->
                <div v-if="apReplacing" class="ap-inline-loader" aria-live="polite"></div>
            </div>

            <div v-if="apLoading" class="ap-loading">
                <div class="ap-spinner"></div>
            </div>

            <div ref="apSentinel" class="ap-sentinel"></div>
        </section>
    </div>
</template>

<script>
    import { Carousel, Slide } from 'vue-carousel';
    import ProductCard         from '../products/card';
    import { mapState, mapActions, mapGetters } from 'vuex';

    export default {
        name: 'home',

        components: { Slide, Carousel, ProductCard },

        data: function () {
            var savedCols = 2;
            try {
                var c = parseInt(localStorage.getItem('pwa.gridCols') || '2', 10);
                if (c === 2 || c === 3) savedCols = c;
            } catch (_) {}
            return {
                sliders: [],
                categories: [],

                product: { 'new': [], 'featured': [] },

                // Filter state
                searchQuery: '',
                _searchTimer: null,
                filterTabs: [],         // [{ code, name, type }]
                activeTab: '',
                attrOptions: {},        // { tabCode: [opts] }
                activeChipId: null,

                // All-products grid
                apProducts: [],
                apPage: 1,
                apTotal: 0,
                apLoading: false,    // true during pagination (load-more)
                apReplacing: false,  // true during filter/sort/search swap
                apHasMore: true,
                apMinHeight: 0,
                _filterTimer: null,

                gridCols: savedCols,
                _io: null,

                // Sort is admin-controlled per channel; no customer-facing dropdown.
                sortKey: 'created_at-desc',

                slideDir: 'right',
                _lastChipKey: null,

                stickyActive: false,
                _stickyIo: null,

                // ── Virtualization state ──────────────────────────────────
                vRowHeight: 240,   // measured at runtime; first paint default
                vWinStart: 0,      // first visible index
                vWinEnd: 24,       // exclusive
                _vScrollTicking: false,
            };
        },

        computed: {
            ...mapState({
                customer: state => state.customer,
            }),
            ...mapGetters(['cartProductIds', 'cartQtyByProduct']),

            cartIds () { return this.cartProductIds; },
            cartQtyMap () { return this.cartQtyByProduct; },

            // ── Virtualization ────────────────────────────────────────────
            vRowsTotal () {
                return Math.ceil(this.apProducts.length / this.gridCols);
            },
            visibleProducts () {
                if (!this.apProducts.length) return [];
                var start = Math.max(0, this.vWinStart);
                var end   = Math.min(this.apProducts.length, this.vWinEnd);
                return this.apProducts.slice(start, end);
            },
            vPadTop () {
                var rows = Math.floor(this.vWinStart / this.gridCols);
                return rows * this.vRowHeight;
            },
            vPadBottom () {
                var endRow   = Math.ceil(this.vWinEnd   / this.gridCols);
                var totalRow = this.vRowsTotal;
                return Math.max(0, (totalRow - endRow) * this.vRowHeight);
            },
            heroSlides () {
                var pwa = (typeof window !== 'undefined' && window.PWA_FRONTEND) || {};
                if (pwa.hero && pwa.hero.length) return pwa.hero;
                return (this.sliders || []).map(function (s) {
                    return { image: s.image, cta_url: s.link || '', title: '', subtitle: '', badge: '', cta_text: '' };
                });
            },
            channelName () {
                try { return (window.channel && window.channel.name) || ''; } catch (_) { return ''; }
            },
            skeletonCount () {
                // Match the previously-rendered grid size so the page doesn't
                // resize between states.
                return this.gridCols === 3 ? 9 : 6;
            },
            isFiltering () {
                return !!(this.searchQuery || this.activeChipId);
            },
            activeChipOptions () {
                if (!this.activeTab) return [];
                if (this.activeTab === 'category') return this.categories;
                return this.attrOptions[this.activeTab] || [];
            },
        },

        mounted () {
            // Pre-fill search from URL query (deep links / nav from off-home)
            try {
                var q = this.$route.query && this.$route.query.q;
                if (q) this.searchQuery = String(q);
            } catch (_) {}

            // Pull admin-configured catalog sort defaults (per-channel).
            try {
                var pwa = window.PWA_FRONTEND || {};
                var cat = pwa.catalog || {};
                if (cat.default_sort) this.sortKey = cat.default_sort;
            } catch (_) {}

            this.fetchHomeBootstrap();
            this.installInfiniteScroll();
            this.installStickyObserver();
            this.installVirtualScroll();

            var self = this;
            EventBus.$on('pwa.search', this._searchHandler = function (term) {
                self.searchQuery = String(term || '');
                self.scrollBarToTop();
                self.fetchAllProducts(true);
            });
        },

        beforeDestroy () {
            if (this._io) this._io.disconnect();
            if (this._stickyIo) this._stickyIo.disconnect();
            if (this._searchTimer) clearTimeout(this._searchTimer);
            if (this._filterTimer) clearTimeout(this._filterTimer);
            if (this._searchHandler) EventBus.$off('pwa.search', this._searchHandler);
            if (this._vOnScroll) {
                window.removeEventListener('scroll', this._vOnScroll);
                window.removeEventListener('resize', this._vOnScroll);
            }
        },

        methods: {
            ...mapActions(['getCart']),

            // ── Bootstrap ─────────────────────────────────────────────────
            async fetchHomeBootstrap () {
                EventBus.$emit('show-ajax-loader');
                await Promise.all([
                    this.fetchCategories(),
                    this.fetchSliders(),
                    this.fetchNew(),
                    this.fetchFeatured(),
                    this.fetchFilterAttributes(),
                ]);
                EventBus.$emit('hide-ajax-loader');
                this.fetchAllProducts(true);
            },

            fetchCategories () {
                var self = this;
                return this.$http.get('/api/v1/descendant-categories', {
                    params: { parent_id: window.channel.root_category_id },
                }).then(function (resp) {
                    self.categories = (resp && resp.data && resp.data.data) || [];
                }).catch(function () {});
            },

            fetchSliders () {
                var self = this;
                return this.$http.get('/api/pwa/sliders').then(function (resp) {
                    var imgs = (resp && resp.data && resp.data.images) || [];
                    imgs.forEach(function (item) {
                        if (item.image && !item.image.startsWith(window.config.app_base_url)) {
                            item.image = window.config.app_base_url + item.image;
                        }
                    });
                    self.sliders = imgs;
                }).catch(function () {});
            },

            fetchNew () {
                var self = this;
                return this.$http.get('/api/v1/products', { params: { 'new': 1, limit: 8, light: 1 } })
                    .then(function (resp) { self.product.new = (resp && resp.data && resp.data.data) || []; })
                    .catch(function () {});
            },
            fetchFeatured () {
                var self = this;
                return this.$http.get('/api/v1/products', { params: { 'featured': 1, limit: 8, light: 1 } })
                    .then(function (resp) { self.product.featured = (resp && resp.data && resp.data.data) || []; })
                    .catch(function () {});
            },

            // ── Filter attributes (filter tabs are admin-controlled per channel) ─
            fetchFilterAttributes () {
                var self = this;
                return this.$http.get('/api/categories/attributes').then(function (resp) {
                    var attrs = (resp && resp.data && resp.data.data) || [];

                    // Admin can pin a specific list of attribute codes per
                    // channel. Empty list = show every filterable attribute
                    // returned by the API. "category" is always present.
                    var pinned = [];
                    try {
                        var cat = (window.PWA_FRONTEND && window.PWA_FRONTEND.catalog) || {};
                        if (Array.isArray(cat.filter_attributes)) {
                            pinned = cat.filter_attributes.map(function (s) { return String(s).toLowerCase(); });
                        }
                    } catch (_) {}

                    var tabs = [];
                    var includeCategory = !pinned.length || pinned.indexOf('category') !== -1;
                    if (includeCategory) {
                        tabs.push({ code: 'category', name: self.$t('Category'), type: 'category' });
                    }

                    var byCode = {};
                    attrs.forEach(function (a) {
                        if (a.type !== 'select') return;
                        if (a.code === 'category') return;
                        byCode[String(a.code).toLowerCase()] = a;
                    });

                    var orderedCodes;
                    if (pinned.length) {
                        // Honour the admin's order, drop "category" since it's
                        // already first (or omitted), drop unknown codes silently.
                        orderedCodes = pinned.filter(function (c) { return c !== 'category' && byCode[c]; });
                    } else {
                        orderedCodes = Object.keys(byCode);
                    }

                    orderedCodes.forEach(function (code) {
                        var a = byCode[code];
                        tabs.push({ code: a.code, name: a.name, type: a.type, id: a.id });
                        if (a.options && a.options.length) {
                            self.$set(self.attrOptions, a.code, a.options);
                        } else {
                            self.loadAttrOptions(a);
                        }
                    });

                    self.filterTabs = tabs;
                    if (!self.activeTab && tabs.length) {
                        self.activeTab = tabs[0].code;
                    }
                }).catch(function () {});
            },

            loadAttrOptions (attr) {
                var self = this;
                this.$http.get('/api/categories/attributes/' + attr.id + '/options').then(function (resp) {
                    var opts = (resp && resp.data && resp.data.data) || [];
                    self.$set(self.attrOptions, attr.code, opts);
                }).catch(function () {});
            },

            // ── All products grid ─────────────────────────────────────────
            buildApParams (page) {
                // `light=1` strips heavy nested relations (variants × price
                // indices × inventory indices × etc.) from the response.
                // We don't render any of those on grid cards, so the smaller
                // payload + faster query is a free win.
                var p = { limit: 12, page: page, light: 1 };
                if (this.searchQuery) p.name = this.searchQuery;
                if (this.activeTab && this.activeChipId) {
                    if (this.activeTab === 'category') {
                        p.category_id = this.activeChipId;
                    } else {
                        p[this.activeTab] = this.activeChipId;
                    }
                }
                // Sort: "{field}-{order}" → Bagisto expects sort + order params.
                var key = this.sortKey || 'created_at-desc';
                if (key === 'featured-desc') {
                    p.featured = 1;
                    p.sort  = 'created_at';
                    p.order = 'desc';
                } else {
                    var dash = key.lastIndexOf('-');
                    if (dash > 0) {
                        p.sort  = key.substring(0, dash);
                        p.order = key.substring(dash + 1);
                    }
                }
                return p;
            },

            fetchAllProducts (reset) {
                // Pagination guard: prevent concurrent fetches.
                if (!reset && (!this.apHasMore || this.apLoading || this.apReplacing)) return;

                // Lock the wrapper height to the current grid before swapping
                // results so the page never collapses or resizes.
                if (reset) {
                    this.lockGridHeight();
                    this.apReplacing = true;
                } else {
                    this.apLoading = true;
                }

                var self = this;
                var page = reset ? 1 : this.apPage;
                var term = (this.searchQuery || '').trim();

                // When the user is typing a search term, hit the fuzzy
                // endpoint (handles typos via Levenshtein). When not, hit
                // Bagisto's regular paginated products API for filtering.
                var promise;
                if (term.length >= 2) {
                    promise = this.$http.get('/api/v1/pwa-frontend/search', { params: { q: term, limit: 24 } })
                        .then(function (resp) {
                            var rows = (resp && resp.data && resp.data.data) || [];
                            var meta = (resp && resp.data && resp.data.meta) || {};
                            return { rows: rows, meta: { total: meta.total || rows.length, current_page: 1, last_page: 1 } };
                        });
                } else {
                    promise = this.$http.get('/api/v1/products', { params: this.buildApParams(page) })
                        .then(function (resp) {
                            return {
                                rows: (resp && resp.data && resp.data.data) || [],
                                meta: (resp && resp.data && resp.data.meta) || {},
                            };
                        });
                }

                promise.then(function (out) {
                    var rows = out.rows;
                    var meta = out.meta;
                    if (reset) {
                        // Swap in place — the grid never goes empty between sets.
                        self.apProducts = rows;
                        self.apPage = 2;
                    } else {
                        self.apProducts = self.apProducts.concat(rows);
                        if (rows.length) self.apPage = page + 1;
                    }
                    self.apTotal = meta.total != null ? meta.total : self.apProducts.length;
                    self.apHasMore = !!(meta.current_page && meta.last_page && meta.current_page < meta.last_page);
                }).catch(function () {
                    self.apHasMore = false;
                }).finally(function () {
                    self.apReplacing = false;
                    self.apLoading = false;
                    // Release the height lock after the new grid is committed
                    // so future scroll-driven height changes still work.
                    self.$nextTick(function () {
                        self.apMinHeight = 0;
                        self.recomputeWindow();
                    });
                });
            },

            lockGridHeight () {
                try {
                    var el = this.$refs.apGrid;
                    if (el && el.offsetHeight) {
                        this.apMinHeight = el.offsetHeight;
                    }
                } catch (_) {}
            },

            scheduleFilterFetch () {
                // Debounce rapid chip taps so we don't fire 4 requests when
                // the user picks 4 chips in a row.
                if (this._filterTimer) clearTimeout(this._filterTimer);
                var self = this;
                this._filterTimer = setTimeout(function () {
                    self.fetchAllProducts(true);
                }, 150);
            },

            installStickyObserver () {
                if (typeof IntersectionObserver === 'undefined') return;
                var self = this;
                this.$nextTick(function () {
                    var el = self.$refs.apStickySentinel;
                    if (!el) return;
                    self._stickyIo = new IntersectionObserver(function (entries) {
                        entries.forEach(function (e) {
                            self.stickyActive = !e.isIntersecting && e.boundingClientRect.top < 0;
                        });
                    }, { threshold: [0, 1] });
                    self._stickyIo.observe(el);
                });
            },

            // ── Virtualization ─────────────────────────────────────────────
            // Window the in-DOM cards so a 3,000-item collection renders only
            // the rows actually visible (± buffer). The grid's padding-top /
            // padding-bottom keeps the scrollbar tracking the full collection
            // length so infinite-scroll, sticky filter, and hash links still
            // work without surprises.
            installVirtualScroll () {
                var self = this;
                this._vOnScroll = function () {
                    if (self._vScrollTicking) return;
                    self._vScrollTicking = true;
                    requestAnimationFrame(function () {
                        self.recomputeWindow();
                        self._vScrollTicking = false;
                    });
                };
                window.addEventListener('scroll', this._vOnScroll, { passive: true });
                window.addEventListener('resize', this._vOnScroll);
                // Initial pass once the grid is in the DOM.
                this.$nextTick(function () { self.recomputeWindow(); });
            },

            recomputeWindow () {
                var grid = this.$refs.apGrid;
                if (!grid || !this.apProducts.length) return;

                // Measure one row's height the first time we see real cards.
                // Cards in our grid are roughly square + name + price + button.
                if (this.vRowHeight < 100 || this._vMeasuredCols !== this.gridCols) {
                    var firstChild = grid.querySelector('.pcard');
                    if (firstChild && firstChild.offsetHeight) {
                        this.vRowHeight = firstChild.offsetHeight + 10; // grid gap
                        this._vMeasuredCols = this.gridCols;
                    }
                }

                var rect      = grid.getBoundingClientRect();
                var vh        = window.innerHeight || document.documentElement.clientHeight;
                var rowHeight = this.vRowHeight || 240;
                var cols      = this.gridCols;

                // Distance between viewport top and grid's top edge.
                // Negative = grid has already scrolled above the viewport.
                var topOffset    = rect.top;
                var firstVisRow  = Math.max(0, Math.floor(-topOffset / rowHeight) - 2); // 2-row buffer
                var rowsInView   = Math.ceil(vh / rowHeight) + 4;                       // 2-row buffer each side
                var lastVisRow   = firstVisRow + rowsInView;

                var start = firstVisRow * cols;
                var end   = Math.min(this.apProducts.length, lastVisRow * cols);

                // Only mutate state when the window changed enough to matter.
                if (start !== this.vWinStart || end !== this.vWinEnd) {
                    this.vWinStart = start;
                    this.vWinEnd   = Math.max(end, start + cols * 4); // never less than 4 rows
                }
            },

            installInfiniteScroll () {
                var self = this;
                if (typeof IntersectionObserver === 'undefined') return;
                this.$nextTick(function () {
                    var el = self.$refs.apSentinel;
                    if (!el) return;
                    self._io = new IntersectionObserver(function (entries) {
                        entries.forEach(function (e) {
                            if (e.isIntersecting && self.apHasMore && !self.apLoading) {
                                self.fetchAllProducts(false);
                            }
                        });
                    }, { rootMargin: '600px 0px' });
                    self._io.observe(el);
                });
            },

            // ── Filter wires ───────────────────────────────────────────────
            // All filter changes go through scheduleFilterFetch() (150ms
            // debounce) so rapid chip taps batch into one request and the
            // grid is never thrashed. The sticky filter bar rides to the
            // viewport top so the user always sees the bar + new results.
            setTab (code) {
                if (this.activeTab === code) return;
                this.computeSlideDir(code, null);
                this.activeTab    = code;
                this.activeChipId = null;
                this.scrollBarToTop();
                this.scheduleFilterFetch();
            },
            setChip (id) {
                this.computeSlideDir(this.activeTab, id);
                this.activeChipId = id;
                this.scrollBarToTop();
                this.scheduleFilterFetch();
            },
            filterByCategory (cat) {
                this.computeSlideDir('category', cat.id);
                this.activeTab    = 'category';
                this.activeChipId = cat.id;
                this.scrollBarToTop();
                this.scheduleFilterFetch();
            },

            // Smooth-scroll the sticky filter bar to the top of the viewport
            // when a filter is tapped. Skips if the bar is already at/near
            // the top (within 8px) so we don't fight the user's scroll.
            scrollBarToTop () {
                var el = this.$refs.allProducts;
                if (!el || !el.getBoundingClientRect) return;
                var rect = el.getBoundingClientRect();
                if (rect.top <= 8 && rect.top >= -2) return;
                var target = window.scrollY + rect.top;
                try {
                    window.scrollTo({ top: target, behavior: 'smooth' });
                } catch (_) {
                    window.scrollTo(0, target);
                }
            },

            clearFilters () {
                this.activeChipId = null;
                this.searchQuery  = '';
                this._lastChipKey = null;
                this.slideDir     = 'left';
                this.scrollBarToTop();
                this.scheduleFilterFetch();
            },

            computeSlideDir (tab, chipId) {
                // Index of the new chip in its tab's option list. Items later
                // in the list slide in from the right; earlier ones slide in
                // from the left. Mirrors the iOS segmented-control feel.
                var opts;
                if (tab === 'category') opts = this.categories;
                else                    opts = this.attrOptions[tab] || [];
                var newIdx = chipId == null ? -1 : opts.findIndex(function (o) { return o.id === chipId; });
                if (this._lastChipKey == null || newIdx === -1 || this._lastChipKey === -1) {
                    this.slideDir = 'right';
                } else {
                    this.slideDir = newIdx >= this._lastChipKey ? 'right' : 'left';
                }
                this._lastChipKey = newIdx;
            },

            onSearchInput () {
                if (this._searchTimer) clearTimeout(this._searchTimer);
                var self = this;
                // First keystroke: snap the bar to the top so the grid uses
                // the rest of the screen. Subsequent keystrokes don't scroll
                // again because scrollBarToTop bails when already at top.
                this.scrollBarToTop();
                this._searchTimer = setTimeout(function () {
                    self.fetchAllProducts(true);
                }, 320);
            },
            clearSearch () {
                this.searchQuery = '';
                this.fetchAllProducts(true);
            },

            setCols (n) {
                this.gridCols = n;
                try { localStorage.setItem('pwa.gridCols', String(n)); } catch (_) {}
                // Force a re-measure (row height differs between 2 and 3 col).
                this.vRowHeight = 0;
                this._vMeasuredCols = null;
                var self = this;
                this.$nextTick(function () { self.recomputeWindow(); });
            },
        },
    };
</script>

<style lang="scss" scoped>
    .home-v3 {
        padding-bottom: 80px;
    }

    /* Upper sections collapse smoothly when the user starts filtering. */
    .home-upper { will-change: opacity, transform; }
    .upper-fade-enter-active,
    .upper-fade-leave-active {
        transition: opacity 0.25s ease, transform 0.28s ease, max-height 0.32s ease;
        overflow: hidden;
    }
    .upper-fade-enter,
    .upper-fade-leave-to {
        opacity: 0;
        transform: translateY(-8px);
        max-height: 0;
    }
    .upper-fade-enter-to,
    .upper-fade-leave {
        opacity: 1;
        max-height: 1500px;
    }

    /* Back-pill on the filter tab strip when filtering is active. */
    .ftab.back-pill {
        background: var(--p, #c8420a);
        color: #fff;
        border-color: var(--p, #c8420a);
        padding: 7px 10px;
        display: flex; align-items: center; justify-content: center;
        flex: 0 0 auto;
    }
    .ftab.back-pill:hover { filter: brightness(1.08); }

    /* Search */
    .home-search-row { padding: 8px 12px 0; }
    .home-search {
        display: flex; align-items: center; gap: 10px;
        background: #f6f1eb;
        border-radius: 14px;
        padding: 9px 12px;
        color: #9a7a5a;
        font-size: 14px;
        border: 1px solid rgba(0,0,0,0.04);
    }
    .home-search-input {
        flex: 1; min-width: 0;
        border: 0; background: transparent;
        outline: none;
        font-size: 13px;
        color: #1a0800;
    }
    .home-search-input::placeholder { color: #9a7a5a; }
    .home-search-clear {
        border: 0; background: transparent;
        font-size: 18px; color: #9a7a5a;
        width: 24px; height: 24px;
        cursor: pointer;
        line-height: 1;
    }

    /* Hero */
    .hero-carousel {
        margin: 12px 12px 14px;
        /* Lock the height up front so vue-carousel's wrappers (which don't
           respect aspect-ratio on the descendant) can't reflow. The 16:9 ratio
           is enforced through the wrapper itself. */
        aspect-ratio: 16 / 9;
        max-height: 220px;
        border-radius: 18px;
        overflow: hidden;
        background-color: #f5f0eb;
    }
    .hero-carousel ::v-deep .VueCarousel,
    .hero-carousel ::v-deep .VueCarousel-wrapper,
    .hero-carousel ::v-deep .VueCarousel-inner,
    .hero-carousel ::v-deep .VueCarousel-slide {
        height: 100% !important;
    }
    .hero-carousel ::v-deep .VueCarousel-wrapper { background: transparent !important; }
    .hero-card {
        display: block;
        position: relative;
        width: 100%;
        aspect-ratio: 16 / 9;
        border-radius: 18px;
        overflow: hidden;
        background-color: #f5f0eb;
        text-decoration: none;
        color: #fff;
    }
    .hero-img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .hero-card::after {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(180deg, rgba(0,0,0,0) 40%, rgba(0,0,0,0.55) 100%);
        pointer-events: none;
        z-index: 1;
    }
    .hero-text { z-index: 2; }
    .hero-badge { z-index: 2; }
    .hero-badge {
        position: absolute; top: 12px; left: 12px;
        background: var(--p, #c8420a);
        color: #fff; font-size: 10px; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.5px;
        padding: 4px 9px; border-radius: 99px;
        z-index: 2;
    }
    .hero-text {
        position: absolute; left: 16px; right: 16px; bottom: 14px;
        z-index: 2;
    }
    .hero-text h2 {
        font-size: 18px; font-weight: 700; margin: 0 0 4px;
        line-height: 1.25; color: #fff;
        text-shadow: 0 1px 4px rgba(0,0,0,0.4);
    }
    .hero-text p {
        font-size: 12px; margin: 0 0 6px;
        opacity: 0.94;
        text-shadow: 0 1px 3px rgba(0,0,0,0.4);
    }
    .hero-cta {
        display: inline-block;
        background: rgba(255,255,255,0.95);
        color: #1a0800;
        font-size: 11px; font-weight: 700;
        padding: 5px 11px; border-radius: 99px;
    }
    .hero-fallback {
        margin: 12px 12px 14px;
        aspect-ratio: 16 / 9;
        max-height: 220px;
        border-radius: 18px;
        background: linear-gradient(135deg, var(--p, #c8420a), var(--acc, #f5a623));
        position: relative;
        overflow: hidden;
        display: flex; align-items: center;
        padding: 0 22px;
        color: #fff;
    }
    .hero-fallback::after {
        content: '';
        position: absolute;
        right: -40px; top: -40px;
        width: 180px; height: 180px;
        background: radial-gradient(circle, rgba(255,255,255,0.25), transparent 70%);
        pointer-events: none;
    }
    .hero-fb-text { position: relative; z-index: 1; }
    .hero-fb-text h2 {
        font-size: 22px; font-weight: 800; margin: 0 0 4px;
        letter-spacing: -0.3px;
    }
    .hero-fb-text p {
        font-size: 12px; margin: 0; opacity: 0.95; font-weight: 500;
    }

    /* Category chip strip */
    .chip-strip { margin: 4px 0 10px; }
    .chip-list {
        display: flex; gap: 12px;
        overflow-x: auto;
        padding: 2px 12px 8px;
        margin: 0;
        list-style: none;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
    }
    .chip-list::-webkit-scrollbar { display: none; }
    .cat-chip {
        flex: 0 0 auto;
        width: 70px;
        text-align: center;
        cursor: pointer;
    }
    .cat-chip-img {
        width: 60px; height: 60px;
        margin: 0 auto 6px;
        border-radius: 16px;
        overflow: hidden;
        background: #f5f0eb;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    }
    .cat-chip-img img { width: 100%; height: 100%; object-fit: cover; }
    .cat-chip-fallback { font-size: 22px; font-weight: 700; color: #9a7a5a; }
    .cat-chip-name {
        font-size: 11px; font-weight: 600; line-height: 1.25;
        color: #1a0800;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        min-height: 28px;
    }

    /* Rails */
    .rail {
        margin: 4px 0 16px;
        min-height: 240px; /* reserve before products fetch returns */
    }
    .rail-head {
        display: flex; justify-content: space-between; align-items: center;
        padding: 0 14px;
        margin-bottom: 8px;
    }
    .rail-head h2 {
        font-size: 15px; font-weight: 800; margin: 0;
        color: var(--p, #c8420a);
    }
    .rail-scroll {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding: 2px 12px 12px;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
    }
    .rail-scroll::-webkit-scrollbar { display: none; }
    .rail-card {
        flex: 0 0 150px;
        width: 150px;
    }

    /* All-products section */
    .all-products {
        margin: 8px 0 20px;
        padding: 0;
    }

    .ap-sticky {
        position: sticky;
        top: 0;
        z-index: 40;
        background: #faf8f5;
        padding-top: 4px;
        /* Reserve space for tabs (~38) + chips (~38) + controls (~38) so
           the bar doesn't grow as data trickles in. Keeps CLS near zero. */
        min-height: 124px;
        transition: box-shadow 0.22s ease, background-color 0.22s ease;
    }
    /* When the sticky bar reaches the viewport top, the shadow appears. */
    .ap-sticky.is-stuck {
        background: rgba(250, 248, 245, 0.96);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        box-shadow: 0 6px 14px rgba(0,0,0,0.06);
    }

    .ftab-strip {
        display: flex;
        gap: 6px;
        overflow-x: auto;
        padding: 4px 12px 6px;
        scrollbar-width: none;
    }
    .ftab-strip::-webkit-scrollbar { display: none; }
    .ftab {
        flex: 0 0 auto;
        background: #fff;
        border: 1.5px solid rgba(0,0,0,0.10);
        color: rgba(0,0,0,0.7);
        border-radius: 99px;
        padding: 7px 14px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: 0.15s;
        white-space: nowrap;
    }
    .ftab.on {
        background: var(--dark, #1a0800);
        color: #fff;
        border-color: var(--dark, #1a0800);
    }

    .fchip-strip {
        display: flex;
        gap: 6px;
        overflow-x: auto;
        padding: 6px 12px 8px;
        scrollbar-width: none;
    }
    .fchip-strip::-webkit-scrollbar { display: none; }
    .fchip {
        flex: 0 0 auto;
        background: #f5f0eb;
        border: 0;
        color: #5a3a1a;
        border-radius: 99px;
        padding: 6px 12px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
    }
    .fchip.on {
        background: var(--p, #c8420a);
        color: #fff;
    }

    .ap-controls {
        display: flex; align-items: center; gap: 8px;
        padding: 6px 14px 8px;
    }
    .ap-controls > .view-btns { flex: 0 0 auto; }
    .ap-controls > .ap-count  { flex: 0 0 auto; }

    .ap-search {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 6px;
        background: #f5f0eb;
        border-radius: 99px;
        padding: 6px 10px;
        color: #9a7a5a;
        transition: background 0.18s, box-shadow 0.18s;
    }
    .ap-search:focus-within {
        background: #fff;
        box-shadow: 0 0 0 1.5px var(--p, #c8420a) inset;
        color: var(--p, #c8420a);
    }
    .ap-search-input {
        flex: 1; min-width: 0;
        border: 0; background: transparent;
        outline: none;
        font-size: 12px;
        color: #1a0800;
    }
    .ap-search-input::placeholder { color: #9a7a5a; }
    .ap-search-clear {
        border: 0; background: transparent;
        font-size: 14px; color: #9a7a5a;
        width: 18px; height: 18px;
        cursor: pointer; line-height: 1; padding: 0;
    }
    .view-btns {
        display: flex; gap: 3px;
        background: #f2ede8;
        padding: 3px;
        border-radius: 8px;
    }
    .vbtn {
        width: 28px; height: 26px;
        border: 0; background: transparent;
        border-radius: 6px;
        color: #9a7a5a;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: 0.18s;
    }
    .vbtn.act {
        background: #fff;
        color: var(--p, #c8420a);
        box-shadow: 0 1px 3px rgba(0,0,0,0.07);
    }
    .ap-count {
        font-size: 11px; color: #9a7a5a; font-weight: 600;
    }

    .ap-grid-wrap {
        position: relative;
        transition: min-height 0.2s ease;
        overflow: hidden;
    }
    .ap-grid {
        padding: 0 12px;
        display: grid;
        gap: 10px;
        transition: opacity 0.22s ease;
    }

    /* Slide-left / slide-right transitions for filter swaps. */
    .ap-slide-right-enter-active,
    .ap-slide-right-leave-active,
    .ap-slide-left-enter-active,
    .ap-slide-left-leave-active {
        transition: transform 0.28s cubic-bezier(.22,.61,.36,1), opacity 0.22s ease;
    }
    .ap-slide-right-enter   { transform: translateX(8%);  opacity: 0; }
    .ap-slide-right-leave-to{ transform: translateX(-8%); opacity: 0; }
    .ap-slide-left-enter    { transform: translateX(-8%); opacity: 0; }
    .ap-slide-left-leave-to { transform: translateX(8%);  opacity: 0; }
    .ap-grid.product-grid-2 { grid-template-columns: 1fr 1fr; }
    .ap-grid.product-grid-3 { grid-template-columns: 1fr 1fr 1fr; gap: 7px; }

    /* Old grid stays visible at reduced opacity while a new set is loading. */
    .ap-grid.dim {
        opacity: 0.45;
        pointer-events: none;
    }

    .ap-empty {
        text-align: center;
        color: #9a7a5a;
        font-size: 13px;
        min-height: 220px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 24px 20px;
    }
    .ap-empty-ic { font-size: 38px; margin-bottom: 6px; }
    .ap-empty p { margin: 0; }
    .ap-empty-sub { font-size: 11px; opacity: 0.75; margin-top: 4px !important; }

    /* Skeleton — shown only when there are NO previous products to dim
       (first load or empty grid being repopulated). */
    .ap-grid.skeleton .skel-card {
        background: #fff;
        border-radius: var(--cr, 18px);
        border: 1px solid rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .skel-img,
    .skel-line {
        background: linear-gradient(90deg, #ede5db 25%, #f5f0eb 50%, #ede5db 75%);
        background-size: 200% 100%;
        animation: skel-shimmer 1.2s infinite;
    }
    .skel-img  { width: 100%; aspect-ratio: 1 / 1; }
    .skel-line { height: 11px; border-radius: 4px; margin: 8px 12px 0; }
    .skel-line.w70 { width: 70%; }
    .skel-line.w50 { width: 50%; margin-bottom: 14px; }
    @keyframes skel-shimmer {
        0%   { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* Inline indeterminate progress bar across the top of the grid wrapper
       while a filter swap is in flight. Calmer than a blocking spinner. */
    .ap-inline-loader {
        position: absolute;
        top: 0; left: 12px; right: 12px;
        height: 2px;
        background: rgba(200, 66, 10, 0.12);
        overflow: hidden;
        border-radius: 2px;
        pointer-events: none;
        z-index: 5;
    }
    .ap-inline-loader::after {
        content: '';
        position: absolute;
        inset: 0;
        background: var(--p, #c8420a);
        transform: translateX(-60%);
        animation: ap-bar 1.1s ease-in-out infinite;
        width: 40%;
    }
    @keyframes ap-bar {
        0%   { transform: translateX(-60%); }
        50%  { transform: translateX(80%);  }
        100% { transform: translateX(180%); }
    }

    /* Count: smooth swap when total changes (the inner number animates). */
    .ap-count {
        display: inline-flex;
        align-items: baseline;
        gap: 3px;
        white-space: nowrap;
    }
    .ap-count > span {
        display: inline-block;
        min-width: 1ch;
    }
    .ap-count-enter-active,
    .ap-count-leave-active { transition: opacity 0.18s ease, transform 0.18s ease; }
    .ap-count-enter        { opacity: 0; transform: translateY(-3px); }
    .ap-count-leave-to     { opacity: 0; transform: translateY(3px); }
    .ap-count-leave-active { position: absolute; }

    .ap-loading {
        display: flex; justify-content: center;
        padding: 16px 0;
    }
    .ap-spinner {
        width: 26px; height: 26px;
        border: 3px solid #e2d5c8;
        border-top-color: var(--p, #c8420a);
        border-radius: 50%;
        animation: home-spin 0.8s linear infinite;
    }
    @keyframes home-spin { to { transform: rotate(360deg); } }

    .ap-sentinel { height: 1px; }
</style>

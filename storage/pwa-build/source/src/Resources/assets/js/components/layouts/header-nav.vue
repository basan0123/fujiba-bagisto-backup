<template>
    <header class="navbar navbar-top" :class="{ 'header-hidden': isScrollingDown || zoomActive }">
        <div class="nav-left">
            <!-- Back: appears on every route except home, always FIRST. -->
            <button v-if="$route.name !== 'home'" class="nav-btn" @click="handleBack" aria-label="Back">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
            </button>
            <!-- Hamburger: always available so the side drawer is reachable. -->
            <button class="nav-btn" @click="handleMaskClick" aria-label="Menu">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                    <line x1="3" y1="6"  x2="21" y2="6"/>
                    <line x1="3" y1="12" x2="21" y2="12"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
        </div>

        <router-link :to="'/'" class="nav-logo">
            <img v-if="logo_url" :src="logo_url" :alt="app_name" class="shop-logo"/>
            <span v-else class="shop-name">{{ app_name }}</span>
        </router-link>

        <div class="nav-right">
            <router-link class="nav-btn" :to="{ name: 'profile' }" aria-label="Profile">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </router-link>

            <router-link class="nav-btn cart-btn" :to="'/checkout/cart'" aria-label="Cart">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9"  cy="21" r="1"/>
                    <circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/>
                </svg>
                <span class="count" v-if="cartCount">{{ cartCount }}</span>
            </router-link>
        </div>
    </header>
</template>

<script>
    import {
        mapState,
        mapActions
    } from 'vuex';

    export default {
        name: 'header-nav',

        data () {
            return {
                isScrollingDown: false,
                _lastScrollY: 0,
                _ticking: false,
                // Set true while a product image lightbox is open. Hides the
                // top nav until the lightbox is closed — independent of the
                // scroll-direction logic so scrolling won't bring it back.
                zoomActive: false,
            }
        },

        mounted () {
            this.getCart();
            this._lastScrollY = window.scrollY;
            window.addEventListener('scroll', this._onScroll, { passive: true });

            var self = this;
            this._onZoomOpen  = function () { self.zoomActive = true;  };
            this._onZoomClose = function () { self.zoomActive = false; };
            EventBus.$on('pwa.zoom.open',  this._onZoomOpen);
            EventBus.$on('pwa.zoom.close', this._onZoomClose);
        },

        beforeDestroy () {
            window.removeEventListener('scroll', this._onScroll);
            EventBus.$off('pwa.zoom.open',  this._onZoomOpen);
            EventBus.$off('pwa.zoom.close', this._onZoomClose);
        },

        computed: {
            app_name () {
                return window.config.app_short_name ? window.config.app_short_name : 'Bagisto';
            },

            // Channel-aware logo URL (set by /api/v1/pwa-frontend/config or
            // window.channel injected by master.blade). Falls back to text.
            logo_url () {
                try {
                    if (window.PWA_FRONTEND && window.PWA_FRONTEND.brand && window.PWA_FRONTEND.brand.logo) {
                        return window.PWA_FRONTEND.brand.logo;
                    }
                    if (window.channel && window.channel.logo_url) {
                        return window.channel.logo_url;
                    }
                } catch (_) {}
                return null;
            },

            ...mapState({
                cart: state => state.cart,
                optimisticDelta: state => state.cartOptimisticDelta || 0,
            }),

            cartCount () {
                var base = (this.cart && this.cart.items) ? this.cart.items.length : 0;
                return base + this.optimisticDelta;
            },
        },

        methods: {
            ...mapActions([
                'getCart',
            ]),

            _onScroll () {
                if (this._ticking) return;
                this._ticking = true;
                requestAnimationFrame(() => {
                    const y = window.scrollY;
                    const delta = y - this._lastScrollY;
                    if (Math.abs(delta) >= 10) {
                        this.isScrollingDown = delta > 0;
                        this._lastScrollY = y;
                    }
                    this._ticking = false;
                });
            },

            handleMaskClick () {
                this.$emit('toggleDrawer')
            },

            handleBack () {
                if (
                    window.history.length <= 2
                    || this.$route.name == 'order-success'
                ) {
                    this.$router.push({name: 'home'})
                } else {
                    this.$router.back();
                }
            },
        }
    }
</script>

<style scoped lang="scss">
    header.navbar.navbar-top {
        position: fixed;
        top: 32px; /* sit below topbar */
        left: 0; right: 0;
        height: 52px;
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.08);
        z-index: 100;
        display: flex;
        align-items: center;
        padding: 0 8px;
        transition: transform 0.3s ease, top 0.3s ease;
        will-change: transform, top;

        .nav-left, .nav-right {
            display: flex;
            align-items: center;
            gap: 4px;
            flex: 0 0 auto;
        }

        .nav-logo {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #1a0800;
            font-weight: 700;
            font-size: 16px;
            min-width: 0;
            padding: 0 8px;
        }
        .shop-logo {
            max-height: 32px;
            max-width: 150px;
            object-fit: contain;
        }
        .shop-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-btn {
            background: transparent;
            border: 0;
            color: #1a0800;
            width: 34px; height: 34px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            text-decoration: none;
            position: relative;
            -webkit-tap-highlight-color: transparent;
            flex: 0 0 auto;
        }
        .nav-btn:hover { background: #f5f0eb; }

        .cart-btn .count {
            position: absolute;
            top: 4px; right: 4px;
            min-width: 16px; height: 16px;
            background: var(--p, #c8420a);
            color: #fff;
            border-radius: 99px;
            font-size: 9px;
            font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            padding: 0 4px;
        }

        &.header-hidden {
            transform: translateY(calc(-100% - 32px));
        }
    }
</style>
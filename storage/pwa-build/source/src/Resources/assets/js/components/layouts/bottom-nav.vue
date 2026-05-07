<template>
    <nav class="botnav" id="botNav" :class="{ hide: isScrollingDown }" aria-label="Mobile bottom navigation">
        <router-link :to="{ name: 'home' }" class="bni" :class="{ act: isActive('home') }">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <span>{{ $t('nav.home') !== 'nav.home' ? $t('nav.home') : $t('Home') }}</span>
        </router-link>

        <router-link :to="ordersTarget" class="bni" :class="{ act: isActive('orders') }">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 01-8 0"/>
            </svg>
            <span>{{ $t('Orders') }}</span>
        </router-link>

        <router-link :to="{ name: 'cart' }" class="bni" :class="{ act: isActive('cart') }">
            <span class="bni-cart-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <path d="M16 10a4 4 0 01-8 0"/>
                </svg>
                <span v-if="cartCount" class="bni-badge">{{ cartCount > 99 ? '99+' : cartCount }}</span>
            </span>
            <span>{{ $t('nav.cart') !== 'nav.cart' ? $t('nav.cart') : $t('Cart') }}</span>
        </router-link>

        <router-link :to="{ name: 'wishlist' }" class="bni" :class="{ act: isActive('wishlist') }">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
            </svg>
            <span>{{ $t('nav.wishlist') !== 'nav.wishlist' ? $t('nav.wishlist') : $t('Wishlist') }}</span>
        </router-link>

        <router-link :to="accountTarget" class="bni" :class="{ act: isActive('profile') }">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <span>{{ $t('nav.profile') !== 'nav.profile' ? $t('nav.profile') : $t('Account') }}</span>
        </router-link>
    </nav>
</template>

<script>
    import { mapState, mapActions } from 'vuex';

    export default {
        name: 'bottom-nav',

        data () {
            return {
                isScrollingDown: false,
                _lastScrollY: 0,
                _ticking: false,
            };
        },

        computed: {
            ...mapState({
                cart: state => state.cart,
                customer: state => state.customer,
                optimisticDelta: state => state.cartOptimisticDelta || 0,
            }),
            cartCount () {
                // Match the cart page header which shows "X Item(s)" by line
                // count, not summed quantity. The optimistic delta is the
                // count of pending add-to-cart actions whose API response
                // hasn't reconciled into cart.items yet.
                var base = (this.cart && this.cart.items) ? this.cart.items.length : 0;
                return base + this.optimisticDelta;
            },
            isGuest () {
                return ! this.customer || ! this.customer.id;
            },
            ordersTarget () {
                return this.isGuest
                    ? { name: 'login-register', query: { redirect: '/customer/account/orders' } }
                    : { name: 'order-list' };
            },
            accountTarget () {
                return this.isGuest
                    ? { name: 'login-register' }
                    : { name: 'profile' };
            },
        },

        mounted () {
            this.getCart();
            this.getCustomer();
            this._lastScrollY = window.scrollY;
            window.addEventListener('scroll', this._onScroll, { passive: true });
        },

        beforeDestroy () {
            window.removeEventListener('scroll', this._onScroll);
        },

        methods: {
            ...mapActions(['getCart', 'getCustomer']),

            isActive (key) {
                var name = (this.$route && this.$route.name) || '';
                if (key === 'home')     return name === 'home';
                if (key === 'orders')   return name && name.indexOf('order') === 0; // order-list, order-success, order-detail
                if (key === 'cart')     return name === 'cart' || name === 'chat-checkout' || name === 'onepage';
                if (key === 'wishlist') return name === 'wishlist';
                if (key === 'profile')  return name && (name === 'profile' || name.indexOf('account') === 0 || name.indexOf('dashboard') === 0);
                return false;
            },

            _onScroll () {
                if (this._ticking) return;
                this._ticking = true;
                var self = this;
                requestAnimationFrame(function () {
                    var y = window.scrollY;
                    var delta = y - self._lastScrollY;
                    if (Math.abs(delta) >= 10) {
                        self.isScrollingDown = delta > 0 && y > 80;
                        self._lastScrollY = y;
                    }
                    self._ticking = false;
                });
            },
        },
    };
</script>

<style lang="scss" scoped>
    .botnav {
        position: fixed;
        bottom: 0; left: 0; right: 0;
        height: 56px;
        background: #fff;
        border-top: 1px solid #e2d5c8;
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        z-index: 99;
        padding-bottom: env(safe-area-inset-bottom, 0);
        transition: transform 0.3s ease;
        will-change: transform;
    }
    .botnav.hide { transform: translateY(calc(100% + env(safe-area-inset-bottom, 0px))); }

    .bni {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 2px;
        color: #9a7a5a;
        text-decoration: none;
        font-size: 9px; font-weight: 600;
        letter-spacing: 0; text-transform: none;
        position: relative;
        transition: color 0.18s;
        -webkit-tap-highlight-color: transparent;
        min-width: 0;
        padding: 0 2px;
        overflow: hidden;
    }
    .bni svg { width: 20px; height: 20px; transition: transform 0.18s cubic-bezier(.34,1.56,.64,1); }
    .bni span:not(.bni-cart-wrap):not(.bni-badge) {
        font-size: 9px;
        line-height: 1.1;
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .bni.act { color: var(--p, #c8420a); }
    .bni.act svg { transform: scale(1.1); }
    .bni.act::before {
        content: '';
        position: absolute;
        top: 0; left: 50%; transform: translateX(-50%);
        width: 24px; height: 2px;
        background: var(--p, #c8420a);
        border-radius: 0 0 3px 3px;
    }

    .bni-cart-wrap { position: relative; display: inline-flex; }
    .bni-badge {
        position: absolute;
        top: -6px; right: -10px;
        min-width: 16px; height: 16px;
        border-radius: 99px;
        background: var(--p, #c8420a);
        color: #fff;
        font-size: 9px; font-weight: 800;
        display: flex; align-items: center; justify-content: center;
        padding: 0 4px;
    }

    @media (min-width: 1024px) {
        .botnav { display: none; }
    }
</style>

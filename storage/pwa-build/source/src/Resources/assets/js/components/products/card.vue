<template>
    <div class="pcard">
        <router-link :to="'/products/' + product.id" class="pcard-img-link">
            <div class="pcard-img">
                <img
                    :alt="product.name"
                    :src="product.base_image.medium_image_url"
                    width="300" height="300"
                    loading="lazy"
                    decoding="async"
                />
                <button v-if="isCustomer" class="pcard-fav"
                    :class="{ on: product.is_saved }"
                    @click.prevent.stop="moveToWishlist"
                    :aria-label="$t('Wishlist')">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
                    </svg>
                </button>
                <span v-if="!product.in_stock" class="pcard-oos">{{ $t('Out Of Stock') }}</span>
            </div>
        </router-link>

        <router-link :to="'/products/' + product.id" class="pcard-body">
            <div class="pcard-name">{{ product.name }}</div>
            <div class="pcard-foot">
                <div class="pcard-price">
                    <span v-html="product.formatted_price"></span>
                </div>
                <button v-if="product.in_stock && !atStockCap"
                    class="pcard-add"
                    :class="{ adding: adding, added: justAdded || inCart }"
                    :aria-label="$t('Add To Cart')"
                    :title="$t('Add To Cart')"
                    :disabled="adding"
                    @click.prevent.stop="quickAdd">
                    <svg v-if="justAdded || inCart" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <span v-else-if="adding" class="pcard-spin"></span>
                    <svg v-else viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/>
                    </svg>
                </button>
                <span v-else-if="!product.in_stock" class="pcard-oos-tag">{{ $t('Out Of Stock') }}</span>
                <span v-else class="pcard-oos-tag" :title="$t('Maximum quantity already in cart')">{{ $t('Max') }}</span>
            </div>
        </router-link>
    </div>
</template>

<script>

    export default {
        name: 'product-card',

        // `customer` and `inCart` come in as props so the card never subscribes
        // to the Vuex store. With 1k+ cards on screen, per-instance store
        // subscriptions caused the whole grid to re-render on every cart
        // mutation. Parent (home / category) reads cartProductIds once and
        // passes the boolean.
        props: {
            product:    { type: Object, required: true },
            isCustomer: { type: Boolean, default: false },
            customer:   { type: Object, default: null },
            inCart:     { type: Boolean, default: false },
            // Quantity already in cart for THIS product. Parent passes it
            // from the Vuex `cartQtyByProduct` getter so we avoid one store
            // subscription per card on a 1000-product grid.
            cartQty:    { type: Number, default: 0 },
        },

        data () {
            return {
                adding: false,
                justAdded: false,
                _resetTimer: null,
            };
        },

        computed: {
            // True when the user has already added every available unit of
            // this product to their cart. The `+` button hides and a tiny
            // "Max" tag shows in its place.
            atStockCap () {
                var avail = this.product && this.product.available_qty;
                if (avail == null) return false; // unmanaged stock = no cap
                return (this.cartQty + 1) > avail;
            },
        },

        beforeDestroy () {
            if (this._resetTimer) clearTimeout(this._resetTimer);
        },

        methods: {
            moveToWishlist () {
                EventBus.$emit('show-ajax-loader');

                this.$http.post('/api/v1/customer/wishlist/' + this.product.id)
                    .then(response => {

                        this.$toasted.show(response.data.message, { type: 'success' })

                        this.product.is_saved = response.data.data ? true : false;

                        EventBus.$emit('hide-ajax-loader');
                    })
                    .catch(error => {
                        this.$toasted.show(error.response.data.error, { type: 'error' })
                    });
            },

            quickAdd () {
                if (this.adding) return;

                // Guest users must log in before adding anything to a cart.
                if (! this.customer) {
                    var msg = this.$t('Please log in to add items to your cart');
                    if (this.$toasted) this.$toasted.show(msg, { type: 'info' });
                    this.$router.push({
                        name: 'login-register',
                        query: { redirect: '/products/' + this.product.id },
                    });
                    return;
                }

                // Configurable products need option selection on the PDP.
                if (this.product && this.product.type && this.product.type !== 'simple' && this.product.type !== 'virtual') {
                    this.$router.push({ name: 'product', params: { id: this.product.id } });
                    return;
                }

                // Optimistic: bump the cart-count badge immediately so the
                // bottom-nav and header reflect the action before the network
                // round-trip completes. We rely on the global axios timing
                // and the user feels the response now, not after the API call.
                this.adding = true;
                if (this._resetTimer) { clearTimeout(this._resetTimer); this._resetTimer = null; }
                this.optimisticBumpCart(+1, this.product.id);

                var self = this;
                this.$http.post('/api/v1/customer/cart/add/' + this.product.id, {
                    product_id: this.product.id,
                    quantity:   1,
                    is_buy_now: 0,
                }).then(function (resp) {
                    self.adding    = false;
                    self.justAdded = true;
                    EventBus.$emit('cart-updated');

                    // Bagisto's POST cart/add returns the full updated cart
                    // in `resp.data.data`. We push that straight into Vuex
                    // via the existing `checkout.cart.changed` channel — no
                    // 2nd round-trip needed. The legacy fallback that fired
                    // a separate getCart() was redundant network traffic.
                    if (resp && resp.data && resp.data.data) {
                        EventBus.$emit('checkout.cart.changed', resp.data.data);
                    }

                    // Brief check-mark, then revert to cart icon.
                    self._resetTimer = setTimeout(function () {
                        self.justAdded = false;
                    }, 1100);
                }).catch(function (err) {
                    // Roll back the optimistic bump on failure.
                    self.optimisticBumpCart(-1, self.product.id);
                    self.adding    = false;
                    self.justAdded = false;
                    var msg = (err && err.response && err.response.data && (err.response.data.message || err.response.data.error)) || self.$t('Error');
                    if (self.$toasted) self.$toasted.show(msg, { type: 'error' });
                });
            },

            // Bump a counter in Vuex (not the cart.items array). Both nav
            // badges read `cart.items.length + cartOptimisticDelta` so the
            // user sees the count tick up immediately. Pushing a synthetic
            // item into cart.items would crash any cart-page render that
            // expects every line to have `cartItem.product.base_image`,
            // because optimistic stubs don't carry full product data.
            optimisticBumpCart (delta, productId) {
                try {
                    var state = this.$store && this.$store.state;
                    if (!state) return;
                    state.cartOptimisticDelta = Math.max(0, (state.cartOptimisticDelta || 0) + delta);
                    if (delta > 0 && productId) {
                        if (!Array.isArray(state.cartOptimisticIds)) state.cartOptimisticIds = [];
                        if (state.cartOptimisticIds.indexOf(productId) === -1) {
                            state.cartOptimisticIds.push(productId);
                        }
                    } else if (delta < 0 && productId) {
                        if (Array.isArray(state.cartOptimisticIds)) {
                            var i = state.cartOptimisticIds.indexOf(productId);
                            if (i !== -1) state.cartOptimisticIds.splice(i, 1);
                        }
                    }
                } catch (_) {}
            },
        }
    }
</script>

<style scoped lang="scss">
    .pcard {
        background: #fff;
        border-radius: var(--cr, 18px);
        box-shadow: 0 1px 6px rgba(0,0,0,0.05);
        border: 1px solid rgba(0,0,0,0.05);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        position: relative;
        transition: transform 0.18s, box-shadow 0.18s;
    }
    .pcard:active { transform: scale(0.98); }
    .pcard:hover  { box-shadow: 0 4px 14px rgba(0,0,0,0.09); }

    .pcard-img-link { display: block; }
    .pcard-img {
        position: relative;
        width: 100%;
        aspect-ratio: 1 / 1;
        background: #f5f0eb;
        overflow: hidden;
    }
    .pcard-img img {
        width: 100%; height: 100%;
        object-fit: cover;
        display: block;
    }

    .pcard-fav {
        position: absolute;
        top: 8px; right: 8px;
        width: 30px; height: 30px;
        border-radius: 50%;
        background: rgba(255,255,255,0.85);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        border: 0;
        color: rgba(0,0,0,0.45);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        transition: 0.15s;
        z-index: 2;
    }
    .pcard-fav:hover { color: var(--p, #c8420a); }
    .pcard-fav.on    { color: var(--p, #c8420a); background: #fff; }
    .pcard-fav.on svg { fill: currentColor; }

    .pcard-oos {
        position: absolute;
        top: 10px; left: 10px;
        background: rgba(0,0,0,0.7);
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 6px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        z-index: 1;
    }

    .pcard-body {
        padding: 10px 12px 12px;
        display: block;
        color: inherit;
        text-decoration: none;
    }
    .pcard-name {
        font-size: 13px;
        font-weight: 600;
        color: #1a0800;
        line-height: 1.3;
        margin-bottom: 6px;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        min-height: 33px;
    }
    .pcard-foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .pcard-price {
        font-size: 14px;
        font-weight: 700;
        color: var(--p, #c8420a);
        flex: 1;
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .pcard-price ::v-deep .regular-price {
        text-decoration: line-through;
        opacity: 0.45;
        font-weight: 500;
        font-size: 11px;
        margin-left: 6px;
        color: rgba(0,0,0,0.6);
    }
    .pcard-price ::v-deep .special-price { color: var(--p, #c8420a); }

    .pcard-add {
        flex: 0 0 60px;
        width: 60px;
        max-width: 60px;
        background: var(--p, #c8420a);
        color: #fff;
        border: 0;
        border-radius: 8px;
        height: 32px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: filter 0.15s, transform 0.1s, background 0.2s;
    }
    .pcard-add:hover  { filter: brightness(1.08); }
    .pcard-add:active { transform: scale(0.92); }
    .pcard-add:disabled { cursor: default; }
    .pcard-add.added {
        background: #2d7a4a;
        animation: pcard-pop 0.32s cubic-bezier(.34,1.56,.64,1);
    }
    @keyframes pcard-pop {
        0%   { transform: scale(0.8); }
        60%  { transform: scale(1.18); }
        100% { transform: scale(1); }
    }
    .pcard-spin {
        width: 14px; height: 14px;
        border: 2px solid rgba(255,255,255,0.4);
        border-top-color: #fff;
        border-radius: 50%;
        animation: pcard-spin 0.7s linear infinite;
    }
    @keyframes pcard-spin { to { transform: rotate(360deg); } }
    .pcard-oos-tag {
        flex: 0 0 auto;
        font-size: 10px;
        color: #c8220a;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    /* Smaller density when 3-up grid */
    .product-grid-3 .pcard-name  { font-size: 12px; min-height: 30px; }
    .product-grid-3 .pcard-price { font-size: 12px; }
    .product-grid-3 .pcard-add   { flex: 0 0 50px; width: 50px; max-width: 50px; height: 28px; padding: 0; border-radius: 6px; }
    .product-grid-3 .pcard-add svg { width: 12px; height: 12px; }
</style>

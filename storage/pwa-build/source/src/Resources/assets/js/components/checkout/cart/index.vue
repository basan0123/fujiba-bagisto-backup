<template>
    <div class="content">
        <custom-header :title="$t('Cart')"></custom-header>

        <div class="cart-container" v-if="cart && cart.items.length">
            <h2 class="item-count">
                {{ $t('number Item(s)', { number: cart.items.length }) }}
            </h2>

            <div class="cart-item-list">
                <cart-item
                    :key='cartItem.uid'
                    :cart-item="cartItem"
                    :quantities="quantities"
                    v-for="cartItem in cart.items"
                    @quantityChanged="quantityChanged"
                    @removeItem="removeItem(cartItem)"
                    @moveToWishlist="moveToWishlist(cartItem)"
                ></cart-item>
            </div>

            <div class="panel cart-actions">
                <div class="panel-heading">
                    <div class="update-cart-link" @click="updateCart">
                        <i class="icon update-icon"></i>

                        <span>{{ $t('Update Cart') }}</span>
                    </div>

                    <div class="empty-cart-link" @click="emptyCart">
                        <i class="icon empty-cart-icon"></i>

                        <span>{{ $t('Empty Cart') }}</span>
                    </div>
                </div>

                <div class="panel-content cont-shop-row">
                    <router-link class="cont-shop-link" :to="'/'">← {{ $t('Continue Shopping') }}</router-link>
                </div>
            </div>

            <div class="total-summary">
                <accordian :title="$t('Order Summary')" :active="true">
                    <div slot="body">

                        <table>
                            <tbody>
                                <tr>
                                    <td>{{ $t('Subtotal') }}</td>
                                    <td>{{ cart.formatted_sub_total }}</td>
                                </tr>

                                <tr>
                                    <td>{{ $t('Tax') }}</td>
                                    <td>{{ cart.formatted_tax_total }}</td>
                                </tr>

                                <tr>
                                    <td>{{ $t('Discount') }}</td>
                                    <td> - {{ cart.formatted_discount }}</td>
                                </tr>

                                <tr v-if="cart.cod_fee > 0">
                                    <td>{{ $t('Cash on Delivery Fee') }}</td>
                                    <td>{{ cart.cod_fee_formatted || cart.cod_fee }}</td>
                                </tr>

                                <tr class="last">
                                    <td>{{ $t('Order Total') }}</td>
                                    <td>{{ cart.formatted_grand_total }}</td>
                                </tr>
                            </tbody>
                        </table>

                    </div>
                </accordian>
            </div>

            <div class="checkout-action">
                <span class="total-info">
                    <p>{{ $t('Amount to be paid') }}</p>
                    <h3>{{ cart.formatted_grand_total }}</h3>
                </span>

                <router-link class="checkout-btn" :to="{ name: checkoutTarget }">{{ $t('Proceed') }}</router-link>
            </div>
        </div>

        <empty-cart v-else></empty-cart>
    </div>
</template>

<script>
    import CustomHeader from '../../layouts/custom-header';
    import EmptyCart    from './empty-cart';
    import CartItem     from './item';
    import Accordian    from '../../shared/accordian';

    export default {
        name: 'cart',

        components: { CustomHeader, CartItem, Accordian, EmptyCart },

        data () {
            return {
                // Hydrate immediately from Vuex so the page paints with whatever
                // cart state we already have. A background refresh fills in the
                // up-to-date values without blocking the navigation.
                cart: (this.$store && this.$store.state && this.$store.state.cart) || null,
                quantities: {},
                debounceTimer: 0,
            }
        },

        mounted () {
            // Skip the network call when Vuex already has fresh cart state
            // (within 5 seconds — typical of users who just added an item
            // and tapped the cart). Cuts ~270 ms off the perceived
            // navigation time when arriving from add-to-cart.
            var freshAge = Date.now() - (this.$store?.state?.cartUpdatedAt || 0);
            if (this.$store?.state?.cart && freshAge < 5000) {
                this.cart = this.$store.state.cart;
                return;
            }
            this.getCart();
        },

        computed: {
            // Mobile (<= 1023px) → chat checkout. Desktop → original onepage.
            checkoutTarget () {
                if (typeof window !== 'undefined' && window.innerWidth <= 1023) {
                    return 'chat-checkout';
                }
                return 'onepage';
            },
        },

        methods: {
            getCart () {
                var this_this = this;

                // No global ajax-loader on the cart-refresh — the page is
                // already rendered from the Vuex cache, so a full-screen
                // spinner just makes the navigation feel slow.
                this.$http.get('/api/v1/customer/cart')
                    .then(function(response) {
                        this_this.cart = response.data.data;
                        EventBus.$emit('checkout.cart.changed', this_this.cart);
                    })
                    .catch(function (error) {});
            },

            moveToWishlist (item) {
                var this_this = this;

                EventBus.$emit('show-ajax-loader');

                this.$http.post('/api/v1/customer/cart/move-to-wishlist/' + item.id)
                    .then(function(response) {
                        this_this.$toasted.show(response.data.message, { type: 'success' })

                        EventBus.$emit('hide-ajax-loader');

                        this_this.cart = response.data.data;

                        EventBus.$emit('checkout.cart.changed', this_this.cart);
                    })
                    .catch(function (error) {
                        this_this.$toasted.show(error.response.data.message, { type: 'error' });
                    });
            },

            removeItem (item) {
                var this_this = this;

                EventBus.$emit('show-ajax-loader');

                this.$http.delete('/api/v1/customer/cart/remove/' + item.id)
                    .then(function(response) {

                        this_this.$toasted.show(response.data.message, { type: 'success' })

                        EventBus.$emit('hide-ajax-loader');

                        this_this.cart = response.data.data;

                        EventBus.$emit('checkout.cart.changed', this_this.cart);
                    })
                    .catch(function (error) {});
            },

            emptyCart () {
                var this_this = this;

                EventBus.$emit('show-ajax-loader');

                this.$http.get('/api/v1/customer/cart/empty')
                    .then(function(response) {
                        this_this.$toasted.show(response.data.message, { type: 'success' })

                        EventBus.$emit('hide-ajax-loader');

                        this_this.cart = null;

                        EventBus.$emit('checkout.cart.changed', this_this.cart);
                    })
                    .catch(function (error) {});
            },

            updateCart () {
                var this_this = this;

                EventBus.$emit('show-ajax-loader');

                this.$http.put('/api/v1/customer/cart/update', { 'qty': this.quantities })
                    .then(function(response) {
                        this_this.$toasted.show(response.data.message, { type: 'success' })

                        EventBus.$emit('hide-ajax-loader');

                        this_this.cart = response.data.data;

                        EventBus.$emit('checkout.cart.changed', this_this.cart);
                    })
                    .catch(function (error) {});
            },

            quantityChanged: function () {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => {
                    this.updateCart();
                }, 2000);
            }
        }
    }
</script>

<style scoped lang="scss">
    .content {
        position: absolute;
        bottom: 0;
        top: 0;
        width: 100%;

        .cart-container {
            padding-bottom: 60px;

            .item-count {
                font-weight: 700;
                font-size: 12px;
                color: rgba(0, 0, 0, 0.86);
                padding: 16px;
                background: #ffffff;
                border-bottom: 1px solid rgba(0, 0, 0 ,0.12);
                text-transform: uppercase;
            }

            .total-summary {
                margin-bottom: 16px;

                table {
                    width: 100%;

                    tr {
                        td {
                            padding: 8px 0;

                            &:first-child {
                                font-size: 14px;
                                color: rgba(0, 0, 0, 0.56);
                            }

                            &:last-child {
                                font-size: 14px;
                                text-align: right;
                            }
                        }

                        &.last {
                            td {
                                padding: 16px 0 0 0;
                                border-top: 1px solid rgba(0, 0, 0, 0.12);

                                &:first-child {
                                    font-size: 18px;
                                    color: rgba(0, 0, 0, 0.56);
                                }

                                &:last-child {
                                    font-size: 18px;
                                    font-weight: 600;
                                    color: rgba(0, 0, 0, 0.87);
                                }
                            }
                        }
                    }
                }
            }

            .panel.cart-actions {
                font-weight: 600;
                font-size: 14px;
                background: #fff;
                margin-top: 28px;

                .panel-heading {
                    padding: 0;
                    border-bottom: 1px solid rgba(0, 0, 0 ,0.12);

                    .update-cart-link {
                        float: left;
                        width: 50%;
                        display: inline-block;
                        padding: 16px;
                        cursor: pointer;
                        color: rgba(0, 0, 0, 0.87);

                        .icon {
                            float: left;
                            margin-right: 8px;
                            opacity: 0.56;
                        }

                        span {
                            line-height: 24px;
                        }
                    }

                    .empty-cart-link {
                        width: 50%;
                        display: inline-block;
                        padding: 16px;
                        cursor: pointer;
                        color: rgba(0, 0, 0, 0.87);

                        .icon {
                            float: left;
                            margin-right: 8px;
                            opacity: 0.56;
                        }

                        span {
                            line-height: 24px;
                        }
                    }
                }

                .panel-content.cont-shop-row {
                    border-bottom: 0;
                    padding: 12px 16px;
                    background: transparent;
                    text-align: center;
                }
                .panel-content .cont-shop-link {
                    border: 0;
                    background: transparent;
                    font-size: 12px;
                    font-weight: 600;
                    color: #9a7a5a;
                    text-decoration: none;
                    padding: 0;
                    display: inline-block;
                }
                .panel-content .cont-shop-link:hover { color: var(--p, #c8420a); }
            }


            .checkout-action {
                position: fixed;
                bottom: 0; /* bottom-nav is hidden on cart page */
                left: 0;
                right: 0;
                padding: 10px 16px calc(10px + env(safe-area-inset-bottom, 0px));
                width: 100%;
                background: #ffffff;
                box-shadow: 0 -6px 16px rgba(0,0,0,0.06);
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                z-index: 30;

                .total-info {
                    flex: 1;
                    min-width: 0;

                    p {
                        font-weight: 600;
                        font-size: 11px;
                        color: rgba(0, 0, 0, 0.55);
                        margin: 0 0 2px;
                    }

                    h3 {
                        font-weight: 700;
                        font-size: 18px;
                        color: var(--p, #c8420a);
                        margin: 0;
                    }
                }

                .checkout-btn {
                    background: var(--p, #c8420a);
                    color: #fff !important;
                    text-decoration: none;
                    padding: 12px 22px;
                    font-size: 14px;
                    font-weight: 700;
                    border-radius: 12px;
                    flex: 0 0 auto;
                    transition: filter 0.15s;
                }
                .checkout-btn:hover { filter: brightness(1.08); }
                .checkout-btn:active { transform: scale(0.98); }
            }
        }
    }
</style>

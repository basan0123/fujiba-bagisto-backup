<template>
    <div :class="['content', 'order', isMenuExpanded ? 'expanded' : '']">

        <sales-header :title="$t('Order Details')" active="order" :order-id="$route.params.id" @onHeaderToggle="toggleHeader($event)"></sales-header>

        <div class="order-details" v-if="order">
            <div class="order-info-section sale-section">
                <h2 class="sale-section-title">{{ order.increment_id }}</h2>

                <div class="order-content sale-section-content">
                    <label>{{ $t('Placed On') }}</label>

                    <div class="date-status">
                        <span class="date">{{ new Date(order.created_at) | moment("D MMMM YYYY") }}</span>
                        <span :class="['status', order.status]">{{ order.status_label }}</span>
                    </div>
                </div>
            </div>

            <div class="order-items-section sale-section odetail-items">
                <h2 class="sale-section-title">{{ $t('number Item(s) Ordered', {number: order.items.length}) }}</h2>

                <div class="odetail-item-list sale-section-content">
                    <div class="odetail-item" v-for="(orderItem, index) in sortedItems" :key="index">
                        <div class="oi-img">
                            <img v-if="orderItem.product && orderItem.product.base_image && orderItem.product.base_image.small_image_url"
                                :src="orderItem.product.base_image.small_image_url"
                                :alt="orderItem.name"
                                onerror="this.src = '/themes/pwa/default/build/assets/images/category-image.png'"/>
                            <span v-else class="oi-img-fallback">{{ (orderItem.name || '?').charAt(0) }}</span>
                        </div>

                        <div class="oi-body">
                            <div class="oi-name">{{ orderItem.name }}</div>

                            <div v-if="orderItem.additional && orderItem.additional.attributes" class="oi-attrs">
                                <span v-for="(a, i) in orderItem.additional.attributes" :key="i" class="oi-attr">
                                    {{ a.attribute_name }}: {{ a.option_label }}
                                </span>
                            </div>

                            <div class="oi-line">
                                <span class="oi-price">{{ orderItem.formatted_base_price }}</span>
                                <span class="oi-qty" :class="{ multi: orderItem.qty_ordered > 1 }">
                                    × {{ orderItem.qty_ordered }}
                                </span>
                            </div>

                            <router-link class="oi-review" :to="'/reviews/' + orderItem.product_id + '/create'">
                                {{ $t('Write a Review') }}
                            </router-link>
                        </div>

                        <div class="oi-subtotal">{{ orderItem.formatted_grant_total }}</div>
                    </div>
                </div>
            </div>

            <div class="total-sale-section sale-section">
                <h2 class="sale-section-title">{{ $t('Price Details') }}</h2>

                <div class="order-totals sale-section-content">
                    <table class="sale-summary">
                        <tbody>
                            <tr>
                                <td>{{ $t('Subtotal') }}</td>
                                <td>{{ order.formatted_sub_total }}</td>
                            </tr>

                            <tr>
                                <td>{{ $t('Shipping Handling') }}</td>
                                <td>{{ order.formatted_shipping_amount }}</td>
                            </tr>

                            <tr v-if="order.cod_fee && order.cod_fee > 0">
                                <td>{{ $t('Cash on Delivery Fee') }}</td>
                                <td>{{ order.cod_fee_formatted || order.cod_fee }}</td>
                            </tr>

                            <tr class="border">
                                <td>{{ $t('Tax') }}</td>
                                <td>{{ order.formatted_tax_amount }}</td>
                            </tr>

                            <tr class="border">
                                <td>{{ $t('Discount') }}</td>
                                <td>- {{ order.formatted_discount_amount }}</td>
                            </tr>

                            <tr class="bold">
                                <td>{{ $t('Total Paid') }}</td>
                                <td>{{ order.formatted_grand_total_invoiced }}</td>
                            </tr>

                            <tr class="bold">
                                <td>{{ $t('Total Refunded') }}</td>
                                <td>{{ order.formatted_grand_total_refunded }}</td>
                            </tr>

                            <tr class="bold last">
                                <td>{{ $t('Grand Total') }}</td>
                                <td>{{ order.formatted_grand_total }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="shipping-payment-section sale-section">
                <h2 class="sale-section-title">{{ $t('Shipping and Payment Info') }}</h2>

                <div class="shipping-payment sale-section-content">
                    <div class="shipping-address">
                        <h3>{{ $t('Shipping Address') }}</h3>

                        <div class="address-deatils" v-if="order.shipping_address">
                            {{ order.shipping_address.address1.join(' ') }}<br/>
                            {{ order.shipping_address.city }}<br/>
                            {{ order.shipping_address.state }}<br/>
                            {{ order.shipping_address.country_name + ' ' + order.shipping_address.postcode }}<br/>
                            {{ order.shipping_address.phone }}
                        </div>
                    </div>

                    <div class="billing-address">
                        <h3>{{ $t('Billing Address') }}</h3>

                        <div class="address-deatils" v-if="order.billing_address">
                            {{ order.billing_address.address1.join(' ') }}<br/>
                            {{ order.billing_address.city }}<br/>
                            {{ order.billing_address.state }}<br/>
                            {{ order.billing_address.country_name + ' (' + order.billing_address.postcode+ ')' }}<br/>
                            {{ $t('Phone') }}: {{ order.billing_address.phone }}
                        </div>
                    </div>

                    <div class="shipping-method">
                        <h3>{{ $t('Shipping Method') }}</h3>

                        <div class="shipping-method-deatils">
                            {{ order.shipping_title }}
                        </div>
                    </div>

                    <div class="payment-method">
                        <h3>{{ $t('Payment Method') }}</h3>

                        <div class="payment-method-deatils">
                            {{ order.payment_title }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import SalesHeader from '../header';

    export default {
        name: 'order-detail',

        components: { SalesHeader },

        data () {
			return {
                isMenuExpanded: false,

				order: null,

                orderItems: [],
            }
        },

        computed: {
            // Sort items by quantity descending so the biggest line items
            // appear first. Stable secondary sort on the original index keeps
            // ties visually stable across renders.
            sortedItems () {
                if (! this.order || ! Array.isArray(this.order.items)) return [];
                return this.order.items
                    .map(function (it, i) { return { it, i, q: parseInt(it.qty_ordered || 0, 10) }; })
                    .sort(function (a, b) {
                        if (b.q !== a.q) return b.q - a.q;
                        return a.i - b.i;
                    })
                    .map(function (x) { return x.it; });
            },
        },

        mounted () {
            this.getOrder(this.$route.params.id)
        },

        methods: {
            getOrder (orderId) {
                var this_this = this;

                EventBus.$emit('show-ajax-loader');

                this.$http.get('/api/v1/customer/orders/' + orderId)
                    .then(function(response) {
                        EventBus.$emit('hide-ajax-loader');

                        this_this.order = response.data.data;
                    })
                    .catch(function (error) {});
            },

            toggleHeader (value) {
                this.isMenuExpanded = value;
            },
        }
    }
</script>

<style scoped lang="scss">
    .content {
        position: absolute;
        bottom: 0;
        top: 0;
        width: 100%;

        .order-details {
            position: absolute;
            width: 100%;
            z-index: 10;

            .sale-section {
                padding: 16px;
                border-bottom: 1px solid rgba(0, 0, 0, 0.12);
                background: #ffffff;

                .sale-section-title {
                    font-size: 14px;
                    color: rgba(0, 0, 0, 0.56);
                    padding-bottom: 16px;
                    border-bottom: 1px solid rgba(0, 0, 0, 0.12);
                }

                .sale-section-content {
                    padding: 16px 0 0 0;
                }
            }

            .order-info-section {
                border-top-left-radius: 15px;
                border-top-right-radius: 15px;

                .order-content {
                    label {
                        font-size: 14px;
                        color: rgba(0, 0, 0, 0.57);
                    }

                    .date-status {
                        margin-top: 4px;
                        display: inline-block;
                        width: 100%;

                        .date {
                            font-size: 14px;
                            color: rgba(0, 0, 0, 0.87);
                        }

                        .status {
                            float: right;
                            font-weight: 600;
                            font-size: 14px;
                            color: #FFFFFF;
                            text-align: center;
                            text-transform: uppercase;
                            padding: 3px 6px;

                            &.pending {
                                background: #FFCC00;
                            }

                            &.pending_payment {
                                background: #FFCC00;
                            }

                            &.processing {
                                background: #59A600;
                            }

                            &.completed {
                                background: #59A600;
                            }

                            &.canceled {
                                background: #FF4848;
                            }

                            &.closed {
                                background: #000000;
                            }

                            &.fraud {
                                background: #FF4848;
                            }
                        }
                    }
                }
            }

            .odetail-items {
                .odetail-item-list {
                    display: flex;
                    flex-direction: column;
                    gap: 12px;
                }
                .odetail-item {
                    display: flex;
                    gap: 12px;
                    padding: 12px 0;
                    border-bottom: 1px solid rgba(0,0,0,0.06);
                }
                .odetail-item:last-child { border-bottom: 0; }
                .oi-img {
                    width: 60px; height: 60px;
                    flex: 0 0 60px;
                    border-radius: 8px;
                    overflow: hidden;
                    background: #f5f0eb;
                    display: flex; align-items: center; justify-content: center;
                }
                .oi-img img { width: 100%; height: 100%; object-fit: cover; }
                .oi-img-fallback { font-size: 22px; color: #9a7a5a; font-weight: 600; }
                .oi-body { flex: 1; min-width: 0; }
                .oi-name {
                    font-size: 14px; font-weight: 600;
                    color: #1a0800;
                    line-height: 1.3;
                    margin-bottom: 4px;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    display: -webkit-box;
                    -webkit-line-clamp: 2;
                    -webkit-box-orient: vertical;
                }
                .oi-attrs { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 6px; }
                .oi-attr {
                    font-size: 11px;
                    color: #9a7a5a;
                    background: #f5f0eb;
                    border-radius: 4px;
                    padding: 1px 6px;
                }
                .oi-line {
                    display: flex; align-items: center; gap: 10px;
                    font-size: 13px;
                }
                .oi-price { color: rgba(0,0,0,0.65); }
                .oi-qty { color: rgba(0,0,0,0.55); font-weight: 500; }
                .oi-qty.multi {
                    color: #c8220a;
                    font-weight: 800;
                    font-size: 17px;
                    letter-spacing: 0.2px;
                }
                .oi-review {
                    display: inline-block;
                    margin-top: 6px;
                    font-size: 11px;
                    color: var(--p, #c8420a);
                    text-decoration: none;
                    font-weight: 600;
                }
                .oi-review:hover { text-decoration: underline; }
                .oi-subtotal {
                    flex: 0 0 auto;
                    align-self: flex-start;
                    font-size: 14px;
                    font-weight: 700;
                    color: var(--p, #c8420a);
                    text-align: right;
                    white-space: nowrap;
                }
            }

            .order-items-section {
                .order-item-list {
                    .order-item {
                        display: inline-block;
                        padding: 16px 0;

                        .order-item-image {
                            margin-right: 16px;
                            float: left;

                            img {
                                width: 96px;
                                height: 96px;
                            }
                        }

                        .order-item-info {
                            display: block;
                            overflow: hidden;

                            > div {
                                margin-bottom: 8px;
                                font-size: 14px;

                                &:last-child {
                                    margin-bottom: 0;
                                }

                                label {
                                    color: rgba(0, 0, 0, 0.56);
                                }

                                span {

                                }
                            }

                            .order-item-name {
                                margin-bottom: 12px;
                            }

                            .order-item-options {
                                .attributes {
                                    display: inline-block;

                                    .option {
                                        display: inline-block;

                                        span {
                                            margin-right: 8px;
                                        }
                                    }
                                }
                            }

                            .order-item-review-link {
                                .icon {
                                    margin-right: 6px;
                                    vertical-align: middle;
                                    display: inline-block;
                                }
                            }
                        }
                    }
                }
            }

            .total-sale-section {
                .order-totals {
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
                                    padding: 12px 0;
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
            }

            .shipping-payment-section {

                .shipping-payment {
                    padding: 8px 0 8px 0;

                    h3 {
                        font-weight: 700;
                        font-size: 12px;
                        color: rgba(0, 0, 0, 0.56);
                        padding: 8px 0 8px 0;
                    }

                    .shipping-address, .billing-address, .shipping-method {
                        margin-bottom: 17px;
                    }

                    .address-deatils, .payment-method-deatils, .shipping-method-deatils {
                        font-size: 14px;
                        color: rgba(0, 0, 0, 0.87);
                    }
                }
            }
        }

        &.expanded {
            .order-details {
                top: 207px;
            }
        }
    }
</style>

<template>
    <div class="order-card v2">
        <router-link :to="'/customer/account/orders/' + order.id" class="order-head">
            <div class="head-l">
                <div class="oid">#{{ order.increment_id }}</div>
                <div class="odate">{{ new Date(order.created_at) | moment("D MMM YYYY") }}</div>
            </div>
            <div class="head-r">
                <span class="status-dot" :style="{ background: shipColor }"></span>
                <span class="status-text">{{ shipLabel }}</span>
                <div class="oprice">{{ order.formatted_grand_total }}</div>
            </div>
        </router-link>

        <div v-if="order.items && order.items.length" class="thumb-row">
            <div v-for="(it, i) in order.items.slice(0, 4)" :key="i" class="thumb">
                <img v-if="it.product && it.product.base_image && it.product.base_image.small_image_url"
                    :src="it.product.base_image.small_image_url" :alt="it.name"/>
                <span v-else class="thumb-fallback">{{ (it.name || '?').charAt(0) }}</span>
            </div>
            <div v-if="order.items.length > 4" class="thumb more">+{{ order.items.length - 4 }}</div>
        </div>

        <div v-if="tracking && tracking.tracking_number" class="track-block">
            <div class="track-head">
                <span class="track-icon">📦</span>
                <a v-if="tracking.tracking_url" :href="tracking.tracking_url" target="_blank" rel="noopener" class="track-no">
                    Sagawa: {{ tracking.tracking_number }}
                </a>
                <span v-else class="track-no">Sagawa: {{ tracking.tracking_number }}</span>
            </div>
            <div class="track-timeline">
                <template v-for="(s, i) in timelineSteps">
                    <div :key="'d'+i" class="tl-step">
                        <span class="tl-dot" :class="dotClass(i)"></span>
                        <span class="tl-label">{{ s }}</span>
                    </div>
                    <span v-if="i < timelineSteps.length - 1" :key="'l'+i" class="tl-line" :class="{ done: i < currentStep }"></span>
                </template>
            </div>
        </div>

        <div class="order-actions">
            <router-link class="view-link" :to="'/customer/account/orders/' + order.id">
                <i class="icon sharp-arrow-line-icon"></i>
                <span>{{ $t('Detail') }}</span>
            </router-link>

            <a v-if="tracking && tracking.tracking_url"
               class="track-link"
               :href="tracking.tracking_url"
               target="_blank" rel="noopener">
                <span>{{ $t('Track') }}</span>
            </a>

            <div class="review-link" @click="bottomSheetOpen = true">
                <i class="icon sharp-post-review-icon"></i>
                <span>{{ $t('Review') }}</span>
            </div>
        </div>

        <bottom-sheet :show="bottomSheetOpen" :class-name="'filter-bottom-sheet'" @onBottomSheetClose="bottomSheetOpen = false;">
            <div slot="header">
                {{ $t('Choose a product to review') }}
            </div>

            <div slot="content">
                <div class="product-list">

                    <router-link class="product-item" v-for="orderItem in order.items" :key="orderItem.product_id" :to="'/reviews/' + orderItem.product_id + '/create'">
                        <div class="product-image">
                            <!-- <img alt="product-base-small-image" :src="orderItem.product.base_image.small_image_url"/> -->
                        </div>

                        <div class="product-name">
                            {{ orderItem.name }}
                        </div>
                    </router-link>
                </div>
            </div>
        </bottom-sheet>
    </div>
</template>

<script>
    import BottomSheet from '../../../../shared/bottom-sheet';

    export default {
        name: 'order-card',

        components: { BottomSheet },

        props: ['order', 'tracking'],

        data () {
			return {
                bottomSheetOpen: false
            }
        },

        computed: {
            shipColor () {
                // Map by tracking status when present, else fall back to the
                // order status. Spec: pending=gray, processing=orange,
                // shipped=blue, delivered=green, cancelled=red.
                var st = this.order && this.order.status;
                var t  = this.tracking && this.tracking.status;
                if (st === 'completed' && (!t || t === 'delivered'))  return '#2d7a4a'; // delivered
                if (t === 'delivered')                                return '#2d7a4a';
                if (t === 'waybill_issued')                           return '#2196F3'; // shipped (in transit)
                if (st === 'canceled' || st === 'fraud')              return '#c8220a';
                if (st === 'closed')                                  return '#9a7a5a';
                if (st === 'processing' || t === 'waybill_pending' || t === 'address_verified') return '#d4a843';
                return '#9a7a5a'; // pending
            },
            shipLabel () {
                var t = this.tracking && this.tracking.status;
                if (t === 'delivered')           return this.$t('Delivered');
                if (t === 'waybill_issued')      return this.$t('Shipped');
                if (t === 'waybill_pending')     return this.$t('Preparing');
                if (t === 'address_verified')    return this.$t('Confirmed');
                if (t === 'pending_verification')return this.$t('Pending');
                if (t === 'address_rejected' || t === 'waybill_failed') return this.$t('Issue');
                return this.order && this.order.status_label
                    ? this.order.status_label
                    : (this.order ? this.order.status : '');
            },
            timelineSteps () {
                return [
                    this.$t('Confirmed'),
                    this.$t('Preparing'),
                    this.$t('In Transit'),
                    this.$t('Delivered'),
                ];
            },
            currentStep () {
                if (!this.tracking) return 0;
                if (typeof this.tracking.status_step === 'number') {
                    return Math.max(0, Math.min(3, this.tracking.status_step));
                }
                return 0;
            },
        },

        methods: {
            dotClass (i) {
                if (i < this.currentStep) return 'done';
                if (i === this.currentStep) return 'now';
                return 'todo';
            },
        },
    }
</script>

<style scoped lang="scss">
    .order-card.v2 {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        margin: 0 12px 14px;
        overflow: hidden;

        .status-dot {
            display: inline-block;
            width: 8px; height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }
        .status-text {
            font-size: 11px;
            font-weight: 700;
            color: rgba(0,0,0,0.65);
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .track-block {
            border-top: 1px solid rgba(0,0,0,0.06);
            padding: 10px 16px 12px;
            background: #faf8f5;
        }
        .track-head {
            display: flex; align-items: center; gap: 6px;
            margin-bottom: 8px;
        }
        .track-icon { font-size: 13px; }
        .track-no {
            font-size: 12px; font-weight: 600;
            color: #2d6fa6; text-decoration: none;
        }
        .track-no:hover { text-decoration: underline; }

        .track-timeline {
            display: flex; align-items: flex-start;
            gap: 0;
        }
        .tl-step {
            display: flex; flex-direction: column; align-items: center;
            flex: 0 0 auto;
            min-width: 52px;
        }
        .tl-dot {
            width: 11px; height: 11px;
            border-radius: 50%;
            background: #d8d2cc;
            margin-bottom: 4px;
            box-sizing: border-box;
        }
        .tl-dot.done { background: #2d7a4a; }
        .tl-dot.now  {
            background: var(--p, #c8420a);
            box-shadow: 0 0 0 3px rgba(200,66,10,0.18);
        }
        .tl-dot.todo { background: #d8d2cc; }
        .tl-label {
            font-size: 10px;
            color: #5a3a1a;
            text-align: center;
            line-height: 1.2;
        }
        .tl-line {
            flex: 1;
            height: 2px;
            background: #e2d5c8;
            margin: 4px 2px 0;
            min-width: 8px;
        }
        .tl-line.done { background: #2d7a4a; }

        .track-link {
            width: 33%;
            display: inline-block;
            padding: 16px;
            cursor: pointer;
            color: rgba(0,0,0,0.87);
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            text-align: center;
            border-left: 1px solid rgba(0,0,0,0.08);
        }
        .order-actions { display: flex; }
        .view-link, .review-link { width: 33%; }

        .order-head {
            display: flex;
            justify-content: space-between;
            padding: 14px 16px;
            text-decoration: none;
            color: inherit;
        }
        .head-l .oid { font-weight: 700; font-size: 15px; color: #1a0800; }
        .head-l .odate { font-size: 12px; color: rgba(0,0,0,0.5); margin-top: 2px; }
        .head-r { text-align: right; }
        .head-r .oprice { font-weight: 700; font-size: 15px; margin-top: 4px; color: #1a0800; }

        .status-pill {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 3px 9px;
            border-radius: 99px;
            background: rgba(0,0,0,0.08);
            color: rgba(0,0,0,0.7);

            &.pending, &.pending_payment { background: rgba(212,168,67,.18); color: #b8862c; }
            &.processing                  { background: rgba(45,122,200,.13); color: #2d6fa6; }
            &.completed                   { background: rgba(45,122,74,.13);  color: #2d7a4a; }
            &.canceled, &.fraud           { background: rgba(200,34,10,.10);  color: #c8220a; }
            &.closed                      { background: rgba(0,0,0,.08);      color: #555; }
        }

        .thumb-row {
            display: flex;
            gap: 6px;
            padding: 0 16px 12px;
        }
        .thumb {
            width: 44px; height: 44px;
            border-radius: 8px;
            overflow: hidden;
            background: #f5f0eb;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; font-weight: 600; color: #9a7a5a;
        }
        .thumb img { width: 100%; height: 100%; object-fit: cover; }
        .thumb.more { font-size: 12px; color: #9a7a5a; background: #ede5db; }
    }

    .order-card {
        position: relative;
        background: #ffffff;
        margin-bottom: 28px;

        .order-details {
            padding: 16px;

            .order-id {
                font-weight: 600;
                font-size: 18px;
                color: rgba(0, 0, 0, 0.87);
                display: block;
                margin-bottom: 11px;
            }

            .order-status {
                margin-bottom: 11px;
                display: block;

                label {
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

            .order-date {
                font-size: 14px;
                color: rgba(0, 0, 0, 0.87);
                display: block;
                margin-bottom: 8px;
            }

            .order-price {
                font-weight: 700;
                font-size: 16px;
                color: rgba(0, 0, 0, 0.87);
                display: block;
            }

            .sharp-arrow-right-icon {
                position: absolute;
                right: 16px;
                bottom: 50%;
                margin-bottom: 12px;
                opacity: 0.16;
            }
        }

        .order-actions {
            border: 1px solid rgba(0, 0, 0, 0.12);
            font-weight: 600;
            font-size: 14px;

            .view-link {
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

            .review-link {
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

        .product-list {
            padding: 16px;
            display: inline-block;

            .product-item {
                margin-bottom: 16px;
                display: inline-block;
                display: flex;
                align-items:center;

                &:last-child {
                    margin-bottom: 0;
                }

                .product-image {
                    margin-right: 16px;

                    img {
                        width: 96px;
                        height: 96px;
                    }
                }

                .product-name {
                    font-size: 14px;
                    color: rgba(0, 0, 0, 0.87);
                    font-weight: 500;
                }
            }
        }
    }
</style>

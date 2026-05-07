<template>
    <div class="content">
        <custom-header :title="$t('Orders')"></custom-header>

        <div class="order-list" v-if="orders.length">
            <order-card v-for="order in orders" :key='order.uid' :order="order" :tracking="trackingByOrder[order.id] || null"></order-card>
        </div>

        <empty-order-list v-else></empty-order-list>

        <pagination :label="$t('Load More Orders')" v-bind="pagination" @onPaginate="paginate($event)"></pagination>
    </div>
</template>

<script>
    import CustomHeader   from '../../../../layouts/custom-header';
    import OrderCard      from './card';
    import Pagination     from '../../../../shared/pagination';
    import EmptyOrderList from './empty-order-list';

    export default {
        name: 'order-list',

        components: { CustomHeader, OrderCard, Pagination, EmptyOrderList },

        data () {
            return {
                orders: [],

                pagination: {},

                trackingByOrder: {},

				params: {
                    'customer_id': this.customer.id
                },
            }
        },

        props: ['customer'],

        mounted () {
            this.getOrders()
        },

        methods: {
            getOrders () {
                var this_this = this;

                EventBus.$emit('show-ajax-loader');

                this.$http.get('/api/v1/customer/orders', { params:
                    {
                        sort:'id',
                        order:'desc',
                        page:1,
                        limit:10,
                    }
                }).then(function(response) {
                    EventBus.$emit('hide-ajax-loader');
                    this_this.orders = response.data.data;
                    this_this.fetchTracking();
                })
                .catch(function (error) {});
            },

            fetchTracking () {
                var ids = (this.orders || []).map(function (o) { return o.id; }).filter(Boolean);
                if (!ids.length) return;
                var self = this;
                this.$http.get('/api/v1/sagawa/tracking', { params: { order_ids: ids.join(',') } })
                    .then(function (resp) {
                        var d = (resp && resp.data && resp.data.data) || {};
                        self.trackingByOrder = d;
                    })
                    .catch(function () {});
            },

            paginate (page) {
                this.params['page'] = page;

                this.getOrders();
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
    }
</style>

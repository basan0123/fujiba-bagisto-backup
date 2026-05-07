<template>
    <Tab
        :current="tab"
        :tabs="tabs"
        routeBase="/orders"
    />
</template>

<script setup>
    import History from '@components/secured/orders/History.vue'
    import Hold from '@components/secured/orders/Hold.vue'
    import Offline from '@components/secured/orders/Offline.vue'

    const props = defineProps({
        tab: String
    });

    const tabs = [
        {
            key: 'history',
            component: History,
            titleKey: 'pos.orders.history.title',
        },
        {
            key: 'hold',
            component: Hold,
            titleKey: 'pos.orders.hold.title',
        },
        {
            key: 'offline',
            component: Offline,
            titleKey: 'pos.orders.offline.title',
        },
    ];
</script>

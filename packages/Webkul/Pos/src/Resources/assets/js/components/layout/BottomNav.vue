<template>
    <!-- Mobile-only bottom tab bar (hidden on xl desktop where sidebar takes over) -->
    <nav
        class="pos-bottom-nav transition-transform duration-300"
        :class="{ 'translate-y-full': isScrollingDown }"
    >
        <div class="grid grid-cols-5 h-[49px]">
            <router-link
                v-for="item in navItems"
                :key="item.path"
                :to="item.path"
                class="flex flex-col items-center justify-center gap-0.5 text-[10px] font-medium transition-colors"
                :class="isActive(item) ? 'text-[#00B8D9]' : 'text-[#8E8E93]'"
            >
                <i
                    class="text-xl leading-none"
                    :class="item.icon"
                ></i>

                <span>{{ $t(item.labelKey) }}</span>
            </router-link>
        </div>
    </nav>
</template>

<script setup>
    import { useRoute } from 'vue-router';
    import { useScrollDirection } from '@src/composable/useScrollDirection';

    const route = useRoute();
    const { isScrollingDown } = useScrollDirection(10);

    const navItems = [
        {
            path: '/dashboard',
            icon: 'icon-home',
            labelKey: 'pos.layout.bottom_nav.home',
        },
        {
            path: '/home',
            icon: 'icon-checkout',
            labelKey: 'pos.layout.bottom_nav.order',
        },
        {
            path: '/orders',
            icon: 'icon-order',
            labelKey: 'pos.layout.bottom_nav.orders',
        },
        {
            path: '/customers',
            icon: 'icon-customer',
            labelKey: 'pos.layout.bottom_nav.customers',
        },
        {
            path: '/settings',
            icon: 'icon-settings',
            labelKey: 'pos.layout.bottom_nav.settings',
        },
    ];

    function isActive(item) {
        if (item.path === '/dashboard') {
            return route.path === '/dashboard';
        }

        // Exact match for /home to avoid matching /home + others
        if (item.path === '/home') {
            return route.path === '/home';
        }

        return route.path.startsWith(item.path);
    }
</script>

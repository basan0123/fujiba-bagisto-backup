<template>
    <!-- Air Regi style home dashboard — tile grid launcher -->
    <div class="min-h-screen bg-[#F2F2F7] pb-24 lg:pb-6 lg:padding-right">
        <div class="p-4 grid gap-3 max-w-[430px] mx-auto lg:max-w-none lg:mx-0">

            <!-- ── Primary tile: Order Entry (full width, prominent) ─────── -->
            <div
                class="pos-card p-5 flex items-center gap-4 cursor-pointer active:opacity-70 transition-opacity"
                @click="router.push('/home')"
            >
                <div class="pos-tile__icon flex-shrink-0">
                    <i class="icon-checkout text-2xl"></i>
                </div>

                <div class="flex-1 min-w-0">
                    <h2 class="pos-tile__title mt-0 text-base">
                        {{ $t('pos.dashboard.tiles.order_title') }}
                    </h2>

                    <p class="pos-tile__sub">
                        {{ $t('pos.dashboard.tiles.order_sub') }}
                    </p>
                </div>

                <i class="icon-chevron-right text-xl text-[#C7C7CC] flex-shrink-0"></i>
            </div>

            <!-- ── Row 2: Cashier + Sales ─────────────────────────────────── -->
            <div class="grid grid-cols-2 gap-3">
                <div
                    class="pos-tile"
                    @click="openCashierSheet"
                >
                    <div class="pos-tile__icon">
                        <i class="icon-cashier text-2xl"></i>
                    </div>

                    <h3 class="pos-tile__title">{{ $t('pos.dashboard.tiles.cashier_title') }}</h3>
                    <p class="pos-tile__sub">{{ $t('pos.dashboard.tiles.cashier_sub') }}</p>
                </div>

                <div
                    class="pos-tile"
                    @click="router.push('/reports')"
                >
                    <div class="pos-tile__icon">
                        <i class="icon-reports text-2xl"></i>
                    </div>

                    <h3 class="pos-tile__title">{{ $t('pos.dashboard.tiles.sales_title') }}</h3>
                    <p class="pos-tile__sub">{{ $t('pos.dashboard.tiles.sales_sub') }}</p>
                </div>
            </div>

            <!-- ── Row 3: Customers + Products ───────────────────────────── -->
            <div class="grid grid-cols-2 gap-3">
                <div
                    class="pos-tile"
                    @click="router.push('/customers')"
                >
                    <div class="pos-tile__icon">
                        <i class="icon-customer text-2xl"></i>
                    </div>

                    <h3 class="pos-tile__title">{{ $t('pos.dashboard.tiles.customers_title') }}</h3>
                    <p class="pos-tile__sub">{{ $t('pos.dashboard.tiles.customers_sub') }}</p>
                </div>

                <div
                    class="pos-tile"
                    @click="router.push('/products/list')"
                >
                    <div class="pos-tile__icon">
                        <i class="icon-product text-2xl"></i>
                    </div>

                    <h3 class="pos-tile__title">{{ $t('pos.dashboard.tiles.products_title') }}</h3>
                    <p class="pos-tile__sub">{{ $t('pos.dashboard.tiles.products_sub') }}</p>
                </div>
            </div>

            <!-- ── Row 4: Settings + Hardware ─────────────────────────────── -->
            <div class="grid grid-cols-2 gap-3">
                <div
                    class="pos-tile"
                    @click="router.push('/settings')"
                >
                    <div class="pos-tile__icon">
                        <i class="icon-settings text-2xl"></i>
                    </div>

                    <h3 class="pos-tile__title">{{ $t('pos.dashboard.tiles.settings_title') }}</h3>
                    <p class="pos-tile__sub">{{ $t('pos.dashboard.tiles.settings_sub') }}</p>
                </div>

                <div
                    class="pos-tile"
                    @click="router.push('/settings/hardware')"
                >
                    <div class="pos-tile__icon">
                        <i class="icon-keyboard text-2xl"></i>
                    </div>

                    <h3 class="pos-tile__title">{{ $t('pos.dashboard.tiles.hardware_title') }}</h3>
                    <p class="pos-tile__sub">{{ $t('pos.dashboard.tiles.hardware_sub') }}</p>
                </div>
            </div>

            <!-- ── Row 5: Cashier / Cash drawer (full width) ──────────────── -->
            <div
                class="pos-card p-4 flex items-center gap-4 cursor-pointer active:opacity-70 transition-opacity"
                @click="router.push('/cashier')"
            >
                <div class="pos-tile__icon flex-shrink-0">
                    <i class="icon-cashier text-2xl"></i>
                </div>

                <div class="flex-1 min-w-0">
                    <h3 class="pos-tile__title mt-0">{{ $t('pos.dashboard.tiles.drawer_title') }}</h3>
                    <p class="pos-tile__sub">{{ $t('pos.dashboard.tiles.drawer_sub') }}</p>
                </div>

                <i class="icon-chevron-right text-xl text-[#C7C7CC] flex-shrink-0"></i>
            </div>

            <!-- ── Agent info card ─────────────────────────────────────────── -->
            <div
                v-if="agent?.firstName"
                class="pos-card p-4 flex items-center gap-3"
            >
                <img
                    :src="agent.imageUrl || '/themes/pos/build/assets/user-placeholder-C_FiyGd9.png'"
                    class="h-10 w-10 rounded-full object-cover"
                    alt="Agent"
                />

                <div class="flex-1 min-w-0">
                    <p class="text-[15px] font-semibold text-[#1C1C1E] truncate">
                        {{ agent.firstName }} {{ agent.lastName }}
                    </p>

                    <p class="text-[13px] text-[#8E8E93] truncate">{{ agent.email }}</p>
                </div>

                <router-link
                    to="/settings/profile"
                    class="text-[#00B8D9] text-[15px] flex-shrink-0"
                >
                    {{ $t('pos.dashboard.edit') }}
                </router-link>
            </div>
        </div>

        <!-- ── Cashier actions bottom sheet (Screen 9 pattern) ────────────── -->
        <BottomSheet
            v-model="showCashierSheet"
            :title="$t('pos.dashboard.cashier_sheet.title')"
            :items="cashierSheetItems"
        />
    </div>
</template>

<script setup>
    import { ref, watchEffect } from 'vue';
    import { useRouter } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import { useIndexedDB } from '@src/composable/indexed-db';
    import BottomSheet from '@components/layout/BottomSheet.vue';

    const router = useRouter();
    const { t }  = useI18n();
    const DB     = useIndexedDB();

    const agent              = ref(null);
    const showCashierSheet   = ref(false);

    watchEffect(async () => {
        agent.value = await DB.getAgent();
    });

    function openCashierSheet() {
        showCashierSheet.value = true;
    }

    const cashierSheetItems = [
        {
            label:  t('pos.dashboard.cashier_sheet.cash_inout'),
            action: () => router.push('/cashier/drawer'),
        },
        {
            label:  t('pos.dashboard.cashier_sheet.history'),
            action: () => router.push('/cashier/history'),
        },
        {
            label:  t('pos.dashboard.cashier_sheet.today_sale'),
            action: () => router.push('/cashier/sale'),
        },
    ];
</script>

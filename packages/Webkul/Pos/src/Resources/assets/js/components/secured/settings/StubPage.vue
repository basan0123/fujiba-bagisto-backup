<template>
    <div class="flex min-h-[60vh] flex-col items-center justify-center px-6 text-center">
        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#FFE9B0]">
            <span class="icon-info text-3xl text-[#8A6100]"></span>
        </div>

        <h1 class="mb-2 text-[20px] font-semibold text-[#1C1C1E]">
            {{ $t(titleKey) }}
        </h1>

        <p class="mb-6 max-w-md text-[15px] text-[#8E8E93]">
            {{ $t('pos.settings.stub.body') }}
        </p>

        <button
            type="button"
            class="secondary-button px-6 py-3 text-[15px]"
            @click="$router.push('/settings')"
        >
            <span class="icon-chevron-left text-xl"></span>
            {{ $t('pos.settings.stub.back') }}
        </button>
    </div>
</template>

<script setup>
    import { computed } from 'vue';
    import { useRoute } from 'vue-router';

    const route = useRoute();

    // Map every stub route to its menu-item label key so the header
    // shows the same Japanese title the user tapped in the menu.
    const tabFromPath = computed(() => route.path.replace(/^\/settings\//, ''));

    const titleKey = computed(() => {
        const map = {
            'basic':            'pos.settings.groups.items.basic',
            'tax':              'pos.settings.groups.items.tax',
            'timezones':        'pos.settings.groups.items.timezones',
            'service-charge':   'pos.settings.groups.items.service_charge',
            'discount':         'pos.settings.groups.items.discount',
            'payment-methods':  'pos.settings.groups.items.payment',
            'x-z-report':       'pos.settings.groups.items.x_z_report',
            'restrictions':     'pos.settings.groups.items.restrictions',
            'faq':              'pos.settings.groups.items.faq',
            'contact':          'pos.settings.groups.items.contact',
            'feedback':         'pos.settings.groups.items.feedback',
            'peripherals':      'pos.settings.groups.items.peripherals',
            'terms':            'pos.settings.groups.items.terms',
            'privacy':          'pos.settings.groups.items.privacy',
            'account-deletion': 'pos.settings.groups.items.account_deletion',
        };

        return map[tabFromPath.value] ?? 'pos.settings.title';
    });
</script>

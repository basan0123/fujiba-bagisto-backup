<template>
    <div class="box-shadow grid gap-6 rounded-lg bg-white dark:bg-gray-900 p-4">
        <!-- Header -->
        <div class="flex flex-col">
            <h2 class="text-base font-medium text-gray-700 dark:text-gray-200">
                {{ $t('pos.settings.shortcuts.title') }}
            </h2>
            
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $t('pos.settings.shortcuts.description') }}
            </p>
        </div>

        <!-- Tabs -->
        <div class="flex space-x-4 border-b border-gray-200 dark:border-gray-700">
            <button
                v-for="section in shortcutSections"
                :key="section.title"
                @click="activeTab = section.title"
                class="px-4 py-2 text-sm font-medium rounded-t-md"
                :class="[
                    activeTab === section.title
                        ? 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-white'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'
                ]"
            >
                {{ section.title }}
            </button>
        </div>

        <!-- Tab content -->
        <div class="grid gap-3">
            <div
                v-for="item in activeSection.shortcuts"
                :key="item.key + item.action"
                class="flex justify-between items-center border rounded-lg p-3 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-600"
            >
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-gray-800 dark:text-gray-100">
                        {{ item.action }}
                    </span>

                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ item.description }}
                    </span>
                </div>
                
                <kbd
                    class="inline-block bg-gray-200 dark:bg-gray-600 text-gray-900 dark:text-white text-sm font-semibold py-1 px-2 rounded">
                    {{ item.key }}
                </kbd>
            </div>
        </div>
    </div>
</template>

<script setup>
    import { ref, computed } from 'vue';
    import { useI18n } from 'vue-i18n';

    const { t } = useI18n();

    const shortcutSections = [
        {
            title: t('pos.settings.shortcuts.sections.header'),
            shortcuts: [
                {
                    key: 'Ctrl + B',
                    action: t('pos.settings.shortcuts.items.open_barcode.action'),
                    description: t('pos.settings.shortcuts.items.open_barcode.description'),
                },
                {
                    key: 'Ctrl + Alt + N',
                    action: t('pos.settings.shortcuts.items.open_product_modal.action'),
                    description: t('pos.settings.shortcuts.items.open_product_modal.description'),
                },
                {
                    key: 'F11',
                    action: t('pos.settings.shortcuts.items.fullscreen.action'),
                    description: t('pos.settings.shortcuts.items.fullscreen.description'),
                },
                {
                    key: 'Ctrl + D',
                    action: t('pos.settings.shortcuts.items.dark_mode.action'),
                    description: t('pos.settings.shortcuts.items.dark_mode.description'),
                },
                {
                    key: 'Ctrl + H',
                    action: t('pos.settings.shortcuts.items.hold_orders.action'),
                    description: t('pos.settings.shortcuts.items.hold_orders.description'),
                },
            ],
        },
        {
            title: t('pos.settings.shortcuts.sections.menu'),
            shortcuts: [
                {
                    key: 'Alt + 1',
                    action: t('pos.settings.shortcuts.items.home.action'),
                    description: t('pos.settings.shortcuts.items.home.description'),
                },
                {
                    key: 'Alt + 2',
                    action: t('pos.settings.shortcuts.items.customers.action'),
                    description: t('pos.settings.shortcuts.items.customers.description'),
                },
                {
                    key: 'Alt + 3',
                    action: t('pos.settings.shortcuts.items.cashier.action'),
                    description: t('pos.settings.shortcuts.items.cashier.description'),
                },
                {
                    key: 'Alt + 4',
                    action: t('pos.settings.shortcuts.items.orders.action'),
                    description: t('pos.settings.shortcuts.items.orders.description'),
                },
                {
                    key: 'Alt + 5',
                    action: t('pos.settings.shortcuts.items.products.action'),
                    description: t('pos.settings.shortcuts.items.products.description'),
                },
                {
                    key: 'Alt + 6',
                    action: t('pos.settings.shortcuts.items.reports.action'),
                    description: t('pos.settings.shortcuts.items.reports.description'),
                },
                {
                    key: 'Alt + 7',
                    action: t('pos.settings.shortcuts.items.settings.action'),
                    description: t('pos.settings.shortcuts.items.settings.description'),
                },
            ],
        },
        {
            title: t('pos.settings.shortcuts.sections.customer'),
            shortcuts: [
                {
                    key: 'Ctrl + Shift + C',
                    action: t('pos.settings.shortcuts.items.create_customer.action'),
                    description: t('pos.settings.shortcuts.items.create_customer.description'),
                },
                {
                    key: 'Ctrl + E',
                    action: t('pos.settings.shortcuts.items.edit_customer.action'),
                    description: t('pos.settings.shortcuts.items.edit_customer.description'),
                },
                {
                    key: 'Del',
                    action: t('pos.settings.shortcuts.items.delete_customer.action'),
                    description: t('pos.settings.shortcuts.items.delete_customer.description'),
                },
                {
                    key: 'Ctrl + Shift + U',
                    action: t('pos.settings.shortcuts.items.change_customer.action'),
                    description: t('pos.settings.shortcuts.items.change_customer.description'),
                },
            ],
        },
        {
            title: t('pos.settings.shortcuts.sections.cart'),
            shortcuts: [
                {
                    key: 'Ctrl + Alt + Shift + N',
                    action: t('pos.settings.shortcuts.items.add_note.action'),
                    description: t('pos.settings.shortcuts.items.add_note.description'),
                },
                {
                    key: 'Ctrl + Backspace',
                    action: t('pos.settings.shortcuts.items.remove_note.action'),
                    description: t('pos.settings.shortcuts.items.remove_note.description'),
                },
                {
                    key: 'Ctrl + Enter',
                    action: t('pos.settings.shortcuts.items.checkout.action'),
                    description: t('pos.settings.shortcuts.items.checkout.description'),
                },
                {
                    key: 'Ctrl + Shift + H',
                    action: t('pos.settings.shortcuts.items.hold_order.action'),
                    description: t('pos.settings.shortcuts.items.hold_order.description'),
                },
            ],
        },
    ];

    const activeTab = ref(shortcutSections[0].title);

    const activeSection = computed(() =>
        shortcutSections.find((section) => section.title === activeTab.value)
    );
</script>

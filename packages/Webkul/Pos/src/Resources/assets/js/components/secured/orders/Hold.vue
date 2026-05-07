<template>
    <div
        v-if="orders.length"
        class="grid items-start gap-4 sm:grid-cols-1 md:grid-cols-2"
    >
        <div
            class="box-shadow grid content-start gap-4 rounded-lg bg-white p-3 dark:bg-gray-900"
            v-for="(order, index) in orders"
            :key="index"
        >
            <div class="grid gap-1.5">
                <p class="text-base font-semibold leading-5 text-gray-900 dark:text-white">
                    {{ `${order.customerFirstName} ${order.customerLastName}` }}
                </p>

                <p class="text-sm font-normal leading-4 text-neutral-400 dark:text-neutral-300">
                    {{ order.createdAt }}
                </p>
            </div>

            <div class="grid gap-1 rounded-xl bg-gray-100 dark:bg-gray-800 p-4">
                <p class="text-base font-semibold leading-5 text-slate-600 dark:text-slate-200">
                    {{ $t('pos.orders.hold.note') }}
                </p>

                <p class="text-sm font-normal leading-4 text-gray-900 dark:text-white">
                    {{ order.note || 'N/A' }}
                </p>
            </div>

            <div
                class="flex justify-between"
                v-for="(item, index) in order.items"
                :key="index"
            >
                <div class="grid gap-1">
                    <p class="text-base font-normal leading-5 text-gray-900 dark:text-white">
                        {{ item.name }}
                    </p>

                    <p class="text-sm font-normal leading-4 text-neutral-400 dark:text-neutral-300">
                        {{ item.sku }}
                    </p>
                </div>

                <p class="text-base font-normal leading-5 text-gray-900 dark:text-white">
                    {{ item.qty }}
                </p>
            </div>

            <div class="flex justify-between gap-2.5">
                <button
                    type="button"
                    class="secondary-button w-full"
                    @click="resumeOrder(order)"
                >
                    <span class="icon-checkout text-2xl rtl:rotate-180"></span>

                    {{ $t('pos.orders.hold.resume') }}
                </button>

                <button
                    type="button"
                    class="transparent-button w-full"
                    @click="removeOrder(order)"
                >
                    <span class="icon-delete text-2xl"></span>

                    {{ $t('pos.orders.hold.remove') }}
                </button>
            </div>
        </div>
    </div>

    <div
        v-else
        class="box-shadow w-full rounded-lg bg-white dark:bg-gray-900 p-3"
    >
        <p class="text-base font-normal leading-5 text-gray-900 dark:text-white">
            {{ $t('pos.orders.hold.no_orders') }}
        </p>
    </div>
</template>

<script setup>
    import { ref, toRaw, inject, onMounted } from 'vue';
    import { useIndexedDB } from '@src/composable/indexed-db';
    import { useI18n } from 'vue-i18n';

    /**
     * General variables
     */
    const { t } = useI18n();
    const DB = useIndexedDB();
    const emitter = inject('emitter');

    /**
     * Fetch the orders from the IndexedDB
     */
    const orders = ref([]);

    onMounted(() => {
        DB.getAllItems('hold_orders').then(data => {
            orders.value = data;
        });
    });

    /**
     * Remove the order from the IndexedDB
     */
    const removeOrder = (order) => {
        emitter.emit('open_confirm_modal', {
            agree: async () => {
                DB.deleteItem('hold_orders', order.id).then(() => {
                    orders.value = orders.value.filter(item => item.id !== order.id);
                });

                emitter.emit('add_flash', {
                    type: 'success',
                    message: t('pos.orders.hold.remove_success'),
                });
            }
        });
    };

    /**
     * Resume the order
     */
    const resumeOrder = (order) => {
        emitter.emit('open_confirm_modal', {
            agree: async () => {
                const cart = await DB.getCart();

                if (Object.keys(cart).length) {
                    emitter.emit('add_flash', {
                        type: 'warning',
                        message: t('pos.orders.hold.resume_error'),
                    });

                    return;
                }

                await DB.deleteAllItems('cart_items');

                await DB.addItem('cart', toRaw(order));

                for (const item of order.allItems) {
                    await DB.addItem('cart_items', toRaw(item));
                }

                emitter.emit('customer_updated', order.customer);

                DB.deleteItem('hold_orders', order.id).then(() => {
                    orders.value = orders.value.filter(item => item.id !== order.id);
                });

                emitter.emit('add_flash', {
                    type: 'success',
                    message: t('pos.orders.hold.resume_success'),
                });
            }
        });
    };
</script>
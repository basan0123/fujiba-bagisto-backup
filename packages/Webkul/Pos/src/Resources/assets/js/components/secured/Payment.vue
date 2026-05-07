<template>
    <div class="grid gap-4">
        <div
            class="flex w-max cursor-pointer items-center"
            @click="$router.go(-1)"
        >
            <span class="icon-chevron-left text-2xl text-slate-600 dark:text-white"></span>

            <span class="text-base font-semibold leading-5 text-slate-600 dark:text-white">
                {{ $t('pos.payment.back') }}
            </span>
        </div>

        <v-form
            class="grid gap-4 lg:grid-cols-2"
            @submit="process"
        >
            <template v-if="!cart?.items?.length">
                <div class="box-shadow flex items-center justify-center rounded-lg bg-white dark:bg-gray-900">
                    <p class="text-base leading-5 text-gray-900 dark:text-gray-100">
                        {{ $t('pos.payment.empty_cart') }}
                    </p>
                </div>
            </template>

            <template v-else>
                <div class="box-shadow flex flex-col gap-4 rounded-lg bg-white dark:bg-gray-900">
                    <div class="flex flex-col px-3">
                        <div class="flex flex-col gap-1 py-4">
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ $t('pos.payment.order_details') }}
                            </p>

                            <div class="flex justify-between">
                                <p class="text-sm leading-4 text-gray-900 dark:text-gray-300">
                                    {{ `${cart.customerFirstName} ${cart.customerLastName}` }}
                                </p>

                                <p class="text-sm leading-4 text-gray-900 dark:text-gray-300">
                                    {{ cart.customerEmail }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col content-baseline gap-1 overflow-y-auto py-2 max-sm:h-full lg:h-[calc(100vh-416px)]">
                            <div
                                class="flex h-max flex-col gap-4 rounded-lg p-2"
                                :class="[index % 2 == 0 ? 'bg-zinc-50 dark:bg-gray-800' : '']"
                                v-for="(item, index) in cart?.items"
                                :key="index"
                            >
                                <div class="flex justify-between gap-2">
                                    <div class="flex gap-2.5">
                                        <p class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                                            {{ index + 1 }}
                                        </p>

                                        <div class="flex flex-col gap-1">
                                            <p class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                                                {{ item.name }}
                                            </p>

                                            <p class="text-sm font-normal leading-4 text-neutral-400 dark:text-neutral-400">
                                                {{ item.sku }}
                                            </p>
                                        </div>
                                    </div>

                                    <p class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                                        {{ formatPrice(item.total) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 rounded-lg bg-zinc-50 dark:bg-gray-900 p-3">
                        <div class="flex flex-col gap-1">
                            <div class="flex justify-between">
                                <p class="text-base font-normal leading-5 text-gray-900 dark:text-gray-100">
                                    {{ $t('pos.payment.subtotal') }}
                                </p>

                                <p class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                                    {{ formatPrice(cart.subTotal) }}
                                </p>
                            </div>

                            <div class="flex justify-between">
                                <p class="text-base font-normal leading-5 text-gray-900 dark:text-gray-100">
                                    {{ $t('pos.payment.tax') }}
                                </p>

                                <p class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                                    {{ formatPrice(cart.taxTotal) }}
                                </p>
                            </div>

                            <div class="flex justify-between">
                                <p class="text-base font-normal leading-5 text-gray-900 dark:text-gray-100">
                                    {{ $t('pos.payment.discount') }}
                                </p>

                                <p class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                                    {{ formatPrice(cart.discountAmount) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex justify-between">
                            <p class="text-xl font-medium leading-6 text-gray-900 dark:text-gray-100">
                                {{ $t('pos.payment.grand_total') }}
                            </p>

                            <p class="text-xl font-medium leading-6 text-gray-900 dark:text-gray-100">
                                {{ formatPrice(cart.grandTotal) }}
                            </p>
                        </div>

                        <button
                            v-if="!isMobileOrTab"
                            class="secondary-button w-full"
                            type="submit"
                            :label="$t('pos.payment.confirm_payment')"
                        >
                            <span class="icon-payment text-2xl"></span>

                            {{ $t('pos.payment.confirm_payment') }}
                        </button>
                    </div>
                </div>
            </template>

            <div class="flex flex-col gap-2">
                <div class="box-shadow grid grid-cols-3 rounded-lg bg-white dark:bg-gray-900 p-4 max-sm:hidden">
                    <div class="flex flex-col gap-1">
                        <p class="text-3xl font-semibold text-green-600 dark:text-green-400">
                            {{ formatPrice(cart.grandTotal) }}
                        </p>

                        <p class="text-xl font-normal leading-6 text-gray-900 dark:text-gray-100">
                            {{ $t('pos.payment.payable_amount') }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1">
                        <p class="text-3xl font-semibold text-green-600 dark:text-green-400">
                            {{ cashTotal != '' ? cashTotal : 0 }}
                        </p>

                        <p class="text-xl font-normal leading-6 text-gray-900 dark:text-gray-100">
                            {{ $t('pos.payment.received') }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1">
                        <p class="text-3xl font-semibold text-[#F83015] dark:text-red-400">
                            {{ amountRemaining }}
                        </p>

                        <p class="text-xl font-normal leading-6 text-gray-900 dark:text-gray-100">
                            {{ $t('pos.payment.remaining') }}
                        </p>
                    </div>
                </div>

                <div class="box-shadow grid gap-4 rounded-lg bg-white dark:bg-gray-900 p-4 max-sm:p-0 select-none">
                    <div class="flex h-9 gap-2.5">
                        <div
                            v-on:click="paymentMethod = method"
                            :class="[
                                paymentMethod === method
                                ? 'bg-gray-100 text-slate-600 !border-slate-600 dark:bg-gray-800 dark:text-slate-300 dark:!border-slate-300'
                                : 'text-gray-900 dark:text-gray-100',
                            ]"
                            class="flex cursor-pointer items-center rounded-lg border-2 border-transparent px-3 py-2 text-base font-medium leading-5"
                            v-for="(method, index) in ['pos_cash', 'pos_card', 'pos_split']"
                            :key="index"
                        >
                            {{ $t(`pos.payment.${method}`) }}
                        </div>
                    </div>

                    <template v-if="
                        paymentMethod === 'pos_cash'
                        || paymentMethod === 'pos_split'
                    ">
                        <ControlGroup>
                            <Field
                                :type="'text'"
                                :name="'cash_total'"
                                :id="'cash_total'"
                                :rules="
                                    paymentMethod === 'pos_cash'
                                        ? `required|decimal|min_value:${cart?.grandTotal}`
                                        : 'required|decimal'
                                "
                                :placeholder="'0'"
                                className="text-2xl text-right font-medium"
                                :label="$t('pos.payment.pos_cash')"
                                v-model="cashTotal"
                            />

                            <Error :name="'cash_total'" />
                        </ControlGroup>
                    </template>

                    <template v-if="
                        paymentMethod === 'pos_cash'
                        && ! isMobileOrTab
                    ">
                        <div class="grid grid-cols-3 gap-2">
                            <div
                                v-for="(key, index) in keys"
                                :key="index"
                                :class="[key === 'cancel' ? 'col-span-2' : '']"
                                class="flex cursor-pointer items-center justify-center rounded bg-gray-100 dark:bg-gray-800 py-2 text-2xl font-semibold text-gray-900 dark:text-gray-100 hover:bg-blue-100 dark:hover:bg-blue-800"
                                @click="keyPress(key)"
                            >
                                <template v-if="key === 'cancel'">
                                    {{ $t('pos.payment.cancel') }}
                                </template>

                                <template v-else-if="key === 'x'">
                                    <span class="icon-backspace text-2xl"></span>
                                </template>

                                <template v-else>
                                    {{ key }}
                                </template>
                            </div>
                        </div>
                    </template>

                    <template v-else-if="
                        paymentMethod === 'pos_card'
                        || paymentMethod === 'pos_split'
                    ">
                        <div class="grid gap-5 border-t border-neutral-400 dark:border-neutral-600 pt-5">
                            <ControlGroup>
                                <Label for="card_details">
                                    {{ $t('pos.payment.card_details') }}
                                </Label>

                                <div class="flex gap-10">
                                    <div class="border-b border-neutral-400 dark:border-neutral-600">
                                        <span class="text-gray-600 dark:text-gray-400 text-lg leading-5">
                                            xxxx
                                        </span>
                                    </div>

                                    <div class="border-b border-neutral-400 dark:border-neutral-600">
                                        <span class="text-gray-600 dark:text-gray-400 text-lg leading-5">
                                            xxxx
                                        </span>
                                    </div>

                                    <div class="border-b border-neutral-400 dark:border-neutral-600">
                                        <span class="text-gray-600 dark:text-gray-400 text-lg leading-5">
                                            xxxx
                                        </span>
                                    </div>

                                    <v-field
                                        type="text"
                                        name="card_details"
                                        id="card_details"
                                        class="text-gray-100-600 w-10 border-b border-neutral-400 text-base leading-5 dark:border-neutral-600 dark:text-gray-400 bg-transparent"
                                        rules="required|numeric|min:4|max:4"
                                        v-model="cardDetails"
                                        :label="$t('pos.payment.card_details')"
                                    />
                                </div>

                                <Error :name="'card_details'" />
                            </ControlGroup>

                            <ControlGroup>
                                <Label for="bank_name">
                                    {{ $t('pos.payment.bank') }}
                                </Label>

                                <Field
                                    :type="'select'"
                                    :name="'bank_name'"
                                    :id="'bank_name'"
                                    :rules="'required'"
                                    :label="$t('pos.payment.bank')"
                                    v-model="bankName"
                                >
                                    <option value="">
                                        {{ $t('pos.payment.select_bank') }}
                                    </option>

                                    <option
                                        v-for="(paymentBank, index) in paymentBanks"
                                        :key="index"
                                        :value="paymentBank.name"
                                    >
                                        {{ paymentBank.name }}
                                    </option>

                                    <option value="other">
                                        {{ $t('pos.payment.other') }}
                                    </option>
                                </Field>

                                <Error :name="'bank_name'" />
                            </ControlGroup>
                        </div>
                    </template>

                    <button
                        v-if="isMobileOrTab"
                        class="secondary-button w-full"
                        type="submit"
                        :label="$t('pos.payment.confirm_payment')"
                    >
                        <span class="icon-payment text-2xl"></span>

                        {{ $t('pos.payment.confirm_payment') }}
                    </button>
                </div>
            </div>
        </v-form>
    </div>
</template>

<script setup>
    import { ref, onMounted, computed, watch, inject, toRaw } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useMutation } from '@vue/apollo-composable';
    import { useRouter } from 'vue-router';
    import { useIndexedDB } from '@src/composable/indexed-db';
    import { useOutlet } from '@src/composable/outlet';
    import { keysToSnake } from '@src/utils/case-converter';
    import { useWindowWidth } from '@src/composable/window';
    import { SYNC_ORDER } from '@src/graphql/orders';
    import CartManager from '@src/helpers/Cart';
    import { pushHeartbeat } from '@src/composable/customer-display-tokens';

    /**
     * Inject the dependency
     */
    const emitter = inject('emitter');
    const isOnline = inject('isOnline');
    const { t } = useI18n();
    const { formatPrice } = useOutlet();
    const DB = useIndexedDB();
    const router = useRouter();

    /**
     * Data to be used in the component
     */
    const cart = ref({});
    const paymentMethod = ref('pos_cash');
    const cashTotal = ref('');
    const cardDetails = ref('');
    const bankName = ref('');
    const { isMobileOrTab } = useWindowWidth();

    /**
     * Return the amount remaining
     */
    const amountRemaining = computed(() => {
        const grandTotal = parseFloat(cart.value?.grandTotal ?? 0);
        const cashTotalAmount = parseFloat(cashTotal.value) || 0;
        
        return (grandTotal - cashTotalAmount).toFixed(2);
    });

    /**
     * Get the cart
     */
    const getCart = async () => {
        DB.getCart().then(data => {
            cart.value = data;
        });
    };

    /**
     * Process the payment
     */
    const { mutate } = useMutation(SYNC_ORDER);

    /**
     * In training mode the cashier is practicing — we never call syncOrder
     * (so no DB row + no inventory move), and we only stash a synthetic
     * outlet_order for the print prompt if trainingPrintReceipt is on.
     */
    const handleTrainingModeCheckout = async () => {
        const cartManager = new CartManager();
        const agent       = await DB.getAgent();

        pushHeartbeat({
            subtotal: Number(cart.value?.subTotal) || 0,
            tax:      Number(cart.value?.taxTotal) || 0,
            total:    Number(cart.value?.grandTotal) || 0,
            items:    Number(cart.value?.itemsQty)  || 0,
            currency: cart.value?.cartCurrencyCode || 'JPY',
            state:    'paid',
        });

        if (agent?.trainingPrintReceipt) {
            // Build a print-payload with a TRAINING marker so the receipt is
            // visibly distinguishable from a real one.
            const fakeOrder = {
                id:           'TRAINING-' + Date.now(),
                isTraining:   true,
                grandTotal:   cart.value?.grandTotal,
                subTotal:     cart.value?.subTotal,
                taxAmount:    cart.value?.taxTotal,
                items:        cart.value?.items,
                cartCurrencyCode: cart.value?.cartCurrencyCode,
            };
            localStorage.setItem('outlet_order', JSON.stringify(fakeOrder));
        }

        await cartManager.removeCart();

        emitter.emit('add_flash', {
            type:    'warning',
            message: t('pos.settings.training.banner'),
        });

        router.push({ path: '/home' });
    };

    const process = async () => {
        // Training mode short-circuits everything — no order, no stock change.
        const agent = await DB.getAgent();

        if (agent?.trainingMode) {
            await handleTrainingModeCheckout();
            return;
        }

        const input = {
            customerEmail: cart.value.customerEmail,
            customerFirstName: cart.value.customerFirstName,
            customerLastName: cart.value.customerLastName,
            paymentMode: paymentMethod.value,
            orderNote: cart.value.note ?? '',
        };

        if (
            paymentMethod.value === 'pos_cash'
            || paymentMethod.value === 'pos_split'
        ) {
            input['cashTotal'] = parseFloat(cashTotal.value);
        }
        
        if (
            paymentMethod.value === 'pos_card'
            || paymentMethod.value === 'pos_split'
        ) {
            input['cardDetails'] = cardDetails.value;
            input['bankName'] = bankName.value;
        }

        input['orderItems'] = cart.value.items.map(item => {
            return {
                productId: parseInt(item.productId),
                customPrice: parseFloat(item.customPrice),
                additional: JSON.stringify(keysToSnake(item.additional))
            }
        });

        const cartManager = new CartManager();

        if (isOnline.value) {
            const response = await mutate({ input });

            const { syncOrder } = response.data;

            if (syncOrder.success) {
                const { outletOrder } = syncOrder;

                pushHeartbeat({
                    subtotal: Number(cart.value?.subTotal) || 0,
                    tax:      Number(cart.value?.taxTotal) || 0,
                    total:    Number(cart.value?.grandTotal) || 0,
                    items:    Number(cart.value?.itemsQty)  || 0,
                    currency: cart.value?.cartCurrencyCode || 'JPY',
                    state:    'paid',
                });

                await cartManager.removeCart();

                emitter.emit('add_flash', {
                    type: 'success',
                    message: t('pos.payment.order_placed'),
                });

                await DB.updateItem('orders', outletOrder);

                localStorage.setItem('outlet_order', JSON.stringify(outletOrder));

                router.push({path: '/home'});
            }
        } else {
            pushHeartbeat({
                subtotal: Number(cart.value?.subTotal) || 0,
                tax:      Number(cart.value?.taxTotal) || 0,
                total:    Number(cart.value?.grandTotal) || 0,
                items:    Number(cart.value?.itemsQty)  || 0,
                currency: cart.value?.cartCurrencyCode || 'JPY',
                state:    'paid',
            });

            await DB.addItem('offline_orders', {
                order: toRaw(cart.value),
                input: input,
                date: new Date(),
            });

            await cartManager.removeCart();

            emitter.emit('add_flash', {
                type: 'success',
                message: t('pos.payment.order_placed'),
            });

            router.push({path: '/home'});
        }
    };

    /**
     * Get the cart and payment bank's on mounted
     */
    const paymentBanks = ref([]);
    
    onMounted(async () => {
        const agent = await DB.getAgent();

        if (agent) {
            paymentBanks.value = agent.banks;
        }

        getCart();
    });

    /**
     * Watch the amount received
     */
    watch(() => cashTotal.value, () => {
        if (cashTotal.value > 10000000000000) {
            emitter.emit('add_flash', {
                type: 'warning',
                message: t('pos.payment.amount_exceeded'),
            });
        }
    });

    /**
     * Method to handle the key press
     */
    const keys = ref(['1', '2', '3', '4', '5', '6', '7', '8', '9', '00', '0', 'x', '.', 'cancel']);

    const keyPress = (key) => {
        switch (key) {
            case 'cancel':
                cashTotal.value = '';
            break;
            
            case 'x':
                cashTotal.value = cashTotal.value.slice(0, -1);
            break;

            case '.':
                if (! cashTotal.value.includes('.')) {
                    cashTotal.value += '.';
                }
            break;

            case '0':
                if (cashTotal.value !== '') {
                    cashTotal.value += '0';
                }
            break;

            case '00':
                if (cashTotal.value !== '') {
                    cashTotal.value += '00';
                }
            break;

            default:
                if (cashTotal.value === '') {
                    cashTotal.value = key;
                } else {
                    cashTotal.value += key;
                }
            break;
        }
    };
</script>

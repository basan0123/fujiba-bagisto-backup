<template>
    <div>
        <div class="flex flex-col gap-4">
            <div class="flex h-14 items-center justify-between bg-gray-100 dark:bg-gray-800 px-3">
                <p class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">
                    {{ $t('pos.common.cart.add') }}
                </p>

                <template v-if="cart?.id">
                    <!-- Order note -->
                    <template v-if="cart?.note">
                        <div class="flex items-center">
                            <p class="text-base leading-5 text-slate-600 dark:text-slate-300">
                                {{ $t('pos.common.cart.note') }}
                            </p>

                            <span
                                class="icon-cross cursor-pointer text-2xl text-slate-600 dark:text-slate-300"
                                @click="removeOrderNote()"
                            >
                            </span>
                        </div>
                    </template>

                    <template v-else>
                        <p
                            class="cursor-pointer text-base leading-5 text-slate-600 dark:text-slate-300"
                            @click="$refs.orderNoteModal.toggle()"
                        >
                            {{ $t('pos.common.cart.note') }}
                        </p>
                    </template>
                </template>
            </div>

            <div class="grid gap-4 bg-zinc-50 dark:bg-gray-900 px-3 py-2">
                <div class="grid gap-2">
                    <div class="flex justify-between">
                        <p class="text-base font-normal leading-5 text-gray-900 dark:text-gray-100">
                            {{ $t('pos.common.cart.subtotal') }}
                        </p>

                        <p class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                            {{ formatPrice(cart?.subTotal ?? 0) }}
                        </p>
                    </div>

                    <div class="flex justify-between">
                        <p class="text-base font-normal leading-5 text-gray-900 dark:text-gray-100">
                            {{ $t('pos.common.cart.tax') }}
                        </p>

                        <p class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                            {{ formatPrice(cart?.taxTotal ?? 0) }}
                        </p>
                    </div>

                    <div class="flex justify-between">
                        <p class="text-base font-normal leading-5 text-gray-900 dark:text-gray-100">
                            {{ $t('pos.common.cart.discount') }}
                        </p>

                        <p class="text-base font-semibold leading-5 text-gray-900 dark:text-gray-100">
                            {{ formatPrice(cart?.discountAmount ?? 0) }}
                        </p>
                    </div>
                </div>

                <div class="flex justify-between">
                    <p class="text-xl font-medium leading-6 text-gray-900 dark:text-gray-100">
                        {{ $t('pos.common.cart.payable_amount') }}
                    </p>

                    <p class="text-xl font-medium leading-6 text-gray-900 dark:text-gray-100">
                        {{ formatPrice(cart?.grandTotal ?? 0) }}
                    </p>
                </div>

                <div class="flex justify-between gap-2.5">
                    <button
                        type="button"
                        class="primary-button w-full"
                        :class="[! cart?.id ? 'opacity-50' : '']"
                        :disabled="! cart?.id"
                        @click="holdOrder"
                    >
                        <span class="icon-hold-cart text-2xl"></span>

                        {{ $t('pos.common.cart.hold_order') }}
                    </button>

                    <template v-if="cart?.id">
                        <router-link
                            :to="`/payment/${cart?.id}`"
                            class="secondary-button w-full"
                        >
                            <span class="icon-checkout text-2xl rtl:rotate-180"></span>

                            {{ $t('pos.common.cart.proceed') }}
                        </router-link>
                    </template>

                    <template v-else>
                        <button
                            type="button"
                            class="secondary-button w-full opacity-50"
                            disabled
                        >
                            <span class="icon-checkout text-2xl rtl:rotate-180"></span>

                            {{ $t('pos.common.cart.proceed') }}
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <Modal ref="orderNoteModal">
                <template v-slot:header>
                    {{ $t('pos.common.cart.order_note_form.title') }}
                </template>
                
                <template v-slot:content="{ toggle }">
                    <v-form
                        class="grid gap-4"
                        @submit="submitOrderNoteForm"
                    >
                        <ControlGroup>
                            <Field
                                :type="'textarea'"
                                :name="'note'"
                                :id="'note'"
                                :rules="'required|max:250'"
                                placeholder="Note"
                                v-model="orderNote"
                            />

                            <Error :name="'note'" />
                        </ControlGroup>

                        <div class="flex justify-end gap-6">
                            <button
                                type="button"
                                class="transparent-button w-36"
                                @click="toggle"
                            >
                                {{ $t('pos.common.cart.order_note_form.cancel_btn_title') }}
                            </button>

                            <button
                                type="submit"
                                class="primary-button w-36"
                            >
                                {{ $t('pos.common.cart.order_note_form.proceed_btn_title') }}
                            </button>
                        </div>
                    </v-form>
                </template>
            </Modal>
        </Teleport>
    </div>
</template>

<script setup>
    import { ref, inject, onMounted, onBeforeUnmount, toRaw } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useRouter } from 'vue-router'
    import { useIndexedDB } from '@src/composable/indexed-db';
    import { useOutlet } from '@src/composable/outlet';
    import CartManager from '@src/helpers/Cart';
    
    const props = defineProps({
        cart: {
            type: Object,
            required: true,
        },
        customer: {
            type: Object,
            required: true,
        },
    });

    const emit = defineEmits(['get-cart']);

    /**
     * General use imports
     */
    const { t } = useI18n();
    const { formatPrice } = useOutlet();
    const emitter = inject('emitter');
    const DB = useIndexedDB();
    const router = useRouter();

    /**
     * Submit order note form
     */
    const orderNote = ref('');
    const orderNoteModal = ref(false);

    const submitOrderNoteForm = () => {
        DB.updateItem('cart', {
            ...toRaw(props.cart),
            note: orderNote.value,
        }).then(result => {
            if (result) {
                emitter.emit('add_flash', {
                    type: 'success',
                    message: t('pos.common.cart.order_note_form.create_success'),
                });

                emit('get-cart');
            } else {
                emitter.emit('add_flash', {
                    type: 'error',
                    message: t('pos.common.flash_messages.error_message'),
                });
            }

            orderNote.value = '';

            orderNoteModal.value.toggle();
        });
    };

    /**
     * Remove order note
     */
     const removeOrderNote = () => {
        emitter.emit('open_confirm_modal', {
            agree: () => {
                DB.updateItem('cart', {
                    ...toRaw(props.cart),
                    note: '',
                }).then(result => {
                    if (result) {
                        emitter.emit('add_flash', {
                            type: 'success',
                            message: t('pos.common.cart.order_note_form.remove_success'),
                        });

                        emit('get-cart');
                    } else {
                        emitter.emit('add_flash', {
                            type: 'error',
                            message: t('pos.common.flash_messages.error_message'),
                        });
                    }
                });
            }
        });
    };

    /**
     * Hold order
     */
    const holdOrder = () => {
        emitter.emit('open_confirm_modal', {
            agree: () => {
                DB.addItem('hold_orders', toRaw(props.cart)).then(async (result) => {
                    if (result) {
                        emitter.emit('add_flash', {
                            type: 'success',
                            message: t('pos.common.cart.hold_order_success'),
                        });

                        const cartManager = new CartManager();

                        await cartManager.removeCart().then(() => {
                            emit('get-cart');
                        });
                    } else {
                        emitter.emit('add_flash', {
                            type: 'error',
                            message: t('pos.common.flash_messages.error_message'),
                        });
                    }
                });
            }
        });
    };

    /**
     * Keyboard Shortcuts
     */
    onMounted(() => {
        emitter.on('checkout', () => {
            if (props.cart?.id) {
                router.push(`/payment/${props.cart.id}`);
            }
        });

        emitter.on('hold_order', () => {
            if (props.cart?.id) {
                holdOrder();
            }
        });

        emitter.on('add_note', () => {
            if (props.cart?.id) {
                orderNoteModal.value.toggle();
            }
        });

        emitter.on('remove_note', () => {
            if (props.cart?.note) {
                removeOrderNote();
            }
        });
    });

    onBeforeUnmount(() => {
        emitter.off('checkout');

        emitter.off('hold_order');

        emitter.off('add_note');

        emitter.off('remove_note');
    });
</script>
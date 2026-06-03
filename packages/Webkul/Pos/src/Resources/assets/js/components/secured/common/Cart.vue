<template>
    <div
        v-if="layout === 'default'"
        class="fixed flex h-[calc(100vh-68px)] w-[clamp(300px,_38vw,_360px)] xl:w-[clamp(420px,_30vw,_600px)] flex-col border-l border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900 ltr:right-0 rtl:left-0"
    >
        <div class="flex-shrink-0 py-4 shadow-[0px_-1px_0px_0px_rgba(0,0,0,0.1)_inset] dark:shadow-[0px_-1px_0px_0px_rgba(255,255,255,0.05)_inset]">
            <CartTop
                :customer="customer"
                :cart="cart"
                @get-cart="getCart"
            />
        </div>

        <div class="flex-1 min-h-0 overflow-y-auto px-3 py-4">
            <CartItem
                v-for="(item, index) in cart?.items"
                :key="index"
                :item="item"
                :index="index"
                :customer="customer"
                @get-cart="getCart"
            />
        </div>

        <div class="flex-shrink-0">
            <CartBottom
                :cart="cart"
                :customer="customer"
                @get-cart="getCart"
            />
        </div>
    </div>

    <div v-else>
        <!-- Mobile view: hide the default cart bar on /home — Main.vue has its own Air Regi cart bar there -->
        <div
            v-if="$route.path !== '/home'"
            class="fixed bottom-0 left-0 right-0 flex w-full items-center justify-between gap-10 border-t border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900 p-2.5 lg:hidden"
        >
            <div class="flex flex-col gap-y-0.5">
                <p class="text-lg font-medium leading-6 text-gray-800 dark:text-white">
                    {{ formatPrice(cart?.grandTotal ?? 0) }}
                </p>

                <p class="text-sm leading-4 text-gray-600 dark:text-gray-400">
                    {{ $t('pos.home.items_in_cart', { count: cart?.items?.length ?? 0 }) }}
                </p>
            </div>

            <button
                class="secondary-button px-6 dark:text-white"
                @click="$refs.mobileCartDrawer.toggle()"
            >
                <span class="icon-cart text-2xl leading-6"></span>

                <span class="text-base font-semibold">
                    {{ $t('pos.home.view_cart_btn') }}
                </span>
            </button>
        </div>

        <Teleport to="body">
            <Drawer ref="mobileCartDrawer">
                <template v-slot:header>
                    <div class="px-2 py-4 bg-white dark:bg-gray-900">
                        <CartTop
                            :customer="customer"
                            :cart="cart"
                            @get-cart="getCart"
                        />
                    </div>
                </template>

                <template v-slot:content>
                    <div class="flex h-full flex-col px-3 bg-white dark:bg-gray-900">
                        <CartItem
                            v-for="(item, index) in cart?.items"
                            :key="index"
                            :item="item"
                            :index="index"
                            :customer="customer"
                            @get-cart="getCart"
                        />
                    </div>
                </template>

                <template v-slot:footer>
                    <CartBottom
                        :cart="cart"
                        :customer="customer"
                        @get-cart="getCart"
                    />
                </template>
            </Drawer>
        </Teleport>
    </div>
</template>

<script setup>
    import { useI18n } from 'vue-i18n';
    import { useOutlet } from '@src/composable/outlet';
    import { onMounted, ref, inject, onBeforeUnmount, toRaw } from 'vue';

    const mobileCartDrawer = ref(null);
    import { useIndexedDB } from '@src/composable/indexed-db';
    import CartTop from '@components/secured/common/cart/Top.vue';
    import CartItem from '@components/secured/common/cart/Item.vue';
    import CartBottom from '@components/secured/common/cart/Bottom.vue';
    import CartManager from '@src/helpers/Cart';
    
    /**
     * General use imports
     */
    const emitter = inject('emitter');
    const DB = useIndexedDB();
    const { formatPrice } = useOutlet();
    const { t } = useI18n();

    /**
     * Props
     */
    defineProps({
        layout: {
            type: String,
            default: 'default',
        },
    })

    /**
     * Cart management
     */
    const cart = ref({});
    
    const getCart = async () => {        
        DB.getCart().then(data => {
            cart.value = data;
        });
    };

    /**
     * Customer management
     */
    const customer = ref({});

    const setCartCustomer = async () => {
        let data = await DB.getCartCustomer();

        /**
         * Auto-assign walk-in customer when none is selected.
         * Looks for a customer whose first name or email contains "walk".
         * Falls back to the logged-in agent so a sale can always proceed.
         */
        if (! data?.id) {
            const all = await DB.getAllItems('customers');

            let fallback = all.find(c =>
                c.firstName?.toLowerCase().includes('walk') ||
                c.email?.toLowerCase().includes('walk')
            );

            if (! fallback) {
                const agent = await DB.getAgent();

                if (agent?.id && agent?.email) {
                    fallback = {
                        id:        agent.id,
                        firstName: agent.firstName,
                        lastName:  agent.lastName,
                        email:     agent.email,
                    };
                }
            }

            if (fallback) {
                await DB.deleteAllItems('cart_customer');
                await DB.addItem('cart_customer', fallback);
                data = fallback;
            }
        }

        customer.value = data;        

        if (cart.value?.id) {
            await DB.updateItem('cart', {
                ...toRaw(cart.value),
                customerId: customer.value.id,
                customerFirstName: customer.value.firstName,
                customerLastName: customer.value.lastName,
                customerEmail: customer.value.email,
            });

            await getCart();
        }
    };

    /**
     * Add to cart
     */
    const addToCart = async (productData) => {
        if (! customer.value?.id) {
            emitter.emit('add_flash', {
                type: 'warning',
                message: t('pos.common.cart.no_customer_selected'),
            });

            return;
        }

        productData = {
            ...productData,
            cartId: cart.value?.id,
            customerId: customer.value?.id,
        };

        const cartManager = new CartManager();

        cartManager.addToCart(productData).then(() => {
            emitter.emit('add_flash', {
                type: 'success',
                message: t('pos.common.cart.product_add_success'),
            });

            getCart();
        }).catch(error => {
            console.log(error);
            
            emitter.emit('add_flash', {
                type: 'warning',
                message: error,
            });
        })
    };

    /**
     * Global events
     */
    const registerEvents = () => {
        emitter.on('add_to_cart', addToCart);

        emitter.on('customer_changed', setCartCustomer);

        // Checkout button in Home/Main.vue emits this to open the mobile cart drawer
        emitter.on('toggle_cart', () => mobileCartDrawer.value?.toggle());
    };

    /**
     * Lifecycle hooks
     */
    onMounted(async () => {
        registerEvents();

        getCart();

        setCartCustomer();
    });

    onBeforeUnmount(() => {
        emitter.off('add_to_cart');

        emitter.off('customer_changed');

        emitter.off('toggle_cart');
    });
</script>
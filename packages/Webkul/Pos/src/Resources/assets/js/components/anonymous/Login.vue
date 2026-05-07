<template>
    <div class="mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mx-auto mt-12 grid w-full max-w-[370px] justify-center gap-10 px-4 sm:px-0">
            <div class="grid justify-items-center gap-2.5">
                <div class="flex h-[74px] w-[74px] items-center justify-center rounded-full bg-white dark:bg-slate-800">
                    <template v-if="getConfigData('pos_logo')">
                        <img
                            :src="getConfigData('pos_logo')"
                            alt="POS Logo"
                            width="34"
                            height="46"
                        />
                    </template>

                    <template v-else>
                        <img
                            src="@images/logo.png"
                            alt="POS Logo"
                            width="34"
                            height="46"
                        />
                    </template>
                </div>

                <h1 class="max-w-full truncate text-3xl font-semibold text-gray-900 dark:text-white">
                    {{ getConfigData('heading_on_login') ?? $t('pos.title') }}
                </h1>
            </div>

            <v-form v-slot="{ handleSubmit }">
                <form
                    class="grid gap-6 rounded-2xl bg-white dark:bg-slate-900 p-6 sm:p-9"
                    @submit="handleSubmit($event, submitForm)"
                >
                    <h2 class="truncate text-2xl font-semibold leading-7 text-gray-900 dark:text-white">
                        {{ getConfigData('sub_heading_on_login') ?? $t('pos.login_form.title') }}
                    </h2>

                    <ControlGroup>
                        <Label for="email">
                            {{ $t('pos.login_form.user_name') }}
                        </Label>

                        <Field
                            :type="'email'"
                            :name="'email'"
                            :id="'email'"
                            :rules="'required|email'"
                            :placeholder="$t('pos.login_form.user_name_placeholder')"
                        />

                        <Error :name="'email'" />
                    </ControlGroup>

                    <ControlGroup>
                        <Label for="password">
                            {{ $t('pos.login_form.password') }}
                        </Label>

                        <div class="relative">
                            <Field
                                :type="showPassword ? 'text' : 'password'"
                                :name="'password'"
                                :id="'password'"
                                :rules="'required'"
                                :placeholder="$t('pos.login_form.password_placeholder')"
                            />

                            <span
                                :class="showPassword ? 'icon-eye-off' : 'icon-eye'"
                                class="absolute right-3 top-1/2 -translate-y-1/2 transform cursor-pointer text-2xl leading-6 opacity-60 dark:text-white"
                                @click="showPassword = !showPassword"
                            >
                            </span>
                        </div>

                        <Error :name="'password'" />
                    </ControlGroup>

                    <Field
                        :type="'checkbox'"
                        :name="'remember'"
                        :id="'remember'"
                        :label="$t('pos.login_form.remember_password')"
                    />

                    <Button
                        type="submit"
                        class="primary-button"
                        :label="$t('pos.login_form.login_btn_title')"
                        :isLoading="isSubmittingForm"
                    />
                </form>
            </v-form>
        </div>

        <footer class="mx-4 sm:mx-10 md:mx-20 lg:mx-32">
            <div class="mb-5 mt-10 flex flex-col gap-3.5 text-center">
                <p
                    class="text-sm font-normal leading-4 text-gray-900 dark:text-gray-400"
                    v-html="getConfigData('footer_content') ?? $t('pos.footer.warning')"
                >
                </p>

                <p class="text-sm font-normal leading-4 text-gray-900 dark:text-gray-400">
                    {{ getConfigData('footer_note') ?? $t('pos.footer.copyright') }}
                </p>
            </div>
        </footer>

        <FlashMessage />
        
        <Loader />
    </div>
</template>

<script setup>
    import { ref, watch, inject } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useCookies } from '@src/composable/cookies';
    import { useApolloClient, useQuery } from '@vue/apollo-composable';
    import { FETCH_GLOBAL_DATA } from '@src/graphql/global';
    import { FETCH_CURRENT_AGENT } from '@src/graphql/session';
    import { useRouter } from 'vue-router';
    import { useIndexedDB } from '@src/composable/indexed-db';
    import FlashMessage from '@components/shared/FlashMessage.vue';
    import Loader from '@components/shared/Loader.vue';

    /**
     * General Variables
     */
    const { t } = useI18n();
    const router = useRouter();
    const DB = useIndexedDB();
    const emitter = inject('emitter');
    const cookies = useCookies();
    const { client } = useApolloClient();

    /**
     * Local State
     */
    const showPassword = ref(false);

    /**
     * Fetch Global Data
     */
    const configurations = ref([]);

    const { result } = useQuery(FETCH_GLOBAL_DATA);

    watch(() => result?.value?.fetchGlobalData, (data) => {
        if (data) {            
            configurations.value = data.configurations;

            DB.updateItem('categories', {
                id: 1,
                categories: data.categories,
            });

            DB.updateItem('configurations', {
                id: 1,
                configurations: data.configurations,
            });

            DB.updateItem('locales_currencies', {
                id: 1,
                currencies: data.currencies,
                locales: data.locales,
            });            

            DB.updateItem('exchange_rates', {
                id: 1,
                exchangeRates: data.exchangeRates,
            });

            DB.updateItem('tax_categories', {
                id: 1,
                taxCategories: data.taxCategories,
            });

            DB.updateItem('countries_states', {
                id: 1,
                countries: data.countries,
                countryStates: data.countryStates,
            });

            cookies.set('default_country', data.defaultCountry);

            if (! cookies.get('locale')) {
                cookies.set('locale', JSON.stringify(data.locales.find(locale => locale.code === data.defaultLocale)));
            }

            if (! cookies.get('currency')) {
                cookies.set('currency', JSON.stringify(data.currencies.find(currency => currency.code === data.baseCurrency)));
            }
        }
    });

    /**
     * All IndexedDB tables that are tenant-specific and must be wiped on tenant switch.
     */
    const TENANT_TABLES = [
        'products',
        'customers',
        'cart',
        'cart_customer',
        'cart_items',
        'categories',
        'configurations',
        'countries_states',
        'exchange_rates',
        'hold_orders',
        'locales_currencies',
        'offline_customers',
        'offline_orders',
        'orders',
        'tax_categories',
        'agent',
    ];

    const isSubmittingForm = ref(false);

    const submitForm = async (params, { setErrors }) => {
        isSubmittingForm.value = true;

        try {
            // Step 1: REST login — resolves tenant/channel from email via user_channel_map.
            const response = await fetch('/api/v1/pos/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ email: params.email, password: params.password }),
            });

            const data = await response.json();

            if (! response.ok) {
                const message = data?.error === 'pos_user_inactive'
                    ? t('pos.outlet.agents.login.not-activated')
                    : t('pos.outlet.agents.login.invalid-credentials');

                emitter.emit('add_flash', { type: 'error', message });
                return;
            }

            // Step 2: Detect tenant change — wipe ALL cached data when switching tenants
            // so tenant A's products/orders/customers never bleed into tenant B's session.
            const prevTenantId = localStorage.getItem('tenantId');
            const newTenantId  = String(data.tenant_id);

            if (prevTenantId && prevTenantId !== newTenantId) {
                await Promise.all(TENANT_TABLES.map(table => DB.deleteAllItems(table)));
                cookies.remove('drawer_fetched');
            }

            // Step 3: Store token + tenant so Apollo auth link and future logins can
            // detect tenant switches without an extra network round-trip.
            localStorage.setItem('accessToken', data.token);
            localStorage.setItem('tenantId', newTenantId);

            // Step 4: Fetch full agent object — ValidatePosJwtTenant now initialises
            // the correct tenant DB from the JWT, so GraphQL resolves against tenant data.
            await client.clearStore();

            const agentResult = await client.query({
                query: FETCH_CURRENT_AGENT,
                fetchPolicy: 'network-only',
            });

            const agent = agentResult?.data?.fetchCurrentAgent;

            if (! agent) {
                emitter.emit('add_flash', { type: 'error', message: t('pos.outlet.agents.login.invalid-credentials') });
                localStorage.removeItem('accessToken');
                localStorage.removeItem('tenantId');
                return;
            }

            // Step 5: Handle same-tenant outlet change — clear product/order caches.
            if (! prevTenantId || prevTenantId === newTenantId) {
                await clearOfflineRecords(await DB.getAgent(), agent);
            }

            await DB.deleteAllItems('agent');
            await DB.addItem('agent', agent);

            // The pre-auth FETCH_GLOBAL_DATA above runs without a JWT, so categories /
            // locales / currencies are resolved by hostname → first-channel. On bare
            // fujiba.co.jp that's the kimono channel, even for an mmfood agent.
            // Refetch now that the JWT is in place so IndexedDB carries the agent's
            // actual channel data.
            try {
                const globalResult = await client.query({
                    query: FETCH_GLOBAL_DATA,
                    fetchPolicy: 'network-only',
                });

                const globalData = globalResult?.data?.fetchGlobalData;

                if (globalData) {
                    await DB.updateItem('categories', {
                        id: 1,
                        categories: globalData.categories,
                    });

                    await DB.updateItem('configurations', {
                        id: 1,
                        configurations: globalData.configurations,
                    });

                    await DB.updateItem('locales_currencies', {
                        id: 1,
                        currencies: globalData.currencies,
                        locales: globalData.locales,
                    });

                    await DB.updateItem('exchange_rates', {
                        id: 1,
                        exchangeRates: globalData.exchangeRates,
                    });

                    await DB.updateItem('tax_categories', {
                        id: 1,
                        taxCategories: globalData.taxCategories,
                    });

                    await DB.updateItem('countries_states', {
                        id: 1,
                        countries: globalData.countries,
                        countryStates: globalData.countryStates,
                    });
                }
            } catch (e) {
                // Non-fatal: the pre-auth fetch still seeded IndexedDB.
            }

            // Always reset the cash drawer state so the opening-amount modal appears
            // on every new login session, regardless of what was set previously.
            cookies.remove('drawer_fetched');

            router.push({ path: '/home' });

        } catch (e) {
            emitter.emit('add_flash', { type: 'error', message: e.message ?? 'Login failed.' });
        } finally {
            isSubmittingForm.value = false;
        }
    };

    /**
     * Clear offline records when the same-tenant agent switches outlets.
     */
    const clearOfflineRecords = async (prevAgent, currentAgent) => {
        if (
            prevAgent
            && prevAgent.outletId !== currentAgent.outletId
        ) {
            await Promise.all([
                'products',
                'customers',
                'cart_customer',
                'cart',
                'cart_items',
                'orders',
                'hold_orders',
            ].map(table => DB.deleteAllItems(table)));
        }
    };

    /**
     * Get Configurations
     */
    const getConfigData = (code) => {
        return configurations.value.find(config => config.code === `pos.settings.general.${code}`)?.value;
    };
</script>

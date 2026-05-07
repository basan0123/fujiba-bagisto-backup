<template>
    <v-form
        class="box-shadow grid gap-6 rounded-lg bg-white dark:bg-gray-900 p-4"
        @submit="submitForm($event)"
    >
        <div class="grid items-start gap-8 md:grid-cols-2 md:gap-2.5">
            <ControlGroup>
                <Label for="locale">
                    {{ $t('pos.settings.regional.locale') }}
                </Label>

                <Field
                    :type="'select'"
                    :name="'locale'"
                    :id="'locale'"
                    :rules="'required'"
                    v-model="form.locale"
                >
                    <option value="">
                        {{ $t('pos.settings.regional.select_locale') }}
                    </option>

                    <option
                        v-for="(locale, index) in locales"
                        :key="index"
                        :value="locale.code"
                    >
                        {{ locale.name }}
                    </option>
                </Field>

                <Error :name="'locale'" />
            </ControlGroup>

            <ControlGroup>
                <Label for="currency">
                    {{ $t('pos.settings.regional.currency') }}
                </Label>

                <Field
                    :type="'select'"
                    :name="'currency'"
                    :id="'currency'"
                    :rules="'required'"
                    v-model="form.currency"
                >
                    <option value="">
                        {{ $t('pos.settings.regional.select_currency') }}
                    </option>

                    <option
                        v-for="(currency, index) in currencies"
                        :key="index"
                        :value="currency.code"
                    >
                        {{ currency.name }}
                    </option>
                </Field>

                <Error :name="'currency'" />
            </ControlGroup>
        </div>

        <button
            type="submit"
            class="primary-button w-full px-7 py-4 md:w-[280px]"
        >
            <span class="icon-check text-2xl"></span>

            {{ $t('pos.settings.regional.save_btn_title') }}
        </button>
    </v-form>
</template>

<script setup>
    import { useI18n } from 'vue-i18n';
    import { ref, watchEffect, inject } from 'vue';
    import { useCookies } from '@src/composable/cookies';
    import { useIndexedDB } from '@src/composable/indexed-db';

    /**
     * General use variables
     */
    const { t, locale } = useI18n();
    const DB = useIndexedDB();
    const emitter = inject('emitter');
    const cookies = useCookies();

    /**
     * Fetch locales and currencies
     */
    const locales = ref([]);
    const currencies = ref([]);

    watchEffect(async () => {
        const localesCurrencies = await DB.getItem('locales_currencies', 1);

        locales.value = localesCurrencies?.locales ?? [];

        currencies.value = localesCurrencies?.currencies ?? [];
    });

    /**
     * Form state
     */
    const form = ref({
        locale: JSON.parse(cookies.get('locale'))?.code ?? 'en',
        currency: JSON.parse(cookies.get('currency'))?.code ?? 'USD'
    });

    /**
     * Submit form
     */
    const submitForm = async (params) => {
        let currentLocale = locales.value.find(locale => locale.code === params.locale);

        let currentCurrency = currencies.value.find(currency => currency.code === params.currency);

        if (currentLocale) {
            locale.value = currentLocale.code;

            document.documentElement.lang = currentLocale.code;
            document.documentElement.dir = currentLocale.direction;

            cookies.set('locale', JSON.stringify(currentLocale));
        }

        if (currentCurrency) {
            cookies.set('currency', JSON.stringify(currentCurrency));
        }

        emitter.emit('add_flash', {
            type: 'success',
            message: t('pos.settings.save_success')
        });
    }
</script>

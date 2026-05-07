<template>
    <div class="max-w-full max-sm:w-full">
        <span
            class="flex items-center gap-2.5 dark:text-gray-200 text-gray-800"
            v-html="product.priceHtml"
        >
        </span>

        <div
            v-if="product.downloadableSamples.length"
            class="sample-list mb-6 mt-4"
        >
            <label class="mb-3 flex font-medium text-gray-800 dark:text-gray-200">
                {{ $t('pos.home.products.view.type.downloadable.samples') }}
            </label>

            <ul>
                <li
                    class="mb-2"
                    v-for="(sample, key) in product.downloadableSamples"
                    :key="key"
                >
                    <a 
                        :href="sample?.url || sample?.fileUrl" 
                        class="text-blue-700"
                        target="_blank"
                    >
                        {{ sample?.translations[0]?.title }}
                    </a>
                </li>
            </ul>
        </div>

        <template v-if="product.downloadableLinks.length">
            <label class="mb-4 mt-8 flex font-medium max-sm:mb-1.5 max-sm:mt-3 text-gray-800 dark:text-gray-200">
                {{ $t('pos.home.products.view.type.downloadable.links') }}
            </label>

            <div class="grid gap-4 max-sm:gap-1">
                <div
                    class="flex select-none flex-col gap-y-2"
                    v-for="(link, key) in product.downloadableLinks"
                    :key="key"
                >
                    <div class="flex gap-x-2">
                        <Field
                            :type="'checkbox'"
                            :name="`links[${key}]`"
                            :value="link.id"
                            :id="link.id"
                            rules="required"
                            :label="$t('pos.home.products.view.type.downloadable.links')"
                        >
                            <template v-slot:label>
                                {{ `${link.translations[0]?.title} + ${outlet.formatPrice(link.price)}` }}
                            </template>
                        </Field>

                        <a
                            v-if="link.sampleFile || link.sampleUrl"
                            :href="link.sampleFile || link.sampleUrl"
                            target="_blank"
                            class="text-blue-700 max-sm:text-sm"
                        >
                            {{ $t('pos.home.products.view.type.downloadable.samples') }}
                        </a>
                    </div>

                    <Error :name="`links[${key}]`" />
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
    import { useOutlet } from '@src/composable/outlet';

    defineProps({
        product: {
            type: Object,
            required: true,
        },
    });

    const outlet = useOutlet();
</script>
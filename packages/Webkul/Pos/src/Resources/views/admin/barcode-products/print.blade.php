<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('pos::app.admin.barcode-products.print.title')
    </x-slot:title>

    @push('styles')
        <style>
            @media print {
                header, .print-barcode, .quantity {
                    display: none !important;
                }
            }
        </style>
    @endpush

    <div class="print-barcode flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('pos::app.admin.barcode-products.print.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            <!-- Back Button -->
            <a
                href="{{ route('admin.pos.barcode_products.index') }}"
                class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
            >
                @lang('pos::app.admin.barcode-products.print.back-btn')
            </a>

            <!-- Save Button -->
            <button
                class="primary-button"
                id="print-barcode"
            >
                @lang('pos::app.admin.barcode-products.print.btn-title')
            </button>
        </div>
    </div>

    <v-print-barcode />

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-print-barcode-template"
        >
            <div class="mt-3 flex gap-2 max-xl:flex-wrap">
                <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                    <x-admin::form
                        v-slot="{ meta, errors, handleSubmit }"
                        as="div"
                    >
                        <form
                            class="quantity mb-2"
                            @submit="handleSubmit($event, submitForm)"
                        >
                            <x-admin::form.control-group class="w-[250px]">
                                <x-admin::form.control-group.label class="required">
                                    @lang('pos::app.admin.barcode-products.print.qty')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="quantity"
                                    rules="required|numeric|min_value:1|max_value:100"
                                    v-model="quantity"
                                />

                                <x-admin::form.control-group.error control-name="quantity" />
                            </x-admin::form.control-group>
                        </form>
                    </x-admin::form>
                    
                    <div
                        class="bg-gray-100 dark:bg-gray-800 p-5 rounded-lg"
                        v-if="isValidBarcode"
                    >
                        <div
                            v-for="(barcode, index) in barcode"
                            :key="index"
                        >
                            <div
                                class="inline-block pr-10 pb-10"
                                v-for="n in parseInt(quantity)"
                            >
                                <img :src="barcode.img_url" />
                                
                                <p
                                    v-if="printProductName"
                                    v-text="barcode.product_name"
                                    class="mt-2 text-center dark:text-white"
                                >
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-print-barcode', {
                template: '#v-print-barcode-template',
                
                data() {
                    return {
                        barcode: @json($barcode),

                        printProductName: @json(core()->getConfigData('pos.settings.barcode.print_product_name')),

                        quantity: 1,
                    }
                },
                
                mounted() {                    
                    document.getElementById('print-barcode').addEventListener('click', () => {
                        window.print();
                    });
                },

                computed: {
                    isValidBarcode() {                        
                        if (
                            this.barcode.length > 0
                            && this.barcode[0].img_url
                            && this.quantity > 0
                            && this.quantity <= 100
                        ) {
                            return true;
                        }
                    },
                },

                methods: {
                    submitForm() {
                        if (this.isValidBarcode) {
                            window.print();
                        }
                    }
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>

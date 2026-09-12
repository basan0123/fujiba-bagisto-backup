{{-- Product picker.

     Bagisto admin runs Vue 3 (app.mount("#app") in the layout) and ships no
     Alpine, so this is a Vue component registered via the same
     x-template + app.component() pattern the core admin components use.

     Selection ORDER is preserved and submitted as product_ids[] — the order the
     admin clicks in becomes the carousel order. --}}

<v-product-picker
    :candidates='@json($candidates)'
    input-name="product_ids"
></v-product-picker>

@pushOnce('scripts')
    <script type="text/x-template" id="v-product-picker-template">
        <div class="flex flex-col gap-3">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div class="text-sm text-gray-600 dark:text-gray-300">
                    @{{ selected.length }} selected
                    <span class="text-gray-400">
                        · click to add, click again to remove · order = carousel order
                    </span>
                </div>
                <button type="button" @click="selected = []"
                        class="px-3 py-1.5 border rounded text-sm dark:border-gray-700">
                    Clear
                </button>
            </div>

            <input v-for="id in selected" :key="'in-'+id"
                   type="hidden" :name="inputName + '[]'" :value="id">

            <div class="grid gap-3 grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                <div v-for="card in candidates" :key="card.product_id"
                     @click="toggle(card.product_id)"
                     class="relative border rounded overflow-hidden cursor-pointer transition dark:border-gray-700"
                     :class="isSelected(card.product_id)
                             ? 'ring-2 ring-blue-500 border-blue-500'
                             : 'hover:border-gray-400'">

                    <div v-if="isSelected(card.product_id)"
                         class="absolute top-1 left-1 z-10 w-6 h-6 rounded-full bg-blue-600 text-white
                                text-xs flex items-center justify-center font-bold">
                        @{{ position(card.product_id) }}
                    </div>

                    <img v-if="card.image" :src="card.image" loading="lazy" alt=""
                         class="w-full aspect-square object-cover bg-gray-100 dark:bg-gray-800">
                    <div v-else
                         class="w-full aspect-square bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-xs text-gray-400">
                        no image
                    </div>

                    <div class="p-1.5">
                        <div class="text-xs truncate" :title="card.name">@{{ card.name }}</div>
                        <div class="text-xs text-gray-500">@{{ formatPrice(card.price) }}</div>
                    </div>
                </div>
            </div>

            <div v-if="! candidates.length"
                 class="p-4 border rounded text-sm text-gray-500 dark:border-gray-700">
                No eligible products. Approve products for Facebook, or relax the
                cooldown / selection mode.
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-product-picker', {
            template: '#v-product-picker-template',

            props: {
                candidates: { type: Array, default: () => [] },
                inputName:  { type: String, default: 'product_ids' },
            },

            data() {
                return { selected: [] };
            },

            methods: {
                isSelected(id) {
                    return this.selected.includes(id);
                },

                toggle(id) {
                    const i = this.selected.indexOf(id);

                    if (i === -1) {
                        this.selected.push(id);
                    } else {
                        this.selected.splice(i, 1);
                    }
                },

                position(id) {
                    return this.selected.indexOf(id) + 1;
                },

                formatPrice(price) {
                    if (! price) {
                        return '';
                    }

                    return '¥' + Number(price).toLocaleString('ja-JP', { maximumFractionDigits: 0 });
                },
            },
        });
    </script>
@endPushOnce

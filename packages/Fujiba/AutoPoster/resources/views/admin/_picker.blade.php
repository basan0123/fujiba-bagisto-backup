{{-- Product picker.

     Bagisto admin runs Vue 3 (app.mount("#app") in the layout) and ships no
     Alpine, so this is a Vue component registered via the same
     x-template + app.component() pattern the core admin components use.

     Selection ORDER is preserved and submitted as product_ids[] — the order the
     admin clicks in becomes the carousel order. Every change is broadcast as
     window "ap:items" so a <v-fb-preview> on the page follows along. --}}

<v-product-picker
    :candidates='@json($candidates)'
    input-name="product_ids"
></v-product-picker>

@pushOnce('scripts')
    <script type="text/x-template" id="v-product-picker-template">
        <div>
            <div class="ap-picker-bar">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <span class="ap-badge" :class="selected.length ? 'ap-tone-blue' : 'ap-tone-gray'">
                        @{{ selected.length }} selected
                    </span>
                    <span class="ap-muted ap-small">Click to add · click again to remove · order = carousel order</span>
                </div>
                <div style="display: flex; gap: 8px; align-items: center; flex: 1; justify-content: flex-end; min-width: 220px;">
                    <input type="search" v-model="query" placeholder="Search products…" style="max-width: 240px; min-height: 34px; padding: 6px 12px;">
                    <button type="button" class="ap-btn ap-btn-ghost ap-btn-sm" :disabled="! selected.length" @click="clear">Clear</button>
                </div>
            </div>

            <input v-for="id in selected" :key="'in-'+id" type="hidden" :name="inputName + '[]'" :value="id">

            <div class="ap-picker-grid">
                <div v-for="card in filtered" :key="card.product_id"
                     @click="toggle(card.product_id)"
                     class="ap-pcard"
                     :class="{ 'is-selected': isSelected(card.product_id) }">
                    <span v-if="isSelected(card.product_id)" class="ap-pcard-num">@{{ position(card.product_id) }}</span>
                    <span class="ap-pcard-check"></span>

                    <div style="overflow: hidden;">
                        <img v-if="card.image" :src="card.image" loading="lazy" alt="" class="ap-pcard-img">
                        <div v-else class="ap-pcard-noimg">No image</div>
                    </div>

                    <div class="ap-pcard-body">
                        <div class="ap-pcard-name" :title="card.name">@{{ card.name }}</div>
                        <div class="ap-pcard-price">@{{ formatPrice(card.price) }}</div>
                        <div v-if="card.tags && card.tags.length" style="display:flex;gap:4px;flex-wrap:wrap;margin-top:4px;">
                            <span v-for="t in card.tags" :key="t" class="ap-badge" :class="t === 'out of stock' ? 'ap-tone-red' : 'ap-tone-gray'" style="font-size:10px;">@{{ t }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="candidates.length && ! filtered.length" class="ap-empty">
                <h3>No matches</h3>
                <p>Nothing matches “@{{ query }}”.</p>
            </div>

            <div v-if="! candidates.length" class="ap-empty">
                <h3>No eligible products</h3>
                <p>Approve products for Facebook, or relax the cooldown / selection mode in Settings.</p>
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
                return { selected: [], query: '' };
            },

            computed: {
                filtered() {
                    const q = this.query.trim().toLowerCase();

                    if (! q) {
                        return this.candidates;
                    }

                    return this.candidates.filter(c => (c.name || '').toLowerCase().includes(q));
                },
            },

            watch: {
                selected: {
                    deep: true,
                    handler() {
                        const byId = Object.fromEntries(this.candidates.map(c => [c.product_id, c]));

                        window.dispatchEvent(new CustomEvent('ap:items', {
                            detail: this.selected.map(id => byId[id]).filter(Boolean),
                        }));
                    },
                },
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

                clear() {
                    this.selected = [];
                },

                position(id) {
                    return this.selected.indexOf(id) + 1;
                },

                formatPrice(price) {
                    if (! (Number(price) > 0)) {
                        return '';
                    }

                    return '¥' + Number(price).toLocaleString('ja-JP', { maximumFractionDigits: 0 });
                },
            },
        });
    </script>
@endPushOnce

{{-- Multi-language product NAME editor.
     Loads ALL products at once. Search filters client-side.
     Save per row OR Save All dirty rows in one bulk request. --}}
<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.products.index.title') — Translations
    </x-slot>

    @include('admin::catalog.products.partials.view-nav')
    @include('admin::catalog.products.partials.reservations-modal')

    @push('styles')
    <style>
        .pt-toolbar {
            position:sticky; top:0; z-index:20;
            display:flex; flex-wrap:wrap; align-items:center; gap:8px;
            padding:10px 12px; margin-bottom:10px;
            background:#fff; border:1px solid #e5e7eb; border-radius:8px;
            box-shadow:0 1px 2px rgba(0,0,0,.04);
        }
        .dark .pt-toolbar { background:#111827; border-color:#1f2937; }
        .pt-toolbar input, .pt-toolbar select {
            height:34px; padding:0 10px; font-size:13px;
            background:#fff; border:1px solid #d1d5db; border-radius:6px; color:#111827;
        }
        .dark .pt-toolbar input, .dark .pt-toolbar select {
            background:#1f2937; border-color:#4b5563; color:#f3f4f6;
        }
        .pt-row {
            display:grid; gap:10px; align-items:center;
            grid-template-columns: 64px repeat({{ count($locales) }}, minmax(0,1fr)) 110px;
            padding:10px 12px;
            background:#fff; border:1px solid #e5e7eb; border-radius:8px;
            margin-bottom:8px;
        }
        .dark .pt-row { background:#111827; border-color:#1f2937; }
        .pt-row.is-dirty { border-color:#f59e0b; box-shadow:0 0 0 1px rgba(245,158,11,.18); }
        .pt-row .pt-img {
            width:64px; height:64px; border-radius:6px;
            object-fit:cover; background:#f3f4f6; border:1px solid #e5e7eb;
        }
        .dark .pt-row .pt-img { background:#1f2937; border-color:#374151; }
        .pt-field { display:flex; flex-direction:column; gap:3px; min-width:0; }
        .pt-field label {
            font-size:10px; font-weight:700; text-transform:uppercase;
            color:#6b7280; letter-spacing:.04em;
        }
        .dark .pt-field label { color:#9ca3af; }
        .pt-field input {
            width:100%; height:34px; padding:0 8px; font-size:13px;
            border:1px solid #d1d5db; border-radius:6px; background:#fff; color:#111827;
        }
        .dark .pt-field input { background:#1f2937; border-color:#4b5563; color:#f3f4f6; }
        .pt-field input:focus {
            outline:none; border-color:#2563eb; box-shadow:0 0 0 2px rgba(37,99,235,.18);
        }
        .pt-field input.dirty {
            border-color:#f59e0b; box-shadow:0 0 0 2px rgba(245,158,11,.15);
        }
        .pt-field input.saved { border-color:#10b981; }
        .pt-meta { font-size:11px; color:#6b7280; }
        .dark .pt-meta { color:#9ca3af; }
        .pt-actions { display:flex; flex-direction:column; gap:4px; }
        .pt-btn {
            height:30px; padding:0 12px; border-radius:6px;
            font-weight:600; font-size:12px; cursor:pointer; border:1px solid;
        }
        .pt-btn-save { background:#2563eb; color:#fff; border-color:#1d4ed8; }
        .pt-btn-save:hover { opacity:.9; }
        .pt-btn-save[disabled] { background:#cbd5e1; border-color:#94a3b8; cursor:not-allowed; }
        .pt-btn-bulk { background:#10b981; color:#fff; border-color:#059669; height:34px; padding:0 14px; font-size:13px; }
        .pt-btn-bulk:hover { opacity:.9; }
        .pt-btn-bulk[disabled] { background:#cbd5e1; border-color:#94a3b8; cursor:not-allowed; }
        .pt-btn-secondary {
            background:#fff; color:#374151; border-color:#d1d5db; height:34px;
        }
        .dark .pt-btn-secondary { background:#1f2937; color:#e5e7eb; border-color:#4b5563; }

        .pt-counter {
            display:inline-flex; align-items:center; gap:4px;
            padding:4px 10px; border-radius:6px;
            background:#fef3c7; color:#92400e; font-size:11px; font-weight:700;
            border:1px solid #fde68a;
        }
        .dark .pt-counter { background:#451a03; color:#fbbf24; border-color:#78350f; }
        .pt-counter.zero { background:#f3f4f6; color:#6b7280; border-color:#e5e7eb; }
        .dark .pt-counter.zero { background:#1f2937; color:#9ca3af; border-color:#374151; }

        /* Mobile: image+meta on top, locale fields stack, save full-width */
        @media (max-width: 768px) {
            .pt-row {
                grid-template-columns: 56px 1fr;
                grid-template-areas: "img meta" "fields fields" "actions actions";
                gap:10px;
            }
            .pt-row .pt-img-wrap { grid-area: img; }
            .pt-row .pt-meta-wrap { grid-area: meta; }
            .pt-row .pt-fields { grid-area: fields; display:grid; gap:8px; }
            .pt-row .pt-actions { grid-area: actions; flex-direction:row; justify-content:flex-end; }
            .pt-row .pt-img { width:56px; height:56px; }
        }
        @media (min-width: 769px) {
            .pt-row .pt-fields { display:contents; }
            .pt-row .pt-meta-wrap { display:none; }
        }
    </style>
    @endpush

    <v-product-translations></v-product-translations>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-product-translations-template">
            <div>
                <div class="flex items-center justify-between gap-4 max-sm:flex-wrap mb-3">
                    <p class="text-xl font-bold text-gray-800 dark:text-white">
                        Multi-language product names
                    </p>

                    <div class="pt-meta">
                        Edit names side-by-side per locale. Changes save to product_flat + attribute_values.
                    </div>
                </div>

                <div class="pt-toolbar">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search by name, SKU or ID…"
                        style="min-width:240px; flex:1 1 240px;"
                    />

                    <select v-model="filters.channel" @change="reload()">
                        <option v-for="ch in channels" :key="ch" :value="ch">@{{ ch }}</option>
                    </select>

                    <select v-model="filters.missing">
                        <option value="">All rows</option>
                        <option v-for="loc in locales" :key="loc.code" :value="loc.code">
                            Missing @{{ loc.code }}
                        </option>
                    </select>

                    <button type="button" class="pt-btn pt-btn-secondary" @click="reload()">↻ Reload</button>

                    <span class="pt-counter" :class="{ zero: dirtyCount === 0 }">
                        @{{ dirtyCount }} unsaved
                    </span>

                    <button
                        type="button"
                        class="pt-btn pt-btn-bulk"
                        :disabled="dirtyCount === 0 || bulkSaving"
                        @click="saveAll"
                    >
                        <span v-if="bulkSaving">Saving @{{ dirtyCount }}…</span>
                        <span v-else>💾 Save all (@{{ dirtyCount }})</span>
                    </button>

                    <span class="pt-meta" v-if="loading">Loading…</span>
                    <span class="pt-meta" v-else>
                        @{{ filteredRows.length }} / @{{ rows.length }} products
                    </span>
                </div>

                <div v-if="loading && rows.length === 0" class="pt-meta" style="padding:24px; text-align:center;">
                    Loading all products…
                </div>

                <div v-else-if="filteredRows.length === 0" class="pt-meta" style="padding:24px; text-align:center;">
                    No products match.
                </div>

                <div
                    class="pt-row"
                    :class="{ 'is-dirty': row._dirty }"
                    v-for="row in filteredRows"
                    :key="row.id"
                >
                    <div class="pt-img-wrap">
                        <img v-if="row.image" :src="row.image" class="pt-img" :alt="row.sku" />
                        <div v-else class="pt-img" style="display:flex;align-items:center;justify-content:center;font-size:9px;color:#9ca3af;">no img</div>
                    </div>

                    <div class="pt-meta-wrap pt-meta">
                        <strong>#@{{ row.id }}</strong> · @{{ row.sku }}
                        <span
                            v-if="parseInt(row.reserved) > 0"
                            class="pm-reserved-badge has"
                            style="margin-top:4px;display:inline-block;"
                            @click.stop="window.pmShowReservations(row.id)"
                        >
                            予約 @{{ row.reserved }}
                        </span>
                    </div>

                    <div class="pt-fields">
                        <div class="pt-field" v-for="loc in locales" :key="loc.code">
                            <label>@{{ loc.code }} — @{{ loc.name }}</label>
                            <input
                                type="text"
                                v-model="row.names[loc.code]"
                                @input="markDirty(row)"
                                :class="{ dirty: row._dirty, saved: row._savedAt && !row._dirty }"
                                placeholder="—"
                            />
                        </div>
                    </div>

                    <div class="pt-actions">
                        <button
                            class="pt-btn pt-btn-save"
                            :disabled="!row._dirty || row._saving"
                            @click="saveOne(row)"
                        >
                            <span v-if="row._saving">Saving…</span>
                            <span v-else-if="row._dirty">Save</span>
                            <span v-else>Saved</span>
                        </button>

                        <a
                            :href="'/admin/catalog/products/edit/' + row.id"
                            target="_blank"
                            class="pt-meta"
                            style="text-align:center; font-size:11px;"
                        >Edit full →</a>
                    </div>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-product-translations', {
                template: '#v-product-translations-template',

                data() {
                    return {
                        locales: @json($locales->map(fn ($l) => ['code' => $l->code, 'name' => $l->name])->values()),
                        channels: @json(\Webkul\Core\Models\Channel::query()->pluck('code')->values()),
                        filters: {
                            channel: @json($channel),
                            missing: '',
                        },
                        search: '',
                        rows: [],
                        loading: false,
                        bulkSaving: false,
                        listUrl:   "{{ route('admin.catalog.products.translations.list') }}",
                        bulkUrl:   "{{ route('admin.catalog.products.translations.bulk_update') }}",
                        updateUrl: "{{ route('admin.catalog.products.update_inline') }}",
                        csrf: document.querySelector('meta[name="csrf-token"]')?.content || "{{ csrf_token() }}",
                    };
                },

                computed: {
                    dirtyCount() {
                        return this.rows.filter(r => r._dirty).length;
                    },
                    filteredRows() {
                        const q = this.search.trim().toLowerCase();
                        const missing = this.filters.missing;
                        return this.rows.filter(row => {
                            if (missing && (row.names[missing] || '').trim() !== '') {
                                return false;
                            }
                            if (!q) return true;
                            if (String(row.id).includes(q)) return true;
                            if ((row.sku || '').toLowerCase().includes(q)) return true;
                            for (const l of this.locales) {
                                if ((row.names[l.code] || '').toLowerCase().includes(q)) return true;
                            }
                            return false;
                        });
                    },
                },

                mounted() {
                    this.reload();
                    window.addEventListener('beforeunload', this.warnIfDirty);
                },

                beforeUnmount() {
                    window.removeEventListener('beforeunload', this.warnIfDirty);
                },

                methods: {
                    warnIfDirty(e) {
                        if (this.dirtyCount > 0) {
                            e.preventDefault();
                            e.returnValue = '';
                        }
                    },

                    async reload() {
                        if (this.dirtyCount > 0 && ! confirm('You have ' + this.dirtyCount + ' unsaved changes. Discard and reload?')) {
                            return;
                        }
                        this.loading = true;
                        try {
                            const r = await this.$axios.get(this.listUrl, {
                                params: { channel: this.filters.channel },
                            });
                            this.rows = (r.data.data || []).map(row => {
                                this.locales.forEach(l => {
                                    if (!(l.code in row.names)) row.names[l.code] = '';
                                });
                                row._dirty   = false;
                                row._saving  = false;
                                row._savedAt = 0;
                                row._original = JSON.stringify(row.names);
                                return row;
                            });
                        } catch (e) {
                            console.error('translations: load failed', e);
                            this.$emitter.emit('add-flash', { type: 'error', message: 'Failed to load products.' });
                        } finally {
                            this.loading = false;
                        }
                    },

                    markDirty(row) {
                        row._dirty = JSON.stringify(row.names) !== row._original;
                    },

                    async saveOne(row) {
                        if (row._saving || !row._dirty) return;
                        row._saving = true;
                        try {
                            const fd = new FormData();
                            fd.append('id', row.id);
                            fd.append('channel', this.filters.channel);
                            this.locales.forEach(l => {
                                fd.append('names[' + l.code + ']', row.names[l.code] ?? '');
                            });
                            await this.$axios.post(this.updateUrl, fd, {
                                headers: {
                                    'X-CSRF-TOKEN': this.csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });
                            row._original = JSON.stringify(row.names);
                            row._dirty = false;
                            row._savedAt = Date.now();
                            this.$emitter.emit('add-flash', { type: 'success', message: 'Saved #' + row.id });
                        } catch (e) {
                            console.error('translations: save failed', e);
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: 'Save failed: ' + (e.response?.data?.message || e.message),
                            });
                        } finally {
                            row._saving = false;
                        }
                    },

                    async saveAll() {
                        const dirty = this.rows.filter(r => r._dirty);
                        if (dirty.length === 0 || this.bulkSaving) return;
                        if (! confirm('Save ' + dirty.length + ' product(s)?')) return;

                        this.bulkSaving = true;
                        dirty.forEach(r => { r._saving = true; });

                        try {
                            const updates = dirty.map(r => ({ id: r.id, names: r.names }));
                            const r = await this.$axios.post(this.bulkUrl, {
                                channel: this.filters.channel,
                                updates,
                            }, {
                                headers: {
                                    'X-CSRF-TOKEN': this.csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });

                            const errors = r.data?.errors || [];
                            const errorIds = new Set(errors.map(e => e.id));

                            dirty.forEach(row => {
                                if (errorIds.has(row.id)) return;
                                row._original = JSON.stringify(row.names);
                                row._dirty    = false;
                                row._savedAt  = Date.now();
                            });

                            this.$emitter.emit('add-flash', {
                                type: errors.length ? 'warning' : 'success',
                                message: 'Saved ' + (r.data?.touched ?? dirty.length) + (errors.length ? ' (' + errors.length + ' errors)' : ''),
                            });
                        } catch (e) {
                            console.error('translations: bulk save failed', e);
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: 'Bulk save failed: ' + (e.response?.data?.message || e.message),
                            });
                        } finally {
                            this.bulkSaving = false;
                            dirty.forEach(r => { r._saving = false; });
                        }
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>

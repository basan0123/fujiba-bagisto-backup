<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.reporting.' . $entity . '.index.' . request()->query('type'))
    </x-slot>

    <v-reporting-stats-table>
        <!-- Shimmer -->
        <x-admin::shimmer.reporting.view />
    </v-reporting-stats-table>

    {{-- Tabulator: sortable + resizable + draggable columns for reporting tables.
         Loaded from CDN to match the catalog list-view pattern (see
         resources/views/catalog/products/list-view.blade.php).

         The admin layout exposes `meta`, `styles`, and `scripts` stacks
         (see components/layouts/index.blade.php). There is no `head`
         stack — pushing to it would silently drop the asset and the
         table would never render. --}}
    @pushOnce('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@6.3.1/dist/css/tabulator_simple.min.css">
        <style>
            .rt-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:8px; padding:3px 8px; background:#f9fafb; border:1px solid #e5e7eb; border-bottom:none; border-radius:6px 6px 0 0; font-size:11px; color:#374151; line-height:1.2; }
            .dark .rt-toolbar { background:#111827; border-color:#1f2937; color:#d1d5db; }
            .rt-toolbar button { padding:2px 8px; border:1px solid #d1d5db; border-radius:3px; background:#fff; cursor:pointer; font-size:11px; }
            .rt-toolbar button:hover { background:#f3f4f6; }
            .dark .rt-toolbar button { background:#1f2937; border-color:#374151; color:#e5e7eb; }
            .dark .rt-toolbar button:hover { background:#0f172a; }
            .rt-toolbar .rt-slider { display:flex; align-items:center; gap:4px; }
            .rt-toolbar input[type=range] { width:90px; }
            /* Hide the long instructional hint on narrow viewports — the
               drag/sort/resize affordances are discoverable on hover. */
            @media (max-width: 768px) { .rt-toolbar .rt-hint { display:none; } }
            #reporting-tabulator-wrap { width:100%; position:relative; }
            #reporting-tabulator-wrap.rt-wide { max-width:none; }
            #reporting-tabulator-wrap:not(.rt-wide) { max-width:1600px; }
            #reporting-tabulator { border:1px solid #e5e7eb; border-radius:0 0 8px 8px; }
            .dark #reporting-tabulator { border-color:#1f2937; }
            /* Drag handle sits on the table's right border. Hovering widens
               and tints the border to signal it's grabbable. */
            #reporting-tabulator-wrap .rt-table-resize {
                position:absolute; top:36px; bottom:0; right:-4px; width:8px;
                cursor:col-resize; z-index:5; background:transparent;
            }
            #reporting-tabulator-wrap .rt-table-resize:hover,
            #reporting-tabulator-wrap.rt-resizing .rt-table-resize { background:#3b82f6; opacity:.7; }
            #reporting-tabulator-wrap.rt-resizing { user-select:none; }
            #reporting-tabulator .tabulator-header { background:#f3f4f6 !important; border-bottom:1.5px solid #9ca3af !important; }
            .dark #reporting-tabulator .tabulator-header { background:#1f2937 !important; border-bottom-color:#4b5563 !important; }
            #reporting-tabulator .tabulator-col { background:#f3f4f6 !important; color:#374151; border-right:1px solid #d1d5db !important; font-weight:600; }
            .dark #reporting-tabulator .tabulator-col { background:#1f2937 !important; color:#d1d5db; border-right-color:#374151 !important; }
            #reporting-tabulator .tabulator-col-resize-handle { width:8px !important; }
            #reporting-tabulator .tabulator-col-resize-handle:hover { background:#3b82f6 !important; }
            #reporting-tabulator .tabulator-row { background:#fff; }
            #reporting-tabulator .tabulator-row.tabulator-row-even { background:#fafbfc; }
            #reporting-tabulator .tabulator-row:hover { background:#eff6ff !important; }
            .dark #reporting-tabulator .tabulator-row { background:#0b1220; color:#e5e7eb; }
            .dark #reporting-tabulator .tabulator-row.tabulator-row-even { background:#0f172a; }
            .dark #reporting-tabulator .tabulator-row:hover { background:rgba(30,58,138,.18) !important; }
            #reporting-tabulator .tabulator-cell { padding:6px 10px; border-right:1px solid #e5e7eb; font-variant-numeric:tabular-nums; }
            .dark #reporting-tabulator .tabulator-cell { border-right-color:#374151; }
            #reporting-tabulator .rt-thumb { width:40px; height:40px; object-fit:cover; border-radius:4px; }
            #reporting-tabulator .rt-thumb-empty { display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:4px; background:#e5e7eb; color:#6b7280; font-size:18px; }

            /* Mobile: rotate header titles -45° so narrow columns can keep
               their labels readable instead of truncating. Tabulator wraps
               each title in .tabulator-col-title, which we transform; the
               column header itself gets extra height so the rotated text
               doesn't clip. The ID column is also hidden via a class flag
               we add to the column def at runtime. */
            @media (max-width: 768px) {
                #reporting-tabulator .tabulator-col { height:90px !important; vertical-align:bottom; }
                #reporting-tabulator .tabulator-col .tabulator-col-content { padding:4px 2px; }
                #reporting-tabulator .tabulator-col-title {
                    transform: rotate(-45deg);
                    transform-origin: left bottom;
                    white-space: nowrap;
                    text-align: left;
                    width: auto;
                    display: inline-block;
                    margin-top: 50px;
                    padding-left: 4px;
                    font-size: 11px;
                }
                #reporting-tabulator .tabulator-header { height:90px !important; }
                #reporting-tabulator .tabulator-cell { padding:4px 6px; font-size:11px; }
                #reporting-tabulator .rt-thumb,
                #reporting-tabulator .rt-thumb-empty { width:28px; height:28px; font-size:14px; }
                /* Hide ID column on mobile — image+name are enough context. */
                #reporting-tabulator .tabulator-col[tabulator-field="id"],
                #reporting-tabulator .tabulator-cell[tabulator-field="id"] { display:none !important; }
            }
        </style>
    @endPushOnce

    @pushOnce('scripts')
        {{-- Load Tabulator before the Vue module below so renderTable() finds
             window.Tabulator immediately instead of polling. --}}
        <script src="https://cdn.jsdelivr.net/npm/tabulator-tables@6.3.1/dist/js/tabulator.min.js"></script>

        <script
            type="text/x-template"
            id="v-reporting-stats-table-template"
        >
            <div>
                <!-- Page Header — compact, single tight row -->
                <div class="mb-2 flex items-center justify-between gap-2 max-sm:flex-wrap">
                    <!-- Title -->
                    <div>
                        <p class="text-base font-semibold leading-5 text-gray-800 dark:text-white">
                            @lang('admin::app.reporting.' . $entity . '.index.' . request()->query('type'))
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-1.5">
                       <!-- Back Button -->
                        <div>
                            <a v-if="entity === 'customers'"
                                href="{{ route('admin.reporting.customers.index') }}"
                                class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800">
                                @lang('admin::app.reporting.view.back-btn')
                            </a>
                            
                            <a v-else-if="entity === 'products'"
                                href="{{ route('admin.reporting.products.index') }}"
                                class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800">
                                @lang('admin::app.reporting.view.back-btn')
                            </a>
                            
                            <a v-else
                                href="{{ route('admin.reporting.sales.index') }}"
                                class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800">
                                @lang('admin::app.reporting.view.back-btn')
                            </a>
                        </div>
             
                        <!-- Export Button -->
                        <x-admin::dropdown position="bottom-right">
                            <x-slot:toggle>
                                <div class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800">
                                    <span class="icon-export text-xl text-gray-600"></span>
            
                                    @lang('admin::app.export.export')
                                </div>
                            </x-slot>

                            <x-slot:menu class="!p-0 shadow-[0_5px_20px_rgba(0,0,0,0.15)] dark:border-gray-800">
                                <x-admin::dropdown.menu.item>
                                    <span @click="exportReporting('csv')">
                                        @lang('admin::app.reporting.view.export-csv')
                                    </span>
                                </x-admin::dropdown.menu.item>

                                <x-admin::dropdown.menu.item>
                                    <span @click="exportReporting('xls')">
                                        @lang('admin::app.reporting.view.export-xls')
                                    </span>
                                </x-admin::dropdown.menu.item>
                            </x-slot>
                        </x-admin::dropdown>
                    </div>
                </div>

                <div class="mb-2 flex items-center justify-between gap-2 max-sm:flex-wrap">
                    <div class="flex items-center gap-x-1">
                        <!-- Channel Filter -->
                        <template v-if="channels.length > 2">
                            <x-admin::dropdown position="bottom-{{ core()->getCurrentLocale()->direction === 'ltr' ? 'left' : 'right' }}" >
                                <x-slot:toggle>
                                    <button
                                        type="button"
                                        class="transparent-button px-1 py-1.5 hover:bg-gray-200 focus:bg-gray-200 dark:text-white dark:hover:bg-gray-800 dark:focus:bg-gray-800"
                                    >
                                        <span class="icon-store text-2xl"></span>

                                        @{{ channels.find(channel => channel.code == filters.channel).name }}
                                        
                                        <span class="icon-sort-down text-2xl"></span>
                                    </button>
                                </x-slot>

                                <x-slot:menu class="!p-0 shadow-[0_5px_20px_rgba(0,0,0,0.15)] dark:border-gray-800">
                                    <x-admin::dropdown.menu.item
                                        v-for="channel in channels"
                                        ::class="{'bg-gray-100 dark:bg-gray-950': channel.code == filters.channel}"
                                        @click="filters.channel = channel.code"
                                    >
                                        @{{ channel.name }}
                                    </x-admin::dropdown.menu.item>
                                </x-slot>
                            </x-admin::dropdown>
                        </template>

                        <!-- Day Filter -->
                        @if (in_array(request()->query('type'), [
                            'total-sales',
                            'total-orders',
                            'average-sales',
                            'tax-collected',
                            'shipping-collected',
                            'refunds',
                            'total-customers',
                            'total-sold-quantities',
                            'total-products-added-to-wishlist',
                        ]))
                            <div class="relative inline-flex w-full max-w-max">
                                <select
                                    class="w-full cursor-pointer appearance-none rounded-md border bg-white px-2.5 py-1.5 pr-8 text-center leading-6 text-gray-600 transition hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                                    v-model="filters.period"
                                >
                                    <option value="day">
                                        @lang('admin::app.reporting.view.day')
                                    </option>
                                    
                                    <option value="month">
                                        @lang('admin::app.reporting.view.month')
                                    </option>
                            
                                    <option value="year">
                                        @lang('admin::app.reporting.view.year')
                                    </option>
                                </select>
                                
                                <span class="icon-sort-down pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 transform text-2xl text-gray-600 dark:text-gray-300"></span>
                            </div>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-1.5">
                        <x-admin::flat-picker.date class="!w-[140px]" ::allow-input="false">
                            <input
                                class="flex min-h-[39px] w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400"
                                v-model="filters.start"
                                placeholder="@lang('admin::app.reporting.view.start-date')"
                            />
                        </x-admin::flat-picker.date>

                        <x-admin::flat-picker.date class="!w-[140px]" ::allow-input="false">
                            <input
                                class="flex min-h-[39px] w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400"
                                v-model="filters.end"
                                placeholder="@lang('admin::app.reporting.view.end-date')"
                            />
                        </x-admin::flat-picker.date>

                        <div class="inline-flex rounded-md border bg-white dark:border-gray-800 dark:bg-gray-900" role="group">
                            <button
                                type="button"
                                @click="filters.mode = 'orders'"
                                ::class="filters.mode === 'orders' ? 'bg-blue-600 text-white' : 'text-gray-600 dark:text-gray-300'"
                                class="px-3 py-1.5 text-sm rounded-l-md transition-all hover:bg-gray-100 dark:hover:bg-gray-800"
                            >
                                Orders
                            </button>
                            <button
                                type="button"
                                @click="filters.mode = 'payments'"
                                ::class="filters.mode === 'payments' ? 'bg-blue-600 text-white' : 'text-gray-600 dark:text-gray-300'"
                                class="px-3 py-1.5 text-sm rounded-r-md transition-all hover:bg-gray-100 dark:hover:bg-gray-800"
                            >
                                Payments
                            </button>
                        </div>
                    </div>
                </div>

                <template v-if="isLoading">
                    <div class="table-responsive box-shadow grid w-full overflow-hidden rounded bg-white dark:bg-gray-900">
                        <x-admin::shimmer.datagrid.table.head />
                        <x-admin::shimmer.datagrid.table.body />
                    </div>
                </template>

                <template v-else>
                    {{-- Tabulator host. Toolbar gives the user a font-size slider,
                         a wide/narrow toggle (constrained max-width vs full screen),
                         and a "Reset Layout" button that wipes per-(user,entity,type)
                         persistence in localStorage. --}}
                    <div id="reporting-tabulator-wrap">
                        <div class="rt-toolbar">
                            <div class="rt-slider">
                                <span>@lang('admin::app.reporting.view.font-size'):</span>
                                <input
                                    type="range" min="10" max="20" step="1"
                                    v-model.number="fontSize"
                                    @input="onFontSize"
                                />
                                <span>@{{ fontSize }}px</span>
                            </div>

                            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                                <input type="checkbox" v-model="wide" @change="onWideToggle" />
                                <span>@lang('admin::app.reporting.view.full-width')</span>
                            </label>

                            <button type="button" @click="resetLayout">
                                @lang('admin::app.reporting.view.reset-layout')
                            </button>

                            <span class="rt-hint" style="margin-left:auto;opacity:.7;">
                                @lang('admin::app.reporting.view.layout-hint')
                            </span>
                        </div>

                        <div id="reporting-tabulator"></div>

                        {{-- Drag handle on the right edge of the table.
                             Mousedown enters a drag loop that resizes the
                             wrapper's max-width; the final width is saved
                             to localStorage so the next page-load matches. --}}
                        <div
                            class="rt-table-resize"
                            @mousedown="onTableResizeStart"
                            @touchstart="onTableResizeStart"
                            title="Drag to resize table width"
                        ></div>
                    </div>
                </template>
            </div>
        </script>

        <script type="module">
            // Per-(user, entity, type) localStorage keys. Each report page gets
            // its own layout slot so visibility/widths/order/sort for the
            // top-selling-by-quantity table don't bleed into top-search-terms.
            const RT_USER  = "{{ optional(auth()->guard('admin')->user())->id ?? 'guest' }}";
            const RT_ENT   = "{{ $entity }}";
            const RT_TYPE  = "{{ request()->query('type') }}";
            const RT_NS    = `rt_${RT_USER}_${RT_ENT}_${RT_TYPE}`;
            const RT_KEYS  = {
                hidden:    `${RT_NS}_hidden`,
                widths:    `${RT_NS}_widths`,
                order:     `${RT_NS}_order`,
                sort:      `${RT_NS}_sort`,
                font:      `${RT_NS}_font`,
                wide:      `${RT_NS}_wide`,
                tableWidth:`${RT_NS}_tw`,
            };
            const rtLoad = (k, fb) => { try { const v = JSON.parse(localStorage.getItem(k) ?? 'null'); return v ?? fb; } catch { return fb; } };
            const rtSave = (k, v) => localStorage.setItem(k, JSON.stringify(v));

            app.component('v-reporting-stats-table', {
                template: '#v-reporting-stats-table-template',

                data() {
                    // Seed channel/start/end/mode from the shared global filter
                    // (fujiba_global_filters) so the detail page opens on the same
                    // window the dashboard / reporting index pages were left on.
                    // type + period stay page-local. fjSavedFilter() already
                    // validates ISO dates, start<=end, mode, and fresh-login reset.
                    const seed = (window.fjSavedFilter ? window.fjSavedFilter() : {}) || {};

                    return {
                        storageKey: 'fujiba_global_filters:' + window.location.host,

                        channels: [
                            {
                                name: "@lang('admin::app.reporting.view.all-channels')",
                                code: ''
                            },
                            ...@json(core()->getAllChannels()),
                        ],

                        filters: {
                            type: "{{ request()->query('type') }}",

                            period: 'day',

                            channel: seed.channel ?? '',

                            start: seed.start ?? "{{ $startDate->format('Y-m-d') }}",

                            end: seed.end ?? "{{ $endDate->format('Y-m-d') }}",

                            mode: seed.mode ?? 'orders',
                        },

                        reporting: [],

                        isLoading: true,

                        entity: "{{ $entity }}",

                        // Tabulator state — instance + restored prefs.
                        table:      null,
                        fontSize:   rtLoad(RT_KEYS.font, 13),
                        wide:       rtLoad(RT_KEYS.wide, false),
                        tableWidth: rtLoad(RT_KEYS.tableWidth, null),

                        // Transient drag state for the right-edge handle.
                        _resizeStartX:  0,
                        _resizeStartW:  0,
                    }
                },

                mounted() {
                    // Apply wide-mode class + saved table width immediately so
                    // the first render respects the user's preferences.
                    this.applyWideClass();
                    this.applyTableWidth();
                    this.getStats();

                    // Keep the table reaching the viewport bottom as the
                    // window resizes. Stored so beforeUnmount can detach it.
                    this._onResize = () => this.onWindowResize();
                    window.addEventListener('resize', this._onResize);
                },

                beforeUnmount() {
                    if (this._onResize) window.removeEventListener('resize', this._onResize);
                    this.table?.destroy();
                },

                watch: {
                    filters: {
                        handler() {
                            // Persist the global-filter slice (channel/start/end/
                            // mode) back to the shared key so a change here carries
                            // over to the dashboard + reporting index pages. type +
                            // period are page-local and intentionally not stored.
                            try {
                                let prev = {};
                                try { prev = JSON.parse(localStorage.getItem(this.storageKey) || '{}') || {}; } catch (e) {}
                                localStorage.setItem(this.storageKey, JSON.stringify({
                                    ...prev,
                                    channel: this.filters.channel,
                                    start:   this.filters.start,
                                    end:     this.filters.end,
                                    mode:    this.filters.mode,
                                }));
                            } catch (e) {}

                            this.getStats();
                        },

                        deep: true
                    }
                },

                methods: {
                    getStats() {
                        this.isLoading = true;

                        this.$axios.get("{{ route('admin.reporting.' . $entity . '.view.stats') }}", {
                                params: this.filters
                            })
                            .then(response => {
                                this.reporting = response.data;

                                this.isLoading = false;
                                // Wait for v-else to mount #reporting-tabulator.
                                this.$nextTick(() => this.renderTable());
                            })
                            .catch(error => {});
                    },

                    exportReporting(format) {
                        let filters = { ...this.filters, format };

                        window.open(
                            "{{ route('admin.reporting.' . $entity . '.export') }}?"  + new URLSearchParams(filters).toString(),
                            '_blank'
                        );
                    },

                    // ── Tabulator integration ─────────────────────────────

                    /**
                     * Height that makes the table reach the bottom of the
                     * viewport. Measured from the host element's top offset so
                     * it adapts to however tall the header/toolbar/filters end
                     * up — no hard-coded magic number. Falls back to a sane
                     * value before the element is in the DOM.
                     */
                    computeTableHeight() {
                        const host = document.getElementById('reporting-tabulator');
                        const top  = host ? host.getBoundingClientRect().top : 240;
                        // Leave a small bottom gutter so the table edge isn't
                        // flush against the window.
                        return Math.max(320, window.innerHeight - top - 24);
                    },

                    // Re-fit the table height on viewport resize (orientation
                    // change, devtools open/close, window drag).
                    onWindowResize() {
                        if (! this.table) return;
                        this.table.setHeight(this.computeTableHeight());
                    },

                    renderTable() {
                        if (typeof Tabulator === 'undefined') {
                            // CDN script may still be loading on the first
                            // paint. Re-try until it's available.
                            return setTimeout(() => this.renderTable(), 80);
                        }

                        const host = document.getElementById('reporting-tabulator');
                        if (! host) return;

                        const cols    = this.reporting?.statistics?.columns ?? [];
                        const records = this.reporting?.statistics?.records ?? [];

                        const tabCols = this.buildColumns(cols, records);

                        this.table?.destroy();
                        this.table = new Tabulator(host, {
                            data:               records,
                            columns:            tabCols,
                            // `fitData` sizes each column to its own content
                            // instead of stretching them to fill the wrapper.
                            // Any leftover space stays empty on the right — the
                            // table no longer pads columns out to full width.
                            // Per-column resize still works; widths are saved
                            // and replayed on the next visit.
                            layout:             'fitData',
                            // Fill the viewport down to the bottom edge. Height
                            // is measured at build time from the host's top
                            // offset (see computeTableHeight) so the table is
                            // as tall as the screen allows, with internal row
                            // scroll. The page itself stays scroll-free.
                            height:             this.computeTableHeight(),
                            placeholder:        "@lang('admin::app.reporting.view.not-available')",
                            movableColumns:     true,
                            resizableColumnFit: false,
                            initialSort:        rtLoad(RT_KEYS.sort, []),
                        });

                        this.table.on('tableBuilt',     this.onTableBuilt);
                        this.table.on('columnResized',  this.onColResized);
                        this.table.on('columnMoved',    this.onColMoved);
                        this.table.on('dataSorted',     this.onSorted);

                        this.applyFontSize();
                    },

                    /**
                     * Convert helper column descriptors into Tabulator column
                     * defs. Image columns get a thumbnail formatter; everything
                     * else is sortable with an auto-detected sorter (numbers
                     * sort numerically, strings alphabetically). Saved widths
                     * are applied per-field.
                     */
                    buildColumns(cols, records) {
                        const widths = rtLoad(RT_KEYS.widths, {});
                        const order  = rtLoad(RT_KEYS.order, null);

                        let defs = cols.map(c => {
                            const isImage = c.type === 'image';
                            const def = {
                                title:      c.label || '',
                                field:      c.key,
                                resizable:  true,
                                headerSort: ! isImage,
                            };

                            if (widths[c.key]) def.width = widths[c.key];

                            if (isImage) {
                                def.formatter = (cell) => {
                                    const v = cell.getValue();
                                    return v
                                        ? `<img class="rt-thumb" src="${v}" alt="">`
                                        : '<span class="rt-thumb-empty">📷</span>';
                                };
                                def.hozAlign = 'center';
                                def.width    = widths[c.key] || 64;
                            } else {
                                // Pick a sorter based on the first non-null
                                // sample value in the records.
                                const sample = records.find(r => r?.[c.key] != null)?.[c.key];
                                if (typeof sample === 'number' || (typeof sample === 'string' && sample !== '' && ! isNaN(Number(sample)))) {
                                    def.sorter   = 'number';
                                    def.hozAlign = 'right';
                                } else {
                                    def.sorter = 'string';
                                }
                            }

                            return def;
                        });

                        // Apply saved column order. Unknown / new columns are
                        // appended in their natural position.
                        if (order && Array.isArray(order) && order.length) {
                            const byField = new Map(defs.map(d => [d.field, d]));
                            const seen = new Set();
                            const ordered = [];
                            order.forEach(f => {
                                if (byField.has(f)) { ordered.push(byField.get(f)); seen.add(f); }
                            });
                            defs.forEach(d => { if (! seen.has(d.field)) ordered.push(d); });
                            defs = ordered;
                        }

                        return defs;
                    },

                    onTableBuilt() {
                        const hidden = rtLoad(RT_KEYS.hidden, {});
                        Object.keys(hidden).forEach(f => {
                            if (hidden[f]) this.table.getColumn(f)?.hide();
                        });
                    },

                    onColResized(col) {
                        const f = col.getField();
                        if (! f) return;
                        const m = rtLoad(RT_KEYS.widths, {});
                        m[f] = col.getWidth();
                        rtSave(RT_KEYS.widths, m);
                    },

                    onColMoved() {
                        const fields = this.table.getColumns(false).map(c => c.getField()).filter(Boolean);
                        rtSave(RT_KEYS.order, fields);
                    },

                    onSorted(sorters) {
                        rtSave(RT_KEYS.sort, sorters.map(s => ({ column: s.field, dir: s.dir })));
                    },

                    onFontSize() {
                        rtSave(RT_KEYS.font, this.fontSize);
                        this.applyFontSize();
                    },

                    applyFontSize() {
                        const host = document.getElementById('reporting-tabulator');
                        if (host) host.style.fontSize = `${this.fontSize}px`;
                    },

                    onWideToggle() {
                        rtSave(RT_KEYS.wide, this.wide);
                        this.applyWideClass();
                        // Tabulator caches layout; force a redraw so the new
                        // width is honored.
                        this.table?.redraw(true);
                    },

                    applyWideClass() {
                        const wrap = document.getElementById('reporting-tabulator-wrap');
                        if (wrap) wrap.classList.toggle('rt-wide', !! this.wide);
                    },

                    // Apply the saved table width by setting an explicit
                    // max-width on the wrapper. When the user is in
                    // wide-mode the rt-wide rule wins; otherwise this
                    // overrides the default 1600px cap.
                    applyTableWidth() {
                        const wrap = document.getElementById('reporting-tabulator-wrap');
                        if (! wrap) return;
                        if (this.tableWidth && this.tableWidth > 320) {
                            wrap.style.maxWidth = `${this.tableWidth}px`;
                        } else {
                            wrap.style.maxWidth = '';
                        }
                    },

                    // Right-edge drag handle: track mouse/touch movement,
                    // update wrapper width live, and persist final width
                    // to localStorage on release.
                    onTableResizeStart(e) {
                        const wrap = document.getElementById('reporting-tabulator-wrap');
                        if (! wrap) return;
                        e.preventDefault();

                        this._resizeStartX = (e.touches ? e.touches[0].clientX : e.clientX);
                        this._resizeStartW = wrap.getBoundingClientRect().width;
                        wrap.classList.add('rt-resizing');

                        const onMove = (ev) => {
                            const x  = ev.touches ? ev.touches[0].clientX : ev.clientX;
                            const dx = x - this._resizeStartX;
                            // Clamp to a sensible range so the table can't
                            // shrink past unusable width or grow past the
                            // viewport.
                            const w  = Math.max(320, Math.min(window.innerWidth - 40, this._resizeStartW + dx));
                            wrap.style.maxWidth = `${w}px`;
                            this.tableWidth = Math.round(w);
                            this.table?.redraw(true);
                        };

                        const onUp = () => {
                            wrap.classList.remove('rt-resizing');
                            window.removeEventListener('mousemove', onMove);
                            window.removeEventListener('mouseup',   onUp);
                            window.removeEventListener('touchmove', onMove);
                            window.removeEventListener('touchend',  onUp);
                            rtSave(RT_KEYS.tableWidth, this.tableWidth);
                        };

                        window.addEventListener('mousemove', onMove);
                        window.addEventListener('mouseup',   onUp);
                        window.addEventListener('touchmove', onMove, { passive: false });
                        window.addEventListener('touchend',  onUp);
                    },

                    resetLayout() {
                        Object.values(RT_KEYS).forEach(k => localStorage.removeItem(k));
                        this.fontSize   = 13;
                        this.wide       = false;
                        this.tableWidth = null;
                        this.applyWideClass();
                        this.applyTableWidth();
                        this.renderTable();
                    },
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>

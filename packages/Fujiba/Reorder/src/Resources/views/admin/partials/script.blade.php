<script>
(function () {
    function boot() {
        var root = document.getElementById('reorder-app');
        if (!root) { return; }

        var urls = {
            products:     root.dataset.productsUrl,
            store:        root.dataset.storeUrl,            // legacy reorder_todos endpoint
            batches:      root.dataset.batchesUrl,
            batchesStore: root.dataset.batchesStoreUrl,
            labels:       root.dataset.labelsUrl,
            arriveBase:   root.dataset.arriveUrl,
        };
        var csrf      = root.dataset.csrf;
        var labels    = window.__reorderLabels || {};
        var preselect = [];
        try { preselect = JSON.parse(root.dataset.preselect || '[]') || []; } catch (_) {}
        preselect = preselect.map(function (n) { return parseInt(n, 10); }).filter(Boolean);

        var state = {
            rows:         [],
            clipboard:    [],
            search:       '',
            channel:      root.dataset.channelCode || '',
            source:       '',
            saving:       false,
            // Server-side products filter is whatever the user requested via
            // the buy_at sort dropdown; we keep that, but the visible sort is
            // now driven entirely by client-side sortGrid / sortClip.
            serverSortBuyAt: 'asc',
            // Client sort: { key, dir } per table.
            sortGrid:     { key: null, dir: 'asc' },
            sortClip:     { key: null, dir: 'asc' },
            // Most recent saved batch (for the "Start Receiving" link).
            pendingBatch: null,
            // Which clipboard rows are checked for bulk fill.
            clipSelected: {},
        };

        var els = {
            search:    document.getElementById('reorder-search'),
            channel:   document.getElementById('reorder-channel'),
            source:    document.getElementById('reorder-source'),
            all:       document.getElementById('reorder-all'),
            rows:      document.getElementById('reorder-rows'),
            gridTable: document.getElementById('reorder-grid-table'),
            clip:      document.getElementById('reorder-clip'),
            clipFoot:  document.getElementById('reorder-clip-foot'),
            clipTable: document.getElementById('reorder-clip-table'),
            save:      document.getElementById('reorder-save'),
            print:     document.getElementById('reorder-print'),
            savedMsg:  document.getElementById('reorder-saved-msg'),
            zoom:      document.getElementById('reorder-zoom'),
            bulkQty:   document.getElementById('reorder-bulk-qty'),
            bulkFill:  document.getElementById('reorder-bulk-fill'),
            clipAllH:  document.getElementById('reorder-clip-allhead'),
            batchBar:  document.getElementById('reorder-batch-bar'),
            batchId:   document.getElementById('reorder-batch-id'),
            receive:   document.getElementById('reorder-receive'),
            toast:     document.getElementById('reorder-toast'),
            // Counters
            gridSel:    document.getElementById('reorder-grid-sel'),
            gridTotal:  document.getElementById('reorder-grid-total'),
            clipCount:  document.getElementById('reorder-clip-count'),
            clipSel:    document.getElementById('reorder-clip-sel'),
            saveCount:  document.getElementById('reorder-save-count'),
            miniCount:  document.getElementById('reorder-mini-count'),
        };

        // Single source of truth for the four selection counters that
        // appear above the grid (sel/total) and inside the clipboard panel
        // (items count, multi-select count, Save-batch button suffix, mobile
        // mini-bar count). Called from both render passes so they stay in
        // sync regardless of which side triggered the change.
        function updateCounters() {
            var total       = state.rows.length;
            // "Selected" in the grid context = number of grid rows that are
            // also in the clipboard. Avoid scanning all of state.rows when
            // either side is empty.
            var selOnGrid = 0;
            if (state.clipboard.length && total) {
                var clipIds = {};
                state.clipboard.forEach(function (c) { clipIds[c.product_id] = true; });
                state.rows.forEach(function (r) { if (clipIds[r.id]) selOnGrid++; });
            }
            var clipCount = state.clipboard.length;
            var clipSel   = 0;
            for (var k in state.clipSelected) {
                if (state.clipSelected[k]) clipSel++;
            }

            if (els.gridSel)   els.gridSel.textContent   = selOnGrid;
            if (els.gridTotal) els.gridTotal.textContent = total;
            if (els.clipCount) els.clipCount.textContent = clipCount;
            if (els.clipSel)   els.clipSel.textContent   = clipSel;
            if (els.miniCount) els.miniCount.textContent = clipCount;
            if (els.saveCount) els.saveCount.textContent = clipCount ? '(' + clipCount + ')' : '';
        }

        function showToast(msg, kind) {
            if (!els.toast) return;
            var color = kind === 'error' ? 'bg-red-600' : kind === 'warn' ? 'bg-amber-600' : 'bg-green-600';
            els.toast.textContent = msg;
            els.toast.className = 'fixed bottom-4 right-4 z-50 rounded px-4 py-2 text-white text-sm shadow-xl ' + color;
            setTimeout(function () { els.toast.classList.add('hidden'); }, 3000);
        }

        function money(v) {
            return (Number(v) || 0).toLocaleString(undefined, { maximumFractionDigits: 2 });
        }

        function escapeHtml(s) {
            return String(s == null ? '' : s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        // Image cell shared by grid + clipboard. The wrapper div carries
        // a "🔍" zoom button visible on hover. Clicking either the image
        // or the button opens the zoom overlay (handled by the existing
        // delegated click listener that reads data-zoom).
        function imageCell(url, smallClass) {
            var cls = smallClass || 'reorder-img';
            if (!url) return '<div class="' + cls + '"></div>';
            var safe = escapeHtml(url);
            return '<span class="reorder-img-wrap">'
                + '<img data-zoom="' + safe + '" src="' + safe + '" class="' + cls + '" onerror="this.style.display=\'none\'"/>'
                + '<span class="reorder-img-zoom" data-zoom="' + safe + '" title="Zoom">🔍</span>'
                + '</span>';
        }

        function isSelected(id) {
            return state.clipboard.some(function (c) { return c.product_id === id; });
        }

        // ───────── client sort ─────────
        function compareValues(av, bv) {
            if (av == null && bv == null) return 0;
            if (av == null) return 1;
            if (bv == null) return -1;
            if (typeof av === 'number' && typeof bv === 'number') return av - bv;
            av = String(av).toLowerCase(); bv = String(bv).toLowerCase();
            return av < bv ? -1 : av > bv ? 1 : 0;
        }

        function sortedGridRows() {
            var rows = state.rows.slice();
            var key = state.sortGrid.key;
            if (!key) return rows;
            var dir = state.sortGrid.dir === 'asc' ? 1 : -1;
            rows.sort(function (a, b) { return compareValues(a[key], b[key]) * dir; });
            return rows;
        }

        function sortedClipRows() {
            var rows = state.clipboard.slice();
            var key = state.sortClip.key;
            if (!key) return rows;
            var dir = state.sortClip.dir === 'asc' ? 1 : -1;
            rows.sort(function (a, b) {
                var av, bv;
                if (key === 'subtotal') { av = a.wish_qty * a.cost; bv = b.wish_qty * b.cost; }
                else { av = a[key]; bv = b[key]; }
                return compareValues(av, bv) * dir;
            });
            return rows;
        }

        function paintArrows(table, sortState) {
            if (!table) return;
            table.querySelectorAll('thead th[data-sort]').forEach(function (th) {
                var arr = th.querySelector('.sort-arr');
                if (!arr) return;
                if (th.dataset.sort === sortState.key) {
                    arr.textContent = sortState.dir === 'asc' ? '▲' : '▼';
                } else {
                    arr.textContent = '';
                }
            });
        }

        function bindSort(table, sortState, rerender) {
            if (!table) return;
            table.querySelectorAll('thead th[data-sort]').forEach(function (th) {
                th.addEventListener('click', function () {
                    var k = th.dataset.sort;
                    if (sortState.key === k) sortState.dir = sortState.dir === 'asc' ? 'desc' : 'asc';
                    else { sortState.key = k; sortState.dir = 'asc'; }
                    rerender();
                });
            });
        }

        // ───────── grid render ─────────
        function renderRows() {
            var rows = sortedGridRows();
            paintArrows(els.gridTable, state.sortGrid);

            if (!rows.length) {
                els.rows.innerHTML = '<tr><td colspan="10" class="text-center py-6 text-gray-400">'
                    + escapeHtml(labels.empty) + '</td></tr>';
                els.all.checked = false;
                updateCounters();
                return;
            }

            var html = rows.map(function (r) {
                var checked = isSelected(r.id) ? 'checked' : '';
                var img = imageCell(r.image);
                var dash    = '<span class="text-gray-300">—</span>';
                var cost    = (r.cost && Number(r.cost) > 0) ? money(r.cost) : dash;
                var buyAt   = r.buy_at ? escapeHtml(String(r.buy_at).substring(0, 10)) : dash;
                var waste   = (r.waste_count && Number(r.waste_count) > 0) ? r.waste_count : dash;
                var sale    = (r.sale_count && Number(r.sale_count) > 0) ? r.sale_count : dash;
                var pending = (r.pending_qty && Number(r.pending_qty) > 0)
                    ? '<span class="reorder-chip" style="background:#fef3c7;color:#92400e">' + r.pending_qty + '</span>'
                    : dash;
                // Line 1: regular product name (or SKU fallback). Line 2:
                // original_name if non-empty, otherwise NOTHING (per spec —
                // do not show SKU here).
                var nameCell = escapeHtml(r.name || r.sku);
                if (r.original_name) {
                    nameCell += '<div class="reorder-orig-name">' + escapeHtml(r.original_name) + '</div>';
                }
                // data-label="..." is consumed by the mobile-only CSS to
                // prefix each stacked cell. Desktop CSS ignores it.
                return '<tr data-id="' + r.id + '">'
                    + '<td><input type="checkbox" data-pick="' + r.id + '" ' + checked + '/></td>'
                    + '<td>' + img + '</td>'
                    + '<td data-label="Name">' + nameCell + '</td>'
                    + '<td data-label="Stock">' + r.stock + '</td>'
                    + '<td data-label="Pending">' + pending + '</td>'
                    + '<td data-label="Buy at" class="text-xs">' + buyAt + '</td>'
                    + '<td data-label="Cost">' + cost + '</td>'
                    + '<td data-label="Sale">' + sale + '</td>'
                    + '<td data-label="Waste">' + waste + '</td>'
                    + '<td data-label="Suggested"><span class="reorder-chip">' + r.suggested_qty + '</span></td>'
                    + '</tr>';
            }).join('');
            els.rows.innerHTML = html;
            els.all.checked = rows.length > 0 && rows.every(function (r) { return isSelected(r.id); });
            updateCounters();
        }

        // ───────── clipboard render (now mirrors grid: image|stock|pending) ─────────
        function renderClipboard() {
            var rows = sortedClipRows();
            paintArrows(els.clipTable, state.sortClip);

            if (!rows.length) {
                els.clip.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-gray-400">'
                    + escapeHtml(labels.clipEmpty) + '</td></tr>';
                els.clipFoot.innerHTML = '';
            } else {
                els.clip.innerHTML = rows.map(function (c) {
                    // Find this row's index in the canonical state.clipboard
                    // so input handlers stay correct under sort.
                    var i = state.clipboard.indexOf(c);
                    var sel  = state.clipSelected[i] ? 'checked' : '';
                    var img  = imageCell(c.image, 'reorder-clip-img');
                    var pending = (c.pending_qty && Number(c.pending_qty) > 0)
                        ? '<span class="reorder-chip" style="background:#fef3c7;color:#92400e">' + c.pending_qty + '</span>'
                        : '<span class="text-gray-300">—</span>';
                    // Clipboard shows original_name when present, otherwise name.
                    var nameCell = escapeHtml(c.original_name || c.name);
                    return '<tr>'
                        + '<td><input type="checkbox" data-clipsel="' + i + '" ' + sel + '/></td>'
                        + '<td>' + img + '</td>'
                        + '<td>' + nameCell + '</td>'
                        + '<td>' + (c.stock != null ? c.stock : '—') + '</td>'
                        + '<td>' + pending + '</td>'
                        + '<td><input type="number" min="1" value="' + c.wish_qty
                            + '" data-clipqty="' + i + '" class="w-16 px-1 border rounded"/></td>'
                        + '<td>' + money(c.wish_qty * c.cost) + '</td>'
                        + '<td><button class="text-red-500" data-clipdel="' + i + '">×</button></td>'
                        + '</tr>';
                }).join('');
                var total = state.clipboard.reduce(function (s, c) { return s + c.wish_qty * c.cost; }, 0);
                els.clipFoot.innerHTML = '<tr>'
                    + '<td colspan="6" class="font-bold text-right">' + escapeHtml(labels.total) + '</td>'
                    + '<td colspan="2" class="font-bold">' + money(total) + '</td>'
                    + '</tr>';
            }
            els.save.disabled  = !state.clipboard.length || state.saving;
            // Don't replace innerHTML — that would wipe #reorder-save-count.
            // Show the saving state via a class swap instead.
            els.save.classList.toggle('opacity-60', state.saving);
            els.print.disabled = !state.savedIds || !state.savedIds.length;

            updateCounters();

            if (els.clipAllH) {
                var n = state.clipboard.length;
                var sel = Object.keys(state.clipSelected).filter(function (k) { return state.clipSelected[k]; }).length;
                els.clipAllH.checked = n > 0 && sel === n;
            }
        }

        // ───────── data load ─────────
        function load() {
            var params = 'q=' + encodeURIComponent(state.search)
                       + '&sort_buy_at=' + state.serverSortBuyAt;
            if (state.channel) { params += '&channel=' + encodeURIComponent(state.channel); }
            if (state.source)  { params += '&source='  + encodeURIComponent(state.source); }
            fetch(urls.products + '?' + params, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    state.rows = (j && j.data) || [];
                    renderRows();
                    applyPreselect();
                });
        }

        var preselectApplied = false;
        function applyPreselect() {
            if (preselectApplied || !preselect.length) return;
            preselectApplied = true;
            var byId = {};
            state.rows.forEach(function (r) { byId[r.id] = r; });
            preselect.forEach(function (id) {
                var r = byId[id];
                if (!r || isSelected(id)) return;
                addToClipboard(r);
            });
            if (state.clipboard.length) {
                renderClipboard();
                renderRows();
                showToast('Loaded ' + state.clipboard.length + ' from clipboard');
            }
        }

        // Direct insert — no modal. Default qty = suggested or 1.
        function addToClipboard(row) {
            state.clipboard.push({
                product_id:    row.id,
                name:          row.name || row.sku,
                original_name: row.original_name || null,
                sku:           row.sku || '',
                image:         row.image || null,
                wish_qty:      Math.max(1, row.suggested_qty || 1),
                cost:          Number(row.cost) || 0,
                stock:         row.stock,
                pending_qty:   row.pending_qty,
                sale_count:    row.sale_count,
                suggested_qty: row.suggested_qty,
            });
        }

        function pickRow(id) {
            var row = state.rows.find(function (r) { return r.id === id; });
            if (!row) { return; }
            if (isSelected(id)) {
                state.clipboard = state.clipboard.filter(function (c) { return c.product_id !== id; });
            } else {
                addToClipboard(row);
            }
            renderClipboard();
            renderRows();
        }

        function toggleAll(checked) {
            if (checked) {
                state.rows.forEach(function (r) {
                    if (!isSelected(r.id)) addToClipboard(r);
                });
            } else {
                state.clipboard = [];
                state.clipSelected = {};
            }
            renderClipboard();
            renderRows();
        }

        // ───────── save batch ─────────
        function save() {
            if (!state.clipboard.length || state.saving) { return; }
            state.saving = true;
            renderClipboard();

            var items = state.clipboard.map(function (c) {
                return { product_id: c.product_id, qty: c.wish_qty, cost: c.cost, name: c.name };
            });

            fetch(urls.batchesStore, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ items: items }),
            })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
            .then(function (res) {
                state.saving = false;
                if (res.ok && res.body && res.body.ok) {
                    state.pendingBatch = res.body.batch;
                    showToast('Batch #' + res.body.batch.id + ' saved');
                    if (els.batchBar) {
                        els.batchBar.classList.remove('hidden');
                        els.batchId.textContent = '#' + res.body.batch.id;
                        if (els.receive) {
                            els.receive.setAttribute('href',
                                urls.arriveBase + '/batches/' + res.body.batch.id + '/receive');
                        }
                    }
                } else {
                    showToast((res.body && res.body.message) || 'Save failed', 'error');
                }
                renderClipboard();
            })
            .catch(function (e) {
                state.saving = false;
                showToast('Save failed: ' + e.message, 'error');
                renderClipboard();
            });
        }

        function printLabels() {
            if (!state.savedIds || !state.savedIds.length) { return; }
            var qs = state.savedIds.map(function (id) { return 'ids[]=' + id; }).join('&');
            window.open(urls.labels + '?' + qs, '_blank');
        }

        // ───────── event wiring ─────────
        els.search.addEventListener('input', function (e) {
            state.search = e.target.value;
            clearTimeout(state._t);
            state._t = setTimeout(load, 250);
        });
        if (els.channel) {
            els.channel.addEventListener('change', function (e) { state.channel = e.target.value; load(); });
        }
        if (els.source) {
            els.source.addEventListener('change', function (e) { state.source = e.target.value; load(); });
        }
        els.all.addEventListener('change', function (e) { toggleAll(e.target.checked); });

        els.rows.addEventListener('click', function (e) {
            var zoomUrl = e.target.getAttribute('data-zoom');
            if (zoomUrl) {
                els.zoom.querySelector('img').src = zoomUrl;
                els.zoom.style.display = 'flex';
            }
        });
        els.rows.addEventListener('change', function (e) {
            var pick = e.target.getAttribute('data-pick');
            if (pick) { pickRow(parseInt(pick, 10)); }
        });

        els.clip.addEventListener('click', function (e) {
            var del = e.target.getAttribute('data-clipdel');
            if (del !== null) {
                var i = parseInt(del, 10);
                state.clipboard.splice(i, 1);
                // Re-key clipSelected after splice
                var next = {};
                state.clipboard.forEach(function (_, k) {
                    if (state.clipSelected[k >= i ? k + 1 : k]) next[k] = true;
                });
                state.clipSelected = next;
                renderClipboard();
                renderRows();
            }
        });
        els.clip.addEventListener('input', function (e) {
            var idx = e.target.getAttribute('data-clipqty');
            if (idx !== null) {
                var v = parseInt(e.target.value, 10);
                state.clipboard[idx].wish_qty = v > 0 ? v : 1;
                renderClipboard();
            }
        });
        els.clip.addEventListener('change', function (e) {
            var sel = e.target.getAttribute('data-clipsel');
            if (sel !== null) {
                var i = parseInt(sel, 10);
                if (e.target.checked) state.clipSelected[i] = true;
                else delete state.clipSelected[i];
                renderClipboard();
            }
        });

        if (els.clipAllH) {
            els.clipAllH.addEventListener('change', function (e) {
                state.clipSelected = {};
                if (e.target.checked) {
                    state.clipboard.forEach(function (_, i) { state.clipSelected[i] = true; });
                }
                renderClipboard();
            });
        }

        if (els.bulkFill) {
            els.bulkFill.addEventListener('click', function () {
                var v = parseInt(els.bulkQty.value, 10);
                if (!(v > 0)) { showToast('Enter qty first', 'warn'); return; }
                var selected = Object.keys(state.clipSelected)
                    .filter(function (k) { return state.clipSelected[k]; })
                    .map(function (k) { return parseInt(k, 10); });
                var targets = selected.length
                    ? selected
                    : state.clipboard.map(function (_, i) { return i; });
                targets.forEach(function (i) {
                    if (state.clipboard[i]) state.clipboard[i].wish_qty = v;
                });
                renderClipboard();
                showToast('Set qty=' + v + ' for ' + targets.length + ' items');
            });
        }

        els.zoom.addEventListener('click', function () { els.zoom.style.display = 'none'; });
        els.save.addEventListener('click', save);
        els.print.addEventListener('click', printLabels);

        bindSort(els.gridTable, state.sortGrid, renderRows);
        bindSort(els.clipTable, state.sortClip, renderClipboard);

        wireSplitter();
        wireMobileBar();
        wireLayoutSwap();

        renderClipboard();
        load();
    }

    // ───────── desktop splitter drag ─────────
    // Saves to localStorage so the user's chosen width survives reload.
    var SPLIT_LS_KEY = 'reorder_clip_w_px';
    function wireSplitter() {
        var rootEl = document.getElementById('rg-root');
        var splitter = document.getElementById('rg-splitter');
        if (!rootEl || !splitter) return;

        // Restore saved width
        var saved = parseInt(localStorage.getItem(SPLIT_LS_KEY) || '0', 10);
        if (saved >= 280 && saved <= 1200) {
            rootEl.style.setProperty('--reorder-clip-w', saved + 'px');
        }

        var dragging = false, startX = 0, startW = 0, rootW = 0;

        splitter.addEventListener('mousedown', function (e) {
            // Only allow drag on desktop layouts (the splitter is hidden < 1024px).
            if (window.matchMedia('(max-width: 1023px)').matches) return;
            dragging = true; splitter.classList.add('dragging');
            startX = e.clientX;
            var clipEl = document.getElementById('rg-clip');
            startW = clipEl ? clipEl.getBoundingClientRect().width : 420;
            rootW  = rootEl.getBoundingClientRect().width;
            document.body.style.userSelect = 'none';
            e.preventDefault();
        });

        document.addEventListener('mousemove', function (e) {
            if (!dragging) return;
            // Drag-LEFT widens the clipboard; drag-RIGHT shrinks it.
            var w = startW - (e.clientX - startX);
            // Clamp so neither side gets squeezed past usability.
            var minW = 280, maxW = Math.max(minW + 100, rootW - 360);
            if (w < minW) w = minW; if (w > maxW) w = maxW;
            rootEl.style.setProperty('--reorder-clip-w', w + 'px');
        });

        document.addEventListener('mouseup', function () {
            if (!dragging) return;
            dragging = false; splitter.classList.remove('dragging');
            document.body.style.userSelect = '';
            var w = parseInt(getComputedStyle(rootEl).getPropertyValue('--reorder-clip-w'), 10);
            if (w > 0) localStorage.setItem(SPLIT_LS_KEY, String(w));
        });
    }

    // ───────── mobile bottom-sheet clipboard ─────────
    // Layout swap: clipboard left ↔ right. Persisted to localStorage so the
    // user's preference survives reload. CSS does the actual move via the
    // .rg-swapped class on #rg-root (using flex `order` on the three children).
    var SWAP_LS_KEY = 'reorder_layout_swapped';
    function wireLayoutSwap() {
        var rootEl = document.getElementById('rg-root');
        var btn    = document.getElementById('rg-swap');
        var icon   = document.getElementById('rg-swap-icon');
        if (!rootEl || !btn) return;

        function paint() {
            var swapped = rootEl.classList.contains('rg-swapped');
            // Arrow points the way the panes will move on click.
            if (icon) icon.textContent = swapped ? '⇆' : '⇄';
            btn.setAttribute('aria-pressed', swapped ? 'true' : 'false');
        }

        if (localStorage.getItem(SWAP_LS_KEY) === '1') {
            rootEl.classList.add('rg-swapped');
        }
        paint();

        btn.addEventListener('click', function () {
            var swapped = rootEl.classList.toggle('rg-swapped');
            localStorage.setItem(SWAP_LS_KEY, swapped ? '1' : '0');
            paint();
        });
    }

    function wireMobileBar() {
        var clip   = document.getElementById('rg-clip');
        var handle = document.getElementById('rg-clip-handle');
        var arrow  = document.getElementById('rg-clip-handle-arrow');
        if (!clip || !handle) return;

        handle.addEventListener('click', function () {
            var open = clip.classList.toggle('rg-clip-open');
            if (arrow) arrow.textContent = open ? '▼' : '▲';
        });
        // The mini-bar count itself is now kept in sync by updateCounters(),
        // so no MutationObserver is needed here.
    }

    // Boot after window.load so Vue's app.mount('#app') has completed.
    if (document.readyState === 'complete') {
        setTimeout(boot, 0);
    } else {
        window.addEventListener('load', function () { setTimeout(boot, 0); });
    }
})();
</script>

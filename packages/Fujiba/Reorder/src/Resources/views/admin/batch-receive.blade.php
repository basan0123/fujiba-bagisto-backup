<x-admin::layouts>
    <x-slot:title>Receive Batch #{{ $batch->id }}</x-slot>

    @push('styles')
        <style>
            .br-table { width: 100%; border-collapse: collapse; background: #fff; }
            .br-table th, .br-table td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; font-size: 13px; vertical-align: middle; }
            .br-table thead th { background: #f3f4f6; text-align: left; cursor: pointer; user-select: none; font-weight: 600; position: sticky; top: 0; z-index: 5; }
            .br-img { width: 38px; height: 38px; object-fit: cover; border-radius: 4px; background: #f3f4f6; }
            .br-pill { padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
            .br-pill.draft     { background: #f3f4f6; color: #4b5563; }
            .br-pill.pending   { background: #fef3c7; color: #92400e; }
            .br-pill.receiving { background: #dbeafe; color: #1e40af; }
            .br-pill.received  { background: #dcfce7; color: #166534; }
            .br-num input { width: 64px; text-align: right; padding: 2px 4px; border: 1px solid #d1d5db; border-radius: 4px; font-variant-numeric: tabular-nums; }
            .br-tostock   { font-weight: 700; color: #166534; font-variant-numeric: tabular-nums; }
            .br-tostock.diff { color: #b45309; }
        </style>
    @endpush

    <div class="mb-4 flex items-center justify-between gap-3">
        <div>
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                Receive Batch #{{ $batch->id }}
                <span class="br-pill {{ $batch->status }}">{{ $batch->status }}</span>
            </p>
            <p class="text-xs text-gray-500">Created {{ $batch->created_at?->format('Y-m-d H:i') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.inventory.reorder.history') }}"
               class="px-3 py-2 text-sm bg-gray-100 rounded">Back to history</a>
            @if ($batch->status !== 'received')
                <button id="br-save"  class="px-3 py-2 text-sm bg-amber-500 text-white rounded">Save draft</button>
                <button id="br-confirm" class="px-3 py-2 text-sm bg-green-600 text-white rounded">Confirm &amp; Apply Stock</button>
            @endif
        </div>
    </div>

    <div id="br-app" v-pre
         data-id="{{ $batch->id }}"
         data-status="{{ $batch->status }}"
         data-items='@json($items)'
         data-start-url="{{ route('admin.inventory.reorder.batches.start-receive', $batch->id) }}"
         data-draft-url="{{ route('admin.inventory.reorder.batches.receive-draft', $batch->id) }}"
         data-confirm-url="{{ route('admin.inventory.reorder.batches.confirm-receive', $batch->id) }}"
         data-csrf="{{ $csrf }}"
         class="rounded border border-gray-200 bg-white shadow-sm overflow-auto"
         style="max-height: calc(100vh - 380px);">
        <table class="br-table">
            <thead>
                <tr>
                    <th data-key="image"></th>
                    <th data-key="name">Name</th>
                    <th data-key="current_stock" style="text-align:right">Stock now</th>
                    <th data-key="qty" style="text-align:right">Ordered</th>
                    <th data-key="delivered_qty" style="text-align:right">Delivered</th>
                    <th data-key="damage_qty" style="text-align:right">Damage</th>
                    <th data-key="loss_qty" style="text-align:right">Loss</th>
                    <th data-key="cut_off_qty" style="text-align:right">Cut-off</th>
                    <th data-key="to_stock_qty" style="text-align:right">→ Stock</th>
                    <th data-key="new_stock" style="text-align:right">New stock</th>
                </tr>
            </thead>
            <tbody id="br-body"></tbody>
        </table>
    </div>

    <h3 class="mt-6 mb-2 text-sm font-bold text-gray-700">Older received batches</h3>
    <div class="rounded border border-gray-200 bg-white overflow-auto" style="max-height: 220px;">
        <table class="br-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th style="text-align:right">Items</th>
                    <th>Note</th>
                    <th>Received</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($history as $h)
                    <tr>
                        <td>#{{ $h->id }}</td>
                        <td style="text-align:right">{{ $h->items_count }}</td>
                        <td>{{ $h->note ?: '—' }}</td>
                        <td>{{ $h->received_at?->format('Y-m-d H:i') }}</td>
                        <td><a href="{{ route('admin.inventory.reorder.batches.receive-screen', $h->id) }}"
                               class="text-blue-600 hover:underline">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-gray-400 py-6">No prior receives</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="br-toast" class="fixed bottom-4 right-4 z-50 hidden rounded px-4 py-2 text-white text-sm shadow-xl"></div>

    @push('scripts')
        <script>
        (function () {
            var root = document.getElementById('br-app');
            if (!root) return;

            var status = root.dataset.status;
            var readonly = (status === 'received');
            var urls = {
                start:   root.dataset.startUrl,
                draft:   root.dataset.draftUrl,
                confirm: root.dataset.confirmUrl,
            };
            var csrf = root.dataset.csrf;
            var items = [];
            try { items = JSON.parse(root.dataset.items || '[]'); } catch (_) {}

            // If user has not entered delivered yet, default to ordered qty so
            // the common "everything arrived as ordered" path is one click.
            items.forEach(function (it) {
                if (!readonly && (it.delivered_qty == null || it.delivered_qty === 0)
                    && it.damage_qty === 0 && it.loss_qty === 0 && it.cut_off_qty === 0
                    && it.to_stock_qty === 0) {
                    it.delivered_qty = it.qty;
                    it.to_stock_qty  = it.qty;
                }
            });

            var sortKey = null, sortDir = 'asc';

            function esc(s) {
                return String(s == null ? '' : s)
                    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                    .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
            }

            function toast(msg, kind) {
                var t = document.getElementById('br-toast');
                var color = kind === 'error' ? 'bg-red-600' : kind === 'warn' ? 'bg-amber-600' : 'bg-green-600';
                t.textContent = msg;
                t.className = 'fixed bottom-4 right-4 z-50 rounded px-4 py-2 text-white text-sm shadow-xl ' + color;
                setTimeout(function () { t.classList.add('hidden'); }, 2500);
            }

            function defaultToStock(it) {
                return Math.max(0,
                    (parseInt(it.delivered_qty, 10) || 0)
                    - (parseInt(it.damage_qty, 10) || 0)
                    - (parseInt(it.loss_qty, 10) || 0)
                    - (parseInt(it.cut_off_qty, 10) || 0)
                );
            }

            function recalc(it) {
                if (!it._toStockOverridden) {
                    it.to_stock_qty = defaultToStock(it);
                }
            }

            function sortItems() {
                if (!sortKey) return;
                var dir = sortDir === 'asc' ? 1 : -1;
                items.sort(function (a, b) {
                    var av = sortKey === 'new_stock'
                        ? (parseInt(a.current_stock, 10) || 0) + (parseInt(a.to_stock_qty, 10) || 0)
                        : a[sortKey];
                    var bv = sortKey === 'new_stock'
                        ? (parseInt(b.current_stock, 10) || 0) + (parseInt(b.to_stock_qty, 10) || 0)
                        : b[sortKey];
                    if (typeof av === 'number' && typeof bv === 'number') return (av - bv) * dir;
                    av = (av == null ? '' : String(av)).toLowerCase();
                    bv = (bv == null ? '' : String(bv)).toLowerCase();
                    return av < bv ? -dir : av > bv ? dir : 0;
                });
            }

            function num(it, field) {
                if (readonly) return esc(String(it[field] || 0));
                return '<input type="number" min="0" value="' + (it[field] || 0)
                    + '" data-i="' + it._idx + '" data-f="' + field + '"/>';
            }

            function render() {
                items.forEach(function (it, i) { it._idx = i; recalc(it); });
                sortItems();
                var body = document.getElementById('br-body');
                if (!items.length) {
                    body.innerHTML = '<tr><td colspan="10" class="text-center py-8 text-gray-400">No items</td></tr>';
                    return;
                }
                body.innerHTML = items.map(function (it) {
                    var img = it.image
                        ? '<img class="br-img" src="' + esc(it.image) + '" onerror="this.style.display=\'none\'"/>'
                        : '<div class="br-img"></div>';
                    var def = defaultToStock(it);
                    var ts  = parseInt(it.to_stock_qty, 10) || 0;
                    var diff = ts !== def ? ' diff' : '';
                    var tsCell;
                    if (readonly) {
                        tsCell = '<span class="br-tostock' + diff + '">' + ts + '</span>';
                    } else {
                        tsCell = '<input class="br-tostock' + diff + '" type="number" min="0" value="' + ts
                               + '" data-i="' + it._idx + '" data-f="to_stock_qty" data-override="1"'
                               + ' style="width:80px;text-align:right;padding:2px 4px;border:1px solid #d1d5db;border-radius:4px;font-weight:700;"/>';
                    }
                    var newStock = (parseInt(it.current_stock, 10) || 0) + ts;
                    return '<tr>'
                        + '<td>' + img + '</td>'
                        + '<td>' + esc(it.name || it.sku) + '<div class="text-xs text-gray-400">' + esc(it.sku || '') + '</div></td>'
                        + '<td style="text-align:right">' + (it.current_stock || 0) + '</td>'
                        + '<td style="text-align:right">' + (it.qty || 0) + '</td>'
                        + '<td class="br-num" style="text-align:right">' + num(it, 'delivered_qty') + '</td>'
                        + '<td class="br-num" style="text-align:right">' + num(it, 'damage_qty')    + '</td>'
                        + '<td class="br-num" style="text-align:right">' + num(it, 'loss_qty')      + '</td>'
                        + '<td class="br-num" style="text-align:right">' + num(it, 'cut_off_qty')   + '</td>'
                        + '<td style="text-align:right">' + tsCell + '</td>'
                        + '<td style="text-align:right;font-weight:600">' + newStock + '</td>'
                        + '</tr>';
                }).join('');
            }

            function payload() {
                return {
                    items: items.map(function (it) {
                        return {
                            id:            it.id,
                            delivered_qty: parseInt(it.delivered_qty, 10) || 0,
                            damage_qty:    parseInt(it.damage_qty,    10) || 0,
                            loss_qty:      parseInt(it.loss_qty,      10) || 0,
                            cut_off_qty:   parseInt(it.cut_off_qty,   10) || 0,
                            to_stock_qty:  parseInt(it.to_stock_qty,  10) || 0,
                        };
                    })
                };
            }

            function send(url, success, failMsg) {
                fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload()),
                })
                .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
                .then(function (res) {
                    if (res.ok && res.body.ok) success(res.body);
                    else toast(res.body.message || failMsg, 'error');
                })
                .catch(function (e) { toast(failMsg + ': ' + e.message, 'error'); });
            }

            document.querySelectorAll('#br-app thead th[data-key]').forEach(function (th) {
                th.addEventListener('click', function () {
                    var k = th.dataset.key;
                    if (sortKey === k) sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                    else { sortKey = k; sortDir = 'asc'; }
                    render();
                });
            });

            document.getElementById('br-body').addEventListener('input', function (e) {
                var i = e.target.getAttribute('data-i');
                var f = e.target.getAttribute('data-f');
                if (i == null || f == null) return;
                var v = parseInt(e.target.value, 10); if (isNaN(v) || v < 0) v = 0;
                items[i][f] = v;
                if (f === 'to_stock_qty') {
                    items[i]._toStockOverridden = true;
                } else {
                    // user changed one of the four → recompute to_stock unless they overrode it.
                    if (!items[i]._toStockOverridden) items[i].to_stock_qty = defaultToStock(items[i]);
                }
                render();
            });

            var saveBtn = document.getElementById('br-save');
            if (saveBtn) saveBtn.addEventListener('click', function () {
                send(urls.draft, function () { toast('Draft saved'); }, 'Save failed');
            });

            var confirmBtn = document.getElementById('br-confirm');
            if (confirmBtn) confirmBtn.addEventListener('click', function () {
                var total = items.reduce(function (a, it) { return a + (parseInt(it.to_stock_qty, 10) || 0); }, 0);
                if (!confirm('Apply ' + total + ' units across ' + items.length + ' items to stock? This cannot be undone.')) return;
                send(urls.confirm, function () {
                    toast('Stock applied. Batch closed.');
                    setTimeout(function () { window.location.href = '{{ route('admin.inventory.reorder.history') }}'; }, 800);
                }, 'Confirm failed');
            });

            // Lock the batch into 'receiving' status the moment the user lands here
            // so concurrent admins can't both confirm. Idempotent.
            if (status === 'pending' || status === 'draft') {
                fetch(urls.start, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
            }

            if (document.readyState === 'complete') setTimeout(render, 0);
            else window.addEventListener('load', function () { setTimeout(render, 0); });
        })();
        </script>
    @endpush
</x-admin::layouts>

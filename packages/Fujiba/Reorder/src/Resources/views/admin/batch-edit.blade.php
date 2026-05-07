<x-admin::layouts>
    <x-slot:title>Batch #{{ $batch->id }}</x-slot>

    @push('styles')
        <style>
            .be-table { width: 100%; border-collapse: collapse; background: #fff; }
            .be-table th, .be-table td { padding: 6px 10px; border-bottom: 1px solid #e5e7eb; font-size: 13px; }
            .be-table thead th { background: #f3f4f6; text-align: left; cursor: pointer; user-select: none; font-weight: 600; position: sticky; top: 0; z-index: 5; }
            .be-img { width: 38px; height: 38px; object-fit: cover; border-radius: 4px; background: #f3f4f6; }
            .be-pill { padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
            .be-pill.draft     { background: #f3f4f6; color: #4b5563; }
            .be-pill.pending   { background: #fef3c7; color: #92400e; }
            .be-pill.receiving { background: #dbeafe; color: #1e40af; }
            .be-pill.received  { background: #dcfce7; color: #166534; }
        </style>
    @endpush

    <div class="mb-4 flex items-center justify-between gap-3">
        <div>
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                Batch #{{ $batch->id }}
                <span class="be-pill {{ $batch->status }}">{{ $batch->status }}</span>
            </p>
            <p class="text-xs text-gray-500">Created {{ $batch->created_at?->format('Y-m-d H:i') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.inventory.reorder.history') }}"
               class="px-3 py-2 text-sm bg-gray-100 rounded">Back</a>
            @if (in_array($batch->status, ['draft', 'pending']))
                <button id="be-save"
                    class="px-3 py-2 text-sm bg-blue-600 text-white rounded">Save</button>
                <a href="{{ route('admin.inventory.reorder.batches.receive-screen', $batch->id) }}"
                   class="px-3 py-2 text-sm bg-green-600 text-white rounded">Start Receiving</a>
            @elseif ($batch->status === 'receiving')
                <a href="{{ route('admin.inventory.reorder.batches.receive-screen', $batch->id) }}"
                   class="px-3 py-2 text-sm bg-green-600 text-white rounded">Continue Receiving</a>
            @endif
        </div>
    </div>

    <div id="be-app" v-pre
         data-id="{{ $batch->id }}"
         data-status="{{ $batch->status }}"
         data-items='@json($items)'
         data-update-url="{{ route('admin.inventory.reorder.batches.update', $batch->id) }}"
         data-csrf="{{ $csrf }}"
         class="rounded border border-gray-200 bg-white shadow-sm overflow-auto"
         style="max-height: calc(100vh - 240px);">
        <table class="be-table">
            <thead>
                <tr>
                    <th data-key="image"></th>
                    <th data-key="name">Name</th>
                    <th data-key="sku">SKU</th>
                    <th data-key="current_stock" style="text-align:right">Current stock</th>
                    <th data-key="qty" style="text-align:right">Qty</th>
                    <th data-key="cost" style="text-align:right">Cost</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="be-body"></tbody>
        </table>
    </div>

    <div id="be-toast" class="fixed bottom-4 right-4 z-50 hidden rounded px-4 py-2 text-white text-sm shadow-xl"></div>

    @push('scripts')
        <script>
        (function () {
            var root = document.getElementById('be-app');
            if (!root) return;

            var status   = root.dataset.status;
            var readonly = !(status === 'draft' || status === 'pending');
            var url      = root.dataset.updateUrl;
            var csrf     = root.dataset.csrf;
            var items    = [];
            try { items = JSON.parse(root.dataset.items || '[]'); } catch (_) {}

            var sortKey = null, sortDir = 'asc';

            function esc(s) {
                return String(s == null ? '' : s)
                    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                    .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
            }

            function toast(msg, kind) {
                var t = document.getElementById('be-toast');
                var color = kind === 'error' ? 'bg-red-600' : kind === 'warn' ? 'bg-amber-600' : 'bg-green-600';
                t.textContent = msg;
                t.className = 'fixed bottom-4 right-4 z-50 rounded px-4 py-2 text-white text-sm shadow-xl ' + color;
                setTimeout(function () { t.classList.add('hidden'); }, 2500);
            }

            function sortItems() {
                if (!sortKey) return;
                var dir = sortDir === 'asc' ? 1 : -1;
                items.sort(function (a, b) {
                    var av = a[sortKey], bv = b[sortKey];
                    if (typeof av === 'number' && typeof bv === 'number') return (av - bv) * dir;
                    av = (av == null ? '' : String(av)).toLowerCase();
                    bv = (bv == null ? '' : String(bv)).toLowerCase();
                    return av < bv ? -dir : av > bv ? dir : 0;
                });
            }

            function render() {
                sortItems();
                var body = document.getElementById('be-body');
                if (!items.length) {
                    body.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-gray-400">No items</td></tr>';
                    return;
                }
                body.innerHTML = items.map(function (it, i) {
                    var img = it.image
                        ? '<img class="be-img" src="' + esc(it.image) + '" onerror="this.style.display=\'none\'"/>'
                        : '<div class="be-img"></div>';
                    var qty = readonly
                        ? esc(String(it.qty))
                        : '<input type="number" min="1" value="' + it.qty + '" data-i="' + i + '" data-f="qty" class="w-20 px-1 py-0.5 border rounded text-right"/>';
                    var cost = readonly
                        ? esc(String(it.cost))
                        : '<input type="number" min="0" step="0.01" value="' + it.cost + '" data-i="' + i + '" data-f="cost" class="w-24 px-1 py-0.5 border rounded text-right"/>';
                    var rm = readonly ? '' :
                        '<button class="text-red-500" data-rm="' + i + '" title="Remove">×</button>';
                    return '<tr>'
                        + '<td>' + img + '</td>'
                        + '<td>' + esc(it.name || it.sku) + '<div class="text-xs text-gray-400">' + esc(it.sku || '') + '</div></td>'
                        + '<td>' + esc(it.sku || '') + '</td>'
                        + '<td style="text-align:right">' + it.current_stock + '</td>'
                        + '<td style="text-align:right">' + qty + '</td>'
                        + '<td style="text-align:right">' + cost + '</td>'
                        + '<td style="text-align:right">' + rm + '</td>'
                        + '</tr>';
                }).join('');
            }

            document.querySelectorAll('#be-app thead th[data-key]').forEach(function (th) {
                th.addEventListener('click', function () {
                    var k = th.dataset.key;
                    if (sortKey === k) sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                    else { sortKey = k; sortDir = 'asc'; }
                    render();
                });
            });

            document.getElementById('be-body').addEventListener('input', function (e) {
                var i = e.target.getAttribute('data-i');
                var f = e.target.getAttribute('data-f');
                if (i == null || f == null) return;
                var v = parseFloat(e.target.value);
                items[i][f] = isNaN(v) ? 0 : v;
            });
            document.getElementById('be-body').addEventListener('click', function (e) {
                var i = e.target.getAttribute('data-rm');
                if (i == null) return;
                items.splice(parseInt(i, 10), 1);
                render();
            });

            var saveBtn = document.getElementById('be-save');
            if (saveBtn) saveBtn.addEventListener('click', function () {
                if (!items.length) { toast('Add at least one item', 'warn'); return; }
                fetch(url, {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        items: items.map(function (it) {
                            return {
                                product_id: it.product_id,
                                qty:        Math.max(1, parseInt(it.qty, 10) || 1),
                                cost:       parseFloat(it.cost) || 0,
                                name:       it.name,
                            };
                        }),
                    }),
                })
                .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
                .then(function (res) {
                    if (res.ok && res.body.ok) toast('Saved');
                    else toast(res.body.message || 'Save failed', 'error');
                })
                .catch(function (e) { toast('Save failed: ' + e.message, 'error'); });
            });

            if (document.readyState === 'complete') setTimeout(render, 0);
            else window.addEventListener('load', function () { setTimeout(render, 0); });
        })();
        </script>
    @endpush
</x-admin::layouts>

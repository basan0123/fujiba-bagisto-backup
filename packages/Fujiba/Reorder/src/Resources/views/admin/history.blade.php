<x-admin::layouts>
    <x-slot:title>Reorder History</x-slot>

    @push('styles')
        <style>
            .rh-table { width: 100%; border-collapse: collapse; background: #fff; }
            .rh-table th, .rh-table td { padding: 8px 12px; border-bottom: 1px solid #e5e7eb; font-size: 13px; text-align: left; }
            .rh-table thead th { background: #f3f4f6; cursor: pointer; user-select: none; font-weight: 600; }
            .rh-table thead th .arr { color: #2563eb; font-size: 10px; margin-left: 4px; }
            .rh-pill { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
            .rh-pill.draft     { background: #f3f4f6; color: #4b5563; }
            .rh-pill.pending   { background: #fef3c7; color: #92400e; }
            .rh-pill.receiving { background: #dbeafe; color: #1e40af; }
            .rh-pill.received  { background: #dcfce7; color: #166534; }
        </style>
    @endpush

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="py-1 text-xl font-bold text-gray-800 dark:text-white">Reorder History</p>
        <div class="flex gap-2 text-sm">
            @php $cur = $status ?? 'all'; @endphp
            @foreach (['all' => 'All', 'draft' => 'Draft', 'pending' => 'Pending', 'receiving' => 'Receiving', 'received' => 'Received'] as $key => $label)
                <a href="{{ route('admin.inventory.reorder.history', ['status' => $key]) }}"
                   class="px-3 py-1 rounded {{ $cur === $key ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    {{ $label }}
                </a>
            @endforeach
            <a href="{{ route('admin.inventory.reorder.index') }}"
               class="ml-2 px-3 py-1 rounded bg-green-600 text-white">+ New batch</a>
        </div>
    </div>

    <div id="rh-app" v-pre data-batches='@json($batches)' class="rounded border border-gray-200 bg-white shadow-sm overflow-hidden">
        <table class="rh-table" id="rh-table">
            <thead>
                <tr>
                    <th data-key="id">#<span class="arr"></span></th>
                    <th data-key="status">Status<span class="arr"></span></th>
                    <th data-key="items_count" style="text-align:right">Items<span class="arr"></span></th>
                    <th data-key="note">Note<span class="arr"></span></th>
                    <th data-key="created_at">Created<span class="arr"></span></th>
                    <th data-key="received_at">Received<span class="arr"></span></th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="rh-body">
                <tr><td colspan="7" class="text-center py-6 text-gray-400">…</td></tr>
            </tbody>
        </table>
    </div>

    @push('scripts')
        <script>
        (function () {
            var root = document.getElementById('rh-app');
            if (!root) return;

            var rows = [];
            try { rows = JSON.parse(root.dataset.batches || '[]') || []; } catch (_) { rows = []; }

            var sortKey = 'created_at';
            var sortDir = 'desc';

            function fmt(d) {
                if (!d) return '<span class="text-gray-300">—</span>';
                return String(d).substring(0, 16).replace('T', ' ');
            }
            function esc(s) {
                return String(s == null ? '' : s)
                    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                    .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
            }

            function actions(b) {
                var canEdit    = b.status === 'draft' || b.status === 'pending';
                var canReceive = b.status === 'pending' || b.status === 'receiving';
                var view = '/admin/inventory/reorder/batches/' + b.id + '/edit';
                var rcv  = '/admin/inventory/reorder/batches/' + b.id + '/receive';
                var html = '';
                html += '<a href="' + view + '" class="text-blue-600 hover:underline mr-3">View</a>';
                if (canEdit)    html += '<a href="' + view + '" class="text-amber-600 hover:underline mr-3">Edit</a>';
                if (canReceive) html += '<a href="' + rcv  + '" class="text-green-600 font-semibold hover:underline mr-3">Start Receiving</a>';
                if (b.status === 'received') html += '<a href="' + rcv + '" class="text-gray-500 hover:underline">Detail</a>';
                return html;
            }

            function sortRows() {
                var dir = sortDir === 'asc' ? 1 : -1;
                rows.sort(function (a, b) {
                    var av = a[sortKey], bv = b[sortKey];
                    if (av == null && bv == null) return 0;
                    if (av == null) return 1;
                    if (bv == null) return -1;
                    if (typeof av === 'number' && typeof bv === 'number') return (av - bv) * dir;
                    av = String(av).toLowerCase(); bv = String(bv).toLowerCase();
                    return av < bv ? -dir : av > bv ? dir : 0;
                });
            }

            function render() {
                sortRows();
                var body = document.getElementById('rh-body');
                if (!rows.length) {
                    body.innerHTML = '<tr><td colspan="7" class="text-center py-6 text-gray-400">No batches</td></tr>';
                    return;
                }
                body.innerHTML = rows.map(function (b) {
                    return '<tr>'
                        + '<td>#' + b.id + '</td>'
                        + '<td><span class="rh-pill ' + esc(b.status) + '">' + esc(b.status) + '</span></td>'
                        + '<td style="text-align:right">' + (b.items_count || 0) + '</td>'
                        + '<td>' + (b.note ? esc(b.note) : '<span class="text-gray-300">—</span>') + '</td>'
                        + '<td>' + fmt(b.created_at) + '</td>'
                        + '<td>' + fmt(b.received_at) + '</td>'
                        + '<td>' + actions(b) + '</td>'
                        + '</tr>';
                }).join('');

                document.querySelectorAll('#rh-table thead th').forEach(function (th) {
                    var arr = th.querySelector('.arr');
                    if (!arr) return;
                    if (th.dataset.key === sortKey) arr.textContent = sortDir === 'asc' ? '▲' : '▼';
                    else arr.textContent = '';
                });
            }

            document.querySelectorAll('#rh-table thead th[data-key]').forEach(function (th) {
                th.addEventListener('click', function () {
                    var k = th.dataset.key;
                    if (sortKey === k) sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                    else { sortKey = k; sortDir = 'asc'; }
                    render();
                });
            });

            if (document.readyState === 'complete') setTimeout(render, 0);
            else window.addEventListener('load', function () { setTimeout(render, 0); });
        })();
        </script>
    @endpush
</x-admin::layouts>

{{--
    Per-product reservations modal — shared by list-view, pos-view, mobile-view.

    Renders a modal that fetches GET /admin/catalog/products/{id}/reservations
    and lists which open orders reserved which units.

    Usage:
        @include('admin::catalog.products.partials.reservations-modal')

    Then in any client code:
        pmShowReservations(productId);
--}}

@push('styles')
<style>
.pm-reserved-badge {
    display:inline-flex; align-items:center; justify-content:center;
    min-width:28px; height:22px; padding:0 6px;
    font-size:12px; font-weight:800; border-radius:11px;
    cursor:pointer; user-select:none;
}
.pm-reserved-badge.has  { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }
.pm-reserved-badge.has:hover { background:#ffedd5; }
.pm-reserved-badge.zero { background:transparent; color:#94a3b8; cursor:default; }
.dark .pm-reserved-badge.has { background:#451a03; color:#fdba74; border-color:#7c2d12; }

.pm-reserved-corner {
    position:absolute; top:4px; left:4px; z-index:3;
    background:#c2410c; color:#fff; font-weight:800; font-size:11px;
    padding:2px 7px; border-radius:10px;
    box-shadow:0 1px 4px rgba(0,0,0,.35);
    cursor:pointer;
}
.pm-reserved-corner:hover { background:#9a3412; }

#pm-resv-modal {
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(15,23,42,.55); align-items:center; justify-content:center;
    padding:16px;
}
#pm-resv-modal.open { display:flex; }
#pm-resv-card {
    background:#fff; border-radius:10px; box-shadow:0 20px 50px rgba(0,0,0,.25);
    width:100%; max-width:780px; max-height:90vh; display:flex; flex-direction:column;
}
.dark #pm-resv-card { background:#111827; color:#f3f4f6; }
#pm-resv-head {
    padding:14px 16px; border-bottom:1px solid #e5e7eb;
    display:flex; align-items:center; justify-content:space-between; gap:8px;
}
.dark #pm-resv-head { border-color:#374151; }
#pm-resv-head h3 { margin:0; font-size:15px; font-weight:700; }
#pm-resv-head .meta { font-size:12px; color:#64748b; }
.dark #pm-resv-head .meta { color:#94a3b8; }
#pm-resv-body { flex:1; overflow:auto; padding:0; }
#pm-resv-body table { width:100%; border-collapse:collapse; font-size:13px; }
#pm-resv-body th, #pm-resv-body td {
    padding:8px 10px; text-align:left; border-bottom:1px solid #f1f5f9;
}
.dark #pm-resv-body th, .dark #pm-resv-body td { border-color:#1f2937; }
#pm-resv-body th { background:#f8fafc; font-weight:700; font-size:12px; color:#475569; position:sticky; top:0; }
.dark #pm-resv-body th { background:#0f172a; color:#cbd5e1; }
#pm-resv-body tr:hover td { background:#f0f9ff; }
.dark #pm-resv-body tr:hover td { background:#0c4a6e; }
#pm-resv-body td.num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
#pm-resv-body a.order-link { color:#0e7490; font-weight:700; text-decoration:none; }
#pm-resv-body a.order-link:hover { text-decoration:underline; }
.pm-resv-empty { padding:32px; text-align:center; color:#64748b; font-size:14px; }
.pm-resv-status {
    display:inline-block; padding:1px 8px; border-radius:10px; font-size:11px; font-weight:600;
    background:#e0f2fe; color:#075985;
}
.pm-resv-status.processing { background:#dcfce7; color:#166534; }
.pm-resv-status.pending    { background:#fef3c7; color:#92400e; }
.pm-resv-status.fraud      { background:#fee2e2; color:#991b1b; }
#pm-resv-foot {
    padding:10px 16px; border-top:1px solid #e5e7eb;
    display:flex; justify-content:flex-end; gap:8px;
}
.dark #pm-resv-foot { border-color:#374151; }
.pm-resv-close {
    height:32px; padding:0 14px; font-size:13px; font-weight:600;
    border:1px solid #cbd5e1; border-radius:6px; background:#fff; color:#475569; cursor:pointer;
}
.dark .pm-resv-close { background:#1f2937; color:#cbd5e1; border-color:#4b5563; }
</style>
@endpush

<div id="pm-resv-modal" role="dialog" aria-modal="true" aria-labelledby="pm-resv-title">
    <div id="pm-resv-card">
        <div id="pm-resv-head">
            <div>
                <h3 id="pm-resv-title">予約在庫の内訳</h3>
                <div class="meta" id="pm-resv-meta"></div>
            </div>
            <button class="pm-resv-close" type="button" onclick="pmCloseReservations()">閉じる</button>
        </div>
        <div id="pm-resv-body">
            <div class="pm-resv-empty">読み込み中…</div>
        </div>
        <div id="pm-resv-foot">
            <button class="pm-resv-close" type="button" onclick="pmCloseReservations()">閉じる</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function(){
    if (window.__pmResvBooted) return;
    window.__pmResvBooted = true;

    const RESV_URL = (id) => "{{ url(config('app.admin_url') . '/catalog/products') }}/" + id + "/reservations";
    const ORDER_EDIT = (id) => "{{ url(config('app.admin_url') . '/sales/orders') }}/" + id;

    const escape = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);

    function fmtDate(s) {
        if (!s) return '';
        const d = new Date(String(s).replace(' ', 'T'));
        return isNaN(d) ? s : d.toLocaleString('ja-JP', { year:'numeric', month:'2-digit', day:'2-digit', hour:'2-digit', minute:'2-digit' });
    }

    window.pmShowReservations = async function(productId) {
        const modal = document.getElementById('pm-resv-modal');
        const body  = document.getElementById('pm-resv-body');
        const meta  = document.getElementById('pm-resv-meta');
        if (!modal) return;
        modal.classList.add('open');
        body.innerHTML = '<div class="pm-resv-empty">読み込み中…</div>';
        meta.textContent = `商品 #${productId}`;

        try {
            const res = await fetch(RESV_URL(productId), { headers:{ 'Accept':'application/json' }, credentials:'same-origin' });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            const rows = json.data || [];

            meta.textContent = `商品 #${productId} ・ 予約合計 ${json.reserved_total || 0}（未出荷 ${json.reserved_open || 0}）`;

            if (!rows.length) {
                body.innerHTML = '<div class="pm-resv-empty">該当する未出荷の予約注文はありません。</div>';
                return;
            }

            let html = '<table><thead><tr>'
                + '<th>注文</th><th>顧客</th><th>チャネル</th><th>状態</th>'
                + '<th style="text-align:right;">予約数</th>'
                + '<th style="text-align:right;">注文/出荷/取消</th>'
                + '<th>日時</th></tr></thead><tbody>';

            for (const r of rows) {
                const stClass = String(r.status || '').toLowerCase();
                html += '<tr>'
                    + `<td><a class="order-link" href="${ORDER_EDIT(r.order_id)}" target="_blank" rel="noopener">${escape(r.increment_id || ('#' + r.order_id))}</a></td>`
                    + `<td>${escape(r.customer_name || r.customer_email || '—')}</td>`
                    + `<td>${escape(r.channel_code || r.channel_name || '')}</td>`
                    + `<td><span class="pm-resv-status ${stClass}">${escape(r.status || '')}</span></td>`
                    + `<td class="num">${r.qty_reserved}</td>`
                    + `<td class="num">${r.qty_ordered} / ${r.qty_shipped || 0} / ${r.qty_canceled || 0}</td>`
                    + `<td>${escape(fmtDate(r.created_at))}</td>`
                    + '</tr>';
            }
            html += '</tbody></table>';
            body.innerHTML = html;
        } catch (err) {
            body.innerHTML = `<div class="pm-resv-empty">読み込みに失敗しました: ${escape(err.message)}</div>`;
        }
    };

    window.pmCloseReservations = function() {
        const modal = document.getElementById('pm-resv-modal');
        if (modal) modal.classList.remove('open');
    };

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const m = document.getElementById('pm-resv-modal');
            if (m && m.classList.contains('open')) pmCloseReservations();
        }
    });
    document.addEventListener('click', (e) => {
        if (e.target && e.target.id === 'pm-resv-modal') pmCloseReservations();
    });
})();
</script>
@endpush

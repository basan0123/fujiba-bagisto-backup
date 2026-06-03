{{-- Product List — Tabulator-powered editable grid --}}
<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.products.index.title') — List
    </x-slot>

@php $cargoRateDefault = 500; @endphp

@include('admin::catalog.products.partials.view-nav')
@include('admin::catalog.products.partials.barcode-scanner')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tabulator-tables@6.3.1/dist/css/tabulator_simple.min.css">
<style>
#pm-toolbar {
    display:flex; flex-wrap:wrap; align-items:flex-end; gap:8px;
    padding:8px; margin-bottom:8px;
    background:#fff; border:1px solid #e5e7eb; border-radius:8px;
    box-shadow:0 1px 2px rgba(0,0,0,.04);
}
.dark #pm-toolbar { background:#111827; border-color:#1f2937; }
#pm-toolbar input, #pm-toolbar select {
    height:34px; padding:0 10px; font-size:13px;
    background:#fff; border:1px solid #d1d5db; border-radius:6px; color:#111827;
}
.dark #pm-toolbar input, .dark #pm-toolbar select {
    background:#1f2937; border-color:#4b5563; color:#f3f4f6;
}
#pm-toolbar input:focus, #pm-toolbar select:focus {
    outline:none; border-color:#2563eb; box-shadow:0 0 0 2px rgba(37,99,235,.2);
}
.pm-tb-pill {
    display:flex; gap:12px; padding:4px 10px;
    background:#eff6ff; border:1px solid #bfdbfe; border-radius:6px;
    font-size:11px; line-height:1.2;
}
.dark .pm-tb-pill { background:rgba(30,58,138,.25); border-color:#1e3a8a; }
.pm-tb-pill .pm-tb-k { color:#3b82f6; font-weight:700; display:block; font-size:10px; }
.pm-tb-pill .pm-tb-v { color:#1e3a8a; font-weight:800; }
.dark .pm-tb-pill .pm-tb-v { color:#bfdbfe; }

.pm-btn-primary {
    height:28px; padding:0 12px; border-radius:5px;
    background:#2563eb; color:#fff; font-weight:600; font-size:11px;
    border:1px solid #1d4ed8; cursor:pointer; transition:opacity .12s;
}
.pm-btn-primary:hover { opacity:.9; }
.pm-btn-primary[data-disabled="1"] {
    background:#cbd5e1; border-color:#94a3b8; cursor:not-allowed; opacity:.7;
}
.pm-btn-secondary {
    height:28px; padding:0 10px; border-radius:5px;
    background:#fff; color:#374151; font-weight:600; font-size:11px;
    border:1px solid #d1d5db; cursor:pointer;
}
.dark .pm-btn-secondary { background:#1f2937; color:#e5e7eb; border-color:#4b5563; }
.pm-btn-secondary:hover { background:#f9fafb; }
.dark .pm-btn-secondary:hover { background:#111827; }
.pm-btn-secondary[data-disabled="1"] { opacity:.5; cursor:not-allowed; }

#pm-col-wrap { position:relative; }
#pm-col-menu {
    display:none; position:absolute; right:0; top:38px; z-index:1000;
    width:230px; max-height:60vh; overflow-y:auto;
    background:#fff; border:1px solid #d1d5db; border-radius:8px;
    box-shadow:0 4px 16px rgba(0,0,0,.12); padding:6px;
}
.dark #pm-col-menu { background:#1f2937; border-color:#374151; }
#pm-col-menu.open { display:block; }
#pm-col-menu label {
    display:flex; align-items:center; gap:8px;
    padding:6px 8px; border-radius:4px; font-size:13px; color:#374151; cursor:pointer;
}
.dark #pm-col-menu label { color:#e5e7eb; }
#pm-col-menu label:hover { background:#f3f4f6; }
.dark #pm-col-menu label:hover { background:#111827; }

#pm-table {
    background:#fff; border:1px solid #e5e7eb;
    border-radius:8px; overflow:hidden;
}
.dark #pm-table { background:#111827; border-color:#1f2937; }

.tabulator { font-size:14px; }
.tabulator .tabulator-header { background:#f3f4f6 !important; border-bottom:1.5px solid #9ca3af !important; }
.dark .tabulator .tabulator-header { background:#1f2937 !important; border-bottom-color:#4b5563 !important; }
.tabulator .tabulator-col { background:#f3f4f6 !important; color:#374151; border-right:1px solid #d1d5db !important; font-weight:600; font-size:13px; }
.dark .tabulator .tabulator-col { background:#1f2937 !important; color:#d1d5db; border-right-color:#374151 !important; }
.tabulator .tabulator-col-resize-handle { width:8px !important; }
.tabulator .tabulator-col-resize-handle:hover { background:#3b82f6 !important; }
.tabulator .tabulator-row { min-height:70px; }
.tabulator .tabulator-row.tabulator-row-even { background:#fafbfc; }
.dark .tabulator .tabulator-row.tabulator-row-even { background:#0f172a; }
.tabulator .tabulator-row:hover { background:#eff6ff !important; }
.dark .tabulator .tabulator-row:hover { background:rgba(30,58,138,.18) !important; }
.tabulator .tabulator-cell { padding:4px 6px; border-right:1px solid #e5e7eb; font-variant-numeric:tabular-nums; }
.dark .tabulator .tabulator-cell { border-right-color:#374151; }
.tabulator .tabulator-cell.tabulator-editable { box-shadow:inset 0 0 0 1px #d1d5db; font-weight:600; }
.dark .tabulator .tabulator-cell.tabulator-editable { box-shadow:inset 0 0 0 1px #4b5563; }
.tabulator .tabulator-cell.tabulator-editing { box-shadow:inset 0 0 0 2px #2563eb !important; background:#fff !important; }
.tabulator .tabulator-cell.tabulator-editing input,
.tabulator .tabulator-cell.tabulator-editing select {
    width:100%; height:100%; border:none; outline:none;
    background:transparent; font:inherit; font-weight:600; padding:0 4px; box-sizing:border-box;
}
.tabulator .tabulator-cell.pm-cell-dirty { background:#fef9c3 !important; box-shadow:inset 0 0 0 2px #eab308 !important; }
.tabulator .tabulator-row.pm-row-dirty .tabulator-cell:first-child { box-shadow:inset 3px 0 0 0 #f59e0b; }
.tabulator .tabulator-row.pm-row-inactive .tabulator-cell:first-child { box-shadow:inset 3px 0 0 0 #ef4444; }

.tabulator .tabulator-header-filter input,
.tabulator .tabulator-header-filter select {
    width:100%; height:26px; padding:0 6px; font-size:12px; font-weight:500;
    background:#fff; color:#111827; border:1px solid #d1d5db; border-radius:4px;
}
.dark .tabulator .tabulator-header-filter input,
.dark .tabulator .tabulator-header-filter select { background:#0f172a; color:#f3f4f6; border-color:#4b5563; }
.tabulator .tabulator-header-filter input:focus,
.tabulator .tabulator-header-filter select:focus { outline:none; border-color:#2563eb; box-shadow:0 0 0 2px rgba(37,99,235,.25); }

.pm-stock-active   { background:rgba(34,197,94,.18); }
.pm-stock-zero     { background:rgba(251,191,36,.20); }
.pm-stock-inactive { background:rgba(239,68,68,.18); }
.pm-thumb { width:64px; height:64px; object-fit:cover; border-radius:4px; display:block; background:#f3f4f6; }
.dark .pm-thumb { background:#1f2937; }
.pm-thumb-empty {
    width:64px; height:64px; border-radius:4px; display:flex; align-items:center; justify-content:center;
    background:#f3f4f6; color:#9ca3af; font-size:9px; font-weight:600; letter-spacing:.5px;
}
.dark .pm-thumb-empty { background:#1f2937; color:#6b7280; }

/* ── Status toggle switch ── */
.pm-status-wrap {
    display:flex; align-items:center; gap:8px; height:100%;
}
.pm-status-switch {
    position:relative; display:inline-block;
    width:36px; height:20px; flex-shrink:0; cursor:pointer;
}
.pm-status-switch input {
    position:absolute; opacity:0; width:0; height:0;
}
.pm-status-knob {
    position:absolute; inset:0;
    background:#cbd5e1; border-radius:20px;
    transition:background-color .15s;
}
.pm-status-knob::before {
    content:""; position:absolute;
    width:16px; height:16px; left:2px; top:2px;
    background:#fff; border-radius:50%;
    box-shadow:0 1px 3px rgba(0,0,0,.25);
    transition:transform .15s;
}
.pm-status-switch input:checked + .pm-status-knob          { background:#16a34a; }
.pm-status-switch input:checked + .pm-status-knob::before  { transform:translateX(16px); }
.pm-status-text {
    font-size:11px; font-weight:700; letter-spacing:.02em;
    color:#6b7280; user-select:none;
}
.pm-status-text.pm-on  { color:#16a34a; }
.pm-status-text.pm-off { color:#9ca3af; }
.dark .pm-status-knob { background:#4b5563; }

.tabulator .tabulator-tableholder::-webkit-scrollbar { width:14px; height:14px; }
.tabulator .tabulator-tableholder::-webkit-scrollbar-track { background:#f1f5f9; }
.tabulator .tabulator-tableholder::-webkit-scrollbar-thumb { background:#94a3b8; border:3px solid #f1f5f9; border-radius:8px; }
.tabulator .tabulator-tableholder::-webkit-scrollbar-thumb:hover { background:#64748b; }
.dark .tabulator .tabulator-tableholder::-webkit-scrollbar-track { background:#0f172a; }
.dark .tabulator .tabulator-tableholder::-webkit-scrollbar-thumb { background:#475569; border-color:#0f172a; }

#pm-dirty-bar {
    position:fixed; bottom:0; left:0; right:0; z-index:50;
    display:none; align-items:center; justify-content:center; gap:12px;
    padding:12px 16px; background:#f59e0b; color:#fff;
    box-shadow:0 -8px 20px rgba(0,0,0,.15); border-top:4px solid #b45309;
}
#pm-dirty-bar.open { display:flex; }
#pm-dirty-bar .pm-dirty-save {
    background:#fff; color:#b45309; font-weight:800;
    padding:10px 24px; border-radius:6px; border:none; cursor:pointer; font-size:14px;
}
#pm-dirty-bar .pm-dirty-cancel {
    background:#b45309; color:#fff; font-weight:600;
    padding:10px 20px; border-radius:6px; border:none; cursor:pointer; font-size:13px;
}

#pm-toast {
    position:fixed; bottom:80px; right:20px; z-index:60;
    padding:10px 18px; border-radius:6px;
    background:#16a34a; color:#fff; font-weight:600; font-size:13px;
    box-shadow:0 4px 14px rgba(0,0,0,.2); display:none;
}
#pm-toast.error { background:#dc2626; }
#pm-toast.show  { display:block; }

/* ── Bulk-edit panel (appears when ≥1 row selected) ── */
#pm-bulk-bar {
    display:none; align-items:center; flex-wrap:wrap; gap:8px;
    padding:8px 12px; margin-bottom:8px;
    background:#ecfeff; border:1px solid #67e8f9; border-radius:8px;
    box-shadow:0 1px 2px rgba(0,0,0,.04);
}
#pm-bulk-bar.open { display:flex; }
.dark #pm-bulk-bar { background:#0c4a6e; border-color:#0e7490; }
#pm-bulk-bar .pm-bulk-label { font-weight:700; color:#0e7490; font-size:13px; }
.dark #pm-bulk-bar .pm-bulk-label { color:#bae6fd; }
#pm-bulk-bar select, #pm-bulk-bar input {
    height:30px; padding:0 8px; font-size:12px;
    background:#fff; border:1px solid #cbd5e1; border-radius:5px; color:#111827;
}
.dark #pm-bulk-bar select, .dark #pm-bulk-bar input { background:#1f2937; border-color:#4b5563; color:#f3f4f6; }
#pm-bulk-bar .pm-bulk-field {
    display:flex; align-items:center; gap:4px;
    padding:2px 6px; background:#fff; border:1px solid #cbd5e1; border-radius:5px;
}
.dark #pm-bulk-bar .pm-bulk-field { background:#1f2937; border-color:#4b5563; }
#pm-bulk-bar .pm-bulk-field label { font-size:11px; color:#475569; font-weight:600; }
.dark #pm-bulk-bar .pm-bulk-field label { color:#cbd5e1; }
#pm-bulk-bar .pm-bulk-field input[type="number"] { width:90px; }
#pm-bulk-cat-btn {
    height:30px; padding:0 10px; font-size:12px; font-weight:600;
    background:#fff; border:1px solid #cbd5e1; border-radius:5px; color:#0e7490; cursor:pointer;
}
#pm-bulk-cat-btn:hover { background:#f0f9ff; }
.dark #pm-bulk-cat-btn { background:#1f2937; border-color:#4b5563; color:#7dd3fc; }
.dark #pm-bulk-cat-btn:hover { background:#0c4a6e; }
#pm-bulk-cat-btn .pm-bulk-cat-count {
    display:inline-block; margin-left:4px; padding:0 6px;
    background:#0e7490; color:#fff; border-radius:9px; font-size:11px;
}

/* ── Category-pick modal (responsive, scrollable) ── */
#pm-cat-modal {
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(15,23,42,.55); align-items:center; justify-content:center;
    padding:16px;
}
#pm-cat-modal.open { display:flex; }
#pm-cat-modal-card {
    background:#fff; border-radius:10px; box-shadow:0 20px 50px rgba(0,0,0,.25);
    width:100%; max-width:560px; max-height:90vh; display:flex; flex-direction:column;
}
.dark #pm-cat-modal-card { background:#111827; color:#f3f4f6; }
#pm-cat-modal-head {
    padding:14px 16px; border-bottom:1px solid #e5e7eb;
    display:flex; align-items:center; justify-content:space-between; gap:8px;
}
.dark #pm-cat-modal-head { border-color:#374151; }
#pm-cat-modal-head h3 { margin:0; font-size:15px; font-weight:700; }
#pm-cat-modal-search {
    width:100%; height:34px; padding:0 10px; font-size:13px;
    background:#f9fafb; border:1px solid #d1d5db; border-radius:6px;
    margin:10px 16px 0; box-sizing:border-box; width:calc(100% - 32px);
}
.dark #pm-cat-modal-search { background:#1f2937; border-color:#4b5563; color:#f3f4f6; }
#pm-cat-modal-toolbar {
    padding:8px 16px; display:flex; gap:8px; align-items:center;
    font-size:12px; color:#475569;
}
.dark #pm-cat-modal-toolbar { color:#cbd5e1; }
#pm-cat-modal-toolbar button {
    padding:4px 10px; font-size:12px; border:1px solid #cbd5e1; border-radius:4px;
    background:#fff; cursor:pointer;
}
.dark #pm-cat-modal-toolbar button { background:#1f2937; border-color:#4b5563; color:#f3f4f6; }
#pm-cat-modal-list {
    flex:1; overflow-y:auto; padding:0 16px 8px;
    border-top:1px solid #f1f5f9;
}
.dark #pm-cat-modal-list { border-color:#374151; }
#pm-cat-modal-list label {
    display:flex; align-items:center; gap:8px;
    padding:8px 4px; border-bottom:1px solid #f1f5f9;
    font-size:13px; cursor:pointer; user-select:none;
}
.dark #pm-cat-modal-list label { border-color:#1f2937; }
#pm-cat-modal-list label:hover { background:#f0f9ff; }
.dark #pm-cat-modal-list label:hover { background:#0c4a6e; }
#pm-cat-modal-list input[type="checkbox"] { width:16px; height:16px; cursor:pointer; }
#pm-cat-modal-foot {
    padding:12px 16px; border-top:1px solid #e5e7eb;
    display:flex; justify-content:flex-end; gap:8px;
}
.dark #pm-cat-modal-foot { border-color:#374151; }
#pm-cat-modal-foot button {
    height:34px; padding:0 14px; font-size:13px; font-weight:600;
    border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;
}
#pm-cat-modal-confirm { background:#0e7490; color:#fff; border-color:#0e7490; }
#pm-cat-modal-confirm:hover { background:#155e75; }
#pm-cat-modal-cancel { background:#fff; color:#475569; }
.dark #pm-cat-modal-cancel { background:#1f2937; color:#cbd5e1; border-color:#4b5563; }
@media (max-width:640px) {
    #pm-cat-modal-card { max-height:95vh; }
    #pm-cat-modal-head h3 { font-size:14px; }
    #pm-cat-modal-list label { padding:10px 4px; font-size:14px; }
}

/* ── Mobile (< 768px): keep frozen-left columns visible.
   The select checkbox is the leftmost frozen column; on narrow screens
   stock + image were eating the row height so the checkbox got pushed
   under the image cell visually. Tighten both widths and shrink the
   image thumbnail so the checkbox + No. + image + stock + status all
   fit in the frozen-left rail. */
@media (max-width:767px) {
    .tabulator .tabulator-cell[tabulator-field="image_url"] { width:48px !important; min-width:42px !important; }
    .tabulator .tabulator-col[tabulator-field="image_url"]  { width:48px !important; min-width:42px !important; }
    .pm-thumb        { width:42px !important; height:42px !important; }
    .pm-thumb-empty  { width:42px !important; height:42px !important; font-size:8px !important; }
    .tabulator .tabulator-cell[tabulator-field="stock"] { width:42px !important; min-width:32px !important; padding:2px 2px !important; }
    .tabulator .tabulator-col[tabulator-field="stock"]  { width:42px !important; min-width:32px !important; }
    .tabulator .tabulator-cell[tabulator-field="_select"] { width:28px !important; min-width:24px !important; padding:0 !important; }
    .tabulator .tabulator-col[tabulator-field="_select"]  { width:28px !important; min-width:24px !important; }
    .tabulator .tabulator-cell[tabulator-field="_rownum"] { width:34px !important; min-width:28px !important; padding:0 2px !important; font-size:11px; }
    .tabulator .tabulator-col[tabulator-field="_rownum"]  { width:34px !important; min-width:28px !important; }
    .tabulator .tabulator-row { min-height:50px; }
    .tabulator { font-size:12px; }
    /* Top toolbar wraps tighter so the status filter doesn't overflow */
    #pm-toolbar input, #pm-toolbar select { height:30px; font-size:12px; padding:0 6px; }
    #pm-search { width:140px !important; }
}
</style>
@endpush

{{-- ── Toolbar ──────────────────────────────────────────────────────── --}}
<div id="pm-toolbar">
    <input type="text" id="pm-search" placeholder="商品名・SKU検索…" style="width:220px;">
    <select id="pm-channel" onchange="pmRebuild()">
        @foreach(core()->getAllChannels() as $ch)
            <option value="{{ $ch->code }}" {{ $ch->code === core()->getCurrentChannelCode() ? 'selected' : '' }}>
                {{ $ch->name }}
            </option>
        @endforeach
    </select>
    <select id="pm-status-filter" title="状態で絞り込み">
        <option value="">— 状態すべて —</option>
        <option value="1">有効</option>
        <option value="0">無効</option>
    </select>
    <input type="number" id="pm-cargo-rate" value="{{ $cargoRateDefault }}" placeholder="¥/kg" style="width:90px;">
    <div class="pm-tb-pill">
        <div><span class="pm-tb-k">在庫</span><span class="pm-tb-v" id="pm-total-stock">0</span></div>
        <div><span class="pm-tb-k">価値</span><span class="pm-tb-v" id="pm-total-value">¥0</span></div>
    </div>
    <div id="pm-col-wrap">
        <button type="button" id="pm-col-btn" class="pm-btn-secondary"
                onclick="window.pm&&window.pm.toggleColMenu(event)">⚙ 列</button>
        <div id="pm-col-menu"></div>
    </div>
    <div style="margin-left:auto; display:flex; gap:8px; align-items:center;">
        {{-- Inline onclick: fires on the element synchronously, so Vue/Bagisto
             outer click-stoppers can't kill it before delegation reaches us. --}}
        <button type="button" id="pm-top-save"   class="pm-btn-primary"
                onclick="window.pm&&window.pm.save()">保存 <span id="pm-top-save-count" style="opacity:.85; font-size:11px;"></span></button>
        <button type="button" id="pm-top-cancel" class="pm-btn-secondary"
                onclick="window.pm&&window.pm.cancel()">取消</button>
        <button type="button" id="pm-find-dupes" class="pm-btn-secondary"
                onclick="window.pm&&window.pm.findDuplicates()"
                title="SKU・商品名で重複している商品を抽出">🔍 重複</button>
        <span id="pm-count" style="font-size:11px; color:#9ca3af;">0 件</span>
    </div>
</div>

{{-- ── Bulk-action bar (visible when ≥1 row selected) ─────────────────
     Trimmed to two operations only: change status (active/inactive)
     and delete. Brand/color/weight bulk fields and clipboard
     export/reorder were removed in favour of a focused bar. --}}
<div id="pm-bulk-bar">
    <span class="pm-bulk-label">選択 <span id="pm-bulk-count">0</span> 件</span>
    <div class="pm-bulk-field">
        <label>ブランド</label>
        <select id="pm-bulk-brand">
            <option value="__skip__">— 変更なし —</option>
        </select>
    </div>
    <div class="pm-bulk-field">
        <label>色</label>
        <select id="pm-bulk-color">
            <option value="__skip__">— 変更なし —</option>
        </select>
    </div>
    <div class="pm-bulk-field">
        <label>重量(kg)</label>
        <input type="number" id="pm-bulk-weight" step="0.001" min="0" placeholder="—">
    </div>
    <div class="pm-bulk-field">
        <label>仕入価格</label>
        <input type="number" id="pm-bulk-buyprice" step="1" min="0" placeholder="—">
    </div>
    <button type="button" id="pm-bulk-cat-btn"
            onclick="window.pm&&window.pm.openCategoryModal()">
        カテゴリ選択<span class="pm-bulk-cat-count" id="pm-bulk-cat-count" style="display:none;">0</span>
    </button>
    <div class="pm-bulk-field">
        <label>状態</label>
        <select id="pm-bulk-status">
            <option value="__skip__">— 変更なし —</option>
            <option value="1">有効</option>
            <option value="0">無効</option>
        </select>
    </div>
    <button type="button" class="pm-btn-primary" id="pm-bulk-apply"
            onclick="window.pm&&window.pm.applyBulk()">適用</button>
    <button type="button" class="pm-btn-secondary" id="pm-bulk-delete"
            style="background:#fee2e2;border-color:#fca5a5;color:#b91c1c;font-weight:700;"
            onclick="window.pm&&window.pm.bulkDelete()"
            title="選択した商品を削除">🗑 選択削除</button>
    <button type="button" class="pm-btn-secondary" id="pm-bulk-clearsel"
            onclick="window.pm&&window.pm.clearSelection()">選択解除</button>
</div>

<div id="pm-cat-modal" role="dialog" aria-modal="true" aria-labelledby="pm-cat-modal-title">
    <div id="pm-cat-modal-card">
        <div id="pm-cat-modal-head">
            <h3 id="pm-cat-modal-title">カテゴリを選択 <span id="pm-cat-modal-picked" style="font-weight:500;color:#64748b;">(0 件)</span></h3>
        </div>
        <input type="text" id="pm-cat-modal-search" placeholder="カテゴリ名で絞り込み…" autocomplete="off">
        <div id="pm-cat-modal-toolbar">
            <button type="button" onclick="window.pm&&window.pm.catModalSelectAll()">全選択(表示中)</button>
            <button type="button" onclick="window.pm&&window.pm.catModalClear()">全解除</button>
            <span style="margin-left:auto;font-size:11px;">確定すると選択行のカテゴリを置き換えます</span>
        </div>
        <div id="pm-cat-modal-list"></div>
        <div id="pm-cat-modal-foot">
            <button type="button" id="pm-cat-modal-cancel"  onclick="window.pm&&window.pm.closeCategoryModal()">キャンセル</button>
            <button type="button" id="pm-cat-modal-confirm" onclick="window.pm&&window.pm.confirmCategoryModal()">確定</button>
        </div>
    </div>
</div>

<div id="pm-table-wrap"><div id="pm-table"></div></div>

<div id="pm-dirty-bar">
    <span style="font-weight:700;"><span id="pm-dirty-count">0</span> 件変更</span>
    <button type="button" class="pm-dirty-save"   id="pm-bulk-save"
            onclick="window.pm&&window.pm.save()">一括保存</button>
    <button type="button" class="pm-dirty-cancel" id="pm-bulk-cancel"
            onclick="window.pm&&window.pm.cancel()">キャンセル</button>
</div>

<div id="pm-toast"></div>

@include('admin::catalog.products.partials.reservations-modal')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@6.3.1/dist/js/tabulator.min.js"></script>
<script>
(function () {
'use strict';

if (window.__pmListBooted) { console.warn('[PM] duplicate boot — skipped'); return; }
window.__pmListBooted = true;

/* ══════════════════════════════════════════════════════
   1. CONFIG
══════════════════════════════════════════════════════ */
const API = {
    all:     "{{ route('admin.catalog.products.all') }}",
    bulk:    "{{ route('admin.catalog.products.bulk_update') }}",
    reorder: "/admin/inventory/reorder/from-clipboard",
    csrf:    document.querySelector('meta[name="csrf-token"]')?.content || "{{ csrf_token() }}",
};
/* Per-user persistence: prefs are keyed by the admin user's id so two
   admins sharing a browser don't see each other's column layout / filters.
   Fallback "guest" keeps the page working even if the guard returns null. */
const PM_USER = "{{ optional(auth()->guard('admin')->user())->id ?? 'guest' }}";
const STORAGE = {
    hidden:    `pm_col_hidden_v3_${PM_USER}`,
    widths:    `pm_col_widths_v3_${PM_USER}`,
    order:     `pm_col_order_v3_${PM_USER}`,
    filters:   `pm_filters_v3_${PM_USER}`,   // header filters per field
    toolbar:   `pm_toolbar_v3_${PM_USER}`,   // top search + channel
};

/* ══════════════════════════════════════════════════════
   1.b  i18n — column titles + status labels
   Resolved server-side at render so the active locale's
   strings ship with the page (no client-side fetch).
══════════════════════════════════════════════════════ */
const T = {
    cols: {
        image:               @json(trans('admin::app.catalog.products.views.cols.image')),
        stock:               @json(trans('admin::app.catalog.products.views.cols.stock')),
        reserved:            '予約',
        name:                @json(trans('admin::app.catalog.products.views.cols.name')),
        originalProductName: @json(trans('admin::app.catalog.products.views.cols.original-product-name')),
        status:              @json(trans('admin::app.catalog.products.views.cols.status')),
        sku:                 @json(trans('admin::app.catalog.products.views.cols.sku')),
        category:            @json(trans('admin::app.catalog.products.views.cols.category')),
        brand:               @json(trans('admin::app.catalog.products.views.cols.brand')),
        price:               @json(trans('admin::app.catalog.products.views.cols.price')),
        specialPrice:        @json(trans('admin::app.catalog.products.views.cols.special-price')),
        source:              @json(trans('admin::app.catalog.products.views.cols.source')),
        jpyCost:             @json(trans('admin::app.catalog.products.views.cols.jpy-cost')),
        buyPrice:            @json(trans('admin::app.catalog.products.views.cols.buy-price')),
        currency:            @json(trans('admin::app.catalog.products.views.cols.currency')),
        exchangeRate:        @json(trans('admin::app.catalog.products.views.cols.exchange-rate')),
        color:               @json(trans('admin::app.catalog.products.views.cols.color')),
        weight:              @json(trans('admin::app.catalog.products.views.cols.weight')),
        weightUnit:          @json(trans('admin::app.catalog.products.views.cols.weight-unit')),
        netCost:             @json(trans('admin::app.catalog.products.views.cols.net-cost')),
        profit:              @json(trans('admin::app.catalog.products.views.cols.profit')),
        soldCount:           @json(trans('admin::app.catalog.products.views.cols.sold-count')),
        type:                @json(trans('admin::app.catalog.products.views.cols.type')),
    },
    statusOn:    @json(trans('admin::app.catalog.products.views.cols.status-on')),
    statusOff:   @json(trans('admin::app.catalog.products.views.cols.status-off')),
    setSource:   @json(trans('admin::app.catalog.products.views.cols.set-source')),
    searchSource:@json(trans('admin::app.catalog.products.views.cols.search-source')),
};

const COLOR_OPTIONS = [
    @foreach(\DB::table('attribute_options')
        ->where('attribute_id', 23)
        ->leftJoin('attribute_option_translations', function($q){
            $q->on('attribute_options.id','=','attribute_option_translations.attribute_option_id')
              ->where('attribute_option_translations.locale', app()->getLocale());
        })
        ->select('attribute_options.id','attribute_options.admin_name','attribute_option_translations.label')
        ->get() as $opt)
    { id: {{ $opt->id }}, label: @json($opt->label ?: $opt->admin_name) },
    @endforeach
];

/* ══════════════════════════════════════════════════════
   2. STATE  (exposed as window._pm for console debugging)
══════════════════════════════════════════════════════ */
const state = {
    table:        null,
    allRows:      [],
    categoryOpts: [],
    brandOpts:    [],
    sourceOpts:   [],
    weightUnit:   new Map(),
    dirty:        new Map(),   // rowId(str) → { apiField: newValue }
    originals:    new Map(),   // rowId(str) → deep-clone of original row
    clipboard:    new Map(),
    saving:       false,
    bulkCatPicked: new Set(),  // string ids of categories picked in the bulk modal
};
window._pm = state;   // console: _pm.dirty, _pm.originals, etc.

/* ══════════════════════════════════════════════════════
   3. HELPERS — calc
══════════════════════════════════════════════════════ */
const getCargoRate = () => parseFloat(document.getElementById('pm-cargo-rate').value) || 0;

function calcCostJPY(r) {
    const jp = parseFloat(r.jpy_cost);
    if (jp > 0) return jp;
    const bp = parseFloat(r.buy_price) || 0;
    const fx = parseFloat(r.exchange_rate) || 1;
    return r.currency === 'MMK' ? bp / fx : bp;
}
function calcNetCost(r) { return calcCostJPY(r) + (parseFloat(r.weight_kg) || 0) * getCargoRate(); }
function calcProfit(r)  {
    // special_price arrives as "0.0000" (truthy string) when unset, so
    // `special_price || price` wrongly yields 0 — use it only when > 0.
    const sp   = parseFloat(r.special_price) || 0;
    const sell = sp > 0 ? sp : (parseFloat(r.price) || 0);
    const net  = calcNetCost(r);
    return sell > 0 ? (sell - net) / sell * 100 : null;
}
const fmtMoney = v => (v == null || v === '') ? '—' : '¥' + Math.round(parseFloat(v) || 0).toLocaleString();

/* ══════════════════════════════════════════════════════
   HELPER — getRow: tries integer index first (API returns
   integer ids), then string. Tabulator stores the index
   using the type that arrived in the data, so passing the
   wrong type returns false silently.
══════════════════════════════════════════════════════ */
function getRow(rowId) {
    if (!state.table) return null;
    const intId = parseInt(rowId, 10);
    let row = state.table.getRow(intId);
    if (!row) row = state.table.getRow(String(rowId));
    return row || null;
}

/* ══════════════════════════════════════════════════════
   4. FORMATTERS
══════════════════════════════════════════════════════ */
const fmt = {
    image(cell) {
        const v = cell.getValue();
        if (!v) {
            const div = document.createElement('div');
            div.className = 'pm-thumb-empty';
            div.textContent = 'NO IMG';
            return div;
        }
        const img = document.createElement('img');
        img.className = 'pm-thumb';
        img.loading = 'lazy';
        img.src = v;
        img.onerror = () => {
            img.onerror = null;
            const div = document.createElement('div');
            div.className = 'pm-thumb-empty';
            div.textContent = 'NO IMG';
            img.replaceWith(div);
        };
        return img;
    },
    stock(cell) {
        // Color the cell purely on stock value: in-stock = green, zero = red.
        // Status (active/inactive) is shown separately by the toggle column.
        const s   = parseFloat(cell.getValue()) || 0;
        const cls = s > 0 ? 'pm-stock-active' : 'pm-stock-zero';
        const sp  = document.createElement('span');
        sp.className = cls;
        sp.style.cssText = 'display:block;width:100%;height:100%;text-align:center;font-weight:800;';
        sp.textContent = cell.getValue() ?? '';
        return sp;
    },
    reserved(cell) {
        const r = parseInt(cell.getValue(), 10) || 0;
        const sp = document.createElement('span');
        sp.className = 'pm-reserved-badge ' + (r > 0 ? 'has' : 'zero');
        sp.textContent = r;
        if (r > 0) {
            const id = cell.getRow().getData().id;
            sp.title = 'クリックで予約注文を表示';
            sp.addEventListener('click', (e) => { e.stopPropagation(); pmShowReservations(id); });
        }
        return sp;
    },
    netCost(cell) { return fmtMoney(calcNetCost(cell.getRow().getData())); },
    profit(cell) {
        const p  = calcProfit(cell.getRow().getData());
        const sp = document.createElement('span');
        sp.textContent  = p == null ? '—' : p.toFixed(1) + '%';
        sp.style.fontWeight = '700';
        sp.style.color  = p == null ? '#6b7280' : p >= 20 ? '#16a34a' : p >= 5 ? '#ca8a04' : '#dc2626';
        return sp;
    },
    /* iOS-style on/off toggle. Clicking the switch flips status and runs
       the same dirty-tracking path as a normal cell edit. */
    statusToggle(cell) {
        const wrap   = document.createElement('div');
        wrap.className = 'pm-status-wrap';

        const sw     = document.createElement('label');
        sw.className = 'pm-status-switch';
        const cb     = document.createElement('input');
        cb.type      = 'checkbox';
        cb.checked   = String(cell.getValue()) !== '0';
        const knob   = document.createElement('span');
        knob.className = 'pm-status-knob';
        sw.appendChild(cb);
        sw.appendChild(knob);

        const lbl    = document.createElement('span');
        const setLabel = (on) => {
            lbl.className = 'pm-status-text ' + (on ? 'pm-on' : 'pm-off');
            lbl.textContent = on ? T.statusOn : T.statusOff;
        };
        setLabel(cb.checked);

        cb.addEventListener('change', (e) => {
            e.stopPropagation();
            const newVal = cb.checked ? '1' : '0';
            const row    = cell.getRow();
            const data   = row.getData();
            row.update({ status: newVal });
            setLabel(cb.checked);
            // Run the same path Tabulator's cellEdited handler runs.
            onCellEdited({
                getRow:    () => row,
                getField:  () => 'status',
                getValue:  () => newVal,
                getElement:() => cell.getElement(),
                getColumn: () => ({ getDefinition: () => ({ editor: null }) }),
            });
        });

        wrap.appendChild(sw);
        wrap.appendChild(lbl);
        return wrap;
    },
    currencyLabel(cell) { return cell.getValue() || 'JPY'; },
    colorLabel(cell) {
        const v = cell.getValue();
        if (v == null || v === '') return '—';
        const f = COLOR_OPTIONS.find(c => String(c.id) === String(v));
        return f ? f.label : String(v);
    },
    brandLabel(cell)    { return cell.getRow().getData().brand || '—'; },
    categoryLabel(cell) {
        const r = cell.getRow().getData();
        if (Array.isArray(r.category_names) && r.category_names.length) return r.category_names.join(', ');
        return r.categories || r.category_name || '—';
    },
    money(cell) {
        const v = cell.getValue();
        return (v == null || v === '') ? '' : Number(v).toLocaleString();
    },
    sourceLabel(cell) {
        const ids = cell.getRow().getData().source_ids || [];
        const lbs = ids.map(id => (state.sourceOpts.find(o => String(o.id) === String(id)) || {}).label).filter(Boolean);
        return lbs.length ? lbs.join(', ') : '—';
    },
    weightDisplay(cell) {
        const r    = cell.getRow().getData();
        const kg   = parseFloat(r.weight_kg);
        if (isNaN(kg) || r.weight_kg == null || r.weight_kg === '') return '';
        const unit = state.weightUnit.get(String(r.id)) || 'kg';
        const v    = unit === 'g' ? Math.round(kg * 1000) : kg;
        return v.toLocaleString();
    },
    weightUnit(cell) { return state.weightUnit.get(String(cell.getRow().getData().id)) || 'kg'; },
    deleteBtn() {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'pm-row-delete';
        b.title = '削除';
        b.innerHTML = '🗑';
        b.style.cssText = 'background:transparent;border:none;cursor:pointer;font-size:16px;color:#dc2626;padding:2px 6px;border-radius:4px;';
        b.onmouseenter = () => b.style.background = '#fee2e2';
        b.onmouseleave = () => b.style.background = 'transparent';
        return b;
    },
};

/* ══════════════════════════════════════════════════════
   5. EDITOR PARAMS
══════════════════════════════════════════════════════ */
const ep = {
    statusList()   { return { values:{ '1':'有効', '0':'無効' } }; },
    currencyList() { return { values:{ JPY:'JPY', MMK:'MMK', USD:'USD' } }; },
    colorList() {
        const v = { '':'—' };
        COLOR_OPTIONS.forEach(c => { v[String(c.id)] = c.label; });
        return { values:v };
    },
    brandList() {
        const v = { '':'—' };
        state.brandOpts.forEach(b => { v[String(b.id)] = b.label; });
        return { values:v };
    },
    categoryList() {
        const v = {};
        state.categoryOpts.forEach(c => { v[String(c.id)] = c.name || ('#' + c.id); });
        return { values:v, multiselect:true, clearable:true };
    },
    sourceList() {
        const v = {};
        state.sourceOpts.forEach(o => { v[String(o.id)] = o.label; });
        return { values:v, multiselect:true, clearable:true, emptyValue:[] };
    },
    unitList() { return { values:{ kg:'kg', g:'g' } }; },
};

/* ══════════════════════════════════════════════════════
   6. COLUMNS — titles use the locale-aware T.cols.* map
══════════════════════════════════════════════════════ */
const COLUMNS = [
    { title:'', field:'_select', formatter:'rowSelection', titleFormatter:'rowSelection', hozAlign:'center', headerSort:false, width:32, minWidth:28, frozen:true, cellClick:(e,cell)=>cell.getRow().toggleSelect() },
    { title:'No.', field:'_rownum', formatter:'rownum', hozAlign:'center', headerSort:false, headerFilter:false, width:48, minWidth:40, frozen:true },
    { title:T.cols.image, field:'image_url', formatter:fmt.image, headerSort:false, width:76, minWidth:72, frozen:true },
    { title:T.cols.stock, field:'stock', editor:'number', editorParams:{ min:0, step:1 }, formatter:fmt.stock, hozAlign:'center', sorter:'number', headerFilter:'number', headerFilterFunc:'>=', width:56, minWidth:36, frozen:true },
    { title:T.cols.reserved, field:'reserved', formatter:fmt.reserved, hozAlign:'center', sorter:'number', headerFilter:'number', headerFilterFunc:'>=', width:56, minWidth:42, frozen:true, headerSort:true },
    {
        // Status — frozen-left right after stock. NO editor; the formatter
        // renders an iOS-style toggle and handles the change itself.
        title:T.cols.status, field:'status',
        formatter:fmt.statusToggle,
        headerFilter:'list',
        headerFilterParams:{ values:{ '': '—', '1': T.statusOn, '0': T.statusOff } },
        headerFilterFunc:(term, _v, row) => term === '' || term == null ? true : String(row.getData().status) === String(term),
        sorter:'number',
        width:92, minWidth:80, frozen:true,
    },
    { title:T.cols.name,                field:'name',                  editor:'input',  headerFilter:'input',  sorter:'string', width:200, minWidth:100 },
    { title:T.cols.originalProductName, field:'original_product_name', editor:'input',  headerFilter:'input',  sorter:'string', width:160, minWidth:100 },
    { title:T.cols.sku,                 field:'sku',                   headerFilter:'input', sorter:'string', width:110, minWidth:80 },
    {
        title:T.cols.category, field:'category_ids',
        editor:'list', editorParams:() => ep.categoryList(), formatter:fmt.categoryLabel,
        headerFilter:'input',
        headerFilterFunc:(term,_v,row) => {
            if (!term) return true;
            const r = row.getData();
            const t = (Array.isArray(r.category_names) ? r.category_names.join(',') : (r.categories||'')) || '';
            return t.toLowerCase().includes(term.toLowerCase());
        },
        width:140, minWidth:100,
    },
    {
        title:T.cols.brand, field:'brand_id',
        editor:'list', editorParams:() => ep.brandList(), formatter:fmt.brandLabel,
        headerFilter:'input',
        headerFilterFunc:(term,_v,row) => !term || (row.getData().brand||'').toLowerCase().includes(term.toLowerCase()),
        width:110, minWidth:90,
    },
    { title:T.cols.price,               field:'price',         editor:'number', editorParams:{ min:0 }, formatter:fmt.money, hozAlign:'right', sorter:'number', headerFilter:'number', headerFilterFunc:'>=', width:90, minWidth:70 },
    { title:T.cols.specialPrice,        field:'special_price', editor:'number', editorParams:{ min:0 }, formatter:fmt.money, hozAlign:'right', sorter:'number', headerFilter:'number', headerFilterFunc:'>=', width:90, minWidth:70 },
    {
        title:T.cols.source, field:'source_ids',
        editor:'list', editorParams:() => ep.sourceList(), formatter:fmt.sourceLabel,
        headerFilter:'input',
        headerFilterFunc:(term,_v,row) => {
            if (!term) return true;
            const ids = row.getData().source_ids || [];
            const t   = ids.map(id => (state.sourceOpts.find(o => String(o.id)===String(id))||{}).label||'').join(',').toLowerCase();
            return t.includes(term.toLowerCase());
        },
        width:130, minWidth:100,
    },
    { title:T.cols.jpyCost,             field:'jpy_cost',      editor:'number', editorParams:{ min:0 }, formatter:fmt.money, hozAlign:'right', sorter:'number', headerFilter:'number', headerFilterFunc:'>=', width:90, minWidth:70 },
    { title:T.cols.buyPrice,            field:'buy_price',     editor:'number', editorParams:{ min:0 }, formatter:fmt.money, hozAlign:'right', sorter:'number', headerFilter:'number', headerFilterFunc:'>=', width:90, minWidth:70 },
    { title:T.cols.currency,            field:'currency',      editor:'list', editorParams:ep.currencyList(), formatter:fmt.currencyLabel, headerFilter:'list', headerFilterParams:ep.currencyList(), width:70, minWidth:60 },
    { title:T.cols.exchangeRate,        field:'exchange_rate', editor:'number', editorParams:{ min:0, step:0.01 }, hozAlign:'right', sorter:'number', headerFilter:'number', headerFilterFunc:'>=', width:84, minWidth:64 },
    { title:T.cols.color,               field:'color',         editor:'list', editorParams:ep.colorList(), formatter:fmt.colorLabel, headerFilter:'list', headerFilterParams:ep.colorList(), width:88, minWidth:72 },
    {
        title:T.cols.weight, field:'weight_kg',
        editor:'number', editorParams:{ min:0, step:0.001 },
        formatter:fmt.weightDisplay,
        mutatorEdit:(value, data) => {
            if (value === '' || value == null) return null;
            const num  = parseFloat(value);
            if (isNaN(num)) return null;
            const unit = state.weightUnit.get(String(data.id)) || 'kg';
            return unit === 'g' ? num / 1000 : num;
        },
        hozAlign:'right',
        sorter:(a,b) => (parseFloat(a)||0) - (parseFloat(b)||0),
        width:84, minWidth:64,
    },
    { title:T.cols.weightUnit,          field:'_weight_unit', editor:'list', editorParams:ep.unitList(), formatter:fmt.weightUnit, headerFilter:false, headerSort:false, width:60, minWidth:50 },
    { title:T.cols.netCost,             field:'_net_cost',    formatter:fmt.netCost, hozAlign:'right', sorter:(_a,_b,aR,bR)=>calcNetCost(aR.getData())-calcNetCost(bR.getData()), width:100, minWidth:80, headerSort:true, headerFilter:false },
    { title:T.cols.profit,              field:'_profit',      formatter:fmt.profit,  hozAlign:'right', sorter:(_a,_b,aR,bR)=>(calcProfit(aR.getData())??-1e9)-(calcProfit(bR.getData())??-1e9), width:84, minWidth:70, headerSort:true, headerFilter:false },
    { title:T.cols.soldCount,           field:'sold_count',   hozAlign:'right', sorter:'number', headerFilter:'number', headerFilterFunc:'>=', width:80, minWidth:64 },
    { title:T.cols.type,                field:'type',         headerFilter:'input', width:80, minWidth:64 },
    {
        // Per-row delete. Stops cellClick propagation so it doesn't toggle
        // selection when the user clicks the icon. Confirms once before
        // the DELETE call.
        title:'', field:'_delete', formatter:fmt.deleteBtn,
        headerSort:false, headerFilter:false, hozAlign:'center',
        width:48, minWidth:40, frozen:true, frozenPosition:'right',
        cellClick:(e, cell) => { e.stopPropagation(); pmDeleteOne(cell.getRow()); },
    },
];

/* ══════════════════════════════════════════════════════
   7. DIRTY TRACKING
══════════════════════════════════════════════════════ */
const dirtyCellMap = new Map();  // "rowId|field" → HTMLElement

function valuesEqual(a, b) {
    if (a === b) return true;
    const aE = (a === null || a === undefined || a === '');
    const bE = (b === null || b === undefined || b === '');
    if (aE && bE) return true;
    if (aE !== bE) return false;
    if (Array.isArray(a) || Array.isArray(b)) {
        const aa = (Array.isArray(a)?a:[]).map(String).filter(Boolean).sort();
        const bb = (Array.isArray(b)?b:[]).map(String).filter(Boolean).sort();
        return aa.length === bb.length && aa.every((v,i) => v === bb[i]);
    }
    const an = parseFloat(a), bn = parseFloat(b);
    if (!isNaN(an) && !isNaN(bn) && String(a).trim() && String(b).trim()) return an === bn;
    return String(a) === String(b);
}

function origFor(rowId, field) {
    const s = state.originals.get(rowId);
    if (!s) return undefined;
    if (field === 'brand')      return s.brand_id ?? null;
    if (field === 'categories') return s.category_ids ?? [];
    if (field === 'source_ids') return s.source_ids ?? [];
    return s[field];
}

function markCell(rowId, field, dirty, el) {
    const key = rowId + '|' + field;
    if (dirty) {
        if (el) { el.classList.add('pm-cell-dirty'); dirtyCellMap.set(key, el); }
    } else {
        const e = dirtyCellMap.get(key) || el;
        if (e) e.classList.remove('pm-cell-dirty');
        dirtyCellMap.delete(key);
    }
}

function markRow(rowId, dirty) {
    const row = getRow(rowId);
    if (row) row.getElement().classList.toggle('pm-row-dirty', dirty);
}

function updateDirty(rowId, field, value, el) {
    const orig = origFor(rowId, field);
    const same = valuesEqual(value, orig);
    console.log('[PM] updateDirty', { rowId, field, value, orig, same });

    if (same) {
        const map = state.dirty.get(rowId);
        if (map) {
            delete map[field];
            if (!Object.keys(map).length) { state.dirty.delete(rowId); markRow(rowId, false); }
        }
        markCell(rowId, field, false, el);
    } else {
        if (!state.dirty.has(rowId)) state.dirty.set(rowId, {});
        state.dirty.get(rowId)[field] = value;
        markCell(rowId, field, true, el);
        markRow(rowId, true);
    }
    syncDirtyUI();
}

function clearDirty() {
    state.dirty.clear();
    dirtyCellMap.forEach(el => el.classList.remove('pm-cell-dirty'));
    dirtyCellMap.clear();
    document.querySelectorAll('.tabulator-row.pm-row-dirty')
        .forEach(el => el.classList.remove('pm-row-dirty'));
    syncDirtyUI();
}

/* ── Button enable/disable via data-disabled (NOT the disabled attr,
      which swallows clicks before our handler fires).              ── */
function setBtn(id, enabled) {
    const el = document.getElementById(id);
    if (!el) return;
    el.setAttribute('data-disabled', enabled ? '0' : '1');
}

function syncDirtyUI() {
    const n = state.dirty.size;
    const sel = state.table?.getSelectedRows().length || 0;
    document.getElementById('pm-dirty-count').textContent    = n;
    document.getElementById('pm-top-save-count').textContent = n > 0 ? `(${n})` : '';
    document.getElementById('pm-dirty-bar').classList.toggle('open', n > 0);
    setBtn('pm-top-save',   n > 0 && !state.saving);
    setBtn('pm-bulk-save',  n > 0 && !state.saving);
    // Cancel is also enabled when rows are selected — it doubles as "clear selection".
    setBtn('pm-top-cancel', n > 0 || sel > 0);
    setBtn('pm-bulk-cancel',n > 0);
}

/* ══════════════════════════════════════════════════════
   8. CELL EDIT HANDLER
══════════════════════════════════════════════════════ */
const RECALC   = new Set(['stock','buy_price','jpy_cost','exchange_rate','currency','weight_kg','price','special_price']);
const CLIP_REF = new Set(['name','original_product_name','stock']);

function onCellEdited(cell) {
    const row   = cell.getRow();
    const data  = row.getData();
    const field = cell.getField();
    let   value = cell.getValue();
    const id    = String(data.id);
    const el    = cell.getElement();

    let apiField = field;
    let apiValue = value;

    if (field === 'brand_id') {
        apiField = 'brand';
        apiValue = (value === '' || value == null) ? null : parseInt(value, 10);
        const opt = state.brandOpts.find(b => String(b.id) === String(value));
        data.brand = opt ? opt.label : '';
        row.update({ brand: data.brand });
    }
    else if (field === 'category_ids') {
        apiField = 'categories';
        const arr = Array.isArray(value) ? value : (value ? [value] : []);
        const ids = arr.map(v => parseInt(v, 10)).filter(Boolean);
        apiValue = ids.length ? ids : [''];
        data.category_ids   = ids;
        data.category_names = ids.map(id => (state.categoryOpts.find(c => String(c.id)===String(id))||{}).name).filter(Boolean);
        data.category_name  = data.category_names[0] || '';
        row.update({ category_ids:ids, category_names:data.category_names, category_name:data.category_name });
    }
    else if (field === 'source_ids') {
        const arr = Array.isArray(value) ? value : (value ? [value] : []);
        apiValue  = arr.map(v => parseInt(v, 10)).filter(Boolean).map(String);
        data.source_ids = apiValue;
        row.update({ source_ids: apiValue });
    }
    else if (field === '_weight_unit') {
        // Display-only — no dirty tracking needed
        state.weightUnit.set(id, value === 'g' ? 'g' : 'kg');
        row.reformat();
        return;
    }
    else if (field === 'status') {
        apiValue = String(value);
        data.status = apiValue;
        row.reformat();
        row.getElement().classList.toggle('pm-row-inactive', apiValue === '0');
    }
    else if (field === 'color') {
        apiValue = (value === '' || value == null) ? null : value;
    }
    else if (cell.getColumn().getDefinition().editor === 'number') {
        apiValue = (value === '' || value == null) ? null : parseFloat(value);
    }

    updateDirty(id, apiField, apiValue, el);

    if (RECALC.has(field)) { row.reformat(); recomputeSummary(); }
    if (state.clipboard.has(id) && CLIP_REF.has(field)) {
        state.clipboard.set(id, snap(data));
        renderClip();
    }
}

/* ══════════════════════════════════════════════════════
   9. SUMMARY
══════════════════════════════════════════════════════ */
function recomputeSummary() {
    const rows = state.table ? state.table.getRows('active').map(r => r.getData()) : state.allRows;
    let ts = 0, tv = 0;
    rows.forEach(r => { const s = parseFloat(r.stock)||0; ts += s; tv += s * calcCostJPY(r); });
    document.getElementById('pm-total-stock').textContent = ts.toLocaleString();
    document.getElementById('pm-total-value').textContent = '¥' + Math.round(tv).toLocaleString();
    document.getElementById('pm-count').textContent       = rows.length + ' 件';
}

/* ══════════════════════════════════════════════════════
   10. SAVE
══════════════════════════════════════════════════════ */
async function bulkSave() {
    console.log('[PM] bulkSave — dirty rows:', state.dirty.size);
    if (state.saving) { console.warn('[PM] already saving'); return; }
    if (!state.dirty.size) { toast('変更がありません'); return; }

    state.saving = true;
    syncDirtyUI();

    const updates = [];
    state.dirty.forEach((changes, rowId) => {
        const u = Object.assign({ id: parseInt(rowId, 10) }, changes);
        // Normalise status — send as integer to avoid Laravel type mismatch
        if ('status' in u) u.status = parseInt(u.status, 10);
        updates.push(u);
    });
    const channel = document.getElementById('pm-channel').value;

    console.log('[PM] POST →', API.bulk);
    console.log('[PM] payload:', JSON.stringify({ updates, channel }, null, 2));

    try {
        const res = await window.axios.post(API.bulk, { updates, channel }, {
            headers:{
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN':     API.csrf,
                'Content-Type':     'application/json',
                'Accept':           'application/json',
            }
        });
        console.log('[PM] response:', res.status, res.data);

        const fail = res.data?.fail ?? 0;
        toast(res.data?.message || `保存: ${updates.length - fail} 成功 / ${fail} 失敗`, fail ? 'error' : '');

        if (!fail) {
            // Sync originals so a subsequent cancel restores the saved state.
            updates.forEach(u => {
                const idx = state.allRows.findIndex(r => String(r.id) === String(u.id));
                if (idx < 0) return;
                Object.assign(state.allRows[idx], u);
                // Re-normalise status back to string (table/comparisons use strings)
                if (state.allRows[idx].status !== undefined)
                    state.allRows[idx].status = String(state.allRows[idx].status);
                state.originals.set(String(u.id), JSON.parse(JSON.stringify(state.allRows[idx])));
            });
            clearDirty();
        }
    } catch (e) {
        console.error('[PM] save error:', e.response?.status, e.response?.data || e.message);
        toast(e.response?.data?.message || e.message || 'Save failed', 'error');
    } finally {
        state.saving = false;
        syncDirtyUI();
    }
}

/* ══════════════════════════════════════════════════════
   11. CANCEL
   KEY FIX: table.updateData() silently skips rows that
   are scrolled out of the virtual DOM viewport.
   row.update() works on every row regardless of whether
   it is currently rendered.
══════════════════════════════════════════════════════ */
async function bulkCancel() {
    console.log('[PM] ▶ bulkCancel — dirty.size=', state.dirty.size,
                'originals.size=', state.originals.size,
                'allRows.length=', state.allRows.length,
                'table?=', !!state.table);

    if (!state.dirty.size) {
        console.log('[PM] cancel: nothing dirty');
        toast('変更がありません');
        return;
    }
    if (!state.table) {
        console.error('[PM] cancel: state.table is null — boot did not complete');
        toast('テーブル未初期化', 'error');
        return;
    }

    const ids = [...state.dirty.keys()];
    console.log('[PM] cancel restoring ids:', ids);

    // 1. Restore state.allRows from originals (mutate in place — keeps the
    //    same array reference Tabulator was given at init).
    ids.forEach(rowId => {
        const orig = state.originals.get(rowId);
        if (!orig) { console.warn('[PM]   no original for id', rowId); return; }
        const idx = state.allRows.findIndex(r => String(r.id) === String(rowId));
        if (idx >= 0) {
            // Mutate the existing object in-place rather than replacing the
            // array slot — Tabulator may hold direct references.
            const liveObj = state.allRows[idx];
            for (const k of Object.keys(liveObj)) delete liveObj[k];
            Object.assign(liveObj, JSON.parse(JSON.stringify(orig)));
        }
        state.weightUnit.delete(rowId);
    });

    // 2. Clear dirty markers BEFORE row.update fires rowFormatter, so it
    //    doesn't re-stamp pm-row-dirty on the restored rows.
    clearDirty();

    // 3. Push original data into each Tabulator row.
    let restored = 0, missing = 0, failed = 0;
    for (const rowId of ids) {
        const orig = state.originals.get(rowId);
        if (!orig) { missing++; continue; }

        const row = getRow(rowId);
        if (!row) {
            console.warn('[PM] cancel: row not found for id', rowId,
                '— attempting state.table.updateData fallback');
            try {
                const patch = JSON.parse(JSON.stringify(orig));
                await state.table.updateData([patch]);
                restored++;
                continue;
            } catch (err) {
                console.error('[PM]   updateData fallback failed:', err);
                missing++;
                continue;
            }
        }

        try {
            const patch = JSON.parse(JSON.stringify(orig));
            delete patch.id;                            // index column is read-only
            await row.update(patch);
            row.reformat();
            const el = row.getElement();
            el.classList.remove('pm-row-dirty');
            el.classList.toggle('pm-row-inactive', String(orig.status) === '0');
            restored++;
        } catch (err) {
            console.error('[PM] cancel: row.update failed for', rowId, err);
            failed++;
        }
    }
    console.log('[PM] ◀ bulkCancel done — restored:', restored,
                'missing:', missing, 'failed:', failed);

    recomputeSummary();
    syncDirtyUI();

    if (restored > 0)        toast(`キャンセル: ${restored} 件 復元`);
    else if (failed > 0)     toast('キャンセル失敗', 'error');
    else                     toast('復元する行がありません', 'error');
}

/* ══════════════════════════════════════════════════════
   12. CLIPBOARD
══════════════════════════════════════════════════════ */
function snap(r) {
    return {
        id:        r.id,
        name:      (r.original_product_name && String(r.original_product_name).trim()) || r.name || '',
        sku:       r.sku || '',
        stock:     r.stock,
        image_url: r.image_url || '',
    };
}

// Clipboard panel removed — `state.clipboard` is kept as a no-op Map so
// scattered `.clear() / .delete() / .set()` callsites stay harmless,
// and `renderClip()` is a no-op for the same reason.
function renderClip() { /* noop — clipboard UI removed */ }

/* ══════════════════════════════════════════════════════
   13. COLUMN VISIBILITY + WIDTH + ORDER + FILTERS  (per-user)

   All four are stored in localStorage under user-scoped keys
   (see PM_USER above). They are applied in this order at
   tableBuilt time:
     1. visibility   (hide cols the user previously hid)
     2. widths       (restore resized widths)
     3. header filters (re-populate per-column filter inputs)
   Column order is applied earlier — before new Tabulator(...) —
   by reordering the COLUMNS array, because Tabulator does not
   expose a runtime "move column to index" API in 6.x that
   round-trips cleanly with frozen columns.
══════════════════════════════════════════════════════ */
const loadHidden  = () => { try { return JSON.parse(localStorage.getItem(STORAGE.hidden) ||'{}'); } catch{ return {}; } };
const saveHidden  = m  => localStorage.setItem(STORAGE.hidden,  JSON.stringify(m));
const loadWidths  = () => { try { return JSON.parse(localStorage.getItem(STORAGE.widths) ||'{}'); } catch{ return {}; } };
const saveWidths  = m  => localStorage.setItem(STORAGE.widths,  JSON.stringify(m));
const loadOrder   = () => { try { const a = JSON.parse(localStorage.getItem(STORAGE.order)||'null'); return Array.isArray(a) ? a : null; } catch{ return null; } };
const saveOrder   = a  => localStorage.setItem(STORAGE.order,   JSON.stringify(a));
const loadFilters = () => { try { return JSON.parse(localStorage.getItem(STORAGE.filters)||'{}'); } catch{ return {}; } };
const saveFilters = m  => localStorage.setItem(STORAGE.filters, JSON.stringify(m));
const loadToolbar = () => { try { return JSON.parse(localStorage.getItem(STORAGE.toolbar)||'{}'); } catch{ return {}; } };
const saveToolbar = m  => localStorage.setItem(STORAGE.toolbar, JSON.stringify(m));

/* Reorder the COLUMNS array using a saved field-order list.
   Frozen "anchor" columns (_select, _delete) are kept at their
   ends regardless of saved order so the table stays sane. */
function applySavedColumnOrder(cols) {
    const order = loadOrder();
    if (!order || !order.length) return cols;
    const byField = new Map(cols.map(c => [c.field, c]));
    const anchorsLeft  = cols.filter(c => c.frozen && c.frozenPosition !== 'right');
    const anchorRight  = cols.find(c => c.frozenPosition === 'right');
    const anchorFields = new Set([...anchorsLeft.map(c => c.field), anchorRight?.field].filter(Boolean));
    const middle = [];
    order.forEach(f => { if (!anchorFields.has(f) && byField.has(f)) middle.push(byField.get(f)); });
    // Append any new columns the saved order didn't know about.
    cols.forEach(c => {
        if (!anchorFields.has(c.field) && !middle.includes(c) && c.field) middle.push(c);
    });
    const out = [...anchorsLeft, ...middle];
    if (anchorRight) out.push(anchorRight);
    return out;
}

function buildColMenu() {
    const menu = document.getElementById('pm-col-menu');
    menu.innerHTML = '';
    if (!state.table) return;
    state.table.getColumns().forEach(col => {
        const def = col.getDefinition();
        if (def.field === '_select' || !def.title) return;
        const lbl = document.createElement('label');
        const cb  = document.createElement('input');
        cb.type = 'checkbox'; cb.checked = col.isVisible();
        cb.addEventListener('change', () => {
            cb.checked ? col.show() : col.hide();
            const m = loadHidden(); cb.checked ? delete m[def.field] : (m[def.field] = true); saveHidden(m);
        });
        const sp = document.createElement('span'); sp.textContent = def.title;
        lbl.appendChild(cb); lbl.appendChild(sp);
        menu.appendChild(lbl);
    });
}

function applyColVisibility() {
    const m = loadHidden();
    Object.keys(m).forEach(f => { const c = state.table?.getColumn(f); if (c && m[f]) c.hide(); });
}
function applyColWidths() {
    const w = loadWidths();
    Object.keys(w).forEach(f => { const c = state.table?.getColumn(f); if (c) c.setWidth(w[f]); });
}
function applyHeaderFilters() {
    const f = loadFilters();
    const fields = Object.keys(f).filter(k => f[k] !== '' && f[k] != null);
    if (!fields.length) return;
    // Tabulator may not have header filter inputs mounted at tableBuilt; defer.
    setTimeout(() => {
        fields.forEach(field => {
            try { state.table?.setHeaderFilterValue(field, f[field]); }
            catch (e) { /* column may be hidden / removed */ }
        });
    }, 0);
}
function captureHeaderFilters() {
    if (!state.table) return;
    if (state.bootingFilters) return;     // don't overwrite saved during boot
    const out = {};
    state.table.getHeaderFilters().forEach(({ field, value }) => {
        if (value !== '' && value != null) out[field] = value;
    });
    saveFilters(out);
}

function wireColMenu() {
    // The open/toggle is wired via inline onclick on the button (calls
    // window.pm.toggleColMenu) because Bagisto/Vue ancestors stopPropagation
    // on click and kill addEventListener handlers — same reason save/cancel
    // use inline onclick. Here we only handle the outside-click close.
    const btn  = document.getElementById('pm-col-btn');
    const menu = document.getElementById('pm-col-menu');
    if (!btn || !menu) return;
    menu.addEventListener('click', e => e.stopPropagation());
    document.addEventListener('click', e => { if (!btn.contains(e.target) && !menu.contains(e.target)) menu.classList.remove('open'); });
}

/* ══════════════════════════════════════════════════════
   14. TABLE BUILD
══════════════════════════════════════════════════════ */
async function loadAndBuild() {
    const channel = document.getElementById('pm-channel').value;
    const search  = document.getElementById('pm-search').value;
    let payload;
    try {
        const res = await window.axios.get(`${API.all}?channel=${encodeURIComponent(channel)}&q=${encodeURIComponent(search)}&limit=3000`);
        payload = res.data;
    } catch (e) {
        console.error('[PM] load failed:', e);
        document.getElementById('pm-table').innerHTML = `<div style="padding:40px;text-align:center;color:#dc2626;">Error: ${e.response?.data?.message || e.message}</div>`;
        return;
    }

    state.allRows      = (payload.data || []).map(normalise);
    state.categoryOpts = payload.options?.categories || [];
    state.brandOpts    = payload.options?.brands     || [];
    state.sourceOpts   = payload.options?.sources    || [];
    state.weightUnit.clear();
    state.originals.clear();
    state.allRows.forEach(r => state.originals.set(String(r.id), JSON.parse(JSON.stringify(r))));
    clearDirty();

    state.table?.destroy();
    state.table = null;
    buildTable();
    populateBulkSelects();
    syncBulkBar();
}

function normalise(r) {
    if (r.status != null) r.status = String(r.status);
    if (r.brand_id != null) r.brand_id = String(r.brand_id);
    r.category_ids   = Array.isArray(r.category_ids) ? r.category_ids.map(String).filter(Boolean)
                     : r.category_ids ? String(r.category_ids).split(',').filter(Boolean) : [];
    r.category_names = Array.isArray(r.category_names) ? r.category_names : [];
    r.source_ids     = Array.isArray(r.source_ids) ? r.source_ids.map(String).filter(Boolean)
                     : r.source_ids ? String(r.source_ids).split(',').filter(Boolean) : [];
    return r;
}

function buildTable() {
    state.table = new Tabulator('#pm-table', {
        data:               state.allRows,
        columns:            applySavedColumnOrder(COLUMNS),
        layout:             'fitDataStretch',
        height:             'calc(100vh - 230px)',
        virtualDom:         true,
        virtualDomBuffer:   300,
        placeholder:        'No products',
        reactiveData:       false,
        movableColumns:     true,
        resizableColumnFit: false,
        persistence:        false,
        selectable:         true,
        rowHeight:          70,
        index:              'id',
        rowFormatter(row) {
            const d = row.getData(), el = row.getElement();
            el.classList.toggle('pm-row-inactive', String(d.status) === '0');
            el.classList.toggle('pm-row-dirty',    state.dirty.has(String(d.id)));
        },
    });
    state.table.on('tableBuilt', () => {
        applyColVisibility();
        applyColWidths();
        // Block capture while applyHeaderFilters re-pushes saved filter values
        // back into the inputs — otherwise dataFiltering races and saves an
        // empty snapshot, wiping the user's filters on next reload.
        state.bootingFilters = true;
        applyHeaderFilters();
        setTimeout(() => { state.bootingFilters = false; }, 600);
        buildColMenu();
        recomputeSummary();
    });
    state.table.on('cellEdited',    onCellEdited);
    state.table.on('columnResized', col => { const d = col.getDefinition(); if (!d.field) return; const m = loadWidths(); m[d.field] = col.getWidth(); saveWidths(m); });
    // Persist column drag order. Save the field list of every visible+hidden
    // column post-move, skipping the synthetic anchors which we always pin.
    state.table.on('columnMoved', () => {
        const fields = state.table.getColumns(false).map(c => c.getField()).filter(Boolean);
        saveOrder(fields);
    });
    // Per-column header filter changes are debounced and persisted.
    let _filtT;
    state.table.on('dataFiltering', () => {
        clearTimeout(_filtT);
        _filtT = setTimeout(captureHeaderFilters, 250);
    });
    state.table.on('dataFiltered',  recomputeSummary);
    state.table.on('rowSelected',   () => { syncBulkBar(); });
    state.table.on('rowDeselected', () => { syncBulkBar(); });
}

/* ══════════════════════════════════════════════════════
   BULK EDIT — apply status (active/inactive) to all selected rows.
   Reuses the same dirty-tracking path as cell edits, so the standard
   Save flow (POST bulk_update) writes the change.
══════════════════════════════════════════════════════ */
function populateBulkSelects() {
    const brandSel = document.getElementById('pm-bulk-brand');
    if (brandSel) {
        brandSel.innerHTML =
            '<option value="__skip__">— 変更なし —</option>' +
            '<option value="">(クリア)</option>' +
            state.brandOpts.map(b =>
                `<option value="${b.id}">${(b.label || '').replace(/</g,'&lt;')}</option>`
            ).join('');
    }
    const colorSel = document.getElementById('pm-bulk-color');
    if (colorSel) {
        colorSel.innerHTML =
            '<option value="__skip__">— 変更なし —</option>' +
            '<option value="">(クリア)</option>' +
            COLOR_OPTIONS.map(c =>
                `<option value="${c.id}">${String(c.label).replace(/</g,'&lt;')}</option>`
            ).join('');
    }
}
function syncBulkBar() {
    const bar = document.getElementById('pm-bulk-bar');
    const cnt = document.getElementById('pm-bulk-count');
    const sel = state.table?.getSelectedRows() || [];
    if (cnt) cnt.textContent = sel.length;
    if (bar) bar.classList.toggle('open', sel.length > 0);
    syncDirtyUI();
}
function applyBulkEdit() {
    if (!state.table) return;
    const sel = state.table.getSelectedRows();
    if (!sel.length) { toast('行を選択してください'); return; }

    const statusV   = document.getElementById('pm-bulk-status').value;
    const brandV    = document.getElementById('pm-bulk-brand').value;
    const colorV    = document.getElementById('pm-bulk-color').value;
    const weightRaw = document.getElementById('pm-bulk-weight').value;
    const buyPriceR = document.getElementById('pm-bulk-buyprice').value;
    const catPicked = state.bulkCatPicked.size > 0;

    // Build an ops list: each entry is { apiField, dataField, value, dirtyField }.
    // dirtyField is what bulk_update accepts; dataField mirrors what tabulator
    // shows in the row so the UI updates immediately.
    const ops = [];
    if (statusV !== '__skip__') {
        ops.push({ apply: (row, id) => {
            const v = String(statusV);
            row.update({ status: v });
            updateDirty(id, 'status', v, row.getCell('status')?.getElement());
            row.getElement().classList.toggle('pm-row-inactive', v === '0');
        }});
    }
    if (brandV !== '__skip__') {
        const idVal = brandV === '' ? null : parseInt(brandV, 10);
        const lbl   = idVal == null ? '' : (state.brandOpts.find(b => String(b.id) === String(idVal))?.label || '');
        ops.push({ apply: (row, id) => {
            row.update({ brand_id: idVal == null ? '' : String(idVal), brand: lbl });
            updateDirty(id, 'brand', idVal, row.getCell('brand_id')?.getElement() || row.getCell('brand')?.getElement());
        }});
    }
    if (colorV !== '__skip__') {
        const v = colorV === '' ? null : colorV;
        ops.push({ apply: (row, id) => {
            row.update({ color: v });
            updateDirty(id, 'color', v, row.getCell('color')?.getElement());
        }});
    }
    if (weightRaw !== '' && weightRaw != null) {
        const w = parseFloat(weightRaw);
        if (!isNaN(w) && w >= 0) {
            ops.push({ apply: (row, id) => {
                row.update({ weight: w });
                updateDirty(id, 'weight', w, row.getCell('weight')?.getElement());
            }});
        }
    }
    if (buyPriceR !== '' && buyPriceR != null) {
        const bp = parseFloat(buyPriceR);
        if (!isNaN(bp) && bp >= 0) {
            ops.push({ apply: (row, id) => {
                row.update({ buy_price: bp });
                updateDirty(id, 'buy_price', bp, row.getCell('buy_price')?.getElement());
            }});
        }
    }
    if (catPicked) {
        const ids   = Array.from(state.bulkCatPicked).map(v => parseInt(v, 10)).filter(Boolean);
        const names = ids.map(cid => (state.categoryOpts.find(c => String(c.id) === String(cid))||{}).name).filter(Boolean);
        ops.push({ apply: (row, id) => {
            row.update({
                category_ids:   ids.map(String),
                category_names: names,
                category_name:  names[0] || '',
            });
            // bulk_update's `categories` key replaces all assignments; empty array
            // sentinel is `['']` per existing inline-edit convention.
            updateDirty(id, 'categories', ids.length ? ids : [''], row.getCell('categories')?.getElement());
        }});
    }

    if (!ops.length) { toast('変更する項目を選択してください'); return; }

    let touched = 0;
    sel.forEach(row => {
        const id = String(row.getData().id);
        ops.forEach(op => op.apply(row, id));
        row.reformat();
        touched++;
    });

    recomputeSummary();
    syncDirtyUI();
    toast(`${touched} 行に ${ops.length} 項目を適用しました — 保存してください`);
}

/* ── Category picker modal ────────────────────────────────────────── */
function renderCategoryModalList(filter) {
    const wrap = document.getElementById('pm-cat-modal-list');
    if (!wrap) return;
    const q = (filter || '').trim().toLowerCase();
    const rows = state.categoryOpts
        .filter(c => !q || String(c.name || '').toLowerCase().includes(q))
        .map(c => {
            const id      = String(c.id);
            const checked = state.bulkCatPicked.has(id) ? 'checked' : '';
            const label   = (c.name || ('#' + c.id)).replace(/</g, '&lt;');
            return `<label data-id="${id}"><input type="checkbox" ${checked} onchange="window.pm.toggleCatPick('${id}', this.checked)"><span>${label}</span></label>`;
        })
        .join('');
    wrap.innerHTML = rows || '<div style="padding:24px;text-align:center;color:#94a3b8;">該当なし</div>';
}
function syncCategoryModalCount() {
    const n  = state.bulkCatPicked.size;
    const lb = document.getElementById('pm-cat-modal-picked');
    if (lb) lb.textContent = `(${n} 件)`;
    const btn = document.getElementById('pm-bulk-cat-count');
    if (btn) {
        if (n > 0) { btn.style.display = 'inline-block'; btn.textContent = String(n); }
        else       { btn.style.display = 'none'; }
    }
}
function openCategoryModal() {
    const m = document.getElementById('pm-cat-modal');
    if (!m) return;
    m.classList.add('open');
    const search = document.getElementById('pm-cat-modal-search');
    if (search) search.value = '';
    renderCategoryModalList('');
    syncCategoryModalCount();
    setTimeout(() => search?.focus(), 50);
}
function closeCategoryModal() {
    document.getElementById('pm-cat-modal')?.classList.remove('open');
}
function confirmCategoryModal() {
    syncCategoryModalCount();
    closeCategoryModal();
    if (state.bulkCatPicked.size > 0) {
        toast(`${state.bulkCatPicked.size} カテゴリを選択 — 「適用」で反映`);
    }
}
function toggleCatPick(id, checked) {
    if (checked) state.bulkCatPicked.add(String(id));
    else         state.bulkCatPicked.delete(String(id));
    syncCategoryModalCount();
}
function catModalSelectAll() {
    // Only the rows currently visible (after the search filter)
    document.querySelectorAll('#pm-cat-modal-list label').forEach(el => {
        const id = el.getAttribute('data-id');
        if (id) {
            state.bulkCatPicked.add(id);
            const cb = el.querySelector('input');
            if (cb) cb.checked = true;
        }
    });
    syncCategoryModalCount();
}
function catModalClear() {
    state.bulkCatPicked.clear();
    document.querySelectorAll('#pm-cat-modal-list input[type="checkbox"]').forEach(cb => cb.checked = false);
    syncCategoryModalCount();
}

/* ══════════════════════════════════════════════════════
   15. TOAST
══════════════════════════════════════════════════════ */
let _toastTimer;
function toast(msg, kind) {
    const el = document.getElementById('pm-toast');
    el.textContent = msg;
    el.className = 'show' + (kind === 'error' ? ' error' : '');
    clearTimeout(_toastTimer);
    _toastTimer = setTimeout(() => { el.className = ''; }, 2800);
}

/* ══════════════════════════════════════════════════════
   16. TOOLBAR WIRING
   NOTE: We gate on state.dirty.size / state.saving ONLY.
   data-disabled is visual only — it must never block the
   actual click because mousedown→blur→cellEdited runs
   synchronously BEFORE the click event fires.
══════════════════════════════════════════════════════ */
function wireToolbar() {
    // Save/Cancel buttons use inline `onclick` in the HTML (calls window.pm.*).
    // We do NOT addEventListener('click', ...) on them, because Bagisto / Vue
    // somewhere up the tree calls stopPropagation on click events, killing
    // delegation. Inline onclick fires synchronously on the element itself.

    // Restore previously-saved toolbar inputs (search + channel) BEFORE we
    // wire the listeners, so the first load uses them and the listeners
    // don't double-fire on initial assignment.
    const saved = loadToolbar();
    const searchEl = document.getElementById('pm-search');
    const chanEl   = document.getElementById('pm-channel');
    const cargoEl  = document.getElementById('pm-cargo-rate');
    if (searchEl && typeof saved.search === 'string') searchEl.value = saved.search;
    if (chanEl   && saved.channel) {
        // Only restore if the option still exists — channels may have been
        // renamed/removed since the prefs were stored.
        const has = Array.from(chanEl.options).some(o => o.value === saved.channel);
        if (has) chanEl.value = saved.channel;
    }
    if (cargoEl  && saved.cargo != null && saved.cargo !== '') cargoEl.value = saved.cargo;

    const persistToolbar = () => saveToolbar({
        search:  searchEl?.value || '',
        channel: chanEl?.value   || '',
        cargo:   cargoEl?.value  || '',
    });

    // Search
    let _searchT;
    searchEl.addEventListener('input', e => {
        clearTimeout(_searchT);
        _searchT = setTimeout(() => {
            if (!state.table) return;
            // A typed search overrides the duplicate filter — reset the toggle
            // state and de-highlight the 重複 button so the next click re-runs.
            if (state.dupActive) {
                state.dupActive = false;
                const b = document.getElementById('pm-find-dupes');
                if (b) { b.style.background = ''; b.style.borderColor = ''; b.style.color = ''; }
            }
            const q = e.target.value;
            if (!q) { state.table.clearFilter(true); persistToolbar(); return; }
            state.table.setFilter([[
                { field:'name',                  type:'like', value:q },
                { field:'sku',                   type:'like', value:q },
                { field:'original_product_name', type:'like', value:q },
            ]]);
            persistToolbar();
        }, 200);
    });

    // Channel switch already triggers pmRebuild() inline; just persist.
    chanEl?.addEventListener('change', persistToolbar);

    cargoEl.addEventListener('input', () => {
        state.table?.redraw(true);
        recomputeSummary();
        persistToolbar();
    });

    window.addEventListener('beforeunload', e => {
        if (state.dirty.size) { e.preventDefault(); e.returnValue = ''; }
    });
}

/* ──────────────────────────────────────────────────────────────
   Public API — bound to inline `onclick` attributes on the
   save/cancel buttons. These are the only entry points; each
   commits any pending in-cell edit, defers one task, then runs.
────────────────────────────────────────────────────────────── */
function commitPendingEdit() {
    const ae = document.activeElement;
    if (ae && ['INPUT','SELECT','TEXTAREA'].includes(ae.tagName)) ae.blur();
}

/* ──────────────────────────────────────────────────────────────
   DELETE / DUPLICATE FINDER
   - pmDeleteOne   : single-row trash icon (admin/catalog/products/edit/{id})
   - pmBulkDelete  : selected rows, uses Bagisto's mass-delete endpoint
   - pmFindDuplicates: filter the table to rows whose SKU appears more
                      than once OR whose name+price collides exactly
────────────────────────────────────────────────────────────── */
async function pmDeleteOne(row) {
    const d = row.getData();
    if (!d || !d.id) return;
    if (!confirm(`削除しますか？\n\n${d.name || d.sku || '#'+d.id}\n\nこの操作は元に戻せません。`)) return;
    const url = `/admin/catalog/products/edit/${d.id}`;
    try {
        await window.axios.delete(url, { headers: { 'X-CSRF-TOKEN': API.csrf, 'X-Requested-With':'XMLHttpRequest' } });
        row.delete();
        state.allRows = state.allRows.filter(r => String(r.id) !== String(d.id));
        state.originals.delete(String(d.id));
        state.clipboard.delete(String(d.id));
        toast('削除しました: ' + (d.sku || d.name || '#'+d.id));
        recomputeSummary();
    } catch (e) {
        console.error('[PM] delete failed', e);
        toast('削除失敗: ' + (e.response?.data?.message || e.message), 'error');
    }
}

async function pmBulkDelete() {
    if (!state.table) return;
    const sel = state.table.getSelectedRows();
    if (!sel.length) { toast('行を選択してください'); return; }
    const ids = sel.map(r => r.getData().id).filter(Boolean);
    if (!confirm(`${ids.length} 件の商品を削除しますか？\n\nこの操作は元に戻せません。`)) return;
    try {
        const fd = new FormData();
        // Bagisto's MassDestroyRequest expects indices[] (array of ids).
        ids.forEach(id => fd.append('indices[]', id));
        fd.append('_token', API.csrf);
        await window.axios.post('/admin/catalog/products/mass-delete', fd, {
            headers: { 'X-CSRF-TOKEN': API.csrf, 'X-Requested-With':'XMLHttpRequest' }
        });
        const idSet = new Set(ids.map(String));
        sel.forEach(r => r.delete());
        state.allRows = state.allRows.filter(r => !idSet.has(String(r.id)));
        ids.forEach(id => { state.originals.delete(String(id)); state.clipboard.delete(String(id)); });
        toast(`${ids.length} 件削除しました`);
        recomputeSummary();
        renderClip();
    } catch (e) {
        console.error('[PM] bulk delete failed', e);
        toast('削除失敗: ' + (e.response?.data?.message || e.message), 'error');
    }
}

function pmFindDuplicates() {
    if (!state.table) return;
    const btn = document.getElementById('pm-find-dupes');
    // Toggle: second click clears the duplicate filter (and search input).
    if (state.dupActive) {
        state.table.clearFilter(true);
        const s = document.getElementById('pm-search'); if (s) s.value = '';
        state.dupActive = false;
        if (btn) { btn.style.background = ''; btn.style.borderColor = ''; btn.style.color = ''; }
        toast('重複フィルタを解除しました');
        return;
    }
    // Group by SKU (case-insensitive, trimmed). Empty SKUs aren't compared.
    // Also flag exact (name + price) collisions for products with no SKU.
    const bySku = new Map();   // sku -> count
    const byNP  = new Map();   // name|price -> count
    for (const r of state.allRows) {
        const sku = String(r.sku || '').trim().toLowerCase();
        if (sku) bySku.set(sku, (bySku.get(sku) || 0) + 1);
        const np = (String(r.name||'').trim().toLowerCase()) + '|' + String(r.price||'');
        if (r.name) byNP.set(np, (byNP.get(np) || 0) + 1);
    }
    const dupSku = new Set(); for (const [k, n] of bySku) if (n > 1) dupSku.add(k);
    const dupNP  = new Set(); for (const [k, n] of byNP)  if (n > 1) dupNP.add(k);

    if (!dupSku.size && !dupNP.size) {
        state.table.clearFilter(true);
        toast('重複は見つかりませんでした');
        return;
    }
    state.table.setFilter((data) => {
        const sku = String(data.sku || '').trim().toLowerCase();
        if (sku && dupSku.has(sku)) return true;
        const np = (String(data.name||'').trim().toLowerCase()) + '|' + String(data.price||'');
        if (data.name && dupNP.has(np)) return true;
        return false;
    });
    state.dupActive = true;
    if (btn) { btn.style.background = '#fde68a'; btn.style.borderColor = '#f59e0b'; btn.style.color = '#92400e'; }
    const total = (state.table.getDataCount('active') || 0);
    toast(`${total} 件の重複候補を表示中（もう一度押すと解除）`);
}

window.pm = {
    state,
    save: () => {
        console.log('[PM] window.pm.save() invoked');
        commitPendingEdit();
        setTimeout(() => {
            console.log('[PM] save runSoon — dirty:', state.dirty.size, 'saving:', state.saving);
            if (state.saving)      { toast('保存中…');           return; }
            if (!state.dirty.size) { toast('変更がありません');   return; }
            bulkSave();
        }, 0);
    },
    cancel: () => {
        console.log('[PM] window.pm.cancel() invoked');
        commitPendingEdit();
        setTimeout(() => {
            const selCount = state.table?.getSelectedRows().length || 0;
            console.log('[PM] cancel runSoon — dirty:', state.dirty.size,
                        'selected:', selCount, 'table?', !!state.table);
            if (state.dirty.size) { bulkCancel(); return; }
            if (selCount) {
                state.table.deselectRow();
                state.clipboard.clear();
                renderClip();
                syncBulkBar();
                toast(`選択を解除しました (${selCount} 件)`);
                return;
            }
            toast('変更がありません');
        }, 0);
    },
    applyBulk:           () => applyBulkEdit(),
    openCategoryModal:   () => openCategoryModal(),
    closeCategoryModal:  () => closeCategoryModal(),
    confirmCategoryModal:() => confirmCategoryModal(),
    toggleCatPick:       (id, checked) => toggleCatPick(id, checked),
    catModalSelectAll:   () => catModalSelectAll(),
    catModalClear:       () => catModalClear(),
    clearSelection: () => {
        if (!state.table) return;
        const n = state.table.getSelectedRows().length;
        state.table.deselectRow();
        state.clipboard.clear();
        renderClip();
        syncBulkBar();
        if (n) toast(`選択を解除しました (${n} 件)`);
    },
    bulkDelete: () => pmBulkDelete(),
    findDuplicates: () => pmFindDuplicates(),
    toggleColMenu: (e) => {
        if (e) { e.preventDefault(); e.stopPropagation(); }
        const menu = document.getElementById('pm-col-menu');
        if (!menu) return;
        // Rebuild the checkbox list every open so it reflects current
        // visibility (esp. after a column was hidden via the menu).
        buildColMenu();
        menu.classList.toggle('open');
    },
    /* Wipe all per-user layout prefs (visibility / widths / order / header
       filters / toolbar) and reload. Call from the browser console:
         window.pm.resetPrefs()
       Useful if a saved layout becomes invalid (e.g. column removed). */
    resetPrefs: () => {
        Object.values(STORAGE).forEach(k => localStorage.removeItem(k));
        toast('レイアウト設定をリセットしました');
        setTimeout(() => location.reload(), 400);
    },
    dump: () => ({
        dirty:     [...state.dirty.entries()],
        originals: state.originals.size,
        allRows:   state.allRows.length,
        tableRows: state.table?.getRows().length,
        saving:    state.saving,
    }),
};
console.log('[PM] window.pm exposed — pm.save() / pm.cancel() / pm.dump()');

/* ══════════════════════════════════════════════════════
   17. BOOT
══════════════════════════════════════════════════════ */
window.pmRebuild = () => loadAndBuild();

async function boot() {
    const t0 = Date.now();
    while (!window.axios) {
        if (Date.now() - t0 > 8000) { console.error('[PM] axios timeout'); return; }
        await new Promise(r => setTimeout(r, 50));
    }
    if (typeof Tabulator === 'undefined') { console.error('[PM] Tabulator not loaded'); return; }

    // Filters reset on every page load. Layout prefs (column hidden/widths/
    // order) are kept; only the search query and per-column header filters
    // are cleared so the user always starts from a clean slate.
    try { localStorage.removeItem(STORAGE.filters); } catch(_) {}
    try {
        const tb = JSON.parse(localStorage.getItem(STORAGE.toolbar) || '{}');
        delete tb.search;
        localStorage.setItem(STORAGE.toolbar, JSON.stringify(tb));
    } catch(_) {}

    wireToolbar();
    wireColMenu();
    wireStatusFilter();
    wireCategoryModal();
    await loadAndBuild();
    console.log('[PM] boot complete — rows:', state.allRows.length);
}

/* Top-toolbar status filter: drives the existing column header filter on
   the `status` field, so existing filter logic + persistence path is
   reused without duplicating filter state. */
function wireStatusFilter() {
    const sel = document.getElementById('pm-status-filter');
    if (!sel) return;
    sel.addEventListener('change', () => {
        if (!state.table) return;
        try { state.table.setHeaderFilterValue('status', sel.value); }
        catch (_) { /* column might be hidden */ }
    });
}

function wireCategoryModal() {
    const modal = document.getElementById('pm-cat-modal');
    const search = document.getElementById('pm-cat-modal-search');
    if (search) {
        let _t;
        search.addEventListener('input', () => {
            clearTimeout(_t);
            _t = setTimeout(() => renderCategoryModalList(search.value), 80);
        });
    }
    if (modal) {
        // Click on backdrop (the modal element itself, not its card) closes.
        modal.addEventListener('click', e => { if (e.target === modal) closeCategoryModal(); });
    }
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && modal?.classList.contains('open')) closeCategoryModal();
    });
}

document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', boot)
    : boot();

/* ── BARCODE SCAN: locate the row in this Tabulator, scroll, flash.
   barcode-scanner partial does the lookup and dispatches this event.
   If the product isn't in the current channel slice, pin #pm-search to
   the SKU and reload, then highlight on the next tick. ── */
window.addEventListener('pos:barcode-resolved', function (ev) {
    const d = ev.detail || {};
    if (!d.found || !d.product) return; // partial already beeped + showed status

    const pid = String(d.product.id);
    const sku = d.product.sku || '';

    function flashRow() {
        if (!state.table) return false;
        const row = getRow(pid);
        if (!row) return false;
        try {
            state.table.deselectRow();
            row.select();
            state.table.scrollToRow(row, 'center', false);
        } catch (_) {}
        const el = row.getElement();
        if (el) {
            el.classList.add('bcs-hit');
            setTimeout(() => el.classList.remove('bcs-hit'), 1600);
        }
        return true;
    }

    if (flashRow()) return;

    // Not in the loaded slice — pin SKU to the search box and reload.
    const searchEl = document.getElementById('pm-search');
    if (searchEl && sku) searchEl.value = sku;
    if (typeof loadAndBuild === 'function') {
        Promise.resolve(loadAndBuild()).then(() => setTimeout(flashRow, 80));
    }
});

})();
</script>
@endpush
</x-admin::layouts>
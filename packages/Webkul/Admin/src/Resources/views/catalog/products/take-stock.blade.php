{{--
  TAKE STOCK — physical stocktake session UI.
  Big-image cards, type counted qty, see diff vs system, "Apply All"
  records a session in pos_stocktake_sessions/items + bumps stock via update_inline.
--}}
<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.products.index.title') — Take Stock
    </x-slot>

@include('admin::catalog.products.partials.view-nav')
@include('admin::catalog.products.partials.barcode-scanner')
@include('admin::catalog.products.partials.reservations-modal')

@push('styles')
<style>
@media(max-width:768px){
  body [class*="min-h-[calc(100vh-62px)]"]{
    padding-left:0 !important;
    padding-right:0 !important;
  }
  #tks-container{ overflow-x:hidden; }
}
#tks-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    padding: 8px;
    width: 100%;
    box-sizing: border-box;
}
#tks-grid.cols-1 { grid-template-columns: 1fr; }
#tks-grid.cols-2 { grid-template-columns: repeat(2, 1fr); }
#tks-grid.cols-3 { grid-template-columns: repeat(3, 1fr); }

#tks-grid .tks-placeholder { grid-column: 1 / -1; }

.tks-card {
    width: 100%;
    display: flex;
    flex-direction: column;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
    box-sizing: border-box;
    position: relative;
    transition: box-shadow .2s;
}
.tks-card.is-counted { border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37,99,235,.12); }

.tks-card__img,
.tks-card__no-img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    display: block;
    background: #f3f4f6;
    position: relative;
}
.tks-card__no-img {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    font-size: 11px;
}
#tks-grid.cols-1 .tks-card__img,
#tks-grid.cols-1 .tks-card__no-img { height: 280px; }
#tks-grid.cols-3 .tks-card__img,
#tks-grid.cols-3 .tks-card__no-img { height: 160px; }

.tks-badge-sys {
    position: absolute; top: 6px; left: 6px;
    background: rgba(0,0,0,.7); color: #fff;
    padding: 4px 10px; border-radius: 6px;
    font-size: 18px; font-weight: 800;
    line-height: 1.1;
}
.tks-badge-diff {
    position: absolute; top: 6px; right: 6px;
    color: #fff;
    padding: 4px 10px; border-radius: 6px;
    font-size: 18px; font-weight: 800;
    line-height: 1.1;
}
.tks-badge-diff.pos { background: #16a34a; }
.tks-badge-diff.neg { background: #ef4444; }
.tks-badge-diff.zero { background: #6b7280; }

.tks-card__body {
    flex: 1;
    min-width: 0;
    padding: 10px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    box-sizing: border-box;
}
.tks-card__name {
    font-size: 14px;
    line-height: 1.3;
    font-weight: 700;
    color: #1f2937;
    word-break: break-word;
    overflow-wrap: anywhere;
    margin: 0;
}
.tks-card__sku {
    font-size: 11px;
    line-height: 1.2;
    color: #9ca3af;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin: 0;
}
.tks-card__row {
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.tks-card__row button {
    width: 36px; height: 36px;
    flex-shrink: 0;
    border-radius: 8px; border: 0;
    font-size: 20px; font-weight: 700;
    cursor: pointer;
}
.tks-minus { background: #fee2e2; color: #dc2626; }
.tks-plus  { background: #dcfce7; color: #16a34a; }
.tks-card__row input {
    flex: 1; min-width: 0;
    height: 36px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    text-align: center;
    font-size: 18px; font-weight: 700;
    color: #1f2937;
    outline: none;
    box-sizing: border-box;
}
.tks-card__row input:focus { border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37,99,235,.18); }

.tks-cols-btn {
    display:inline-flex;align-items:center;justify-content:center;
    width:36px;height:36px;border-radius:6px;
    border:1px solid #d1d5db;background:#fff;color:#374151;
    cursor:pointer;transition:all .15s;
}
.tks-cols-btn:hover{background:#f3f4f6;}
.tks-cols-btn.is-active{background:#2563eb;color:#fff;border-color:#1d4ed8;}

.tks-tab-btn{display:inline-flex;align-items:center;gap:4px;border:1.5px solid #2563eb;background:#fff;color:#2563eb;}
.tks-tab-btn:hover{background:#eff6ff;}
.tks-tab-btn.is-active{background:#2563eb;color:#fff;}

.dark .tks-card { background: #111827; border-color: #374151; }
.dark .tks-card__name { color: #f3f4f6; }
.dark .tks-card__img,
.dark .tks-card__no-img { background: #1f2937; }
.dark .tks-card__row input { background:#1f2937; color:#f9fafb; border-color:#4b5563; }
</style>
@endpush

<div id="tks-container" class="relative z-0">

  <div id="tks-header" class="sticky top-0 z-20 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-md shadow-sm">

    <div class="px-4 pt-3 pb-2 flex items-center justify-between gap-2">
      <h1 class="text-lg sm:text-xl font-bold text-gray-800 dark:text-white">Take Stock</h1>
      <div class="flex gap-1 flex-shrink-0">
        <button type="button" class="tks-cols-btn" data-cols="1" onclick="tksSetCols(1)" title="1 column">
          <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><rect x="3" y="3" width="14" height="14" rx="1.5"/></svg>
        </button>
        <button type="button" class="tks-cols-btn is-active" data-cols="2" onclick="tksSetCols(2)" title="2 columns">
          <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><rect x="2" y="3" width="7" height="14" rx="1"/><rect x="11" y="3" width="7" height="14" rx="1"/></svg>
        </button>
        <button type="button" class="tks-cols-btn" data-cols="3" onclick="tksSetCols(3)" title="3 columns">
          <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><rect x="2" y="3" width="4" height="14" rx="1"/><rect x="8" y="3" width="4" height="14" rx="1"/><rect x="14" y="3" width="4" height="14" rx="1"/></svg>
        </button>
      </div>
    </div>

    <div class="px-4 pb-2 flex gap-2 overflow-x-auto">
      <button type="button" class="tks-tab-btn is-active px-4 py-1.5 rounded-md text-sm font-semibold whitespace-nowrap" data-tab="count" onclick="tksSetTab('count')">Count</button>
      <button type="button" class="tks-tab-btn px-4 py-1.5 rounded-md text-sm font-semibold whitespace-nowrap" data-tab="history" onclick="tksSetTab('history')">History</button>
    </div>

    <div class="px-4 py-3 space-y-2 sm:space-y-0 sm:flex sm:gap-2 sm:flex-wrap" id="tks-filter-row">
      <input type="text" id="tks-search" placeholder="@lang('admin::app.catalog.products.views.search-placeholder')"
        class="w-full sm:flex-1 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-blue-400"
        oninput="tksFilterList()">
      <select id="tks-channel"
              class="custom-select w-full sm:w-auto rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 pr-8 text-sm text-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-blue-400"
              onchange="tksLoadProducts()">
        @foreach(core()->getAllChannels() as $ch)
          <option value="{{ $ch->code }}" {{ $ch->code === core()->getCurrentChannelCode() ? 'selected' : '' }}>{{ $ch->name }}</option>
        @endforeach
      </select>
      <button type="button" onclick="tksFillSystem()" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-xs font-semibold text-gray-700 dark:text-white">Fill from System</button>
      <button type="button" onclick="tksClearCounts()" class="rounded-md border border-red-300 bg-white px-3 py-2 text-xs font-semibold text-red-600">Clear</button>
    </div>

    <div class="px-4 pb-3 flex flex-wrap gap-2 items-center" id="tks-extra-filter-row">
      <select id="tks-status-filter"
              class="custom-select rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 pr-8 text-sm text-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-blue-400"
              onchange="tksFilterList()">
        <option value="all">All</option>
        <option value="active">Active only</option>
        <option value="inactive">Inactive only</option>
      </select>
      <input type="number" id="tks-stock-lt" min="0" placeholder="Stock <"
             class="w-24 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-blue-400"
             oninput="tksFilterList()">
      <select id="tks-sort"
              class="custom-select rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 pr-8 text-sm text-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-blue-400"
              onchange="tksFilterList()">
        <option value="default">Sort: default</option>
        <option value="name_asc">Name ↑</option>
        <option value="name_desc">Name ↓</option>
        <option value="stock_asc">Stock ↑</option>
        <option value="stock_desc">Stock ↓</option>
        <option value="status_asc">Active first</option>
        <option value="status_desc">Inactive first</option>
      </select>
    </div>

    <div class="px-4 pb-3 grid grid-cols-4 gap-2" id="tks-summary-row">
      <div class="bg-blue-50 dark:bg-blue-900/20 rounded-md p-2 border border-blue-200 dark:border-blue-800">
        <div class="text-[10px] font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wide">Products</div>
        <div class="text-lg font-black text-blue-700 dark:text-blue-300" id="tks-tot-products">0</div>
      </div>
      <div class="bg-gray-50 dark:bg-gray-900/20 rounded-md p-2 border border-gray-200 dark:border-gray-700">
        <div class="text-[10px] font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">System Qty</div>
        <div class="text-lg font-black text-gray-700 dark:text-gray-300" id="tks-tot-system">0</div>
      </div>
      <div class="bg-cyan-50 dark:bg-cyan-900/20 rounded-md p-2 border border-cyan-200 dark:border-cyan-800">
        <div class="text-[10px] font-semibold text-cyan-600 dark:text-cyan-400 uppercase tracking-wide">Counted Qty</div>
        <div class="text-lg font-black text-cyan-700 dark:text-cyan-300" id="tks-tot-counted">0</div>
      </div>
      <div class="bg-amber-50 dark:bg-amber-900/20 rounded-md p-2 border border-amber-200 dark:border-amber-800">
        <div class="text-[10px] font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wide">Diff</div>
        <div class="text-lg font-black text-amber-700 dark:text-amber-300" id="tks-tot-diff">0</div>
      </div>
    </div>
  </div>

  {{-- COUNT TAB --}}
  <div id="tks-count-pane">
    <div id="tks-grid">
      <div class="tks-placeholder text-center py-12 text-gray-500">@lang('admin::app.catalog.products.views.loading')</div>
    </div>
    <div style="height:5rem;"></div>
  </div>

  {{-- HISTORY TAB --}}
  <div id="tks-history-pane" class="hidden p-3">
    <div id="tks-history-list" class="bg-white dark:bg-gray-900 rounded-md border border-gray-200 dark:border-gray-800 divide-y divide-gray-100 dark:divide-gray-800">
      <div class="text-center py-8 text-gray-500 text-sm">@lang('admin::app.catalog.products.views.loading')</div>
    </div>
  </div>
</div>

{{-- Bottom Apply bar --}}
<div id="tks-apply-bar"
     class="hidden fixed left-0 right-0 bottom-0 z-[9500] bg-blue-600 text-white shadow-2xl border-t-4 border-blue-800
            flex items-center justify-center gap-3 py-3 px-4">
  <span id="tks-apply-info" class="font-bold text-sm">0 items counted</span>
  <button type="button" id="tks-apply-btn" class="bg-white text-blue-700 font-bold px-5 py-2 rounded hover:bg-blue-50">Apply All</button>
  <button type="button" onclick="tksClearCounts()" class="bg-blue-800 text-white px-5 py-2 rounded hover:bg-blue-900">Clear</button>
</div>

<div id="tks-toast"
     class="fixed bottom-20 right-4 z-50 rounded-lg px-4 py-3 text-white text-sm font-semibold shadow-xl opacity-0 transform translate-y-2 pointer-events-none transition-all duration-300">
</div>

@push('scripts')
<script>
(function() {
  'use strict';

  const API_ALL       = "{{ route('admin.catalog.products.all') }}";
  const API_UPDATE    = "{{ route('admin.catalog.products.update_inline') }}";
  const API_SESSIONS  = "{{ route('admin.catalog.stocktake.sessions') }}";
  const API_SAVE_SESS = "{{ route('admin.catalog.stocktake.session_store') }}";

  let allProducts = [];
  /** @type Map<string,number|null> */
  const counts = new Map();   // productId -> counted qty (null if cleared)

  document.addEventListener('DOMContentLoaded', () => {
    let saved = 2;
    try { const v = parseInt(localStorage.getItem('tks-cols')||'',10); if (v>=1 && v<=3) saved = v; } catch(_) {}
    tksSetCols(saved);
    tksLoadProducts();
    document.getElementById('tks-apply-btn').addEventListener('click', tksApplyAll);
  });

  function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  window.tksSetTab = function(tab) {
    document.querySelectorAll('.tks-tab-btn').forEach(b =>
      b.classList.toggle('is-active', b.dataset.tab === tab));
    document.getElementById('tks-count-pane').classList.toggle('hidden', tab !== 'count');
    document.getElementById('tks-history-pane').classList.toggle('hidden', tab !== 'history');
    document.getElementById('tks-filter-row').classList.toggle('hidden', tab !== 'count');
    const extraRow = document.getElementById('tks-extra-filter-row');
    if (extraRow) extraRow.classList.toggle('hidden', tab !== 'count');
    document.getElementById('tks-summary-row').classList.toggle('hidden', tab !== 'count');
    if (tab === 'history') tksLoadHistory();
  };

  window.tksSetCols = function(n) {
    const grid = document.getElementById('tks-grid');
    if (!grid) return;
    grid.classList.remove('cols-1','cols-2','cols-3');
    grid.classList.add('cols-'+n);
    document.querySelectorAll('.tks-cols-btn').forEach(b =>
      b.classList.toggle('is-active', String(b.dataset.cols) === String(n)));
    try { localStorage.setItem('tks-cols', String(n)); } catch(_) {}
  };

  window.tksLoadProducts = async function() {
    const channel = document.getElementById('tks-channel').value;
    document.getElementById('tks-grid').innerHTML =
      `<div class="tks-placeholder text-center py-12 text-gray-500">Loading...</div>`;
    try {
      const res = await window.axios.get(`${API_ALL}?channel=${encodeURIComponent(channel)}&limit=500`);
      allProducts = res.data.data || [];
      counts.clear();
      tksFilterList();
    } catch (err) {
      document.getElementById('tks-grid').innerHTML =
        `<div class="tks-placeholder text-center py-12 text-gray-500">Failed to load</div>`;
    }
  };

  window.tksFilterList = function() {
    const q       = document.getElementById('tks-search').value.toLowerCase().trim();
    const statusEl= document.getElementById('tks-status-filter');
    const status  = statusEl ? statusEl.value : 'all';
    const ltEl    = document.getElementById('tks-stock-lt');
    const ltRaw   = ltEl ? ltEl.value : '';
    const ltNum   = ltRaw === '' ? null : parseInt(ltRaw, 10);
    const ltActive= ltNum !== null && !isNaN(ltNum);
    const sortEl  = document.getElementById('tks-sort');
    const sortKey = sortEl ? sortEl.value : 'default';

    let filtered = allProducts.filter(p => {
      if (q && !((p.name||'').toLowerCase().includes(q) || (p.sku||'').toLowerCase().includes(q))) return false;
      if (status === 'active'   && String(p.status) !== '1') return false;
      if (status === 'inactive' && String(p.status) === '1') return false;
      if (ltActive && (parseFloat(p.stock)||0) >= ltNum) return false;
      return true;
    });

    if (sortKey !== 'default') {
      const cmpName  = (a,b) => (a.name||'').localeCompare(b.name||'');
      const cmpStock = (a,b) => (parseFloat(a.stock)||0) - (parseFloat(b.stock)||0);
      const cmpStat  = (a,b) => (parseInt(b.status)||0) - (parseInt(a.status)||0);
      filtered = filtered.slice().sort({
        name_asc:    cmpName,
        name_desc:   (a,b) => -cmpName(a,b),
        stock_asc:   cmpStock,
        stock_desc:  (a,b) => -cmpStock(a,b),
        status_asc:  cmpStat,
        status_desc: (a,b) => -cmpStat(a,b),
      }[sortKey]);
    }
    tksRender(filtered);
    tksUpdateTotals();
  };

  function tksRender(list) {
    const grid = document.getElementById('tks-grid');
    if (list.length === 0) {
      grid.innerHTML = `<div class="tks-placeholder text-center py-12 text-gray-500">@lang('admin::app.catalog.products.views.no-products')</div>`;
      return;
    }
    grid.innerHTML = list.map(p => {
      const sys = parseInt(p.stock||0, 10);
      const cnt = counts.has(String(p.id)) ? counts.get(String(p.id)) : null;
      const diff = (cnt === null || cnt === undefined) ? null : (cnt - sys);
      const diffClass = diff === null ? 'zero' : (diff > 0 ? 'pos' : (diff < 0 ? 'neg' : 'zero'));
      const diffText  = diff === null ? '—' : ((diff > 0 ? '+' : '') + diff);
      const imgHtml = p.image_url
        ? `<img class="tks-card__img" src="${esc(p.image_url)}" loading="lazy" alt="">`
        : `<div class="tks-card__no-img">No Image</div>`;
      const reserved = parseInt(p.reserved)||0;
      const resvHtml = reserved > 0
        ? `<span class="pm-reserved-corner" data-resv="${p.id}" title="予約 ${reserved} 件">予約 ${reserved}</span>`
        : '';
      return `<div class="tks-card${cnt !== null && cnt !== undefined ? ' is-counted' : ''}" data-id="${p.id}">
        <div style="position:relative;">
          ${imgHtml}
          ${resvHtml}
          <span class="tks-badge-sys">Sys: ${sys}</span>
          <span class="tks-badge-diff ${diffClass}">${esc(diffText)}</span>
        </div>
        <div class="tks-card__body">
          <p class="tks-card__name">${esc(p.name||'')}</p>
          <p class="tks-card__sku">${esc(p.sku||'')}</p>
          <div class="tks-card__row">
            <button type="button" class="tks-minus" data-step="-1" data-id="${p.id}">−</button>
            <input type="number" inputmode="numeric" min="0" value="${cnt === null || cnt === undefined ? '' : cnt}" data-id="${p.id}" data-count-input placeholder="—">
            <button type="button" class="tks-plus" data-step="1" data-id="${p.id}">＋</button>
          </div>
        </div>
      </div>`;
    }).join('');
  }

  function tksUpdateTotals() {
    let products = 0, sys = 0, counted = 0, countedRows = 0, diff = 0;
    for (const p of allProducts) {
      products++;
      const s = parseInt(p.stock||0, 10);
      sys += s;
      const c = counts.get(String(p.id));
      if (c !== null && c !== undefined && !isNaN(c)) {
        countedRows++;
        counted += c;
        diff += (c - s);
      }
    }
    document.getElementById('tks-tot-products').textContent = products.toLocaleString();
    document.getElementById('tks-tot-system').textContent   = sys.toLocaleString();
    document.getElementById('tks-tot-counted').textContent  = counted.toLocaleString();
    document.getElementById('tks-tot-diff').textContent     = (diff > 0 ? '+' : '') + diff.toLocaleString();

    const bar = document.getElementById('tks-apply-bar');
    if (countedRows > 0) {
      bar.classList.remove('hidden');
      document.getElementById('tks-apply-info').textContent = countedRows + ' items counted';
    } else {
      bar.classList.add('hidden');
    }
  }

  function setCount(id, raw) {
    if (raw === '' || raw === null || raw === undefined) {
      counts.delete(String(id));
    } else {
      const n = Math.max(0, parseInt(raw, 10) || 0);
      counts.set(String(id), n);
    }
    // update card visuals
    const p = allProducts.find(x => String(x.id) === String(id));
    if (!p) return;
    const card = document.querySelector(`.tks-card[data-id="${id}"]`);
    if (!card) return;
    const cnt = counts.get(String(id));
    card.classList.toggle('is-counted', cnt !== null && cnt !== undefined);
    const diffEl = card.querySelector('.tks-badge-diff');
    if (diffEl) {
      if (cnt === null || cnt === undefined) {
        diffEl.className = 'tks-badge-diff zero';
        diffEl.textContent = '—';
      } else {
        const sys = parseInt(p.stock||0, 10);
        const d = cnt - sys;
        diffEl.className = 'tks-badge-diff ' + (d > 0 ? 'pos' : (d < 0 ? 'neg' : 'zero'));
        diffEl.textContent = (d > 0 ? '+' : '') + d;
      }
    }
    tksUpdateTotals();
  }

  document.addEventListener('click', function(e) {
    const resv = e.target.closest && e.target.closest('.pm-reserved-corner');
    if (resv) {
      e.stopPropagation();
      if (typeof window.pmShowReservations === 'function') window.pmShowReservations(resv.dataset.resv);
      return;
    }
    const btn = e.target.closest && e.target.closest('.tks-card__row button');
    if (!btn) return;
    const id   = btn.dataset.id;
    const step = parseInt(btn.dataset.step, 10) || 0;
    const input = document.querySelector(`input[data-count-input][data-id="${id}"]`);
    if (!input) return;
    const cur = counts.has(String(id)) ? (counts.get(String(id)) || 0) : (parseInt((allProducts.find(x => String(x.id) === String(id))||{}).stock || 0, 10));
    const next = Math.max(0, cur + step);
    input.value = next;
    setCount(id, next);
  });

  document.addEventListener('input', function(e) {
    const input = e.target.closest && e.target.closest('input[data-count-input]');
    if (!input) return;
    setCount(input.dataset.id, input.value);
  });

  document.addEventListener('focusin', function(e) {
    const input = e.target.closest && e.target.closest('input[data-count-input]');
    if (input) input.select();
  });

  window.tksFillSystem = function() {
    for (const p of allProducts) counts.set(String(p.id), parseInt(p.stock||0, 10));
    tksFilterList();
  };
  window.tksClearCounts = function() {
    counts.clear();
    tksFilterList();
  };

  async function tksApplyAll() {
    const dirty = [];
    for (const p of allProducts) {
      const c = counts.get(String(p.id));
      if (c === null || c === undefined) continue;
      const sys = parseInt(p.stock||0, 10);
      if (c === sys) continue;
      dirty.push({ product: p, sys, counted: c });
    }
    if (!dirty.length) {
      tksToast('Nothing to apply', 'bg-amber-600');
      return;
    }
    if (!confirm(`Apply ${dirty.length} stock changes?`)) return;

    const btn = document.getElementById('tks-apply-btn');
    btn.disabled = true;
    btn.textContent = 'Applying...';

    const channel = document.getElementById('tks-channel').value;
    let ok = 0, failed = 0;

    // Sequential to avoid hammering the server
    for (const { product, counted } of dirty) {
      try {
        await window.axios.post(API_UPDATE, { id: product.id, channel, stock: counted });
        product.stock = counted;
        ok++;
      } catch (_) { failed++; }
    }

    // Record history session
    try {
      await window.axios.post(API_SAVE_SESS, {
        items: dirty.map(({ product, sys, counted }) => ({
          product_id:  product.id,
          sku:         product.sku || null,
          system_qty:  sys,
          counted_qty: counted,
        })),
      });
    } catch (_) { /* history non-fatal */ }

    counts.clear();
    btn.disabled = false;
    btn.textContent = 'Apply All';
    tksFilterList();
    tksToast(`Applied ${ok}` + (failed ? ` (${failed} failed)` : ''), failed ? 'bg-amber-600' : 'bg-green-600');
  }

  // ── HISTORY ───────────────────────────────────────────────────────
  async function tksLoadHistory() {
    const list = document.getElementById('tks-history-list');
    list.innerHTML = `<div class="text-center py-8 text-gray-500 text-sm">Loading...</div>`;
    try {
      const res = await window.axios.get(API_SESSIONS + '?limit=100');
      const rows = res.data.data || [];
      if (!rows.length) {
        list.innerHTML = `<div class="text-center py-8 text-gray-500 text-sm">No sessions yet</div>`;
        return;
      }
      list.innerHTML = rows.map(s => {
        const dt = s.completed_date || s.start_date || (s.created_at||'').slice(0,10);
        const status = s.status === 'completed' ? 'Completed' : 'In Progress';
        const statusCls = s.status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700';
        return `<div class="flex items-center gap-3 px-4 py-3">
          <span class="w-28 text-sm text-gray-700 dark:text-gray-200">${esc(dt)}</span>
          <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold ${statusCls}">${status}</span>
          <span class="flex-1 text-right text-sm text-blue-600">${s.item_count} items</span>
        </div>`;
      }).join('');
    } catch (err) {
      list.innerHTML = `<div class="text-center py-8 text-red-500 text-sm">Failed to load history</div>`;
    }
  }

  function tksToast(msg, bg='bg-blue-600') {
    const t = document.getElementById('tks-toast');
    t.textContent = msg;
    t.className = `fixed bottom-20 right-4 z-50 rounded-lg px-4 py-3 text-white text-sm font-semibold shadow-xl transition-all duration-300 ${bg}`;
    requestAnimationFrame(() => t.classList.remove('opacity-0','translate-y-2','pointer-events-none'));
    setTimeout(() => t.classList.add('opacity-0','translate-y-2','pointer-events-none'), 2500);
  }

  // Barcode scan: pin search and focus the count input
  window.addEventListener('pos:barcode-resolved', function(ev){
    const d = ev.detail||{};
    if (!d.found || !d.product) {
      tksToast('No product for '+d.barcode, 'bg-red-600');
      return;
    }
    const sku = d.product.sku || '';
    const searchEl = document.getElementById('tks-search');
    if (searchEl && sku) { searchEl.value = sku; tksFilterList(); }
    setTimeout(() => {
      const card = document.querySelector('.tks-card[data-id="'+d.product.id+'"]');
      if (!card) return;
      card.scrollIntoView({behavior:'smooth', block:'center'});
      const input = card.querySelector('input[data-count-input]');
      if (input) { input.focus(); input.select(); }
    }, 80);
  });

})();
</script>
@endpush
</x-admin::layouts>

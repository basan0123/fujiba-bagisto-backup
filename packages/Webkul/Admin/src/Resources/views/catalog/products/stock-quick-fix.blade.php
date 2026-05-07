{{--
  STOCK QUICK FIX — single-mode (stock only) variant of mobile-view.
  Big images, big fonts, tap-edit stock, instant save via update_inline.
--}}
<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.products.index.title') — Stock Quick Fix
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
  #sqf-container{ overflow-x:hidden; }
}
#sqf-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    padding: 8px;
    width: 100%;
    box-sizing: border-box;
}
#sqf-grid.cols-1 { grid-template-columns: 1fr; }
#sqf-grid.cols-2 { grid-template-columns: repeat(2, 1fr); }
#sqf-grid.cols-3 { grid-template-columns: repeat(3, 1fr); }
#sqf-grid.cols-4 { grid-template-columns: repeat(4, 1fr); }

#sqf-grid .sqf-placeholder { grid-column: 1 / -1; }

.sqf-card {
    width: 100%;
    display: flex;
    flex-direction: column;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
    transition: transform 80ms ease, box-shadow 120ms ease;
    box-sizing: border-box;
    position: relative;
}
.sqf-card:active { transform: scale(0.97); }
.sqf-card.is-inactive { opacity: 0.55; }

.sqf-card__img,
.sqf-card__no-img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    display: block;
    flex-shrink: 0;
    background: #f3f4f6;
}
.sqf-card__no-img {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: #9ca3af;
}
#sqf-grid.cols-1 .sqf-card__img,
#sqf-grid.cols-1 .sqf-card__no-img { height: 280px; }
#sqf-grid.cols-3 .sqf-card__img,
#sqf-grid.cols-3 .sqf-card__no-img { height: 160px; }
#sqf-grid.cols-4 .sqf-card__img,
#sqf-grid.cols-4 .sqf-card__no-img { height: 130px; }

.sqf-card__body {
    flex: 1;
    min-width: 0;
    padding: 10px 10px 10px 10px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    box-sizing: border-box;
}
.sqf-card__name {
    font-size: 14px;
    line-height: 1.3;
    font-weight: 700;
    color: #1f2937;
    word-break: break-word;
    overflow-wrap: anywhere;
    margin: 0;
}
.sqf-card__sku {
    font-size: 11px;
    line-height: 1.2;
    color: #9ca3af;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin: 0;
}
.sqf-card__stock {
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.sqf-card__stock button {
    width: 36px; height: 36px;
    flex-shrink: 0;
    border-radius: 8px;
    border: 0;
    font-size: 20px;
    font-weight: 700;
    cursor: pointer;
}
.sqf-card__stock .sqf-minus { background: #fee2e2; color: #dc2626; }
.sqf-card__stock .sqf-plus  { background: #dcfce7; color: #16a34a; }
.sqf-card__stock input {
    flex: 1; min-width: 0;
    height: 36px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    text-align: center;
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    outline: none;
    box-sizing: border-box;
}
.sqf-card__stock input:focus { border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37,99,235,.18); }

.sqf-cols-btn {
    display:inline-flex;align-items:center;justify-content:center;
    width:36px;height:36px;border-radius:6px;
    border:1px solid #d1d5db;background:#fff;color:#374151;
    cursor:pointer;transition:all .15s;
}
.sqf-cols-btn:hover{background:#f3f4f6;}
.sqf-cols-btn.is-active{background:#2563eb;color:#fff;border-color:#1d4ed8;}

.dark .sqf-card { background: #111827; border-color: #374151; }
.dark .sqf-card__name { color: #f3f4f6; }
.dark .sqf-card__img,
.dark .sqf-card__no-img { background: #1f2937; }
.dark .sqf-card__stock input { background:#1f2937; color:#f9fafb; border-color:#4b5563; }

@keyframes sqf-ring-fade {
    0%   { box-shadow: 0 0 0 2px #4ade80; }
    100% { box-shadow: 0 0 0 0 transparent; }
}
.sqf-card.sqf-saved { animation: sqf-ring-fade .8s ease forwards; }
</style>
@endpush

<div id="sqf-container" class="relative z-0">

  <div id="sqf-header" class="sticky top-0 z-20 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-md shadow-sm">

    <div class="px-4 pt-3 pb-2 flex items-center justify-between gap-2">
      <h1 class="text-lg sm:text-xl font-bold text-gray-800 dark:text-white">Stock Quick Fix</h1>
      <div class="flex gap-1 flex-shrink-0">
        <button type="button" class="sqf-cols-btn" data-cols="1" onclick="sqfSetCols(1)" title="1 column">
          <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><rect x="3" y="3" width="14" height="14" rx="1.5"/></svg>
        </button>
        <button type="button" class="sqf-cols-btn is-active" data-cols="2" onclick="sqfSetCols(2)" title="2 columns">
          <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><rect x="2" y="3" width="7" height="14" rx="1"/><rect x="11" y="3" width="7" height="14" rx="1"/></svg>
        </button>
        <button type="button" class="sqf-cols-btn" data-cols="3" onclick="sqfSetCols(3)" title="3 columns">
          <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><rect x="2" y="3" width="4" height="14" rx="1"/><rect x="8" y="3" width="4" height="14" rx="1"/><rect x="14" y="3" width="4" height="14" rx="1"/></svg>
        </button>
      </div>
    </div>

    <div class="px-4 py-3 space-y-2 sm:space-y-0 sm:flex sm:gap-2">
      <input type="text" id="sqf-search" placeholder="@lang('admin::app.catalog.products.views.search-placeholder')"
        class="w-full sm:flex-1 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-blue-400"
        oninput="sqfFilterList()">
      <select id="sqf-channel"
              class="custom-select w-full sm:w-auto rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 pr-8 text-sm text-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-blue-400"
              onchange="sqfLoadProducts()">
        @foreach(core()->getAllChannels() as $ch)
          <option value="{{ $ch->code }}" {{ $ch->code === core()->getCurrentChannelCode() ? 'selected' : '' }}>
            {{ $ch->name }}
          </option>
        @endforeach
      </select>
    </div>

    <div class="px-4 pb-3 flex flex-wrap gap-2 items-center">
      <select id="sqf-status-filter"
              class="custom-select rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 pr-8 text-sm text-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-blue-400"
              onchange="sqfFilterList()">
        <option value="all">All</option>
        <option value="active">Active only</option>
        <option value="inactive">Inactive only</option>
      </select>
      <input type="number" id="sqf-stock-lt" min="0" placeholder="Stock <"
             class="w-24 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-blue-400"
             oninput="sqfFilterList()">
      <select id="sqf-sort"
              class="custom-select rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 pr-8 text-sm text-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-blue-400"
              onchange="sqfFilterList()">
        <option value="default">Sort: default</option>
        <option value="name_asc">Name ↑</option>
        <option value="name_desc">Name ↓</option>
        <option value="stock_asc">Stock ↑</option>
        <option value="stock_desc">Stock ↓</option>
        <option value="status_asc">Active first</option>
        <option value="status_desc">Inactive first</option>
      </select>
    </div>

    <div class="px-4 pb-3 flex gap-2 sm:gap-3">
      <div class="flex-1 bg-blue-50 dark:bg-blue-900/20 rounded-md p-2 sm:p-3 border border-blue-200 dark:border-blue-800">
        <div class="text-[10px] sm:text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wide">@lang('admin::app.catalog.products.views.mobile-total-stock')</div>
        <div class="text-lg sm:text-xl font-black text-blue-700 dark:text-blue-300" id="sqf-total-stock">0</div>
      </div>
      <div class="flex-1 bg-green-50 dark:bg-green-900/20 rounded-md p-2 sm:p-3 border border-green-200 dark:border-green-800">
        <div class="text-[10px] sm:text-xs font-semibold text-green-600 dark:text-green-400 uppercase tracking-wide">@lang('admin::app.catalog.products.views.mobile-total-value')</div>
        <div class="text-lg sm:text-xl font-black text-green-700 dark:text-green-300" id="sqf-total-value">¥0</div>
      </div>
    </div>
  </div>

  <div id="sqf-grid">
    <div class="sqf-placeholder text-center py-12 text-gray-500">@lang('admin::app.catalog.products.views.loading')</div>
  </div>

  <div style="height:4rem;"></div>
</div>

<div id="sqf-toast"
     class="fixed bottom-6 right-4 z-50 rounded-lg px-4 py-3 text-white text-sm font-semibold shadow-xl opacity-0 transform translate-y-2 pointer-events-none transition-all duration-300">
</div>

@push('scripts')
<script>
(function() {
  'use strict';

  const API_ALL    = "{{ route('admin.catalog.products.all') }}";
  const API_UPDATE = "{{ route('admin.catalog.products.update_inline') }}";

  const I18N = {
    saveSuccess: @json(trans('admin::app.catalog.products.views.save-success')),
    saveFailed:  @json(trans('admin::app.catalog.products.views.save-failed')),
    invalid:     @json(trans('admin::app.catalog.products.views.invalid-value')),
    loading:     @json(trans('admin::app.catalog.products.views.loading')),
    noProducts:  @json(trans('admin::app.catalog.products.views.no-products')),
    loadFailed:  @json(trans('admin::app.catalog.products.views.load-failed')),
  };

  let allProducts = [];

  document.addEventListener('DOMContentLoaded', () => {
    let saved = 2;
    try { const v = parseInt(localStorage.getItem('sqf-cols')||'',10); if (v>=1 && v<=4) saved = v; } catch(_) {}
    sqfSetCols(saved);
    sqfLoadProducts();
  });

  function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  window.sqfSetCols = function(n) {
    const grid = document.getElementById('sqf-grid');
    if (!grid) return;
    grid.classList.remove('cols-1','cols-2','cols-3','cols-4');
    grid.classList.add('cols-'+n);
    document.querySelectorAll('.sqf-cols-btn').forEach(b =>
      b.classList.toggle('is-active', String(b.dataset.cols) === String(n)));
    try { localStorage.setItem('sqf-cols', String(n)); } catch(_) {}
  };

  window.sqfLoadProducts = async function() {
    const channel = document.getElementById('sqf-channel').value;
    document.getElementById('sqf-grid').innerHTML =
      `<div class="sqf-placeholder text-center py-12 text-gray-500">${esc(I18N.loading)}</div>`;
    try {
      const res = await window.axios.get(`${API_ALL}?channel=${encodeURIComponent(channel)}&limit=500`);
      allProducts = res.data.data || [];
      sqfFilterList();
    } catch (err) {
      document.getElementById('sqf-grid').innerHTML =
        `<div class="sqf-placeholder text-center py-12 text-gray-500">${esc(I18N.loadFailed)}</div>`;
      sqfShowToast(I18N.loadFailed, 'bg-red-600');
    }
  };

  window.sqfFilterList = function() {
    const q       = document.getElementById('sqf-search').value.toLowerCase().trim();
    const statusEl= document.getElementById('sqf-status-filter');
    const status  = statusEl ? statusEl.value : 'all';
    const ltEl    = document.getElementById('sqf-stock-lt');
    const ltRaw   = ltEl ? ltEl.value : '';
    const ltNum   = ltRaw === '' ? null : parseInt(ltRaw, 10);
    const ltActive= ltNum !== null && !isNaN(ltNum);
    const sortEl  = document.getElementById('sqf-sort');
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
    sqfRender(filtered);
    sqfSummary(filtered);
  };

  function sqfRender(list) {
    const grid = document.getElementById('sqf-grid');
    if (list.length === 0) {
      grid.innerHTML = `<div class="sqf-placeholder text-center py-12 text-gray-500">${esc(I18N.noProducts)}</div>`;
      return;
    }
    grid.innerHTML = list.map(p => {
      const isActive = String(p.status) === '1';
      const stock    = parseInt(p.stock||0, 10);
      const imgHtml  = p.image_url
        ? `<img class="sqf-card__img" src="${esc(p.image_url)}" loading="lazy" alt="">`
        : `<div class="sqf-card__no-img">No Image</div>`;
      const reserved = parseInt(p.reserved)||0;
      const resvHtml = reserved > 0
        ? `<span class="pm-reserved-corner" data-resv="${p.id}" title="予約 ${reserved} 件 — タップで詳細">予約 ${reserved}</span>`
        : '';
      return `<div class="sqf-card${isActive?'':' is-inactive'}" data-id="${p.id}">
        ${resvHtml}
        ${imgHtml}
        <div class="sqf-card__body">
          <p class="sqf-card__name">${esc(p.name||'')}</p>
          <p class="sqf-card__sku">${esc(p.sku||'')}</p>
          <div class="sqf-card__stock">
            <button type="button" class="sqf-minus" data-step="-1" data-id="${p.id}" aria-label="−">−</button>
            <input type="number" inputmode="numeric" min="0" value="${stock}" data-id="${p.id}" data-stock-input>
            <button type="button" class="sqf-plus" data-step="1" data-id="${p.id}" aria-label="+">＋</button>
          </div>
        </div>
      </div>`;
    }).join('');
  }

  function sqfSummary(list) {
    let stock=0, value=0;
    list.forEach(p => {
      if (String(p.status) !== '1') return;
      const s = parseFloat(p.stock)||0;
      const c = parseFloat(p.jpy_cost!=null?p.jpy_cost:p.cost)||0;
      stock += s;
      value += s * c;
    });
    document.getElementById('sqf-total-stock').textContent = stock.toLocaleString();
    document.getElementById('sqf-total-value').textContent = '¥'+Math.round(value).toLocaleString();
  }

  // ── Save (debounced per-card) ─────────────────────────────────────
  const saveTimers = new Map();
  function scheduleSave(id, value) {
    clearTimeout(saveTimers.get(id));
    const tm = setTimeout(() => doSave(id, value), 600);
    saveTimers.set(id, tm);
  }

  async function doSave(id, value) {
    const channel = document.getElementById('sqf-channel').value;
    const card    = document.querySelector(`.sqf-card[data-id="${id}"]`);
    try {
      await window.axios.post(API_UPDATE, { id: parseInt(id,10), channel, stock: parseInt(value,10) });
      const p = allProducts.find(x => String(x.id) === String(id));
      if (p) p.stock = parseInt(value, 10);
      if (card) {
        card.classList.add('sqf-saved');
        setTimeout(()=>card.classList.remove('sqf-saved'), 900);
      }
      sqfSummary(allProducts);
    } catch (err) {
      sqfShowToast((err.response?.data?.message||I18N.saveFailed), 'bg-red-600');
    }
  }

  // Reserved-badge tap → show reservations modal
  document.addEventListener('click', function(e) {
    const resv = e.target.closest && e.target.closest('.pm-reserved-corner');
    if (!resv) return;
    e.stopPropagation();
    if (typeof window.pmShowReservations === 'function') window.pmShowReservations(resv.dataset.resv);
  });

  // Stepper buttons + input change
  document.addEventListener('click', function(e) {
    const btn = e.target.closest && e.target.closest('.sqf-card__stock button');
    if (!btn) return;
    const id    = btn.dataset.id;
    const step  = parseInt(btn.dataset.step, 10) || 0;
    const input = document.querySelector(`input[data-stock-input][data-id="${id}"]`);
    if (!input) return;
    const next = Math.max(0, (parseInt(input.value,10)||0) + step);
    input.value = next;
    scheduleSave(id, next);
  });

  document.addEventListener('input', function(e) {
    const input = e.target.closest && e.target.closest('input[data-stock-input]');
    if (!input) return;
    const id = input.dataset.id;
    const v  = Math.max(0, parseInt(input.value,10) || 0);
    scheduleSave(id, v);
  });

  document.addEventListener('focusin', function(e) {
    const input = e.target.closest && e.target.closest('input[data-stock-input]');
    if (input) input.select();
  });

  window.sqfShowToast = function(message, bgClass='bg-blue-600') {
    const t = document.getElementById('sqf-toast');
    t.textContent = message;
    t.className = `fixed bottom-6 right-4 z-50 rounded-lg px-4 py-3 text-white text-sm font-semibold shadow-xl transition-all duration-300 ${bgClass}`;
    requestAnimationFrame(() => t.classList.remove('opacity-0','translate-y-2','pointer-events-none'));
    setTimeout(() => t.classList.add('opacity-0','translate-y-2','pointer-events-none'), 2500);
  };

  // Barcode scan support
  window.addEventListener('pos:barcode-resolved', function(ev){
    const d = ev.detail||{};
    if (!d.found || !d.product) {
      sqfShowToast('No product for '+d.barcode, 'bg-red-600');
      return;
    }
    const sku = d.product.sku || '';
    const searchEl = document.getElementById('sqf-search');
    if (searchEl && sku) { searchEl.value = sku; sqfFilterList(); }
    setTimeout(() => {
      const card = document.querySelector('.sqf-card[data-id="'+d.product.id+'"]');
      if (!card) return;
      card.scrollIntoView({behavior:'smooth', block:'center'});
      const input = card.querySelector('input[data-stock-input]');
      if (input) { input.focus(); input.select(); }
    }, 80);
  });

})();
</script>
@endpush
</x-admin::layouts>

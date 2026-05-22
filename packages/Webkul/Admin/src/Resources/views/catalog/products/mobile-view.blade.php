{{--
  MOBILE-FIRST POS INTERFACE
  Grid: pure CSS in @push('styles') — immune to Tailwind purge, no Bootstrap needed
  3 cols mobile → 4 sm → 6 md → 12 xl
  All card classes prefixed pos- to avoid collisions
--}}
<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.products.index.title') — Mobile
    </x-slot>

@include('admin::catalog.products.partials.view-nav')
@include('admin::catalog.products.partials.barcode-scanner')
@include('admin::catalog.products.partials.reservations-modal')

@push('styles')
<style>
/* ═══ Strip layout padding on mobile so grid fills viewport edge-to-edge ═══ */
@media(max-width:768px){
  body [class*="min-h-[calc(100vh-62px)]"]{
    padding-left:0 !important;
    padding-right:0 !important;
  }
  #mobile-pos-container{ overflow-x:hidden; }
}
/* ═══ GRID — default 2 cols, switchable via #pos-grid.cols-{n} ═══ */
#pos-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    padding: 8px;
    width: 100%;
    box-sizing: border-box;
}
#pos-grid.cols-1 { grid-template-columns: 1fr; }
#pos-grid.cols-2 { grid-template-columns: repeat(2, 1fr); }
#pos-grid.cols-3 { grid-template-columns: repeat(3, 1fr); }
#pos-grid.cols-4 { grid-template-columns: repeat(4, 1fr); }

#pos-grid .pos-placeholder { grid-column: 1 / -1; }

/* ═══ CARD ═══ */
.pos-card {
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
.pos-card:active { transform: scale(0.97); }
.pos-card.is-inactive { opacity: 0.55; }

/* ═══ IMAGE — 3:2 aspect ratio (width:height = 3:2) ═══ */
.pos-card__img {
    width: 100%;
    aspect-ratio: 3 / 2;
    height: auto;
    object-fit: cover;
    display: block;
    flex-shrink: 0;
    background: #f3f4f6;
}
.pos-card__no-img {
    width: 100%;
    aspect-ratio: 3 / 2;
    height: auto;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: #9ca3af;
    background: #f3f4f6;
}

/* ═══ MOBILE (≤768px): shorter image height — wider aspect so it's flatter ═══ */
@media(max-width:768px){
  .pos-card__img,
  .pos-card__no-img { aspect-ratio: 2 / 1; }
}

/* ═══ PC (≥769px): smaller cards overall — narrower grid tracks shrink both
   width AND (via 3:2 aspect) height. Tracks fill the row, no gaps. ═══ */
@media(min-width:769px){
  #pos-grid,
  #pos-grid.cols-1,
  #pos-grid.cols-2,
  #pos-grid.cols-3,
  #pos-grid.cols-4 {
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  }
}

/* ═══ BODY ═══ */
.pos-card__body {
    flex: 1;
    min-width: 0;
    padding: 8px 10px 36px 10px; /* bottom padding reserves space for corner toggle */
    display: flex;
    flex-direction: column;
    gap: 2px;
    box-sizing: border-box;
    position: relative;
}
.pos-card__name {
    font-size: 13px;
    line-height: 1.3;
    font-weight: 600;
    color: #1f2937;
    word-break: break-word;
    overflow-wrap: anywhere;
    margin: 0;
}
.pos-card__sku {
    font-size: 11px;
    line-height: 1.2;
    color: #9ca3af;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin: 0;
}
.pos-card__value {
    margin-top: 4px;
    line-height: 1;
}
.pos-card__value span { font-size: 16px; font-weight: 800; }

/* ═══ Corner status toggle (bottom-right) ═══ */
.pos-card__toggle {
    position: absolute;
    bottom: 6px;
    right: 6px;
    width: 44px;
    height: 24px;
    border-radius: 12px;
    background: #d1d5db;
    cursor: pointer;
    transition: background .2s;
    z-index: 2;
}
.pos-card__toggle::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #fff;
    transition: left .2s;
    box-shadow: 0 1px 3px rgba(0,0,0,.25);
}
.pos-card__toggle.is-on { background: #22c55e; }
.pos-card__toggle.is-on::after { left: calc(100% - 22px); }

/* ═══ Layout switcher buttons ═══ */
.pos-cols-btn{
    display:inline-flex;align-items:center;justify-content:center;
    width:36px;height:36px;border-radius:6px;
    border:1px solid #d1d5db;background:#fff;color:#374151;
    cursor:pointer;transition:all .15s;
}
.pos-cols-btn:hover{background:#f3f4f6;}
.pos-cols-btn.is-active{background:#2563eb;color:#fff;border-color:#1d4ed8;}
.dark .pos-cols-btn{background:#1f2937;border-color:#4b5563;color:#d1d5db;}
.dark .pos-cols-btn.is-active{background:#1d4ed8;border-color:#1e40af;color:#fff;}

/* ═══ DARK ═══ */
.dark .pos-card         { background: #111827; border-color: #374151; }
.dark .pos-card__name   { color: #f3f4f6; }
.dark .pos-card__img,
.dark .pos-card__no-img { background: #1f2937; }
.dark .pos-card__no-img { color: #6b7280; }

/* ═══ Bagisto-style mode tab ═══ */
.mobile-mode-tab{
    border:2px solid #2563eb;background:#fff;color:#2563eb;
    transition:all .15s;
}
.mobile-mode-tab:hover{background:#eff6ff;}
.mobile-mode-tab.is-active{background:#2563eb;color:#fff;border-color:#1d4ed8;}
.dark .mobile-mode-tab{background:#1f2937;border-color:#9ca3af;color:#fff;}
.dark .mobile-mode-tab.is-active{background:#1d4ed8;border-color:#1e40af;color:#fff;}

/* ═══ SAVE RING ═══ */
@keyframes pos-ring-fade {
    0%   { box-shadow: 0 0 0 2px #4ade80; }
    100% { box-shadow: 0 0 0 0 transparent; }
}
.pos-card.pos-saved { animation: pos-ring-fade 0.8s ease forwards; }
</style>
@endpush

<div id="mobile-pos-container" class="relative z-0">

  {{-- ═══ STICKY HEADER ═══ --}}
  <div id="mobile-pos-header" class="sticky top-0 z-20 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-md shadow-sm">

    <div class="px-4 pt-3 pb-2">
      <h1 class="text-lg sm:text-xl font-bold text-gray-800 dark:text-white">@lang('admin::app.catalog.products.views.mobile-title')</h1>
    </div>

    <div class="px-4 pb-2 flex items-center gap-1.5">
      <div class="flex gap-1.5 overflow-x-auto flex-1">
        <button type="button" class="mobile-mode-tab flex-shrink-0 px-3 sm:px-4 py-1.5 rounded-md text-xs sm:text-sm font-semibold whitespace-nowrap"
                data-mode="stock" onclick="mobileSetMode('stock')">@lang('admin::app.catalog.products.views.mode-stock')</button>
        <button type="button" class="mobile-mode-tab flex-shrink-0 px-3 sm:px-4 py-1.5 rounded-md text-xs sm:text-sm font-semibold whitespace-nowrap"
                data-mode="price" onclick="mobileSetMode('price')">@lang('admin::app.catalog.products.views.mode-price')</button>
        <button type="button" class="mobile-mode-tab flex-shrink-0 px-3 sm:px-4 py-1.5 rounded-md text-xs sm:text-sm font-semibold whitespace-nowrap"
                data-mode="category" onclick="mobileSetMode('category')">@lang('admin::app.catalog.products.views.mode-category')</button>
        <button type="button" class="mobile-mode-tab flex-shrink-0 px-3 sm:px-4 py-1.5 rounded-md text-xs sm:text-sm font-semibold whitespace-nowrap"
                data-mode="brand" onclick="mobileSetMode('brand')">@lang('admin::app.catalog.products.views.mode-brand')</button>
        <button type="button" class="mobile-mode-tab flex-shrink-0 px-3 sm:px-4 py-1.5 rounded-md text-xs sm:text-sm font-semibold whitespace-nowrap"
                data-mode="color" onclick="mobileSetMode('color')">@lang('admin::app.catalog.products.views.mode-color')</button>
      </div>
      <div class="flex gap-1 flex-shrink-0">
        <button type="button" class="pos-cols-btn" data-cols="1" onclick="mobileSetCols(1)" title="1 column" aria-label="1 column">
          <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><rect x="3" y="3" width="14" height="14" rx="1.5"/></svg>
        </button>
        <button type="button" class="pos-cols-btn is-active" data-cols="2" onclick="mobileSetCols(2)" title="2 columns" aria-label="2 columns">
          <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><rect x="2" y="3" width="7" height="14" rx="1"/><rect x="11" y="3" width="7" height="14" rx="1"/></svg>
        </button>
      </div>
    </div>

    <div class="px-4 py-3 space-y-2 sm:space-y-0 sm:flex sm:gap-2">
      <input type="text" id="mobile-search" placeholder="@lang('admin::app.catalog.products.views.search-placeholder')"
        class="w-full sm:flex-1 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-blue-400"
        oninput="mobileFilterList()">
      <select id="mobile-channel"
              class="custom-select w-full sm:w-auto rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 pr-8 text-sm text-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-blue-400"
              onchange="mobileLoadProducts()">
        @foreach(core()->getAllChannels() as $ch)
          <option value="{{ $ch->code }}" {{ $ch->code === core()->getCurrentChannelCode() ? 'selected' : '' }}>
            {{ $ch->name }}
          </option>
        @endforeach
      </select>
    </div>

    <div class="px-4 pb-3 flex flex-wrap gap-2">
      <select id="mobile-status-filter"
              class="custom-select rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 pr-8 text-sm text-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-blue-400"
              onchange="mobileFilterList()">
        <option value="all">All</option>
        <option value="active">Active only</option>
        <option value="inactive">Inactive only</option>
      </select>
      <input type="number" id="mobile-stock-lt" min="0" placeholder="Stock <"
             class="w-28 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-blue-400"
             oninput="mobileFilterList()">
    </div>

    <div class="px-4 pb-3 flex gap-2 sm:gap-3">
      <div class="flex-1 bg-blue-50 dark:bg-blue-900/20 rounded-md p-2 sm:p-3 border border-blue-200 dark:border-blue-800">
        <div class="text-[10px] sm:text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wide">@lang('admin::app.catalog.products.views.mobile-total-stock')</div>
        <div class="text-lg sm:text-xl font-black text-blue-700 dark:text-blue-300" id="mobile-total-stock">0</div>
      </div>
      <div class="flex-1 bg-green-50 dark:bg-green-900/20 rounded-md p-2 sm:p-3 border border-green-200 dark:border-green-800">
        <div class="text-[10px] sm:text-xs font-semibold text-green-600 dark:text-green-400 uppercase tracking-wide" title="Active stock × cost price">@lang('admin::app.catalog.products.views.mobile-total-value')</div>
        <div class="text-lg sm:text-xl font-black text-green-700 dark:text-green-300" id="mobile-total-value">¥0</div>
      </div>
    </div>

  </div>

  {{-- ═══ PRODUCT GRID ═══ --}}
  <div id="pos-grid">
    <div class="pos-placeholder text-center py-12 text-gray-500">Loading products...</div>
  </div>

  <div style="height:4rem;"></div>

</div>

{{-- ═══ OVERLAY ═══ --}}
<div id="mobile-overlay"
     class="fixed inset-0 bg-black/0 z-30 transition-all duration-300 pointer-events-none"
     onclick="mobileCloseSheet()">
</div>

{{-- ═══ BOTTOM SHEET ═══ --}}
<div id="mobile-sheet"
     class="fixed bottom-0 left-0 right-0 z-40 bg-white dark:bg-gray-900 rounded-t-3xl shadow-2xl transition-transform duration-300 transform translate-y-full max-h-[90vh] overflow-y-auto"
     style="padding-bottom: env(safe-area-inset-bottom)">

  <div class="sticky top-0 bg-white dark:bg-gray-900 rounded-t-3xl pt-4 pb-3 border-b border-gray-200 dark:border-gray-800 touch-none flex justify-center">
    <div class="w-12 h-1.5 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
  </div>

  <div class="px-4 sm:px-6 py-4">
    <div class="mb-6">
      <h2 class="text-lg sm:text-xl font-bold text-gray-800 dark:text-white mb-1" id="mobile-sheet-title">@lang('admin::app.catalog.products.views.edit-stock')</h2>
      <p class="text-sm text-gray-600 dark:text-gray-400" id="mobile-sheet-product"></p>
    </div>

    {{-- STOCK --}}
    <div data-mode-body="stock" class="hidden">
      <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-900/50 rounded-2xl p-6 sm:p-8 mb-6 text-center border border-blue-200 dark:border-blue-800">
        <label class="text-xs sm:text-sm font-semibold text-blue-600 dark:text-blue-400 block mb-4 uppercase tracking-wide">@lang('admin::app.catalog.products.views.current-stock')</label>
        <div class="flex items-center justify-center gap-2 sm:gap-4 mb-6">
          <button type="button" class="flex-shrink-0 min-h-[50px] min-w-[50px] sm:min-h-[56px] sm:min-w-[56px] rounded-full bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 font-bold text-2xl hover:bg-red-200 active:scale-95 transition-all touch-manipulation shadow-sm"
                  onclick="mobileAdjustStock(-1)">−</button>
          <input type="number" id="mobile-stock-input" value="0"
                 class="w-20 h-20 sm:w-24 sm:h-24 text-center text-4xl sm:text-5xl font-black bg-white dark:bg-gray-800 border-2 border-blue-400 dark:border-blue-500 rounded-2xl text-blue-600 dark:text-blue-400 outline-none focus:ring-2 focus:ring-blue-500 shadow-md"
                 inputmode="numeric" autocomplete="off" min="0">
          <button type="button" class="flex-shrink-0 min-h-[50px] min-w-[50px] sm:min-h-[56px] sm:min-w-[56px] rounded-full bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 font-bold text-2xl hover:bg-green-200 active:scale-95 transition-all touch-manipulation shadow-sm"
                  onclick="mobileAdjustStock(1)">+</button>
        </div>
        <div class="text-xs sm:text-sm text-blue-700 dark:text-blue-300 font-medium">@lang('admin::app.catalog.products.views.current-stock-info')</div>
      </div>
      <div class="grid grid-cols-4 gap-2 sm:gap-3 mb-6">
        @foreach ([1, 3, 5, 8, 10, 15, 20] as $preset)
          <button type="button" class="px-2 py-2 sm:py-3 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs sm:text-sm font-semibold hover:bg-gray-200 active:scale-95 transition-all min-h-[44px] flex items-center justify-center"
                  onclick="mobileSetStock({{ $preset }})">{{ $preset }}</button>
        @endforeach
        <button type="button" class="px-2 py-2 sm:py-3 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs sm:text-sm font-semibold hover:bg-gray-200 active:scale-95 transition-all min-h-[44px] flex items-center justify-center"
                onclick="mobileSetStock(0)">@lang('admin::app.catalog.products.views.reset-zero')</button>
      </div>
    </div>

    {{-- PRICE --}}
    <div data-mode-body="price" class="hidden">
      <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-900/50 rounded-2xl p-6 sm:p-8 mb-6 text-center border border-green-200 dark:border-green-800">
        <label class="text-xs sm:text-sm font-semibold text-green-600 dark:text-green-400 block mb-4 uppercase tracking-wide">@lang('admin::app.catalog.products.views.sale-price')</label>
        <div class="flex items-center justify-center gap-2">
          <span class="text-3xl sm:text-4xl font-black text-green-700 dark:text-green-400">¥</span>
          <input type="number" id="mobile-price-input" value="0"
                 class="flex-1 max-w-[280px] h-16 sm:h-20 text-center text-3xl sm:text-4xl font-black bg-white dark:bg-gray-800 border-2 border-green-400 dark:border-green-500 rounded-2xl text-green-700 dark:text-green-400 outline-none focus:ring-2 focus:ring-green-500 shadow-md"
                 inputmode="numeric" autocomplete="off" min="0" step="1">
        </div>
        <div class="text-xs sm:text-sm text-green-700 dark:text-green-300 font-medium mt-4">@lang('admin::app.catalog.products.views.sale-price-info')</div>
      </div>
    </div>

    {{-- CATEGORY --}}
    <div data-mode-body="category" class="hidden">
      <div class="rounded-2xl p-4 sm:p-6 mb-6 border border-blue-200 dark:border-blue-800 bg-blue-50/40 dark:bg-blue-900/10">
        <label class="text-xs sm:text-sm font-semibold text-blue-600 dark:text-blue-400 block mb-3 uppercase tracking-wide">@lang('admin::app.catalog.products.views.mode-category')</label>
        <input type="text" id="mobile-cat-search" placeholder="@lang('admin::app.catalog.products.views.search-category')"
               class="w-full mb-3 rounded-md border border-blue-300 dark:border-blue-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-blue-400"
               oninput="mobileFilterCategoryList()">
        <div id="mobile-cat-count" class="text-xs text-blue-700 dark:text-blue-300 font-medium mb-2"></div>
        <div id="mobile-cat-list" class="max-h-[44vh] overflow-y-auto rounded-md border border-blue-200 dark:border-blue-800 bg-white dark:bg-gray-900">
          <p class="text-center text-xs text-gray-400 py-6">@lang('admin::app.catalog.products.views.loading-categories')</p>
        </div>
      </div>
    </div>

    {{-- FOOTER --}}
    <div class="flex gap-2 sm:gap-3 w-full fixed bottom-0 left-0 right-0 p-4 sm:p-6 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 sm:static sm:border-t-0 sm:bg-transparent sm:p-0"
         style="padding-bottom: max(1rem, env(safe-area-inset-bottom))">
      <button type="button" id="mobile-sheet-cancel"
              class="transparent-button flex-1 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-950 min-h-[44px]"
              onclick="mobileCloseSheet()">@lang('admin::app.catalog.products.views.cancel')</button>
      <button type="button" id="mobile-sheet-save"
              class="primary-button flex-1 min-h-[44px]"
              onclick="mobileSaveSheet()">@lang('admin::app.catalog.products.views.save')</button>
    </div>

    <div class="h-20 sm:h-0"></div>
  </div>
</div>

{{-- ═══ TOAST ═══ --}}
<div id="mobile-toast"
     class="fixed bottom-6 right-4 z-50 rounded-lg px-4 py-3 text-white text-sm font-semibold shadow-xl opacity-0 transform translate-y-2 pointer-events-none transition-all duration-300">
</div>

@push('scripts')
<script>
(function() {
  'use strict';

  const API_ALL    = "{{ route('admin.catalog.products.all') }}";
  const API_UPDATE = "{{ route('admin.catalog.products.update_inline') }}";

  const I18N = {
    editStock:        @json(trans('admin::app.catalog.products.views.edit-stock')),
    editPrice:        @json(trans('admin::app.catalog.products.views.edit-price')),
    editCategory:     @json(trans('admin::app.catalog.products.views.mode-category')),
    saveSuccess:      @json(trans('admin::app.catalog.products.views.save-success')),
    saveFailed:       @json(trans('admin::app.catalog.products.views.save-failed')),
    saving:           @json(trans('admin::app.catalog.products.views.saving')),
    save:             @json(trans('admin::app.catalog.products.views.save')),
    invalid:          @json(trans('admin::app.catalog.products.views.invalid-value')),
    loading:          @json(trans('admin::app.catalog.products.views.loading')),
    loadingCats:      @json(trans('admin::app.catalog.products.views.loading-categories')),
    notFound:         @json(trans('admin::app.catalog.products.views.not-found')),
    noProducts:       @json(trans('admin::app.catalog.products.views.no-products')),
    selectedCount:    @json(trans('admin::app.catalog.products.views.selected-count')),
    loadFailed:       @json(trans('admin::app.catalog.products.views.load-failed')),
  };

  let allProducts     = [];
  let categoryOptions = [];
  // Color rows carry only the option id (color_attr.integer_value); this map
  // resolves id → readable label from the API's options.colors block.
  let colorLabelById  = {};
  let currentMode     = 'stock';

  let sheet = {
    mode: 'stock', productId: null, productName: '',
    channel: '', catChecked: new Set(), catFilter: '',
  };

  document.addEventListener('DOMContentLoaded', () => {
    initializeModeTab();
    let savedCols = 2;
    try { const v = parseInt(localStorage.getItem('mobile-pos-cols')||'',10); if (v>=1 && v<=4) savedCols = v; } catch(_) {}
    if (typeof window.mobileSetCols === 'function') window.mobileSetCols(savedCols);
    mobileLoadProducts();
  });

  function initializeModeTab() {
    document.querySelectorAll('.mobile-mode-tab').forEach(tab => {
      tab.classList.toggle('is-active', tab.dataset.mode === currentMode);
    });
  }

  window.mobileSetMode = function(mode) {
    currentMode = mode;
    document.querySelectorAll('.mobile-mode-tab').forEach(tab => {
      tab.classList.toggle('is-active', tab.dataset.mode === mode);
    });
    mobileFilterList();
  };

  window.mobileLoadProducts = async function() {
    const channel = document.getElementById('mobile-channel').value;
    sheet.channel = channel;
    document.getElementById('pos-grid').innerHTML =
      `<div class="pos-placeholder text-center py-12 text-gray-500">${esc(I18N.loading)}</div>`;
    try {
      const res = await window.axios.get(`${API_ALL}?channel=${encodeURIComponent(channel)}&limit=500`);
      allProducts = res.data.data || [];
      categoryOptions = ((res.data.options || {}).categories || []).map(c => ({
        id: String(c.id), name: c.name || ('#' + c.id),
      }));
      colorLabelById = {};
      ((res.data.options || {}).colors || []).forEach(c => {
        colorLabelById[String(c.id)] = c.label || c.name || ('#' + c.id);
      });
      mobileFilterList();
    } catch (err) {
      console.error('Load error:', err);
      document.getElementById('pos-grid').innerHTML =
        `<div class="pos-placeholder text-center py-12 text-gray-500">${esc(I18N.loadFailed)}</div>`;
      mobileShowToast(I18N.loadFailed, 'bg-red-600');
    }
  };

  function currentFilterPredicate() {
    const q = (document.getElementById('mobile-search').value||'').toLowerCase().trim();
    const statusEl = document.getElementById('mobile-status-filter');
    const status   = statusEl ? statusEl.value : 'all';
    const ltEl     = document.getElementById('mobile-stock-lt');
    const ltRaw    = ltEl ? ltEl.value : '';
    const ltNum    = ltRaw === '' ? null : parseInt(ltRaw, 10);
    const ltActive = ltNum !== null && !isNaN(ltNum);
    return function(p) {
      if (q && !((p.name||'').toLowerCase().includes(q) || (p.sku||'').toLowerCase().includes(q))) return false;
      if (status === 'active'   && String(p.status) !== '1') return false;
      if (status === 'inactive' && String(p.status) === '1') return false;
      if (ltActive && (parseFloat(p.stock)||0) >= ltNum) return false;
      return true;
    };
  }

  window.mobileFilterList = function() {
    const pred = currentFilterPredicate();
    const filtered = allProducts.filter(pred);
    mobileRenderCards(filtered.map(p => allProducts.indexOf(p)).filter(i => i >= 0));
    calculateMobileSummary(filtered);
  };

  function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  function buildValueBadge(p, mode) {
    switch (mode) {
      case 'stock': {
        const s = Math.round(p.stock || 0);
        return `<span style="color:${s===0?'#ef4444':'#2563eb'};font-size:20px;font-weight:800;">${s}</span>`;
      }
      case 'price':
        return `<span style="color:#16a34a;font-size:10px;font-weight:700;">¥${Math.round(parseFloat(p.price||0)).toLocaleString()}</span>`;
      case 'category': {
        const cat = (p.category_names && p.category_names[0]) || p.categories || '—';
        return `<span style="color:#9333ea;font-size:9px;font-weight:600;overflow:hidden;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;">${esc(cat)}</span>`;
      }
      case 'brand': {
        // `brand` is already the resolved label (COALESCE of translation/text_value).
        const brand = p.brand || '—';
        return `<span style="color:#d97706;font-size:9px;font-weight:600;overflow:hidden;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;">${esc(brand)}</span>`;
      }
      case 'color': {
        // `color` is the option id; resolve to a readable label via colorLabelById.
        const cid = p.color != null && p.color !== '' ? String(p.color) : '';
        const color = cid ? (colorLabelById[cid] || cid) : '—';
        return `<span style="color:#0d9488;font-size:9px;font-weight:600;overflow:hidden;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;">${esc(color)}</span>`;
      }
      default: return '';
    }
  }

  window.mobileRenderCards = function(indexes) {
    const grid = document.getElementById('pos-grid');
    if (indexes.length === 0) {
      grid.innerHTML = `<div class="pos-placeholder text-center py-12 text-gray-500">${esc(I18N.noProducts)}</div>`;
      return;
    }

    // No wrapper div — each .pos-card is a direct grid child
    grid.innerHTML = indexes.map(i => {
      const p     = allProducts[i];
      const badge = buildValueBadge(p, currentMode);
      const imgHtml = p.image_url
        ? `<img class="pos-card__img" src="${esc(p.image_url)}" loading="lazy" alt="">`
        : `<div class="pos-card__no-img">No Image</div>`;
      const isActive = String(p.status) === '1';

      const reserved = parseInt(p.reserved)||0;
      const resvHtml = reserved > 0
        ? `<span class="pm-reserved-corner" data-resv="${p.id}" title="予約 ${reserved} 件 — タップで詳細">予約 ${reserved}</span>`
        : '';
      return `<div class="pos-card${isActive?'':' is-inactive'}" data-id="${p.id}" onclick="mobileCardClick(event, ${i})">
        ${resvHtml}
        ${imgHtml}
        <div class="pos-card__body">
          <p class="pos-card__name">${esc(p.name||'')}</p>
          <p class="pos-card__sku">${esc(p.sku||'')}</p>
          <div class="pos-card__value">${badge}</div>
          <div class="pos-card__toggle${isActive?' is-on':''}" data-toggle="${p.id}" role="switch" aria-checked="${isActive}" title="Active"></div>
        </div>
      </div>`;
    }).join('');
  };

  window.mobileCardClick = function(e, idx) {
    if (e.target && e.target.closest && e.target.closest('.pos-card__toggle')) return;
    const resv = e.target && e.target.closest && e.target.closest('.pm-reserved-corner');
    if (resv) {
      e.stopPropagation();
      if (typeof window.pmShowReservations === 'function') window.pmShowReservations(resv.dataset.resv);
      return;
    }
    mobileOpenSheet(idx);
  };

  window.mobileSetCols = function(n) {
    const grid = document.getElementById('pos-grid');
    if (!grid) return;
    grid.classList.remove('cols-1','cols-2','cols-3','cols-4');
    grid.classList.add('cols-'+n);
    document.querySelectorAll('.pos-cols-btn').forEach(b =>
      b.classList.toggle('is-active', String(b.dataset.cols) === String(n)));
    try { localStorage.setItem('mobile-pos-cols', String(n)); } catch(_) {}
  };

  document.addEventListener('click', async function(e){
    const tog = e.target.closest && e.target.closest('.pos-card__toggle');
    if (!tog) return;
    e.stopPropagation();
    const id = tog.dataset.toggle;
    const p = allProducts.find(x => String(x.id) === String(id));
    if (!p) return;
    const newStatus = String(p.status) === '1' ? 0 : 1;
    p.status = newStatus;
    tog.classList.toggle('is-on', newStatus === 1);
    tog.setAttribute('aria-checked', newStatus === 1 ? 'true' : 'false');
    const card = tog.closest('.pos-card');
    if (card) card.classList.toggle('is-inactive', newStatus !== 1);
    calculateMobileSummary(allProducts.filter(currentFilterPredicate()));
    try {
      const ch = document.getElementById('mobile-channel').value;
      await window.axios.post(API_UPDATE, { id: p.id, channel: ch, status: newStatus });
    } catch (err) {
      p.status = newStatus === 1 ? 0 : 1;
      tog.classList.toggle('is-on', p.status === 1);
      if (card) card.classList.toggle('is-inactive', String(p.status) !== '1');
      mobileShowToast(I18N.saveFailed, 'bg-red-600');
    }
  });

  function showSheetBody(mode) {
    document.querySelectorAll('[data-mode-body]').forEach(el =>
      el.classList.toggle('hidden', el.dataset.modeBody !== mode));
  }

  window.mobileOpenSheet = function(idx) {
    const p = allProducts[idx];
    if (!p) return;
    sheet.mode        = currentMode;
    sheet.productId   = parseInt(p.id, 10);
    sheet.productName = p.name || '';
    sheet.channel     = document.getElementById('mobile-channel').value;

    document.getElementById('mobile-sheet-product').textContent = sheet.productName;
    document.getElementById('mobile-sheet-title').textContent =
      ({stock:I18N.editStock, price:I18N.editPrice, category:I18N.editCategory})[sheet.mode] || I18N.editStock;

    showSheetBody(sheet.mode);

    if (sheet.mode === 'stock') {
      document.getElementById('mobile-stock-input').value = parseInt(p.stock||0, 10);
    } else if (sheet.mode === 'price') {
      document.getElementById('mobile-price-input').value = parseFloat(p.price||0);
    } else if (sheet.mode === 'category') {
      const pre = Array.isArray(p.category_ids) ? p.category_ids
        : String(p.category_ids||'').split(',').filter(Boolean);
      sheet.catChecked = new Set(pre.map(String));
      sheet.catFilter  = '';
      const s = document.getElementById('mobile-cat-search');
      if (s) s.value = '';
      renderCategoryList();
    }

    document.getElementById('mobile-sheet').classList.remove('translate-y-full');
    const ov = document.getElementById('mobile-overlay');
    ov.classList.remove('bg-black/0','pointer-events-none');
    ov.classList.add('bg-black/50','pointer-events-auto');
  };

  window.mobileCloseSheet = function() {
    document.getElementById('mobile-sheet').classList.add('translate-y-full');
    const ov = document.getElementById('mobile-overlay');
    ov.classList.remove('bg-black/50','pointer-events-auto');
    ov.classList.add('bg-black/0','pointer-events-none');
    sheet = { mode:'stock', productId:null, productName:'', channel:sheet.channel, catChecked:new Set(), catFilter:'' };
  };

  window.mobileAdjustStock = function(d) {
    const el = document.getElementById('mobile-stock-input');
    el.value = Math.max(0, (parseInt(el.value)||0) + d);
  };
  window.mobileSetStock = function(v) {
    document.getElementById('mobile-stock-input').value = v;
  };

  function visibleCategories() {
    return sheet.catFilter
      ? categoryOptions.filter(c => (c.name||'').toLowerCase().includes(sheet.catFilter))
      : categoryOptions;
  }
  window.mobileFilterCategoryList = function() {
    sheet.catFilter = (document.getElementById('mobile-cat-search').value||'').toLowerCase().trim();
    renderCategoryList();
  };
  function renderCategoryList() {
    const list = document.getElementById('mobile-cat-list');
    if (!list) return;
    const vis = visibleCategories();
    const cnt = document.getElementById('mobile-cat-count');
    if (cnt) cnt.textContent = sheet.catChecked.size > 0 ? I18N.selectedCount.replace(':count', sheet.catChecked.size) : '';
    if (vis.length === 0) {
      list.innerHTML = `<p class="text-center text-xs text-gray-400 py-6">${esc(categoryOptions.length===0?I18N.loadingCats:I18N.notFound)}</p>`;
      return;
    }
    const frag = document.createDocumentFragment();
    vis.forEach(c => {
      const lbl = document.createElement('label');
      lbl.className = 'flex items-center gap-3 px-3 py-2.5 cursor-pointer border-b border-blue-100 dark:border-blue-900/30 last:border-b-0';
      lbl.innerHTML = `<input type="checkbox" class="w-4 h-4 accent-blue-600 flex-shrink-0" value="${esc(c.id)}" ${sheet.catChecked.has(String(c.id))?'checked':''}>
        <span class="text-sm text-gray-800 dark:text-gray-200 flex-1">${esc(c.name)}</span>`;
      lbl.querySelector('input').addEventListener('change', function() {
        this.checked ? sheet.catChecked.add(this.value) : sheet.catChecked.delete(this.value);
        if (cnt) cnt.textContent = sheet.catChecked.size > 0 ? I18N.selectedCount.replace(':count', sheet.catChecked.size) : '';
      });
      frag.appendChild(lbl);
    });
    list.innerHTML = '';
    list.appendChild(frag);
  }

  function mobileUpdateSingleProduct(payload) {
    const idx = allProducts.findIndex(p => String(p.id) === String(payload.id));
    if (idx < 0) return;
    const p = allProducts[idx];
    if ('stock'      in payload) p.stock = payload.stock;
    if ('price'      in payload) p.price = payload.price;
    if ('categories' in payload) {
      const ids = (payload.categories||[]).map(String).filter(Boolean);
      p.category_ids   = ids;
      p.category_names = ids.map(id => (categoryOptions.find(c=>c.id===id)||{}).name).filter(Boolean);
      p.category_name  = p.category_names[0] || '';
    }
    const card = document.querySelector(`.pos-card[data-id="${p.id}"]`);
    if (!card) return;
    const vd = card.querySelector('.pos-card__value');
    if (vd) vd.innerHTML = buildValueBadge(p, currentMode);
    card.classList.add('pos-saved');
    setTimeout(() => card.classList.remove('pos-saved'), 900);
    calculateMobileSummary(allProducts.filter(currentFilterPredicate()));
  }

  window.reloadProductsSilent = function() { mobileLoadProducts(); };

  window.mobileSaveSheet = async function() {
    if (sheet.productId === null) return;
    const btn = document.getElementById('mobile-sheet-save');
    btn.disabled = true; btn.textContent = I18N.saving;
    const payload = { id: sheet.productId, channel: sheet.channel };
    if (sheet.mode === 'stock') {
      payload.stock = parseInt(document.getElementById('mobile-stock-input').value||0, 10);
    } else if (sheet.mode === 'price') {
      const v = parseFloat(document.getElementById('mobile-price-input').value);
      if (isNaN(v)||v<0) { mobileShowToast(I18N.invalid,'bg-red-600'); btn.disabled=false; btn.textContent=I18N.save; return; }
      payload.price = v;
    } else if (sheet.mode === 'category') {
      const ids = [...sheet.catChecked].map(Number).filter(Boolean);
      payload.categories = ids.length ? ids : [''];
    }
    try {
      await window.axios.post(API_UPDATE, payload);
      mobileShowToast(I18N.saveSuccess, 'bg-green-600');
      mobileCloseSheet();
      mobileUpdateSingleProduct(payload);
    } catch (err) {
      console.error('Save error:', err);
      mobileShowToast((err.response?.data?.message||I18N.saveFailed), 'bg-red-600');
    } finally {
      btn.disabled = false; btn.textContent = I18N.save;
    }
  };

  window.mobileShowToast = function(message, bgClass='bg-blue-600') {
    const t = document.getElementById('mobile-toast');
    t.textContent = message;
    t.className = `fixed bottom-6 right-4 z-50 rounded-lg px-4 py-3 text-white text-sm font-semibold shadow-xl transition-all duration-300 ${bgClass}`;
    setTimeout(() => t.classList.add('opacity-0','translate-y-2','pointer-events-none'), 2500);
  };

  function calculateMobileSummary(products) {
    let stock=0, value=0;
    products.forEach(p => {
      if (String(p.status) !== '1') return;
      const s = parseFloat(p.stock)||0;
      const c = parseFloat(p.jpy_cost!=null?p.jpy_cost:p.cost)||0;
      stock += s;
      value += s * c;
    });
    document.getElementById('mobile-total-stock').textContent = stock.toLocaleString();
    document.getElementById('mobile-total-value').textContent = '¥'+Math.round(value).toLocaleString();
  }

  /* ── BARCODE SCAN: pin search to SKU, scroll to card, flash it ── */
  window.addEventListener('pos:barcode-resolved', function(ev){
    const d = ev.detail||{};
    if (!d.found || !d.product) {
      mobileShowToast('No product for '+d.barcode, 'bg-red-600');
      return;
    }
    const pid = String(d.product.id);
    const sku = d.product.sku || '';
    const searchEl = document.getElementById('mobile-search');
    if (searchEl && sku) searchEl.value = sku;

    const have = allProducts.some(p => String(p.id) === pid);
    if (!have) {
      // Not in current channel slice — reload then highlight
      mobileLoadProducts().then(() => flashCard(pid));
      return;
    }
    mobileFilterList();
    flashCard(pid);
  });
  function flashCard(pid){
    setTimeout(()=>{
      const card = document.querySelector('.pos-card[data-id="'+pid+'"]');
      if (!card) return;
      card.scrollIntoView({behavior:'smooth', block:'center'});
      card.classList.add('bcs-hit');
      setTimeout(()=>card.classList.remove('bcs-hit'), 1600);
    }, 60);
  }

})();
</script>
@endpush
</x-admin::layouts>
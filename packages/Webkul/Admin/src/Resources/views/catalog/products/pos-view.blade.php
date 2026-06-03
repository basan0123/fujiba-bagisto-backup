{{-- Standalone page: POS Grid (Air Regi style) --}}
<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.products.index.title') — POS
    </x-slot>

@include('admin::catalog.products.partials.view-nav')
@include('admin::catalog.products.partials.barcode-scanner')
@include('admin::catalog.products.partials.reservations-modal')

@push('meta')
<script>
(function(){
  var m=document.querySelector('meta[name=viewport]');
  if(m){m.setAttribute('content','width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no');}
  else{m=document.createElement('meta');m.name='viewport';m.content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no';document.head.appendChild(m);}
  function tag(){if(document.body)document.body.classList.add('pos-fullbleed');}
  if(document.body){tag();}else{document.addEventListener('DOMContentLoaded',tag);}
})();
</script>
@endpush

@push('styles')
<style>
.pos-underline-input{display:block;width:100%;background:transparent;border:none;border-bottom:1px dashed #9ca3af;border-radius:0;padding:.375rem .25rem;font-size:.875rem;color:#1f2937;outline:none;transition:border-color .15s;}
.dark .pos-underline-input{color:#f9fafb;border-bottom-color:#6b7280;}
.pos-underline-input:hover{border-bottom:2px solid #60a5fa;}
.pos-underline-input:focus{border-bottom:2px solid #2563eb;background:rgba(59,130,246,0.06);}

/* ── Bagisto-style mode tabs ── */
.pos-tab-btn{
    display:inline-flex;align-items:center;gap:4px;
    border:2px solid #2563eb;background:#fff;color:#2563eb;
    transition:all .15s;
}
.pos-tab-btn:hover{background:#eff6ff;}
.pos-tab-btn.is-active{background:#2563eb;color:#fff;border-color:#1d4ed8;}
.dark .pos-tab-btn{background:#1f2937;border-color:#9ca3af;color:#fff;}
.dark .pos-tab-btn:hover{opacity:.85;}
.dark .pos-tab-btn.is-active{background:#1d4ed8;border-color:#1e40af;color:#fff;}

/* ── Bagisto-style sort buttons ── */
.pos-sort-btn{
    display:inline-flex;align-items:center;gap:4px;
    background:#fff;color:#374151;
    transition:all .15s;
}
.pos-sort-btn:hover{background:#f3f4f6;color:#1f2937;}
.pos-sort-btn.is-active{background:#2563eb;color:#fff;border-color:#1d4ed8;}
.dark .pos-sort-btn{background:#1f2937;color:#d1d5db;}
.dark .pos-sort-btn:hover{background:#111827;color:#fff;}
.dark .pos-sort-btn.is-active{background:#1d4ed8;border-color:#1e40af;color:#fff;}

.pos-row{display:flex;align-items:center;padding:10px 8px;background:#fff;gap:8px;}
.dark .pos-row{background:#030712;}
.pos-row:hover{background:#eff6ff;}
.dark .pos-row:hover{background:rgba(30,58,138,0.15);}

.pos-ar-under{display:inline-block;padding:4px 10px;border-bottom:2px solid #9ca3af;cursor:pointer;transition:border-color .12s,background .12s;}
.pos-ar-under:hover{border-bottom-color:#2563eb;background:rgba(59,130,246,.06);}
.pos-ar-dashed{display:inline-block;padding:4px 10px;border-bottom:2px dashed #9ca3af;cursor:pointer;transition:border-color .12s;}
.pos-ar-dashed:hover{border-bottom-color:#2563eb;background:rgba(59,130,246,.06);}

/* prevent iOS auto-zoom on focus — inputs must be ≥16px */
#pos-grid input,#pos-grid select,#pos-grid textarea,
#pos-edit-panel input,#pos-edit-panel select,#pos-edit-panel textarea,
#pos-bulk-modal input,#pos-bulk-modal select,#pos-bulk-modal textarea,
#pos-topbar input,#pos-topbar select{font-size:16px !important;}

/* Mobile: edge-to-edge — strip layout's horizontal padding for full-width rows */
@media(max-width:768px){
  body.pos-fullbleed [class*="min-h-[calc(100vh-62px)]"]{
    padding-left:0 !important;
    padding-right:0 !important;
  }
}
/* Tablet (769–1023px, no sidebar): symmetric side padding */
@media(min-width:769px) and (max-width:1023px){
  body.pos-fullbleed [class*="min-h-[calc(100vh-62px)]"]{
    padding-left:96px !important;
    padding-right:96px !important;
  }
}
/* Desktop (≥1024px): preserve layout's lg:ltr:pl-[286px] sidebar offset, add extra breathing room */
@media(min-width:1024px){
  body.pos-fullbleed [class*="min-h-[calc(100vh-62px)]"]{
    padding-left:310px !important;
    padding-right:160px !important;
  }
}
@media(min-width:1280px){
  body.pos-fullbleed [class*="min-h-[calc(100vh-62px)]"]{
    padding-left:330px !important;
    padding-right:240px !important;
  }
}
@media(min-width:1536px){
  body.pos-fullbleed [class*="min-h-[calc(100vh-62px)]"]{
    padding-left:350px !important;
    padding-right:320px !important;
  }
}

/* ensure no ancestor breaks position:sticky on the topbar */
body.pos-fullbleed,
body.pos-fullbleed [class*="min-h-[calc(100vh-62px)]"],
body.pos-fullbleed .w-full.overflow-x-hidden { overflow: visible !important; }
/* Stick BELOW the 62px-tall admin header (which is itself sticky top:0 with a
   higher z-index). With top:0 the topbar pinned to the same line and was painted
   behind the header — looked "missing" on iPad while scrolling. !important beats
   the Tailwind `top-0` utility on the element. */
#pos-topbar { position: sticky; top: 62px !important; z-index: 40; }

/* ── Mobile (≤768px): compact topbar — smaller fonts/padding so it stays out of the way when pinned ── */
@media(max-width:768px){
  #pos-topbar .pos-tab-btn{padding:4px 10px !important;font-size:12px !important;border-width:1.5px !important;}
  #pos-topbar .pos-sort-btn{padding:3px 8px !important;font-size:11px !important;}
  #pos-topbar #pos-channel{padding:4px 24px 4px 8px !important;max-width:120px !important;}
  #pos-topbar #pos-search{padding-top:7px !important;padding-bottom:7px !important;}
  #pos-topbar > div{padding-bottom:4px !important;}
  #pos-topbar > div:first-child{padding-top:6px !important;}
  #pos-topbar .text-sm.text-gray-500{font-size:11px !important;}
  #pos-topbar .primary-button{padding:4px 10px !important;font-size:12px !important;}
}
@media(max-width:480px){
  #pos-topbar .pos-tab-btn{padding:3px 8px !important;font-size:11px !important;}
  #pos-topbar .pos-sort-btn{padding:2px 6px !important;font-size:10px !important;}
}

.pos-dd-item{display:block;width:100%;text-align:left;padding:10px 16px;font-size:13px;color:#374151;background:none;border:none;cursor:pointer;}
.pos-dd-item:hover{background:#f9fafb;}

/* ── Desktop layout ── */
@media(min-width:769px){
  .pos-row { padding:12px 12px; }
  .pos-thumb { width:140px !important; height:140px !important; }
  .pos-row-body { padding-left:16px !important; padding-right:16px !important; }
  .pos-corner-toggle { right:8px !important; }
  .pos-corner-chk { right:8px !important; }

  /* Modals: wider, centered */
  #pos-bulk-modal  > div,
  #pos-cat-modal   > div,
  #pos-brand-modal > div,
  #pos-color-modal > div {
    width: 640px !important;
    max-width: 90vw !important;
  }
  #pos-bulk-modal,#pos-cat-modal,#pos-brand-modal,#pos-color-modal {
    align-items: center;
    justify-content: center;
  }

  /* Edit panel: floating card top-center */
  #pos-edit-panel {
    width: 520px !important;
    max-width: 90vw !important;
    bottom: auto !important;
    left: 50% !important;
    right: auto !important;
    top: 120px !important;
    transform: translateX(-50%) !important;
    border-radius: 10px !important;
    border: 2px solid #2563eb !important;
    box-shadow: 0 4px 12px rgba(37,99,235,.15), 0 0 0 4px rgba(37,99,235,.06) !important;
    padding-bottom: 0 !important;
  }
  #pos-edit-panel > div:first-child { display: none !important; }
  #pos-edit-panel { padding-top: 4px; }
}

#pos-edit-backdrop { background: rgba(0,0,0,.08) !important; }

#pos-edit-panel .px-5 { padding-left:16px !important; padding-right:16px !important; }
#pos-edit-panel #pos-edit-label   { font-size:12px; color:#6b7280; margin-bottom:2px; }
#pos-edit-panel #pos-edit-product { font-size:14px; font-weight:600; white-space:normal; word-break:break-word; }

#pos-edit-panel #pos-edit-input,
#pos-edit-panel #pos-edit-select {
  border: 1.5px solid #2563eb !important;
  border-radius: 6px !important;
  padding: 10px 12px !important;
  background: #fff !important;
  font-size: 18px !important;
  margin-top: 6px;
  margin-bottom: 8px;
  width: 100%;
  box-sizing: border-box;
}
.dark #pos-edit-panel #pos-edit-input,
.dark #pos-edit-panel #pos-edit-select {
  background: #1f2937 !important;
  color: #f9fafb !important;
}
#pos-edit-panel #pos-edit-input:focus,
#pos-edit-panel #pos-edit-select:focus {
  border-color: #1d4ed8 !important;
  outline: none;
}
#pos-edit-panel .pb-5 { padding-bottom: 14px !important; }
.dark #pos-edit-panel { background: #111827 !important; }
</style>
@endpush

{{-- ═══ TOP BAR ═══ --}}
<div id="pos-topbar"
     class="sticky top-0 z-30 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 shadow-sm">

  <div class="px-0 pt-2 pb-2">
    <div class="relative">
      <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
      </svg>
      <input type="text" id="pos-search" placeholder="@lang('admin::app.catalog.products.views.search-placeholder')"
        class="w-full pl-10 pr-4 py-3 rounded-none border-0 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-base outline-none focus:ring-0 focus:border-blue-500 dark:text-white" style="font-size:16px;">
    </div>
  </div>

  <div class="flex items-center gap-2 px-0 pb-2">
    <div class="flex gap-2 overflow-x-auto flex-1" style="scrollbar-width:none;">
      <button type="button" class="pos-tab-btn px-4 py-2 rounded-md text-sm font-semibold whitespace-nowrap" data-mode="price"    onclick="posSetMode('price')">@lang('admin::app.catalog.products.views.mode-price')</button>
      <button type="button" class="pos-tab-btn px-4 py-2 rounded-md text-sm font-semibold whitespace-nowrap" data-mode="stock"    onclick="posSetMode('stock')">@lang('admin::app.catalog.products.views.mode-stock')</button>
      <button type="button" class="pos-tab-btn px-4 py-2 rounded-md text-sm font-semibold whitespace-nowrap" data-mode="category" onclick="posSetMode('category')">@lang('admin::app.catalog.products.views.mode-category')</button>
      <button type="button" class="pos-tab-btn px-4 py-2 rounded-md text-sm font-semibold whitespace-nowrap" data-mode="brand"    onclick="posSetMode('brand')">@lang('admin::app.catalog.products.views.mode-brand')</button>
      <button type="button" class="pos-tab-btn px-4 py-2 rounded-md text-sm font-semibold whitespace-nowrap" data-mode="color"    onclick="posSetMode('color')">@lang('admin::app.catalog.products.views.mode-color')</button>
      <button type="button" class="pos-tab-btn px-4 py-2 rounded-md text-sm font-semibold whitespace-nowrap" data-mode="weight"   onclick="posSetMode('weight')">@lang('admin::app.catalog.products.views.mode-weight')</button>
    </div>
    <select id="pos-channel"
      class="custom-select max-w-[160px] truncate rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 pr-8 text-sm font-medium text-gray-700 dark:text-white"
      style="font-size:16px;"
      onchange="posChannelChange()">
      @foreach(core()->getAllChannels() as $ch)
        <option value="{{ $ch->code }}" {{ $ch->code === core()->getCurrentChannelCode() ? 'selected' : '' }}>{{ $ch->name }}</option>
      @endforeach
    </select>
  </div>

  <div class="flex items-center justify-between px-0 pb-2 gap-2 flex-wrap">
    <div class="flex items-center gap-2 flex-wrap">
      <span class="text-sm text-gray-500 dark:text-gray-400">@lang('admin::app.catalog.products.views.sort-by')</span>
      <button type="button" class="pos-sort-btn text-sm font-semibold px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600" data-col="name"   onclick="posSort('name')">@lang('admin::app.catalog.products.views.sort-name')</button>
      <button type="button" class="pos-sort-btn text-sm font-semibold px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600" id="pos-sort-val-btn" data-col="value" onclick="posSort('value')">@lang('admin::app.catalog.products.views.mode-stock')</button>
      <button type="button" class="pos-sort-btn text-sm font-semibold px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600" data-col="status" onclick="posSort('status')">@lang('admin::app.catalog.products.views.sort-status')</button>
      <select id="pos-status-filter"
              class="custom-select rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-2 py-1.5 pr-7 text-xs text-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-blue-400"
              onchange="posStatusFilterChange()">
        <option value="all">All</option>
        <option value="active">Active only</option>
        <option value="inactive">Inactive only</option>
      </select>
      <input type="number" id="pos-stock-lt" min="0" placeholder="Stock <"
             class="w-24 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-2 py-1.5 text-xs text-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-blue-400"
             oninput="posStatusFilterChange()">
    </div>
    <div id="pos-bulk-bar" class="hidden">
      <button type="button" onclick="posOpenBulk()" class="primary-button text-sm px-4 py-2">
        @lang('admin::app.catalog.products.views.bulk-update')
        <span id="pos-bulk-cnt" class="bg-white/25 rounded-full px-1.5 ml-1 text-xs font-bold">0</span>
      </button>
    </div>
  </div>

  {{-- Column header retained for accessibility but visually hidden in card layout --}}
  <div id="pos-col-header" class="hidden">
    <span id="pos-val-header"></span>
  </div>
</div>

{{-- ═══ PRODUCT LIST ═══ --}}
<div id="pos-grid" class="divide-y divide-gray-100 dark:divide-gray-800/60 bg-white dark:bg-gray-950">
  @for($i=0;$i<8;$i++)
  <div class="flex items-center px-3 py-2.5 animate-pulse" style="gap:0;">
    <div class="rounded-lg bg-gray-200 dark:bg-gray-800 flex-shrink-0" style="width:36px;height:36px;"></div>
    <div class="rounded bg-gray-200 dark:bg-gray-800 flex-shrink-0 mx-1" style="width:16px;height:16px;"></div>
    <div class="flex-1 space-y-1.5 mx-1"><div class="h-3 bg-gray-200 dark:bg-gray-800 rounded w-3/4"></div><div class="h-2 bg-gray-200 dark:bg-gray-800 rounded w-1/3"></div></div>
    <div class="bg-gray-200 dark:bg-gray-800 rounded flex-shrink-0" style="width:56px;height:12px;"></div>
    <div class="bg-gray-200 dark:bg-gray-800 rounded-full flex-shrink-0 mx-1" style="width:36px;height:20px;"></div>
  </div>
  @endfor
</div>
<div id="pos-sentinel" class="h-10 flex items-center justify-center gap-3">
  <span id="pos-count"   class="text-sm font-bold text-gray-700 dark:text-gray-200">0 / 0</span>
  <span id="pos-loading" class="hidden text-xs text-gray-400">@lang('admin::app.catalog.products.views.loading')</span>
  <span id="pos-end"     class="hidden text-xs text-gray-400">@lang('admin::app.catalog.products.views.end')</span>
</div>

{{-- ═══ DIRTY SAVE BAR ═══ --}}
<div id="pos-dirty-bar"
     class="hidden fixed left-0 right-0 bottom-0 z-[9500] bg-amber-500 text-white shadow-2xl border-t-4 border-amber-700
            flex items-center justify-center gap-3 py-3 px-4">
  <span id="pos-dirty-count" class="font-bold text-sm">@lang('admin::app.catalog.products.views.changes-count', ['count' => 0])</span>
  <button type="button" id="pos-dirty-save"  class="bg-white text-amber-700 font-bold px-5 py-2 rounded hover:bg-amber-50">@lang('admin::app.catalog.products.views.save')</button>
  <button type="button" id="pos-dirty-cancel" class="bg-amber-700 text-white px-5 py-2 rounded hover:bg-amber-800">@lang('admin::app.catalog.products.views.cancel')</button>
</div>

{{-- ═══ EDIT PANEL ═══ --}}
<div id="pos-edit-backdrop" class="hidden fixed inset-0 bg-black/35 z-[9000]" onclick="posCloseEdit()"></div>
<div id="pos-edit-panel" class="hidden fixed left-0 right-0 bottom-0 z-[9001] bg-white dark:bg-gray-900 rounded-t-[20px] shadow-2xl" style="padding-bottom:env(safe-area-inset-bottom,0);">
  <div class="flex justify-center pt-3 pb-1"><div class="w-10 h-1 rounded-full bg-gray-300"></div></div>
  <div class="px-5 pb-3 border-b border-gray-100">
    <p id="pos-edit-label"   class="text-[13px] text-gray-500"></p>
    <p id="pos-edit-product" class="text-base font-semibold text-gray-900 dark:text-white truncate mt-0.5"></p>
  </div>
  <div class="px-5 pt-4 pb-2">
    {{-- Use display:none inline so CSS class conflicts can't override it --}}
    <input  id="pos-edit-input"  type="text"   style="display:none;" class="pos-underline-input w-full px-1 py-2 text-2xl font-medium text-gray-900 dark:text-white outline-none" placeholder="">
    <select id="pos-edit-select" style="display:none;" class="pos-underline-input w-full px-1 py-2 text-base font-medium text-gray-900 dark:text-white outline-none mt-1"></select>
  </div>
  <div class="flex items-center justify-between px-5 pb-5">
    <div class="flex gap-4">
      <button type="button" class="text-2xl font-bold text-gray-700 px-1" onclick="posEditNav(-1)">∧</button>
      <button type="button" class="text-2xl font-bold text-gray-700 px-1" onclick="posEditNav(1)">∨</button>
    </div>
    <div class="flex gap-4">
      <button type="button" class="text-[17px] font-semibold text-blue-600 px-2" onclick="posSaveEdit()">@lang('admin::app.catalog.products.views.save')</button>
      <button type="button" class="text-[17px] font-semibold text-gray-400 px-2" onclick="posCloseEdit()">@lang('admin::app.catalog.products.views.done')</button>
    </div>
  </div>
</div>

{{-- ═══ BULK MODAL ═══ --}}
<div id="pos-bulk-modal" class="hidden fixed inset-0 z-[9100] bg-black/45 items-center justify-center p-4">
  <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-lg p-6">
    <h3 id="pos-bulk-title" class="text-lg font-bold text-gray-900 dark:text-white mb-1">@lang('admin::app.catalog.products.views.bulk-update-title')</h3>
    <p  id="pos-bulk-sub"   class="text-sm text-gray-500 dark:text-gray-400 mb-4"></p>
    <input  id="pos-bulk-input"  type="number" min="0" style="display:none;" class="pos-underline-input w-full px-1 py-2 text-xl outline-none dark:text-white mb-4">
    <select id="pos-bulk-select" style="display:none;" class="pos-underline-input w-full px-1 py-2 text-base outline-none dark:text-white mb-4"></select>
    <div class="flex gap-3">
      <button type="button" onclick="posCloseBulk()" class="transparent-button flex-1 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-950">@lang('admin::app.catalog.products.views.cancel')</button>
      <button type="button" onclick="posExecBulk()"  class="primary-button flex-1">@lang('admin::app.catalog.products.views.bulk-update')</button>
    </div>
  </div>
</div>

{{-- ═══ CATEGORY MODAL ═══ --}}
<div id="pos-cat-modal" class="hidden fixed inset-0 z-[9300] bg-black/50 items-center justify-center p-4">
  <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-lg flex flex-col" style="max-height:88vh;">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
      <div>
        <h3 class="text-base font-bold text-gray-900 dark:text-white">@lang('admin::app.catalog.products.views.set-category')</h3>
        <p id="pos-cat-product-name" class="text-xs text-gray-500 mt-0.5"></p>
      </div>
      <button type="button" onclick="posCloseCatModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none ml-3">&times;</button>
    </div>
    <div class="px-4 pt-3 pb-2 flex-shrink-0 space-y-2">
      <input type="text" id="pos-cat-search" placeholder="@lang('admin::app.catalog.products.views.search-category')"
             class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm dark:text-white outline-none focus:ring-2 focus:ring-blue-400"
             oninput="posCatFilter(this.value)">
      <div class="flex items-center justify-between text-xs text-gray-500 px-0.5">
        <label class="flex items-center gap-1.5 cursor-pointer">
          <input type="checkbox" id="pos-cat-all" class="accent-blue-600 w-3.5 h-3.5" onchange="posCatSelectAll(this.checked)">
          <span>@lang('admin::app.catalog.products.views.select-all')</span>
        </label>
        <span id="pos-cat-count" class="text-blue-600 font-semibold"></span>
      </div>
    </div>
    <div id="pos-cat-list" class="flex-1 overflow-y-auto px-4 pb-2 min-h-0"></div>
    <div class="flex gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
      <button type="button" onclick="posCloseCatModal()" class="transparent-button flex-1 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-950">@lang('admin::app.catalog.products.views.cancel')</button>
      <button type="button" onclick="posSaveCatModal()" id="pos-cat-save-btn" class="primary-button flex-1">@lang('admin::app.catalog.products.views.save')</button>
    </div>
  </div>
</div>

{{-- ═══ BRAND MODAL ═══ --}}
<div id="pos-brand-modal" class="hidden fixed inset-0 z-[9300] bg-black/50 items-center justify-center p-4">
  <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-lg flex flex-col" style="max-height:88vh;">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
      <div>
        <h3 class="text-base font-bold text-gray-900 dark:text-white">@lang('admin::app.catalog.products.views.set-brand')</h3>
        <p id="pos-brand-product-name" class="text-xs text-gray-500 mt-0.5"></p>
      </div>
      <button type="button" onclick="posCloseBrandModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none ml-3">&times;</button>
    </div>
    <div class="px-4 pt-3 pb-2 flex-shrink-0">
      <input type="text" id="pos-brand-search" placeholder="@lang('admin::app.catalog.products.views.search-brand')"
             class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm dark:text-white outline-none focus:ring-2 focus:ring-blue-400"
             oninput="posBrandFilter(this.value)">
    </div>
    <div id="pos-brand-list" class="flex-1 overflow-y-auto px-4 pb-2 min-h-0"></div>
    <div class="flex gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
      <button type="button" onclick="posCloseBrandModal()" class="transparent-button flex-1 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-950">@lang('admin::app.catalog.products.views.cancel')</button>
      <button type="button" onclick="posSaveBrandModal()" id="pos-brand-save-btn" class="primary-button flex-1">@lang('admin::app.catalog.products.views.save')</button>
    </div>
  </div>
</div>

{{-- ═══ COLOR MODAL ═══ --}}
<div id="pos-color-modal" class="hidden fixed inset-0 z-[9300] bg-black/50 items-center justify-center p-4">
  <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-lg flex flex-col" style="max-height:88vh;">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
      <div>
        <h3 class="text-base font-bold text-gray-900 dark:text-white">@lang('admin::app.catalog.products.views.set-color')</h3>
        <p id="pos-color-product-name" class="text-xs text-gray-500 mt-0.5"></p>
      </div>
      <button type="button" onclick="posCloseColorModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none ml-3">&times;</button>
    </div>
    <div class="px-4 pt-3 pb-2 flex-shrink-0 space-y-2">
      <input type="text" id="pos-color-search" placeholder="@lang('admin::app.catalog.products.views.search-color')"
             class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm dark:text-white outline-none focus:ring-2 focus:ring-blue-400"
             oninput="posColorFilter(this.value)">
      <div class="flex items-center justify-between text-xs text-gray-500 px-0.5">
        <label class="flex items-center gap-1.5 cursor-pointer">
          <input type="checkbox" id="pos-color-all" class="accent-blue-600 w-3.5 h-3.5" onchange="posColorSelectAll(this.checked)">
          <span>@lang('admin::app.catalog.products.views.select-all')</span>
        </label>
        <span id="pos-color-count" class="text-blue-600 font-semibold"></span>
      </div>
    </div>
    <div id="pos-color-list" class="flex-1 overflow-y-auto px-4 pb-2 min-h-0"></div>
    <div class="flex gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
      <button type="button" onclick="posCloseColorModal()" class="transparent-button flex-1 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-950">@lang('admin::app.catalog.products.views.cancel')</button>
      <button type="button" onclick="posSaveColorModal()" id="pos-color-save-btn" class="primary-button flex-1">@lang('admin::app.catalog.products.views.save')</button>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function(){
'use strict';
if(window.__posBooted){ console.log('[POS] duplicate ignored'); return; }
window.__posBooted = true;

const API_ALL    = "{{ route('admin.catalog.products.all') }}";
const API_UPDATE = "{{ route('admin.catalog.products.update_inline') }}";
const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content || "{{ csrf_token() }}";
const LIMIT      = 2000;

let allData=[], filtered=[], offset=0, loading=false, ended=false;
let searchQ='', sortCol=null, sortDir='asc', mode='stock';
let categories=[], brands=[], colorOptions=[];
let selected=new Set(), editIdx=-1, initialized=false;

const $   = id => document.getElementById(id);
const esc = s  => { const d=document.createElement('div'); d.textContent=String(s??''); return d.innerHTML; };

/* ── helpers: show/hide using inline style — avoids CSS class specificity fights ── */
function show(el){ if(el) el.style.display='block'; }
function hide(el){ if(el) el.style.display='none';  }
function showFlex(el){ if(el){ el.style.display='flex'; } }

/* ── LOAD OPTIONS ── */
async function loadOptions(){
  try{
    const ch=$('pos-channel')?.value||'';
    const res=await window.axios.get(`${API_ALL}?limit=1&channel=${encodeURIComponent(ch)}`);
    const opts=res.data.options||{};
    categories   =(opts.categories||[]).map(c=>({id:String(c.id),name:c.name||('#'+c.id)}));
    brands       =(opts.brands||[]).map(b=>({id:String(b.id),name:b.label||b.name||String(b.id)}));
    colorOptions =(opts.colors||[]).map(c=>({id:String(c.id),name:c.label||c.name||String(c.id)}));
    if(brands.length===0&&allData.length>0){
      const bmap=new Map(); allData.forEach(r=>{if(r.brand_id&&r.brand)bmap.set(String(r.brand_id),r.brand);});
      brands=[...bmap.entries()].map(([id,name])=>({id,name}));
    }
    if(colorOptions.length===0&&allData.length>0){
      const cmap=new Map(); allData.forEach(r=>{if(r.color)cmap.set(String(r.color),String(r.color));});
      colorOptions=[...cmap.entries()].map(([id,name])=>({id,name}));
    }
  }catch(e){ console.error('[POS] loadOptions failed',e); }
}

/* ── i18n labels ── */
const I18N = {
  price:           @json(trans('admin::app.catalog.products.views.mode-price')),
  stock:           @json(trans('admin::app.catalog.products.views.mode-stock')),
  category:        @json(trans('admin::app.catalog.products.views.mode-category')),
  brand:           @json(trans('admin::app.catalog.products.views.mode-brand')),
  color:           @json(trans('admin::app.catalog.products.views.mode-color')),
  weight:          @json(trans('admin::app.catalog.products.views.mode-weight-kg')),
  editName:        @json(trans('admin::app.catalog.products.views.edit-name')),
  editPrice:       @json(trans('admin::app.catalog.products.views.edit-price')),
  editStock:       @json(trans('admin::app.catalog.products.views.edit-stock')),
  editWeight:      @json(trans('admin::app.catalog.products.views.edit-weight')),
  bulkPrice:       @json(trans('admin::app.catalog.products.views.bulk-update-title') . ' — ' . trans('admin::app.catalog.products.views.mode-price')),
  bulkStock:       @json(trans('admin::app.catalog.products.views.bulk-update-title') . ' — ' . trans('admin::app.catalog.products.views.mode-stock')),
  bulkCategory:    @json(trans('admin::app.catalog.products.views.bulk-update-title') . ' — ' . trans('admin::app.catalog.products.views.mode-category')),
  bulkBrand:       @json(trans('admin::app.catalog.products.views.bulk-update-title') . ' — ' . trans('admin::app.catalog.products.views.mode-brand')),
  bulkColor:       @json(trans('admin::app.catalog.products.views.bulk-update-title') . ' — ' . trans('admin::app.catalog.products.views.mode-color')),
  bulkWeight:      @json(trans('admin::app.catalog.products.views.bulk-update-title') . ' — ' . trans('admin::app.catalog.products.views.mode-weight-kg')),
  selectedCount:   @json(trans('admin::app.catalog.products.views.selected-count')),
  unset:           @json(trans('admin::app.catalog.products.views.unset')),
  saveSuccess:     @json(trans('admin::app.catalog.products.views.save-success')),
  saveFailed:      @json(trans('admin::app.catalog.products.views.save-failed')),
  saving:          @json(trans('admin::app.catalog.products.views.saving')),
  save:            @json(trans('admin::app.catalog.products.views.save')),
  loading:         @json(trans('admin::app.catalog.products.views.loading')),
  loadingCats:     @json(trans('admin::app.catalog.products.views.loading-categories')),
  loadingBrands:   @json(trans('admin::app.catalog.products.views.loading-brands')),
  loadingColors:   @json(trans('admin::app.catalog.products.views.loading-colors')),
  notFound:        @json(trans('admin::app.catalog.products.views.not-found')),
  noProducts:      @json(trans('admin::app.catalog.products.views.no-products')),
  noResults:       @json(trans('admin::app.catalog.products.views.no-results')),
  outShort:        @json(trans('admin::app.catalog.products.views.out-of-stock-short')),
  invalid:         @json(trans('admin::app.catalog.products.views.invalid-value')),
  updatedCount:    @json(trans('admin::app.catalog.products.views.updated-count')),
  failedCount:     @json(trans('admin::app.catalog.products.views.failed-count')),
  enterPrice:      @json(trans('admin::app.catalog.products.views.enter-price')),
  enterStock:      @json(trans('admin::app.catalog.products.views.enter-stock')),
  enterWeight:     @json(trans('admin::app.catalog.products.views.enter-weight')),
  sessionExpired:  @json(trans('admin::app.catalog.products.views.session-expired')),
  csrfExpired:     @json(trans('admin::app.catalog.products.views.csrf-expired')),
  loadFailedHttp:  @json(trans('admin::app.catalog.products.views.load-failed-http')),
  loadFailed:      @json(trans('admin::app.catalog.products.views.load-failed')),
  initFailed:      @json(trans('admin::app.catalog.products.views.init-failed')),
  axiosFailed:     @json(trans('admin::app.catalog.products.views.axios-failed')),
};

/* ── MODE ── */
window.posSetMode=function(m){
  mode=m;
  document.querySelectorAll('.pos-tab-btn').forEach(b=>{
    b.classList.toggle('is-active', b.dataset.mode===m);
  });
  const labels={price:I18N.price,stock:I18N.stock,category:I18N.category,brand:I18N.brand,color:I18N.color,weight:I18N.weight};
  const vh=$('pos-val-header'); if(vh)vh.textContent=labels[m]||m;
  const svb=$('pos-sort-val-btn'); if(svb)svb.textContent=labels[m]||m;
  applyFilterSort(); renderRows();
};

/* ── SORT ── */
window.posSort=function(col){
  sortDir=(sortCol===col&&sortDir==='asc')?'desc':'asc'; sortCol=col;
  document.querySelectorAll('.pos-sort-btn').forEach(b=>{
    b.classList.toggle('is-active', b.dataset.col===col);
  });
  applyFilterSort(); renderRows();
};

// Effective sell price: special_price wins only when it's a positive number.
// Note special_price arrives as the string "0.0000" when unset, which is
// truthy in JS — so `special_price || price` would wrongly yield 0. Compare > 0.
function sellPrice(r){
  const sp=parseFloat(r.special_price)||0;
  return sp>0 ? sp : (parseFloat(r.price)||0);
}

function getRowValue(r){
  if(mode==='price')    return sellPrice(r);
  if(mode==='stock')    return parseInt(r.stock)||0;
  if(mode==='category') return ((r.category_names&&r.category_names[0])||r.category_name||'').toLowerCase();
  if(mode==='brand')    return (r.brand||'').toLowerCase();
  if(mode==='color')    return (r.color||'').toString().toLowerCase();
  if(mode==='weight')   return parseFloat(r.weight_kg||r.weight)||0;
  return '';
}

window.posStatusFilterChange = function(){ applyFilterSort(); renderRows(); };

function applyFilterSort(){
  const q=searchQ.toLowerCase();
  const statusEl=$('pos-status-filter');
  const status=statusEl?statusEl.value:'all';
  const ltEl=$('pos-stock-lt');
  const ltRaw=ltEl?ltEl.value:'';
  const ltNum=ltRaw===''?null:parseInt(ltRaw,10);
  const ltActive=ltNum!==null&&!isNaN(ltNum);
  filtered=allData.filter(r=>{
    if(q && !((r.name||'').toLowerCase().includes(q) || (r.sku||'').toLowerCase().includes(q))) return false;
    if(status==='active'   && String(r.status)!=='1') return false;
    if(status==='inactive' && String(r.status)==='1') return false;
    if(ltActive && (parseFloat(r.stock)||0) >= ltNum) return false;
    return true;
  });
  if(sortCol){
    filtered.sort((a,b)=>{
      let av,bv;
      if(sortCol==='name'){av=(a.name||'').toLowerCase();bv=(b.name||'').toLowerCase();}
      else if(sortCol==='status'){av=+a.status||0;bv=+b.status||0;}
      else{av=getRowValue(a);bv=getRowValue(b);}
      if(av<bv)return sortDir==='asc'?-1:1; if(av>bv)return sortDir==='asc'?1:-1; return 0;
    });
  }
}

/* ── CHANNEL ── */
window.posChannelChange=async function(){
  allData=[]; offset=0; ended=false; selected.clear(); updateBulkBar();
  await loadOptions(); await fetchMore(true); await loadAllRemaining();
};

/* Pull every remaining page sequentially until the backend signals end.
   Independent of the scroll observer so the grid always loads in full. */
async function loadAllRemaining(){
  let guard=0; // hard cap (pages) to avoid any pathological infinite loop
  while(!ended && !loading && guard++ < 200){
    await fetchMore(false);
  }
}

/* ── FETCH ── */
async function fetchMore(reset){
  if(loading)return; if(!reset&&ended)return;
  if(reset){offset=0;allData=[];ended=false;}
  loading=true;
  if($('pos-loading'))$('pos-loading').classList.remove('hidden');
  try{
    const ch=$('pos-channel')?.value||'';
    const res=await window.axios.get(`${API_ALL}?q=${encodeURIComponent(searchQ)}&limit=${LIMIT}&offset=${offset}&channel=${encodeURIComponent(ch)}`);
    const recs=res.data.data||[]; const total=res.data.total||0;
    if(reset&&res.data.options){
      const opts=res.data.options;
      categories  =(opts.categories||[]).map(c=>({id:String(c.id),name:c.name||('#'+c.id)}));
      brands      =(opts.brands||[]).map(b=>({id:String(b.id),name:b.label||b.name||String(b.id)}));
      colorOptions=(opts.colors||[]).map(c=>({id:String(c.id),name:c.label||c.name||String(c.id)}));
    }
    recs.forEach(r=>allData.push(r)); offset+=recs.length;
    if($('pos-count'))$('pos-count').textContent=`${allData.length} / ${total}`;
    if(offset>=total||recs.length<LIMIT){ended=true;if($('pos-end'))$('pos-end').classList.remove('hidden');}
    applyFilterSort(); renderRows();
    if(brands.length===0){const bmap=new Map();allData.forEach(r=>{if(r.brand_id&&r.brand)bmap.set(String(r.brand_id),r.brand);else if(r.brand)bmap.set(r.brand,r.brand);});brands=[...bmap.entries()].map(([id,name])=>({id,name}));}
    if(colorOptions.length===0){const cmap=new Map();allData.forEach(r=>{if(r.color)cmap.set(String(r.color),String(r.color));});colorOptions=[...cmap.entries()].map(([id,name])=>({id,name}));}
  }catch(e){
    console.error('[POS] fetch failed',e);
    const status=e?.response?.status;
    const msg=status===401?I18N.sessionExpired:status===419?I18N.csrfExpired:status?I18N.loadFailedHttp.replace(':status',status):I18N.loadFailed;
    toast(msg,'error');
    if(reset&&allData.length===0){const grid=$('pos-grid');if(grid)grid.innerHTML=`<div class="px-4 py-8 text-center text-sm text-red-600">${esc(msg)}</div>`;}
  }
  finally{loading=false;if($('pos-loading'))$('pos-loading').classList.add('hidden');}
}

/* ── RENDER ── */
function renderRows(){
  const grid=$('pos-grid');if(!grid)return;
  if(filtered.length===0){
    grid.innerHTML=loading?`<div class="px-4 py-8 text-center text-sm text-gray-400">${esc(I18N.loading)}</div>`:allData.length===0?`<div class="px-4 py-8 text-center text-sm text-gray-500">${esc(I18N.noProducts)}</div>`:`<div class="px-4 py-8 text-center text-sm text-gray-500">${esc(I18N.noResults)}</div>`;
    return;
  }
  const frag=document.createDocumentFragment();
  filtered.forEach(row=>frag.appendChild(buildRow(row)));
  grid.innerHTML=''; grid.appendChild(frag);
}

function buildRow(row){
  const div=document.createElement('div');
  div.className='pos-row'; div.dataset.id=row.id;
  const active=String(row.status)==='1', stock=parseInt(row.stock)||0, pid=row.id, isSel=selected.has(String(pid));
  let valHTML='';
  if(mode==='price'){
    // Inline-editable sell + buy price. Both inputs save on blur / Enter via
    // pos-price-inline handler — no modal. Sell = product_flat.price, buy =
    // cost attribute (float_value), shipped to updateInline as `price`/`cost`.
    const sell=parseFloat(row.price)||0;
    const buy=parseFloat(row.cost)||0;
    valHTML=`
      <div class="pos-price-inline" data-pid="${pid}" style="display:flex;flex-direction:column;gap:4px;align-items:flex-end;">
        <label style="display:flex;align-items:center;gap:6px;font-size:11px;color:#6b7280;">
          <span>売</span>
          <span style="font-size:16px;color:#111827;">¥</span>
          <input type="number" min="0" step="1" inputmode="decimal"
                 class="pos-price-input pos-sell-input"
                 data-pid="${pid}" data-field="price"
                 value="${sell||''}"
                 style="width:110px;font-size:20px;font-weight:700;text-align:right;padding:2px 6px;border:1px solid #e5e7eb;border-radius:6px;background:#fff;color:#111827;">
        </label>
        <label style="display:flex;align-items:center;gap:6px;font-size:11px;color:#6b7280;">
          <span>買</span>
          <span style="font-size:14px;color:#6b7280;">¥</span>
          <input type="number" min="0" step="1" inputmode="decimal"
                 class="pos-price-input pos-buy-input"
                 data-pid="${pid}" data-field="cost"
                 value="${buy||''}"
                 style="width:110px;font-size:16px;font-weight:600;text-align:right;padding:2px 6px;border:1px solid #e5e7eb;border-radius:6px;background:#fff;color:#374151;">
        </label>
      </div>`;
  }
  else if(mode==='stock'){const cls=stock===0?'color:#ef4444':'color:#16a34a';valHTML=`<span class="pos-val-click pos-ar-under font-bold" style="${cls};font-size:26px;" data-pid="${pid}">${stock===0?esc(I18N.outShort):stock}</span>`;}
  else if(mode==='weight'){const w=parseFloat(row.weight_kg||row.weight)||0;valHTML=`<span class="pos-val-click pos-ar-under font-bold text-gray-800 dark:text-gray-100" style="font-size:24px;" data-pid="${pid}">${w?w+' kg':'—'}</span>`;}
  else if(mode==='category'){const lbl=(row.category_names&&row.category_names.length)?row.category_names.join(', '):(row.category_name||'—');valHTML=`<span class="pos-cat-click pos-ar-dashed font-semibold text-gray-800 dark:text-gray-100" data-pid="${pid}" title="${esc(lbl)}" style="display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%;font-size:18px;">${esc(lbl)}</span>`;}
  else if(mode==='brand'){const lbl=row.brand||'—';valHTML=`<span class="pos-brand-click pos-ar-dashed font-semibold text-gray-800 dark:text-gray-100" data-pid="${pid}" style="display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:18px;">${esc(lbl)}</span>`;}
  else if(mode==='color'){const lbl=row.color||'—';valHTML=`<span class="pos-color-click pos-ar-dashed font-semibold text-gray-800 dark:text-gray-100" data-pid="${pid}" style="display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:18px;">${esc(lbl)}</span>`;}
  const toggleBg=active?'#22c55e':'#d1d5db';
  const togLeft=active?'calc(100% - 26px)':'2px';
  const reserved = parseInt(row.reserved)||0;
  const resvBadge = reserved > 0
    ? `<span class="pm-reserved-corner" data-pid="${pid}" data-resv="1" title="予約 ${reserved} 件 — クリックで詳細">予約 ${reserved}</span>`
    : '';
  div.innerHTML=`
    <div class="pos-thumb pos-act-thumb" data-pid="${pid}" style="position:relative;width:112px;height:112px;flex-shrink:0;border-radius:10px;overflow:hidden;background:#f3f4f6;cursor:pointer;">
      ${resvBadge}
      ${row.image_url?`<img src="${esc(row.image_url)}" style="width:100%;height:100%;object-fit:cover;" loading="lazy">`:`<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:12px;color:#9ca3af;">N/A</div>`}
    </div>
    <div class="pos-row-body" style="flex:1;min-width:0;display:flex;flex-direction:row;align-items:center;gap:12px;justify-content:space-between;padding:0 12px;position:relative;align-self:stretch;">
      <div class="pos-name-click" data-pid="${pid}" style="flex:1;min-width:0;cursor:pointer;">
        <p style="font-size:15px;font-weight:600;color:#1f2937;word-break:break-word;overflow-wrap:anywhere;" class="dark:text-white">${esc(row.name||'')}</p>
        <p style="font-size:12px;color:#9ca3af;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(row.sku||'')}</p>
      </div>
      <div class="pos-val-col" style="flex-shrink:0;max-width:45%;overflow:hidden;text-align:right;line-height:1.1;margin-right:58px;">${valHTML}</div>
      <label class="pos-corner-toggle" style="position:absolute;top:0;right:4px;cursor:pointer;display:flex;align-items:center;">
        <input type="checkbox" class="pos-status-toggle sr-only" data-pid="${pid}" ${active?'checked':''}>
        <div style="width:52px;height:30px;border-radius:15px;background:${toggleBg};position:relative;transition:background .2s;">
          <div style="width:24px;height:24px;border-radius:50%;background:#fff;position:absolute;top:3px;left:${togLeft};transition:left .2s;box-shadow:0 1px 4px rgba(0,0,0,.25);"></div>
        </div>
      </label>
      <label class="pos-corner-chk" style="position:absolute;bottom:0;right:4px;cursor:pointer;display:flex;align-items:center;justify-content:center;">
        <input type="checkbox" class="pos-chk sr-only" data-pid="${pid}" ${isSel?'checked':''}>
        <div class="pos-chk-box" data-pid="${pid}" style="width:22px;height:22px;border-radius:4px;border:2px solid ${isSel?'#2563eb':'#d1d5db'};background:${isSel?'#2563eb':'transparent'};display:flex;align-items:center;justify-content:center;">
          ${isSel?'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>':''}
        </div>
      </label>
    </div>
    `;
  return div;
}

/* ── EVENTS ── */
document.addEventListener('click',function(e){
  // Inline price inputs handle themselves — do not let the click bubble to
  // pos-name-click / openEditPanel, which would re-open the modal.
  if(e.target.closest('.pos-price-inline')) return;
  const pidToIdx=pid=>filtered.findIndex(r=>String(r.id)===String(pid));
  const resvBadge=e.target.closest('.pm-reserved-corner');
  if(resvBadge){e.stopPropagation();e.preventDefault();if(typeof window.pmShowReservations==='function')window.pmShowReservations(resvBadge.dataset.pid);return;}
  const chkBox=e.target.closest('.pos-chk-box');
  if(chkBox){const pid=String(chkBox.dataset.pid);selected.has(pid)?selected.delete(pid):selected.add(pid);updateBulkBar();applyFilterSort();renderRows();return;}
  const tog=e.target.closest('.pos-status-toggle');
  if(tog){e.stopPropagation();const pid=tog.dataset.pid,ns=tog.checked?1:0;const row=allData.find(r=>r.id==pid);if(row)row.status=ns;persistNow(pid,{status:ns}).then(()=>toast(I18N.saveSuccess)).catch(()=>toast(I18N.saveFailed,'error'));applyFilterSort();renderRows();return;}
  const menuBtn=e.target.closest('.pos-menu-btn');
  if(menuBtn){e.stopPropagation();closeAllDD();const dd=document.querySelector(`[data-dropdown="${menuBtn.dataset.pid}"]`);if(dd)dd.style.display='block';return;}
  const valClick=e.target.closest('.pos-val-click');
  if(valClick){openEditPanel(pidToIdx(valClick.dataset.pid),mode);closeAllDD();return;}
  const catClick=e.target.closest('.pos-cat-click');
  if(catClick){openCatModal(pidToIdx(catClick.dataset.pid));closeAllDD();return;}
  const brandClick=e.target.closest('.pos-brand-click');
  if(brandClick){openBrandModal(pidToIdx(brandClick.dataset.pid));closeAllDD();return;}
  const colorClick=e.target.closest('.pos-color-click');
  if(colorClick){openColorModal(pidToIdx(colorClick.dataset.pid));closeAllDD();return;}
  const nameClick=e.target.closest('.pos-name-click');
  if(nameClick){openEditPanel(pidToIdx(nameClick.dataset.pid),'name');closeAllDD();return;}
  const thumb=e.target.closest('.pos-act-thumb');
  if(thumb){openEditPanel(pidToIdx(thumb.dataset.pid),'name');closeAllDD();return;}
  const priceBtn=e.target.closest('.pos-act-price');if(priceBtn){closeAllDD();openEditPanel(pidToIdx(priceBtn.dataset.pid),'price');return;}
  const stockBtn=e.target.closest('.pos-act-stock');if(stockBtn){closeAllDD();openEditPanel(pidToIdx(stockBtn.dataset.pid),'stock');return;}
  const catBtn=e.target.closest('.pos-act-cat');if(catBtn){closeAllDD();openCatModal(pidToIdx(catBtn.dataset.pid));return;}
  const brandBtn=e.target.closest('.pos-act-brand');if(brandBtn){closeAllDD();openBrandModal(pidToIdx(brandBtn.dataset.pid));return;}
  const colorBtn=e.target.closest('.pos-act-color');if(colorBtn){closeAllDD();openColorModal(pidToIdx(colorBtn.dataset.pid));return;}
  if(!e.target.closest('.pos-dropdown')&&!e.target.closest('.pos-menu-btn'))closeAllDD();
});

function closeAllDD(){document.querySelectorAll('.pos-dropdown').forEach(d=>d.style.display='none');}

/* ── EDIT PANEL — inline style show/hide prevents CSS class conflicts ── */
function openEditPanel(idx,field){
  if(idx<0||idx>=filtered.length)return;
  editIdx=idx;
  const row=filtered[idx];
  const panel=$('pos-edit-panel');
  panel.dataset.field=field;
  const labels={name:I18N.editName,price:I18N.editPrice,stock:I18N.editStock,weight:I18N.editWeight};
  $('pos-edit-label').textContent=labels[field]||field;
  $('pos-edit-product').textContent=row.name||'';
  const inp=$('pos-edit-input'), sel=$('pos-edit-select');
  // Always hide both first, then show only the relevant one
  hide(inp); hide(sel);
  const numeric=(field==='price'||field==='stock'||field==='weight');
  inp.type=numeric?'number':'text';
  inp.min=numeric?'0':'';
  inp.step=field==='price'?'0.01':field==='weight'?'0.001':'1';
  inp.value=field==='name'?(row.name||''):field==='price'?(row.price||''):field==='stock'?(row.stock||''):(row.weight_kg||row.weight||'');
  show(inp);
  $('pos-edit-backdrop').classList.remove('hidden');
  panel.classList.remove('hidden');
  setTimeout(()=>{inp.focus();try{inp.select();}catch(_){}},60);
}

window.posCloseEdit=function(){$('pos-edit-backdrop').classList.add('hidden');$('pos-edit-panel').classList.add('hidden');editIdx=-1;};
window.posEditNav=function(dir){const field=$('pos-edit-panel').dataset.field;const ni=editIdx+dir;if(ni>=0&&ni<filtered.length){posSaveEdit(false);openEditPanel(ni,field);}};
window.posSaveEdit=async function(close=true){
  // Snapshot everything up front. On touch devices a stray tap can close the
  // panel (resetting editIdx to -1) between tapping Save and this running —
  // which made saves silently no-op. Resolve the target row by id, not index.
  const idx=editIdx;
  if(idx<0||idx>=filtered.length){ toast(I18N.saveFailed,'error'); return; }
  const targetId=filtered[idx].id;
  const field=$('pos-edit-panel').dataset.field;
  const inp=$('pos-edit-input');
  const val=inp?inp.value:'';
  const payload={};
  if(field==='name')  payload.name=val;
  if(field==='price') payload.price=val;
  if(field==='stock') payload.stock=val;
  if(field==='weight')payload.weight_kg=val;
  if(Object.keys(payload).length===0){ if(close)posCloseEdit(); return; }
  try{
    await persistNow(targetId,payload);
    // Apply to local data only after the server confirms.
    [allData,filtered].forEach(arr=>{const r=arr.find(x=>x.id==targetId);if(r)Object.assign(r,payload);});
    toast(I18N.saveSuccess);
    applyFilterSort();renderRows();
    if(close)posCloseEdit();
  }catch(e){
    const status=e?.response?.status;
    console.error('[POS] save failed',status,e?.response?.data||e);
    toast(status?`${I18N.saveFailed} (${status})`:I18N.saveFailed,'error');
  }
};

/* ── BULK ── */
function updateBulkBar(){
  const bar=$('pos-bulk-bar'),cnt=$('pos-bulk-cnt');
  if(!bar)return;
  bar.classList.toggle('hidden', selected.size===0);
  if(cnt)cnt.textContent=selected.size;
}
window.posOpenBulk=function(){
  if(selected.size===0)return;
  // Stock/price/weight: skip modal, apply inline via row update + dirty bar
  if(mode==='stock'||mode==='price'||mode==='weight'){
    const promptMsg=mode==='price'?I18N.enterPrice:mode==='stock'?I18N.enterStock:I18N.enterWeight;
    const raw=window.prompt(promptMsg+' ('+I18N.selectedCount.replace(':count', selected.size)+')','');
    if(raw===null||raw==='')return;
    const num=parseFloat(raw);
    if(isNaN(num)||num<0){toast(I18N.invalid,'error');return;}
    const payload={};
    if(mode==='price')payload.price=num;
    if(mode==='stock')payload.stock=num;
    if(mode==='weight')payload.weight_kg=num;
    posBulkApply(payload);
    return;
  }
  const titles={category:I18N.bulkCategory,brand:I18N.bulkBrand,color:I18N.bulkColor};
  $('pos-bulk-title').textContent=titles[mode]||@json(trans('admin::app.catalog.products.views.bulk-update-title'));
  $('pos-bulk-sub').textContent=I18N.selectedCount.replace(':count', selected.size);
  const inp=$('pos-bulk-input'),sel=$('pos-bulk-select');
  hide(inp); hide(sel);
  if(mode==='category'){
    show(sel); sel.innerHTML=`<option value="">${esc(I18N.unset)}</option>`;
    categories.forEach(c=>{const o=document.createElement('option');o.value=c.id;o.textContent=c.name;sel.appendChild(o);});
  } else if(mode==='brand'){
    show(sel); sel.innerHTML=`<option value="">${esc(I18N.unset)}</option>`;
    brands.forEach(b=>{const o=document.createElement('option');o.value=b.id;o.textContent=b.name;sel.appendChild(o);});
  } else if(mode==='color'){
    show(sel); sel.innerHTML=`<option value="">${esc(I18N.unset)}</option>`;
    colorOptions.forEach(c=>{const o=document.createElement('option');o.value=c.id;o.textContent=c.name;sel.appendChild(o);});
  }
  $('pos-bulk-modal').classList.remove('hidden');$('pos-bulk-modal').classList.add('flex');
};
async function posBulkApply(payload){
  const ids=[...selected];let ok=0,fail=0;
  for(const pid of ids){
    try{
      await persistNow(pid,payload);
      const row=allData.find(r=>r.id==pid);
      if(row)Object.assign(row,payload);
      ok++;
    }catch(_){fail++;}
  }
  selected.clear();updateBulkBar();applyFilterSort();renderRows();
  toast(I18N.updatedCount.replace(':count', ok)+(fail?' '+I18N.failedCount.replace(':count', fail):''));
}
window.posCloseBulk=function(){$('pos-bulk-modal').classList.add('hidden');$('pos-bulk-modal').classList.remove('flex');};
window.posExecBulk=async function(){
  const inp=$('pos-bulk-input'),sel=$('pos-bulk-select');
  let payload={};
  if(mode==='category'){if(!sel.value)return;payload.categories=[sel.value];}
  else if(mode==='brand'){payload.brand=sel.value||'';}
  else if(mode==='color'){payload.color=sel.value||'';}
  else{const num=parseFloat(inp.value);if(isNaN(num)||num<0){toast(I18N.invalid,'error');return;}if(mode==='price')payload.price=num;if(mode==='stock')payload.stock=num;if(mode==='weight')payload.weight_kg=num;}
  posCloseBulk();
  const ids=[...selected];let ok=0,fail=0;
  for(const pid of ids){
    try{
      await persistNow(pid,payload);
      const row=allData.find(r=>r.id==pid);
      if(row){
        if(mode==='category'){row.category_ids=[sel.value];row.category_name=categories.find(c=>c.id===sel.value)?.name||'';}
        else if(mode==='brand'){row.brand=brands.find(b=>b.id===sel.value)?.name||'';}
        else if(mode==='color'){row.color=colorOptions.find(c=>c.id===sel.value)?.name||'';}
        else Object.assign(row,payload);
      }
      ok++;
    }catch(_){fail++;}
  }
  selected.clear();updateBulkBar();applyFilterSort();renderRows();
  toast(I18N.updatedCount.replace(':count', ok)+(fail?' '+I18N.failedCount.replace(':count', fail):''));
};

/* ── CATEGORY MODAL ── */
let catModalIdx=-1,catModalChecked=new Set(),catFilterQ='';
function openCatModal(idx){
  if(idx<0||idx>=filtered.length)return;
  catModalIdx=idx;
  const row=filtered[idx];
  catModalChecked=new Set((Array.isArray(row.category_ids)?row.category_ids:[]).map(String).filter(Boolean));
  catFilterQ='';
  if($('pos-cat-product-name'))$('pos-cat-product-name').textContent=row.name||'';
  if($('pos-cat-search'))$('pos-cat-search').value='';
  renderCatList();
  $('pos-cat-modal').classList.remove('hidden');$('pos-cat-modal').classList.add('flex');
  setTimeout(()=>$('pos-cat-search')?.focus(),80);
}
window.posCloseCatModal=function(){$('pos-cat-modal').classList.add('hidden');$('pos-cat-modal').classList.remove('flex');catModalIdx=-1;};
window.posCatFilter=function(q){catFilterQ=(q||'').toLowerCase().trim();renderCatList();};
window.posCatSelectAll=function(chk){getVisCats().forEach(c=>chk?catModalChecked.add(String(c.id)):catModalChecked.delete(String(c.id)));renderCatList();};
function getVisCats(){return catFilterQ?categories.filter(c=>(c.name||'').toLowerCase().includes(catFilterQ)):categories;}
function renderCatList(){
  const list=$('pos-cat-list');if(!list)return;
  const vis=getVisCats();
  const allChk=$('pos-cat-all');if(allChk)allChk.checked=vis.length>0&&vis.every(c=>catModalChecked.has(String(c.id)));
  const cnt=$('pos-cat-count');if(cnt)cnt.textContent=catModalChecked.size>0?I18N.selectedCount.replace(':count', catModalChecked.size):'';
  if(vis.length===0){list.innerHTML=`<p style="text-align:center;font-size:12px;color:#9ca3af;padding:24px 0;">${esc(categories.length===0?I18N.loadingCats:I18N.notFound)}</p>`;return;}
  const frag=document.createDocumentFragment();
  vis.forEach(c=>{
    const chk=catModalChecked.has(String(c.id));
    const lbl=document.createElement('label');
    lbl.style.cssText='display:flex;align-items:center;gap:10px;padding:10px 4px;cursor:pointer;border-bottom:1px solid #f3f4f6;';
    lbl.innerHTML=`<input type="checkbox" class="pos-cat-chk accent-blue-600" style="width:16px;height:16px;flex-shrink:0;" value="${c.id}" ${chk?'checked':''}><span style="font-size:14px;flex:1;">${esc(c.name)}</span>`;
    lbl.querySelector('.pos-cat-chk').addEventListener('change',function(){this.checked?catModalChecked.add(this.value):catModalChecked.delete(this.value);renderCatList();});
    frag.appendChild(lbl);
  });
  list.innerHTML='';list.appendChild(frag);
}
window.posSaveCatModal=async function(){
  if(catModalIdx<0)return;
  const row=filtered[catModalIdx];
  const btn=$('pos-cat-save-btn');if(btn){btn.disabled=true;btn.textContent=I18N.saving;}
  const ids=[...catModalChecked].map(Number).filter(Boolean);
  try{
    await persistNow(row.id,{categories:ids.length?ids:['']});
    row.category_ids=ids.map(String);
    row.category_names=ids.map(id=>categories.find(c=>c.id==id)?.name||'').filter(Boolean);
    row.category_name=row.category_names[0]||'';
    const orig=allData.find(r=>r.id==row.id);if(orig)Object.assign(orig,{category_ids:row.category_ids,category_names:row.category_names,category_name:row.category_name});
    applyFilterSort();renderRows();posCloseCatModal();toast(I18N.saveSuccess);
  }catch(e){toast(I18N.saveFailed,'error');}
  finally{if(btn){btn.disabled=false;btn.textContent=I18N.save;}}
};

/* ── BRAND MODAL ── */
let brandModalIdx=-1,brandModalSelected=null,brandFilterQ='';
function openBrandModal(idx){
  if(idx<0||idx>=filtered.length)return;
  brandModalIdx=idx;
  const row=filtered[idx];
  brandModalSelected=String(row.brand_id||row.brand||'');
  brandFilterQ='';
  if($('pos-brand-product-name'))$('pos-brand-product-name').textContent=row.name||'';
  if($('pos-brand-search'))$('pos-brand-search').value='';
  renderBrandList();
  $('pos-brand-modal').classList.remove('hidden');$('pos-brand-modal').classList.add('flex');
  setTimeout(()=>$('pos-brand-search')?.focus(),80);
}
window.posCloseBrandModal=function(){$('pos-brand-modal').classList.add('hidden');$('pos-brand-modal').classList.remove('flex');brandModalIdx=-1;};
window.posBrandFilter=function(q){brandFilterQ=(q||'').toLowerCase().trim();renderBrandList();};
function getVisBrands(){return brandFilterQ?brands.filter(b=>(b.name||'').toLowerCase().includes(brandFilterQ)):brands;}
function renderBrandList(){
  const list=$('pos-brand-list');if(!list)return;
  const vis=getVisBrands();
  if(vis.length===0){list.innerHTML=`<p style="text-align:center;font-size:12px;color:#9ca3af;padding:24px 0;">${esc(brands.length===0?I18N.loadingBrands:I18N.notFound)}</p>`;return;}
  const frag=document.createDocumentFragment();
  vis.forEach(b=>{
    const chk=brandModalSelected===String(b.id)||brandModalSelected===b.name;
    const lbl=document.createElement('label');
    lbl.style.cssText='display:flex;align-items:center;gap:10px;padding:10px 4px;cursor:pointer;border-bottom:1px solid #f3f4f6;';
    lbl.innerHTML=`<input type="radio" name="pos-brand-radio" class="pos-brand-radio accent-green-600" style="width:16px;height:16px;flex-shrink:0;" value="${b.id}" ${chk?'checked':''}><span style="font-size:14px;flex:1;">${esc(b.name)}</span>`;
    lbl.querySelector('.pos-brand-radio').addEventListener('change',function(){brandModalSelected=this.value;});
    frag.appendChild(lbl);
  });
  list.innerHTML='';list.appendChild(frag);
}
window.posSaveBrandModal=async function(){
  if(brandModalIdx<0)return;
  const row=filtered[brandModalIdx];
  const btn=$('pos-brand-save-btn');if(btn){btn.disabled=true;btn.textContent=I18N.saving;}
  try{
    await persistNow(row.id,{brand:brandModalSelected||''});
    row.brand_id=brandModalSelected;
    row.brand=brands.find(b=>b.id===brandModalSelected)?.name||brandModalSelected||'';
    const orig=allData.find(r=>r.id==row.id);if(orig){orig.brand=row.brand;orig.brand_id=row.brand_id;}
    applyFilterSort();renderRows();posCloseBrandModal();toast(I18N.saveSuccess);
  }catch(e){toast(I18N.saveFailed,'error');}
  finally{if(btn){btn.disabled=false;btn.textContent=I18N.save;}}
};

/* ── COLOR MODAL ── */
let colorModalIdx=-1,colorModalChecked=new Set(),colorFilterQ='';
function openColorModal(idx){
  if(idx<0||idx>=filtered.length)return;
  colorModalIdx=idx;
  const row=filtered[idx];
  let pre=[];
  if(Array.isArray(row.color_ids))pre=row.color_ids;
  else if(row.color)pre=String(row.color).split(',').map(s=>s.trim()).filter(Boolean);
  colorModalChecked=new Set(pre.map(String).filter(Boolean));
  colorFilterQ='';
  if($('pos-color-product-name'))$('pos-color-product-name').textContent=row.name||'';
  if($('pos-color-search'))$('pos-color-search').value='';
  renderColorList();
  $('pos-color-modal').classList.remove('hidden');$('pos-color-modal').classList.add('flex');
  setTimeout(()=>$('pos-color-search')?.focus(),80);
}
window.posCloseColorModal=function(){$('pos-color-modal').classList.add('hidden');$('pos-color-modal').classList.remove('flex');colorModalIdx=-1;};
window.posColorFilter=function(q){colorFilterQ=(q||'').toLowerCase().trim();renderColorList();};
window.posColorSelectAll=function(chk){getVisColors().forEach(c=>chk?colorModalChecked.add(String(c.id)):colorModalChecked.delete(String(c.id)));renderColorList();};
function getVisColors(){return colorFilterQ?colorOptions.filter(c=>(c.name||'').toLowerCase().includes(colorFilterQ)):colorOptions;}
function renderColorList(){
  const list=$('pos-color-list');if(!list)return;
  const vis=getVisColors();
  const allEl=$('pos-color-all'),cntEl=$('pos-color-count');
  if(cntEl)cntEl.textContent=colorModalChecked.size>0?I18N.selectedCount.replace(':count', colorModalChecked.size):'';
  if(allEl)allEl.checked=vis.length>0&&vis.every(c=>colorModalChecked.has(String(c.id)));
  if(vis.length===0){list.innerHTML=`<p style="text-align:center;font-size:12px;color:#9ca3af;padding:24px 0;">${esc(colorOptions.length===0?I18N.loadingColors:I18N.notFound)}</p>`;return;}
  const frag=document.createDocumentFragment();
  vis.forEach(c=>{
    const chk=colorModalChecked.has(String(c.id));
    const lbl=document.createElement('label');
    lbl.style.cssText='display:flex;align-items:center;gap:10px;padding:10px 4px;cursor:pointer;border-bottom:1px solid #f3f4f6;';
    lbl.innerHTML=`<input type="checkbox" class="pos-color-chk accent-purple-600" style="width:16px;height:16px;flex-shrink:0;" value="${c.id}" ${chk?'checked':''}><span style="font-size:14px;flex:1;">${esc(c.name)}</span>`;
    lbl.querySelector('.pos-color-chk').addEventListener('change',function(){this.checked?colorModalChecked.add(this.value):colorModalChecked.delete(this.value);renderColorList();});
    frag.appendChild(lbl);
  });
  list.innerHTML='';list.appendChild(frag);
}
window.posSaveColorModal=async function(){
  if(colorModalIdx<0)return;
  const row=filtered[colorModalIdx];
  const btn=$('pos-color-save-btn');if(btn){btn.disabled=true;btn.textContent=I18N.saving;}
  const ids=[...colorModalChecked];
  try{
    await persistNow(row.id,{colors:ids.length?ids:[''],color:ids.length?ids[0]:''});
    row.color_ids=ids;
    row.color=ids.map(id=>colorOptions.find(c=>c.id===id)?.name||id).join(', ');
    const orig=allData.find(r=>r.id==row.id);if(orig){orig.color_ids=ids;orig.color=row.color;}
    applyFilterSort();renderRows();posCloseColorModal();toast(I18N.saveSuccess);
  }catch(e){toast(I18N.saveFailed,'error');}
  finally{if(btn){btn.disabled=false;btn.textContent=I18N.save;}}
};

/* ── PERSIST ── */
async function persistNow(pid,fields){
  const ch=$('pos-channel')?.value||'';
  const fd=new FormData();
  fd.append('id',pid);fd.append('channel',ch);
  for(const [k,v] of Object.entries(fields)){
    if(Array.isArray(v)){v.length===0?fd.append(k+'[]',''):v.forEach(i=>fd.append(k+'[]',i));}
    else fd.append(k,v??'');
  }
  return window.axios.post(API_UPDATE,fd,{headers:{'X-CSRF-TOKEN':CSRF}});
}

/* ── DIRTY BAR ── */
document.addEventListener('click',function(e){
  if(e.target.id==='pos-dirty-save')   flushDirty();
  if(e.target.id==='pos-dirty-cancel') cancelDirty();
});
function flushDirty(){toast(I18N.saveSuccess,'');}
function cancelDirty(){applyFilterSort();renderRows();$('pos-dirty-bar').classList.add('hidden');}

/* ── SEARCH ── */
let searchTimer;
document.addEventListener('input',function(e){
  if(e.target.id!=='pos-search')return;
  clearTimeout(searchTimer);
  searchTimer=setTimeout(()=>{searchQ=e.target.value.trim();applyFilterSort();renderRows();},280);
});

/* ── INFINITE SCROLL ── */
function setupObserver(){
  const s=$('pos-sentinel');if(!s)return;
  new IntersectionObserver(entries=>{
    if(entries[0].isIntersecting&&!loading&&!ended)fetchMore(false);
  },{rootMargin:'200px'}).observe(s);
}

/* ── KEYBOARD ── */
document.addEventListener('keydown',function(e){
  if(e.key==='Escape'){posCloseEdit();posCloseBulk();posCloseCatModal();posCloseBrandModal();posCloseColorModal();}
  if(e.key==='Enter'&&!$('pos-edit-panel').classList.contains('hidden')){e.preventDefault();posSaveEdit();}
});

/* ── INLINE PRICE EDIT (price mode rows) ── */
// Save on blur or Enter. Both inputs (sell=price, buy=cost) share one path.
// Only persist when the value actually changed vs. the row's current value,
// so passive taps don't generate save traffic.
async function posInlinePriceSave(inp){
  const pid=inp.dataset.pid, field=inp.dataset.field;
  const row=allData.find(r=>String(r.id)===String(pid)); if(!row) return;
  const raw=inp.value.trim();
  const num=raw===''?0:parseFloat(raw);
  if(isNaN(num)||num<0){toast(I18N.invalid,'error');inp.value=parseFloat(row[field])||'';return;}
  if((parseFloat(row[field])||0)===num) return;
  inp.disabled=true;
  try{
    await persistNow(pid,{[field]:num});
    row[field]=num;
    const f=filtered.find(r=>String(r.id)===String(pid)); if(f)f[field]=num;
    toast(I18N.saveSuccess);
  }catch(err){
    console.error('[POS] inline price save failed',err);
    toast(I18N.saveFailed,'error');
    inp.value=parseFloat(row[field])||'';
  }finally{inp.disabled=false;}
}
document.addEventListener('change',function(e){
  const inp=e.target.closest('.pos-price-input'); if(!inp) return;
  posInlinePriceSave(inp);
});
document.addEventListener('keydown',function(e){
  if(e.key!=='Enter') return;
  const inp=e.target.closest('.pos-price-input'); if(!inp) return;
  e.preventDefault(); inp.blur();
});

/* ── TOAST ── */
function toast(msg,type){
  const t=document.createElement('div');
  t.style.cssText=`position:fixed;bottom:24px;right:20px;z-index:99999;padding:10px 18px;border-radius:10px;color:#fff;font-size:14px;font-weight:500;background:${type==='error'?'#dc2626':'#16a34a'};box-shadow:0 4px 14px rgba(0,0,0,.2);`;
  t.textContent=msg;document.body.appendChild(t);
  setTimeout(()=>{t.style.opacity='0';t.style.transition='opacity .3s';setTimeout(()=>t.remove(),300);},2500);
}

/* ── INIT ── */
function showFatal(msg){
  const grid=$('pos-grid');if(grid)grid.innerHTML=`<div class="px-4 py-8 text-center text-sm text-red-600">${esc(msg)}</div>`;
  const sentinel=$('pos-sentinel');if(sentinel)sentinel.style.display='none';
}

async function boot(){
  if(initialized)return; initialized=true;
  const start=Date.now();
  while(!window.axios){
    if(Date.now()-start>10000){showFatal(I18N.axiosFailed);return;}
    await new Promise(r=>setTimeout(r,50));
  }
  try{
    mode='stock';
    document.querySelectorAll('.pos-tab-btn').forEach(b=>{
      b.classList.toggle('is-active', b.dataset.mode==='stock');
    });
    const vh=$('pos-val-header');if(vh)vh.textContent=I18N.stock;
    const svb=$('pos-sort-val-btn');if(svb)svb.textContent=I18N.stock;
    await fetchMore(true);
    setupObserver();
    // Safety net: keep pulling pages until everything is loaded, in case the
    // IntersectionObserver never fires (content shorter than viewport, or
    // certain iPad WebKit builds). Without this the grid could appear to
    // "stop" partway even though more products exist.
    await loadAllRemaining();
    // A scan from another admin page lands here with ?scan=<sku>. Filter to it
    // and flash-highlight the matching row (same UX as scanning while on POS).
    applyScanFromUrl();
  }catch(e){
    console.error('[POS] init failed',e);
    showFatal(I18N.initFailed.replace(':message', (e?.message||e)));
  }
}

if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',boot);}
else{boot();}

/* ── BARCODE SCAN: filter to the matched product, scroll into view, flash row ── */
window.addEventListener('pos:barcode-resolved', function(ev){
  const d = ev.detail||{};
  if(!d.found || !d.product){ toast('No product for '+d.barcode, 'error'); return; }
  const pid = String(d.product.id);
  // Pin the search box to SKU so the user sees what was filtered
  const sku = d.product.sku || '';
  const searchEl = $('pos-search');
  if(searchEl && sku){ searchEl.value = sku; searchQ = sku; }
  // If the scanned product isn't in the loaded slice, we need to load by SKU
  const have = allData.find(r=>String(r.id)===pid);
  if(!have && sku){
    // Re-fetch with the SKU as the query so backend returns it
    (async ()=>{
      offset=0; allData=[]; ended=false;
      await fetchMore(true);
      flashRow(pid);
    })();
    return;
  }
  applyFilterSort(); renderRows();
  flashRow(pid);
});
function flashRow(pid){
  setTimeout(()=>{
    const row = document.querySelector('.pos-row[data-id="'+pid+'"]');
    if(!row) return;
    row.scrollIntoView({behavior:'smooth', block:'center'});
    row.classList.add('bcs-hit');
    setTimeout(()=>row.classList.remove('bcs-hit'), 1600);
  }, 60);
}

/* A barcode scanned on another admin page redirects here with ?scan=<sku>.
   Filter the grid to it and highlight the matching row, then strip the param
   so a manual reload doesn't keep re-filtering. */
function applyScanFromUrl(){
  let scan='';
  try{ scan=new URLSearchParams(window.location.search).get('scan')||''; }catch(_){}
  if(!scan) return;
  const searchEl=$('pos-search');
  if(searchEl){ searchEl.value=scan; }
  searchQ=scan;
  applyFilterSort(); renderRows();
  const hit=allData.find(r=>String(r.sku||'').toLowerCase()===scan.toLowerCase());
  if(hit) flashRow(String(hit.id));
  else toast('No product for '+scan,'error');
  // Clean the URL (keep history entry; just drop the query)
  try{ window.history.replaceState({}, '', window.location.pathname); }catch(_){}
}

})();
</script>
@endpush
</x-admin::layouts>
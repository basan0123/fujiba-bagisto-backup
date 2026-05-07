@php
    $reorderLabels = [
        'searchPh'      => __('reorder::app.ui.search-ph'),
        'sortBuyAtAsc'  => __('reorder::app.ui.sort-buyat-asc'),
        'sortBuyAtDesc' => __('reorder::app.ui.sort-buyat-desc'),
        'image'         => __('reorder::app.ui.image'),
        'name'          => __('reorder::app.ui.name'),
        'stock'         => __('reorder::app.ui.stock'),
        'buyAt'         => __('reorder::app.ui.buy-at'),
        'cost'          => __('reorder::app.ui.cost'),
        'sale'          => __('reorder::app.ui.sale'),
        'waste'         => __('reorder::app.ui.waste'),
        'suggested'     => __('reorder::app.ui.suggested'),
        'wishQty'       => __('reorder::app.ui.wish-qty'),
        'subtotal'      => __('reorder::app.ui.subtotal'),
        'total'         => __('reorder::app.ui.total'),
        'clipboard'     => __('reorder::app.ui.clipboard'),
        'clipEmpty'     => __('reorder::app.ui.clip-empty'),
        'empty'         => __('reorder::app.ui.empty'),
        'cancel'        => __('reorder::app.ui.cancel'),
        'add'           => __('reorder::app.ui.add'),
        'save'          => __('reorder::app.ui.save'),
        'saving'        => '...',
        'print'         => __('reorder::app.ui.print'),
        'saved'         => __('reorder::app.ui.saved'),
    ];
@endphp

<script>
    window.__reorderLabels = @json($reorderLabels);
</script>

<div v-pre id="rg-root" class="reorder-grid">
    <div class="rg-grid">
        <div class="flex gap-2 mb-2 items-center">
            <input id="reorder-search" type="text" placeholder="{{ $reorderLabels['searchPh'] }}"
                   class="px-2 py-1 border rounded text-sm flex-1" />
            <span id="reorder-grid-count"
                  class="px-2 py-1 text-xs rounded bg-blue-50 text-blue-700 font-semibold whitespace-nowrap">
                <span id="reorder-grid-sel">0</span>/<span id="reorder-grid-total">0</span>
            </span>
            <a href="{{ route('admin.inventory.reorder.history') }}"
               class="px-3 py-1 text-sm bg-gray-100 rounded hover:bg-gray-200">History</a>
        </div>

        <div class="rg-grid-scroll">
            <table class="reorder-table w-full" id="reorder-grid-table">
                <thead>
                    <tr>
                        <th><input id="reorder-all" type="checkbox"/></th>
                        <th data-sort="image">{{ $reorderLabels['image'] }}</th>
                        <th data-sort="name">{{ $reorderLabels['name'] }} <span class="sort-arr"></span></th>
                        <th data-sort="stock">{{ $reorderLabels['stock'] }} <span class="sort-arr"></span></th>
                        <th data-sort="pending_qty">Pending <span class="sort-arr"></span></th>
                        <th data-sort="buy_at">{{ $reorderLabels['buyAt'] }} <span class="sort-arr"></span></th>
                        <th data-sort="cost">{{ $reorderLabels['cost'] }} <span class="sort-arr"></span></th>
                        <th data-sort="sale_count">{{ $reorderLabels['sale'] }} <span class="sort-arr"></span></th>
                        <th data-sort="waste_count">{{ $reorderLabels['waste'] }} <span class="sort-arr"></span></th>
                        <th data-sort="suggested_qty">{{ $reorderLabels['suggested'] }} <span class="sort-arr"></span></th>
                    </tr>
                </thead>
                <tbody id="reorder-rows">
                    <tr><td colspan="10" class="text-center py-6 text-gray-400">...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div id="rg-splitter" class="rg-splitter" title="Drag to resize"></div>

    <aside id="rg-clip" class="rg-clip">
        <div id="rg-clip-handle" class="rg-clip-handle">
            <span>クリップボード <span id="reorder-mini-count" class="ml-1 px-2 py-0.5 rounded-full bg-white/20">0</span></span>
            <span id="rg-clip-handle-arrow">▲</span>
        </div>
        <div class="reorder-clip">
        <h3 class="font-bold mb-2 flex items-center justify-between">
            <span>{{ $reorderLabels['clipboard'] }}</span>
            <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700">
                <span id="reorder-clip-count">0</span> items
            </span>
        </h3>

        <div class="flex gap-2 mb-2 items-center">
            <input id="reorder-bulk-qty" type="number" min="1" placeholder="qty"
                   class="px-2 py-1 border rounded text-sm w-20" />
            <button id="reorder-bulk-fill" class="px-2 py-1 text-xs bg-gray-200 rounded">
                Fill selected/all
            </button>
            <span class="ml-auto text-xs text-gray-500 whitespace-nowrap">
                sel <span id="reorder-clip-sel" class="font-semibold text-gray-700">0</span>
            </span>
        </div>

        <div class="rg-clip-scroll">
            <table class="reorder-table w-full" id="reorder-clip-table">
                <thead>
                    <tr>
                        <th><input id="reorder-clip-allhead" type="checkbox"/></th>
                        <th data-sort="image"></th>
                        <th data-sort="name">{{ $reorderLabels['name'] }} <span class="sort-arr"></span></th>
                        <th data-sort="stock">Stock <span class="sort-arr"></span></th>
                        <th data-sort="pending_qty">Pending <span class="sort-arr"></span></th>
                        <th data-sort="wish_qty">{{ $reorderLabels['wishQty'] }} <span class="sort-arr"></span></th>
                        <th data-sort="subtotal">{{ $reorderLabels['subtotal'] }} <span class="sort-arr"></span></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="reorder-clip"></tbody>
                <tfoot id="reorder-clip-foot"></tfoot>
            </table>
        </div>

        <div class="flex gap-2 mt-3">
            <button id="reorder-save" class="px-3 py-2 text-sm bg-blue-600 text-white rounded flex-1" disabled>
                Save batch <span id="reorder-save-count" class="ml-1 opacity-90"></span>
            </button>
            <button id="reorder-print" class="px-3 py-2 text-sm bg-gray-200 rounded" disabled>
                {{ $reorderLabels['print'] }}
            </button>
        </div>

        <div id="reorder-batch-bar" class="hidden mt-3 p-2 rounded bg-amber-50 border border-amber-200">
            <p class="text-xs text-gray-600 mb-2">
                Saved batch <span id="reorder-batch-id" class="font-bold"></span> — pending receive.
            </p>
            <a id="reorder-receive" href="#"
               class="block text-center w-full px-3 py-2 text-sm bg-green-600 text-white rounded">
                Start Receiving
            </a>
        </div>

        <p id="reorder-saved-msg" class="text-xs text-green-600 mt-2 hidden"></p>
        </div>
    </aside>
</div>

<div id="reorder-toast" class="fixed bottom-4 right-4 z-50 hidden rounded px-4 py-2 text-white text-sm shadow-xl"></div>

<div id="reorder-zoom" v-pre class="zoom-overlay" style="display:none"><img src="" alt=""/></div>

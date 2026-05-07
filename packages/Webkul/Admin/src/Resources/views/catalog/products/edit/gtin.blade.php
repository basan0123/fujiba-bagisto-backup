@php
    $productBarcode = app(\Webkul\Pos\Repositories\ProductBarcodeRepository::class)
        ->findOneWhere(['product_id' => $product->id]);

    $currentScanType = old('scan_type', $productBarcode?->scan_type ?? '');
    $currentGtin     = old('gtin',      $productBarcode?->gtin      ?? '');
@endphp

<!-- Barcode Settings Panel -->
<div
    class="box-shadow rounded bg-white p-4 dark:bg-gray-900"
    x-data="{ scanType: '{{ $currentScanType }}' }"
>
    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
        @lang('pos::app.admin.barcode-products.index.datagrid.barcode-settings')
    </p>

    {{-- Scan Type selector --}}
    <x-admin::form.control-group>
        <x-admin::form.control-group.label>
            @lang('pos::app.admin.barcode-products.index.datagrid.scan-type')
        </x-admin::form.control-group.label>

        <select
            name="scan_type"
            id="scan_type"
            x-model="scanType"
            class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
        >
            <option value="">@lang('pos::app.admin.barcode-products.index.datagrid.scan-type-none')</option>
            <option value="product_id" @selected($currentScanType === 'product_id')>
                @lang('pos::app.admin.barcode-products.index.datagrid.scan-type-product-id')
            </option>
            <option value="sku" @selected($currentScanType === 'sku')>
                @lang('pos::app.admin.barcode-products.index.datagrid.scan-type-sku')
            </option>
            <option value="gtin" @selected($currentScanType === 'gtin')>
                @lang('pos::app.admin.barcode-products.index.datagrid.scan-type-gtin')
            </option>
        </select>
    </x-admin::form.control-group>

    {{-- Product ID info (read-only) --}}
    <div x-show="scanType === 'product_id'" x-cloak class="mt-2 rounded-md bg-blue-50 px-3 py-2 text-sm text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
        @lang('pos::app.admin.barcode-products.index.datagrid.scan-type-product-id-info', ['id' => $product->id])
    </div>

    {{-- SKU info (read-only) --}}
    <div x-show="scanType === 'sku'" x-cloak class="mt-2 rounded-md bg-blue-50 px-3 py-2 text-sm text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
        @lang('pos::app.admin.barcode-products.index.datagrid.scan-type-sku-info', ['sku' => $product->sku])
    </div>

    {{-- GTIN manual entry --}}
    <div x-show="scanType === 'gtin'" x-cloak>
        <x-admin::form.control-group class="!mb-0 mt-2">
            <x-admin::form.control-group.label>
                @lang('pos::app.admin.barcode-products.index.datagrid.gtin')
            </x-admin::form.control-group.label>

            <input
                type="text"
                name="gtin"
                id="gtin"
                value="{{ $currentGtin }}"
                placeholder="{{ __('pos::app.admin.barcode-products.index.datagrid.gtin-placeholder') }}"
                class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
            >
        </x-admin::form.control-group>
    </div>
</div>

{{--
  Navigation bar for product views.
  @include('admin::catalog.products.partials.view-nav')
--}}
@php
    $currentRoute = request()->route()?->getName() ?? '';
    $views = [
        ['route' => 'admin.catalog.products.list_view',         'label' => trans('admin::app.catalog.products.views.list'),   'icon' => 'icon-product-listing'],
        ['route' => 'admin.catalog.products.pos_view',          'label' => trans('admin::app.catalog.products.views.pos'),    'icon' => 'icon-product-grid'],
        ['route' => 'admin.catalog.products.mobile_view',       'label' => trans('admin::app.catalog.products.views.mobile'), 'icon' => 'icon-mobile'],
        ['route' => 'admin.catalog.products.stock_quick_fix',   'label' => 'Stock Quick Fix',                                  'icon' => 'icon-product-grid'],
        ['route' => 'admin.catalog.products.take_stock',        'label' => 'Take Stock',                                       'icon' => 'icon-product-listing'],
        ['route' => 'admin.catalog.products.translations',      'label' => 'Translations',                                     'icon' => 'icon-language'],
    ];

    /**
     * Quick-filter chips for the main product listing. Each chip deep-links to
     * the index route with a single URL param; the datagrid forwards those
     * params to the backend where Fujiba\CatalogAdmin applies the filter. A chip
     * is "active" when its param matches the current request. Chips are
     * independent one-click toggles — clicking one replaces the current chip
     * filter (clicking an active chip clears it).
     */
    $indexUrl  = route('admin.catalog.products.index');
    $reqStock  = request('stock');
    $reqStatus = request('product_status');
    $reqMissing = request('missing_cost');

    $chips = [
        [
            'label'  => trans('catalog-admin::app.products.chips.in_stock'),
            'icon'   => 'icon-checked',
            'active' => $reqStock === 'in',
            'url'    => $reqStock === 'in' ? $indexUrl : $indexUrl.'?stock=in',
        ],
        [
            'label'  => trans('catalog-admin::app.products.chips.out_of_stock'),
            'icon'   => 'icon-cancel',
            'active' => $reqStock === 'out',
            'url'    => $reqStock === 'out' ? $indexUrl : $indexUrl.'?stock=out',
        ],
        [
            'label'  => trans('catalog-admin::app.products.chips.active'),
            'icon'   => 'icon-tick',
            'active' => $reqStatus === '1',
            'url'    => $reqStatus === '1' ? $indexUrl : $indexUrl.'?product_status=1',
        ],
        [
            'label'  => trans('catalog-admin::app.products.chips.inactive'),
            'icon'   => 'icon-cross',
            'active' => $reqStatus === '0',
            'url'    => $reqStatus === '0' ? $indexUrl : $indexUrl.'?product_status=0',
        ],
        [
            'label'  => trans('catalog-admin::app.products.chips.missing_cost'),
            'icon'   => 'icon-information',
            'active' => $reqMissing === '1',
            'url'    => $reqMissing === '1' ? $indexUrl : $indexUrl.'?missing_cost=1',
        ],
    ];

    $hasActiveChip = $reqStock !== null || $reqStatus !== null || $reqMissing !== null;
@endphp

<div class="mb-2 flex flex-wrap items-center gap-1 rounded-md border border-gray-200 bg-white p-1 text-xs dark:border-gray-800 dark:bg-gray-900 sm:mb-4 sm:gap-2 sm:p-2 sm:text-sm">
    <a
        href="{{ route('admin.catalog.products.index') }}"
        class="transparent-button !px-2 !py-1 !text-xs hover:bg-gray-100 dark:text-white dark:hover:bg-gray-950 sm:!px-3 sm:!py-1.5 sm:!text-sm"
    >
        <span class="icon-arrow-left rtl:icon-arrow-right text-base sm:text-xl"></span>
        @lang('admin::app.catalog.products.views.back')
    </a>

    <span class="hidden h-6 w-px bg-gray-200 dark:bg-gray-700 sm:inline-block"></span>

    @foreach($views as $v)
        @if($currentRoute === $v['route'])
            <a
                href="{{ route($v['route']) }}"
                class="primary-button !px-2 !py-1 !text-xs sm:!px-3 sm:!py-1.5 sm:!text-sm"
                aria-current="page"
            >
                {{ $v['label'] }}
            </a>
        @else
            <a
                href="{{ route($v['route']) }}"
                class="secondary-button !px-2 !py-1 !text-xs sm:!px-3 sm:!py-1.5 sm:!text-sm"
            >
                {{ $v['label'] }}
            </a>
        @endif
    @endforeach
</div>

@if($currentRoute === 'admin.catalog.products.index')
    <div class="mb-2 flex flex-wrap items-center gap-1 rounded-md border border-gray-200 bg-white p-1 text-xs dark:border-gray-800 dark:bg-gray-900 sm:mb-4 sm:gap-2 sm:p-2 sm:text-sm">
        <span class="ml-1 font-medium text-gray-500 dark:text-gray-300">
            @lang('catalog-admin::app.products.chips.label')
        </span>

        <span class="hidden h-6 w-px bg-gray-200 dark:bg-gray-700 sm:inline-block"></span>

        @foreach($chips as $chip)
            <a
                href="{{ $chip['url'] }}"
                @class([
                    '!px-2 !py-1 !text-xs sm:!px-3 sm:!py-1.5 sm:!text-sm',
                    'primary-button'   => $chip['active'],
                    'secondary-button' => ! $chip['active'],
                ])
                @if($chip['active']) aria-current="true" @endif
            >
                <span class="{{ $chip['icon'] }} text-base sm:text-lg"></span>
                {{ $chip['label'] }}
            </a>
        @endforeach

        @if($hasActiveChip)
            <a
                href="{{ $indexUrl }}"
                class="transparent-button !px-2 !py-1 !text-xs hover:bg-gray-100 dark:text-white dark:hover:bg-gray-950 sm:!px-3 sm:!py-1.5 sm:!text-sm"
            >
                <span class="icon-cancel-1 text-base sm:text-lg"></span>
                @lang('catalog-admin::app.products.chips.clear')
            </a>
        @endif
    </div>
@endif

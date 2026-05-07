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

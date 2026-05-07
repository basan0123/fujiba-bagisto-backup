@php
    $opts = $theme->translate($currentLocale->code)['options'] ?? [];

    $chipSources         = (array) ($opts['chip_sources']          ?? ['category']);
    $viewOptions         = (array) ($opts['view_options']          ?? [1, 2, 3]);
    $viewOptionsMobile   = (array) ($opts['view_options_mobile']   ?? []);
    $viewOptionsDesktop  = (array) ($opts['view_options_desktop']  ?? []);
    $defaultViewMobile   = (int)   ($opts['default_view_mobile']   ?? $opts['default_view'] ?? 2);
    $defaultViewDesktop  = (int)   ($opts['default_view_desktop']  ?? $opts['default_view'] ?? 3);
    $perPage             = (int)   ($opts['per_page']              ?? 24);
    $title               = (string)($opts['title']                 ?? '');
    $filterAttribute     = (string)($opts['filter_attribute']      ?? '');
    $sticky              = (bool)  ($opts['sticky_filter_nav']     ?? true);

    $availableChipSources = [
        'category' => 'Category',
        'brand'    => 'Brand',
        'color'    => 'Color',
        'writer'   => 'Writer',
    ];
@endphp

<div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
    <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
        <p class="mb-1 text-base font-semibold text-gray-800 dark:text-white">
            @lang('admin::app.settings.themes.edit.product-grid-filter.title', [], 'Product Grid Filter')
        </p>
        <p class="mb-4 text-xs text-gray-500 dark:text-gray-300">
            @lang('admin::app.settings.themes.edit.product-grid-filter.description', [], 'Configure the chip row, sticky attribute filter, view switch and infinite product grid.')
        </p>

        {{-- Section title --}}
        <x-admin::form.control-group>
            <x-admin::form.control-group.label>
                @lang('admin::app.settings.themes.edit.product-grid-filter.section-title', [], 'Section Title')
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="text"
                :name="$currentLocale->code.'[options][title]'"
                :value="$title"
                placeholder="Shop by Category"
            />
        </x-admin::form.control-group>

        {{-- Chip sources (No.1: category/brand/color/writer buttons) --}}
        <x-admin::form.control-group>
            <x-admin::form.control-group.label>
                @lang('admin::app.settings.themes.edit.product-grid-filter.chip-sources', [], 'Chip Sources (Top Row)')
            </x-admin::form.control-group.label>

            <div class="flex flex-wrap gap-4">
                @foreach ($availableChipSources as $code => $label)
                    <label class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            name="{{ $currentLocale->code }}[options][chip_sources][]"
                            value="{{ $code }}"
                            @checked(in_array($code, $chipSources))
                        />
                        <span class="text-sm text-gray-700 dark:text-gray-200">{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            <p class="mt-1 text-xs text-gray-500">
                @lang('admin::app.settings.themes.edit.product-grid-filter.chip-sources-hint', [], 'Top chips let users pick the category/brand/etc. Single-select.')
            </p>
        </x-admin::form.control-group>

        {{-- Filter nav attribute (No.2: sticky horizontal filter) --}}
        <x-admin::form.control-group>
            <x-admin::form.control-group.label>
                @lang('admin::app.settings.themes.edit.product-grid-filter.filter-attribute', [], 'Sticky Filter Attribute Code')
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="text"
                :name="$currentLocale->code.'[options][filter_attribute]'"
                :value="$filterAttribute"
                placeholder="material"
            />

            <p class="mt-1 text-xs text-gray-500">
                @lang('admin::app.settings.themes.edit.product-grid-filter.filter-attribute-hint', [], 'Attribute code for the horizontal filter nav, e.g. material, size, color. Leave empty to hide.')
            </p>
        </x-admin::form.control-group>

        {{-- Sticky toggle --}}
        <x-admin::form.control-group>
            <label class="flex items-center gap-2">
                <input
                    type="hidden"
                    name="{{ $currentLocale->code }}[options][sticky_filter_nav]"
                    value="0"
                />
                <input
                    type="checkbox"
                    name="{{ $currentLocale->code }}[options][sticky_filter_nav]"
                    value="1"
                    @checked($sticky)
                />
                <span class="text-sm text-gray-700 dark:text-gray-200">
                    @lang('admin::app.settings.themes.edit.product-grid-filter.sticky', [], 'Sticky filter nav on scroll')
                </span>
            </label>
        </x-admin::form.control-group>

        {{-- View options (No.3: 1/2/3 per row) --}}
        <x-admin::form.control-group>
            <x-admin::form.control-group.label>
                @lang('admin::app.settings.themes.edit.product-grid-filter.view-options', [], 'Allowed View Columns (Fallback)')
            </x-admin::form.control-group.label>

            <p class="mb-3 text-xs text-gray-500">
                @lang('admin::app.settings.themes.edit.product-grid-filter.view-options-fallback-hint', [], 'Used as fallback if mobile/desktop options are not set.')
            </p>

            <div class="flex gap-4">
                @foreach ([1, 2, 3, 4, 5, 6] as $n)
                    <label class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            name="{{ $currentLocale->code }}[options][view_options][]"
                            value="{{ $n }}"
                            @checked(in_array($n, $viewOptions))
                        />
                        <span class="text-sm">{{ $n }}</span>
                    </label>
                @endforeach
            </div>
        </x-admin::form.control-group>

        {{-- View options mobile --}}
        <x-admin::form.control-group>
            <x-admin::form.control-group.label>
                @lang('admin::app.settings.themes.edit.product-grid-filter.view-options-mobile', [], 'Allowed View Columns — Mobile')
            </x-admin::form.control-group.label>

            <p class="mb-3 text-xs text-gray-500">
                @lang('admin::app.settings.themes.edit.product-grid-filter.view-options-mobile-hint', [], 'Leave empty to use fallback. (Mobile: width ≤ 768px)')
            </p>

            <div class="flex gap-4">
                @foreach ([1, 2, 3, 4, 5, 6] as $n)
                    <label class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            name="{{ $currentLocale->code }}[options][view_options_mobile][]"
                            value="{{ $n }}"
                            @checked(in_array($n, $viewOptionsMobile))
                        />
                        <span class="text-sm">{{ $n }}</span>
                    </label>
                @endforeach
            </div>
        </x-admin::form.control-group>

        {{-- View options desktop --}}
        <x-admin::form.control-group>
            <x-admin::form.control-group.label>
                @lang('admin::app.settings.themes.edit.product-grid-filter.view-options-desktop', [], 'Allowed View Columns — Desktop')
            </x-admin::form.control-group.label>

            <p class="mb-3 text-xs text-gray-500">
                @lang('admin::app.settings.themes.edit.product-grid-filter.view-options-desktop-hint', [], 'Leave empty to use fallback. (Desktop: width > 768px)')
            </p>

            <div class="flex gap-4">
                @foreach ([1, 2, 3, 4, 5, 6] as $n)
                    <label class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            name="{{ $currentLocale->code }}[options][view_options_desktop][]"
                            value="{{ $n }}"
                            @checked(in_array($n, $viewOptionsDesktop))
                        />
                        <span class="text-sm">{{ $n }}</span>
                    </label>
                @endforeach
            </div>
        </x-admin::form.control-group>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('admin::app.settings.themes.edit.product-grid-filter.default-view-mobile', [], 'Default View — Mobile')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    :name="$currentLocale->code.'[options][default_view_mobile]'"
                    :value="$defaultViewMobile"
                >
                    @foreach ([1, 2, 3, 4, 5, 6] as $n)
                        <option value="{{ $n }}" @selected($defaultViewMobile === $n)>
                            {{ $n }}{{ $n === 1 ? ' (List)' : '' }}
                        </option>
                    @endforeach
                </x-admin::form.control-group.control>

                <p class="mt-1 text-xs text-gray-500">
                    @lang('admin::app.settings.themes.edit.product-grid-filter.list-hint', [], '1 = Bagisto list style (horizontal card).')
                </p>
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label>
                    @lang('admin::app.settings.themes.edit.product-grid-filter.default-view-desktop', [], 'Default View — Desktop')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    :name="$currentLocale->code.'[options][default_view_desktop]'"
                    :value="$defaultViewDesktop"
                >
                    @foreach ([1, 2, 3, 4, 5, 6] as $n)
                        <option value="{{ $n }}" @selected($defaultViewDesktop === $n)>
                            {{ $n }}{{ $n === 1 ? ' (List)' : '' }}
                        </option>
                    @endforeach
                </x-admin::form.control-group.control>
            </x-admin::form.control-group>
        </div>

        <x-admin::form.control-group>
            <x-admin::form.control-group.label>
                @lang('admin::app.settings.themes.edit.product-grid-filter.per-page', [], 'Products per Page')
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="text"
                :name="$currentLocale->code.'[options][per_page]'"
                :value="$perPage"
                placeholder="24"
            />
        </x-admin::form.control-group>
    </div>
</div>

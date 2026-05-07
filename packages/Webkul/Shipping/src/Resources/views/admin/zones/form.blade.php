<x-admin::layouts>
    <x-slot:title>{{ $zone ? __('shipping::app.zones.edit_zone_short') : __('shipping::app.zones.add_zone') }}</x-slot:title>

    <div class="flex flex-col gap-4">

        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                {{ $zone ? __('shipping::app.zones.edit_zone') . $zone->zone_name : __('shipping::app.zones.add_zone_title') }}
            </p>
            <a href="{{ route('admin.shipping.zones.index', array_filter(['channel_id' => $channelId])) }}"
               class="px-4 py-2 border rounded text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">
                {{ __('shipping::app.common.back_to_list') }}
            </a>
        </div>

        @if($errors->any())
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <ul class="list-disc list-inside text-red-700 dark:text-red-400 text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
              action="{{ $zone ? route('admin.shipping.zones.update', $zone->id) : route('admin.shipping.zones.store') }}">
            @csrf
            @if($zone) @method('PUT') @endif

            {{-- Basic info --}}
            <div class="box-shadow rounded bg-white dark:bg-gray-900 p-6 mb-4">
                <p class="font-semibold text-gray-700 dark:text-gray-300 mb-4">{{ __('shipping::app.zones.basic_info') }}</p>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('shipping::app.zones.zone_name') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="zone_name"
                               value="{{ old('zone_name', $zone->zone_name ?? '') }}"
                               placeholder="{{ __('shipping::app.zones.zone_name_placeholder') }}"
                               required
                               class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('shipping::app.zones.zone_code') }}
                        </label>
                        <input type="text" name="zone_code"
                               value="{{ old('zone_code', $zone->zone_code ?? '') }}"
                               placeholder="{{ __('shipping::app.zones.zone_code_placeholder') }}"
                               class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.common.channel') }}</label>
                        <select name="channel_id"
                                class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                            <option value="">{{ __('shipping::app.common.channels_common_full') }}</option>
                            @foreach($channels as $ch)
                                <option value="{{ $ch->id }}"
                                    {{ old('channel_id', $channelId) == $ch->id ? 'selected' : '' }}>
                                    {{ $ch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.zones.sort_order') }}</label>
                        <input type="number" name="sort_order"
                               value="{{ old('sort_order', $zone->sort_order ?? 0) }}"
                               min="0" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                    </div>
                    <div class="flex items-end gap-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.common.enabled') }}</label>
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active', $zone->is_active ?? true) ? 'checked' : '' }}
                               class="w-4 h-4">
                    </div>
                </div>
            </div>

            {{-- Prefecture checkboxes --}}
            <div class="box-shadow rounded bg-white dark:bg-gray-900 p-6 mb-4">
                <div class="flex items-center justify-between mb-4">
                    <p class="font-semibold text-gray-700 dark:text-gray-300">{{ __('shipping::app.zones.select_prefectures') }} <span class="text-red-500">*</span></p>
                    <div class="flex gap-2 text-sm">
                        <button type="button" onclick="selectAll()"
                                class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded hover:bg-gray-200">{{ __('shipping::app.common.select_all') }}</button>
                        <button type="button" onclick="clearAll()"
                                class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded hover:bg-gray-200">{{ __('shipping::app.common.deselect_all') }}</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4" id="pref-grid">
                    @foreach($regionGroups as $regionName => $codes)
                        <div class="border dark:border-gray-700 rounded-lg p-3">
                            <div class="flex items-center gap-2 mb-2">
                                <input type="checkbox" id="region_{{ $loop->index }}"
                                       onchange="toggleRegion(this, '{{ $loop->index }}')"
                                       class="w-4 h-4">
                                <label for="region_{{ $loop->index }}"
                                       class="text-sm font-semibold text-gray-700 dark:text-gray-300 cursor-pointer select-none">
                                    {{ $regionName }}
                                </label>
                            </div>
                            <div class="flex flex-col gap-1 pl-2 region-group" data-region="{{ $loop->index }}">
                                @foreach($codes as $code)
                                    <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                                        <input type="checkbox"
                                               name="prefectures[]"
                                               value="{{ $code }}"
                                               data-region="{{ $loop->parent->index }}"
                                               {{ in_array($code, old('prefectures', $selectedPrefs)) ? 'checked' : '' }}
                                               class="pref-cb w-4 h-4"
                                               onchange="updateRegionHeader({{ $loop->parent->index }})">
                                        {{ $prefectureMap[$code] }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Rate rows --}}
            <div class="box-shadow rounded bg-white dark:bg-gray-900 p-6 mb-4">
                <div class="flex items-center justify-between mb-4">
                    <p class="font-semibold text-gray-700 dark:text-gray-300">{{ __('shipping::app.zones.rate_ranges') }} <span class="text-red-500">*</span></p>
                    <button type="button" onclick="addRateRow()"
                            class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                        {{ __('shipping::app.zones.add_row') }}
                    </button>
                </div>
                <p class="text-xs text-gray-400 mb-4">{{ __('shipping::app.zones.rate_ranges_hint') }}</p>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                                <th class="pb-2 text-left font-medium">{{ __('shipping::app.prefecture.qty_from') }}</th>
                                <th class="pb-2 text-left font-medium">{{ __('shipping::app.prefecture.qty_to') }}</th>
                                <th class="pb-2 text-left font-medium">{{ __('shipping::app.prefecture.shipping_fee') }}</th>
                                <th class="pb-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="rate-rows">
                            @php
                                $existingRates = old('rates', $rates);
                                if (empty($existingRates)) {
                                    // Default empty rows
                                    $existingRates = [
                                        ['qty_from'=>1,  'qty_to'=>1,  'price'=>430],
                                        ['qty_from'=>2,  'qty_to'=>3,  'price'=>600],
                                        ['qty_from'=>4,  'qty_to'=>5,  'price'=>''],
                                        ['qty_from'=>6,  'qty_to'=>15, 'price'=>''],
                                        ['qty_from'=>16, 'qty_to'=>30, 'price'=>''],
                                    ];
                                }
                            @endphp
                            @foreach($existingRates as $i => $rate)
                                <tr class="rate-row border-b dark:border-gray-800">
                                    <td class="py-2 pr-3">
                                        <input type="number" name="rates[{{ $i }}][qty_from]"
                                               value="{{ $rate['qty_from'] ?? '' }}"
                                               min="0" required
                                               class="w-full border rounded px-2 py-1.5 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                                    </td>
                                    <td class="py-2 pr-3">
                                        <input type="number" name="rates[{{ $i }}][qty_to]"
                                               value="{{ $rate['qty_to'] ?? '' }}"
                                               min="1" required
                                               class="w-full border rounded px-2 py-1.5 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                                    </td>
                                    <td class="py-2 pr-3">
                                        <div class="relative">
                                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400">¥</span>
                                            <input type="number" name="rates[{{ $i }}][price]"
                                                   value="{{ $rate['price'] ?? ($rate['shipping_price'] ?? '') }}"
                                                   min="0" step="1" required
                                                   class="w-full border rounded pl-6 pr-2 py-1.5 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                                        </div>
                                    </td>
                                    <td class="py-2">
                                        <button type="button" onclick="removeRow(this)"
                                                class="text-red-400 hover:text-red-600 text-lg leading-none px-1">✕</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.shipping.zones.index', array_filter(['channel_id' => $channelId])) }}"
                   class="px-6 py-2 border rounded text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">
                    {{ __('shipping::app.common.cancel') }}
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-medium">
                    {{ $zone ? __('shipping::app.common.update') : __('shipping::app.common.save') }}
                </button>
            </div>
        </form>
    </div>

    <script>
    let rateRowIndex = {{ count($existingRates ?? []) }};

    function addRateRow() {
        const i = rateRowIndex++;
        const tbody = document.getElementById('rate-rows');
        const tr = document.createElement('tr');
        tr.className = 'rate-row border-b dark:border-gray-800';
        tr.innerHTML = `
            <td class="py-2 pr-3">
                <input type="number" name="rates[${i}][qty_from]" min="0" required
                       class="w-full border rounded px-2 py-1.5 dark:bg-gray-800 dark:text-white dark:border-gray-600">
            </td>
            <td class="py-2 pr-3">
                <input type="number" name="rates[${i}][qty_to]" min="1" required
                       class="w-full border rounded px-2 py-1.5 dark:bg-gray-800 dark:text-white dark:border-gray-600">
            </td>
            <td class="py-2 pr-3">
                <div class="relative">
                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400">¥</span>
                    <input type="number" name="rates[${i}][price]" min="0" step="1" required
                           class="w-full border rounded pl-6 pr-2 py-1.5 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                </div>
            </td>
            <td class="py-2">
                <button type="button" onclick="removeRow(this)"
                        class="text-red-400 hover:text-red-600 text-lg leading-none px-1">✕</button>
            </td>
        `;
        tbody.appendChild(tr);
    }

    function removeRow(btn) {
        const rows = document.querySelectorAll('.rate-row');
        if (rows.length <= 1) { alert('{{ __('shipping::app.zones.at_least_one_row') }}'); return; }
        btn.closest('tr').remove();
    }

    function selectAll() {
        document.querySelectorAll('.pref-cb').forEach(cb => cb.checked = true);
        document.querySelectorAll('[id^="region_"]').forEach(cb => cb.checked = true);
    }

    function clearAll() {
        document.querySelectorAll('.pref-cb').forEach(cb => cb.checked = false);
        document.querySelectorAll('[id^="region_"]').forEach(cb => cb.checked = false);
    }

    function toggleRegion(headerCb, regionIndex) {
        document.querySelectorAll(`.pref-cb[data-region="${regionIndex}"]`)
            .forEach(cb => cb.checked = headerCb.checked);
    }

    function updateRegionHeader(regionIndex) {
        const all = document.querySelectorAll(`.pref-cb[data-region="${regionIndex}"]`);
        const checked = document.querySelectorAll(`.pref-cb[data-region="${regionIndex}"]:checked`);
        const header = document.getElementById(`region_${regionIndex}`);
        header.checked = all.length === checked.length;
        header.indeterminate = checked.length > 0 && checked.length < all.length;
    }

    // Initialize region header states on load
    document.addEventListener('DOMContentLoaded', function () {
        @foreach($regionGroups as $regionName => $codes)
            updateRegionHeader({{ $loop->index }});
        @endforeach
    });
    </script>
</x-admin::layouts>

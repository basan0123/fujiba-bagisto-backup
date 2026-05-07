<x-admin::layouts>
    <x-slot:title>{{ __('shipping::app.thresholds.edit_title') }}</x-slot:title>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">{{ isset($threshold->id) ? __('shipping::app.thresholds.edit') : __('shipping::app.thresholds.new') }}</p>
            <a href="{{ route('admin.shipping.thresholds.index') }}" class="px-4 py-2 border rounded text-gray-600 dark:text-gray-300 hover:bg-gray-100">{{ __('shipping::app.common.back_to_list') }}</a>
        </div>

        @if(session('success'))
            <div class="p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ isset($threshold->id) ? route('admin.shipping.thresholds.update', $threshold->id) : route('admin.shipping.thresholds.store') }}">
            @csrf
            @if(isset($threshold->id)) @method('PUT') @endif

            <div class="box-shadow rounded bg-white dark:bg-gray-900 p-6">
                <div class="grid grid-cols-1 gap-6">

                    <!-- Channel -->
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.common.channel') }}</label>
                        <select name="channel_id" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                            <option value="">{{ __('shipping::app.common.channels_common_full') }}</option>
                            @foreach($channels as $ch)
                                <option value="{{ $ch->id }}" {{ old('channel_id', $threshold->channel_id ?? '') == $ch->id ? 'selected' : '' }}>{{ $ch->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-sm text-gray-500 mt-1">{{ __('shipping::app.thresholds.channel_hint') }}</p>
                    </div>

                    <!-- Label -->
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.thresholds.label') }} <span class="text-red-500">*</span></label>
                        <input type="text" name="label" value="{{ old('label', $threshold->label ?? '') }}" placeholder="{{ __('shipping::app.thresholds.label_placeholder') }}" required class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                    </div>

                    <!-- Threshold Amount -->
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.thresholds.threshold_amount') }} <span class="text-red-500">*</span></label>
                        <input type="number" name="threshold_amount" value="{{ old('threshold_amount', $threshold->threshold_amount ?? '') }}" min="0" step="1" placeholder="5000" required class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                        <p class="text-sm text-gray-500 mt-1">{{ __('shipping::app.thresholds.threshold_amount_hint') }}</p>
                    </div>

                    <!-- Base Shipping Fee -->
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.thresholds.base_fee') }}</label>
                        <input type="number" name="base_shipping_fee" value="{{ old('base_shipping_fee', $threshold->base_shipping_fee ?? 0) }}" min="0" step="1" placeholder="650" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                        <p class="text-sm text-gray-500 mt-1">{{ __('shipping::app.thresholds.base_fee_hint') }}</p>
                    </div>

                    <!-- Prefecture Codes -->
                    @php
                        $allPrefs = [
                            '01'=>'北海道','02'=>'青森県','03'=>'岩手県','04'=>'宮城県','05'=>'秋田県',
                            '06'=>'山形県','07'=>'福島県','08'=>'茨城県','09'=>'栃木県','10'=>'群馬県',
                            '11'=>'埼玉県','12'=>'千葉県','13'=>'東京都','14'=>'神奈川県','15'=>'新潟県',
                            '16'=>'富山県','17'=>'石川県','18'=>'福井県','19'=>'山梨県','20'=>'長野県',
                            '21'=>'岐阜県','22'=>'静岡県','23'=>'愛知県','24'=>'三重県','25'=>'滋賀県',
                            '26'=>'京都府','27'=>'大阪府','28'=>'兵庫県','29'=>'奈良県','30'=>'和歌山県',
                            '31'=>'鳥取県','32'=>'島根県','33'=>'岡山県','34'=>'広島県','35'=>'山口県',
                            '36'=>'徳島県','37'=>'香川県','38'=>'愛媛県','39'=>'高知県','40'=>'福岡県',
                            '41'=>'佐賀県','42'=>'長崎県','43'=>'熊本県','44'=>'大分県','45'=>'宮崎県',
                            '46'=>'鹿児島県','47'=>'沖縄県',
                        ];
                        $selected = old('prefecture_codes', $threshold->prefecture_codes ?? []) ?: [];
                    @endphp
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.thresholds.target_prefectures') }}</label>
                        <div class="grid grid-cols-4 gap-2 max-h-60 overflow-y-auto border rounded p-3 dark:border-gray-600">
                            @foreach($allPrefs as $code => $name)
                                <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                                    <input type="checkbox" name="prefecture_codes[]" value="{{ $code }}" {{ in_array($code, $selected) ? 'checked' : '' }}>
                                    {{ $name }}
                                </label>
                            @endforeach
                        </div>
                        <p class="text-sm text-gray-500 mt-1">{{ __('shipping::app.thresholds.target_prefectures_hint') }}</p>
                    </div>

                    <!-- Checkboxes -->
                    <div class="grid grid-cols-3 gap-6">
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('shipping::app.thresholds.apply_cool_free') }}</label>
                            <input type="hidden" name="apply_to_cool" value="0">
                            <input type="checkbox" name="apply_to_cool" value="1" {{ old('apply_to_cool', $threshold->apply_to_cool ?? false) ? 'checked' : '' }}>
                            <p class="text-sm text-gray-500 mt-1">{{ __('shipping::app.thresholds.apply_cool_hint') }}</p>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('shipping::app.thresholds.apply_frozen_free') }}</label>
                            <input type="hidden" name="apply_to_frozen" value="0">
                            <input type="checkbox" name="apply_to_frozen" value="1" {{ old('apply_to_frozen', $threshold->apply_to_frozen ?? false) ? 'checked' : '' }}>
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('shipping::app.common.enabled') }}</label>
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $threshold->is_active ?? true) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">{{ __('shipping::app.common.save') }}</button>
                </div>
            </div>
        </form>
    </div>
</x-admin::layouts>

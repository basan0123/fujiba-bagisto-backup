<x-admin::layouts>
    <x-slot:title>{{ __('shipping::app.cool_fee.title') }}</x-slot:title>

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">{{ __('shipping::app.cool_fee.title') }}</p>
        </div>

        @if(session('success'))
            <div class="p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
        @endif

        {{-- Channel tabs --}}
        @if($channels->count() > 1)
        <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
            @foreach($channels as $ch)
            <a href="{{ route('admin.shipping.cool-fees.edit', $ch->id) }}"
               class="px-4 py-2 text-sm font-medium rounded-t transition-colors
                      {{ $channelId == $ch->id ? 'bg-white dark:bg-gray-900 border border-b-white dark:border-gray-700 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                {{ $ch->name }}
            </a>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('admin.shipping.cool-fees.update', $channelId) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="channel_id" value="{{ $channelId }}">

            <div class="box-shadow rounded bg-white dark:bg-gray-900 p-4">
                @if($channels->count() == 1)
                <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded text-sm text-blue-700 dark:text-blue-300">
                    {{ __('shipping::app.cool_fee.channel_prefix') }}: <strong>{{ $channels->firstWhere('id', $channelId)?->name ?? __('shipping::app.cool_fee.channel_common') }}</strong>
                </div>
                @endif

                <div class="grid grid-cols-1 gap-6 mb-6">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.cool_fee.rule_active') }}</label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="sr-only peer" {{ $setting->is_active ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-300 peer-checked:bg-blue-600 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                        </label>
                    </div>
                </div>

                <hr class="mb-6">
                <p class="font-bold text-lg mb-4">{{ __('shipping::app.cool_fee.cool_section') }}</p>

                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.cool_fee.cool_enable') }}</label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="cool_enabled" value="0">
                            <input type="checkbox" name="cool_enabled" value="1" class="sr-only peer" {{ $setting->cool_enabled ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-300 peer-checked:bg-blue-600 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                        </label>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.cool_fee.cool_fee_label') }}</label>
                        <input type="number" name="cool_box_fee" value="{{ $setting->cool_box_fee }}" min="0" step="1"
                            class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                        <p class="text-sm text-gray-500 mt-1">{{ __('shipping::app.cool_fee.cool_fee_hint') }}</p>
                    </div>
                </div>

                <hr class="mb-6">
                <p class="font-bold text-lg mb-4">{{ __('shipping::app.cool_fee.frozen_section') }}</p>

                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.cool_fee.frozen_enable') }}</label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="frozen_enabled" value="0">
                            <input type="checkbox" name="frozen_enabled" value="1" class="sr-only peer" {{ $setting->frozen_enabled ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-300 peer-checked:bg-blue-600 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                        </label>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.cool_fee.frozen_fee_label') }}</label>
                        <input type="number" name="frozen_box_fee" value="{{ $setting->frozen_box_fee }}" min="0" step="1"
                            class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                    </div>
                </div>

                <hr class="mb-6">
                <p class="font-bold text-lg mb-4">{{ __('shipping::app.cool_fee.options_section') }}</p>

                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.cool_fee.fee_per_item') }}</label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="fee_per_item" value="0">
                            <input type="checkbox" name="fee_per_item" value="1" class="sr-only peer" {{ $setting->fee_per_item ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-300 peer-checked:bg-blue-600 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                        </label>
                        <p class="text-sm text-gray-500 mt-1">{{ __('shipping::app.cool_fee.fee_per_item_hint') }}</p>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.cool_fee.max_fee_cap') }}</label>
                        <input type="number" name="max_fee_cap" value="{{ $setting->max_fee_cap }}" min="0" step="1" placeholder="{{ __('shipping::app.cool_fee.max_fee_cap_placeholder') }}"
                            class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                        <p class="text-sm text-gray-500 mt-1">{{ __('shipping::app.cool_fee.max_fee_cap_hint') }}</p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        {{ __('shipping::app.common.save') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-admin::layouts>

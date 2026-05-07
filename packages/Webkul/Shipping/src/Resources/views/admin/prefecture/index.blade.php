<x-admin::layouts>
    <x-slot:title>{{ __('shipping::app.prefecture.title') }}</x-slot:title>
    <div class="flex flex-col gap-4">
        <p class="text-xl font-bold text-gray-800 dark:text-white">{{ __('shipping::app.prefecture.title') }}</p>
        @if(session('success'))<div class="p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>@endif

        {{-- Channel filter tabs --}}
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.shipping.prefecture-rates.index') }}"
               class="px-4 py-2 rounded text-sm font-medium {{ !$channelId ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                {{ __('shipping::app.common.channels_all') }}
            </a>
            @foreach($channels as $ch)
            <a href="{{ route('admin.shipping.prefecture-rates.index', ['channel_id' => $ch->id]) }}"
               class="px-4 py-2 rounded text-sm font-medium {{ $channelId == $ch->id ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                {{ $ch->name }}
            </a>
            @endforeach
        </div>

        <div class="box-shadow rounded bg-white dark:bg-gray-900 p-6">
            <p class="font-bold mb-4">{{ __('shipping::app.prefecture.add_new_rate') }}</p>
            <form method="POST" action="{{ route('admin.shipping.prefecture-rates.store') }}">
                @csrf
                <div class="grid grid-cols-7 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.common.channel') }}</label>
                        <select name="channel_id" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                            <option value="">{{ __('shipping::app.common.channels_common') }}</option>
                            @foreach($channels as $ch)
                                <option value="{{ $ch->id }}" {{ $channelId == $ch->id ? 'selected' : '' }}>{{ $ch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.prefecture.prefecture') }}</label>
                        <select name="prefecture_code" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                            @foreach($prefectures as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.prefecture.qty_from') }}</label>
                        <input type="number" name="qty_from" value="1" min="1" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.prefecture.qty_to') }}</label>
                        <input type="number" name="qty_to" value="10" min="1" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.prefecture.shipping_fee') }}</label>
                        <input type="number" name="shipping_price" value="800" min="0" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.common.enabled') }}</label>
                        <select name="is_active" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                            <option value="1">{{ __('shipping::app.common.active') }}</option>
                            <option value="0">{{ __('shipping::app.common.inactive') }}</option>
                        </select>
                    </div>
                    <div><button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">{{ __('shipping::app.common.add_short') }}</button></div>
                </div>
            </form>
        </div>
        @foreach($prefectures as $code => $name)
            @if(isset($rates[$code]) && $rates[$code]->count() > 0)
            <div class="box-shadow rounded bg-white dark:bg-gray-900 overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 font-medium text-gray-700 dark:text-gray-300">{{ $name }}</div>
                <table class="w-full">
                    <thead><tr class="text-sm text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                        <th class="px-4 py-2 text-left">{{ __('shipping::app.common.channel') }}</th>
                        <th class="px-4 py-2 text-left">{{ __('shipping::app.prefecture.qty_from') }}</th>
                        <th class="px-4 py-2 text-left">{{ __('shipping::app.prefecture.qty_to') }}</th>
                        <th class="px-4 py-2 text-left">{{ __('shipping::app.prefecture.shipping') }}</th>
                        <th class="px-4 py-2 text-center">{{ __('shipping::app.common.enabled') }}</th>
                        <th class="px-4 py-2 text-center">{{ __('shipping::app.prefecture.delete_col') }}</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($rates[$code] as $rate)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-2">
                                @if($rate->channel_id)
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">{{ $channels->firstWhere('id', $rate->channel_id)?->name ?? 'CH#'.$rate->channel_id }}</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-500">{{ __('shipping::app.common.channels_common') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $rate->qty_from }}{{ __('shipping::app.prefecture.qty_unit') }}</td>
                            <td class="px-4 py-2 text-gray-800 dark:text-gray-200">{{ $rate->qty_to }}{{ __('shipping::app.prefecture.qty_unit') }}</td>
                            <td class="px-4 py-2 font-medium text-gray-800 dark:text-gray-200">¥{{ number_format($rate->shipping_price) }}</td>
                            <td class="px-4 py-2 text-center"><span class="px-2 py-1 text-xs rounded-full {{ $rate->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $rate->is_active ? __('shipping::app.common.active') : __('shipping::app.common.inactive') }}</span></td>
                            <td class="px-4 py-2 text-center">
                                <form method="POST" action="{{ route('admin.shipping.prefecture-rates.destroy', $rate->id) }}">@csrf @method('DELETE')<button class="px-3 py-1 text-sm bg-red-500 text-white rounded hover:bg-red-600" onclick="return confirm('{{ __('shipping::app.common.confirm_delete') }}')">{{ __('shipping::app.common.delete') }}</button></form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        @endforeach
    </div>
</x-admin::layouts>

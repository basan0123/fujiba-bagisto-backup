<x-admin::layouts>
    <x-slot:title>{{ __('shipping::app.bulk_bonus.title') }}</x-slot:title>
    <div class="flex flex-col gap-4">
        <p class="text-xl font-bold text-gray-800 dark:text-white">{{ __('shipping::app.bulk_bonus.title') }}</p>
        @if(session('success'))<div class="p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>@endif

        {{-- Channel filter tabs --}}
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.shipping.bulk-bonus.index') }}"
               class="px-4 py-2 rounded text-sm font-medium {{ !$channelId ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                {{ __('shipping::app.common.channels_all') }}
            </a>
            @foreach($channels as $ch)
            <a href="{{ route('admin.shipping.bulk-bonus.index', ['channel_id' => $ch->id]) }}"
               class="px-4 py-2 rounded text-sm font-medium {{ $channelId == $ch->id ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                {{ $ch->name }}
            </a>
            @endforeach
        </div>

        <div class="box-shadow rounded bg-white dark:bg-gray-900 p-6">
            <p class="font-bold mb-4">{{ __('shipping::app.bulk_bonus.add_new_rule') }}</p>
            <form method="POST" action="{{ route('admin.shipping.bulk-bonus.store') }}">
                @csrf
                <div class="grid grid-cols-12 gap-3 items-end">
                    <div class="col-span-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.common.channel') }}</label>
                        <select name="channel_id" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                            <option value="">{{ __('shipping::app.common.channels_common') }}</option>
                            @foreach($channels as $ch)
                                <option value="{{ $ch->id }}" {{ $channelId == $ch->id ? 'selected' : '' }}>{{ $ch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.bulk_bonus.discount_type') }}</label>
                        <select name="discount_type" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                            <option value="free_items">{{ __('shipping::app.bulk_bonus.type_free_items') }}</option>
                            <option value="percent_off_order">{{ __('shipping::app.bulk_bonus.type_percent') }}</option>
                            <option value="fixed_per_tier">{{ __('shipping::app.bulk_bonus.type_fixed') }}</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.bulk_bonus.buy_qty') }}</label>
                        <input type="number" name="buy_qty" value="10" min="1" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                        <p class="text-xs text-gray-500 mt-1">{{ __('shipping::app.bulk_bonus.buy_qty_hint') }}</p>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.bulk_bonus.free_qty') }}</label>
                        <input type="number" name="free_qty" value="1" min="0" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                        <p class="text-xs text-gray-500 mt-1">{{ __('shipping::app.bulk_bonus.free_qty_hint') }}</p>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.bulk_bonus.percent_off') }}</label>
                        <input type="number" name="percent_off" min="0" max="100" step="0.01" placeholder="{{ __('shipping::app.bulk_bonus.percent_off_placeholder') }}" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                        <p class="text-xs text-gray-500 mt-1">{{ __('shipping::app.bulk_bonus.percent_off_hint') }}</p>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.bulk_bonus.fixed_amount') }}</label>
                        <input type="number" name="fixed_amount" min="0" step="1" placeholder="{{ __('shipping::app.bulk_bonus.fixed_amount_placeholder') }}" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                        <p class="text-xs text-gray-500 mt-1">{{ __('shipping::app.bulk_bonus.fixed_amount_hint') }}</p>
                    </div>

                    <div class="col-span-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.bulk_bonus.min_product_price') }}</label>
                        <input type="number" name="min_product_price" min="0" step="0.01" placeholder="{{ __('shipping::app.bulk_bonus.min_product_price_placeholder') }}" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                        <p class="text-xs text-gray-500 mt-1">{{ __('shipping::app.bulk_bonus.min_product_price_hint') }}</p>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.bulk_bonus.max_free_per_order') }}</label>
                        <input type="number" name="max_free_per_order" min="1" placeholder="{{ __('shipping::app.bulk_bonus.max_free_per_order_placeholder') }}" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.bulk_bonus.priority') }}</label>
                        <input type="number" name="priority" value="0" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                        <p class="text-xs text-gray-500 mt-1">{{ __('shipping::app.bulk_bonus.priority_hint') }}</p>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.bulk_bonus.replaces_lower') }}</label>
                        <select name="replaces_lower_tiers" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                            <option value="0">{{ __('shipping::app.common.no') }}</option>
                            <option value="1">{{ __('shipping::app.bulk_bonus.replaces_lower_yes') }}</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.bulk_bonus.block_coupons') }}</label>
                        <select name="block_coupons" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                            <option value="0">{{ __('shipping::app.bulk_bonus.block_coupons_allow') }}</option>
                            <option value="1">{{ __('shipping::app.bulk_bonus.block_coupons_disable') }}</option>
                        </select>
                    </div>
                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.bulk_bonus.stackable') }}</label>
                        <select name="stackable" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                            <option value="1">{{ __('shipping::app.common.yes') }}</option>
                            <option value="0">{{ __('shipping::app.common.no') }}</option>
                        </select>
                    </div>
                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('shipping::app.common.enabled') }}</label>
                        <select name="is_active" class="w-full border rounded px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                            <option value="1">{{ __('shipping::app.common.active') }}</option>
                            <option value="0">{{ __('shipping::app.common.inactive') }}</option>
                        </select>
                    </div>
                    <div class="col-span-1"><button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">{{ __('shipping::app.common.add_short') }}</button></div>
                </div>
            </form>
        </div>
        <div class="box-shadow rounded bg-white dark:bg-gray-900 overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-3 text-left font-medium text-gray-600 dark:text-gray-300">{{ __('shipping::app.common.channel') }}</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-600 dark:text-gray-300">{{ __('shipping::app.bulk_bonus.col_type') }}</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-600 dark:text-gray-300">{{ __('shipping::app.bulk_bonus.col_buy') }}</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-600 dark:text-gray-300">{{ __('shipping::app.bulk_bonus.col_free') }}</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-600 dark:text-gray-300">{{ __('shipping::app.bulk_bonus.col_percent') }}</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-600 dark:text-gray-300">{{ __('shipping::app.bulk_bonus.col_fixed') }}</th>
                        <th class="px-3 py-3 text-left font-medium text-gray-600 dark:text-gray-300">{{ __('shipping::app.bulk_bonus.col_min_price') }}</th>
                        <th class="px-3 py-3 text-center font-medium text-gray-600 dark:text-gray-300">{{ __('shipping::app.bulk_bonus.col_priority') }}</th>
                        <th class="px-3 py-3 text-center font-medium text-gray-600 dark:text-gray-300">{{ __('shipping::app.bulk_bonus.col_replaces_lower') }}</th>
                        <th class="px-3 py-3 text-center font-medium text-gray-600 dark:text-gray-300">{{ __('shipping::app.bulk_bonus.col_block_coupons') }}</th>
                        <th class="px-3 py-3 text-center font-medium text-gray-600 dark:text-gray-300">{{ __('shipping::app.bulk_bonus.col_stack') }}</th>
                        <th class="px-3 py-3 text-center font-medium text-gray-600 dark:text-gray-300">{{ __('shipping::app.common.enabled') }}</th>
                        <th class="px-3 py-3 text-center font-medium text-gray-600 dark:text-gray-300">{{ __('shipping::app.bulk_bonus.col_delete') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($rules as $rule)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-3 py-2">
                            @if($rule->channel_id)
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">{{ $channels->firstWhere('id', $rule->channel_id)?->name ?? 'CH#'.$rule->channel_id }}</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-500">{{ __('shipping::app.common.channels_common') }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            @php $type = $rule->discount_type ?? 'free_items'; @endphp
                            @if($type === 'percent_off_order')
                                <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-700">% OFF</span>
                            @elseif($type === 'fixed_per_tier')
                                <span class="px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-700">¥固定</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">FREE</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $rule->buy_qty }}{{ __('shipping::app.bulk_bonus.qty_plus') }}</td>
                        <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $rule->free_qty ? $rule->free_qty.__('shipping::app.prefecture.qty_unit') : '—' }}</td>
                        <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $rule->percent_off ? $rule->percent_off.'%' : '—' }}</td>
                        <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $rule->fixed_amount ? '¥'.number_format((float)$rule->fixed_amount) : '—' }}</td>
                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $rule->min_product_price ? '¥'.number_format((float)$rule->min_product_price) : '—' }}</td>
                        <td class="px-3 py-2 text-center text-gray-600 dark:text-gray-300">{{ $rule->priority }}</td>
                        <td class="px-3 py-2 text-center">{{ $rule->replaces_lower_tiers ? '✓' : '—' }}</td>
                        <td class="px-3 py-2 text-center">{{ $rule->block_coupons ? '✓' : '—' }}</td>
                        <td class="px-3 py-2 text-center">{{ $rule->stackable ? '✓' : '—' }}</td>
                        <td class="px-3 py-2 text-center"><span class="px-2 py-1 text-xs rounded-full {{ $rule->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $rule->is_active ? __('shipping::app.common.active') : __('shipping::app.common.inactive') }}</span></td>
                        <td class="px-3 py-2 text-center">
                            <form method="POST" action="{{ route('admin.shipping.bulk-bonus.destroy', $rule->id) }}">@csrf @method('DELETE')<button class="px-2 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600" onclick="return confirm('{{ __('shipping::app.common.confirm_delete') }}')">{{ __('shipping::app.common.delete') }}</button></form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="13" class="px-4 py-8 text-center text-gray-500">{{ __('shipping::app.bulk_bonus.empty_state') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin::layouts>

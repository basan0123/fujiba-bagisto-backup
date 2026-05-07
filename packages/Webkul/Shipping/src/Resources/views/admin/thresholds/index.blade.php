<x-admin::layouts>
    <x-slot:title>{{ __('shipping::app.thresholds.title') }}</x-slot:title>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">{{ __('shipping::app.thresholds.title') }}</p>
            <a href="{{ route('admin.shipping.thresholds.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">{{ __('shipping::app.common.add') }}</a>
        </div>
        @if(session('success'))<div class="p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>@endif
        <div class="box-shadow rounded bg-white dark:bg-gray-900 overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('shipping::app.common.channel') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('shipping::app.thresholds.col_label') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('shipping::app.thresholds.col_threshold') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('shipping::app.thresholds.col_below_fee') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('shipping::app.thresholds.col_target_areas') }}</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('shipping::app.thresholds.col_free_cool') }}</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('shipping::app.thresholds.col_free_frozen') }}</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('shipping::app.common.enabled') }}</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('shipping::app.thresholds.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($thresholds as $t)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3">
                            @if($t->channel_id)
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">{{ $channels->firstWhere('id', $t->channel_id)?->name ?? 'CH#'.$t->channel_id }}</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-500">{{ __('shipping::app.common.channels_common') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200">{{ $t->label }}</td>
                        <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">¥{{ number_format($t->threshold_amount) }}</td>
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200">{{ $t->base_shipping_fee > 0 ? '¥'.number_format($t->base_shipping_fee) : '—' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                            @if($t->prefecture_codes)
                                @php
                                    $prefMap = ['01'=>'北海道','02'=>'青森','03'=>'岩手','04'=>'宮城','05'=>'秋田','06'=>'山形','07'=>'福島','08'=>'茨城','09'=>'栃木','10'=>'群馬','11'=>'埼玉','12'=>'千葉','13'=>'東京','14'=>'神奈川','15'=>'新潟','16'=>'富山','17'=>'石川','18'=>'福井','19'=>'山梨','20'=>'長野','21'=>'岐阜','22'=>'静岡','23'=>'愛知','24'=>'三重','25'=>'滋賀','26'=>'京都','27'=>'大阪','28'=>'兵庫','29'=>'奈良','30'=>'和歌山','31'=>'鳥取','32'=>'島根','33'=>'岡山','34'=>'広島','35'=>'山口','36'=>'徳島','37'=>'香川','38'=>'愛媛','39'=>'高知','40'=>'福岡','41'=>'佐賀','42'=>'長崎','43'=>'熊本','44'=>'大分','45'=>'宮崎','46'=>'鹿児島','47'=>'沖縄'];
                                @endphp
                                {{ implode(', ', array_map(fn($c) => $prefMap[$c] ?? $c, $t->prefecture_codes)) }}
                            @else
                                {{ __('shipping::app.thresholds.all_prefectures') }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">{{ $t->apply_to_cool ? '✓' : '—' }}</td>
                        <td class="px-4 py-3 text-center">{{ $t->apply_to_frozen ? '✓' : '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 text-xs rounded-full {{ $t->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $t->is_active ? __('shipping::app.common.active') : __('shipping::app.common.inactive') }}</span>
                        </td>
                        <td class="px-4 py-3 text-center flex justify-center gap-2">
                            <a href="{{ route('admin.shipping.thresholds.edit', $t->id) }}" class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">{{ __('shipping::app.common.edit') }}</a>
                            <form method="POST" action="{{ route('admin.shipping.thresholds.destroy', $t->id) }}">@csrf @method('DELETE')<button class="px-3 py-1 text-sm bg-red-500 text-white rounded" onclick="return confirm('{{ __('shipping::app.common.confirm_delete') }}')">{{ __('shipping::app.common.delete') }}</button></form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-gray-500">{{ __('shipping::app.thresholds.empty_state') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin::layouts>

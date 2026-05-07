<x-admin::layouts>
    <x-slot:title>{{ __('shipping::app.zones.title') }}</x-slot:title>

    <div class="flex flex-col gap-4">

        {{-- Header --}}
        <div class="flex items-center justify-between flex-wrap gap-2">
            <p class="text-xl font-bold text-gray-800 dark:text-white">{{ __('shipping::app.zones.title_with_icon') }}</p>
            <div class="flex gap-2">
                {{-- Seed Kimono zones button --}}
                <form method="POST" action="{{ route('admin.shipping.zones.seed') }}"
                      onsubmit="return confirm('{{ __('shipping::app.zones.seed_kimono_confirm') }}')">
                    @csrf
                    <input type="hidden" name="channel_id" value="{{ $channelId ?? 1 }}">
                    <button type="submit"
                            class="px-4 py-2 bg-amber-500 text-white rounded hover:bg-amber-600 text-sm font-medium">
                        {{ __('shipping::app.zones.seed_kimono') }}
                    </button>
                </form>
                <a href="{{ route('admin.shipping.zones.create', array_filter(['channel_id' => $channelId])) }}"
                   class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm font-medium">
                    {{ __('shipping::app.zones.add_zone') }}
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="p-4 bg-red-100 text-red-800 rounded-lg">{{ session('error') }}</div>
        @endif

        {{-- Channel tabs --}}
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.shipping.zones.index') }}"
               class="px-4 py-2 rounded text-sm font-medium {{ !$channelId ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                {{ __('shipping::app.common.channels_all') }}
            </a>
            @foreach($channels as $ch)
                <a href="{{ route('admin.shipping.zones.index', ['channel_id' => $ch->id]) }}"
                   class="px-4 py-2 rounded text-sm font-medium {{ $channelId == $ch->id ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                    {{ $ch->name }}
                </a>
            @endforeach
        </div>

        {{-- Help note --}}
        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-sm text-blue-700 dark:text-blue-300">
            <strong>{{ __('shipping::app.zones.usage_label') }}</strong>
            {{ __('shipping::app.zones.usage_text') }}
        </div>

        @if($zones->isEmpty())
            <div class="box-shadow rounded bg-white dark:bg-gray-900 p-12 text-center">
                <p class="text-gray-400 text-lg mb-4">{{ __('shipping::app.zones.empty_no_zones') }}</p>
                <p class="text-gray-400 text-sm mb-6">{{ __('shipping::app.zones.empty_seed_hint') }}</p>
                <a href="{{ route('admin.shipping.zones.create') }}"
                   class="px-6 py-3 bg-blue-600 text-white rounded hover:bg-blue-700">{{ __('shipping::app.zones.add_zone_button') }}</a>
            </div>
        @endif

        {{-- Zone cards --}}
        @foreach($zones as $zone)
            @php
                $zoneColors = [
                    0 => 'border-l-blue-500',
                    1 => 'border-l-green-500',
                    2 => 'border-l-orange-500',
                    3 => 'border-l-purple-500',
                    4 => 'border-l-red-500',
                ];
                $color = $zoneColors[$loop->index % 5];
                $prefCodes = $zone->prefectures->pluck('prefecture_code')->toArray();
            @endphp
            <div class="box-shadow rounded bg-white dark:bg-gray-900 border-l-4 {{ $color }} overflow-hidden">
                <div class="px-5 py-4 flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-3">
                        <span class="text-lg font-bold text-gray-800 dark:text-white">{{ $zone->zone_name }}</span>
                        @if($zone->zone_code)
                            <span class="text-xs px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-500 rounded font-mono">{{ $zone->zone_code }}</span>
                        @endif
                        @if($zone->channel_id)
                            <span class="text-xs px-2 py-0.5 bg-blue-100 text-blue-700 rounded">{{ $channels->firstWhere('id', $zone->channel_id)?->name }}</span>
                        @else
                            <span class="text-xs px-2 py-0.5 bg-gray-100 text-gray-500 rounded">{{ __('shipping::app.zones.all_channels') }}</span>
                        @endif
                        @if(!$zone->is_active)
                            <span class="text-xs px-2 py-0.5 bg-red-100 text-red-600 rounded">{{ __('shipping::app.common.inactive') }}</span>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.shipping.zones.edit', $zone->id) }}"
                           class="px-3 py-1.5 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200">
                            {{ __('shipping::app.zones.edit_zone_short') }}
                        </a>
                        <form method="POST" action="{{ route('admin.shipping.zones.destroy', $zone->id) }}"
                              onsubmit="return confirm('{{ __('shipping::app.zones.delete_zone_confirm', ['zone' => $zone->zone_name]) }}')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="px-3 py-1.5 text-sm bg-red-500 text-white rounded hover:bg-red-600">
                                {{ __('shipping::app.zones.delete_zone') }}
                            </button>
                        </form>
                    </div>
                </div>

                <div class="px-5 pb-5 grid grid-cols-1 lg:grid-cols-2 gap-4">
                    {{-- Prefectures --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wide">
                            {{ __('shipping::app.zones.prefectures_count', ['count' => $zone->prefectures->count()]) }}
                        </p>
                        <div class="flex flex-wrap gap-1">
                            @foreach($zone->prefectures->sortBy('prefecture_code') as $pref)
                                <span class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded">
                                    {{ $pref->prefecture_name_ja }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    {{-- Rate table --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wide">
                            {{ __('shipping::app.zones.rates_count', ['count' => $zone->rates->count()]) }}
                        </p>
                        @if($zone->rates->isNotEmpty())
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                                        <th class="pb-1 text-left">{{ __('shipping::app.prefecture.qty_from') }}</th>
                                        <th class="pb-1 text-left">{{ __('shipping::app.prefecture.qty_to') }}</th>
                                        <th class="pb-1 text-right font-medium">{{ __('shipping::app.prefecture.shipping') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach($zone->rates as $rate)
                                        <tr>
                                            <td class="py-1 text-gray-700 dark:text-gray-300">{{ $rate->qty_from }}{{ __('shipping::app.prefecture.qty_unit') }}</td>
                                            <td class="py-1 text-gray-700 dark:text-gray-300">{{ $rate->qty_to }}{{ __('shipping::app.prefecture.qty_unit') }}</td>
                                            <td class="py-1 text-right font-semibold text-gray-800 dark:text-gray-200">¥{{ number_format($rate->shipping_price) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-gray-400 text-sm">{{ __('shipping::app.zones.no_ranges') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Link to individual prefecture rates --}}
        <div class="text-center text-sm text-gray-400 dark:text-gray-500 py-2">
            {{ __('shipping::app.zones.individual_pref_link_prefix') }}
            <a href="{{ route('admin.shipping.prefecture-rates.index', array_filter(['channel_id' => $channelId])) }}"
               class="text-blue-500 hover:underline">{{ __('shipping::app.zones.individual_pref_link_text') }}</a>
            {{ __('shipping::app.zones.individual_pref_link_suffix') }}
        </div>

    </div>
</x-admin::layouts>

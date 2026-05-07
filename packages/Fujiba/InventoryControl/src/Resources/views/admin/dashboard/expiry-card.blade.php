@php
    $today = \Illuminate\Support\Carbon::today();
    $expiredCount = \Fujiba\InventoryControl\Models\InventoryBatch::query()
        ->where('status', \Fujiba\InventoryControl\Models\InventoryBatch::STATUS_ACTIVE)
        ->where('quantity_remaining', '>', 0)
        ->whereNotNull('expiry_date')
        ->where('expiry_date', '<', $today)
        ->count();

    $soonCount = \Fujiba\InventoryControl\Models\InventoryBatch::query()
        ->where('status', \Fujiba\InventoryControl\Models\InventoryBatch::STATUS_ACTIVE)
        ->where('quantity_remaining', '>', 0)
        ->whereNotNull('expiry_date')
        ->whereBetween('expiry_date', [$today, $today->copy()->addDays(7)])
        ->count();
@endphp

@if ($expiredCount > 0 || $soonCount > 0)
    <div class="mb-6 bg-white dark:bg-gray-900 border-l-4 border-orange-500 rounded-lg shadow-sm p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="font-semibold text-gray-800 dark:text-white">{{ __('inventory-control::app.dashboard.card_title') }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    @if ($expiredCount > 0)<span class="text-red-700 font-semibold">{{ $expiredCount }} {{ __('inventory-control::app.dashboard.expired') }}</span>@endif
                    @if ($expiredCount > 0 && $soonCount > 0) &middot; @endif
                    @if ($soonCount > 0)<span class="text-orange-700 font-semibold">{{ $soonCount }} {{ __('inventory-control::app.dashboard.expiring_soon') }}</span>@endif
                </p>
            </div>
            <a href="{{ route('admin.inventory_control.expiry_alerts.index') }}" class="text-sm px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white rounded">{{ __('inventory-control::app.dashboard.view_all') }}</a>
        </div>
    </div>
@endif

<x-admin::layouts>
    <x-slot:title>{{ __('inventory-control::app.expiry_alerts.title') }}</x-slot:title>

    <div class="mb-6">
        <p class="text-xl font-bold text-gray-800 dark:text-white">{{ __('inventory-control::app.expiry_alerts.title') }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('inventory-control::app.expiry_alerts.info') }}</p>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 text-sm bg-green-50 text-green-800 border border-green-200 rounded">{{ session('success') }}</div>
    @endif

    @php
        $rows = $expired->map(fn ($b) => ['batch' => $b, 'group' => 'expired'])
            ->concat($expiringSoon->map(fn ($b) => ['batch' => $b, 'group' => 'soon']));
    @endphp

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-left text-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-3 py-2">{{ __('inventory-control::app.expiry_alerts.col.status') }}</th>
                    <th class="px-3 py-2">{{ __('inventory-control::app.expiry_alerts.col.product') }}</th>
                    <th class="px-3 py-2">{{ __('inventory-control::app.expiry_alerts.col.sku') }}</th>
                    <th class="px-3 py-2">{{ __('inventory-control::app.expiry_alerts.col.batch') }}</th>
                    <th class="px-3 py-2 text-right">{{ __('inventory-control::app.expiry_alerts.col.qty') }}</th>
                    <th class="px-3 py-2">{{ __('inventory-control::app.expiry_alerts.col.expiry') }}</th>
                    <th class="px-3 py-2">{{ __('inventory-control::app.expiry_alerts.col.days_left') }}</th>
                    <th class="px-3 py-2">{{ __('inventory-control::app.expiry_alerts.col.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($rows as $row)
                    @php
                        $batch = $row['batch'];
                        $daysLeft = (int) $today->diffInDays($batch->expiry_date, false);
                        if ($daysLeft < 0) {
                            $badgeClass = 'bg-red-100 text-red-800';
                            $badgeText = __('inventory-control::app.expiry_alerts.badge.expired');
                        } elseif ($daysLeft <= 3) {
                            $badgeClass = 'bg-orange-100 text-orange-800';
                            $badgeText = __('inventory-control::app.expiry_alerts.badge.urgent', ['days' => $daysLeft]);
                        } else {
                            $badgeClass = 'bg-yellow-100 text-yellow-800';
                            $badgeText = __('inventory-control::app.expiry_alerts.badge.this_week', ['days' => $daysLeft]);
                        }
                    @endphp
                    <tr>
                        <td class="px-3 py-2"><span class="inline-block px-2 py-0.5 rounded text-xs font-semibold {{ $badgeClass }}">{{ $badgeText }}</span></td>
                        <td class="px-3 py-2">{{ $batch->product?->name ?? '—' }}</td>
                        <td class="px-3 py-2"><code class="text-xs">{{ $batch->product?->sku ?? '—' }}</code></td>
                        <td class="px-3 py-2">{{ $batch->batch_number ?? '—' }}</td>
                        <td class="px-3 py-2 text-right">{{ $batch->quantity_remaining }}</td>
                        <td class="px-3 py-2">{{ $batch->expiry_date?->toDateString() }}</td>
                        <td class="px-3 py-2">{{ $daysLeft }}</td>
                        <td class="px-3 py-2">
                            <form method="POST" action="{{ route('admin.inventory_control.expiry_alerts.mark_removed', $batch) }}" class="inline">
                                @csrf
                                <input type="hidden" name="status" value="removed">
                                <button type="submit" class="text-xs px-2 py-1 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 rounded">{{ __('inventory-control::app.expiry_alerts.action.remove') }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.inventory_control.expiry_alerts.mark_removed', $batch) }}" class="inline">
                                @csrf
                                <input type="hidden" name="status" value="expired">
                                <button type="submit" class="text-xs px-2 py-1 bg-red-100 text-red-800 hover:bg-red-200 rounded">{{ __('inventory-control::app.expiry_alerts.action.mark_expired') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-3 py-6 text-center text-gray-500">{{ __('inventory-control::app.expiry_alerts.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin::layouts>

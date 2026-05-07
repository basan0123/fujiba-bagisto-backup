<x-admin::layouts>
    <x-slot:title>{{ __('inventory-control::app.batches.title') }}</x-slot:title>

    <div class="mb-6 flex items-center justify-between">
        <p class="text-xl font-bold text-gray-800 dark:text-white">{{ __('inventory-control::app.batches.title') }}</p>
        <a href="{{ route('admin.inventory_control.batches.create') }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">{{ __('inventory-control::app.batches.create_btn') }}</a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 text-sm bg-green-50 text-green-800 border border-green-200 rounded">{{ session('success') }}</div>
    @endif

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-left text-gray-700 dark:text-gray-300">
                <tr>
                    <th class="px-3 py-2">{{ __('inventory-control::app.batches.col.received') }}</th>
                    <th class="px-3 py-2">{{ __('inventory-control::app.batches.col.product') }}</th>
                    <th class="px-3 py-2">{{ __('inventory-control::app.batches.col.batch') }}</th>
                    <th class="px-3 py-2 text-right">{{ __('inventory-control::app.batches.col.received_qty') }}</th>
                    <th class="px-3 py-2 text-right">{{ __('inventory-control::app.batches.col.remaining') }}</th>
                    <th class="px-3 py-2">{{ __('inventory-control::app.batches.col.expiry') }}</th>
                    <th class="px-3 py-2">{{ __('inventory-control::app.batches.col.status') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($batches as $batch)
                    <tr>
                        <td class="px-3 py-2">{{ $batch->created_at?->toDateString() }}</td>
                        <td class="px-3 py-2">{{ $batch->product?->name ?? '—' }} <code class="text-xs text-gray-500">{{ $batch->product?->sku }}</code></td>
                        <td class="px-3 py-2">{{ $batch->batch_number ?? '—' }}</td>
                        <td class="px-3 py-2 text-right">{{ $batch->quantity_received }}</td>
                        <td class="px-3 py-2 text-right">{{ $batch->quantity_remaining }}</td>
                        <td class="px-3 py-2">{{ $batch->expiry_date?->toDateString() ?? '—' }}</td>
                        <td class="px-3 py-2"><span class="text-xs px-2 py-0.5 rounded bg-gray-100">{{ $batch->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-6 text-center text-gray-500">{{ __('inventory-control::app.batches.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $batches->links() }}</div>
</x-admin::layouts>

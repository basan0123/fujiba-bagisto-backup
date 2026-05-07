<x-admin::layouts>
    <x-slot:title>{{ __('inventory-control::app.stock_take.title') }}</x-slot:title>

    <div class="flex items-center justify-between mb-6">
        <p class="text-xl font-bold text-gray-800 dark:text-white">{{ __('inventory-control::app.stock_take.title') }}</p>
        <a href="{{ route('admin.inventory_control.stock_take.create') }}"
           class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md">
            + {{ __('inventory-control::app.stock_take.new_session') }}
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 text-sm bg-green-50 text-green-800 border border-green-200 rounded">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-3 text-sm bg-red-50 text-red-800 border border-red-200 rounded">{{ session('error') }}</div>
    @endif

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-left">
                <tr>
                    <th class="px-4 py-2">{{ __('inventory-control::app.stock_take.col.name') }}</th>
                    <th class="px-4 py-2">{{ __('inventory-control::app.stock_take.col.warehouse') }}</th>
                    <th class="px-4 py-2 text-right">{{ __('inventory-control::app.stock_take.col.items') }}</th>
                    <th class="px-4 py-2">{{ __('inventory-control::app.stock_take.col.status') }}</th>
                    <th class="px-4 py-2">{{ __('inventory-control::app.stock_take.col.created') }}</th>
                    <th class="px-4 py-2">{{ __('inventory-control::app.stock_take.col.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sessions as $s)
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <td class="px-4 py-2 font-medium">{{ $s->name }}</td>
                        <td class="px-4 py-2">{{ $s->inventorySource->name ?? '—' }}</td>
                        <td class="px-4 py-2 text-right">{{ $s->items_count }}</td>
                        <td class="px-4 py-2">
                            @php
                                $badge = match ($s->status) {
                                    'open'      => 'bg-blue-100 text-blue-800',
                                    'applied'   => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-gray-200 text-gray-700',
                                    default     => 'bg-gray-100 text-gray-700',
                                };
                            @endphp
                            <span class="inline-block px-2 py-0.5 text-xs rounded {{ $badge }}">
                                {{ __('inventory-control::app.stock_take.status.' . $s->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-gray-500">{{ $s->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-2">
                            <a href="{{ route('admin.inventory_control.stock_take.show', $s->id) }}"
                               class="text-blue-600 hover:underline">
                                {{ __('inventory-control::app.stock_take.open_session') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">{{ __('inventory-control::app.stock_take.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $sessions->links() }}</div>
</x-admin::layouts>

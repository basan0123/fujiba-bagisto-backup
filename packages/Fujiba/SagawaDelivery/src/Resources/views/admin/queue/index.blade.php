<x-admin::layouts>
    <x-slot:title>{{ __('sagawa::app.menu.queue') }}</x-slot>

    @php
        $statusLabel = [
            'pending_verification' => 'pending',
            'address_verified'     => 'processing',
            'address_rejected'     => 'canceled',
            'waybill_pending'      => 'pending',
            'waybill_issued'       => 'completed',
            'waybill_failed'       => 'canceled',
            'reissue_requested'    => 'closed',
        ];

        $statuses = [
            'pending_verification' => __('sagawa::app.queue.pending_verification'),
            'address_verified'     => __('sagawa::app.queue.address_verified'),
            'address_rejected'     => __('sagawa::app.queue.address_rejected'),
            'waybill_pending'      => __('sagawa::app.queue.waybill_pending'),
            'waybill_issued'       => __('sagawa::app.queue.waybill_issued'),
            'waybill_failed'       => __('sagawa::app.queue.waybill_failed'),
            'reissue_requested'    => __('sagawa::app.queue.reissue_requested'),
        ];
    @endphp

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            {{ __('sagawa::app.menu.queue') }}
        </p>
    </div>

    <!-- Status filter chips -->
    <div class="mt-3.5 flex flex-wrap gap-1.5">
        <a
            href="{{ route('admin.sagawa.queue.index') }}"
            class="rounded-full border px-3 py-1 text-xs font-semibold transition-all
                {{ ! request('status')
                    ? 'border-gray-800 bg-gray-800 text-white dark:border-gray-200 dark:bg-gray-200 dark:text-gray-900'
                    : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800' }}"
        >
            {{ __('sagawa::app.queue.all') }}
        </a>

        @foreach ($statuses as $val => $label)
            <a
                href="{{ route('admin.sagawa.queue.index', ['status' => $val]) }}"
                class="rounded-full border px-3 py-1 text-xs font-semibold transition-all
                    {{ request('status') === $val
                        ? 'border-gray-800 bg-gray-800 text-white dark:border-gray-200 dark:bg-gray-200 dark:text-gray-900'
                        : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800' }}"
            >
                {{ $label }}
            </a>
        @endforeach
    </div>

    <!-- Table -->
    <div class="mt-3.5 box-shadow overflow-hidden rounded bg-white dark:bg-gray-900">
        <div class="overflow-x-auto">
            <x-admin::table>
                <x-admin::table.thead>
                    <x-admin::table.thead.tr>
                        <x-admin::table.th>{{ __('sagawa::app.queue.order') }}</x-admin::table.th>
                        <x-admin::table.th>{{ __('sagawa::app.queue.delivery-date') }}</x-admin::table.th>
                        <x-admin::table.th>Tracking</x-admin::table.th>
                        <x-admin::table.th>Status</x-admin::table.th>
                        <x-admin::table.th>Updated</x-admin::table.th>
                    </x-admin::table.thead.tr>
                </x-admin::table.thead>

                <x-admin::table.tbody>
                    @forelse ($shipments as $s)
                        <x-admin::table.tbody.tr
                            class="cursor-pointer border-b border-gray-200 last:border-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800"
                            onclick="window.location='{{ route('admin.sagawa.queue.show', $s) }}'"
                        >
                            <x-admin::table.td class="font-semibold text-gray-800 dark:text-white">
                                #{{ $s->order_id }}
                            </x-admin::table.td>

                            <x-admin::table.td>
                                @if ($s->delivery_date)
                                    {{ $s->delivery_date->format('Y-m-d') }}
                                    @if ($s->delivery_time)
                                        <span class="text-gray-400">/ {{ $s->delivery_time }}</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </x-admin::table.td>

                            <x-admin::table.td class="font-mono text-xs">
                                {{ $s->tracking_number ?: '—' }}
                            </x-admin::table.td>

                            <x-admin::table.td>
                                <span class="label-{{ $statusLabel[$s->status] ?? 'info' }}">
                                    {{ $statuses[$s->status] ?? $s->status }}
                                </span>
                            </x-admin::table.td>

                            <x-admin::table.td class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $s->updated_at?->diffForHumans() }}
                            </x-admin::table.td>
                        </x-admin::table.tbody.tr>
                    @empty
                        <x-admin::table.tbody.tr>
                            <x-admin::table.td colspan="5" class="py-10 text-center text-sm text-gray-400">
                                {{ __('sagawa::app.queue.empty') }}
                            </x-admin::table.td>
                        </x-admin::table.tbody.tr>
                    @endforelse
                </x-admin::table.tbody>
            </x-admin::table>
        </div>

        @if ($shipments->hasPages())
            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
                {{ $shipments->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</x-admin::layouts>

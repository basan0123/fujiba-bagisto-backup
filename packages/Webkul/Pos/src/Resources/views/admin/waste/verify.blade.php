<x-admin::layouts>
    <x-slot:title>@lang('pos::app.admin.waste.verify.title')</x-slot:title>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap mb-6">
        <p class="text-xl text-gray-800 dark:text-white font-bold">
            @lang('pos::app.admin.waste.verify.title')
        </p>
        <a href="{{ route('admin.pos.waste.index') }}" class="transparent-button">
            @lang('pos::app.admin.waste.verify.back')
        </a>
    </div>

    <div class="box-shadow rounded-lg bg-white dark:bg-gray-900 p-6 grid gap-6">
        {{-- Product --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">@lang('pos::app.admin.waste.index.datagrid.product')</p>
                <p class="font-semibold text-gray-800 dark:text-white">{{ $entry->product_name }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">@lang('pos::app.admin.waste.index.datagrid.sku')</p>
                <p class="font-semibold text-gray-800 dark:text-white">{{ $entry->product_sku ?? '—' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">@lang('pos::app.admin.waste.index.datagrid.qty')</p>
                <p class="font-semibold text-gray-800 dark:text-white">{{ $entry->quantity }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">@lang('pos::app.admin.waste.index.datagrid.total_cost')</p>
                <p class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($entry->total_cost) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">@lang('pos::app.admin.waste.index.datagrid.reason')</p>
                <p class="font-semibold text-gray-800 dark:text-white">{{ $entry->reason }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">@lang('pos::app.admin.waste.index.datagrid.status')</p>
                <span class="px-2 py-0.5 rounded text-xs font-semibold
                    {{ $entry->status === 'verified' ? 'bg-green-100 text-green-800' :
                       ($entry->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                    {{ ucfirst($entry->status) }}
                </span>
            </div>
            <div class="col-span-2">
                <p class="text-sm text-gray-500 dark:text-gray-400">@lang('pos::app.admin.waste.verify.notes')</p>
                <p class="text-gray-800 dark:text-white">{{ $entry->notes ?? '—' }}</p>
            </div>
        </div>

        @if ($entry->status === 'pending')
            <div class="flex gap-3 pt-4 border-t dark:border-gray-700">
                <button
                    type="button"
                    class="primary-button"
                    onclick="updateStatus('verified')"
                >
                    @lang('pos::app.admin.waste.verify.approve')
                </button>

                <button
                    type="button"
                    class="transparent-button border-red-500 text-red-600 hover:bg-red-50"
                    onclick="updateStatus('rejected')"
                >
                    @lang('pos::app.admin.waste.verify.reject')
                </button>
            </div>
        @endif
    </div>

    <script>
        async function updateStatus(status) {
            const resp = await fetch('{{ route('admin.pos.waste.verify', $entry->id) }}', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body:    JSON.stringify({ status }),
            });
            const data = await resp.json();
            if (resp.ok) {
                window.location.href = '{{ route('admin.pos.waste.index') }}';
            } else {
                alert(data.message ?? 'Error');
            }
        }
    </script>
</x-admin::layouts>

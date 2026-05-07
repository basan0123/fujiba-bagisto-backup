<x-admin::layouts>
    <x-slot:title>Edit Remark — Stock Track</x-slot>

    <form
        action="{{ route('admin.catalog.track.remark.update', $audit->id) }}"
        method="POST"
    >
        @csrf
        @method('PUT')

        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                Edit Remark — Audit #{{ $audit->id }}
            </p>

            <div class="flex items-center gap-x-2.5">
                <a href="{{ route('admin.catalog.track.index') }}" class="secondary-button">
                    Cancel
                </a>
                <button type="submit" class="primary-button">
                    Save Remark
                </button>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div class="rounded-md border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <label class="mb-2 block text-sm font-medium text-gray-800 dark:text-white">
                        Remark
                    </label>
                    <textarea
                        name="remark"
                        rows="8"
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        placeholder="Why was the stock changed?"
                    >{{ old('remark', $audit->remark) }}</textarea>

                    @if ($audit->remark_updated_at)
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Last updated by <b>{{ $audit->remark_updater_name ?? '?' }}</b>
                            on {{ $audit->remark_updated_at }}
                        </p>
                    @endif
                </div>
            </div>

            <div>
                <div class="rounded-md border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-800 dark:bg-gray-950">
                    <p class="mb-3 font-semibold text-gray-800 dark:text-white">Audit Details</p>
                    <dl class="space-y-1.5 text-gray-700 dark:text-gray-300">
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">Date</dt><dd>{{ $audit->created_at }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">SKU</dt><dd>{{ $audit->sku ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">Product</dt><dd class="text-right">{{ $audit->product_name ?? '#'.$audit->product_id }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">Source</dt><dd>{{ $audit->source_name ?? '#'.$audit->inventory_source_id }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">From → To</dt><dd>{{ $audit->qty_before }} → {{ $audit->qty_after }}</dd></div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Δ</dt>
                            <dd>
                                <span style="color: {{ $audit->delta > 0 ? '#15803d' : '#b91c1c' }}; font-weight: 600">
                                    {{ $audit->delta > 0 ? '+' : '' }}{{ $audit->delta }}
                                </span>
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">Admin</dt><dd>{{ $audit->admin_name ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">Action</dt><dd>{{ $audit->source_action ?? '—' }}</dd></div>
                    </dl>
                </div>
            </div>
        </div>
    </form>
</x-admin::layouts>

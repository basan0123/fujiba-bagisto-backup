<x-admin::layouts>
    <x-slot:title>{{ __('inventory-control::app.batches.create_title') }}</x-slot:title>

    <p class="text-xl font-bold text-gray-800 dark:text-white mb-6">{{ __('inventory-control::app.batches.create_title') }}</p>

    @if ($errors->any())
        <div class="mb-4 p-3 text-sm bg-red-50 text-red-800 border border-red-200 rounded">
            <ul class="list-disc list-inside">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.inventory_control.batches.store') }}" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-5 space-y-4 max-w-2xl">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1">{{ __('inventory-control::app.batches.field.product_id') }} *</label>
            <input type="number" name="product_id" value="{{ old('product_id') }}" required class="w-full border rounded px-3 py-2 text-sm">
            <p class="text-xs text-gray-500 mt-1">{{ __('inventory-control::app.batches.field.product_id_hint') }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('inventory-control::app.batches.field.channel') }}</label>
                <select name="channel_id" class="w-full border rounded px-3 py-2 text-sm">
                    <option value="">—</option>
                    @foreach ($channels as $c)<option value="{{ $c->id }}" @selected(old('channel_id') == $c->id)>{{ $c->code }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('inventory-control::app.batches.field.inventory_source') }}</label>
                <select name="inventory_source_id" class="w-full border rounded px-3 py-2 text-sm">
                    <option value="">—</option>
                    @foreach ($inventorySources as $s)<option value="{{ $s->id }}" @selected(old('inventory_source_id') == $s->id)>{{ $s->name }}</option>@endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">{{ __('inventory-control::app.batches.field.batch_number') }}</label>
            <input type="text" name="batch_number" value="{{ old('batch_number') }}" maxlength="100" class="w-full border rounded px-3 py-2 text-sm">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('inventory-control::app.batches.field.quantity') }} *</label>
                <input type="number" name="quantity" value="{{ old('quantity') }}" min="1" required class="w-full border rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('inventory-control::app.batches.field.cost_price') }}</label>
                <input type="number" name="cost_price" value="{{ old('cost_price') }}" step="0.0001" min="0" class="w-full border rounded px-3 py-2 text-sm">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('inventory-control::app.batches.field.manufacture_date') }}</label>
                <input type="date" name="manufacture_date" value="{{ old('manufacture_date') }}" class="w-full border rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('inventory-control::app.batches.field.expiry_date') }}</label>
                <input type="date" name="expiry_date" value="{{ old('expiry_date') }}" class="w-full border rounded px-3 py-2 text-sm">
                <p class="text-xs text-gray-500 mt-1">{{ __('inventory-control::app.batches.field.expiry_hint') }}</p>
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('admin.inventory_control.batches.index') }}" class="px-4 py-2 text-sm bg-gray-200 rounded">{{ __('inventory-control::app.batches.cancel') }}</a>
            <button type="submit" class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">{{ __('inventory-control::app.batches.save') }}</button>
        </div>
    </form>
</x-admin::layouts>

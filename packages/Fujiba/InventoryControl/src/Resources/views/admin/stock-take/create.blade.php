<x-admin::layouts>
    <x-slot:title>{{ __('inventory-control::app.stock_take.create_title') }}</x-slot:title>

    <p class="text-xl font-bold text-gray-800 dark:text-white mb-6">{{ __('inventory-control::app.stock_take.create_title') }}</p>

    @if ($errors->any())
        <div class="mb-4 p-3 text-sm bg-red-50 text-red-800 border border-red-200 rounded">
            <ul class="list-disc list-inside">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.inventory_control.stock_take.store') }}"
          class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-5 space-y-4 max-w-2xl">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1">{{ __('inventory-control::app.stock_take.session_name') }} *</label>
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="150"
                   class="w-full border rounded px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">{{ __('inventory-control::app.stock_take.inventory_source') }} *</label>
            <select name="inventory_source_id" required class="w-full border rounded px-3 py-2 text-sm">
                <option value="">—</option>
                @foreach ($inventorySources as $s)
                    <option value="{{ $s->id }}" @selected(old('inventory_source_id') == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">{{ __('inventory-control::app.stock_take.channel') }} *</label>
            <select name="channel_id" required class="w-full border rounded px-3 py-2 text-sm">
                <option value="">—</option>
                @foreach ($channels as $c)
                    <option value="{{ $c->id }}" @selected(old('channel_id') == $c->id)>{{ $c->name ?? $c->code }}</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1">{{ __('inventory-control::app.stock_take.channel_hint') }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">{{ __('inventory-control::app.stock_take.notes') }}</label>
            <textarea name="notes" rows="3" maxlength="1000" class="w-full border rounded px-3 py-2 text-sm">{{ old('notes') }}</textarea>
        </div>

        <div class="border-t pt-3">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="advanced_mode" value="1" @checked(old('advanced_mode'))
                       class="mt-1 h-4 w-4">
                <div>
                    <p class="text-sm font-medium">{{ __('inventory-control::app.stock_take.advanced.label') }}</p>
                    <p class="text-xs text-gray-500">{{ __('inventory-control::app.stock_take.advanced.hint') }}</p>
                </div>
            </label>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-md">
                {{ __('inventory-control::app.stock_take.start') }}
            </button>
            <a href="{{ route('admin.inventory_control.stock_take.index') }}" class="text-sm text-gray-600 hover:underline">
                {{ __('inventory-control::app.stock_take.cancel') }}
            </a>
        </div>
    </form>
</x-admin::layouts>

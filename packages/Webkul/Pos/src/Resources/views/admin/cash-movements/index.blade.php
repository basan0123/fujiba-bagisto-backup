<x-admin::layouts>
    <x-slot:title>@lang('pos::app.admin.cash-movements.index.title')</x-slot:title>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap mb-6">
        <p class="text-xl text-gray-800 dark:text-white font-bold">
            @lang('pos::app.admin.cash-movements.index.title')
        </p>
        <a href="{{ route('admin.pos.cash-movements.create') }}" class="primary-button">
            @lang('pos::app.admin.cash-movements.index.add-btn')
        </a>
    </div>

    <x-admin::datagrid :src="route('admin.pos.cash-movements.index')" />
</x-admin::layouts>

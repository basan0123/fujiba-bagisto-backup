<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('pos::app.admin.orders.index.title')
    </x-slot:title>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-white font-bold py-3">
            @lang('pos::app.admin.orders.index.title')
        </p>
    </div>

    <x-admin::datagrid src="{{ route('admin.pos.orders.index') }}" />
</x-admin::layouts>


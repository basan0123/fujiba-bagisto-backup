<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('pos::app.admin.receipts.index.title')
    </x-slot:title>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-white font-bold">
            @lang('pos::app.admin.receipts.index.title')
        </p>

        @if (bouncer()->hasPermission('pos.receipts.create'))
            <div class="flex gap-x-2.5 items-center">
                <a
                    href="{{ route('admin.pos.receipts.create') }}"
                    class="primary-button"
                >
                    @lang('pos::app.admin.receipts.index.create-btn')
                </a>
            </div>
        @endif
    </div>

    <x-admin::datagrid src="{{ route('admin.pos.receipts.index') }}" />
</x-admin::layouts>

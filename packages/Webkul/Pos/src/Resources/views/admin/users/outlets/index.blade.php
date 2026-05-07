<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('pos::app.admin.users.outlets.index.title')
    </x-slot:title>
    
    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-[20px] text-gray-800 dark:text-white font-bold">
            @lang('pos::app.admin.users.outlets.index.title')
        </p>

        <div class="flex items-center gap-2.5 max-sm:flex-wrap">
            @if (bouncer()->hasPermission('pos.users.outlets.create'))
                <a
                    href="{{ route('admin.pos.outlets.create') }}"
                    class="primary-button"
                >
                    @lang('pos::app.admin.users.outlets.index.create-btn')
                </a>
            @endif

            <a
                href="{{ url()->to('/pos') }}"
                target="_blank"
                class="secondary-button"
            >
                @lang('pos::app.admin.users.outlets.index.pos-front')
            </a>
        </div>
    </div>

    <x-admin::datagrid :src="route('admin.pos.outlets.index')" />
</x-admin::layouts>

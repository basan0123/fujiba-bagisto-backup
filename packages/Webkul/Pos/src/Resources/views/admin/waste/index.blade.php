<x-admin::layouts>
    <x-slot:title>@lang('pos::app.admin.waste.index.title')</x-slot:title>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-white font-bold">
            @lang('pos::app.admin.waste.index.title')
        </p>
    </div>

    <x-admin::datagrid src="{{ route('admin.pos.waste.index') }}" />
</x-admin::layouts>

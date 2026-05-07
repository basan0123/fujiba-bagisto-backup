<x-admin::layouts>
    <x-slot:title>Stock Track</x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Stock Track — Manual Inventory Changes
        </p>
        <div class="text-sm text-gray-500 dark:text-gray-300">
            Auto-recorded for every admin stock edit (excludes sales). Read-only except remark.
        </div>
    </div>

    <x-admin::datagrid :src="route('admin.catalog.track.index')" />
</x-admin::layouts>

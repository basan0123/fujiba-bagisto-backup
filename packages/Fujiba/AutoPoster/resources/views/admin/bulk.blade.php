<x-admin::layouts>
    <x-slot:title>Auto Poster — Bulk Schedule</x-slot:title>

    <div class="flex flex-col gap-4">
        <p class="text-xl text-gray-800 dark:text-white font-bold">Bulk Schedule</p>

        @include('autoposter::admin._nav')
        @include('autoposter::admin._flash')

        <div class="p-3 border border-blue-500 rounded text-sm bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200">
            Select products and they will be split into posts of
            <strong>{{ $settings->products_per_post }}</strong>, spread across the daily posting slots
            starting from the date you choose. Every post is created as <strong>scheduled</strong> —
            review and edit each one in the queue before it goes live.
            <span class="block mt-1">{{ $eligibleCount }} product(s) currently eligible.</span>
        </div>

        <form method="POST" action="{{ route('admin.autoposter.bulk.store') }}" class="flex flex-col gap-4">
            @csrf
            <input type="hidden" name="channel_id" value="{{ $channelId }}">

            <div class="border rounded p-4 dark:border-gray-700 grid gap-4 md:grid-cols-3">
                <label class="text-sm">
                    <span class="block mb-1">Start date</span>
                    <input type="date" name="start_date" value="{{ now($settings->timezone ?: 'Asia/Tokyo')->addDay()->toDateString() }}"
                           class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                </label>

                <label class="text-sm">
                    <span class="block mb-1">Spread over (max days)</span>
                    <input type="number" name="max_days" min="1" max="60" value="14"
                           class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                </label>

                <div class="text-sm flex items-end">
                    <button class="w-full px-4 py-2 bg-indigo-600 text-white rounded"
                            onclick="return confirm('Create scheduled posts from the selected products?');">
                        Build schedule →
                    </button>
                </div>
            </div>

            <div class="border rounded p-4 dark:border-gray-700">
                <h2 class="font-semibold mb-3">Select products</h2>
                @include('autoposter::admin._picker')
            </div>
        </form>
    </div>
</x-admin::layouts>

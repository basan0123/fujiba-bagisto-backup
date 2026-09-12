<x-admin::layouts>
    <x-slot:title>Auto Poster — Manual Post</x-slot:title>

    <div class="flex flex-col gap-4">
        <p class="text-xl text-gray-800 dark:text-white font-bold">Manual Post</p>

        @include('autoposter::admin._nav')
        @include('autoposter::admin._flash')

        @if ($aiAvailable)
            <div class="p-3 border border-green-500 rounded text-sm bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200">
                Captions are written by <strong>Claude</strong> in EN / JA / MY, guided by your
                selected template's tone and toggles. You can edit the result on the next screen.
            </div>
        @else
            <div class="p-3 border border-blue-500 rounded text-sm bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200">
                Captions are rendered from your saved <strong>templates</strong>.
                AI caption generation is not configured yet (no <code>ANTHROPIC_API_KEY</code>),
                so text comes straight from the template — you can edit it on the next screen.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.autoposter.posts.store') }}" class="flex flex-col gap-4">
            @csrf
            <input type="hidden" name="channel_id" value="{{ $channelId }}">

            <div class="border rounded p-4 dark:border-gray-700 grid gap-4 md:grid-cols-3">
                <label class="text-sm">
                    <span class="block mb-1">Format</span>
                    <select name="format" class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                        <option value="multi_photo" @selected($settings->default_format === 'multi_photo')>Multi-photo carousel</option>
                        <option value="single_image" @selected($settings->default_format === 'single_image')>Single image + link</option>
                    </select>
                </label>

                <label class="text-sm">
                    <span class="block mb-1">Caption template</span>
                    <select name="template_id" class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700">
                        <option value="">— default —</option>
                        @foreach ($templates as $tpl)
                            <option value="{{ $tpl->id }}" @selected($tpl->id == $settings->default_template_id)>
                                {{ $tpl->name }} ({{ $tpl->language_mode }})
                            </option>
                        @endforeach
                    </select>
                </label>

                <div class="text-sm flex items-end">
                    <button class="w-full px-4 py-2 bg-blue-600 text-white rounded">
                        Create draft &amp; preview →
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

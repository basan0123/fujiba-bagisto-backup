<x-admin::layouts>
    <x-slot:title>
        {{ __('fujiba_livechat::app.settings.page_title') }}
    </x-slot>

    <div class="flex items-center justify-between gap-4 mb-5">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            {{ __('fujiba_livechat::app.settings.page_title') }}
        </p>
    </div>

    <div class="rounded-lg border bg-white p-4 dark:bg-gray-900 dark:border-gray-800">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b">
                    <th class="p-2 text-left">{{ __('fujiba_livechat::app.common.channel') }}</th>
                    <th class="p-2 text-left">{{ __('fujiba_livechat::app.settings.col_chatpion_url') }}</th>
                    <th class="p-2 text-left">{{ __('fujiba_livechat::app.settings.col_fb_page_id') }}</th>
                    <th class="p-2 text-left">{{ __('fujiba_livechat::app.settings.col_bot_id') }}</th>
                    <th class="p-2 text-left">{{ __('fujiba_livechat::app.settings.col_livechat') }}</th>
                    <th class="p-2 text-left">{{ __('fujiba_livechat::app.settings.col_auto_login') }}</th>
                    <th class="p-2 text-left">{{ __('fujiba_livechat::app.settings.col_notifications') }}</th>
                    <th class="p-2 text-left">{{ __('fujiba_livechat::app.settings.col_actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($settings as $setting)
                <tr class="border-b">
                    <td class="p-2">{{ $setting->channel_code }}</td>
                    <td class="p-2">{{ $setting->chatpion_url_1 }}</td>
                    <td class="p-2">{{ $setting->fb_page_id }}</td>
                    <td class="p-2">{{ $setting->fb_bot_id }}</td>
                    <td class="p-2">{!! $setting->livechat_enabled ? '<span class="text-green-600">'.__('fujiba_livechat::app.common.on').'</span>' : '<span class="text-red-600">'.__('fujiba_livechat::app.common.off').'</span>' !!}</td>
                    <td class="p-2">{!! $setting->auto_login_enabled ? '<span class="text-green-600">'.__('fujiba_livechat::app.common.on').'</span>' : '<span class="text-red-600">'.__('fujiba_livechat::app.common.off').'</span>' !!}</td>
                    <td class="p-2">{!! $setting->order_notifications_enabled ? '<span class="text-green-600">'.__('fujiba_livechat::app.common.on').'</span>' : '<span class="text-red-600">'.__('fujiba_livechat::app.common.off').'</span>' !!}</td>
                    <td class="p-2">
                        <a href="{{ route('admin.fujiba.livechat.settings.show', $setting->channel_id) }}" class="text-blue-600 hover:underline mr-2">{{ __('fujiba_livechat::app.common.edit') }}</a>
                        <button class="text-yellow-600 hover:underline test-btn" data-channel="{{ $setting->channel_id }}">{{ __('fujiba_livechat::app.common.test') }}</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="p-4 text-center text-gray-500">{{ __('fujiba_livechat::app.settings.no_channels') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <hr class="my-4">
        <p class="font-semibold mb-2">{{ __('fujiba_livechat::app.settings.add_channel') }}</p>
        @foreach($channels as $channel)
            @php $existing = $settings->firstWhere('channel_id', $channel->id); @endphp
            @if(!$existing)
            <a href="{{ route('admin.fujiba.livechat.settings.show', $channel->id) }}" class="inline-block px-3 py-1 m-1 text-sm bg-green-100 text-green-700 rounded hover:bg-green-200">
                + {{ $channel->name }} ({{ $channel->code }})
            </a>
            @endif
        @endforeach
    </div>

@push('scripts')
<script>
const LC_TEST_I18N = {
    test:    @json(__('fujiba_livechat::app.common.test')),
    testing: @json(__('fujiba_livechat::app.common.testing')),
    error:   @json(__('fujiba_livechat::app.settings.error_saving')),
};
document.querySelectorAll('.test-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const channelId = this.dataset.channel;
        this.textContent = LC_TEST_I18N.testing;
        try {
            const res = await fetch(`{{ url(config('app.admin_path', 'admin') . '/fujiba-livechat/settings') }}/${channelId}/test`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            });
            const data = await res.json();
            this.textContent = JSON.stringify(data);
            setTimeout(() => { this.textContent = LC_TEST_I18N.test; }, 3000);
        } catch(e) {
            this.textContent = LC_TEST_I18N.error;
            setTimeout(() => { this.textContent = LC_TEST_I18N.test; }, 3000);
        }
    });
});
</script>
@endpush

</x-admin::layouts>

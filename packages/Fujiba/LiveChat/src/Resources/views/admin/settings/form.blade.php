<x-admin::layouts>
    <x-slot:title>
        {{ __('fujiba_livechat::app.settings.page_title') }} - {{ $channel->name ?? __('fujiba_livechat::app.common.channel') }}
    </x-slot>

    <div class="flex items-center justify-between gap-4 mb-5">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            {{ __('fujiba_livechat::app.settings.page_title') }} &mdash; {{ $channel->name ?? __('fujiba_livechat::app.common.channel') }} ({{ $channel->code ?? '' }})
        </p>
    </div>

    <div class="rounded-lg border bg-white p-6 dark:bg-gray-900 dark:border-gray-800">
        <form id="settingsForm" class="grid gap-4 max-w-2xl">
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('fujiba_livechat::app.settings.chatpion_url_1') }} *</label>
                <input type="url" name="chatpion_url_1" value="{{ $setting->chatpion_url_1 ?? '' }}" required class="w-full rounded border px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-700" placeholder="https://fujiba.shop">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('fujiba_livechat::app.settings.chatpion_url_2') }}</label>
                <input type="url" name="chatpion_url_2" value="{{ $setting->chatpion_url_2 ?? '' }}" class="w-full rounded border px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-700">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('fujiba_livechat::app.settings.chatpion_url_3') }}</label>
                <input type="url" name="chatpion_url_3" value="{{ $setting->chatpion_url_3 ?? '' }}" class="w-full rounded border px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-700">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('fujiba_livechat::app.settings.chatpion_api_key') }} *</label>
                <input type="text" name="chatpion_api_key" value="{{ $setting->chatpion_api_key ?? '' }}" required class="w-full rounded border px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-700">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('fujiba_livechat::app.settings.chatpion_user_id') }}</label>
                <input type="text" name="chatpion_user_id" value="{{ $setting->chatpion_user_id ?? '' }}" class="w-full rounded border px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-700">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('fujiba_livechat::app.settings.fb_page_id_field') }} *</label>
                <input type="text" name="fb_page_id" value="{{ $setting->fb_page_id ?? '' }}" required class="w-full rounded border px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-700">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">{{ __('fujiba_livechat::app.settings.bot_id_field') }} *</label>
                <input type="text" name="fb_bot_id" value="{{ $setting->fb_bot_id ?? '' }}" required class="w-full rounded border px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-700">
            </div>
            <hr class="my-2">
            <div class="flex flex-col gap-2">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="livechat_enabled" value="1" {{ ($setting->livechat_enabled ?? true) ? 'checked' : '' }}> {{ __('fujiba_livechat::app.settings.livechat_enabled') }}</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="auto_login_enabled" value="1" {{ ($setting->auto_login_enabled ?? true) ? 'checked' : '' }}> {{ __('fujiba_livechat::app.settings.auto_login_enabled') }}</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="order_notifications_enabled" value="1" {{ ($setting->order_notifications_enabled ?? true) ? 'checked' : '' }}> {{ __('fujiba_livechat::app.settings.order_notifs_enabled') }}</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="customer_chat_plugin_enabled" value="1" {{ ($setting->customer_chat_plugin_enabled ?? false) ? 'checked' : '' }}> {{ __('fujiba_livechat::app.settings.customer_chat_enabled') }}</label>
            </div>

            <hr class="my-2">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Auto-flow dispatcher</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Sends a Messenger flow to new customers who messaged the page but received no staff or bot reply within the lookback window. Runs every 15 minutes.</p>
            <div class="flex flex-col gap-3">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="auto_flow_enabled" value="1" {{ ($setting->auto_flow_enabled ?? false) ? 'checked' : '' }}>
                    Enable auto-flow for this channel
                </label>
                <div>
                    <label class="block text-xs font-medium mb-1">Flow postback ID</label>
                    <input type="text" name="auto_flow_postback_id" maxlength="64"
                        value="{{ $setting->auto_flow_postback_id ?? '' }}"
                        class="w-full rounded border px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-700"
                        placeholder="e.g. 3NYSXhmp_yz-Jvt9">
                    <p class="text-xs text-gray-500 mt-1">ChatPion postback_id of the flow to send. Leave blank to disable.</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium mb-1">Lookback minutes</label>
                        <input type="number" name="auto_flow_lookback_minutes" min="5" max="1440"
                            value="{{ $setting->auto_flow_lookback_minutes ?? 20 }}"
                            class="w-full rounded border px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-700">
                        <p class="text-xs text-gray-500 mt-1">How far back to look for unanswered messages. Max 1440 (24h). Use 720 (12h) or 1440 (24h) to catch backlog; FB's 24h messaging window blocks anything older.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Grace minutes</label>
                        <input type="number" name="auto_flow_grace_minutes" min="0" max="60"
                            value="{{ $setting->auto_flow_grace_minutes ?? 3 }}"
                            class="w-full rounded border px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-700">
                        <p class="text-xs text-gray-500 mt-1">Skip messages newer than this (lets staff reply first).</p>
                    </div>
                </div>
            </div>
            <hr class="my-2">
            <p id="abandoned-cart" class="text-sm font-semibold text-gray-700 dark:text-gray-200">Abandoned cart reminder</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Sends a Messenger reminder + product carousel to logged-in customers whose cart has been idle longer than the delay below. Runs every 15 minutes. Only customers within FB's 24h messaging window are reachable; one reminder per cart.</p>
            <div class="flex flex-col gap-3">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="abandoned_cart_enabled" value="1" {{ ($setting->abandoned_cart_enabled ?? false) ? 'checked' : '' }}>
                    Enable abandoned-cart reminder for this channel
                </label>
                <div>
                    <label class="block text-xs font-medium mb-1">Delay minutes</label>
                    <input type="number" name="abandoned_cart_delay_minutes" min="5" max="1440"
                        value="{{ $setting->abandoned_cart_delay_minutes ?? 60 }}"
                        class="w-full rounded border px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-700">
                    <p class="text-xs text-gray-500 mt-1">Wait this long after the customer's last cart update before sending. Default 60. Min 5, max 1440 (24h — past this FB window blocks the send).</p>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Message (English) — <span class="text-red-500">required</span></label>
                    <textarea name="abandoned_cart_message_en" rows="3" maxlength="2000"
                        class="w-full rounded border px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-700 font-mono"
                        v-pre>{{ $setting->abandoned_cart_message_en ?? '' }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Placeholders: <code>@{{ customer.first_name }}</code>, <code>@{{ customer.last_name }}</code>, <code>@{{ cart_url }}</code>. The cart URL is auto-appended if you don't include it.</p>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Message (Japanese)</label>
                    <textarea name="abandoned_cart_message_ja" rows="3" maxlength="2000"
                        class="w-full rounded border px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-700 font-mono"
                        v-pre>{{ $setting->abandoned_cart_message_ja ?? '' }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Used when the channel default locale is <code>ja</code>. Falls back to English if blank.</p>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Message (Burmese)</label>
                    <textarea name="abandoned_cart_message_my" rows="3" maxlength="2000"
                        class="w-full rounded border px-3 py-2 text-sm dark:bg-gray-800 dark:border-gray-700 font-mono"
                        v-pre>{{ $setting->abandoned_cart_message_my ?? '' }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Used when channel default locale is <code>my</code>. Falls back to English if blank.</p>
                </div>
            </div>

            <hr class="my-2">
            <div class="flex items-center gap-3">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">{{ __('fujiba_livechat::app.common.save') }}</button>
                <a href="{{ route('admin.fujiba.livechat.settings.index') }}" class="px-4 py-2 border rounded text-sm hover:bg-gray-50 dark:hover:bg-gray-800">{{ __('fujiba_livechat::app.common.back') }}</a>
                <span id="saveStatus" class="text-sm"></span>
            </div>
        </form>
    </div>

@push('scripts')
<script>
const LC_FORM_I18N = {
    saving: @json(__('fujiba_livechat::app.settings.saving')),
    saved:  @json(__('fujiba_livechat::app.settings.saved')),
    error:  @json(__('fujiba_livechat::app.settings.error_saving')),
};
document.getElementById('settingsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const status = document.getElementById('saveStatus');
    status.textContent = LC_FORM_I18N.saving;
    const fd = new FormData(this);
    const body = {};
    fd.forEach((v,k) => body[k] = v);
    ['livechat_enabled','auto_login_enabled','order_notifications_enabled','customer_chat_plugin_enabled','auto_flow_enabled','abandoned_cart_enabled'].forEach(k => {
        body[k] = fd.has(k) ? 1 : 0;
    });
    try {
        const res = await fetch('{{ route("admin.fujiba.livechat.settings.save", $channel->id ?? 0) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(body),
        });
        const data = await res.json();
        status.textContent = data.status === 'saved' ? LC_FORM_I18N.saved : JSON.stringify(data);
        status.style.color = data.status === 'saved' ? 'green' : 'red';
    } catch(e) {
        status.textContent = LC_FORM_I18N.error;
        status.style.color = 'red';
    }
});
</script>
@endpush

</x-admin::layouts>

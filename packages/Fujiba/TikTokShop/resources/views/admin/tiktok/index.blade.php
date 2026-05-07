<x-admin::layouts>
    <x-slot:title>TikTok Shop</x-slot:title>

    <div class="flex flex-col gap-4">
        <p class="text-xl text-gray-800 dark:text-white font-bold">
            TikTok Shop — Channel Connections
        </p>

        @if ($categoryIdMissing)
            <div class="p-3 border-l-4 border-red-500 rounded text-sm bg-red-50 dark:bg-red-900/20">
                <strong>TIKTOK_DEFAULT_CATEGORY_ID is not set.</strong>
                Product sync is disabled across all channels until this is set in
                <code>.env</code>. The "Sync Products Now" buttons are hidden below.
                See §8 of the master prompt.
            </div>
        @endif

        @if (session('success'))
            <div class="p-3 border border-green-500 rounded text-sm bg-green-50 dark:bg-green-900/20">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="p-3 border border-red-500 rounded text-sm bg-red-50 dark:bg-red-900/20">
                {{ session('error') }}
            </div>
        @endif

        @foreach ($channels as $channel)
            @php($creds = $credentialsByChannel->get($channel->id))
            @php($stats = $syncStats[$channel->id] ?? null)
            @php($hasAppCreds = $creds && ! empty($creds->tiktok_app_key) && ! empty($creds->tiktok_app_secret) && ! empty($creds->redirect_uri))

            <div class="border rounded p-4 flex flex-col gap-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-lg font-semibold">
                            {{ $channel->code }} — {{ $channel->name }}
                        </h2>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            @if (! $creds || ! $creds->is_active)
                                <span class="text-gray-500">Not connected to TikTok</span>
                            @else
                                <span class="text-green-600">Connected</span>
                                @if ($creds->tiktok_shop_name)
                                    — {{ $creds->tiktok_shop_name }}
                                @endif
                                @if ($creds->tiktok_shop_id)
                                    <span class="text-xs text-gray-500">(ID: {{ $creds->tiktok_shop_id }})</span>
                                @endif
                                @if ($creds->token_expires_at)
                                    @php($mins = (int) max(0, now()->diffInMinutes($creds->token_expires_at, false)))
                                    <span class="text-xs ml-2 {{ $mins < 60 ? 'text-orange-600' : 'text-gray-500' }}">
                                        @if ($mins <= 0)
                                            Token EXPIRED — refresh pending
                                        @elseif ($mins < 60)
                                            Token expires in {{ $mins }}m
                                        @else
                                            Token expires {{ $creds->token_expires_at->diffForHumans() }}
                                        @endif
                                    </span>
                                @endif
                            @endif
                        </div>

                        @if ($stats)
                            <div class="text-xs mt-1">
                                <span class="text-green-600">✓ {{ $stats['synced'] }} synced</span>
                                @if ($stats['failed'] > 0)
                                    <span class="text-red-600 ml-2">✗ {{ $stats['failed'] }} failed</span>
                                @endif
                                @if (($stats['last_synced_at'] ?? null))
                                    <span class="text-gray-500 ml-2">last sync {{ $stats['last_synced_at']->diffForHumans() }}</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-col gap-2 items-end">
                        @if ($creds && $creds->is_active)
                            <form method="POST" action="{{ route('admin.tiktok.disconnect') }}">
                                @csrf
                                <input type="hidden" name="channel_id" value="{{ $channel->id }}">
                                <button type="submit" class="secondary-button">Disconnect</button>
                            </form>
                        @endif
                    </div>
                </div>

                <details {{ $hasAppCreds ? '' : 'open' }} class="border rounded">
                    <summary class="p-3 cursor-pointer text-sm font-medium bg-gray-50 dark:bg-gray-800">
                        TikTok App Credentials
                        @if ($hasAppCreds)
                            <span class="text-xs text-green-600 ml-2">(saved)</span>
                        @else
                            <span class="text-xs text-red-600 ml-2">(required before Connect)</span>
                        @endif
                    </summary>

                    <div class="p-4 flex flex-col gap-3">
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            Each channel uses its own TikTok developer-app credentials. Fields left blank
                            on save are unchanged. The app secret is encrypted at rest.
                        </p>

                        <form method="POST" action="{{ route('admin.tiktok.credentials.save') }}" class="flex flex-col gap-2">
                            @csrf
                            <input type="hidden" name="channel_id" value="{{ $channel->id }}">

                            <label class="flex flex-col gap-1 text-sm">
                                <span>App Key</span>
                                <input type="text" name="tiktok_app_key"
                                       placeholder="{{ $creds?->tiktok_app_key ? '(saved — leave blank to keep)' : 'Enter app key' }}"
                                       class="border rounded p-2 dark:bg-gray-800 font-mono text-xs">
                            </label>

                            <label class="flex flex-col gap-1 text-sm">
                                <span>App Secret</span>
                                <input type="password" name="tiktok_app_secret"
                                       placeholder="{{ $creds?->tiktok_app_secret ? '(saved — leave blank to keep)' : 'Enter app secret' }}"
                                       class="border rounded p-2 dark:bg-gray-800 font-mono text-xs">
                            </label>

                            <label class="flex flex-col gap-1 text-sm">
                                <span>Redirect URI <span class="text-xs text-gray-500">(this exact value must be registered in TikTok dev console)</span></span>
                                <input type="text" name="redirect_uri" required
                                       value="{{ $creds?->redirect_uri ?? url('/admin/tiktok/callback') }}"
                                       class="border rounded p-2 dark:bg-gray-800 font-mono text-xs">
                            </label>

                            <button type="submit" class="primary-button mt-2 w-fit">Save Credentials</button>
                        </form>

                        @if ($webhookUrl)
                            <div class="mt-3">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                    Webhook URL (paste into TikTok dev console):
                                </div>
                                <input type="text" readonly value="{{ $webhookUrl }}"
                                       onclick="this.select()"
                                       class="w-full border rounded p-2 font-mono text-xs dark:bg-gray-800">
                            </div>
                        @endif
                    </div>
                </details>

                <div class="flex gap-2 items-center">
                    @if (! $creds || ! $creds->is_active)
                        @if ($hasAppCreds)
                            <a href="{{ route('admin.tiktok.connect', ['channel_id' => $channel->id]) }}"
                               class="primary-button">
                                Connect TikTok Shop
                            </a>
                        @else
                            <button type="button" disabled
                                    title="Save app_key, app_secret, and redirect_uri above first"
                                    class="primary-button opacity-50 cursor-not-allowed">
                                Connect TikTok Shop
                            </button>
                            <span class="text-xs text-gray-500 italic">
                                Save app credentials first
                            </span>
                        @endif
                    @else
                        @if (! $categoryIdMissing)
                            <form method="POST" action="{{ route('admin.tiktok.sync.products') }}" class="inline">
                                @csrf
                                <input type="hidden" name="channel_id" value="{{ $channel->id }}">
                                <button type="submit" class="primary-button">Sync Products Now</button>
                            </form>

                            @if ($stats && ($stats['failed'] ?? 0) > 0)
                                <form method="POST" action="{{ route('admin.tiktok.sync.products.retry_failed') }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="channel_id" value="{{ $channel->id }}">
                                    <button type="submit" class="secondary-button">
                                        Retry {{ $stats['failed'] }} Failed
                                    </button>
                                </form>
                            @endif
                        @else
                            <span class="text-xs text-gray-500 italic">
                                Sync disabled (set TIKTOK_DEFAULT_CATEGORY_ID)
                            </span>
                        @endif
                    @endif
                </div>
            </div>
        @endforeach

        <p class="text-xs text-gray-500 max-w-2xl mt-2">
            Tokens are stored encrypted at rest (Laravel <code>encrypted</code> cast). The DB
            row's <code>tiktok_app_secret</code>, <code>access_token</code>, and
            <code>refresh_token</code> columns appear as ciphertext in any direct DB
            inspection — this is by design.
        </p>
    </div>
</x-admin::layouts>

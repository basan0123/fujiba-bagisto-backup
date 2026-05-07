<x-admin::layouts>
    <x-slot:title>Facebook Catalog</x-slot:title>

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <p class="text-xl text-gray-800 dark:text-white font-bold">
                Facebook Catalog — Channel Connections
            </p>
            <a href="{{ route('admin.facebook_catalog.products.index') }}"
               class="px-4 py-2 bg-blue-600 text-white rounded text-sm">
                Manage approved products →
            </a>
        </div>

        <div class="p-3 border-l-4 border-blue-500 rounded text-sm bg-blue-50 dark:bg-blue-900/20">
            Sync products to Facebook Commerce Manager Catalog. Each channel maps to one Facebook catalog.
            Approve products on the <strong>Products</strong> page first, then click <strong>Sync now</strong> below.
            Price &amp; stock changes auto-resync if enabled per channel.
        </div>

        @if (session('success'))
            <div class="p-3 border border-green-500 rounded text-sm bg-green-50 dark:bg-green-900/20">
                {{ session('success') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="p-3 border border-yellow-500 rounded text-sm bg-yellow-50 dark:bg-yellow-900/20">
                {{ session('warning') }}
            </div>
        @endif

        @if (session('error'))
            <div class="p-3 border border-red-500 rounded text-sm bg-red-50 dark:bg-red-900/20">
                {{ session('error') }}
            </div>
        @endif

        @foreach ($channels as $channel)
            @php($cfg = $settingsByChannel->get($channel->id))
            @php($cstats = $stats[$channel->id] ?? null)

            <div class="border rounded p-4 flex flex-col gap-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-lg font-semibold">
                            {{ $channel->code }} — {{ $channel->name }}
                        </h2>
                        <div class="text-sm mt-1">
                            @if (! $cfg)
                                <span class="text-gray-500">No settings yet</span>
                            @elseif (! $cfg->is_active)
                                <span class="text-gray-500">Configured (disabled)</span>
                                @if ($cfg->last_sync_error)
                                    <div class="text-red-600 text-xs mt-1">{{ $cfg->last_sync_error }}</div>
                                @endif
                            @else
                                <span class="text-green-600">Active — catalog {{ $cfg->catalog_id }}</span>
                            @endif

                            @if ($cstats)
                                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    Approved: {{ $cstats['approved'] ?? 0 }} ·
                                    Synced: {{ $cstats['synced'] ?? 0 }} ·
                                    Failed: {{ $cstats['failed'] ?? 0 }} ·
                                    Pending/Queued: {{ ($cstats['pending'] ?? 0) + ($cstats['queued'] ?? 0) }}
                                    @if (! empty($cstats['last_synced_at']))
                                        — last sync {{ \Carbon\Carbon::parse($cstats['last_synced_at'])->diffForHumans() }}
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex gap-2">
                        @if ($cfg && $cfg->is_active)
                            <form method="POST" action="{{ route('admin.facebook_catalog.sync.all') }}">
                                @csrf
                                <input type="hidden" name="channel_id" value="{{ $channel->id }}">
                                <button class="px-3 py-1 bg-blue-600 text-white text-sm rounded">
                                    Sync now
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.facebook_catalog.sync.retry') }}">
                                @csrf
                                <input type="hidden" name="channel_id" value="{{ $channel->id }}">
                                <button class="px-3 py-1 bg-orange-500 text-white text-sm rounded">
                                    Retry failed
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.facebook_catalog.settings.disable') }}">
                                @csrf
                                <input type="hidden" name="channel_id" value="{{ $channel->id }}">
                                <button class="px-3 py-1 bg-gray-300 text-sm rounded">Disable</button>
                            </form>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.facebook_catalog.settings.save') }}"
                      class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                    @csrf
                    <input type="hidden" name="channel_id" value="{{ $channel->id }}">

                    <label class="flex flex-col gap-1">
                        <span>Catalog ID *</span>
                        <input name="catalog_id" required
                               value="{{ old('catalog_id', $cfg->catalog_id ?? '') }}"
                               class="border rounded px-2 py-1 dark:bg-gray-800">
                    </label>

                    <label class="flex flex-col gap-1">
                        <span>Business ID</span>
                        <input name="business_id"
                               value="{{ old('business_id', $cfg->business_id ?? '') }}"
                               class="border rounded px-2 py-1 dark:bg-gray-800">
                    </label>

                    <label class="flex flex-col gap-1">
                        <span>Page ID</span>
                        <input name="page_id"
                               value="{{ old('page_id', $cfg->page_id ?? '') }}"
                               class="border rounded px-2 py-1 dark:bg-gray-800">
                    </label>

                    <label class="flex flex-col gap-1">
                        <span>Pixel ID</span>
                        <input name="pixel_id"
                               value="{{ old('pixel_id', $cfg->pixel_id ?? '') }}"
                               class="border rounded px-2 py-1 dark:bg-gray-800">
                    </label>

                    <label class="flex flex-col gap-1">
                        <span>Default currency</span>
                        <input name="default_currency" maxlength="3"
                               value="{{ old('default_currency', $cfg->default_currency ?? 'JPY') }}"
                               class="border rounded px-2 py-1 dark:bg-gray-800">
                    </label>

                    <label class="flex flex-col gap-1">
                        <span>Default locale</span>
                        <input name="default_locale"
                               value="{{ old('default_locale', $cfg->default_locale ?? 'ja') }}"
                               class="border rounded px-2 py-1 dark:bg-gray-800">
                    </label>

                    <label class="flex flex-col gap-1 md:col-span-3">
                        <span>Access token (system-user token recommended) — leave blank to keep existing</span>
                        <input name="access_token" type="password" autocomplete="off"
                               placeholder="{{ $cfg && $cfg->access_token ? '•••••• stored ••••••' : 'paste token' }}"
                               class="border rounded px-2 py-1 dark:bg-gray-800">
                    </label>

                    <label class="flex items-center gap-2 md:col-span-3">
                        <input type="checkbox" name="auto_sync_price_stock" value="1"
                            {{ old('auto_sync_price_stock', $cfg->auto_sync_price_stock ?? true) ? 'checked' : '' }}>
                        <span>Auto-sync price &amp; stock changes</span>
                    </label>

                    <div class="md:col-span-3 flex gap-2 mt-2">
                        <button class="px-4 py-2 bg-green-600 text-white text-sm rounded">Save</button>
                        @if ($cfg)
                            <button formaction="{{ route('admin.facebook_catalog.settings.test') }}"
                                    class="px-4 py-2 bg-blue-600 text-white text-sm rounded">
                                Test &amp; Activate
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        @endforeach
    </div>
</x-admin::layouts>

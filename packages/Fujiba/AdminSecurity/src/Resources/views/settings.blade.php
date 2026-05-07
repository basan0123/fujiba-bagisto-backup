<x-admin::layouts>
    <x-slot:title>Admin Security Settings</x-slot>

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                Admin Security Settings
            </p>
        </div>

        @if (session('success'))
            <div class="rounded border border-green-300 bg-green-50 p-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded border border-red-300 bg-red-50 p-3 text-red-800">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded border border-amber-300 bg-amber-50 p-3 text-amber-900">
            <strong>Phase 1 — observation mode.</strong>
            Saving these settings will NOT block any traffic yet. The middleware is currently logging only.
            Enforcement is flipped per-tenant in Phase 3 by an administrator.
        </div>

        <form method="POST" action="{{ route('admin.security.settings.update') }}" class="flex flex-col gap-4" id="admin-security-form">
            @csrf

            <div class="rounded border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="mb-2 font-semibold">Tenant</p>
                <p class="text-sm text-gray-600">
                    Tenant ID: <code>{{ $tenantId ?? '(none — central context)' }}</code>
                </p>
            </div>

            <div class="rounded border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <label class="mb-2 block font-semibold" for="secret_path">Secret admin path</label>
                <input
                    type="text"
                    name="secret_path"
                    id="secret_path"
                    value="{{ old('secret_path', $row->secret_path ?? '') }}"
                    placeholder="e.g. ops-console-7Hf2"
                    class="w-full rounded border border-gray-300 px-3 py-2 dark:bg-gray-800"
                    pattern="[A-Za-z0-9\-]{8,64}"
                    autocomplete="off"
                >
                <p class="mt-1 text-xs text-gray-500">
                    Alphanumeric and dashes, 8–64 chars. When set + "Block default /admin" is on, the admin panel is reachable only via this path. Leave blank to use default /admin.
                </p>
            </div>

            <div class="rounded border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="mb-2 font-semibold">Security mode</p>
                @php $mode = old('security_mode', $row->security_mode ?? 'none'); @endphp
                <label class="mb-1 flex items-center gap-2">
                    <input type="radio" name="security_mode" value="none" {{ $mode === 'none' ? 'checked' : '' }}>
                    <span>None — secret path only (or no extra check)</span>
                </label>
                <label class="mb-1 flex items-center gap-2">
                    <input type="radio" name="security_mode" value="basic_auth" {{ $mode === 'basic_auth' ? 'checked' : '' }}>
                    <span>HTTP Basic Auth</span>
                </label>
                <label class="mb-1 flex items-center gap-2">
                    <input type="radio" name="security_mode" value="ip_allowlist" {{ $mode === 'ip_allowlist' ? 'checked' : '' }}>
                    <span>IP allowlist</span>
                </label>
            </div>

            <div class="rounded border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="mb-2 font-semibold">Basic auth credentials</p>
                <label class="mb-1 block text-sm">Username</label>
                <input
                    type="text"
                    name="basic_auth_user"
                    value="{{ old('basic_auth_user', $row->basic_auth_user ?? '') }}"
                    class="mb-2 w-full rounded border border-gray-300 px-3 py-2 dark:bg-gray-800"
                    autocomplete="off"
                >
                <label class="mb-1 block text-sm">
                    Password (leave blank to keep existing)
                </label>
                <input
                    type="password"
                    name="basic_auth_pass_new"
                    class="w-full rounded border border-gray-300 px-3 py-2 dark:bg-gray-800"
                    autocomplete="new-password"
                >
                @if (! empty($row?->basic_auth_pass_hash))
                    <p class="mt-1 text-xs text-gray-500">A password is currently set.</p>
                @endif
            </div>

            <div class="rounded border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <label class="mb-2 block font-semibold" for="allowed_ips_text">Allowed IPs (one per line, CIDR or single IP)</label>
                <textarea
                    name="allowed_ips_text"
                    id="allowed_ips_text"
                    rows="4"
                    class="w-full rounded border border-gray-300 px-3 py-2 font-mono text-sm dark:bg-gray-800"
                    placeholder="203.0.113.5&#10;10.0.0.0/8"
                >{{ old('allowed_ips_text', $allowedIpsText) }}</textarea>
            </div>

            <div class="rounded border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <label class="flex items-start gap-2">
                    <input
                        type="checkbox"
                        name="block_default_admin"
                        value="1"
                        {{ old('block_default_admin', $row->block_default_admin ?? false) ? 'checked' : '' }}
                        class="mt-1"
                    >
                    <span>
                        <strong>Block the default <code>/admin</code> path</strong>
                        <span class="block text-sm text-gray-600">
                            Once enabled, /admin returns 404 and only the secret path resolves. Set a secret path before turning this on.
                        </span>
                    </span>
                </label>
            </div>

            <div class="rounded border border-amber-300 bg-amber-50 p-4">
                <label class="flex items-start gap-2">
                    <input
                        type="checkbox"
                        name="enabled"
                        value="1"
                        {{ old('enabled', $row->enabled ?? false) ? 'checked' : '' }}
                        class="mt-1"
                    >
                    <span>
                        <strong>Enable enforcement</strong>
                        <span class="block text-sm">
                            (Phase 1: still observation-only — flag is stored but middleware does not block. Phase 3 flips behavior.)
                        </span>
                    </span>
                </label>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    id="test-btn"
                    class="rounded border border-gray-400 bg-white px-4 py-2 text-sm hover:bg-gray-100"
                >
                    Test before save
                </button>
                <button type="submit" class="primary-button">Save settings</button>
            </div>

            <pre id="test-result" class="hidden rounded border border-gray-300 bg-gray-50 p-3 text-xs"></pre>
        </form>
    </div>

    <script>
        document.getElementById('test-btn').addEventListener('click', async () => {
            const form = document.getElementById('admin-security-form');
            const fd = new FormData(form);
            const out = document.getElementById('test-result');
            out.classList.remove('hidden');
            out.textContent = 'Testing...';
            try {
                const resp = await fetch('{{ route('admin.security.settings.test') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: fd,
                });
                const data = await resp.json();
                out.textContent = JSON.stringify(data, null, 2);
            } catch (e) {
                out.textContent = 'Error: ' + e.message;
            }
        });
    </script>
</x-admin::layouts>

<x-admin::layouts>
    <x-slot:title>Facebook Catalog — Products</x-slot:title>

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <p class="text-xl text-gray-800 dark:text-white font-bold">
                Approve products for Facebook Catalog
            </p>
            <a href="{{ route('admin.facebook_catalog.index') }}"
               class="px-3 py-1 bg-gray-200 text-sm rounded">
                ← Back to settings
            </a>
        </div>

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

        @if (session('warning'))
            <div class="p-3 border border-yellow-500 rounded text-sm bg-yellow-50 dark:bg-yellow-900/20">
                {{ session('warning') }}
            </div>
        @endif

        <form method="GET" class="flex gap-3 items-end flex-wrap">
            <label class="flex flex-col gap-1 text-sm">
                <span>Channel</span>
                <select name="channel_id" onchange="this.form.submit()"
                        class="border rounded px-2 py-1 dark:bg-gray-800">
                    @foreach ($channels as $c)
                        <option value="{{ $c->id }}" {{ $channelId === $c->id ? 'selected' : '' }}>
                            {{ $c->code }} — {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="flex flex-col gap-1 text-sm">
                <span>Storefront status</span>
                <select name="status" onchange="this.form.submit()"
                        class="border rounded px-2 py-1 dark:bg-gray-800">
                    <option value="active"   {{ $status === 'active' ? 'selected' : '' }}>Active only</option>
                    <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Disabled / hidden</option>
                    <option value="any"      {{ $status === 'any' ? 'selected' : '' }}>Any status</option>
                </select>
            </label>

            <label class="flex flex-col gap-1 text-sm">
                <span>Filter</span>
                <select name="filter" onchange="this.form.submit()"
                        class="border rounded px-2 py-1 dark:bg-gray-800">
                    <option value="all"        {{ $filter === 'all' ? 'selected' : '' }}>All products</option>
                    <option value="approved"   {{ $filter === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="unapproved" {{ $filter === 'unapproved' ? 'selected' : '' }}>Not approved</option>
                    <option value="synced"     {{ $filter === 'synced' ? 'selected' : '' }}>Synced</option>
                    <option value="failed"     {{ $filter === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </label>

            <label class="flex flex-col gap-1 text-sm flex-1 min-w-[200px]">
                <span>Search SKU or name</span>
                <input name="q" value="{{ $search }}"
                       class="border rounded px-2 py-1 dark:bg-gray-800">
            </label>

            <button class="px-3 py-1 bg-blue-600 text-white text-sm rounded">Apply</button>
        </form>

        @if ($staleApproved > 0)
            <div class="p-3 border-l-4 border-red-500 rounded text-sm bg-red-50 dark:bg-red-900/20">
                <strong>{{ $staleApproved }}</strong> approved product(s) on this channel are disabled or hidden on
                the storefront. These are still eligible for sync and may already be live on Facebook linking to a
                dead product page.
                <a href="{{ request()->fullUrlWithQuery(['status' => 'inactive', 'filter' => 'approved', 'page' => null]) }}"
                   class="underline font-medium">Review them</a>
                and untick approval.
            </div>
        @endif

        @if (! $settings)
            <div class="p-3 border-l-4 border-yellow-500 rounded text-sm bg-yellow-50 dark:bg-yellow-900/20">
                This channel has no Facebook catalog settings yet. Save credentials on
                <a href="{{ route('admin.facebook_catalog.index') }}" class="underline">the settings page</a>
                first. You can still tick approval checkboxes — products won't sync until the channel is active.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.facebook_catalog.products.bulk') }}" id="bulk-form">
            @csrf
            <input type="hidden" name="channel_id" value="{{ $channelId }}">

            {{-- Carries the active filters so "select all matching" resolves the
                 same set server-side instead of trusting a client id list. --}}
            <input type="hidden" name="q"      value="{{ $search }}">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="hidden" name="select_all" id="select-all-flag" value="0">

            <div class="flex items-center gap-2 mb-2 text-sm flex-wrap">
                <span class="text-gray-600">With selected:</span>
                <button name="approve" value="1"
                        class="px-3 py-1 bg-green-600 text-white text-xs rounded">Approve</button>
                <button name="approve" value="0"
                        class="px-3 py-1 bg-gray-300 text-xs rounded">Unapprove</button>
                <span class="text-xs text-gray-500 ml-4" id="bulk-count">0 selected</span>
            </div>

            @if ($products->total() > $products->count())
                <div class="mb-2 p-2 rounded text-xs bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800"
                     id="select-all-bar" style="display:none">
                    <span id="select-all-prompt">
                        All <strong>{{ $products->count() }}</strong> on this page are selected.
                        <button type="button" id="select-all-matching" class="underline font-medium text-blue-700 dark:text-blue-300">
                            Select all {{ number_format($products->total()) }} products matching the current filter
                        </button>
                    </span>
                    <span id="select-all-active" style="display:none">
                        All <strong>{{ number_format($products->total()) }}</strong> products matching the current filter are selected.
                        <button type="button" id="select-all-clear" class="underline font-medium text-blue-700 dark:text-blue-300">
                            Clear selection
                        </button>
                    </span>
                </div>
            @endif

            <div class="overflow-x-auto border rounded">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-800">
                        <tr>
                            <th class="p-2 w-8">
                                <input type="checkbox" id="bulk-toggle-all">
                            </th>
                            <th class="p-2 text-left">SKU</th>
                            <th class="p-2 text-left">Name</th>
                            <th class="p-2 text-right">Price</th>
                            <th class="p-2 text-center w-24">Approved</th>
                            <th class="p-2 text-center">Sync status</th>
                            <th class="p-2 text-left">Last synced / error</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($products as $p)
                        @php
                            $statusBadge = match ($p->sync_status) {
                                'synced' => 'bg-green-100 text-green-800',
                                'failed' => 'bg-red-100 text-red-800',
                                'queued' => 'bg-blue-100 text-blue-800',
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                default => 'bg-gray-100 text-gray-700',
                            };
                            $effectivePrice = ($p->special_price && $p->special_price > 0 && $p->special_price < $p->price) ? $p->special_price : $p->price;
                        @endphp
                        <tr class="border-t">
                            <td class="p-2">
                                <input type="checkbox" name="product_ids[]" value="{{ $p->product_id }}"
                                       class="bulk-row-cb">
                            </td>
                            <td class="p-2 font-mono text-xs">{{ $p->sku }}</td>
                            <td class="p-2">
                                <div>{{ $p->name }}</div>
                                @if (! $p->status || ! $p->visible_individually)
                                    <span class="text-xs text-gray-500">(hidden in storefront)</span>
                                @endif
                            </td>
                            <td class="p-2 text-right">{{ number_format((float) $effectivePrice, 0) }}</td>
                            <td class="p-2 text-center">
                                <input type="checkbox"
                                       data-product-id="{{ $p->product_id }}"
                                       data-channel-id="{{ $channelId }}"
                                       class="approval-toggle"
                                       {{ $p->approved ? 'checked' : '' }}>
                            </td>
                            <td class="p-2 text-center">
                                <span class="px-2 py-0.5 rounded text-xs {{ $statusBadge }}">
                                    {{ $p->sync_status ?: '—' }}
                                </span>
                            </td>
                            <td class="p-2 text-xs">
                                @if ($p->last_synced_at)
                                    {{ \Carbon\Carbon::parse($p->last_synced_at)->diffForHumans() }}
                                @endif
                                @if ($p->sync_error)
                                    <div class="text-red-600 mt-1">{{ \Illuminate\Support\Str::limit($p->sync_error, 120) }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-4 text-center text-gray-500">No products match.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        <div>{{ $products->links() }}</div>
    </div>

    <script>
        (function () {
            const csrf = '{{ csrf_token() }}';
            const url  = '{{ route('admin.facebook_catalog.products.toggle') }}';

            document.querySelectorAll('.approval-toggle').forEach(cb => {
                cb.addEventListener('change', async (e) => {
                    const el = e.target;
                    el.disabled = true;
                    try {
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                product_id: el.dataset.productId,
                                channel_id: el.dataset.channelId,
                                approved: el.checked ? 1 : 0,
                            }),
                        });
                        const json = await res.json();
                        if (! res.ok || ! json.ok) {
                            el.checked = ! el.checked;
                            alert('Failed: ' + (json.error || res.status));
                        }
                    } catch (err) {
                        el.checked = ! el.checked;
                        alert('Network error: ' + err.message);
                    } finally {
                        el.disabled = false;
                    }
                });
            });

            const allCb   = document.getElementById('bulk-toggle-all');
            const rowCbs  = document.querySelectorAll('.bulk-row-cb');
            const counter = document.getElementById('bulk-count');

            const flag       = document.getElementById('select-all-flag');
            const bar        = document.getElementById('select-all-bar');
            const promptEl   = document.getElementById('select-all-prompt');
            const activeEl   = document.getElementById('select-all-active');
            const matchBtn   = document.getElementById('select-all-matching');
            const clearBtn   = document.getElementById('select-all-clear');
            const totalCount = {{ (int) $products->total() }};

            // Leaving select-all-matching mode on any narrower selection change
            // keeps the flag from silently applying to rows the operator just
            // deselected — the submitted set must match what the bar claims.
            const exitSelectAll = () => {
                if (flag) flag.value = '0';
                if (promptEl) promptEl.style.display = '';
                if (activeEl) activeEl.style.display = 'none';
            };

            const updateCount = () => {
                const n = document.querySelectorAll('.bulk-row-cb:checked').length;
                const selectAllOn = flag && flag.value === '1';

                if (counter) {
                    counter.textContent = selectAllOn
                        ? totalCount.toLocaleString() + ' selected (all matching)'
                        : n + ' selected';
                }

                if (bar) {
                    bar.style.display = (n > 0 && n === rowCbs.length) ? '' : 'none';
                }
            };

            if (allCb) {
                allCb.addEventListener('change', () => {
                    rowCbs.forEach(cb => { cb.checked = allCb.checked; });
                    exitSelectAll();
                    updateCount();
                });
            }

            rowCbs.forEach(cb => cb.addEventListener('change', () => {
                exitSelectAll();
                if (allCb) allCb.checked = document.querySelectorAll('.bulk-row-cb:checked').length === rowCbs.length;
                updateCount();
            }));

            if (matchBtn) {
                matchBtn.addEventListener('click', () => {
                    if (flag) flag.value = '1';
                    if (promptEl) promptEl.style.display = 'none';
                    if (activeEl) activeEl.style.display = '';
                    updateCount();
                });
            }

            if (clearBtn) {
                clearBtn.addEventListener('click', () => {
                    rowCbs.forEach(cb => { cb.checked = false; });
                    if (allCb) allCb.checked = false;
                    exitSelectAll();
                    updateCount();
                });
            }

            // Guard the destructive-ish path: unapproving hundreds of products
            // pulls them from the live Facebook catalog on the next sync.
            const bulkForm = document.getElementById('bulk-form');
            if (bulkForm) {
                bulkForm.addEventListener('submit', (e) => {
                    if (flag && flag.value === '1') {
                        const verb = (e.submitter && e.submitter.value === '0') ? 'Unapprove' : 'Approve';
                        if (! confirm(verb + ' all ' + totalCount.toLocaleString() + ' products matching the current filter?')) {
                            e.preventDefault();
                        }
                    }
                });
            }

            updateCount();
        })();
    </script>
</x-admin::layouts>

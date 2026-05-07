<x-admin::layouts>
    <x-slot:title>{{ $receipt->vendor_name ?: '#' . $receipt->id }}</x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            {{ __('accounting::app.receipts.title') }} #{{ $receipt->id }}
            @php
                $cls = match ($receipt->status) {
                    'uploaded'        => 'bg-gray-100 text-gray-700',
                    'ready_for_review'=> 'bg-amber-100 text-amber-700',
                    'confirmed'       => 'bg-emerald-100 text-emerald-700',
                    'rejected'        => 'bg-rose-100 text-rose-700',
                    default           => 'bg-gray-100 text-gray-700',
                };
            @endphp
            <span class="ml-2 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $cls }}">
                {{ __('accounting::app.receipts.status_' . str_replace('ready_for_review','ready',$receipt->status)) }}
            </span>
        </p>
        <a href="{{ route('admin.accounting.receipts.index') }}"
           class="rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50 dark:border-gray-700">
            ← {{ __('accounting::app.common.back') }}
        </a>
    </div>

    @if (session('success'))
        <div class="mt-3 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mt-3 rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="mt-3 rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
        </div>
    @endif

    <div class="mt-4 grid gap-4 md:grid-cols-2">

        {{-- LEFT: file preview --}}
        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            @if ($receipt->mime_type && str_starts_with($receipt->mime_type, 'image/'))
                <img src="{{ $fileUrl }}" alt="" class="w-full rounded border bg-gray-50 object-contain"/>
            @else
                <iframe src="{{ $fileUrl }}" class="w-full h-[600px] rounded border" title="receipt"></iframe>
            @endif
            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-500">
                <div><span class="text-gray-400">{{ __('accounting::app.receipts.sha256') }}:</span> <span class="font-mono break-all">{{ $receipt->file_sha256 ?: '—' }}</span></div>
                <div class="text-right">{{ $receipt->original_filename }}</div>
            </div>
        </div>

        {{-- RIGHT: editable form --}}
        <div class="box-shadow rounded bg-white p-5 dark:bg-gray-900">
            @if ($receipt->status !== 'confirmed')
                <form method="POST" action="{{ route('admin.accounting.receipts.update', $receipt->id) }}">
                    @csrf
                    @method('PUT')
                    @include('accounting::admin.receipts._fields', ['receipt' => $receipt, 'expenseAccounts' => $expenseAccounts])
                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                            {{ __('accounting::app.common.save') }}
                        </button>
                    </div>
                </form>
            @else
                @include('accounting::admin.receipts._fields', ['receipt' => $receipt, 'expenseAccounts' => $expenseAccounts, 'readonly' => true])

                @if ($receipt->journalEntry)
                    <div class="mt-4">
                        <a href="{{ route('admin.accounting.journal.show', $receipt->journalEntry->id) }}"
                           class="text-sm text-blue-600 hover:underline">
                            → {{ __('accounting::app.receipts.jump_to_journal') }} ({{ $receipt->journalEntry->entry_no }})
                        </a>
                    </div>
                @endif
            @endif

            {{-- Action buttons --}}
            <div class="mt-6 flex flex-wrap gap-2 border-t pt-4 dark:border-gray-800">
                @if ($receipt->status === 'uploaded')
                    <form method="POST" action="{{ route('admin.accounting.receipts.ocr', $receipt->id) }}">@csrf
                        <button class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-700">
                            {{ __('accounting::app.receipts.run_ocr') }}
                        </button>
                    </form>
                @endif

                @if (in_array($receipt->status, ['uploaded', 'ready_for_review'], true))
                    <form method="POST" action="{{ route('admin.accounting.receipts.confirm', $receipt->id) }}">@csrf
                        <button class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            {{ __('accounting::app.receipts.confirm') }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.accounting.receipts.reject', $receipt->id) }}">@csrf
                        <button class="rounded-md border border-rose-300 px-4 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50">
                            {{ __('accounting::app.receipts.reject') }}
                        </button>
                    </form>
                @endif

                @if (in_array($receipt->status, ['uploaded', 'rejected'], true))
                    <form method="POST" action="{{ route('admin.accounting.receipts.destroy', $receipt->id) }}"
                          onsubmit="return confirm('Delete this receipt permanently?');">@csrf @method('DELETE')
                        <button class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            {{ __('accounting::app.receipts.delete') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <script>
        (function () {
            const verifyBtn = document.getElementById('verify-t-number');
            if (! verifyBtn) return;
            verifyBtn.addEventListener('click', async () => {
                const t = document.getElementById('vendor_t_number').value.trim();
                const status = document.getElementById('t-number-status');
                if (! /^T\d{13}$/.test(t)) {
                    status.textContent = '{{ __('accounting::app.receipts.invalid_format') }}';
                    status.className = 'text-xs text-rose-600';
                    return;
                }
                status.textContent = '…';
                status.className = 'text-xs text-gray-500';
                try {
                    const r = await fetch(`{{ url('/admin/accounting/api/invoice-registrants') }}/${t}`, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    const body = await r.json();
                    if (body.valid) {
                        status.textContent = `✓ ${body.name}`;
                        status.className = 'text-xs text-emerald-600';
                    } else {
                        status.textContent = body.error || '{{ __('accounting::app.receipts.unverified') }}';
                        status.className = 'text-xs text-amber-600';
                    }
                } catch (e) {
                    status.textContent = e.message;
                    status.className = 'text-xs text-rose-600';
                }
            });
        })();
    </script>
</x-admin::layouts>

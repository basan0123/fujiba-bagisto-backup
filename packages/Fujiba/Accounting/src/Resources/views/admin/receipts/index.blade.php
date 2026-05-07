<x-admin::layouts>
    <x-slot:title>{{ __('accounting::app.receipts.title') }}</x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            {{ __('accounting::app.receipts.title') }}
        </p>
        <a href="{{ route('admin.accounting.receipts.create') }}"
           class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            + {{ __('accounting::app.receipts.upload') }}
        </a>
    </div>

    @if (session('success'))
        <div class="mt-3 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.accounting.receipts.index') }}"
          class="mt-3.5 flex flex-wrap items-end gap-3 box-shadow rounded bg-white p-4 dark:bg-gray-900">
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.receipts.date') }}</label>
            <input type="date" name="from" value="{{ $filters['from'] }}" class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">→</label>
            <input type="date" name="to" value="{{ $filters['to'] }}" class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.receipts.vendor') }}</label>
            <input type="text" name="vendor" value="{{ $filters['vendor'] }}" placeholder="..."
                   class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('accounting::app.journal.status') }}</label>
            <select name="status" class="h-9 rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <option value="">— {{ __('accounting::app.common.all') }} —</option>
                @foreach (['uploaded','ready_for_review','confirmed','rejected'] as $st)
                    <option value="{{ $st }}" @selected($filters['status'] === $st)>{{ __('accounting::app.receipts.status_' . str_replace('ready_for_review','ready',$st)) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="h-9 rounded-md bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">
            {{ __('accounting::app.common.search') }}
        </button>
    </form>

    {{-- Table --}}
    <div class="mt-4 box-shadow rounded bg-white dark:bg-gray-900 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-950">
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <th class="px-4 py-3 w-16">{{ __('accounting::app.receipts.thumbnail') }}</th>
                    <th class="px-4 py-3">{{ __('accounting::app.receipts.vendor') }}</th>
                    <th class="px-4 py-3">{{ __('accounting::app.receipts.date') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('accounting::app.receipts.total') }}</th>
                    <th class="px-4 py-3">{{ __('accounting::app.journal.status') }}</th>
                    <th class="px-4 py-3">{{ __('accounting::app.receipts.t_number') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($receipts as $r)
                    @php
                        $cls = match ($r->status) {
                            'uploaded'        => 'bg-gray-100 text-gray-700',
                            'ready_for_review'=> 'bg-amber-100 text-amber-700',
                            'confirmed'       => 'bg-emerald-100 text-emerald-700',
                            'rejected'        => 'bg-rose-100 text-rose-700',
                            default           => 'bg-gray-100 text-gray-700',
                        };
                        $isImage = $r->mime_type && str_starts_with($r->mime_type, 'image/');
                    @endphp
                    <tr class="border-b last:border-b-0 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-950">
                        <td class="px-4 py-2">
                            <a href="{{ route('admin.accounting.receipts.show', $r->id) }}">
                                @if ($isImage)
                                    <img src="{{ route('admin.accounting.receipts.file', $r->id) }}"
                                         alt="" class="h-12 w-12 object-cover rounded border"/>
                                @else
                                    <div class="h-12 w-12 rounded border bg-gray-50 flex items-center justify-center text-xs text-gray-500">PDF</div>
                                @endif
                            </a>
                        </td>
                        <td class="px-4 py-2">
                            <a href="{{ route('admin.accounting.receipts.show', $r->id) }}"
                               class="text-blue-600 hover:underline">
                                {{ $r->vendor_name ?: '—' }}
                            </a>
                        </td>
                        <td class="px-4 py-2">{{ $r->receipt_date?->toDateString() ?? '—' }}</td>
                        <td class="px-4 py-2 text-right font-mono">¥{{ number_format((int) $r->total_amount) }}</td>
                        <td class="px-4 py-2">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $cls }}">
                                {{ __('accounting::app.receipts.status_' . str_replace('ready_for_review','ready',$r->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-2 font-mono text-xs">{{ $r->vendor_t_number ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                            {{ __('accounting::app.receipts.no_results') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($receipts->hasPages())
            <div class="border-t px-4 py-3 dark:border-gray-800">
                {{ $receipts->links() }}
            </div>
        @endif
    </div>
</x-admin::layouts>

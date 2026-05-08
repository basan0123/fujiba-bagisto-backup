<x-admin::layouts>
    <x-slot:title>{{ __('accounting::app.staff.print_qr') }} — {{ $staff->name_ja }}</x-slot>

    <div class="flex items-center justify-between gap-4 print:hidden">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            {{ __('accounting::app.staff.print_qr') }} — {{ $staff->name_ja }}
        </p>
        <div class="flex gap-2">
            <a href="{{ route('admin.accounting.staff.edit', $staff->id) }}"
               class="rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50 dark:border-gray-700">
                ← {{ __('accounting::app.common.back') }}
            </a>
            <button onclick="window.print()" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                印刷
            </button>
        </div>
    </div>

    <div class="mx-auto mt-6 max-w-md rounded-xl border-2 border-gray-300 bg-white p-8 text-center print:border-0 print:shadow-none">
        <p class="text-2xl font-bold text-gray-800">{{ $staff->name_ja }}</p>
        @if ($staff->name_kana)
            <p class="text-sm text-gray-500">{{ $staff->name_kana }}</p>
        @endif
        <p class="mt-1 font-mono text-xs text-gray-500">{{ $staff->staff_code }}</p>

        <div class="mt-6">
            {!! $svg !!}
        </div>

        <div class="mt-4 text-xs text-gray-500">
            打刻 URL: <span class="font-mono">{{ $kioskUrl }}</span>
        </div>
        <div class="mt-1 break-all text-[10px] font-mono text-gray-400">
            token: {{ $staff->qr_token }}
        </div>
    </div>
</x-admin::layouts>

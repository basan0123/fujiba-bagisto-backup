<x-admin::layouts>
    <x-slot:title>{{ __('accounting::app.receipts.upload') }}</x-slot>

    <div class="flex items-center justify-between gap-4">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            {{ __('accounting::app.receipts.upload') }}
        </p>
        <a href="{{ route('admin.accounting.receipts.index') }}"
           class="rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50 dark:border-gray-700">
            ← {{ __('accounting::app.common.back') }}
        </a>
    </div>

    @if ($errors->any())
        <div class="mt-3 rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.accounting.receipts.store') }}"
          enctype="multipart/form-data"
          class="mt-4 grid gap-4 md:grid-cols-2">
        @csrf

        {{-- Drop zone --}}
        <div class="md:col-span-2 box-shadow rounded bg-white p-6 dark:bg-gray-900">
            <label for="receipt-file" class="block">
                <div class="rounded-lg border-2 border-dashed border-gray-300 p-10 text-center hover:border-blue-500 dark:border-gray-700 cursor-pointer">
                    <svg class="mx-auto h-12 w-12 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M3 16V4a1 1 0 011-1h12l5 5v8a1 1 0 01-1 1h-2M9 21l3-3 3 3M12 18v-7"/>
                    </svg>
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                        {{ __('accounting::app.receipts.drop_here') }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">
                        jpg / jpeg / png / pdf · max {{ (int) (config('accounting.receipts.max_size_kb', 10240) / 1024) }} MB
                    </p>
                    <span class="mt-3 inline-block rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white">
                        {{ __('accounting::app.receipts.choose_file') }}
                    </span>
                </div>
            </label>
            <input type="file" id="receipt-file" name="file" required class="hidden"
                   accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"/>
            <div id="receipt-file-name" class="mt-2 text-xs text-gray-500"></div>
        </div>

        {{-- Optional metadata --}}
        <div class="box-shadow rounded bg-white p-5 dark:bg-gray-900">
            <p class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">
                任意項目 (OCR後に自動入力されます)
            </p>
            <div class="grid gap-3">
                <div>
                    <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.receipts.vendor') }}</label>
                    <input type="text" name="vendor_name" maxlength="128"
                           class="mt-1 h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.receipts.date') }}</label>
                    <input type="date" name="receipt_date"
                           class="mt-1 h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.receipts.total') }}</label>
                    <input type="number" name="total_amount" min="0" step="1"
                           class="mt-1 h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
                </div>
            </div>
        </div>

        <div class="box-shadow rounded bg-white p-5 dark:bg-gray-900">
            <div class="grid gap-3">
                <div>
                    <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.journal.channel') }}</label>
                    <select name="channel_id" class="mt-1 h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">— 法人 (corporate) —</option>
                        @foreach (config('accounting.channels', []) as $cid => $code)
                            <option value="{{ $cid }}">{{ $cid }} — {{ $code }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.receipts.tax_category') }}</label>
                    <select name="tax_category" class="mt-1 h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                        <option value="standard">標準 10%</option>
                        <option value="reduced">軽減 8%</option>
                        <option value="non_taxable">非課税</option>
                        <option value="exempt">免税</option>
                        <option value="out_of_scope">不課税</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.receipts.payment_method') }}</label>
                    <select name="payment_method" class="mt-1 h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">—</option>
                        <option value="cash">現金 (cash)</option>
                        <option value="credit">クレジット</option>
                        <option value="bank_transfer">銀行振込</option>
                        <option value="other">その他</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.receipts.notes') }}</label>
                    <textarea name="notes" rows="2" class="mt-1 w-full rounded-md border px-3 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"></textarea>
                </div>
            </div>
        </div>

        <div class="md:col-span-2 flex justify-end gap-2">
            <a href="{{ route('admin.accounting.receipts.index') }}"
               class="rounded-md border px-4 py-2 text-sm hover:bg-gray-50 dark:border-gray-700">
                {{ __('accounting::app.common.cancel') }}
            </a>
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                {{ __('accounting::app.receipts.upload') }}
            </button>
        </div>
    </form>

    <script>
        (function () {
            const input = document.getElementById('receipt-file');
            const out   = document.getElementById('receipt-file-name');
            if (input && out) {
                input.addEventListener('change', () => {
                    out.textContent = input.files?.[0]?.name ?? '';
                });
            }
        })();
    </script>
</x-admin::layouts>

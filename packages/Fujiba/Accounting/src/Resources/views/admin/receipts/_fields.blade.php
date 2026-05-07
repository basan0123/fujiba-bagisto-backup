@php $ro = $readonly ?? false; @endphp
<div class="grid gap-3">
    <div>
        <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.receipts.vendor') }}</label>
        <input type="text" name="vendor_name" value="{{ old('vendor_name', $receipt->vendor_name) }}" maxlength="128"
               @disabled($ro)
               class="mt-1 h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
    </div>

    <div>
        <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.receipts.t_number') }}</label>
        <div class="mt-1 flex gap-2">
            <input type="text" id="vendor_t_number" name="vendor_t_number" value="{{ old('vendor_t_number', $receipt->vendor_t_number) }}"
                   pattern="^T\d{13}$" placeholder="T1234567890123"
                   @disabled($ro)
                   class="h-9 flex-1 rounded-md border px-3 font-mono text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
            @if (! $ro)
                <button type="button" id="verify-t-number"
                        class="h-9 rounded-md border px-3 text-xs font-medium hover:bg-gray-50 dark:border-gray-700">
                    {{ __('accounting::app.receipts.verify_t_number') }}
                </button>
            @endif
        </div>
        <div id="t-number-status" class="mt-1 text-xs text-gray-500"></div>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.receipts.date') }}</label>
            <input type="date" name="receipt_date" value="{{ optional($receipt->receipt_date)->toDateString() }}"
                   @disabled($ro)
                   class="mt-1 h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.receipts.total') }}</label>
            <input type="number" name="total_amount" min="0" step="1" value="{{ old('total_amount', (int) $receipt->total_amount) }}"
                   @disabled($ro)
                   class="mt-1 h-9 w-full rounded-md border px-3 text-right font-mono text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.receipts.tax') }}</label>
            <input type="number" name="tax_amount" min="0" step="1" value="{{ old('tax_amount', (int) $receipt->tax_amount) }}"
                   @disabled($ro)
                   class="mt-1 h-9 w-full rounded-md border px-3 text-right font-mono text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"/>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.receipts.tax_category') }}</label>
            <select name="tax_category" @disabled($ro)
                    class="mt-1 h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                @foreach (['standard' => '標準 10%','reduced' => '軽減 8%','non_taxable' => '非課税','exempt' => '免税','out_of_scope' => '不課税'] as $val => $label)
                    <option value="{{ $val }}" @selected($receipt->tax_category === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.receipts.payment_method') }}</label>
            <select name="payment_method" @disabled($ro)
                    class="mt-1 h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <option value="">—</option>
                @foreach (['cash' => '現金','credit' => 'クレジット','bank_transfer' => '銀行振込','other' => 'その他'] as $val => $label)
                    <option value="{{ $val }}" @selected($receipt->payment_method === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.receipts.account') }}</label>
            <select name="account_id" @disabled($ro)
                    class="mt-1 h-9 w-full rounded-md border px-3 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <option value="">— 選択 —</option>
                @foreach ($expenseAccounts as $a)
                    <option value="{{ $a->id }}" @selected($receipt->account_id === $a->id)>{{ $a->code }} — {{ $a->name_ja }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label class="text-xs font-medium text-gray-600">{{ __('accounting::app.receipts.notes') }}</label>
        <textarea name="notes" rows="2" @disabled($ro)
                  class="mt-1 w-full rounded-md border px-3 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">{{ old('notes', $receipt->notes) }}</textarea>
    </div>
</div>

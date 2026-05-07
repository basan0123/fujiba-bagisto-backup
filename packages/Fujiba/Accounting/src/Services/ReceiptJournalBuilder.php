<?php

namespace Fujiba\Accounting\Services;

use Fujiba\Accounting\Models\Account;
use Fujiba\Accounting\Models\Receipt;
use Fujiba\Accounting\Services\Tax\ConsumptionTaxCalculator;

/**
 * Translate a Receipt row into a JournalPoster payload.
 *
 *   DR  expense account (operator-selected, default 6390 雑費)  ¥net
 *   DR  仮払消費税 (1220)                                       ¥tax     (only if tax > 0)
 *       CR  現金 (1110) when payment_method ∈ {cash, 現金}
 *       CR  未払金 (2130) otherwise
 */
class ReceiptJournalBuilder
{
    public function __construct(
        protected ConsumptionTaxCalculator $taxCalc,
    ) {}

    public function build(Receipt $receipt): array
    {
        $total = (int) round((float) $receipt->total_amount);
        if ($total <= 0) {
            throw new \InvalidArgumentException('Receipt total_amount must be > 0 to build a journal entry.');
        }

        // Resolve debit account — operator pick wins; otherwise sub_type fallback.
        $debitCode = $this->resolveDebitAccountCode($receipt);

        // Resolve tax: prefer the explicit tax_amount on the receipt, otherwise
        // compute via the calculator from the tax_category.
        $tax = (int) round((float) $receipt->tax_amount);
        if ($tax === 0 && in_array($receipt->tax_category, ['standard', 'reduced'], true)) {
            $rate = $receipt->tax_category === 'reduced' ? 8.0 : 10.0;
            $calc = $this->taxCalc->calculate($total, $rate);
            $tax = $calc['tax'];
        }
        $net = $total - $tax;

        $isCash = $this->isCash($receipt->payment_method);
        $payableCode = $isCash ? '1110' : '2130';   // 現金 vs 未払金

        $lines = [];
        $lines[] = [
            'account_code' => $debitCode,
            'debit'        => $net,
            'credit'       => 0,
            'memo'         => $receipt->vendor_name ?: '領収書',
            'partner_name' => $receipt->vendor_name,
        ];

        if ($tax > 0) {
            $lines[] = [
                'account_code' => '1220',   // 仮払消費税
                'debit'        => $tax,
                'credit'       => 0,
                'tax_rate'     => $receipt->tax_category === 'reduced' ? '8' : '10',
                'tax_amount'   => $tax,
                'memo'         => '仮払消費税',
            ];
        }

        $lines[] = [
            'account_code' => $payableCode,
            'debit'        => 0,
            'credit'       => $total,
            'memo'         => $isCash ? '現金支出' : '未払金',
            'partner_name' => $receipt->vendor_name,
        ];

        return [
            'entry_date'      => $receipt->receipt_date ?: now(),
            'channel_id'      => $receipt->channel_id,
            'memo'            => $receipt->vendor_name ? "領収書 {$receipt->vendor_name}" : '領収書',
            'reference_no'    => $receipt->vendor_t_number,
            'source_type'     => 'receipt',
            'source_id'       => (int) $receipt->id,
            'idempotency_key' => "receipt-{$receipt->id}",
            'lines'           => $lines,
        ];
    }

    protected function resolveDebitAccountCode(Receipt $receipt): string
    {
        if ($receipt->account_id) {
            $account = Account::find($receipt->account_id);
            if ($account) {
                return $account->code;
            }
        }
        return '6390'; // 雑費 — operator should refine on confirm screen
    }

    protected function isCash(?string $method): bool
    {
        if (! $method) {
            return false;
        }
        $m = strtolower($method);
        return $m === 'cash'
            || $m === '現金'
            || $m === 'cod'
            || $m === 'cashondelivery'
            || $m === 'pos_cash';
    }
}

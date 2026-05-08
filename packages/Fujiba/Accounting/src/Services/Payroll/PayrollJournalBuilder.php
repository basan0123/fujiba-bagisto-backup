<?php

namespace Fujiba\Accounting\Services\Payroll;

use Fujiba\Accounting\Models\PayrollSlip;

/**
 * Translate a finalized PayrollSlip into a JournalPoster payload.
 *
 *   DR  6120 給料手当                ¥gross
 *       CR  2210 預り金(社保)        ¥(health + pension + employment_insurance)
 *       CR  2220 預り金(源泉)        ¥income_tax
 *       CR  2230 預り金(住民税)      ¥resident_tax
 *       CR  2240 預り金(その他)      ¥other_deduction
 *       CR  1130 普通預金            ¥net_pay
 *
 * (For 役員 employment_type, this could swap to 6110 役員報酬 — that's an
 *  optional refinement; for now everyone posts to 6120. Operators can
 *  manually edit the resulting entry before close-of-month.)
 */
class PayrollJournalBuilder
{
    public function build(PayrollSlip $slip): array
    {
        $gross = (int) round((float) $slip->gross_pay);
        if ($gross <= 0) {
            throw new \InvalidArgumentException('PayrollSlip gross_pay must be > 0 to build a journal entry.');
        }

        $hi  = (int) round((float) $slip->health_insurance);
        $pen = (int) round((float) $slip->pension);
        $ei  = (int) round((float) $slip->employment_insurance);
        $shaho = $hi + $pen + $ei;

        $incomeTax     = (int) round((float) $slip->income_tax);
        $residentTax   = (int) round((float) $slip->resident_tax);
        $otherDeduction= (int) round((float) $slip->other_deduction);
        $net = (int) round((float) $slip->net_pay);

        $lines = [
            ['account_code' => '6120', 'debit' => $gross, 'credit' => 0, 'memo' => "給料 {$slip->year_month}"],
        ];

        if ($shaho > 0) {
            $lines[] = ['account_code' => '2210', 'debit' => 0, 'credit' => $shaho, 'memo' => '預り金(社保)'];
        }
        if ($incomeTax > 0) {
            $lines[] = ['account_code' => '2220', 'debit' => 0, 'credit' => $incomeTax, 'memo' => '預り金(源泉)'];
        }
        if ($residentTax > 0) {
            $lines[] = ['account_code' => '2230', 'debit' => 0, 'credit' => $residentTax, 'memo' => '預り金(住民税)'];
        }
        if ($otherDeduction > 0) {
            $lines[] = ['account_code' => '2240', 'debit' => 0, 'credit' => $otherDeduction, 'memo' => '預り金(その他)'];
        }
        if ($net > 0) {
            $lines[] = ['account_code' => '1130', 'debit' => 0, 'credit' => $net, 'memo' => '普通預金 振込'];
        }

        return [
            'entry_date'      => $slip->pay_date ?: now(),
            'channel_id'      => $slip->channel_id,
            'memo'            => "給与 {$slip->year_month} #{$slip->staff_id}",
            'reference_no'    => "PAY-{$slip->year_month}-{$slip->staff_id}",
            'source_type'     => 'payroll',
            'source_id'       => (int) $slip->id,
            'idempotency_key' => "payroll-{$slip->id}",
            'lines'           => $lines,
        ];
    }
}

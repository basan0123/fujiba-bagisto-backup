<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\JournalEntry;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class PostBagistoRefundsCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected function seedOrderAndRefund(array $overrides = []): array
    {
        $now = now();
        $orderId = DB::table('orders')->insertGetId([
            'increment_id'         => 'REF-O-' . uniqid(),
            'status'               => 'completed',
            'channel_id'           => 1,
            'grand_total'          => $overrides['order_total'] ?? 1100,
            'sub_total'            => $overrides['order_total'] ?? 1100,
            'tax_amount'           => 0,
            'grand_total_invoiced' => $overrides['order_total'] ?? 1100,
            'payment_confirmed_at' => $now,
            'created_at'           => $now,
            'updated_at'           => $now,
            'is_gift'              => 0,
            'sub_total_incl_tax'   => 0,
            'base_sub_total_incl_tax' => 0,
            'shipping_amount_incl_tax' => 0,
            'base_shipping_amount_incl_tax' => 0,
            'shipping_amount'      => 0,
            'shipping_tax_amount'  => 0,
            'base_shipping_tax_amount' => 0,
            'shipping_tax_refunded' => 0,
            'base_shipping_tax_refunded' => 0,
            'cod_fee'              => 0,
            'base_cod_fee'         => 0,
            'cool_surcharge'       => 0,
            'base_cool_surcharge'  => 0,
            'fulfillment_type'     => 'delivery',
            'fujiba_label_printed' => 0,
        ]);

        DB::table('order_payment')->insert([
            'order_id' => $orderId,
            'method'   => 'cashondelivery',
            'method_title' => 'COD',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Pre-post the order entry so the refund has an anchor.
        $this->artisan('accounting:post-orders', ['--since' => '2020-01-01'])->run();

        $refundId = DB::table('refunds')->insertGetId([
            'increment_id'         => 'REF-' . uniqid(),
            'state'                => 'refunded',
            'order_id'             => $orderId,
            'grand_total'          => $overrides['refund_total'] ?? 550,
            'sub_total'            => $overrides['refund_total'] ?? 550,
            'tax_amount'           => 0,
            'created_at'           => $now,
            'updated_at'           => $now,
            'sub_total_incl_tax'   => 0,
            'base_sub_total_incl_tax' => 0,
            'shipping_amount_incl_tax' => 0,
            'base_shipping_amount_incl_tax' => 0,
            'base_shipping_tax_amount' => 0,
            'shipping_tax_amount'  => 0,
        ]);

        return [$orderId, $refundId];
    }

    public function test_refund_command_posts_reversing_entry(): void
    {
        [$orderId, $refundId] = $this->seedOrderAndRefund(['order_total' => 1100, 'refund_total' => 1100]);

        $this->artisan('accounting:post-refunds', ['--since' => '2020-01-01'])->run();

        $entry = JournalEntry::where('source_type', 'refund')
            ->where('source_id', $refundId)
            ->first();
        $this->assertNotNull($entry, 'Refund entry not posted');

        $debit = (int) $entry->lines->sum('debit');
        $credit = (int) $entry->lines->sum('credit');
        $this->assertSame($debit, $credit, 'Refund entry must be balanced');
        $this->assertSame(1100, $debit);

        // Receivable (1110 cash for COD) on the credit side
        $cashLine = $entry->lines->firstWhere('credit', '>', 0);
        $this->assertSame('1110', $cashLine->account?->code);
    }

    public function test_refund_without_matching_order_entry_is_skipped(): void
    {
        $now = now();
        // No prior order-posting; just an orphaned refund.
        $orphanOrderId = DB::table('orders')->insertGetId([
            'increment_id'         => 'ORPHAN-' . uniqid(),
            'status'               => 'completed',
            'channel_id'           => 1,
            'grand_total'          => 100,
            'sub_total'            => 100,
            'tax_amount'           => 0,
            'grand_total_invoiced' => 0,
            'payment_confirmed_at' => null,
            'created_at'           => $now,
            'updated_at'           => $now,
            'is_gift'              => 0,
            'sub_total_incl_tax'   => 0,
            'base_sub_total_incl_tax' => 0,
            'shipping_amount_incl_tax' => 0,
            'base_shipping_amount_incl_tax' => 0,
            'shipping_amount'      => 0,
            'shipping_tax_amount'  => 0,
            'base_shipping_tax_amount' => 0,
            'shipping_tax_refunded' => 0,
            'base_shipping_tax_refunded' => 0,
            'cod_fee'              => 0,
            'base_cod_fee'         => 0,
            'cool_surcharge'       => 0,
            'base_cool_surcharge'  => 0,
            'fulfillment_type'     => 'delivery',
            'fujiba_label_printed' => 0,
        ]);

        $refundId = DB::table('refunds')->insertGetId([
            'increment_id' => 'ORPH-REF-' . uniqid(),
            'state'        => 'refunded',
            'order_id'     => $orphanOrderId,
            'grand_total'  => 100,
            'sub_total'    => 100,
            'tax_amount'   => 0,
            'created_at'   => $now,
            'updated_at'   => $now,
            'sub_total_incl_tax' => 0,
            'base_sub_total_incl_tax' => 0,
            'shipping_amount_incl_tax' => 0,
            'base_shipping_amount_incl_tax' => 0,
            'base_shipping_tax_amount' => 0,
            'shipping_tax_amount' => 0,
        ]);

        $this->artisan('accounting:post-refunds', ['--since' => '2020-01-01'])->run();

        $count = JournalEntry::where('source_type', 'refund')
            ->where('source_id', $refundId)
            ->count();
        $this->assertSame(0, $count, 'Orphan refund without anchor entry must be skipped, not errored');
    }

    public function test_dry_run_creates_no_entries(): void
    {
        [, $refundId] = $this->seedOrderAndRefund();

        $this->artisan('accounting:post-refunds', [
            '--since' => '2020-01-01',
            '--dry-run' => true,
        ])->run();

        $count = JournalEntry::where('source_type', 'refund')->where('source_id', $refundId)->count();
        $this->assertSame(0, $count);
    }
}

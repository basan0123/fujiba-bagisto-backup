<?php

namespace Fujiba\Accounting\Tests\Feature;

use Fujiba\Accounting\Models\JournalEntry;
use Fujiba\Accounting\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class PostBagistoOrdersCommandTest extends TestCase
{
    use DatabaseTransactions;

    /** Seed a fake Bagisto order in the orders / order_payment / order_items tables. */
    protected function seedFakeOrder(array $overrides = []): int
    {
        $now = now();
        $defaults = [
            'increment_id'         => 'TST-' . uniqid(),
            'status'               => 'completed',
            'channel_id'           => 1,
            'grand_total'          => 1000,
            'sub_total'            => 1000,
            'tax_amount'           => 0,
            'shipping_amount'      => 0,
            'shipping_tax_amount'  => 0,
            'cod_fee'              => 0,
            'grand_total_invoiced' => 1000,
            'payment_confirmed_at' => $now,
            'created_at'           => $now,
            'updated_at'           => $now,
            'is_gift'              => 0,
            'sub_total_incl_tax'   => 1000,
            'base_sub_total_incl_tax' => 1000,
            'shipping_amount_incl_tax' => 0,
            'base_shipping_amount_incl_tax' => 0,
            'base_cod_fee'         => 0,
            'cool_surcharge'       => 0,
            'base_cool_surcharge'  => 0,
            'base_shipping_tax_amount' => 0,
            'base_shipping_tax_refunded' => 0,
            'shipping_tax_refunded' => 0,
            'fulfillment_type'     => 'delivery',
            'fujiba_label_printed' => 0,
        ];

        $orderId = DB::table('orders')->insertGetId(array_merge($defaults, $overrides));

        DB::table('order_payment')->insert([
            'order_id' => $orderId,
            'method'   => 'cashondelivery',
            'method_title' => 'COD',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $orderId;
    }

    public function test_command_posts_a_balanced_entry_for_an_order(): void
    {
        $orderId = $this->seedFakeOrder(['grand_total' => 1100, 'tax_amount' => 100]);

        $this->artisan('accounting:post-orders', ['--since' => '2020-01-01'])
            ->assertExitCode(0);

        $entry = JournalEntry::where('source_type', 'order')
            ->where('source_id', $orderId)
            ->first();
        $this->assertNotNull($entry, 'Journal entry was not created for the seeded order');

        $debit = (int) $entry->lines->sum('debit');
        $credit = (int) $entry->lines->sum('credit');
        $this->assertSame(1100, $debit);
        $this->assertSame($debit, $credit, 'Posted entry must be balanced');

        // First line is the receivable (cash for COD)
        $first = $entry->lines->firstWhere('debit', '>', 0);
        $this->assertSame('1110', $first->account?->code);
    }

    public function test_double_run_does_not_duplicate_entries(): void
    {
        $orderId = $this->seedFakeOrder(['grand_total' => 550]);

        $this->artisan('accounting:post-orders', ['--since' => '2020-01-01'])->run();
        $this->artisan('accounting:post-orders', ['--since' => '2020-01-01'])->run();

        $count = JournalEntry::where('source_type', 'order')
            ->where('source_id', $orderId)
            ->count();
        $this->assertSame(1, $count, 'Re-running must be idempotent');
    }

    public function test_dry_run_creates_no_entries(): void
    {
        $orderId = $this->seedFakeOrder(['grand_total' => 770]);

        $this->artisan('accounting:post-orders', [
            '--since'   => '2020-01-01',
            '--dry-run' => true,
        ])->run();

        $count = JournalEntry::where('source_type', 'order')
            ->where('source_id', $orderId)
            ->count();
        $this->assertSame(0, $count, '--dry-run must not write entries');
    }

    public function test_channel_filter_restricts_scope(): void
    {
        // Ensure channel 2 exists in the central DB FK target. (Tenant DBs have it
        // already; central is only seeded with `default`=1 in this install.)
        $hasChannel2 = DB::table('channels')->where('id', 2)->exists();
        if (! $hasChannel2) {
            // Mirror channel 1's locale/currency so FKs are satisfied.
            $tpl = DB::table('channels')->where('id', 1)->first();
            DB::table('channels')->insert([
                'id'                 => 2,
                'code'               => 'mmfood-test-' . substr(uniqid(), -6),
                'theme'              => $tpl->theme ?? 'default',
                'logo'               => null,
                'favicon'            => null,
                'hostname'           => 'mmfood.test.local',
                'is_maintenance_on'  => 0,
                'default_locale_id'  => $tpl->default_locale_id,
                'base_currency_id'   => $tpl->base_currency_id,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }

        try {
            $kimonoOrder = $this->seedFakeOrder(['channel_id' => 1, 'grand_total' => 100]);
            $mmfoodOrder = $this->seedFakeOrder(['channel_id' => 2, 'grand_total' => 200]);

            $this->artisan('accounting:post-orders', [
                '--channel' => '1',
                '--since'   => '2020-01-01',
            ])->run();

            $this->assertNotNull(JournalEntry::where('source_id', $kimonoOrder)->where('source_type', 'order')->first());
            $this->assertNull(JournalEntry::where('source_id', $mmfoodOrder)->where('source_type', 'order')->first(),
                'channel=1 filter must skip channel 2 orders');
        } finally {
            if (! $hasChannel2) {
                DB::table('orders')->where('channel_id', 2)->delete();
                DB::table('channels')->where('id', 2)->delete();
            }
        }
    }
}

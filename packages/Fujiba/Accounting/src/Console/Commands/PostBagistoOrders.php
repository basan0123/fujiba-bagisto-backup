<?php

namespace Fujiba\Accounting\Console\Commands;

use Fujiba\Accounting\Exceptions\ClosedPeriodException;
use Fujiba\Accounting\Models\AuditLog;
use Fujiba\Accounting\Models\JournalEntry;
use Fujiba\Accounting\Services\JournalPoster;
use Fujiba\Accounting\Services\SettingService;
use Fujiba\Accounting\Services\Tax\ConsumptionTaxCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PostBagistoOrders extends Command
{
    protected $signature = 'accounting:post-orders
                            {--channel= : Limit to a single Bagisto channel_id}
                            {--since= : ISO timestamp to scan from (default: last successful run)}
                            {--dry-run : Print what would be posted without writing}';

    protected $description = 'Post completed/paid Bagisto orders into the accounting journal.';

    public function handle(
        JournalPoster $poster,
        ConsumptionTaxCalculator $taxCalc,
        SettingService $settings,
    ): int {
        if (config('app.env') === 'production' && ! $this->option('dry-run') && ! $this->confirm('Production env detected — continue posting orders?')) {
            $this->warn('Aborted by operator.');
            return self::FAILURE;
        }

        $since = $this->option('since')
            ? Carbon::parse($this->option('since'))
            : ($settings->get('last_order_post_run_at')
                ? Carbon::parse($settings->get('last_order_post_run_at'))
                : Carbon::now()->subDays(30));   // first run: last 30 days

        $this->line("Scanning orders updated_at >= {$since->toDateTimeString()}");

        $query = DB::table('orders')
            ->select([
                'orders.id', 'orders.increment_id', 'orders.channel_id', 'orders.status',
                'orders.grand_total', 'orders.sub_total', 'orders.tax_amount',
                'orders.shipping_amount', 'orders.shipping_tax_amount', 'orders.cod_fee',
                'orders.payment_confirmed_at', 'orders.created_at', 'orders.updated_at',
            ])
            ->where('orders.status', 'completed')
            ->where(function ($q) {
                // Bagisto's "paid" signal: payment_confirmed_at is set
                // OR grand_total has been fully invoiced.
                $q->whereNotNull('orders.payment_confirmed_at')
                  ->orWhereColumn('orders.grand_total_invoiced', '>=', 'orders.grand_total');
            })
            ->where('orders.updated_at', '>=', $since)
            ->orderBy('orders.id');

        if ($this->option('channel')) {
            $query->where('orders.channel_id', (int) $this->option('channel'));
        }

        $orders = $query->get();
        $this->line("Found {$orders->count()} candidate order(s).");

        $posted = 0;
        $skipped = 0;
        $errors = [];
        $samples = [];   // dry-run: capture line previews

        foreach ($orders as $order) {
            try {
                // ── Idempotency double-check (fast SELECT before transaction) ──
                $existing = JournalEntry::where('source_type', 'order')
                    ->where('source_id', $order->id)
                    ->first();
                if ($existing) {
                    $skipped++;
                    continue;
                }

                if ($order->channel_id === null) {
                    Log::warning('Fujiba\\Accounting: order has NULL channel_id — corporate-level posting not supported, skipping', [
                        'order_id' => $order->id,
                    ]);
                    $skipped++;
                    continue;
                }

                $payload = $this->buildPayload($order, $taxCalc);

                if ($this->option('dry-run')) {
                    $samples[] = $this->summarizePayload($order, $payload);
                    $posted++;
                    continue;
                }

                $entry = $poster->post($payload);

                AuditLog::create([
                    'table_name'  => 'fujiba_journal_entries',
                    'record_id'   => $entry->id,
                    'action'      => 'created',
                    'before_json' => null,
                    'after_json'  => ['source' => 'cron:post-orders', 'order_id' => $order->id, 'entry_no' => $entry->entry_no],
                    'user_id'     => null,
                    'user_name'   => 'cron:accounting:post-orders',
                ]);

                $posted++;
                $this->info(" ✓ posted entry {$entry->entry_no} for order #{$order->increment_id} (¥{$order->grand_total})");
            } catch (ClosedPeriodException $e) {
                // Predates the open fiscal period — informational, not an error. Bookkeeper
                // can post manually via the journal UI when the period reopens.
                Log::info('Fujiba\\Accounting: order skipped — closed/no-period', [
                    'order_id' => $order->id,
                    'message'  => $e->getMessage(),
                ]);
                $this->warn(" ↷ order #{$order->increment_id}: {$e->getMessage()}");
                $skipped++;
            } catch (Throwable $e) {
                $errors[$order->id] = $e->getMessage();
                Log::error('Fujiba\\Accounting: order posting failed', [
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                    'trace'    => $e->getTraceAsString(),
                ]);
                $this->error(" ✗ order #{$order->increment_id}: {$e->getMessage()}");
            }
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->line('=== DRY RUN — would post the following entries ===');
            foreach ($samples as $s) {
                $this->line($s);
            }
        }

        if (! $this->option('dry-run') && empty($errors)) {
            $settings->set('last_order_post_run_at', now()->toDateTimeString());
        }

        $this->newLine();
        $this->table(['posted', 'skipped', 'errors'], [[$posted, $skipped, count($errors)]]);

        return empty($errors) ? self::SUCCESS : self::FAILURE;
    }

    protected function buildPayload(object $order, ConsumptionTaxCalculator $taxCalc): array
    {
        $channelId = (int) $order->channel_id;
        $grandTotal = (int) round((float) $order->grand_total);

        // Determine receivable account: cash for POS/cash payments, AR for everything else.
        $payment = DB::table('order_payment')->where('order_id', $order->id)->first();
        $isCash = $payment && in_array(strtolower((string) $payment->method), [
            'cash', 'cashondelivery', 'pos_cash', 'cod', 'moneytransfer',
        ], true);
        // moneytransfer (Japanese 銀行振込) is funded but treated as cash from the
        // accounting side once payment_confirmed_at is set — the upstream listener
        // (see project memory: BankTransferOrderListener) confirms that.
        $receivableCode = $isCash ? '1110' : '1150';   // 現金 vs 売掛金

        $lines = [];
        $lines[] = [
            'account_code' => $receivableCode,
            'debit'        => $grandTotal,
            'credit'       => 0,
            'memo'         => $isCash ? '現金売上' : '掛売上',
            'partner_name' => trim((string) ($order->customer_email ?? '')) ?: null,
        ];

        // Build the credit side(s).
        if ($channelId === (int) (array_search('mmfood', config('accounting.channels', []), true) ?: 2)) {
            // mmfood: split 8% / 10% via item attributes if available.
            $items = DB::table('order_items')
                ->select(['order_items.id', 'order_items.product_id', 'order_items.sku',
                          'order_items.total', 'order_items.tax_amount'])
                ->where('order_id', $order->id)
                ->get()
                ->map(function ($it) {
                    $reducedAttr = $this->lookupReducedRateAttribute($it->product_id);
                    return [
                        'product_id'      => $it->product_id,
                        'sku'             => $it->sku,
                        'gross'           => (int) round((float) $it->total + (float) $it->tax_amount),
                        'is_takeout_food' => $reducedAttr,
                    ];
                })->toArray();

            $buckets = $taxCalc->splitFoodVsDrink($items);
            $totalNet = 0; $totalTax = 0;
            foreach ($buckets as $b) {
                $code = $b['rate'] === 8.0 ? '4120' : '4110';
                $lines[] = [
                    'account_code' => $code,
                    'debit'        => 0,
                    'credit'       => $b['net'],
                    'tax_rate'     => (string) (int) $b['rate'],
                    'tax_amount'   => $b['tax'],
                    'memo'         => "売上 {$b['rate']}%",
                ];
                $totalNet += $b['net'];
                $totalTax += $b['tax'];
            }
            $diff = $grandTotal - $totalNet - $totalTax;
            // Tax payable line (consolidated)
            if ($totalTax > 0) {
                $lines[] = [
                    'account_code' => '2180',   // 仮受消費税
                    'debit'        => 0,
                    'credit'       => $totalTax,
                    'memo'         => '仮受消費税',
                ];
            }
            // Reconcile rounding — small ¥1-2 drift due to floor(); park on 雑収入.
            if ($diff !== 0) {
                $lines[] = [
                    'account_code' => '7120',   // 雑収入
                    'debit'        => $diff < 0 ? -$diff : 0,
                    'credit'       => $diff > 0 ? $diff : 0,
                    'memo'         => '端数調整',
                ];
            }
        } else {
            // kimono (channel 1) or any other: single 10% line.
            $calc = $taxCalc->calculate($grandTotal, 10.0);
            $lines[] = [
                'account_code' => '4110',
                'debit'        => 0,
                'credit'       => $calc['net'],
                'tax_rate'     => '10',
                'tax_amount'   => $calc['tax'],
                'memo'         => '売上 10%',
            ];
            if ($calc['tax'] > 0) {
                $lines[] = [
                    'account_code' => '2180',
                    'debit'        => 0,
                    'credit'       => $calc['tax'],
                    'memo'         => '仮受消費税',
                ];
            }
        }

        return [
            'entry_date'      => Carbon::parse($order->payment_confirmed_at ?? $order->created_at),
            'channel_id'      => $channelId,
            'memo'            => "Order #{$order->increment_id}",
            'reference_no'    => $order->increment_id,
            'source_type'     => 'order',
            'source_id'       => (int) $order->id,
            'idempotency_key' => "ord-{$order->id}-paid",
            'lines'           => $lines,
        ];
    }

    /**
     * Resolve the tax_reduced_eligible product attribute. Returns null if the
     * attribute is not configured on this Bagisto install (per spec, default 10%).
     */
    protected function lookupReducedRateAttribute(?int $productId): ?bool
    {
        if (! $productId) {
            return null;
        }
        $attr = DB::table('attributes')->where('code', 'tax_reduced_eligible')->first();
        if (! $attr) {
            return null;
        }
        $col = match ($attr->type ?? '') {
            'boolean', 'select' => 'boolean_value',
            default             => 'text_value',
        };
        $val = DB::table('product_attribute_values')
            ->where('product_id', $productId)
            ->where('attribute_id', $attr->id)
            ->value($col);
        return $val === null ? null : (bool) $val;
    }

    protected function summarizePayload(object $order, array $payload): string
    {
        $totals = collect($payload['lines'])->map(fn ($l) => sprintf(
            '    %s  DR ¥%s  CR ¥%s  %s',
            str_pad($l['account_code'], 6),
            number_format($l['debit']),
            number_format($l['credit']),
            $l['memo'] ?? ''
        ))->implode("\n");
        return "  Order #{$order->increment_id} (id={$order->id}, ¥" . number_format((float) $order->grand_total) . ")\n{$totals}";
    }
}

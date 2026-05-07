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

class PostBagistoRefunds extends Command
{
    protected $signature = 'accounting:post-refunds
                            {--since= : ISO timestamp to scan from (default: last successful run)}
                            {--dry-run : Print what would be posted without writing}';

    protected $description = 'Post Bagisto refunds as reversing entries into the accounting journal.';

    public function handle(
        JournalPoster $poster,
        ConsumptionTaxCalculator $taxCalc,
        SettingService $settings,
    ): int {
        $since = $this->option('since')
            ? Carbon::parse($this->option('since'))
            : ($settings->get('last_refund_post_run_at')
                ? Carbon::parse($settings->get('last_refund_post_run_at'))
                : Carbon::now()->subDays(30));

        $this->line("Scanning refunds updated_at >= {$since->toDateTimeString()}");

        $refunds = DB::table('refunds')
            ->where('refunds.updated_at', '>=', $since)
            ->orderBy('refunds.id')
            ->get();

        $this->line("Found {$refunds->count()} candidate refund(s).");

        $posted = 0;
        $skipped = 0;
        $errors = [];
        $samples = [];

        foreach ($refunds as $refund) {
            try {
                $existing = JournalEntry::where('source_type', 'refund')
                    ->where('source_id', $refund->id)
                    ->first();
                if ($existing) {
                    $skipped++;
                    continue;
                }

                // Look up original order entry — required to know the receivable side.
                $originalEntry = JournalEntry::where('source_type', 'order')
                    ->where('source_id', $refund->order_id)
                    ->first();
                if (! $originalEntry) {
                    Log::warning('Fujiba\\Accounting: refund without matching order entry — skipping', [
                        'refund_id' => $refund->id,
                        'order_id'  => $refund->order_id,
                    ]);
                    $skipped++;
                    continue;
                }

                $order = DB::table('orders')->where('id', $refund->order_id)->first();
                if (! $order) {
                    Log::warning('Fujiba\\Accounting: refund references missing order — skipping', [
                        'refund_id' => $refund->id,
                        'order_id'  => $refund->order_id,
                    ]);
                    $skipped++;
                    continue;
                }

                $payload = $this->buildPayload($refund, $order, $taxCalc);

                if ($this->option('dry-run')) {
                    $samples[] = $this->summarizePayload($refund, $payload);
                    $posted++;
                    continue;
                }

                $entry = $poster->post($payload);

                AuditLog::create([
                    'table_name'  => 'fujiba_journal_entries',
                    'record_id'   => $entry->id,
                    'action'      => 'created',
                    'after_json'  => ['source' => 'cron:post-refunds', 'refund_id' => $refund->id, 'entry_no' => $entry->entry_no],
                    'user_name'   => 'cron:accounting:post-refunds',
                ]);

                $posted++;
                $this->info(" ✓ posted refund entry {$entry->entry_no} for refund #{$refund->increment_id} (¥{$refund->grand_total})");
            } catch (ClosedPeriodException $e) {
                Log::info('Fujiba\\Accounting: refund skipped — closed/no-period', [
                    'refund_id' => $refund->id,
                    'message'   => $e->getMessage(),
                ]);
                $this->warn(" ↷ refund #{$refund->id}: {$e->getMessage()}");
                $skipped++;
            } catch (Throwable $e) {
                $errors[$refund->id] = $e->getMessage();
                Log::error('Fujiba\\Accounting: refund posting failed', [
                    'refund_id' => $refund->id,
                    'error'     => $e->getMessage(),
                ]);
                $this->error(" ✗ refund #{$refund->id}: {$e->getMessage()}");
            }
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->line('=== DRY RUN — would post the following refund entries ===');
            foreach ($samples as $s) {
                $this->line($s);
            }
        }

        if (! $this->option('dry-run') && empty($errors)) {
            $settings->set('last_refund_post_run_at', now()->toDateTimeString());
        }

        $this->newLine();
        $this->table(['posted', 'skipped', 'errors'], [[$posted, $skipped, count($errors)]]);

        return empty($errors) ? self::SUCCESS : self::FAILURE;
    }

    protected function buildPayload(object $refund, object $order, ConsumptionTaxCalculator $taxCalc): array
    {
        $channelId = (int) $order->channel_id;
        $grandTotal = (int) round((float) $refund->grand_total);

        // Mirror the original order's payment routing (cash vs AR).
        $payment = DB::table('order_payment')->where('order_id', $order->id)->first();
        $isCash = $payment && in_array(strtolower((string) $payment->method), [
            'cash', 'cashondelivery', 'pos_cash', 'cod', 'moneytransfer',
        ], true);
        $receivableCode = $isCash ? '1110' : '1150';

        $lines = [];

        // Reverse the original direction: revenue is debited, cash/AR is credited.
        if ($channelId === (int) (array_search('mmfood', config('accounting.channels', []), true) ?: 2)) {
            // For mmfood we approximate: reverse using order-wide split by tax_amount portion.
            // Refund-level tax breakdown isn't tracked granularly in Bagisto refunds.
            $totalTax = (int) round((float) $refund->tax_amount);
            $totalNet = $grandTotal - $totalTax;

            $lines[] = ['account_code' => '4110', 'debit' => $totalNet, 'credit' => 0, 'memo' => '売上戻り (10%)'];
            if ($totalTax > 0) {
                $lines[] = ['account_code' => '2180', 'debit' => $totalTax, 'credit' => 0, 'memo' => '仮受消費税戻り'];
            }
        } else {
            $totalTax = (int) round((float) $refund->tax_amount);
            $calc = $totalTax > 0
                ? ['net' => $grandTotal - $totalTax, 'tax' => $totalTax]
                : $taxCalc->calculate($grandTotal, 10.0);

            $lines[] = ['account_code' => '4110', 'debit' => $calc['net'], 'credit' => 0, 'memo' => '売上戻り (10%)'];
            if (($calc['tax'] ?? 0) > 0) {
                $lines[] = ['account_code' => '2180', 'debit' => $calc['tax'], 'credit' => 0, 'memo' => '仮受消費税戻り'];
            }
        }

        $lines[] = [
            'account_code' => $receivableCode,
            'debit'        => 0,
            'credit'       => $grandTotal,
            'memo'         => $isCash ? '現金返金' : '売掛金戻し',
        ];

        return [
            'entry_date'      => Carbon::parse($refund->created_at ?? now()),
            'channel_id'      => $channelId,
            'memo'            => "Refund of order #{$order->increment_id}",
            'reference_no'    => $refund->increment_id,
            'source_type'     => 'refund',
            'source_id'       => (int) $refund->id,
            'idempotency_key' => "ord-{$order->id}-refund-{$refund->id}",
            'lines'           => $lines,
        ];
    }

    protected function summarizePayload(object $refund, array $payload): string
    {
        $totals = collect($payload['lines'])->map(fn ($l) => sprintf(
            '    %s  DR ¥%s  CR ¥%s  %s',
            str_pad($l['account_code'], 6),
            number_format($l['debit']),
            number_format($l['credit']),
            $l['memo'] ?? ''
        ))->implode("\n");
        return "  Refund #{$refund->id} for order #{$refund->order_id} (¥" . number_format((float) $refund->grand_total) . ")\n{$totals}";
    }
}

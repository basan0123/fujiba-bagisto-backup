<?php

namespace Fujiba\Notification\Console\Commands;

use Fujiba\Notification\Models\CustomerChannelToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill customer_channel_tokens directly from customers.chatpion_subscribe_id.
 *
 * Sibling to fujiba:backfill-messenger-tokens which sources from
 * chatpion_import.messenger_bot_subscriber and joins on email. That command
 * misses any customer whose email is empty or synthetic (@messenger.local) —
 * which on the kimono+mmfood tenant is ~12k of the ~12.2k subscribed customers.
 *
 * This command bypasses that join. The subscribe_id is already on the customer
 * row, populated by MessengerLogin/AutoLogin at first contact. We just lift it
 * into customer_channel_tokens so the standard send path (OrderEventListener →
 * MessengerChannel) can find it.
 *
 * Page IDs are hardcoded (see PAGE_IDS below) rather than resolved at runtime
 * via chatpion_import.facebook_rx_fb_page_info — that table is documented as a
 * stale snapshot, and the universe of channels for this backfill is exactly 2.
 * Loud-fail on unknown channel keeps mistakes visible.
 *
 * Idempotent — firstOrCreate keyed on (customer_id, provider, page_id, token).
 *
 * Every row written by this command is tagged in `meta` for surgical rollback:
 *   meta = {"source":"customer_backfill","run_at":"<iso>","tenant":"<uuid>"}
 *   rollback: DELETE WHERE provider='messenger'
 *               AND meta->>'$.source' = 'customer_backfill'
 *               AND meta->>'$.run_at' = '<specific run>'
 */
class BackfillTokensFromCustomers extends Command
{
    /**
     * channel_id => real Facebook page_id
     *
     * Source: chatpion_import.facebook_rx_fb_page_info on 2026-05-06.
     *   internal_id 71 (kimono) → page_id 103196179075087 (Fujiba Elegance Emporium)
     *   internal_id 69 (mmfood) → page_id 592609133927678 (Fujiba Myanmar)
     *
     * Hardcoded so we don't depend on chatpion_import (stale snapshot per
     * `feedback_chatpion_import_vs_stage`). If FB pages change, update here.
     */
    private const PAGE_IDS = [
        1 => '103196179075087', // kimono - Fujiba Elegance Emporium
        2 => '592609133927678', // mmfood - Fujiba Myanmar
    ];

    protected $signature = 'fujiba:backfill-tokens-from-customers
                            {--tenants= : Comma-separated tenant IDs (default: all)}
                            {--dry-run : Report counts and 10 sample rows without inserting}';

    protected $description = 'Backfill customer_channel_tokens from customers.chatpion_subscribe_id';

    public function handle(): int
    {
        $tenantIds = $this->resolveTenantIds();

        if (empty($tenantIds)) {
            $this->error('No tenants matched.');
            return self::FAILURE;
        }

        $isDryRun = (bool) $this->option('dry-run');
        $runAt    = now()->toIso8601String();

        $this->info(sprintf(
            '%s on %d tenant(s)%s',
            $isDryRun ? 'DRY RUN' : 'BACKFILL',
            count($tenantIds),
            $isDryRun ? ' — no writes will be performed' : ''
        ));
        $this->info('run_at: '.$runAt);
        $this->newLine();

        $totals = ['inserted' => 0, 'already_linked' => 0, 'unknown_channel' => 0, 'no_subscribe_id' => 0];

        foreach ($tenantIds as $tenantId) {
            $tenant = \App\Models\Tenant::find($tenantId);

            if (! $tenant) {
                $this->warn("Tenant {$tenantId} not found — skipped.");
                continue;
            }

            tenancy()->initialize($tenant);

            $perTenant = $this->processTenant($tenantId, $runAt, $isDryRun);

            tenancy()->end();

            $this->line(sprintf(
                '  Tenant %s: inserted=%d already_linked=%d unknown_channel=%d no_subscribe_id=%d',
                $tenantId,
                $perTenant['inserted'],
                $perTenant['already_linked'],
                $perTenant['unknown_channel'],
                $perTenant['no_subscribe_id'],
            ));

            foreach ($perTenant as $k => $v) {
                $totals[$k] += $v;
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'TOTAL: inserted=%d already_linked=%d unknown_channel=%d no_subscribe_id=%d',
            $totals['inserted'],
            $totals['already_linked'],
            $totals['unknown_channel'],
            $totals['no_subscribe_id'],
        ));

        if ($isDryRun && $totals['inserted'] > 0) {
            $this->newLine();
            $this->comment("Dry run — {$totals['inserted']} would be inserted. Re-run without --dry-run to apply.");
        }

        return self::SUCCESS;
    }

    /**
     * Process one tenant. Returns counters per outcome.
     *
     * Sample selection (dry-run only): first 5 customer rows per channel,
     * ordered by customer_id ascending. Deterministic, fast, gives both-channel
     * visibility without ORDER BY RAND() on a 12k-row scan.
     */
    protected function processTenant(string $tenantId, string $runAt, bool $isDryRun): array
    {
        $stats = ['inserted' => 0, 'already_linked' => 0, 'unknown_channel' => 0, 'no_subscribe_id' => 0];

        $samplesByChannel = [];

        $candidates = DB::table('customers')
            ->whereNotNull('chatpion_subscribe_id')
            ->where('chatpion_subscribe_id', '!=', '')
            ->orderBy('id')
            ->select('id', 'channel_id', 'chatpion_subscribe_id', 'email')
            ->cursor();

        foreach ($candidates as $cust) {
            $subId = (string) $cust->chatpion_subscribe_id;

            if ($subId === '') {
                $stats['no_subscribe_id']++;
                continue;
            }

            $channelId = (int) $cust->channel_id;
            $pageId    = self::PAGE_IDS[$channelId] ?? null;

            if ($pageId === null) {
                $stats['unknown_channel']++;
                continue;
            }

            $exists = CustomerChannelToken::query()
                ->where('customer_id', $cust->id)
                ->where('provider', CustomerChannelToken::PROVIDER_MESSENGER)
                ->where('page_id', $pageId)
                ->where('token', $subId)
                ->exists();

            if ($exists) {
                $stats['already_linked']++;
                continue;
            }

            if ($isDryRun) {
                $stats['inserted']++;

                $samplesByChannel[$channelId] ??= [];
                if (count($samplesByChannel[$channelId]) < 5) {
                    $samplesByChannel[$channelId][] = [
                        'customer_id' => $cust->id,
                        'channel_id'  => $channelId,
                        'page_id'     => $pageId,
                        'token'       => $subId,
                        'email'       => $cust->email ?: '(empty)',
                    ];
                }
                continue;
            }

            CustomerChannelToken::create([
                'customer_id' => $cust->id,
                'provider'    => CustomerChannelToken::PROVIDER_MESSENGER,
                'page_id'     => $pageId,
                'token'       => $subId,
                'is_active'   => true,
                'verified_at' => now(),
                'meta'        => [
                    'source'  => 'customer_backfill',
                    'run_at'  => $runAt,
                    'tenant'  => $tenantId,
                ],
            ]);

            $stats['inserted']++;
        }

        if ($isDryRun && ! empty($samplesByChannel)) {
            $this->newLine();
            $this->info("  Sample rows that would be inserted (5 per channel):");
            foreach ($samplesByChannel as $channelId => $rows) {
                foreach ($rows as $r) {
                    $this->line(sprintf(
                        '    channel=%d cust=%d page=%s token=%s email=%s',
                        $r['channel_id'], $r['customer_id'], $r['page_id'], $r['token'], $r['email']
                    ));
                }
            }
            $this->newLine();
        }

        return $stats;
    }

    protected function resolveTenantIds(): array
    {
        $opt = (string) $this->option('tenants');

        if ($opt === '') {
            return \App\Models\Tenant::pluck('id')->toArray();
        }

        return array_values(array_filter(array_map('trim', explode(',', $opt))));
    }
}

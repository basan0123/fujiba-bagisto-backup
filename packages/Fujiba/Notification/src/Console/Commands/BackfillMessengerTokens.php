<?php

namespace Fujiba\Notification\Console\Commands;

use Fujiba\Notification\Models\CustomerChannelToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill customer_channel_tokens from chatpion_import.messenger_bot_subscriber.
 *
 * Source filter: status='1' AND is_email_unsubscriber='0' AND unsubscribed_at IS NULL AND email != ''
 * Match: customers.email per tenant, case-insensitive
 * Insert: provider='messenger', token=subscribe_id, page_id=page_id, is_active=true, verified_at=now()
 *
 * Idempotent — firstOrCreate keyed on (customer_id, provider, page_id, token).
 * Re-running on a tenant with linked customers is a no-op.
 *
 * Synthetic-email customers (@messenger.local) are skipped here. Their PSID
 * is already encoded in the local-part of the email; if they need a token,
 * insert manually or build a separate command for that path.
 */
class BackfillMessengerTokens extends Command
{
    protected $signature = 'fujiba:backfill-messenger-tokens
                            {--tenants= : Comma-separated tenant IDs (default: all)}
                            {--dry-run : Report counts without inserting}';

    protected $description = 'Backfill customer_channel_tokens from chatpion_import.messenger_bot_subscriber';

    public function handle(): int
    {
        $tenantIds = $this->resolveTenantIds();

        if (empty($tenantIds)) {
            $this->error('No tenants matched.');
            return self::FAILURE;
        }

        $isDryRun = (bool) $this->option('dry-run');

        $this->info(sprintf(
            '%s on %d tenant(s)%s',
            $isDryRun ? 'DRY RUN' : 'BACKFILL',
            count($tenantIds),
            $isDryRun ? ' — no writes will be performed' : ''
        ));
        $this->newLine();

        // Source data is in the central chatpion_import connection — load once,
        // tenant-specific matching happens per tenant below. Filtered to active
        // subscribers with a non-empty email (the join key).
        $subscribers = DB::connection('chatpion_import')
            ->table('messenger_bot_subscriber')
            ->where('status', '1')
            ->where(function ($q) {
                $q->whereNull('is_email_unsubscriber')
                  ->orWhere('is_email_unsubscriber', '0');
            })
            ->whereNull('unsubscribed_at')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->select('subscribe_id', 'page_id', 'email', 'first_name', 'last_name')
            ->get();

        $this->info(sprintf('Source: %d eligible subscribers in chatpion_import.messenger_bot_subscriber', $subscribers->count()));
        $this->newLine();

        $totals = ['matched' => 0, 'already_linked' => 0, 'no_match' => 0, 'invalid' => 0];

        foreach ($tenantIds as $tenantId) {
            $tenant = \App\Models\Tenant::find($tenantId);
            if (! $tenant) {
                $this->warn("Tenant {$tenantId} not found — skipped.");
                continue;
            }

            tenancy()->initialize($tenant);

            $perTenant = ['matched' => 0, 'already_linked' => 0, 'no_match' => 0, 'invalid' => 0];

            foreach ($subscribers as $sub) {
                $email = trim((string) $sub->email);
                if ($email === '') {
                    $perTenant['invalid']++;
                    continue;
                }

                // Case-insensitive match against the tenant's customers table.
                $customerId = DB::table('customers')
                    ->whereRaw('LOWER(email) = ?', [strtolower($email)])
                    ->value('id');

                if (! $customerId) {
                    $perTenant['no_match']++;
                    continue;
                }

                if ($isDryRun) {
                    // Check whether a row with the same key already exists.
                    $exists = CustomerChannelToken::where('customer_id', $customerId)
                        ->where('provider', 'messenger')
                        ->where('page_id', (string) $sub->page_id)
                        ->where('token', (string) $sub->subscribe_id)
                        ->exists();

                    if ($exists) {
                        $perTenant['already_linked']++;
                    } else {
                        $perTenant['matched']++;
                    }
                    continue;
                }

                $row = CustomerChannelToken::firstOrCreate(
                    [
                        'customer_id' => $customerId,
                        'provider'    => 'messenger',
                        'page_id'     => (string) $sub->page_id,
                        'token'       => (string) $sub->subscribe_id,
                    ],
                    [
                        'is_active'   => true,
                        'verified_at' => now(),
                    ],
                );

                if ($row->wasRecentlyCreated) {
                    $perTenant['matched']++;
                } else {
                    $perTenant['already_linked']++;
                }
            }

            tenancy()->end();

            $this->line(sprintf(
                '  Tenant %s: matched=%d already_linked=%d no_match=%d invalid=%d',
                $tenantId,
                $perTenant['matched'],
                $perTenant['already_linked'],
                $perTenant['no_match'],
                $perTenant['invalid'],
            ));

            foreach ($perTenant as $k => $v) {
                $totals[$k] += $v;
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'TOTAL: matched=%d already_linked=%d no_match=%d invalid=%d',
            $totals['matched'],
            $totals['already_linked'],
            $totals['no_match'],
            $totals['invalid'],
        ));

        if ($isDryRun && $totals['matched'] > 0) {
            $this->newLine();
            $this->comment("Dry run — {$totals['matched']} would be inserted. Re-run without --dry-run to apply.");
        }

        return self::SUCCESS;
    }

    /**
     * --tenants=ID,ID  → those tenants
     * --tenants= (empty / not given) → all tenants
     */
    protected function resolveTenantIds(): array
    {
        $opt = (string) $this->option('tenants');

        if ($opt === '') {
            return \App\Models\Tenant::pluck('id')->toArray();
        }

        return array_values(array_filter(array_map('trim', explode(',', $opt))));
    }
}

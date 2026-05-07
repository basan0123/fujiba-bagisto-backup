<?php

namespace Webkul\Pos\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Pos\Models\CustomerDisplaySession;

class RevokeExpiredCustomerDisplays extends Command
{
    protected $signature = 'pos:customer-displays:revoke-expired';

    protected $description = 'Revoke customer-display sessions whose expires_at has passed. Currently a no-op: pair() sets expires_at 100 years in the future. Re-enable a TTL policy by lowering that value.';

    public function handle(): int
    {
        $count = CustomerDisplaySession::whereNull('revoked_at')
            ->where('expires_at', '<', now())
            ->update([
                'revoked_at'     => now(),
                'revoked_reason' => 'expired',
            ]);

        $this->info("Revoked {$count} expired customer display session(s).");

        return self::SUCCESS;
    }
}

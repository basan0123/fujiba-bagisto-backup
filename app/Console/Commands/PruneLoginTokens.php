<?php

namespace App\Console\Commands;

use App\Models\LoginToken;
use Illuminate\Console\Command;

class PruneLoginTokens extends Command
{
    protected $signature = 'login-tokens:prune';

    protected $description = 'Delete expired or stale used login_tokens (per tenant via tenants:run)';

    public function handle(): int
    {
        $cutoff = now()->subDay();

        $deleted = LoginToken::query()
            ->where(function ($q) use ($cutoff) {
                $q->where('expires_at', '<', $cutoff)
                  ->orWhere(function ($q2) use ($cutoff) {
                      $q2->whereNotNull('used_at')
                         ->where('created_at', '<', $cutoff);
                  });
            })
            ->delete();

        $this->info("Pruned {$deleted} login_tokens row(s).");

        return self::SUCCESS;
    }
}

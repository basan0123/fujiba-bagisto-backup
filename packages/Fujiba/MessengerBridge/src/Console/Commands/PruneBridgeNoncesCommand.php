<?php

namespace Fujiba\MessengerBridge\Console\Commands;

use Fujiba\MessengerBridge\Services\NonceStore;
use Illuminate\Console\Command;

class PruneBridgeNoncesCommand extends Command
{
    protected $signature   = 'messenger-bridge:prune-nonces {--seconds=3600 : Delete rows older than this}';
    protected $description = 'Delete expired messenger bridge nonces from the central DB.';

    public function handle(NonceStore $store): int
    {
        $seconds = max(60, (int) $this->option('seconds'));
        $deleted = $store->prune($seconds);
        $this->info("Pruned {$deleted} nonces older than {$seconds}s.");
        return self::SUCCESS;
    }
}

<?php

namespace Fujiba\AuthGateway\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Phase 2 helper. In Phase 1 we use HS256 with the existing JWT_SECRET, so
 * this command just prints guidance. Reserved here so the production runbook
 * has a stable name.
 */
class RotateJwtKeyCommand extends Command
{
    protected $signature   = 'auth-gateway:keygen {--print : Print a new candidate secret to stdout (does not modify .env)}';
    protected $description = 'Generate a new JWT secret (HS256) or keypair (RS256, Phase 2).';

    public function handle(): int
    {
        $algo = config('auth-gateway.jwt.algorithm');
        if ($algo === 'HS256') {
            $secret = Str::random(64);
            $this->info('Generated HS256 secret (64 chars):');
            $this->line($secret);
            $this->newLine();
            $this->comment('Phase 1 uses the existing JWT_SECRET — do NOT swap unless you are coordinating');
            $this->comment('a tenant-side rollout (every tenant must accept the new value).');
            $this->comment('Add to .env as POS_JWT_SECRET=... and restart workers.');
            return self::SUCCESS;
        }

        $this->warn('RS256 keygen is Phase 2; not implemented yet.');
        return self::FAILURE;
    }
}

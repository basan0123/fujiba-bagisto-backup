<?php

namespace Fujiba\MessengerBridge\Services;

use Illuminate\Support\Facades\DB;

/**
 * Replay-defense storage. Backed by the messenger_bridge_nonces table on
 * the central DB so the bridge works before tenancy initialization.
 */
class NonceStore
{
    /** Connection name in config/database.php that points at bagisto_central. */
    private const CONN = 'central';

    /**
     * Returns true if the nonce was newly recorded (caller may proceed).
     * Returns false if it already existed (caller MUST reject as replay).
     */
    public function recordOrReject(string $nonce): bool
    {
        try {
            DB::connection(self::CONN)->table('messenger_bridge_nonces')->insert([
                'nonce'   => $nonce,
                'seen_at' => now(),
            ]);
            return true;
        } catch (\Illuminate\Database\QueryException $e) {
            // Duplicate primary key (1062) → replay. Anything else surfaces.
            if ((int) $e->errorInfo[1] === 1062) {
                return false;
            }
            throw $e;
        }
    }

    /** Delete rows older than $olderThanSeconds (caller schedules this). */
    public function prune(int $olderThanSeconds = 3600): int
    {
        return DB::connection(self::CONN)
            ->table('messenger_bridge_nonces')
            ->where('seen_at', '<', now()->subSeconds($olderThanSeconds))
            ->delete();
    }
}

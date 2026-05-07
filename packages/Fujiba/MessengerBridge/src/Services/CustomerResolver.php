<?php

namespace Fujiba\MessengerBridge\Services;

use Illuminate\Support\Facades\DB;

/**
 * Resolves a Bagisto customer from a verified ChatPion bridge token.
 *
 * Strategy (Phase 2 data validation):
 *   1. customers.chatpion_subscribe_id matches → 99.6% / 99.2% coverage
 *   2. customer_channel_tokens.token + provider='messenger' fallback
 *      via customers.id (covers customers re-linked via MessengerLogin)
 *   3. null → caller redirects to channel home with "please log in" flash
 *
 * Always operates on the default ('mysql') connection, which the
 * stancl/tenancy bootstrapper has already swapped to the tenant DB
 * by the time this runs (host-based middleware fires first).
 */
class CustomerResolver
{
    /**
     * @return object|null  raw row with at least .id; null if not resolvable
     */
    public function resolve(string $chatpionSubscribeId, int $bagistoChannelId): ?object
    {
        // Primary path — exact subscribe_id match within channel.
        $row = DB::table('customers')
            ->where('chatpion_subscribe_id', $chatpionSubscribeId)
            ->where('channel_id', $bagistoChannelId)
            ->select(['id', 'channel_id', 'email', 'first_name', 'last_name'])
            ->first();
        if ($row) return $row;

        // Fallback — customer_channel_tokens (provider='messenger') joined
        // back to customers within the same channel. Covers cases where the
        // legacy MessengerLogin path created the token row but
        // chatpion_subscribe_id wasn't backfilled.
        return DB::table('customer_channel_tokens AS cct')
            ->join('customers AS c', 'c.id', '=', 'cct.customer_id')
            ->where('cct.provider', 'messenger')
            ->where('cct.token', $chatpionSubscribeId)
            ->where('cct.is_active', 1)
            ->where('c.channel_id', $bagistoChannelId)
            ->select(['c.id', 'c.channel_id', 'c.email', 'c.first_name', 'c.last_name'])
            ->first();
    }
}

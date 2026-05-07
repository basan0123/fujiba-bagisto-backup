<?php

namespace Fujiba\MessengerBridge\Services;

/**
 * Single source of truth for ChatPion store_id ↔ Bagisto channel mapping.
 *
 * ChatPion store_id is the SOT (signed in the token). Bagisto channel_id
 * and the expected receiving host are derived from it here so the rest of
 * the bridge can't drift.
 *
 * Trap encoded: kimono's Bagisto channel CODE is 'default', not 'kimono'.
 * Don't add a code-based lookup elsewhere.
 */
class ChannelMapper
{
    private const MAP = [
        9  => ['bagisto_channel_id' => 2, 'host' => 'mmfood.fujiba.co.jp',  'label' => 'mmfood'],
        10 => ['bagisto_channel_id' => 1, 'host' => 'kimono.fujiba.co.jp', 'label' => 'kimono'],
    ];

    public static function isAllowed(int $chatpionStoreId): bool
    {
        return isset(self::MAP[$chatpionStoreId]);
    }

    public static function bagistoChannelId(int $chatpionStoreId): ?int
    {
        return self::MAP[$chatpionStoreId]['bagisto_channel_id'] ?? null;
    }

    public static function expectedHost(int $chatpionStoreId): ?string
    {
        return self::MAP[$chatpionStoreId]['host'] ?? null;
    }

    public static function label(int $chatpionStoreId): ?string
    {
        return self::MAP[$chatpionStoreId]['label'] ?? null;
    }
}

<?php

namespace Fujiba\Dashboard\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Subscribers KPI = ChatPion `messenger_bot_subscriber` snapshot, joined to
 * Bagisto channels via page_table_id (see migrate_chatpion.php):
 *   page_table_id 71 → channel `default` (kimono)
 *   page_table_id 69 → channel `mmfood`  (myanmar-food)
 *
 * Reads from `chatpion_stage` — the live connection (refreshed hourly by
 * SyncChatpion). `chatpion_import` is a frozen snapshot, never read here.
 *
 * The "total" is an all-time active subscriber count (channel-scoped).
 * The "gain" is subscribers whose `subscribed_at` falls inside the dashboard
 * period the user has selected.
 */
class SubscribersRepository
{
    private const CONNECTION = 'chatpion_stage';
    private const TABLE      = 'messenger_bot_subscriber';

    /** Bagisto channel code → ChatPion page_table_id. */
    private const PAGE_TABLE_BY_CHANNEL = [
        'default' => 71, // kimono
        'mmfood'  => 69, // myanmar-food
    ];

    /**
     * @return array{
     *   total: int,
     *   gain: int,
     *   period: array{start: string, end: string},
     *   has_data: bool
     * }
     */
    public function getStats(?string $channelCode, Carbon $start, Carbon $end): array
    {
        $pageTableIds = $this->resolvePageTableIds($channelCode);

        try {
            $base = DB::connection(self::CONNECTION)
                ->table(self::TABLE)
                ->whereIn('page_table_id', $pageTableIds);

            // Active = status = 1 AND not unsubscribed.
            // ChatPion uses '0000-00-00 00:00:00' for "never unsubscribed",
            // so we filter both NULL and the zero-date sentinel.
            $total = (clone $base)
                ->where('status', 1)
                ->where(function ($q) {
                    $q->whereNull('unsubscribed_at')
                      ->orWhere('unsubscribed_at', '0000-00-00 00:00:00');
                })
                ->count();

            $gain = (clone $base)
                ->whereBetween('subscribed_at', [$start, $end])
                ->count();

            return [
                'total'    => (int) $total,
                'gain'     => (int) $gain,
                'period'   => [
                    'start' => $start->toDateString(),
                    'end'   => $end->toDateString(),
                ],
                'has_data' => $total > 0 || $gain > 0,
            ];
        } catch (Throwable $e) {
            Log::warning('Fujiba\\Dashboard SubscribersRepository failed: '.$e->getMessage(), [
                'channel'   => $channelCode,
                'exception' => $e,
            ]);

            return [
                'total'    => 0,
                'gain'     => 0,
                'period'   => [
                    'start' => $start->toDateString(),
                    'end'   => $end->toDateString(),
                ],
                'has_data' => false,
            ];
        }
    }

    /** @return array<int, int> */
    private function resolvePageTableIds(?string $channelCode): array
    {
        if ($channelCode === null || $channelCode === '') {
            return array_values(self::PAGE_TABLE_BY_CHANNEL);
        }

        if (isset(self::PAGE_TABLE_BY_CHANNEL[$channelCode])) {
            return [self::PAGE_TABLE_BY_CHANNEL[$channelCode]];
        }

        return array_values(self::PAGE_TABLE_BY_CHANNEL);
    }
}

<?php

namespace Fujiba\LiveChat\Console\Commands;

use Fujiba\LiveChat\Models\LiveChatSetting;
use Fujiba\LiveChat\Services\ChatpionFailoverService;
use Fujiba\LiveChat\Services\SubscriberLinker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncChatpionSubscribersCommand extends Command
{
    protected $signature = 'fujiba:sync-chatpion-subscribers
        {--dry-run : Report matches without writing messenger_subscriber_id}
        {--channel= : Restrict to a single channel id}
        {--limit=50 : Page size for the Chatpion conversations endpoint}
        {--max-pages=50 : Safety cap on number of pages to fetch per channel}
        {--sleep=200 : Milliseconds to sleep between pages}';

    protected $description = 'Backfill customers.messenger_subscriber_id by walking Chatpion conversations and matching by email/phone.';

    public function handle(SubscriberLinker $linker): int
    {
        $dryRun     = (bool) $this->option('dry-run');
        $channelOpt = $this->option('channel');
        $limit      = max(1, min(200, (int) $this->option('limit')));
        $maxPages   = max(1, (int) $this->option('max-pages'));
        $sleepMs    = max(0, (int) $this->option('sleep'));

        $logChannel = $this->dedicatedLogChannel();

        $settings = LiveChatSetting::query();
        if ($channelOpt) {
            $settings->where('channel_id', (int) $channelOpt);
        }
        $settings = $settings->get();

        if ($settings->isEmpty()) {
            $this->error('No LiveChatSetting rows found.');
            return self::FAILURE;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . 'Syncing ' . $settings->count() . ' channel(s).');

        $totals = [
            SubscriberLinker::RESULT_LINKED         => 0,
            SubscriberLinker::RESULT_ALREADY_LINKED => 0,
            SubscriberLinker::RESULT_NO_MATCH       => 0,
            SubscriberLinker::RESULT_SKIPPED        => 0,
        ];

        foreach ($settings as $setting) {
            $this->line('');
            $this->info("Channel #{$setting->channel_id} (bot {$setting->fb_bot_id}):");

            $svc = new ChatpionFailoverService($setting);
            $offset = 0;

            for ($page = 1; $page <= $maxPages; $page++) {
                $resp = $svc->apiPost('livechat_conversations', [
                    'whatsapp_bot_id' => $setting->fb_bot_id,
                    'limit'           => $limit,
                    'offset'          => $offset,
                ]);

                if (($resp['status'] ?? '') !== 'success') {
                    $this->warn("  page {$page}: chatpion error - " . ($resp['message'] ?? '(no message)'));
                    break;
                }

                $items = $resp['data'] ?? [];
                if (empty($items)) {
                    $this->line("  page {$page}: empty - done");
                    break;
                }

                foreach ($items as $item) {
                    $payload = (array) $item;
                    $payload['subscriber_id'] = $payload['from_user_id'] ?? $payload['subscriber_id'] ?? null;
                    if (isset($setting->fb_page_id)) {
                        $payload['page_id'] = $payload['page_id'] ?? $setting->fb_page_id;
                    }

                    $r = $linker->link($payload, (int) $setting->channel_id, $dryRun);
                    $totals[$r['result']]++;

                    if ($r['result'] === SubscriberLinker::RESULT_LINKED) {
                        $this->line(sprintf(
                            '  ✓ linked sub=%s → customer #%d (%s)',
                            $r['subscriber_id'], $r['customer_id'], $r['customer_name']
                        ));
                    } elseif ($r['result'] === SubscriberLinker::RESULT_NO_MATCH) {
                        Log::channel($logChannel)->info('No match', $r);
                    }
                }

                $offset += $limit;
                if ($sleepMs > 0) usleep($sleepMs * 1000);
            }
        }

        $this->line('');
        $this->info(sprintf(
            'Matched %d / Already linked %d / Not found %d / Skipped %d',
            $totals[SubscriberLinker::RESULT_LINKED],
            $totals[SubscriberLinker::RESULT_ALREADY_LINKED],
            $totals[SubscriberLinker::RESULT_NO_MATCH],
            $totals[SubscriberLinker::RESULT_SKIPPED]
        ));
        if ($dryRun) {
            $this->warn('Dry run — no rows were updated.');
        }
        $this->line("Unmatched subscribers logged to storage/logs/chatpion-sync.log");

        return self::SUCCESS;
    }

    /**
     * Configure a dedicated log file at storage/logs/chatpion-sync.log so the
     * "no match" entries don't drown out the main laravel.log.
     */
    protected function dedicatedLogChannel(): string
    {
        config(['logging.channels.chatpion_sync' => [
            'driver' => 'single',
            'path'   => storage_path('logs/chatpion-sync.log'),
            'level'  => 'debug',
        ]]);

        return 'chatpion_sync';
    }
}

<?php

namespace Fujiba\LiveChat\Console\Commands;

use Carbon\Carbon;
use Fujiba\LiveChat\Models\AutoFlowDispatchLog;
use Fujiba\LiveChat\Models\LiveChatSetting;
use Fujiba\LiveChat\Services\BotFlowSender;
use Fujiba\LiveChat\Services\ChatpionFailoverService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * fujiba:dispatch-auto-flow
 *
 * Sends a configured Messenger flow to new customers who messaged the page
 * but got no staff/bot reply within the configured lookback window.
 *
 * Driven by per-channel rows in livechat_settings (auto_flow_enabled +
 * auto_flow_postback_id + auto_flow_lookback_minutes + auto_flow_grace_minutes).
 * No code-level hardcoded flow IDs or page IDs; admin UI is the source of truth.
 */
class DispatchAutoFlowCommand extends Command
{
    protected $signature = 'fujiba:dispatch-auto-flow
        {--tenant= : Tenant ID (UUID). Required when --channel is provided.}
        {--channel= : Bagisto channel ID within the tenant.}
        {--all : Process every LiveChatSetting with auto_flow_enabled=1 across all tenants.}
        {--dry-run : Print candidates and skip Graph + send + log writes.}
        {--limit=200 : Cap candidates per channel per run.}';

    protected $description = 'Dispatch the configured auto-flow to new unanswered Messenger subscribers (per-channel settings).';

    public function handle(BotFlowSender $sender): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $limit    = max(1, (int) $this->option('limit'));

        // Config-drift guard: warn loudly if a channel that operators expect to
        // have auto-flow enabled is found disabled/misconfigured. Runs BEFORE
        // resolveTargets() so it still fires on the "No matching channels found"
        // path — the exact silent-revert symptom from the 2026-05 cutover restore.
        $this->warnOnUnexpectedlyDisabledChannels();

        $settings = $this->resolveTargets();
        if ($settings->isEmpty()) {
            $this->info('No matching channels found.');
            return self::SUCCESS;
        }

        $totals = ['candidates' => 0, 'sent' => 0, 'skipped_dup' => 0, 'skipped_not_new' => 0, 'failed' => 0, 'retried' => 0];

        foreach ($settings as $setting) {
            $channelTotals = $this->processChannel($setting, $sender, $isDryRun, $limit);
            foreach ($channelTotals as $k => $v) {
                $totals[$k] += $v;
            }
        }

        $this->line('');
        $this->info(sprintf(
            'Done. candidates=%d sent=%d retried=%d skipped_dup=%d skipped_not_new=%d failed=%d%s',
            $totals['candidates'], $totals['sent'], $totals['retried'], $totals['skipped_dup'],
            $totals['skipped_not_new'], $totals['failed'],
            $isDryRun ? ' [DRY RUN]' : ''
        ));

        return self::SUCCESS;
    }

    /**
     * Resolve which LiveChatSetting rows this run should process.
     */
    protected function resolveTargets()
    {
        if ($this->option('all')) {
            return LiveChatSetting::where('auto_flow_enabled', true)
                ->whereNotNull('auto_flow_postback_id')
                ->where('auto_flow_postback_id', '!=', '')
                ->get();
        }

        $tenant  = $this->option('tenant');
        $channel = $this->option('channel');

        if ($channel && ! $tenant) {
            $this->error('--tenant is required when --channel is specified.');
            return collect();
        }
        if (! $channel) {
            $this->error('Provide --all, or both --tenant and --channel.');
            return collect();
        }

        return LiveChatSetting::where('tenant_id', $tenant)
            ->where('channel_id', (int) $channel)
            ->get();
    }

    /**
     * Config-drift guard.
     *
     * Compares the operator-declared allowlist (config fujiba_livechat
     * .auto_flow_expected_channels, env LIVECHAT_AUTOFLOW_EXPECTED) against the
     * live livechat_settings rows and Log::warning()s for any expected channel
     * that is missing, has auto_flow_enabled=false, or has an empty postback_id.
     *
     * Motivation: a stale pre-feature DB dump restored over the 2026-05 cutover
     * silently reset auto_flow_enabled to its 0 default; the dispatcher merely
     * logged "No matching channels found" and exited SUCCESS, so a live feature
     * went dark with no signal. This makes that failure mode loud. Heuristic
     * only — does not modify data. Skipped for targeted (--tenant/--channel)
     * runs, which intentionally scope to a single channel.
     */
    protected function warnOnUnexpectedlyDisabledChannels(): void
    {
        if (! $this->option('all')) {
            return;
        }

        $expected = (array) config('fujiba_livechat.auto_flow_expected_channels', []);
        if (empty($expected)) {
            return;
        }

        $byCode = LiveChatSetting::whereIn('channel_code', $expected)
            ->get()
            ->keyBy('channel_code');

        foreach ($expected as $code) {
            $setting = $byCode->get($code);

            if (! $setting) {
                $reason = 'no livechat_settings row';
            } elseif (! $setting->auto_flow_enabled) {
                $reason = 'auto_flow_enabled=false';
            } elseif (empty($setting->auto_flow_postback_id)) {
                $reason = 'auto_flow_postback_id empty';
            } else {
                continue; // healthy
            }

            $msg = "Auto-flow config drift: expected channel '{$code}' is not dispatch-ready ({$reason}). "
                 . 'A live new-customer greeting flow may be silently OFF — check '
                 . '/admin/fujiba-livechat/settings after any DB restore/cutover.';

            Log::warning('DispatchAutoFlow config drift detected', [
                'channel_code' => $code,
                'reason'       => $reason,
                'tenant_id'    => $setting->tenant_id ?? null,
                'channel_id'   => $setting->channel_id ?? null,
            ]);
            $this->warn("[config-drift] {$msg}");
        }
    }

    /**
     * Process one channel: filter candidates, run the per-subscriber dispatch loop.
     *
     * @return array<string,int>
     */
    protected function processChannel(LiveChatSetting $setting, BotFlowSender $sender, bool $isDryRun, int $limit): array
    {
        $totals = ['candidates' => 0, 'sent' => 0, 'skipped_dup' => 0, 'skipped_not_new' => 0, 'failed' => 0, 'retried' => 0];

        $tag = "channel={$setting->channel_id}({$setting->channel_code}) bot={$setting->fb_bot_id}";

        if (! $setting->livechat_enabled) {
            $this->line("[$tag] skipped: livechat_enabled=false");
            return $totals;
        }
        if (! $setting->auto_flow_enabled) {
            $this->line("[$tag] skipped: auto_flow_enabled=false");
            return $totals;
        }
        if (empty($setting->auto_flow_postback_id)) {
            $this->line("[$tag] skipped: no postback_id configured");
            return $totals;
        }

        $lookback = $setting->auto_flow_lookback_minutes ?: 20;
        $grace    = $setting->auto_flow_grace_minutes ?? 3;
        if ($grace >= $lookback) {
            $this->warn("[$tag] grace ($grace) >= lookback ($lookback); skipping for safety.");
            return $totals;
        }

        $windowStart = Carbon::now()->subMinutes($lookback);
        $windowEnd   = Carbon::now()->subMinutes($grace);

        // PERF: ChatPion's livechat_conversations endpoint returns the full subscriber
        // list per call (the `limit` request param is ignored server-side). We filter
        // client-side. With ~5k subscribers the response is ~3MB; acceptable at one
        // poll per channel per 15 min. If candidate volume grows, consider asking
        // ChatPion to add a server-side last_message_time filter. See FB Graph rate
        // limits at https://developers.facebook.com/docs/graph-api/overview/rate-limiting/
        $svc  = new ChatpionFailoverService($setting);
        $resp = $svc->apiPost('livechat_conversations', [
            'whatsapp_bot_id' => $setting->fb_bot_id,
        ]);

        if (($resp['status'] ?? '') !== 'success') {
            $this->error("[$tag] livechat_conversations failed: " . ($resp['message'] ?? '(no message)'));
            return $totals;
        }

        $candidates = collect($resp['data'] ?? [])
            ->filter(function ($row) use ($windowStart, $windowEnd) {
                $t = $row['last_message_time'] ?? null;
                if (! $t) {
                    return false;
                }
                $ts = Carbon::parse($t);
                return $ts->greaterThanOrEqualTo($windowStart) && $ts->lessThanOrEqualTo($windowEnd);
            })
            ->take($limit)
            ->values();

        $totals['candidates'] = $candidates->count();
        $this->line("[$tag] window=[{$windowStart}, {$windowEnd}] candidates={$totals['candidates']}");

        if ($isDryRun) {
            foreach ($candidates as $row) {
                $this->line(sprintf(
                    '  [dry-run] subscriber=%s last_message_time=%s',
                    $row['from_user_id'] ?? '?',
                    $row['last_message_time'] ?? '?'
                ));
            }
            return $totals;
        }

        foreach ($candidates as $row) {
            $subscriberId = (string) ($row['from_user_id'] ?? '');
            $threadId     = (string) ($row['thread_id'] ?? '');
            if ($subscriberId === '') {
                continue;
            }

            try {
                $logRow = AutoFlowDispatchLog::create([
                    'tenant_id'        => $setting->tenant_id,
                    'channel_id'       => $setting->channel_id,
                    'fb_bot_id'        => $setting->fb_bot_id,
                    'subscriber_id'    => $subscriberId,
                    'postback_id'      => $setting->auto_flow_postback_id,
                    'fb_thread_id'     => $threadId ?: null,
                    'candidate_seen_at'=> Carbon::now(),
                    'status'           => 'pending',
                ]);
            } catch (QueryException $e) {
                if (! $this->isDuplicateKey($e)) {
                    throw $e;
                }

                // A row already exists for this (tenant, channel, subscriber, flow).
                // 'sent' and 'skipped_not_new' are terminal — never retry them.
                // 'failed' (a transient FB/Graph error) and 'pending' (a previous run
                // crashed mid-loop before resolving status) ARE retryable: re-run the
                // fresh-customer check + send IN PLACE on the existing row instead of
                // skipping. No-migration approach — reusing the row means the UNIQUE
                // constraint no longer permanently strands a customer after one bad
                // attempt. Retries continue until the send succeeds (-> 'sent') or the
                // customer is no longer fresh / drops out of the lookback window.
                $logRow = AutoFlowDispatchLog::where('tenant_id', $setting->tenant_id)
                    ->where('channel_id', $setting->channel_id)
                    ->where('subscriber_id', $subscriberId)
                    ->where('postback_id', $setting->auto_flow_postback_id)
                    ->first();

                if (! $logRow || ! in_array($logRow->status, ['failed', 'pending'], true)) {
                    $totals['skipped_dup']++;
                    continue;
                }

                Log::info('DispatchAutoFlow retrying previously-unresolved row', [
                    'channel_id'    => $setting->channel_id,
                    'subscriber_id' => $subscriberId,
                    'prior_status'  => $logRow->status,
                    'prior_error'   => $logRow->error_message,
                    'first_seen_at' => optional($logRow->candidate_seen_at)->toDateTimeString(),
                ]);
                $this->line("  [retry] subscriber={$subscriberId} prior_status={$logRow->status}");
                $totals['retried']++;

                // Re-stamp this attempt; reset to pending so a fresh crash is visible.
                $logRow->update([
                    'status'            => 'pending',
                    'fb_thread_id'      => $threadId ?: $logRow->fb_thread_id,
                    'candidate_seen_at' => Carbon::now(),
                ]);
            }

            try {
                if (! $this->isFreshNewCustomer($setting, $subscriberId)) {
                    $logRow->update(['status' => 'skipped_not_new', 'dispatched_at' => Carbon::now()]);
                    $totals['skipped_not_new']++;
                    continue;
                }

                $result = $sender->send(
                    $setting,
                    $subscriberId,
                    $setting->auto_flow_postback_id,
                    'AutoFlow:dispatcher'
                );

                $isSuccess = ($result['status'] ?? '') === 'success';
                $logRow->update([
                    'status'         => $isSuccess ? 'sent' : 'failed',
                    'steps_total'    => $this->parseSteps($result['message'] ?? '', 'total'),
                    'steps_sent'     => $this->parseSteps($result['message'] ?? '', 'sent'),
                    'error_message'  => $isSuccess ? null : ($result['message'] ?? 'unknown'),
                    'dispatched_at'  => Carbon::now(),
                ]);

                if (! $isSuccess) {
                    // Leave status='failed' so the next scheduled run picks this row
                    // back up via the retry path above (UNIQUE dup -> retry in place).
                    Log::warning('DispatchAutoFlow send failed — will retry on next run', [
                        'channel_id'    => $setting->channel_id,
                        'subscriber_id' => $subscriberId,
                        'message'       => $result['message'] ?? 'unknown',
                    ]);
                }

                $isSuccess ? $totals['sent']++ : $totals['failed']++;
            } catch (\Throwable $e) {
                Log::error('DispatchAutoFlow per-subscriber failure', [
                    'channel_id'    => $setting->channel_id,
                    'subscriber_id' => $subscriberId,
                    'exception'     => $e->getMessage(),
                ]);
                $logRow->update([
                    'status'        => 'failed',
                    'error_message' => 'exception: ' . substr($e->getMessage(), 0, 500),
                    'dispatched_at' => Carbon::now(),
                ]);
                $totals['failed']++;
            }
        }

        return $totals;
    }

    /**
     * Use FB Graph API to confirm the subscriber has exactly one inbound message
     * and zero outbound (page-side) messages — i.e. a brand-new customer that no
     * one has replied to. Both bot and staff replies show as from.id == pageId.
     *
     * Reuses the same two-call pattern as LiveChatController::conversation.
     */
    protected function isFreshNewCustomer(LiveChatSetting $setting, string $subscriberId): bool
    {
        $pageInfo = DB::connection('chatpion_import')
            ->table('facebook_rx_fb_page_info')
            ->where('id', $setting->fb_bot_id)
            ->first(['page_id', 'page_access_token']);

        if (! $pageInfo || ! $pageInfo->page_access_token) {
            return false;
        }
        $token  = $pageInfo->page_access_token;
        $pageId = (string) $pageInfo->page_id;

        $convRes = Http::timeout(10)->get("https://graph.facebook.com/v18.0/{$pageId}/conversations", [
            'user_id'      => $subscriberId,
            'fields'       => 'id',
            'access_token' => $token,
        ]);
        $fbThreadId = $convRes->json()['data'][0]['id'] ?? null;
        if (! $fbThreadId) {
            return false;
        }

        $msgRes = Http::timeout(10)->get("https://graph.facebook.com/v18.0/{$fbThreadId}/messages", [
            'fields'       => 'from,created_time',
            'limit'        => 50,
            'access_token' => $token,
        ]);
        $msgs = $msgRes->json()['data'] ?? [];

        $inbound  = 0;
        $fromPage = 0;
        foreach ($msgs as $m) {
            $fromId = $m['from']['id'] ?? '';
            if ($fromId === $pageId) {
                $fromPage++;
            } elseif ($fromId !== '') {
                $inbound++;
            }
        }

        return $inbound === 1 && $fromPage === 0;
    }

    protected function isDuplicateKey(QueryException $e): bool
    {
        return (int) ($e->errorInfo[1] ?? 0) === 1062;
    }

    /**
     * BotFlowSender returns "Bot flow sent (N step(s))" or
     * "Bot flow partially sent (N/M steps)". Pull the numeric portion out so
     * the dispatch log can record steps_total / steps_sent without changing the
     * service contract.
     */
    protected function parseSteps(string $message, string $which): ?int
    {
        if (preg_match('/\((\d+)\/(\d+) steps?\)/', $message, $m)) {
            return $which === 'sent' ? (int) $m[1] : (int) $m[2];
        }
        if (preg_match('/\((\d+) step/', $message, $m)) {
            return (int) $m[1];
        }
        return null;
    }
}

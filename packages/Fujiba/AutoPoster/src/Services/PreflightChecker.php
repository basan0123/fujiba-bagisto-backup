<?php

namespace Fujiba\AutoPoster\Services;

use Fujiba\AutoPoster\Models\AutoPosterSetting;
use Fujiba\AutoPoster\Models\AutoPosterTemplate;
use Fujiba\FacebookCatalog\Models\FacebookCatalogSetting;
use Fujiba\FacebookCatalog\Services\GraphClient;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pre-flight validation gate for auto posting.
 *
 * Auto posting may only be switched on when every BLOCKING check passes. Checks
 * are deliberately split into blocking vs. warning: a missing page token makes
 * posting impossible, whereas a thin product pool or an inactive ChatPion flow
 * degrades the outcome without breaking it, and should not stop an operator who
 * knows what they are doing.
 *
 * Network checks are best-effort. If Facebook is unreachable we report the
 * check as failed with the reason rather than throwing — pre-flight must always
 * render an answer.
 */
class PreflightChecker
{
    public const STATUS_PASS = 'pass';
    public const STATUS_WARN = 'warn';
    public const STATUS_FAIL = 'fail';

    /** Scopes a page access token needs to publish to a Page feed. */
    public const REQUIRED_SCOPES = ['pages_manage_posts', 'pages_read_engagement'];

    public function __construct(
        protected ProductSelectionService $selection,
    ) {}

    /**
     * Run every check for a channel.
     *
     * @return array{ready:bool,checks:array<int,array>,blocking_failures:int,warnings:int}
     */
    public function run(int $channelId, ?AutoPosterSetting $settings = null): array
    {
        $settings ??= AutoPosterSetting::where('channel_id', $channelId)->first()
            ?? AutoPosterSetting::defaultsFor($channelId);

        $catalog = FacebookCatalogSetting::where('channel_id', $channelId)->first();

        $checks = [];

        $checks[] = $this->checkCatalogConfigured($catalog);
        $checks[] = $this->checkPageSelected($catalog);

        // Only worth calling Graph if we have something to call it with.
        $tokenCheck = $catalog && ! empty($catalog->access_token)
            ? $this->checkTokenAndPermissions($catalog)
            : $this->fail('facebook_token', 'Facebook token', 'No access token stored for this channel.',
                'Add the Page access token on the Facebook Catalog settings page.');
        $checks[] = $tokenCheck;

        $checks[] = $this->checkCatalogConnection($catalog);
        $checks[] = $this->checkProductsAvailable($settings);
        $checks[] = $this->checkSchedule($settings);
        $checks[] = $this->checkCaptionTemplate($settings);
        $checks[] = $this->checkMessengerDestination($settings, $catalog);
        $checks[] = $this->checkChatpionIntegration($channelId);
        $checks[] = $this->checkScheduleEnabled();

        $blocking = collect($checks)->where('status', self::STATUS_FAIL)->where('blocking', true)->count();
        $warnings = collect($checks)->whereIn('status', [self::STATUS_WARN])->count()
            + collect($checks)->where('status', self::STATUS_FAIL)->where('blocking', false)->count();

        return [
            'ready'             => $blocking === 0,
            'checks'            => $checks,
            'blocking_failures' => $blocking,
            'warnings'          => $warnings,
        ];
    }

    /**
     * Persist the last result so the dashboard can show it without re-calling
     * Facebook on every page load.
     */
    public function runAndStore(int $channelId, ?AutoPosterSetting $settings = null): array
    {
        $result = $this->run($channelId, $settings);

        $row = AutoPosterSetting::where('channel_id', $channelId)->first();

        if ($row) {
            $row->update([
                'last_preflight_at'     => now(),
                'last_preflight_passed' => $result['ready'],
                'last_preflight_result' => $result,
            ]);
        }

        return $result;
    }

    protected function checkCatalogConfigured(?FacebookCatalogSetting $catalog): array
    {
        if (! $catalog) {
            return $this->fail('catalog_configured', 'Facebook Catalog connected',
                'This channel has no Facebook Catalog settings.',
                'Configure the channel under Facebook Catalog first — Auto Poster reuses that connection.');
        }

        if (! $catalog->is_active) {
            return $this->fail('catalog_configured', 'Facebook Catalog connected',
                'The Facebook Catalog connection for this channel is disabled.',
                'Enable it on the Facebook Catalog settings page.');
        }

        return $this->pass('catalog_configured', 'Facebook Catalog connected',
            'Catalog ' . $catalog->catalog_id . ' is active.');
    }

    protected function checkPageSelected(?FacebookCatalogSetting $catalog): array
    {
        if (! $catalog || empty($catalog->page_id)) {
            return $this->fail('page_selected', 'Facebook Page selected',
                'No Facebook Page ID is configured for this channel.',
                'Set the Page ID on the Facebook Catalog settings page — posts and the Messenger CTA both need it.');
        }

        return $this->pass('page_selected', 'Facebook Page selected', 'Page ' . $catalog->page_id . '.');
    }

    /**
     * Token validity + publishing scopes.
     *
     * The catalog token was issued for catalog_management; publishing to a Page
     * feed needs pages_manage_posts + pages_read_engagement. A missing scope is
     * reported as an actionable item rather than worked around — the client has
     * to grant it.
     */
    protected function checkTokenAndPermissions(FacebookCatalogSetting $catalog): array
    {
        try {
            $client = new GraphClient($catalog->access_token);
            $debug  = $client->debugToken();

            $data = $debug['data'] ?? null;

            if (! is_array($data)) {
                // Shape we did not expect — report it rather than guessing, so
                // the operator sees exactly what Facebook said.
                return $this->warn('facebook_token', 'Facebook token valid',
                    'Could not read the token introspection response.',
                    'Verify the token manually in Graph API Explorer. Raw keys: '
                    . implode(', ', array_keys($debug ?: [])));
            }

            if (isset($data['is_valid']) && ! $data['is_valid']) {
                $reason = $data['error']['message'] ?? 'Token reported invalid by Facebook.';

                return $this->fail('facebook_token', 'Facebook token valid', $reason,
                    'Regenerate the Page access token and save it on the Facebook Catalog settings page.');
            }

            $scopes  = array_map('strval', $data['scopes'] ?? []);
            $missing = array_values(array_diff(self::REQUIRED_SCOPES, $scopes));

            if (! empty($missing)) {
                return $this->fail('facebook_token', 'Facebook token valid',
                    'Token is valid but missing publishing permission(s): ' . implode(', ', $missing) . '.',
                    'This token can sync the catalog but cannot publish Page posts. Request '
                    . implode(' + ', $missing) . ' for this Page from the client, then save the new token.');
            }

            $expiry = ! empty($data['expires_at'])
                ? ' Expires ' . date('Y-m-d', (int) $data['expires_at']) . '.'
                : ' Long-lived token.';

            return $this->pass('facebook_token', 'Facebook token valid',
                'Token valid with publishing scopes.' . $expiry);
        } catch (Throwable $e) {
            Log::warning('[AutoPoster] pre-flight token check failed', ['error' => $e->getMessage()]);

            return $this->fail('facebook_token', 'Facebook token valid',
                'Could not verify the token: ' . $e->getMessage(),
                'Check the token and that this server can reach graph.facebook.com.');
        }
    }

    protected function checkCatalogConnection(?FacebookCatalogSetting $catalog): array
    {
        if (! $catalog || empty($catalog->catalog_id) || empty($catalog->access_token)) {
            return $this->fail('catalog_reachable', 'Catalog reachable',
                'Catalog ID or token missing.', 'Complete the Facebook Catalog settings first.');
        }

        try {
            $info  = (new GraphClient($catalog->access_token))->getCatalog($catalog->catalog_id);
            $count = $info['product_count'] ?? null;

            return $this->pass('catalog_reachable', 'Catalog reachable',
                ($info['name'] ?? 'Catalog') . ($count !== null ? " — {$count} products." : '.'));
        } catch (Throwable $e) {
            return $this->warn('catalog_reachable', 'Catalog reachable',
                'Could not read the catalog: ' . $e->getMessage(),
                'Posting can still work — the catalog is only used for product data. Verify the catalog ID.');
        }
    }

    protected function checkProductsAvailable(AutoPosterSetting $settings): array
    {
        $count   = $this->selection->eligibleCount($settings);
        $perPost = max(1, (int) $settings->products_per_post);
        $perDay  = max(1, (int) $settings->posts_per_day);
        $needed  = $perPost * $perDay;

        if ($count === 0) {
            return $this->fail('products_available', 'Products available to post',
                'No eligible products. Nothing would be posted.',
                'Approve products for Facebook on the Facebook Catalog → Products page, '
                . 'or relax the repeat cooldown / selection mode.');
        }

        if ($count < $needed) {
            return $this->warn('products_available', 'Products available to post',
                "Only {$count} eligible product(s); a full day needs {$needed}.",
                'Posting will stop early once the pool is exhausted. Approve more products '
                . 'or shorten the repeat cooldown.');
        }

        return $this->pass('products_available', 'Products available to post',
            "{$count} eligible product(s) — enough for " . intdiv($count, $perPost) . ' post(s).');
    }

    protected function checkSchedule(AutoPosterSetting $settings): array
    {
        $perDay = (int) $settings->posts_per_day;

        if ($perDay < 1) {
            return $this->fail('schedule_valid', 'Schedule valid',
                'Posts per day must be at least 1.', 'Set posts per day on the Settings page.');
        }

        $planner  = new SchedulePlanner();
        $explicit = $planner->explicitTimes($settings);

        if (! empty($explicit)) {
            if (count($explicit) !== $perDay) {
                return $this->warn('schedule_valid', 'Schedule valid',
                    count($explicit) . ' posting time(s) set but posts/day is ' . $perDay . '.',
                    'The explicit times win — ' . count($explicit) . ' post(s) per day will go out.');
            }

            return $this->pass('schedule_valid', 'Schedule valid',
                count($explicit) . ' fixed time(s): ' . implode(', ', $explicit) . ' ' . $settings->timezone . '.');
        }

        $start = strtotime($settings->posting_window_start ?: '09:00:00');
        $end   = strtotime($settings->posting_window_end ?: '21:30:00');

        if ($end <= $start) {
            return $this->fail('schedule_valid', 'Schedule valid',
                'Posting window ends before it starts.',
                'Fix the posting window on the Settings page.');
        }

        return $this->pass('schedule_valid', 'Schedule valid',
            "{$perDay} post(s)/day auto-distributed between "
            . substr((string) $settings->posting_window_start, 0, 5) . ' and '
            . substr((string) $settings->posting_window_end, 0, 5) . ' ' . $settings->timezone . '.');
    }

    protected function checkCaptionTemplate(AutoPosterSetting $settings): array
    {
        $hasTemplate = AutoPosterTemplate::where('channel_id', $settings->channel_id)
            ->where('is_active', true)
            ->exists();

        if (! $hasTemplate) {
            return $this->fail('caption_template', 'Caption template available',
                'No active caption template for this channel.',
                'Create one on the Templates page — every post needs a caption.');
        }

        $hasDefault = AutoPosterTemplate::where('channel_id', $settings->channel_id)
            ->where('is_active', true)
            ->where(function ($q) use ($settings) {
                $q->where('is_default', true)->orWhere('id', $settings->default_template_id);
            })->exists();

        if (! $hasDefault) {
            return $this->warn('caption_template', 'Caption template available',
                'Templates exist but none is marked default.',
                'Pick a default template on the Settings page, or the first active one is used.');
        }

        return $this->pass('caption_template', 'Caption template available', 'Default template set.');
    }

    protected function checkMessengerDestination(AutoPosterSetting $settings, ?FacebookCatalogSetting $catalog): array
    {
        if (! empty($settings->messenger_destination)) {
            return $this->pass('messenger_destination', 'Messenger destination set',
                $settings->messenger_destination);
        }

        if ($catalog && ! empty($catalog->page_id)) {
            return $this->pass('messenger_destination', 'Messenger destination set',
                'Derived from Page ID: https://m.me/' . $catalog->page_id);
        }

        return $this->fail('messenger_destination', 'Messenger destination set',
            'No Messenger destination and no Page ID to derive one from.',
            'Set the Page ID (Facebook Catalog settings) or an explicit m.me link on the Settings page. '
            . 'Without it posts carry no Send Message call-to-action.');
    }

    /**
     * ChatPion is what actually greets the customer once they open Messenger.
     * Auto posting still functions without it — the post goes out and the
     * customer reaches the page — so this warns rather than blocks. But a
     * Messenger-first funnel with no bot on the other end is worth flagging
     * loudly, since the whole point is the handoff.
     */
    protected function checkChatpionIntegration(int $channelId): array
    {
        $status = app(LiveChatLocator::class)->status($channelId);

        if (! $status['configured']) {
            return $this->warn('chatpion_active', 'ChatPion Messenger flow active',
                'No LiveChat/ChatPion settings for this channel.',
                'Customers reaching Messenger will not get an automated greeting. '
                . 'Configure it under LiveChat settings.');
        }

        if (! $status['enabled']) {
            return $this->warn('chatpion_active', 'ChatPion Messenger flow active',
                'LiveChat is disabled for this channel.',
                'Enable LiveChat so incoming Messenger conversations are handled.');
        }

        if (! $status['auto_flow']) {
            return $this->warn('chatpion_active', 'ChatPion Messenger flow active',
                'ChatPion auto-flow greeting is off for this channel.',
                'Customers arriving from a post will not be greeted automatically. '
                . 'Turn on the auto-flow and pick a greeting flow in LiveChat settings.');
        }

        return $this->pass('chatpion_active', 'ChatPion Messenger flow active',
            'Auto-flow greeting enabled (flow ' . $status['postback_id'] . ').');
    }

    /**
     * The operator-level kill switch. Not blocking — an admin may legitimately
     * prepare a channel before operations enable the scheduler globally — but
     * without it nothing will actually fire, which must be visible.
     */
    protected function checkScheduleEnabled(): array
    {
        if (! config('autoposter.schedule_enabled')) {
            return $this->warn('scheduler_enabled', 'Scheduler running',
                'The global Auto Poster scheduler is OFF (AUTOPOSTER_SCHEDULE_ENABLED).',
                'Posts will be queued but never published until an operator sets '
                . 'AUTOPOSTER_SCHEDULE_ENABLED=true in .env. Manual "Post Now" still works.');
        }

        return $this->pass('scheduler_enabled', 'Scheduler running', 'Global scheduler enabled.');
    }

    protected function pass(string $key, string $label, string $detail): array
    {
        return compact('key', 'label', 'detail') + ['status' => self::STATUS_PASS, 'blocking' => true, 'fix' => null];
    }

    protected function warn(string $key, string $label, string $detail, string $fix): array
    {
        return compact('key', 'label', 'detail', 'fix') + ['status' => self::STATUS_WARN, 'blocking' => false];
    }

    protected function fail(string $key, string $label, string $detail, string $fix): array
    {
        return compact('key', 'label', 'detail', 'fix') + ['status' => self::STATUS_FAIL, 'blocking' => true];
    }
}

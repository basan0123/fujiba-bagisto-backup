<?php

namespace Fujiba\AutoPoster\Ops\Insights;

/**
 * Pulls lifetime Insights for recently published FB/IG posts (Graph GET only)
 * and snapshots them into fi_performance for $date.
 *
 * Statuses: ok | partial | blocked:no_token | blocked:auth | error:rate_limited | noop
 *
 * Source column carries the platform until WS-B adds a `platform` column:
 * 'meta_api:fb' / 'meta_api:ig' (keeps FB and IG rows for one creative apart
 * under the existing unique (creative_id,date,source)).
 * Posts without an fi_creatives row are counted as `unmapped` and skipped,
 * because fi_performance has no post-id column to key them on.
 */
class MetaInsightsImporter
{
    /** FB post metrics (Graph v25). Configurable: Meta renames these often. */
    public const FB_METRICS = ['post_impressions_unique', 'post_media_view', 'post_clicks', 'post_video_views'];

    public const IG_METRICS = ['reach', 'views', 'total_interactions', 'likes', 'comments', 'shares', 'saved'];

    public function __construct(
        private MetaInsightsTokenProvider $tokens,
        private GraphClient $graph,
        private PublishedPostSource $posts,
        private PerformanceStore $store,
        private array $fbMetrics = self::FB_METRICS,
        private array $igMetrics = self::IG_METRICS,
    ) {}

    public function run(?string $date = null): array
    {
        $date ??= now()->toDateString();
        $token = $this->tokens->token();
        if ($token === null) {
            return ['status' => 'blocked:no_token', 'date' => $date, 'written' => 0, 'unmapped' => 0, 'failed' => 0];
        }

        $written = $unmapped = $failed = 0;
        $errors = [];

        foreach ($this->posts->posts() as $p) {
            if (! $p['creative_id']) {
                $unmapped++;
                continue;
            }
            try {
                $metrics = $p['platform'] === 'ig'
                    ? $this->igMetricsFor($p['external_id'], $token)
                    : $this->fbMetricsFor($p['external_id'], $token);
                $this->store->upsert((int) $p['creative_id'], $date, 'meta_api:'.$p['platform'], $metrics);
                $written++;
            } catch (GraphException $e) {
                if ($e->isAuthError()) {
                    return ['status' => 'blocked:auth', 'date' => $date, 'written' => $written, 'unmapped' => $unmapped, 'failed' => $failed + 1, 'error' => $e->getMessage()];
                }
                if ($e->isRateLimit()) {
                    return ['status' => 'error:rate_limited', 'date' => $date, 'written' => $written, 'unmapped' => $unmapped, 'failed' => $failed + 1];
                }
                $failed++;
                $errors[] = $p['platform'].':'.$p['queue_id'].' '.$e->getMessage();
            }
        }

        $status = $failed ? 'partial' : ($written ? 'ok' : 'noop');

        return compact('status', 'date', 'written', 'unmapped', 'failed') + ['errors' => array_slice($errors, 0, 10)];
    }

    public function fbMetricsFor(string $postId, string $token): array
    {
        $ins = $this->flatten($this->graph->get("{$postId}/insights", ['metric' => implode(',', $this->fbMetrics), 'period' => 'lifetime'], $token));
        $obj = $this->graph->get($postId, ['fields' => 'reactions.summary(true).limit(0),comments.summary(true).limit(0),shares'], $token);

        $reactions = (int) ($obj['reactions']['summary']['total_count'] ?? 0);
        $comments = (int) ($obj['comments']['summary']['total_count'] ?? 0);
        $shares = (int) ($obj['shares']['count'] ?? 0);

        return [
            'reach'       => (int) ($ins['post_impressions_unique'] ?? 0),
            'impressions' => (int) ($ins['post_media_view'] ?? $ins['post_impressions'] ?? 0),
            'video_views' => (int) ($ins['post_video_views'] ?? 0),
            'clicks'      => (int) ($ins['post_clicks'] ?? 0),
            'engagement'  => $reactions + $comments + $shares,
        ];
    }

    public function igMetricsFor(string $mediaId, string $token): array
    {
        $ins = $this->flatten($this->graph->get("{$mediaId}/insights", ['metric' => implode(',', $this->igMetrics)], $token));

        $engagement = $ins['total_interactions']
            ?? (($ins['likes'] ?? 0) + ($ins['comments'] ?? 0) + ($ins['shares'] ?? 0) + ($ins['saved'] ?? 0));

        return [
            'reach'       => (int) ($ins['reach'] ?? 0),
            'impressions' => (int) ($ins['views'] ?? $ins['impressions'] ?? 0),
            'video_views' => (int) ($ins['views'] ?? 0),
            'clicks'      => 0,
            'engagement'  => (int) $engagement,
        ];
    }

    /** Graph insights `data[]` -> [name => value]. Handles values[] and total_value shapes. */
    private function flatten(array $body): array
    {
        $out = [];
        foreach ($body['data'] ?? [] as $m) {
            $v = $m['total_value']['value'] ?? ($m['values'][0]['value'] ?? null);
            if (is_array($v)) {
                $v = array_sum(array_filter($v, 'is_numeric'));
            }
            if (is_numeric($v)) {
                $out[$m['name']] = $v + 0;
            }
        }

        return $out;
    }
}

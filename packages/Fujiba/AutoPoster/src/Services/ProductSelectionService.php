<?php

namespace Fujiba\AutoPoster\Services;

use Carbon\Carbon;
use Fujiba\AutoPoster\Models\AutoPosterSetting;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Models\Channel;

/**
 * Picks which products go into the next post.
 *
 * Eligibility is deliberately layered, cheapest filter first:
 *
 *  1. Approved for Facebook — reuses facebook_catalog_product_sync rather than
 *     inventing a second approval flag. If an operator has not cleared a
 *     product for Meta, the Auto Poster must not post it either.
 *  2. Live on the storefront — same re-check BatchSyncService does at send
 *     time: approval is intent set weeks ago, status/visibility is live state.
 *     A disabled product would post a dead link, which is a policy risk.
 *  3. Has a photo — a multi-photo post without images cannot be built.
 *  4. Not excluded, and past its cooldown.
 *
 * Only after all four does the mode-specific ordering apply.
 */
class ProductSelectionService
{
    public function __construct(
        protected UrlResolver $urls,
    ) {}

    /**
     * Select products for one post.
     *
     * @param  array<int,int>  $excludeProductIds  already used by sibling posts
     *                                             in the same bulk/day batch
     * @return array<int,int>  product ids, in the order they should appear
     */
    public function select(AutoPosterSetting $settings, array $excludeProductIds = [], ?int $limit = null): array
    {
        $limit ??= max(1, (int) $settings->products_per_post);
        $mode    = $settings->selection_mode ?: AutoPosterSetting::MODE_RANDOM;
        $config  = $settings->selection_config ?? [];

        // Manual mode never computes a selection — the admin already chose.
        if ($mode === AutoPosterSetting::MODE_MANUAL) {
            $ids = array_values(array_diff(
                array_map('intval', $config['product_ids'] ?? []),
                $excludeProductIds
            ));

            return array_slice($ids, 0, $limit);
        }

        $query = $this->eligibleQuery($settings, $excludeProductIds);

        $this->applyMode($query, $mode, $config, $settings);

        return $query->limit($limit)->pluck('pf.product_id')->map('intval')->all();
    }

    /**
     * How many products could be posted right now under the current rules.
     * Pre-flight uses this to warn before auto-posting is switched on, and the
     * dashboard shows it so an admin can see the pool draining.
     */
    public function eligibleCount(AutoPosterSetting $settings): int
    {
        if (($settings->selection_mode ?: '') === AutoPosterSetting::MODE_MANUAL) {
            return count($settings->selection_config['product_ids'] ?? []);
        }

        return $this->eligibleQuery($settings, [])->count();
    }

    /**
     * Base eligibility query — returns a builder over product_flat aliased `pf`.
     */
    protected function eligibleQuery(AutoPosterSetting $settings, array $excludeProductIds)
    {
        $channelId   = (int) $settings->channel_id;
        $channelCode = Channel::where('id', $channelId)->value('code') ?: 'default';
        $locale      = $this->catalogLocale($channelId);

        $query = DB::table('product_flat as pf')
            ->select('pf.product_id')
            ->where('pf.channel', $channelCode)
            ->where('pf.locale', $locale)
            ->where('pf.status', 1)
            ->where('pf.visible_individually', 1)
            // (1) approved for Facebook
            ->join('facebook_catalog_product_sync as fs', function ($join) use ($channelId) {
                $join->on('fs.product_id', '=', 'pf.product_id')
                    ->where('fs.channel_id', '=', $channelId)
                    ->where('fs.approved', '=', 1);
            })
            // (3) must have at least one image
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('product_images as pi')
                    ->whereColumn('pi.product_id', 'pf.product_id');
            })
            // (4) not parked by the admin
            ->whereNotExists(function ($q) use ($channelId) {
                $q->select(DB::raw(1))
                    ->from('autoposter_product_history as h')
                    ->whereColumn('h.product_id', 'pf.product_id')
                    ->where('h.channel_id', $channelId)
                    ->where('h.is_excluded', 1);
            });

        if (! empty($excludeProductIds)) {
            $query->whereNotIn('pf.product_id', $excludeProductIds);
        }

        $this->applyCooldown($query, $settings);

        return $query;
    }

    /**
     * Exclude anything posted inside the cooldown window.
     *
     * A product with no history row has never been posted and is always
     * eligible, so this is a NOT EXISTS on a recent post rather than a join —
     * an inner join here would silently make never-posted products ineligible,
     * which is the opposite of the intent.
     */
    protected function applyCooldown($query, AutoPosterSetting $settings): void
    {
        $days = (int) $settings->repeat_cooldown_days;

        if ($days <= 0) {
            return;
        }

        $cutoff    = Carbon::now()->subDays($days);
        $channelId = (int) $settings->channel_id;

        $query->whereNotExists(function ($q) use ($channelId, $cutoff) {
            $q->select(DB::raw(1))
                ->from('autoposter_product_history as hc')
                ->whereColumn('hc.product_id', 'pf.product_id')
                ->where('hc.channel_id', $channelId)
                ->whereNotNull('hc.last_posted_at')
                ->where('hc.last_posted_at', '>=', $cutoff);
        });

        // A product already sitting in an unpublished queue row is spoken for.
        // Without this, every run before the first publish would re-pick the
        // same products (history is only written on success), so a day's worth
        // of scheduled posts could be near-identical.
        $query->whereNotExists(function ($q) use ($channelId) {
            $q->select(DB::raw(1))
                ->from('autoposter_post_products as pp')
                ->join('autoposter_queue as pq', 'pq.id', '=', 'pp.queue_id')
                ->whereColumn('pp.product_id', 'pf.product_id')
                ->where('pq.channel_id', $channelId)
                ->whereIn('pq.status', ['draft', 'scheduled', 'processing']);
        });
    }

    protected function applyMode($query, string $mode, array $config, AutoPosterSetting $settings): void
    {
        $channelId = (int) $settings->channel_id;

        switch ($mode) {
            case AutoPosterSetting::MODE_NEW_ARRIVALS:
                $days = (int) ($config['new_arrival_days'] ?? 30);
                $query->where('pf.created_at', '>=', Carbon::now()->subDays($days))
                    ->orderByDesc('pf.created_at');
                break;

            case AutoPosterSetting::MODE_CATEGORY:
                $categoryIds = array_map('intval', $config['category_ids'] ?? []);
                if (! empty($categoryIds)) {
                    $query->whereExists(function ($q) use ($categoryIds) {
                        $q->select(DB::raw(1))
                            ->from('product_categories as pc')
                            ->whereColumn('pc.product_id', 'pf.product_id')
                            ->whereIn('pc.category_id', $categoryIds);
                    });
                }
                $query->inRandomOrder();
                break;

            case AutoPosterSetting::MODE_PRODUCT_SET:
                // A Facebook product set is a saved filter on the catalog; we
                // hold its membership as retailer ids (Bagisto SKUs — see
                // ProductPayloadBuilder) so no extra Graph call is needed here.
                $retailerIds = array_filter((array) ($config['retailer_ids'] ?? []));
                if (! empty($retailerIds)) {
                    $query->whereExists(function ($q) use ($retailerIds) {
                        $q->select(DB::raw(1))
                            ->from('products as p')
                            ->whereColumn('p.id', 'pf.product_id')
                            ->whereIn('p.sku', $retailerIds);
                    });
                }
                $query->inRandomOrder();
                break;

            case AutoPosterSetting::MODE_NEVER_POSTED:
                $query->whereNotExists(function ($q) use ($channelId) {
                    $q->select(DB::raw(1))
                        ->from('autoposter_product_history as hn')
                        ->whereColumn('hn.product_id', 'pf.product_id')
                        ->where('hn.channel_id', $channelId)
                        ->whereNotNull('hn.last_posted_at');
                })->inRandomOrder();
                break;

            case AutoPosterSetting::MODE_LEAST_RECENTLY_POSTED:
                // Never-posted first (NULL sorts before any date), then oldest.
                $query->leftJoin('autoposter_product_history as hl', function ($join) use ($channelId) {
                    $join->on('hl.product_id', '=', 'pf.product_id')
                        ->where('hl.channel_id', '=', $channelId);
                })->orderByRaw('hl.last_posted_at IS NULL DESC')
                  ->orderBy('hl.last_posted_at', 'asc');
                break;

            case AutoPosterSetting::MODE_RANDOM:
            default:
                $query->inRandomOrder();
                break;
        }
    }

    /**
     * product_flat rows are per (channel, locale). Prefer the locale the
     * catalog integration already writes with so selection and payload agree.
     */
    protected function catalogLocale(int $channelId): string
    {
        $locale = DB::table('facebook_catalog_settings')
            ->where('channel_id', $channelId)
            ->value('default_locale');

        $locale = trim((string) $locale);

        if ($locale === '') {
            return 'ja';
        }

        // facebook_catalog_settings stores Meta-style "ja_JP"; product_flat
        // stores Bagisto locale codes ("ja", "my"). Normalise to the prefix.
        return strtolower(explode('_', $locale)[0]);
    }
}

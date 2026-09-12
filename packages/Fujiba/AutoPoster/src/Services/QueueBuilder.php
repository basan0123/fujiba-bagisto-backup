<?php

namespace Fujiba\AutoPoster\Services;

use Carbon\CarbonInterface;
use Fujiba\AutoPoster\Models\AutoPosterPostProduct;
use Fujiba\AutoPoster\Models\AutoPosterQueue;
use Fujiba\AutoPoster\Models\AutoPosterSetting;
use Fujiba\FacebookCatalog\Models\FacebookCatalogSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates queue rows (draft or scheduled) with their product snapshots.
 *
 * Caption generation is intentionally NOT done here — a row is built first and
 * captioned separately, so a caption-service outage degrades to "post needs a
 * caption" rather than losing the whole schedule.
 */
class QueueBuilder
{
    public function __construct(
        protected ProductSelectionService $selection,
        protected UrlResolver $urls,
    ) {}

    /**
     * Build one post.
     *
     * @param  array<int,int>|null  $productIds  explicit products (manual/bulk);
     *                                          null runs the selection service
     */
    public function build(
        AutoPosterSetting $settings,
        ?CarbonInterface $scheduledAt = null,
        ?array $productIds = null,
        string $origin = AutoPosterQueue::ORIGIN_AUTO,
        array $excludeProductIds = [],
        ?int $createdBy = null,
    ): ?AutoPosterQueue {
        $channelId = (int) $settings->channel_id;

        $productIds ??= $this->selection->select($settings, $excludeProductIds);
        $productIds   = array_values(array_unique(array_map('intval', $productIds)));

        if (empty($productIds)) {
            return null;
        }

        $catalog   = FacebookCatalogSetting::where('channel_id', $channelId)->first();
        $messenger = $this->messengerDestination($settings, $catalog);

        // Resolve photos BEFORE creating the row: a product whose image cannot
        // be resolved would produce a post Facebook rejects at publish time, so
        // drop it here while the post can still be built from the others.
        $rows = [];
        $sort = 0;
        foreach ($productIds as $pid) {
            $image = $this->urls->imageUrl($pid, $channelId);
            if (! $image) {
                continue;
            }

            $rows[] = [
                'product_id'  => $pid,
                'sort_order'  => $sort++,
                'image_url'   => $image,
                'product_url' => $this->urls->productUrlFor($pid, $channelId),
            ];
        }

        if (empty($rows)) {
            return null;
        }

        return DB::transaction(function () use ($settings, $channelId, $rows, $scheduledAt, $origin, $messenger, $createdBy) {
            $post = AutoPosterQueue::create([
                'channel_id'            => $channelId,
                'status'                => $scheduledAt
                    ? AutoPosterQueue::STATUS_SCHEDULED
                    : AutoPosterQueue::STATUS_DRAFT,
                'format'                => $settings->default_format ?: AutoPosterSetting::FORMAT_MULTI_PHOTO,
                'language_mode'         => $settings->default_language,
                'template_id'           => $settings->default_template_id,
                'messenger_destination' => $messenger,
                'messenger_ref'         => $settings->messenger_ref,
                'scheduled_at'          => $scheduledAt,
                'origin'                => $origin,
                'created_by'            => $createdBy,
                'idempotency_key'       => (string) Str::uuid(),
            ]);

            foreach ($rows as $row) {
                AutoPosterPostProduct::create($row + ['queue_id' => $post->id]);
            }

            return $post->load('products');
        });
    }

    /**
     * Plan a whole day: build one post per upcoming slot, keeping products
     * distinct across the day's posts so the same items don't appear twice.
     *
     * @return array<int,AutoPosterQueue>
     */
    public function planDay(AutoPosterSetting $settings, SchedulePlanner $planner, ?CarbonInterface $day = null): array
    {
        $slots = $planner->upcomingSlotsForDay($settings, $day);

        if (empty($slots)) {
            return [];
        }

        $created = [];
        $used    = [];

        foreach ($slots as $slot) {
            if ($this->slotAlreadyFilled($settings, $slot)) {
                continue;
            }

            $post = $this->build($settings, $slot, null, AutoPosterQueue::ORIGIN_AUTO, $used);

            if (! $post) {
                // Eligible pool exhausted — stop rather than emitting empty
                // posts. The dashboard surfaces the shortfall via eligibleCount.
                break;
            }

            $used = array_merge($used, $post->products->pluck('product_id')->all());
            $created[] = $post;
        }

        return $created;
    }

    /**
     * Split a product list into posts of products_per_post, spread over the
     * next N days' slots. Used by Bulk Schedule.
     *
     * @param  array<int,int>  $productIds
     * @return array<int,AutoPosterQueue>
     */
    public function planBulk(
        AutoPosterSetting $settings,
        SchedulePlanner $planner,
        array $productIds,
        CarbonInterface $startDay,
        int $maxDays = 14,
        ?int $createdBy = null,
    ): array {
        $perPost = max(1, (int) $settings->products_per_post);
        $chunks  = array_chunk(array_values(array_unique(array_map('intval', $productIds))), $perPost);

        $created = [];
        $day     = $startDay->copy();
        $slots   = [];
        $daysUsed = 0;

        foreach ($chunks as $chunk) {
            // Refill the slot list a day at a time until we have somewhere to
            // put this chunk, or we hit the horizon.
            while (empty($slots) && $daysUsed < $maxDays) {
                $slots = $planner->upcomingSlotsForDay($settings, $day);
                $slots = array_values(array_filter(
                    $slots,
                    fn ($s) => ! $this->slotAlreadyFilled($settings, $s)
                ));
                $day->addDay();
                $daysUsed++;
            }

            if (empty($slots)) {
                break; // horizon reached; remaining chunks are not scheduled
            }

            $slot = array_shift($slots);
            $post = $this->build(
                $settings,
                $slot,
                $chunk,
                AutoPosterQueue::ORIGIN_BULK,
                [],
                $createdBy
            );

            if ($post) {
                $created[] = $post;
            }
        }

        return $created;
    }

    /**
     * Has a post already been scheduled for (about) this slot?
     *
     * Guards re-planning the same day twice — the planner is idempotent per
     * slot, so an admin hitting "generate schedule" repeatedly does not stack
     * duplicate posts on the same minute. A 60s window absorbs jitter drift
     * between runs.
     */
    protected function slotAlreadyFilled(AutoPosterSetting $settings, CarbonInterface $slot): bool
    {
        return AutoPosterQueue::where('channel_id', $settings->channel_id)
            ->whereIn('status', [
                AutoPosterQueue::STATUS_SCHEDULED,
                AutoPosterQueue::STATUS_PROCESSING,
                AutoPosterQueue::STATUS_PUBLISHED,
            ])
            ->whereBetween('scheduled_at', [
                $slot->copy()->subSeconds(60),
                $slot->copy()->addSeconds(60),
            ])
            ->exists();
    }

    /**
     * Where the Messenger CTA points. Admin override wins; otherwise the page
     * id from the existing Facebook Catalog settings — never a second copy of
     * the page id in our own table.
     */
    protected function messengerDestination(AutoPosterSetting $settings, ?FacebookCatalogSetting $catalog): ?string
    {
        if (! empty($settings->messenger_destination)) {
            return $settings->messenger_destination;
        }

        if ($catalog && ! empty($catalog->page_id)) {
            return $this->urls->messengerUrl($catalog->page_id, $settings->messenger_ref);
        }

        return null;
    }
}

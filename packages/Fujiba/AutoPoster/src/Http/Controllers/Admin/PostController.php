<?php

namespace Fujiba\AutoPoster\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Fujiba\AutoPoster\Models\AutoPosterPostProduct;
use Fujiba\AutoPoster\Models\AutoPosterQueue;
use Fujiba\AutoPoster\Models\AutoPosterSetting;
use Fujiba\AutoPoster\Models\AutoPosterTemplate;
use Fujiba\AutoPoster\Services\CaptionGenerator;
use Fujiba\AutoPoster\Services\PostPublisher;
use Fujiba\AutoPoster\Services\ProductSelectionService;
use Fujiba\AutoPoster\Services\QueueBuilder;
use Fujiba\AutoPoster\Services\SchedulePlanner;
use Fujiba\AutoPoster\Services\UrlResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Models\Channel;

class PostController extends Controller
{
    public function __construct(
        protected QueueBuilder $builder,
        protected CaptionGenerator $captions,
        protected PostPublisher $publisher,
        protected ProductSelectionService $selection,
        protected SchedulePlanner $planner,
        protected UrlResolver $urls,
    ) {}

    /* ---------------------------------------------------------------- queue */

    public function queue(Request $request)
    {
        $channels  = Channel::all();
        $channelId = (int) $request->input('channel_id') ?: $channels->first()?->id;
        $status    = $request->input('status');

        $query = AutoPosterQueue::with('products')
            ->where('channel_id', $channelId)
            ->orderByRaw('COALESCE(scheduled_at, created_at) DESC');

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        return view('autoposter::admin.queue', [
            'channels'  => $channels,
            'channelId' => $channelId,
            'status'    => $status ?: 'all',
            'posts'     => $query->paginate(20)->withQueryString(),
            'counts'    => AutoPosterQueue::where('channel_id', $channelId)
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')->pluck('total', 'status')->all(),
        ]);
    }

    public function history(Request $request)
    {
        $channels  = Channel::all();
        $channelId = (int) $request->input('channel_id') ?: $channels->first()?->id;

        return view('autoposter::admin.history', [
            'channels'  => $channels,
            'channelId' => $channelId,
            'posts'     => AutoPosterQueue::with('products')
                ->where('channel_id', $channelId)
                ->whereIn('status', [
                    AutoPosterQueue::STATUS_PUBLISHED,
                    AutoPosterQueue::STATUS_FAILED,
                    AutoPosterQueue::STATUS_CANCELLED,
                ])
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    /* --------------------------------------------------------- manual create */

    public function create(Request $request)
    {
        $channels  = Channel::all();
        $channelId = (int) $request->input('channel_id') ?: $channels->first()?->id;

        $settings = AutoPosterSetting::where('channel_id', $channelId)->first()
            ?? AutoPosterSetting::defaultsFor($channelId);

        // Suggest products using the configured rules so the admin starts from
        // a sensible set rather than an empty picker.
        $suggested = $this->selection->select($settings, [], 24);

        return view('autoposter::admin.create', [
            'channels'   => $channels,
            'channelId'  => $channelId,
            'settings'   => $settings,
            'templates'  => AutoPosterTemplate::where('channel_id', $channelId)
                ->where('is_active', true)->orderByDesc('is_default')->get(),
            'candidates' => $this->productCards($suggested, $channelId),
            'aiAvailable'=> $this->captions->isAiAvailable(),
        ]);
    }

    /**
     * Build a draft from the manual picker, generate its caption, then send the
     * admin to the preview screen.
     */
    public function store(Request $request)
    {
        $channelId  = (int) $request->input('channel_id');
        $productIds = array_values(array_filter(array_map('intval', (array) $request->input('product_ids', []))));

        if (empty($productIds)) {
            return back()->with('error', 'Select at least one product.');
        }

        $settings = AutoPosterSetting::where('channel_id', $channelId)->first()
            ?? AutoPosterSetting::defaultsFor($channelId);

        // The picker's own ordering is the carousel order.
        $post = $this->builder->build(
            $settings,
            null,
            $productIds,
            AutoPosterQueue::ORIGIN_MANUAL,
            [],
            auth()->guard('admin')->id()
        );

        if (! $post) {
            return back()->with('error',
                'Could not build the post — none of the selected products has a usable image.');
        }

        if ($format = $request->input('format')) {
            $post->update(['format' => $format]);
        }

        if ($templateId = (int) $request->input('template_id')) {
            $post->update(['template_id' => $templateId]);
        }

        $generated = $this->captions->generate($post->fresh());
        $post->update([
            'caption'      => $generated['caption'],
            'caption_meta' => $generated['meta'],
        ]);

        return redirect()
            ->route('admin.autoposter.posts.edit', $post->id)
            ->with('success', 'Draft created. Review the preview, then publish or schedule.');
    }

    /* ------------------------------------------------------------ edit/preview */

    public function edit(int $id)
    {
        $post = AutoPosterQueue::with('products')->findOrFail($id);

        return view('autoposter::admin.edit', [
            'post'        => $post,
            'products'    => $this->productCards(
                $post->products->pluck('product_id')->all(),
                $post->channel_id,
                $post
            ),
            'templates'   => AutoPosterTemplate::where('channel_id', $post->channel_id)
                ->where('is_active', true)->orderByDesc('is_default')->get(),
            'aiAvailable' => $this->captions->isAiAvailable(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $post = AutoPosterQueue::findOrFail($id);

        if ($post->isPublished()) {
            return back()->with('error', 'This post is already published and cannot be edited.');
        }

        $data = $request->validate([
            'caption'               => ['required', 'string', 'max:60000'],
            'format'                => ['required', 'string'],
            'template_id'           => ['nullable', 'integer'],
            'messenger_destination' => ['nullable', 'url', 'max:255'],
            'scheduled_at'          => ['nullable', 'date'],
        ]);

        $post->update([
            'caption'               => $data['caption'],
            'format'                => $data['format'],
            'template_id'           => $data['template_id'] ?: null,
            'messenger_destination' => $data['messenger_destination'] ?: $post->messenger_destination,
            'scheduled_at'          => $data['scheduled_at'] ? Carbon::parse($data['scheduled_at']) : $post->scheduled_at,
        ]);

        return back()->with('success', 'Post updated.');
    }

    /**
     * Persist drag-and-drop carousel order.
     */
    public function reorder(Request $request, int $id)
    {
        $post = AutoPosterQueue::findOrFail($id);

        if ($post->isPublished()) {
            return response()->json(['status' => 'error', 'message' => 'Already published'], 422);
        }

        $order = (array) $request->input('order', []);

        DB::transaction(function () use ($order, $post) {
            foreach (array_values($order) as $i => $productId) {
                AutoPosterPostProduct::where('queue_id', $post->id)
                    ->where('product_id', (int) $productId)
                    ->update(['sort_order' => $i]);
            }
        });

        return response()->json(['status' => 'success']);
    }

    public function removeProduct(Request $request, int $id)
    {
        $post = AutoPosterQueue::findOrFail($id);

        if ($post->isPublished()) {
            return back()->with('error', 'This post is already published.');
        }

        if ($post->products()->count() <= 1) {
            return back()->with('error', 'A post needs at least one product.');
        }

        AutoPosterPostProduct::where('queue_id', $post->id)
            ->where('product_id', (int) $request->input('product_id'))
            ->delete();

        return back()->with('success', 'Product removed from the post.');
    }

    public function regenerateCaption(int $id)
    {
        $post = AutoPosterQueue::with('products')->findOrFail($id);

        if ($post->isPublished()) {
            return back()->with('error', 'This post is already published.');
        }

        $generated = $this->captions->generate($post);

        $post->update([
            'caption'      => $generated['caption'],
            'caption_meta' => $generated['meta'],
        ]);

        return back()->with('success', $generated['source'] === 'template'
            ? 'Caption regenerated from the template. (AI generation is not configured yet.)'
            : 'Caption regenerated.');
    }

    /* --------------------------------------------------------------- publish */

    public function publishNow(int $id)
    {
        $post = AutoPosterQueue::with('products')->findOrFail($id);

        if ($post->isPublished()) {
            return back()->with('error', 'This post has already been published.');
        }

        if (empty(trim((string) $post->caption))) {
            return back()->with('error', 'Add a caption before publishing.');
        }

        // claim() only matches 'scheduled', so a draft is promoted first. This
        // is the same path the worker uses — no bypass of the locking.
        if ($post->status !== AutoPosterQueue::STATUS_SCHEDULED) {
            AutoPosterQueue::where('id', $post->id)
                ->whereNotIn('status', [AutoPosterQueue::STATUS_PUBLISHED, AutoPosterQueue::STATUS_PROCESSING])
                ->update(['status' => AutoPosterQueue::STATUS_SCHEDULED]);
        }

        $token = $this->publisher->claim($post->fresh());

        if (! $token) {
            return back()->with('error',
                'Could not claim this post — it may already be publishing. Refresh and check.');
        }

        $result = $this->publisher->publish($post->fresh(), $token);

        return back()->with(
            $result['status'] === 'success' ? 'success' : 'error',
            $result['status'] === 'success'
                ? 'Published to Facebook (post ' . ($result['post_id'] ?? '?') . ').'
                : 'Publish failed: ' . $result['message']
        );
    }

    public function schedule(Request $request, int $id)
    {
        $post = AutoPosterQueue::findOrFail($id);

        if ($post->isPublished()) {
            return back()->with('error', 'This post is already published.');
        }

        $data = $request->validate(['scheduled_at' => ['required', 'date', 'after:now']]);

        $post->update([
            'status'       => AutoPosterQueue::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::parse($data['scheduled_at']),
        ]);

        return back()->with('success', 'Scheduled for ' . $post->scheduled_at->format('Y-m-d H:i') . '.');
    }

    public function retry(int $id)
    {
        $post = AutoPosterQueue::findOrFail($id);

        if ($post->isPublished()) {
            return back()->with('error', 'This post is already published.');
        }

        $post->update([
            'status'        => AutoPosterQueue::STATUS_SCHEDULED,
            'scheduled_at'  => now(),
            'next_retry_at' => null,
            'lock_token'    => null,
            'locked_at'     => null,
        ]);

        return back()->with('success', 'Queued for retry — it will publish on the next dispatch run.');
    }

    public function cancel(int $id)
    {
        $post = AutoPosterQueue::findOrFail($id);

        if (! $post->isCancellable()) {
            return back()->with('error', 'Only draft or scheduled posts can be cancelled.');
        }

        $post->update(['status' => AutoPosterQueue::STATUS_CANCELLED]);

        return back()->with('success', 'Post cancelled.');
    }

    public function destroy(int $id)
    {
        $post = AutoPosterQueue::findOrFail($id);

        // Published posts are the audit trail of what went out to customers;
        // deleting one would lose the Facebook post id and its history link.
        if ($post->isPublished()) {
            return back()->with('error',
                'Published posts cannot be deleted — they are the record of what went live.');
        }

        $post->delete();

        return redirect()
            ->route('admin.autoposter.queue')
            ->with('success', 'Post deleted.');
    }

    /**
     * Copy a post into a fresh draft — "duplicate" and "post again" both land
     * here. The copy never inherits the original's Facebook identity.
     */
    public function duplicate(int $id)
    {
        $post = AutoPosterQueue::with('products')->findOrFail($id);

        $copy = $this->builder->build(
            AutoPosterSetting::where('channel_id', $post->channel_id)->first()
                ?? AutoPosterSetting::defaultsFor($post->channel_id),
            null,
            $post->products->pluck('product_id')->all(),
            AutoPosterQueue::ORIGIN_MANUAL,
            [],
            auth()->guard('admin')->id()
        );

        if (! $copy) {
            return back()->with('error', 'Could not duplicate — product images are no longer available.');
        }

        $copy->update([
            'caption'       => $post->caption,
            'format'        => $post->format,
            'template_id'   => $post->template_id,
            'language_mode' => $post->language_mode,
        ]);

        return redirect()
            ->route('admin.autoposter.posts.edit', $copy->id)
            ->with('success', 'Duplicated as a new draft.');
    }

    /* ------------------------------------------------------------------ bulk */

    public function bulkForm(Request $request)
    {
        $channels  = Channel::all();
        $channelId = (int) $request->input('channel_id') ?: $channels->first()?->id;

        $settings = AutoPosterSetting::where('channel_id', $channelId)->first()
            ?? AutoPosterSetting::defaultsFor($channelId);

        return view('autoposter::admin.bulk', [
            'channels'      => $channels,
            'channelId'     => $channelId,
            'settings'      => $settings,
            'candidates'    => $this->productCards($this->selection->select($settings, [], 60), $channelId),
            'eligibleCount' => $this->selection->eligibleCount($settings),
        ]);
    }

    public function bulkStore(Request $request)
    {
        $channelId  = (int) $request->input('channel_id');
        $productIds = array_values(array_filter(array_map('intval', (array) $request->input('product_ids', []))));

        if (empty($productIds)) {
            return back()->with('error', 'Select at least one product.');
        }

        $settings = AutoPosterSetting::where('channel_id', $channelId)->first();

        if (! $settings) {
            return back()->with('error', 'Configure the channel on the Settings page first.');
        }

        $startDay = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'), $settings->timezone ?: 'Asia/Tokyo')
            : Carbon::tomorrow($settings->timezone ?: 'Asia/Tokyo');

        $posts = $this->builder->planBulk(
            $settings,
            $this->planner,
            $productIds,
            $startDay,
            max(1, (int) $request->input('max_days', 14)),
            auth()->guard('admin')->id()
        );

        if (empty($posts)) {
            return back()->with('error',
                'No posts were scheduled — every slot in the window is already taken. '
                . 'Try a later start date or a longer window.');
        }

        // Caption each one so the admin can review real text, not placeholders.
        foreach ($posts as $post) {
            $generated = $this->captions->generate($post->fresh());
            $post->update(['caption' => $generated['caption'], 'caption_meta' => $generated['meta']]);
        }

        $unscheduled = count($productIds) - collect($posts)->sum(fn ($p) => $p->products->count());

        return redirect()
            ->route('admin.autoposter.queue', ['channel_id' => $channelId, 'status' => 'scheduled'])
            ->with('success', count($posts) . ' post(s) scheduled.'
                . ($unscheduled > 0 ? " {$unscheduled} product(s) did not fit in the window." : '')
                . ' Review each one before it goes live.');
    }

    /* --------------------------------------------------------------- helpers */

    /**
     * Product cards for the pickers and preview — name, thumbnail, price.
     *
     * @param  array<int,int>  $productIds
     * @return array<int,array>
     */
    protected function productCards(array $productIds, int $channelId, ?AutoPosterQueue $post = null): array
    {
        if (empty($productIds)) {
            return [];
        }

        $locale = DB::table('facebook_catalog_settings')
            ->where('channel_id', $channelId)
            ->value('default_locale');
        $locale = strtolower(explode('_', (string) ($locale ?: 'ja'))[0]);

        $flat = DB::table('product_flat')
            ->whereIn('product_id', $productIds)
            ->where('locale', $locale)
            ->get(['product_id', 'name', 'price', 'special_price', 'sku'])
            ->keyBy('product_id');

        // Preserve the caller's order (carousel order for a post, relevance for
        // a picker) rather than the database's.
        $ordering = $post
            ? $post->products->sortBy('sort_order')->pluck('product_id')->all()
            : $productIds;

        $overrides = $post
            ? $post->products->keyBy('product_id')
            : collect();

        $cards = [];
        foreach ($ordering as $pid) {
            $row = $flat->get($pid);

            $cards[] = [
                'product_id' => $pid,
                'name'       => $row->name ?? ('Product ' . $pid),
                'sku'        => $row->sku ?? '',
                'price'      => $row->special_price && $row->special_price < $row->price
                    ? $row->special_price
                    : ($row->price ?? null),
                'image'      => $overrides->has($pid)
                    ? $overrides[$pid]->effectiveImageUrl()
                    : $this->urls->imageUrl($pid, $channelId),
            ];
        }

        return $cards;
    }
}

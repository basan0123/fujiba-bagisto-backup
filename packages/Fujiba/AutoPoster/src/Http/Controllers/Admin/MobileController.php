<?php

namespace Fujiba\AutoPoster\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Fujiba\AutoPoster\Models\AutoPosterPostProduct;
use Fujiba\AutoPoster\Models\AutoPosterQueue;
use Fujiba\AutoPoster\Models\AutoPosterSetting;
use Fujiba\AutoPoster\Models\AutoPosterTemplate;
use Fujiba\AutoPoster\Services\CaptionGenerator;
use Fujiba\AutoPoster\Services\CollectionPostBuilder;
use Fujiba\AutoPoster\Services\PostCoverRenderer;
use Fujiba\AutoPoster\Services\PostPublisher;
use Fujiba\AutoPoster\Services\ProductSelectionService;
use Fujiba\AutoPoster\Services\UrlResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Phone-first planner: a week of scheduled posts by day, each editable in a
 * bottom sheet (caption, time, new caption, new products, skip, post now).
 * JSON endpoints so the page never reloads on a slow connection.
 */
class MobileController extends Controller
{
    private const CHANNEL_ID = 1;

    public function __construct(
        protected CaptionGenerator $captions,
        protected PostCoverRenderer $covers,
        protected PostPublisher $publisher,
        protected ProductSelectionService $selection,
        protected UrlResolver $urls,
    ) {}

    public function index()
    {
        return view('autoposter::admin.mobile', [
            'enabled' => (bool) AutoPosterSetting::where('channel_id', self::CHANNEL_ID)->value('auto_posting_enabled'),
        ]);
    }

    public function posts(): JsonResponse
    {
        $tz    = 'Asia/Tokyo';
        $from  = Carbon::now($tz)->startOfDay()->subDay();
        $posts = AutoPosterQueue::with('products')
            ->where('channel_id', self::CHANNEL_ID)
            ->whereIn('status', ['scheduled', 'draft', 'failed', 'processing', 'published'])
            ->where('scheduled_at', '>=', $from->copy()->utc())
            ->orderBy('scheduled_at')
            ->limit(200)
            ->get();

        $names = DB::table('product_flat')
            ->whereIn('product_id', $posts->flatMap->products->pluck('product_id')->unique())
            ->where('channel', 'default')->where('locale', 'ja')
            ->pluck('name', 'product_id');

        return response()->json(['data' => $posts->map(fn ($p) => $this->row($p, $names))]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $post = $this->editable($id);
        $data = $request->validate([
            'caption'      => ['required', 'string', 'max:60000'],
            'scheduled_at' => ['required', 'date'],
        ]);

        $post->update([
            'caption'      => $data['caption'],
            'scheduled_at' => Carbon::parse($data['scheduled_at'], 'Asia/Tokyo'),
            'status'       => $post->status === AutoPosterQueue::STATUS_FAILED ? AutoPosterQueue::STATUS_SCHEDULED : $post->status,
        ]);

        return $this->one($post);
    }

    /** New caption from a different random template. */
    public function recaption(int $id): JsonResponse
    {
        $post = $this->editable($id);

        if (CollectionPostBuilder::isCollection($post)) {
            app(CollectionPostBuilder::class)->refresh($post);

            return $this->one($post->fresh());
        }

        $next = AutoPosterTemplate::where('channel_id', $post->channel_id)->where('is_active', true)
            ->where('id', '<>', (int) $post->template_id)->pluck('id')->shuffle()->first();

        if ($next) {
            $post->update(['template_id' => $next]);
        }

        $post->update(['caption' => $this->captions->generate($post->fresh('products'))['caption']]);

        return $this->one($post);
    }

    /** Swap in a fresh random set of in-stock products, then caption + cover. */
    public function shuffle(int $id): JsonResponse
    {
        $post     = $this->editable($id);

        // Collection posts shuffle to the next same-pattern / same-colour group.
        if (CollectionPostBuilder::isCollection($post)) {
            $kind = ($post->caption_meta['collection'] ?? 'pattern') === 'pattern' ? 'new' : 'pattern';

            return app(CollectionPostBuilder::class)->fill($post, $kind)
                ? $this->one($post->fresh('products'))
                : response()->json(['message' => 'No other collection is free to use right now.'], 422);
        }

        $settings = AutoPosterSetting::where('channel_id', $post->channel_id)->firstOrFail();
        $current  = $post->products()->pluck('product_id')->all();
        $picked   = $this->selection->select($settings, $current);

        if (empty($picked)) {
            return response()->json(['message' => 'No other in-stock products are free to use right now.'], 422);
        }

        DB::transaction(function () use ($post, $picked) {
            $post->products()->delete();
            $sort = 0;

            foreach ($picked as $pid) {
                $image = $this->urls->imageUrl((int) $pid, (int) $post->channel_id);
                if (! $image) {
                    continue;
                }

                AutoPosterPostProduct::create([
                    'queue_id'    => $post->id,
                    'product_id'  => (int) $pid,
                    'sort_order'  => $sort++,
                    'image_url'   => $image,
                    'product_url' => $this->urls->productUrlFor((int) $pid, (int) $post->channel_id),
                ]);
            }
        });

        $post->load('products');
        $post->update(['caption' => $this->captions->generate($post)['caption']]);
        $this->covers->render($post);

        return $this->one($post);
    }

    public function skip(int $id): JsonResponse
    {
        $post = $this->editable($id);
        $post->update(['status' => AutoPosterQueue::STATUS_CANCELLED]);

        return response()->json(['ok' => true]);
    }

    public function publish(int $id): JsonResponse
    {
        $post = $this->editable($id);

        AutoPosterQueue::where('id', $post->id)
            ->whereNotIn('status', [AutoPosterQueue::STATUS_PUBLISHED, AutoPosterQueue::STATUS_PROCESSING])
            ->update(['status' => AutoPosterQueue::STATUS_SCHEDULED]);

        $token = $this->publisher->claim($post->fresh());

        if (! $token) {
            return response()->json(['message' => 'This post is already being published. Refresh in a moment.'], 409);
        }

        $result = $this->publisher->publish($post->fresh(), $token);

        if ($result['status'] !== 'success') {
            return response()->json(['message' => $result['message'] ?? 'Publishing failed'], 502);
        }

        return $this->one($post);
    }

    public function toggle(Request $request): JsonResponse
    {
        $on = $request->boolean('enabled');
        AutoPosterSetting::where('channel_id', self::CHANNEL_ID)->update(['auto_posting_enabled' => $on]);

        return response()->json(['enabled' => $on]);
    }

    private function editable(int $id): AutoPosterQueue
    {
        $post = AutoPosterQueue::with('products')->where('channel_id', self::CHANNEL_ID)->findOrFail($id);

        abort_if($post->isPublished() || $post->status === AutoPosterQueue::STATUS_PROCESSING, 422, 'This post is already published.');

        return $post;
    }

    private function one(AutoPosterQueue $post): JsonResponse
    {
        $post = $post->fresh('products');
        $names = DB::table('product_flat')
            ->whereIn('product_id', $post->products->pluck('product_id'))
            ->where('channel', 'default')->where('locale', 'ja')
            ->pluck('name', 'product_id');

        return response()->json(['data' => $this->row($post, $names)]);
    }

    private function row(AutoPosterQueue $p, $names): array
    {
        $at = optional($p->scheduled_at)->copy()?->setTimezone('Asia/Tokyo');

        return [
            'id'        => $p->id,
            'status'    => $p->status,
            'day'       => $at?->format('Y-m-d'),
            'time'      => $at?->format('H:i'),
            'local'     => $at?->format('Y-m-d\TH:i'),
            'caption'   => (string) $p->caption,
            'cover'     => $this->coverPath($p),
            'products'  => $p->products->sortBy('sort_order')->map(fn ($i) => [
                'name'  => $names[$i->product_id] ?? ('#'.$i->product_id),
                'image' => $i->effectiveImageUrl(),
            ])->values(),
            'permalink' => $p->facebook_permalink,
            'error'     => $p->status === AutoPosterQueue::STATUS_FAILED ? $p->last_error : null,
        ];
    }

    /** Relative URL so it loads on whatever host the admin is using. */
    private function coverPath(AutoPosterQueue $p): ?string
    {
        $file = $this->covers->file($p);

        return is_file($file)
            ? '/fb-posts/auto/'.basename(dirname($file)).'/'.$p->id.'.jpg?v='.filemtime($file)
            : null;
    }
}

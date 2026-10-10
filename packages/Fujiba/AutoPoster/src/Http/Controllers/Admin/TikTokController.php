<?php

namespace Fujiba\AutoPoster\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Fujiba\AutoPoster\Models\AutoPosterQueue;
use Fujiba\AutoPoster\Models\TikTokAccount;
use Fujiba\AutoPoster\Models\TikTokPost;
use Fujiba\AutoPoster\Services\TikTokClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;
use Webkul\Core\Models\Channel;

/**
 * TikTok connect + manual-confirm posting. TikTok's Content Sharing
 * Guidelines require the user to see the account, pick privacy (no default),
 * opt in to interactions, declare commercial content and consent before
 * anything is posted — so this screen is the only path that posts.
 */
class TikTokController extends Controller
{
    public function __construct(protected TikTokClient $client) {}

    public function index(Request $request)
    {
        if ($redirect = $this->onRedirectHost($request)) {
            return $redirect;
        }

        $channels  = Channel::all();
        $channelId = (int) $request->input('channel_id') ?: $channels->first()?->id;

        $posts = AutoPosterQueue::where('channel_id', $channelId)
            ->whereNotNull('instagram_payload')
            ->orderByDesc('published_at')
            ->limit(30)
            ->get();

        return view('autoposter::admin.tiktok.index', [
            'channels'  => $channels,
            'channelId' => $channelId,
            'account'   => TikTokAccount::where('channel_id', $channelId)->first(),
            'posts'     => $posts,
            'sent'      => TikTokPost::where('channel_id', $channelId)->latest('id')->limit(20)->get(),
        ]);
    }

    public function connect(Request $request)
    {
        if ($redirect = $this->onRedirectHost($request)) {
            return $redirect;
        }

        $state = Str::random(40);
        $request->session()->put('tiktok_oauth', ['state' => $state, 'channel_id' => (int) $request->input('channel_id', 1)]);

        return redirect()->away($this->client->authorizeUrl($state));
    }

    public function callback(Request $request)
    {
        $saved = $request->session()->pull('tiktok_oauth');
        $back  = route('admin.autoposter.tiktok', ['channel_id' => $saved['channel_id'] ?? 1]);

        if (! $saved || ! hash_equals($saved['state'], (string) $request->input('state'))) {
            return redirect($back)->with('error', 'TikTok login expired or was tampered with. Please try again.');
        }

        if ($request->filled('error')) {
            return redirect($back)->with('error', 'TikTok login cancelled: '.$request->input('error_description', $request->input('error')));
        }

        try {
            $account = $this->client->connect($saved['channel_id'], (string) $request->input('code'));
        } catch (Throwable $e) {
            return redirect($back)->with('error', $e->getMessage());
        }

        return redirect($back)->with('success', 'Connected TikTok account '.($account->display_name ?: $account->open_id).'.');
    }

    public function disconnect(Request $request)
    {
        $channelId = (int) $request->input('channel_id');

        if ($account = TikTokAccount::where('channel_id', $channelId)->first()) {
            try {
                $this->client->disconnect($account);
            } catch (Throwable) {
                // Row is deleted in finally; a failed revoke only leaves TikTok's side stale.
            }
        }

        return redirect()->route('admin.autoposter.tiktok', ['channel_id' => $channelId])->with('success', 'TikTok disconnected.');
    }

    public function compose(Request $request, int $id)
    {
        $post    = $this->findPost($request, $id);
        $account = TikTokAccount::where('channel_id', $post->channel_id)->first();

        if (! $account) {
            return redirect()->route('admin.autoposter.tiktok', ['channel_id' => $post->channel_id])->with('error', 'Connect TikTok first.');
        }

        try {
            $creator = $this->client->creatorInfo($account);
        } catch (Throwable $e) {
            return redirect()->route('admin.autoposter.tiktok', ['channel_id' => $post->channel_id])->with('error', $e->getMessage());
        }

        $media = $this->media($post);

        return view('autoposter::admin.tiktok.compose', [
            'channelId' => $post->channel_id,
            'post'      => $post,
            'account'   => $account,
            'creator'   => $creator,
            'video'     => $media['video'],
            'images'    => $media['images'],
            'caption'   => Str::limit((string) ($post->instagram_payload['message'] ?? $post->caption), 2150, ''),
        ]);
    }

    public function publish(Request $request, int $id)
    {
        $post    = $this->findPost($request, $id);
        $account = TikTokAccount::where('channel_id', $post->channel_id)->firstOrFail();
        $media   = $this->media($post);

        $data = $request->validate([
            'mode'          => ['required', 'in:direct,draft'],
            'caption'       => ['nullable', 'string', 'max:2200'],
            'privacy_level' => ['required_if:mode,direct', 'nullable', 'string'],
            'consent'       => ['required_if:mode,direct', 'nullable', 'accepted'],
        ]);

        $direct = $data['mode'] === 'direct';
        $brand  = $request->boolean('disclose') && ($request->boolean('your_brand') || $request->boolean('branded'));

        if ($request->boolean('disclose') && ! $brand) {
            return back()->withInput()->with('error', 'Choose "Your brand" and/or "Branded content", or turn off content disclosure.');
        }

        if ($direct && $request->boolean('branded') && $data['privacy_level'] === 'SELF_ONLY') {
            return back()->withInput()->with('error', 'Branded content cannot be posted as "Only me".');
        }

        $caption  = (string) ($data['caption'] ?? '');
        $postInfo = [
            'title'                => $caption,
            'privacy_level'        => $data['privacy_level'] ?? 'SELF_ONLY',
            'disable_comment'      => ! $request->boolean('allow_comment'),
            'brand_content_toggle' => $request->boolean('disclose') && $request->boolean('branded'),
            'brand_organic_toggle' => $request->boolean('disclose') && $request->boolean('your_brand'),
        ];

        $record = TikTokPost::create([
            'channel_id'    => $post->channel_id,
            'queue_id'      => $post->id ?: null,
            'mode'          => $data['mode'],
            'media_type'    => $media['video'] ? 'video' : 'photo',
            'privacy_level' => $direct ? $postInfo['privacy_level'] : null,
            'caption'       => $caption,
            'status'        => 'submitting',
            'created_by'    => auth()->guard('admin')->id(),
        ]);

        try {
            if ($media['video']) {
                $publishId = $direct
                    ? $this->client->directPostVideo($account, $media['video'], $postInfo + [
                        'disable_duet'   => ! $request->boolean('allow_duet'),
                        'disable_stitch' => ! $request->boolean('allow_stitch'),
                    ])
                    : $this->client->draftVideo($account, $media['video']);
            } else {
                $publishId = $this->client->postPhotos($account, $media['images'], $postInfo, $direct);
            }

            $record->update(['publish_id' => $publishId, 'status' => 'PROCESSING_DOWNLOAD']);
        } catch (Throwable $e) {
            $record->update(['status' => 'FAILED', 'error' => Str::limit($e->getMessage(), 2000, '')]);

            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.autoposter.tiktok', ['channel_id' => $post->channel_id])
            ->with('success', $direct
                ? 'Sent to TikTok. It can take a few minutes to process before it appears on your profile.'
                : 'Sent to your TikTok inbox. Open the TikTok app to edit and post it.');
    }

    public function status(int $id)
    {
        $record  = TikTokPost::findOrFail($id);
        $account = TikTokAccount::where('channel_id', $record->channel_id)->first();

        if ($account && $record->publish_id && ! in_array($record->status, ['PUBLISH_COMPLETE', 'SEND_TO_USER_INBOX', 'FAILED'], true)) {
            try {
                $data = $this->client->publishStatus($account, $record->publish_id);
                $record->update([
                    'status'        => $data['status'] ?? $record->status,
                    'error'         => $data['fail_reason'] ?? null,
                    'last_response' => $data,
                ]);
            } catch (Throwable $e) {
                $record->update(['error' => Str::limit($e->getMessage(), 2000, '')]);
            }
        }

        return response()->json($record->only(['id', 'status', 'error']));
    }

    /**
     * TikTok sends the user back to the registered redirect URI's host, and the
     * admin session cookie is per host — so the whole TikTok flow must run on
     * that host (also the verified Web URL shown in the review video).
     */
    protected function onRedirectHost(Request $request)
    {
        $host = parse_url((string) config('autoposter.tiktok.redirect_uri'), PHP_URL_HOST);

        if ($host && $request->getHost() !== $host) {
            return redirect()->away('https://'.$host.$request->getRequestUri());
        }

        return null;
    }

    /**
     * Post id 0 is the fixed "Content Posting API test video" used for sandbox
     * testing and the app-review demo; it has no queue row.
     */
    protected function findPost(Request $request, int $id): AutoPosterQueue
    {
        if ($id !== 0) {
            return AutoPosterQueue::findOrFail($id);
        }

        $post = new AutoPosterQueue([
            'channel_id'        => (int) $request->input('channel_id', 1),
            'caption'           => 'Fujiba Kimono – TikTok Content Posting API test video',
            'instagram_payload' => [
                'message' => 'Fujiba Kimono – TikTok Content Posting API test video',
                'video'   => self::TEST_VIDEO,
            ],
        ]);
        $post->id = 0;

        return $post;
    }

    public const TEST_VIDEO = 'https://kimono.fujiba.co.jp/tiktok-test/content-posting-api-test.mp4';

    /** @return array{video:?string,images:array<int,string>} */
    protected function media(AutoPosterQueue $post): array
    {
        $p = $post->instagram_payload ?? [];

        return [
            'video'  => $p['video'] ?? null,
            // TikTok photo posts accept JPEG/WebP only.
            'images' => array_values(array_filter($p['images'] ?? [], fn ($u) => ! preg_match('/\.png(\?|$)/i', $u))),
        ];
    }
}

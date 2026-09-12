<?php

namespace Fujiba\AutoPoster\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Fujiba\AutoPoster\Models\AutoPosterQueue;
use Fujiba\AutoPoster\Models\AutoPosterSetting;
use Fujiba\AutoPoster\Services\PreflightChecker;
use Fujiba\AutoPoster\Services\ProductSelectionService;
use Fujiba\AutoPoster\Services\QueueBuilder;
use Fujiba\AutoPoster\Services\SchedulePlanner;
use Fujiba\FacebookCatalog\Models\FacebookCatalogSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Models\Channel;

class DashboardController extends Controller
{
    public function __construct(
        protected PreflightChecker $preflight,
        protected ProductSelectionService $selection,
    ) {}

    public function index(Request $request)
    {
        $channels  = Channel::all();
        $channelId = $this->resolveChannelId($request, $channels);

        if (! $channelId) {
            return view('autoposter::admin.dashboard', [
                'channels'  => $channels,
                'channelId' => null,
            ]);
        }

        $settings = AutoPosterSetting::where('channel_id', $channelId)->first();
        $catalog  = FacebookCatalogSetting::where('channel_id', $channelId)->first();

        // Pre-flight hits the Graph API, so it is not run on every page load.
        // Show the stored result and let the admin re-run it explicitly.
        $preflight = $settings?->last_preflight_result;

        return view('autoposter::admin.dashboard', [
            'channels'      => $channels,
            'channelId'     => $channelId,
            'settings'      => $settings,
            'catalog'       => $catalog,
            'preflight'     => $preflight,
            'preflightAt'   => $settings?->last_preflight_at,
            'stats'         => $this->stats($channelId),
            'nextPost'      => $this->nextScheduled($channelId),
            'lastPublished' => $this->lastPublished($channelId),
            'recent'        => $this->recentPosts($channelId),
            'eligibleCount' => $settings ? $this->selection->eligibleCount($settings) : 0,
            'scheduleOn'    => (bool) config('autoposter.schedule_enabled'),
        ]);
    }

    /**
     * Re-run pre-flight on demand and store the result.
     */
    public function preflight(Request $request)
    {
        $channelId = (int) $request->input('channel_id');

        if (! Channel::where('id', $channelId)->exists()) {
            return back()->with('error', 'Invalid channel.');
        }

        $result = $this->preflight->runAndStore($channelId);

        return back()->with(
            $result['ready'] ? 'success' : 'warning',
            $result['ready']
                ? 'Pre-flight passed — ready to launch.'
                : $result['blocking_failures'] . ' blocking issue(s) found. See the checklist below.'
        );
    }

    /**
     * Master auto-posting toggle. Turning ON is gated on pre-flight passing —
     * the check is re-run here rather than trusting a stored result that may be
     * hours old and no longer true.
     */
    public function toggle(Request $request)
    {
        $channelId = (int) $request->input('channel_id');
        $enable    = $request->boolean('enable');

        $settings = AutoPosterSetting::where('channel_id', $channelId)->first();

        if (! $settings) {
            return back()->with('error', 'Configure the channel on the Settings page first.');
        }

        if ($enable) {
            $result = $this->preflight->runAndStore($channelId, $settings);

            if (! $result['ready']) {
                return back()->with(
                    'error',
                    'Cannot enable auto posting — ' . $result['blocking_failures']
                        . ' pre-flight check(s) failing. Fix them and try again.'
                );
            }

            $settings->update(['auto_posting_enabled' => true]);

            return back()->with('success', 'Auto posting is ON.');
        }

        $settings->update(['auto_posting_enabled' => false]);

        $pending = AutoPosterQueue::where('channel_id', $channelId)
            ->where('status', AutoPosterQueue::STATUS_SCHEDULED)
            ->count();

        return back()->with(
            'warning',
            'Auto posting is OFF.' . ($pending > 0
                ? " {$pending} scheduled post(s) remain in the queue and will NOT be published while it is off."
                : '')
        );
    }

    /**
     * Generate the rest of today's schedule on demand.
     */
    public function planNow(Request $request, QueueBuilder $builder, SchedulePlanner $planner)
    {
        $channelId = (int) $request->input('channel_id');
        $settings  = AutoPosterSetting::where('channel_id', $channelId)->first();

        if (! $settings) {
            return back()->with('error', 'Configure the channel on the Settings page first.');
        }

        $created = $builder->planDay($settings, $planner);

        if (empty($created)) {
            return back()->with('warning',
                'No posts created — every remaining slot today is already filled, '
                . 'or no eligible products are left.');
        }

        return back()->with('success', count($created) . ' post(s) scheduled for today.');
    }

    protected function resolveChannelId(Request $request, $channels): ?int
    {
        $requested = (int) $request->input('channel_id');

        if ($requested && $channels->contains('id', $requested)) {
            return $requested;
        }

        return $channels->first()?->id;
    }

    /**
     * @return array<string,int>
     */
    protected function stats(int $channelId): array
    {
        $byStatus = AutoPosterQueue::where('channel_id', $channelId)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $stats = [];
        foreach ([
            AutoPosterQueue::STATUS_DRAFT,
            AutoPosterQueue::STATUS_SCHEDULED,
            AutoPosterQueue::STATUS_PROCESSING,
            AutoPosterQueue::STATUS_PUBLISHED,
            AutoPosterQueue::STATUS_FAILED,
            AutoPosterQueue::STATUS_CANCELLED,
        ] as $status) {
            $stats[$status] = (int) ($byStatus[$status] ?? 0);
        }

        $stats['published_today'] = AutoPosterQueue::where('channel_id', $channelId)
            ->where('status', AutoPosterQueue::STATUS_PUBLISHED)
            ->whereDate('published_at', now()->toDateString())
            ->count();

        return $stats;
    }

    protected function nextScheduled(int $channelId): ?AutoPosterQueue
    {
        return AutoPosterQueue::where('channel_id', $channelId)
            ->where('status', AutoPosterQueue::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->orderBy('scheduled_at')
            ->first();
    }

    protected function lastPublished(int $channelId): ?AutoPosterQueue
    {
        return AutoPosterQueue::where('channel_id', $channelId)
            ->where('status', AutoPosterQueue::STATUS_PUBLISHED)
            ->orderByDesc('published_at')
            ->first();
    }

    protected function recentPosts(int $channelId)
    {
        return AutoPosterQueue::with('products')
            ->where('channel_id', $channelId)
            ->orderByDesc('id')
            ->limit(8)
            ->get();
    }
}

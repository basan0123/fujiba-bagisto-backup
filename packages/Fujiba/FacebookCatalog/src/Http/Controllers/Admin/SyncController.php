<?php

namespace Fujiba\FacebookCatalog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Fujiba\FacebookCatalog\Jobs\SyncApprovedProductsJob;
use Fujiba\FacebookCatalog\Models\FacebookCatalogProductSync;
use Fujiba\FacebookCatalog\Models\FacebookCatalogSetting;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function syncAll(Request $request)
    {
        $channelId = (int) $request->input('channel_id', 0);

        $settings = FacebookCatalogSetting::where('channel_id', $channelId)
            ->where('is_active', true)
            ->first();

        if (! $settings) {
            return back()->with('error', 'No active Facebook catalog for this channel. Save credentials and click Test first.');
        }

        $approved = FacebookCatalogProductSync::where('channel_id', $channelId)
            ->where('approved', true)
            ->count();

        if ($approved === 0) {
            return back()->with('warning', 'No approved products to sync. Approve products first.');
        }

        FacebookCatalogProductSync::where('channel_id', $channelId)
            ->where('approved', true)
            ->update(['sync_status' => FacebookCatalogProductSync::STATUS_QUEUED]);

        SyncApprovedProductsJob::dispatch($channelId)
            ->onQueue((string) config('fujiba-facebook-catalog.queue', 'default'));

        return back()->with('success', "Queued {$approved} product(s) for Facebook sync on channel #{$channelId}.");
    }

    public function retryFailed(Request $request)
    {
        $channelId = (int) $request->input('channel_id', 0);

        $count = FacebookCatalogProductSync::where('channel_id', $channelId)
            ->where('approved', true)
            ->where('sync_status', FacebookCatalogProductSync::STATUS_FAILED)
            ->update(['sync_status' => FacebookCatalogProductSync::STATUS_QUEUED, 'sync_error' => null]);

        if ($count === 0) {
            return back()->with('warning', 'No failed products to retry.');
        }

        SyncApprovedProductsJob::dispatch($channelId)
            ->onQueue((string) config('fujiba-facebook-catalog.queue', 'default'));

        return back()->with('success', "Re-queued {$count} failed product(s).");
    }
}

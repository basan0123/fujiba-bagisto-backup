<?php

namespace Fujiba\FacebookCatalog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Fujiba\FacebookCatalog\Models\FacebookCatalogProductSync;
use Fujiba\FacebookCatalog\Models\FacebookCatalogSetting;
use Fujiba\FacebookCatalog\Services\GraphClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Core\Models\Channel;

class SettingsController extends Controller
{
    public function index()
    {
        $channels = Channel::all();

        $settingsByChannel = FacebookCatalogSetting::all()->keyBy('channel_id');

        $statsRows = FacebookCatalogProductSync::select(
            'channel_id',
            'sync_status',
            DB::raw('SUM(CASE WHEN approved = 1 THEN 1 ELSE 0 END) as approved_count'),
            DB::raw('COUNT(*) as total'),
            DB::raw('MAX(last_synced_at) as last_synced_at')
        )->groupBy('channel_id', 'sync_status')->get();

        $stats = [];
        foreach ($statsRows as $row) {
            $cid = (int) $row->channel_id;
            $stats[$cid] ??= ['approved' => 0, 'synced' => 0, 'failed' => 0, 'pending' => 0, 'last_synced_at' => null];
            $stats[$cid]['approved'] += (int) $row->approved_count;
            $stats[$cid][$row->sync_status] = (int) $row->total;
            if ($row->last_synced_at) {
                $ts = \Carbon\Carbon::parse($row->last_synced_at);
                if (! $stats[$cid]['last_synced_at'] || $ts->gt($stats[$cid]['last_synced_at'])) {
                    $stats[$cid]['last_synced_at'] = $ts;
                }
            }
        }

        return view('facebook_catalog::admin.index', [
            'channels'          => $channels,
            'settingsByChannel' => $settingsByChannel,
            'stats'             => $stats,
        ]);
    }

    public function save(Request $request)
    {
        $channelId       = (int) $request->input('channel_id', 0);
        $catalogId       = trim((string) $request->input('catalog_id', ''));
        $businessId      = trim((string) $request->input('business_id', ''));
        $pageId          = trim((string) $request->input('page_id', ''));
        $pixelId         = trim((string) $request->input('pixel_id', ''));
        $accessToken     = trim((string) $request->input('access_token', ''));
        $defaultCurrency = strtoupper(trim((string) $request->input('default_currency', 'JPY')));
        $defaultLocale   = trim((string) $request->input('default_locale', 'ja'));
        $autoSync        = $request->boolean('auto_sync_price_stock');

        if ($channelId <= 0 || ! Channel::where('id', $channelId)->exists()) {
            return back()->with('error', 'Invalid channel_id.');
        }

        if ($catalogId === '') {
            return back()->with('error', 'catalog_id is required.');
        }

        $settings = FacebookCatalogSetting::firstOrNew(['channel_id' => $channelId]);
        $settings->catalog_id        = $catalogId;
        $settings->business_id       = $businessId ?: null;
        $settings->page_id           = $pageId ?: null;
        $settings->pixel_id          = $pixelId ?: null;
        $settings->default_currency  = $defaultCurrency ?: 'JPY';
        $settings->default_locale    = $defaultLocale ?: 'ja';
        $settings->auto_sync_price_stock = $autoSync;

        if ($accessToken !== '') {
            $settings->access_token = $accessToken;
        }

        if (! $settings->access_token) {
            return back()->with('error', 'access_token is required for first save.');
        }

        $settings->save();

        return back()->with('success', "Saved Facebook catalog settings for channel #{$channelId}.");
    }

    public function test(Request $request)
    {
        $channelId = (int) $request->input('channel_id', 0);
        $settings  = FacebookCatalogSetting::where('channel_id', $channelId)->first();

        if (! $settings) {
            return back()->with('error', 'No settings configured for this channel.');
        }

        try {
            $client = new GraphClient($settings->access_token);
            $info = $client->getCatalog($settings->catalog_id);

            $settings->is_active = true;
            $settings->last_sync_error = null;
            $settings->save();

            return back()->with('success', sprintf(
                'Connected to "%s" (catalog %s, %d products on FB).',
                $info['name'] ?? '?',
                $info['id'] ?? $settings->catalog_id,
                $info['product_count'] ?? 0
            ));
        } catch (Throwable $e) {
            $settings->is_active = false;
            $settings->last_sync_error = $e->getMessage();
            $settings->save();

            Log::warning('[FacebookCatalog] connection test failed', [
                'channel_id' => $channelId,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Connection failed: ' . $e->getMessage());
        }
    }

    public function disable(Request $request)
    {
        $channelId = (int) $request->input('channel_id', 0);
        FacebookCatalogSetting::where('channel_id', $channelId)->update(['is_active' => false]);

        return back()->with('success', "Disabled Facebook catalog sync for channel #{$channelId}.");
    }
}

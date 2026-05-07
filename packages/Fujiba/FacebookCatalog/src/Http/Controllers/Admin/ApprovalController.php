<?php

namespace Fujiba\FacebookCatalog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Fujiba\FacebookCatalog\Models\FacebookCatalogProductSync;
use Fujiba\FacebookCatalog\Models\FacebookCatalogSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Models\Channel;
use Webkul\Product\Models\Product;

/**
 * Admin product browser with per-product Facebook approval checkbox.
 *
 * Each row = one Bagisto product. Toggling the checkbox upserts
 * facebook_catalog_product_sync(product_id, channel_id) with approved=1
 * and approved_by/approved_at filled. Approved rows are eligible for the
 * next sync run; unapproved rows are skipped.
 */
class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $channels = Channel::all();
        $channelId = (int) $request->query('channel_id', $channels->first()->id ?? 0);

        $search = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('filter', 'all');

        $settings = FacebookCatalogSetting::where('channel_id', $channelId)->first();

        $locale = $settings?->default_locale ?: 'ja';
        $channelCode = Channel::where('id', $channelId)->value('code') ?: 'default';

        $query = DB::table('product_flat as pf')
            ->leftJoin('facebook_catalog_product_sync as fb', function ($j) use ($channelId) {
                $j->on('fb.product_id', '=', 'pf.product_id')
                  ->where('fb.channel_id', '=', $channelId);
            })
            ->where('pf.channel', $channelCode)
            ->where('pf.locale', $locale)
            ->select(
                'pf.product_id',
                'pf.sku',
                'pf.name',
                'pf.price',
                'pf.special_price',
                'pf.status',
                'pf.visible_individually',
                'fb.approved',
                'fb.sync_status',
                'fb.sync_error',
                'fb.last_synced_at',
                'fb.fb_retailer_id',
            );

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('pf.name', 'like', "%{$search}%")
                  ->orWhere('pf.sku', 'like', "%{$search}%");
            });
        }

        if ($filter === 'approved') {
            $query->where('fb.approved', 1);
        } elseif ($filter === 'unapproved') {
            $query->whereNull('fb.approved')->orWhere('fb.approved', 0);
        } elseif ($filter === 'failed') {
            $query->where('fb.sync_status', 'failed');
        } elseif ($filter === 'synced') {
            $query->where('fb.sync_status', 'synced');
        }

        $products = $query->orderByDesc('pf.product_id')->paginate(40)->withQueryString();

        return view('facebook_catalog::admin.products', [
            'channels'    => $channels,
            'channelId'   => $channelId,
            'settings'    => $settings,
            'products'    => $products,
            'search'      => $search,
            'filter'      => $filter,
        ]);
    }

    public function toggle(Request $request)
    {
        $channelId = (int) $request->input('channel_id', 0);
        $productId = (int) $request->input('product_id', 0);
        $approved  = $request->boolean('approved');

        if ($channelId <= 0 || $productId <= 0) {
            return response()->json(['ok' => false, 'error' => 'invalid input'], 422);
        }

        if (! Product::where('id', $productId)->exists() || ! Channel::where('id', $channelId)->exists()) {
            return response()->json(['ok' => false, 'error' => 'product or channel not found'], 404);
        }

        $row = FacebookCatalogProductSync::firstOrNew([
            'product_id' => $productId,
            'channel_id' => $channelId,
        ]);

        $row->approved = $approved;

        if ($approved) {
            $row->approved_by = Auth::guard('admin')->id();
            $row->approved_at = now();
            if ($row->sync_status === FacebookCatalogProductSync::STATUS_SYNCED) {
                // re-approval after un-approval — keep status, just re-flag
            } else {
                $row->sync_status = FacebookCatalogProductSync::STATUS_PENDING;
            }
        } else {
            $row->approved_by = null;
            $row->approved_at = null;
        }

        $row->save();

        return response()->json([
            'ok'          => true,
            'approved'    => (bool) $row->approved,
            'sync_status' => $row->sync_status,
        ]);
    }

    public function bulkApprove(Request $request)
    {
        $channelId  = (int) $request->input('channel_id', 0);
        $productIds = (array) $request->input('product_ids', []);
        $approve    = $request->boolean('approve', true);

        $productIds = array_values(array_filter(array_map('intval', $productIds), fn ($id) => $id > 0));

        if ($channelId <= 0 || empty($productIds)) {
            return back()->with('error', 'Select at least one product and a channel.');
        }

        if (! Channel::where('id', $channelId)->exists()) {
            return back()->with('error', 'Invalid channel.');
        }

        $adminId = Auth::guard('admin')->id();
        $now     = now();

        foreach ($productIds as $productId) {
            $row = FacebookCatalogProductSync::firstOrNew([
                'product_id' => $productId,
                'channel_id' => $channelId,
            ]);

            $row->approved = $approve;

            if ($approve) {
                $row->approved_by = $adminId;
                $row->approved_at = $now;
                $row->sync_status ??= FacebookCatalogProductSync::STATUS_PENDING;
            } else {
                $row->approved_by = null;
                $row->approved_at = null;
            }

            $row->save();
        }

        $verb = $approve ? 'Approved' : 'Unapproved';

        return back()->with('success', "{$verb} " . count($productIds) . " product(s) for channel #{$channelId}.");
    }
}

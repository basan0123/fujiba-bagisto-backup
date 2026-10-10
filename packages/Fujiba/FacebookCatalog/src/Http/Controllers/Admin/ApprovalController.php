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
    /**
     * Build the filtered product query shared by index() and bulkApprove().
     *
     * Both the listing and "select all matching" must resolve the SAME set —
     * if they drift, a bulk action silently hits rows the operator never saw.
     * So the filter predicates live here once rather than in each caller.
     */
    protected function filteredQuery(int $channelId, string $channelCode, string $locale, string $search, string $filter, string $status)
    {
        $query = DB::table('product_flat as pf')
            ->leftJoin('facebook_catalog_product_sync as fb', function ($j) use ($channelId) {
                $j->on('fb.product_id', '=', 'pf.product_id')
                  ->where('fb.channel_id', '=', $channelId);
            })
            ->where('pf.channel', $channelCode)
            ->where('pf.locale', $locale);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('pf.name', 'like', "%{$search}%")
                  ->orWhere('pf.sku', 'like', "%{$search}%");
            });
        }

        // Storefront status axis. 'active' = live on the storefront and
        // individually visible — the only products safe to advertise, since a
        // Facebook catalog entry links back to the PDP.
        if ($status === 'active') {
            $query->where('pf.status', 1)->where('pf.visible_individually', 1);
        } elseif ($status === 'inactive') {
            $query->where(function ($q) {
                $q->where('pf.status', '!=', 1)
                  ->orWhereNull('pf.status')
                  ->orWhere('pf.visible_individually', '!=', 1);
            });
        }

        if ($filter === 'approved') {
            $query->where('fb.approved', 1);
        } elseif ($filter === 'unapproved') {
            // Grouped: without the closure the orWhere escapes to the top level
            // and cancels the status/search constraints above.
            $query->where(function ($q) {
                $q->whereNull('fb.approved')->orWhere('fb.approved', 0);
            });
        } elseif ($filter === 'failed') {
            $query->where('fb.sync_status', 'failed');
        } elseif ($filter === 'synced') {
            $query->where('fb.sync_status', 'synced');
        }

        return $query;
    }

    public function index(Request $request)
    {
        $channels = Channel::all();
        $channelId = (int) $request->query('channel_id', $channels->first()->id ?? 0);

        $search = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('filter', 'all');

        // Storefront status is a SEPARATE axis from sync state: operators need
        // "active AND not yet approved" to find the real backlog, which a single
        // combined dropdown can't express. Defaults to 'active' — approving a
        // disabled product pushes a dead PDP link to Facebook, so the unfiltered
        // list was the dangerous default (37 disabled products were already
        // approved+synced on the default channel when this filter was added).
        $status = (string) $request->query('status', 'active');

        $settings = FacebookCatalogSetting::where('channel_id', $channelId)->first();

        $locale = $settings?->default_locale ?: 'ja';
        $channelCode = Channel::where('id', $channelId)->value('code') ?: 'default';

        $query = $this->filteredQuery($channelId, $channelCode, $locale, $search, $filter, $status)
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

        $products = $query->orderByDesc('pf.product_id')->paginate(40)->withQueryString();

        // Count approved-but-not-active rows so the page can warn about entries
        // already pushed to Facebook that no longer resolve on the storefront.
        $staleApproved = DB::table('product_flat as pf')
            ->join('facebook_catalog_product_sync as fb', function ($j) use ($channelId) {
                $j->on('fb.product_id', '=', 'pf.product_id')
                  ->where('fb.channel_id', '=', $channelId);
            })
            ->where('pf.channel', $channelCode)
            ->where('pf.locale', $locale)
            ->where('fb.approved', 1)
            ->where(function ($q) {
                $q->where('pf.status', '!=', 1)
                  ->orWhereNull('pf.status')
                  ->orWhere('pf.visible_individually', '!=', 1);
            })
            ->count();

        return view('facebook_catalog::admin.products', [
            'channels'      => $channels,
            'channelId'     => $channelId,
            'settings'      => $settings,
            'products'      => $products,
            'search'        => $search,
            'filter'        => $filter,
            'status'        => $status,
            'staleApproved' => $staleApproved,
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
        $selectAll  = $request->boolean('select_all');

        $productIds = array_values(array_filter(array_map('intval', $productIds), fn ($id) => $id > 0));

        if ($channelId <= 0) {
            return back()->with('error', 'Select a channel.');
        }

        if (! Channel::where('id', $channelId)->exists()) {
            return back()->with('error', 'Invalid channel.');
        }

        // "Select all matching" mode: resolve the id list server-side from the
        // SAME filters the listing used, rather than trusting a client-supplied
        // list. The page only renders 40 rows, so posting every id would both
        // blow past max_input_vars and let a stale form act on rows the current
        // filter no longer covers.
        $settings    = FacebookCatalogSetting::where('channel_id', $channelId)->first();
        $locale      = $settings?->default_locale ?: 'ja';
        $channelCode = Channel::where('id', $channelId)->value('code') ?: 'default';

        if ($selectAll) {
            $productIds = $this->filteredQuery(
                $channelId,
                $channelCode,
                $locale,
                trim((string) $request->input('q', '')),
                (string) $request->input('filter', 'all'),
                (string) $request->input('status', 'active'),
            )->pluck('pf.product_id')->map('intval')->unique()->values()->all();
        }

        if (empty($productIds)) {
            return back()->with('error', 'Select at least one product.');
        }

        // Approving a disabled product publishes a dead PDP link to Facebook.
        // The UI defaults to active-only, but a deliberate "Disabled/hidden"
        // filter or a stale form can still submit them, so enforce it here too
        // — this is the same guard BatchSyncService applies at send time.
        $blocked = 0;

        if ($approve) {
            $activeIds = DB::table('product_flat')
                ->whereIn('product_id', $productIds)
                ->where('channel', $channelCode)
                ->where('locale', $locale)
                ->where('status', 1)
                ->where('visible_individually', 1)
                ->pluck('product_id')
                ->map('intval')
                ->all();

            $blocked    = count($productIds) - count($activeIds);
            $productIds = $activeIds;

            if (empty($productIds)) {
                return back()->with('error', "None of the selected products are active on the storefront — nothing approved.");
            }
        }

        $adminId = Auth::guard('admin')->id();
        $now     = now();

        // Select-all can cover the whole channel (hundreds of rows), so write in
        // chunks instead of one save() per product. Existing rows keep their
        // sync_status — only a row that has never synced is moved to pending, so
        // re-approving an already-synced product doesn't force a needless resend.
        DB::transaction(function () use ($productIds, $channelId, $approve, $adminId, $now) {
            foreach (array_chunk($productIds, 500) as $chunk) {
                $existing = FacebookCatalogProductSync::where('channel_id', $channelId)
                    ->whereIn('product_id', $chunk)
                    ->pluck('sync_status', 'product_id');

                $rows = [];

                foreach ($chunk as $productId) {
                    $rows[] = [
                        'product_id'  => $productId,
                        'channel_id'  => $channelId,
                        'approved'    => $approve,
                        'approved_by' => $approve ? $adminId : null,
                        'approved_at' => $approve ? $now : null,
                        'sync_status' => $existing[$productId]
                            ?? ($approve ? FacebookCatalogProductSync::STATUS_PENDING : null),
                        'updated_at'  => $now,
                        'created_at'  => $now,
                    ];
                }

                FacebookCatalogProductSync::upsert(
                    $rows,
                    ['product_id', 'channel_id'],
                    ['approved', 'approved_by', 'approved_at', 'sync_status', 'updated_at'],
                );
            }
        });

        $verb = $approve ? 'Approved' : 'Unapproved';
        $msg  = "{$verb} " . count($productIds) . " product(s) for channel #{$channelId}.";

        if ($blocked > 0) {
            $msg .= " Skipped {$blocked} product(s) that are disabled or hidden on the storefront.";
        }

        return back()->with('success', $msg);
    }
}

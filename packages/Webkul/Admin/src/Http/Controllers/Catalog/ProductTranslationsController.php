<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Models\Locale;

/**
 * Side-by-side multi-language product NAME editor.
 * Loads every product at once; search/filter is client-side; saves either
 * one row at a time (per-row Save) or many at once (Save All).
 */
class ProductTranslationsController extends Controller
{
    public function index()
    {
        $locales = Locale::query()->orderBy('id')->get(['code', 'name']);
        $channel = core()->getCurrentChannelCode();

        return view('admin::catalog.products.translations', [
            'locales' => $locales,
            'channel' => $channel,
        ]);
    }

    /**
     * JSON: ALL products with names per locale (channel-filtered).
     */
    public function list(): JsonResponse
    {
        $channel = request('channel') ?: core()->getCurrentChannelCode();
        $locales = Locale::query()->pluck('code')->toArray();

        $channelId = DB::table('channels')->where('code', $channel)->value('id');

        $query = DB::table('products')
            ->whereIn('type', ['simple', 'virtual', 'downloadable', 'configurable', 'bundle', 'grouped', 'booking']);

        if ($channelId) {
            $query->whereExists(function ($q) use ($channelId) {
                $q->select(DB::raw(1))
                    ->from('product_channels')
                    ->whereColumn('product_channels.product_id', 'products.id')
                    ->where('product_channels.channel_id', $channelId);
            });
        }

        $products = $query->orderBy('id', 'desc')->get(['id', 'sku']);

        $ids = $products->pluck('id')->all();

        $names = [];
        if ($ids) {
            $flat = DB::table('product_flat')
                ->whereIn('product_id', $ids)
                ->where('channel', $channel)
                ->get(['product_id', 'locale', 'name']);
            foreach ($flat as $r) {
                $names[$r->product_id][$r->locale] = $r->name;
            }
        }

        $images = [];
        if ($ids) {
            $imgs = DB::table('product_images')
                ->whereIn('product_id', $ids)
                ->orderBy('product_id')
                ->orderBy('position')
                ->get(['product_id', 'path']);
            foreach ($imgs as $img) {
                if (! isset($images[$img->product_id])) {
                    $images[$img->product_id] = Storage::url($img->path);
                }
            }
        }

        $reserved = [];
        if ($ids) {
            $resvRows = DB::table('product_ordered_inventories')
                ->whereIn('product_id', $ids)
                ->select('product_id', DB::raw('SUM(qty) as qty'))
                ->groupBy('product_id')
                ->get();
            foreach ($resvRows as $r) {
                $reserved[$r->product_id] = (int) $r->qty;
            }
        }

        $data = $products->map(function ($r) use ($names, $images, $reserved, $locales) {
            $row = [
                'id'       => (int) $r->id,
                'sku'      => $r->sku,
                'image'    => $images[$r->id] ?? null,
                'reserved' => $reserved[$r->id] ?? 0,
                'names'    => [],
            ];
            foreach ($locales as $loc) {
                $row['names'][$loc] = $names[$r->id][$loc] ?? '';
            }
            return $row;
        })->values();

        return new JsonResponse([
            'data'    => $data,
            'total'   => $data->count(),
            'channel' => $channel,
            'locales' => $locales,
        ]);
    }

    /**
     * Bulk update: accepts updates[] = [{ id, names: { locale: name, ... } }, ...]
     * Writes both product_attribute_values (master) and product_flat (denormalized).
     */
    public function bulkUpdate(): JsonResponse
    {
        $channel = request('channel') ?: core()->getCurrentChannelCode();
        $updates = request('updates', []);
        if (! is_array($updates) || empty($updates)) {
            return new JsonResponse(['message' => 'No updates'], 422);
        }

        $allowedLocales = Locale::query()->pluck('code')->toArray();
        $nameAttr       = DB::table('attributes')->where('code', 'name')->first();
        if (! $nameAttr) {
            return new JsonResponse(['message' => 'name attribute not found'], 500);
        }

        $touched = 0;
        $errors  = [];

        DB::beginTransaction();
        try {
            foreach ($updates as $u) {
                $id = (int) ($u['id'] ?? 0);
                $names = $u['names'] ?? null;
                if (! $id || ! is_array($names)) {
                    continue;
                }
                if (! DB::table('products')->where('id', $id)->exists()) {
                    $errors[] = ['id' => $id, 'reason' => 'not found'];
                    continue;
                }

                foreach ($names as $loc => $val) {
                    if (! in_array($loc, $allowedLocales, true)) {
                        continue;
                    }
                    $val = is_string($val) ? trim($val) : $val;

                    DB::table('product_attribute_values')
                        ->where('product_id', $id)
                        ->where('attribute_id', $nameAttr->id)
                        ->where('locale', $loc)
                        ->delete();

                    if ($val !== '' && $val !== null) {
                        DB::table('product_attribute_values')->insert([
                            'product_id'   => $id,
                            'attribute_id' => $nameAttr->id,
                            'locale'       => $loc,
                            'channel'      => null,
                            'text_value'   => (string) $val,
                        ]);
                    }

                    DB::table('product_flat')
                        ->where('product_id', $id)
                        ->where('locale', $loc)
                        ->where('channel', $channel)
                        ->update(['name' => $val === '' ? null : $val]);
                }

                Event::dispatch('catalog.product.update.before', $id);
                $touched++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse(['message' => $e->getMessage()], 500);
        }

        return new JsonResponse([
            'message' => 'Saved',
            'touched' => $touched,
            'errors'  => $errors,
        ]);
    }
}

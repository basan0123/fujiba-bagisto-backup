<?php

namespace App\Http\Controllers\Api\V1\POS;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Live barcode resolver — used by the POS as a fallback when local
 * IndexedDB does not contain a matching product (e.g. after admin
 * bulk-fills barcodes server-side and the cashier has not run Sync yet).
 */
class BarcodeResolveController extends Controller
{
    public function resolve(Request $request): JsonResponse
    {
        $code = trim((string) $request->query('code', ''));

        if ($code === '') {
            return new JsonResponse(['found' => false, 'reason' => 'empty_code'], 422);
        }

        $agent    = auth('pos')->user();
        $outletId = $agent->outlet_id ?? null;

        // Match order mirrors the Vue resolver: scan_value > gtin > barcode > sku > id
        $row = DB::table('pos_product_barcode as ppb')
            ->where(function ($q) use ($code) {
                $q->where('ppb.scan_value', $code)
                    ->orWhere('ppb.gtin', $code)
                    ->orWhere('ppb.barcode', $code);
            })
            ->select('ppb.product_id')
            ->first();

        $productId = $row->product_id ?? null;

        if (! $productId) {
            // SKU / id fallback against product_flat
            $bySku = DB::table('product_flat')
                ->where('sku', $code)
                ->orderBy('product_id')
                ->value('product_id');

            if ($bySku) {
                $productId = (int) $bySku;
            } elseif (ctype_digit($code)) {
                $byId = DB::table('products')->where('id', (int) $code)->value('id');
                if ($byId) {
                    $productId = (int) $byId;
                }
            }
        }

        if (! $productId) {
            return new JsonResponse(['found' => false, 'code' => $code]);
        }

        // Restrict to outlet's products when the agent has an outlet
        if ($outletId) {
            $belongs = DB::table('pos_outlet_product')
                ->where('outlet_id', $outletId)
                ->where('product_id', $productId)
                ->exists();

            if (! $belongs) {
                return new JsonResponse([
                    'found'  => false,
                    'code'   => $code,
                    'reason' => 'outside_outlet',
                ]);
            }
        }

        $bc = DB::table('pos_product_barcode')
            ->where('product_id', $productId)
            ->select('barcode', 'gtin', 'scan_type', 'scan_value')
            ->first();

        $flat = DB::table('product_flat')
            ->where('product_id', $productId)
            ->where('locale', app()->getLocale())
            ->select('product_id', 'sku', 'name', 'price')
            ->first();

        if (! $flat) {
            $flat = DB::table('product_flat')
                ->where('product_id', $productId)
                ->select('product_id', 'sku', 'name', 'price')
                ->first();
        }

        return new JsonResponse([
            'found'   => true,
            'code'    => $code,
            'product' => [
                'id'        => $productId,
                'sku'       => $flat?->sku,
                'name'      => $flat?->name,
                'price'     => $flat?->price !== null ? (float) $flat->price : null,
                'barcode'   => $bc?->barcode,
                'gtin'      => $bc?->gtin,
                'scanType'  => $bc?->scan_type,
                'scanValue' => $bc?->scan_value,
            ],
        ]);
    }
}

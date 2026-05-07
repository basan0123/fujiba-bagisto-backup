<?php

namespace App\Http\Controllers\Api\V1\POS;

use App\Http\Controllers\Controller;
use App\Rules\RelaxedImage;
use App\Services\ImageProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Pos\Models\ReceiptSetting;

class ReceiptSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        $agent    = auth('pos')->user();
        $outletId = $agent->outlet_id;

        if (! $outletId) {
            return response()->json(['error' => 'no_outlet'], 422);
        }

        $setting = ReceiptSetting::firstOrNew(['outlet_id' => $outletId]);

        return response()->json($this->serialize($setting));
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'store_name'     => 'nullable|string|max:255',
            'address1'       => 'nullable|string|max:255',
            'address2'       => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:64',
            'url'            => 'nullable|string|max:255',
            'footer_message' => 'nullable|string|max:2000',
            'logo'           => ['nullable', new RelaxedImage],
        ]);

        $agent    = auth('pos')->user();
        $outletId = $agent->outlet_id;

        if (! $outletId) {
            return response()->json(['error' => 'no_outlet'], 422);
        }

        $setting = ReceiptSetting::firstOrNew(['outlet_id' => $outletId]);

        $setting->store_name     = $data['store_name']     ?? null;
        $setting->address_line1  = $data['address1']       ?? null;
        $setting->address_line2  = $data['address2']       ?? null;
        $setting->phone          = $data['phone']          ?? null;
        $setting->url            = $data['url']            ?? null;
        $setting->footer_message = $data['footer_message'] ?? null;

        if ($request->hasFile('logo')) {
            if ($setting->logo_path && Storage::disk('public')->exists($setting->logo_path)) {
                Storage::disk('public')->delete($setting->logo_path);
            }

            // Normalise logo via ImageProcessor: HEIC/AVIF/oversize → WebP.
            $uploaded  = $request->file('logo');
            $processed = app(ImageProcessor::class)->processToWebp($uploaded);

            if ($processed !== null) {
                $path = 'pos-receipts/'.Str::random(40).'.'.$processed['extension'];
                Storage::disk('public')->put($path, $processed['data']);
                $setting->logo_path = $path;
            } else {
                $setting->logo_path = $uploaded->store('pos-receipts', 'public');
            }
        }

        $setting->outlet_id = $outletId;
        $setting->save();

        return response()->json([
            'message' => 'Receipt settings saved.',
        ] + $this->serialize($setting));
    }

    private function serialize(ReceiptSetting $setting): array
    {
        return [
            'outlet_id'      => $setting->outlet_id,
            'store_name'     => $setting->store_name,
            'address1'       => $setting->address_line1,
            'address2'       => $setting->address_line2,
            'phone'          => $setting->phone,
            'url'            => $setting->url,
            'footer_message' => $setting->footer_message,
            'logo_url'       => $setting->logo_url,
        ];
    }
}

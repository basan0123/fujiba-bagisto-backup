<?php

namespace Fujiba\PwaFrontend\Http\Controllers\Api;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PostcodeController extends Controller
{
    /**
     * GET /api/v1/postcode/{code}
     *
     * Japan-postcode lookup. Uses zipcloud (free, no API key, official-ish).
     * Returns city/state/country in a normalised shape.
     *
     * Cached for 24h per postcode. Hyphens stripped (1234567 == 123-4567).
     */
    public function show(string $code): JsonResponse
    {
        $clean = preg_replace('/[^0-9]/', '', $code);

        if (strlen($clean) !== 7) {
            return response()->json([
                'error' => 'Invalid Japanese postcode (must be 7 digits).',
            ], 422);
        }

        $cacheKey = 'jp.postcode.'.$clean;

        $payload = Cache::remember($cacheKey, now()->addDay(), function () use ($clean) {
            try {
                $resp = Http::timeout(4)
                    ->get('https://zipcloud.ibsnet.co.jp/api/search', ['zipcode' => $clean]);
            } catch (ConnectionException $e) {
                Log::warning('postcode.lookup.timeout', ['code' => $clean, 'msg' => $e->getMessage()]);
                return null;
            }

            if (! $resp->ok()) {
                return null;
            }

            $body = $resp->json();
            $row  = $body['results'][0] ?? null;

            if (! $row) {
                return ['found' => false];
            }

            return [
                'found'      => true,
                'postcode'   => $clean,
                'country'    => 'JP',
                'state'      => $row['address1'] ?? '',
                'state_kana' => $row['kana1']    ?? '',
                'city'       => trim(($row['address2'] ?? '').' '.($row['address3'] ?? '')),
                'city_kana'  => trim(($row['kana2']    ?? '').' '.($row['kana3']    ?? '')),
            ];
        });

        if ($payload === null) {
            return response()->json(['error' => 'Postcode lookup service unavailable.'], 503);
        }

        if (! ($payload['found'] ?? false)) {
            return response()->json(['error' => 'Postcode not found.', 'data' => null], 404);
        }

        return response()->json(['data' => $payload]);
    }
}

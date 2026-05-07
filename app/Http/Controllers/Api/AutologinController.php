<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Customer\Models\Customer;

class AutologinController extends Controller
{
    /**
     * POST /api/autologin
     * Body: { "token": "<64-char raw token>" }
     *
     * Validates the one-time login token issued by the Messenger autologin
     * controllers, marks it used, and returns a Sanctum personal access
     * token the PWA can stash in localStorage.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $raw = (string) $request->input('token', '');

        if ($raw === '' || strlen($raw) !== 64) {
            return response()->json(['status' => 'error', 'message' => 'Invalid token'], 422);
        }

        $record = LoginToken::where('token_hash', LoginToken::hash($raw))->first();

        if (! $record || ! $record->isUsable()) {
            return response()->json(['status' => 'error', 'message' => 'Token expired or already used'], 401);
        }

        $customer = Customer::find($record->user_id);
        if (! $customer) {
            return response()->json(['status' => 'error', 'message' => 'Account not found'], 404);
        }

        $record->markUsed();

        $deviceName = 'autologin-' . substr(hash('sha256', $request->userAgent() ?? 'unknown'), 0, 12);
        $sanctum    = $customer->createToken($deviceName, ['role:customer'])->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token'  => $sanctum,
            'customer' => [
                'id'         => $customer->id,
                'first_name' => $customer->first_name,
                'last_name'  => $customer->last_name,
                'email'      => $customer->email,
            ],
        ]);
    }
}

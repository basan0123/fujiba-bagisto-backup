<?php

namespace Fujiba\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Fujiba\Accounting\Models\Staff;
use Fujiba\Accounting\Models\Timecard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Tablet-mounted punch UI.
 *
 * Auth model:
 *   1. Staff scans QR (qr_token, ULID, unguessable).
 *   2. If staff has a 4-digit PIN set (Sprint 6 Phase 1B), the response
 *      asks the kiosk to prompt for it; the second request includes
 *      both `token` and `pin` and we verify before punching.
 *   3. Staff with no PIN punch immediately on first scan (移行期間).
 *
 * The 30-second idempotency window applies AFTER PIN verification, so
 * a flubbed PIN doesn't trigger the no-op return path.
 */
class TimecardKioskController extends Controller
{
    public function index(): View
    {
        return view('accounting::kiosk.index');
    }

    public function punch(Request $request): JsonResponse
    {
        $token = trim((string) $request->input('token', ''));
        if ($token === '') {
            return response()->json([
                'ok' => false,
                'message' => __('accounting::app.kiosk.unknown_qr'),
            ], 422);
        }

        $staff = Staff::where('qr_token', $token)->first();
        if (! $staff) {
            return response()->json([
                'ok' => false,
                'message' => __('accounting::app.kiosk.unknown_qr'),
            ], 404);
        }

        if (! $staff->is_active) {
            return response()->json([
                'ok' => false,
                'message' => __('accounting::app.kiosk.inactive'),
                'staff_name' => $staff->name_ja,
            ], 403);
        }

        // Sprint 6 Phase 1B: PIN gate.
        if ($staff->hasPin()) {
            $pin = (string) $request->input('pin', '');
            if ($pin === '') {
                return response()->json([
                    'ok'         => false,
                    'pin_required' => true,
                    'staff_name' => $staff->name_ja,
                    'message'    => __('accounting::app.kiosk.pin_required'),
                ], 401);
            }
            if (! $staff->checkPin($pin)) {
                return response()->json([
                    'ok'         => false,
                    'pin_required' => true,
                    'staff_name' => $staff->name_ja,
                    'message'    => __('accounting::app.kiosk.pin_invalid'),
                ], 401);
            }
        }

        $now = now();
        $today = $now->toDateString();

        $card = Timecard::firstOrNew(
            ['staff_id' => $staff->id, 'work_date' => $today],
        );

        // Idempotency: re-scan within 30 s of an existing punch is a no-op.
        $lastTouch = $card->updated_at;
        if ($card->exists && $lastTouch && $now->diffInSeconds($lastTouch) < 30) {
            $action = $card->clock_out ? 'punched_out' : 'punched_in';
            return response()->json([
                'ok' => true,
                'idempotent' => true,
                'message' => __('accounting::app.kiosk.' . $action),
                'staff_name' => $staff->name_ja,
                'time' => $now->format('H:i'),
            ]);
        }

        if (! $card->exists) {
            // First scan of the day → clock-in.
            $card->fill([
                'clock_in'   => $now,
                'channel_id' => $staff->channel_id,
                'status'     => 'open',
            ])->save();
            $action = 'punched_in';
        } elseif (! $card->clock_out) {
            // Second scan → clock-out.
            $card->clock_out = $now;
            // Compute work_minutes lazily; admin can refine.
            if ($card->clock_in) {
                $break = (int) ($card->break_minutes ?? 0);
                $card->work_minutes = max(0, Carbon::parse($card->clock_in)->diffInMinutes($now) - $break);
            }
            $card->status = 'closed';
            $card->save();
            $action = 'punched_out';
        } else {
            // Already clocked out today → idempotent acknowledge.
            $card->touch();
            $action = 'punched_out';
        }

        return response()->json([
            'ok' => true,
            'idempotent' => false,
            'message' => __('accounting::app.kiosk.' . $action),
            'staff_name' => $staff->name_ja,
            'time' => $now->format('H:i'),
        ]);
    }
}

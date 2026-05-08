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
 * Tablet-mounted punch UI. No login — staff scan the QR token printed from
 * the admin "QR印刷" page. Tokens are unguessable ULIDs so this is acceptable
 * for the in-store-only deployment; for a public deployment add a kiosk PIN.
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

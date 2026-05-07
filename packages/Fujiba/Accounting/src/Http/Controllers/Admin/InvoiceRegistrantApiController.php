<?php

namespace Fujiba\Accounting\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Fujiba\Accounting\Services\InvoiceRegistrantLookup;
use Illuminate\Http\JsonResponse;

class InvoiceRegistrantApiController extends Controller
{
    public function show(string $tNumber, InvoiceRegistrantLookup $lookup): JsonResponse
    {
        if (! preg_match('/^T\d{13}$/', $tNumber)) {
            return response()->json([
                'valid' => false,
                'error' => 'Invalid T-number format. Expected T followed by 13 digits.',
            ], 422);
        }

        $row = $lookup->lookup($tNumber);

        if (! $row) {
            return response()->json([
                'valid' => false,
                'error' => 'No registrant found for ' . $tNumber,
            ], 200);
        }

        return response()->json([
            'valid'             => true,
            't_number'          => $row->t_number,
            'name'              => $row->name,
            'kana_name'         => $row->kana_name,
            'address'           => $row->address,
            'registration_date' => $row->registration_date?->toDateString(),
            'fetched_at'        => $row->fetched_at?->toIso8601String(),
        ]);
    }
}

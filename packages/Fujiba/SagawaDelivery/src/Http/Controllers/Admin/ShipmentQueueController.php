<?php

namespace Fujiba\SagawaDelivery\Http\Controllers\Admin;

use App\Jobs\Sagawa\IssueWaybillJob;
use App\Jobs\Sagawa\ValidateAddressJob;
use Fujiba\SagawaDelivery\Models\Shipment;
use Fujiba\SagawaDelivery\Services\CredentialResolver;
use Fujiba\SagawaDelivery\Services\SagawaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ShipmentQueueController extends Controller
{
    /** @return \Illuminate\Contracts\View\View|JsonResponse */
    public function index(Request $request)
    {
        $query = Shipment::query()
            ->orderByDesc('updated_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $shipments = $query->paginate(20);

        if ($request->wantsJson()) {
            return response()->json($shipments);
        }

        return view('sagawa::admin.queue.index', compact('shipments'));
    }

    /** @return \Illuminate\Contracts\View\View|JsonResponse */
    public function show(Shipment $shipment, Request $request)
    {
        if ($request->wantsJson()) {
            return response()->json($shipment);
        }

        return view('sagawa::admin.queue.show', compact('shipment'));
    }

    /**
     * Trigger async address validation for a shipment.
     */
    public function addressCheck(Shipment $shipment): JsonResponse
    {
        if ($shipment->status !== Shipment::STATUS_PENDING_VERIFICATION) {
            return response()->json(['error' => 'Shipment is not pending verification.'], 422);
        }

        ValidateAddressJob::dispatch($shipment->id);

        return response()->json(['status' => 'queued', 'shipment_id' => $shipment->id]);
    }

    /**
     * Admin manually approves the address (optionally with corrections) and queues waybill issuance.
     */
    public function verify(Shipment $shipment, Request $request): JsonResponse
    {
        $data = $request->validate([
            'corrected_address'            => 'sometimes|array',
            'corrected_address.postcode'   => 'sometimes|string|max:10',
            'corrected_address.prefecture' => 'sometimes|string|max:10',
            'corrected_address.city'       => 'sometimes|string|max:100',
            'corrected_address.address'    => 'sometimes|string|max:500',
            'corrected_address.address2'   => 'sometimes|nullable|string|max:500',
        ]);

        if (! in_array($shipment->status, [
            Shipment::STATUS_PENDING_VERIFICATION,
            Shipment::STATUS_ADDRESS_REJECTED,
        ], true)) {
            return response()->json(['error' => 'Shipment cannot be verified in its current status.'], 422);
        }

        $corrected = $data['corrected_address'] ?? ($shipment->original_address ?? []);

        $shipment->markAddressVerified(
            adminId: (int) auth()->id(),
            corrected: $corrected,
        );

        IssueWaybillJob::dispatch($shipment->id);

        return response()->json(['status' => 'verified', 'shipment_id' => $shipment->id]);
    }

    /**
     * Admin rejects the address with a reason.
     */
    public function reject(Shipment $shipment, Request $request): JsonResponse
    {
        $data = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        if (! in_array($shipment->status, [
            Shipment::STATUS_PENDING_VERIFICATION,
            Shipment::STATUS_ADDRESS_VERIFIED,
        ], true)) {
            return response()->json(['error' => 'Shipment cannot be rejected in its current status.'], 422);
        }

        $shipment->markAddressRejected($data['reason']);

        return response()->json(['status' => 'rejected', 'shipment_id' => $shipment->id]);
    }

    /**
     * Request a waybill reissue. Only within the configurable reissue window.
     */
    public function reissue(Shipment $shipment, CredentialResolver $resolver): JsonResponse
    {
        if ($shipment->status !== Shipment::STATUS_WAYBILL_ISSUED) {
            return response()->json(['error' => 'Waybill has not been issued yet.'], 422);
        }

        $windowHours = (int) config('sagawa.reissue_window_hours', 72);
        if ($shipment->waybill_issued_at && $shipment->waybill_issued_at->diffInHours(now()) > $windowHours) {
            return response()->json(['error' => "Reissue window ({$windowHours}h) has expired."], 422);
        }

        if (! $shipment->tracking_number) {
            return response()->json(['error' => 'No tracking number to reissue.'], 422);
        }

        try {
            $credential = $resolver->for(
                tenantId: function_exists('tenant') ? tenant('id') : null,
                channelId: $shipment->channel_id,
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Credential error.'], 500);
        }

        $client = new SagawaClient($credential);
        $result = $client->retryPrint([$shipment->tracking_number]);

        $code    = (string) ($result['result_code'] ?? '');
        $printId = $result['print_request_id'] ?? null;

        if (str_starts_with($code, 'S') && $printId) {
            $shipment->markReissueRequested();
            $shipment->update([
                'print_request_id'  => $printId,
                'pdf_poll_attempts' => 0,
                'pdf_url'           => null,
                'pdf_expires_at'    => null,
            ]);

            \App\Jobs\Sagawa\PollWaybillPdfJob::dispatch($shipment->id)
                ->delay(now()->addSeconds((int) config('sagawa.pdf_poll_interval', 30)));

            return response()->json([
                'status'           => 'reissue_requested',
                'print_request_id' => $printId,
                'shipment_id'      => $shipment->id,
            ]);
        }

        return response()->json([
            'error'       => 'Sagawa retryPrint failed.',
            'result_code' => $code ?: null,
            'errors'      => $result['errors'] ?? [],
        ], 422);
    }

    /**
     * Redirect to the Sagawa-issued PDF URL so the admin can print from the
     * browser. Opens in a new tab client-side; HTML-only, no regeneration.
     */
    public function printLabel(Shipment $shipment)
    {
        if (! $shipment->pdf_url) {
            return response()->json(['error' => 'Label not available yet.'], 404);
        }

        return redirect()->away($shipment->pdf_url);
    }

    /**
     * Sync tracking/print status from Sagawa. Used by auto-refresh and the
     * admin "Refresh" button. Currently polls print job status via checkFile
     * when a print_request_id exists; falls back to returning current DB state.
     */
    public function syncTracking(Shipment $shipment, CredentialResolver $resolver): JsonResponse
    {
        if (! $shipment->print_request_id) {
            return response()->json([
                'status'          => $shipment->status,
                'tracking_number' => $shipment->tracking_number,
                'pdf_url'         => $shipment->pdf_url,
                'synced'          => false,
            ]);
        }

        try {
            $credential = $resolver->for(
                tenantId: function_exists('tenant') ? tenant('id') : null,
                channelId: $shipment->channel_id,
            );

            $client = new SagawaClient($credential);
            $result = $client->checkFile($shipment->print_request_id);

            $items = $result['items'] ?? [];

            if (! empty($items[0]['pdf_url'])) {
                $shipment->update([
                    'pdf_url'        => $items[0]['pdf_url'],
                    'pdf_expires_at' => $items[0]['expires_at'] ?? null,
                ]);
            }
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Sync failed: '.$e->getMessage()], 500);
        }

        return response()->json([
            'status'          => $shipment->fresh()->status,
            'tracking_number' => $shipment->tracking_number,
            'pdf_url'         => $shipment->fresh()->pdf_url,
            'synced'          => true,
        ]);
    }
}

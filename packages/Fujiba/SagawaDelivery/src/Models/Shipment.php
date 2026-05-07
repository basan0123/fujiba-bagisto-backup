<?php

namespace Fujiba\SagawaDelivery\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Sagawa shipment row. Lives on the tenant DB alongside orders — do NOT set a
 * connection override, the model must follow the current tenant connection.
 */
class Shipment extends Model
{
    protected $table = 'sagawa_shipments';

    // Legacy statuses (kept for code that still references them).
    public const STATUS_PENDING = 'pending_verification';
    public const STATUS_READY   = 'waybill_pending';
    public const STATUS_SHIPPED = 'waybill_issued';
    public const STATUS_FAILED  = 'waybill_failed';

    // Expanded admin-queue statuses.
    public const STATUS_PENDING_VERIFICATION = 'pending_verification';
    public const STATUS_ADDRESS_VERIFIED     = 'address_verified';
    public const STATUS_ADDRESS_REJECTED     = 'address_rejected';
    public const STATUS_WAYBILL_PENDING      = 'waybill_pending';
    public const STATUS_WAYBILL_ISSUED       = 'waybill_issued';
    public const STATUS_WAYBILL_FAILED       = 'waybill_failed';
    public const STATUS_REISSUE_REQUESTED    = 'reissue_requested';

    protected $fillable = [
        'order_id',
        'channel_id',
        'delivery_date',
        'delivery_time',
        'status',
        'tracking_number',
        'raw_response',
        'shipped_at',
        'original_address',
        'corrected_address',
        'address_check_result',
        'address_check_at',
        'verified_by',
        'verified_at',
        'rejection_reason',
        'print_request_id',
        'pdf_url',
        'pdf_expires_at',
        'pdf_poll_attempts',
        'sagawa_raw_request',
        'sagawa_raw_response',
        'waybill_issued_at',
        'waybill_failed_reason',
    ];

    protected $casts = [
        'delivery_date'        => 'date',
        'raw_response'         => 'array',
        'original_address'     => 'array',
        'corrected_address'    => 'array',
        'address_check_result' => 'array',
        'address_check_at'     => 'datetime',
        'verified_at'          => 'datetime',
        'pdf_expires_at'       => 'datetime',
        'pdf_poll_attempts'    => 'int',
        'waybill_issued_at'    => 'datetime',
        'shipped_at'           => 'datetime',
    ];

    public function markPendingVerification(): self
    {
        $this->update(['status' => self::STATUS_PENDING_VERIFICATION]);

        return $this;
    }

    /** @param  array<string,mixed>  $corrected */
    public function markAddressVerified(int $adminId, array $corrected): self
    {
        $this->update([
            'status'            => self::STATUS_ADDRESS_VERIFIED,
            'verified_by'       => $adminId,
            'verified_at'       => now(),
            'corrected_address' => $corrected,
            'rejection_reason'  => null,
        ]);

        return $this;
    }

    public function markAddressRejected(string $reason): self
    {
        $this->update([
            'status'           => self::STATUS_ADDRESS_REJECTED,
            'rejection_reason' => $reason,
        ]);

        return $this;
    }

    public function markWaybillPending(?string $printRequestId = null): self
    {
        $update = ['status' => self::STATUS_WAYBILL_PENDING];
        if ($printRequestId !== null) {
            $update['print_request_id'] = $printRequestId;
        }
        $this->update($update);

        return $this;
    }

    public function markWaybillIssued(string $trackingNumber, ?string $pdfUrl = null, ?CarbonInterface $expiresAt = null): self
    {
        $this->update([
            'status'            => self::STATUS_WAYBILL_ISSUED,
            'tracking_number'   => $trackingNumber,
            'pdf_url'           => $pdfUrl,
            'pdf_expires_at'    => $expiresAt,
            'waybill_issued_at' => now(),
            'shipped_at'        => $this->shipped_at ?? now(),
        ]);

        return $this;
    }

    public function markWaybillFailed(string $reason): self
    {
        $this->update([
            'status'                => self::STATUS_WAYBILL_FAILED,
            'waybill_failed_reason' => $reason,
        ]);

        return $this;
    }

    public function markReissueRequested(): self
    {
        $this->update(['status' => self::STATUS_REISSUE_REQUESTED]);

        return $this;
    }

    // ---- Legacy aliases kept so existing callers keep working. ----

    /** @deprecated Use markWaybillPending() or markWaybillIssued(). */
    public function markShipped(?string $trackingNumber = null, ?string $pdfUrl = null, ?CarbonInterface $expiresAt = null): self
    {
        if ($trackingNumber !== null) {
            return $this->markWaybillIssued($trackingNumber, $pdfUrl, $expiresAt);
        }

        return $this->markWaybillPending();
    }

    /** @deprecated Use markWaybillPending(). */
    public function markReady(): self
    {
        return $this->markWaybillPending();
    }

    /** @deprecated Use markWaybillFailed(). */
    public function markFailed(string $reason = ''): self
    {
        return $this->markWaybillFailed($reason);
    }
}

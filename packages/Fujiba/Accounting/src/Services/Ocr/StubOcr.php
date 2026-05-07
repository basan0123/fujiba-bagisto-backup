<?php

namespace Fujiba\Accounting\Services\Ocr;

/**
 * Deterministic empty OCR result. Used when no external service is configured —
 * the operator types fields manually. Useful in dev/CI and as a graceful
 * fallback when Azure credentials are missing.
 */
class StubOcr implements ReceiptOcrInterface
{
    public function extract(string $absolutePath): array
    {
        return [
            'vendor_name'     => null,
            'vendor_t_number' => null,
            'receipt_date'    => null,
            'total_amount'    => null,
            'tax_amount'      => null,
            'tax_rate'        => null,
            'payment_method'  => null,
            'confidence'      => 0.0,
            'raw_json'        => [
                'driver' => 'stub',
                'note'   => 'No external OCR configured; fields will be entered manually.',
            ],
        ];
    }
}

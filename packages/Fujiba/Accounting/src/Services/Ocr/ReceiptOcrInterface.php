<?php

namespace Fujiba\Accounting\Services\Ocr;

interface ReceiptOcrInterface
{
    /**
     * Extract structured fields from a receipt image/PDF.
     *
     * @return array{
     *   vendor_name: ?string,
     *   vendor_t_number: ?string,
     *   receipt_date: ?string,
     *   total_amount: ?int,
     *   tax_amount: ?int,
     *   tax_rate: ?float,
     *   payment_method: ?string,
     *   confidence: float,
     *   raw_json: array<string,mixed>,
     * }
     */
    public function extract(string $absolutePath): array;
}

<?php

namespace Fujiba\Accounting\Services\Ocr;

use Fujiba\Accounting\Exceptions\OcrServiceException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Azure AI Document Intelligence — prebuilt-receipt model.
 *
 * Flow:
 *   1) POST {endpoint}/documentintelligence/documentModels/{model}:analyze?api-version=…
 *      with the binary file → receives 202 + Operation-Location header
 *   2) Poll Operation-Location until status ∈ {succeeded, failed}
 *   3) Map Azure's `documents[0].fields.*` shape into our normalized array.
 *
 * https://learn.microsoft.com/azure/ai-services/document-intelligence/
 *
 * Pricing (2026-05): ~¥1.5 per receipt on the S0 tier.
 */
class AzureDocIntelligenceOcr implements ReceiptOcrInterface
{
    public function __construct(
        protected string $endpoint,
        protected string $key,
        protected string $model = 'prebuilt-receipt',
        protected string $apiVersion = '2024-11-30',
        protected int $pollIntervalMs = 1000,
        protected int $pollMaxAttempts = 60,
        protected ?ClientInterface $http = null,
    ) {
        $this->endpoint = rtrim($this->endpoint, '/');
        $this->http ??= new Client(['timeout' => 30, 'http_errors' => false]);
    }

    public function extract(string $absolutePath): array
    {
        if (! is_readable($absolutePath)) {
            throw new OcrServiceException("Receipt file not readable: {$absolutePath}");
        }

        $analyzeUrl = sprintf(
            '%s/documentintelligence/documentModels/%s:analyze?api-version=%s',
            $this->endpoint,
            $this->model,
            $this->apiVersion,
        );

        try {
            $resp = $this->http->request('POST', $analyzeUrl, [
                'headers' => [
                    'Ocp-Apim-Subscription-Key' => $this->key,
                    'Content-Type' => $this->mimeFor($absolutePath),
                ],
                'body' => fopen($absolutePath, 'rb'),
            ]);
        } catch (GuzzleException $e) {
            throw new OcrServiceException('Azure analyze request failed: ' . $e->getMessage());
        }

        $status = $resp->getStatusCode();
        $requestId = $resp->getHeaderLine('apim-request-id') ?: null;

        if ($status !== 202) {
            $body = (string) $resp->getBody();
            throw new OcrServiceException(
                "Azure analyze HTTP {$status}: {$body}",
                requestId: $requestId,
                httpStatus: $status,
            );
        }

        $operationUrl = $resp->getHeaderLine('Operation-Location');
        if (! $operationUrl) {
            throw new OcrServiceException('Missing Operation-Location header from analyze response.', requestId: $requestId);
        }

        $result = $this->poll($operationUrl, $requestId);

        Log::info('Fujiba\\Accounting: Azure OCR completed', [
            'request_id' => $requestId,
            'model'      => $this->model,
            'status'     => $result['status'] ?? null,
        ]);

        return $this->mapResult($result);
    }

    protected function poll(string $operationUrl, ?string $requestId): array
    {
        for ($i = 0; $i < $this->pollMaxAttempts; $i++) {
            usleep($this->pollIntervalMs * 1000);

            try {
                $resp = $this->http->request('GET', $operationUrl, [
                    'headers' => ['Ocp-Apim-Subscription-Key' => $this->key],
                ]);
            } catch (GuzzleException $e) {
                throw new OcrServiceException('Azure poll failed: ' . $e->getMessage(), requestId: $requestId);
            }

            $body = json_decode((string) $resp->getBody(), true) ?? [];
            $status = strtolower((string) ($body['status'] ?? ''));

            if ($status === 'succeeded') {
                return $body;
            }
            if ($status === 'failed') {
                throw new OcrServiceException(
                    'Azure analyze failed: ' . json_encode($body['error'] ?? $body),
                    requestId: $requestId,
                );
            }
            // 'running' / 'notStarted' → keep polling
        }

        throw new OcrServiceException(
            "Azure analyze timed out after {$this->pollMaxAttempts} polls.",
            requestId: $requestId,
        );
    }

    /**
     * Normalize Azure's documents[0].fields.* shape into our flat schema.
     */
    protected function mapResult(array $azureBody): array
    {
        $analyze = $azureBody['analyzeResult'] ?? [];
        $documents = $analyze['documents'] ?? [];
        $doc = $documents[0] ?? [];
        $fields = $doc['fields'] ?? [];
        $confidence = (float) ($doc['confidence'] ?? 0.0);

        $vendor = $this->scalar($fields['MerchantName'] ?? null);
        $date = $this->scalar($fields['TransactionDate'] ?? null);
        $total = $this->scalar($fields['Total'] ?? null);
        $tax = $this->scalar($fields['TotalTax'] ?? null);
        $payMethod = $this->scalar($fields['PaymentMethod'] ?? null);

        // T-number: scan all detected text content for the regex.
        $allText = $analyze['content'] ?? '';
        $tNumber = null;
        if (preg_match('/T\d{13}/u', (string) $allText, $m)) {
            $tNumber = $m[0];
        }

        // Tax rate: best-effort from the receipt-level fields if present, otherwise infer.
        $taxRate = null;
        if ($total !== null && $tax !== null && $total > 0) {
            // floor((tax / (total - tax)) * 100) approximation
            $net = $total - $tax;
            if ($net > 0) {
                $taxRate = round(($tax / $net) * 100, 2);
            }
        }

        return [
            'vendor_name'     => $vendor !== null ? (string) $vendor : null,
            'vendor_t_number' => $tNumber,
            'receipt_date'    => $date !== null ? (string) $date : null,
            'total_amount'    => $total !== null ? (int) round((float) $total) : null,
            'tax_amount'      => $tax !== null ? (int) round((float) $tax) : null,
            'tax_rate'        => $taxRate,
            'payment_method'  => $payMethod !== null ? (string) $payMethod : null,
            'confidence'      => $confidence,
            'raw_json'        => $azureBody,
        ];
    }

    /**
     * Pull the canonical scalar value out of an Azure field object regardless of type.
     */
    protected function scalar(mixed $field): mixed
    {
        if (! is_array($field)) {
            return null;
        }
        // valueString / valueNumber / valueDate / valueCurrency.amount / valueDate
        return $field['valueString']
            ?? $field['valueNumber']
            ?? $field['valueDate']
            ?? ($field['valueCurrency']['amount'] ?? null)
            ?? $field['content']
            ?? null;
    }

    protected function mimeFor(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'pdf'         => 'application/pdf',
            'tif', 'tiff' => 'image/tiff',
            default       => 'application/octet-stream',
        };
    }
}

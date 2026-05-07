<?php

namespace App\Http\Controllers\Api\V1\POS;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\EscposImage;

class PrintReceiptController extends Controller
{
    // ── Font paths (served locally, no CDN required) ─────────────────────────
    private const FONT_JA = '/var/www/bagisto/public/fonts/IPAGothic-Regular.ttf';
    private const FONT_MM = '/var/www/bagisto/public/fonts/Pyidaungsu-Regular.ttf';
    private const FONT_EN = '/var/www/bagisto/public/fonts/Hind-Regular.ttf';

    /**
     * Print a synthetic sample receipt — used by the Receipt Settings
     * "Test Print" button so the cashier can verify printer/font/paper-size
     * settings without needing a real order in the system.
     *
     * Reuses printAsImage / printAsEscpos but synthesises an order/items/credit
     * payload from the agent's outlet so headers, logo and store name are real.
     */
    public function testPrint(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'paper_size'   => 'nullable|in:80mm,58mm',
            'receipt_type' => 'nullable|string|max:32',
        ]);

        $paperSize   = $validated['paper_size']   ?? '80mm';
        $receiptType = $validated['receipt_type'] ?? null;
        $width       = $paperSize === '58mm' ? 32 : 48;

        $agent   = auth('pos')->user();
        $outlet  = $agent && $agent->outlet_id
            ? DB::table('pos_outlets')->where('id', $agent->outlet_id)->first()
            : null;
        $receipt = $outlet
            ? DB::table('pos_receipts')->where('id', $outlet->receipt_id)->first()
            : null;

        // Synthetic order/items/credit — no DB write, no inventory touched.
        $order = (object) [
            'id'                  => 'TEST-'.now()->format('YmdHis'),
            'created_at'          => now()->toDateTimeString(),
            'customer_first_name' => 'Test',
            'customer_last_name'  => 'Customer',
            'sub_total'           => 1000,
            'tax_amount'          => 80,
            'discount_amount'     => 0,
            'grand_total'         => 1080,
        ];

        $items = collect([
            (object) ['name' => 'Sample Item A', 'qty_ordered' => 1, 'total' => 500],
            (object) ['name' => 'Sample Item B', 'qty_ordered' => 2, 'total' => 500],
        ]);

        $credit = (object) [
            'tendered_amount' => 1100,
            'change_amount'   => 20,
        ];

        $allText = $items->pluck('name')->implode(' ').' '.($outlet?->name ?? '');

        if ($this->hasNonAscii($allText)) {
            return $this->printAsImage($order, $items, $outlet, $receipt, $credit, $agent, $paperSize, $allText, $receiptType);
        }

        return $this->printAsEscpos($order, $items, $outlet, $receipt, $credit, $agent, $width, $receiptType);
    }

    public function print(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pos_order_id' => 'required|integer',
            'paper_size'   => 'nullable|in:80mm,58mm',
            'printer'      => 'nullable|string|max:255',
            'receipt_type' => 'nullable|string|max:32',
        ]);

        $paperSize   = $validated['paper_size']   ?? '80mm';
        $receiptType = $validated['receipt_type'] ?? null;
        $width       = $paperSize === '58mm' ? 32 : 48;

        // ── Load order data ──────────────────────────────────────────────────
        $posOrder = DB::table('pos_order')->where('id', $validated['pos_order_id'])->first();
        if (! $posOrder) {
            return response()->json(['error' => 'order_not_found'], 404);
        }

        $order = DB::table('orders')->where('id', $posOrder->order_id)->first();
        if (! $order) {
            return response()->json(['error' => 'order_not_found'], 404);
        }

        $items  = DB::table('order_items')->where('order_id', $posOrder->order_id)->get();
        $outlet = DB::table('pos_outlets')->where('id', $posOrder->outlet_id)->first();
        $receipt = $outlet ? DB::table('pos_receipts')->where('id', $outlet->receipt_id)->first() : null;
        $credit  = DB::table('pos_customer_credit')->where('order_id', $posOrder->order_id)->first();
        $agent   = auth('pos')->user();

        // ── Detect non-ASCII text anywhere in the receipt ────────────────────
        $allText = collect($items)->pluck('name')->implode(' ');
        if ($order->customer_first_name) {
            $allText .= ' ' . $order->customer_first_name . ' ' . ($order->customer_last_name ?? '');
        }
        if ($outlet?->name) {
            $allText .= ' ' . $outlet->name;
        }

        $needsUnicode = $this->hasNonAscii($allText);

        if ($needsUnicode) {
            // ── Unicode path: render receipt to PNG bitmap then ESC/POS raster
            return $this->printAsImage($order, $items, $outlet, $receipt, $credit, $agent, $paperSize, $allText, $receiptType);
        }

        // ── ASCII path: plain ESC/POS text (fast, original behaviour) ────────
        return $this->printAsEscpos($order, $items, $outlet, $receipt, $credit, $agent, $width, $receiptType);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ASCII path — ESC/POS text commands
    // ─────────────────────────────────────────────────────────────────────────
    private function printAsEscpos($order, $items, $outlet, $receipt, $credit, $agent, int $width, ?string $receiptType = null): JsonResponse
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'pos_rcpt_');
        try {
            $connector = new FilePrintConnector($tmpPath);
            $p         = new Printer($connector);

            $sep  = str_repeat('-', $width) . "\n";
            $sep2 = str_repeat('=', $width) . "\n";

            $p->text($sep2);
            if ($receiptType === 'ryoshuho') {
                $p->text($this->center('領収証', $width));
            } elseif ($outlet?->name) {
                $p->text($this->center($outlet->name, $width));
            }

            $address = implode(', ', array_filter([
                $outlet->address ?? null,
                $outlet->city    ?? null,
                $outlet->state   ?? null,
            ]));
            if ($address) {
                foreach ($this->wordWrap($address, $width) as $line) {
                    $p->text($line . "\n");
                }
            }

            if ($receipt?->header_content) {
                $plain = $this->htmlToPlain($receipt->header_content, $width);
                foreach (explode("\n", $plain) as $line) {
                    $p->text($line . "\n");
                }
            }

            $p->text($sep);
            $p->text('Date   : ' . $this->formatDate($order->created_at) . "\n");
            $p->text('Order  : #' . $order->id . "\n");

            if ($order->customer_first_name) {
                $cust = trim($order->customer_first_name . ' ' . ($order->customer_last_name ?? ''));
                $p->text('Customer: ' . $cust . "\n");
            }

            $p->text($sep);

            $qtyW  = 4;
            $totW  = 9;
            $nameW = max(8, $width - $qtyW - $totW - 2);

            $p->text(str_pad('Item', $nameW) . str_pad('Qty', $qtyW, ' ', STR_PAD_LEFT) . str_pad('Total', $totW, ' ', STR_PAD_LEFT) . "\n");
            $p->text($sep);

            foreach ($items as $item) {
                $name  = $item->name ?? '';
                $qty   = (int) ($item->qty_ordered ?? 1);
                $total = number_format((float) ($item->total ?? 0), 2);
                $first = true;

                foreach ($this->wordWrap($name, $nameW) as $namePart) {
                    if ($first) {
                        $nameLen = mb_strlen($namePart, 'UTF-8');
                        $namePad = $nameLen < $nameW
                            ? $namePart . str_repeat(' ', $nameW - $nameLen)
                            : mb_substr($namePart, 0, $nameW, 'UTF-8');
                        $p->text($namePad . str_pad($qty, $qtyW, ' ', STR_PAD_LEFT) . str_pad($total, $totW, ' ', STR_PAD_LEFT) . "\n");
                        $first = false;
                    } else {
                        $p->text('  ' . $namePart . "\n");
                    }
                }
            }

            $p->text($sep);
            $p->text($this->twoCol('Subtotal', number_format((float) ($order->sub_total ?? 0), 2), $width) . "\n");

            if ((float) ($order->tax_amount ?? 0) > 0) {
                $p->text($this->twoCol('Tax', number_format((float) $order->tax_amount, 2), $width) . "\n");
            }
            if ((float) ($order->discount_amount ?? 0) > 0) {
                $p->text($this->twoCol('Discount', '-' . number_format((float) $order->discount_amount, 2), $width) . "\n");
            }

            $p->text($sep);
            $grandLabel = $receipt?->grand_total_label ?: 'TOTAL';
            $p->text($this->twoCol('*** ' . $grandLabel . ' ***', number_format((float) ($order->grand_total ?? 0), 2), $width) . "\n");
            $p->text($sep);

            if ($credit) {
                if ((float) ($credit->tendered_amount ?? 0) > 0) {
                    $p->text($this->twoCol('Paid', number_format((float) $credit->tendered_amount, 2), $width) . "\n");
                }
                if ((float) ($credit->change_amount ?? 0) > 0) {
                    $p->text($this->twoCol('Change', number_format((float) $credit->change_amount, 2), $width) . "\n");
                }
            }

            $p->text($sep2);

            if ($receipt?->footer_content) {
                $plain = $this->htmlToPlain($receipt->footer_content, $width);
                foreach (explode("\n", $plain) as $line) {
                    $p->text($this->center($line, $width));
                }
            }

            $p->text($this->center('Thank you!', $width));
            $p->text($sep2);
            $p->feed(4);
            $p->cut();
            $p->close();

            $rawBytes = file_get_contents($tmpPath);
            @unlink($tmpPath);

            return response()->json([
                'success' => true,
                'raw'     => base64_encode($rawBytes),
                'html'    => $this->buildHtmlReceipt($order, $items, $outlet, $receipt, $credit, $width === 32 ? '58mm' : '80mm', $receiptType),
            ]);

        } catch (\Throwable $e) {
            @unlink($tmpPath);
            return response()->json(['success' => false, 'message' => 'Print failed: ' . $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Unicode path — render receipt to a PNG bitmap with GD + TTF,
    // then encode as ESC/POS raster (bitImage).
    // Uses Pyidaungsu for Myanmar, IPA Gothic for Japanese, Hind for Latin.
    // ─────────────────────────────────────────────────────────────────────────
    private function printAsImage($order, $items, $outlet, $receipt, $credit, $agent, string $paperSize, string $allText, ?string $receiptType = null): JsonResponse
    {
        // Detect which Unicode block dominates → pick font
        $hasMM = (bool) preg_match('/[\x{1000}-\x{109F}\x{AA60}-\x{AA7F}]/u', $allText);
        $hasJP = (bool) preg_match('/[\x{3000}-\x{9FFF}\x{F900}-\x{FAFF}]/u', $allText);

        $fontPath = match (true) {
            $hasMM  => self::FONT_MM,
            $hasJP  => self::FONT_JA,
            default => self::FONT_EN,
        };

        // Fallback chain if preferred font is missing
        if (! file_exists($fontPath)) {
            $fontPath = file_exists(self::FONT_EN) ? self::FONT_EN : null;
        }
        if (! $fontPath) {
            // No TTF available — fall back to ESC/POS text with UTF-8 pass-through
            return $this->printAsEscpos($order, $items, $outlet, $receipt, $credit, $agent,
                $paperSize === '58mm' ? 32 : 48);
        }

        // ── Build receipt lines ───────────────────────────────────────────────
        $lines   = [];
        $pxWidth  = $paperSize === '58mm' ? 384 : 576;  // standard thermal dot widths
        $fontSize = 22;
        $lineH    = (int) ($fontSize * 1.5);
        $margin   = 12;

        if ($receiptType === 'ryoshuho') {
            $lines[] = ['text' => '領収証', 'size' => 28, 'center' => true];
            $lines[] = ['text' => '（正式領収書）', 'size' => 18, 'center' => true];
        } elseif ($outlet?->name) {
            $lines[] = ['text' => $outlet->name, 'size' => 26, 'center' => true];
        }

        $address = implode(', ', array_filter([
            $outlet->address ?? null,
            $outlet->city    ?? null,
            $outlet->state   ?? null,
        ]));
        if ($address) {
            $maxAddrPx = $pxWidth - $margin * 2;
            foreach ($this->gdWordWrap($address, 18, $fontPath, $maxAddrPx) as $addrLine) {
                $lines[] = ['text' => $addrLine, 'size' => 18, 'center' => true];
            }
        }

        $lines[] = ['sep' => true];
        $lines[] = ['text' => $this->formatDate($order->created_at)];
        $lines[] = ['text' => 'Order #' . $order->id];

        $lines[] = ['sep' => true];

        // Pre-measure right column so product names wrap correctly
        foreach ($items as $item) {
            $name  = $item->name ?? '';
            $qty   = (int) ($item->qty_ordered ?? 1);
            $total = number_format((float) ($item->total ?? 0), 2);
            $rval  = 'x' . $qty . '  ' . $total;

            $rbbox   = imagettfbbox($fontSize, 0, $fontPath, $rval);
            $rw      = abs($rbbox[4] - $rbbox[0]);
            $maxLeft = $pxWidth - $margin * 2 - $rw - 10;

            $nameLines = $this->gdWordWrap($name, $fontSize, $fontPath, $maxLeft);

            // First line: name left + price right
            $lines[] = ['text' => $nameLines[0], 'right' => $rval];

            // Continuation lines (indented, no right value)
            for ($i = 1; $i < count($nameLines); $i++) {
                $lines[] = ['text' => '  ' . $nameLines[$i]];
            }
        }

        $lines[] = ['sep' => true];
        $lines[] = ['text' => 'Subtotal', 'right' => number_format((float) ($order->sub_total ?? 0), 2)];

        if ((float) ($order->tax_amount ?? 0) > 0) {
            $lines[] = ['text' => 'Tax', 'right' => number_format((float) $order->tax_amount, 2)];
        }
        if ((float) ($order->discount_amount ?? 0) > 0) {
            $lines[] = ['text' => 'Discount', 'right' => '-' . number_format((float) $order->discount_amount, 2)];
        }

        $lines[] = ['sep' => true];
        $lines[] = ['text' => 'TOTAL', 'right' => number_format((float) ($order->grand_total ?? 0), 2), 'bold' => true];

        if ($credit) {
            if ((float) ($credit->tendered_amount ?? 0) > 0) {
                $lines[] = ['text' => 'Paid', 'right' => number_format((float) $credit->tendered_amount, 2)];
            }
            if ((float) ($credit->change_amount ?? 0) > 0) {
                $lines[] = ['text' => 'Change', 'right' => number_format((float) $credit->change_amount, 2)];
            }
        }

        $lines[] = ['sep' => true];
        $lines[] = ['text' => 'Thank you!', 'center' => true];

        // ── Calculate image height ────────────────────────────────────────────
        $imgH = 20; // top padding
        foreach ($lines as $line) {
            $imgH += isset($line['sep']) ? 6 : $lineH;
        }
        $imgH += 20; // bottom padding

        // ── Create GD image ───────────────────────────────────────────────────
        $img   = imagecreate($pxWidth, $imgH);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);

        $y = 20;

        foreach ($lines as $line) {
            if (isset($line['sep'])) {
                $sepY = $y + 2;
                imageline($img, $margin, $sepY, $pxWidth - $margin, $sepY, $black);
                $y += 6;
                continue;
            }

            $text  = $line['text'] ?? '';
            $size  = $line['size'] ?? $fontSize;
            $cx    = $line['center'] ?? false;

            if ($cx) {
                // Center the text
                $bbox = imagettfbbox($size, 0, $fontPath, $text);
                $tw   = abs($bbox[4] - $bbox[0]);
                $tx   = (int) (($pxWidth - $tw) / 2);
                imagettftext($img, $size, 0, $tx, $y + $size, $black, $fontPath, $text);
            } elseif (isset($line['right'])) {
                // Left text + right-aligned value
                imagettftext($img, $size, 0, $margin, $y + $size, $black, $fontPath, $text);
                $rval = $line['right'];
                $rbbox = imagettfbbox($size, 0, $fontPath, $rval);
                $rw    = abs($rbbox[4] - $rbbox[0]);
                imagettftext($img, $size, 0, $pxWidth - $margin - $rw, $y + $size, $black, $fontPath, $rval);
            } else {
                imagettftext($img, $size, 0, $margin, $y + $size, $black, $fontPath, $text);
            }

            $y += $lineH;
        }

        // ── Save PNG to temp file ─────────────────────────────────────────────
        $tmpPng = tempnam(sys_get_temp_dir(), 'pos_img_') . '.png';
        imagepng($img, $tmpPng);
        imagedestroy($img);

        // ── Convert PNG to ESC/POS raster ─────────────────────────────────────
        $tmpEscpos = tempnam(sys_get_temp_dir(), 'pos_rcpt_');
        try {
            $connector = new FilePrintConnector($tmpEscpos);
            $p         = new Printer($connector);

            $escImg = EscposImage::load($tmpPng, false);
            $p->bitImage($escImg);
            $p->feed(4);
            $p->cut();
            $p->close();

            $rawBytes = file_get_contents($tmpEscpos);

            return response()->json([
                'success' => true,
                'raw'     => base64_encode($rawBytes),
                'html'    => $this->buildHtmlReceipt($order, $items, $outlet, $receipt, $credit, $paperSize, $receiptType),
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Image print failed: ' . $e->getMessage()], 500);
        } finally {
            @unlink($tmpPng);
            @unlink($tmpEscpos);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Measure the pixel width of a string rendered with imagettftext.
     */
    private function gdTextWidth(string $text, float $size, string $font): int
    {
        if ($text === '') {
            return 0;
        }
        $bbox = imagettfbbox($size, 0, $font, $text);
        return (int) abs($bbox[4] - $bbox[0]);
    }

    /**
     * Wrap $text so each line fits within $maxPx pixels using TTF font metrics.
     * Handles CJK/Myanmar characters (no space boundaries) via character-level splits.
     */
    private function gdWordWrap(string $text, float $size, string $font, int $maxPx): array
    {
        $text  = trim($text);
        $lines = [];

        // Split on whitespace, keeping the delimiter so we can re-attach spaces
        $words   = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $current = '';

        foreach ($words as $word) {
            $test = $current === '' ? $word : $current . $word;

            if ($this->gdTextWidth($test, $size, $font) <= $maxPx) {
                $current = $test;
                continue;
            }

            // Flush current line
            if ($current !== '') {
                $lines[] = rtrim($current);
            }

            // The word itself may be wider than maxPx — split by character
            $remaining = ltrim($word);
            while ($remaining !== '' && $this->gdTextWidth($remaining, $size, $font) > $maxPx) {
                $len = mb_strlen($remaining, 'UTF-8');
                $lo  = 1;
                $hi  = $len;
                while ($lo < $hi) {
                    $mid = (int) (($lo + $hi + 1) / 2);
                    if ($this->gdTextWidth(mb_substr($remaining, 0, $mid, 'UTF-8'), $size, $font) <= $maxPx) {
                        $lo = $mid;
                    } else {
                        $hi = $mid - 1;
                    }
                }
                $lines[]   = mb_substr($remaining, 0, $lo, 'UTF-8');
                $remaining = mb_substr($remaining, $lo, null, 'UTF-8');
            }

            $current = $remaining;
        }

        if ($current !== '') {
            $lines[] = rtrim($current);
        }

        return $lines ?: [''];
    }

    private function hasNonAscii(string $text): bool
    {
        return (bool) preg_match('/[^\x20-\x7E]/', $text);
    }

    private function center(string $text, int $width): string
    {
        $len = mb_strlen($text, 'UTF-8');
        if ($len >= $width) {
            return mb_substr($text, 0, $width, 'UTF-8') . "\n";
        }
        $pad = (int) (($width - $len) / 2);
        return str_repeat(' ', $pad) . $text . "\n";
    }

    private function twoCol(string $label, string $value, int $width): string
    {
        $space = $width - mb_strlen($label, 'UTF-8') - mb_strlen($value, 'UTF-8');
        if ($space < 1) {
            $space = 1;
            $label = mb_substr($label, 0, $width - mb_strlen($value, 'UTF-8') - 1, 'UTF-8');
        }
        return $label . str_repeat(' ', $space) . $value;
    }

    private function wordWrap(string $text, int $width): array
    {
        $text  = trim($text);
        $lines = [];
        while (mb_strlen($text, 'UTF-8') > $width) {
            $cut = mb_substr($text, 0, $width, 'UTF-8');
            $pos = mb_strrpos($cut, ' ');
            if ($pos === false) {
                $pos = $width;
            }
            $lines[] = mb_substr($text, 0, $pos, 'UTF-8');
            $text    = ltrim(mb_substr($text, $pos, null, 'UTF-8'));
        }
        if ($text !== '') {
            $lines[] = $text;
        }
        return $lines ?: [''];
    }

    private function htmlToPlain(string $html, int $width): string
    {
        $text = strip_tags(html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $text = preg_replace('/\s+/', ' ', trim($text));
        return implode("\n", $this->wordWrap($text, $width));
    }

    private function formatDate(string $datetime): string
    {
        try {
            return \Carbon\Carbon::parse($datetime)->format('d/m/Y H:i');
        } catch (\Throwable) {
            return $datetime;
        }
    }

    private function buildHtmlReceipt($order, $items, $outlet, $receipt, $credit, string $paperSize, ?string $receiptType): string
    {
        $width = $paperSize === '58mm' ? '58mm' : '80mm';
        $e     = fn ($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        $fmt   = fn ($v) => number_format((float) ($v ?? 0), 2);

        $title = $receiptType === 'ryoshuho' ? '領収証' : ($outlet?->name ?? 'Receipt');

        $address = implode(', ', array_filter([
            $outlet->address ?? null,
            $outlet->city    ?? null,
            $outlet->state   ?? null,
        ]));

        $itemRows = '';
        foreach ($items as $item) {
            $qty   = (int) ($item->qty_ordered ?? 1);
            $total = $fmt($item->total ?? 0);
            $price = $fmt($item->price ?? 0);
            $itemRows .= '<tr><td>' . $e($item->name) . '</td>'
                . '<td class="r">' . $qty . '</td>'
                . '<td class="r">' . $price . '</td>'
                . '<td class="r">' . $total . '</td></tr>';
        }

        $taxAmt      = (float) ($order->tax_amount ?? 0);
        $discountAmt = (float) ($order->discount_amount ?? 0);
        $tendered    = (float) ($credit->tendered_amount ?? 0);
        $change      = (float) ($credit->change_amount ?? 0);
        $grandLabel  = $receipt?->grand_total_label ?: 'TOTAL';

        $html  = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1">';
        $html .= '<title>' . $e($title) . '</title><style>';
        $html .= "@font-face{font-family:'IPAGothic';src:url('/fonts/IPAGothic-Regular.ttf') format('truetype')}";
        $html .= "@font-face{font-family:'Pyidaungsu';src:url('/fonts/Pyidaungsu-Regular.ttf') format('truetype')}";
        $html .= "@font-face{font-family:'Hind';src:url('/fonts/Hind-Regular.ttf') format('truetype')}";
        // Lock @page paper size for browsers that honor it (Chrome ≥99 + thermal driver, Firefox print dialog).
        // Repeated declarations + !important so user-stylesheet / preview UI cannot override.
        $html .= "@page{size:{$width} auto !important;margin:3mm 2mm !important}";
        $html .= "@page :first{size:{$width} auto !important;margin:3mm 2mm !important}";
        $html .= "*{margin:0;padding:0;box-sizing:border-box}";
        $html .= "html,body{width:{$width} !important;max-width:{$width} !important;margin:0 !important;padding:0 !important;background:#fff}";
        $html .= "body{font-family:'IPAGothic','Pyidaungsu','Hind','Courier New',monospace;font-size:11px}";
        // Print-only: kill any browser fit-to-page scaling and pin the receipt to the configured roll width.
        $html .= "@media print{";
        $html .= "html,body{width:{$width} !important;max-width:{$width} !important;margin:0 !important;padding:0 !important;-webkit-print-color-adjust:exact;print-color-adjust:exact;zoom:1 !important;transform:none !important}";
        $html .= ".no-print{display:none !important}";
        $html .= "}";
        $html .= "h1{font-size:14px;text-align:center;margin-bottom:2px}";
        $html .= ".c{text-align:center}.r{text-align:right}";
        $html .= "hr{border:none;border-top:1px dashed #000;margin:4px 0}";
        $html .= "table{width:100%;border-collapse:collapse}";
        $html .= "th,td{padding:1px 2px;font-size:11px}";
        $html .= "th{text-align:left;border-bottom:1px dashed #000}";
        $html .= "th.r,td.r{text-align:right}";
        $html .= ".grand td{font-size:13px;font-weight:bold;border-top:1px dashed #000;padding-top:3px}";
        $html .= ".addr{font-size:10px;text-align:center;margin-bottom:2px}";
        // On-screen helper bar (hidden when printing). Tells the cashier which paper size to pick
        // if the browser dialog defaults wrong, and exposes a manual reprint button.
        $html .= ".hint-bar{position:fixed;top:0;left:0;right:0;background:#1C1C1E;color:#fff;font:600 12px/1.4 system-ui,sans-serif;padding:8px 12px;display:flex;justify-content:space-between;align-items:center;z-index:9999}";
        $html .= ".hint-bar button{background:#00B8D9;color:#fff;border:0;border-radius:6px;padding:6px 12px;font:inherit;cursor:pointer}";
        $html .= ".hint-bar small{opacity:.85;font-weight:400}";
        $html .= ".rcpt{margin-top:44px}";
        $html .= '</style></head><body>';
        $html .= '<div class="hint-bar no-print">';
        $html .= '<span>Paper size: <strong>' . $width . '</strong> &nbsp; <small>If preview shows A4, change Paper Size in the dialog.</small></span>';
        $html .= '<button onclick="window.print()">Print</button>';
        $html .= '</div>';
        $html .= '<div class="rcpt">';

        $html .= '<h1>' . $e($title) . '</h1>';
        if ($receiptType === 'ryoshuho') {
            $html .= '<div class="c" style="font-size:10px;margin-bottom:4px;">（正式領収書）</div>';
        }
        if ($address) {
            $html .= '<div class="addr">' . $e($address) . '</div>';
        }
        $html .= '<div class="c">' . $e($this->formatDate($order->created_at)) . '</div>';
        $html .= '<div class="c">Order #' . $e($order->id) . '</div>';
        $html .= '<hr>';
        $html .= '<table><thead><tr><th>Item</th><th class="r">Qty</th><th class="r">Price</th><th class="r">Total</th></tr></thead>';
        $html .= '<tbody>' . $itemRows . '</tbody></table>';
        $html .= '<hr><table>';
        $html .= '<tr><td>Subtotal</td><td class="r">' . $fmt($order->sub_total ?? 0) . '</td></tr>';
        if ($taxAmt > 0) {
            $html .= '<tr><td>Tax</td><td class="r">' . $fmt($taxAmt) . '</td></tr>';
        }
        if ($discountAmt > 0) {
            $html .= '<tr><td>Discount</td><td class="r">-' . $fmt($discountAmt) . '</td></tr>';
        }
        $html .= '<tr class="grand"><td>' . $e($grandLabel) . '</td><td class="r">' . $fmt($order->grand_total ?? 0) . '</td></tr>';
        if ($tendered > 0) {
            $html .= '<tr><td>Paid</td><td class="r">' . $fmt($tendered) . '</td></tr>';
        }
        if ($change > 0) {
            $html .= '<tr><td>Change</td><td class="r">' . $fmt($change) . '</td></tr>';
        }
        $html .= '</table>';
        $html .= '<hr><div class="c" style="margin-top:6px">Thank you!</div>';
        $html .= '</div>'; // /.rcpt
        // Auto-print after fonts are ready. Don't auto-close — keep popup open
        // so the cashier can re-print or adjust paper size if the OS dialog
        // defaulted to A4. Cashier closes it manually.
        $html .= "<script>window.addEventListener('load',function(){var go=function(){try{window.focus();window.print();}catch(e){}};document.fonts&&document.fonts.ready?document.fonts.ready.then(go).catch(go):go();});</script>";
        $html .= '</body></html>';

        return $html;
    }
}

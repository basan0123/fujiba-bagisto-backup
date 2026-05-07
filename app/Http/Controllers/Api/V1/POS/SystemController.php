<?php

namespace App\Http\Controllers\Api\V1\POS;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SystemController extends Controller
{
    /**
     * Return the list of CUPS printer names visible to the server process.
     * The POS app can pre-populate a printer dropdown from this list and
     * fall back gracefully when lpstat is unavailable (e.g. cloud servers).
     */
    public function printers(): JsonResponse
    {
        $printers = $this->getCupsPrinters();

        return response()->json(['printers' => $printers]);
    }

    private function getCupsPrinters(): array
    {
        // lpstat -a lists all accepting queues; -p lists enabled printers.
        // We use -a and parse "printer-name accepting …" lines.
        $output = @shell_exec('lpstat -a 2>/dev/null');

        if (! $output) {
            return [];
        }

        $printers = [];
        foreach (explode("\n", trim($output)) as $line) {
            if (preg_match('/^(\S+)\s+accepting/', $line, $m)) {
                $printers[] = $m[1];
            }
        }

        return $printers;
    }
}

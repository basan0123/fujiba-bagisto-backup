<?php

namespace Fujiba\AutoPoster\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

/**
 * Owner approval for Content Engine v2 previews.
 *
 * Lists storage/app/autoposter/preview/<dir>/ assets with their fabric-gate
 * metrics and records Approve / Reject in <dir>/approvals.json.
 * It ONLY records the decision — it never publishes, queues or enqueues
 * anything, and never touches the database.
 */
class PreviewApprovalController extends Controller
{
    private const DIR_RX = '/^\d{4}-\d{2}-\d{2}[A-Za-z0-9_-]*$/';

    private const KEY_RX = '/^[A-Za-z0-9_.-]+$/';

    public function index(Request $request)
    {
        $dirs = $this->dirs();
        $dir = $request->query('dir');
        $dir = in_array($dir, $dirs, true) ? $dir : ($dirs[0] ?? null);

        return view('autoposter::admin.previews.index', [
            'dirs' => $dirs,
            'dir' => $dir,
            'assets' => $dir ? $this->assets($dir) : [],
        ]);
    }

    /** Streams a delivery file / gate mask from a preview dir (read-only). */
    public function media(string $dir, string $file)
    {
        abort_unless(preg_match(self::DIR_RX, $dir) && preg_match(self::KEY_RX, $file), 404);
        abort_unless(preg_match('/\.(jpg|mp4|gatemask\.png)$/', $file), 404);
        $path = realpath($this->root().'/'.$dir.'/'.$file);
        abort_unless($path && str_starts_with($path, realpath($this->root()).DIRECTORY_SEPARATOR) && is_file($path), 404);

        return response()->file($path, ['Cache-Control' => 'private, max-age=300']);
    }

    public function decide(Request $request, string $dir, string $key)
    {
        abort_unless(preg_match(self::DIR_RX, $dir) && preg_match(self::KEY_RX, $key), 404);
        $data = $request->validate([
            'decision' => 'required|in:approved,rejected',
            'note' => 'nullable|string|max:500',
        ]);
        $base = $this->root().'/'.$dir;
        abort_unless(is_file("{$base}/{$key}.gate.json"), 404);

        $file = "{$base}/approvals.json";
        $fh = fopen($file, 'c+');
        flock($fh, LOCK_EX);
        $all = json_decode(stream_get_contents($fh) ?: '{}', true) ?: [];
        $admin = auth('admin')->user();
        $all[$key] = [
            'decision' => $data['decision'],
            'note' => $data['note'] ?? null,
            'by_id' => $admin?->id,
            'by' => $admin?->name,
            'at' => now('Asia/Tokyo')->toIso8601String(),
            'gate_sha1' => sha1_file("{$base}/{$key}.gate.json"),
            'history' => array_merge($all[$key]['history'] ?? [], isset($all[$key]) ? [array_diff_key($all[$key], ['history' => 1])] : []),
        ];
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        flock($fh, LOCK_UN);
        fclose($fh);

        return redirect()->route('admin.autoposter.previews', ['dir' => $dir])
            ->with('success', "{$key}: {$data['decision']} (recorded only — nothing was posted)");
    }

    private function root(): string
    {
        return storage_path('app/autoposter/preview');
    }

    private function dirs(): array
    {
        if (! is_dir($this->root())) {
            return [];
        }
        $d = array_values(array_filter(array_map('basename', File::directories($this->root())), fn ($n) => preg_match(self::DIR_RX, $n)));
        rsort($d);

        return $d;
    }

    private function assets(string $dir): array
    {
        $base = $this->root().'/'.$dir;
        $decisions = json_decode((string) @file_get_contents("{$base}/approvals.json"), true) ?: [];
        $out = [];
        foreach (glob("{$base}/*.gate.json") as $g) {
            $key = basename($g, '.gate.json');
            $gate = json_decode((string) file_get_contents($g), true) ?: [];
            $delivery = is_file("{$base}/{$key}.jpg") ? "{$key}.jpg" : (is_file("{$base}/{$key}.mp4") ? "{$key}.mp4" : null);
            $side = $delivery ? (json_decode((string) @file_get_contents("{$base}/{$delivery}.json"), true) ?: []) : [];
            $out[] = [
                'key' => $key,
                'passed' => (bool) ($gate['passed'] ?? false),
                'summary' => $gate['summary'] ?? [],
                'failed' => array_values(array_filter($gate['checks'] ?? [], fn ($c) => ($c['pass'] ?? null) === false)),
                'delivery' => $delivery,
                'video' => $delivery && str_ends_with($delivery, '.mp4'),
                'mask' => is_file("{$base}/{$key}.gatemask.png") ? "{$key}.gatemask.png" : null,
                'drift' => $side['delivery_drift_deltaE00'] ?? null,
                'decision' => $decisions[$key] ?? null,
            ];
        }

        return $out;
    }
}

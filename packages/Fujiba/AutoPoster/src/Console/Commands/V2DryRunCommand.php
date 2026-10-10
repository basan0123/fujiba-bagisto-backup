<?php

namespace Fujiba\AutoPoster\Console\Commands;

use Fujiba\AutoPoster\Models\AutoPosterPatternKnowledge;
use Fujiba\AutoPoster\Services\Intel\DuplicateImageGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * autoposter:v2-dryrun — Content Engine v2 first-run preview.
 *
 * Renders 3 images (H colour set, J folded stack, B pattern story) and 2 reels
 * (dragon_story, gift_to_mum) from the pattern-knowledge records, runs the
 * fabric gate on the LOSSLESS master (bin/intel/fabric_gate.py --master, max 2
 * retries), derives the JPEG/MP4 delivery file only after a pass (bin/v2/deliver.py),
 * logs each asset to autoposter_creative_log (dry_run=1) and writes
 * storage/app/autoposter/preview/<date>/.
 * NOTHING is queued or posted. Runs inside a tenant context (tenants:run).
 */
class V2DryRunCommand extends Command
{
    protected $signature = 'autoposter:v2-dryrun {--only= : comma list of asset keys}';

    protected $description = 'Content Engine v2: render preview assets + quality gate (no posting).';

    private string $bin;

    private string $dir;

    public function handle(DuplicateImageGuard $guard): int
    {
        $this->bin = dirname(__DIR__, 3).'/bin/v2';
        $this->dir = storage_path('app/autoposter/preview/'.now('Asia/Tokyo')->format('Y-m-d'));
        @mkdir($this->dir, 0775, true);

        $k = AutoPosterPatternKnowledge::all()->keyBy('name');
        $need = ['FV-59-A', 'FV-73-C', 'FV-81-A', 'FV-10-A', 'FV-61-C'];
        if ($missing = array_diff($need, $k->keys()->all())) {
            $this->error('Missing knowledge for '.implode(', ', $missing).' — run autoposter:knowledge import');

            return 1;
        }
        $price = (int) DB::table('product_flat')->where('product_id', $k['FV-73-C']->product_id)->value('price');

        $assets = [
            'H_colourset_FV-59' => ['image', 'H', ['products' => [$this->p($k['FV-59-A'])], 'set' => $this->colourways('FV-59'),
                'set_title' => 'Rising Dragon · 4 colours', 'set_title_ja' => '荒波に昇龍 — 全4色']],
            'J_stack_five' => ['image', 'J', ['products' => array_map(fn ($n) => $this->p($k[$n]), ['FV-61-C', 'FV-10-A', 'FV-81-A', 'FV-59-A', 'FV-73-C']), 'price' => $price]],
            'B_story_FV-73-C' => ['image', 'B', ['products' => [$this->p($k['FV-73-C'])], 'story' => [
                'title' => '鶴  The Crane',
                'lines' => ['In Japan, the crane is a symbol of', 'long life and good fortune.',
                    'ဂျပန်မှာ ကြိုးကြာငှက်ဟာ အသက်ရှည်ခြင်း', 'နဲ့ ကံကောင်းခြင်းကို ကိုယ်စားပြုပါတယ်',
                    'जापानमा सारस दीर्घायु र सौभाग्यको प्रतीक हो'],
                'grade' => 'B']]],
            'R1_dragon_story_FV-59' => ['video', 'dragon_story', ['products' => [$this->p($k['FV-59-A'])], 'set' => $this->colourways('FV-59')]],
            'R2_gift_to_mum_FV-73-C' => ['video', 'gift_to_mum', ['products' => [$this->p($k['FV-73-C'])], 'stack_image' => "{$this->dir}/J_stack_five.png"]],
        ];
        $only = array_filter(explode(',', (string) $this->option('only')));
        $rows = [];

        foreach ($assets as $key => [$kind, $tpl, $manifest]) {
            if ($only && ! in_array($key, $only, true)) {
                continue;
            }
            $ids = array_merge(array_column($manifest['products'], 'id'), array_column($manifest['set'] ?? [], 'id'));
            if ($blocked = $guard->filter($ids)) {   // unresolved duplicate-image group => refuse
                foreach ($blocked as $pid => $why) {
                    $this->line("{$key}: product {$pid} {$why}");
                }
                $rows[] = [$key, 'BLOCKED', '-', DuplicateImageGuard::REASON];

                continue;
            }
            $master = "{$this->dir}/{$key}.".($kind === 'image' ? 'png' : 'mkv');   // lossless — the only thing the gate trusts
            $out = "{$this->dir}/{$key}.".($kind === 'image' ? 'jpg' : 'mp4');       // delivery, derived after a pass
            $mf = "{$this->dir}/{$key}.manifest.json";
            file_put_contents($mf, json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            $gate = null;
            for ($try = 1; $try <= 3; $try++) {          // first render + max 2 retries
                $t0 = microtime(true);
                exec($this->py($kind === 'image' ? 'compose.py' : 'reel.py', [$mf, $tpl, $master]).' 2>&1', $o, $rc);
                if ($rc !== 0) {
                    $gate = ['passed' => false, 'checks' => [['check' => 'render', 'pass' => false, 'value' => implode("\n", array_slice($o, -5))]]];

                    continue;
                }
                $g = shell_exec($this->py('../intel/fabric_gate.py', ['--master', $master, '--mask-out', "{$this->dir}/{$key}.gatemask.png"]));
                $gate = json_decode((string) $g, true) ?: ['passed' => false, 'checks' => [['check' => 'gate', 'pass' => false]]];
                $gate['render_seconds'] = round(microtime(true) - $t0, 1);
                $gate['attempt'] = $try;
                if ($gate['passed']) {
                    break;
                }
            }
            if ($gate['passed']) {
                file_put_contents("{$this->dir}/{$key}.gate.json", json_encode($gate, JSON_UNESCAPED_UNICODE));
                $d = json_decode((string) shell_exec($this->py('deliver.py', [$master, "{$this->dir}/{$key}.gate.json", $out])), true);
                if (! ($d['delivered'] ?? false)) {
                    $gate['passed'] = false;
                    $gate['checks'][] = ['check' => 'deliver', 'pass' => false, 'value' => $d['error'] ?? 'delivery failed'];
                } else {
                    $gate['delivery_drift_deltaE00'] = $d['delivery_drift_deltaE00'];
                }
            }
            $side = json_decode((string) @file_get_contents("{$master}.json"), true) ?: [];
            $combo = $side['combo'] ?? ['layout' => $tpl];
            DB::table('autoposter_creative_log')->insert([
                'queue_id' => null, 'product_ids' => json_encode(array_values(array_unique(array_merge(
                    array_column($manifest['products'], 'id'), array_column($manifest['set'] ?? [], 'id'))))),
                'kind' => $kind, 'template' => $tpl, 'scenario' => $kind === 'video' ? $tpl : null,
                'combo' => json_encode($combo, JSON_UNESCAPED_UNICODE), 'combo_key' => substr(sha1($tpl.json_encode($combo)), 0, 16),
                'gate' => json_encode($gate, JSON_UNESCAPED_UNICODE), 'gate_passed' => (bool) $gate['passed'],
                'file' => $gate['passed'] ? $out : null, 'cost_jpy' => 0, 'dry_run' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
            if (! $gate['passed']) {
                @rename($master, "{$master}.FAILED");      // a failed master never gets a delivery file
                @unlink($out);
            }
            $rows[] = [$key, $gate['passed'] ? 'PASS' : 'FAIL', $gate['attempt'] ?? '-', $this->worst($gate)];
        }
        $this->table(['asset', 'gate', 'try', 'worst fabric region (ΔE00 / SSIM / ORB inliers)'], $rows);
        $this->line("preview: {$this->dir}");

        return 0;
    }

    private function p(AutoPosterPatternKnowledge $k): array
    {
        $root = storage_path('app/public/tenants/1/'.tenancy()->tenant->getTenantKey());

        return ['id' => $k->product_id, 'name' => $k->name, 'image' => $root.'/'.$k->closeup['image'], 'box' => $k->closeup['box'],
            'focus' => $k->closeup['focus'] ?? $k->closeup['box'], 'ground' => $k->colors[0]['hex'] ?? '#202020'];
    }

    /** In-stock colourways of a pattern (FV-59-A…D), full first photo of each. */
    private function colourways(string $pattern): array
    {
        $root = storage_path('app/public/tenants/1/'.tenancy()->tenant->getTenantKey());
        $rows = DB::table('product_flat')->where('channel', 'default')->where('locale', 'ja')->where('status', 1)
            ->where('name', 'like', "{$pattern}-_")->orderBy('name')->get(['product_id', 'name', 'description']);
        $out = [];
        foreach ($rows as $r) {
            if ((int) DB::table('product_inventories')->where('product_id', $r->product_id)->sum('qty') < 1) {
                continue;
            }
            $path = DB::table('product_images')->where('product_id', $r->product_id)->orderBy('position')->value('path');
            $f = $root.'/'.$path;
            $jpg = preg_replace('/\.webp$/', '.jpg', $f);
            $f = is_file($jpg) ? $jpg : $f;
            [$w, $h] = getimagesize($f);
            preg_match('/Color:\s*\w+\s*\(([^)]+)\)/', strip_tags((string) $r->description), $c);
            $out[] = ['id' => $r->product_id, 'name' => $r->name, 'letter' => substr($r->name, -1), 'colour' => $c[1] ?? '', 'image' => $f, 'box' => [0, 0, $w, $h]];
        }

        return $out;
    }

    private function py(string $script, array $args): string
    {
        return 'cd '.escapeshellarg($this->bin).' && python3 '.implode(' ', array_map('escapeshellarg', [$script, ...$args]));
    }

    private function worst(array $gate): string
    {
        $s = $gate['summary'] ?? null;

        return ! $s || ! $s['regions'] ? '-' : sprintf('%.2f / %.3f / %.2f  (%d regions)', $s['worst_deltaE00_mean'], $s['min_ssim'], $s['min_orb_inliers'] ?? 0, $s['regions']);
    }
}

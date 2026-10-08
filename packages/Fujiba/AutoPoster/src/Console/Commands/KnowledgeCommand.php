<?php

namespace Fujiba\AutoPoster\Console\Commands;

use Fujiba\AutoPoster\Models\AutoPosterPatternKnowledge;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * autoposter:knowledge import|status
 *
 * import: reads knowledge/patterns/{product_id}.json (the vision analysis),
 * adds MEASURED facts (image hash, close-up sharpness, palette via
 * bin/v2/measure.py) and upserts autoposter_pattern_knowledge. sku/name always
 * come from product_flat, never from the JSON.
 * status: lists records whose product images changed since analysis (stale).
 * Runs inside a tenant context (tenants:run).
 */
class KnowledgeCommand extends Command
{
    protected $signature = 'autoposter:knowledge {action=status : import|status} {--product=* : limit to product ids}';

    protected $description = 'Content Engine v2: import / check the per-product pattern knowledge records.';

    public function handle(): int
    {
        return $this->argument('action') === 'import' ? $this->import() : $this->status();
    }

    private function import(): int
    {
        $dir = dirname(__DIR__, 3).'/knowledge/patterns';
        $only = array_map('intval', $this->option('product'));

        foreach (glob("{$dir}/*.json") as $file) {
            $r = json_decode(file_get_contents($file), true);
            $pid = (int) $r['product_id'];
            if ($only && ! in_array($pid, $only, true)) {
                continue;
            }
            $flat = DB::table('product_flat')->where('product_id', $pid)->where('channel', 'default')->where('locale', 'ja')->first(['sku', 'name']);
            if (! $flat) {
                $this->error("{$pid}: product not found");

                continue;
            }
            [$images, $hash] = $this->images($pid);
            $box = $r['closeup']['box'];
            $m = $this->measure($this->root().'/'.$r['closeup']['image'], $box);
            if (! $m) {
                $this->error("{$pid}: measure failed");

                continue;
            }
            $roles = ['dominant', '2nd', '3rd', '4th', '5th', '6th'];   // by measured share — NOT the ground (see pattern.background)
            $colors = array_map(fn ($c, $i) => $c + ['role' => $roles[$i] ?? 'minor', 'grade' => 'A'], $m['palette'], array_keys($m['palette']));

            AutoPosterPatternKnowledge::updateOrCreate(['product_id' => $pid], [
                'sku' => $flat->sku, 'name' => $flat->name, 'image_hash' => $hash, 'images' => $images,
                'closeup' => $r['closeup'] + ['sharpness' => $m['sharpness'], 'source_size' => $m['size']],
                'colors' => $colors, 'motifs' => $r['motifs'], 'pattern' => $r['pattern'],
                'meanings' => $r['meanings'] ?? [], 'seasons' => $r['seasons'] ?? [], 'occasions' => $r['occasions'] ?? [],
                'fingerprint' => $r['fingerprint'], 'photo_needs' => $r['photo_needs'] ?? [], 'hooks' => $r['hooks'] ?? [],
                'analysed_by' => $r['analysed_by'] ?? 'claude-session-2026-10-08', 'analysed_at' => now(),
            ]);
            $this->line(sprintf('%-9s %s  dominant %s  sharp %s  %d motifs', $flat->name, substr($hash, 0, 8), $colors[0]['hex'] ?? '-', $m['sharpness'], count($r['motifs'])));
        }

        return 0;
    }

    private function status(): int
    {
        $rows = AutoPosterPatternKnowledge::orderBy('name')->get();
        foreach ($rows as $k) {
            $stale = $this->images($k->product_id)[1] !== $k->image_hash;
            $this->line(sprintf('%-9s %s %s', $k->name, $stale ? 'STALE (images changed — re-analyse)' : 'ok', $k->photo_needs ? '· photo needs: '.count($k->photo_needs) : ''));
        }
        $this->info($rows->count().' record(s)');

        return 0;
    }

    /** @return array{0: array, 1: string} images + sha256 over the files in position order */
    private function images(int $pid): array
    {
        $out = [];
        $h = hash_init('sha256');
        foreach (DB::table('product_images')->where('product_id', $pid)->orderBy('position')->pluck('path') as $path) {
            $f = $this->root().'/'.$path;
            $jpg = preg_replace('/\.webp$/', '.jpg', $f);
            $f = is_file($jpg) ? $jpg : $f;
            if (! is_file($f)) {
                continue;
            }
            [$w, $hh] = @getimagesize($f) ?: [null, null];
            $out[] = ['path' => substr($f, strlen($this->root()) + 1), 'w' => $w, 'h' => $hh];
            hash_update($h, hash_file('sha256', $f));
        }

        return [$out, hash_final($h)];
    }

    private function measure(string $file, array $box): ?array
    {
        $cmd = array_map('escapeshellarg', ['python3', dirname(__DIR__, 3).'/bin/v2/measure.py', $file, ...array_map('strval', $box)]);
        $json = shell_exec(implode(' ', $cmd).' 2>/dev/null');

        return $json ? json_decode($json, true) : null;
    }

    private function root(): string
    {
        return storage_path('app/public/tenants/1/'.tenancy()->tenant->getTenantKey());
    }
}

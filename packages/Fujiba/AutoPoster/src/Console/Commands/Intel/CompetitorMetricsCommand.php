<?php

namespace Fujiba\AutoPoster\Console\Commands\Intel;

use Carbon\Carbon;
use Fujiba\AutoPoster\Services\Intel\CsvSheet;
use Fujiba\AutoPoster\Services\Intel\OwnerSheetValues;
use Fujiba\AutoPoster\Services\Intel\PublicCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Engagement numbers read off owner screenshots → fi_competitor_posts.
 *
 * CSV columns (storage/app/intel/competitors/engagement_import_template.csv):
 *   handle, post_ref, cover_note, views, likes, comments, shares, saves,
 *   captured_at, screenshot_file, notes
 *
 * post_ref: TikTok video id (or video URL) | IG screenshot no. (e.g. 7 →
 * owner_screenshot_07) | an existing owner_* id | "new" (creates a row with
 * source owner_screenshot; its id is derived from handle + screenshot_file +
 * cover_note so re-importing the same sheet updates instead of duplicating).
 *
 * Upserts only: metrics columns that are filled overwrite, empty cells never
 * blank a stored value, every import is appended to raw.owner_metrics, and
 * nothing is ever deleted. Rows whose notes still say "ocr_draft" are refused
 * until a human has checked them against the screenshot.
 */
class CompetitorMetricsCommand extends Command
{
    protected $signature = 'fujiba:intel:competitor-metrics {csv : filled engagement sheet}
        {--tenant=1bd1181f-fcd5-4238-a12d-a207d6ea4006}
        {--dry-run : validate and show what would change, write nothing}';

    protected $description = 'Upsert owner-screenshot engagement metrics into fi_competitor_posts (never deletes)';

    public const COLUMNS = ['handle', 'post_ref', 'cover_note', 'views', 'likes', 'comments', 'shares', 'saves', 'captured_at', 'screenshot_file', 'notes'];

    public const METRICS = ['views', 'likes', 'comments', 'shares', 'saves'];

    public function handle(): int
    {
        if (function_exists('tenancy') && ! tenancy()->initialized) {
            tenancy()->initialize($this->option('tenant'));
        }
        $path = (string) $this->argument('csv');
        $dry = (bool) $this->option('dry-run');

        try {
            $sheet = CsvSheet::read($path);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        if ($missing = array_diff(['handle', 'post_ref'], $sheet['header'])) {
            $this->error('missing column(s): '.implode(', ', $missing));

            return self::FAILURE;
        }

        $competitors = [];
        foreach (DB::table('fi_competitors')->get(['id', 'platform', 'handle']) as $c) {
            $competitors[strtolower($c->handle)][] = $c;
        }

        $plans = [];
        $rejected = [];
        $planned = [];
        foreach ($sheet['rows'] as $r) {
            try {
                $plan = $this->plan($r, $competitors, $path, $planned);
                $planned[$plan['key']] = true;
                $plans[] = $plan;
            } catch (InvalidArgumentException $e) {
                $rejected[] = [$r['_line'], $r['handle'] ?? '', $r['post_ref'] ?? '', $e->getMessage()];
            }
        }

        $this->info(sprintf('%s: %d data rows | %d update | %d insert | %d rejected',
            basename($path), count($sheet['rows']),
            count(array_filter($plans, fn ($p) => $p['action'] !== 'insert')),
            count(array_filter($plans, fn ($p) => $p['action'] === 'insert')), count($rejected)));
        if ($plans) {
            $this->table(['line', 'competitor', 'external_id', 'action', 'views', 'likes', 'comments', 'shares', 'saves', 'captured_at'],
                array_map(fn ($p) => [
                    $p['line'], $p['platform'].':'.$p['handle'], $p['external_id'], $p['action'],
                    ...array_map(fn ($m) => $p['metrics'][$m] ?? '-', self::METRICS), $p['captured_at'] ?? '-',
                ], $plans));
        }
        if ($rejected) {
            $this->warn('rejected rows:');
            $this->table(['line', 'handle', 'post_ref', 'reason'], $rejected);
        }
        if ($dry) {
            $this->warn('dry-run: nothing written');

            return self::SUCCESS;
        }

        $now = now();
        DB::transaction(function () use ($plans, $path, $now) {
            foreach ($plans as $p) {
                $existing = DB::table('fi_competitor_posts')->where('platform', $p['platform'])->where('external_id', $p['external_id'])->first(['id', 'raw']);
                $raw = $existing ? json_decode((string) $existing->raw, true) : [];
                if (! is_array($raw)) {
                    $raw = ['_previous_raw' => $existing->raw ?? null];
                }
                $raw['owner_metrics'][] = $p['metrics'] + [
                    'captured_at' => $p['captured_at'], 'source_file' => $p['source_file'],
                    'sheet' => basename($path), 'line' => $p['line'], 'imported_at' => $now->toDateTimeString(),
                ];

                $data = $p['metrics'] + [
                    'metrics_status'      => 'owner_screenshot',
                    'metrics_source_file' => $p['source_file'],
                    'metrics_captured_at' => $p['captured_at'],
                    'raw'                 => json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updated_at'          => $now,
                ];
                if ($p['notes'] !== '') {
                    $data['metrics_notes'] = $p['notes'];
                }
                if ($p['cover_note'] !== '') {
                    $data['cover_note'] = $p['cover_note'];
                }

                if ($existing) {
                    DB::table('fi_competitor_posts')->where('id', $existing->id)->update($data);
                } else {
                    DB::table('fi_competitor_posts')->insert($data + [
                        'competitor_id'  => $p['competitor_id'],
                        'platform'       => $p['platform'],
                        'external_id'    => $p['external_id'],
                        'url'            => $p['url'],
                        'source'         => 'owner_screenshot',
                        'fetch_status'   => 'not_applicable',
                        'internal_only'  => 1,
                        'created_at'     => $now,
                    ]);
                }
            }
        });
        $this->info('written: '.count($plans).' post row(s) (fi_competitor_posts)');

        return self::SUCCESS;
    }

    /** Validate one sheet row and decide update/insert. Throws InvalidArgumentException with the reject reason. */
    private function plan(array $r, array $competitors, string $path, array $planned): array
    {
        $notes = trim($r['notes'] ?? '');
        if (stripos($notes, 'ocr_draft') !== false) {
            throw new InvalidArgumentException("unverified OCR draft — check the numbers against the screenshot, then remove 'ocr_draft' from notes");
        }

        // competitor
        $h = strtolower(ltrim(trim($r['handle'] ?? ''), '@'));
        $platform = null;
        if (preg_match('/^(tiktok|instagram|ig):(.+)$/', $h, $m)) {
            $platform = $m[1] === 'ig' ? 'instagram' : $m[1];
            $h = ltrim($m[2], '@');
        }
        if ($h === '') {
            throw new InvalidArgumentException('handle empty');
        }
        $cands = array_values(array_filter($competitors[$h] ?? [], fn ($c) => ! $platform || $c->platform === $platform));
        if (! $cands) {
            throw new InvalidArgumentException("unknown competitor handle '{$h}' (add it to fi_competitors first)");
        }
        if (count($cands) > 1) {
            throw new InvalidArgumentException("handle '{$h}' exists on several platforms — write tiktok:{$h} or instagram:{$h}");
        }
        $c = $cands[0];

        // metrics
        $metrics = [];
        $errors = [];
        foreach (self::METRICS as $m) {
            try {
                $v = OwnerSheetValues::count($r[$m] ?? '');
                if ($v !== null) {
                    $metrics[$m] = $v;
                }
            } catch (InvalidArgumentException $e) {
                $errors[] = "{$m}: ".$e->getMessage();
            }
        }
        if ($errors) {
            throw new InvalidArgumentException(implode('; ', $errors));
        }
        if (! $metrics) {
            throw new InvalidArgumentException('no metrics in the row');
        }

        // captured_at
        $captured = null;
        if (($ca = trim($r['captured_at'] ?? '')) !== '') {
            try {
                $dt = Carbon::parse(str_replace('/', '-', $ca));
            } catch (Throwable) {
                throw new InvalidArgumentException("captured_at '{$ca}' is not a date (use YYYY-MM-DD or YYYY-MM-DD HH:MM)");
            }
            if ($dt->year < 2015 || $dt->gt(now()->addDay())) {
                throw new InvalidArgumentException("captured_at '{$ca}' is out of range");
            }
            $captured = $dt->toDateTimeString();
        }

        // post
        $ref = trim($r['post_ref'] ?? '');
        $shot = trim($r['screenshot_file'] ?? '');
        $cover = mb_substr(PublicCode::mask(trim($r['cover_note'] ?? '')), 0, 255);
        $url = null;
        $mayInsert = false;
        if (preg_match('~/video/(\d{8,25})~', $ref, $mm)) {
            $ref = $mm[1];
        }
        if ($ref === '') {
            throw new InvalidArgumentException("post_ref empty (TikTok video id, IG screenshot no., or 'new')");
        } elseif (strtolower($ref) === 'new') {
            if ($shot === '' && $cover === '') {
                throw new InvalidArgumentException("'new' needs screenshot_file or cover_note (so a re-import updates instead of duplicating)");
            }
            $ext = 'owner_new_'.substr(sha1($c->platform.'|'.$h.'|'.$shot.'|'.$cover), 0, 16);
            $mayInsert = true;
        } elseif (ctype_digit($ref) && strlen($ref) >= 15) {
            if ($c->platform !== 'tiktok') {
                throw new InvalidArgumentException("a TikTok video id was given for {$c->platform} account '{$h}'");
            }
            $ext = $ref;
            $url = "https://www.tiktok.com/@{$c->handle}/video/{$ref}";
            $mayInsert = true;
        } elseif (ctype_digit($ref) && strlen($ref) <= 4) {
            if ($c->platform !== 'instagram') {
                throw new InvalidArgumentException("screenshot numbers are for Instagram accounts; '{$h}' is {$c->platform}");
            }
            $ext = sprintf('owner_screenshot_%02d', (int) $ref);
        } elseif (preg_match('/^owner_(screenshot|new)_[a-z0-9_]+$/i', $ref)) {
            $ext = $ref;
        } else {
            throw new InvalidArgumentException("unrecognised post_ref '{$ref}'");
        }

        $key = $c->platform.'|'.$ext;
        $existing = DB::table('fi_competitor_posts')->where('platform', $c->platform)->where('external_id', $ext)->first(['id', 'competitor_id']);
        if ($existing && (int) $existing->competitor_id !== (int) $c->id) {
            throw new InvalidArgumentException("post {$ext} belongs to another competitor (#{$existing->competitor_id})");
        }
        if ($existing) {
            $action = 'update';
        } elseif (isset($planned[$key])) {
            $action = 'update (same sheet)';
        } elseif ($mayInsert) {
            $action = 'insert';
        } else {
            throw new InvalidArgumentException("no post {$ext} for '{$h}' — use post_ref=new for a post we have not recorded");
        }

        return [
            'line'          => $r['_line'],
            'key'           => $key,
            'competitor_id' => (int) $c->id,
            'platform'      => $c->platform,
            'handle'        => $c->handle,
            'external_id'   => $ext,
            'url'           => $url,
            'action'        => $action,
            'metrics'       => $metrics,
            'captured_at'   => $captured,
            'source_file'   => mb_substr($shot !== '' ? $shot : basename($path).'#line'.$r['_line'], 0, 512),
            'cover_note'    => $cover,
            'notes'         => PublicCode::mask($notes),
        ];
    }
}

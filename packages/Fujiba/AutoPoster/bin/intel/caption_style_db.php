<?php
/**
 * WS-K caption-style research — DB side (export inputs / load results).
 *
 *   php caption_style_db.php export <bundle.json>  [--base=/var/www/bagisto] [--tenant=<uuid>]
 *   php caption_style_db.php load   <analysis.json> [--dry-run] [--base=...] [--tenant=...]
 *
 * export  READ-ONLY. Writes one JSON bundle for bin/intel/caption_style.py:
 *         competitor posts (fi_competitor_posts + fi_competitors), our last 50
 *         published v1 captions (autoposter_queue, status=published, newest
 *         first) with the products in each post, every published caption
 *         (for own-page paragraphs), and the facts of the draft products.
 * load    Upserts ONLY fi_caption_samples (by fingerprint), fi_caption_style
 *         (by language+metric+method) and fi_caption_scores (by
 *         queue_id+segment_language; a status a human changed away from
 *         'proposed' is never reset). Never touches autoposter_* or product
 *         tables. One transaction.
 *
 * Standalone (no service-provider registration): bootstraps Laravel from
 * --base and initialises one tenant. Never prints credentials.
 */

$argvRest = array_slice($argv, 1);
$opts = ['base' => '/var/www/bagisto', 'tenant' => '1bd1181f-fcd5-4238-a12d-a207d6ea4006', 'dry-run' => false];
$pos = [];
foreach ($argvRest as $a) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $a, $m)) {
        $opts[$m[1]] = $m[2] ?? true;
    } else {
        $pos[] = $a;
    }
}
[$cmd, $path] = [$pos[0] ?? null, $pos[1] ?? null];
if (! in_array($cmd, ['export', 'load'], true) || ! $path) {
    fwrite(STDERR, "usage: php caption_style_db.php export|load <file.json> [--dry-run] [--base=] [--tenant=]\n");
    exit(2);
}

require rtrim($opts['base'], '/').'/vendor/autoload.php';
$app = require rtrim($opts['base'], '/').'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tenant = \App\Models\Tenant::find($opts['tenant']);
if (! $tenant) {
    fwrite(STDERR, "tenant not found\n");
    exit(1);
}
tenancy()->initialize($tenant);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

const DRAFT_PRODUCTS = ['FV-67-A', 'FV-67-B', 'FV-67-C', 'FV-73-A', 'FV-83-A'];

function productFacts(array $ids = [], array $names = []): array
{
    $q = DB::table('product_flat')->where('locale', 'en')->where('channel', 'default');
    $q->where(function ($w) use ($ids, $names) {
        if ($ids) {
            $w->orWhereIn('product_id', $ids);
        }
        if ($names) {
            $w->orWhereIn('name', $names);
        }
    });
    $out = [];
    foreach ($q->get(['product_id', 'sku', 'name', 'status', 'price', 'special_price', 'description']) as $r) {
        $desc = trim(preg_replace('/\s+/u', ' ', strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", (string) $r->description))));
        preg_match('/Color:\s*(.+?)(?=Pattern:|Fabric:|$)/u', $desc, $c);
        preg_match('/Pattern:\s*(.+?)(?=Fabric:|Color:|$)/u', $desc, $p);
        preg_match('/Fabric:\s*(.+?)(?=Pattern:|Color:|$)/u', $desc, $f);
        $out[] = [
            'product_id' => (int) $r->product_id,
            'sku'        => $r->sku,
            'name'       => $r->name,
            'status'     => (int) $r->status,
            'price'      => (float) $r->price,
            'special_price' => $r->special_price !== null ? (float) $r->special_price : null,
            'qty'        => (int) DB::table('product_inventories')->where('product_id', $r->product_id)->sum('qty'),
            'colour'     => isset($c[1]) ? trim($c[1]) : null,
            'pattern'    => isset($p[1]) ? trim($p[1]) : null,
            'fabric'     => isset($f[1]) ? trim($f[1]) : null,
        ];
    }

    return $out;
}

if ($cmd === 'export') {
    $competitors = DB::table('fi_competitors')->get(['id', 'platform', 'handle', 'target_audience', 'language'])->keyBy('id');
    $posts = [];
    foreach (DB::table('fi_competitor_posts')->orderBy('id')->get() as $r) {
        $posts[] = [
            'id' => (int) $r->id, 'competitor_id' => (int) $r->competitor_id, 'platform' => $r->platform,
            'external_id' => $r->external_id, 'source' => $r->source, 'fetch_status' => $r->fetch_status,
            'handle' => $r->author ?: ($competitors[$r->competitor_id]->handle ?? null),
            'caption' => $r->caption, 'on_screen_text' => $r->on_screen_text,
            'hashtags' => $r->hashtags ? json_decode($r->hashtags, true) : null,
            'price_shown' => $r->price_shown, 'content_kind' => $r->content_kind,
            'ai_generated_suspected' => (bool) $r->ai_generated_suspected, 'posted_at' => $r->posted_at,
            'target_audience' => $competitors[$r->competitor_id]->target_audience ?? null,
        ];
    }

    $rows = DB::table('autoposter_queue')->where('status', 'published')->orderByDesc('published_at')->limit(50)
        ->get(['id', 'format', 'language_mode', 'template_id', 'caption', 'caption_meta', 'published_at']);
    $ours = [];
    foreach ($rows as $r) {
        $pids = DB::table('autoposter_post_products')->where('queue_id', $r->id)->orderBy('sort_order')->pluck('product_id')->map(fn ($v) => (int) $v)->all();
        $ours[] = [
            'id' => (int) $r->id, 'format' => $r->format, 'language_mode' => $r->language_mode,
            'template_id' => $r->template_id, 'published_at' => $r->published_at, 'caption' => $r->caption,
            'caption_meta' => $r->caption_meta ? json_decode($r->caption_meta, true) : null,
            'product_ids' => $pids,
        ];
    }

    $all = DB::table('autoposter_queue')->where('status', 'published')->orderBy('id')->get(['id', 'caption'])
        ->map(fn ($r) => ['id' => (int) $r->id, 'caption' => $r->caption])->all();

    $bundle = [
        'generated_at' => now()->toIso8601String(),
        'tenant' => $opts['tenant'],
        'competitor_posts' => $posts,
        'ours_last50' => $ours,
        'all_published' => $all,
        'draft_products' => productFacts([], DRAFT_PRODUCTS),
        'messenger_link' => 'https://m.me/'.config('autoposter.page_ids.1'),
    ];
    $json = json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (file_put_contents($path, $json) === false || filesize($path) === 0) {
        fwrite(STDERR, "write failed or zero-size: {$path}\n");
        exit(1);
    }
    printf("export ok: %d competitor posts, %d last-50, %d published, %d draft products -> %s (%d bytes)\n",
        count($posts), count($ours), count($all), count($bundle['draft_products']), $path, filesize($path));
    exit(0);
}

// ---------------------------------------------------------------- load
foreach (['fi_caption_samples', 'fi_caption_style', 'fi_caption_scores'] as $t) {
    if (! Schema::hasTable($t)) {
        fwrite(STDERR, "missing table {$t} — run the 2026_10_12_000005 migration first\n");
        exit(1);
    }
}
$data = json_decode((string) file_get_contents($path), true);
if (! is_array($data)) {
    fwrite(STDERR, "cannot parse {$path}\n");
    exit(1);
}
$j = fn ($v) => $v === null ? null : json_encode($v, JSON_UNESCAPED_UNICODE);
$now = now();
$stats = ['samples' => [0, 0], 'style' => [0, 0], 'scores' => [0, 0, 0]];   // [insert, update(, kept_status)]

$run = function () use ($data, $j, $now, &$stats) {
    foreach ($data['samples'] ?? [] as $s) {
        $row = [
            'source' => $s['source'], 'source_ref' => $s['source_ref'] ?? null,
            'competitor_post_id' => $s['competitor_post_id'] ?? null, 'platform' => $s['platform'] ?? null,
            'handle' => $s['handle'] ?? null, 'language' => $s['language'], 'script' => $s['script'] ?? null,
            'script_detail' => $j($s['script_detail'] ?? null), 'read_confidence' => $s['read_confidence'] ?? null,
            'text' => $s['text'] ?? null, 'overlay_text' => $s['overlay_text'] ?? null,
            'word_count' => $s['word_count'] ?? 0, 'unit_count' => $s['unit_count'] ?? 0,
            'line_count' => $s['line_count'] ?? 0, 'char_count' => $s['char_count'] ?? 0,
            'emoji_count' => $s['emoji_count'] ?? 0, 'emoji_list' => $j($s['emoji_list'] ?? null),
            'hashtag_count' => $s['hashtag_count'] ?? 0, 'hashtags' => $j($s['hashtags'] ?? null),
            'english_mix_ratio' => $s['english_mix_ratio'] ?? null, 'tone' => $s['tone'] ?? null,
            'features' => $j($s['features'] ?? null), 'ai_likeness' => $s['ai_likeness'] ?? null,
            'ai_reasons' => $j($s['ai_reasons'] ?? null), 'included' => (bool) ($s['included'] ?? false),
            'exclusion_reason' => $s['exclusion_reason'] ?? null, 'internal_only' => true,
            'method_version' => $s['method_version'] ?? 'cs-1.0', 'updated_at' => $now,
        ];
        $exists = DB::table('fi_caption_samples')->where('fingerprint', $s['fingerprint'])->exists();
        if ($exists) {
            DB::table('fi_caption_samples')->where('fingerprint', $s['fingerprint'])->update($row);
            $stats['samples'][1]++;
        } else {
            DB::table('fi_caption_samples')->insert($row + ['fingerprint' => $s['fingerprint'], 'created_at' => $now]);
            $stats['samples'][0]++;
        }
    }

    foreach ($data['style'] ?? [] as $s) {
        $key = ['language' => $s['language'], 'metric' => $s['metric'], 'method' => $s['method']];
        $row = [
            'value' => $j($s['value']), 'sample_size' => $s['sample_size'] ?? 0,
            'status' => $s['status'] ?? 'provisional', 'notes' => $s['notes'] ?? null,
            'method_version' => $s['method_version'] ?? 'cs-1.0', 'updated_at' => $now,
        ];
        if (DB::table('fi_caption_style')->where($key)->exists()) {
            DB::table('fi_caption_style')->where($key)->update($row);
            $stats['style'][1]++;
        } else {
            DB::table('fi_caption_style')->insert($key + $row + ['created_at' => $now]);
            $stats['style'][0]++;
        }
    }

    foreach ($data['scores'] ?? [] as $s) {
        $key = ['queue_id' => $s['queue_id'], 'segment_language' => $s['segment_language']];
        $row = [
            'segment_text' => $s['segment_text'] ?? null, 'ai_sounding' => (bool) $s['ai_sounding'],
            'ai_score' => $s['ai_score'] ?? null, 'reasons' => $j($s['reasons'] ?? null),
            'rewrite_suggestion' => $s['rewrite_suggestion'] ?? null, 'facts_used' => $j($s['facts_used'] ?? null),
            'overlap_check' => $j($s['overlap_check'] ?? null),
            'method_version' => $s['method_version'] ?? 'cs-1.0', 'updated_at' => $now,
        ];
        $cur = DB::table('fi_caption_scores')->where($key)->first(['id', 'status']);
        if ($cur) {
            if ($cur->status !== 'proposed') {     // a human reviewed it: keep their decision and text
                $stats['scores'][2]++;
                continue;
            }
            DB::table('fi_caption_scores')->where('id', $cur->id)->update($row);
            $stats['scores'][1]++;
        } else {
            DB::table('fi_caption_scores')->insert($key + $row + ['status' => 'proposed', 'created_at' => $now]);
            $stats['scores'][0]++;
        }
    }
};

DB::beginTransaction();
try {
    $run();
    if ($opts['dry-run']) {
        DB::rollBack();
    } else {
        DB::commit();
    }
} catch (\Throwable $e) {
    DB::rollBack();
    fwrite(STDERR, 'load failed, rolled back: '.$e->getMessage()."\n");
    exit(1);
}
printf("%s samples +%d ~%d | style +%d ~%d | scores +%d ~%d (human-reviewed kept %d)\n",
    $opts['dry-run'] ? 'DRY-RUN (rolled back):' : 'loaded:',
    $stats['samples'][0], $stats['samples'][1], $stats['style'][0], $stats['style'][1],
    $stats['scores'][0], $stats['scores'][1], $stats['scores'][2]);

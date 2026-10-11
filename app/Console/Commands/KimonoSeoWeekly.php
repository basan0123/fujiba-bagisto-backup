<?php

namespace App\Console\Commands;

use Fujiba\SupplierImageSync\Services\SummaryNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Weekly kimono SEO review. Owner (2026-10-10): every kimono-fabric buyer sews
 * Myanmar longyi/htamein or Nepal/Bhutan lungi, so all SEO targets that.
 *
 * Rewrites meta title/description/keywords (ja/en/my) of the price-band and
 * cut categories and the home page from LIVE data (real lowest price, live
 * product count), so Google text never drifts from the shop. Messengers the
 * owner what changed. Run via tenants:run on the kimono tenant only.
 */
class KimonoSeoWeekly extends Command
{
    protected $signature = 'fujiba:kimono-seo-weekly {--dry-run} {--notify}';

    protected $description = 'Weekly longyi/lungi SEO refresh for kimono categories and home page.';

    private const KW = [
        'ja' => '着物 生地, 和柄 生地, ロンジー 生地, ミャンマー ロンジー 着物生地, ルンギ 生地, ネパール 生地, カット済み 生地',
        'en' => 'kimono fabric, kimono longyi, longyi fabric, htamein fabric, lungi fabric, kimono lungi, nepali lungi, bhutan lungi, लुंगी कपडा, किमोनो कपडा',
        'my' => 'ကီမိုနိုလုံချည်, ကီမိုနိုထဘီ, ကီမိုနိုပိတ်စ, လုံချည်ပိတ်စ, ထဘီပိတ်စ, ဂျပန်ပိတ်စ, kimono longyi',
    ];

    public function handle(): int
    {
        if (Str::startsWith(DB::connection()->getDatabaseName(), 'bagisto_central')) {
            $this->error('Run via tenants:run on the kimono tenant.');

            return self::FAILURE;
        }

        $channel = DB::table('channels')->where('code', 'default')->first();
        $root = DB::table('categories')->where('id', $channel->root_category_id)->first();
        $dry = $this->option('dry-run');
        $changes = [];
        $notes = [];

        $cats = DB::table('categories as c')
            ->join('category_translations as ct', 'ct.category_id', '=', 'c.id')
            ->where('c._lft', '>', $root->_lft)->where('c._rgt', '<', $root->_rgt)
            ->where('c.status', 1)
            ->where(fn ($q) => $q->where('ct.slug', 'regexp', '^[0-9]+yen$')->orWhere('ct.slug', '3mcut'))
            ->select('c.id', 'ct.id as tid', 'ct.locale', 'ct.slug', 'ct.meta_title', 'ct.meta_description', 'ct.meta_keywords')
            ->get();

        $allMin = null;
        $allLive = 0;

        foreach ($cats->groupBy('id') as $id => $rows) {
            $stats = DB::table('product_categories as pc')
                ->join('product_flat as pf', fn ($j) => $j->on('pf.product_id', '=', 'pc.product_id')
                    ->where('pf.channel', 'default')->where('pf.locale', 'ja')->where('pf.status', 1))
                ->where('pc.category_id', $id)
                ->selectRaw('COUNT(*) n, MIN(pf.price) minp')->first();

            $slug = $rows->first()->slug;
            if (! $stats->n) {
                $notes[] = "⚠️ {$slug}: 0 products on sale (consider hiding the category)";
                continue;
            }
            $allLive += $stats->n;
            if (preg_match('/^\d+yen$/', $slug)) {
                $allMin = $allMin === null ? (float) $stats->minp : min($allMin, (float) $stats->minp);
            }

            $p = number_format((float) $stats->minp);
            $n = (int) $stats->n;
            $cut = $slug === '3mcut';
            $want = [
                'ja' => [
                    $cut ? "ロンジー・ルンギ用 着物生地 3mカット ¥{$p}〜 | Fujiba" : "ロンジー・ルンギ用 着物生地 ¥{$p}｜カット済み 和柄 | Fujiba",
                    "ミャンマーのロンジー・タメイン、ネパール・ブータンのルンギ用の着物生地".($cut ? '3mカット' : '')."。¥{$p}から、在庫{$n}柄。入金後24時間以内に発送。",
                ],
                'en' => [
                    $cut ? "Kimono Fabric 3m Cut for Longyi & Lungi from ¥{$p} | Fujiba" : "Kimono Fabric for Longyi & Lungi – ¥{$p} Pre-Cut | Fujiba",
                    "Japanese kimono fabric".($cut ? ' in 3m cuts' : '')." for Myanmar longyi & htamein and Nepal / Bhutan lungi. From ¥{$p}, {$n} patterns in stock in Japan. Ships within 24h of payment.",
                ],
                'my' => [
                    $cut ? "ကီမိုနိုလုံချည်/ထဘီ ပိတ်စ 3m ¥{$p} မှစ | Fujiba" : "ကီမိုနိုလုံချည်/ထဘီ ပိတ်စ ¥{$p} | Fujiba",
                    "ကီမိုနိုလုံချည်၊ ကီမိုနိုထဘီ ချုပ်ဖို့ ဂျပန်ပိတ်စ ¥{$p} မှစ၊ ဒီဇိုင်း {$n} မျိုး စတော့ရှိ။ ငွေချေပြီး ၂၄ နာရီအတွင်း ပို့။",
                ],
            ];

            foreach ($rows as $r) {
                if (! isset($want[$r->locale])) {
                    continue;
                }
                [$t, $d] = $want[$r->locale];
                if ($r->meta_title === $t && $r->meta_description === $d && $r->meta_keywords === self::KW[$r->locale]) {
                    continue;
                }
                $changes[] = "{$slug} [{$r->locale}] → {$t}";
                if (! $dry) {
                    DB::table('category_translations')->where('id', $r->tid)->update([
                        'meta_title' => $t, 'meta_description' => $d, 'meta_keywords' => self::KW[$r->locale],
                    ]);
                }
            }
        }

        // Home page: keep the "from ¥X" in title/description equal to the real lowest band price.
        if ($allMin) {
            $p = '¥'.number_format($allMin);
            foreach (DB::table('channel_translations')->where('channel_id', $channel->id)->get() as $ct) {
                $seo = json_decode($ct->home_seo ?? '{}', true) ?: [];
                $new = $seo;
                foreach (['meta_title', 'meta_description'] as $f) {
                    if (isset($new[$f])) {
                        $new[$f] = preg_replace('/¥[\d,]+/u', $p, $new[$f], 1);
                    }
                }
                $kw = array_map('trim', explode(',', $new['meta_keywords'] ?? ''));
                $new['meta_keywords'] = implode(', ', array_values(array_unique(array_filter(array_merge($kw, explode(', ', self::KW[$ct->locale] ?? ''))))));
                if ($new != $seo) {
                    $changes[] = "home [{$ct->locale}] price/keywords refreshed";
                    if (! $dry) {
                        DB::table('channel_translations')->where('id', $ct->id)->update(['home_seo' => json_encode($new, JSON_UNESCAPED_UNICODE)]);
                    }
                }
            }
        }

        $body = "👘 Weekly longyi/lungi SEO".($dry ? ' (dry run)' : '')."\n"
            ."On sale: {$allLive} fabrics, lowest ¥".number_format((float) $allMin)."\n"
            .($changes ? 'Updated '.count($changes).":\n- ".implode("\n- ", array_slice($changes, 0, 15)) : 'No changes needed ✅')
            .($notes ? "\n".implode("\n", $notes) : '');
        $this->line($body);

        if ($this->option('notify') && ! $dry && ($changes || $notes)) {
            $sent = app()->makeWith(SummaryNotifier::class, ['channelId' => (int) config('supplier-finder.channel_id'), 'label' => (string) config('supplier-image-sync.notify_label')])
                ->sendText('kimono_seo.weekly', $body);
            $this->info("Messenger: sent to {$sent}");
        }

        return self::SUCCESS;
    }
}

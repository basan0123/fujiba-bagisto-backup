<?php

namespace Fujiba\PwaFrontend\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webkul\Category\Models\CategoryTranslationProxy;
use Webkul\CMS\Models\PageProxy;
use Webkul\CMS\Models\PageTranslationProxy;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;

/**
 * Tenant-side SEO augmentation for the kimono catalog. Run via:
 *
 *   php artisan tenants:run fujiba:seed-kimono-seo-tenant
 *
 * What it does:
 *   1. Writes meta_title / meta_description / meta_keywords on the existing
 *      kimono categories (slugs 1295yen, 1395yen, …, 3mcut, japanese-fan,
 *      hair-stick-…, all-kimono, other) in en/ja/my. Overwrites empty
 *      metas; leaves non-empty fields alone (idempotent + non-destructive).
 *   2. Creates 5 blog CMS pages × 3 locales, attached to the kimono channel
 *      (channel code "default"). Idempotent: matches by url_key.
 *
 * What it does NOT do:
 *   - Touch mmfood-channel categories (3-15) or the tenant-wide "all-product"
 *     and "discount" categories.
 *   - Touch existing CMS pages (about-us, return-policy, etc.).
 *   - Create new categories. (User requested B3 — augment only.)
 */
class SeedKimonoSeoTenantCommand extends Command
{
    protected $signature = 'fujiba:seed-kimono-seo-tenant {--dry-run}';

    protected $description = 'Augment kimono-channel categories with SEO meta + seed 5 blog CMS pages (tenant-side).';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $this->info($dry ? '[DRY RUN]' : 'Writing.');

        $localeIds = Locale::pluck('id', 'code')->all();
        foreach (['en', 'ja', 'my'] as $code) {
            if (empty($localeIds[$code])) {
                $this->error("Locale '$code' not found in this tenant.");
                return self::FAILURE;
            }
        }
        $this->line('Locale IDs: '.json_encode($localeIds));

        $kimonoChannel = Channel::query()->where('code', 'default')->first();
        if (! $kimonoChannel) {
            $this->error("Channel 'default' (kimono) not found in tenant.");
            return self::FAILURE;
        }
        $this->line("Kimono channel: id={$kimonoChannel->id} hostname={$kimonoChannel->hostname}");

        DB::beginTransaction();
        try {
            foreach ($this->categoryMetaCopy() as $slug => $perLocale) {
                $this->upsertCategoryMeta($slug, $perLocale, $localeIds, $dry);
            }

            foreach ($this->cmsPagesCopy() as $urlKey => $perLocale) {
                $this->upsertCmsPage($urlKey, $perLocale, $kimonoChannel->id, $dry);
            }

            if ($dry) {
                DB::rollBack();
                $this->warn('[DRY RUN] Rolled back.');
            } else {
                DB::commit();
                $this->info('Committed.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Failed: '.$e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function upsertCategoryMeta(string $slug, array $perLocale, array $localeIds, bool $dry): void
    {
        $transModel = CategoryTranslationProxy::modelClass();

        // Find the category by ANY locale's slug match (some categories
        // localize their slug in JA, e.g. japanese-fan / 扇子).
        $existingSlugs = $perLocale['_slug_aliases'] ?? [$slug];
        $catId = $transModel::query()
            ->whereIn('slug', $existingSlugs)
            ->value('category_id');
        unset($perLocale['_slug_aliases']);

        if (! $catId) {
            $this->warn("[skip] no category found for slug '$slug'");
            return;
        }
        $this->line("[meta] cat $catId / slug=$slug");

        foreach ($perLocale as $locale => $meta) {
            if (! isset($localeIds[$locale])) continue;
            if ($dry) continue;

            $row = $transModel::query()
                ->where('category_id', $catId)
                ->where('locale', $locale)
                ->first();

            if (! $row) {
                $this->warn("  [warn] no $locale translation for cat $catId — skipping");
                continue;
            }

            // Don't overwrite non-empty existing values for description.
            if (! empty($meta['meta_title']) && empty($row->meta_title)) {
                $row->meta_title = $meta['meta_title'];
            }
            if (! empty($meta['meta_description']) && empty($row->meta_description)) {
                $row->meta_description = $meta['meta_description'];
            }
            if (! empty($meta['meta_keywords']) && empty($row->meta_keywords)) {
                $row->meta_keywords = $meta['meta_keywords'];
            }
            $row->save();
        }
    }

    private function upsertCmsPage(string $urlKey, array $perLocale, int $kimonoChannelId, bool $dry): void
    {
        $pageModel  = PageProxy::modelClass();
        $transModel = PageTranslationProxy::modelClass();

        $existingTrans = $transModel::query()->where('url_key', $urlKey)->first();
        $page = $existingTrans
            ? $pageModel::query()->find($existingTrans->cms_page_id)
            : null;

        if ($page) {
            $this->line("[update] CMS page '$urlKey' (id={$page->id})");
        } else {
            $this->line("[create] CMS page '$urlKey'");
            if ($dry) {
                $page = (object) ['id' => 0];
            } else {
                $page = $pageModel::query()->create(['layout' => null]);
                $page->refresh();
            }
        }

        foreach ($perLocale as $locale => $fields) {
            if ($dry) continue;
            $payload = array_merge($fields, [
                'url_key' => $urlKey,
                'locale'  => $locale,
            ]);
            $transModel::query()->updateOrCreate(
                ['cms_page_id' => $page->id, 'locale' => $locale],
                $payload,
            );
        }

        if (! $dry && is_object($page) && method_exists($page, 'channels')) {
            $page->channels()->sync([$kimonoChannelId]);
        }
    }

    /* ───────────────────────── Copy ───────────────────────── */

    /**
     * meta_title / meta_description / meta_keywords for the existing kimono
     * categories. Keys are EN slugs (or any slug from `_slug_aliases`).
     */
    private function categoryMetaCopy(): array
    {
        return [
            'all-kimono' => [
                'en' => [
                    'meta_title'       => 'All Kimono Fabric – Pre-Cut Japanese Wagara | Fujiba',
                    'meta_description' => 'Browse the full Fujiba kimono fabric catalogue — pre-cut wagara textiles, Japanese fans, kanzashi hair sticks. Ships worldwide from Japan in JPY.',
                    'meta_keywords'    => 'kimono fabric, Japanese fabric, wagara, all kimono',
                ],
                'ja' => [
                    'meta_title'       => '着物・和柄生地 全商品｜カット済み・ハンドメイド向け | Fujiba',
                    'meta_description' => 'Fujiba の着物・和柄生地、扇子、髪飾りなどの全商品をご覧いただけます。ハンドメイド作家向けにカット済み、日本円（JPY）で全国・海外発送対応。',
                    'meta_keywords'    => '着物 全商品, 和柄 生地 通販, 着物 ハンドメイド',
                ],
                'my' => [
                    'meta_title'       => 'ကီမိုနို အထည် အားလုံး – ဂျပန်ရိုးရာ Wagara | Fujiba',
                    'meta_description' => 'Fujiba ၏ ကီမိုနို အထည်အားလုံး — wagara ပုံစံများ၊ ဂျပန်ယပ်တောင်များ၊ ဆံထိုးများ။ ဂျပန်မှ ကမ္ဘာတစ်ဝှမ်း ပို့ဆောင်သည်။',
                    'meta_keywords'    => 'ကီမိုနို အထည် အားလုံး, wagara, ဂျပန် အထည်',
                ],
            ],
            'japanese-fan' => [
                '_slug_aliases' => ['japanese-fan', '扇子'],
                'en' => [
                    'meta_title'       => 'Japanese Fans (Sensu / Uchiwa) – Handmade & Wagara | Fujiba',
                    'meta_description' => 'Traditional Japanese fans (sensu and uchiwa) with wagara prints. Authentic craft from Japan, priced in JPY, shipped worldwide.',
                    'meta_keywords'    => 'Japanese fan, sensu, uchiwa, 扇子, wagara fan',
                ],
                'ja' => [
                    'meta_title'       => '扇子・うちわ｜和柄プリント 日本製 | Fujiba',
                    'meta_description' => '日本伝統の扇子・うちわを和柄プリントで。お土産・ギフト・舞踊用に。日本円（JPY）表示、海外発送対応。',
                    'meta_keywords'    => '扇子, うちわ, 和柄 扇子, 日本製 扇子',
                ],
                'my' => [
                    'meta_title'       => 'ဂျပန်ယပ်တောင် (Sensu / Uchiwa) – Wagara ပုံစံ | Fujiba',
                    'meta_description' => 'ဂျပန်ရိုးရာ ယပ်တောင်များ — sensu, uchiwa — wagara ပုံစံများဖြင့်။ ဂျပန်မှ တိုက်ရိုက် ပို့ဆောင်သည်။',
                    'meta_keywords'    => 'ဂျပန်ယပ်တောင်, sensu, uchiwa',
                ],
            ],
            '1295yen' => [
                'en' => [
                    'meta_title'       => 'Kimono Fabric ¥1,295 – Pre-Cut Wagara Panels | Fujiba',
                    'meta_description' => 'Pre-cut kimono fabric panels at ¥1,295. Affordable wagara textiles for half-obi, pouches and beginner handmade kimono crafts.',
                    'meta_keywords'    => 'kimono fabric 1295 yen, cheap kimono fabric, pre-cut wagara',
                ],
                'ja' => [
                    'meta_title'       => '着物生地 1,295円｜カット済み 和柄ハンドメイド用 | Fujiba',
                    'meta_description' => '1,295円のカット済み着物生地。半幅帯・ポーチ・小物制作向けの手頃な和柄生地を取り揃えました。',
                    'meta_keywords'    => '着物 生地 1295円, 和柄 生地 安い, カット済み',
                ],
                'my' => [
                    'meta_title'       => 'ကီမိုနို အထည် ¥၁,၂၉၅ – ကြိုတင်ဖြတ်ထား Wagara | Fujiba',
                    'meta_description' => '¥၁,၂၉၅ ဖြင့် ကြိုတင်ဖြတ်ထား ကီမိုနို အထည် panel များ။ Half-obi, ပန်းကန်အိတ်နှင့် လက်လုပ်လုပ်ငန်းအတွက်။',
                    'meta_keywords'    => 'ကီမိုနို အထည် ၁၂၉၅, စျေးသက်သာ',
                ],
            ],
            '1395yen' => [
                'en' => [
                    'meta_title'       => 'Kimono Fabric ¥1,395 – Pre-Cut Wagara Panels | Fujiba',
                    'meta_description' => 'Pre-cut kimono fabric panels at ¥1,395. Sakura, asanoha and other traditional Japanese patterns sized for handmade crafts.',
                    'meta_keywords'    => 'kimono fabric 1395 yen, wagara, pre-cut Japanese fabric',
                ],
                'ja' => [
                    'meta_title'       => '着物生地 1,395円｜カット済み 和柄ハンドメイド用 | Fujiba',
                    'meta_description' => '1,395円のカット済み着物生地。桜・麻の葉などの和柄を、ハンドメイド向けの使いやすいサイズで。',
                    'meta_keywords'    => '着物 生地 1395円, 和柄 生地, カット済み',
                ],
                'my' => [
                    'meta_title'       => 'ကီမိုနို အထည် ¥၁,၃၉၅ – Wagara ပုံစံများ | Fujiba',
                    'meta_description' => '¥၁,၃၉၅ ဖြင့် ကြိုတင်ဖြတ်ထား ကီမိုနို အထည် — ဆာကူရာ၊ asanoha နှင့် အခြား wagara ပုံစံများ။',
                    'meta_keywords'    => 'ကီမိုနို အထည် ၁၃၉၅',
                ],
            ],
            '1595yen' => [
                'en' => [
                    'meta_title'       => 'Kimono Fabric ¥1,595 – Pre-Cut Wagara | Fujiba',
                    'meta_description' => 'Pre-cut kimono fabric at ¥1,595. Mid-range wagara panels in cotton and polyester for obi, accessories and home decor projects.',
                    'meta_keywords'    => 'kimono fabric 1595 yen, mid-range kimono fabric',
                ],
                'ja' => [
                    'meta_title'       => '着物生地 1,595円｜カット済み 和柄 | Fujiba',
                    'meta_description' => '1,595円のカット済み着物生地。綿・ポリエステルの中価格帯の和柄を、帯・小物・インテリア制作向けに。',
                    'meta_keywords'    => '着物 生地 1595円, 和柄 生地',
                ],
                'my' => [
                    'meta_title'       => 'ကီမိုနို အထည် ¥၁,၅၉၅ – Wagara | Fujiba',
                    'meta_description' => '¥၁,၅၉၅ ဖြင့် ကြိုတင်ဖြတ်ထား ကီမိုနို အထည်။ Obi, ဆက်စပ်ပစ္စည်းနှင့် အလှပြင်ပစ္စည်းအတွက်။',
                    'meta_keywords'    => 'ကီမိုနို အထည် ၁၅၉၅',
                ],
            ],
            '1700yenn' => [
                'en' => [
                    'meta_title'       => 'Kimono Fabric ¥1,700 – Pre-Cut Wagara | Fujiba',
                    'meta_description' => 'Pre-cut kimono fabric at ¥1,700. Higher-grade wagara panels for haori, obi face and statement craft pieces.',
                    'meta_keywords'    => 'kimono fabric 1700 yen, premium wagara',
                ],
                'ja' => [
                    'meta_title'       => '着物生地 1,700円｜カット済み 和柄 | Fujiba',
                    'meta_description' => '1,700円のカット済み着物生地。羽織・帯・リメイクの主役素材になるグレードの和柄を取り揃えています。',
                    'meta_keywords'    => '着物 生地 1700円, 和柄 生地 羽織',
                ],
                'my' => [
                    'meta_title'       => 'ကီမိုနို အထည် ¥၁,၇၀၀ – Wagara | Fujiba',
                    'meta_description' => '¥၁,၇၀၀ ဖြင့် ကြိုတင်ဖြတ်ထား ကီမိုနို အထည်။ Haori, obi နှင့် အထူးပြုလုပ်ငန်းများအတွက်။',
                    'meta_keywords'    => 'ကီမိုနို အထည် ၁၇၀၀',
                ],
            ],
            '1800yen' => [
                'en' => [
                    'meta_title'       => 'Kimono Fabric ¥1,800 – Pre-Cut Wagara | Fujiba',
                    'meta_description' => 'Pre-cut kimono fabric at ¥1,800. Quality wagara textiles for serious handmade kimono and obi projects.',
                    'meta_keywords'    => 'kimono fabric 1800 yen, quality wagara',
                ],
                'ja' => [
                    'meta_title'       => '着物生地 1,800円｜カット済み 和柄 | Fujiba',
                    'meta_description' => '1,800円のカット済み着物生地。本格的なハンドメイド着物・帯制作向けの上質な和柄を揃えました。',
                    'meta_keywords'    => '着物 生地 1800円, 上質 和柄',
                ],
                'my' => [
                    'meta_title'       => 'ကီမိုနို အထည် ¥၁,၈၀၀ – Wagara | Fujiba',
                    'meta_description' => '¥၁,၈၀၀ ဖြင့် ကြိုတင်ဖြတ်ထား ကီမိုနို အထည်။ ပိုကောင်းသော wagara ပုံစံများ။',
                    'meta_keywords'    => 'ကီမိုနို အထည် ၁၈၀၀',
                ],
            ],
            '2300yen' => [
                'en' => [
                    'meta_title'       => 'Kimono Fabric ¥2,300 – Premium Pre-Cut Wagara | Fujiba',
                    'meta_description' => 'Pre-cut kimono fabric at ¥2,300. Premium wagara including kinran (gold brocade) and silk panels for ceremonial and statement pieces.',
                    'meta_keywords'    => 'kimono fabric 2300 yen, premium kimono fabric, kinran',
                ],
                'ja' => [
                    'meta_title'       => '着物生地 2,300円｜上質カット済み 金襴・正絹 | Fujiba',
                    'meta_description' => '2,300円のカット済み着物生地。金襴・正絹を含む上質和柄を、式典用・ハンドメイドのこだわり制作向けに。',
                    'meta_keywords'    => '着物 生地 2300円, 金襴 生地, 正絹 生地',
                ],
                'my' => [
                    'meta_title'       => 'ကီမိုနို အထည် ¥၂,၃၀၀ – Premium Wagara | Fujiba',
                    'meta_description' => '¥၂,၃၀၀ ဖြင့် Premium ကီမိုနို အထည် — kinran ရွှေပန်းပု နှင့် ပိုးအထည် အပါအဝင်။',
                    'meta_keywords'    => 'ကီမိုနို အထည် ၂၃၀၀, kinran',
                ],
            ],
            '3mcut' => [
                'en' => [
                    'meta_title'       => 'Kimono Fabric 3m Cut – Pre-Cut Panels for Obi & Haori | Fujiba',
                    'meta_description' => 'Pre-cut kimono fabric in 3m panels — sized for nine-inch nagoya obi, haori jackets, large totes and full tapestries. Wagara and gold brocade.',
                    'meta_keywords'    => 'kimono fabric 3m, 3m cut, obi fabric, haori fabric',
                ],
                'ja' => [
                    'meta_title'       => '着物生地 3m カット｜羽織・九寸帯向け 和柄 | Fujiba',
                    'meta_description' => '3m カット済みの着物生地。九寸名古屋帯・羽織・大判タペストリーなど大物制作向けの和柄・金襴を取り揃えました。',
                    'meta_keywords'    => '着物 生地 3m, 九寸帯 生地, 羽織 生地',
                ],
                'my' => [
                    'meta_title'       => 'ကီမိုနို အထည် ၃မီတာ – Obi နှင့် Haori အတွက် | Fujiba',
                    'meta_description' => '၃မီတာ ကြိုတင်ဖြတ်ထား ကီမိုနို အထည် — Nine-inch nagoya obi, haori, နံရံချိတ်အလှပြင်ပစ္စည်းကြီးများ။',
                    'meta_keywords'    => 'ကီမိုနို အထည် ၃မီတာ',
                ],
            ],
            'hair-stick-髪飾りဆထ' => [
                'en' => [
                    'meta_title'       => 'Japanese Hair Sticks (Kanzashi / 髪飾り) | Fujiba',
                    'meta_description' => 'Authentic Japanese hair sticks (kanzashi / 髪飾り) — traditional ornamental hairpins crafted in Japan. Pairs perfectly with kimono and yukata.',
                    'meta_keywords'    => 'kanzashi, Japanese hair stick, 髪飾り, ဆံထိုး',
                ],
                'ja' => [
                    'meta_title'       => '髪飾り・かんざし 日本製｜着物・浴衣に | Fujiba',
                    'meta_description' => '日本製の髪飾り・かんざし。着物・浴衣に合わせる伝統的な髪留めを、ハンドメイド作家・コレクター向けに。',
                    'meta_keywords'    => '髪飾り, かんざし, 日本製 髪飾り',
                ],
                'my' => [
                    'meta_title'       => 'ဂျပန် ဆံထိုး (Kanzashi / 髪飾り) | Fujiba',
                    'meta_description' => 'ဂျပန်ရိုးရာ ဆံထိုးများ — kanzashi / 髪飾り — ဂျပန်တွင် လက်ဖြင့်ပြုလုပ်ထား။ ကီမိုနိုနှင့် ယူကာတာအတွက်။',
                    'meta_keywords'    => 'ဂျပန် ဆံထိုး, kanzashi',
                ],
            ],
            'other' => [
                'en' => [
                    'meta_title'       => 'Other Kimono Crafts & Accessories | Fujiba',
                    'meta_description' => 'Other Japanese kimono-related crafts, accessories and small textiles from Fujiba\'s catalogue. Authentic, made in Japan.',
                    'meta_keywords'    => 'Japanese craft accessories, kimono accessories',
                ],
                'ja' => [
                    'meta_title'       => 'その他｜着物関連の小物・雑貨 | Fujiba',
                    'meta_description' => 'Fujiba の着物関連の小物・雑貨カテゴリー。日本製のクラフト・アクセサリーを取り揃えています。',
                    'meta_keywords'    => '着物 小物, 着物 雑貨, 日本製 雑貨',
                ],
                'my' => [
                    'meta_title'       => 'အခြား ကီမိုနို ဆက်စပ်ပစ္စည်းများ | Fujiba',
                    'meta_description' => 'Fujiba မှ အခြား ဂျပန် ကီမိုနို ဆက်စပ်ပစ္စည်းများနှင့် လက်လုပ်အနုပညာ ပစ္စည်းများ။',
                    'meta_keywords'    => 'ကီမိုနို ဆက်စပ်ပစ္စည်း',
                ],
            ],
        ];
    }

    /**
     * 5 blog CMS pages × 3 locales. Body is stored as HTML in html_content.
     * Cross-link URLs use kimono.fujiba.co.jp.
     */
    private function cmsPagesCopy(): array
    {
        return [
            'what-is-kimono-fabric' => [
                'en' => [
                    'page_title'       => 'What Is Kimono Fabric? A Beginner\'s Guide',
                    'meta_title'       => 'What Is Kimono Fabric? A Beginner\'s Guide to Wagara | Fujiba',
                    'meta_description' => 'New to kimono fabric? Learn what wagara textiles are, common widths and materials, and how pre-cut panels make handmade kimono crafts approachable.',
                    'meta_keywords'    => 'kimono fabric, wagara, Japanese textile guide',
                    'html_content'     => $this->blogHtml('what-is-kimono-fabric', 'en'),
                ],
                'ja' => [
                    'page_title'       => '着物生地とは？種類・幅・素材を初心者向けに解説',
                    'meta_title'       => '着物生地とは？初心者向けガイド | Fujiba',
                    'meta_description' => '着物生地・和柄の基本（幅110cm、素材、代表的な文様）と、ハンドメイドで使いやすいカット済み生地の選び方を解説します。',
                    'meta_keywords'    => '着物 生地, 和柄 とは, ハンドメイド 着物',
                    'html_content'     => $this->blogHtml('what-is-kimono-fabric', 'ja'),
                ],
                'my' => [
                    'page_title'       => 'ကီမိုနို အထည်ဆိုသည်မှာ?',
                    'meta_title'       => 'ကီမိုနို အထည်ဆိုသည်မှာ — Wagara လမ်းညွှန် | Fujiba',
                    'meta_description' => 'ကီမိုနို အထည်နှင့် Wagara ပုံစံများကို စတင်လေ့လာသူများအတွက် လမ်းညွှန်။',
                    'meta_keywords'    => 'ကီမိုနို အထည်, wagara',
                    'html_content'     => $this->blogHtml('what-is-kimono-fabric', 'my'),
                ],
            ],
            'how-to-use-pre-cut-kimono-fabric' => [
                'en' => [
                    'page_title'       => 'How to Use Pre-Cut Kimono Fabric',
                    'meta_title'       => 'How to Use Pre-Cut Kimono Fabric – Project Ideas | Fujiba',
                    'meta_description' => 'Practical guide to working with pre-cut kimono fabric: pre-wash, bias cuts for obi, project ideas (half-obi, pouches, haori, tapestries).',
                    'meta_keywords'    => 'pre-cut kimono fabric uses, kimono crafts, kimono remake',
                    'html_content'     => $this->blogHtml('how-to-use-pre-cut-kimono-fabric', 'en'),
                ],
                'ja' => [
                    'page_title'       => 'カット済み着物生地の使い方',
                    'meta_title'       => 'カット済み着物生地の使い方 | Fujiba',
                    'meta_description' => 'カット済み着物生地の水通し・斜め裁ち・お手入れの基本と、作れる作品例（半幅帯、ポーチ、羽織、タペストリー）。',
                    'meta_keywords'    => '着物 生地 リメイク, カット済み 生地 活用',
                    'html_content'     => $this->blogHtml('how-to-use-pre-cut-kimono-fabric', 'ja'),
                ],
                'my' => [
                    'page_title'       => 'ကြိုတင်ဖြတ်ထား ကီမိုနို အထည် အသုံးပြုနည်း',
                    'meta_title'       => 'ကြိုတင်ဖြတ်ထား ကီမိုနို အထည် — အသုံးပြုနည်း | Fujiba',
                    'meta_description' => 'ကြိုတင်ဖြတ်ထား ကီမိုနို အထည် ဖြင့် လုပ်နိုင်သော လက်လုပ်လုပ်ငန်းများ — half-obi, ပန်းကန်အိတ်, haori။',
                    'meta_keywords'    => 'ကီမိုနို အထည် အသုံးပြုနည်း',
                    'html_content'     => $this->blogHtml('how-to-use-pre-cut-kimono-fabric', 'my'),
                ],
            ],
            '2m-vs-3m-fabric' => [
                'en' => [
                    'page_title'       => '2m vs 3m Kimono Fabric: Which to Choose',
                    'meta_title'       => 'Kimono Fabric 2m vs 3m: Size Guide | Fujiba',
                    'meta_description' => 'A quick decision guide: when 2m is enough (half-obi, pouches, dolls) and when you need 3m (haori, nine-inch obi, tapestries, large totes).',
                    'meta_keywords'    => 'kimono fabric 2m vs 3m, kimono fabric size guide',
                    'html_content'     => $this->blogHtml('2m-vs-3m-fabric', 'en'),
                ],
                'ja' => [
                    'page_title'       => '着物生地 2m と 3m の違い・使い分け',
                    'meta_title'       => '着物生地 2m と 3m の違い | Fujiba',
                    'meta_description' => '2m と 3m はどちらを選ぶべきか。半幅帯・小物に最適な 2m と、九寸帯・羽織・タペストリーに必要な 3m の用途別ガイド。',
                    'meta_keywords'    => '着物 生地 2m 3m 違い',
                    'html_content'     => $this->blogHtml('2m-vs-3m-fabric', 'ja'),
                ],
                'my' => [
                    'page_title'       => '၂မီတာ နှင့် ၃မီတာ ကွာခြားချက်',
                    'meta_title'       => 'ကီမိုနို အထည် ၂မီတာ နှင့် ၃မီတာ | Fujiba',
                    'meta_description' => '၂မီတာ နှင့် ၃မီတာ — မည်သည့် အရွယ် ရွေးချယ်ရမည်ကို လွယ်ကူသော လမ်းညွှန်။',
                    'meta_keywords'    => '၂မီတာ ၃မီတာ, ကီမိုနို အရွယ်',
                    'html_content'     => $this->blogHtml('2m-vs-3m-fabric', 'my'),
                ],
            ],
            'japanese-pattern-meanings' => [
                'en' => [
                    'page_title'       => 'Traditional Japanese Patterns and Their Meaning',
                    'meta_title'       => 'Japanese Pattern Meanings: Sakura, Asanoha & More | Fujiba',
                    'meta_description' => 'The symbolism behind sakura, asanoha (hemp leaf), seigaiha (waves), ichimatsu (checker) and kinran (gold brocade) — and how to choose the right one.',
                    'meta_keywords'    => 'Japanese pattern meaning, wagara meaning, sakura meaning',
                    'html_content'     => $this->blogHtml('japanese-pattern-meanings', 'en'),
                ],
                'ja' => [
                    'page_title'       => '日本の伝統文様（和柄）の意味と由来',
                    'meta_title'       => '和柄の意味｜桜・麻の葉・青海波 | Fujiba',
                    'meta_description' => '桜・麻の葉・青海波・市松・金襴など、代表的な和柄の意味と由来、向いている制作物を解説します。',
                    'meta_keywords'    => '和柄 意味, 桜 意味, 麻の葉 意味',
                    'html_content'     => $this->blogHtml('japanese-pattern-meanings', 'ja'),
                ],
                'my' => [
                    'page_title'       => 'ဂျပန်ပုံစံ အဓိပ္ပါယ်များ',
                    'meta_title'       => 'ဂျပန်ပုံစံ အဓိပ္ပါယ်များ | Fujiba',
                    'meta_description' => 'ဆာကူရာ၊ asanoha၊ seigaiha၊ ichimatsu၊ kinran — ဂျပန်ရိုးရာ ပုံစံများ၏ အဓိပ္ပါယ်နှင့် မူရင်း။',
                    'meta_keywords'    => 'ဂျပန်ပုံစံ အဓိပ္ပါယ်',
                    'html_content'     => $this->blogHtml('japanese-pattern-meanings', 'my'),
                ],
            ],
            'best-fabric-for-handmade-kimono' => [
                'en' => [
                    'page_title'       => 'Best Fabric for Handmade Kimono Crafts',
                    'meta_title'       => 'Best Fabric for Handmade Kimono Crafts | Fujiba',
                    'meta_description' => 'Cotton vs polyester vs silk: how to pick the right kimono fabric for your project, with weight, drape and print-quality notes plus top-pick links.',
                    'meta_keywords'    => 'best kimono fabric, handmade kimono fabric',
                    'html_content'     => $this->blogHtml('best-fabric-for-handmade-kimono', 'en'),
                ],
                'ja' => [
                    'page_title'       => 'ハンドメイドにおすすめの着物生地',
                    'meta_title'       => 'ハンドメイドにおすすめの着物生地 | Fujiba',
                    'meta_description' => '綿・ポリエステル・正絹の特徴とおすすめ用途を比較。ハンドメイドで失敗しない着物生地の選び方を素材別に解説します。',
                    'meta_keywords'    => 'ハンドメイド 着物 生地, おすすめ 着物 生地',
                    'html_content'     => $this->blogHtml('best-fabric-for-handmade-kimono', 'ja'),
                ],
                'my' => [
                    'page_title'       => 'လက်လုပ် ကီမိုနိုအတွက် အကောင်းဆုံး အထည်',
                    'meta_title'       => 'လက်လုပ် ကီမိုနိုအတွက် အကောင်းဆုံး အထည် | Fujiba',
                    'meta_description' => 'ဝါဂွမ်း၊ ပိုလီဂျက်စတာ၊ ပိုးအထည် — လက်လုပ် ကီမိုနိုအတွက် မည်သည့် အထည်ကို ရွေးချယ်ရမည်ကို လမ်းညွှန်။',
                    'meta_keywords'    => 'အကောင်းဆုံး ကီမိုနို အထည်',
                    'html_content'     => $this->blogHtml('best-fabric-for-handmade-kimono', 'my'),
                ],
            ],
        ];
    }

    private function md(string $text): string
    {
        $paragraphs = preg_split('/\n\s*\n/', trim($text));
        return collect($paragraphs)->map(fn ($p) => '<p>'.nl2br(e(trim($p))).'</p>')->implode("\n");
    }

    private function blogHtml(string $slug, string $locale): string
    {
        $sections = $this->blogSections($slug, $locale);
        $out = '';
        foreach ($sections as $h2 => $body) {
            $out .= '<h2>'.e($h2).'</h2>'."\n".$this->md($body)."\n";
        }
        return $out;
    }

    private function blogSections(string $slug, string $locale): array
    {
        // Same content as the central seeder. Inlined here so this file is
        // self-contained and the central seeder can be removed later if you
        // want to fully decommission snssale.com.
        $copy = [
            'what-is-kimono-fabric' => [
                'en' => [
                    'Definition' => 'Kimono fabric (kimono-ji) is the textile traditionally used to make Japan\'s national garment. Today it is widely used in obi, accessories, and home-decor crafts. Fujiba\'s pre-cut panels are 110cm wide for general home-sewing compatibility.',
                    'Common materials' => 'Cotton (washable, beginner-friendly), polyester (colour-stable, affordable), and pure silk (drapes beautifully, dry-clean only).',
                    'Pattern families' => 'Sakura (cherry blossom), asanoha (hemp leaf), seigaiha (waves), ichimatsu (checker), kinran (gold brocade) and seasonal florals.',
                    'Where pre-cut helps' => 'A traditional bolt is around 12 metres. Most home projects need 2m or 3m. Pre-cut means no leftover, no yardage maths, and predictable shipping cost.',
                ],
                'ja' => [
                    '着物生地とは' => '着物生地（着物地）とは、日本の伝統衣装である着物を仕立てるために用いられてきた織物の総称です。現在では帯・小物・インテリアにも広く使われています。Fujiba のカット済み生地はハンドメイド向けに 110cm 幅で展開しています。',
                    '主な素材' => '綿（家庭で洗える、初心者向き）、ポリエステル（色落ちが少なく安価）、正絹（最も美しいドレープ、ドライクリーニング推奨）。',
                    '代表的な文様' => '桜・麻の葉・青海波・市松・金襴、季節の花柄など。',
                    'カット済みの利点' => '反物は約12mあり、家庭用途では余りが多く出ます。2m / 3m のカット済みなら、用尺計算不要・余り無し・送料も予測可能です。',
                ],
                'my' => [
                    'အဓိပ္ပါယ်' => 'ကီမိုနို အထည် (kimono-ji) သည် ဂျပန်ရိုးရာ ဝတ်စုံ ဖြစ်သော ကီမိုနို ချုပ်လုပ်ရန်အတွက် အသုံးပြုသော အထည်အမျိုးအစား ဖြစ်သည်။ Fujiba ၏ ကြိုတင်ဖြတ်ထား panel များသည် ၁၁၀စင်တီ အကျယ်ဖြစ်သည်။',
                    'ပစ္စည်းများ' => 'ဝါဂွမ်း၊ ပိုလီဂျက်စတာ၊ ပိုးအထည်။',
                    'ပုံစံ အမျိုးအစားများ' => 'ဆာကူရာ၊ asanoha၊ seigaiha၊ ichimatsu၊ kinran။',
                    'ကြိုတင်ဖြတ်ထား အကျိုးကျေးဇူး' => 'ပုံမှန် ၁၂မီတာ tanmono ထက် ၂မီတာ၊ ၃မီတာ ကြိုတင်ဖြတ်ထား panel များသည် ပိုသင့်တော်ပါသည်။',
                ],
            ],
            'how-to-use-pre-cut-kimono-fabric' => [
                'en' => [
                    'What 2m and 3m can make' => '2m fits half-obi, tsuke-eri collars, pouches, doll garments and small tapestries. 3m fits nine-inch nagoya obi, haori, large totes and full-size tapestries.',
                    'Pre-wash guide' => 'Cotton: machine-wash cold, single load on first wash. Polyester: cold rinse. Silk: dry-clean only.',
                    'Cutting on the bias for obi' => 'Bias-cut gives the obi face a softer fall. Mark the bias line with chalk; allow 1cm seam allowance.',
                    'Care after sewing' => 'Iron on the reverse to preserve gold thread (kinran). Store flat or rolled.',
                ],
                'ja' => [
                    '2m / 3m で作れるもの' => '2m：半幅帯・つけ襟・ポーチ・人形衣装・小タペストリー。3m：九寸帯・羽織・大判バッグ・大型タペストリー。',
                    '水通しの基本' => '綿：初回は単独で水通し。ポリエステル：冷水ですすぐ程度。正絹：ドライクリーニングのみ。',
                    '帯の斜め裁ち' => '帯は斜め（バイアス）に裁つことで風合いがやわらかくなります。縫い代1cmで裁断。',
                    '完成後のお手入れ' => '金襴はアイロンを裏面から。保管は平置きまたは丸めて。',
                ],
                'my' => [
                    '၂မီတာ၊ ၃မီတာ ဖြင့် လုပ်နိုင်သော အရာများ' => '၂မီတာ — half-obi, lapel, ပန်းကန်အိတ်များ။ ၃မီတာ — Nagoya obi, haori, အိတ်ကြီးများ။',
                    'အပြိုင်လျှော်နည်း' => 'ဝါဂွမ်း — ပထမအကြိမ် သီးသန့်။ ပိုလီဂျက်စတာ — အအေး။ ပိုးအထည် — Dry-clean သာ။',
                    'Obi အတွက် bias cut' => 'Obi မျက်နှာစာ ပိုနူးညံ့စေရန် bias ဖြတ်ပါ။',
                    'ပြုပြင်ထိန်းသိမ်းခြင်း' => 'Kinran ကို နောက်ဘက်မှ ပူပါ။',
                ],
            ],
            '2m-vs-3m-fabric' => [
                'en' => [
                    'Quick decision table' => 'Half-obi, pouch, doll, small tapestry → 2m. Nine-inch obi, haori, full tapestry, large tote → 3m.',
                    '2m projects' => 'Most beginner and intermediate handmade projects fit comfortably in 2m × 110cm.',
                    '3m projects' => 'A nine-inch nagoya obi finished face is around 3.6m × 31cm. Cutting from a 3m panel is feasible with careful layout.',
                    'Common pitfalls' => 'Don\'t buy 2m if your project mentions "obi" — most obi shapes won\'t fit. Don\'t buy 3m if all you need is collars and pouches.',
                ],
                'ja' => [
                    'クイック判定表' => '半幅帯・ポーチ・人形・小タペストリー → 2m。九寸帯・羽織・大判タペストリー・大バッグ → 3m。',
                    '2m で作れる物' => '初級〜中級のハンドメイドのほとんどは 2m × 110cm で収まります。',
                    '3m で作れる物' => '九寸名古屋帯（仕立て上がり 約3.6m × 31cm）の表地は、3m × 110cm から型取り次第で制作可能。',
                    'よくある失敗' => '帯を作るのに 2m を買ってしまう／小物しか作らないのに 3m を買う、の二つに注意。',
                ],
                'my' => [
                    'အမြန် ရွေးချယ်နည်း' => 'Half-obi, ပန်းကန်အိတ်, အရုပ် → ၂မီတာ။ Nine-inch obi, haori → ၃မီတာ။',
                    '၂မီတာ ဖြင့် လုပ်နိုင်သော အရာများ' => 'အစပျိုး၊ အလယ်တန်း လက်လုပ်လုပ်ငန်းအများစု ၂မီတာ ဖြင့် လုပ်နိုင်သည်။',
                    '၃မီတာ ဖြင့် လုပ်နိုင်သော အရာများ' => 'Nine-inch nagoya obi (၃.၆မီတာ) ကို ၃မီတာ panel မှ ဖြတ်နိုင်သည်။',
                    'အများ ပြုလုပ်တတ်သော အမှားများ' => 'Obi လုပ်ရန် ၂မီတာ ဝယ်ခြင်း — ရှောင်ပါ။',
                ],
            ],
            'japanese-pattern-meanings' => [
                'en' => [
                    'Sakura (cherry blossom)' => 'Beauty and the transient nature of life. Worn in spring; appropriate for celebratory pieces.',
                    'Asanoha (hemp leaf)' => 'Healthy growth of children. Common on baby kimono and gifts for new parents.',
                    'Seigaiha (waves)' => 'Eternal peace and good fortune flowing endlessly. Suits formal pieces.',
                    'Ichimatsu (checker)' => 'Lasting prosperity. Modern and graphic — also used by the Tokyo 2020 Olympics emblem.',
                    'Kinran (gold brocade)' => 'Highest formality. Appropriate for bridal, ceremonial and altar pieces.',
                ],
                'ja' => [
                    '桜（さくら）' => '美しさと、無常観・はかなさ。春のテーマ、祝い物に。',
                    '麻の葉（あさのは）' => '子どもの健やかな成長。出産祝いや子供物に多い。',
                    '青海波（せいがいは）' => '永遠の平穏、絶え間なく続く幸福。フォーマル向け。',
                    '市松（いちまつ）' => '繁栄が途切れず続くこと。モダンな印象。',
                    '金襴（きんらん）' => '最もフォーマル。婚礼・式典・仏事向き。',
                ],
                'my' => [
                    'ဆာကူရာ' => 'အလှနှင့် ဘဝ၏ ခဏတာသော သဘော။ နွေဦးတွင် ဝတ်ဆင်ခြင်း။',
                    'Asanoha (လျှော်ရွက်)' => 'ကလေး ကျန်းမာစွာ ကြီးထွားခြင်း။',
                    'Seigaiha (လှိုင်း)' => 'ထာဝရ ငြိမ်းချမ်းခြင်း။',
                    'Ichimatsu (ဇကာကွက်)' => 'မဆုံးနိုင်သော စည်ပင်သိုက်မြိုက်မှု။',
                    'Kinran (ရွှေပန်းပု)' => 'အမြင့်ဆုံး ပုံမှန်အဆင့်။',
                ],
            ],
            'best-fabric-for-handmade-kimono' => [
                'en' => [
                    'Cotton vs polyester vs silk' => 'Cotton: easy to wash, beginner-friendly. Polyester: colourfast and cheap. Silk: best drape; requires dry cleaning.',
                    'Weight and drape' => 'Lightweight (≤ 100 gsm) suits collars and pouches. Mid-weight (100–180 gsm) is the most versatile. Heavy (180+ gsm) suits obi and outerwear.',
                    'Print quality' => 'Look for clean registration on multi-colour wagara. Gold thread on kinran should sit flat.',
                    'Pre-cut vs bolt' => 'Pre-cut wins on cost predictability and beginner accessibility.',
                ],
                'ja' => [
                    '綿・ポリエステル・正絹' => '綿：扱いやすく初心者向き。ポリエステル：色落ちなく安価。正絹：最高のドレープと風合いだが要ドライクリーニング。',
                    '生地の重さとドレープ' => '軽量（〜100g/m²）は襟・小物に。中量（100〜180g/m²）が最も汎用。厚地は帯・羽織向き。',
                    'プリント品質' => '多色和柄は版ズレの少なさをチェック。金襴の金糸は浮かず・ほつれず平らに織られていること。',
                    'カット済み vs 反物' => 'コスト予測と初心者アクセスではカット済みが有利。',
                ],
                'my' => [
                    'ပစ္စည်း နှိုင်းယှဉ်ခြင်း' => 'ဝါဂွမ်း — လွယ်ကူ၊ ပိုလီဂျက်စတာ — အရောင်ခိုင်မြဲ၊ ပိုးအထည် — အကောင်းဆုံး drape။',
                    'အလေးချိန်နှင့် Drape' => 'ပေါ့ပါးသော အထည် — လည်ဆွဲ၊ ပန်းကန်အိတ်။',
                    'Print အရည်အသွေး' => 'Multi-colour wagara — registration ကြည်ကြည်လင်လင်။',
                    'ကြိုတင်ဖြတ် vs Tanmono' => 'ကြိုတင်ဖြတ်ထား — ကုန်ကျစရိတ် ခန့်မှန်းရလွယ်။',
                ],
            ],
        ];

        return $copy[$slug][$locale] ?? [];
    }
}

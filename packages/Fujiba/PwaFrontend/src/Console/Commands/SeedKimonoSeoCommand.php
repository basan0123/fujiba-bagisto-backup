<?php

namespace Fujiba\PwaFrontend\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webkul\Category\Models\CategoryProxy;
use Webkul\Category\Models\CategoryTranslationProxy;
use Webkul\CMS\Models\PageProxy;
use Webkul\CMS\Models\PageTranslationProxy;
use Webkul\Core\Models\Locale;

/**
 * Seeds the kimono-fabric category tree (1 parent + 6 children) and 5 blog
 * CMS pages, each in en/ja/my, against the central database. Idempotent:
 * matches by slug/url_key and updates the row in place if it already exists.
 *
 * Usage:
 *   php artisan fujiba:seed-kimono-seo            # write
 *   php artisan fujiba:seed-kimono-seo --dry-run  # show what would change
 *
 * After this command runs, the storefront category tree, sitemap, and hreflang
 * additions from Chunk 2 pick up the new rows automatically (sitemap is cached
 * 1h; the command flushes that cache when --no-cache-clear isn't passed).
 */
class SeedKimonoSeoCommand extends Command
{
    protected $signature = 'fujiba:seed-kimono-seo {--dry-run : Show what would change without writing}
                                                   {--no-cache-clear : Skip view/config/cache flush at end}';

    protected $description = 'Seed kimono-fabric categories and SEO blog CMS pages (3 locales).';

    /**
     * Channel id to attach CMS pages to (1 = default per the central schema).
     */
    private const CHANNEL_ID = 1;

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $this->info($dry ? '[DRY RUN] No DB writes will be performed.' : 'Writing to central DB.');

        $localeIds = Locale::pluck('id', 'code')->all();
        foreach (['en', 'ja', 'my'] as $code) {
            if (empty($localeIds[$code])) {
                $this->error("Locale '$code' not found in locales table. Aborting.");
                return self::FAILURE;
            }
        }

        $rootId = CategoryProxy::modelClass()::query()->whereNull('parent_id')->orderBy('id')->value('id');
        if (! $rootId) {
            $this->error('No root category found. Aborting.');
            return self::FAILURE;
        }
        $this->line("Root category id: $rootId");

        DB::beginTransaction();
        try {
            $parent = $this->upsertCategory(
                slug: 'kimono-fabric',
                parentId: $rootId,
                position: 10,
                translations: $this->parentCategoryCopy(),
                localeIds: $localeIds,
                dry: $dry,
            );

            foreach ($this->childCategoriesCopy() as $slug => $copy) {
                $this->upsertCategory(
                    slug: $slug,
                    parentId: $parent['id'],
                    position: $copy['position'],
                    translations: $copy['translations'],
                    localeIds: $localeIds,
                    dry: $dry,
                );
            }

            foreach ($this->cmsPagesCopy() as $urlKey => $copy) {
                $this->upsertCmsPage(
                    urlKey: $urlKey,
                    translations: $copy,
                    localeIds: $localeIds,
                    dry: $dry,
                );
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
            $this->line($e->getTraceAsString());
            return self::FAILURE;
        }

        if (! $dry && ! $this->option('no-cache-clear')) {
            $this->call('cache:clear');
            $this->call('config:clear');
            $this->call('view:clear');
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    /**
     * @return array{id:int}
     */
    private function upsertCategory(string $slug, int $parentId, int $position, array $translations, array $localeIds, bool $dry): array
    {
        $catModel   = CategoryProxy::modelClass();
        $transModel = CategoryTranslationProxy::modelClass();

        $existing = $transModel::query()->where('slug', $slug)->first();
        $cat = $existing
            ? $catModel::query()->find($existing->category_id)
            : null;

        if ($cat) {
            $this->line("[update] category '$slug' (id={$cat->id})");
            if (! $dry) {
                $cat->parent_id = $parentId;
                $cat->position  = $position;
                $cat->status    = 1;
                $cat->save();
            }
        } else {
            $this->line("[create] category '$slug'");
            if ($dry) {
                $cat = (object) ['id' => 0];
            } else {
                // Use Kalnoy nested-set: create a node under the parent so
                // _lft/_rgt are filled. NodeTrait::create() uses the parent
                // relation's method when `parent_id` is in attributes.
                $cat = new $catModel([
                    'position'     => $position,
                    'status'       => 1,
                    'display_mode' => 'products_and_description',
                ]);
                $parent = $catModel::query()->find($parentId);
                $cat->appendToNode($parent)->save();
                $cat->refresh();
            }
        }

        foreach ($translations as $localeCode => $fields) {
            if ($dry) continue;
            $localeId = $localeIds[$localeCode];

            // CategoryTranslation::$fillable doesn't include category_id /
            // locale, so updateOrCreate's mass-assign drops them. Set them
            // explicitly via direct attribute assignment.
            $row = $transModel::query()
                ->where('category_id', $cat->id)
                ->where('locale', $localeCode)
                ->first()
                ?? new $transModel();

            $row->category_id      = $cat->id;
            $row->locale           = $localeCode;
            $row->locale_id        = $localeId;
            $row->slug             = $slug;
            $row->name             = $fields['name'];
            $row->description      = $fields['description'];
            $row->meta_title       = $fields['meta_title'];
            $row->meta_description = $fields['meta_description'];
            $row->meta_keywords    = $fields['meta_keywords'];
            $row->save();
        }

        return ['id' => $cat->id];
    }

    private function upsertCmsPage(string $urlKey, array $translations, array $localeIds, bool $dry): void
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

        foreach ($translations as $localeCode => $fields) {
            $payload = array_merge($fields, [
                'url_key' => $urlKey,
                'locale'  => $localeCode,
            ]);
            if ($dry) continue;
            $transModel::query()->updateOrCreate(
                ['cms_page_id' => $page->id, 'locale' => $localeCode],
                $payload,
            );
        }

        if (! $dry && is_object($page) && method_exists($page, 'channels')) {
            $page->channels()->sync([self::CHANNEL_ID]);
        }
    }

    /* ───────────────────────── Copy ───────────────────────── */

    private function parentCategoryCopy(): array
    {
        return [
            'en' => [
                'name'             => 'Kimono Fabric',
                'description'      => $this->md(<<<'TXT'
Fujiba's Kimono Fabric collection is built for handmade-craft makers, kimono-remake hobbyists and accessory designers who want pre-cut Japanese wagara textiles without the hassle of buying a full bolt. Standard kimono bolts (tanmono) are around 12 metres long, awkward to store, and force you to calculate yardage before you even start a project. We solve that by cutting every fabric in our catalogue to either 2m × 110cm or 3m × 110cm — the two sizes that cover almost every handmade kimono, obi, accessory and home-decor project.

The catalogue focuses on traditional Japanese patterns: sakura (cherry blossom), asanoha (hemp leaf), seigaiha (waves), ichimatsu (checker) and kinran (gold brocade), alongside seasonal florals and modern takes on wagara. Every panel is inspected for weave defects and colour consistency before it ships.

Shop by size, by pattern, or read our 2m vs 3m guide for project-by-project sizing. All prices in Japanese Yen (JPY), shipping worldwide.
TXT),
                'meta_title'       => 'Kimono Fabric – Pre-Cut 2m & 3m Japanese Wagara | Fujiba',
                'meta_description' => 'Buy pre-cut kimono fabric in 2m × 110cm or 3m × 110cm panels. Sakura, asanoha, gold brocade and other traditional Japanese patterns for handmade crafts.',
                'meta_keywords'    => 'kimono fabric, Japanese traditional fabric, wagara, pre-cut kimono fabric, 2m, 3m, 110cm',
            ],
            'ja' => [
                'name'             => '着物生地',
                'description'      => $this->md(<<<'TXT'
Fujiba の「着物生地」カテゴリーは、和柄を愛するハンドメイド作家・着物リメイク愛好家・小物作家のための、カット済み生地の総合ページです。一般的な反物（約12m）は家庭での取り回しが難しく、用尺の計算もハードルになります。当店ではこの問題を解決するため、すべての生地を 2m × 110cm または 3m × 110cm に事前カットして販売しています。

桜・麻の葉・青海波・市松・金襴など、日本の伝統文様を中心に、季節を問わず使える定番柄から、贈答・リメイク向けの華やかな柄まで取り揃えています。すべての生地は反物から検反のうえ 110cm 幅でカットし、ほつれ・色ムラのないものだけを出荷しています。

サイズで選ぶ、柄で選ぶ、または 2m と 3m の違いガイドをご覧ください。価格はすべて日本円（JPY）表示、日本国内および海外配送に対応しています。
TXT),
                'meta_title'       => '着物生地 通販｜2m・3m カット済み 和柄ハンドメイド | Fujiba',
                'meta_description' => '和柄の着物生地を 2m × 110cm / 3m × 110cm のカット済みでお届け。桜・麻の葉・金襴など、ハンドメイド向けの日本伝統文様を取り揃えました。',
                'meta_keywords'    => '着物 生地, 和柄 生地, 着物 生地 通販, カット済み, 2m, 3m, 110cm',
            ],
            'my' => [
                'name'             => 'ကီမိုနို အထည်',
                'description'      => $this->md(<<<'TXT'
Fujiba ၏ ကီမိုနို အထည် အမျိုးအစားသည် လက်လုပ် အနုပညာ ဖန်တီးသူများ၊ ကီမိုနို ပြန်လည်ပြုပြင်သူများ အတွက် အထူးပြင်ဆင်ထားသော အထည်စုဆောင်းမှု ဖြစ်ပါသည်။ ဂျပန်ရိုးရာ ကီမိုနို အထည်ကို ပုံမှန် ၁၂မီတာ အရှည် (tanmono) ဖြင့် ဝယ်ယူရသည် ထို့ကြောင့် အိမ်တွင် သိမ်းဆည်းရန်နှင့် တွက်ချက်ရန် ခက်ခဲပါသည်။ Fujiba တွင် အထည်အားလုံးကို ၂မီတာ × ၁၁၀စင်တီ သို့မဟုတ် ၃မီတာ × ၁၁၀စင်တီ အရွယ် ကြိုတင်ဖြတ်ထား ရောင်းချပေးသည်။

ဆာကူရာ၊ အာဆာနိုဟာ၊ ဆေးဂိုင်ဟာ၊ အီချီမတ်ဆု၊ ကင်းရန် — ဂျပန်ရိုးရာ ပုံစံများ အပါအဝင် ရာသီအလိုက် ပန်းပုံစံများ ရရှိနိုင်ပါသည်။

အရွယ်အလိုက်၊ ပုံစံအလိုက် ဝယ်ယူနိုင်ပါသည်။ ဈေးနှုန်းအားလုံးကို ဂျပန် ယန်း (JPY) ဖြင့် ဖော်ပြထားပြီး ကမ္ဘာတစ်ဝှမ်း ပို့ဆောင်ပေးပါသည်။
TXT),
                'meta_title'       => 'ကီမိုနို အထည် ၂မီတာ၊ ၃မီတာ ကြိုတင်ဖြတ်ထား | Fujiba',
                'meta_description' => 'ကြိုတင်ဖြတ်ထား ကီမိုနို အထည်များ — ၂မီတာ × ၁၁၀စင်တီ နှင့် ၃မီတာ × ၁၁၀စင်တီ။ ဆာကူရာ၊ အာဆာနိုဟာ၊ ရွှေပန်းပု ပုံစံများ။',
                'meta_keywords'    => 'ကီမိုနို အထည်, ဂျပန် ရိုးရာ အထည်, ၂မီတာ, ၃မီတာ',
            ],
        ];
    }

    private function childCategoriesCopy(): array
    {
        return [
            'kimono-fabric-2m' => [
                'position' => 20,
                'translations' => [
                    'en' => [
                        'name' => 'Kimono Fabric 2m × 110cm',
                        'description' => $this->md('The 2m × 110cm category collects every kimono fabric in our catalogue cut to the most workable length for home sewists — half-obi, tsuke-eri collars, pouches, doll garments and small tapestries. Patterns include sakura, asanoha, seigaiha, ichimatsu and kinran. Materials: 100% cotton, polyester and pure silk. All prices in JPY.'),
                        'meta_title' => 'Kimono Fabric 2m × 110cm – Pre-Cut Wagara Panels | Fujiba',
                        'meta_description' => 'Pre-cut kimono fabric in 2m × 110cm panels — sized for half-obi, collars, pouches and doll garments. Sakura, asanoha and gold brocade patterns.',
                        'meta_keywords' => 'kimono fabric 2m, 2m 110cm, half-obi fabric, pre-cut kimono fabric',
                    ],
                    'ja' => [
                        'name' => '着物生地 2m × 110cm',
                        'description' => $this->md('「着物生地 2m × 110cm」は、ハンドメイド作家がもっとも扱いやすい長さに事前カットされた和柄生地のカテゴリーです。半幅帯・つけ襟・つけ帯・ポーチ・がま口・人形衣装・タペストリー（小）など、初心者から中級者の制作のほぼすべてをカバーします。素材は綿100%・ポリエステル・正絹。価格はすべて日本円（JPY）。'),
                        'meta_title' => '着物生地 2m × 110cm カット済み｜和柄ハンドメイド用 | Fujiba',
                        'meta_description' => '2m × 110cm にカット済みの着物生地。半幅帯・つけ襟・小物制作に最適なサイズで、桜・麻の葉・金襴などの和柄を取り揃えました。',
                        'meta_keywords' => '着物 生地 2m, 着物 生地 110cm, 半幅帯 生地, カット済み 生地',
                    ],
                    'my' => [
                        'name' => 'ကီမိုနို အထည် ၂မီတာ × ၁၁၀စင်တီ',
                        'description' => $this->md('၂မီတာ × ၁၁၀စင်တီ အမျိုးအစားသည် လက်လုပ် အနုပညာ ဖန်တီးသူများအတွက် အသုံးပြုရ အလွယ်ဆုံး အရွယ်ဖြစ်သည်။ Half-obi, tsuke-eri လည်ဆွဲ, ပန်းကန်အိတ်, အရုပ်ဝတ်စုံများ နှင့် နံရံချိတ်အလှပြင်ပစ္စည်း ငယ်များ — အစပျိုး၊ အလယ်တန်း လက်လုပ်လုပ်ငန်းအများစုကို ဖြည့်ဆည်းပေးနိုင်ပါသည်။ ဈေးနှုန်းများ ဂျပန် ယန်း (JPY)။'),
                        'meta_title' => 'ကီမိုနို အထည် ၂မီတာ × ၁၁၀စင်တီ ကြိုတင်ဖြတ်ထား | Fujiba',
                        'meta_description' => '၂မီတာ × ၁၁၀စင်တီ ကြိုတင်ဖြတ်ထား ကီမိုနို အထည်။ Half-obi, ပန်းကန်အိတ်နှင့် လက်လုပ် အနုပညာအတွက် သင့်တော်သည်။',
                        'meta_keywords' => 'ကီမိုနို အထည် ၂မီတာ, ၂မီတာ ၁၁၀စင်တီ',
                    ],
                ],
            ],
            'kimono-fabric-3m' => [
                'position' => 30,
                'translations' => [
                    'en' => [
                        'name' => 'Kimono Fabric 3m × 110cm',
                        'description' => $this->md('The 3m × 110cm category is built for larger handmade projects — nine-inch nagoya obi, haori jackets, full-size tapestries, large totes, and the upper bodice of kimono-remake garments. At 3 metres, the panel gives you the length needed for a finished obi face (~360cm) while staying manageable on a domestic sewing setup. Materials: 100% cotton, polyester and pure silk. All prices in JPY.'),
                        'meta_title' => 'Kimono Fabric 3m × 110cm – Pre-Cut for Obi & Haori | Fujiba',
                        'meta_description' => 'Pre-cut kimono fabric in 3m × 110cm panels. Sized for nine-inch obi, haori jackets, tapestries and large totes. Sakura, wagara and gold brocade patterns.',
                        'meta_keywords' => 'kimono fabric 3m, nagoya obi fabric, haori fabric, 3m 110cm',
                    ],
                    'ja' => [
                        'name' => '着物生地 3m × 110cm',
                        'description' => $this->md('「着物生地 3m × 110cm」は、羽織・九寸名古屋帯・大判バッグ・タペストリー・着物リメイクの上身頃など、大物制作向けにカット済みの和柄生地カテゴリーです。3m × 110cm は、九寸帯（仕立て上がり約 360cm × 31cm）の表地に必要な長さを確保しつつ、家庭での取り回しが可能な実用サイズ。素材は綿100%・ポリエステル・正絹。価格はすべて日本円（JPY）。'),
                        'meta_title' => '着物生地 3m × 110cm カット済み｜羽織・九寸帯向け | Fujiba',
                        'meta_description' => '3m × 110cm にカット済みの着物生地。羽織・九寸帯・大判バッグ・タペストリーなど大物制作に最適。和柄・桜・金襴を取り揃えました。',
                        'meta_keywords' => '着物 生地 3m, 九寸帯 生地, 羽織 生地, 着物 生地 110cm',
                    ],
                    'my' => [
                        'name' => 'ကီမိုနို အထည် ၃မီတာ × ၁၁၀စင်တီ',
                        'description' => $this->md('၃မီတာ × ၁၁၀စင်တီ အမျိုးအစားသည် ပိုကြီးသော လက်လုပ်လုပ်ငန်းများအတွက် ဖန်တီးထားသည် — nine-inch nagoya obi, haori ဂျာကင်များ, နံရံချိတ် အလှပြင်ပစ္စည်းကြီးများ, အိတ်ကြီးများ, ကီမိုနို ပြန်လည်ပြုပြင်ခြင်း ၏ အပေါ်ပိုင်း အင်္ကျီများ။ ဈေးနှုန်းများ ဂျပန် ယန်း (JPY)။'),
                        'meta_title' => 'ကီမိုနို အထည် ၃မီတာ × ၁၁၀စင်တီ – Obi နှင့် Haori အတွက် | Fujiba',
                        'meta_description' => '၃မီတာ × ၁၁၀စင်တီ ကြိုတင်ဖြတ်ထား ကီမိုနို အထည်။ Nine-inch obi, haori ဂျာကင်နှင့် နံရံချိတ် အလှပြင်ပစ္စည်းများအတွက်။',
                        'meta_keywords' => 'ကီမိုနို အထည် ၃မီတာ, ၃မီတာ ၁၁၀စင်တီ',
                    ],
                ],
            ],
            'wagara' => [
                'position' => 40,
                'translations' => [
                    'en' => [
                        'name' => 'Traditional Patterns (Wagara)',
                        'description' => $this->md('Wagara is the umbrella term for traditional Japanese patterns — asanoha, seigaiha, ichimatsu, shippo, yagasuri — refined over centuries and central to kimono and craft textiles today. Each pattern carries meaning: asanoha for healthy growth, seigaiha for eternal peace, ichimatsu for prosperity, shippo for harmonious relationships. All panels cut to handmade-friendly 2m or 3m × 110cm. Prices in JPY.'),
                        'meta_title' => 'Traditional Japanese Pattern Fabric (Wagara) – Pre-Cut | Fujiba',
                        'meta_description' => 'Wagara kimono fabric — asanoha, seigaiha, ichimatsu, shippo and more. Traditional Japanese patterns in pre-cut 2m and 3m panels with pattern-meaning notes.',
                        'meta_keywords' => 'wagara fabric, asanoha, seigaiha, ichimatsu, shippo, Japanese pattern',
                    ],
                    'ja' => [
                        'name' => '和柄',
                        'description' => $this->md('和柄は、日本で何百年にもわたって受け継がれてきた伝統文様の総称です。麻の葉・青海波・市松・七宝・矢絣など、由緒ある文様を中心に、ハンドメイドで使いやすい 2m × 110cm / 3m × 110cm のカット済み生地として展開しています。麻の葉は子どもの健やかな成長、青海波は永遠の平穏、市松は子孫繁栄、七宝は円満な人間関係——贈り物や記念品の制作にも適しています。価格はすべて日本円（JPY）。'),
                        'meta_title' => '和柄 生地 通販｜伝統文様 着物生地カット済み | Fujiba',
                        'meta_description' => '麻の葉・青海波・市松・七宝など、日本の伝統文様（和柄）生地を 2m / 3m カット済みでお届け。意味と由来も商品ページでご紹介。',
                        'meta_keywords' => '和柄 生地, 麻の葉 生地, 青海波 生地, 市松 生地, 七宝 生地, 矢絣',
                    ],
                    'my' => [
                        'name' => 'ရိုးရာပုံစံများ (Wagara)',
                        'description' => $this->md('Wagara သည် ဂျပန်ရိုးရာ ပုံစံများ၏ စုပေါင်းအမည် ဖြစ်သည် — အာဆာနိုဟာ၊ ဆေးဂိုင်ဟာ၊ အီချီမတ်ဆု၊ shippo, yagasuri — ရာစုနှစ်များစွာ တိုးတက်ဖြစ်ပေါ်လာသော ဒီဇိုင်း အမွေအနှစ်များ။ ပုံစံတိုင်း တွင် အဓိပ္ပါယ်ရှိသည်။ ၂မီတာ နှင့် ၃မီတာ × ၁၁၀စင်တီ ကြိုတင်ဖြတ်ထားသော အရွယ်များဖြင့် ရရှိနိုင်သည်။ ဈေးနှုန်းများ ဂျပန် ယန်း (JPY)။'),
                        'meta_title' => 'ဂျပန် ရိုးရာ ပုံစံ အထည် (Wagara) ကြိုတင်ဖြတ်ထား | Fujiba',
                        'meta_description' => 'Wagara — အာဆာနိုဟာ၊ ဆေးဂိုင်ဟာ၊ အီချီမတ်ဆု၊ shippo ဂျပန်ရိုးရာ ပုံစံ ကီမိုနို အထည်များ — ပုံစံ အဓိပ္ပါယ်များ နှင့်အတူ။',
                        'meta_keywords' => 'wagara, ဂျပန် ရိုးရာ ပုံစံ, asanoha, seigaiha',
                    ],
                ],
            ],
            'sakura' => [
                'position' => 50,
                'translations' => [
                    'en' => [
                        'name' => 'Sakura',
                        'description' => $this->md('Sakura — the cherry blossom — is the single most iconic motif in Japanese textile design. Solo-blossom prints, sakura with flowing-water, sakura with fans, sakura highlighted with gold leaf. Ground colours include pink, white, navy, black and gold. Pre-cut to 2m × 110cm or 3m × 110cm for spring craft, graduation/wedding keepsakes, New Year decor and gift tapestries. Prices in JPY.'),
                        'meta_title' => 'Sakura Kimono Fabric – Pre-Cut Cherry Blossom Wagara | Fujiba',
                        'meta_description' => 'Cherry-blossom (sakura) kimono fabric in pre-cut 2m × 110cm and 3m × 110cm panels. Pink, white, navy, black and gold grounds for handmade crafts.',
                        'meta_keywords' => 'sakura fabric, cherry blossom fabric, sakura kimono, pink wagara',
                    ],
                    'ja' => [
                        'name' => '桜',
                        'description' => $this->md('桜は日本を象徴する花であり、和柄のなかでも特に人気の高い文様です。春の単独柄から、桜と流水・桜と扇・桜と金箔を組み合わせた華やかな意匠まで、ハンドメイド作家向けの桜柄生地を 2m × 110cm / 3m × 110cm のカット済みで取り揃えています。ピンク地・白地・紺地・黒地・金地。価格はすべて日本円（JPY）。'),
                        'meta_title' => '桜柄 生地 通販｜着物生地 カット済み 2m・3m | Fujiba',
                        'meta_description' => '桜柄の着物生地を 2m × 110cm / 3m × 110cm カット済みでお届け。春の和小物・つけ襟・タペストリー制作に最適です。',
                        'meta_keywords' => '桜柄 生地, 着物 生地 桜, 桜 ピンク 生地',
                    ],
                    'my' => [
                        'name' => 'ဆာကူရာ',
                        'description' => $this->md('ဆာကူရာ (ချယ်ရီပန်း) သည် ဂျပန်အထည်ဒီဇိုင်း တွင် အထင်ရှားဆုံး ပုံစံ ဖြစ်သည်။ ပန်းရောင်၊ အဖြူ၊ နှင်းအပြာ၊ အနက်နှင့် ရွှေ နောက်ခံများ။ ၂မီတာ × ၁၁၀စင်တီ နှင့် ၃မီတာ × ၁၁၀စင်တီ ကြိုတင်ဖြတ်ထား။ ဈေးနှုန်းများ ဂျပန် ယန်း (JPY)။'),
                        'meta_title' => 'ဆာကူရာ ကီမိုနို အထည် – ချယ်ရီပန်း ၂မီတာ၊ ၃မီတာ | Fujiba',
                        'meta_description' => 'ဆာကူရာ (ချယ်ရီပန်း) ကီမိုနို အထည် ၂မီတာ × ၁၁၀စင်တီ နှင့် ၃မီတာ × ၁၁၀စင်တီ ကြိုတင်ဖြတ်ထား။',
                        'meta_keywords' => 'ဆာကူရာ, ချယ်ရီပန်း အထည်',
                    ],
                ],
            ],
            'floral' => [
                'position' => 60,
                'translations' => [
                    'en' => [
                        'name' => 'Floral',
                        'description' => $this->md('Beyond sakura, Japanese floral textiles cover a full seasonal vocabulary: peony (botan — nobility/wealth), plum (ume — early spring/perseverance), chrysanthemum (kiku — longevity/dignity), camellia (tsubaki — quiet beauty), wisteria (fuji — graceful flexibility). Cut to handmade-friendly 2m or 3m × 110cm. Prices in JPY.'),
                        'meta_title' => 'Japanese Floral Kimono Fabric – Peony, Plum, Chrysanthemum | Fujiba',
                        'meta_description' => 'Japanese floral kimono fabric beyond sakura — peony, plum, chrysanthemum, camellia, wisteria. Pre-cut 2m × 110cm and 3m × 110cm panels.',
                        'meta_keywords' => 'Japanese floral fabric, peony fabric, plum fabric, chrysanthemum fabric',
                    ],
                    'ja' => [
                        'name' => '花柄',
                        'description' => $this->md('桜以外にも、日本の花柄には季節を表現する豊かな種類があります。牡丹（高貴・富貴）、梅（早春・忍耐）、菊（長寿・気高さ）、椿（控えめな美しさ）、藤（しなやかさ）など、四季それぞれを象徴する花柄を、ハンドメイド向けの 2m × 110cm / 3m × 110cm カット済みで展開しています。価格はすべて日本円（JPY）。'),
                        'meta_title' => '花柄 着物生地 通販｜牡丹・梅・菊 カット済み 2m・3m | Fujiba',
                        'meta_description' => '牡丹・梅・菊・椿・藤など、桜以外の花柄着物生地を 2m × 110cm / 3m × 110cm カット済みでお届け。和小物・タペストリー制作に。',
                        'meta_keywords' => '花柄 生地, 牡丹 生地, 梅 生地, 菊 生地, 椿 生地, 藤 生地',
                    ],
                    'my' => [
                        'name' => 'ပန်းပုံစံ',
                        'description' => $this->md('ဆာကူရာ အပြင် ဂျပန်အထည်ဒီဇိုင်း တွင် ရာသီတိုင်းအတွက် ပန်းပုံစံများ ရှိသည် — peony (botan)၊ plum (ume)၊ chrysanthemum (kiku)၊ camellia (tsubaki)၊ wisteria (fuji)။ ၂မီတာ × ၁၁၀စင်တီ နှင့် ၃မီတာ × ၁၁၀စင်တီ ကြိုတင်ဖြတ်ထား။ ဈေးနှုန်းများ ဂျပန် ယန်း (JPY)။'),
                        'meta_title' => 'ဂျပန် ပန်းပုံစံ ကီမိုနို အထည် – Peony, Plum, Chrysanthemum | Fujiba',
                        'meta_description' => 'ဆာကူရာ မဟုတ်သော ဂျပန် ပန်းပုံစံ ကီမိုနို အထည်များ — peony, plum, chrysanthemum, camellia, wisteria။ ၂မီတာ၊ ၃မီတာ ကြိုတင်ဖြတ်ထား။',
                        'meta_keywords' => 'ဂျပန် ပန်းပုံစံ, peony, plum, chrysanthemum',
                    ],
                ],
            ],
            'gold-brocade' => [
                'position' => 70,
                'translations' => [
                    'en' => [
                        'name' => 'Gold Brocade',
                        'description' => $this->md('Kinran is the most formal of all kimono textiles — a brocade woven with gold thread to render auspicious patterns in shimmer and relief. Historically used in uchikake bridal robes, fukuro-obi, Noh costumes and temple decoration. Available in handmade-ready 2m × 110cm and 3m × 110cm panels: sakura kinran, peony kinran, kumotori (cloud-band) kinran, geometric kinran. Prices in JPY.'),
                        'meta_title' => 'Gold Brocade (Kinran) Kimono Fabric – Pre-Cut Formal Wagara | Fujiba',
                        'meta_description' => 'Kinran (gold brocade) kimono fabric in pre-cut 2m × 110cm and 3m × 110cm panels. For obi, ceremonial garments and decorative pieces.',
                        'meta_keywords' => 'kinran, gold brocade, formal kimono fabric, obi fabric',
                    ],
                    'ja' => [
                        'name' => '金襴',
                        'description' => $this->md('金襴（きんらん）は、金糸を織り込んで文様を表現する、もっともフォーマルな和柄生地です。古来より打掛・袋帯・能装束・寺社の装飾に使われてきた格式高い織物で、Fujiba では帯・式典用衣装・装飾用途のハンドメイドのために、2m × 110cm / 3m × 110cm のカット済みでご提供しています。価格はすべて日本円（JPY）。'),
                        'meta_title' => '金襴 生地 通販｜フォーマル和柄 カット済み 2m・3m | Fujiba',
                        'meta_description' => '金襴（きんらん）生地を 2m × 110cm / 3m × 110cm カット済みでお届け。袋帯・九寸帯・式典用衣装・装飾向けのフォーマル和柄。',
                        'meta_keywords' => '金襴 生地, 金襴 着物, きんらん 生地, 袋帯 生地',
                    ],
                    'my' => [
                        'name' => 'ရွှေပန်းပု',
                        'description' => $this->md('Kinran (金襴) သည် ကီမိုနို အထည်များထဲတွင် အခမ်းနားဆုံး အမျိုးအစား ဖြစ်သည် — ရွှေချည် ဖြင့် ရက်ထားသော ပန်းပုံစံများ။ ၂မီတာ × ၁၁၀စင်တီ နှင့် ၃မီတာ × ၁၁၀စင်တီ ကြိုတင်ဖြတ်ထား ရရှိနိုင်သည်။ ဈေးနှုန်းများ ဂျပန် ယန်း (JPY)။'),
                        'meta_title' => 'ရွှေပန်းပု (Kinran) ကီမိုနို အထည် ၂မီတာ၊ ၃မီတာ | Fujiba',
                        'meta_description' => 'Kinran ရွှေပန်းပု ကီမိုနို အထည်များ — ၂မီတာ × ၁၁၀စင်တီ နှင့် ၃မီတာ × ၁၁၀စင်တီ ကြိုတင်ဖြတ်ထား။ Obi နှင့် အခမ်းအနား ဝတ်စုံများအတွက်။',
                        'meta_keywords' => 'kinran, ရွှေပန်းပု, formal kimono',
                    ],
                ],
            ],
        ];
    }

    private function cmsPagesCopy(): array
    {
        return [
            'what-is-kimono-fabric' => [
                'en' => [
                    'page_title'       => 'What Is Kimono Fabric? A Beginner\'s Guide to Wagara Textiles',
                    'meta_title'       => 'What Is Kimono Fabric? A Beginner\'s Guide to Wagara Textiles | Fujiba',
                    'meta_description' => 'New to kimono fabric? Learn what wagara textiles are, common widths and materials, and how pre-cut 2m and 3m panels make handmade kimono crafts approachable.',
                    'meta_keywords'    => 'kimono fabric, wagara, Japanese textile guide',
                    'html_content'     => $this->blogContent('what-is-kimono-fabric', 'en'),
                ],
                'ja' => [
                    'page_title'       => '着物生地とは？種類・幅・素材を初心者向けに解説',
                    'meta_title'       => '着物生地とは？種類・幅・素材を初心者向けに解説 | Fujiba',
                    'meta_description' => '着物生地・和柄の基本（幅110cm、素材、代表的な文様）と、ハンドメイドで使いやすい 2m / 3m カット済み生地の選び方を解説します。',
                    'meta_keywords'    => '着物 生地, 和柄 とは, ハンドメイド 着物 入門',
                    'html_content'     => $this->blogContent('what-is-kimono-fabric', 'ja'),
                ],
                'my' => [
                    'page_title'       => 'ကီမိုနို အထည်ဆိုသည်မှာ?',
                    'meta_title'       => 'ကီမိုနို အထည်ဆိုသည်မှာ — Wagara လမ်းညွှန် | Fujiba',
                    'meta_description' => 'ကီမိုနို အထည်နှင့် Wagara ပုံစံများကို စတင်လေ့လာသူများအတွက် လမ်းညွှန်။',
                    'meta_keywords'    => 'ကီမိုနို အထည်, wagara',
                    'html_content'     => $this->blogContent('what-is-kimono-fabric', 'my'),
                ],
            ],
            'how-to-use-pre-cut-kimono-fabric' => [
                'en' => [
                    'page_title'       => 'How to Use Pre-Cut Kimono Fabric',
                    'meta_title'       => 'How to Use Pre-Cut Kimono Fabric – 2m & 3m Project Ideas | Fujiba',
                    'meta_description' => 'Practical guide to working with pre-cut 2m and 3m kimono fabric: pre-wash, bias cuts for obi, project ideas (half-obi, pouches, haori, tapestries).',
                    'meta_keywords'    => 'pre-cut kimono fabric uses, kimono crafts, kimono remake',
                    'html_content'     => $this->blogContent('how-to-use-pre-cut-kimono-fabric', 'en'),
                ],
                'ja' => [
                    'page_title'       => 'カット済み着物生地の使い方',
                    'meta_title'       => 'カット済み着物生地の使い方 — 2m・3m の活用例 | Fujiba',
                    'meta_description' => 'カット済み着物生地の水通し・斜め裁ち・お手入れの基本と、2m / 3m サイズで作れる作品例（半幅帯、ポーチ、羽織、タペストリー）。',
                    'meta_keywords'    => '着物 生地 リメイク 使い方, カット済み 生地 活用',
                    'html_content'     => $this->blogContent('how-to-use-pre-cut-kimono-fabric', 'ja'),
                ],
                'my' => [
                    'page_title'       => 'ကြိုတင်ဖြတ်ထား ကီမိုနို အထည် အသုံးပြုနည်း',
                    'meta_title'       => 'ကြိုတင်ဖြတ်ထား ကီမိုနို အထည် — အသုံးပြုနည်း | Fujiba',
                    'meta_description' => '၂မီတာ၊ ၃မီတာ ကြိုတင်ဖြတ်ထား ကီမိုနို အထည် ဖြင့် လုပ်နိုင်သော လက်လုပ်လုပ်ငန်းများ — half-obi, ပန်းကန်အိတ်, haori, နံရံချိတ်အလှပြင်ပစ္စည်း။',
                    'meta_keywords'    => 'ကီမိုနို အထည် အသုံးပြုနည်း, ၂မီတာ ၃မီတာ',
                    'html_content'     => $this->blogContent('how-to-use-pre-cut-kimono-fabric', 'my'),
                ],
            ],
            '2m-vs-3m-fabric' => [
                'en' => [
                    'page_title'       => '2m vs 3m Kimono Fabric: Which to Choose',
                    'meta_title'       => 'Kimono Fabric 2m vs 3m: Which Size for Your Project? | Fujiba',
                    'meta_description' => 'A quick decision guide: when 2m × 110cm is enough (half-obi, pouches, dolls) and when you need 3m (haori, nine-inch obi, tapestries, large totes).',
                    'meta_keywords'    => 'kimono fabric 2m vs 3m, kimono fabric size guide',
                    'html_content'     => $this->blogContent('2m-vs-3m-fabric', 'en'),
                ],
                'ja' => [
                    'page_title'       => '着物生地 2m と 3m の違い・使い分け',
                    'meta_title'       => '着物生地 2m と 3m はどう違う？用途別の選び方 | Fujiba',
                    'meta_description' => '2m × 110cm と 3m × 110cm はどちらを選ぶべきか。半幅帯・小物に最適な 2m と、九寸帯・羽織・タペストリーに必要な 3m の用途別ガイド。',
                    'meta_keywords'    => '着物 生地 2m 3m 違い, カット済み 生地 サイズ',
                    'html_content'     => $this->blogContent('2m-vs-3m-fabric', 'ja'),
                ],
                'my' => [
                    'page_title'       => '၂မီတာ နှင့် ၃မီတာ ကွာခြားချက်',
                    'meta_title'       => 'ကီမိုနို အထည် ၂မီတာ နှင့် ၃မီတာ ကွာခြားချက် | Fujiba',
                    'meta_description' => '၂မီတာ × ၁၁၀စင်တီ နှင့် ၃မီတာ × ၁၁၀စင်တီ — မည်သည့် အရွယ် ရွေးချယ်ရမည်ကို လွယ်ကူသော လမ်းညွှန်။',
                    'meta_keywords'    => '၂မီတာ ၃မီတာ, ကီမိုနို အထည် အရွယ်',
                    'html_content'     => $this->blogContent('2m-vs-3m-fabric', 'my'),
                ],
            ],
            'japanese-pattern-meanings' => [
                'en' => [
                    'page_title'       => 'Traditional Japanese Patterns and Their Meaning',
                    'meta_title'       => 'Japanese Pattern Meanings: Sakura, Asanoha, Seigaiha & More | Fujiba',
                    'meta_description' => 'The symbolism behind sakura, asanoha (hemp leaf), seigaiha (waves), ichimatsu (checker) and kinran (gold brocade) — and how to choose the right one.',
                    'meta_keywords'    => 'Japanese pattern meaning, wagara meaning, sakura meaning',
                    'html_content'     => $this->blogContent('japanese-pattern-meanings', 'en'),
                ],
                'ja' => [
                    'page_title'       => '日本の伝統文様（和柄）の意味と由来',
                    'meta_title'       => '和柄の意味｜桜・麻の葉・青海波・市松・金襴 | Fujiba',
                    'meta_description' => '桜・麻の葉・青海波・市松・金襴など、代表的な和柄の意味と由来、向いている制作物を解説します。',
                    'meta_keywords'    => '和柄 意味, 桜 意味, 麻の葉 意味, 青海波 意味, 市松 意味',
                    'html_content'     => $this->blogContent('japanese-pattern-meanings', 'ja'),
                ],
                'my' => [
                    'page_title'       => 'ဂျပန်ပုံစံ အဓိပ္ပါယ်များ',
                    'meta_title'       => 'ဂျပန်ပုံစံ အဓိပ္ပါယ်များ — ဆာကူရာ၊ Asanoha နှင့် အခြား | Fujiba',
                    'meta_description' => 'ဆာကူရာ၊ asanoha၊ seigaiha၊ ichimatsu၊ kinran — ဂျပန်ရိုးရာ ပုံစံများ၏ အဓိပ္ပါယ်နှင့် မူရင်း။',
                    'meta_keywords'    => 'ဂျပန်ပုံစံ အဓိပ္ပါယ်, wagara meaning',
                    'html_content'     => $this->blogContent('japanese-pattern-meanings', 'my'),
                ],
            ],
            'best-fabric-for-handmade-kimono' => [
                'en' => [
                    'page_title'       => 'Best Fabric for Handmade Kimono Crafts',
                    'meta_title'       => 'Best Fabric for Handmade Kimono Crafts | Fujiba',
                    'meta_description' => 'Cotton vs polyester vs silk: how to pick the right kimono fabric for your project, with weight, drape and print-quality notes plus top-pick links.',
                    'meta_keywords'    => 'best kimono fabric, handmade kimono fabric, kimono fabric comparison',
                    'html_content'     => $this->blogContent('best-fabric-for-handmade-kimono', 'en'),
                ],
                'ja' => [
                    'page_title'       => 'ハンドメイドにおすすめの着物生地',
                    'meta_title'       => 'ハンドメイドにおすすめの着物生地｜素材別の選び方 | Fujiba',
                    'meta_description' => '綿・ポリエステル・正絹の特徴とおすすめ用途を比較。ハンドメイドで失敗しない着物生地の選び方を素材別に解説します。',
                    'meta_keywords'    => 'ハンドメイド 着物 生地, おすすめ 着物 生地, 綿 ポリエステル 正絹',
                    'html_content'     => $this->blogContent('best-fabric-for-handmade-kimono', 'ja'),
                ],
                'my' => [
                    'page_title'       => 'လက်လုပ် ကီမိုနိုအတွက် အကောင်းဆုံး အထည်',
                    'meta_title'       => 'လက်လုပ် ကီမိုနိုအတွက် အကောင်းဆုံး အထည် | Fujiba',
                    'meta_description' => 'ဝါဂွမ်း၊ ပိုလီဂျက်စတာ၊ ပိုးအထည် — လက်လုပ် ကီမိုနိုအတွက် မည်သည့် အထည်ကို ရွေးချယ်ရမည်ကို လမ်းညွှန်။',
                    'meta_keywords'    => 'အကောင်းဆုံး ကီမိုနို အထည်, လက်လုပ် ကီမိုနို',
                    'html_content'     => $this->blogContent('best-fabric-for-handmade-kimono', 'my'),
                ],
            ],
        ];
    }

    /**
     * Wrap plain-text paragraphs in <p>…</p> with double-newline split, since
     * the storefront renders description as raw HTML.
     */
    private function md(string $text): string
    {
        $paragraphs = preg_split('/\n\s*\n/', trim($text));
        return collect($paragraphs)->map(fn ($p) => '<p>'.nl2br(e(trim($p))).'</p>')->implode("\n");
    }

    /**
     * Each blog page gets a small but real article: H2 sections + paragraphs +
     * cross-links to the kimono-fabric category tree we just seeded. Keeping
     * this inline (rather than splitting into 15 view files) so the seeder is
     * one self-contained file you can re-run.
     */
    private function blogContent(string $slug, string $locale): string
    {
        $sections = $this->blogSections($slug, $locale);
        $out = '';
        foreach ($sections as $h2 => $body) {
            $out .= '<h2>'.e($h2).'</h2>'."\n".$this->md($body)."\n";
        }
        $out .= $this->relatedLinks($locale);
        return $out;
    }

    private function blogSections(string $slug, string $locale): array
    {
        $copy = [
            'what-is-kimono-fabric' => [
                'en' => [
                    'Definition' => 'Kimono fabric (kimono-ji) is the textile traditionally used to make Japan\'s national garment, the kimono. Today it is also widely used in obi, accessories, and home-decor crafts. The fabric is woven on narrow looms — the standard width is roughly 36cm to 40cm — but Fujiba\'s pre-cut panels are 110cm wide for general home-sewing compatibility.',
                    'Common materials' => 'Cotton (washable, beginner-friendly), polyester (colour-stable, affordable), and pure silk (drapes beautifully, dry-clean only). We list material on every product page.',
                    'Pattern families' => 'Sakura (cherry blossom), asanoha (hemp leaf), seigaiha (waves), ichimatsu (checker), kinran (gold brocade) and seasonal florals.',
                    'Where pre-cut helps' => 'A traditional bolt is around 12 metres. Most home projects need 2m or 3m. Pre-cut means no leftover, no yardage maths, and predictable shipping cost.',
                ],
                'ja' => [
                    '着物生地とは' => '着物生地（着物地）とは、日本の伝統衣装である着物を仕立てるために用いられてきた織物の総称です。現在では帯・小物・インテリアにも広く使われています。本来は反幅（約36〜40cm）で織られますが、Fujiba のカット済み生地はハンドメイド向けに 110cm 幅で展開しています。',
                    '主な素材' => '綿（家庭で洗える、初心者向き）、ポリエステル（色落ちが少なく安価）、正絹（最も美しいドレープ、ドライクリーニング推奨）。素材は商品ページに明記しています。',
                    '代表的な文様' => '桜・麻の葉・青海波・市松・金襴、季節の花柄など。',
                    'カット済みの利点' => '反物は約12mあり、家庭用途では余りが多く出ます。2m / 3m のカット済みなら、用尺計算不要・余り無し・送料も予測可能です。',
                ],
                'my' => [
                    'အဓိပ္ပါယ်' => 'ကီမိုနို အထည် (kimono-ji) သည် ဂျပန်ရိုးရာ ဝတ်စုံ ဖြစ်သော ကီမိုနို ချုပ်လုပ်ရန်အတွက် အသုံးပြုသော အထည်အမျိုးအစား ဖြစ်သည်။ ယခုအခါ obi, ဆက်စပ်ပစ္စည်းများနှင့် အိမ်အလှပြင်ပစ္စည်းများတွင်လည်း အသုံးပြုသည်။ Fujiba ၏ ကြိုတင်ဖြတ်ထား panel များသည် ၁၁၀စင်တီ အကျယ်ဖြစ်သည်။',
                    'ပစ္စည်းများ' => 'ဝါဂွမ်း (လျှော်ရလွယ်ကူ၊ စတင်ကာစ)၊ ပိုလီဂျက်စတာ (အရောင်ခိုင်မြဲ၊ စျေးသက်သာ)၊ ပိုးအထည် (အလှဆုံး drape, dry-clean သာ)။',
                    'ပုံစံ အမျိုးအစားများ' => 'ဆာကူရာ၊ asanoha၊ seigaiha၊ ichimatsu၊ kinran နှင့် ရာသီအလိုက် ပန်းပုံစံများ။',
                    'ကြိုတင်ဖြတ်ထား အကျိုးကျေးဇူး' => 'ပုံမှန် ၁၂မီတာ tanmono ထက် ၂မီတာ၊ ၃မီတာ ကြိုတင်ဖြတ်ထား panel များသည် ပိုသင့်တော်ပါသည်။',
                ],
            ],
            'how-to-use-pre-cut-kimono-fabric' => [
                'en' => [
                    'What 2m and 3m can make' => '2m fits half-obi, tsuke-eri collars, pouches, doll garments and small tapestries. 3m fits nine-inch nagoya obi, haori, large totes and full-size tapestries.',
                    'Pre-wash guide' => 'Cotton: machine-wash cold, single load on first wash to test for colour transfer. Polyester: cold rinse. Silk: dry-clean only.',
                    'Cutting on the bias for obi' => 'Bias-cut gives the obi face a softer fall. Mark the bias line with chalk before cutting; allow 1cm seam allowance.',
                    'Care after sewing' => 'Iron on the reverse to preserve gold thread (kinran). Store flat or rolled — never folded along the same line repeatedly.',
                    'Project ideas' => 'See our 2m × 110cm collection for short-fabric projects, and the 3m × 110cm collection for larger pieces.',
                ],
                'ja' => [
                    '2m / 3m で作れるもの' => '2m：半幅帯・つけ襟・ポーチ・人形衣装・小タペストリー。3m：九寸帯・羽織・大判バッグ・大型タペストリー。',
                    '水通しの基本' => '綿：初回は単独で水通し（色移り確認）。ポリエステル：冷水ですすぐ程度。正絹：ドライクリーニングのみ。',
                    '帯の斜め裁ち' => '帯は斜め（バイアス）に裁つことで風合いがやわらかくなります。チャコでバイアス線を引き、縫い代1cmで裁断。',
                    '完成後のお手入れ' => '金襴はアイロンを裏面から。保管は平置きまたは丸めて、同じ折り目を繰り返さないよう注意。',
                    '作品例' => '2m × 110cm カテゴリーで短尺の作品、3m × 110cm カテゴリーで大物制作の例をご覧いただけます。',
                ],
                'my' => [
                    '၂မီတာ၊ ၃မီတာ ဖြင့် လုပ်နိုင်သော အရာများ' => '၂မီတာ — half-obi, lapel, ပန်းကန်အိတ်များ, အရုပ်ဝတ်စုံများ။ ၃မီတာ — Nagoya obi, haori, အိတ်ကြီးများ။',
                    'အပြိုင်လျှော်နည်း' => 'ဝါဂွမ်း — ပထမအကြိမ် သီးသန့် လျှော်ရန်။ ပိုလီဂျက်စတာ — အအေး။ ပိုးအထည် — Dry-clean သာ။',
                    'Obi အတွက် bias cut' => 'Obi မျက်နှာစာ ပိုနူးညံ့စေရန် bias (ထောင့်စောင်း) ဖြတ်ပါ။ ၁ စင်တီ seam allowance ထားပါ။',
                    'ပြုပြင်ထိန်းသိမ်းခြင်း' => 'Kinran ကို နောက်ဘက်မှ ပူပါ။ မှန်ကန်စွာ သိမ်းဆည်းပါ။',
                    'လုပ်ငန်း ဥပမာများ' => '၂မီတာ × ၁၁၀စင်တီ နှင့် ၃မီတာ × ၁၁၀စင်တီ အမျိုးအစား စာမျက်နှာတွင် ဥပမာများ ရှိသည်။',
                ],
            ],
            '2m-vs-3m-fabric' => [
                'en' => [
                    'Quick decision table' => 'Half-obi, pouch, doll, small tapestry → 2m. Nine-inch obi, haori, full tapestry, large tote → 3m. Anywhere in between → 3m gives flexibility.',
                    '2m projects' => 'Most beginner and intermediate handmade projects fit comfortably in 2m × 110cm. The panel sits on a domestic sewing-machine table without folding.',
                    '3m projects' => 'A nine-inch nagoya obi finished face is around 3.6m × 31cm. Cutting from a 3m panel is feasible with careful layout. Haori jackets need 2.4–3m.',
                    'Common pitfalls' => 'Don\'t buy 2m if your project list mentions "obi" — most obi shapes won\'t fit. Don\'t buy 3m if all you need is collars and pouches; you\'ll waste fabric.',
                ],
                'ja' => [
                    'クイック判定表' => '半幅帯・ポーチ・人形・小タペストリー → 2m。九寸帯・羽織・大判タペストリー・大バッグ → 3m。中間サイズなら 3m が安心。',
                    '2m で作れる物' => '初級〜中級のハンドメイドのほとんどは 2m × 110cm で収まります。家庭用ミシンの作業台に折らずに広げられるサイズ。',
                    '3m で作れる物' => '九寸名古屋帯（仕立て上がり 約3.6m × 31cm）の表地は、3m × 110cm から型取り次第で制作可能。羽織は 2.4〜3m 必要。',
                    'よくある失敗' => '帯を作るのに 2m を買ってしまう／小物しか作らないのに 3m を買う、の二つに注意。',
                ],
                'my' => [
                    'အမြန် ရွေးချယ်နည်း' => 'Half-obi, ပန်းကန်အိတ်, အရုပ် → ၂မီတာ။ Nine-inch obi, haori, အိတ်ကြီး → ၃မီတာ။',
                    '၂မီတာ ဖြင့် လုပ်နိုင်သော အရာများ' => 'အစပျိုး၊ အလယ်တန်း လက်လုပ်လုပ်ငန်းအများစု ၂မီတာ × ၁၁၀စင်တီ ဖြင့် လုပ်နိုင်သည်။',
                    '၃မီတာ ဖြင့် လုပ်နိုင်သော အရာများ' => 'Nine-inch nagoya obi (၃.၆မီတာ) ကို ၃မီတာ panel မှ ဖြတ်နိုင်သည်။ Haori — ၂.၄ မှ ၃ မီတာ။',
                    'အများ ပြုလုပ်တတ်သော အမှားများ' => 'Obi လုပ်ရန် ၂မီတာ ဝယ်ခြင်း / ပန်းကန်အိတ်သာ လုပ်မည်ဆိုပြီး ၃မီတာ ဝယ်ခြင်း — ရှောင်ပါ။',
                ],
            ],
            'japanese-pattern-meanings' => [
                'en' => [
                    'Sakura (cherry blossom)' => 'Beauty and the transient nature of life. Worn in spring; appropriate for celebratory pieces.',
                    'Asanoha (hemp leaf)' => 'Healthy growth of children — hemp grows fast and straight. Common on baby kimono and gifts for new parents.',
                    'Seigaiha (waves)' => 'Eternal peace and good fortune flowing endlessly. Suits formal and ceremonial pieces.',
                    'Ichimatsu (checker)' => 'Lasting prosperity (the pattern repeats infinitely). Modern and graphic — also used by the Tokyo 2020 Olympics emblem.',
                    'Kinran (gold brocade)' => 'Highest formality. Appropriate for bridal, ceremonial and altar pieces; not casual.',
                ],
                'ja' => [
                    '桜（さくら）' => '美しさと、無常観・はかなさ。春のテーマ、祝い物に。',
                    '麻の葉（あさのは）' => '子どもの健やかな成長。麻はまっすぐ早く育つことから。出産祝いや子供物に多い。',
                    '青海波（せいがいは）' => '永遠の平穏、絶え間なく続く幸福。フォーマル向け。',
                    '市松（いちまつ）' => '繁栄が途切れず続くこと。モダンでグラフィックな印象。東京五輪エンブレムにも使われた。',
                    '金襴（きんらん）' => '最もフォーマル。婚礼・式典・仏事向き。普段使いには不向き。',
                ],
                'my' => [
                    'ဆာကူရာ' => 'အလှနှင့် ဘဝ၏ ခဏတာသော သဘော။ နွေဦးတွင် ဝတ်ဆင်ခြင်း။',
                    'Asanoha (လျှော်ရွက်)' => 'ကလေး ကျန်းမာစွာ ကြီးထွားခြင်း။ မွေးကင်းစ ကလေးအတွက် လက်ဆောင်များတွင် တွေ့ရသည်။',
                    'Seigaiha (လှိုင်း)' => 'ထာဝရ ငြိမ်းချမ်းခြင်း၊ မဆုံးနိုင်သော ကံကောင်းခြင်း။',
                    'Ichimatsu (ဇကာကွက်)' => 'မဆုံးနိုင်သော စည်ပင်သိုက်မြိုက်မှု။ ခေတ်မီသော ပုံစံ။',
                    'Kinran (ရွှေပန်းပု)' => 'အမြင့်ဆုံး ပုံမှန်အဆင့်။ မင်္ဂလာဆောင်နှင့် အခမ်းအနားများအတွက်။',
                ],
            ],
            'best-fabric-for-handmade-kimono' => [
                'en' => [
                    'Cotton vs polyester vs silk' => 'Cotton: easy to wash, beginner-friendly, less drape. Polyester: colourfast and cheap, slight shine. Silk: best drape, hand-feel and prestige; requires dry cleaning.',
                    'Weight and drape' => 'Lightweight (≤ 100 gsm) suits collars and pouches. Mid-weight (100–180 gsm) is the most versatile. Heavy (180+ gsm) suits obi and outerwear.',
                    'Print quality' => 'Look for clean registration on multi-colour wagara. Gold thread on kinran should sit flat; raised or fraying threads are a quality red flag.',
                    'Pre-cut vs bolt' => 'Pre-cut wins on cost predictability and beginner accessibility. Bolts only make sense if you sew kimono full-time.',
                    'Top picks' => 'See our Sakura, Wagara and Gold Brocade categories for shortlists by use case.',
                ],
                'ja' => [
                    '綿・ポリエステル・正絹' => '綿：扱いやすく初心者向き。ポリエステル：色落ちなく安価、わずかに光沢。正絹：最高のドレープと風合いだが要ドライクリーニング。',
                    '生地の重さとドレープ' => '軽量（〜100g/m²）は襟・小物に。中量（100〜180g/m²）が最も汎用。厚地（180g/m²〜）は帯・羽織向き。',
                    'プリント品質' => '多色和柄は版ズレの少なさをチェック。金襴の金糸は浮かず・ほつれず平らに織られていること。',
                    'カット済み vs 反物' => 'コスト予測と初心者アクセスではカット済みが有利。本職以外は反物の必要なし。',
                    'おすすめ商品' => '桜・和柄・金襴のカテゴリーから用途別の候補をご覧ください。',
                ],
                'my' => [
                    'ပစ္စည်း နှိုင်းယှဉ်ခြင်း' => 'ဝါဂွမ်း — လွယ်ကူ၊ စတင်ကာစ။ ပိုလီဂျက်စတာ — အရောင်ခိုင်မြဲ၊ စျေးသက်သာ။ ပိုးအထည် — အကောင်းဆုံး drape, dry-clean သာ။',
                    'အလေးချိန်နှင့် Drape' => 'ပေါ့ပါးသော အထည် — လည်ဆွဲ၊ ပန်းကန်အိတ်။ အလယ်အလတ် — အသုံးအများဆုံး။ လေးသော အထည် — obi, haori။',
                    'Print အရည်အသွေး' => 'Multi-colour wagara — registration ကြည်ကြည်လင်လင် ရှိရန်။ Kinran ရွှေချည် — အပြားရှိရမည်။',
                    'ကြိုတင်ဖြတ် vs Tanmono' => 'ကြိုတင်ဖြတ်ထား — ကုန်ကျစရိတ် ခန့်မှန်းရလွယ်၊ စတင်ကာစ။',
                    'ထူးခြားသော ပစ္စည်းများ' => 'ဆာကူရာ၊ Wagara နှင့် ရွှေပန်းပု အမျိုးအစားများကို ကြည့်ပါ။',
                ],
            ],
        ];

        return $copy[$slug][$locale] ?? [];
    }

    private function relatedLinks(string $locale): string
    {
        $base = rtrim((string) (core()->getCurrentChannel()->hostname ?? config('app.url')), '/');
        $cats = [
            'kimono-fabric'    => ['en' => 'Kimono Fabric',          'ja' => '着物生地',         'my' => 'ကီမိုနို အထည်'],
            'kimono-fabric-2m' => ['en' => 'Kimono Fabric 2m × 110cm','ja' => '2m × 110cm','my' => '၂မီတာ × ၁၁၀စင်တီ'],
            'kimono-fabric-3m' => ['en' => 'Kimono Fabric 3m × 110cm','ja' => '3m × 110cm','my' => '၃မီတာ × ၁၁၀စင်တီ'],
            'wagara'           => ['en' => 'Traditional Patterns',   'ja' => '和柄',             'my' => 'ရိုးရာပုံစံများ'],
            'sakura'           => ['en' => 'Sakura',                 'ja' => '桜',               'my' => 'ဆာကူရာ'],
            'gold-brocade'     => ['en' => 'Gold Brocade',           'ja' => '金襴',             'my' => 'ရွှေပန်းပု'],
        ];
        $heading = ['en' => 'Related categories', 'ja' => '関連カテゴリー', 'my' => 'သက်ဆိုင်သော အမျိုးအစားများ'][$locale] ?? 'Related';
        $items = '';
        foreach ($cats as $slug => $names) {
            $name = $names[$locale] ?? $names['en'];
            $items .= '<li><a href="'.e($base.'/'.$slug).'">'.e($name).'</a></li>';
        }
        return '<h2>'.e($heading).'</h2><ul>'.$items.'</ul>';
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed real source_name options the user wants:
 *   - ナカ貿易    (en: "naka Trading",  ja: "ナカ貿易")
 *   - Cosmo       (en: "Cosmo",         ja: "コスモ")
 *
 * Idempotent: skips inserts that already exist (matched on admin_name).
 * Translations are upserted per (option_id, locale) — replace if changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        $attrId = DB::table('attributes')->where('code', 'source_name')->value('id');
        if (! $attrId) {
            return; // The earlier migration that creates source_name hasn't run.
        }

        $seeds = [
            // admin_name (the canonical label, used as fallback)
            // → [locale => label, ...]
            'ナカ貿易' => ['en' => 'naka Trading', 'ja' => 'ナカ貿易'],
            'Cosmo'    => ['en' => 'Cosmo',         'ja' => 'コスモ'],
        ];

        foreach ($seeds as $adminName => $translations) {
            $optId = DB::table('attribute_options')
                ->where('attribute_id', $attrId)
                ->where('admin_name', $adminName)
                ->value('id');

            if (! $optId) {
                $optId = DB::table('attribute_options')->insertGetId([
                    'attribute_id' => $attrId,
                    'admin_name'   => $adminName,
                    'sort_order'   => 0,
                ]);
            }

            foreach ($translations as $locale => $label) {
                $exists = DB::table('attribute_option_translations')
                    ->where('attribute_option_id', $optId)
                    ->where('locale', $locale)
                    ->exists();

                if ($exists) {
                    DB::table('attribute_option_translations')
                        ->where('attribute_option_id', $optId)
                        ->where('locale', $locale)
                        ->update(['label' => $label]);
                } else {
                    DB::table('attribute_option_translations')->insert([
                        'attribute_option_id' => $optId,
                        'locale'              => $locale,
                        'label'               => $label,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $attrId = DB::table('attributes')->where('code', 'source_name')->value('id');
        if (! $attrId) return;

        $optIds = DB::table('attribute_options')
            ->where('attribute_id', $attrId)
            ->whereIn('admin_name', ['ナカ貿易', 'Cosmo'])
            ->pluck('id');

        if ($optIds->isNotEmpty()) {
            DB::table('attribute_option_translations')->whereIn('attribute_option_id', $optIds)->delete();
            DB::table('attribute_options')->whereIn('id', $optIds)->delete();
        }
    }
};

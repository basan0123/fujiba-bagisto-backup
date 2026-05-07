<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attributes') || ! Schema::hasTable('attribute_translations')) {
            return;
        }

        $existing = DB::table('attributes')->where('code', 'requires_cool_shipping')->first();

        if ($existing) {
            return;
        }

        $now = now();

        $position = (int) DB::table('attributes')->max('position') + 1;

        $attributeId = DB::table('attributes')->insertGetId([
            'code'                => 'requires_cool_shipping',
            'admin_name'          => 'Requires Cool Shipping',
            'type'                => 'boolean',
            'swatch_type'         => null,
            'validation'          => null,
            'regex'               => null,
            'position'            => $position,
            'is_required'         => 0,
            'is_unique'           => 0,
            'is_filterable'       => 0,
            'is_comparable'       => 0,
            'is_configurable'     => 0,
            'is_user_defined'     => 1,
            'is_visible_on_front' => 0,
            'value_per_locale'    => 0,
            'value_per_channel'   => 0,
            'default_value'       => 0,
            'enable_wysiwyg'      => 0,
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);

        $locales = ['en' => 'Requires Cool Shipping', 'ja' => 'クール便'];

        foreach ($locales as $locale => $label) {
            $exists = DB::table('attribute_translations')
                ->where('attribute_id', $attributeId)
                ->where('locale', $locale)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('attribute_translations')->insert([
                'attribute_id' => $attributeId,
                'locale'       => $locale,
                'name'         => $label,
            ]);
        }

        // Attach to every product family's "Inventory" group so the field
        // shows up on the product edit page automatically. Falls back to the
        // family's first group if no inventory group exists.
        if (! Schema::hasTable('attribute_groups') || ! Schema::hasTable('attribute_group_mappings')) {
            return;
        }

        foreach (DB::table('attribute_families')->pluck('id') as $familyId) {
            $group = DB::table('attribute_groups')
                ->where('attribute_family_id', $familyId)
                ->whereIn('code', ['inventories', 'inventory'])
                ->orderBy('position')
                ->first();

            if (! $group) {
                $group = DB::table('attribute_groups')
                    ->where('attribute_family_id', $familyId)
                    ->orderBy('position')
                    ->first();
            }

            if (! $group) {
                continue;
            }

            $alreadyMapped = DB::table('attribute_group_mappings')
                ->where('attribute_id', $attributeId)
                ->where('attribute_group_id', $group->id)
                ->exists();

            if ($alreadyMapped) {
                continue;
            }

            $position = (int) DB::table('attribute_group_mappings')
                ->where('attribute_group_id', $group->id)
                ->max('position') + 1;

            DB::table('attribute_group_mappings')->insert([
                'attribute_id'       => $attributeId,
                'attribute_group_id' => $group->id,
                'position'           => $position,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('attributes')) {
            return;
        }

        $row = DB::table('attributes')->where('code', 'requires_cool_shipping')->first();

        if (! $row) {
            return;
        }

        if (Schema::hasTable('attribute_translations')) {
            DB::table('attribute_translations')->where('attribute_id', $row->id)->delete();
        }

        DB::table('attributes')->where('id', $row->id)->delete();
    }
};

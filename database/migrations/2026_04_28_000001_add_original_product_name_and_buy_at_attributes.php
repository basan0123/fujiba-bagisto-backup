<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $defaults = [
            'value_per_locale'      => 0,
            'value_per_channel'     => 0,
            'is_required'           => 0,
            'is_unique'             => 0,
            'is_filterable'         => 0,
            'is_configurable'       => 0,
            'is_user_defined'       => 1,
            'is_visible_on_front'   => 0,
            'is_comparable'         => 0,
            'created_at'            => $now,
            'updated_at'            => $now,
        ];

        $attrs = [
            [
                'code'         => 'original_product_name',
                'admin_name'   => 'Original Product Name',
                'type'         => 'text',
                'position'     => 100,
            ],
            [
                'code'         => 'buy_at',
                'admin_name'   => 'Buy At',
                'type'         => 'price',
                'position'     => 101,
            ],
        ];

        foreach ($attrs as $a) {
            $exists = DB::table('attributes')->where('code', $a['code'])->exists();
            if ($exists) {
                continue;
            }
            DB::table('attributes')->insert(array_merge($defaults, $a));
        }

        // Attach to every existing attribute family under the "General" group so
        // the values are editable from the admin product edit screen.
        $families = DB::table('attribute_families')->pluck('id');
        foreach (['original_product_name', 'buy_at'] as $code) {
            $attrId = DB::table('attributes')->where('code', $code)->value('id');
            if (! $attrId) continue;

            foreach ($families as $famId) {
                $groupId = DB::table('attribute_groups')
                    ->where('attribute_family_id', $famId)
                    ->orderBy('position')
                    ->value('id');

                if (! $groupId) continue;

                $linked = DB::table('attribute_group_mappings')
                    ->where('attribute_id', $attrId)
                    ->where('attribute_group_id', $groupId)
                    ->exists();

                if (! $linked) {
                    DB::table('attribute_group_mappings')->insert([
                        'attribute_id'       => $attrId,
                        'attribute_group_id' => $groupId,
                        'position'           => 100,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('attributes')
            ->whereIn('code', ['original_product_name', 'buy_at'])
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('attribute_group_mappings')->whereIn('attribute_id', $ids)->delete();
            DB::table('product_attribute_values')->whereIn('attribute_id', $ids)->delete();
            DB::table('attributes')->whereIn('id', $ids)->delete();
        }
    }
};

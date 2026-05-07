<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $attributes = [
        [
            'code'       => 'buy_at',
            'admin_name' => 'Last Purchased At',
            'type'       => 'datetime',
        ],
        [
            'code'       => 'waste_count',
            'admin_name' => 'Waste Count',
            'type'       => 'integer',
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('attributes')) {
            return;
        }

        foreach ($this->attributes as $attr) {
            $exists = DB::table('attributes')->where('code', $attr['code'])->exists();

            if ($exists) {
                continue;
            }

            $id = DB::table('attributes')->insertGetId([
                'code'                 => $attr['code'],
                'admin_name'           => $attr['admin_name'],
                'type'                 => $attr['type'],
                'is_user_defined'      => 1,
                'is_required'          => 0,
                'is_unique'            => 0,
                'is_filterable'        => 0,
                'is_comparable'        => 0,
                'is_configurable'      => 0,
                'is_visible_on_front'  => 0,
                'value_per_locale'     => 0,
                'value_per_channel'    => 0,
                'enable_wysiwyg'       => 0,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            if (Schema::hasTable('attribute_family_mappings')) {
                $families = DB::table('attribute_families')->pluck('id');
                foreach ($families as $fid) {
                    DB::table('attribute_family_mappings')->updateOrInsert(
                        ['attribute_id' => $id, 'attribute_family_id' => $fid],
                        []
                    );
                }
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('attributes')) {
            return;
        }

        DB::table('attributes')
            ->whereIn('code', array_column($this->attributes, 'code'))
            ->delete();
    }
};

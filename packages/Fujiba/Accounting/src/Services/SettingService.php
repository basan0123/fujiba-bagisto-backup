<?php

namespace Fujiba\Accounting\Services;

use Fujiba\Accounting\Models\Setting;
use Illuminate\Support\Facades\DB;

class SettingService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $row = Setting::find($key);
        return $row ? $row->value : $default;
    }

    public function set(string $key, mixed $value): void
    {
        DB::table('fujiba_settings')->upsert(
            [['key' => $key, 'value' => is_string($value) ? $value : (string) $value, 'updated_at' => now()]],
            ['key'],
            ['value', 'updated_at']
        );
    }
}

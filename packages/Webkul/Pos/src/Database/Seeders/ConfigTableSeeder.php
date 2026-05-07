<?php

namespace Webkul\Pos\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Repositories\ChannelRepository;

class ConfigTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('core_config')->where('code', 'like', 'pos.%')->delete();

        $now = now()->toDateTimeString();

        $channels = app(ChannelRepository::class)->with('locales')->all();

        /*
        |--------------------------------------------------------------------------
        | General Configuration
        |--------------------------------------------------------------------------
        */
        $configurations = [];

        foreach ($channels as $channel) {
            $configurations = array_merge($configurations, [
                [
                    'code'         => 'pos.settings.general.status',
                    'value'        => 0,
                    'channel_code' => $channel->code,
                ], [
                    'code'         => 'pos.settings.general.pos_logo',
                    'value'        => 'configuration/pos-logo.png',
                    'channel_code' => $channel->code,
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | General Configuration for multiple locales
        |--------------------------------------------------------------------------
        */
        foreach ($channels as $channel) {
            foreach ($channel->locales as $locale) {
                $configurations = array_merge($configurations, [
                    [
                        'code'         => 'pos.settings.general.heading_on_login',
                        'value'        => trans(
                            key: 'pos::app.seeders.configurations.heading-on-login',
                            locale: $locale->code
                        ),
                        'channel_code' => $channel->code,
                        'locale_code'  => $locale->code,
                    ], [
                        'code'         => 'pos.settings.general.sub_heading_on_login',
                        'value'        => trans(
                            key: 'pos::app.seeders.configurations.sub-heading-on-login',
                            locale: $locale->code
                        ),
                        'channel_code' => $channel->code,
                        'locale_code'  => $locale->code,
                    ], [
                        'code'         => 'pos.settings.general.footer_content',
                        'value'        => trans(
                            key: 'pos::app.seeders.configurations.footer-content',
                            locale: $locale->code
                        ),
                        'channel_code' => $channel->code,
                        'locale_code'  => $locale->code,
                    ], [
                        'code'         => 'pos.settings.general.footer_note',
                        'value'        => trans(
                            key: 'pos::app.seeders.configurations.footer-note',
                            locale: $locale->code
                        ),
                        'channel_code' => $channel->code,
                        'locale_code'  => $locale->code,
                    ],
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Barcode Configuration
        |--------------------------------------------------------------------------
        */
        foreach ($channels as $channel) {
            $configurations = array_merge($configurations, [
                [
                    'code'         => 'pos.settings.barcode.print_product_name',
                    'value'        => 1,
                    'channel_code' => $channel->code,
                ], [
                    'code'         => 'pos.settings.barcode.width',
                    'value'        => 100,
                    'channel_code' => $channel->code,
                ], [
                    'code'         => 'pos.settings.barcode.height',
                    'value'        => 100,
                    'channel_code' => $channel->code,
                ], [
                    'code'         => 'pos.settings.barcode.generate_with',
                    'value'        => 'sku',
                    'channel_code' => $channel->code,
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Product Configuration
        |--------------------------------------------------------------------------
        */
        foreach ($channels as $channel) {
            $configurations = array_merge($configurations, [
                [
                    'code'         => 'pos.settings.product.allow_sku',
                    'value'        => 1,
                    'channel_code' => $channel->code,
                ],
            ]);
        }

        $configurations = collect($configurations)->map(function ($configuration) use ($now) {
            return array_merge($configuration, [
                'created_at'  => $now,
                'updated_at'  => $now,
                'locale_code' => $configuration['locale_code'] ?? null,
            ]);
        })->all();

        DB::table('core_config')->insert($configurations);
    }
}

<?php

return [
    [
        'key'    => 'sales.inventory.reorder',
        'name'   => 'reorder::app.config.title',
        'info'   => 'reorder::app.config.info',
        'sort'   => 20,
        'fields' => [
            [
                'name'          => 'lead_time_days',
                'title'         => 'reorder::app.config.lead-time-days',
                'info'          => 'reorder::app.config.lead-time-days-info',
                'type'          => 'text',
                'validation'    => 'nullable|numeric|min:0',
                'channel_based' => true,
                'locale_based'  => false,
                'default'       => '7',
            ], [
                'name'          => 'sales_window_days',
                'title'         => 'reorder::app.config.sales-window-days',
                'info'          => 'reorder::app.config.sales-window-days-info',
                'type'          => 'text',
                'validation'    => 'nullable|numeric|min:1',
                'channel_based' => true,
                'locale_based'  => false,
                'default'       => '30',
            ], [
                'name'          => 'safety_stock',
                'title'         => 'reorder::app.config.safety-stock',
                'info'          => 'reorder::app.config.safety-stock-info',
                'type'          => 'text',
                'validation'    => 'nullable|numeric|min:0',
                'channel_based' => true,
                'locale_based'  => false,
                'default'       => '0',
            ],
        ],
    ],
];

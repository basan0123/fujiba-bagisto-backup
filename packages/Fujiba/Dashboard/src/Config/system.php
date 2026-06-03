<?php

/*
|--------------------------------------------------------------------------
| Fujiba — Fiscal year (会計年度 / 期) configuration
|--------------------------------------------------------------------------
| Adds a "Fiscal Year" section under Configure → General so each company
| (tenant) can set the month its business year starts. Japanese companies
| commonly start in April (4); Faburay's default is April. The value is
| stored per-tenant in `core_config` and read via
|   core()->getConfigData('general.fujiba_fiscal_year.settings.start_month')
|
| The config tree is 3-level (group → section → field-group): the core
| SystemConfig builder only renders field cards at the 3rd level (children of
| a section). A 2-level key shows the section page title but NO fields — so
| the actual `fields` MUST live under .settings, not directly on the section.
|
| The dashboard quick-range presets 今期 / 前期 (this / last fiscal year)
| are computed from this month. See FilterEnhancementsListener +
| filter-enhancements.blade.php.
*/

return [
    [
        // Section page (slug general/fujiba_fiscal_year) — no fields itself.
        'key'  => 'general.fujiba_fiscal_year',
        'name' => 'fujiba::app.dashboard.fiscal_year.section_title',
        'info' => 'fujiba::app.dashboard.fiscal_year.section_info',
        'sort' => 4,
    ], [
        // Field card rendered inside that section page.
        'key'  => 'general.fujiba_fiscal_year.settings',
        'name' => 'fujiba::app.dashboard.fiscal_year.settings_title',
        'info' => 'fujiba::app.dashboard.fiscal_year.start_month_info',
        'sort' => 1,
        'fields' => [
            [
                'name'          => 'start_month',
                'title'         => 'fujiba::app.dashboard.fiscal_year.start_month',
                'info'          => 'fujiba::app.dashboard.fiscal_year.start_month_info',
                'type'          => 'select',
                'channel_based' => false,
                'locale_based'  => false,
                'default'       => '4',
                'options'       => [
                    ['title' => 'fujiba::app.dashboard.fiscal_year.months.1',  'value' => '1'],
                    ['title' => 'fujiba::app.dashboard.fiscal_year.months.2',  'value' => '2'],
                    ['title' => 'fujiba::app.dashboard.fiscal_year.months.3',  'value' => '3'],
                    ['title' => 'fujiba::app.dashboard.fiscal_year.months.4',  'value' => '4'],
                    ['title' => 'fujiba::app.dashboard.fiscal_year.months.5',  'value' => '5'],
                    ['title' => 'fujiba::app.dashboard.fiscal_year.months.6',  'value' => '6'],
                    ['title' => 'fujiba::app.dashboard.fiscal_year.months.7',  'value' => '7'],
                    ['title' => 'fujiba::app.dashboard.fiscal_year.months.8',  'value' => '8'],
                    ['title' => 'fujiba::app.dashboard.fiscal_year.months.9',  'value' => '9'],
                    ['title' => 'fujiba::app.dashboard.fiscal_year.months.10', 'value' => '10'],
                    ['title' => 'fujiba::app.dashboard.fiscal_year.months.11', 'value' => '11'],
                    ['title' => 'fujiba::app.dashboard.fiscal_year.months.12', 'value' => '12'],
                ],
            ],
        ],
    ],
];

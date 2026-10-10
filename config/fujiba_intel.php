<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mode
    |--------------------------------------------------------------------------
    |
    | off    — nothing runs.
    | shadow — analyse, plan and store; NEVER publish. (default)
    | assist — same as shadow plus owner-facing suggestions; still never
    |          publishes on its own.
    |
    | Publishing stays with v1 (Fujiba\AutoPoster\Services\PostPublisher).
    | Nothing in the intelligence layer may write to autoposter_queue.
    |
    */
    'mode' => env('FUJIBA_INTEL_MODE', 'shadow'),

    /*
    |--------------------------------------------------------------------------
    | Budget — generation spend
    |--------------------------------------------------------------------------
    |
    | Default 0: no paid generation. CostGuard hard-stops when the running
    | month's logged cost would exceed this. Never raised automatically.
    |
    */
    'monthly_budget_jpy' => (int) env('FUJIBA_AI_MONTHLY_BUDGET_JPY', 0),

    /*
    |--------------------------------------------------------------------------
    | Evidence thresholds — promoting a marketing learning
    |--------------------------------------------------------------------------
    |
    | A hypothesis becomes a confirmed_learning only when ALL of these hold.
    | An AI-written idea always enters as 'hypothesis'; nothing may create a
    | 'confirmed_learning' directly.
    |
    */
    'evidence' => [
        'min_experiments'      => (int) env('FUJIBA_INTEL_MIN_EXPERIMENTS', 2),
        'min_clicks_per_arm'   => (int) env('FUJIBA_INTEL_MIN_CLICKS_PER_ARM', 30),
        'min_attributed_orders' => (int) env('FUJIBA_INTEL_MIN_ORDERS', 5),
        'min_win_probability'  => (float) env('FUJIBA_INTEL_MIN_WIN_PROB', 0.9),
        'default_ttl_days'     => (int) env('FUJIBA_INTEL_MEMORY_TTL_DAYS', 180),
    ],

    /*
    |--------------------------------------------------------------------------
    | Profit Guard
    |--------------------------------------------------------------------------
    |
    | expected_value = reach x ctr x cvr x aov x margin - generation_cost
    |
    | Priors are deliberately conservative and are used only when we have no
    | measured history; anything using them is flagged low_data.
    |
    */
    'profit_guard' => [
        'min_roi'          => (float) env('FUJIBA_INTEL_MIN_ROI', 3.0),
        'priors' => [
            'reach'       => (int) env('FUJIBA_INTEL_PRIOR_REACH', 800),
            'click_rate'  => (float) env('FUJIBA_INTEL_PRIOR_CTR', 0.01),
            'conversion'  => (float) env('FUJIBA_INTEL_PRIOR_CVR', 0.02),
            'aov_jpy'     => (int) env('FUJIBA_INTEL_PRIOR_AOV', 3200),
            'margin'      => (float) env('FUJIBA_INTEL_PRIOR_MARGIN', 0.35),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fabric Integrity Gate
    |--------------------------------------------------------------------------
    |
    | Stricter than bin/v2/gate.py (DE 3.0 / SSIM 0.80, CIE76). Per the master
    | command we keep the stricter value and document both; the v2 renderers'
    | own gate is left alone so another session's work is untouched.
    |
    | 'pattern_inlier_ratio' needs feature matching (ORB/SIFT). OpenCV is NOT
    | installed, so that check reports 'unavailable' rather than silently
    | passing — see docs/intelligence/00-current-state.md §8.
    |
    */
    'fabric_gate' => [
        'ciede2000_mean_max' => (float) env('FUJIBA_INTEL_DE_MEAN_MAX', 2.0),
        'ciede2000_p95_max'  => (float) env('FUJIBA_INTEL_DE_P95_MAX', 5.0),
        'ssim_min'           => (float) env('FUJIBA_INTEL_SSIM_MIN', 0.95),
        'pattern_inlier_min' => (float) env('FUJIBA_INTEL_INLIER_MIN', 0.6),
        'legacy' => [
            'note'     => 'bin/v2/gate.py thresholds, kept for reference only',
            'deltaE76' => 3.0,
            'ssim'     => 0.80,
            'phash'    => 8,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Photography audit — when to recommend a reshoot
    |--------------------------------------------------------------------------
    */
    'photo_audit' => [
        'min_long_side'        => (int) env('FUJIBA_INTEL_MIN_LONG_SIDE', 1600),
        'min_sharpness'        => (float) env('FUJIBA_INTEL_MIN_SHARPNESS', 100.0),
        'min_fabric_visibility' => (float) env('FUJIBA_INTEL_MIN_FABRIC_VIS', 0.60),
    ],

    /*
    | Product evidence research (ProductResearchService). Supplier rows are
    | internal-only (secret supplier codes). Social networks are hard-blocked
    | in PoliteFetcher regardless of this list.
    */
    'research' => [
        'cache_dir'            => env('FUJIBA_INTEL_RESEARCH_CACHE', storage_path('app/intel/research-cache')),
        'min_interval_seconds' => 5,
        'allowed_hosts'        => ['naka-trading.jp', 'ja.wikipedia.org', 'en.wikipedia.org'],
        'naka_base'            => 'https://naka-trading.jp',
        'cosmo_codes'          => ['AP42901', 'AP45103', 'AP52902H'],
    ],
];

<?php

/*
 * Seeds below are applied by `supplier-finder:seed` (idempotent, matched by
 * code/name/pattern). After seeding, the DB rows are the source of truth and
 * admins edit them there — editing this file does not overwrite admin edits.
 */
return [
    'tenant_id'  => env('SUPPLIER_FINDER_TENANT', '1bd1181f-fcd5-4238-a12d-a207d6ea4006'),
    'channel_id' => 1,

    'http' => [
        'user_agent'   => env('SUPPLIER_FINDER_UA', 'FujibaSupplierFinder/1.0 (+https://kimono.fujiba.co.jp; support-f@fujiba.shop)'),
        'min_interval' => 3.0,   // seconds between requests to the same domain
        'timeout'      => 30,
        'max_pages'    => 40,    // per start URL, safety stop
        'backoff_429'  => 120,   // seconds; a second 429 aborts the shop
    ],

    // Private disk root (storage/app/supplier-finder) — nothing here is web-served.
    'storage_dir' => 'supplier-finder',

    'target_price_per_m' => 500,
    // Rough retail→wholesale factor; results are labelled "estimate", never fact.
    'wholesale_estimate_factor' => 0.6,

    'match' => [
        'exact_phash_distance' => 10,   // of 64 bits
        'similar_min_score'    => 0.45,
        'keep_per_product'     => 3,    // top-N of our products stored per crawled item
    ],

    'motif_words' => [
        '鶴', '手毬', '鞠', '牡丹', '菊', '桜', '梅', '椿', '七宝', '扇', '亀甲', '流水', '千代紙', '民芸', '紅型',
        '麻の葉', '青海波', '矢絣', '市松', '雲', '龍', '竜', '鯉', '金魚', '兎', 'うさぎ', '猫', '蝶', '藤', '菖蒲',
        '松', '竹', '笹', '紅葉', '波', '花車', '御所車', '鼓', '貝', '熨斗', '松竹梅', '桔梗', '撫子', '薔薇', '唐草', '絞り', '鹿の子',
    ],
    'style_words' => ['和調', '和柄', '和風', '和モダン', '開花', 'ちりめん', '縮緬', '金彩', 'ラメ', '金', '友禅', '小紋', '古典', '京'],

    'materials' => [
        // canonical => needles
        'シーチング' => ['シーチング', 'sheeting'],
        '細布'     => ['細布'],
        'スケアー'  => ['スケアー', 'スケア'],
        'ブロード'  => ['ブロード', 'broad'],
        'ちりめん'  => ['ちりめん', '縮緬', 'クレープ'],
        'ツイル'    => ['ツイル', 'twill'],
        'オックス'  => ['オックス', 'oxford'],
        'シャンタン' => ['シャンタン'],
        'ムラ糸'    => ['ムラ糸'],
        'ドビー'    => ['ドビー'],
    ],

    'suppliers' => [
        ['name' => '株式会社ナカトレーディング', 'name_en' => 'Naka Trading', 'kind' => 'maker', 'region' => 'Wakayama', 'url' => 'https://naka-trading.jp', 'b2b_route' => 'direct', 'notes' => '現行メイン仕入先。和調プリント (開花プリント/ちりめん)'],
        ['name' => '株式会社トマト', 'name_en' => 'Tomato (Nippori)', 'kind' => 'wholesaler', 'region' => 'Tokyo', 'url' => 'https://www.nippori-tomato-onlineshop.com', 'b2b_route' => 'showroom', 'notes' => '日暮里。卸+小売'],
        ['name' => '山冨商店 YAMATOMI', 'name_en' => 'YAMATOMI', 'kind' => 'wholesaler', 'region' => 'Osaka', 'address' => '船場センタービル5号館', 'url' => 'https://yamatomi.biz', 'b2b_route' => 'direct', 'notes' => 'sz 品番 = 自社品番。ビジネス会員価格あり'],
        ['name' => 'コスモテキスタイル', 'name_en' => 'Cosmo Textile', 'kind' => 'maker', 'region' => 'Osaka', 'b2b_route' => 'direct', 'notes' => 'AP 品番'],
        ['name' => 'コットンこばやし (小林繊維)', 'name_en' => 'Cotton Kobayashi', 'kind' => 'maker', 'region' => 'Osaka', 'address' => '大阪 船場', 'b2b_route' => 'direct', 'notes' => 'KTS 品番'],
        ['name' => '泉陽商事', 'name_en' => 'Senyo Shoji', 'kind' => 'wholesaler', 'notes' => null],
        ['name' => '浅野繊維', 'name_en' => 'Asano Seni', 'kind' => 'wholesaler', 'notes' => null],
        ['name' => '上田布行 (ウエダ)', 'name_en' => 'Ueda Fabric', 'kind' => 'wholesaler', 'region' => 'Kyoto', 'url' => 'https://uedafabric.com', 'notes' => 'あらゆる生地ウエダ'],
        ['name' => 'Quilt Gate', 'name_en' => 'Quilt Gate', 'kind' => 'maker', 'notes' => 'HR 品番'],
    ],

    'code_rules' => [
        ['name' => 'Naka Trading series', 'priority' => 10, 'family' => 'naka', 'supplier' => 'Naka Trading', 'confidence' => 0.90,
            'pattern' => '/^(?<base>(?:B1000|1100|1200|1400|1500|1600|1750|1950|2000|2200|2500|2600|2700|2800|2900|4680)-\d{1,3}N?)(?:-?[A-Z])?$/'],
        ['name' => '京彩華 / 和彩 YK', 'priority' => 20, 'family' => 'yk', 'series' => '京彩華 / 和彩', 'confidence' => 0.60,
            'pattern' => '/^(?<base>YK-?0[1-3](?:-\d{2,3}(?![0-9C]))?)/'],
        ['name' => 'COCO26 金雅乃詩 etc.', 'priority' => 20, 'family' => 'coco26', 'series' => '金雅乃詩 / にゃんこだ和 / モダンだ和', 'confidence' => 0.60,
            'pattern' => '/^(?<base>COCO26\d{2})/'],
        ['name' => 'YAMATOMI 850/88', 'priority' => 30, 'family' => 'yamatomi', 'supplier' => 'YAMATOMI', 'confidence' => 0.80,
            'pattern' => '/^(?<base>850\d{3}|88\d{3})(?:-|$)/'],
        ['name' => 'Cosmo AP', 'priority' => 30, 'family' => 'cosmo', 'supplier' => 'Cosmo Textile', 'confidence' => 0.85,
            'pattern' => '/^(?<base>AP[4-6]\d{4})/'],
        ['name' => 'Cotton Kobayashi KTS', 'priority' => 30, 'family' => 'kts', 'supplier' => 'Cotton Kobayashi', 'confidence' => 0.80,
            'pattern' => '/^(?<base>KTS7\d{3})/'],
        ['name' => 'サザンクロス/ドビー 1026', 'priority' => 40, 'family' => '1026', 'series' => 'サザンクロス / ドビー', 'confidence' => 0.50,
            'pattern' => '/^(?<base>1026-11\d{2})/'],
        ['name' => '綿ちりめんプリント 0606', 'priority' => 40, 'family' => '0606', 'series' => '綿ちりめんプリント (四季彩爛漫)', 'confidence' => 0.60,
            'pattern' => '/^(?<base>0606-\d+)/'],
        ['name' => 'Quilt Gate HR', 'priority' => 40, 'family' => 'quiltgate', 'supplier' => 'Quilt Gate', 'confidence' => 0.70,
            'pattern' => '/^(?<base>HR\d{4}H?)/'],
    ],

    'shops' => [
        ['code' => 'yamayo', 'name' => 'やまよ', 'base_url' => 'https://yamayo-nippori.com', 'adapter' => 'YamayoAdapter', 'enabled' => true, 'region' => 'Tokyo', 'price_type' => 'retail',
            'start_urls' => ['https://yamayo-nippori.com/?mode=grp&gid=2629929', 'https://yamayo-nippori.com/?mode=grp&gid=2629899&sort=n']],
        ['code' => 'tomato', 'name' => '日暮里トマト', 'base_url' => 'https://www.nippori-tomato-onlineshop.com', 'adapter' => 'TomatoAdapter', 'enabled' => true, 'region' => 'Tokyo', 'price_type' => 'retail',
            'supplier' => 'Tomato (Nippori)', 'strip_prefixes' => ['F1-'], 'start_urls' => []],
        ['code' => 'yamatomi', 'name' => 'YAMATOMI', 'base_url' => 'https://yamatomi.biz', 'adapter' => 'YamatomiAdapter', 'enabled' => true, 'region' => 'Osaka', 'price_type' => 'retail',
            'supplier' => 'YAMATOMI', 'strip_prefixes' => ['SZ', 'AS', 'UN'], 'start_urls' => ['https://yamatomi.biz/feature/?feature_id=77']],
        ['code' => 'otsukaya', 'name' => '大塚屋', 'base_url' => 'https://otsukaya.net', 'adapter' => 'OtsukayaBlogAdapter', 'enabled' => true, 'region' => 'Aichi', 'price_type' => 'retail',
            'start_urls' => ['https://otsukaya.net/archives/category/%E5%92%8C%E6%9F%84%E3%83%BB%E5%92%8C%E8%AA%BF%E6%9F%84']],
        ['code' => 'toa', 'name' => 'TOA', 'base_url' => 'https://toa-ltd.com/collections/japanese_pattern'],
        ['code' => 'ueda', 'name' => 'あらゆる生地ウエダ', 'base_url' => 'https://uedafabric.com', 'region' => 'Kyoto', 'supplier' => 'Ueda Fabric'],
        ['code' => 'tajimaya', 'name' => '布の但馬屋', 'base_url' => 'https://www.tajimaya.net', 'adapter' => 'TajimayaAdapter', 'enabled' => true, 'region' => 'Shiga', 'price_type' => 'retail',
            'start_urls' => ['https://www.tajimaya.net/view/category/ct95']],
        ['code' => 'nunogatari', 'name' => '布がたり', 'base_url' => 'https://www.nunogatari.co.jp'],
        ['code' => 'yuzawaya', 'name' => 'ユザワヤ', 'base_url' => 'https://www.yuzawaya.shop/shopbrand/cl_pat_Japanese/'],
        ['code' => 'nomura', 'name' => 'ノムラテーラー', 'base_url' => 'https://www.nomura-tailor.co.jp/fs/tailorshop/c/gr89', 'region' => 'Kyoto'],
    ],
];

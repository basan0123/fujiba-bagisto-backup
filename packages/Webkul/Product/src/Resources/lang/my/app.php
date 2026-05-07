<?php

return [
    'checkout' => [
        'cart' => [
            'integrity' => [
                'qty-missing'   => 'အနည်းဆုံး ကုန်ပစ္စည်းတစ်ခု၏ အရေအတွက်သည် ၁ ထက် ပိုရပါမည်။',
            ],

            'inventory-warning' => 'တောင်းဆိုထားသော အရေအတွက်သည် လက်ကျန်မရှိပါ၊ ခေတ္တစောင့်ပြီးမှ ပြန်လည်ကြိုးစားပေးပါ။',
            'missing-links'     => 'ဤကုန်ပစ္စည်းအတွက် ဒေါင်းလုဒ်ရယူနိုင်သော လင့်ခ်များ (Links) လိုအပ်နေပါသည်။',
            'missing-options'   => 'ဤကုန်ပစ္စည်းအတွက် ရွေးချယ်စရာများ (Options) လိုအပ်နေပါသည်။',
        ],
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'copy-of-:value',
        'copy-of'                       => ':value ၏ မိတ္တူ',
        'variant-already-exist-message' => 'တူညီသော အချက်အလက်များပါဝင်သည့် ကုန်ပစ္စည်းအမျိုးအစားခွဲ (Variant) ရှိနှင့်ပြီးသား ဖြစ်သည်။',
    ],

    'response' => [
        'product-can-not-be-copied' => ':type အမျိုးအစား ကုန်ပစ္စည်းများကို မိတ္တူကူး၍ မရနိုင်ပါ။',
    ],

    'sort-by'  => [
        'options' => [
            'cheapest-first'  => 'စျေးအနည်းဆုံးမှ စတင်ရန်',
            'expensive-first' => 'စျေးအများဆုံးမှ စတင်ရန်',
            'from-a-z'        => 'A မှ Z အစဉ်လိုက်',
            'from-z-a'        => 'Z မှ A အစဉ်လိုက်',
            'latest-first'    => 'အသစ်ဆုံးမှ စတင်ရန်',
            'oldest-first'    => 'အဟောင်းဆုံးမှ စတင်ရန်',
        ],
    ],

    'type'     => [
        'abstract'     => [
            'offers' => ':qty ခု ဝယ်လျှင် တစ်ခုကို :price နှုန်းဖြင့် ရရှိမည်ဖြစ်ပြီး :discount သက်သာမည်။',
        ],

        'bundle'       => 'အစုလိုက် (Bundle)',
        'configurable' => 'စိတ်ကြိုက်ပြင်ဆင်နိုင်သော (Configurable)',
        'downloadable' => 'ဒေါင်းလုဒ်ရယူနိုင်သော (Downloadable)',
        'grouped'      => 'အုပ်စုလိုက် (Grouped)',
        'simple'       => 'ရိုးရိုး (Simple)',
        'virtual'      => 'အွန်လိုင်းဝန်ဆောင်မှု (Virtual)',
    ],
];
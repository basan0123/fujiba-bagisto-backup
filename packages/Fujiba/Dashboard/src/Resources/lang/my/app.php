<?php

return [
    'dashboard' => [

        // ───────────── range_presets (quick-range bar above filters) ─────────────
        'range_presets' => [
            'today'      => 'ယနေ့',
            'yesterday'  => 'မနေ့က',
            'this_month' => 'ဒီလ',
            'last_month' => 'ပြီးခဲ့သည့်လ',
            'last_3m'    => 'နောက်ဆုံး ၃ လ',
            'this_year'  => 'ဒီနှစ်',
            'all'        => 'အားလုံး',
        ],

        // ───────────── common ─────────────
        'common' => [
            'loading'         => 'ဖွင့်နေသည်...',
            'error_load'      => 'ဒေတာ မရရှိပါ',
            'retry'           => 'ပြန်ကြိုးစားရန်',
            'refresh'         => 'ပြန်ဆန်းရန်',
            'view_all'        => 'အားလုံးကြည့်ရန်',
            'show_more'       => 'ပိုပြရန်',
            'hide'            => 'ဖျောက်ရန်',
            'last_updated'    => 'နောက်ဆုံးပြင်ဆင်ချိန် :time',
            'no_data'         => 'ဒေတာ မရှိပါ',
            'today'           => 'ယနေ့',
            'this_month'      => 'ဒီလ',
            'last_month'      => 'ပြီးခဲ့သည့်လ',
            'last_7_days'     => 'နောက်ဆုံး ၇ ရက်',
            'last_30_days'    => 'နောက်ဆုံး ၃၀ ရက်',
            'wrong_channel'   => 'ဤပန်နယ်သည် kimono အတွက်သာ။ ချန်နယ်ပြောင်းပါ။',
        ],

        // ───────────── W1: hero ─────────────
        'hero' => [
            'title'           => 'Today at a glance',
            'orders_today'    => 'Orders Today',
            'revenue_today'   => 'Revenue Today',
            'aov_today'       => 'Average Order Value',
            'no_data'         => 'No orders yet today',
        ],

        // ───────────── W1: business_overview ─────────────
        'business_overview' => [
            'title'              => 'စီးပွားရေး အကျဉ်းချုပ်',
            'subtitle'           => 'ကာလအတွင်း လှုပ်ရှားမှုကိန်းဂဏန်းများ',
            'card_revenue'                  => 'ဝင်ငွေ',
            'card_profit'                   => 'အမြတ်',
            'card_margin'                   => 'အမြတ်ရာခိုင်နှုန်း',
            'card_inventory_cost'           => 'ကုန်ပစ္စည်းကုန်ကျစရိတ်',
            'card_inventory_cost_subtitle'  => 'လက်ကျန် × ဝယ်ဈေး',
            'card_fixed_expenses'           => 'ပုံသေစရိတ်',
            'card_fixed_expenses_subtitle'  => 'ကာလအတွင်း စုစုပေါင်း',
            'card_delivery'                 => 'ပို့ဆောင်ခ',
            'card_delivery_subtitle'        => 'ကာလအတွင်း စုစုပေါင်း',
            'card_open_orders'              => 'မပြီးဆုံးသေးသော အော်ဒါ',
            'card_open_orders_subtitle'     => 'စောင့်ဆိုင်း + ဆောင်ရွက်နေဆဲ',
            'card_open_orders_tooltip'      => 'ငွေတောင်းခံလွှာ ဒါမှမဟုတ် ပို့ဆောင်မှု စောင့်ဆိုင်းနေသော အော်ဒါများ။',
            'card_profit_caveat'            => 'အမြတ်တွင် ပို့ဆောင်ခများ မပါဝင်ပါ။',
            'section_financial'             => 'စွမ်းဆောင်ရည်',
            'section_operational'           => 'လုပ်ငန်းဆောင်ရွက်မှု',
            'section_inventory_cost'        => 'ကုန်ပစ္စည်း + ကုန်ကျစရိတ်',
            'card_aov'                      => 'ပျမ်းမျှ အော်ဒါ',
            'card_aov_subtitle'             => 'အော်ဒါတစ်ခုလျှင်',
            'card_total_orders'             => 'အော်ဒါ စုစုပေါင်း',
            'card_total_customers'          => 'ဝယ်သူ စုစုပေါင်း',
            'card_unpaid_invoices'          => 'မပေးရသေးသော ငွေတောင်းခံလွှာ',
            'card_unpaid_invoices_subtitle' => 'အချိန်မရွေး စုစုပေါင်း',
            'card_total_stock'              => 'လက်ကျန် စုစုပေါင်း',
            'card_total_stock_subtitle'     => 'SKU အားလုံး',
            'profit_label'       => 'Profit',
            'profit_margin'      => ':percent% margin',
            'expenses_hidden'    => 'POS expenses hidden — no POS activity yet',
            'cogs_caveat'        => 'COGS uses current cost prices. Products without cost set contribute zero to COGS — not all sold products are factored in.',
            'period_today'       => 'Today',
            'period_7d'          => 'Last 7 days',
            'period_30d'         => 'Last 30 days',
            'period_mtd'         => 'Month to date',
            'empty_state'        => 'No orders in this period',
        ],

        // ───────────── W11: total_profit (referenced by business_overview) ─────────────
        'total_profit' => [
            'title'              => 'Total profit',
            'subtitle'           => 'Revenue minus all costs',
            'breakdown_revenue'  => 'Revenue',
            'breakdown_cogs'     => 'Cost of goods sold',
            'breakdown_delivery' => 'Delivery fees',
            'breakdown_waste'    => 'Waste',
            'breakdown_expenses' => 'Operating expenses',
            'breakdown_fees'     => 'Gateway fees',
            'empty_state'        => 'No completed orders in this period',
        ],

        // ───────────── W12: delivery_fees ─────────────
        'delivery_fees' => [
            'title'        => 'Delivery fees collected',
            'subtitle'     => 'Shipping charged to customers',
            'empty_state'  => 'No deliveries in this period',
        ],

        // ───────────── W2/W3: inventory_value ─────────────
        'inventory_value' => [
            'title'                 => 'Inventory Value',
            'stock_value'           => 'Stock Value',
            'priced_sku_count'      => 'Priced SKUs',
            'missing_cost'          => 'Missing Cost',
            'missing_cost_badge'    => ':count products without cost',
            'set_cost_link'         => 'Set cost prices',
            'as_of'                 => 'As of :time',
            'empty_state'           => 'No stock data available',
            'all_company_label'     => 'All company',
            'all_company_subtitle'  => 'Across all channels',
            'all_channels_label'    => 'All channels',
        ],

        // ───────────── W4: subscribers ─────────────
        'subscribers' => [
            'title'           => 'Subscribers',
            'total'           => 'Total Subscribers',
            'subscriber_gain' => 'Subscriber Gain',
            'delta_up'        => '+:count',
            'delta_down'      => ':count',
            'delta_flat'      => 'No change',
            'empty_state'     => 'No subscribers yet',
            'empty_hint'      => 'Subscriber growth chart will appear once you have 5 or more subscribers.',
        ],

        // ───────────── W5: payment_methods ─────────────
        'payment_methods' => [
            'title'              => 'ငွေပေးချေနည်းများ',
            'subtitle'           => 'ငွေပေးချေနည်းအလိုက် အော်ဒါများ',
            'col_method'         => 'နည်းလမ်း',
            'col_orders'         => 'အော်ဒါများ',
            'col_gross'          => 'စုစုပေါင်းရောင်းရငွေ',
            'col_fees'           => 'ခန့်မှန်းအခကြေး',
            'col_net'            => 'စုစုပေါင်းအသား',
            'gateway_fees'       => 'Gateway အခကြေးငွေ',
            'fees_estimate_note' => 'အခကြေးငွေသည် သတ်မှတ်ထားသော နှုန်းအပေါ် ခန့်မှန်းထားပါသည်',
            'empty_state'        => 'ရွေးချယ်ထားသော ကာလအတွင်း ငွေပေးချေမှု မရှိပါ',
        ],

        // ───────────── Orders by Region (Japan map) ─────────────
        'orders_by_region' => [
            'title'           => 'ဒေသအလိုက် အော်ဒါများ',
            'subtitle'        => 'ဂျပန်တစ်ခွင် အော်ဒါ ဖြန့်ချိမှု',
            'col_orders'      => 'အော်ဒါများ',
            'col_revenue'     => 'ဝင်ငွေ',
            'top_prefectures' => 'ထိပ်ဆုံး ဒေသများ',
            'empty_state'     => 'ဤကာလအတွင်း ပို့ဆောင်လိပ်စာရှိသော အော်ဒါ မရှိပါ',
        ],

        // ───────────── Sales by Customer State (horizontal bar) ─────────────
        'sales_by_state' => [
            'title'         => 'ဝယ်သူနေရပ်အလိုက် အရောင်း',
            'subtitle'      => 'ပို့ဆောင်ဒေသအလိုက် ဝင်ငွေ အဆင့်',
            'caption'       => 'ဝယ်သူများကို ၎င်းတို့၏ ပို့ဆောင်လိပ်စာဒေသအလိုက် စုစည်းထားပါသည်။',
            'total_revenue' => 'စုစုပေါင်း ဝင်ငွေ',
            'total_orders'  => 'စုစုပေါင်း အော်ဒါ',
            'states_count'  => 'ဒေသများ',
            'orders_short'  => 'အော်ဒါ',
            'unmapped'      => 'မမှတ်တမ်းဝင် (ဒေသမသိ)',
            'empty_state'   => 'ဤကာလအတွင်း ပို့ဆောင်လိပ်စာပါသော အရောင်း မရှိပါ',
        ],

        // ───────────── Cart Activity (live cart feed, top-of-dashboard) ─────────────
        'cart_activity' => [
            'title'           => 'ဈေးခြင်း လှုပ်ရှားမှု',
            'action_converted' => 'အရောင်းအောင်မြင်',
            'action_abandoned' => 'ဈေးခြင်းမှ ဖယ်ရှား',
            'action_active'   => 'ဈေးခြင်းသို့ ပစ္စည်းအသစ်ထည့်',
            'state_all'       => 'အားလုံး',
            'state_active'    => 'လုပ်ဆောင်နေဆဲ',
            'state_abandoned' => 'စွန့်ပစ်ထား',
            'state_converted' => 'ဝယ်ပြီး',
            'guest'           => 'ဧည့်သည်',
            'empty_state'     => 'ဈေးခြင်း လှုပ်ရှားမှု မရှိသေးပါ',
            'cart_id'         => 'ဈေးခြင်း',
            'col_item'        => 'ပစ္စည်း',
            'col_qty'         => 'အရေအတွက်',
            'col_total'       => 'စုစုပေါင်း',
            'sub_total'       => 'အပေါင်းပေါင်း',
            'discount'        => 'လျှော့စျေး',
            'tax'             => 'အခွန်',
            'shipping'        => 'ပို့ဆောင်ခ',
            'grand_total'     => 'အကြီးစားစုစုပေါင်း',
            'cost'             => 'ကုန်ကျစရိတ်',
            'estimated_profit' => 'ခန့်မှန်းအမြတ်',
            'cost_missing'     => 'ပစ္စည်းများတွင် ကုန်ကျစရိတ် မသတ်မှတ်ထားပါ — အမြတ်ကို တွက်ချက်၍မရပါ',
            'detail_failed'   => 'ဈေးခြင်း အသေးစိတ် မရရှိပါ',
            'not_found'       => 'ဈေးခြင်း မတွေ့ရှိပါ',
        ],

        // ───────────── W6: cart_funnel ─────────────
        'cart_funnel' => [
            'title'           => 'Cart Funnel',
            'subtitle'        => 'Conversion path from visit to purchase',
            'stage_visits'    => 'Visits',
            'stage_cart_add'  => 'Added to Cart',
            'stage_checkout'  => 'Reached Checkout',
            'stage_paid'      => 'Paid',
            'stage_completed' => 'Completed',
            'drop_off'        => '-:percent% drop-off',
            'conversion_rate' => 'Overall: :percent%',
            'empty_state'     => 'Not enough data to draw the funnel',
        ],

        // ───────────── W7: cart_status ─────────────
        'cart_status' => [
            'title'             => 'Cart Status',
            'abandoned_cart'    => 'Abandoned Carts',
            'recovered_cart'    => 'Recovered Carts',
            'abandonment_rate'  => 'Abandonment Rate',
            'recovery_rate'     => 'Recovery Rate',
            'value_at_risk'     => 'Value at Risk',
            'recovery_disabled' => 'Recovery tracking not enabled',
            'empty_state'       => 'No abandoned carts in selected range',
        ],

        // ───────────── W8: pos_vs_online ─────────────
        'pos_vs_online' => [
            'title'          => 'POS vs Online',
            'subtitle'       => 'Channel mix for the selected range',
            'pos_label'      => 'POS (in-store)',
            'online_label'   => 'Online',
            'pos_revenue'    => 'POS Revenue',
            'online_revenue' => 'Online Revenue',
            'pos_orders'     => 'POS Orders',
            'online_orders'  => 'Online Orders',
            'mix_percent'    => ':percent% :channel',
            'empty_state'    => 'No POS data yet',
        ],

        // ───────────── W9: total_waste ─────────────
        'total_waste' => [
            'title'             => 'Total Waste',
            'subtitle'          => 'Cost of damaged, expired, or lost stock',
            'total_amount'      => 'Total Amount',
            'total_qty'         => 'Total Quantity',
            'total_stock_items' => 'Items Wasted',
            'top_reason'        => 'Top Reason',
            'empty_state'       => 'No waste recorded',
        ],

        // ───────────── W10: total_expense ─────────────
        'total_expense' => [
            'title'         => 'Total Expense',
            'subtitle'      => 'Recurring + ad-hoc expenses',
            'total_amount'  => 'Total Expense',
            'fixed_portion' => 'Fixed (recurring)',
            'cash_portion'  => 'Cash movements',
            'empty_state'   => 'No expenses recorded',
        ],

    ],
];

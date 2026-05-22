<?php

return [
    'dashboard' => [

        // ───────────── range_presets (quick-range bar above filters) ─────────────
        'range_presets' => [
            'today'      => 'Today',
            'yesterday'  => 'Yesterday',
            'this_month' => 'This month',
            'last_month' => 'Last month',
            'last_3m'    => 'Last 3 months',
            'this_year'  => 'This year',
            'all'        => 'All time',
        ],

        // ───────────── common ─────────────
        'common' => [
            'loading'         => 'Loading...',
            'error_load'      => 'Could not load data',
            'retry'           => 'Retry',
            'refresh'         => 'Refresh',
            'view_all'        => 'View all',
            'show_more'       => 'Show more',
            'hide'            => 'Hide',
            'last_updated'    => 'Last updated :time',
            'no_data'         => 'No data available',
            'today'           => 'Today',
            'this_month'      => 'This month',
            'last_month'      => 'Last month',
            'last_7_days'     => 'Last 7 days',
            'last_30_days'    => 'Last 30 days',
            'wrong_channel'   => 'This panel is kimono-only. Switch channel to view.',
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
            'title'              => 'Business overview',
            'subtitle'           => 'Period-scoped flow metrics',
            'card_revenue'                  => 'Revenue',
            'card_profit'                   => 'Profit',
            'card_margin'                   => 'Margin',
            'card_inventory_cost'           => 'Inventory cost',
            'card_inventory_cost_subtitle'  => 'Stock × cost (now)',
            'card_fixed_expenses'           => 'Fixed expenses',
            'card_fixed_expenses_subtitle'  => 'Period total (manual)',
            'card_delivery'                 => 'Delivery',
            'card_delivery_subtitle'        => 'Period total',
            'card_open_orders'              => 'Open orders',
            'card_open_orders_subtitle'     => 'Pending + processing',
            'card_open_orders_tooltip'      => 'Orders awaiting invoicing or shipment. Pending = order placed, not yet invoiced. Processing = invoiced, not yet shipped/closed.',
            'card_profit_caveat'            => 'Profit excludes carrier shipping costs (not yet tracked). Once carrier costs are entered, profit will be reduced accordingly.',
            'section_financial'             => 'Performance',
            'section_operational'           => 'Operations',
            'section_inventory_cost'        => 'Inventory & cost',
            'card_aov'                      => 'Avg order',
            'card_aov_subtitle'             => 'Per order',
            'card_total_orders'             => 'Total orders',
            'card_total_customers'          => 'Total customers',
            'card_unpaid_invoices'          => 'Unpaid invoices',
            'card_unpaid_invoices_subtitle' => 'All-time tenant total',
            'card_total_stock'              => 'Total stock',
            'card_total_stock_subtitle'     => 'All SKUs total',
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

        // ───────────── Shipping performance (margin + free-ship subsidy) ─────────────
        'shipping_performance' => [
            'title'                => 'Shipping Performance',
            'loading'              => 'Loading…',
            'collected'            => 'Collected from Customers',
            'carrier_cost'         => 'Actual Carrier Cost',
            'margin'               => 'Margin',
            'margin_hint'          => 'Positive = collected exceeds carrier billing',
            'over_n_orders'        => 'across :count orders',
            'fully_reconciled'     => 'All orders reconciled',
            'pending_n'            => ':count orders pending carrier invoice',
            'free_shipping_block'  => 'Free Shipping Applied',
            'free_orders'          => 'Free-Shipped Orders',
            'subsidy'              => 'Shipping Subsidised',
            'avg_subsidy'          => 'Average per Order',
            'by_rule'              => 'By threshold rule',
            'rule_other'           => 'Other / unmatched rule',
            'no_free_orders'       => 'No free-shipping orders in this period.',
            'manage_costs'         => 'Manage carrier costs',
            'import'               => [
                'page_title'        => 'Carrier Shipping Costs',
                'back_to_dashboard' => 'Back to dashboard',
                'upload_section'    => 'Import carrier invoice CSV',
                'csv_file'          => 'CSV file',
                'carrier_code'      => 'Carrier (default)',
                'billing_month'     => 'Billing month (default)',
                'submit'            => 'Import',
                'format_hint'       => 'CSV header: tracking_number, carrier_cost [, billing_month, carrier_code, notes]. Or use order_id instead of tracking_number.',
                'recent_section'    => 'Recent entries',
                'empty_state'       => 'No carrier costs recorded yet.',
                'done'              => 'Imported :count rows.',
                'saved'             => 'Saved.',
                'read_failed'       => 'Could not read CSV file.',
                'empty'             => 'CSV file is empty.',
                'bad_header'        => 'CSV must include either tracking_number or order_id, plus carrier_cost.',
                'unknown_order'     => 'Order not found.',
                'unmatched_header'  => ':count rows could not be matched to an order',
                'last_month_label'  => 'last month :m',
            ],
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
            'title'              => 'Payment Methods',
            'subtitle'           => 'Order share by payment method',
            'col_method'         => 'Method',
            'col_orders'         => 'Orders',
            'col_gross'          => 'Gross Total',
            'col_fees'           => 'Est. Fees',
            'col_net'            => 'Net',
            'gateway_fees'       => 'Gateway Fees',
            'fees_estimate_note' => 'Fees are estimated based on configured rates',
            'empty_state'        => 'No payments in selected range',
        ],

        // ───────────── Orders by Region (Japan map) ─────────────
        'orders_by_region' => [
            'title'           => 'Orders by Region',
            'subtitle'        => 'Order distribution across Japan',
            'col_orders'      => 'Orders',
            'col_revenue'     => 'Revenue',
            'top_prefectures' => 'Top Prefectures',
            'empty_state'     => 'No orders with shipping addresses in this period',
        ],

        // ───────────── Sales by Customer State (horizontal bar) ─────────────
        'sales_by_state' => [
            'title'         => 'Sales by Customer State',
            'subtitle'      => 'Revenue ranked by shipping prefecture',
            'caption'       => 'Customers grouped by their shipping address state.',
            'total_revenue' => 'Total revenue',
            'total_orders'  => 'Total orders',
            'states_count'  => 'States',
            'orders_short'  => 'orders',
            'unmapped'      => 'Unmapped (no/invalid prefecture)',
            'empty_state'   => 'No sales with shipping addresses in this period',
        ],

        // ───────────── Cart Activity (live cart feed, top-of-dashboard) ─────────────
        'cart_activity' => [
            'title'           => 'Cart activities',
            'action_converted' => 'Successful checkout',
            'action_abandoned' => 'Item removed from cart',
            'action_active'   => 'New item added to cart',
            'state_all'       => 'All',
            'state_active'    => 'Active',
            'state_abandoned' => 'Abandoned',
            'state_converted' => 'Converted',
            'guest'           => 'Guest',
            'empty_state'     => 'No cart activity yet',
            'cart_id'         => 'Cart',
            'col_item'        => 'Item',
            'col_qty'         => 'Qty',
            'col_total'       => 'Total',
            'sub_total'       => 'Subtotal',
            'discount'        => 'Discount',
            'tax'             => 'Tax',
            'shipping'        => 'Shipping',
            'grand_total'     => 'Grand total',
            'cost'             => 'Cost',
            'estimated_profit' => 'Estimated profit',
            'cost_missing'     => 'Cost not set on products — profit cannot be estimated',
            'detail_failed'   => 'Could not load cart detail',
            'not_found'       => 'Cart not found',
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
